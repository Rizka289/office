<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PIUTANG CUSTOMER (menu Keuangan)
 *
 * Alur: Penawaran approved -> Sales Order -> (DP) -> Konfirmasi -> Proses / Kirim (SJ) -> Pelunasan -> SO Selesai
 * Piutang = SO yang belum lunas. Pembayaran dicatat ke tabel yang sama dengan
 * halaman detail SO (sales_order_pembayaran), jadi angka di kedua menu selalu sama.
 */
class Piutang extends MY_Controller
{
    /**
     * HAK AKSES. Ubah di sini saja kalau pembagian tugas berubah.
     *   lihat : buka menu piutang
     *   bayar : catat penerimaan pembayaran dari customer
     */
    private $izin = array(
        'lihat' => array('super_admin', 'atasan', 'finance'),
        'bayar' => array('super_admin', 'finance'),
    );

    public function __construct()
    {
        parent::__construct();
        $this->requireRole($this->izin['lihat']);
        $this->load->model(array('Piutang_model', 'Sales_order_model'));
        $this->load->helper(array('url', 'form'));
        $this->load->library('session');
    }

    // MENU: daftar piutang (SO belum lunas)
    public function index()
    {
        $tahap = (string) $this->input->get('tahap');
        if (!isset($this->Piutang_model->tahap_label[$tahap])) {
            $tahap = 'semua';
        }
        $q = mb_substr(trim((string) $this->input->get('q', true)), 0, 60);

        $semua = $this->Piutang_model->get_piutang(array('q' => $q));

        // Ringkasan dihitung dari SEMUA baris (sebelum filter tahap)
        $ring = array('total' => 0, 'tagihan' => 0, 'diterima' => 0, 'sisa' => 0,
            'tagih_sekarang' => 0, 'jumlah_tahap' => array('dp' => 0, 'proses' => 0, 'tagih' => 0),
            'aging' => array_fill_keys(array_keys($this->Piutang_model->aging_label), 0));
        $per_customer = array();
        foreach ($semua as $r) {
            $ring['total']++;
            $ring['tagihan']        += (float) $r->grand_total;
            $ring['diterima']       += (float) $r->total_bayar;
            $ring['sisa']           += $r->sisa;
            $ring['tagih_sekarang'] += $r->tagih_sekarang;
            $ring['jumlah_tahap'][$r->tahap]++;
            if ($r->tahap === 'tagih') {
                $ring['aging'][$r->aging] += $r->sisa;
            }
            $k = (int) $r->id_customer;
            if (!isset($per_customer[$k])) {
                $per_customer[$k] = array('id' => $k, 'nama' => $r->nama_customer, 'sisa' => 0, 'jumlah' => 0);
            }
            $per_customer[$k]['sisa'] += $r->sisa;
            $per_customer[$k]['jumlah']++;
        }
        usort($per_customer, function ($a, $b) {
            return $b['sisa'] <=> $a['sisa'];
        });

        $data['title']        = 'Piutang Customer';
        $data['tahap']        = $tahap;
        $data['q']            = $q;
        $data['ring']         = $ring;
        $data['per_customer'] = $per_customer;
        $data['piutang']      = $tahap === 'semua' ? $semua
            : array_values(array_filter($semua, function ($r) use ($tahap) {
                return $r->tahap === $tahap;
            }));
        $data['active_menu']  = 'piutang';
        $this->_view('piutang/index', $data);
    }

    // Riwayat pembayaran masuk dari customer
    public function penerimaan()
    {
        $dari   = $this->_tgl($this->input->get('dari')) ?: date('Y-m-01');
        $sampai = $this->_tgl($this->input->get('sampai')) ?: date('Y-m-d');
        if ($dari > $sampai) {
            list($dari, $sampai) = array($sampai, $dari);
        }
        $q = mb_substr(trim((string) $this->input->get('q', true)), 0, 60);

        $data['title']       = 'Penerimaan Pembayaran';
        $data['dari']        = $dari;
        $data['sampai']      = $sampai;
        $data['q']           = $q;
        $data['list']        = $this->Piutang_model->get_penerimaan($dari, $sampai, $q);
        $data['active_menu'] = 'piutang_terima';
        $this->_view('piutang/penerimaan', $data);
    }

