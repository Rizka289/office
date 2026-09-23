<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Penawaran <?= html_escape($header->no_surat); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root { --brand: #1f7a45; --brand-dark: #14512f; --brand-light: #eaf5ee; }
  * { box-sizing: border-box; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #222; margin: 0; padding: 24px; background: #f2f4f3; }
  .sheet { max-width: 900px; margin: 0 auto; background: #fff; padding: 28px 32px; border-radius: 6px; box-shadow: 0 2px 10px rgba(0,0,0,.08); }

  .no-print { text-align: right; margin-bottom: 14px; }
  .no-print button { background: var(--brand); color: #fff; border: none; padding: 8px 18px; border-radius: 6px; font-size: 13px; cursor: pointer; }

  .kop { display: flex; align-items: center; justify-content: center; gap: 14px; border-bottom: 3px solid var(--brand); padding-bottom: 12px; margin-bottom: 16px; }
  .kop-logo { width: 46px; height: 46px; border-radius: 8px; background: var(--brand-light); color: var(--brand-dark); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px; }
  .kop-text { text-align: center; }
  .kop-text .nama { font-weight: 700; font-size: 16px; color: var(--brand-dark); }
  .kop-text .sub { font-size: 11px; color: #666; }

  .info-surat { width: 100%; margin-bottom: 16px; font-size: 12px; }
  .info-surat td { padding: 2px 0; vertical-align: top; }
  .info-surat td.label { width: 170px; color: #555; }
  .info-surat td.sep { width: 12px; }

  table.item-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
  table.item-table th, table.item-table td { border: 1px solid #d7dbd9; padding: 6px 8px; font-size: 11.5px; }
  table.item-table thead th { background: var(--brand-light); color: var(--brand-dark); text-align: center; font-weight: 700; }
  table.item-table tbody td { vertical-align: top; }
  table.item-table .num { text-align: center; white-space: nowrap; }
  table.item-table .money { text-align: right; white-space: nowrap; }
  .spek-line { display: block; color: #555; font-size: 11px; }

  table.total-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
  table.total-table td { padding: 5px 8px; font-size: 12px; }
  table.total-table td.tlabel { text-align: right; width: 82%; color: #444; }
  table.total-table td.tval { text-align: right; font-weight: 600; width: 18%; white-space: nowrap; }
  table.total-table tr.grand td { border-top: 2px solid var(--brand); font-weight: 700; font-size: 13px; color: var(--brand-dark); padding-top: 8px; }

  .syarat { font-size: 10.5px; color: #555; margin-bottom: 28px; }
  .syarat ol { margin: 4px 0 0 16px; padding: 0; }

  .ttd { display: flex; justify-content: space-between; margin-top: 24px; }
  .ttd .box { text-align: center; width: 220px; }
  .ttd .line { margin-top: 60px; border-top: 1px solid #333; padding-top: 4px; font-size: 11px; }

  @media print {
    body { background: #fff; padding: 0; }
    .sheet { box-shadow: none; padding: 0; }
    .no-print { display: none; }
  }
</style>
</head>
<body>

<div class="sheet">

  <div class="no-print">
    <button onclick="window.print()"><i class="bi bi-printer"></i> Cetak / Simpan PDF</button>
  </div>

  <!-- Kop Surat -->
  <div class="kop">
    <div class="kop-logo">OPJ</div>
    <div class="kop-text">
      <div class="nama">PT OUPAI PINTU JENDELA INDONESIA</div>
      <div class="sub">Manufaktur Pintu &amp; Jendela</div>
    </div>
  </div>

  <!-- Info Surat -->
  <table class="info-surat">
    <tr>
      <td class="label">Tanggal</td><td class="sep">:</td>
      <td><?= date('d/m/Y', strtotime($header->tanggal)); ?></td>
    </tr>
    <tr>
      <td class="label">No. Surat</td><td class="sep">:</td>
      <td><?= html_escape($header->no_surat); ?></td>
    </tr>
    <tr>
      <td class="label">Nama Perusahaan Klien</td><td class="sep">:</td>
      <td><?= html_escape($header->customer ?? '-'); ?></td>
    </tr>
    <tr>
      <td class="label">Informasi Kontak</td><td class="sep">:</td>
      <td><?= html_escape($header->customer_telepon ?? '-'); ?></td>
    </tr>
    <tr>
      <td class="label">Alamat Surat</td><td class="sep">:</td>
      <td><?= html_escape($header->customer_alamat ?? '-'); ?></td>
    </tr>
  </table>

  <!-- Tabel Item -->
  <table class="item-table">
    <thead>
      <tr>
        <th style="width:3%">No</th>
        <th style="width:34%">Deskripsi</th>
        <th style="width:9%">Lebar<br>(mm)</th>
        <th style="width:9%">Tinggi<br>(mm)</th>
        <th style="width:9%">Total Luas<br>(m²)</th>
        <th style="width:6%">Qty</th>
        <th style="width:14%">Harga/Unit</th>
        <th style="width:16%">Total Harga</th>
      </tr>
    </thead>
    <tbody>
      <?php $no = 1; $jumlah_unit = 0; ?>
      <?php foreach ($items as $it): $jumlah_unit += (int) $it->qty; ?>
        <tr>
          <td class="num"><?= $no++; ?></td>
          <td>
            <strong><?= html_escape($it->jenis_item); ?></strong>
            <?php if (!empty($it->warna)): ?><span class="spek-line">Warna: <?= html_escape($it->warna); ?></span><?php endif; ?>
            <?php if (!empty($it->komponen_kusen) || !empty($it->komponen_daun)): ?>
              <span class="spek-line">Ketebalan Plate Kusen/Daun: <?= html_escape($it->komponen_kusen ?: '-'); ?> / <?= html_escape($it->komponen_daun ?: '-'); ?> mm</span>
            <?php endif; ?>
            <?php if (!empty($it->finishing)): ?><span class="spek-line">Finishing: <?= html_escape($it->finishing); ?></span><?php endif; ?>
            <?php if (!empty($it->keterangan)): ?><span class="spek-line">Ket: <?= nl2br(html_escape($it->keterangan)); ?></span><?php endif; ?>
          </td>
          <td class="num"><?= (int) $it->lebar_mm; ?></td>
          <td class="num"><?= (int) $it->tinggi_mm; ?></td>
          <td class="num"><?= number_format((float) $it->luas_m2, 4); ?></td>
          <td class="num"><?= (int) $it->qty; ?></td>
          <td class="money">Rp <?= number_format((float) $it->harga_unit, 0, ',', '.'); ?></td>
          <td class="money">Rp <?= number_format((float) $it->total_harga, 0, ',', '.'); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <table class="total-table">
    <tr>
      <td class="tlabel">Jumlah Unit: <?= $jumlah_unit; ?></td>
      <td class="tval">Subtotal: Rp <?= number_format((float) $header->subtotal, 0, ',', '.'); ?></td>
    </tr>
    <tr>
      <td class="tlabel">PPN <?= rtrim(rtrim(number_format($header->ppn_persen, 2), '0'), '.'); ?>%</td>
      <td class="tval">Rp <?= number_format((float) $header->ppn_nominal, 0, ',', '.'); ?></td>
    </tr>
    <tr>
      <td class="tlabel">PPh <?= rtrim(rtrim(number_format($header->pph_persen, 2), '0'), '.'); ?>%</td>
      <td class="tval">Rp <?= number_format((float) $header->pph_nominal, 0, ',', '.'); ?></td>
    </tr>
    <tr class="grand">
      <td class="tlabel">Grand Total</td>
      <td class="tval">Rp <?= number_format((float) $header->grand_total, 0, ',', '.'); ?></td>
    </tr>
  </table>

  <!-- Syarat & Ketentuan -->
  <div class="syarat">
    <strong>Syarat &amp; Ketentuan:</strong>
    <ol>
      <li>Pembayaran dibayarkan 100% saat memesan.</li>
      <li>Pembayaran transfer ke Bank Mandiri 1360077787882 atas nama PT OUPAI PINTU JENDELA INDONESIA.</li>
      <li>Penawaran ini tidak bisa dipilih-pilih itemnya, apabila ada perubahan dibuatkan penawaran baru.</li>
      <li>Harga yang ditawarkan tidak termasuk biaya pengiriman.</li>
    </ol>
    <?php if (!empty($header->catatan)): ?>
      <p><strong>Catatan tambahan:</strong> <?= nl2br(html_escape($header->catatan)); ?></p>
    <?php endif; ?>
  </div>

  <!-- Tanda Tangan -->
  <div class="ttd">
    <div class="box">
      <div>Hormat kami,</div>
      <div class="line">PT OUPAI PINTU JENDELA INDONESIA</div>
    </div>
    <div class="box">
      <div>Disetujui oleh,</div>
      <div class="line"><?= html_escape($header->customer ?? '-'); ?></div>
    </div>
  </div>

</div>

</body>
</html>