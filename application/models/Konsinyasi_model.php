<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * KONSINYASI (TITIP JUAL)
 *
 * Alur:
 *   1. Penawaran jenis "konsinyasi" (masa 30 / 60 hari) -> approve
 *   2. Sales Order dibuat dari penawaran (jenis & masa ikut tersalin, tanpa DP)
 *   3. Pemenuhan seperti biasa (produksi / beli / stok) -> Surat Jalan = barang DITITIPKAN ke customer
 *   4. Selama masa titip, customer melapor barang yang LAKU  -> menu ini "Lapor laku"
 *        qty_laku naik, tagihan SO (nilai_laku) naik -> finance menagih & mencatat pembayaran di SO
 *   5. Barang yang TIDAK laku DITARIK kembali ke gudang -> menu ini "Tarik ke gudang"
 *        qty_retur naik, stok_barang bertambah + tercatat di Kartu Stok (KONSINYASI_TARIK)
 *   6. Lewat masa titip (tanggal SJ + masa hari) -> sisa barang di customer DITARIK OTOMATIS
 *        ke lokasi gudang default (config/konsinyasi.php). Item tanpa master barang ditandai
 *        "perlu tarik manual" karena stok gudang wajib punya barang.
 *   7. SO selesai otomatis kalau semua barang sudah laku / ditarik dan tagihan barang laku lunas.
 *
 * Masa titip dihitung PER SURAT JALAN (FIFO): barang yang dilaporkan laku / ditarik
 * mengurangi kiriman paling lama lebih dulu.
 */
class Konsinyasi_model extends CI_Model
{
    const MASA_DEFAULT = 60;

    // Status SO yang barang titipannya masih bisa dilapor laku / ditarik
    public $status_aktif = array('produksi', 'terkirim');

    public $opsi_valid = array('bukaan_kanan', 'bukaan_kiri', 'aksesoris', 'bahan');

    private $eps = 0.0001;

    public function __construct()
    {
        parent::__construct();
        $this->config->load('konsinyasi', true, true);
    }

    // ================= KESIAPAN DATABASE =================

    // Migrasi database/konsinyasi.sql sudah dijalankan?
    public function siap()
    {
        static $siap = null;
        if ($siap === null) {
            $siap = $this->db->table_exists('konsinyasi_mutasi')
                && $this->db->field_exists('jenis_penjualan', 'sales_order')
                && $this->db->field_exists('masa_konsinyasi', 'sales_order')
                && $this->db->field_exists('nilai_laku', 'sales_order')
                && $this->db->field_exists('qty_laku', 'sales_order_detail')
                && $this->db->field_exists('qty_retur', 'sales_order_detail');
        }
        return $siap;
    }

    private function _cfg($key, $default)
    {
        $v = $this->config->item($key, 'konsinyasi');
        return $v === null ? $default : $v;
    }

    public function auto_tarik_aktif()
    {
        return (bool) $this->_cfg('konsinyasi_auto_tarik', true);
    }

    // Lokasi gudang untuk barang yang ditarik otomatis
    public function lokasi_retur_default()
    {
        $id = (int) $this->_cfg('konsinyasi_lokasi_retur', 0);
        if ($id > 0 && $this->db->where('id', $id)->count_all_results('locations') > 0) {
            return $id;
        }
        $r = $this->db->select_min('id', 'id')->get('locations')->row();
        return $r && $r->id ? (int) $r->id : 0;
    }

    // ================= HITUNGAN (fungsi murni, mudah diuji) =================

    /**
     * Masa titip per surat jalan (FIFO).
     * $batches  : array of array('tanggal' => 'Y-m-d', 'qty' => float, 'no_sj' => string), urut tanggal kirim
     * $terpakai : qty_laku + qty_retur item tsb
     * $masa     : hari
     * $hari_ini : 'Y-m-d'
     * Barang lewat masa kalau hari_ini > tanggal SJ + masa (hari ke-masa masih boleh di customer).
     */
    public static function hitung_batch(array $batches, $terpakai, $masa, $hari_ini)
    {
        $eps        = 0.0001;
        $sisa_pakai = max(0, (float) $terpakai);
        $hasil      = array('batch' => array(), 'di_customer' => 0.0, 'qty_lewat' => 0.0, 'jt_terdekat' => null);
        $t_hari_ini = strtotime($hari_ini);

        foreach ($batches as $b) {
            $qty   = (float) $b['qty'];
            $pakai = min($qty, $sisa_pakai);
            $sisa_pakai -= $pakai;
            $sisa  = max(0, $qty - $pakai);
            $jt    = date('Y-m-d', strtotime($b['tanggal'] . ' +' . (int) $masa . ' days'));
            $lewat = $t_hari_ini > strtotime($jt);

            $hasil['batch'][] = array(
                'tanggal'     => $b['tanggal'],
                'no_sj'       => $b['no_sj'] ?? '',
                'qty'         => $qty,
                'sisa'        => $sisa,
                'jatuh_tempo' => $jt,
                'lewat'       => $lewat,
                'sisa_hari'   => (int) round((strtotime($jt) - $t_hari_ini) / 86400),
            );
            if ($sisa > $eps) {
                $hasil['di_customer'] += $sisa;
                if ($lewat) {
                    $hasil['qty_lewat'] += $sisa;
                }
                if ($hasil['jt_terdekat'] === null || $jt < $hasil['jt_terdekat']) {
                    $hasil['jt_terdekat'] = $jt;
                }
            }
        }
        return $hasil;
    }

