<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Lokasi file: application/controllers/super_admin/User.php
// URL akses: domain.com/super_admin/user

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
        $data['title'] = 'Manajemen Customer';
        $data['customers'] = $this->Customer_model->get_all_customer();

        $this->load->view('templates/header', $data);
        $this->load->view('super_admin/customer_grid_view', $data);
        $this->load->view('templates/footer', $data);
    }

    // Helper: selipkan csrf_hash terbaru ke setiap response JSON,
    // supaya JS di view bisa refresh token untuk request AJAX berikutnya.
    private function jsonResponse($payload)
    {
        $payload['csrf_hash'] = $this->security->get_csrf_hash();
        echo json_encode($payload);
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
                ? ['status' => true, 'message' => 'Data berhasil disimpan']
                : ['status' => false, 'message' => 'Gagal menyimpan data']
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
                ? ['status' => true, 'message' => 'Data berhasil diperbarui']
                : ['status' => false, 'message' => 'Gagal memperbarui data']
        );
    }

    // Delete data user via AJAX
    public function delete($id)
    {
        if (empty($id)) {
            $this->jsonResponse(['status' => false, 'message' => 'ID tidak ditemukan!']);
            return;
        }

        $delete = $this->Customer_model->delete_customer($id);

        $this->jsonResponse(
            $delete
                ? ['status' => true, 'message' => 'Data berhasil dihapus']
                : ['status' => false, 'message' => 'Gagal menghapus data']
        );
    }
}
