<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Lokasi file: application/controllers/super_admin/User.php
// URL akses: domain.com/super_admin/user

class Supplier extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['super_admin', 'staff_purchasing']); // hanya super admin yang boleh akses seluruh method di sini
        $this->load->model('Supplier_model');
        $this->load->helper('url');
    }

    public function index()
    {
        $data['title'] = 'Manajemen Supplier';
        $data['page_title']     =  translate('app_list') . ' ' . translate('list_pemasok');


        $this->load->view('templates/header', $data);
        $this->load->view('super_admin/supplier_grid_view', $data);
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

        $total = $this->Supplier_model->count_supplier($search);
        $data  = $this->Supplier_model->get_supplier_paginated($search, $perPage, $offset);

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
        $nama     = $this->input->post('nama', true);
        $kontak = $this->input->post('kontak', true);
        $deskripsi = $this->input->post('deskripsi', true);
        $alamat = $this->input->post('alamat', true);

        if (empty($nama) || empty($kontak) || empty($deskripsi) || empty($alamat)) {
            $this->jsonResponse(['status' => false, 'message' => 'Semua field wajib diisi!']);
            return;
        }

        $data = array(
            'nama'       => $nama,
            'kontak'   => $kontak,
            'deskripsi'   => $deskripsi,
            'alamat'   => $alamat,
            'created_at' => date('Y-m-d H:i:s')
        );

        $simpan = $this->Supplier_model->insert_supplier($data);

        $this->jsonResponse(
            $simpan
                ? ['status' => true, 'message' => 'Data berhasil disimpan']
                : ['status' => false, 'message' => 'Gagal menyimpan data']
        );
    }

    // Ambil data user berdasarkan ID (untuk form edit modal)
    public function get_by_id($id)
    {
        $supplier = $this->Supplier_model->get_by_id($id);
        $this->jsonResponse(
            $supplier
                ? ['status' => true, 'data' => $supplier]
                : ['status' => false, 'message' => 'Data tidak ditemukan!']
        );
    }

    // Update data user via AJAX
    public function update()
    {
        $id       = $this->input->post('id', true);
        $nama     = $this->input->post('nama', true);
        $deskripsi     = $this->input->post('deskripsi', true);
        $kontak = $this->input->post('kontak', true);
        $alamat = $this->input->post('alamat', true);

        if (empty($id) || empty($nama) || empty($kontak) || empty($alamat) || empty($deskripsi)) {
            $this->jsonResponse(['status' => false, 'message' => 'Field nama, kontak, dan alamat wajib diisi!']);
            return;
        }

        $data = array(
            'nama'     => $nama,
            'kontak' => $kontak,
            'deskripsi' => $deskripsi,
            'alamat'     => $alamat
        );


        $update = $this->Supplier_model->update_supplier($id, $data);

        $this->jsonResponse(
            $update
                ? ['status' => true, 'message' => 'Data berhasil diperbarui']
                : ['status' => false, 'message' => 'Gagal memperbarui data']
        );
    }

    // Delete data user via AJAX
    public function delete($id)
    {
        if (empty($id)) {
            $this->jsonResponse(['status' => false, 'message' => 'ID tidak ditemukan!']);
            return;
        }

        $delete = $this->Supplier_model->delete_supplier($id);

        $this->jsonResponse(
            $delete
                ? ['status' => true, 'message' => 'Data berhasil dihapus']
                : ['status' => false, 'message' => 'Gagal menghapus data']
        );
    }
}
