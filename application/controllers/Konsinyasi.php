<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * MENU KONSINYASI (barang titip jual di customer)
 *
 *  - Daftar SO konsinyasi: berapa yang masih di customer, laku, ditarik, jatuh tempo, tagihan
 *  - Lapor laku   : customer melapor barang terjual -> tagihan SO bertambah
 *  - Tarik barang : barang tidak laku kembali ke gudang (stok + Kartu Stok)
 *  - Tarik otomatis untuk barang yang lewat masa titip (lihat config/konsinyasi.php)
 *
 * Alur lengkap: lihat komentar di models/Konsinyasi_model.php
 */
class Konsinyasi extends MY_Controller
{
    /**
     * HAK AKSES per aksi.
     *   lihat : semua yang ikut proses penjualan
     *   laku  : yang menerima laporan penjualan dari customer
     *   tarik : yang mengurus barang keluar/masuk gudang
     *   batal : koreksi salah input
     */
    private $izin = array(
        'lihat' => array('super_admin', 'atasan', 'finance', 'staff_purchasing', 'staff_gudang'),
        'laku'  => array('super_admin', 'atasan', 'finance', 'staff_purchasing'),
        'tarik' => array('super_admin', 'staff_purchasing', 'staff_gudang'),
        'batal' => array('super_admin', 'atasan'),
    );

    private $tahap_valid = array('aktif', 'jatuh_tempo', 'berjalan', 'belum_kirim', 'selesai', 'semua');

    public function __construct()
    {
        parent::__construct();
        $this->requireRole($this->izin['lihat']);
        $this->load->model('Konsinyasi_model');
        $this->load->helper(array('url', 'form'));
    }

    // MENU: daftar SO konsinyasi
    public function index()
    {
        if (!$this->Konsinyasi_model->siap()) {
            return $this->_belum_siap();
        }
        // Barang yang lewat masa titip ditarik dulu sebelum daftar ditampilkan
        $data['auto'] = $this->Konsinyasi_model->auto_tarik(0, $this->_id_user());

        $tahap = (string) $this->input->get('tahap');
        if (!in_array($tahap, $this->tahap_valid, true)) {
            $tahap = 'aktif';
        }
        $q   = mb_substr(trim((string) $this->input->get('q', true)), 0, 60);
        $res = $this->Konsinyasi_model->daftar(array('tahap' => $tahap, 'q' => $q));

        $data['title']  = translate('i18n_ksy_menu');
        $data['list']   = $res['list'];
        $data['jumlah'] = $res['jumlah'];
        $data['tahap']  = $tahap;
        $data['q']      = $q;
        $this->_view('konsinyasi/index', $data);
    }

    // Detail barang titipan 1 SO
    public function so($id = 0)
    {
        if (!$this->Konsinyasi_model->siap()) {
            return $this->_belum_siap();
        }
        $id = (int) $id;
        $data['auto'] = $this->Konsinyasi_model->auto_tarik($id, $this->_id_user());

        $d = $this->Konsinyasi_model->detail($id);
        if (!$d) {
            show_404();
        }
        if (($d['so']->jenis_penjualan ?? 'biasa') !== 'konsinyasi') {
            $this->session->set_flashdata('error', translate('i18n_ksy_so_bukan_konsinyasi'));
            redirect('sales_order/detail/' . $id);
        }
        $data['title']  = translate('i18n_barang_titipan') . ' ' . $d['so']->no_so;
        $data['d']      = $d;
        $data['aktif']  = in_array($d['so']->status, $this->Konsinyasi_model->status_aktif, true);
        $data['barang'] = $this->Konsinyasi_model->get_barang();
        $data['lokasi'] = $this->Konsinyasi_model->get_lokasi();
        $data['opsi']   = $this->Konsinyasi_model->opsi_valid;
        $this->_view('konsinyasi/so', $data);
    }

