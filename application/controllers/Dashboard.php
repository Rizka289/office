<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller {

    public function index() {
        $role = $this->session->userdata('role');
        
        switch ($role) {
            case 'super_admin':
                redirect('super_admin');
                break;
            case 'staff_gudang':
                redirect('staff_gudang');
                break;
            case 'supervisor':
                redirect('supervisor');
                break;
            default:
                $this->session->sess_destroy();
                redirect('login');
        }
    }
}