<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PIUTANG CUSTOMER
 *
 * Sumber piutang = Sales Order (bukan tabel baru):
 *   tagihan  = sales_order.grand_total
 *   diterima = SUM(sales_order_pembayaran.nominal)   (DP + pelunasan + lainnya)
 *   sisa     = tagihan - diterima
 *
 * Tahap penagihan tiap SO (dihitung, tidak disimpan):
 *   dp      : DP sesuai termin belum masuk (SO masih Draft / DP kurang)
 *   proses  : DP sudah cukup, barang belum terkirim semua -> pelunasan belum jatuh tempo
 *   tagih   : semua barang sudah terkirim tapi belum lunas -> SIAP DITAGIH pelunasan
 *
 * Umur piutang:
 *   tahap tagih -> dihitung dari tanggal Surat Jalan terakhir
 *   tahap lain  -> dihitung dari tanggal SO
 */
class Piutang_model extends CI_Model
{
    private $eps = 0.005;

    public $tahap_label = array(
        'dp'     => array('warning', 'Tagih DP'),
        'proses' => array('info', 'Menunggu pengiriman'),
        'tagih'  => array('danger', 'Siap ditagih pelunasan'),
    );

    public $aging_label = array(
        '0_30'   => '0 – 30 hari',
        '31_60'  => '31 – 60 hari',
        '61_90'  => '61 – 90 hari',
        '90_up'  => '> 90 hari',
    );

    // Semua SO (bukan batal) yang masih punya sisa tagihan.
    // $f: q (no SO / no PO / customer), id_customer
    public function get_piutang($f = array())
    {
        $ada_sj = $this->db->table_exists('pm_sj');
        $sj_sql = $ada_sj
            ? "(SELECT MAX(sj.tanggal) FROM pm_sj sj WHERE sj.id_so = so.id AND COALESCE(sj.status, '') <> 'batal')"
            : 'NULL';
        $kirim_sql = $this->db->field_exists('qty_terkirim', 'sales_order_detail')
            ? '(SELECT COALESCE(SUM(d.qty_terkirim), 0) FROM sales_order_detail d WHERE d.id_so = so.id)'
            : '0';

        $where  = array("COALESCE(so.status, '') <> 'batal'");
        $params = array();
        if (!empty($f['q'])) {
            $where[] = '(so.no_so LIKE ? OR so.no_po_customer LIKE ? OR c.nama LIKE ?)';
            $like    = '%' . $this->db->escape_like_str($f['q']) . '%';
            array_push($params, $like, $like, $like);
        }
        if (!empty($f['id_customer'])) {
            $where[]  = 'so.id_customer = ?';
            $params[] = (int) $f['id_customer'];
        }

        $sql = "SELECT * FROM (
                    SELECT so.id, so.no_so, so.tanggal, so.tgl_kirim, so.status, so.termin, so.dp_persen,
                           so.grand_total, so.no_po_customer, so.id_customer,
                           c.nama AS nama_customer, c.kontak AS kontak_customer,
                           (SELECT COALESCE(SUM(sp.nominal), 0) FROM sales_order_pembayaran sp WHERE sp.id_so = so.id) AS total_bayar,
                           (SELECT MAX(sp.tanggal) FROM sales_order_pembayaran sp WHERE sp.id_so = so.id) AS tgl_bayar_terakhir,
                           (SELECT COALESCE(SUM(d.qty), 0) FROM sales_order_detail d WHERE d.id_so = so.id) AS total_qty,
                           {$kirim_sql} AS total_kirim,
                           {$sj_sql} AS tgl_sj_terakhir
                      FROM sales_order so
                      LEFT JOIN customer c ON c.id = so.id_customer
                     WHERE " . implode(' AND ', $where) . "
                ) x
                WHERE x.grand_total - x.total_bayar > {$this->eps}
                ORDER BY x.tanggal ASC, x.id ASC";

