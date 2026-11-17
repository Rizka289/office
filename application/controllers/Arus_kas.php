<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * LAPORAN ARUS KAS (menu Keuangan)
 *
 * Gabungan semua uang masuk (pembayaran customer + pemasukan lain & modal)
 * dan uang keluar (pembayaran supplier + biaya operasional) per bulan,
 * lengkap dengan saldo awal & akhir menurut catatan sistem.
 */
class Arus_kas extends MY_Controller
{
    private $izin_lihat = array('super_admin', 'atasan', 'finance');

    public function __construct()
    {
        parent::__construct();
        $this->requireRole($this->izin_lihat);
        $this->load->model('Arus_kas_model');
        $this->load->helper(array('url', 'form'));
    }

    public function index()
    {
        $tahun = (int) $this->input->get('tahun');
        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = (int) date('Y');
        }

        $data['title']       = translate('i18n_ak_judul') . ' ' . $tahun;
        $data['page_title']  = $data['title'];
        $data['tahun']       = $tahun;
        $data['baris']       = $this->Arus_kas_model->per_bulan($tahun);
        $data['tidak_ada']   = $this->Arus_kas_model->tidak_ada;
        $data['saldo_awal']  = $this->Arus_kas_model->saldo_sebelum($tahun);
        $data['active_menu'] = 'arus_kas';

        $this->load->view('templates/header', $data);
        $this->load->view('arus_kas/index', $data);
        $this->load->view('templates/footer', $data);
    }
}
