<?php defined('BASEPATH') or exit('No direct script access allowed');

class Sales_order extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Sales_order_model');
        $this->load->helper(array('url', 'form'));
        $this->load->library('session');
    }

    public function index()
    {
        $data['title']       = translate('pesanan_penjualan');
        $data['page_title']  =  translate('pesanan_penjualan');
        $data['active_menu'] = 'sales_order';

        $filter = array(
            'q'      => trim($this->input->get('q', true)),
            'dari'   => trim($this->input->get('dari', true)),
            'sampai' => trim($this->input->get('sampai', true)),
        );

        $data['sales_order_list'] = $this->Sales_order_model->get_all_sales_order($filter);
        $data['total_bulan_ini']  = count($data['sales_order_list']);

        $total_nilai = 0;
        foreach ($data['sales_order_list'] as $so) {
            $total_nilai += (float) $so->grand_total;
        }
        $data['total_nilai']   = $total_nilai;

        $data['filter_q']      = $filter['q'];
        $data['filter_dari']   = $filter['dari'];
        $data['filter_sampai'] = $filter['sampai'];

        $this->load->view('templates/header', $data);
        $this->load->view('penjualan/sales_order_index', $data);
        $this->load->view('templates/footer', $data);
    }

    public function detail($id = 0)
    {
        $so = $this->Sales_order_model->get_so_lengkap($id);
        if (!$so) {
            show_404();
        }

        $data['title']       = 'Detail ' . $so['header']->no_so;
        $data['page_title']  = 'Detail Sales Order';
        $data['active_menu'] = 'sales_order';
        $data['so']          = $so;

        $this->load->view('templates/header', $data);
        $this->load->view('penjualan/sales_order_detail', $data);
        $this->load->view('templates/footer', $data);
    }

    public function tambah()
    {
        $data['title']          = 'Buat Sales Order';
        $data['penawaran_list'] = $this->Sales_order_model->get_penawaran_siap();
        $data['no_so']          = $this->Sales_order_model->generate_no(date('Y-m-d'))['no_so'];

        $this->load->view('templates/header', $data);
        $this->load->view('penjualan/sales_order_add', $data);
        $this->load->view('templates/footer', $data);
    }

    // AJAX: data penawaran untuk ditampilkan di form (read-only)
    public function get_penawaran($id = 0)
    {
        $res = $this->Sales_order_model->get_penawaran_lengkap($id);
        $this->output->set_content_type('application/json')
            ->set_output(json_encode($res ? $res : array('error' => 'Penawaran tidak ditemukan')));
    }

    // AJAX: preview nomor SO saat tanggal diubah
    public function preview_no()
    {
        $tgl = $this->input->get('tanggal');
        if (!$tgl || !strtotime($tgl)) {
            $tgl = date('Y-m-d');
        }
        $no = $this->Sales_order_model->generate_no($tgl);
        $this->output->set_content_type('application/json')->set_output(json_encode(array('no_so' => $no['no_so'])));
    }

    public function simpan()
    {
        $id_penawaran = (int) $this->input->post('id_penawaran');
        $tanggal      = $this->input->post('tanggal');
        $termin       = $this->input->post('termin');
        $dp           = (float) $this->input->post('dp_persen');

        $termin_valid = array('100', '50-50', '30-70', 'custom');
        if (!$id_penawaran || !$tanggal || !strtotime($tanggal) || !in_array($termin, $termin_valid, true)) {
            $this->session->set_flashdata('error', 'Penawaran, tanggal, dan termin wajib diisi.');
            redirect('sales_order/tambah');
            return;
        }
        if ($termin === '100') {
            $dp = 0;
        }
        if ($termin === '50-50') {
            $dp = 50;
        }
        if ($termin === '30-70') {
            $dp = 30;
        }
        if ($dp < 0 || $dp > 100) {
            $this->session->set_flashdata('error', 'DP harus antara 0 dan 100 persen.');
            redirect('sales_order/tambah');
            return;
        }

        $input = array(
            'tanggal'        => date('Y-m-d', strtotime($tanggal)),
            'no_po_customer' => trim($this->input->post('no_po_customer', true)),
            'tgl_kirim'      => trim($this->input->post('tgl_kirim', true)),
            'alamat_kirim'   => trim($this->input->post('alamat_kirim', true)),
            'termin'         => $termin,
            'dp_persen'      => $dp,
            'catatan'        => trim($this->input->post('catatan', true)),
        );

        list($ok, $pesan) = $this->Sales_order_model->simpan_dari_penawaran(
            $id_penawaran,
            $input,
            $this->session->userdata('id_user')
        );

        if ($ok) {
            $this->session->set_flashdata('success', 'Sales Order ' . $pesan . ' berhasil dibuat.');
            redirect('sales_order');
        } else {
            $this->session->set_flashdata('error', $pesan);
            redirect('sales_order/tambah');
        }
    }
}