        $rows = $this->db->query($sql, $params)->result();
        $hari_ini = strtotime(date('Y-m-d'));
        foreach ($rows as $r) {
            $this->_lengkapi($r, $hari_ini);
        }
        return $rows;
    }

    // Hitung sisa, kekurangan DP, tahap, umur & kelompok aging
    private function _lengkapi($r, $hari_ini)
    {
        $grand         = (float) $r->grand_total;
        $bayar         = (float) $r->total_bayar;
        $r->sisa       = max(0, $grand - $bayar);
        $r->dp_nilai   = round($grand * (float) $r->dp_persen / 100);
        $r->kurang_dp  = max(0, $r->dp_nilai - $bayar);
        $qty           = (float) $r->total_qty;
        $kirim         = (float) $r->total_kirim;
        $r->persen_kirim = $qty > 0 ? min(100, round($kirim / $qty * 100)) : 0;
        $semua_terkirim  = $qty > 0 && $kirim + $this->eps >= $qty;

        if ($semua_terkirim || in_array($r->status, array('terkirim', 'selesai'), true)) {
            $r->tahap = 'tagih';
        } elseif ($r->kurang_dp > $this->eps) {
            $r->tahap = 'dp';
        } else {
            $r->tahap = 'proses';
        }

        // Nominal yang SEKARANG perlu ditagih
        if ($r->tahap === 'dp') {
            $r->tagih_sekarang = $r->kurang_dp;
        } elseif ($r->tahap === 'tagih') {
            $r->tagih_sekarang = $r->sisa;
        } else {
            $r->tagih_sekarang = 0; // pelunasan setelah barang dikirim
        }

        $acuan        = ($r->tahap === 'tagih' && $r->tgl_sj_terakhir) ? $r->tgl_sj_terakhir : $r->tanggal;
        $r->tgl_acuan = $acuan;
        $r->umur      = $acuan ? max(0, (int) floor(($hari_ini - strtotime($acuan)) / 86400)) : 0;
        $r->aging     = $r->umur <= 30 ? '0_30' : ($r->umur <= 60 ? '31_60' : ($r->umur <= 90 ? '61_90' : '90_up'));
    }

    // Pembayaran customer yang masuk pada rentang tanggal
    public function get_penerimaan($dari, $sampai, $q = '')
    {
        $this->db->select('sp.*, so.no_so, so.id_customer, c.nama AS nama_customer')
            ->from('sales_order_pembayaran sp')
            ->join('sales_order so', 'so.id = sp.id_so', 'left')
            ->join('customer c', 'c.id = so.id_customer', 'left')
            ->where('sp.tanggal >=', $dari)
            ->where('sp.tanggal <=', $sampai);
        if ($q !== '') {
            $this->db->group_start()
                ->like('so.no_so', $q)
                ->or_like('c.nama', $q)
                ->or_like('sp.no_referensi', $q)
                ->group_end();
        }
        return $this->db->order_by('sp.tanggal', 'DESC')->order_by('sp.id', 'DESC')->get()->result();
    }

    public function get_customer($id)
    {
        return $this->db->get_where('customer', array('id' => (int) $id))->row();
    }

    // Kartu piutang: semua SO customer (kecuali batal) + seluruh pembayarannya
    public function get_kartu_customer($id_customer)
    {
        $so = $this->db->select('so.id, so.no_so, so.tanggal, so.status, so.grand_total, so.no_po_customer,
                (SELECT COALESCE(SUM(sp.nominal), 0) FROM sales_order_pembayaran sp WHERE sp.id_so = so.id) AS total_bayar', false)
            ->from('sales_order so')
            ->where('so.id_customer', (int) $id_customer)
            ->where("COALESCE(so.status, '') <> 'batal'", null, false)
            ->order_by('so.tanggal', 'ASC')->order_by('so.id', 'ASC')
            ->get()->result();

        $bayar = $this->db->select('sp.*, so.no_so')
            ->from('sales_order_pembayaran sp')
            ->join('sales_order so', 'so.id = sp.id_so')
            ->where('so.id_customer', (int) $id_customer)
            ->where("COALESCE(so.status, '') <> 'batal'", null, false)
            ->order_by('sp.tanggal', 'ASC')->order_by('sp.id', 'ASC')
            ->get()->result();

        // Mutasi: SO = debit (tagihan), pembayaran = kredit
        $mutasi = array();
        foreach ($so as $s) {
            $mutasi[] = (object) array('tanggal' => $s->tanggal, 'urut' => 0, 'id' => (int) $s->id,
                'ket' => 'Sales Order ' . $s->no_so . ($s->no_po_customer ? ' (PO ' . $s->no_po_customer . ')' : ''),
                'id_so' => (int) $s->id, 'debit' => (float) $s->grand_total, 'kredit' => 0);
        }
        foreach ($bayar as $b) {
            $mutasi[] = (object) array('tanggal' => $b->tanggal, 'urut' => 1, 'id' => (int) $b->id,
                'ket' => 'Pembayaran ' . strtoupper($b->jenis) . ' ' . $b->no_so
                    . ($b->metode ? ' · ' . $b->metode : '') . ($b->no_referensi ? ' · ' . $b->no_referensi : ''),
                'id_so' => (int) $b->id_so, 'debit' => 0, 'kredit' => (float) $b->nominal);
        }
        usort($mutasi, function ($a, $b) {
            return array($a->tanggal, $a->urut, $a->id) <=> array($b->tanggal, $b->urut, $b->id);
        });
        $saldo = 0;
        foreach ($mutasi as $m) {
            $saldo  += $m->debit - $m->kredit;
            $m->saldo = $saldo;
        }
        return array('so' => $so, 'mutasi' => $mutasi, 'saldo' => $saldo);
    }
}
