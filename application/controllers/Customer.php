<?php
defined('BASEPATH') or exit('No direct script access allowed');


class Customer extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRole('super_admin'); // hanya super admin yang boleh akses seluruh method di sini
        $this->load->model('Customer_model');
        $this->load->helper('url');
    }

    public function index()
    {
        $data['title'] = translate('app_list') . ' ' . translate('pelanggan');
        $data['page_title']     =  translate('app_list') . ' ' . translate('pelanggan');
        $data['active_menu']   = 'customer';


        $this->load->view('templates/header', $data);
        $this->load->view('master_data/customer_index', $data);
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

        $total = $this->Customer_model->count_customer($search);
        $data  = $this->Customer_model->get_customer_paginated($search, $perPage, $offset);

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
        $nama     = $this->input->post('nama', true);
        $kontak = $this->input->post('kontak', true);
        $alamat = $this->input->post('alamat', true);

        if (empty($nama) || empty($kontak) || empty($alamat)) {
            $this->jsonResponse(['status' => false, 'message' => 'Semua field wajib diisi!']);
            return;
        }

        $data = array(
            'nama'       => $nama,
            'kontak'   => $kontak,
            'alamat'   => $alamat,
            'created_at' => date('Y-m-d H:i:s')
        );

        $simpan = $this->Customer_model->insert_customer($data);

        $this->jsonResponse(
            $simpan
                ? ['status' => true, 'message' =>  translate('message_sukses')]
                : ['status' => false, 'message' => translate('message_gagal')]
        );
    }

    // Ambil data user berdasarkan ID (untuk form edit modal)
    public function get_by_id($id)
    {
        $customers = $this->Customer_model->get_by_id($id);
        $this->jsonResponse(
            $customers
                ? ['status' => true, 'data' => $customers]
                : ['status' => false, 'message' => 'Data tidak ditemukan!']
        );
    }

    // Update data user via AJAX
    public function update()
    {
        $id       = $this->input->post('id', true);
        $nama     = $this->input->post('nama', true);
        $kontak = $this->input->post('kontak', true);
        $alamat = $this->input->post('alamat', true);

        if (empty($id) || empty($nama) || empty($kontak) || empty($alamat)) {
            $this->jsonResponse(['status' => false, 'message' => 'Field nama, kontak, dan alamat wajib diisi!']);
            return;
        }

        $data = array(
            'nama'       => $nama,
            'kontak'     => $kontak,
            'alamat'     => $alamat
        );


        $update = $this->Customer_model->update_customer($id, $data);

        $this->jsonResponse(
            $update
                ? ['status' => true, 'message' => translate('message_update')]
                : ['status' => false, 'message' => translate('message_update_gagal')]
        );
    }

    public function delete($id = NULL)
    {
        if (empty($id)) {
            $this->jsonResponse(['status' => false, 'message' => 'ID tidak ditemukan!']);
            return;
        }

        $result = $this->Customer_model->delete_customer($id);

        if ($result['success']) {
            $this->jsonResponse(['status' => true, 'message' => translate('message_delete_sukses')]);
            return;
        }

        $code = (int) $result['error']['code'];
        log_message('error', 'Delete Customer gagal: ' . $result['error']['message']);

        if ($code === 1451) { // foreign key constraint
            $msg = translate('p_customer_relasi');
        } else {
            $msg = 'Gagal menghapus data dari database';
        }

        $this->jsonResponse(['status' => false, 'message' => $msg]);
    }
}
