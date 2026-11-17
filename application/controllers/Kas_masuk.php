<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEMASUKAN LAIN & MODAL (menu Keuangan)
 *
 * Uang masuk yang TIDAK berasal dari pembayaran customer (SO), misalnya:
 * setoran modal / investasi, pinjaman bank, bunga bank, penjualan aset.
 * Pembayaran customer tetap dicatat di menu Piutang -> Penerimaan Pembayaran.
 * Bukti (slip transfer / kuitansi) disimpan di uploads/kas_masuk/.
 */
class Kas_masuk extends MY_Controller
{
    /**
     * HAK AKSES. Ubah di sini saja kalau pembagian tugas berubah.
     *   lihat : buka menu pemasukan lain
     *   catat : tambah / ubah / hapus pemasukan & kelola kategori
     */
    private $izin = array(
        'lihat' => array('super_admin', 'atasan', 'finance'),
        'catat' => array('super_admin', 'finance'),
    );

    private $metode_list = array('Tunai', 'Transfer', 'E-Wallet');
    private $folder_bukti = 'uploads/kas_masuk/';

    public function __construct()
    {
        parent::__construct();
        $this->requireRole($this->izin['lihat']);
        $this->load->model('Kas_masuk_model');
        $this->load->helper(array('url', 'form'));
        $this->load->library('session');

        if (!$this->Kas_masuk_model->tabel_siap()) {
            show_error(translate('i18n_km_tabel_belum_ada'), 500, translate('i18n_km_judul'));
        }
    }

    // MENU: daftar pemasukan non-penjualan + ringkasan per jenis
    public function index()
    {
        $f = $this->_filter_get();

        $data['title']         = translate('i18n_km_judul');
        $data['f']             = $f;
        $data['list']          = $this->Kas_masuk_model->get_list($f);
        $data['per_jenis']     = $this->Kas_masuk_model->get_per_jenis($f);
        $data['per_kategori']  = $this->Kas_masuk_model->get_per_kategori($f);
        $data['kategori']      = $this->Kas_masuk_model->get_kategori(false);
        $data['kategori_form'] = $this->Kas_masuk_model->get_kategori(true);
        $data['jenis_list']    = $this->Kas_masuk_model->jenis_valid;
        $data['metode_list']   = $this->metode_list;
        $data['active_menu']   = 'kas_masuk';
        $this->_view('kas_masuk/index', $data);
    }

    // POST: tambah ($id = 0) atau ubah pemasukan
    public function simpan($id = 0)
    {
        $this->_wajib_post();
        $this->_wajib_izin('catat');
        $kembali = $this->_kembali();
        $id      = (int) $id;
        $lama    = null;
        if ($id) {
            $lama = $this->Kas_masuk_model->get($id);
            if (!$lama) {
                show_404();
            }
        }

        $tanggal     = $this->_tgl($this->input->post('tanggal'));
        $id_kategori = (int) $this->input->post('id_kategori');
        $keterangan  = mb_substr(trim((string) $this->input->post('keterangan', true)), 0, 255);
        $sumber      = mb_substr(trim((string) $this->input->post('sumber', true)), 0, 100);
        $nominal     = round((float) $this->input->post('nominal'), 2);
        $metode      = (string) $this->input->post('metode');
        $rekening    = mb_substr(trim((string) $this->input->post('rekening_tujuan', true)), 0, 100);

        $kat = $this->Kas_masuk_model->get_kategori_by_id($id_kategori);
        $err = array();
        if ($tanggal === '') {
            $err[] = translate('tanggal');
        } elseif ($tanggal > date('Y-m-d')) {
            $this->_flash(false, translate('i18n_km_tanggal_masa_depan'));
            redirect($kembali);
        }
        if (!$kat || (!$kat->aktif && (!$lama || (int) $lama->id_kategori !== $id_kategori))) {
            $err[] = translate('i18n_kategori_2');
        }
        if ($sumber === '') {
            $err[] = translate('i18n_km_sumber');
        }
        if ($keterangan === '') {
            $err[] = translate('deskripsi');
        }
        if ($nominal <= 0) {
            $err[] = translate('i18n_nominal_2');
        }
        if (!in_array($metode, $this->metode_list, true)) {
            $err[] = translate('i18n_metode');
        }
        if ($err) {
            $this->_flash(false, (translate('i18n_lengkapi_dulu') . ' ') . implode(', ', $err) . '.');
            redirect($kembali);
        }

        $d = array(
            'tanggal'         => $tanggal,
            'id_kategori'     => $id_kategori,
            'sumber'          => $sumber,
            'keterangan'      => $keterangan,
            'nominal'         => $nominal,
            'metode'          => $metode,
            'rekening_tujuan' => $rekening !== '' ? $rekening : null,
        );

        $ada_file = !empty($_FILES['bukti']['name']);
        if ($ada_file) {
            list($ok, $hasil) = $this->_upload_bukti('bukti');
            if (!$ok) {
                $this->_flash(false, $hasil);
                redirect($kembali);
            }
            $d['bukti'] = $hasil;
        }

        list($ok, $pesan) = $this->Kas_masuk_model->simpan($id, $d, $this->_id_user());
        if ($ok && $ada_file && $lama && $lama->bukti) {
            $this->_hapus_file($lama->bukti);          // bukti lama diganti
        } elseif (!$ok && $ada_file) {
            $this->_hapus_file($d['bukti']);           // simpan gagal -> buang file baru
        }
        $this->_flash($ok, $pesan);
        redirect($kembali);
    }

