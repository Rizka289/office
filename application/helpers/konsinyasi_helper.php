<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Ekspresi SQL "tagihan" sebuah Sales Order.
 *   penjualan biasa : grand_total
 *   konsinyasi      : nilai_laku (hanya barang yang sudah dilaporkan laku yang ditagih)
 * Sebelum migrasi database/konsinyasi.sql dijalankan, hasilnya tetap grand_total.
 *
 * Contoh: "SELECT " . sql_tagihan_so('so') . " AS tagihan FROM sales_order so"
 */
if (!function_exists('sql_tagihan_so')) {
    function sql_tagihan_so($alias = 'so')
    {
        static $ada = null;
        if ($ada === null) {
            $CI  = &get_instance();
            $ada = $CI->db->field_exists('jenis_penjualan', 'sales_order') && $CI->db->field_exists('nilai_laku', 'sales_order');
        }
        $a = preg_replace('/[^a-z0-9_]/i', '', (string) $alias);
        return $ada
            ? "(CASE WHEN {$a}.jenis_penjualan = 'konsinyasi' THEN COALESCE({$a}.nilai_laku, 0) ELSE {$a}.grand_total END)"
            : "{$a}.grand_total";
    }
}
