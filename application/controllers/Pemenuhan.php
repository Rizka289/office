<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PROSES PESANAN (setelah Sales Order dikonfirmasi)
 *
 *  1. Tentukan sumber tiap item SO : Produksi sendiri / Beli (masuk gudang) / Dropship / Ambil dari stok
 *  2. Produksi -> SPK      | Beli -> PO stok      | Dropship -> PO dropship (alamat kirim = customer)
 *  3. SPK selesai / PO stok diterima  -> barang siap di gudang -> Surat Jalan
 *     PO dropship dikonfirmasi diterima customer -> otomatis tercatat terkirim
 *  4. Semua terkirim + lunas -> SO "Selesai" (otomatis)
 */
class Pemenuhan extends MY_Controller
{
    /**
     * HAK AKSES per aksi. Ubah di sini saja kalau pembagian tugas berubah.
     *   super_admin      : semua
     *   atasan           : lihat semua, setujui / tolak / batalkan PO
     *   finance          : catat & hapus pembayaran ke supplier
     *   staff_purchasing : atur sumber, buat & kirim PO, buat SPK
     *   staff_gudang     : terima barang PO, update SPK, surat jalan
     */
    private $izin = array(
        'lihat'    => array('super_admin', 'atasan', 'finance', 'staff_purchasing', 'staff_gudang'),
        'atur'     => array('super_admin', 'staff_purchasing'),
        'sumber'   => array('super_admin', 'staff_purchasing', 'finance'),   // pilih sumber & supplier item SO
        'approve'  => array('super_admin', 'atasan'),
        'batal_po' => array('super_admin', 'atasan', 'staff_purchasing'),
        'terima'   => array('super_admin', 'staff_gudang', 'staff_purchasing'),
        'produksi' => array('super_admin', 'staff_gudang', 'staff_purchasing'),
        'kirim'    => array('super_admin', 'staff_gudang', 'staff_purchasing'),
        'bayar'    => array('super_admin', 'finance'),
        // izin buka halaman (menu)
        'menu_proses'  => array('super_admin', 'atasan', 'staff_purchasing', 'staff_gudang'),
        'menu_po'      => array('super_admin', 'atasan', 'finance', 'staff_purchasing', 'staff_gudang'),
        'menu_terima'  => array('super_admin', 'staff_gudang', 'staff_purchasing'),
        'menu_spk'     => array('super_admin', 'atasan', 'staff_purchasing', 'staff_gudang'),
        'menu_sj'      => array('super_admin', 'atasan', 'staff_purchasing', 'staff_gudang'),
        'menu_hutang'  => array('super_admin', 'atasan', 'finance'),
    );

    // Filter status di menu Purchase Order
    private $filter_po = array(
        // 'label' berisi key tabel interfaces, diterjemahkan di __construct()
        'semua'    => array('label' => 'i18n_semua',             'status' => array()),
        'draft'    => array('label' => 'i18n_menunggu_approval', 'status' => array('draft')),
        'approved' => array('label' => 'setuju',                 'status' => array('approved')),
        'dipesan'  => array('label' => 'i18n_dipesan',           'status' => array('dipesan', 'sebagian')),
        'selesai'  => array('label' => 'selesai',                'status' => array('selesai')),
        'batal'    => array('label' => 'btn_batal',              'status' => array('batal')),
    );

    public function __construct()
    {
        parent::__construct();
        $this->requireRole($this->izin['lihat']);
        $this->load->model(array('Pemenuhan_model', 'Sales_order_model', 'Supplier_model'));
        $this->load->helper(array('url', 'form'));
        $this->load->library('session');
        foreach ($this->filter_po as $k => $f) {
            $this->filter_po[$k]['label'] = translate($f['label']);
        }
    }

    // ================= HALAMAN =================

    // MENU: Proses Pesanan (SO berjalan)
    public function index()
    {
        $this->_wajib_izin('menu_proses', 'dashboard');
        $data['title']       = translate('app_purchasing');
        $data['so_list']     = $this->Pemenuhan_model->get_so_aktif();
        $data['active_menu'] = 'pemenuhan';
        $this->_view('pemenuhan/index', $data);
    }

    // MENU: Purchase Order (semua PO + filter status & cari)
    public function po()
    {
        $this->_wajib_izin('menu_po', 'dashboard');
        $f = (string) $this->input->get('status');
        if (!isset($this->filter_po[$f])) {
            $f = 'semua';
        }
        $q = mb_substr(trim((string) $this->input->get('q', true)), 0, 60);
        $data['title']       = translate('i18n_purchase_order');
        $data['filter']      = $f;
        $data['q']           = $q;
        $data['filter_list'] = $this->filter_po;
        $data['jumlah']      = $this->Pemenuhan_model->hitung_po_per_status();
        $data['po_list']     = $this->Pemenuhan_model->cari_po($this->filter_po[$f]['status'], $q);
        $data['active_menu'] = 'po';
        $this->_view('pemenuhan/po_list', $data);
    }

    // MENU: Penerimaan Barang (PO yang sedang dikirim supplier)
    public function penerimaan()
    {
        $this->_wajib_izin('menu_terima', 'dashboard');
        $data['title']       = translate('penerimaan_barang');
        $data['po_list']     = $this->Pemenuhan_model->get_po_penerimaan();
        $data['active_menu'] = 'po_terima';
        $this->_view('pemenuhan/penerimaan', $data);
    }

