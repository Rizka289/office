<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penerimaan_barang extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['super_admin', 'staff_purchasing', 'staff_gudang']);
        $this->load->model('Penerimaan_barang_model');
        $this->load->model('Purchase_order_model');
        $this->load->helper('url');
    }

    // Halaman Index (Menampilkan List Data)
    public function index()
    {
        $data['page_title']    = 'Daftar Penerimaan Barang';
        $data['page_subtitle'] = 'Riwayat dan status penerimaan barang dari PO';
        $data['active_menu']   = 'penerimaan_barang';

        $filter = [
            'q'      => $this->input->get('q', TRUE),
            'dari'   => $this->input->get('dari', TRUE),
            'sampai' => $this->input->get('sampai', TRUE),
            'status' => $this->input->get('status', TRUE)
        ];

        $data['filter_q']       = $filter['q'];
        $data['filter_dari']    = $filter['dari'];
        $data['filter_sampai']  = $filter['sampai'];
        $data['filter_status']  = $filter['status'];

        // Get Data dari Database
        $penerimaan_list = $this->Penerimaan_barang_model->get_all_penerimaan($filter);

        // Simulasi kalkulasi status berdasarkan penerimaan
        foreach ($penerimaan_list as $row) {
            $row->status = 'selesai'; // Default status visual
        }

        $data['penerimaan_list']   = $penerimaan_list;
        $data['total_bulan_ini']   = count($penerimaan_list);
        $data['total_draft']       = 0;
        $data['total_selesai']     = count($penerimaan_list);
        $data['total_kurang']      = 0;

        $this->load->view('templates/header', $data);
        $this->load->view('staff_gudang/penerimaan_barang_view', $data);
        $this->load->view('templates/footer', $data);
    }

    // Halaman Form Penerimaan Baru
    public function tambah()
    {
        $data['page_title']    = 'Penerimaan Barang Baru';
        $data['page_subtitle'] = 'Input barang masuk dari PO';
        $data['active_menu']   = 'penerimaan_barang';
        $data['no_penerimaan'] = 'GR/' . date('Y/m') . '/' . sprintf('%04d', rand(1, 9999));

        $this->load->view('templates/header', $data);
        $this->load->view('staff_gudang/add_penerimaan_barang_view', $data);
        $this->load->view('templates/footer', $data);
    }

    // Endpoint AJAX untuk Fetch Item PO ke JavaScript
    public function get_po_items()
    {
        $no_po = $this->input->get('no_po', TRUE);

        if (empty($no_po)) {
            $this->output
                 ->set_status_header(400)
                 ->set_content_type('application/json')
                 ->set_output(json_encode(['message' => 'No PO tidak boleh kosong']));
            return;
        }

        $data_po = $this->Penerimaan_barang_model->get_po_by_no($no_po);

        if (!$data_po) {
            $this->output
                 ->set_status_header(404)
                 ->set_content_type('application/json')
                 ->set_output(json_encode(['message' => 'PO tidak ditemukan']));
            return;
        }

        $this->output
             ->set_content_type('application/json')
             ->set_output(json_encode($data_po));
    }
}