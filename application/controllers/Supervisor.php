<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Supervisor extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->requireRole('supervisor'); // Hanya siswa
    }

    public function index() {
        $data['title'] = translate('i18n_dashboard_supervisor');
        $data['user']  = $this->session->userdata();
        $this->load->view('supervisor/dashboard', $data);
    }
}