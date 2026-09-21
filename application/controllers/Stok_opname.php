<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stok_opname extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Stok_opname_model');
    }

    public function index()
    {
        $data['title']        = 'Form Stok Opname';
        $data['active_menu'] = 'stok_opname'; 
        $data['list_barang']   = $this->Stok_opname_model->get_all_barang();
        $data['list_location'] = $this->Stok_opname_model->get_all_location();

        $this->load->view('templates/header', $data);
        $this->load->view('inventory/stok_opname_view', $data);
        $this->load->view('templates/footer');
    }

    public function simpan()
    {
        $id_barang   = $this->input->post('id_barang');
        $id_location = $this->input->post('id_location');
        $qty_fisik   = (int) $this->input->post('qty_fisik');
        $keterangan  = $this->input->post('keterangan');
        $created_by  = $this->session->userdata('id_user') ?? 1; // Sesuai session user kamu

        if (empty($id_barang) || empty($id_location)) {
            $this->session->set_flashdata('error', 'Barang dan Lokasi wajib diisi!');
            redirect('stok_opname');
        }

        $simpan = $this->Stok_opname_model->proses_stok_opname([
            'id_barang'   => $id_barang,
            'id_location' => $id_location,
            'qty_fisik'   => $qty_fisik,
            'keterangan'  => $keterangan,
            'created_by'  => $created_by
        ]);

        if ($simpan) {
            $this->session->set_flashdata('success', 'Stok opname berhasil disimpan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal memproses stok opname.');
        }

        redirect('stok_opname');
    }
}