    // MENU: SPK Pesanan
    public function spk_list()
    {
        $this->_wajib_izin('menu_spk', 'dashboard');
        $data['title']       = translate('i18n_spk_pesanan');
        $data['spk_list']    = $this->Pemenuhan_model->get_spk_list();
        $data['active_menu'] = 'pm_spk';
        $this->_view('pemenuhan/spk_list', $data);
    }

    // MENU: Surat Jalan
    public function sj_list()
    {
        $this->_wajib_izin('menu_sj', 'dashboard');
        $data['title']       = translate('i18n_surat_jalan');
        $data['sj_list']     = $this->Pemenuhan_model->get_sj_list();
        $data['so_siap']     = $this->Pemenuhan_model->get_so_siap_kirim();
        $data['active_menu'] = 'pm_sj';
        $this->_view('pemenuhan/sj_list', $data);
    }

    // MENU: Hutang Supplier (finance)
    public function hutang()
    {
        $this->_wajib_izin('menu_hutang', 'dashboard');
        $data['title']       = translate('i18n_hutang_supplier');
        $data['po_hutang']   = $this->Pemenuhan_model->get_po_hutang();
        $data['active_menu'] = 'hutang';
        $this->_view('pemenuhan/hutang', $data);
    }

    // Halaman utama per SO
    public function so($id = 0)
    {
        $h = $this->_so_atau_404($id);
        $data['title']         = (translate('i18n_proses') . ' ') . $h->no_so;
        $data['h']             = $h;
        $data['items']         = $this->Pemenuhan_model->get_items($h->id);
        $data['po_list']       = $this->Pemenuhan_model->get_po_list($h->id);
        $data['spk_list']      = $this->Pemenuhan_model->get_spk_list($h->id);
        $data['sj_list']       = $this->Pemenuhan_model->get_sj_list($h->id);
        $data['supplier_list'] = $this->Supplier_model->get_aktif();
        $data['bisa_proses']   = $this->_status_proses($h);
        $this->_view('pemenuhan/so', $data);
    }

    public function simpan_sumber($id = 0)
    {
        $this->_wajib_post();
        $this->_wajib_izin('sumber', 'pemenuhan/so/' . (int) $id);
        $h      = $this->_so_atau_404($id);
        $sumber = (array) $this->input->post('sumber');
        $sup    = (array) $this->input->post('id_supplier');
        $input  = array();
        foreach ($sumber as $id_d => $s) {
            $input[(int) $id_d] = array(
                'sumber'      => (string) $s,
                'id_supplier' => isset($sup[$id_d]) ? (int) $sup[$id_d] : 0,
            );
        }
        list($ok, $pesan) = $this->Pemenuhan_model->simpan_sumber($h->id, $input);
        $this->_flash($ok, $pesan);
        redirect('pemenuhan/so/' . $h->id);
    }

    // ================= PURCHASE ORDER =================

    public function po_buat($id_so = 0)
    {
        $this->_wajib_izin('atur', 'pemenuhan/so/' . (int) $id_so);
        $h = $this->_so_atau_404($id_so);
        if (!$this->_status_proses($h)) {
            return $this->_tolak($h->id, translate('i18n_so_harus_berstatus_dikonfirmasi_produksi'));
        }
        $jenis = $this->input->get('jenis') === 'dropship' ? 'dropship' : 'stok';
        $perlu = $jenis === 'dropship' ? 'dropship' : 'beli';

        $items = array();
        foreach ($this->Pemenuhan_model->get_items($h->id) as $it) {
            if ($it->sumber === $perlu && $it->p_sisa_proses > 0) {
                $items[] = $it;
            }
        }
        if ($jenis === 'dropship' && !$items) {
            return $this->_tolak($h->id, translate('i18n_tidak_ada_item_dropship_yang_belum'));
        }

        // supplier: dari pilihan user, atau supplier default item pertama
        $id_sup = (int) $this->input->get('id_supplier');
        if (!$id_sup) {
            foreach ($items as $it) {
                if ((int) $it->id_supplier) {
                    $id_sup = (int) $it->id_supplier;
                    break;
                }
            }
        }
        if ($id_sup) {
            $items = array_values(array_filter($items, function ($it) use ($id_sup) {
                return !(int) $it->id_supplier || (int) $it->id_supplier === $id_sup;
            }));
        }

        $p = $this->Pemenuhan_model->perusahaan();
        $data['title']         = (translate('i18n_buat_po') . ' ') . ($jenis === 'dropship' ? 'Dropship' : translate('i18n_beli'));
        $data['h']             = $h;
        $data['jenis']         = $jenis;
        $data['items']         = $items;
        $data['id_supplier']   = $id_sup;
        $data['supplier_list'] = $this->Supplier_model->get_aktif();
        $data['ada_tabel_biaya'] = $this->Pemenuhan_model->ada_tabel_biaya_po();
        $data['ada_kolom_ppn']   = $this->Pemenuhan_model->ada_kolom_ppn_po();
        $data['tarif_ppn']       = Pemenuhan_model::TARIF_PPN_PO;
        $data['alamat_default'] = $jenis === 'dropship'
            ? trim($h->nama_customer . "\n" . ($h->alamat_kirim ?: $h->alamat_customer))
            : 'Gudang ' . $p['nama'] . "\n" . $p['alamat'];
        $this->_view('pemenuhan/po_form', $data);
    }