    /**
     * Faktor pengali harga item -> nilai tagihan.
     * grand_total SO sudah termasuk PPN, PPh, biaya pasang & ongkir; dibagi rata
     * sesuai porsi total_harga tiap item. Jadi kalau SEMUA barang laku, tagihan = grand_total.
     */
    public static function faktor($grand_total, $sum_total_harga)
    {
        return (float) $sum_total_harga > 0 ? (float) $grand_total / (float) $sum_total_harga : 0.0;
    }

    // Harga tagihan per 1 unit item (sudah termasuk porsi pajak & biaya)
    public static function harga_unit_net($total_harga, $qty, $faktor)
    {
        return (float) $qty > 0 ? (float) $total_harga / (float) $qty * (float) $faktor : 0.0;
    }

    // nilai_laku SO = jumlah qty_laku x harga net per unit (dihitung ulang dari nol supaya tidak ada selisih pembulatan menumpuk)
    public static function hitung_nilai_laku(array $items, $grand_total)
    {
        $sum = 0.0;
        foreach ($items as $it) {
            $sum += (float) $it->total_harga;
        }
        $f     = self::faktor($grand_total, $sum);
        $nilai = 0.0;
        foreach ($items as $it) {
            $nilai += (float) $it->qty_laku * self::harga_unit_net($it->total_harga, $it->qty, $f);
        }
        return round(min($nilai, (float) $grand_total), 2);
    }

    // ================= DATA =================

    private function _batches($id_so_list)
    {
        $hasil = array();
        if (!$id_so_list) {
            return $hasil;
        }
        $rows = $this->db->select('d.id_so_detail, sj.tanggal, sj.no_sj, d.qty')
            ->from('pm_sj_detail d')
            ->join('pm_sj sj', 'sj.id = d.id_sj')
            ->where_in('sj.id_so', array_map('intval', $id_so_list))
            ->where('sj.status', 'terkirim')
            ->order_by('sj.tanggal', 'ASC')->order_by('sj.id', 'ASC')
            ->get()->result();
        foreach ($rows as $r) {
            $hasil[(int) $r->id_so_detail][] = array('tanggal' => substr($r->tanggal, 0, 10), 'qty' => (float) $r->qty, 'no_sj' => $r->no_sj);
        }
        return $hasil;
    }

    private function _masa($so)
    {
        $m = (int) ($so->masa_konsinyasi ?? 0);
        return $m > 0 ? $m : self::MASA_DEFAULT;
    }

    // Lengkapi tiap item: harga net, di customer, batch, qty lewat masa
    private function _lengkapi_items($so, &$items, $batches, $hari_ini)
    {
        $sum = 0.0;
        foreach ($items as $it) {
            $sum += (float) $it->total_harga;
        }
        $f    = self::faktor($so->grand_total, $sum);
        $masa = $this->_masa($so);
        foreach ($items as $it) {
            $it->qty_terkirim = (float) $it->qty_terkirim;
            $it->qty_laku     = (float) $it->qty_laku;
            $it->qty_retur    = (float) $it->qty_retur;
            $it->harga_net    = self::harga_unit_net($it->total_harga, $it->qty, $f);
            $b                = self::hitung_batch($batches[(int) $it->id] ?? array(), $it->qty_laku + $it->qty_retur, $masa, $hari_ini);
            $it->batch        = $b['batch'];
            // di customer = terkirim - laku - ditarik (sumber utama), batch hanya untuk jatuh tempo
            $it->di_customer  = max(0, $it->qty_terkirim - $it->qty_laku - $it->qty_retur);
            $it->qty_lewat    = min($it->di_customer, $b['qty_lewat']);
            $it->jt_terdekat  = $b['jt_terdekat'];
        }
    }

