<?php defined('BASEPATH') or exit('No direct script access allowed');

class Spk extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Spk_model');
        $this->load->helper(array('url', 'form'));
        $this->load->library('session');
    }

    public function index()
    {
        $data['title']       = 'Daftar SPK Produksi';
        $data['page_title']  = 'Daftar Surat Perintah Kerja';
        $data['active_menu'] = 'spk';

        $filter = array(
            'q'      => trim($this->input->get('q', true)),
            'dari'   => trim($this->input->get('dari', true)),
            'sampai' => trim($this->input->get('sampai', true)),
        );

        $data['spk_list']      = $this->Spk_model->get_all_spk($filter);
        $data['filter_q']      = $filter['q'];
        $data['filter_dari']   = $filter['dari'];
        $data['filter_sampai'] = $filter['sampai'];

        $this->load->view('templates/header', $data);
        $this->load->view('produksi/spk_index', $data);
        $this->load->view('templates/footer', $data);
    }

    public function detail($id = 0)
    {
        $spk = $this->Spk_model->get_spk_lengkap($id);
        if (!$spk) {
            show_404();
        }

        $data['title']       = 'Detail ' . $spk['header']->no_spk;
        $data['page_title']  = 'Detail Surat Perintah Kerja';
        $data['active_menu'] = 'spk';
        $data['spk']         = $spk;
        $data['list_barang_gudang'] = $this->Spk_model->get_all_stok();

        $this->load->view('templates/header', $data);
        $this->load->view('produksi/spk_detail', $data);
        $this->load->view('templates/footer', $data);
    }

    public function tambah()
    {
        $data['title']        = 'Buat SPK Baru';
        $data['page_title']   = 'Buat Surat Perintah Kerja';
        $data['active_menu']  = 'spk';
        $data['so_siap_list'] = $this->Spk_model->get_so_siap_spk();


        $this->load->view('templates/header', $data);
        $this->load->view('produksi/spk_add', $data);
        $this->load->view('templates/footer', $data);
    }

    public function simpan()
    {
        $this->_wajib_post();

        $id_so            = (int) $this->input->post('id_so');
        $tanggal_spk      = $this->input->post('tanggal_spk');
        $target_selesai   = $this->input->post('target_selesai');
        $catatan_produksi = trim($this->input->post('catatan_produksi', true));

        if (!$id_so || !$tanggal_spk || !strtotime($tanggal_spk)) {
            $this->session->set_flashdata('error', 'Sales Order dan tanggal SPK wajib diisi.');
            redirect('spk/tambah');
            return;
        }

        $input = array(
            'tanggal_spk'      => date('Y-m-d', strtotime($tanggal_spk)),
            'target_selesai'   => (!empty($target_selesai) && strtotime($target_selesai)) ? date('Y-m-d', strtotime($target_selesai)) : '',
            'catatan_produksi' => $catatan_produksi,
        );

        list($ok, $pesan) = $this->Spk_model->simpan_spk($id_so, $input, $this->_id_user());

        if ($ok) {
            $this->session->set_flashdata('success', 'SPK ' . $pesan . ' berhasil dibuat dan status SO diubah menjadi Produksi.');
            redirect('spk');
        } else {
            $this->session->set_flashdata('error', $pesan);
            redirect('spk/tambah');
        }
    }

    public function keluarkan_material($id_spk = 0)
    {
        $this->_wajib_post();
        $id_spk = (int) $id_spk;

        $tgl = $this->input->post('tanggal_keluar');
        $data_material = array(
            'id_spk_detail'   => (int) $this->input->post('id_spk_detail'),
            'id_barang_bahan' => (int) $this->input->post('id_barang_bahan'),
            'qty_pakai'       => (float) $this->input->post('qty_pakai'),
            'tanggal_keluar'  => ($tgl && strtotime($tgl)) ? date('Y-m-d', strtotime($tgl)) : '',
            'keterangan'      => trim($this->input->post('keterangan', true)),
        );

        if (!$id_spk
            || !$data_material['id_spk_detail']
            || !$data_material['id_barang_bahan']
            || $data_material['qty_pakai'] <= 0
            || $data_material['tanggal_keluar'] === '') {
            $this->session->set_flashdata('error', 'Barang, qty, dan tanggal keluar wajib diisi dengan benar.');
            redirect('spk/detail/' . $id_spk);
            return;
        }

        list($ok, $pesan) = $this->Spk_model->catat_pengeluaran_material($id_spk, $data_material, $this->_id_user());

        $this->session->set_flashdata($ok ? 'success' : 'error', $pesan);
        redirect('spk/detail/' . $id_spk);
    }

    // Kembalikan sisa bahan (yang sudah keluar gudang) ke gudang
    public function retur_material($id_spk = 0)
    {
        $this->_wajib_post();
        $id_spk = (int) $id_spk;

        $tgl  = $this->input->post('tanggal_retur');
        $data = array(
            'id_material'   => (int) $this->input->post('id_material'),
            'qty_retur'     => (float) $this->input->post('qty_retur'),
            'tanggal_retur' => ($tgl && strtotime($tgl)) ? date('Y-m-d', strtotime($tgl)) : '',
            'keterangan'    => trim($this->input->post('keterangan', true)),
        );

        if (!$id_spk || !$data['id_material'] || $data['qty_retur'] <= 0 || $data['tanggal_retur'] === '') {
            $this->session->set_flashdata('error', 'Bahan, qty retur, dan tanggal wajib diisi dengan benar.');
            redirect('spk/detail/' . $id_spk);
            return;
        }

        list($ok, $pesan) = $this->Spk_model->retur_material($id_spk, $data, $this->_id_user());
        $this->session->set_flashdata($ok ? 'success' : 'error', $pesan);
        redirect('spk/detail/' . $id_spk);
    }

    public function ubah_tahap($id_spk = 0)
    {
        $this->_wajib_post();
        $id_spk   = (int) $id_spk;
        $id_tahap = (int) $this->input->post('id_tahap');
        $aksi     = $this->input->post('aksi', true);

        if (!$id_spk || !$id_tahap || !in_array($aksi, array('mulai', 'selesai', 'buka'), true)) {
            $this->session->set_flashdata('error', 'Data tahapan tidak valid.');
            redirect('spk/detail/' . $id_spk);
            return;
        }

        list($ok, $pesan) = $this->Spk_model->ubah_tahap($id_spk, $id_tahap, $aksi, $this->_id_user());
        $this->session->set_flashdata($ok ? 'success' : 'error', $pesan);
        redirect('spk/detail/' . $id_spk);
    }

    public function ubah_status($id_spk = 0, $status = '')
    {
        $this->_wajib_post();
        $id_spk = (int) $id_spk;

        list($ok, $pesan) = $this->Spk_model->ubah_status_spk($id_spk, $status);
        $this->session->set_flashdata($ok ? 'success' : 'error', $pesan);
        redirect('spk/detail/' . $id_spk);
    }

    private function _wajib_post()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
    }

    private function _id_user()
    {
        foreach (array('id_user', 'user_id', 'id') as $k) {
            $v = $this->session->userdata($k);
            if ($v) {
                return (int) $v;
            }
        }
        return null;
    }
}