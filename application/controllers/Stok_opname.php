<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stok_opname extends CI_Controller {

    // Daftar opsi yang valid (harus sama dengan <option> di view)
    private $opsi_valid = ['bukaan_kanan', 'bukaan_kiri', 'aksesoris', 'bahan'];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Stok_opname_model');
    }

    public function index()
    {
        $data['title']         = translate('so');
        $data['page_title']     =  translate('so');
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
        $opsi        = $this->input->post('opsi');
        $qty_raw     = $this->input->post('qty_fisik');
        $mode        = $this->input->post('mode');                   // 'tambah' | 'opname'
        $ukuran      = trim((string) $this->input->post('ukuran'));  // opsional
        $keterangan  = $this->input->post('keterangan');

        // Petugas input: dari session login (key 'user_id' diset di Login::proses)
        $created_by  = $this->session->userdata('user_id');

        if (empty($created_by)) {
            $this->session->set_flashdata('error', 'Sesi login habis, silakan login ulang.');
            redirect('login');
        }

        if (!in_array($mode, ['tambah', 'opname'], true)) {
            $mode = 'tambah';
        }

        if (empty($id_barang) || empty($id_location)) {
            $this->session->set_flashdata('error', 'Barang dan Lokasi wajib diisi!');
            redirect('stok_opname');
        }

        // Opsi wajib dan harus salah satu dari daftar valid
        if (!in_array($opsi, $this->opsi_valid, true)) {
            $this->session->set_flashdata('error', 'Opsi wajib dipilih!');
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
            'opsi'        => $opsi,
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