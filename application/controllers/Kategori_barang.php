<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Lokasi file : application/controllers/Kategori_barang.php
// URL akses   : domain.com/kategori_barang
// (Kalau file ini kamu taruh di subfolder, mis. super_admin/, semua site_url() di view
//  juga harus ikut diubah jadi 'super_admin/kategori_barang/...')

class Kategori_barang extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRole('super_admin'); // hanya super admin yang boleh akses seluruh method di sini
        $this->load->model('Kategori_barang_model');
        $this->load->helper('url');
    }

    // ------------------------------------------------------------------
    // Helper internal (private => tidak bisa diakses lewat URL)
    // ------------------------------------------------------------------

    // Semua response JSON WAJIB lewat sini supaya selalu membawa csrf_hash terbaru.
    // (CSRF di CI3 diregenerasi setiap POST; hash lama langsung tidak valid.)
    private function respond(array $payload)
    {
        $payload['csrf_hash'] = $this->security->get_csrf_hash();
        $this->jsonResponse($payload);
    }

    // Aksi yang mengubah data hanya boleh lewat POST. CSRF di CI3 hanya diverifikasi
    // untuk POST, jadi kalau delete() bisa dipanggil via GET, proteksinya bisa dilewati.
    private function mustBePost()
    {
        if ($this->input->method() !== 'post') {
            $this->respond(['status' => false, 'message' => 'Metode request tidak valid.']);
            return false;
        }
        return true;
    }

    private function isValidId($id)
    {
        return $id !== null && ctype_digit((string) $id) && (int) $id > 0;
    }

    // ------------------------------------------------------------------
    // Halaman
    // ------------------------------------------------------------------

    public function index()
    {
        $data['title']        = 'Manajemen Kategori Barang';
        $data['page_title']   = translate('kategori_barang');
        $data['active_menu']  = 'kategori';

        $this->load->view('templates/header', $data);
        $this->load->view('master_data/kategori_barang', $data);
        $this->load->view('templates/footer', $data);
    }

    // ------------------------------------------------------------------
    // Endpoint AJAX
    // ------------------------------------------------------------------

    // Ambil data kategori barang dengan pagination (max 5/halaman) & search
    public function list_data()
    {
        $search = trim((string) $this->input->get('search', true));
        $page   = (int) $this->input->get('page', true);
        if ($page < 1) {
            $page = 1;
        }

        $perPage = 5;
        $offset  = ($page - 1) * $perPage;

        $total = $this->Kategori_barang_model->count_kat_barang($search);
        $data  = $this->Kategori_barang_model->get_kat_barang_paginated($search, $perPage, $offset);

        $this->respond([
            'status'       => true,
            'data'         => $data,
            'total'        => (int) $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'total_pages'  => (int) ceil($total / $perPage),
        ]);
    }

    // Simpan data baru via AJAX
    // Nama field di form: 'kode', 'nama', 'deskripsi'
    public function simpan()
    {
        if (!$this->mustBePost()) {
            return;
        }

        $kode      = trim((string) $this->input->post('kode', true));
        $nama      = trim((string) $this->input->post('nama', true));
        $deskripsi = trim((string) $this->input->post('deskripsi', true));

        // Pakai === '' (bukan empty()) supaya nilai "0" tidak dianggap kosong
        if ($kode === '' || $nama === '' || $deskripsi === '') {
            $this->respond(['status' => false, 'message' => 'Semua field wajib diisi!']);
            return;
        }

        if ($this->Kategori_barang_model->kode_exists($kode)) {
            $this->respond(['status' => false, 'message' => 'Kode kategori sudah digunakan, gunakan kode lain.']);
            return;
        }

        $data = array(
            'kode_kategori' => $kode,
            'nama_kategori' => $nama,
            'deskripsi'     => $deskripsi,
            'created_at'    => date('Y-m-d H:i:s'),
        );

        $simpan = $this->Kategori_barang_model->insert_kategori_barang($data);

        $this->respond(
            $simpan
                ? ['status' => true, 'message' => 'Data berhasil disimpan']
                : ['status' => false, 'message' => $this->Kategori_barang_model->get_error_message('Gagal menyimpan data')]
        );
    }

    // Ambil data kategori barang by id (untuk mengisi form edit)
    public function get_by_id($id = null)
    {
        if (!$this->isValidId($id)) {
            $this->respond(['status' => false, 'message' => 'ID tidak valid']);
            return;
        }

        $row = $this->Kategori_barang_model->get_by_id($id);

        $this->respond(
            $row
                ? ['status' => true, 'data' => $row]
                : ['status' => false, 'message' => 'Data tidak ditemukan']
        );
    }

    // Update data via AJAX
    public function update()
    {
        if (!$this->mustBePost()) {
            return;
        }

        $id        = $this->input->post('id', true);
        $kode      = trim((string) $this->input->post('kode', true));
        $nama      = trim((string) $this->input->post('nama', true));
        $deskripsi = trim((string) $this->input->post('deskripsi', true));

        if (!$this->isValidId($id) || $kode === '' || $nama === '' || $deskripsi === '') {
            $this->respond(['status' => false, 'message' => 'Semua field wajib diisi!']);
            return;
        }

        if (!$this->Kategori_barang_model->get_by_id($id)) {
            $this->respond(['status' => false, 'message' => 'Data tidak ditemukan (mungkin sudah dihapus).']);
            return;
        }

        // Cek kode kembar, kecuali terhadap dirinya sendiri
        if ($this->Kategori_barang_model->kode_exists($kode, $id)) {
            $this->respond(['status' => false, 'message' => 'Kode kategori sudah digunakan, gunakan kode lain.']);
            return;
        }

        $data = array(
            'kode_kategori' => $kode,
            'nama_kategori' => $nama,
            'deskripsi'     => $deskripsi,
        );

        $update = $this->Kategori_barang_model->update_kategori_barang($id, $data);

        $this->respond(
            $update
                ? ['status' => true, 'message' => 'Data berhasil diperbarui']
                : ['status' => false, 'message' => $this->Kategori_barang_model->get_error_message('Gagal memperbarui data')]
        );
    }

    // Hapus data via AJAX
    public function delete($id = null)
    {
        if (!$this->mustBePost()) {
            return;
        }

        if (!$this->isValidId($id)) {
            $this->respond(['status' => false, 'message' => 'ID tidak valid']);
            return;
        }

        if (!$this->Kategori_barang_model->get_by_id($id)) {
            $this->respond(['status' => false, 'message' => 'Data tidak ditemukan (mungkin sudah dihapus).']);
            return;
        }

        $hapus = $this->Kategori_barang_model->delete_kategori_barang($id);

        $this->respond(
            $hapus
                ? ['status' => true, 'message' => 'Data berhasil dihapus']
                : ['status' => false, 'message' => $this->Kategori_barang_model->get_error_message('Gagal menghapus data')]
        );
    }
}