    // Kartu piutang per customer (mutasi tagihan & pembayaran + saldo)
    public function customer($id = 0)
    {
        $c = $this->Piutang_model->get_customer((int) $id);
        if (!$c) {
            show_404();
        }
        $data['title']       = 'Kartu Piutang ' . $c->nama;
        $data['customer']    = $c;
        $data['kartu']       = $this->Piutang_model->get_kartu_customer($c->id);
        $data['terbuka']     = $this->Piutang_model->get_piutang(array('id_customer' => $c->id));
        $data['active_menu'] = 'piutang';
        $this->_view('piutang/customer', $data);
    }

    // POST: catat pembayaran customer langsung dari menu piutang
    public function bayar($id_so = 0)
    {
        $this->_wajib_post();
        $kembali = $this->_kembali('piutang');
        if (!$this->_boleh('bayar')) {
            $this->_flash(false, 'Anda tidak punya hak akses untuk mencatat pembayaran.');
            redirect($kembali);
        }
        $id_so = (int) $id_so;
        if (!$this->Sales_order_model->get_so_lengkap($id_so)) {
            show_404();
        }

        $tanggal = $this->_tgl($this->input->post('tanggal'));
        $jenis   = (string) $this->input->post('jenis');
        $nominal = (float) $this->input->post('nominal');
        if ($tanggal === '' || !in_array($jenis, array('dp', 'pelunasan', 'lainnya'), true) || $nominal <= 0) {
            $this->_flash(false, 'Tanggal, jenis, dan nominal pembayaran wajib diisi dengan benar.');
            redirect($kembali);
        }

        list($ok, $pesan) = $this->Sales_order_model->simpan_pembayaran($id_so, array(
            'tanggal'      => $tanggal,
            'jenis'        => $jenis,
            'nominal'      => $nominal,
            'metode'       => mb_substr(trim((string) $this->input->post('metode', true)), 0, 50),
            'no_referensi' => mb_substr(trim((string) $this->input->post('no_referensi', true)), 0, 100),
            'catatan'      => mb_substr(trim((string) $this->input->post('catatan', true)), 0, 255),
        ), $this->_id_user());

        // Sudah terkirim semua + sekarang lunas -> SO otomatis "Selesai"
        if ($ok) {
            $this->load->model('Pemenuhan_model');
            $this->Pemenuhan_model->sinkron_status_so($id_so);
        }
        $this->_flash($ok, $pesan);
        redirect($kembali);
    }

    // ================= HELPER =================

    private function _view($view, $data)
    {
        $data['page_title'] = $data['title'];
        $data['izin']       = array();
        foreach (array_keys($this->izin) as $k) {
            $data['izin'][$k] = $this->_boleh($k);
        }
        $data['tahap_label'] = $this->Piutang_model->tahap_label;
        $data['aging_label'] = $this->Piutang_model->aging_label;
        $this->load->view('templates/header', $data);
        $this->load->view($view, $data);
        $this->load->view('templates/footer', $data);
    }

    private function _boleh($kunci)
    {
        return isset($this->izin[$kunci]) && in_array((string) $this->session->userdata('role'), $this->izin[$kunci], true);
    }

    // Tujuan redirect setelah bayar (hanya halaman di modul piutang)
    private function _kembali($default)
    {
        $k = trim((string) $this->input->post('kembali'));
        return preg_match('#^piutang(/[a-z_]+(/\d+)?)?(\?[a-z_=&%0-9A-Z.+-]*)?$#', $k) ? $k : $default;
    }

    private function _flash($ok, $pesan)
    {
        $this->session->set_flashdata($ok ? 'success' : 'error', $pesan);
    }

    private function _tgl($v)
    {
        $v = trim((string) $v);
        return ($v !== '' && strtotime($v)) ? date('Y-m-d', strtotime($v)) : '';
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