    public function po_simpan($id_so = 0)
    {
        $this->_wajib_post();
        $this->_wajib_izin('atur', 'pemenuhan/so/' . (int) $id_so);
        $h     = $this->_so_atau_404($id_so);
        $jenis = $this->input->post('jenis') === 'dropship' ? 'dropship' : 'stok';
        $hdr   = array(
            'tanggal'      => $this->_tgl($this->input->post('tanggal')) ?: date('Y-m-d'),
            'jenis'        => $jenis,
            'id_supplier'  => (int) $this->input->post('id_supplier'),
            'tgl_butuh'    => $this->_tgl($this->input->post('tgl_butuh')),
            'alamat_kirim' => trim((string) $this->input->post('alamat_kirim', true)),
            'catatan'      => trim((string) $this->input->post('catatan', true)),
            'pakai_ppn'    => (bool) $this->input->post('pakai_ppn'),
        );
        $ids    = (array) $this->input->post('line_id');
        $nama   = (array) $this->input->post('line_nama', true);
        $spek   = (array) $this->input->post('line_spek', true);
        $qty    = (array) $this->input->post('line_qty');
        $satuan = (array) $this->input->post('line_satuan', true);
        $harga  = (array) $this->input->post('line_harga');
        $lebar  = (array) $this->input->post('line_lebar');
        $tinggi = (array) $this->input->post('line_tinggi');
        $lines  = array();
        foreach ($ids as $i => $v) {
            $lines[] = array(
                'id_so_detail' => (int) $v,
                'nama_barang'  => (string) ($nama[$i] ?? ''),
                'lebar_mm'     => (int) ($lebar[$i] ?? 0),
                'tinggi_mm'    => (int) ($tinggi[$i] ?? 0),
                'spesifikasi'  => (string) ($spek[$i] ?? ''),
                'qty'          => (float) ($qty[$i] ?? 0),
                'satuan'       => (string) ($satuan[$i] ?? 'unit'),
                'harga'        => (float) ($harga[$i] ?? 0),
            );
        }
        // Biaya tambahan PO: biaya kirim, biaya gosok, packing, dll
        $b_nama  = (array) $this->input->post('biaya_nama', true);
        $b_nom   = (array) $this->input->post('biaya_nominal');
        $b_ket   = (array) $this->input->post('biaya_ket', true);
        $b_per   = (array) $this->input->post('biaya_hitung');
        $b_luas  = (array) $this->input->post('biaya_luas');
        $b_hrg   = (array) $this->input->post('biaya_harga');
        $biaya   = array();
        foreach ($b_nama as $i => $nm) {
            $biaya[] = array(
                'nama'       => (string) $nm,
                'hitung'     => (string) ($b_per[$i] ?? 'borongan'),
                'luas'       => (float) str_replace(',', '.', (string) ($b_luas[$i] ?? 0)),
                'harga'      => (float) ($b_hrg[$i] ?? 0),
                'nominal'    => (float) ($b_nom[$i] ?? 0),
                'keterangan' => (string) ($b_ket[$i] ?? ''),
            );
        }
        $hasil = $this->Pemenuhan_model->simpan_po($h->id, $hdr, $lines, $this->_id_user(), $biaya);
        $this->_flash($hasil[0], $hasil[1]);
        if ($hasil[0]) {
            redirect('pemenuhan/so/' . $h->id);
        } else {
            redirect('pemenuhan/po_buat/' . $h->id . '?jenis=' . $jenis . '&id_supplier=' . $hdr['id_supplier']);
        }
    }

    // ---------- PO BAHAN / STOK (tanpa Sales Order) ----------
    // Pembelian di luar penawaran, mis. aluminium / kaca / aksesoris untuk produksi sendiri.

    public function po_bebas()
    {
        $this->_wajib_izin('atur', 'pemenuhan/po');
        $p = $this->Pemenuhan_model->perusahaan();
        $data['title']           = 'Buat PO Bahan / Stok';
        $data['siap']            = $this->Pemenuhan_model->po_bebas_siap();
        $data['supplier_list']   = $this->Supplier_model->get_aktif();
        $data['barang_list']     = $this->Pemenuhan_model->get_barang_beli();
        $data['ada_tabel_biaya'] = $this->Pemenuhan_model->ada_tabel_biaya_po();
        $data['ada_kolom_ppn']   = $this->Pemenuhan_model->ada_kolom_ppn_po();
        $data['tarif_ppn']       = Pemenuhan_model::TARIF_PPN_PO;
        $data['alamat_default']  = 'Gudang ' . $p['nama'] . "\n" . $p['alamat'];
        $data['active_menu']     = 'po';
        $this->_view('pemenuhan/po_bebas_form', $data);
    }

