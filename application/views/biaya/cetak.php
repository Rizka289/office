<?php defined('BASEPATH') or exit('No direct script access allowed');
/*
 * Cetak / PDF daftar Biaya Operasional (sesuai filter di halaman Biaya).
 * Data dari Biaya::_data_export(): $list, $per_kategori, $total, $periode, $info_filter, $dicetak, $nama_user
 */
include APPPATH . 'views/pemenuhan/_cetak.php';   // $kop, $css, $toolbar, $rp, $tgl
$metode_label = function ($m) {
  return $m ? translate('i18n_metode_' . strtolower(str_replace(array(' ', '-'), '_', $m))) : '-';
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title><?= strip_tags(translate('i18n_biaya_operasional')) ?> <?= html_escape($f['dari']) ?> - <?= html_escape($f['sampai']) ?></title>
  <?= $css ?>
  <style>
    table.item td, table.item th { font-size: 10px; }
    .ringkas { display: flex; gap: 18px; margin-top: 14px; page-break-inside: avoid; }
    .ringkas .box { flex: 1; border: 1px solid #c9d1d9; border-radius: 4px; padding: 8px 10px; }
    .ringkas h4 { margin: 0 0 6px; font-size: 10px; text-transform: uppercase; letter-spacing: .08em; color: #6b7782; }
    .ringkas table { width: 100%; border-collapse: collapse; }
    .ringkas td { padding: 2px 0; border-bottom: 1px dotted #d5dbe0; }
    .total-besar { font-size: 18px; font-weight: 700; color: #b32121; }
  </style>
</head>
<body>
  <?= $toolbar ?>
  <div class="page">
    <?php $kop(); ?>

    <div class="judul">
      <h1><?= mb_strtoupper(strip_tags(translate('i18n_biaya_operasional'))) ?></h1>
      <div class="no"><?= html_escape($periode) ?></div>
      <div class="muted"><?= html_escape($info_filter) ?></div>
    </div>

    <table class="item">
      <thead>
        <tr>
          <th class="c" style="width:28px">No</th>
          <th style="width:62px"><?= translate('tanggal') ?></th>
          <th><?= translate('i18n_bx_no_bukti') ?></th>
          <th><?= translate('i18n_kategori_2') ?></th>
          <th><?= translate('deskripsi') ?></th>
          <th><?= translate('i18n_metode') ?></th>
          <th class="r"><?= translate('i18n_nominal_2') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$list): ?>
          <tr><td colspan="7" class="c muted" style="padding:16px"><?= translate('i18n_belum_ada_pengeluaran_pada_periode_ini') ?></td></tr>
        <?php endif; ?>
        <?php foreach ($list as $i => $b): ?>
          <tr>
            <td class="c"><?= $i + 1 ?></td>
            <td><?= date('d/m/Y', strtotime($b->tanggal)) ?></td>
            <td><?= html_escape($b->no_bukti) ?></td>
            <td><?= html_escape($b->nama_kategori ?: '-') ?></td>
            <td>
              <?= html_escape($b->keterangan) ?>
              <?php if ($b->dibayar_ke): ?><div class="muted"><?= translate('i18n_ke') ?> <?= html_escape($b->dibayar_ke) ?></div><?php endif; ?>
            </td>
            <td><?= html_escape($metode_label($b->metode)) ?></td>
            <td class="r"><?= $rp($b->nominal) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr><td colspan="6" class="r"><?= translate('total') ?> (<?= count($list) ?> <?= translate('i18n_bx_transaksi') ?>)</td><td class="r"><?= $rp($total) ?></td></tr>
      </tfoot>
    </table>

    <div class="ringkas">
      <div class="box">
        <h4><?= translate('i18n_per_kategori') ?></h4>
        <table>
          <?php foreach ($per_kategori as $k): ?>
            <tr>
              <td><?= html_escape($k->nama ?: '-') ?> <span class="muted">(<?= (int) $k->jumlah ?>)</span></td>
              <td class="r"><?= $rp($k->total) ?></td>
              <td class="r muted" style="width:46px"><?= $total > 0 ? number_format($k->total / $total * 100, 1, ',', '.') : '0' ?>%</td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$per_kategori): ?><tr><td class="muted">-</td></tr><?php endif; ?>
        </table>
      </div>
      <div class="box" style="max-width:38%">
        <h4><?= translate('i18n_total_pengeluaran') ?></h4>
        <div class="total-besar"><?= $rp($total) ?></div>
        <div class="muted"><?= html_escape($periode) ?></div>
      </div>
    </div>

    <div class="ttd">
      <div class="col"><div><?= translate('i18n_bx_dibuat_oleh') ?></div><div class="garis"></div><div class="nama"><?= html_escape($nama_user) ?></div></div>
      <div class="col"><div><?= translate('i18n_bx_diperiksa_oleh') ?></div><div class="garis"></div><div class="nama">&nbsp;</div></div>
      <div class="col"><div><?= translate('i18n_disetujui_oleh') ?></div><div class="garis"></div><div class="nama">&nbsp;</div></div>
    </div>

    <div class="muted" style="margin-top:14px"><?= html_escape($dicetak) ?></div>
  </div>
</body>
</html>
