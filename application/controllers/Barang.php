<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Lokasi file: application/controllers/super_admin/Barang.php
// URL akses: domain.com/super_admin/barang

class Barang extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRole('super_admin'); // hanya super admin yang boleh akses seluruh method di sini
        $this->load->model('Barang_model');
        $this->load->model('Kategori_barang_model'); // 1. Load model kategori
        $this->load->helper('url');
    }

    public function index()
    {
        $data['title'] = 'Manajemen Nama Barang';
        // 2. Ambil data kategori list dari model untuk dikirim ke view
        $data['kategoriList'] = $this->Kategori_barang_model->get_kat_barang_paginated('', 1000, 0);

        $this->load->view('templates/header', $data);
        $this->load->view('super_admin/barang_grid_view', $data);
        $this->load->view('templates/footer', $data);
    }

    // Endpoint AJAX: ambil data nama barang dengan pagination (max 5/halaman) & search
    public function list_data()
    {
        $search = $this->input->get('search', true);
        $page   = (int) $this->input->get('page', true);
        if ($page < 1) {
            $page = 1;
        }

        $perPage = 5;
        $offset  = ($page - 1) * $perPage;

        $total = $this->Barang_model->count_barang($search);
        $data  = $this->Barang_model->get_barang_paginated($search, $perPage, $offset);

        $this->jsonResponse([
            'status'       => true,
            'data'         => $data,
            'total'        => (int) $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'total_pages'  => (int) ceil($total / $perPage),
        ]);
    }

    // Helper: selipkan csrf_hash terbaru ke setiap response JSON,
    // supaya JS di view bisa refresh token untuk request AJAX berikutnya.
    private function jsonResponse($payload)
    {
        $payload['csrf_hash'] = $this->security->get_csrf_hash();
        echo json_encode($payload);
    }

    // Method simpan data via AJAX
    public function simpan()
    {
        // Nama field input di form: 'kode', 'nama', 'kategori', 'jenis',
        // 'satuan', 'dimensi', 'stok_minimum' (lihat name="..." pada
        // modalTambahNamaBarang).
        $kode         = $this->input->post('kode', true);
        $nama         = $this->input->post('nama', true);
        $idkategori     = $this->input->post('id_kategori', true);
        $jenis        = $this->input->post('jenis', true);
        $satuan       = $this->input->post('satuan', true);
        $dimensi      = $this->input->post('dimensi', true);
        $stokMinimum  = $this->input->post('stok_minimum', true);

        if (
            empty($kode) || empty($nama) || empty($idkategori) || empty($jenis)
            || empty($satuan) || empty($dimensi) || $stokMinimum === null || $stokMinimum === ''
        ) {
            $this->jsonResponse(['status' => false, 'message' => 'Semua field wajib diisi!']);
            return;
        }
        // Pastikan id_kategori yang dikirim benar-benar ada di tabel kategori_barang
        if (!$this->Kategori_barang_model->get_by_id($idkategori)) {
            $this->jsonResponse(['status' => false, 'message' => 'Kategori tidak valid!']);
            return;
        }


        $data = array(
            'kode_barang'  => $kode,
            'nama'         => $nama,
            'id_kategori'  => $idkategori,
            'jenis_barang' => $jenis,
            'satuan'       => $satuan,
            'dimensi'      => $dimensi,
            'stok_minimum' => $stokMinimum,
            'created_at'   => date('Y-m-d H:i:s'),
        );

        $simpan = $this->Barang_model->insert_nama_barang($data);

        $this->jsonResponse(
            $simpan
                ? ['status' => true, 'message' => 'Data berhasil disimpan']
                : ['status' => false, 'message' => 'Gagal menyimpan data']
        );
    }

    // Ambil data nama barang by id (untuk mengisi form edit)
    public function get_by_id($id)
    {
        $row = $this->Barang_model->get_by_id($id);

        if ($row) {
            $this->jsonResponse(['status' => true, 'data' => $row]);
        } else {
            $this->jsonResponse(['status' => false, 'message' => 'Data tidak ditemukan']);
        }
    }

    // Method update data via AJAX
    public function update()
    {
        $id           = $this->input->post('id', true);
        $kode         = $this->input->post('kode', true);
        $nama         = $this->input->post('nama', true);
        $idkategori   = $this->input->post('id_kategori', true);
        $jenis        = $this->input->post('jenis', true);
        $satuan       = $this->input->post('satuan', true);
        $dimensi      = $this->input->post('dimensi', true);
        $stokMinimum  = $this->input->post('stok_minimum', true);

        if (
            empty($id) || empty($kode) || empty($nama) || empty($idkategori)  || empty($jenis)
            || empty($satuan) || empty($dimensi) || $stokMinimum === null || $stokMinimum === ''
        ) {
            $this->jsonResponse(['status' => false, 'message' => 'Semua field wajib diisi!']);
            return;
        }

        $data = array(
            'kode_barang'  => $kode,
            'nama'         => $nama,
            'id_kategori'  => $idkategori,
            'jenis_barang' => $jenis,
            'satuan'       => $satuan,
            'dimensi'      => $dimensi,
            'stok_minimum' => $stokMinimum,
        );

        $update = $this->Barang_model->update_nama_barang($id, $data);

        $this->jsonResponse(
            $update
                ? ['status' => true, 'message' => 'Data berhasil diperbarui']
                : ['status' => false, 'message' => 'Gagal memperbarui data']
        );
    }

    // Method hapus data via AJAX
    public function delete($id)
    {
        $hapus = $this->Barang_model->delete_nama_barang($id);

        $this->jsonResponse(
            $hapus
                ? ['status' => true, 'message' => 'Data berhasil dihapus']
                : ['status' => false, 'message' => 'Gagal menghapus data']
        );
    }
}