    public function po_bebas_simpan()
    {
        $this->_wajib_post();
        $this->_wajib_izin('atur', 'pemenuhan/po');
        $hdr = array(
            'tanggal'      => $this->_tgl($this->input->post('tanggal')) ?: date('Y-m-d'),
            'id_supplier'  => (int) $this->input->post('id_supplier'),
            'tgl_butuh'    => $this->_tgl($this->input->post('tgl_butuh')),
            'alamat_kirim' => trim((string) $this->input->post('alamat_kirim', true)),
            'catatan'      => trim((string) $this->input->post('catatan', true)),
            'pakai_ppn'    => (bool) $this->input->post('pakai_ppn'),
        );
        $id_b   = (array) $this->input->post('line_barang');
        $nama   = (array) $this->input->post('line_nama', true);
        $spek   = (array) $this->input->post('line_spek', true);
        $qty    = (array) $this->input->post('line_qty');
        $satuan = (array) $this->input->post('line_satuan', true);
        $harga  = (array) $this->input->post('line_harga');
        $lines  = array();
        foreach ($nama as $i => $nm) {
            $lines[] = array(
                'id_barang'   => (int) ($id_b[$i] ?? 0),
                'nama_barang' => (string) $nm,
                'spesifikasi' => (string) ($spek[$i] ?? ''),
                'qty'         => (float) str_replace(',', '.', (string) ($qty[$i] ?? 0)),
                'satuan'      => (string) ($satuan[$i] ?? ''),
                'harga'       => (float) ($harga[$i] ?? 0),
            );
        }
        $b_nama = (array) $this->input->post('biaya_nama', true);
        $b_nom  = (array) $this->input->post('biaya_nominal');
        $b_ket  = (array) $this->input->post('biaya_ket', true);
        $biaya  = array();
        foreach ($b_nama as $i => $nm) {
            $biaya[] = array(
                'nama'       => (string) $nm,
                'hitung'     => 'borongan',
                'nominal'    => (float) ($b_nom[$i] ?? 0),
                'keterangan' => (string) ($b_ket[$i] ?? ''),
            );
        }
        $hasil = $this->Pemenuhan_model->simpan_po_bebas($hdr, $lines, $this->_id_user(), $biaya);
        $this->_flash($hasil[0], $hasil[1]);
        redirect($hasil[0] ? 'pemenuhan/po_detail/' . (int) $hasil[2] : 'pemenuhan/po_bebas');
    }

    // $aksi: setujui | tolak (atasan) | kirim (purchasing) | batal
    public function po_aksi($id = 0, $aksi = '')
    {
        $this->_wajib_post();
        $po = $this->db->get_where('pm_po', array('id' => (int) $id))->row();
        if (!$po) {
            show_404();
        }
        $kembali = $this->_kembali($po->id_so ? 'pemenuhan/so/' . $po->id_so : 'pemenuhan/po_detail/' . $po->id);
        $perlu   = array('setujui' => 'approve', 'tolak' => 'approve', 'kirim' => 'atur', 'batal' => 'batal_po');
        if (!isset($perlu[$aksi])) {
            $this->_flash(false, translate('i18n_aksi_tidak_dikenal'));
            redirect($kembali);
        }
        $this->_wajib_izin($perlu[$aksi], $kembali);

        list($ok, $pesan) = $this->Pemenuhan_model->aksi_po(
            $po->id,
            $aksi,
            $this->_id_user(),
            (string) $this->input->post('alasan', true)
        );
        $this->_flash($ok, $pesan);
        redirect($kembali);
    }

    // Halaman detail PO: info, approval, penerimaan, pembayaran ke supplier
    public function po_detail($id = 0)
    {
        $po = $this->Pemenuhan_model->get_po($id);
        if (!$po) {
            show_404();
        }
        $data['title']       = 'PO ' . $po['header']->no_po;
        $data['po']          = $po;
        // PO tanpa SO (PO bahan/stok) selalu bisa diproses
        $data['bisa_proses'] = !$po['header']->id_so
            || in_array($po['header']->status_so, array('confirmed', 'produksi', 'terkirim'), true);
        $data['boleh_bayar'] = in_array($po['header']->status, $this->Pemenuhan_model->status_boleh_bayar, true);
        $data['active_menu'] = 'po';
        $this->_view('pemenuhan/po_detail', $data);
    }

    // POST: catat pembayaran ke supplier (DP / termin / pelunasan) + upload bukti
    public function po_bayar($id = 0)
    {
        $this->_wajib_post();
        $kembali = 'pemenuhan/po_detail/' . (int) $id;
        $this->_wajib_izin('bayar', $kembali);
        $po = $this->db->get_where('pm_po', array('id' => (int) $id))->row();
        if (!$po) {
            show_404();
        }

        $bukti = '';
        if (!empty($_FILES['bukti']['name'])) {
            list($ok_up, $hasil) = $this->_upload_bukti('bukti');
            if (!$ok_up) {
                $this->_flash(false, $hasil);
                redirect($kembali);
            }
            $bukti = $hasil;
        }

        $nominal = preg_replace('/[^0-9,]/', '', (string) $this->input->post('nominal'));
        // Rekening asal dari pilihan; "Lainnya" -> pakai teks yang diketik
        $rek = trim((string) $this->input->post('rekening_asal', true));
        if ($rek === '__lain') {
            $rek = trim((string) $this->input->post('rekening_asal_lain', true));
        }
        $d = array(
            'tanggal'       => $this->_tgl($this->input->post('tanggal')) ?: date('Y-m-d'),
            'jenis'         => (string) $this->input->post('jenis'),
            'nominal'       => (float) str_replace(',', '.', $nominal),
            'metode'        => (string) $this->input->post('metode'),
            'rekening_asal' => mb_substr($rek, 0, 100),
            'bukti'         => $bukti,
            'keterangan'    => trim((string) $this->input->post('keterangan', true)),
        );
        list($ok, $pesan) = $this->Pemenuhan_model->simpan_pembayaran_po($po->id, $d, $this->_id_user());
        if (!$ok && $bukti !== '' && is_file(FCPATH . $bukti)) {
            @unlink(FCPATH . $bukti);
        }
        $this->_flash($ok, $pesan);
        redirect($kembali);
    }

