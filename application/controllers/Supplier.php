<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Lokasi file: application/controllers/Supplier.php
// URL akses: domain.com/supplier

class Supplier extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['super_admin', 'staff_purchasing']); // hanya role ini yang boleh akses seluruh method di sini
        $this->load->model('Supplier_model');
        $this->load->helper('url');
    }

    public function index()
    {
        $data['title'] = 'Manajemen Supplier';
        $data['page_title']     =  translate('app_list') . ' ' . translate('list_pemasok');
        $data['active_menu']   = 'supplier';

        $this->load->view('templates/header', $data);
        $this->load->view('master_data/supplier_index', $data);
        $this->load->view('templates/footer', $data);
    }

    // Endpoint AJAX: ambil data supplier dengan pagination (max 5/halaman) & search
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

    // Method simpan data via AJAX
    public function simpan()
    {
        $nama      = $this->input->post('nama', true);
        $kontak    = $this->input->post('kontak', true);
        $deskripsi = $this->input->post('deskripsi', true);
        $alamat    = $this->input->post('alamat', true);

        if (empty($nama) || empty($kontak) || empty($deskripsi) || empty($alamat)) {
            $this->jsonResponse(['status' => false, 'message' => 'Semua field wajib diisi!']);
            return;
        }

        $data = array(
            'nama'       => $nama,
            'kontak'     => $kontak,
            'deskripsi'  => $deskripsi,
            'alamat'     => $alamat,
            'created_at' => date('Y-m-d H:i:s')
        );

        $simpan = $this->Supplier_model->insert_supplier($data);

        $this->jsonResponse(
            $simpan
                ? ['status' => true, 'message' => translate('message_sukses')]
                : ['status' => false, 'message' => translate('message_gagal')]
        );
    }

    // Ambil data supplier berdasarkan ID (untuk form edit modal)
    public function get_by_id($id)
    {
        $supplier = $this->Supplier_model->get_by_id($id);
        $this->jsonResponse(
            $supplier
                ? ['status' => true, 'data' => $supplier]
                : ['status' => false, 'message' => 'Data tidak ditemukan!']
        );
    }

    // Update data supplier via AJAX
    public function update()
    {
        $id        = $this->input->post('id', true);
        $nama      = $this->input->post('nama', true);
        $deskripsi = $this->input->post('deskripsi', true);
        $kontak    = $this->input->post('kontak', true);
        $alamat    = $this->input->post('alamat', true);

        if (empty($id) || empty($nama) || empty($kontak) || empty($alamat) || empty($deskripsi)) {
            $this->jsonResponse(['status' => false, 'message' => 'Field nama, kontak, deskripsi, dan alamat wajib diisi!']);
            return;
        }

        $data = array(
            'nama'      => $nama,
            'kontak'    => $kontak,
            'deskripsi' => $deskripsi,
            'alamat'    => $alamat
        );

        $update = $this->Supplier_model->update_supplier($id, $data);

        $this->jsonResponse(
            $update
                ? ['status' => true, 'message' =>  translate('message_update')]
                : ['status' => false, 'message' =>translate('message_update_gagal')]
        );
    }

    // Delete data supplier via AJAX
    public function delete($id = NULL)
    {
        if (empty($id)) {
            $this->jsonResponse(['status' => false, 'message' => 'ID tidak ditemukan!']);
            return;
        }

        $result = $this->Supplier_model->delete_supplier($id);

        if ($result['success']) {
            $this->jsonResponse(['status' => true, 'message' => 'Data berhasil dihapus']);
            return;
        }

        $code = (int) $result['error']['code'];
        log_message('error', 'Delete Supplier gagal: ' . $result['error']['message']);

        if ($code === 1451) { // foreign key constraint
            $msg = translate('p_supplier_relasi');
        } else {
            $msg = 'Gagal menghapus data dari database';
        }

        $this->jsonResponse(['status' => false, 'message' => $msg]);
    }
}