    // POST: customer melapor barang laku
    public function laku($id = 0)
    {
        $this->_wajib_post();
        $id = (int) $id;
        $this->_wajib_izin('laku', 'konsinyasi/so/' . $id);
        $tanggal = $this->_tanggal_valid('konsinyasi/so/' . $id);

        $qty = array();
        foreach ((array) $this->input->post('laku') as $k => $v) {
            $qty[(int) $k] = (float) str_replace(',', '.', (string) $v);
        }
        list($ok, $pesan) = $this->Konsinyasi_model->lapor_laku($id, $tanggal, $qty,
            mb_substr(trim((string) $this->input->post('catatan', true)), 0, 255), $this->_id_user());
        $this->session->set_flashdata($ok ? 'success' : 'error', $pesan);
        redirect('konsinyasi/so/' . $id);
    }

    // POST: tarik barang tidak laku ke gudang
    public function tarik($id = 0)
    {
        $this->_wajib_post();
        $id = (int) $id;
        $this->_wajib_izin('tarik', 'konsinyasi/so/' . $id);
        $tanggal = $this->_tanggal_valid('konsinyasi/so/' . $id);

        $barang = (array) $this->input->post('barang');
        $lokasi = (array) $this->input->post('lokasi');
        $opsi   = (array) $this->input->post('opsi');
        $ukuran = (array) $this->input->post('ukuran', true);
        $rows   = array();
        foreach ((array) $this->input->post('tarik') as $k => $v) {
            $q = (float) str_replace(',', '.', (string) $v);
            if ($q <= 0) {
                continue;
            }
            $rows[(int) $k] = array(
                'qty'         => $q,
                'id_barang'   => (int) ($barang[$k] ?? 0),
                'id_location' => (int) ($lokasi[$k] ?? 0),
                'opsi'        => (string) ($opsi[$k] ?? ''),
                'ukuran'      => (string) ($ukuran[$k] ?? ''),
            );
        }
        list($ok, $pesan) = $this->Konsinyasi_model->tarik($id, $tanggal, $rows,
            mb_substr(trim((string) $this->input->post('catatan', true)), 0, 255), $this->_id_user());
        $this->session->set_flashdata($ok ? 'success' : 'error', $pesan);
        redirect('konsinyasi/so/' . $id);
    }

    // POST: batalkan 1 catatan laku / tarik
    public function batal($id_mutasi = 0)
    {
        $this->_wajib_post();
        $m = $this->db->get_where('konsinyasi_mutasi', array('id' => (int) $id_mutasi))->row();
        if (!$m) {
            show_404();
        }
        $this->_wajib_izin('batal', 'konsinyasi/so/' . (int) $m->id_so);
        list($ok, $pesan, $id_so) = $this->Konsinyasi_model->batal_mutasi($m->id, $this->_id_user());
        $this->session->set_flashdata($ok ? 'success' : 'error', $pesan);
        redirect('konsinyasi/so/' . ($id_so ?: (int) $m->id_so));
    }

    // ================= HELPER =================

    private function _view($view, $data)
    {
        $data['page_title']  = $data['title'];
        $data['active_menu'] = 'konsinyasi';
        $data['izin']        = array();
        foreach (array_keys($this->izin) as $k) {
            $data['izin'][$k] = $this->_boleh($k);
        }
        $this->load->view('templates/header', $data);
        $this->load->view($view, $data);
        $this->load->view('templates/footer', $data);
    }

    private function _belum_siap()
    {
        $data['title'] = translate('i18n_ksy_menu');
        $this->_view('konsinyasi/belum_siap', $data);
    }

    // Tanggal laporan: wajib valid & tidak boleh lebih dari hari ini
    private function _tanggal_valid($kembali)
    {
        $t = trim((string) $this->input->post('tanggal'));
        if ($t === '' || !strtotime($t)) {
            $t = date('Y-m-d');
        }
        $t = date('Y-m-d', strtotime($t));
        if ($t > date('Y-m-d')) {
            $this->session->set_flashdata('error', translate('i18n_ksy_tanggal_masa_depan'));
            redirect($kembali);
        }
        return $t;
    }

    private function _boleh($kunci)
    {
        return isset($this->izin[$kunci]) && in_array((string) $this->session->userdata('role'), $this->izin[$kunci], true);
    }

    private function _wajib_izin($kunci, $kembali)
    {
        if (!$this->_boleh($kunci)) {
            $this->session->set_flashdata('error', translate('i18n_anda_tidak_punya_hak_akses_untuk_aksi'));
            redirect($kembali);
        }
    }

    private function _wajib_post()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
    }

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