    // POST: hapus pembayaran yang salah input
    public function po_bayar_hapus($id_bayar = 0)
    {
        $this->_wajib_post();
        $b = $this->db->get_where('pm_po_pembayaran', array('id' => (int) $id_bayar))->row();
        if (!$b) {
            show_404();
        }
        $kembali = 'pemenuhan/po_detail/' . (int) $b->id_po;
        $this->_wajib_izin('bayar', $kembali);
        list($ok, $pesan, $file) = $this->Pemenuhan_model->hapus_pembayaran_po($b->id);
        if ($ok && $file !== '' && is_file(FCPATH . $file)) {
            @unlink(FCPATH . $file);
        }
        $this->_flash($ok, $pesan);
        redirect($kembali);
    }

    // GET: form penerimaan; POST: simpan penerimaan
    public function po_terima($id = 0)
    {
        $po = $this->Pemenuhan_model->get_po($id);
        if (!$po) {
            show_404();
        }
        $h = $po['header'];
        $this->_wajib_izin('terima', 'pemenuhan/po_detail/' . (int) $h->id);

        if ($this->input->method() === 'post') {
            $hdr = array(
                'tanggal'        => $this->_tgl($this->input->post('tanggal')) ?: date('Y-m-d'),
                'no_sj_supplier' => mb_substr(trim((string) $this->input->post('no_sj_supplier', true)), 0, 100),
                'ekspedisi'      => mb_substr(trim((string) $this->input->post('ekspedisi', true)), 0, 100),
                'penerima'       => mb_substr(trim((string) $this->input->post('penerima', true)), 0, 100),
                'catatan'        => trim((string) $this->input->post('catatan', true)),
                'id_location'    => (int) $this->input->post('id_location'),
            );
            $qty = array();
            foreach ((array) $this->input->post('qty') as $k => $v) {
                $qty[(int) $k] = (float) $v;
            }
            $aks = $h->jenis === 'dropship' ? $this->_baca_aksesoris() : array();
            list($ok, $pesan) = $this->Pemenuhan_model->terima_po($h->id, $hdr, $qty, $this->_id_user(), $aks);
            $this->_flash($ok, $pesan);
            redirect($ok ? 'pemenuhan/po_detail/' . $h->id : 'pemenuhan/po_terima/' . $h->id);
            return;
        }

        if (!in_array($h->status, array('dipesan', 'sebagian'), true)) {
            $pesan = 'PO ' . $h->no_po . (' ' . translate('i18n_belum_dikirim_ke_supplier_sudah_selesai'));
            if (!$h->id_so) {
                $this->session->set_flashdata('error', $pesan);
                redirect('pemenuhan/po_detail/' . (int) $h->id);
            }
            return $this->_tolak($h->id_so, $pesan);
        }
        $ada_barang = false;
        foreach ($po['items'] as $d) {
            if (!empty($d->id_barang)) {
                $ada_barang = true;
            }
        }
        $data['title']      = ($h->jenis === 'dropship' ? 'Konfirmasi Dropship ' : (translate('i18n_terima_barang') . ' ')) . $h->no_po;
        $data['po']         = $po;
        $data['ada_barang'] = $ada_barang && $h->jenis === 'stok';
        $data['lokasi']     = $data['ada_barang'] ? $this->Pemenuhan_model->get_lokasi() : array();
        if ($h->jenis === 'dropship') {
            $ids = array();
            foreach ($po['items'] as $d) {
                if ($d->id_so_detail) {
                    $ids[] = (int) $d->id_so_detail;
                }
            }
            $this->_data_aksesoris($data, $ids);
        }
        $data['active_menu'] = 'po_terima';
        $this->_view('pemenuhan/po_terima', $data);
    }

    // $mode: po (dokumen PO ke supplier) | sj (surat jalan dropship, tanpa harga)
    public function po_cetak($id = 0, $mode = 'po')
    {
        $po = $this->Pemenuhan_model->get_po($id);
        if (!$po) {
            show_404();
        }
        $this->load->view('pemenuhan/po_cetak', array(
            'po'   => $po,
            'mode' => ($mode === 'sj' && $po['header']->jenis === 'dropship') ? 'sj' : 'po',
        ));
    }

    // ================= SPK PRODUKSI =================

