<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Stok_riwayat extends CI_Controller
{
    // Role yang boleh menghapus riwayat (SESUAIKAN dengan nama role di sistem kamu)
    private $role_boleh_hapus = ['super_admin', 'admin'];

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

        // Tombol hapus hanya untuk admin, dan hanya di baris yang memenuhi syarat
        $data['is_admin']      = $this->is_admin();
        $data['id_bisa_hapus'] = $data['is_admin']
            ? array_flip($this->Stok_riwayat_model->get_id_bisa_hapus())
            : [];

        $this->load->view('templates/header', $data); // Sesuaikan template template kamu
        $this->load->view('inventory/stok_riwayat', $data);
        $this->load->view('templates/footer');
    }

    /**
     * POST stok_riwayat/hapus
     * Hapus riwayat dari Stok Opname + kembalikan stok. Hanya admin.
     */
    public function hapus()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        if (empty($this->session->userdata('user_id'))) {
            $this->session->set_flashdata('error', translate('i18n_sesi_login_habis_silakan_login_ulang'));
            redirect('login');
        }

        // Kembali ke halaman riwayat dengan filter yang sama
        $qs   = http_build_query(array_filter([
            'id_barang'   => $this->input->post('filter_barang'),
            'id_location' => $this->input->post('filter_location'),
        ]));
        $back = 'stok_riwayat' . ($qs !== '' ? '?' . $qs : '');

        if (!$this->is_admin()) {
            $this->session->set_flashdata('error', 'Hanya admin yang boleh menghapus riwayat stok.');
            redirect($back);
        }

        $id = (int) $this->input->post('id');
        if ($id <= 0) {
            $this->session->set_flashdata('error', 'Data riwayat tidak valid.');
            redirect($back);
        }

        $hasil = $this->Stok_riwayat_model->hapus_stok_opname($id);
        $this->session->set_flashdata($hasil['status'] ? 'success' : 'error', $hasil['pesan']);

        redirect($back);
    }

    private function is_admin()
    {
        // SESUAIKAN key session role kamu (misalnya 'role', 'level', 'role_name')
        $role = $this->session->userdata('role');
        return in_array($role, $this->role_boleh_hapus, true);
    }
}