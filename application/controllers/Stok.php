<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Lokasi file: application/controllers/super_admin/Barang.php
// URL akses: domain.com/super_admin/barang

class Stok extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRole('super_admin'); // hanya super admin yang boleh akses seluruh method di sini
        $this->load->model('Stok_model');
        $this->load->model('Barang_model'); 
        $this->load->model('Locations_model'); 
        $this->load->helper('url');
    }

    public function index()
    {
        $data['title'] = translate('stok');
        $data['page_title']     =  translate('stok');
        $data['active_menu']   = translate('stok_barang');


      
        $this->load->view('templates/header', $data);
        $this->load->view('staff_gudang/stok_grid_view', $data);
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

        $total = $this->Stok_model->count_stok($search);
        $data  = $this->Stok_model->get_stok_paginated($search, $perPage, $offset);

        $this->jsonResponse([
            'status'       => true,
            'data'         => $data,
            'total'        => (int) $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'total_pages'  => (int) ceil($total / $perPage),
        ]);
    }
}
