<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BIAYA OPERASIONAL / PENGELUARAN KANTOR (menu Keuangan)
 *
 * Uang keluar yang TIDAK lewat PO supplier: listrik, gaji, ATK, bensin, sewa, dll.
 * Dicatat langsung oleh finance (tanpa approval), wajib lampir foto nota / bukti.
 * Foto disimpan di uploads/biaya/.
 */
class Biaya extends MY_Controller
{
    /**
     * HAK AKSES. Ubah di sini saja kalau pembagian tugas berubah.
     *   lihat : buka menu biaya & rekap
     *   catat : tambah / ubah / hapus pengeluaran & kelola kategori
     */
    private $izin = array(
        'lihat' => array('super_admin', 'atasan', 'finance'),
        'catat' => array('super_admin', 'finance'),
    );

    private $metode_list = array('Tunai', 'Transfer', 'Kartu Debit', 'Kartu Kredit', 'E-Wallet');
    private $folder_foto = 'uploads/biaya/';

    public function __construct()
    {
        parent::__construct();
        $this->requireRole($this->izin['lihat']);
        $this->load->model('Biaya_model');
        $this->load->helper(array('url', 'form'));
        $this->load->library('session');
    }

    // MENU: daftar pengeluaran + ringkasan per kategori
    public function index()
    {
        $f = $this->_filter_get();

        $data['title']        = 'Biaya Operasional';
        $data['f']            = $f;
        $data['list']         = $this->Biaya_model->get_list($f);
        $data['per_kategori'] = $this->Biaya_model->get_per_kategori($f);
        $data['kategori']     = $this->Biaya_model->get_kategori(false);
        $data['kategori_form'] = $this->Biaya_model->get_kategori(true);
        $data['metode_list']  = $this->metode_list;
        $data['active_menu']  = 'biaya';
        $this->_view('biaya/index', $data);
    }

    // POST: tambah ($id = 0) atau ubah pengeluaran
    public function simpan($id = 0)
    {
        $this->_wajib_post();
        $this->_wajib_izin('catat');
        $kembali = $this->_kembali();
        $id      = (int) $id;
        $lama    = null;
        if ($id) {
            $lama = $this->Biaya_model->get($id);
            if (!$lama) {
                show_404();
            }
        }

        $tanggal     = $this->_tgl($this->input->post('tanggal'));
        $id_kategori = (int) $this->input->post('id_kategori');
        $keterangan  = mb_substr(trim((string) $this->input->post('keterangan', true)), 0, 255);
        $nominal     = round((float) $this->input->post('nominal'), 2); // input type=number
        $metode      = (string) $this->input->post('metode');
        $dibayar_ke  = mb_substr(trim((string) $this->input->post('dibayar_ke', true)), 0, 100);

        $kat = $this->Biaya_model->get_kategori_by_id($id_kategori);
        $err = array();
        if ($tanggal === '') {
            $err[] = 'tanggal';
        } elseif ($tanggal > date('Y-m-d')) {
            $this->_flash(false, 'Tanggal pengeluaran tidak boleh di masa depan.');
            redirect($kembali);
        }
        if (!$kat || (!$kat->aktif && (!$lama || (int) $lama->id_kategori !== $id_kategori))) {
            $err[] = 'kategori';
        }
        if ($keterangan === '') {
            $err[] = 'keterangan';
        }
        if ($nominal <= 0) {
            $err[] = 'nominal';
        }
        if (!in_array($metode, $this->metode_list, true)) {
            $err[] = 'metode';
        }
        $ada_file = !empty($_FILES['foto']['name']);
        if (!$id && !$ada_file) {
            $err[] = 'foto nota';
        }
        if ($err) {
            $this->_flash(false, 'Lengkapi dulu: ' . implode(', ', $err) . '.');
            redirect($kembali);
        }

        $d = array(
            'tanggal'     => $tanggal,
            'id_kategori' => $id_kategori,
            'keterangan'  => $keterangan,
            'dibayar_ke'  => $dibayar_ke !== '' ? $dibayar_ke : null,
            'nominal'     => $nominal,
            'metode'      => $metode,
        );

        if ($ada_file) {
            list($ok, $hasil) = $this->_upload_foto('foto');
            if (!$ok) {
                $this->_flash(false, $hasil);
                redirect($kembali);
            }
            $d['foto'] = $hasil;
        }

        list($ok, $pesan) = $this->Biaya_model->simpan($id, $d, $this->_id_user());
        if ($ok && $ada_file && $lama && $lama->foto) {
            $this->_hapus_file($lama->foto);          // foto lama diganti
        } elseif (!$ok && $ada_file) {
            $this->_hapus_file($d['foto']);           // simpan gagal -> buang file baru
        }
        $this->_flash($ok, $pesan);
        redirect($kembali);
    }

    // POST: hapus pengeluaran + fotonya
    public function hapus($id = 0)
    {
        $this->_wajib_post();
        $this->_wajib_izin('catat');
        $b = $this->Biaya_model->get((int) $id);
        if (!$b) {
            show_404();
        }
        if ($this->Biaya_model->hapus($b->id)) {
            if ($b->foto) {
                $this->_hapus_file($b->foto);
            }
            $this->_flash(true, 'Pengeluaran ' . $b->no_bukti . ' dihapus.');
        } else {
            $this->_flash(false, 'Gagal menghapus pengeluaran.');
        }
        redirect($this->_kembali());
    }

