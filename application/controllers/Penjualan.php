<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penjualan extends MY_Controller
{
    // Tarif pajak (persen). Dipakai untuk tampilan (view) dan perhitungan di server.
    const TARIF_PPN   = 11; // PPN, ditambahkan ke total
    const TARIF_PPH23 = 2;  // PPh 23, dipotong oleh customer dari pembayaran

    public function __construct()
    {
        parent::__construct();
        $this->requireRole('super_admin'); // hanya super admin yang boleh akses seluruh method di sini
        $this->load->model('Penjualan_model');
        $this->load->helper('url');
    }

    public function index()
    {
        $data['title']         = 'Penjualan Barang Jadi';
        $data['page_title']    = 'Penjualan Barang Jadi'; // dipakai templates/header (judul topbar & <title>)
        $data['active_menu']   = 'penjualan';             // supaya menu Penjualan terbuka & aktif di sidebar
        $data['no_faktur']     = 'PJ-' . date('YmdHis');
        $data['list_stok']     = $this->Penjualan_model->get_all_barang_stok();
        $data['list_customer'] = $this->Penjualan_model->get_list_customer();
        $data['tarif_ppn']     = self::TARIF_PPN;
        $data['tarif_pph23']   = self::TARIF_PPH23;

        $this->load->view('templates/header', $data);
        $this->load->view('penjualan/penjualan', $data);
        $this->load->view('templates/footer');
    }

    public function simpan()
    {
        $id_stok      = $this->input->post('id_stok', true); // Format: idBarang_idLocation
        $qty          = (int) $this->input->post('qty');
        $harga_satuan = (float) $this->input->post('harga_satuan');
        $id_customer  = (int) $this->input->post('id_customer');
        $pakai_ppn    = $this->input->post('pakai_ppn') ? 1 : 0;
     
        if (empty($id_stok) || strpos($id_stok, '_') === false || $qty <= 0 || $harga_satuan < 0) {
            $this->session->set_flashdata('error', 'Barang, Qty, dan Harga harus diisi dengan benar!');
            redirect('penjualan');
        }

        if ($id_customer <= 0) {
            $this->session->set_flashdata('error', 'Customer harus dipilih!');
            redirect('penjualan');
        }

        // Split id_barang dan id_location dari select option
        list($id_barang, $id_location) = explode('_', $id_stok, 2);

        // Hitung nominal di server (jangan percaya angka dari browser)
        $subtotal = $qty * $harga_satuan;
        $ppn      = $pakai_ppn   ? round($subtotal * self::TARIF_PPN / 100)   : 0; 
        $total    = $subtotal + $ppn; // total yang dibayar customer

        $data_header = [
            'no_faktur'   => $this->input->post('no_faktur'),
            'id_customer' => $id_customer,
            'tanggal'     => date('Y-m-d H:i:s'),
            'subtotal'    => $subtotal,
            'ppn'         => $ppn,
            'total_bayar' => $total,
            'created_by'  => $this->session->userdata('id_user') ?? 1
        ];

        $items = [
            [
                'id_barang'    => $id_barang,
                'id_location'  => $id_location,
                'qty'          => $qty,
                'harga_satuan' => $harga_satuan
            ]
        ];

        $simpan = $this->Penjualan_model->simpan_penjualan($data_header, $items);

        if ($simpan) {
            $this->session->set_flashdata('success', 'Transaksi penjualan berhasil diselesaikan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal memproses transaksi penjualan. Pastikan stok masih mencukupi.');
        }

        redirect('penjualan');
    }
}