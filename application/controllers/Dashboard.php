<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        // Sesuaikan role apa saja yang boleh akses dashboard
        $this->requireRole(['super_admin', 'staff_purchasing']);
    }

    public function index()
    {
        $data['title']        = translate('app_dashboard_title'); // dipakai untuk <title>
        $data['page_title']   = translate('app_dashboard_title');
       
        // WAJIB diisi supaya sidebar tahu menu mana yang harus di-highlight.
        // Kosongkan / sesuaikan jika dashboard tidak termasuk salah satu item menu.
        $data['active_menu']  = 'dashboard';

        $this->load->view('templates/header', $data);
        $this->load->view('dashboard/index', $data);
        $this->load->view('templates/footer', $data);
    }
}