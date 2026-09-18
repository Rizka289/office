<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Stok_riwayat extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        // Adjust model loading or authentication checks as per your project standard
        $this->load->model('Stok_riwayat_model');
    }

    public function index()
    {
        $data['title'] = translate('ks');
        $data['page_title']     =  translate('ks');
        $data['active_menu']   = 'stok_riwayat';


        // Filter opsional jika user memilih barang / tanggal tertentu
        $id_barang = $this->input->get('id_barang');
        $id_location = $this->input->get('id_location');

        $data['list_barang']   = $this->Stok_riwayat_model->get_all_barang();
        $data['list_location'] = $this->Stok_riwayat_model->get_all_location();
        $data['riwayat']       = $this->Stok_riwayat_model->get_riwayat($id_barang, $id_location);

        $data['filter_barang']   = $id_barang;
        $data['filter_location'] = $id_location;

        $this->load->view('templates/header', $data); // Sesuaikan template template kamu
        $this->load->view('staff_gudang/stok_riwayat_view', $data);
        $this->load->view('templates/footer');
    }
}
