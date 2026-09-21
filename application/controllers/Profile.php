<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Profile extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->library('upload');
        $this->load->helper('url');
    }

    // Menampilkan Halaman View Profile
    public function index()
    {
        $user_id = $this->session->userdata('user_id');
        $data['title'] = 'Profil Saya';
        $data['user']  = $this->User_model->get_by_id($user_id);

        $this->load->view('templates/header', $data);
        $this->load->view('profile_view', $data);
        $this->load->view('templates/footer', $data);
    }

    // Memproses Update Profil & Upload Foto
    public function update()
    {
        $user_id = $this->session->userdata('user_id');
        $user    = $this->User_model->get_by_id($user_id); // AMBIL DATA USER DULU

        // Ambil input dari form
        $nama     = $this->input->post('nama', true);
        $username = $this->input->post('username', true);
        $password = $this->input->post('password', true);

        // Siapkan array data untuk update database
        $data_update = [
            'nama'     => $nama,
            'username' => $username,
        ];

        // Jika password diisi, update password baru
        if (!empty($password)) {
            $data_update['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        // Proses Unggah Foto Profil (Jika ada file yang dipilih)
        if (!empty($_FILES['foto']['name'])) {
            $config['upload_path']   = './uploads/profiles/';
            $config['allowed_types'] = 'jpg|jpeg|png|webp';
            $config['max_size']      = 15360; // 15 MB
            $config['encrypt_name']  = TRUE;

            if (!is_dir($config['upload_path'])) {
                mkdir($config['upload_path'], 0777, true);
            }

            $this->upload->initialize($config);

            if ($this->upload->do_upload('foto')) {
                $upload_data = $this->upload->data();
                $file_name   = $upload_data['file_name'];
                $full_path   = $upload_data['full_path'];

                // Kompresi & Resize Otomatis
                $img_config['image_library']  = 'gd2';
                $img_config['source_image']   = $full_path;
                $img_config['create_thumb']   = FALSE;
                $img_config['maintain_ratio'] = TRUE;
                $img_config['width']          = 800;
                $img_config['height']         = 800;
                $img_config['quality']        = '70%';

                $this->load->library('image_lib', $img_config);
                if ($this->image_lib->resize()) {
                    $this->image_lib->clear();
                }

                // Hapus foto lama di folder jika ada
                if (!empty($user->foto) && file_exists('./uploads/profiles/' . $user->foto)) {
                    unlink('./uploads/profiles/' . $user->foto);
                }

                // Masukkan nama file foto baru ke array update database & session
                $data_update['foto'] = $file_name;
                $this->session->set_userdata('foto', $file_name);
            } else {
                $this->session->set_flashdata('error', $this->upload->display_errors('', ''));
                redirect('profile');
                return;
            }
        }

        // 🚀 EKSEKUSI UPDATE KE DATABASE (BAGIAN INI YANG SEBELUMNYA TERTINGGAL)
        $update = $this->User_model->update_user($user_id, $data_update);

        if ($update) {
            // Update Session nama & username secara instan
            $this->session->set_userdata([
                'nama'     => $nama,
                'username' => $username
            ]);

            $this->session->set_flashdata('success', 'Profil berhasil diperbarui!');
        } else {
            $this->session->set_flashdata('error', 'Gagal memperbarui database.');
        }

        redirect('profile');
    }
}