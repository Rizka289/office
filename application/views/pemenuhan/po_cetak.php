<?php defined('BASEPATH') or exit('No direct script access allowed');
/*
 * $mode = 'po' : Purchase Order ke supplier (dengan harga)
 * $mode = 'sj' : Surat jalan dropship atas nama perusahaan kita, untuk dibawa supplier ke customer (TANPA harga)
 */
include APPPATH . 'views/pemenuhan/_cetak.php';
$h        = $po['header'];
$items    = $po['items'];
$sup      = $h->supplier;
$dropship = $h->jenis === 'dropship';
$is_sj    = $mode === 'sj';
$judul    = $is_sj ? 'SURAT JALAN' : 'PURCHASE ORDER';
$fmt_luas = function ($v) {
  $s   = number_format((float) $v, 4, '.', '');
  $dec = max(2, strlen(rtrim(substr($s, strpos($s, '.') + 1), '0')));
  return number_format((float) $v, $dec, ',', '.');
};
$per_m2 = function ($d) { return ($d->hitung_per ?? 'unit') === 'm2'; };
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title><?= $judul ?> <?= html_escape($h->no_po) ?></title>
  <?= $css ?>
</head>
<body>
  <?= $toolbar ?>
  <div class="page">
    <?php if (in_array($h->status, array('draft', 'batal'), true)): ?>
      <div style="border:2px solid #c0392b;color:#c0392b;text-align:center;font-weight:700;padding:6px;margin-bottom:10px">
        <?= $h->status === 'draft' ? 'DRAFT - BELUM DISETUJUI ATASAN, JANGAN DIKIRIM KE SUPPLIER' : 'PO DIBATALKAN' ?>
      </div>
    <?php endif; ?>
    <?php $kop(); ?>

    <div class="judul">
      <h1><?= $judul ?></h1>
      <div class="no">No. <?= html_escape($is_sj ? str_replace('/PO-', '/SJD-', $h->no_po) : $h->no_po) ?></div>
    </div>

    <?php if (!$is_sj && $dropship): ?>
      <div class="peringatan">DROPSHIP &mdash; KIRIM LANGSUNG KE ALAMAT CUSTOMER. JANGAN SERTAKAN NOTA / HARGA.</div>
    <?php endif; ?>

    <div class="info">
      <div class="box">
        <?php if ($is_sj): ?>
          <h4>Kepada (penerima)</h4>
          <div><?= $multi($h->alamat_kirim) ?></div>
        <?php else: ?>
          <h4>Kepada supplier</h4>
          <div style="font-weight:700;font-size:12px"><?= html_escape($sup ? $sup->nama : '-') ?></div>
          <?php if ($sup && $isi($sup->alamat ?? '')): ?><div><?= $multi($sup->alamat) ?></div><?php endif; ?>
          <?php if ($sup && $isi($sup->kontak ?? '')): ?><div>Kontak: <?= html_escape($sup->kontak) ?></div><?php endif; ?>
        <?php endif; ?>
      </div>
      <div class="box">
        <h4><?= $is_sj ? 'Detail pengiriman' : 'Detail PO' ?></h4>
        <table>
          <tr><td>Tanggal</td><td>: <?= $tgl($h->tanggal) ?></td></tr>
          <?php if ($is_sj): ?>
            <tr><td>Ref. pesanan</td><td>: <?= html_escape($h->no_so) ?></td></tr>
            <?php if ($isi($h->no_po_customer)): ?><tr><td>No. PO Anda</td><td>: <?= html_escape($h->no_po_customer) ?></td></tr><?php endif; ?>
          <?php else: ?>
            <?php if ($h->tgl_butuh): ?><tr><td>Dibutuhkan</td><td>: <?= $tgl($h->tgl_butuh) ?></td></tr><?php endif; ?>
            <tr><td>Kirim ke</td><td>: <?= $multi($h->alamat_kirim) ?></td></tr>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <table class="item">
      <thead>
        <tr>
          <th style="width:4%">No</th>
          <th>Barang</th>
          <th style="width:8%">Lebar (mm)</th>
          <th style="width:8%">Tinggi (mm)</th>
          <th style="width:6%">Qty</th>
          <th style="width:9%">Luas (m²)</th>
          <th style="width:<?= $is_sj ? '30' : '18' ?>%">Keterangan</th>
          <?php if (!$is_sj): ?>
            <th style="width:12%">Harga</th>
            <th style="width:13%">Total Harga</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1; $tq = 0; $tl = 0; foreach ($items as $d): $tq += (float) $d->qty; $tl += (float) ($d->luas_m2 ?? 0) * (float) $d->qty; ?>
          <tr>
            <td class="c"><?= $no++ ?></td>
            <td><strong><?= html_escape($d->nama_barang) ?></strong></td>
            <td class="c"><?= (int) ($d->lebar_mm ?? 0) > 0 ? $num($d->lebar_mm) : '-' ?></td>
            <td class="c"><?= (int) ($d->tinggi_mm ?? 0) > 0 ? $num($d->tinggi_mm) : '-' ?></td>
            <td class="c"><?= $num($d->qty) ?></td>
            <td class="c"><?= (float) ($d->luas_m2 ?? 0) > 0 ? $fmt_luas($d->luas_m2) : '-' ?></td>
            <td><?= $isi($d->spesifikasi) ? $multi($d->spesifikasi) : '' ?></td>
            <?php if (!$is_sj): ?>
              <td class="r"><?= $rp($d->harga) ?><div class="muted"><?= $per_m2($d) ? '/ m²' : '/ unit' ?></div></td>
              <td class="r"><?= $rp($d->total) ?></td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <?php if (!$is_sj): ?>
        <tfoot>
          <tr><td colspan="4" class="r">TOTAL</td><td class="c"><?= $num($tq) ?></td><td class="c"><?= $tl > 0 ? $fmt_luas($tl) : '-' ?></td><td colspan="2" class="r">TOTAL PO</td><td class="r"><?= $rp($h->total) ?></td></tr>
        </tfoot>
      <?php endif; ?>
    </table>

    <?php if (!$is_sj && $isi($h->catatan)): ?>
      <div class="catatan"><strong>Catatan:</strong><br><?= $multi($h->catatan) ?></div>
    <?php endif; ?>
    <?php if ($is_sj): ?>
      <div class="catatan">Barang diterima dalam keadaan baik dan lengkap. Mohon tanda tangan, nama jelas &amp; tanggal terima.</div>
    <?php endif; ?>

    <div class="ttd">
      <?php if ($is_sj): ?>
        <div class="col"><div>Pengirim,</div><div class="garis"></div><div class="nama"><?= html_escape($perusahaan['nama']) ?></div></div>
        <div class="col"><div>Sopir / Ekspedisi,</div><div class="garis"></div><div class="nama">&nbsp;</div></div>
        <div class="col"><div>Penerima,</div><div class="garis"></div><div class="nama">&nbsp;</div></div>
      <?php else: ?>
        <div class="col"><div>Dibuat oleh,</div><div class="garis"></div><div class="nama"><?= html_escape(!empty($h->nama_pembuat) ? $h->nama_pembuat : 'Purchasing') ?></div></div>
        <div class="col"><div>Disetujui,</div><div class="garis"></div><div class="nama"><?= html_escape(($h->status !== 'draft' && $h->status !== 'batal' && !empty($h->nama_approver)) ? $h->nama_approver : 'Atasan') ?></div></div>
        <div class="col"><div>Supplier,</div><div class="garis"></div><div class="nama"><?= html_escape($sup ? $sup->nama : '') ?></div></div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
