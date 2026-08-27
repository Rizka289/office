<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Lokasi file: application/controllers/super_admin/User.php
// URL akses: domain.com/super_admin/user

class Kategori_barang extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRole('super_admin'); // hanya super admin yang boleh akses seluruh method di sini
        $this->load->model('Kategori_barang_model');
        $this->load->helper('url');
    }

    public function index()
    {
        $data['title'] = 'Manajemen Kategori Barang';
        // $data['kategori_barang'] = $this->Kategori_barang_model->get_all_kat_barang();

        $this->load->view('templates/header', $data);
        $this->load->view('super_admin/kategori_barang', $data);
        $this->load->view('templates/footer', $data);
    }
    // Endpoint AJAX: ambil data kategori barang dengan pagination (max 5/halaman) & search
    public function list_data()
    {
        $search = $this->input->get('search', true);
        $page   = (int) $this->input->get('page', true);
        if ($page < 1) {
            $page = 1;
        }

        $perPage = 5;
        $offset  = ($page - 1) * $perPage;

        $total = $this->Kategori_barang_model->count_kat_barang($search);
        $data  = $this->Kategori_barang_model->get_kat_barang_paginated($search, $perPage, $offset);

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
        // NOTE: nama field input di form adalah 'kode', 'nama', 'deskripsi'
        // (lihat name="..." pada input di modalTambahKatBarang), bukan
        // 'kode_kategori' / 'nama_kategori'. Sebelumnya tidak sinkron
        // sehingga data selalu terbaca kosong.
        $kode      = $this->input->post('kode', true);
        $nama      = $this->input->post('nama', true);
        $deskripsi = $this->input->post('deskripsi', true);

        if (empty($kode) || empty($nama) || empty($deskripsi)) {
            $this->jsonResponse(['status' => false, 'message' => 'Semua field wajib diisi!']);
            return;
        }

        $data = array(
            'kode_kategori'  => $kode,
            'nama_kategori'  => $nama,
            'deskripsi'      => $deskripsi,
            'created_at'     => date('Y-m-d H:i:s')
        );

        $simpan = $this->Kategori_barang_model->insert_kategori_barang($data);

        $this->jsonResponse(
            $simpan
                ? ['status' => true, 'message' => 'Data berhasil disimpan']
                : ['status' => false, 'message' => 'Gagal menyimpan data']
        );
    }

    // Ambil data kategori barang by id (untuk mengisi form edit)
    public function get_by_id($id)
    {
        $row = $this->Kategori_barang_model->get_by_id($id);

        if ($row) {
            $this->jsonResponse(['status' => true, 'data' => $row]);
        } else {
            $this->jsonResponse(['status' => false, 'message' => 'Data tidak ditemukan']);
        }
    }

    // Method update data via AJAX
    public function update()
    {
        $id        = $this->input->post('id', true);
        $kode      = $this->input->post('kode', true);
        $nama      = $this->input->post('nama', true);
        $deskripsi = $this->input->post('deskripsi', true);

        if (empty($id) || empty($kode) || empty($nama) || empty($deskripsi)) {
            $this->jsonResponse(['status' => false, 'message' => 'Semua field wajib diisi!']);
            return;
        }

        $data = array(
            'kode_kategori'  => $kode,
            'nama_kategori'  => $nama,
            'deskripsi'      => $deskripsi,
        );

        $update = $this->Kategori_barang_model->update_kategori_barang($id, $data);

        $this->jsonResponse(
            $update
                ? ['status' => true, 'message' => 'Data berhasil diperbarui']
                : ['status' => false, 'message' => 'Gagal memperbarui data']
        );
    }

    // Method hapus data via AJAX
    public function delete($id)
    {
        $hapus = $this->Kategori_barang_model->delete_kategori_barang($id);

        $this->jsonResponse(
            $hapus
                ? ['status' => true, 'message' => 'Data berhasil dihapus']
                : ['status' => false, 'message' => 'Gagal menghapus data']
        );
    }
}