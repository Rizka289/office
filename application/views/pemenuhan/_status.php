<?php defined('BASEPATH') or exit('No direct script access allowed');
// Label & warna status dipakai di beberapa view. Panggil: $badge('po', $status)
$status_label = array(
  'so' => array(
    'draft' => array('secondary', 'Draft'), 'confirmed' => array('primary', 'Dikonfirmasi'),
    'produksi' => array('warning', 'Diproses'), 'terkirim' => array('info', 'Terkirim'),
    'selesai' => array('success', 'Selesai'), 'batal' => array('danger', 'Batal'),
  ),
  'po' => array(
    'draft' => array('secondary', 'Menunggu approval'), 'approved' => array('dark', 'Disetujui'),
    'dipesan' => array('primary', 'Dipesan'),
    'sebagian' => array('warning', 'Diterima sebagian'), 'selesai' => array('success', 'Diterima'),
    'batal' => array('danger', 'Batal'),
  ),
  'spk' => array(
    'antri' => array('secondary', 'Antri'), 'proses' => array('warning', 'Dikerjakan'),
    'selesai' => array('success', 'Selesai'), 'batal' => array('danger', 'Batal'),
  ),
  'sj' => array('terkirim' => array('success', 'Terkirim'), 'batal' => array('danger', 'Batal')),
  'bayar' => array(
    'belum' => array('light', 'Belum dibayar'), 'dp' => array('warning', 'DP / sebagian'),
    'lunas' => array('success', 'Lunas'),
  ),
  'jenis_bayar' => array(
    'dp' => array('warning', 'DP'), 'termin' => array('info', 'Termin'), 'pelunasan' => array('success', 'Pelunasan'),
  ),
  'sumber' => array(
    'produksi' => array('primary', 'Produksi sendiri'),
    'beli' => array('warning', 'Beli (ke gudang)'),
    'dropship' => array('info', 'Dropship'),
    'stok' => array('success', 'Ambil dari stok'),
  ),
);
$badge = function ($grup, $s) use ($status_label) {
  $x = $status_label[$grup][(string) $s] ?? array('light', $s !== null && $s !== '' ? ucfirst($s) : 'Belum ditentukan');
  return '<span class="badge text-bg-' . $x[0] . ' fw-normal">' . html_escape($x[1]) . '</span>';
};
$rp  = function ($n) { return 'Rp ' . number_format((float) $n, 0, ',', '.'); };
$num = function ($n, $d = 2) {
  $s = number_format((float) $n, $d, ',', '.');
  return $d > 0 ? rtrim(rtrim($s, '0'), ',') : $s;
};
$tgl = function ($d) { return $d ? date('d M Y', strtotime($d)) : '-'; };
// Tombol aksi kecil (form POST + konfirmasi)
$tombol = function ($url, $label, $kelas, $tanya) {
  return form_open($url, array('class' => 'd-inline', 'onsubmit' => "return confirm('" . $tanya . "')"))
    . '<button type="submit" class="btn btn-sm ' . $kelas . ' py-0 px-2">' . $label . '</button>'
    . form_close();
};