    // Kelola kategori biaya
    public function kategori()
    {
        $data['title']       = 'Kategori Biaya';
        $data['kategori']    = $this->Biaya_model->get_kategori(false);
        $data['active_menu'] = 'biaya';
        $this->_view('biaya/kategori', $data);
    }

    // POST: tambah / ganti nama kategori
    public function kategori_simpan($id = 0)
    {
        $this->_wajib_post();
        $this->_wajib_izin('catat');
        $id   = (int) $id;
        $nama = mb_substr(trim(preg_replace('/\s+/', ' ', (string) $this->input->post('nama', true))), 0, 100);
        if ($nama === '') {
            $this->_flash(false, 'Nama kategori wajib diisi.');
        } elseif ($id && !$this->Biaya_model->get_kategori_by_id($id)) {
            show_404();
        } elseif ($this->Biaya_model->nama_kategori_dipakai($nama, $id)) {
            $this->_flash(false, 'Kategori "' . $nama . '" sudah ada.');
        } else {
            $ok = $this->Biaya_model->simpan_kategori($id, $nama);
            $this->_flash($ok, $ok ? 'Kategori "' . $nama . '" disimpan.' : 'Gagal menyimpan kategori.');
        }
        redirect('biaya/kategori');
    }

    // POST: aktif / nonaktif kategori
    public function kategori_aktif($id = 0)
    {
        $this->_wajib_post();
        $this->_wajib_izin('catat');
        $k = $this->Biaya_model->get_kategori_by_id((int) $id);
        if (!$k) {
            show_404();
        }
        $this->Biaya_model->set_aktif_kategori($k->id, !$k->aktif);
        $this->_flash(true, 'Kategori "' . $k->nama . '" ' . ($k->aktif ? 'dinonaktifkan.' : 'diaktifkan kembali.'));
        redirect('biaya/kategori');
    }

    // POST: hapus kategori (hanya yang belum dipakai)
    public function kategori_hapus($id = 0)
    {
        $this->_wajib_post();
        $this->_wajib_izin('catat');
        list($ok, $pesan) = $this->Biaya_model->hapus_kategori((int) $id);
        $this->_flash($ok, $pesan);
        redirect('biaya/kategori');
    }

    // Rekap tahunan: kategori x bulan + kas masuk vs keluar
    public function rekap()
    {
        $tahun = (int) $this->input->get('tahun');
        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = (int) date('Y');
        }
        $data['title']       = 'Rekap Biaya ' . $tahun;
        $data['tahun']       = $tahun;
        $data['rekap']       = $this->Biaya_model->get_rekap_tahun($tahun);
        $data['kas_masuk']   = $this->Biaya_model->get_kas_masuk_tahun($tahun);
        $data['active_menu'] = 'biaya_rekap';
        $this->_view('biaya/rekap', $data);
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
        $dari   = $this->_tgl($this->input->get('dari')) ?: date('Y-m-01');
        $sampai = $this->_tgl($this->input->get('sampai')) ?: date('Y-m-d');
        if ($dari > $sampai) {
            list($dari, $sampai) = array($sampai, $dari);
        }
        return array(
            'dari'        => $dari,
            'sampai'      => $sampai,
            'id_kategori' => (int) $this->input->get('kategori'),
            'q'           => mb_substr(trim((string) $this->input->get('q', true)), 0, 60),
        );
    }

    // Upload foto nota (jpg/png/webp/pdf, maks 5MB). Return: array(ok, path relatif | pesan error)
    private function _upload_foto($field)
    {
        $dir = FCPATH . $this->folder_foto;
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return array(false, 'Folder ' . $this->folder_foto . ' tidak bisa dibuat. Buat manual & beri permission 755.');
        }
        $this->load->library('upload');
        $this->upload->initialize(array(
            'upload_path'   => $dir,
            'allowed_types' => 'jpg|jpeg|png|webp|pdf',
            'max_size'      => 5120,
            'file_name'     => 'biaya_' . date('YmdHis') . '_' . mt_rand(100, 999),
            'overwrite'     => false,
        ), true);
        if (!$this->upload->do_upload($field)) {
            return array(false, 'Upload foto gagal: ' . strip_tags($this->upload->display_errors('', '')) . ' (jpg/png/webp/pdf, maks 5MB)');
        }
        return array(true, $this->folder_foto . $this->upload->data('file_name'));
    }

    // Hapus file hanya kalau memang ada di folder foto biaya
    private function _hapus_file($rel)
    {
        if (strpos($rel, $this->folder_foto) === 0 && strpos($rel, '..') === false && is_file(FCPATH . $rel)) {
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
            $this->_flash(false, 'Anda tidak punya hak akses untuk aksi ini.');
            redirect('biaya');
        }
    }

    // Tujuan redirect setelah aksi (hanya halaman biaya + query filter)
    private function _kembali()
    {
        $k = trim((string) $this->input->post('kembali'));
        return preg_match('#^biaya(\?[a-z_=&%0-9A-Z.+-]*)?$#', $k) ? $k : 'biaya';
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
