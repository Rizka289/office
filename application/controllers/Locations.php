<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Lokasi file: application/controllers/super_admin/Locations.php
// URL akses: domain.com/super_admin/locations

class Locations extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRole('super_admin'); // hanya super admin yang boleh akses seluruh method di sini
        $this->load->model('Locations_model');
        $this->load->helper('url');
    }

    public function index()
    {
        $data['title'] = translate('app_rak');
        // $data['kategori_barang'] = $this->Kategori_barang_model->get_all_kat_barang();

        $this->load->view('templates/header', $data);
        $this->load->view('super_admin/location_view', $data);
        $this->load->view('templates/footer', $data);
    }

    // Endpoint AJAX: ambil data kategori location dengan pagination (max 5/halaman) & search
    public function list_data()
    {
        $search = $this->input->get('search', true);
        $page   = (int) $this->input->get('page', true);
        if ($page < 1) {
            $page = 1;
        }

        $perPage = 5;
        $offset  = ($page - 1) * $perPage;

        $total = $this->Locations_model->count_location($search);
        $data  = $this->Locations_model->get_location_paginated($search, $perPage, $offset);

        $this->jsonResponse([
            'status'       => true,
            'data'         => $data,
            'total'        => (int) $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'total_pages'  => (int) ceil($total / $perPage),
        ]);
    }

    // Ambil data location by id (untuk mengisi form edit)
    public function get_by_id($id)
    {
        $row = $this->Locations_model->get_by_id($id);

        if ($row) {
            $this->jsonResponse(['status' => true, 'data' => $row]);
        } else {
            $this->jsonResponse(['status' => false, 'message' => 'Data tidak ditemukan']);
        }
    }

    // Simpan data location baru (AJAX POST)
    public function simpan()
    {
        $payload = $this->getValidatedInput();
        if ($payload === false) {
            return; // pesan error sudah dikirim oleh getValidatedInput()
        }

        $insert = $this->Locations_model->insert_location($payload);

        if ($insert) {
            $this->jsonResponse(['status' => true, 'message' => 'Data lokasi berhasil ditambahkan']);
        } else {
            $this->jsonResponse(['status' => false, 'message' => 'Gagal menambahkan data lokasi']);
        }
    }

    // Update data location (AJAX POST)
    public function update()
    {
        $id = (int) $this->input->post('id', true);
        if (!$id) {
            $this->jsonResponse(['status' => false, 'message' => 'ID lokasi tidak valid']);
            return;
        }

        $existing = $this->Locations_model->get_by_id($id);
        if (!$existing) {
            $this->jsonResponse(['status' => false, 'message' => 'Data tidak ditemukan']);
            return;
        }

        $payload = $this->getValidatedInput();
        if ($payload === false) {
            return;
        }

        $update = $this->Locations_model->update_location($id, $payload);

        if ($update !== false) {
            $this->jsonResponse(['status' => true, 'message' => 'Data lokasi berhasil diperbarui']);
        } else {
            $this->jsonResponse(['status' => false, 'message' => 'Gagal memperbarui data lokasi']);
        }
    }

    // Hapus data location (AJAX POST)
    public function delete($id)
    {
        $existing = $this->Locations_model->get_by_id($id);
        if (!$existing) {
            $this->jsonResponse(['status' => false, 'message' => 'Data tidak ditemukan']);
            return;
        }

        $delete = $this->Locations_model->delete_location($id);

        if ($delete) {
            $this->jsonResponse(['status' => true, 'message' => 'Data lokasi berhasil dihapus']);
        } else {
            $this->jsonResponse(['status' => false, 'message' => 'Gagal menghapus data lokasi']);
        }
    }

    // Ambil & validasi input form tambah/edit location dari POST.
    // Return array data siap simpan, atau false jika tidak valid (sudah mengirim jsonResponse error).
    private function getValidatedInput()
    {
        $data = [
            'location_code' => trim($this->input->post('location_code', true)),
            'zone_name'     => trim($this->input->post('zone_name', true)),
            'aisle'         => trim($this->input->post('aisle', true)),
            'rack_number'   => trim($this->input->post('rack_number', true)),
            'level'         => trim($this->input->post('level', true)),
            'location_type' => trim($this->input->post('location_type', true)),
        ];

        if ($data['location_code'] === '' || $data['zone_name'] === '') {
            $this->jsonResponse([
                'status'  => false,
                'message' => 'Kode lokasi dan nama zona wajib diisi',
            ]);
            return false;
        }

        return $data;
    }

    // Helper: selipkan csrf_hash terbaru ke setiap response JSON,
    // supaya JS di view bisa refresh token untuk request AJAX berikutnya.
    private function jsonResponse($payload)
    {
        $payload['csrf_hash'] = $this->security->get_csrf_hash();
        echo json_encode($payload);
    }
}