    // POST: hapus pemasukan + buktinya
    public function hapus($id = 0)
    {
        $this->_wajib_post();
        $this->_wajib_izin('catat');
        $m = $this->Kas_masuk_model->get((int) $id);
        if (!$m) {
            show_404();
        }
        if ($this->Kas_masuk_model->hapus($m->id)) {
            if ($m->bukti) {
                $this->_hapus_file($m->bukti);
            }
            $this->_flash(true, (translate('i18n_km_pemasukan') . ' ') . $m->no_bukti . (' ' . translate('i18n_dihapus')));
        } else {
            $this->_flash(false, translate('i18n_km_gagal_hapus'));
        }
        redirect($this->_kembali());
    }

    // Kelola kategori pemasukan
    public function kategori()
    {
        $data['title']       = translate('i18n_km_kategori_judul');
        $data['kategori']    = $this->Kas_masuk_model->get_kategori(false);
        $data['jenis_list']  = $this->Kas_masuk_model->jenis_valid;
        $data['active_menu'] = 'kas_masuk';
        $this->_view('kas_masuk/kategori', $data);
    }

    // POST: tambah / ubah kategori (nama + jenis)
    public function kategori_simpan($id = 0)
    {
        $this->_wajib_post();
        $this->_wajib_izin('catat');
        $id    = (int) $id;
        $nama  = mb_substr(trim(preg_replace('/\s+/', ' ', (string) $this->input->post('nama', true))), 0, 100);
        $jenis = (string) $this->input->post('jenis');
        if ($nama === '') {
            $this->_flash(false, translate('i18n_nama_kategori_wajib_diisi'));
        } elseif (!in_array($jenis, $this->Kas_masuk_model->jenis_valid, true)) {
            $this->_flash(false, (translate('i18n_lengkapi_dulu') . ' ') . translate('i18n_km_jenis') . '.');
        } elseif ($id && !$this->Kas_masuk_model->get_kategori_by_id($id)) {
            show_404();
        } elseif ($this->Kas_masuk_model->nama_kategori_dipakai($nama, $id)) {
            $this->_flash(false, translate('i18n_kategori') . $nama . translate('i18n_q_sudah_ada'));
        } else {
            $ok = $this->Kas_masuk_model->simpan_kategori($id, $nama, $jenis);
            $this->_flash($ok, $ok ? translate('i18n_kategori') . $nama . translate('i18n_q_disimpan') : translate('i18n_gagal_menyimpan_kategori'));
        }
        redirect('kas_masuk/kategori');
    }

    // POST: aktif / nonaktif kategori
    public function kategori_aktif($id = 0)
    {
        $this->_wajib_post();
        $this->_wajib_izin('catat');
        $k = $this->Kas_masuk_model->get_kategori_by_id((int) $id);
        if (!$k) {
            show_404();
        }
        $this->Kas_masuk_model->set_aktif_kategori($k->id, !$k->aktif);
        $this->_flash(true, translate('i18n_kategori') . $k->nama . '" ' . ($k->aktif ? translate('i18n_dinonaktifkan') : translate('i18n_diaktifkan_kembali')));
        redirect('kas_masuk/kategori');
    }

