<?php defined('BASEPATH') or exit('No direct script access allowed');

// Extends MY_Controller (bukan CI_Controller lagi) supaya bahasa otomatis ke-load
class Login extends MY_Controller
{

    public function __construct()
    {
        parent::__construct(); // ini juga menjalankan _set_language() di MY_Controller
        $this->load->model('user_model');

        // Jika sudah login, redirect ke dashboard — KECUALI saat mengakses logout / ganti bahasa
        $free_methods = ['logout', 'lang'];
        if ($this->session->userdata('logged_in') && !in_array($this->router->fetch_method(), $free_methods, TRUE)) {
            redirect('dashboard');
        }
    }

    public function index()
    {
        
        $this->load->view('login');
    }

    public function proses()
    {
        $this->form_validation->set_rules(
            'username',
            lang('app_username'),
            'required|trim'
        );

        $this->form_validation->set_rules(
            'password',
            lang('app_password'),
            'required'
        );

        if ($this->form_validation->run() === FALSE) {
            $this->load->view('login');
            return;
        }

        $username = $this->input->post('username', TRUE);
        $password = $this->input->post('password');

        $user = $this->user_model->get_by_username($username);

        if ($user && password_verify($password, $user->password)) {

            $session = [
                'user_id'   => $user->id,
                'username'  => $user->username,
                'nama'      => $user->nama_lengkap,
                'role'      => $user->role,
                'foto'      => $user->foto,
                'logged_in' => TRUE
            ];

            $this->session->set_userdata($session);

            redirect('dashboard');
            return;
        }

        $this->session->set_flashdata(
            'error',
            lang('app_login_failed')
        );

        redirect('login');
    }

    public function logout()
    {
        $this->session->sess_destroy();
        redirect('login');
    }

    // Method lang($lang_code) untuk ganti bahasa (id/en/zh) sudah tersedia
    // otomatis dari MY_Controller — tidak perlu ditulis ulang di sini.
    // Contoh pemanggilan: site_url('login/lang/en')
}