    public function spk_buat($id_so = 0)
    {
        $this->_wajib_izin('atur', 'pemenuhan/so/' . (int) $id_so);
        $h = $this->_so_atau_404($id_so);
        if (!$this->_status_proses($h)) {
            return $this->_tolak($h->id, translate('i18n_so_harus_berstatus_dikonfirmasi_produksi_2'));
        }
        $items = array();
        foreach ($this->Pemenuhan_model->get_items($h->id) as $it) {
            if ($it->sumber === 'produksi' && $it->p_sisa_proses > 0) {
                $items[] = $it;
            }
        }
        if (!$items) {
            return $this->_tolak($h->id, translate('i18n_tidak_ada_item_produksi_yang_belum'));
        }
        $data['title'] = translate('i18n_buat_spk_produksi');
        $data['h']     = $h;
        $data['items'] = $items;
        $this->_view('pemenuhan/spk_form', $data);
    }

    public function spk_simpan($id_so = 0)
    {
        $this->_wajib_post();
        $this->_wajib_izin('atur', 'pemenuhan/so/' . (int) $id_so);
        $h   = $this->_so_atau_404($id_so);
        $hdr = array(
            'tanggal'        => $this->_tgl($this->input->post('tanggal')) ?: date('Y-m-d'),
            'target_selesai' => $this->_tgl($this->input->post('target_selesai')),
            'catatan'        => trim((string) $this->input->post('catatan', true)),
        );
        $qty = array();
        foreach ((array) $this->input->post('qty') as $k => $v) {
            $qty[(int) $k] = (float) $v;
        }
        $hasil = $this->Pemenuhan_model->simpan_spk($h->id, $hdr, $qty, $this->_id_user());
        $this->_flash($hasil[0], $hasil[1]);
        redirect($hasil[0] ? 'pemenuhan/so/' . $h->id : 'pemenuhan/spk_buat/' . $h->id);
    }

    // $aksi: mulai | selesai | batal
    public function spk_aksi($id = 0, $aksi = '')
    {
        $this->_wajib_post();
        $spk = $this->db->get_where('pm_spk', array('id' => (int) $id))->row();
        if (!$spk) {
            show_404();
        }
        $this->_wajib_izin('produksi', 'pemenuhan/so/' . (int) $spk->id_so);
        list($ok, $pesan) = $this->Pemenuhan_model->aksi_spk($spk->id, $aksi);
        $this->_flash($ok, $pesan);
        redirect('pemenuhan/so/' . $spk->id_so);
    }

    public function spk_cetak($id = 0)
    {
        $spk = $this->Pemenuhan_model->get_spk($id);
        if (!$spk) {
            show_404();
        }
        // item lengkap (dengan aksesoris & kaca) dari SO
        $so   = $this->Sales_order_model->get_so_lengkap($spk['header']->id_so);
        $peta = array();
        foreach ($so ? $so['items'] : array() as $it) {
            $peta[(int) $it->id] = $it;
        }
        $baris = array();
        foreach ($spk['detail'] as $d) {
            if (isset($peta[(int) $d->id_so_detail])) {
                $baris[] = array('item' => $peta[(int) $d->id_so_detail], 'qty' => (float) $d->qty);
            }
        }
        $this->load->view('pemenuhan/spk_cetak', array('h' => $spk['header'], 'baris' => $baris));
    }

    // ================= SURAT JALAN =================

    // GET: pilih stok untuk item bersumber "stok"; POST: simpan
    public function stok_ambil($id_detail = 0)
    {
        $d = $this->db->get_where('sales_order_detail', array('id' => (int) $id_detail))->row();
        if (!$d) {
            show_404();
        }
        $this->_wajib_izin('atur', 'pemenuhan/so/' . (int) $d->id_so);
        $h = $this->_so_atau_404($d->id_so);

        if ($this->input->method() === 'post') {
            // kunci[] & qty[] sejajar (kunci berisi ukuran bebas, tidak dipakai sebagai key form)
            $pilihan = array();
            $kunci   = (array) $this->input->post('kunci');
            foreach ((array) $this->input->post('qty') as $i => $q) {
                $q = (float) str_replace(',', '.', (string) $q);
                $k = isset($kunci[$i]) ? (string) $kunci[$i] : '';
                if ($q > 0 && $k !== '') {
                    $pilihan[$k] = ($pilihan[$k] ?? 0) + $q;
                }
            }
            list($ok, $pesan) = $this->Pemenuhan_model->alokasi_stok($d->id, $pilihan, $this->_id_user());
            $this->_flash($ok, $pesan);
            redirect($ok ? 'pemenuhan/so/' . $h->id : 'pemenuhan/stok_ambil/' . $d->id);
        }

        $it = null;
        foreach ($this->Pemenuhan_model->get_items($h->id) as $x) {
            if ((int) $x->id === (int) $d->id) {
                $it = $x;
            }
        }
        if (!$it || $it->sumber !== 'stok') {
            return $this->_tolak($h->id, translate('i18n_atur_sumber_item_ambil_dari_stok_lalu'));
        }
        $cari      = mb_substr(trim((string) $this->input->get('q', true)), 0, 60);
        $semua     = (string) $this->input->get('semua') === '1';
        $id_barang = (!$semua && !empty($d->id_barang)) ? (int) $d->id_barang : 0;

        $data['title']       = translate('i18n_ambil_dari_stok');
        $data['h']           = $h;
        $data['it']          = $it;
        $data['cari']        = $cari;
        $data['semua']       = $semua;
        $data['filter_brg']  = $id_barang > 0;
        $data['ada_brg']     = !empty($d->id_barang);
        $data['stok_list']   = $this->Pemenuhan_model->get_stok_tersedia($id_barang, $cari);
        $data['alokasi']     = $this->Pemenuhan_model->get_alokasi($d->id);
        $data['bisa_proses'] = $this->_status_proses($h);
        $this->_view('pemenuhan/stok_ambil', $data);
    }

