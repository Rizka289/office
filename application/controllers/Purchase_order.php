<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Purchase_order extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['super_admin', 'staff_purchasing']);
        $this->load->model('Purchase_order_model');
        $this->load->model('Supplier_model');
        $this->load->helper('url');
    }

    public function index()
    {
        $data['title'] = 'Manajemen Pesanan Pembelian';
        $data['supplier'] = $this->Supplier_model->get_all();

        $this->load->view('templates/header', $data);
        // Memuat view frontend Purchase Order (Source 3)
        $this->load->view('staff_purchasing/po_view', $data);
        $this->load->view('templates/footer', $data);
    }

    public function list_data()
    {
        $search  = $this->input->get('search', true);
        $page    = max(1, (int) $this->input->get('page', true));
        $perPage = 10;
        $offset  = ($page - 1) * $perPage;

        $total = $this->Purchase_order_model->count_po($search);
        $data  = $this->Purchase_order_model->get_po_paginated($search, $perPage, $offset);

        $this->jsonResponse([
            'status'       => true,
            'data'         => $data,
            'total'        => (int) $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'total_pages'  => (int) ceil($total / $perPage),
        ]);
    }

    public function simpan()
    {
        $id_supplier = $this->input->post('id_supplier', true);
        $tanggal     = $this->input->post('tanggal', true);
        $items       = $this->input->post('items'); // Array item PO

        if (empty($id_supplier) || empty($tanggal) || empty($items)) {
            $this->jsonResponse(['status' => false, 'message' => 'Supplier, tanggal, dan item barang wajib diisi!']);
            return;
        }

        $po_data = [
            'no_po'       => $this->Purchase_order_model->generate_po_number(),
            'id_supplier' => $id_supplier,
            'tanggal'     => $tanggal,
            'status'      => $this->input->post('status', true) ?: 'draft',
            'created_at'  => date('Y-m-d H:i:s')
        ];

        $simpan = $this->Purchase_order_model->insert_po($po_data, $items);

        $this->jsonResponse(
            $simpan
                ? ['status' => true, 'message' => 'PO berhasil disimpan']
                : ['status' => false, 'message' => 'Gagal menyimpan PO']
        );
    }

    private function jsonResponse($payload)
    {
        $payload['csrf_hash'] = $this->security->get_csrf_hash();
        $this->output
             ->set_content_type('application/json')
             ->set_output(json_encode($payload));
    }
}