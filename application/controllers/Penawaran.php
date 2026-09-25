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

        $data['header'] = $penawaran['header'];
        $data['items']  = $penawaran['items'];

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

    // Simpan penawaran baru: 1 baris ke `penawaran` (header) + N baris ke
    // `penawaran_detail` (item), dalam satu transaksi database.
    public function simpan()
    {
        $tanggal     = $this->input->post('tanggal', TRUE);
        $id_customer = (int) $this->input->post('id_customer');
        $catatan     = $this->input->post('catatan', TRUE);
        $no_surat    = $this->input->post('no_surat', TRUE);

        if (empty($tanggal) || $id_customer <= 0) {
            $this->session->set_flashdata('error', 'Tanggal dan Customer harus diisi!');
            redirect('penawaran/tambah');
        }

        // Ambil semua baris item dari form (array per-field, index sinkron per baris)
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

        // Hitung ulang semua nominal di server — jangan percaya angka dari
        // browser (luas m² & subtotal per baris hanya untuk tampilan di form).
        $items    = [];
        $subtotal = 0;

        foreach ($jenis_item as $i => $jenis) {
            $jenis = trim($jenis);
            if ($jenis === '') {
                continue; // baris kosong (tidak diisi) dilewati
            }

            $l   = max(0, (int) ($lebar_mm[$i] ?? 0));
            $t   = max(0, (int) ($tinggi_mm[$i] ?? 0));
            $q   = max(1, (int) ($qty[$i] ?? 1));
            $h   = max(0, (float) ($harga_unit[$i] ?? 0));
            $idb = (int) ($id_barang[$i] ?? 0);

            $luas  = round(($l * $t) / 1000000, 4); // mm² -> m²
            $total = $q * $h;

            $items[] = [
                // id_barang boleh kosong (0/null) kalau user isi item manual
                // tanpa memilih dari master Barang. Nama barang yang sudah
                // dipilih tetap tersimpan sebagai teks di jenis_item, jadi
                // histori penawaran tidak berubah walau data barang di
                // master diedit/dihapus di kemudian hari.
                'id_barang'      => $idb > 0 ? $idb : null,
                'jenis_item'     => $jenis,
                'warna'          => trim($warna[$i] ?? ''),
                'finishing'      => trim($finishing[$i] ?? ''),
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

        if (empty($items)) {
            $this->session->set_flashdata('error', 'Minimal harus ada 1 item penawaran yang diisi!');
            redirect('penawaran/tambah');
        }

        $ppn_persen  = $this->input->post('pakai_ppn') ? self::TARIF_PPN : 0;
        $pph_persen  = $this->input->post('pakai_pph') ? self::TARIF_PPH : 0;
        $ppn_nominal = round($subtotal * $ppn_persen / 100);
        $pph_nominal = round($subtotal * $pph_persen / 100);
        $grand_total = $subtotal + $ppn_nominal + $pph_nominal;

        $header = [
            // no_surat dipercaya dari form (dibuat readonly & sudah dijamin
            // urut oleh generate_no_penawaran() saat form dibuka). Kalau
            // kosong (mis. dikirim tanpa lewat form), buat baru sebagai jaga-jaga.
            'no_surat'    => !empty($no_surat) ? $no_surat : $this->Penawaran_model->generate_no_penawaran($tanggal),
            'tanggal'     => $tanggal,
            'id_customer' => $id_customer,
            'subtotal'    => $subtotal,
            'ppn_persen'  => $ppn_persen,
            'ppn_nominal' => $ppn_nominal,
            'pph_persen'  => $pph_persen,
            'pph_nominal' => $pph_nominal,
            'grand_total' => $grand_total,
            'catatan'     => $catatan,
            'id_user'     => $this->session->userdata('user_id') ?? 0,
        ];

        $id_penawaran = $this->Penawaran_model->simpan_penawaran($header, $items);

        if ($id_penawaran) {
            $this->session->set_flashdata('success', 'Penawaran ' . $header['no_surat'] . ' berhasil disimpan.');
            redirect('penawaran/detail/' . $id_penawaran);
        } else {
            $this->session->set_flashdata('error', 'Gagal menyimpan penawaran. Silakan coba lagi.');
            redirect('penawaran/tambah');
        }
    }
}