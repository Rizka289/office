<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
  .pnw-detail-page .info-card,
  .pnw-detail-page .table-wrap,
  .pnw-detail-page .summary-card {
    background: #fff;
    border: 1px solid #edf0f1;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(15, 42, 41, .04);
  }

  .pnw-detail-page .info-card { padding: 18px 20px; margin-bottom: 18px; }
  .pnw-detail-page .info-label { font-size: .74rem; text-transform: uppercase; letter-spacing: .03em; color: #8a938d; margin-bottom: 2px; }
  .pnw-detail-page .info-value { font-size: .95rem; font-weight: 600; margin-bottom: 12px; }

  .pnw-detail-page .table-wrap { overflow: hidden; margin-bottom: 18px; }
  .pnw-detail-page table thead th {
    background: var(--brand-light);
    color: var(--brand-dark);
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .03em;
    font-weight: 700;
    white-space: nowrap;
    border: none;
    padding: .85rem 1rem;
  }
  .pnw-detail-page table tbody td { padding: .8rem 1rem; vertical-align: top; font-size: .88rem; border-color: #f1f3f2; }
  .pnw-detail-page .spek-line { display: block; font-size: .8rem; color: #6c757d; }
  .pnw-detail-page .keterangan-box { font-size: .78rem; color: #8a938d; font-style: italic; margin-top: 4px; }

  .pnw-detail-page .summary-card { padding: 18px 20px; max-width: 360px; margin-left: auto; }
  .pnw-detail-page .summary-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: .9rem; }
  .pnw-detail-page .summary-row.total { border-top: 2px solid var(--brand-light); margin-top: 6px; padding-top: 12px; font-size: 1.05rem; font-weight: 700; color: var(--brand-dark); }
</style>

<?php
  $subtotal = isset($header->subtotal) ? (float) $header->subtotal : 0;
  $ppn      = isset($header->ppn_nominal) ? (float) $header->ppn_nominal : 0;
  $pph      = isset($header->pph_nominal) ? (float) $header->pph_nominal : 0;
  $grand    = isset($header->grand_total) ? (float) $header->grand_total : 0;
  $rp = function ($v) { return 'Rp ' . number_format((float) $v, 0, ',', '.'); };
?>

<div class="pnw-detail-page">

  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
      <h5 class="mb-0"><?= html_escape($header->no_surat); ?></h5>
      <div class="text-muted small">Detail Penawaran</div>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= site_url('penawaran/cetak/' . $header->id); ?>" target="_blank" class="btn btn-brand">
        <i class="bi bi-printer me-1"></i> Cetak PDF
      </a>
      <a href="<?= site_url('penawaran'); ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
      </a>
    </div>
  </div>

  <!-- Info Header -->
  <div class="info-card">
    <div class="row">
      <div class="col-md-3">
        <div class="info-label">No. Surat</div>
        <div class="info-value"><?= html_escape($header->no_surat); ?></div>
      </div>
      <div class="col-md-3">
        <div class="info-label">Tanggal</div>
        <div class="info-value"><?= date('d M Y', strtotime($header->tanggal)); ?></div>
      </div>
      <div class="col-md-3">
        <div class="info-label">Customer</div>
        <div class="info-value"><?= html_escape($header->customer ?? '-'); ?></div>
      </div>
      <div class="col-md-3">
        <div class="info-label">Dibuat Oleh</div>
        <div class="info-value"><?= html_escape($header->dibuat_oleh ?? '-'); ?></div>
      </div>
    </div>
    <?php if (!empty($header->catatan)): ?>
      <div class="mt-2">
        <div class="info-label">Catatan</div>
        <div class="text-muted small"><?= nl2br(html_escape($header->catatan)); ?></div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Tabel Item -->
  <div class="table-wrap">
    <div class="table-responsive">
      <table class="table mb-0">
        <thead>
          <tr>
            <th style="width:3%">No</th>
            <th style="width:32%">Deskripsi Item</th>
            <th style="width:12%">Ukuran (mm)</th>
            <th style="width:8%" class="text-center">Luas m²</th>
            <th style="width:6%" class="text-center">Qty</th>
            <th style="width:12%" class="text-end">Harga/Unit</th>
            <th style="width:12%" class="text-end">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($items)): $no = 1; ?>
            <?php foreach ($items as $it): ?>
              <tr>
                <td><?= $no++; ?></td>
                <td>
                  <strong><?= html_escape($it->jenis_item); ?></strong>
                  <?php if (!empty($it->warna)): ?>
                    <span class="spek-line">Warna: <?= html_escape($it->warna); ?></span>
                  <?php endif; ?>
                  <?php if (!empty($it->finishing)): ?>
                    <span class="spek-line">Finishing: <?= html_escape($it->finishing); ?></span>
                  <?php endif; ?>
                  <?php if (!empty($it->komponen_kusen) || !empty($it->komponen_daun)): ?>
                    <span class="spek-line">
                      Plate Kusen/Daun: <?= html_escape($it->komponen_kusen ?: '-'); ?> / <?= html_escape($it->komponen_daun ?: '-'); ?> mm
                    </span>
                  <?php endif; ?>
                  <?php if (!empty($it->keterangan)): ?>
                    <div class="keterangan-box"><?= nl2br(html_escape($it->keterangan)); ?></div>
                  <?php endif; ?>
                </td>
                <td><?= (int) $it->lebar_mm; ?> x <?= (int) $it->tinggi_mm; ?></td>
                <td class="text-center"><?= number_format((float) $it->luas_m2, 2); ?></td>
                <td class="text-center"><?= (int) $it->qty; ?></td>
                <td class="text-end"><?= $rp($it->harga_unit); ?></td>
                <td class="text-end"><?= $rp($it->total_harga); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">Belum ada item pada penawaran ini.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Ringkasan Nilai -->
  <div class="summary-card">
    <div class="summary-row">
      <span>Subtotal</span>
      <span><?= $rp($subtotal); ?></span>
    </div>
    <div class="summary-row">
      <span>PPN <?= isset($header->ppn_persen) ? rtrim(rtrim(number_format($header->ppn_persen, 2), '0'), '.') : '11'; ?>%</span>
      <span><?= $rp($ppn); ?></span>
    </div>
    <div class="summary-row">
      <span>PPh <?= isset($header->pph_persen) ? rtrim(rtrim(number_format($header->pph_persen, 2), '0'), '.') : '2.5'; ?>%</span>
      <span><?= $rp($pph); ?></span>
    </div>
    <div class="summary-row total">
      <span>Grand Total</span>
      <span><?= $rp($grand); ?></span>
    </div>
  </div>

</div>