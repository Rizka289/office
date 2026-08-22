<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Super_admin extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->requireRole('super_admin'); // Hanya admin
    }

    public function index() {
        $data['title'] = 'Dashboard Super Admin';
        $data['user']  = $this->session->userdata();
        $this->load->view('super_admin/dashboard', $data);
    }
}