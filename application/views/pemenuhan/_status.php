<?php defined('BASEPATH') or exit('No direct script access allowed');
// Label & warna status dipakai di beberapa view. Panggil: $badge('po', $status)
$status_label = array(
  'so' => array(
    'draft' => array('secondary', translate('i18n_draft')), 'confirmed' => array('primary', translate('i18n_dikonfirmasi')),
    'produksi' => array('warning', translate('i18n_diproses')), 'terkirim' => array('info', translate('kirim')),
    'selesai' => array('success', translate('selesai')), 'batal' => array('danger', translate('btn_batal')),
  ),
  'po' => array(
    'draft' => array('secondary', translate('i18n_menunggu_approval')), 'approved' => array('dark', translate('setuju')),
    'dipesan' => array('primary', translate('i18n_dipesan')),
    'sebagian' => array('warning', translate('i18n_diterima_sebagian')), 'selesai' => array('success', translate('i18n_diterima')),
    'batal' => array('danger', translate('btn_batal')),
  ),
  'spk' => array(
    'antri' => array('secondary', translate('i18n_antri')), 'proses' => array('warning', translate('i18n_dikerjakan')),
    'selesai' => array('success', translate('selesai')), 'batal' => array('danger', translate('btn_batal')),
  ),
  'sj' => array('terkirim' => array('success', translate('kirim')), 'batal' => array('danger', translate('btn_batal'))),
  'bayar' => array(
    'belum' => array('light', translate('i18n_belum_dibayar')), 'dp' => array('warning', translate('i18n_dp_sebagian')),
    'lunas' => array('success', translate('i18n_lunas')),
  ),
  'jenis_bayar' => array(
    'dp' => array('warning', 'DP'), 'termin' => array('info', translate('i18n_termin')), 'pelunasan' => array('success', translate('pelunasan')),
  ),
  'sumber' => array(
    'produksi' => array('primary', translate('i18n_produksi_sendiri')),
    'beli' => array('warning', translate('i18n_beli_ke_gudang')),
    'dropship' => array('info', translate('i18n_dropship')),
    'stok' => array('success', translate('i18n_ambil_dari_stok_2')),
  ),
);
$badge = function ($grup, $s) use ($status_label) {
  $x = $status_label[$grup][(string) $s] ?? array('light', $s !== null && $s !== '' ? ucfirst($s) : translate('i18n_belum_ditentukan'));
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
  return form_open($url, array('class' => 'd-inline', 'onsubmit' => "return confirm('" . js_esc($tanya) . "')"))
    . '<button type="submit" class="btn btn-sm ' . $kelas . ' py-0 px-2">' . $label . '</button>'
    . form_close();
};
