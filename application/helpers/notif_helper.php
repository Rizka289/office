<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * NOTIFIKASI per role — dipakai bersama oleh header (lonceng + badge menu)
 * dan dashboard (kartu "Perlu Persetujuan").
 *
 * Hasil di-cache per request, jadi walau dipanggil header DAN dashboard,
 * query ke database hanya jalan sekali.
 *
 * return array(
 *   'list'  => [ [ikon, judul, sub, url, waktu, jenis], ... ],
 *   'badge' => [ key_menu => jumlah ],
 *   'count' => [ 'penawaran' => n, 'po' => n ],
 * )
 */
if (!function_exists('get_notifikasi')) {
    function get_notifikasi($role)
    {
        static $cache = array();
        if (isset($cache[$role])) {
            return $cache[$role];
        }

        $notif_list  = array();
        $notif_badge = array();
        $notif_count = array('penawaran' => 0, 'po' => 0);

        if (in_array($role, array('atasan', 'super_admin'), true)) {
            $CI       =& get_instance();
            $db_debug = $CI->db->db_debug;
            $CI->db->db_debug = false; // kalau tabel/kolom belum ada, halaman tetap tampil

            // 1) Penawaran yang belum di-approve
            $qp = $CI->db->query(
                "SELECT pen.id, pen.no_surat, pen.grand_total, pen.tanggal, cus.nama AS nama_customer, u.nama AS nama_pembuat
                   FROM penawaran pen
                   LEFT JOIN customer cus ON cus.id = pen.id_customer
                   LEFT JOIN users u      ON u.id = pen.id_user
                  WHERE pen.status IS NULL OR pen.status <> 'approved'
                  ORDER BY pen.id ASC"
            );
            foreach ($qp ? $qp->result() : array() as $r) {
                $notif_list[] = array(
                    'jenis' => 'penawaran',
                    'ikon'  => 'bi-tags-fill text-primary',
                    'judul' => translate('i18n_penawaran') . ' ' . $r->no_surat . ' ' . translate('i18n_menunggu_approval_2'),
                    'sub'   => trim(($r->nama_customer ?: '-') . ' ' . translate('i18n_rp_2') . ' ' . number_format((float) $r->grand_total, 0, ',', '.')
                        . ($r->nama_pembuat ? ' ' . translate('i18n_oleh') . ' ' . $r->nama_pembuat : '')),
                    'url'   => site_url('penawaran/detail/' . (int) $r->id),
                    'waktu' => $r->tanggal ? date('d M Y', strtotime($r->tanggal)) : '',
                );
                $notif_count['penawaran']++;
            }

            // 2) PO yang masih draft
            $q = $CI->db->query(
                "SELECT po.id, po.no_po, po.total, po.tanggal, s.nama AS nama_supplier, so.no_so, u.nama AS nama_pembuat
                   FROM pm_po po
                   LEFT JOIN supplier s     ON s.id = po.id_supplier
                   LEFT JOIN sales_order so ON so.id = po.id_so
                   LEFT JOIN users u        ON u.id = po.id_user
                  WHERE po.status = 'draft'
                  ORDER BY po.id ASC"
            );
            $CI->db->db_debug = $db_debug;
            foreach ($q ? $q->result() : array() as $r) {
                $notif_list[] = array(
                    'jenis' => 'po',
                    'ikon'  => 'bi-hourglass-split text-warning',
                    'judul' => 'PO ' . $r->no_po . ' ' . translate('i18n_menunggu_approval_2'),
                    'sub'   => trim(($r->nama_supplier ?: '-') . ' ' . translate('i18n_rp_2') . ' ' . number_format((float) $r->total, 0, ',', '.')
                        . ($r->nama_pembuat ? ' ' . translate('i18n_oleh') . ' ' . $r->nama_pembuat : '')),
                    'url'   => site_url('pemenuhan/po_detail/' . (int) $r->id),
                    'waktu' => $r->tanggal ? date('d M Y', strtotime($r->tanggal)) : '',
                );
                $notif_count['po']++;
            }

            if ($notif_count['penawaran']) {
                $notif_badge['penawaran'] = $notif_count['penawaran'];
            }
            if ($notif_count['po']) {
                $notif_badge['po'] = $notif_count['po'];
            }
        }

        return $cache[$role] = array('list' => $notif_list, 'badge' => $notif_badge, 'count' => $notif_count);
    }
}
