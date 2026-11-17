<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Staff_purchasing extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->requireRole('staff_purchasing'); // Hanya staff purchasing
    }

    public function index() {
        $data['title'] = translate('i18n_dashboard_staff_purchasing');
        $data['user']  = $this->session->userdata();
        $this->load->view('staff_purchasing/dashboard', $data);
    }
}