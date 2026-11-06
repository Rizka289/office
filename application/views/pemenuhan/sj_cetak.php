<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_cetak.php';
$h        = $sj['header'];
$items    = $sj['items'];
$dropship = $h->jenis === 'dropship';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Surat Jalan <?= html_escape($h->no_sj) ?></title>
  <?= $css ?>
</head>
<body>
  <?= $toolbar ?>
  <div class="page">
    <?php $kop(); ?>

    <div class="judul">
      <h1>SURAT JALAN</h1>
      <div class="no">No. <?= html_escape($h->no_sj) ?></div>
    </div>

    <?php if ($h->status === 'batal'): ?><div class="peringatan">SURAT JALAN INI DIBATALKAN</div><?php endif; ?>

    <div class="info">
      <div class="box">
        <h4>Kepada</h4>
        <div style="font-weight:700;font-size:12px"><?= html_escape($h->nama_customer) ?></div>
        <div><?= $multi($isi($h->alamat_kirim) ? $h->alamat_kirim : $h->alamat_customer) ?></div>
      </div>
      <div class="box">
        <h4>Detail pengiriman</h4>
        <table>
          <tr><td>Tanggal</td><td>: <?= $tgl($h->tanggal) ?></td></tr>
          <tr><td>Ref. SO</td><td>: <?= html_escape($h->no_so) ?></td></tr>
          <?php if ($isi($h->no_po_customer)): ?><tr><td>No. PO Anda</td><td>: <?= html_escape($h->no_po_customer) ?></td></tr><?php endif; ?>
          <?php if ($dropship): ?>
            <tr><td>Dikirim oleh</td><td>: <?= html_escape($h->nama_supplier) ?> (dropship)</td></tr>
          <?php endif; ?>
          <?php if ($isi($h->ekspedisi)): ?><tr><td>Ekspedisi</td><td>: <?= html_escape($h->ekspedisi) ?></td></tr><?php endif; ?>
          <?php if ($isi($h->no_kendaraan)): ?><tr><td>No. kendaraan</td><td>: <?= html_escape($h->no_kendaraan) ?></td></tr><?php endif; ?>
          <?php if ($isi($h->no_resi)): ?><tr><td>No. resi</td><td>: <?= html_escape($h->no_resi) ?></td></tr><?php endif; ?>
        </table>
      </div>
    </div>

    <table class="item">
      <thead>
        <tr><th style="width:5%">No</th><th>Barang</th><th style="width:10%">Qty</th><th style="width:30%">Keterangan</th></tr>
      </thead>
      <tbody>
        <?php $no = 1; foreach ($items as $it): ?>
          <tr>
            <td class="c"><?= $no++ ?></td>
            <td><strong><?= html_escape($it->p_nama) ?></strong>
              <?php if ($isi($it->finishing ?? '')): ?><div class="muted"><?= html_escape($it->finishing) ?></div><?php endif; ?>
            </td>
            <td class="c"><?= $num($it->qty_kirim) ?></td>
            <td></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ($isi($h->catatan)): ?>
      <div class="catatan"><strong>Catatan:</strong><br><?= $multi($h->catatan) ?></div>
    <?php endif; ?>
    <?php if ($dropship && $isi($h->penerima)): ?>
      <div class="catatan">Diterima oleh: <strong><?= html_escape($h->penerima) ?></strong></div>
    <?php endif; ?>

    <div class="ttd">
      <div class="col"><div>Hormat kami,</div><div class="garis"></div><div class="nama">Gudang / Admin</div></div>
      <div class="col"><div>Pengirim / Sopir,</div><div class="garis"></div><div class="nama">&nbsp;</div></div>
      <div class="col"><div>Penerima,</div><div class="garis"></div><div class="nama"><?= $isi($h->penerima) ? html_escape($h->penerima) : '&nbsp;' ?></div></div>
    </div>
  </div>
</body>
</html>
