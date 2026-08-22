<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Staff_gudang extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->requireRole('staff_gudang'); // Hanya staff gudang
    }

    public function index() {
        $data['title'] = 'Dashboard Staff Gudang';
        $data['user']  = $this->session->userdata();
        $this->load->view('staff_gudang/dashboard', $data);
    }
}