    // Ringkasan per SO dari item-itemnya
    private function _ringkas($so, $items)
    {
        $r = array('qty' => 0, 'terkirim' => 0, 'laku' => 0, 'retur' => 0, 'di_customer' => 0, 'lewat' => 0, 'jt_terdekat' => null);
        foreach ($items as $it) {
            $r['qty']         += (float) $it->qty;
            $r['terkirim']    += $it->qty_terkirim;
            $r['laku']        += $it->qty_laku;
            $r['retur']       += $it->qty_retur;
            $r['di_customer'] += $it->di_customer;
            $r['lewat']       += $it->qty_lewat;
            if ($it->di_customer > $this->eps && $it->jt_terdekat && ($r['jt_terdekat'] === null || $it->jt_terdekat < $r['jt_terdekat'])) {
                $r['jt_terdekat'] = $it->jt_terdekat;
            }
        }
        if ($so->status === 'selesai' || ($r['qty'] > 0 && $r['laku'] + $r['retur'] + $this->eps >= $r['qty'])) {
            $r['tahap'] = 'selesai';
        } elseif ($r['terkirim'] <= $this->eps) {
            $r['tahap'] = 'belum_kirim';
        } elseif ($r['lewat'] > $this->eps) {
            $r['tahap'] = 'jatuh_tempo';
        } else {
            $r['tahap'] = 'berjalan';
        }
        $r['tagihan']     = (float) $so->nilai_laku;
        $r['dibayar']     = (float) ($so->total_bayar ?? 0);
        $r['sisa_tagih']  = max(0, $r['tagihan'] - $r['dibayar']);
        return $r;
    }

    private function _items_so($id_so_list)
    {
        if (!$id_so_list) {
            return array();
        }
        return $this->db->where_in('id_so', array_map('intval', $id_so_list))
            ->order_by('id', 'ASC')->get('sales_order_detail')->result();
    }

