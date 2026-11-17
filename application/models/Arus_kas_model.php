<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * ARUS KAS: semua uang masuk & keluar per bulan, dari tabel yang sudah ada.
 *
 *   MASUK  : sales_order_pembayaran (pembayaran customer)
 *            kas_masuk + kas_masuk_kategori (modal / pinjaman / pendapatan lain)
 *   KELUAR : pm_po_pembayaran (pembayaran ke supplier)
 *            biaya_operasional (biaya kantor)
 *
 * Tabel yang belum ada dilewati (nilainya 0) dan dicatat di $this->tidak_ada.
 */
class Arus_kas_model extends CI_Model
{
    public $tidak_ada = array();

    // Sumber sederhana: tabel => kunci baris
    private $sumber = array(
        'customer' => 'sales_order_pembayaran',
        'supplier' => 'pm_po_pembayaran',
        'biaya'    => 'biaya_operasional',
    );

    /**
     * Return array baris => array(1..12 => nominal):
     *   customer, modal, pinjaman, pendapatan, supplier, biaya
     */
    public function per_bulan($tahun)
    {
        $dari   = sprintf('%04d-01-01', $tahun);
        $sampai = sprintf('%04d-12-31', $tahun);
        $kosong = array_fill(1, 12, 0.0);
        $hasil  = array_fill_keys(array('customer', 'modal', 'pinjaman', 'pendapatan', 'supplier', 'biaya'), $kosong);
        $this->tidak_ada = array();

        foreach ($this->sumber as $baris => $tabel) {
            if (!$this->db->table_exists($tabel)) {
                $this->tidak_ada[] = $tabel;
                continue;
            }
            $rows = $this->db->query(
                'SELECT MONTH(tanggal) AS bln, SUM(nominal) AS total FROM ' . $this->db->protect_identifiers($tabel) . '
                  WHERE tanggal BETWEEN ? AND ? GROUP BY MONTH(tanggal)',
                array($dari, $sampai)
            )->result();
            foreach ($rows as $r) {
                $hasil[$baris][(int) $r->bln] = (float) $r->total;
            }
        }

        if ($this->_kas_masuk_siap()) {
            $rows = $this->db->query(
                'SELECT k.jenis, MONTH(m.tanggal) AS bln, SUM(m.nominal) AS total
                   FROM kas_masuk m JOIN kas_masuk_kategori k ON k.id = m.id_kategori
                  WHERE m.tanggal BETWEEN ? AND ? GROUP BY k.jenis, MONTH(m.tanggal)',
                array($dari, $sampai)
            )->result();
            foreach ($rows as $r) {
                if (isset($hasil[$r->jenis])) {
                    $hasil[$r->jenis][(int) $r->bln] += (float) $r->total;
                }
            }
        } else {
            $this->tidak_ada[] = 'kas_masuk';
        }
        return $hasil;
    }

    // Saldo kas menurut catatan sistem sebelum 1 Januari $tahun (semua masuk - semua keluar)
    public function saldo_sebelum($tahun)
    {
        $batas = sprintf('%04d-01-01', $tahun);
        $tanda = array('customer' => 1, 'supplier' => -1, 'biaya' => -1);
        $saldo = 0.0;
        foreach ($this->sumber as $baris => $tabel) {
            if (!$this->db->table_exists($tabel)) {
                continue;
            }
            $r = $this->db->query(
                'SELECT COALESCE(SUM(nominal), 0) AS total FROM ' . $this->db->protect_identifiers($tabel) . ' WHERE tanggal < ?',
                array($batas)
            )->row();
            $saldo += $tanda[$baris] * (float) $r->total;
        }
        if ($this->_kas_masuk_siap()) {
            $r = $this->db->query('SELECT COALESCE(SUM(nominal), 0) AS total FROM kas_masuk WHERE tanggal < ?', array($batas))->row();
            $saldo += (float) $r->total;
        }
        return $saldo;
    }

    private function _kas_masuk_siap()
    {
        return $this->db->table_exists('kas_masuk') && $this->db->table_exists('kas_masuk_kategori');
    }
}
