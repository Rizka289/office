<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penawaran extends MY_Controller
{
    const TARIF_PPN = 11;
    // PPh 23 atas jasa pemasangan: hanya boleh dipakai kalau ada biaya pemasangan,
    // dan DIPOTONG customer (mengurangi grand total), bukan ditambahkan.
    const TARIF_PPH = 2;
    const DEFAULT_TIPE_MOTOR_ROLLING  = 'Electra 600';
    const DEFAULT_POWER_ROLLING       = '250 W';
    const DEFAULT_VOLTAGE_ROLLING     = '220 V - 50 Hz';
    const DEFAULT_KELENGKAPAN_ROLLING = "3 Bh Remote hand / 3 Bh Remote hand / 3个手持遥控器\n1 Bh Remote dinding / 1个壁挂遥控器\nRantai Emergency / 紧急链条";
    const DEFAULT_BOOKS_PENUTUP_ROLLING = "Hollow 30 x 30 x 1.0 mm / 角钢 30 x 30 x 1.0 毫米\nPlat besi galvanis 0.5 mm / 镀锌铁板 0.5 毫米";

    // Folder foto item penawaran manual (relatif terhadap FCPATH & base_url)
    const FOLDER_FOTO = 'uploads/penawaran/';

    // Pesan error upload foto (ditampilkan setelah redirect)
    private $foto_errors = [];

    public function __construct()
    {
        parent::__construct();
        // purchasing = admin penawaran; atasan ikut melihat & approve penawaran
        $this->requireRole(['super_admin', 'atasan', 'staff_purchasing']);
        $this->load->model('Penawaran_model');
        $this->load->helper('url');
    }

    public function index()
    {
        $data['title']       = translate('penawaran');
        $data['page_title']  = translate('penawaran');
        $data['active_menu'] = 'penawaran';

        $filter = [
            'q'      => $this->input->get('q', TRUE),
            'dari'   => $this->input->get('dari', TRUE),
            'sampai' => $this->input->get('sampai', TRUE),
        ];

        $data['filter_q']      = $filter['q'];
        $data['filter_dari']   = $filter['dari'];
        $data['filter_sampai'] = $filter['sampai'];

        // Pagination: 5 data per halaman (halaman dipilih lewat ?page=N)
        $this->load->library('pagination');

        $per_page   = 5;
        $total_data = $this->Penawaran_model->count_penawaran($filter);
        $total_page = max(1, (int) ceil($total_data / $per_page));
        $page       = min(max(1, (int) $this->input->get('page')), $total_page);
        $offset     = ($page - 1) * $per_page;

        $this->pagination->initialize([
            'base_url'             => site_url('penawaran'),
            'total_rows'           => $total_data,
            'per_page'             => $per_page,
            'page_query_string'    => TRUE,
            'query_string_segment' => 'page',
            'use_page_numbers'     => TRUE,
            'reuse_query_string'   => TRUE,   // q, dari, sampai ikut terbawa
            'num_links'            => 2,
            'full_tag_open'        => '<ul class="pagination pagination-sm mb-0">',
            'full_tag_close'       => '</ul>',
            'first_tag_open'       => '<li class="page-item">',
            'first_tag_close'      => '</li>',
            'last_tag_open'        => '<li class="page-item">',
            'last_tag_close'       => '</li>',
            'next_tag_open'        => '<li class="page-item">',
            'next_tag_close'       => '</li>',
            'prev_tag_open'        => '<li class="page-item">',
            'prev_tag_close'       => '</li>',
            'num_tag_open'         => '<li class="page-item">',
            'num_tag_close'        => '</li>',
            'cur_tag_open'         => '<li class="page-item active"><a class="page-link" href="#">',
            'cur_tag_close'        => '</a></li>',
            'attributes'           => ['class' => 'page-link'],
            'first_link'           => '&laquo;',
            'last_link'            => '&raquo;',
            'next_link'            => '&rsaquo;',
            'prev_link'            => '&lsaquo;',
        ]);

        $penawaran_list = $this->Penawaran_model->get_all_penawaran($filter, $per_page, $offset);

        $data['pagination']         = $this->pagination->create_links();
        $data['total_data']         = $total_data;
        $data['nomor_awal']         = $offset + 1;
        $data['jumlah_ditampilkan'] = count($penawaran_list);
        $ringkasan      = $this->Penawaran_model->get_ringkasan_bulan_ini();

        $data['penawaran_list'] = $penawaran_list;
        $data['total_semua']    = $ringkasan['total'];     // semua penawaran bulan ini
        $data['total_approved'] = $ringkasan['approved'];  // sudah approved bulan ini
        $data['total_belum']    = $ringkasan['belum'];     // belum approved bulan ini
        $data['total_nilai']    = $ringkasan['nilai'];     // nilai uang yang approved bulan ini
        // $data['rekap_bulanan']  = $this->Penawaran_model->get_rekap_per_bulan(12); untuk rsekap 12 bulan
        $data['rekap_bulanan']  = $this->Penawaran_model->get_rekap_bulan_ini_saja();

        $this->load->view('templates/header', $data);
        $this->load->view('penjualan/penawaran_index', $data);
        $this->load->view('templates/footer', $data);
    }

    public function detail($id)
    {
        $id = (int)$id;
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

    public function cetak($id)
    {
        $id = (int)$id;
        $penawaran = $this->Penawaran_model->get_penawaran_detail($id);

        if (!$penawaran) {
            show_404();
        }

        $termin = $this->input->get('termin', TRUE);

        $data['header'] = $penawaran['header'];
        $data['items']  = $penawaran['items'];
        $data['termin'] = in_array($termin, ['50_50', '100'], TRUE) ? $termin : '50_50';

        $this->load->view('penjualan/penawaran_cetak', $data);
    }

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
        $data['tipe']          = ($this->input->get('tipe') === 'manual') ? 'manual' : 'rumus';

        $this->load->view('templates/header', $data);
        $this->load->view('penjualan/add_penawaran', $data);
        $this->load->view('templates/footer', $data);
    }

    private function _kumpulkan_item($tipe = 'rumus')
    {
        if ($tipe === 'manual') {
            return $this->_kumpulkan_item_manual();
        }

        $id_barang      = $this->input->post('id_barang', TRUE)      ?: [];
        $jenis_item     = $this->input->post('jenis_item', TRUE)     ?: [];
        $warna          = $this->input->post('warna', TRUE)          ?: [];
        $finishing      = $this->input->post('finishing', TRUE)      ?: [];
        $komponen_kusen = $this->input->post('komponen_kusen', TRUE) ?: [];
        $komponen_daun  = $this->input->post('komponen_daun', TRUE)  ?: [];
        $lebar_mm       = $this->input->post('lebar_mm', TRUE)       ?: [];
        $tinggi_mm      = $this->input->post('tinggi_mm', TRUE)      ?: [];
        $qty            = $this->input->post('qty', TRUE)            ?: [];
        $harga_unit     = $this->input->post('harga_unit', TRUE)     ?: [];
        $keterangan     = $this->input->post('keterangan', TRUE)     ?: [];
        $tipe_motor     = $this->input->post('tipe_motor', TRUE)     ?: [];
        $input_power    = $this->input->post('input_power', TRUE)    ?: [];
        $voltage        = $this->input->post('voltage', TRUE)        ?: [];
        $books_penutup  = $this->input->post('books_penutup', TRUE)  ?: [];
        $kelengkapan    = $this->input->post('kelengkapan', TRUE)    ?: [];
        $komponen       = $this->input->post('komponen', TRUE)       ?: [];
        $aks_nama       = $this->input->post('aks_nama', TRUE)       ?: [];
        $aks_qty        = $this->input->post('aks_qty', TRUE)        ?: [];
        $aks_harga      = $this->input->post('aks_harga', TRUE)      ?: [];
        $lebar_m2       = $this->input->post('lebar_m2_books', TRUE) ?: [];
        $tinggi_m2      = $this->input->post('tinggi_m2_books', TRUE) ?: [];
        $qty_m2         = $this->input->post('qty_books', TRUE)      ?: [];
        $tebal         = $this->input->post('tebal', TRUE)      ?: [];
        $harga_books    = $this->input->post('harga_m2_books', TRUE) ?: [];
        $qty_instalasi = $this->input->post('qty_instalasi', TRUE) ?: [];
        $harga_instalasi = $this->input->post('harga_m2_instalasi', TRUE) ?: [];
        $kaca_nama      = $this->input->post('kaca_nama', TRUE)      ?: [];
        $kaca_luas      = $this->input->post('kaca_luas', TRUE)      ?: [];
        $kaca_harga     = $this->input->post('kaca_harga', TRUE)     ?: [];

        $master_barang = $this->Penawaran_model->get_barang_by_ids($id_barang);

        $items    = [];
        $subtotal = 0;

        foreach ($jenis_item as $i => $jenis) {
            $jenis = trim($jenis);
            if ($jenis === '') {
                continue;
            }

            $l   = max(0, (int) ($lebar_mm[$i] ?? 0));
            $t   = max(0, (int) ($tinggi_mm[$i] ?? 0));
            $q   = max(1, (int) ($qty[$i] ?? 1));
            $idb = (int) ($id_barang[$i] ?? 0);

            if ($idb > 0 && isset($master_barang[$idb])) {
                $jenis          = $master_barang[$idb]['nama'];
                $komponen_item  = array_key_exists($i, $komponen)
                    ? trim((string) $komponen[$i])
                    : trim((string) ($master_barang[$idb]['komponen'] ?? ''));
                $harga_m2       = (float) ($master_barang[$idb]['harga_satuan'] ?? $harga_unit[$i] ?? 0);
                $kategori_rumus = $master_barang[$idb]['kategori_rumus'] ?? 'unit_fix';
            } else {
                $idb            = 0;
                $komponen_item  = trim((string) ($komponen[$i] ?? ''));
                $harga_m2       = max(0, (float) ($harga_unit[$i] ?? 0));
                $kategori_rumus = 'unit_fix';
            }

            // Luas dibulatkan 2 angka di belakang koma (mis. 1,649 -> 1,65),
            // dihitung dari mm² langsung supaya tidak terjadi pembulatan ganda.
            $luas_riil    = round(($l * $t) / 1000000, 2);
            $luas_billing = round($this->_luas_billing($kategori_rumus, $luas_riil, $l, $t), 2);

            $total = ($kategori_rumus === 'unit_fix')
                ? $q * $harga_m2
                : $luas_billing * $q * $harga_m2;

            // Aksesoris
            $aks_list  = [];
            $total_aks = 0;
            $aks_nama_item = $aks_nama[$i] ?? [];
            if (is_array($aks_nama_item)) {
                foreach ($aks_nama_item as $k => $nm) {
                    $nm = trim((string) $nm);
                    if ($nm === '') continue;
                    $aq = max(1, (int) ($aks_qty[$i][$k] ?? 1));
                    $ah = max(0, (float) ($aks_harga[$i][$k] ?? 0));

                    $aks_list[] = [
                        'nama'        => $nm,
                        'qty'         => $aq,
                        'harga'       => $ah,
                        'total_harga' => round($aq * $ah, 2),
                    ];
                    $total_aks += $aq * $ah;
                }
            }
            $total += $total_aks;

            // Detail Kaca: Luas (boleh pakai koma) x Harga per m²
            $kaca_list  = [];
            $total_kaca = 0;
            $kaca_nama_item = $kaca_nama[$i] ?? [];
            if (is_array($kaca_nama_item)) {
                foreach ($kaca_nama_item as $k => $nm) {
                    $nm = trim((string) $nm);
                    if ($nm === '') continue;
                    $kl = max(0, $this->_ke_angka($kaca_luas[$i][$k] ?? 0));
                    $kh = max(0, (float) ($kaca_harga[$i][$k] ?? 0));
                    $kt = round($kl * $kh, 2);

                    $kaca_list[] = [
                        'nama'        => $nm,
                        'luas'        => round($kl, 4),
                        'harga'       => $kh,
                        'total_harga' => $kt,
                    ];
                    $total_kaca += $kt;
                }
            }
            $total += $total_kaca;

            // Books Penutup
            $books_l = 0;
            $books_t = 0;
            $books_luas = 0;
            $books_q = 0;
            $books_h = 0;
            $books_total = 0;
            if ($this->_is_rolling_door($kategori_rumus)) {
                $books_l     = max(0, (int) ($lebar_m2[$i] ?? 0));
                $books_t     = max(0, (int) ($tinggi_m2[$i] ?? 0));
                $books_luas  = round(($books_l * $books_t) / 1000000, 2);
                $books_q     = max(0, (int) ($qty_m2[$i] ?? 0));
                $books_h     = max(0, (float) ($harga_books[$i] ?? 0));
                $books_total = round($books_luas * $books_q * $books_h, 2);
            }
            $total += $books_total;

            // Biaya Instalasi Rolling Door (Luas Billing x Qty x Harga Pasang per m²)
            $instalasi_h = 0;
            $instalasi_qty = 0;
            $instalasi_total = 0;
            if ($this->_is_rolling_door($kategori_rumus)) {
                $instalasi_h     = max(0, (float) ($harga_instalasi[$i] ?? 0));
                $instalasi_qty     = max(0, (float) ($qty_instalasi[$i] ?? 0));
                $instalasi_total = round($luas_billing * $instalasi_qty * $instalasi_h, 2);
            }
            $total += $instalasi_total;

            $tipe_motor_val  = trim($tipe_motor[$i] ?? '');
            $input_power_val = trim($input_power[$i] ?? '');
            $voltage_val     = trim($voltage[$i] ?? '');
            $books_penutup_val     = trim($books_penutup[$i] ?? '');
            $kelengkapan_val     = trim($kelengkapan[$i] ?? '');

            if ($this->_is_rolling_door($kategori_rumus)) {
                if ($tipe_motor_val === '')  $tipe_motor_val  = self::DEFAULT_TIPE_MOTOR_ROLLING;
                if ($input_power_val === '') $input_power_val = self::DEFAULT_POWER_ROLLING;
                if ($voltage_val === '')     $voltage_val     = self::DEFAULT_VOLTAGE_ROLLING;
                if ($books_penutup_val === '')     $books_penutup_val     = self::DEFAULT_BOOKS_PENUTUP_ROLLING;
                if ($kelengkapan_val === '')     $kelengkapan_val     = self::DEFAULT_KELENGKAPAN_ROLLING;
            }

            $fin_val = trim($finishing[$i] ?? '');
            if ($fin_val === '') {
                $fin_val = 'Powder Coating / 粉末涂料';
            }

            $items[] = [
                'id_barang'       => $idb > 0 ? $idb : null,
                'jenis_item'      => $jenis,
                'warna'           => trim($warna[$i] ?? ''),
                'komponen'        => $komponen_item,
                'finishing'       => $fin_val,
                'komponen_kusen'  => trim($komponen_kusen[$i] ?? ''),
                'komponen_daun'   => trim($komponen_daun[$i] ?? ''),
                'tebal'           => trim($tebal[$i] ?? ''),
                'lebar_mm'        => $l,
                'tinggi_mm'       => $t,
                'luas_m2'         => $luas_riil,
                'luas_billing_m2' => $luas_billing,
                'qty'             => $q,
                'harga_unit'      => $harga_m2,
                'total_harga'     => round($total, 2),
                'keterangan'      => trim($keterangan[$i] ?? ''),
                'tipe_motor'      => $tipe_motor_val,
                'input_power'     => $input_power_val,
                'voltage'         => $voltage_val,
                'books_penutup'   => $books_penutup_val,
                'lebar_m2_books'  => $books_l,
                'tinggi_m2_books' => $books_t,
                'luas_m2_books'   => $books_luas,
                'qty_books'       => $books_q,
                'harga_m2_books'  => $books_h,
                'harga_m2_instalasi' => $instalasi_h, // Disimpan jika kolom ada di DB, atau lewati jika tidak pakai kolom tersendiri
                'qty_instalasi' => $instalasi_qty, // Disimpan jika kolom ada di DB, atau lewati jika tidak pakai kolom tersendiri
                'kelengkapan'     => $kelengkapan_val,
                'aksesoris'       => $aks_list,
                'kaca'            => $kaca_list,
            ];

            $subtotal += $total;
        }

        return [$items, $subtotal];
    }

    // ------------------------------------------------------------------
    // MODE MANUAL: semua isian diketik langsung (tanpa master barang).
    // - Nama barang boleh kosong; item tetap disimpan kalau ada aksesoris / detail kaca.
    // - Ukuran diinput Lebar x Tinggi (mm) -> Luas (m2) dihitung otomatis,
    //   tapi Luas boleh diketik sendiri (luas_m2[]) dan ketikan itu yang dipakai.
    // - Total barang = Luas x Qty x Harga per m2.
    //   (Kalau lebar/tinggi dikosongkan sehingga luas = 0, total = Qty x Harga.)
    // - Total item = total barang + aksesoris + detail kaca.
    // - Foto per item (foto[]) dan foto per aksesoris (aks_foto[i][]).
    //   Foto lama dibawa lewat foto_lama[] dan aks_foto_lama[i][].
    // ------------------------------------------------------------------
    private function _kumpulkan_item_manual()
    {
        $jenis_item    = $this->input->post('jenis_item', TRUE)    ?: [];
        $warna         = $this->input->post('warna', TRUE)         ?: [];
        $finishing     = $this->input->post('finishing', TRUE)     ?: [];
        $komponen      = $this->input->post('komponen', TRUE)      ?: [];
        $lebar_mm      = $this->input->post('lebar_mm', TRUE)      ?: [];
        $tinggi_mm     = $this->input->post('tinggi_mm', TRUE)     ?: [];
        $luas_input    = $this->input->post('luas_m2', TRUE)       ?: [];
        $qty           = $this->input->post('qty', TRUE)           ?: [];
        $harga_unit    = $this->input->post('harga_unit', TRUE)    ?: [];
        $tebal         = $this->input->post('tebal', TRUE)    ?: [];
        $komponen_kusen    = $this->input->post('komponen_kusen', TRUE)    ?: [];
        $komponen_daun    = $this->input->post('komponen_daun', TRUE)    ?: [];
        $keterangan    = $this->input->post('keterangan', TRUE)    ?: [];
        $foto_lama     = $this->input->post('foto_lama', TRUE)     ?: [];
        $aks_nama      = $this->input->post('aks_nama', TRUE)      ?: [];
        $aks_qty       = $this->input->post('aks_qty', TRUE)       ?: [];
        $aks_harga     = $this->input->post('aks_harga', TRUE)     ?: [];
        $aks_foto_lama = $this->input->post('aks_foto_lama', TRUE) ?: [];
        $kaca_nama     = $this->input->post('kaca_nama', TRUE)     ?: [];
        $kaca_luas     = $this->input->post('kaca_luas', TRUE)     ?: [];
        $kaca_harga    = $this->input->post('kaca_harga', TRUE)    ?: [];

        $items    = [];
        $subtotal = 0;

        foreach ($jenis_item as $i => $jenis) {
            $jenis = trim((string) $jenis);
            $h     = max(0, (float) ($harga_unit[$i] ?? 0));
            $q     = max(1, (int) ($qty[$i] ?? 1));
            $l     = max(0, (int) ($lebar_mm[$i] ?? 0));
            $t     = max(0, (int) ($tinggi_mm[$i] ?? 0));
            // Luas manual boleh diketik sendiri (mis. "1,65"). Kalau kosong / 0,
            // dihitung dari Lebar x Tinggi. Selalu dibulatkan 2 angka di belakang koma.
            $luas_ketik = round(max(0, $this->_ke_angka($luas_input[$i] ?? '')), 2);
            $luas       = ($luas_ketik > 0) ? $luas_ketik : round(($l * $t) / 1000000, 2);

            // Aksesoris (+ foto per aksesoris)
            $aks_list  = [];
            $total_aks = 0;
            $aks_nama_item = $aks_nama[$i] ?? [];
            if (is_array($aks_nama_item)) {
                foreach ($aks_nama_item as $k => $nm) {
                    $nm = trim((string) $nm);
                    if ($nm === '') continue;
                    $aq = max(1, (int) ($aks_qty[$i][$k] ?? 1));
                    $ah = max(0, (float) ($aks_harga[$i][$k] ?? 0));

                    // Foto aksesoris: upload baru > foto lama (saat edit) > kosong
                    $aks_foto = $this->_upload_foto_aks($i, $k);
                    if ($aks_foto === null) {
                        $aks_foto = $this->_foto_valid($aks_foto_lama[$i][$k] ?? '');
                    }

                    $aks_list[] = [
                        'nama'        => $nm,
                        'qty'         => $aq,
                        'harga'       => $ah,
                        'total_harga' => round($aq * $ah, 2),
                        'foto'        => $aks_foto,
                    ];
                    $total_aks += $aq * $ah;
                }
            }

            // Detail kaca: luas x harga per m²
            $kaca_list  = [];
            $total_kaca = 0;
            $kaca_nama_item = $kaca_nama[$i] ?? [];
            if (is_array($kaca_nama_item)) {
                foreach ($kaca_nama_item as $k => $nm) {
                    $nm = trim((string) $nm);
                    if ($nm === '') continue;
                    $kl = max(0, $this->_ke_angka($kaca_luas[$i][$k] ?? 0));
                    $kh = max(0, (float) ($kaca_harga[$i][$k] ?? 0));
                    $kt = round($kl * $kh, 2);
                    $kaca_list[] = [
                        'nama'        => $nm,
                        'luas'        => round($kl, 4),
                        'harga'       => $kh,
                        'total_harga' => $kt,
                    ];
                    $total_kaca += $kt;
                }
            }

            // Item kosong (tanpa barang, tanpa aksesoris, tanpa kaca) dilewati
            if ($jenis === '' && $h <= 0 && empty($aks_list) && empty($kaca_list)) {
                continue;
            }

            // "Ada barang" = nama diisi atau harga diisi. Kalau tidak, item ini
            // hanya membawa aksesoris/kaca (qty, ukuran & harga barang = 0).
            $ada_barang = ($jenis !== '' || $h > 0);
            if ($jenis === '' && $h > 0) {
                $jenis = 'Item';
            }

            // Rumus barang manual: Luas x Qty x Harga/m².
            // Kalau ukuran tidak diisi (luas 0), pakai Qty x Harga supaya total tidak 0.
            $total_barang = 0;
            if ($ada_barang) {
                $total_barang = ($luas > 0) ? ($luas * $q * $h) : ($q * $h);
            }
            $total = round($total_barang, 2) + $total_aks + $total_kaca;

            // Foto item: upload baru > foto lama (saat edit) > kosong
            $foto = null;
            if ($ada_barang) {
                $foto = $this->_upload_foto($i);
                if ($foto === null) {
                    $foto = $this->_foto_valid($foto_lama[$i] ?? '');
                }
            }

            $items[] = [
                'id_barang'          => null,
                'jenis_item'         => $jenis,
                'warna'              => trim($warna[$i] ?? ''),
                'komponen'           => trim($komponen[$i] ?? ''),
                'finishing'          => trim($finishing[$i] ?? ''),
                'tebal'              =>  trim($tebal[$i] ?? ''),
                'komponen_kusen'     =>  trim($komponen_kusen[$i] ?? ''),
                'komponen_daun'      =>  trim($komponen_daun[$i] ?? ''),
                'lebar_mm'           => $ada_barang ? $l : 0,
                'tinggi_mm'          => $ada_barang ? $t : 0,
                'luas_m2'            => $ada_barang ? $luas : 0,
                'luas_billing_m2'    => $ada_barang ? $luas : 0,
                'qty'                => $ada_barang ? $q : 0,
                'harga_unit'         => $ada_barang ? $h : 0,
                'total_harga'        => round($total, 2),
                'keterangan'         => trim($keterangan[$i] ?? ''),
                'tipe_motor'         => '',
                'input_power'        => '',
                'voltage'            => '',
                'books_penutup'      => '',
                'lebar_m2_books'     => 0,
                'tinggi_m2_books'    => 0,
                'luas_m2_books'      => 0,
                'qty_books'          => 0,
                'harga_m2_books'     => 0,
                'harga_m2_instalasi' => 0,
                'qty_instalasi'      => 0,
                'kelengkapan'        => '',
                'foto'               => $foto,
                'aksesoris'          => $aks_list,
                'kaca'               => $kaca_list,
            ];

            $subtotal += $total;
        }

        return [$items, $subtotal];
    }

    // Upload foto untuk item ke-$i (input file bernama foto[]). Return nama file atau null.
    private function _upload_foto($i)
    {
        if (!isset($_FILES['foto']['name'][$i])) {
            return null;
        }
        return $this->_simpan_upload([
            'name'     => $_FILES['foto']['name'][$i],
            'type'     => $_FILES['foto']['type'][$i] ?? '',
            'tmp_name' => $_FILES['foto']['tmp_name'][$i] ?? '',
            'error'    => $_FILES['foto']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size'     => $_FILES['foto']['size'][$i] ?? 0,
        ], 'Foto item ' . ($i + 1));
    }

    // Upload foto aksesoris ke-$k milik item ke-$i (input file aks_foto[i][]).
    private function _upload_foto_aks($i, $k)
    {
        if (!isset($_FILES['aks_foto']['name'][$i][$k])) {
            return null;
        }
        return $this->_simpan_upload([
            'name'     => $_FILES['aks_foto']['name'][$i][$k],
            'type'     => $_FILES['aks_foto']['type'][$i][$k] ?? '',
            'tmp_name' => $_FILES['aks_foto']['tmp_name'][$i][$k] ?? '',
            'error'    => $_FILES['aks_foto']['error'][$i][$k] ?? UPLOAD_ERR_NO_FILE,
            'size'     => $_FILES['aks_foto']['size'][$i][$k] ?? 0,
        ], 'Foto aksesoris ' . ($k + 1) . ' (item ' . ($i + 1) . ')');
    }

    // Proses 1 file upload. CI Upload hanya membaca $_FILES[field] tunggal,
    // jadi entri file disalin dulu ke $_FILES['foto_upload'].
    private function _simpan_upload(array $file, $label)
    {
        if (empty($file['name']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $folder = FCPATH . self::FOLDER_FOTO;
        if (!is_dir($folder)) {
            @mkdir($folder, 0755, true);
        }

        $_FILES['foto_upload'] = $file;

        $this->load->library('upload');
        $this->upload->initialize([
            'upload_path'   => $folder,
            'allowed_types' => 'jpg|jpeg|png',
            'max_size'      => 3072, // KB
            'encrypt_name'  => TRUE,
        ], TRUE);

        if ($this->upload->do_upload('foto_upload')) {
            return $this->upload->data('file_name');
        }

        $this->foto_errors[] = $label . ': ' . strip_tags($this->upload->display_errors('', ''));
        return null;
    }

    // Nama file foto lama yang valid (format aman & file benar-benar ada), selain itu null
    private function _foto_valid($nama)
    {
        $nama = trim((string) $nama);
        if ($nama === '' || !preg_match('/^[A-Za-z0-9_.-]+$/', $nama)) {
            return null;
        }
        return is_file(FCPATH . self::FOLDER_FOTO . $nama) ? $nama : null;
    }

    private function _hapus_file_foto(array $daftar)
    {
        foreach ($daftar as $nama) {
            $nama = $this->_foto_valid($nama);
            if ($nama !== null) {
                @unlink(FCPATH . self::FOLDER_FOTO . $nama);
            }
        }
    }

    // Ubah "1,25" / "1.25" / "1.234,5" menjadi float
    private function _ke_angka($v)
    {
        $v = trim((string) $v);
        if ($v === '') return 0.0;
        if (strpos($v, ',') !== false) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        }
        return (float) $v;
    }

    private function _is_rolling_door($kategori)
    {
        return (bool) preg_match('/^ro{1,2}l{1,2}ing_(door_)?(tahan_api|baja)$/', (string) $kategori);
    }

    private function _luas_billing($kategori, $luas_riil, $lebar_mm = 0, $tinggi_mm = 0)
    {
        switch ($kategori) {
            case 'jendela_sliding':
                return max($luas_riil, 1.3);
            case 'jendela_swing_b1':
            case 'jendela_jungkit' :
                return max($luas_riil, 1.4);
            case 'jendela_swing_b2':
                $luas = max($luas_riil, 1.4);
                if ($luas_riil > 3.0 && $luas_riil < 5.0) {
                    $luas = $luas_riil + 1.3;
                }
                return $luas;
            case 'jendela_jalusi':
            case 'jendela_mati':
                return max($luas_riil, 1.0);
            case 'pintu_baja':
            case 'pintu_kayu':
            case 'pintu_alumunium':
            case 'pintu_tahan_api':
                return max($luas_riil, 2.0);
            case 'pintu_baja_double':
            case 'pintu_alumunium_double':
            case 'pintu_tahan_api_double':
                return max($luas_riil, 3.9);
            case 'rolling_door_tahan_api':
            case 'rolling_door_baja':
            case 'rooling_door_tahan_api':
            case 'rooling_door_baja':
                $luas = round((($lebar_mm + 200) * ($tinggi_mm + 600)) / 1000000, 2);
                return max($luas, 10.0);
            default:
                if ($this->_is_rolling_door($kategori)) {
                    return max(round((($lebar_mm + 200) * ($tinggi_mm + 600)) / 1000000, 2), 10.0);
                }
                return $luas_riil;
        }
    }

    // Nilai JASA pemasangan = biaya pasang di header + biaya instalasi rolling door
    // di semua item (luas billing x qty instalasi x tarif pasang per m²).
    // Inilah dasar pengenaan PPh 23 (bukan seluruh subtotal barang).
    private function _dpp_jasa($biaya_pasang, array $items)
    {
        $jasa = max(0, (float) $biaya_pasang);
        foreach ($items as $it) {
            $jasa += (float) ($it['luas_billing_m2'] ?? 0)
                * (float) ($it['qty_instalasi'] ?? 0)
                * (float) ($it['harga_m2_instalasi'] ?? 0);
        }
        return round($jasa, 2);
    }

    // Ada biaya pemasangan = nilai jasa pemasangan > 0
    private function _ada_pemasangan($biaya_pasang, array $items)
    {
        return $this->_dpp_jasa($biaya_pasang, $items) > 0;
    }

    private function _hitung_total($subtotal, array $items = [], $ppn_persen = null, $pph_persen = null)
    {
        if ($ppn_persen === null) {
            $ppn_persen = $this->input->post('pakai_ppn') ? self::TARIF_PPN : 0;
        }

        $biaya_pasang = $this->input->post('pakai_pasang')
            ? max(0, (float) $this->input->post('biaya_pasang')) : null;
        $biaya_ongkir = $this->input->post('pakai_ongkir')
            ? max(0, (float) $this->input->post('biaya_ongkir')) : null;

        // PPh hanya boleh kalau ada pemasangan (dicek ulang di server, tidak percaya form)
        if ($pph_persen === null) {
            $pph_persen = $this->input->post('pakai_pph') ? self::TARIF_PPH : 0;
        }
        if (!$this->_ada_pemasangan($biaya_pasang, $items)) {
            $pph_persen = 0;
        }

        $dasar_pajak = $subtotal + (float) $biaya_pasang + (float)$biaya_ongkir;

        // PPN 11% dari seluruh nilai (barang + jasa + ongkir).
        // PPh 23 (2%) HANYA dari nilai jasa pemasangan/instalasi.
        $ppn_nominal = round($dasar_pajak * $ppn_persen / 100);
        $pph_nominal = round($this->_dpp_jasa($biaya_pasang, $items) * $pph_persen / 100);

        return [
            'subtotal'     => $subtotal,
            'biaya_pasang' => $biaya_pasang,
            'biaya_ongkir' => $biaya_ongkir,
            'ppn_persen'   => $ppn_persen,
            'ppn_nominal'  => $ppn_nominal,
            'pph_persen'   => $pph_persen,
            'pph_nominal'  => $pph_nominal,
            // PPh dipotong customer -> MENGURANGI tagihan
            'grand_total'  => $dasar_pajak + $ppn_nominal - $pph_nominal,
        ];
    }

    public function simpan()
    {
        $tanggal     = $this->input->post('tanggal', TRUE);
        $id_customer = (int)$this->input->post('id_customer');
        $catatan     = $this->input->post('catatan', TRUE);
        $tipe        = ($this->input->post('tipe') === 'manual') ? 'manual' : 'rumus';
        $balik       = 'penawaran/tambah' . ($tipe === 'manual' ? '?tipe=manual' : '');

        if (empty($tanggal) || $id_customer <= 0) {
            $this->session->set_flashdata('error', 'Tanggal dan Customer harus diisi!');
            redirect($balik);
        }

        list($items, $subtotal) = $this->_kumpulkan_item($tipe);

        if (empty($items)) {
            $this->session->set_flashdata('error', 'Minimal harus ada 1 item penawaran yang diisi!');
            redirect($balik);
        }

        $header = array_merge([
            'no_surat'    => $this->Penawaran_model->generate_no_penawaran($tanggal),
            'tanggal'     => $tanggal,
            'id_customer' => $id_customer,
            'catatan'     => $catatan,
            'tipe'        => $tipe,
            'status'      => 'draft',
            'id_user'     => $this->session->userdata('user_id') ?? 0,
        ], $this->_hitung_total($subtotal, $items));

        $id_penawaran = $this->Penawaran_model->simpan_penawaran($header, $items);

        if (!empty($this->foto_errors)) {
            $this->session->set_flashdata('error', implode(' ', $this->foto_errors));
        }

        if ($id_penawaran) {
            $this->session->set_flashdata('success', 'Penawaran ' . $header['no_surat'] . ' berhasil disimpan.');
            redirect('penawaran/detail/' . $id_penawaran);
        } else {
            $this->session->set_flashdata('error', 'Gagal menyimpan penawaran. Silakan coba lagi.');
            redirect($balik);
        }
    }

    public function edit($id)
    {
        $id        = (int)$id;
        $penawaran = $this->Penawaran_model->get_penawaran_detail($id);

        if (!$penawaran) {
            $this->session->set_flashdata('error', 'Data penawaran tidak ditemukan.');
            redirect('penawaran');
        }

        if (($penawaran['header']->status ?? 'draft') === 'approved') {
            $this->session->set_flashdata('error', 'Penawaran ini sudah di-approve, data tidak bisa diedit lagi.');
            redirect('penawaran/detail/' . $id);
        }

        $data['title']          = translate('penawaran');
        $data['page_title']     = translate('penawaran');
        $data['active_menu']    = 'penawaran';
        $data['mode']           = 'edit';
        $data['penawaran_edit'] = $penawaran['header'];
        $data['items_edit']     = $penawaran['items'];
        $data['no_surat']       = $penawaran['header']->no_surat;
        $data['list_customer']  = $this->Penawaran_model->get_all_customer();
        $data['list_barang']    = $this->Penawaran_model->get_all_barang();
        $data['tarif_ppn']      = self::TARIF_PPN;
        $data['tarif_pph']      = self::TARIF_PPH;
        $data['tipe']           = (($penawaran['header']->tipe ?? 'rumus') === 'manual') ? 'manual' : 'rumus';

        $this->load->view('templates/header', $data);
        $this->load->view('penjualan/add_penawaran', $data);
        $this->load->view('templates/footer', $data);
    }

    public function update($id)
    {
        $id        = (int)$id;
        $penawaran = $this->Penawaran_model->get_penawaran_detail($id);

        if (!$penawaran) {
            $this->session->set_flashdata('error', 'Data penawaran tidak ditemukan.');
            redirect('penawaran');
        }

        if (($penawaran['header']->status ?? 'draft') === 'approved') {
            $this->session->set_flashdata('error', 'Penawaran ini sudah di-approve, data tidak bisa diedit lagi.');
            redirect('penawaran/detail/' . $id);
        }

        $tanggal     = $this->input->post('tanggal', TRUE) ?: $penawaran['header']->tanggal;
        $id_customer = (int) $this->input->post('id_customer') ?: $penawaran['header']->id_customer;
        $catatan     = $this->input->post('catatan', TRUE);

        $tipe          = (($penawaran['header']->tipe ?? 'rumus') === 'manual') ? 'manual' : 'rumus';
        $foto_sebelum  = $this->Penawaran_model->get_foto_list($id);

        list($items, $subtotal) = $this->_kumpulkan_item($tipe);

        if (empty($items)) {
            $this->session->set_flashdata('error', 'Minimal harus ada 1 item penawaran yang diisi!');
            redirect('penawaran/edit/' . $id);
        }

        $header = array_merge([
            'tanggal'     => $tanggal,
            'id_customer' => $id_customer,
            'catatan'     => $catatan,
        ], $this->_hitung_total($subtotal, $items));

        if ($this->Penawaran_model->update_penawaran($id, $header, $items)) {
            // Hapus file foto yang tidak dipakai lagi (diganti / item dihapus)
            $foto_baru = array_filter(array_column($items, 'foto'));
            foreach ($items as $it_baru) {
                foreach (($it_baru['aksesoris'] ?? []) as $ak_baru) {
                    if (!empty($ak_baru['foto'])) {
                        $foto_baru[] = $ak_baru['foto'];
                    }
                }
            }
            $this->_hapus_file_foto(array_diff($foto_sebelum, $foto_baru));
            if (!empty($this->foto_errors)) {
                $this->session->set_flashdata('error', implode(' ', $this->foto_errors));
            }
            $this->session->set_flashdata('success', 'Penawaran ' . $penawaran['header']->no_surat . ' berhasil diperbarui.');
            redirect('penawaran/detail/' . $id);
        }

        $this->session->set_flashdata('error', $this->Penawaran_model->get_last_error() ?: 'Gagal memperbarui penawaran.');
        redirect('penawaran/detail/' . $id);
    }

    public function delete($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
        }

        $id        = (int)$id;
        $penawaran = $this->Penawaran_model->get_penawaran_detail($id);

        if (!$penawaran) {
            $this->session->set_flashdata('error', 'Data penawaran tidak ditemukan.');
            redirect('penawaran');
        }

        if (($penawaran['header']->status ?? 'draft') === 'approved') {
            $this->session->set_flashdata('error', 'Penawaran ini sudah di-approve, data tidak bisa dihapus.');
            redirect('penawaran/detail/' . $id);
        }

        $foto_list = $this->Penawaran_model->get_foto_list($id);

        if ($this->Penawaran_model->hapus_penawaran($id)) {
            $this->_hapus_file_foto($foto_list);
            $this->session->set_flashdata('success', 'Penawaran ' . $penawaran['header']->no_surat . ' berhasil dihapus.');
            redirect('penawaran');
        }

        $this->session->set_flashdata('error', $this->Penawaran_model->get_last_error() ?: 'Gagal menghapus penawaran.');
        redirect('penawaran/detail/' . $id);
    }

    public function approve($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
        }

        $id = (int)$id;

        if ($this->Penawaran_model->approve_penawaran($id, $this->session->userdata('user_id') ?? 0)) {
            $this->session->set_flashdata('success', 'Penawaran berhasil di-approve. Data tidak bisa diedit lagi.');
        } else {
            $this->session->set_flashdata('error', 'Penawaran tidak ditemukan atau sudah di-approve.');
        }

        redirect('penawaran/detail/' . $id);
    }
}