    /**
     * Daftar SO konsinyasi + ringkasan.
     * $f: tahap (semua|aktif|berjalan|jatuh_tempo|belum_kirim|selesai), q
     */
    public function daftar($f = array())
    {
        $this->db->select("so.*, c.nama AS nama_customer,
                (SELECT COALESCE(SUM(sp.nominal), 0) FROM sales_order_pembayaran sp WHERE sp.id_so = so.id) AS total_bayar", false)
            ->from('sales_order so')
            ->join('customer c', 'c.id = so.id_customer', 'left')
            ->where('so.jenis_penjualan', 'konsinyasi')
            ->where_not_in('so.status', array('batal', 'draft'));
        if (!empty($f['q'])) {
            $this->db->group_start()->like('so.no_so', $f['q'])->or_like('so.no_po_customer', $f['q'])->or_like('c.nama', $f['q'])->group_end();
        }
        $so_list = $this->db->order_by('so.tanggal', 'DESC')->order_by('so.id', 'DESC')->get()->result();

        $ids     = array_map(function ($s) { return (int) $s->id; }, $so_list);
        $batches = $this->_batches($ids);
        $per_so  = array();
        foreach ($this->_items_so($ids) as $it) {
            $per_so[(int) $it->id_so][] = $it;
        }

        $hari_ini = date('Y-m-d');
        $hasil    = array();
        $jumlah   = array('semua' => 0, 'aktif' => 0, 'belum_kirim' => 0, 'berjalan' => 0, 'jatuh_tempo' => 0, 'selesai' => 0);
        $tahap    = $f['tahap'] ?? 'aktif';
        foreach ($so_list as $so) {
            $items = $per_so[(int) $so->id] ?? array();
            $this->_lengkapi_items($so, $items, $batches, $hari_ini);
            $so->r = $this->_ringkas($so, $items);
            $jumlah['semua']++;
            $jumlah[$so->r['tahap']]++;
            if ($so->r['tahap'] !== 'selesai') {
                $jumlah['aktif']++;
            }
            if ($tahap === 'semua' || $tahap === $so->r['tahap'] || ($tahap === 'aktif' && $so->r['tahap'] !== 'selesai')) {
                $hasil[] = $so;
            }
        }
        // Jatuh tempo terdekat di atas
        usort($hasil, function ($a, $b) {
            $ja = $a->r['jt_terdekat'] ?: '9999-12-31';
            $jb = $b->r['jt_terdekat'] ?: '9999-12-31';
            return array($ja, -(int) $a->id) <=> array($jb, -(int) $b->id);
        });
        return array('list' => $hasil, 'jumlah' => $jumlah);
    }

    // Detail 1 SO konsinyasi: header, item (lengkap dengan batch & identitas stok default), mutasi
    public function detail($id_so)
    {
        $so = $this->db->select("so.*, c.nama AS nama_customer, c.alamat AS alamat_customer,
                (SELECT COALESCE(SUM(sp.nominal), 0) FROM sales_order_pembayaran sp WHERE sp.id_so = so.id) AS total_bayar", false)
            ->from('sales_order so')
            ->join('customer c', 'c.id = so.id_customer', 'left')
            ->where('so.id', (int) $id_so)
            ->get()->row();
        if (!$so) {
            return null;
        }
        $items = $this->_items_so(array($so->id));
        $this->_lengkapi_items($so, $items, $this->_batches(array($so->id)), date('Y-m-d'));

        $this->load->model('Pemenuhan_model');
        foreach ($items as $it) {
            $it->p_nama = $this->Pemenuhan_model->nama_item($it);
            $it->stok   = $this->identitas_stok($it);
        }
        return array(
            'so'     => $so,
            'items'  => $items,
            'r'      => $this->_ringkas($so, $items),
            'mutasi' => $this->get_mutasi($so->id),
            'masa'   => $this->_masa($so),
        );
    }

    /**
     * Ke stok mana barang yang ditarik dimasukkan (bisa diubah di form tarik manual).
     *  - item yang diambil dari stok -> kembali ke barang/lokasi/opsi/ukuran asal
     *  - item produksi / beli        -> master barang item + ukuran pesanan + lokasi retur default
     */
    public function identitas_stok($it)
    {
        if ((string) $it->sumber === 'stok' && $this->db->table_exists('pm_alokasi_stok')) {
            $a = $this->db->where('id_so_detail', (int) $it->id)->where('qty_keluar >', 0)
                ->order_by('id', 'DESC')->limit(1)->get('pm_alokasi_stok')->row();
            if ($a) {
                return array('id_barang' => (int) $a->id_barang, 'id_location' => (int) $a->id_location,
                    'opsi' => (string) $a->opsi, 'ukuran' => (string) $a->ukuran);
            }
        }
        $this->load->model('Pemenuhan_model');
        list($l, $t) = $this->Pemenuhan_model->ukuran($it);
        return array(
            'id_barang'   => (int) ($it->id_barang ?? 0),
            'id_location' => $this->lokasi_retur_default(),
            'opsi'        => '',
            'ukuran'      => ($l > 0 && $t > 0) ? $l . ' x ' . $t : '',
        );
    }

    public function get_mutasi($id_so)
    {
        return $this->db->select('m.*, u.nama AS nama_user, b.kode_barang, b.nama AS nama_barang,
                l.location_code AS kode_lokasi, l.zone_name AS nama_lokasi', false)
            ->from('konsinyasi_mutasi m')
            ->join('users u', 'u.id = m.id_user', 'left')
            ->join('barang b', 'b.id = m.id_barang', 'left')
            ->join('locations l', 'l.id = m.id_location', 'left')
            ->where('m.id_so', (int) $id_so)
            ->order_by('m.tanggal', 'DESC')->order_by('m.id', 'DESC')
            ->get()->result();
    }

    public function get_barang()
    {
        return $this->db->select('id, kode_barang, nama, warna')->order_by('nama', 'ASC')->get('barang')->result();
    }

    public function get_lokasi()
    {
        return $this->db->select('id, location_code, zone_name')->order_by('id', 'ASC')->get('locations')->result();
    }

    // ================= PROSES =================

    // Jalankan dengan db_debug mati: error DB jadi pesan, bukan halaman error
    private function _aman($metode, $args)
    {
        $lama               = $this->db->db_debug;
        $this->db->db_debug = false;
        try {
            return $this->$metode(...$args);
        } finally {
            $this->db->db_debug = $lama;
        }
    }

    private function _gagal($pesan)
    {
        $e = $this->db->error();
        $this->db->trans_rollback();
        if (!empty($e['code'])) {
            log_message('error', 'Konsinyasi_model: ' . $pesan . ' | DB ' . $e['code'] . ': ' . $e['message']);
            $pesan .= ' (DB ' . $e['code'] . ': ' . $e['message'] . ')';
        }
        return array(false, $pesan);
    }

    // Kunci SO + muat item terbaru (di dalam transaksi)
    private function _kunci($id_so)
    {
        $so = $this->db->query('SELECT * FROM sales_order WHERE id = ? FOR UPDATE', array((int) $id_so))->row();
        if (!$so || ($so->jenis_penjualan ?? 'biasa') !== 'konsinyasi') {
            return array(null, array());
        }
        $items = $this->db->query('SELECT * FROM sales_order_detail WHERE id_so = ? ORDER BY id ASC FOR UPDATE', array((int) $so->id))->result();
        $this->_lengkapi_items($so, $items, $this->_batches(array($so->id)), date('Y-m-d'));
        return array($so, $items);
    }

    // Hitung ulang tagihan (nilai_laku) + status SO
    private function _sinkron($so)
    {
        $items = $this->db->select('qty, total_harga, qty_laku')->where('id_so', (int) $so->id)->get('sales_order_detail')->result();
        $this->db->where('id', (int) $so->id)->update('sales_order', array('nilai_laku' => self::hitung_nilai_laku($items, $so->grand_total)));
        $this->load->model('Pemenuhan_model');
        $this->Pemenuhan_model->sinkron_status_so($so->id);
    }

    /**
     * Customer melapor barang laku.
     * $qty_map: id_so_detail => qty
     * Return array(ok, pesan)
     */
    public function lapor_laku($id_so, $tanggal, $qty_map, $catatan, $id_user)
    {
        return $this->_aman('_proses_laku', array($id_so, $tanggal, $qty_map, $catatan, $id_user));
    }

    private function _proses_laku($id_so, $tanggal, $qty_map, $catatan, $id_user)
    {
        $this->db->trans_begin();
        list($so, $items) = $this->_kunci($id_so);
        if (!$so) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_ksy_so_bukan_konsinyasi'));
        }
        if (!in_array($so->status, $this->status_aktif, true)) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_ksy_status_tidak_bisa_diproses'));
        }

        $sum = 0.0;
        foreach ($items as $it) {
            $sum += (float) $it->total_harga;
        }
        $f     = self::faktor($so->grand_total, $sum);
        $total = 0;
        foreach ($items as $it) {
            $q = isset($qty_map[(int) $it->id]) ? (float) $qty_map[(int) $it->id] : 0;
            if ($q <= 0) {
                continue;
            }
            if ($q > $it->di_customer + $this->eps) {
                $nama = $this->_nama($it);
                return $this->_gagal(sprintf(translate('i18n_ksy_qty_melebihi_di_customer'), $nama, $this->_angka($it->di_customer)));
            }
            $this->db->insert('konsinyasi_mutasi', array(
                'id_so'        => (int) $so->id,
                'id_so_detail' => (int) $it->id,
                'jenis'        => 'laku',
                'tanggal'      => $tanggal,
                'qty'          => $q,
                'nilai'        => round($q * self::harga_unit_net($it->total_harga, $it->qty, $f), 2),
                'catatan'      => $catatan,
                'status'       => 'aktif',
                'id_user'      => $id_user,
                'created_at'   => date('Y-m-d H:i:s'),
            ));
            $this->db->query('UPDATE sales_order_detail SET qty_laku = qty_laku + ? WHERE id = ?', array($q, (int) $it->id));
            $total += $q;
        }
        if ($total <= 0) {
            return $this->_gagal(translate('i18n_ksy_isi_qty_minimal_satu'));
        }
        $this->_sinkron($so);
        if ($this->db->trans_status() === false) {
            return $this->_gagal(translate('i18n_ksy_gagal_simpan'));
        }
        $this->db->trans_commit();
        $baru = $this->db->select('nilai_laku')->get_where('sales_order', array('id' => (int) $so->id))->row();
        return array(true, sprintf(translate('i18n_ksy_laku_dicatat'), $this->_angka($total), 'Rp ' . number_format((float) $baru->nilai_laku, 0, ',', '.')));
    }

    /**
     * Tarik barang tidak laku ke gudang.
     * $rows: id_so_detail => array(qty, id_barang, id_location, opsi, ukuran)
     */
    public function tarik($id_so, $tanggal, $rows, $catatan, $id_user)
    {
        return $this->_aman('_proses_tarik', array($id_so, $tanggal, $rows, $catatan, $id_user));
    }

    private function _proses_tarik($id_so, $tanggal, $rows, $catatan, $id_user)
    {
        $this->db->trans_begin();
        list($so, $items) = $this->_kunci($id_so);
        if (!$so) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_ksy_so_bukan_konsinyasi'));
        }
        if (!in_array($so->status, $this->status_aktif, true)) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_ksy_status_tidak_bisa_diproses'));
        }
        list($ok, $hasil) = $this->_tarik_items($so, $items, $tanggal, $rows, $catatan, $id_user, false);
        if (!$ok) {
            return $this->_gagal($hasil);
        }
        if ($hasil <= 0) {
            return $this->_gagal(translate('i18n_ksy_isi_qty_minimal_satu'));
        }
        $this->_sinkron($so);
        if ($this->db->trans_status() === false) {
            return $this->_gagal(translate('i18n_ksy_gagal_simpan'));
        }
        $this->db->trans_commit();
        return array(true, sprintf(translate('i18n_ksy_tarik_dicatat'), $this->_angka($hasil)));
    }

    // Dipakai tarik manual & otomatis (di dalam transaksi, SO sudah dikunci).
    // Return array(true, total_qty) atau array(false, pesan)
    private function _tarik_items($so, $items, $tanggal, $rows, $catatan, $id_user, $otomatis)
    {
        $total = 0;
        foreach ($items as $it) {
            $r = $rows[(int) $it->id] ?? null;
            $q = $r ? (float) $r['qty'] : 0;
            if ($q <= 0) {
                continue;
            }
            $nama = $this->_nama($it);
            if (abs($q - round($q)) > $this->eps) {
                return array(false, sprintf(translate('i18n_ksy_qty_tarik_harus_bulat'), $nama));
            }
            if ($q > $it->di_customer + $this->eps) {
                return array(false, sprintf(translate('i18n_ksy_qty_melebihi_di_customer'), $nama, $this->_angka($it->di_customer)));
            }
            $id_barang = (int) $r['id_barang'];
            $id_lokasi = (int) $r['id_location'];
            $opsi      = (string) $r['opsi'];
            $ukuran    = mb_substr(preg_replace('/\s+/u', ' ', trim((string) $r['ukuran'])), 0, 100);
            if ($opsi !== '' && !in_array($opsi, $this->opsi_valid, true)) {
                $opsi = '';
            }
            if ($opsi === 'aksesoris') {
                $ukuran = '';
            }
            if ($id_barang <= 0 || $this->db->where('id', $id_barang)->count_all_results('barang') === 0) {
                return array(false, sprintf(translate('i18n_ksy_pilih_master_barang'), $nama));
            }
            if ($id_lokasi <= 0 || $this->db->where('id', $id_lokasi)->count_all_results('locations') === 0) {
                return array(false, sprintf(translate('i18n_ksy_pilih_lokasi'), $nama));
            }

            $q = (int) round($q);
            $this->db->insert('konsinyasi_mutasi', array(
                'id_so'        => (int) $so->id,
                'id_so_detail' => (int) $it->id,
                'jenis'        => 'tarik',
                'tanggal'      => $tanggal,
                'qty'          => $q,
                'nilai'        => 0,
                'id_barang'    => $id_barang,
                'id_location'  => $id_lokasi,
                'opsi'         => $opsi,
                'ukuran'       => $ukuran,
                'otomatis'     => $otomatis ? 1 : 0,
                'catatan'      => $catatan,
                'status'       => 'aktif',
                'id_user'      => $id_user,
                'created_at'   => date('Y-m-d H:i:s'),
            ));
            $id_mutasi = (int) $this->db->insert_id();
            $this->db->query('UPDATE sales_order_detail SET qty_retur = qty_retur + ? WHERE id = ?', array($q, (int) $it->id));

            $ket = ($otomatis ? translate('i18n_ksy_ket_tarik_otomatis') : translate('i18n_ksy_ket_tarik')) . ' ' . $so->no_so;
            $err = $this->_mutasi_stok($id_barang, $id_lokasi, $opsi, $ukuran, $q, 'KONSINYASI_TARIK', $id_mutasi, $ket, $id_user);
            if ($err !== '') {
                return array(false, $nama . ': ' . $err);
            }
            $it->di_customer -= $q;
            $total += $q;
        }
        return array(true, $total);
    }

    /**
     * Tarik otomatis semua barang yang sudah lewat masa titip.
     * $id_so = 0 -> semua SO konsinyasi aktif. Tiap SO diproses dalam transaksinya sendiri.
     * Return array('qty' => total ditarik, 'so' => jumlah SO, 'manual' => array(item yang harus ditarik manual), 'error' => array)
     */
    public function auto_tarik($id_so = 0, $id_user = null)
    {
        $hasil = array('qty' => 0, 'so' => 0, 'manual' => array(), 'error' => array());
        if (!$this->siap() || !$this->auto_tarik_aktif()) {
            return $hasil;
        }
        $this->db->select('id')->from('sales_order')
            ->where('jenis_penjualan', 'konsinyasi')
            ->where_in('status', $this->status_aktif);
        if ((int) $id_so > 0) {
            $this->db->where('id', (int) $id_so);
        }
        $ids = array_map(function ($r) { return (int) $r->id; }, $this->db->get()->result());

        // Saring cepat tanpa kunci: hanya SO yang memang punya barang lewat masa
        $kandidat = array();
        if ($ids) {
            $batches = $this->_batches($ids);
            $so_rows = $this->db->where_in('id', $ids)->get('sales_order')->result();
            $per_so  = array();
            foreach ($this->_items_so($ids) as $it) {
                $per_so[(int) $it->id_so][] = $it;
            }
            foreach ($so_rows as $so) {
                $items = $per_so[(int) $so->id] ?? array();
                $this->_lengkapi_items($so, $items, $batches, date('Y-m-d'));
                foreach ($items as $it) {
                    if ($it->qty_lewat >= 1 - $this->eps) {
                        $kandidat[] = (int) $so->id;
                        break;
                    }
                }
            }
        }

        foreach ($kandidat as $id) {
            $r = $this->_aman('_proses_auto_so', array($id, $id_user));
            $hasil['qty'] += $r['qty'];
            $hasil['so']  += $r['qty'] > 0 ? 1 : 0;
            $hasil['manual'] = array_merge($hasil['manual'], $r['manual']);
            if ($r['error'] !== '') {
                $hasil['error'][] = $r['error'];
            }
        }
        return $hasil;
    }

    private function _proses_auto_so($id_so, $id_user)
    {
        $out = array('qty' => 0, 'manual' => array(), 'error' => '');
        $this->db->trans_begin();
        list($so, $items) = $this->_kunci($id_so);
        if (!$so || !in_array($so->status, $this->status_aktif, true)) {
            $this->db->trans_rollback();
            return $out;
        }
        $this->load->model('Pemenuhan_model');
        $rows = array();
        foreach ($items as $it) {
            $q = (int) floor($it->qty_lewat + $this->eps);
            if ($q <= 0) {
                continue;
            }
            $stok = $this->identitas_stok($it);
            if ($stok['id_barang'] <= 0 || $stok['id_location'] <= 0) {
                $out['manual'][] = array('id_so' => (int) $so->id, 'no_so' => $so->no_so,
                    'nama' => $this->Pemenuhan_model->nama_item($it), 'qty' => $q);
                continue;
            }
            $rows[(int) $it->id] = $stok + array('qty' => $q);
        }
        if (!$rows) {
            $this->db->trans_rollback();
            return $out;
        }
        list($ok, $total) = $this->_tarik_items($so, $items, date('Y-m-d'), $rows, translate('i18n_ksy_catatan_otomatis'), $id_user, true);
        if (!$ok) {
            $r            = $this->_gagal($total);
            $out['error'] = $so->no_so . ': ' . $r[1];
            return $out;
        }
        $this->_sinkron($so);
        if ($this->db->trans_status() === false) {
            $r            = $this->_gagal(translate('i18n_ksy_gagal_simpan'));
            $out['error'] = $so->no_so . ': ' . $r[1];
            return $out;
        }
        $this->db->trans_commit();
        $out['qty'] = $total;
        return $out;
    }

    /**
     * Batalkan 1 catatan laku / tarik (salah input).
     *  - laku : tidak boleh kalau tagihan jadi lebih kecil dari uang yang sudah dibayar
     *  - tarik: stok di gudang harus masih ada (belum terpakai)
     * Return array(ok, pesan, id_so)
     */
    public function batal_mutasi($id_mutasi, $id_user)
    {
        return $this->_aman('_proses_batal', array($id_mutasi, $id_user));
    }

    private function _proses_batal($id_mutasi, $id_user)
    {
        $m = $this->db->get_where('konsinyasi_mutasi', array('id' => (int) $id_mutasi))->row();
        if (!$m) {
            return array(false, translate('i18n_ksy_data_tidak_ditemukan'), 0);
        }
        $this->db->trans_begin();
        list($so, $items) = $this->_kunci($m->id_so);
        $m = $this->db->query('SELECT * FROM konsinyasi_mutasi WHERE id = ? FOR UPDATE', array((int) $id_mutasi))->row();
        if (!$so || !in_array($so->status, array('produksi', 'terkirim', 'selesai'), true)) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_ksy_status_tidak_bisa_diproses'), (int) $m->id_so);
        }
        if ($m->status !== 'aktif') {
            $this->db->trans_rollback();
            return array(false, translate('i18n_ksy_sudah_dibatalkan'), (int) $so->id);
        }

        if ($m->jenis === 'laku') {
            $this->db->query('UPDATE sales_order_detail SET qty_laku = GREATEST(0, qty_laku - ?) WHERE id = ?', array((float) $m->qty, (int) $m->id_so_detail));
            $sisa = $this->db->select('qty, total_harga, qty_laku')->where('id_so', (int) $so->id)->get('sales_order_detail')->result();
            $nilai_baru = self::hitung_nilai_laku($sisa, $so->grand_total);
            if ($nilai_baru + 0.005 < $this->_total_bayar($so->id)) {
                $this->db->trans_rollback();
                return array(false, translate('i18n_ksy_laku_sudah_dibayar'), (int) $so->id);
            }
        } else {
            $this->db->query('UPDATE sales_order_detail SET qty_retur = GREATEST(0, qty_retur - ?) WHERE id = ?', array((float) $m->qty, (int) $m->id_so_detail));
            $err = $this->_mutasi_stok((int) $m->id_barang, (int) $m->id_location, (string) $m->opsi, (string) $m->ukuran,
                -(int) round((float) $m->qty), 'KONSINYASI_BATAL', (int) $m->id, translate('i18n_ksy_ket_batal_tarik') . ' ' . $so->no_so, $id_user);
            if ($err !== '') {
                return array_merge($this->_gagal($err), array((int) $so->id));
            }
        }

        $this->db->where('id', (int) $m->id)->update('konsinyasi_mutasi', array(
            'status' => 'batal', 'batal_oleh' => $id_user, 'batal_at' => date('Y-m-d H:i:s'),
        ));
        $this->_sinkron($so);
        if ($this->db->trans_status() === false) {
            return array_merge($this->_gagal(translate('i18n_ksy_gagal_simpan')), array((int) $so->id));
        }
        $this->db->trans_commit();
        return array(true, translate('i18n_ksy_dibatalkan'), (int) $so->id);
    }

    private function _total_bayar($id_so)
    {
        $r = $this->db->select_sum('nominal', 't')->where('id_so', (int) $id_so)->get('sales_order_pembayaran')->row();
        return $r ? (float) $r->t : 0.0;
    }

    // Ubah stok_barang (barang+lokasi+opsi+ukuran) sebesar $delta + catat Kartu Stok. Return '' atau pesan error.
    private function _mutasi_stok($id_barang, $id_lokasi, $opsi, $ukuran, $delta, $jenis, $id_ref, $ket, $id_user)
    {
        $rows = $this->db->query(
            'SELECT id, stok FROM stok_barang WHERE id_barang = ? AND id_location = ? AND opsi = ? AND ukuran = ? ORDER BY id ASC FOR UPDATE',
            array((int) $id_barang, (int) $id_lokasi, (string) $opsi, (string) $ukuran)
        )->result();
        $sebelum = 0;
        foreach ($rows as $r) {
            $sebelum += (int) $r->stok;
        }
        $sesudah = $sebelum + (int) $delta;
        if ($sesudah < 0) {
            return sprintf(translate('i18n_ksy_stok_gudang_kurang'), $sebelum);
        }
        if ($rows) {
            $this->db->set('stok', 'stok + (' . (int) $delta . ')', false)->where('id', $rows[0]->id)->update('stok_barang');
        } else {
            $ok = $this->db->insert('stok_barang', array(
                'id_barang' => (int) $id_barang, 'id_location' => (int) $id_lokasi,
                'opsi' => (string) $opsi, 'ukuran' => (string) $ukuran, 'stok' => $sesudah,
            ));
            if (!$ok) {
                return translate('i18n_ksy_gagal_tambah_stok');
            }
        }
        $this->db->insert('stok_riwayat', array(
            'id_barang'       => (int) $id_barang,
            'id_location'     => (int) $id_lokasi,
            'jenis_transaksi' => $jenis,
            'id_referensi'    => (int) $id_ref,
            'ukuran'          => (string) $ukuran,
            'opsi'            => (string) $opsi,
            'qty_masuk'       => $delta > 0 ? (int) $delta : 0,
            'qty_keluar'      => $delta < 0 ? (int) abs($delta) : 0,
            'stok_sebelum'    => $sebelum,
            'stok_sesudah'    => $sesudah,
            'keterangan'      => $ket,
            'created_by'      => $id_user,
            'created_at'      => date('Y-m-d H:i:s'),
        ));
        return '';
    }

    private function _nama($it)
    {
        $this->load->model('Pemenuhan_model');
        return $this->Pemenuhan_model->nama_item($it);
    }

    private function _angka($n)
    {
        $s = number_format((float) $n, 2, ',', '.');
        return rtrim(rtrim($s, '0'), ',');
    }
}
