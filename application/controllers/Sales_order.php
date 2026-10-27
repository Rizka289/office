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
            'q'      => trim($this->input->get('q', true)),
            'dari'   => trim($this->input->get('dari', true)),
            'sampai' => trim($this->input->get('sampai', true)),
        );

        $data['sales_order_list'] = $this->Sales_order_model->get_all_sales_order($filter);

        $total_so       = 0;
        $total_nilai    = 0; // grand_total semua SO aktif
        $total_dp       = 0; // uang muka (jenis = dp) yang sudah masuk
        $total_diterima = 0; // semua pembayaran (DP + pelunasan)
        $total_sisa     = 0; // sisa tagihan

        foreach ($data['sales_order_list'] as $so) {
            if ($so->status === 'batal') {
                continue; // SO batal tidak ikut KPI
            }
            $total_so++;

            $grand    = (float) $so->grand_total;
            $diterima = (float) $so->total_diterima;

            $total_nilai    += $grand;
            $total_dp       += (float) $so->total_dp;
            $total_diterima += $diterima;
            $total_sisa     += isset($so->sisa_bayar) ? (float) $so->sisa_bayar : ($grand - $diterima);
        }

        $data['total_so']       = $total_so;
        $data['total_nilai']    = $total_nilai;
        $data['total_dp']       = $total_dp;
        $data['total_diterima'] = $total_diterima;
        $data['sisa_diterima']  = $total_sisa;

        $data['filter_q']      = $filter['q'];
        $data['filter_dari']   = $filter['dari'];
        $data['filter_sampai'] = $filter['sampai'];

        $this->load->view('templates/header', $data);
        $this->load->view('penjualan/sales_order_index', $data);
        $this->load->view('templates/footer', $data);
    }

    public function detail($id = 0)
    {
        $so = $this->Sales_order_model->get_so_lengkap($id);
        if (!$so) {
            show_404();
        }

        $data['title']       = 'Detail ' . $so['header']->no_so;
        $data['page_title']  = 'Detail Sales Order';
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
        $data['title']          = 'Buat Sales Order';
        $data['active_menu']    = 'sales_order';
        $data['penawaran_list'] = $this->Sales_order_model->get_penawaran_siap();
        $data['no_so']          = $this->Sales_order_model->generate_no(date('Y-m-d'))['no_so'];

        $this->load->view('templates/header', $data);
        $this->load->view('penjualan/sales_order_add', $data);
        $this->load->view('templates/footer', $data);
    }

    // AJAX: data penawaran untuk ditampilkan di form (read-only)
    public function get_penawaran($id = 0)
    {
        $res = $this->Sales_order_model->get_penawaran_lengkap($id);
        if ($res && $res['header']->status !== 'approved') {
            $res = null;
        }
        $this->output->set_content_type('application/json')
            ->set_output(json_encode($res ? $res : array('error' => 'Penawaran tidak ditemukan')));
    }

    // AJAX: preview nomor SO saat tanggal diubah
    public function preview_no()
    {
        $tgl = $this->input->get('tanggal');
        if (!$tgl || !strtotime($tgl)) {
            $tgl = date('Y-m-d');
        }
        $no = $this->Sales_order_model->generate_no($tgl);
        $this->output->set_content_type('application/json')->set_output(json_encode(array('no_so' => $no['no_so'])));
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

        $input = array(
            'tanggal'        => date('Y-m-d', strtotime($tanggal)),
            'no_po_customer' => trim($this->input->post('no_po_customer', true)),
            'tgl_kirim'      => trim($this->input->post('tgl_kirim', true)),
            'alamat_kirim'   => trim($this->input->post('alamat_kirim', true)),
            'termin'         => $termin,
            'dp_persen'      => $dp,
            'catatan'        => trim($this->input->post('catatan', true)),
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
        if (!$this->Sales_order_model->get_so_lengkap($id)) {
            show_404();
        }
        list($ok, $pesan) = $this->Sales_order_model->konfirmasi($id);
        $this->session->set_flashdata($ok ? 'success' : 'error', $pesan);
        redirect('sales_order/detail/' . (int) $id);
    }

    // Batal: hanya jika belum ada barang yang terkirim
    public function batal($id = 0)
    {
        $this->_wajib_post();
        if (!$this->Sales_order_model->get_so_lengkap($id)) {
            show_404();
        }
        list($ok, $pesan) = $this->Sales_order_model->batalkan($id);
        $this->session->set_flashdata($ok ? 'success' : 'error', $pesan);
        redirect('sales_order/detail/' . (int) $id);
    }

    // Catat pembayaran (DP / pelunasan) dari halaman detail
    public function bayar($id = 0)
    {
        $this->_wajib_post();
        if (!$this->Sales_order_model->get_so_lengkap($id)) {
            show_404();
        }
        $tanggal = $this->input->post('tanggal');
        $jenis   = $this->input->post('jenis');
        $nominal = (float) $this->input->post('nominal');

        if (!$tanggal || !strtotime($tanggal) || !in_array($jenis, array('dp', 'pelunasan', 'lainnya'), true) || $nominal <= 0) {
            $this->session->set_flashdata('error', 'Tanggal, jenis, dan nominal pembayaran wajib diisi dengan benar.');
            redirect('sales_order/detail/' . (int) $id);
            return;
        }

        list($ok, $pesan) = $this->Sales_order_model->simpan_pembayaran($id, array(
            'tanggal'      => date('Y-m-d', strtotime($tanggal)),
            'jenis'        => $jenis,
            'nominal'      => $nominal,
            'metode'       => trim($this->input->post('metode', true)),
            'no_referensi' => trim($this->input->post('no_referensi', true)),
            'catatan'      => trim($this->input->post('catatan', true)),
        ), $this->_id_user());

        $this->session->set_flashdata($ok ? 'success' : 'error', $pesan);
        redirect('sales_order/detail/' . (int) $id);
    }

    public function hapus_bayar($id_bayar = 0)
    {
        $this->_wajib_post();
        list($ok, $pesan, $id_so) = $this->Sales_order_model->hapus_pembayaran($id_bayar);
        $this->session->set_flashdata($ok ? 'success' : 'error', $pesan);
        redirect($id_so ? 'sales_order/detail/' . $id_so : 'sales_order');
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