<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!--
  Halaman cetak berdiri sendiri (BUKAN fragment) — sengaja TIDAK memuat
  templates/header & templates/footer aplikasi (lihat Penerimaan_barang::cetak()),
  supaya hasil print bersih tanpa sidebar/topbar.
-->
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Bukti Penerimaan Barang - RCV-<?= html_escape($penerimaan->no_penerimaan); ?></title>
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 13px;
      color: #1f1f1f;
      margin: 0;
      padding: 24px;
    }
    .doc-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 3px solid #234f38;
      padding-bottom: 12px;
      margin-bottom: 18px;
    }
    .doc-header h1 { font-size: 18px; margin: 0 0 4px; color: #234f38; }
    .doc-header .doc-no { font-size: 15px; font-weight: bold; }
    .doc-header .status {
      display: inline-block; margin-top: 4px; padding: 3px 10px;
      border-radius: 12px; font-size: 11px; font-weight: bold;
    }
    .status.draft { background: #fff4d6; color: #946c00; }
    .status.selesai { background: #dff3e6; color: #1f7a45; }

    table.info { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
    table.info td { padding: 4px 6px; vertical-align: top; }
    table.info .label { width: 140px; color: #6b6b6b; }
    table.info .colon { width: 10px; }

    table.items { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    table.items th, table.items td {
      border: 1px solid #cfd6d2; padding: 6px 8px; font-size: 12px;
    }
    table.items th { background: #eef4f0; text-transform: uppercase; font-size: 10.5px; }
    table.items td.num { text-align: center; }

    .catatan-box { border: 1px dashed #cfd6d2; padding: 10px; border-radius: 6px; margin-bottom: 30px; font-size: 12px; }

    .ttd-wrap { display: flex; justify-content: space-between; margin-top: 40px; }
    .ttd-box { width: 30%; text-align: center; font-size: 12px; }
    .ttd-box .garis { margin-top: 60px; border-top: 1px solid #333; padding-top: 4px; }

    .no-print { margin-bottom: 16px; }

    @media print {
      .no-print { display: none; }
      body { padding: 0; }
    }
  </style>
</head>
<body>

  <div class="no-print">
    <button onclick="window.print()">🖨️ Cetak Dokumen</button>
    <button onclick="window.close()">Tutup</button>
  </div>

  <div class="doc-header">
    <div>
      <h1>BUKTI PENERIMAAN BARANG</h1>
      <div class="doc-no">RCV-<?= html_escape($penerimaan->no_penerimaan); ?></div>
    </div>
    <div>
      <?php $is_selesai = ($penerimaan->status === 'selesai'); ?>
      <span class="status <?= $is_selesai ? 'selesai' : 'draft'; ?>">
        <?= $is_selesai ? 'SELESAI' : 'DRAFT'; ?>
      </span>
    </div>
  </div>

  <table class="info">
    <tr>
      <td class="label">No. Purchase Order</td><td class="colon">:</td><td><?= html_escape($penerimaan->no_po); ?></td>
      <td class="label">Tanggal Terima</td><td class="colon">:</td><td><?= date('d M Y', strtotime($penerimaan->tanggal_terima)); ?></td>
    </tr>
    <tr>
      <td class="label">Supplier</td><td class="colon">:</td><td><?= html_escape($penerimaan->supplier); ?></td>
      <td class="label">No. Surat Jalan</td><td class="colon">:</td><td><?= !empty($penerimaan->surat_jalan_supplier) ? html_escape($penerimaan->surat_jalan_supplier) : '-'; ?></td>
    </tr>
    <tr>
      <td class="label">Diterima Oleh</td><td class="colon">:</td><td><?= !empty($penerimaan->dibuat_oleh) ? html_escape($penerimaan->dibuat_oleh) : '-'; ?></td>
      <td class="label"></td><td class="colon"></td><td></td>
    </tr>
  </table>

  <table class="items">
    <thead>
      <tr>
        <th style="width:4%">#</th>
        <th style="width:14%">Kode Barang</th>
        <th>Nama Barang</th>
        <th style="width:10%">Qty</th>
        <th style="width:8%">Satuan</th>
        <th style="width:10%">Kondisi</th>
        <th style="width:16%">Lokasi</th>
        <th style="width:14%">Keterangan</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($items)): $no = 1; foreach ($items as $it): ?>
        <tr>
          <td class="num"><?= $no++; ?></td>
          <td><?= !empty($it->kode_barang) ? html_escape($it->kode_barang) : '-'; ?></td>
          <td><?= html_escape($it->nama); ?></td>
          <td class="num"><?= rtrim(rtrim(number_format((float) $it->qty_diterima, 2, ',', '.'), '0'), ','); ?></td>
          <td class="num"><?= html_escape($it->satuan); ?></td>
          <td><?= html_escape(ucfirst($it->kondisi)); ?></td>
          <td><?= !empty($it->location_code) ? html_escape($it->location_code) : '-'; ?></td>
          <td><?= !empty($it->keterangan) ? html_escape($it->keterangan) : '-'; ?></td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="8" style="text-align:center;">Belum ada item barang.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <?php if (!empty($penerimaan->keterangan)): ?>
    <div class="catatan-box">
      <strong>Catatan:</strong><br>
      <?= nl2br(html_escape($penerimaan->keterangan)); ?>
    </div>
  <?php endif; ?>

  <div class="ttd-wrap">
    <div class="ttd-box">
      <div>Diserahkan Oleh</div>
      <div class="garis">Supplier</div>
    </div>
    <div class="ttd-box">
      <div>Diperiksa Oleh</div>
      <div class="garis">QC / Gudang</div>
    </div>
    <div class="ttd-box">
      <div>Diterima Oleh</div>
      <div class="garis"><?= !empty($penerimaan->dibuat_oleh) ? html_escape($penerimaan->dibuat_oleh) : ''; ?></div>
    </div>
  </div>

</body>
</html>