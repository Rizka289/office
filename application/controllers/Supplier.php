<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Master Supplier
 * Kolom tabel supplier: id, nama, kontak, alamat, deskripsi (+ created_at / aktif bila ada)
 * Semua aksi lewat AJAX dan mengembalikan JSON (dipakai views/master_data/supplier_index.php)
 */
class Supplier extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        // super admin mengelola master; purchasing (admin penawaran) juga butuh menambah supplier baru
        $this->requireRole(['super_admin', 'staff_purchasing']);
        $this->load->model('Supplier_model');
        $this->load->helper('url');
    }

    public function index()
    {
        $data['title']       = 'Supplier';
        $data['page_title']  = 'Supplier';
        $data['active_menu'] = 'supplier';

        $this->load->view('templates/header', $data);
        $this->load->view('master_data/supplier_index', $data);
        $this->load->view('templates/footer', $data);
    }

    // AJAX: data supplier dengan pagination & pencarian
    public function list_data()
    {
        $search = trim((string) $this->input->get('search', true));
        $page   = max(1, (int) $this->input->get('page', true));

        $perPage = 5;
        $offset  = ($page - 1) * $perPage;

        $total = $this->Supplier_model->count_supplier($search);
        $rows  = $this->Supplier_model->get_supplier_paginated($search, $perPage, $offset);

        $this->jsonResponse([
            'status'       => true,
            'data'         => $rows,
            'total'        => (int) $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'total_pages'  => (int) ceil($total / $perPage),
        ]);
    }

    // AJAX: tambah supplier
    public function simpan()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        list($data, $error) = $this->_ambil_input();
        if ($error !== '') {
            $this->jsonResponse(['status' => false, 'message' => $error]);
            return;
        }
        if ($this->Supplier_model->nama_dipakai($data['nama'])) {
            $this->jsonResponse(['status' => false, 'message' => 'Supplier "' . $data['nama'] . '" sudah ada.']);
            return;
        }
        $data['created_at'] = date('Y-m-d H:i:s');

        $ok = $this->Supplier_model->insert_supplier($data);
        $this->jsonResponse(
            $ok
                ? ['status' => true, 'message' => translate('message_sukses')]
                : ['status' => false, 'message' => translate('message_gagal')]
        );
    }

    // AJAX: ambil 1 supplier untuk modal edit
    public function get_by_id($id = 0)
    {
        $row = $this->Supplier_model->get_by_id((int) $id);
        $this->jsonResponse(
            $row
                ? ['status' => true, 'data' => $row]
                : ['status' => false, 'message' => 'Data tidak ditemukan!']
        );
    }

    // AJAX: update supplier
    public function update()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $id = (int) $this->input->post('id', true);
        if ($id <= 0 || !$this->Supplier_model->get_by_id($id)) {
            $this->jsonResponse(['status' => false, 'message' => 'Data tidak ditemukan!']);
            return;
        }
        list($data, $error) = $this->_ambil_input();
        if ($error !== '') {
            $this->jsonResponse(['status' => false, 'message' => $error]);
            return;
        }
        if ($this->Supplier_model->nama_dipakai($data['nama'], $id)) {
            $this->jsonResponse(['status' => false, 'message' => 'Supplier "' . $data['nama'] . '" sudah ada.']);
            return;
        }

        $ok = $this->Supplier_model->update_supplier($id, $data);
        $this->jsonResponse(
            $ok
                ? ['status' => true, 'message' => translate('message_update')]
                : ['status' => false, 'message' => translate('message_update_gagal')]
        );
    }

    // AJAX: hapus supplier (yang sudah dipakai di PO / SO tidak dihapus)
    public function delete($id = 0)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        if ((int) $id <= 0) {
            $this->jsonResponse(['status' => false, 'message' => 'ID tidak ditemukan!']);
            return;
        }
        list($ok, $pesan) = $this->Supplier_model->hapus((int) $id);
        $this->jsonResponse(['status' => $ok, 'message' => $pesan]);
    }

    // Ambil & validasi input form (tambah / edit). Return array(data, pesan_error)
    private function _ambil_input()
    {
        $nama      = trim((string) $this->input->post('nama', true));
        $kontak    = preg_replace('/[^0-9]/', '', (string) $this->input->post('kontak', true));
        $alamat    = trim((string) $this->input->post('alamat', true));
        $deskripsi = trim((string) $this->input->post('deskripsi', true));

        if ($nama === '' || $kontak === '' || $alamat === '' || $deskripsi === '') {
            return [[], 'Nama, kontak, deskripsi, dan alamat wajib diisi!'];
        }
        return [[
            'nama'      => mb_substr($nama, 0, 150),
            'kontak'    => mb_substr($kontak, 0, 30),
            'alamat'    => $alamat,
            'deskripsi' => $deskripsi,
        ], ''];
    }
}
