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
            'status' => in_array($this->input->get('status', TRUE), ['pending', 'approved', 'batal'], TRUE)
                ? $this->input->get('status', TRUE) : '',
        ];

        $data['filter_status'] = $filter['status'];
        $data['filter_q']      = $filter['q'];
        $data['filter_dari']   = $filter['dari'];
        $data['filter_sampai'] = $filter['sampai'];

        // Pagination: 5 data per halaman (halaman dipilih lewat ?page=N)
        $this->load->library('pagination');

        $per_page   = 20;
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
        $data['boleh_hapus']        = $this->_boleh_hapus();
        $ringkasan      = $this->Penawaran_model->get_ringkasan_bulan_ini();

        $data['penawaran_list'] = $penawaran_list;
        $data['total_semua']    = $ringkasan['total'];     // semua penawaran bulan ini
        $data['total_approved'] = $ringkasan['approved'];  // sudah approved bulan ini
        $data['total_belum']    = $ringkasan['belum'];     // belum approved bulan ini (tidak termasuk batal)
        $data['total_batal']    = $ringkasan['batal'];     // batal / tidak jadi deal bulan ini
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
        $data['boleh_hapus'] = $this->_boleh_hapus();
        $data['boleh_batal'] = $this->_boleh_batal();

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
        // Rumus ukuran rolling door per item: l200_t600 (lebar +200, tinggi +600) | t500 (lebar tetap, tinggi +500)
        $rumus_ukuran   = $this->input->post('rumus_ukuran', TRUE)   ?: [];

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

            $ru = $this->_is_rolling_door($kategori_rumus) ? $this->_rumus_ukuran_valid($rumus_ukuran[$i] ?? '') : null;

            // Luas dibulatkan 2 angka di belakang koma (mis. 1,649 -> 1,65),
            // dihitung dari mm² langsung supaya tidak terjadi pembulatan ganda.
            $luas_riil    = round(($l * $t) / 1000000, 2);
            $luas_billing = round($this->_luas_billing($kategori_rumus, $luas_riil, $l, $t, $ru), 2);

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
                'rumus_ukuran'    => $ru,
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
    // - Satuan harga per item (satuan_harga[]): 'm2' atau 'unit'.
    //   m2   : Total barang = Luas x Qty x Harga per m2
    //          (kalau lebar/tinggi dikosongkan sehingga luas = 0, total = Qty x Harga).
    //   unit : Total barang = Qty x Harga per unit (luas tidak dipakai).
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
        // Satuan harga per item: 'm2' (Luas x Qty x Harga/m²) | 'unit' (Qty x Harga/unit)
        $satuan_harga  = $this->input->post('satuan_harga', TRUE)  ?: [];
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
            $sat   = (($satuan_harga[$i] ?? 'm2') === 'unit') ? 'unit' : 'm2';
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

            // Rumus barang manual:
            // - Harga per unit : Qty x Harga (luas hanya informasi, tidak ikut dihitung).
            // - Harga per m²   : Luas x Qty x Harga/m². Kalau ukuran tidak diisi (luas 0),
            //                    pakai Qty x Harga supaya total tidak 0.
            $total_barang = 0;
            if ($ada_barang) {
                $total_barang = ($sat === 'unit' || $luas <= 0) ? ($q * $h) : ($luas * $q * $h);
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
                'satuan_harga'       => $sat,
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

        // Kolom satuan_harga belum dibuat -> total tetap benar, tapi pilihan "per unit"
        // tidak tersimpan (saat edit akan kembali ke per m²). Beri tahu user.
        if (in_array('unit', array_column($items, 'satuan_harga'), true)
            && !$this->db->field_exists('satuan_harga', 'penawaran_detail')) {
            $this->foto_errors[] = 'Kolom satuan_harga belum ada di tabel penawaran_detail (jalankan database/satuan_harga.sql), pilihan harga per unit belum tersimpan.';
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

    // Rolling door: tahan api, baja, otomatis (ejaan "rooling" / tanpa "door_" juga dikenali)
    private function _is_rolling_door($kategori)
    {
        return (bool) preg_match('/^ro{1,2}l{1,2}ing_(door_)?(tahan_api|baja|otomatis)$/', (string) $kategori);
    }

    // Pilihan rumus ukuran rolling door. Default (data lama / tidak dipilih) = lebar +200, tinggi +600.
    const RUMUS_UKURAN_ROLLING = [
        'l200_t600' => 'Lebar +200, tinggi +600',
        't500'      => 'Lebar tetap, tinggi +500',
    ];

    private function _rumus_ukuran_valid($v)
    {
        return isset(self::RUMUS_UKURAN_ROLLING[$v]) ? $v : 'l200_t600';
    }

    // Ukuran tagih rolling door (mm) sesuai rumus ukuran
    private function _ukuran_rolling($lebar_mm, $tinggi_mm, $rumus_ukuran)
    {
        if ($rumus_ukuran === 't500') {
            return [$lebar_mm, $tinggi_mm + 500];
        }
        return [$lebar_mm + 200, $tinggi_mm + 600];
    }

    private function _luas_billing($kategori, $luas_riil, $lebar_mm = 0, $tinggi_mm = 0, $rumus_ukuran = null)
    {
        // Rolling door (tahan api / baja / otomatis): luas dari ukuran tagih, minimal 10 m²
        if ($this->_is_rolling_door($kategori)) {
            list($lb, $tb) = $this->_ukuran_rolling($lebar_mm, $tinggi_mm, $this->_rumus_ukuran_valid((string) $rumus_ukuran));
            return max(round(($lb * $tb) / 1000000, 2), 10.0);
        }
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
            default:
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

    // Kolom penawaran.pph_ongkir sudah ada? (migrasi database/pph_ongkir.sql)
    private function _kolom_pph_ongkir()
    {
        static $ada = null;
        if ($ada === null) {
            $ada = $this->db->field_exists('pph_ongkir', 'penawaran');
        }
        return $ada;
    }

    // Dasar pengenaan PPh = jasa pemasangan (+ ongkir kalau customer juga memotong PPh atas ongkir)
    private function _dpp_pph($biaya_pasang, $biaya_ongkir, array $items, $pph_ongkir)
    {
        $dpp = $this->_dpp_jasa($biaya_pasang, $items);
        if ($pph_ongkir) {
            $dpp += max(0, (float) $biaya_ongkir);
        }
        return round($dpp, 2);
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

        // Ongkir ikut dipotong PPh oleh customer? (hanya berarti kalau ongkir dipakai & PPh aktif)
        $pph_ongkir = ($biaya_ongkir !== null && $this->input->post('pakai_pph') && $this->input->post('pph_ongkir')) ? 1 : 0;

        // PPh hanya boleh kalau ada dasar PPh: jasa pemasangan, atau ongkir yang dipotong PPh
        // (dicek ulang di server, tidak percaya form)
        if ($pph_persen === null) {
            $pph_persen = $this->input->post('pakai_pph') ? self::TARIF_PPH : 0;
        }
        $dpp_pph = $this->_dpp_pph($biaya_pasang, $biaya_ongkir, $items, $pph_ongkir);
        if ($dpp_pph <= 0) {
            $pph_persen = 0;
            $pph_ongkir = 0;
        }

        $dasar_pajak = $subtotal + (float) $biaya_pasang + (float)$biaya_ongkir;

        // PPN 11% dari seluruh nilai (barang + jasa + ongkir).
        // PPh 23 (2%) dari nilai jasa pemasangan/instalasi (+ ongkir kalau dicentang).
        $ppn_nominal = round($dasar_pajak * $ppn_persen / 100);
        $pph_nominal = round($dpp_pph * $pph_persen / 100);

        $hasil = [
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
        if ($this->_kolom_pph_ongkir()) {
            $hasil['pph_ongkir'] = $pph_ongkir;
        }
        return $hasil;
    }

    // Pilihan masa konsinyasi (hari, dihitung sejak tanggal surat jalan)
    const MASA_KONSINYASI = [30, 60];
    const MASA_KONSINYASI_DEFAULT = 60;

    // Jenis penjualan dari form: biasa / konsinyasi (titip jual).
    // Kolom hanya diisi kalau migrasi database/konsinyasi.sql sudah dijalankan.
    // $lama / $masa_lama = nilai sebelumnya (edit), dipakai kalau field tidak terkirim.
    // Masa konsinyasi (30 / 60 hari) disimpan kalau kolom masa_konsinyasi sudah ada.
    private function _jenis_penjualan($lama = 'biasa', $masa_lama = null)
    {
        if (!$this->db->field_exists('jenis_penjualan', 'penawaran')) {
            return [];
        }
        $v = $this->input->post('jenis_penjualan');
        if ($v === null) {
            $v = $lama;
        }
        $jenis = ($v === 'konsinyasi') ? 'konsinyasi' : 'biasa';
        $hasil = ['jenis_penjualan' => $jenis];

        if ($this->db->field_exists('masa_konsinyasi', 'penawaran')) {
            $masa = $this->input->post('masa_konsinyasi');
            $masa = ($masa === null) ? (int) $masa_lama : (int) $masa;
            if (!in_array($masa, self::MASA_KONSINYASI, TRUE)) {
                $masa = self::MASA_KONSINYASI_DEFAULT;
            }
            $hasil['masa_konsinyasi'] = ($jenis === 'konsinyasi') ? $masa : null;
        }
        return $hasil;
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
        ], $this->_jenis_penjualan(), $this->_hitung_total($subtotal, $items));

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

        if (($penawaran['header']->status ?? 'draft') === 'batal') {
            $this->session->set_flashdata('error', 'Penawaran ini sudah dibatalkan. Aktifkan kembali dulu kalau ingin diedit.');
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

        if (($penawaran['header']->status ?? 'draft') === 'batal') {
            $this->session->set_flashdata('error', 'Penawaran ini sudah dibatalkan. Aktifkan kembali dulu kalau ingin diedit.');
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
        ], $this->_jenis_penjualan($penawaran['header']->jenis_penjualan ?? 'biasa', $penawaran['header']->masa_konsinyasi ?? null), $this->_hitung_total($subtotal, $items));

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

        // Hanya super_admin & admin penawaran (staff_purchasing) yang boleh menghapus
        if (!$this->_boleh_hapus()) {
            $this->session->set_flashdata('error', 'Anda tidak punya akses untuk menghapus penawaran.');
            redirect('penawaran/detail/' . $id);
        }

        // Penawaran approved BOLEH dihapus (mis. customer batal order),
        // asalkan belum dibuatkan Sales Order yang masih aktif.
        $no_so = $this->_so_aktif($id);
        if ($no_so !== null) {
            $this->session->set_flashdata('error', 'Penawaran ini sudah dibuatkan Sales Order ' . $no_so . '. Batalkan SO tersebut dulu sebelum menghapus penawaran.');
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

    // Role yang boleh menghapus penawaran (draft maupun approved)
    private function _boleh_hapus()
    {
        return in_array($this->session->userdata('role'), ['super_admin', 'staff_purchasing'], TRUE);
    }

    // No. SO aktif (bukan batal) yang dibuat dari penawaran ini, atau null kalau tidak ada
    private function _so_aktif($id_penawaran)
    {
        if (!$this->db->table_exists('sales_order') || !$this->db->field_exists('id_penawaran', 'sales_order')) {
            return null;
        }
        $this->db->select('no_so')->from('sales_order')->where('id_penawaran', (int) $id_penawaran);
        if ($this->db->field_exists('status', 'sales_order')) {
            // Sama dengan Sales_order_model: SO berstatus NULL/kosong tetap dianggap aktif
            $this->db->where("COALESCE(status, '') <> 'batal'", null, false);
        }
        $row = $this->db->limit(1)->get()->row();
        return $row ? $row->no_so : null;
    }

    public function approve($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
        }

        $id = (int)$id;

        // Yang boleh approve hanya atasan / super_admin (staff purchasing hanya membuat)
        if (!in_array($this->session->userdata('role'), ['atasan', 'super_admin'], TRUE)) {
            $this->session->set_flashdata('error', 'Hanya atasan yang dapat meng-approve penawaran.');
            redirect('penawaran/detail/' . $id);
        }

        $cek = $this->Penawaran_model->get_penawaran_detail($id);
        if ($cek && ($cek['header']->status ?? 'draft') === 'batal') {
            $this->session->set_flashdata('error', 'Penawaran yang sudah dibatalkan tidak bisa di-approve. Aktifkan kembali dulu.');
            redirect('penawaran/detail/' . $id);
        }

        if ($this->Penawaran_model->approve_penawaran($id, $this->session->userdata('user_id') ?? 0)) {
            $this->session->set_flashdata('success', 'Penawaran berhasil di-approve. Data tidak bisa diedit lagi.');
        } else {
            $this->session->set_flashdata('error', 'Penawaran tidak ditemukan atau sudah di-approve.');
        }

        redirect('penawaran/detail/' . $id);
    }

    // Role yang boleh membatalkan / mengaktifkan kembali penawaran
    private function _boleh_batal()
    {
        return in_array($this->session->userdata('role'), ['super_admin', 'atasan', 'staff_purchasing'], TRUE);
    }

    // Tandai penawaran BATAL (customer tidak jadi deal). Data tetap tersimpan sebagai riwayat.
    // Bisa untuk penawaran draft maupun approved, asal belum ada Sales Order aktif.
    public function batal($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
        }

        $id        = (int) $id;
        $penawaran = $this->Penawaran_model->get_penawaran_detail($id);
        if (!$penawaran) {
            $this->session->set_flashdata('error', 'Data penawaran tidak ditemukan.');
            redirect('penawaran');
        }
        if (!$this->_boleh_batal()) {
            $this->session->set_flashdata('error', 'Anda tidak punya akses untuk membatalkan penawaran.');
            redirect('penawaran/detail/' . $id);
        }
        if (($penawaran['header']->status ?? 'draft') === 'batal') {
            $this->session->set_flashdata('error', 'Penawaran ini sudah berstatus batal.');
            redirect('penawaran/detail/' . $id);
        }

        $alasan = trim((string) $this->input->post('alasan_batal', TRUE));
        if ($alasan === '') {
            $this->session->set_flashdata('error', 'Alasan pembatalan wajib diisi.');
            redirect('penawaran/detail/' . $id);
        }

        $no_so = $this->_so_aktif($id);
        if ($no_so !== null) {
            $this->session->set_flashdata('error', 'Penawaran ini sudah dibuatkan Sales Order ' . $no_so . '. Batalkan SO tersebut dulu.');
            redirect('penawaran/detail/' . $id);
        }

        $data = ['status' => 'batal'];
        if ($this->db->field_exists('alasan_batal', 'penawaran')) {
            $data['alasan_batal'] = mb_substr($alasan, 0, 255);
        }
        if ($this->db->field_exists('dibatalkan_at', 'penawaran')) {
            $data['dibatalkan_at'] = date('Y-m-d H:i:s');
        }
        if ($this->db->field_exists('dibatalkan_oleh', 'penawaran')) {
            $data['dibatalkan_oleh'] = (int) ($this->session->userdata('user_id') ?? 0) ?: null;
        }

        if ($this->db->where('id', $id)->update('penawaran', $data)) {
            $this->session->set_flashdata('success', 'Penawaran ' . $penawaran['header']->no_surat . ' ditandai BATAL.');
        } else {
            $err = $this->db->error();
            $this->session->set_flashdata('error', 'Gagal membatalkan penawaran' . (!empty($err['message']) ? ': ' . $err['message'] : '.'));
        }
        redirect('penawaran/detail/' . $id);
    }

    // Aktifkan kembali penawaran yang batal (mis. customer jadi deal).
    // Status kembali ke DRAFT supaya harga bisa direvisi & di-approve ulang oleh atasan.
    public function aktifkan($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
        }

        $id        = (int) $id;
        $penawaran = $this->Penawaran_model->get_penawaran_detail($id);
        if (!$penawaran) {
            $this->session->set_flashdata('error', 'Data penawaran tidak ditemukan.');
            redirect('penawaran');
        }
        if (!$this->_boleh_batal()) {
            $this->session->set_flashdata('error', 'Anda tidak punya akses untuk mengaktifkan kembali penawaran.');
            redirect('penawaran/detail/' . $id);
        }
        if (($penawaran['header']->status ?? 'draft') !== 'batal') {
            $this->session->set_flashdata('error', 'Penawaran ini tidak berstatus batal.');
            redirect('penawaran/detail/' . $id);
        }

        $data = ['status' => 'draft'];
        foreach (['alasan_batal', 'dibatalkan_at', 'dibatalkan_oleh', 'approved_at'] as $kolom) {
            if ($this->db->field_exists($kolom, 'penawaran')) {
                $data[$kolom] = null;
            }
        }

        if ($this->db->where('id', $id)->update('penawaran', $data)) {
            $this->session->set_flashdata('success', 'Penawaran ' . $penawaran['header']->no_surat . ' aktif kembali (status draft, perlu approval ulang).');
        } else {
            $err = $this->db->error();
            $this->session->set_flashdata('error', 'Gagal mengaktifkan penawaran' . (!empty($err['message']) ? ': ' . $err['message'] : '.'));
        }
        redirect('penawaran/detail/' . $id);
    }
}