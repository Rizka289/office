<?php defined('BASEPATH') or exit('No direct script access allowed');

class Sales_order extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Sales_order_model');
        $this->load->helper(array('url', 'form'));
        $this->load->library('session');
    }

    public function index()
    {
        $data['title']       = translate('pesanan_penjualan');
        $data['page_title']  = translate('pesanan_penjualan');
        $data['active_menu'] = 'sales_order';

        $filter = array(
            'q'      => trim((string) $this->input->get('q', true)),
            'dari'   => trim((string) $this->input->get('dari', true)),
            'sampai' => trim((string) $this->input->get('sampai', true)),
        );

        // ---- Periode (kotak KPI & tabel selalu memakai periode yang sama) ----
        // ?periode=bulan_ini | bulan_lalu | tahun_ini | semua
        // Kalau dari/sampai diisi manual -> periode 'custom'.
        // Kalau dibuka tanpa filter apa pun -> default bulan ini.
        // Kalau hanya mencari (q) tanpa tanggal -> cari di semua periode.
        $periode = (string) $this->input->get('periode', true);
        if ($filter['dari'] !== '' || $filter['sampai'] !== '') {
            $periode = 'custom';
        } elseif (!in_array($periode, array('bulan_ini', 'bulan_lalu', 'tahun_ini', 'semua'), true)) {
            $periode = ($filter['q'] !== '') ? 'semua' : 'bulan_ini';
        }

        $nama_bulan = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
            'Agustus', 'September', 'Oktober', 'November', 'Desember');
        $tgl_indo = function ($ymd) use ($nama_bulan) {
            $t = strtotime($ymd);
            return $t ? date('j', $t) . ' ' . $nama_bulan[(int) date('n', $t)] . ' ' . date('Y', $t) : $ymd;
        };

        switch ($periode) {
            case 'bulan_ini':
                $filter['dari']   = date('Y-m-01');
                $filter['sampai'] = date('Y-m-t');
                $label_periode    = $nama_bulan[(int) date('n')] . ' ' . date('Y');
                break;
            case 'bulan_lalu':
                $awal             = strtotime('first day of last month');
                $filter['dari']   = date('Y-m-01', $awal);
                $filter['sampai'] = date('Y-m-t', $awal);
                $label_periode    = $nama_bulan[(int) date('n', $awal)] . ' ' . date('Y', $awal);
                break;
            case 'tahun_ini':
                $filter['dari']   = date('Y-01-01');
                $filter['sampai'] = date('Y-12-31');
                $label_periode    = 'Tahun ' . date('Y');
                break;
            case 'semua':
                $label_periode = 'Semua periode';
                break;
            default: // custom
                if ($filter['dari'] !== '' && $filter['sampai'] !== '') {
                    $label_periode = $tgl_indo($filter['dari']) . ' – ' . $tgl_indo($filter['sampai']);
                } elseif ($filter['dari'] !== '') {
                    $label_periode = 'Sejak ' . $tgl_indo($filter['dari']);
                } else {
                    $label_periode = 'Sampai ' . $tgl_indo($filter['sampai']);
                }
        }

        $data['sales_order_list'] = $this->Sales_order_model->get_all_sales_order($filter);

        $total_so       = 0;
        $total_nilai    = 0; // grand_total semua SO aktif
        $total_dp       = 0; // uang muka (jenis = dp) yang sudah masuk
        $total_diterima = 0; // semua pembayaran (DP + pelunasan)
        $total_sisa     = 0; // sisa tagihan
        $jml_so_dp      = 0; // berapa SO yang sudah bayar DP
        $jml_belum_lunas = 0; // berapa SO yang masih ada sisa tagihan

        foreach ($data['sales_order_list'] as $so) {
            if ($so->status === 'batal') {
                continue; // SO batal tidak ikut KPI
            }
            $total_so++;

            $grand    = (float) $so->grand_total;
            $diterima = (float) $so->total_diterima;
            $sisa     = isset($so->sisa_bayar) ? (float) $so->sisa_bayar : ($grand - $diterima);

            $total_nilai    += $grand;
            $total_dp       += (float) $so->total_dp;
            $total_diterima += $diterima;
            $total_sisa     += $sisa;

            if ((float) $so->total_dp > 0) {
                $jml_so_dp++;
            }
            if ($sisa > 0.5) { // toleransi pembulatan
                $jml_belum_lunas++;
            }
        }

        $data['total_so']        = $total_so;
        $data['total_nilai']     = $total_nilai;
        $data['total_dp']        = $total_dp;
        $data['total_diterima']  = $total_diterima;
        $data['sisa_diterima']   = $total_sisa;
        $data['jml_so_dp']       = $jml_so_dp;
        $data['jml_belum_lunas'] = $jml_belum_lunas;
        $data['periode']         = $periode;
        $data['label_periode']   = $label_periode;

        // Uang masuk: menurut TANGGAL BAYAR pada periode yang sama (termasuk pelunasan SO bulan-bulan sebelumnya)
        $data['uang_masuk'] = $this->Sales_order_model->get_uang_masuk($filter['dari'], $filter['sampai'], $filter['q']);
        // Piutang: semua SO belum lunas s/d hari ini, tanpa melihat periode.
        // "Dari periode sebelumnya" = SO yang tanggalnya sebelum awal periode yang dipilih.
        $data['piutang'] = $this->Sales_order_model->get_piutang_berjalan($filter['dari'], $filter['q']);

        $data['filter_q']      = $filter['q'];
        // Kolom tanggal di form hanya terisi kalau user mengisinya sendiri (periode custom).
        // Untuk periode tombol (bulan ini, bulan lalu, dst.) kolom tanggal dibiarkan kosong;
        // periodenya tetap terlihat di label "Ringkasan periode".
        $data['filter_dari']   = ($periode === 'custom') ? $filter['dari'] : '';
        $data['filter_sampai'] = ($periode === 'custom') ? $filter['sampai'] : '';

        $this->load->view('templates/header', $data);
        $this->load->view('penjualan/sales_order_index', $data);
        $this->load->view('templates/footer', $data);
    }

    public function detail($id = 0)
    {
        $id = (int) $id;
        $so = $this->Sales_order_model->get_so_lengkap($id);
        if (!$so) {
            show_404();
        }

        $data['title']       = 'Detail ' . $so['header']->no_so;
        $data['page_title']  = translate('detai_so');
        $data['active_menu'] = 'sales_order';
        $so['pembayaran']    = $this->Sales_order_model->get_pembayaran($id);
        $so['total_dibayar'] = $this->Sales_order_model->total_dibayar($id);
        $data['so']          = $so;

        $this->load->view('templates/header', $data);
        $this->load->view('penjualan/sales_order_detail', $data);
        $this->load->view('templates/footer', $data);
    }

    public function tambah()
    {
        $data['title']          = translate('pesanan_penjualan');
        $data['page_title']     = translate('pesanan_penjualan');
        $data['active_menu']    = 'sales_order';
        $data['penawaran_list'] = $this->Sales_order_model->get_penawaran_siap();
        $no                     = $this->Sales_order_model->generate_no(date('Y-m-d'));
        $data['no_so']          = $no['no_so'];

        $this->load->view('templates/header', $data);
        $this->load->view('penjualan/sales_order_add', $data);
        $this->load->view('templates/footer', $data);
    }

    // AJAX: data penawaran untuk ditampilkan di form (read-only)
    public function get_penawaran($id = 0)
    {
        $id  = (int) $id;
        $res = $id > 0 ? $this->Sales_order_model->get_penawaran_lengkap($id) : null;

        if (!$res || empty($res['header'])) {
            return $this->_json(array('error' => 'Penawaran #' . $id . ' tidak ditemukan.'));
        }
        if (($res['header']->status ?? '') !== 'approved') {
            return $this->_json(array('error' => 'Penawaran ini belum di-approve (status: ' . ($res['header']->status ?? '-') . ').'));
        }
        if (empty($res['items'])) {
            return $this->_json(array('error' => 'Penawaran ini tidak punya item, SO tidak bisa dibuat.'));
        }
        $this->_json($res);
    }

    // Kirim JSON dengan aman: karakter UTF-8 rusak (mis. teks Mandarin dari DB) diganti,
    // supaya json_encode tidak menghasilkan FALSE (respon kosong).
    private function _json($data)
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        $out = json_encode($data, $flags);
        if ($out === false) {
            log_message('error', 'Sales_order JSON gagal: ' . json_last_error_msg());
            $out = json_encode(array('error' => 'Data penawaran gagal dibaca (' . json_last_error_msg() . ').'));
        }
        $this->output->set_content_type('application/json', 'utf-8')->set_output($out);
    }

    // AJAX: preview nomor SO saat tanggal diubah
    public function preview_no()
    {
        $tgl = $this->input->get('tanggal');
        if (!$tgl || !strtotime($tgl)) {
            $tgl = date('Y-m-d');
        }
        $no = $this->Sales_order_model->generate_no($tgl);
        $this->_json(array('no_so' => $no['no_so']));
    }

    public function simpan()
    {
        $this->_wajib_post();

        $id_penawaran = (int) $this->input->post('id_penawaran');
        $tanggal      = $this->input->post('tanggal');
        $termin       = $this->input->post('termin');
        $dp           = (float) $this->input->post('dp_persen');

        $termin_valid = array('100', '50-50', '30-70', 'custom');
        if (!$id_penawaran || !$tanggal || !strtotime($tanggal) || !in_array($termin, $termin_valid, true)) {
            $this->session->set_flashdata('error', 'Penawaran, tanggal, dan termin wajib diisi.');
            redirect('sales_order/tambah');
            return;
        }
        if ($termin === '100') {
            $dp = 0;
        } elseif ($termin === '50-50') {
            $dp = 50;
        } elseif ($termin === '30-70') {
            $dp = 30;
        }
        if ($dp < 0 || $dp > 100) {
            $this->session->set_flashdata('error', 'DP harus antara 0 dan 100 persen.');
            redirect('sales_order/tambah');
            return;
        }

        // tgl_kirim opsional: kosong atau tanggal valid (Y-m-d)
        $tgl_kirim = trim((string) $this->input->post('tgl_kirim', true));
        if ($tgl_kirim !== '') {
            if (!strtotime($tgl_kirim)) {
                $this->session->set_flashdata('error', 'Tanggal target kirim tidak valid.');
                redirect('sales_order/tambah');
                return;
            }
            $tgl_kirim = date('Y-m-d', strtotime($tgl_kirim));
        }

        $input = array(
            'tanggal'        => date('Y-m-d', strtotime($tanggal)),
            'no_po_customer' => mb_substr(trim((string) $this->input->post('no_po_customer', true)), 0, 50),
            'tgl_kirim'      => $tgl_kirim,
            'alamat_kirim'   => trim((string) $this->input->post('alamat_kirim', true)),
            'termin'         => $termin,
            'dp_persen'      => $dp,
            'catatan'        => trim((string) $this->input->post('catatan', true)),
        );

        list($ok, $pesan) = $this->Sales_order_model->simpan_dari_penawaran(
            $id_penawaran,
            $input,
            $this->_id_user()
        );

        if ($ok) {
            $this->session->set_flashdata('success', 'Sales Order ' . $pesan . ' berhasil dibuat.');
            redirect('sales_order');
        } else {
            $this->session->set_flashdata('error', $pesan);
            redirect('sales_order/tambah');
        }
    }

    // Draft -> Dikonfirmasi (hanya jika DP sudah tercatat)
    public function konfirmasi($id = 0)
    {
        $this->_wajib_post();
        $id = (int) $id;
        if (!$this->Sales_order_model->get_so_lengkap($id)) {
            show_404();
        }
        list($ok, $pesan) = $this->Sales_order_model->konfirmasi($id);
        $this->session->set_flashdata($ok ? 'success' : 'error', $pesan);
        redirect('sales_order/detail/' . $id);
    }

    // Batal: hanya jika belum ada barang yang terkirim
    public function batal($id = 0)
    {
        $this->_wajib_post();
        $id = (int) $id;
        if (!$this->Sales_order_model->get_so_lengkap($id)) {
            show_404();
        }
        list($ok, $pesan) = $this->Sales_order_model->batalkan($id);
        $this->session->set_flashdata($ok ? 'success' : 'error', $pesan);
        redirect('sales_order/detail/' . $id);
    }

    // Ubah No. PO customer, target tanggal kirim & catatan dari halaman detail
    public function ubah_info($id = 0)
    {
        $this->_wajib_post();
        $id = (int) $id;
        if (!$this->Sales_order_model->get_so_lengkap($id)) {
            show_404();
        }

        $tgl_kirim = trim((string) $this->input->post('tgl_kirim', true));
        if ($tgl_kirim !== '') {
            if (!strtotime($tgl_kirim)) {
                $this->session->set_flashdata('error', 'Tanggal target kirim tidak valid.');
                redirect('sales_order/detail/' . $id);
                return;
            }
            $tgl_kirim = date('Y-m-d', strtotime($tgl_kirim));
        }

        // Tanggal SO (opsional; kosong = tidak diubah). Tidak boleh lebih dari hari ini.
        $tanggal = trim((string) $this->input->post('tanggal', true));
        if ($tanggal !== '') {
            if (!strtotime($tanggal)) {
                $this->session->set_flashdata('error', 'Tanggal SO tidak valid.');
                redirect('sales_order/detail/' . $id);
                return;
            }
            $tanggal = date('Y-m-d', strtotime($tanggal));
            if ($tanggal > date('Y-m-d')) {
                $this->session->set_flashdata('error', 'Tanggal SO tidak boleh melebihi hari ini.');
                redirect('sales_order/detail/' . $id);
                return;
            }
        }

        list($ok, $pesan) = $this->Sales_order_model->ubah_info($id, array(
            'tanggal'        => $tanggal,
            'no_po_customer' => mb_substr(trim((string) $this->input->post('no_po_customer', true)), 0, 50),
            'tgl_kirim'      => $tgl_kirim,
            'catatan'        => trim((string) $this->input->post('catatan', true)),
        ));
        $this->session->set_flashdata($ok ? 'success' : 'error', $pesan);
        redirect('sales_order/detail/' . $id);
    }

    // Catat pembayaran (DP / pelunasan) dari halaman detail
    public function bayar($id = 0)
    {
        $this->_wajib_post();
        $id = (int) $id;
        if (!$this->Sales_order_model->get_so_lengkap($id)) {
            show_404();
        }
        $tanggal = $this->input->post('tanggal');
        $jenis   = $this->input->post('jenis');
        $nominal = (float) $this->input->post('nominal');

        if (!$tanggal || !strtotime($tanggal) || !in_array($jenis, array('dp', 'pelunasan', 'lainnya'), true) || $nominal <= 0) {
            $this->session->set_flashdata('error', 'Tanggal, jenis, dan nominal pembayaran wajib diisi dengan benar.');
            redirect('sales_order/detail/' . $id);
            return;
        }

        list($ok, $pesan) = $this->Sales_order_model->simpan_pembayaran($id, array(
            'tanggal'      => date('Y-m-d', strtotime($tanggal)),
            'jenis'        => $jenis,
            'nominal'      => $nominal,
            'metode'       => trim((string) $this->input->post('metode', true)),
            'no_referensi' => trim((string) $this->input->post('no_referensi', true)),
            'catatan'      => trim((string) $this->input->post('catatan', true)),
        ), $this->_id_user());

        // PPh tidak dicatat sebagai pembayaran: grand_total dari penawaran sudah dikurangi PPh.
        // SO yang sudah terkirim semua + sekarang lunas -> otomatis jadi "selesai"
        if ($ok) {
            $this->load->model('Pemenuhan_model');
            $this->Pemenuhan_model->sinkron_status_so($id);
        }
        $this->session->set_flashdata($ok ? 'success' : 'error', $pesan);
        redirect('sales_order/detail/' . $id);
    }

    public function hapus_bayar($id_bayar = 0)
    {
        $this->_wajib_post();
        list($ok, $pesan, $id_so) = $this->Sales_order_model->hapus_pembayaran((int) $id_bayar);
        $this->session->set_flashdata($ok ? 'success' : 'error', $pesan);
        redirect($id_so ? 'sales_order/detail/' . $id_so : 'sales_order');
    }

    // Cetak invoice berkop surat (buka di tab baru)
    public function cetak($id = 0)
    {
        $id = (int) $id;
        $so = $this->Sales_order_model->get_so_lengkap($id);
        if (!$so) {
            show_404();
        }
        $so['pembayaran']    = $this->Sales_order_model->get_pembayaran($id);
        $so['total_dibayar'] = $this->Sales_order_model->total_dibayar($id);

        $this->load->view('penjualan/sales_order_invoice', array('so' => $so));
    }

    private function _wajib_post()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
    }

    // Sesuaikan dengan key session login di aplikasi Anda
    private function _id_user()
    {
        foreach (array('id_user', 'user_id', 'id') as $k) {
            $v = $this->session->userdata($k);
            if ($v) {
                return (int) $v;
            }
        }
        return null;
    }
}