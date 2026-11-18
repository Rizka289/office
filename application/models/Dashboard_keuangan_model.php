<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Data keuangan untuk grafik dashboard: uang MASUK vs uang KELUAR per bulan.
 *
 *   MASUK  = pembayaran customer (piutang/penerimaan) + kas masuk lain (modal, pinjaman, dll)
 *   KELUAR = biaya operasional + pembayaran hutang supplier
 *
 * Nama tabel/kolom DIDETEKSI OTOMATIS dari daftar kandidat di $sumber.
 * Kalau suatu sumber tidak ketemu, dashboard tetap jalan dan menampilkan
 * peringatan kecil (khusus super_admin). Cukup tambahkan nama tabel yang
 * benar di daftar 'tabel', atau isi 'tgl' / 'nom' kalau nama kolomnya beda.
 */
class Dashboard_keuangan_model extends CI_Model
{
    protected $sumber = array(
        'customer' => array(
            'arah'  => 'masuk',
            'label' => 'i18n_ak_pembayaran_customer', 'label_def' => 'Pembayaran customer',
            'url'   => 'piutang/penerimaan',
            'tabel' => array('piutang_bayar', 'piutang_penerimaan', 'piutang_pembayaran', 'penerimaan_pembayaran',
                             'pembayaran_customer', 'so_pembayaran', 'sales_order_bayar', 'so_bayar', 'pembayaran_so'),
        ),
        'kas_lain' => array(
            'arah'  => 'masuk',
            'label' => 'i18n_km_menu', 'label_def' => 'Kas masuk lain',
            'url'   => 'kas_masuk',
            'tabel' => array('kas_masuk'),
        ),
        'biaya' => array(
            'arah'  => 'keluar',
            'label' => 'i18n_biaya_operasional', 'label_def' => 'Biaya operasional',
            'url'   => 'biaya',
            'tabel' => array('biaya', 'biaya_operasional'),
        ),
        'hutang' => array(
            'arah'  => 'keluar',
            'label' => 'i18n_hutang_supplier', 'label_def' => 'Pembayaran hutang supplier',
            'url'   => 'pemenuhan/hutang',
            'tabel' => array('pm_hutang_bayar', 'pm_hutang_pembayaran', 'pm_po_bayar', 'pm_po_pembayaran', 'pm_pembayaran',
                             'hutang_bayar', 'hutang_pembayaran', 'pembayaran_supplier', 'po_bayar'),
        ),
    );

    protected $kol_tanggal = array('tanggal', 'tanggal_bayar', 'tgl_bayar', 'tgl', 'tanggal_terima', 'created_at');
    protected $kol_nominal = array('nominal', 'jumlah', 'jumlah_bayar', 'nominal_bayar', 'nilai', 'bayar', 'amount', 'total');

    public function get_data($tahun = null)
    {
        $tahun = (int) $tahun ?: (int) date('Y');
        $nol   = array_fill(1, 12, 0.0);
        $hasil = array(
            'tahun'        => $tahun,
            'sumber'       => array(),
            'masuk'        => $nol,
            'keluar'       => $nol,
            'tidak_ketemu' => array(),
        );

        $db_debug = $this->db->db_debug;
        $this->db->db_debug = false;

        foreach ($this->sumber as $key => $s) {
            $det   = $this->_deteksi($s);
            $bulan = $nol;

            if ($det) {
                $tgl = $this->db->protect_identifiers($det['tgl']);
                $nom = $this->db->protect_identifiers($det['nom']);
                $tbl = $this->db->protect_identifiers($det['tabel'], true);
                $q = $this->db->query(
                    "SELECT MONTH($tgl) AS b, SUM($nom) AS n FROM $tbl
                      WHERE $tgl >= ? AND $tgl < ? {$det['where']}
                      GROUP BY MONTH($tgl)",
                    array($tahun . '-01-01', ($tahun + 1) . '-01-01')
                );
                foreach ($q ? $q->result() : array() as $r) {
                    if ($r->b >= 1 && $r->b <= 12) {
                        $bulan[(int) $r->b] = (float) $r->n;
                    }
                }
            } else {
                $hasil['tidak_ketemu'][] = $key;
            }

            foreach ($bulan as $b => $n) {
                $hasil[$s['arah']][$b] += $n;
            }
            $hasil['sumber'][$key] = array(
                'arah'  => $s['arah'],
                'label' => $s['label'],
                'label_def' => $s['label_def'],
                'url'   => $s['url'],
                'tabel' => $det ? $det['tabel'] : null,
                'bulan' => $bulan,
                'total' => array_sum($bulan),
            );
        }

        $this->db->db_debug = $db_debug;

        // Total, selisih per bulan & saldo berjalan (kumulatif sejak Januari)
        $hasil['tot_masuk']  = array_sum($hasil['masuk']);
        $hasil['tot_keluar'] = array_sum($hasil['keluar']);
        $hasil['selisih']    = array();
        $hasil['saldo']      = array();
        $jalan = 0;
        foreach ($hasil['masuk'] as $b => $m) {
            $hasil['selisih'][$b] = $m - $hasil['keluar'][$b];
            $jalan += $hasil['selisih'][$b];
            $hasil['saldo'][$b] = $jalan;
        }

        // Bulan berjalan (kalau tahun yg dipilih = tahun ini), selain itu Desember
        $bln = ($tahun === (int) date('Y')) ? (int) date('n') : 12;
        $hasil['bulan_ini']        = $bln;
        $hasil['masuk_bulan_ini']  = $hasil['masuk'][$bln];
        $hasil['keluar_bulan_ini'] = $hasil['keluar'][$bln];
        $hasil['masuk_bulan_lalu'] = $bln > 1 ? $hasil['masuk'][$bln - 1] : null;
        $hasil['keluar_bulan_lalu'] = $bln > 1 ? $hasil['keluar'][$bln - 1] : null;

        return $hasil;
    }

    /** Cari tabel + kolom tanggal + kolom nominal yang benar-benar ada. */
    protected function _deteksi($s)
    {
        foreach ($s['tabel'] as $t) {
            if (!$this->db->table_exists($t)) {
                continue;
            }
            $fields = array_map('strtolower', (array) $this->db->list_fields($t));
            $tgl = isset($s['tgl']) ? $s['tgl'] : $this->_pilih($this->kol_tanggal, $fields);
            $nom = isset($s['nom']) ? $s['nom'] : $this->_pilih($this->kol_nominal, $fields);
            if (!$tgl || !$nom) {
                continue;
            }
            // Abaikan data yang di-soft-delete / dibatalkan kalau kolomnya ada
            $where = '';
            if (in_array('deleted_at', $fields, true)) { $where .= ' AND deleted_at IS NULL'; }
            if (in_array('is_deleted', $fields, true)) { $where .= ' AND (is_deleted = 0 OR is_deleted IS NULL)'; }
            if (in_array('batal', $fields, true))      { $where .= ' AND (batal = 0 OR batal IS NULL)'; }
            return array('tabel' => $t, 'tgl' => $tgl, 'nom' => $nom, 'where' => $where);
        }
        return null;
    }

    protected function _pilih($kandidat, $fields)
    {
        foreach ($kandidat as $k) {
            if (in_array($k, $fields, true)) {
                return $k;
            }
        }
        return null;
    }
}
