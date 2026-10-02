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

        $penawaran_list = $this->Penawaran_model->get_all_penawaran($filter);

        $total_nilai = 0;
        foreach ($penawaran_list as $row) {
            $total_nilai += (float)$row->grand_total;
        }

        $data['penawaran_list']  = $penawaran_list;
        $data['total_bulan_ini'] = count($penawaran_list);
        $data['total_nilai']     = $total_nilai;

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

        $this->load->view('templates/header', $data);
        $this->load->view('penjualan/add_penawaran', $data);
        $this->load->view('templates/footer', $data);
    }

    /**
     * Kumpulkan item dari POST lalu hitung ulang seluruh nominal dan kalkulasi
     * berdasarkan aturan bisnis (5 Kategori Rumus) di server.
     * Return: [$items,$subtotal]
     */
    private function _kumpulkan_item()
    {
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
            $idb = (int) ($id_barang[$i] ?? 0);

            // 1. Ambil data master barang (termasuk harga per m2 & kategori rumus)
            if ($idb > 0 && isset($master_barang[$idb])) {
                $jenis          = $master_barang[$idb]['nama'];
                $komponen_item  = trim((string) ($master_barang[$idb]['komponen'] ?? ''));
                $harga_m2       = (float) ($master_barang[$idb]['harga_satuan'] ?? $harga_unit[$i] ?? 0);
                $kategori_rumus = $master_barang[$idb]['kategori_rumus'] ?? 'unit_fix';
            } else {
                $idb            = 0;
                $komponen_item  = '';
                $harga_m2       = max(0, (float) ($harga_unit[$i] ?? 0));
                $kategori_rumus = 'unit_fix';
            }

            // 2. Luas riil (mm -> m), dibulatkan 4 desimal supaya batas 1,3 / 1,4 / 3 / 5 tidak meleset karena floating point
            $luas_riil = round(($l / 1000) * ($t / 1000), 4);

            // 3. Luas penagihan sesuai kategori rumus
            $luas_billing = $this->_luas_billing($kategori_rumus, $luas_riil);

            // 4. Total item: unit_fix = qty x harga; kategori lain = luas penagihan x qty x harga/m2
            $total = ($kategori_rumus === 'unit_fix')
                ? $q * $harga_m2
                : $luas_billing * $q * $harga_m2;

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
                'lebar_mm'        => $l,
                'tinggi_mm'       => $t,
                'luas_m2'         => round($luas_riil, 4),
                'luas_billing_m2' => round($luas_billing, 4),
                'qty'             => $q,
                'harga_unit'      => $harga_m2,
                'total_harga'     => round($total, 2),
                'keterangan'      => trim($keterangan[$i] ?? ''),
                'tipe_motor'      => trim($tipe_motor[$i] ?? ''),
                'input_power'     => trim($input_power[$i] ?? ''),
                'voltage'         => trim($voltage[$i] ?? ''),
                'books_penutup'   => trim($books_penutup[$i] ?? ''),
                'kelengkapan'     => trim($kelengkapan[$i] ?? ''),
            ];

            $subtotal += $total;
        }

        return [$items, $subtotal];
    }

    /**
     * Luas penagihan (m2) per kategori rumus. Rumus total tetap sama:
     * luas_billing x qty x harga satuan.
     *  - jendela_sliding : minimum 1,3
     *  - jendela_swing_* : minimum 1,4; kalau 3 < luas < 5 ditambah 1,3
     *  - pintu_baja      : minimum 2
     */
    private function _luas_billing($kategori, $luas_riil, $lebar_mm = 0, $tinggi_mm = 0)
    {
        switch ($kategori) {
            case 'jendela_sliding':
                return min($luas_riil, 1.3);

            case 'jendela_swing_b1':
                return min($luas_riil, 1.4);

            case 'jendela_swing_b2':
                $luas = min($luas_riil, 3.0);
                if ($luas > 3.0 && $luas < 5.0) {
                    $luas += 1.3;
                }
                return $luas;

            case 'pintu_baja':
            case 'pintu_kayu':
            case 'pintu_alumunium':
                return min($luas_riil, 2.0);

            case 'pintu_baja_double':
            case 'pintu_alumunium_double':
                return min($luas_riil, 3.9);

            case 'rolling_door_tahan_api':
            case 'rolling_door_baja':
                $luas = round((($lebar_mm + 200) / 1000) * (($tinggi_mm + 600) / 1000), 4);
                return max($luas, 10.0);

            default: // unit_fix
                return $luas_riil;
        }
    }

    private function _hitung_total($subtotal, $ppn_persen = null, $pph_persen = null)
    {
        if ($ppn_persen === null) {
            $ppn_persen = $this->input->post('pakai_ppn') ? self::TARIF_PPN : 0;
        }
        if ($pph_persen === null) {
            $pph_persen = $this->input->post('pakai_pph') ? self::TARIF_PPH : 0;
        }

        $biaya_pasang = $this->input->post('pakai_pasang')
            ? max(0, (float) $this->input->post('biaya_pasang')) : null;
        $biaya_ongkir = $this->input->post('pakai_ongkir')
            ? max(0, (float) $this->input->post('biaya_ongkir')) : null;

        $dasar_pajak = $subtotal + (float) $biaya_pasang + (float)$biaya_ongkir;

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

    public function simpan()
    {
        $tanggal     = $this->input->post('tanggal', TRUE);
        $id_customer = (int)$this->input->post('id_customer');
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
            'no_surat'    => $this->Penawaran_model->generate_no_penawaran($tanggal),
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

        list($items, $subtotal) = $this->_kumpulkan_item();

        if (empty($items)) {
            $this->session->set_flashdata('error', 'Minimal harus ada 1 item penawaran yang diisi!');
            redirect('penawaran/edit/' . $id);
        }

        // Gabungkan perubahan header dasar (tanggal, customer, catatan) dan nominal total
        $header = array_merge([
            'tanggal'     => $tanggal,
            'id_customer' => $id_customer,
            'catatan'     => $catatan,
        ], $this->_hitung_total($subtotal));

        if ($this->Penawaran_model->update_penawaran($id, $header, $items)) {
            $this->session->set_flashdata('success', 'Penawaran ' . $penawaran['header']->no_surat . ' berhasil diperbarui.');
            redirect('penawaran/detail/' . $id);
        }

        $this->session->set_flashdata('error', $this->Penawaran_model->get_last_error() ?: 'Gagal memperbarui penawaran.');
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
