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
        $data['title']         = 'Form Stok Opname';
        $data['active_menu']   = 'stok_opname';
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
        $qty_raw     = $this->input->post('qty_fisik');
        $mode        = $this->input->post('mode');          // 'tambah' | 'opname'
        $ukuran      = trim((string) $this->input->post('ukuran'));
        $keterangan  = $this->input->post('keterangan');
        $created_by  = $this->session->userdata('id_user') ?? 1; // Sesuai session user kamu

        if (!in_array($mode, ['tambah', 'opname'], true)) {
            $mode = 'tambah';
        }

        if (empty($id_barang) || empty($id_location)) {
            $this->session->set_flashdata('error', 'Barang dan Lokasi wajib diisi!');
            redirect('stok_opname');
        }

        // Jangan sampai input kosong dianggap 0 (bisa mengosongkan stok)
        if ($qty_raw === null || $qty_raw === '' || !is_numeric($qty_raw)) {
            $this->session->set_flashdata('error', 'Jumlah wajib diisi dengan angka!');
            redirect('stok_opname');
        }

        $qty = (int) $qty_raw;

        if ($qty < 0) {
            $this->session->set_flashdata('error', 'Jumlah tidak boleh negatif!');
            redirect('stok_opname');
        }

        if ($mode === 'tambah' && $qty === 0) {
            $this->session->set_flashdata('error', 'Jumlah stok yang ditambahkan harus lebih dari 0!');
            redirect('stok_opname');
        }

        $simpan = $this->Stok_opname_model->proses_stok_opname([
            'id_barang'   => $id_barang,
            'id_location' => $id_location,
            'qty_fisik'   => $qty,
            'mode'        => $mode,
            'ukuran'      => ($ukuran !== '' ? $ukuran : null),
            'keterangan'  => $keterangan,
            'created_by'  => $created_by
        ]);

        if ($simpan) {
            $this->session->set_flashdata('success', 'Stok berhasil disimpan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal memproses stok.');
        }

        redirect('stok_opname');
    }
}