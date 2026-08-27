<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Lokasi file: application/controllers/super_admin/User.php
// URL akses: domain.com/super_admin/user

class User extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRole('super_admin'); // hanya super admin yang boleh akses seluruh method di sini
        $this->load->model('User_model');
        $this->load->helper('url');
    }

    public function index()
    {
        $data['title'] = 'Manajemen User';
        $data['users'] = $this->User_model->get_all_users();

        $this->load->view('templates/header', $data);
        $this->load->view('super_admin/user_grid_view', $data);
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
        $username = $this->input->post('username', true);
        $password = $this->input->post('password', true);
        $role     = $this->input->post('role', true);

        if (empty($nama) || empty($username) || empty($password) || empty($role)) {
            $this->jsonResponse(['status' => false, 'message' => 'Semua field wajib diisi!']);
            return;
        }

        $data = array(
            'nama'       => $nama,
            'username'   => $username,
            'password'   => password_hash($password, PASSWORD_BCRYPT),
            'role'       => $role,
            'created_at' => date('Y-m-d H:i:s')
        );

        $simpan = $this->User_model->insert_user($data);

        $this->jsonResponse($simpan
            ? ['status' => true, 'message' => 'Data berhasil disimpan']
            : ['status' => false, 'message' => 'Gagal menyimpan data']
        );
    }

    // Ambil data user berdasarkan ID (untuk form edit modal)
    public function get_by_id($id)
    {
        $user = $this->User_model->get_by_id($id);
        $this->jsonResponse($user
            ? ['status' => true, 'data' => $user]
            : ['status' => false, 'message' => 'Data tidak ditemukan!']
        );
    }

    // Update data user via AJAX
    public function update()
    {
        $id       = $this->input->post('id', true);
        $nama     = $this->input->post('nama', true);
        $username = $this->input->post('username', true);
        $password = $this->input->post('password', true);
        $role     = $this->input->post('role', true);

        if (empty($id) || empty($nama) || empty($username) || empty($role)) {
            $this->jsonResponse(['status' => false, 'message' => 'Field nama, username, dan role wajib diisi!']);
            return;
        }

        $data = array(
            'nama'     => $nama,
            'username' => $username,
            'role'     => $role
        );

        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $update = $this->User_model->update_user($id, $data);

        $this->jsonResponse($update
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

        $delete = $this->User_model->delete_user($id);

        $this->jsonResponse($delete
            ? ['status' => true, 'message' => 'Data berhasil dihapus']
            : ['status' => false, 'message' => 'Gagal menghapus data']
        );
    }
}