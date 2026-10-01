<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penawaran extends MY_Controller
{
    // Tarif pajak (persen) — dipakai untuk tampilan (view) dan perhitungan
    // di server. Nilainya sama dengan default kolom ppn_persen/pph_persen
    // di tabel `penawaran`. Ubah tarif di sini saja.
    const TARIF_PPN = 11;    // PPN, ditambahkan ke total
    const TARIF_PPH = 2.5;   // PPh, ditambahkan ke total (sesuai data existing)

    public function __construct()
    {
        parent::__construct();
        $this->requireRole('super_admin'); // hanya super admin yang boleh akses seluruh method di sini
        $this->load->model('Penawaran_model');
        $this->load->helper('url');
    }

    public function index()
    {
        $data['title']        = translate('penawaran');
        $data['page_title']   = translate('penawaran'); // dipakai templates/header (judul topbar & <title>)
        $data['active_menu']  = 'penawaran';             // supaya menu Penjualan terbuka & aktif di sidebar

        $filter = [
            'q'      => $this->input->get('q', TRUE),
            'dari'   => $this->input->get('dari', TRUE),
            'sampai' => $this->input->get('sampai', TRUE),
        ];

        $data['filter_q']      = $filter['q'];
        $data['filter_dari']   = $filter['dari'];
        $data['filter_sampai'] = $filter['sampai'];

        $penawaran_list = $this->Penawaran_model->get_all_penawaran($filter);

        // Tabel `penawaran` tidak punya kolom status, jadi statistik yang
        // relevan untuk ditampilkan adalah jumlah dokumen & total nilai,
        // bukan draft/selesai (kolom itu tidak ada di database).
        $total_nilai = 0;
        foreach ($penawaran_list as $row) {
            $total_nilai += (float) $row->grand_total;
        }

        // PENTING: key ini HARUS 'penawaran_list' karena itu yang dipakai
        // oleh view penawaran_index.php. Sebelumnya key-nya salah
        // ('penerimaan_list') sehingga tabel selalu tampil kosong.
        $data['penawaran_list']  = $penawaran_list;
        $data['total_bulan_ini'] = count($penawaran_list);
        $data['total_nilai']     = $total_nilai;

        $this->load->view('templates/header', $data);
        $this->load->view('penjualan/penawaran_index', $data);
        $this->load->view('templates/footer', $data);
    }

    // Catatan: method simpan() yang sebelumnya ada di sini (id_stok, qty,
    // Penjualan_model, redirect ke 'penjualan') adalah kode modul Penjualan
    // yang salah tempel di controller Penawaran ini. Method tersebut sudah
    // dihapus karena tidak relevan dan akan fatal error jika dipanggil
    // (Penjualan_model tidak pernah di-load di constructor Penawaran).
    //
    // Jika Anda memang butuh method untuk menyimpan penawaran baru
    // (header + item ke tabel `penawaran` & `penawaran_detail`), itu perlu
    // dibuat terpisah — beri tahu saya kalau mau saya buatkan sekalian.

    // Halaman detail (di dalam aplikasi, ada sidebar/topbar) — untuk
    // mengecek data penawaran sebelum dicetak/dikirim ke customer.
    public function detail($id)
    {
        $id = (int) $id;

        $penawaran = $this->Penawaran_model->get_penawaran_detail($id);

        if (!$penawaran) {
            $this->session->set_flashdata('error', 'Data penawaran tidak ditemukan.');
            redirect('penawaran');
        }

        $data['title']       = translate('penawaran');
        $data['page_title']  = translate('penawaran');
        $data['active_menu'] = 'penawaran';
        $data['header']      = $penawaran['header'];
        $data['items']       = $penawaran['items'];

        $this->load->view('templates/header', $data);
        $this->load->view('penjualan/penawaran_detail', $data);
        $this->load->view('templates/footer', $data);
    }

    // Halaman cetak — berdiri sendiri (tanpa sidebar/topbar aplikasi),
    // dibuka di tab baru lalu di-print ke PDF lewat browser (Ctrl+P).
    public function cetak($id)
    {
        $id = (int) $id;

        $penawaran = $this->Penawaran_model->get_penawaran_detail($id);

        if (!$penawaran) {
            show_404();
        }

        // Termin pembayaran untuk Syarat & Ketentuan (dipilih saat klik
        // Cetak PDF). Hanya nilai di whitelist yang diterima; default 50_50.
        $termin = $this->input->get('termin', TRUE);

        $data['header'] = $penawaran['header'];
        $data['items']  = $penawaran['items'];
        $data['termin'] = in_array($termin, ['50_50', '100'], TRUE) ? $termin : '50_50';

        $this->load->view('penjualan/penawaran_cetak', $data);
    }

    // Form tambah penawaran baru.
    public function tambah()
    {
        $data['title']       = translate('penawaran');
        $data['page_title']  = translate('penawaran');
        $data['active_menu'] = 'penawaran';

        $data['no_surat']      = $this->Penawaran_model->generate_no_penawaran();
        $data['list_customer'] = $this->Penawaran_model->get_all_customer();
        $data['list_barang']   = $this->Penawaran_model->get_all_barang();
        $data['tarif_ppn']     = self::TARIF_PPN;
        $data['tarif_pph']     = self::TARIF_PPH;

        $this->load->view('templates/header', $data);
        $this->load->view('penjualan/add_penawaran', $data);
        $this->load->view('templates/footer', $data);
    }

    // Kumpulkan item dari POST lalu hitung ulang semua nominal di server
    // (jangan percaya angka dari browser). Dipakai oleh simpan() & update().
    // Return: [$items, $subtotal]
    private function _kumpulkan_item()
    {
        $id_barang      = $this->input->post('id_barang')      ?: [];
        $jenis_item     = $this->input->post('jenis_item')     ?: [];
        $warna          = $this->input->post('warna')          ?: [];
        $finishing      = $this->input->post('finishing')      ?: [];
        $komponen_kusen = $this->input->post('komponen_kusen') ?: [];
        $komponen_daun  = $this->input->post('komponen_daun')  ?: [];
        $lebar_mm       = $this->input->post('lebar_mm')       ?: [];
        $tinggi_mm      = $this->input->post('tinggi_mm')      ?: [];
        $qty            = $this->input->post('qty')            ?: [];
        $harga_unit     = $this->input->post('harga_unit')     ?: [];
        $keterangan     = $this->input->post('keterangan')     ?: [];

        // Nama & komponen barang dipaksa sama dengan master (field di form
        // hanya readonly, masih bisa diakali lewat DevTools).
        $master_barang = $this->Penawaran_model->get_barang_by_ids($id_barang);

        $items    = [];
        $subtotal = 0;

        foreach ($jenis_item as $i => $jenis) {
            $jenis = trim($jenis);
            if ($jenis === '') {
                continue; // baris kosong dilewati
            }

            $l   = max(0, (int) ($lebar_mm[$i] ?? 0));
            $t   = max(0, (int) ($tinggi_mm[$i] ?? 0));
            $q   = max(1, (int) ($qty[$i] ?? 1));
            $h   = max(0, (float) ($harga_unit[$i] ?? 0));
            $idb = (int) ($id_barang[$i] ?? 0);

            if ($idb > 0 && isset($master_barang[$idb])) {
                $jenis         = $master_barang[$idb]['nama'];
                $komponen_item = trim((string) ($master_barang[$idb]['komponen'] ?? ''));
            } else {
                $idb           = 0; // item manual / id tidak valid
                $komponen_item = '';
            }

            $luas  = round(($l * $t) / 1000000, 4); // mm² -> m²
            $total = $q * $h;

            $fin_val = trim($finishing[$i] ?? '');
            if ($fin_val === '') {
                $fin_val = 'Powder Coating / 粉末涂料';
            }

            $items[] = [
                'id_barang'      => $idb > 0 ? $idb : null,
                'jenis_item'     => $jenis,
                'warna'          => trim($warna[$i] ?? ''),
                'komponen'       => $komponen_item,
                'finishing'      => $fin_val,
                'komponen_kusen' => trim($komponen_kusen[$i] ?? ''),
                'komponen_daun'  => trim($komponen_daun[$i] ?? ''),
                'lebar_mm'       => $l,
                'tinggi_mm'      => $t,
                'luas_m2'        => $luas,
                'qty'            => $q,
                'harga_unit'     => $h,
                'total_harga'    => $total,
                'keterangan'     => trim($keterangan[$i] ?? ''),
            ];

            $subtotal += $total;
        }

        return [$items, $subtotal];
    }

    // Hitung PPN, PPh & grand total dari subtotal (sesuai checkbox di form).
    // Saat edit, checkbox pajak juga boleh diubah (selama belum approve),
    // jadi persen dibaca dari POST sama seperti saat tambah.
    private function _hitung_total($subtotal, $ppn_persen = null, $pph_persen = null)
    {
        if ($ppn_persen === null) {
            $ppn_persen = $this->input->post('pakai_ppn') ? self::TARIF_PPN : 0;
        }
        if ($pph_persen === null) {
            $pph_persen = $this->input->post('pakai_pph') ? self::TARIF_PPH : 0;
        }

        // Biaya pemasangan & pengiriman (ongkir): switch mati -> NULL (S&K cetak
        // "belum termasuk"); switch hidup -> angka >= 0 (S&K cetak "sudah termasuk").
        // Nominal diabaikan di server kalau switch-nya tidak dicentang.
        $biaya_pasang = $this->input->post('pakai_pasang')
            ? max(0, (float) $this->input->post('biaya_pasang')) : null;
        $biaya_ongkir = $this->input->post('pakai_ongkir')
            ? max(0, (float) $this->input->post('biaya_ongkir')) : null;

        // Biaya tambahan ikut menjadi dasar perhitungan PPN/PPh
        $dasar_pajak = $subtotal + (float) $biaya_pasang + (float) $biaya_ongkir;

        $ppn_nominal = round($dasar_pajak * $ppn_persen / 100);
        $pph_nominal = round($dasar_pajak * $pph_persen / 100);

        return [
            'subtotal'     => $subtotal,
            'biaya_pasang' => $biaya_pasang,
            'biaya_ongkir' => $biaya_ongkir,
            'ppn_persen'   => $ppn_persen,
            'ppn_nominal'  => $ppn_nominal,
            'pph_persen'   => $pph_persen,
            'pph_nominal'  => $pph_nominal,
            'grand_total'  => $dasar_pajak + $ppn_nominal + $pph_nominal,
        ];
    }

    // Simpan penawaran baru: 1 baris ke `penawaran` (header) + N baris ke
    // `penawaran_detail` (item), dalam satu transaksi database.
    // Penawaran baru selalu berstatus 'draft' (belum approve).
    public function simpan()
    {
        $tanggal     = $this->input->post('tanggal', TRUE);
        $id_customer = (int) $this->input->post('id_customer');
        $catatan     = $this->input->post('catatan', TRUE);

        if (empty($tanggal) || $id_customer <= 0) {
            $this->session->set_flashdata('error', 'Tanggal dan Customer harus diisi!');
            redirect('penawaran/tambah');
        }

        list($items, $subtotal) = $this->_kumpulkan_item();

        if (empty($items)) {
            $this->session->set_flashdata('error', 'Minimal harus ada 1 item penawaran yang diisi!');
            redirect('penawaran/tambah');
        }

        $header = array_merge([
            'no_surat'    => $this->Penawaran_model->generate_no_penawaran($tanggal), // selalu dibuat ulang di server sesuai bulan/tahun tanggal penawaran
            'tanggal'     => $tanggal,
            'id_customer' => $id_customer,
            'catatan'     => $catatan,
            'status'      => 'draft',
            'id_user'     => $this->session->userdata('user_id') ?? 0,
        ], $this->_hitung_total($subtotal));

        $id_penawaran = $this->Penawaran_model->simpan_penawaran($header, $items);

        if ($id_penawaran) {
            $this->session->set_flashdata('success', 'Penawaran ' . $header['no_surat'] . ' berhasil disimpan.');
            redirect('penawaran/detail/' . $id_penawaran);
        } else {
            $this->session->set_flashdata('error', 'Gagal menyimpan penawaran. Silakan coba lagi.');
            redirect('penawaran/tambah');
        }
    }

    // Form edit penawaran. Hanya boleh kalau BELUM di-approve.
    public function edit($id)
    {
        $id        = (int) $id;
        $penawaran = $this->Penawaran_model->get_penawaran_detail($id);

        if (!$penawaran) {
            $this->session->set_flashdata('error', 'Data penawaran tidak ditemukan.');
            redirect('penawaran');
        }

        if (($penawaran['header']->status ?? 'draft') === 'approved') {
            $this->session->set_flashdata('error', 'Penawaran ini sudah di-approve, data tidak bisa diedit lagi.');
            redirect('penawaran/detail/' . $id);
        }

        $data['title']         = translate('penawaran');
        $data['page_title']    = translate('penawaran');
        $data['active_menu']   = 'penawaran';
        $data['mode']          = 'edit';
        $data['penawaran_edit'] = $penawaran['header'];
        $data['items_edit']    = $penawaran['items'];
        $data['no_surat']      = $penawaran['header']->no_surat;
        $data['list_customer'] = $this->Penawaran_model->get_all_customer();
        $data['list_barang']   = $this->Penawaran_model->get_all_barang();
        $data['tarif_ppn']     = self::TARIF_PPN;
        $data['tarif_pph']     = self::TARIF_PPH;

        $this->load->view('templates/header', $data);
        $this->load->view('penjualan/add_penawaran', $data);
        $this->load->view('templates/footer', $data);
    }

    // Simpan hasil edit. Hanya ITEM yang berubah (header dikunci). Ditolak di
    // controller (cek cepat) dan di model (cek final dalam transaksi)
    // kalau penawaran sudah di-approve.
    public function update($id)
    {
        $id        = (int) $id;
        $penawaran = $this->Penawaran_model->get_penawaran_detail($id);

        if (!$penawaran) {
            $this->session->set_flashdata('error', 'Data penawaran tidak ditemukan.');
            redirect('penawaran');
        }

        if (($penawaran['header']->status ?? 'draft') === 'approved') {
            $this->session->set_flashdata('error', 'Penawaran ini sudah di-approve, data tidak bisa diedit lagi.');
            redirect('penawaran/detail/' . $id);
        }

        // Header (tanggal, customer, catatan, no. surat) TIDAK ikut diubah
        // saat edit. Yang boleh berubah: item dan pajak (PPN/PPh), selama
        // penawaran belum di-approve.
        list($items, $subtotal) = $this->_kumpulkan_item();

        if (empty($items)) {
            $this->session->set_flashdata('error', 'Minimal harus ada 1 item penawaran yang diisi!');
            redirect('penawaran/edit/' . $id);
        }

        // Yang berubah di header: subtotal, persen & nominal pajak, grand total
        // (dihitung ulang dari item baru + checkbox PPN/PPh yang dikirim form).
        $header = $this->_hitung_total($subtotal);

        if ($this->Penawaran_model->update_penawaran($id, $header, $items)) {
            $this->session->set_flashdata('success', 'Penawaran ' . $penawaran['header']->no_surat . ' berhasil diperbarui.');
            redirect('penawaran/detail/' . $id);
        }

        $this->session->set_flashdata('error', $this->Penawaran_model->get_last_error() ?: 'Gagal memperbarui penawaran.');
        redirect('penawaran/detail/' . $id);
    }

    // Approve penawaran (hanya lewat POST). Setelah approve, edit ditolak.
    public function approve($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
        }

        $id = (int) $id;

        if ($this->Penawaran_model->approve_penawaran($id, $this->session->userdata('user_id') ?? 0)) {
            $this->session->set_flashdata('success', 'Penawaran berhasil di-approve. Data tidak bisa diedit lagi.');
        } else {
            $this->session->set_flashdata('error', 'Penawaran tidak ditemukan atau sudah di-approve.');
        }

        redirect('penawaran/detail/' . $id);
    }
}