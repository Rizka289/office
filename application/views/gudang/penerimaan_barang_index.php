<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>


<style>
  /* Hanya style baru yang belum ada di header.php, di-scope ke .rcv-index-page */
  .rcv-index-page .toolbar-card {
    background: #fff;
    border: 1px solid #edf0f1;
    border-radius: 12px;
    padding: 14px 16px;
    box-shadow: 0 2px 10px rgba(15, 42, 41, .04);
    margin-bottom: 18px;
  }

  .rcv-index-page .kpi-card {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .rcv-index-page .kpi-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
  }

  .rcv-index-page .kpi-icon.bg-total {
    background: var(--brand-light);
    color: var(--brand);
  }

  .rcv-index-page .kpi-icon.bg-draft {
    background: #fff4d6;
    color: #946c00;
  }

  .rcv-index-page .kpi-icon.bg-selesai {
    background: #dff3e6;
    color: #1f7a45;
  }

  .rcv-index-page .table-wrap {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #edf0f1;
    box-shadow: 0 2px 10px rgba(15, 42, 41, .04);
    overflow: hidden;
  }

  .rcv-index-page table thead th {
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

  .rcv-index-page table tbody td {
    padding: .8rem 1rem;
    vertical-align: middle;
    font-size: .88rem;
    border-color: #f1f3f2;
  }

  .rcv-index-page table tbody tr:hover {
    background: #fafcfb;
  }

  .rcv-index-page .badge-status {
    padding: 5px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: .74rem;
    white-space: nowrap;
  }

  .rcv-index-page .badge-draft {
    background: #fff4d6;
    color: #946c00;
  }

  .rcv-index-page .badge-selesai {
    background: #dff3e6;
    color: #1f7a45;
  }

  .rcv-index-page .btn-icon-sm {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: .9rem;
  }

  .rcv-index-page .empty-row td {
    padding: 48px 20px;
    text-align: center;
    color: #8a938d;
  }

  .rcv-index-page .empty-row i {
    font-size: 2.2rem;
    display: block;
    margin-bottom: 10px;
    color: #c3d0c9;
  }

  .rcv-index-page .pagination .page-link {
    color: var(--brand);
    border-color: #e5e7eb;
  }

  .rcv-index-page .pagination .page-item.active .page-link {
    background: var(--brand);
    border-color: var(--brand);
  }

  @media (max-width:767px) {
    .rcv-index-page .table-wrap {
      overflow-x: auto;
    }

    .rcv-index-page .toolbar-card .row>div {
      margin-bottom: .5rem;
    }
  }
</style>

<div class="rcv-index-page">

  <!-- KPI Ringkasan -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-lg-6">
      <div class="kpi-card">
        <div class="kpi-icon bg-total"><i class="bi bi-box-seam"></i></div>
        <div>
          <div class="kpi-value"><?= isset($total_bulan_ini) ? $total_bulan_ini : 0; ?></div>
          <div class="kpi-label"><?= translate('t_pb') ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-6">
      <div class="kpi-card">
        <div class="kpi-icon bg-selesai"><i class="bi bi-check2-circle"></i></div>
        <div>
          <div class="kpi-value"><?= isset($total_draft) ? $total_draft : 0; ?></div>
          <div class="kpi-label"><?= translate('selesai') ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Toolbar: filter + tombol tambah -->
  <div class="toolbar-card">
    <?php echo form_open('penerimaan_barang', ['method' => 'get', 'class' => 'row g-2 align-items-end']); ?>
    <div class="col-md-3">
      <label class="form-label small text-muted mb-1"><?= translate('cari') ?></label>
      <div class="input-group">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input type="text" name="q" class="form-control" placeholder="No. Penerimaan / No. PO / Supplier"
          value="<?= isset($filter_q) ? html_escape($filter_q) : ''; ?>">
      </div>
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label small text-muted mb-1"><?= translate('filter_tanggal1') ?></label>
      <input type="date" name="dari" class="form-control" value="<?= isset($filter_dari) ? $filter_dari : ''; ?>">
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label small text-muted mb-1"><?= translate('filter_tanggal2') ?></label>
      <input type="date" name="sampai" class="form-control" value="<?= isset($filter_sampai) ? $filter_sampai : ''; ?>">
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label small text-muted mb-1"><?= translate('status') ?></label>
      <select name="status" class="form-select">
        <option value="">Semua Status</option>
        <option value="draft" <?= (isset($filter_status) && $filter_status === 'draft') ? 'selected' : ''; ?>>Draft</option>
        <option value="selesai" <?= (isset($filter_status) && $filter_status === 'selesai') ? 'selected' : ''; ?>>Selesai</option>
      </select>
    </div>
    <div class="col-6 col-md-1 d-grid">
      <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-funnel"></i></button>
    </div>
    <div class="col-12 col-md-2 d-grid">
      <a href="<?= site_url('penerimaan_barang/tambah'); ?>" class="btn btn-brand">
        <i class="bi bi-plus-lg me-1"></i>Penerimaan Baru
      </a>
    </div>
    <?php echo form_close(); ?>
  </div>

  <!-- Tabel Daftar -->
  <div class="table-wrap">
    <div class="table-responsive">
      <table class="table mb-0">
        <thead>
          <tr>
            <th style="width:3%"><?= translate('no') ?></th>
            <th style="width:13%">No. Penerimaan</th>
            <th style="width:10%"><?= translate('tanggal') ?></th>
            <th style="width:12%">No. PO</th>
            <th><?= translate('list_pemasok') ?></th>
            <th style="width:14%">Diterima Oleh</th>
            <th style="width:8%" class="text-center">Jml Item</th>
            <th style="width:11%">Status</th>
            <th style="width:9%" class="text-center"><?= translate('aksi') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($penerimaan_list)): ?>
            <?php $no = isset($nomor_awal) ? $nomor_awal : 1; ?>
            <?php foreach ($penerimaan_list as $row): ?>
              <?php
              // Ambil nilai status_penerimaan, jika NULL/kosong gunakan 'draft' sebagai fallback
              $raw_status = !empty($row->status_penerimaan) ? $row->status_penerimaan : 'draft';
              $status     = strtolower(trim($raw_status));

              $status_class = ($status === 'selesai') ? 'badge-selesai' : 'badge-draft';
              $status_label = ($status === 'selesai') ? 'Selesai' : 'Draf';
              ?>
              <tr>
                <td><?= $no++; ?></td>
                <td class="fw-semibold"><?= html_escape($row->no_penerimaan); ?></td>
                <td><?= date('d M Y', strtotime($row->tanggal_terima)); ?></td>
                <td><?= html_escape($row->no_po); ?></td>
                <td><?= html_escape($row->supplier); ?></td>
                <td><?= html_escape($row->diterima_oleh); ?></td>
                <td class="text-center"><?= (int) $row->total_item; ?></td>
                <td><span class="badge-status <?= $status_class; ?>"><?= $status_label; ?></span></td>
                <td class="text-center">
                  <a href="<?= site_url('penerimaan_barang/detail/' . $row->id); ?>"
                    class="btn btn-icon-sm btn-outline-secondary" title="Lihat Detail">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="<?= site_url('penerimaan_barang/cetak/' . $row->id); ?>"
                    class="btn btn-icon-sm btn-outline-secondary" title="Cetak">
                    <i class="bi bi-printer"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr class="empty-row">
              <td colspan="9">
                <i class="bi bi-inbox"></i>
                Belum ada data penerimaan barang<?= (isset($filter_q) && $filter_q !== '') ? ' untuk pencarian "' . html_escape($filter_q) . '"' : ''; ?>.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pagination -->
  <?php if (!empty($pagination)): ?>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
      <div class="text-muted small">
        Menampilkan <?= isset($jumlah_ditampilkan) ? $jumlah_ditampilkan : 0; ?> dari <?= isset($total_data) ? $total_data : 0; ?> data
      </div>
      <nav>
        <?= $pagination; ?>
      </nav>
    </div>
  <?php endif; ?>

</div>