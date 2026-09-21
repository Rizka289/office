<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<!--
  Fragment Detail Penerimaan Barang.
  Dipakai bersama header.php + footer.php (JANGAN tambahkan <!DOCTYPE>/<html>/<head>/<body> di sini).
-->

<style>
  .rcv-detail-page .page-header {
    background: linear-gradient(135deg, var(--brand), var(--brand-dark));
    color: #fff; padding: 22px 28px; border-radius: 14px; margin-bottom: 22px;
    box-shadow: 0 6px 18px rgba(0, 0, 0, .12);
  }
  .rcv-detail-page .page-header h4 { margin: 0; font-weight: 600; }
  .rcv-detail-page .page-header small { opacity: .85; }

  .rcv-detail-page .card-modern {
    background: #fff; border: none; border-radius: 14px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06); margin-bottom: 22px;
  }
  .rcv-detail-page .card-modern .card-header {
    background: #fff; border-bottom: 1px solid #eef1ef;
    border-radius: 14px 14px 0 0; font-weight: 600;
    color: var(--brand-dark); padding: 16px 20px;
  }
  .rcv-detail-page .card-modern .card-body { padding: 20px; }

  .rcv-detail-page .info-label { font-size: .78rem; text-transform: uppercase; letter-spacing: .03em; color: #8a938d; margin-bottom: 2px; }
  .rcv-detail-page .info-value { font-weight: 600; color: #2a2a2a; margin-bottom: 14px; }

  .rcv-detail-page table thead th {
    background: var(--brand-light); color: var(--brand-dark);
    font-size: .78rem; text-transform: uppercase; letter-spacing: .03em; font-weight: 700;
    white-space: nowrap; border: none; padding: .85rem 1rem;
  }
  .rcv-detail-page table tbody td { padding: .8rem 1rem; vertical-align: middle; font-size: .88rem; border-color: #f1f3f2; }

  .rcv-detail-page .badge-status { padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: .78rem; }
  .rcv-detail-page .badge-draft { background: #fff4d6; color: #946c00; }
  .rcv-detail-page .badge-selesai { background: #dff3e6; color: #1f7a45; }
</style>

<?php
  $status_class = ($penerimaan->status === 'selesai') ? 'badge-selesai' : 'badge-draft';
  $status_label = ($penerimaan->status === 'selesai') ? 'Selesai' : 'Draft';
?>

<div class="rcv-detail-page">

  <div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <h4><i class="bi bi-box-seam me-2"></i>Detail Penerimaan Barang</h4>
      <small>RCV-<?= html_escape($penerimaan->no_penerimaan); ?></small>
    </div>
    <div class="text-end">
      <span class="badge-status <?= $status_class; ?>"><?= $status_label; ?></span>
    </div>
  </div>

  <div class="card-modern">
    <div class="card-header"><i class="bi bi-info-circle me-2"></i>Informasi Penerimaan</div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3">
          <div class="info-label">No. Penerimaan</div>
          <div class="info-value">RCV-<?= html_escape($penerimaan->no_penerimaan); ?></div>
        </div>
        <div class="col-md-3">
          <div class="info-label">No. PO</div>
          <div class="info-value"><?= html_escape($penerimaan->no_po); ?></div>
        </div>
        <div class="col-md-3">
          <div class="info-label">Supplier</div>
          <div class="info-value"><?= html_escape($penerimaan->supplier); ?></div>
        </div>
        <div class="col-md-3">
          <div class="info-label">Tanggal Terima</div>
          <div class="info-value"><?= date('d M Y', strtotime($penerimaan->tanggal_terima)); ?></div>
        </div>

        <div class="col-md-4">
          <!-- FIX: kolom DB bernama surat_jalan_supplier, bukan no_surat_jalan -->
          <div class="info-label">No. Surat Jalan</div>
          <div class="info-value"><?= !empty($penerimaan->surat_jalan_supplier) ? html_escape($penerimaan->surat_jalan_supplier) : '-'; ?></div>
        </div>
        <div class="col-md-4">
          <!-- FIX: tidak ada kolom diterima_oleh terpisah; nama diambil dari id_user (join users) -->
          <div class="info-label">Diterima Oleh</div>
          <div class="info-value"><?= !empty($penerimaan->dibuat_oleh) ? html_escape($penerimaan->dibuat_oleh) : '-'; ?></div>
        </div>
        <div class="col-md-4">
          <div class="info-label">Status</div>
          <div class="info-value"><span class="badge-status <?= $status_class; ?>"><?= $status_label; ?></span></div>
        </div>

        <div class="col-12">
          <div class="info-label">Catatan</div>
          <div class="info-value" style="font-weight:400;"><?= !empty($penerimaan->keterangan) ? nl2br(html_escape($penerimaan->keterangan)) : '-'; ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="card-modern">
    <div class="card-header"><i class="bi bi-list-check me-2"></i>Detail Barang Diterima</div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table mb-0">
          <thead>
            <tr>
              <th style="width:3%">#</th>
              <th style="width:12%">Kode Barang</th>
              <th>Nama Barang</th>
              <th style="width:10%">Qty Diterima</th>
              <th style="width:8%">Satuan</th>
              <th style="width:11%">Kondisi</th>
              <th style="width:16%">Lokasi</th>
              <th>Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($items)): ?>
              <?php $no = 1; foreach ($items as $it): ?>
                <tr>
                  <td><?= $no++; ?></td>
                  <td><?= !empty($it->kode_barang) ? html_escape($it->kode_barang) : '-'; ?></td>
                  <td>
                    <?= html_escape($it->nama); ?>
                    <?php if (empty($it->id_po_detail)): ?>
                      <span class="badge bg-light text-dark border">Diluar PO</span>
                    <?php endif; ?>
                  </td>
                  <td><?= rtrim(rtrim(number_format((float) $it->qty_diterima, 2, ',', '.'), '0'), ','); ?></td>
                  <td><?= html_escape($it->satuan); ?></td>
                  <td><?= html_escape(ucfirst($it->kondisi)); ?></td>
                  <td><?= !empty($it->location_code) ? html_escape($it->location_code . ' - ' . $it->zone_name) : '-'; ?></td>
                  <td><?= !empty($it->keterangan) ? html_escape($it->keterangan) : '-'; ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center text-muted py-4">Belum ada item barang.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2 mb-4">
    <a href="<?= site_url('penerimaan_barang'); ?>" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar
    </a>
    <a href="<?= site_url('penerimaan_barang/cetak/' . $penerimaan->id); ?>" class="btn btn-brand" target="_blank">
      <i class="bi bi-printer me-1"></i>Cetak
    </a>
  </div>

</div>