    // POST: lepas stok yang belum dikirim
    public function stok_lepas($id = 0)
    {
        $this->_wajib_post();
        $a = $this->db->get_where('pm_alokasi_stok', array('id' => (int) $id))->row();
        if (!$a) {
            show_404();
        }
        $this->_wajib_izin('atur', 'pemenuhan/so/' . (int) $a->id_so);
        list($ok, $pesan) = $this->Pemenuhan_model->lepas_alokasi($a->id);
        $this->_flash($ok, $pesan);
        redirect('pemenuhan/stok_ambil/' . (int) $a->id_so_detail);
    }

    public function sj_buat($id_so = 0)
    {
        $this->_wajib_izin('kirim', 'pemenuhan/so/' . (int) $id_so);
        $h = $this->_so_atau_404($id_so);
        if (!$this->_status_proses($h)) {
            return $this->_tolak($h->id, translate('i18n_so_harus_berstatus_dikonfirmasi_produksi_3'));
        }
        $items = array();
        foreach ($this->Pemenuhan_model->get_items($h->id) as $it) {
            if ($it->p_siap_kirim > 0) {
                $items[] = $it;
            }
        }
        if (!$items) {
            return $this->_tolak($h->id, translate('i18n_belum_ada_barang_siap_kirim_selesaikan'));
        }
        $data['title'] = translate('i18n_buat_surat_jalan');
        $data['h']     = $h;
        $data['items'] = $items;
        $this->_data_aksesoris($data, array_map(function ($it) { return (int) $it->id; }, $items));
        $this->_view('pemenuhan/sj_form', $data);
    }

    public function sj_simpan($id_so = 0)
    {
        $this->_wajib_post();
        $this->_wajib_izin('kirim', 'pemenuhan/so/' . (int) $id_so);
        $h   = $this->_so_atau_404($id_so);
        $hdr = array(
            'tanggal'      => $this->_tgl($this->input->post('tanggal')) ?: date('Y-m-d'),
            'alamat_kirim' => trim((string) $this->input->post('alamat_kirim', true)),
            'ekspedisi'    => mb_substr(trim((string) $this->input->post('ekspedisi', true)), 0, 100),
            'no_kendaraan' => mb_substr(trim((string) $this->input->post('no_kendaraan', true)), 0, 30),
            'catatan'      => trim((string) $this->input->post('catatan', true)),
        );
        $qty = array();
        foreach ((array) $this->input->post('qty') as $k => $v) {
            $qty[(int) $k] = (float) $v;
        }
        $hasil = $this->Pemenuhan_model->simpan_sj($h->id, $hdr, $qty, $this->_id_user(), $this->_baca_aksesoris());
        $this->_flash($hasil[0], $hasil[1]);
        redirect($hasil[0] ? 'pemenuhan/so/' . $h->id : 'pemenuhan/sj_buat/' . $h->id);
    }

    public function sj_batal($id = 0)
    {
        $this->_wajib_post();
        $sj = $this->db->get_where('pm_sj', array('id' => (int) $id))->row();
        if (!$sj) {
            show_404();
        }
        $this->_wajib_izin('kirim', 'pemenuhan/so/' . (int) $sj->id_so);
        list($ok, $pesan) = $this->Pemenuhan_model->batal_sj($sj->id);
        $this->_flash($ok, $pesan);
        redirect('pemenuhan/so/' . $sj->id_so);
    }

    public function sj_cetak($id = 0)
    {
        $sj = $this->Pemenuhan_model->get_sj($id);
        if (!$sj) {
            show_404();
        }
        // Aksesoris paket (mis. handle pintu) dicetak di bawah barangnya.
        // Item yang aksesorisnya dicatat saat SJ dibuat (pintu kamar) -> pakai catatan itu.
        // Item lain / SJ lama -> perkiraan proporsional dari aksesoris SO (qty aksesoris di SO = total seluruh qty item).
        $tercatat = array();
        foreach ((array) $this->Pemenuhan_model->get_sj_aksesoris($sj['header']->id) as $a) {
            $tercatat[(int) $a->id_so_detail][] = $a;
        }
        $so   = $sj['header']->id_so ? $this->Sales_order_model->get_so_lengkap($sj['header']->id_so) : null;
        $peta = array();
        foreach ($so ? $so['items'] : array() as $it) {
            $peta[(int) $it->id] = $it;
        }
        foreach ($sj['items'] as $it) {
            $it->aksesoris_kirim = array();
            if (isset($tercatat[(int) $it->id])) {
                foreach ($tercatat[(int) $it->id] as $a) {
                    if ((float) $a->qty > 0) {
                        $it->aksesoris_kirim[] = (object) array('nama' => $a->nama, 'qty' => (float) $a->qty);
                    }
                }
                continue;
            }
            $src = $peta[(int) $it->id] ?? null;
            if (!$src || empty($src->aksesoris) || (float) $it->qty <= 0) {
                continue;
            }
            $rasio = min(1, (float) $it->qty_kirim / (float) $it->qty);
            foreach ($src->aksesoris as $a) {
                $q = round((float) $a->qty * $rasio, 2);
                if ($q > 0 && trim((string) $a->nama) !== '') {
                    $it->aksesoris_kirim[] = (object) array('nama' => $a->nama, 'qty' => $q);
                }
            }
        }
        $this->load->view('pemenuhan/sj_cetak', array('sj' => $sj));
    }