    // POST: hapus kategori (hanya yang belum dipakai)
    public function kategori_hapus($id = 0)
    {
        $this->_wajib_post();
        $this->_wajib_izin('catat');
        list($ok, $pesan) = $this->Kas_masuk_model->hapus_kategori((int) $id);
        $this->_flash($ok, $pesan);
        redirect('kas_masuk/kategori');
    }

    // ================= HELPER =================

    private function _view($view, $data)
    {
        $data['page_title'] = $data['title'];
        $data['izin']       = array();
        foreach (array_keys($this->izin) as $k) {
            $data['izin'][$k] = $this->_boleh($k);
        }
        $this->load->view('templates/header', $data);
        $this->load->view($view, $data);
        $this->load->view('templates/footer', $data);
    }

    private function _filter_get()
    {
        $dari   = $this->_tgl($this->input->get('dari')) ?: date('Y-01-01');
        $sampai = $this->_tgl($this->input->get('sampai')) ?: date('Y-m-d');
        if ($dari > $sampai) {
            list($dari, $sampai) = array($sampai, $dari);
        }
        $jenis = (string) $this->input->get('jenis');
        return array(
            'dari'        => $dari,
            'sampai'      => $sampai,
            'id_kategori' => (int) $this->input->get('kategori'),
            'jenis'       => in_array($jenis, $this->Kas_masuk_model->jenis_valid, true) ? $jenis : '',
            'q'           => mb_substr(trim((string) $this->input->get('q', true)), 0, 60),
        );
    }

    // Upload bukti (jpg/png/webp/pdf, maks 5MB). Return: array(ok, path relatif | pesan error)
    private function _upload_bukti($field)
    {
        $dir = FCPATH . $this->folder_bukti;
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return array(false, (translate('i18n_folder') . ' ') . $this->folder_bukti . (' ' . translate('i18n_tidak_bisa_dibuat_buat_manual_beri')));
        }
        $this->load->library('upload');
        $this->upload->initialize(array(
            'upload_path'   => $dir,
            'allowed_types' => 'jpg|jpeg|png|webp|pdf',
            'max_size'      => 5120,
            'file_name'     => 'km_' . date('YmdHis') . '_' . mt_rand(100, 999),
            'overwrite'     => false,
        ), true);
        if (!$this->upload->do_upload($field)) {
            return array(false, (translate('i18n_upload_foto_gagal') . ' ') . strip_tags($this->upload->display_errors('', '')) . (' ' . translate('i18n_jpg_png_webp_pdf_maks_5mb')));
        }
        return array(true, $this->folder_bukti . $this->upload->data('file_name'));
    }

    // Hapus file hanya kalau memang ada di folder bukti kas masuk
    private function _hapus_file($rel)
    {
        if (strpos($rel, $this->folder_bukti) === 0 && strpos($rel, '..') === false && is_file(FCPATH . $rel)) {
            @unlink(FCPATH . $rel);
        }
    }

    private function _boleh($kunci)
    {
        return isset($this->izin[$kunci]) && in_array((string) $this->session->userdata('role'), $this->izin[$kunci], true);
    }

    private function _wajib_izin($kunci)
    {
        if (!$this->_boleh($kunci)) {
            $this->_flash(false, translate('i18n_anda_tidak_punya_hak_akses_untuk_aksi'));
            redirect('kas_masuk');
        }
    }

    // Tujuan redirect setelah aksi (hanya halaman kas_masuk + query filter)
    private function _kembali()
    {
        $k = trim((string) $this->input->post('kembali'));
        return preg_match('#^kas_masuk(\?[a-z_=&%0-9A-Z.+-]*)?$#', $k) ? $k : 'kas_masuk';
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
        foreach (array('user_id', 'id_user', 'id') as $k) {
            $v = $this->session->userdata($k);
            if ($v) {
                return (int) $v;
            }
        }
        return null;
    }
}