    // ================= HELPER =================

    // Data aksesoris paket (mis. handle) + pilihan stok gudang untuk form SJ / terima dropship
    private function _data_aksesoris(&$data, $ids_so_detail)
    {
        $data['aks_aktif'] = $this->Pemenuhan_model->ada_tabel_sj_aksesoris();
        $data['aks_map']   = $data['aks_aktif'] ? $this->Pemenuhan_model->get_aksesoris_so($ids_so_detail) : array();
        $prioritas = array();
        foreach ($data['aks_map'] as $list) {
            foreach ($list as $a) {
                $prioritas[] = (int) ($a->id_barang_default ?? 0);
            }
        }
        $data['aks_stok']  = $data['aks_map'] ? $this->Pemenuhan_model->get_stok_aksesoris($prioritas) : array();
    }

    // POST aks_qty[id_aksesoris], aks_stok[id_aksesoris] -> array(id => array(qty, kunci))
    private function _baca_aksesoris()
    {
        $qty   = (array) $this->input->post('aks_qty');
        $kunci = (array) $this->input->post('aks_stok');
        $hasil = array();
        foreach ($qty as $id => $q) {
            $hasil[(int) $id] = array(
                'qty'   => (float) str_replace(',', '.', (string) $q),
                'kunci' => (string) ($kunci[$id] ?? ''),
            );
        }
        return $hasil;
    }

    private function _view($view, $data)
    {
        $data['page_title']  = $data['title'];
        $data['active_menu'] = isset($data['active_menu']) ? $data['active_menu'] : 'pemenuhan';
        $data['izin']        = array();
        foreach (array_keys($this->izin) as $k) {
            $data['izin'][$k] = $this->_boleh($k);
        }
        $this->load->view('templates/header', $data);
        $this->load->view($view, $data);
        $this->load->view('templates/footer', $data);
    }

    private function _so_atau_404($id)
    {
        $so = $this->Sales_order_model->get_so_lengkap((int) $id);
        if (!$so) {
            show_404();
        }
        return $so['header'];
    }

    private function _status_proses($h)
    {
        return in_array($h->status, array('confirmed', 'produksi', 'terkirim'), true);
    }

    private function _tolak($id_so, $pesan)
    {
        $this->session->set_flashdata('error', $pesan);
        redirect('pemenuhan/so/' . (int) $id_so);
    }

    private function _flash($ok, $pesan)
    {
        $this->session->set_flashdata($ok ? 'success' : 'error', $pesan);
    }

    private function _tgl($v)
    {
        $v = trim((string) $v);
        return ($v !== '' && strtotime($v)) ? date('Y-m-d', strtotime($v)) : '';
    }

    // ---------- Hak akses ----------
    private function _role()
    {
        return (string) $this->session->userdata('role');
    }

    private function _boleh($kunci)
    {
        return isset($this->izin[$kunci]) && in_array($this->_role(), $this->izin[$kunci], true);
    }

    private function _wajib_izin($kunci, $kembali = 'pemenuhan')
    {
        if (!$this->_boleh($kunci)) {
            $this->session->set_flashdata('error', translate('i18n_anda_tidak_punya_hak_akses_untuk_aksi'));
            redirect($kembali);
        }
    }

    // Tujuan redirect setelah aksi (hanya halaman di modul pemenuhan)
    private function _kembali($default)
    {
        $k = trim((string) $this->input->post('kembali'));
        return preg_match('#^pemenuhan/[a-z_]+(/\d+)?$#', $k) ? $k : $default;
    }

    // Upload bukti transfer ke uploads/po_pembayaran/ (jpg, png, pdf, maks 2MB)
    private function _upload_bukti($field)
    {
        $rel = 'uploads/po_pembayaran/';
        $dir = FCPATH . $rel;
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return array(false, (translate('i18n_folder') . ' ') . $rel . (' ' . translate('i18n_tidak_bisa_dibuat_buat_manual_beri')));
        }
        $this->load->library('upload');
        $this->upload->initialize(array(
            'upload_path'   => $dir,
            'allowed_types' => 'jpg|jpeg|png|pdf',
            'max_size'      => 2048,
            'file_name'     => 'bayar_po_' . date('YmdHis') . '_' . mt_rand(100, 999),
            'overwrite'     => false,
        ));
        if (!$this->upload->do_upload($field)) {
            return array(false, (translate('i18n_upload_bukti_gagal') . ' ') . strip_tags($this->upload->display_errors('', '')) . (' ' . translate('i18n_jpg_png_pdf_maks_2mb')));
        }
        return array(true, $rel . $this->upload->data('file_name'));
    }

    private function _wajib_post()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
    }

    // Sesuaikan dengan key session login di aplikasi Anda
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
