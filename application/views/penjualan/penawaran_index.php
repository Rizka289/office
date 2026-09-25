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

  .rcv-index-page .kpi-icon.bg-nilai {
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
        <div class="kpi-icon bg-total"><i class="bi bi-file-earmark-text"></i></div>
        <div>
          <div class="kpi-value"><?= isset($total_bulan_ini) ? $total_bulan_ini : 0; ?></div>
          <div class="kpi-label"><?= translate('total_penawaran') ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-6">
      <div class="kpi-card">
        <div class="kpi-icon bg-nilai"><i class="bi bi-cash-stack"></i></div>
        <div>
          <div class="kpi-value">Rp <?= isset($total_nilai) ? number_format($total_nilai, 0, ',', '.') : 0; ?></div>
          <div class="kpi-label"><?= translate('total_nilai') ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Toolbar: filter + tombol tambah -->
  <div class="toolbar-card">
    <?php echo form_open('penawaran', ['method' => 'get', 'class' => 'row g-2 align-items-end']); ?>
    <div class="col-md-3">
      <label class="form-label small text-muted mb-1"><?= translate('cari') ?></label>
      <div class="input-group">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input type="text" name="q" class="form-control" placeholder="No. Penawaran / Customer"
          value="<?= isset($filter_q) ? html_escape($filter_q) : ''; ?>">
      </div>
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label small text-muted mb-1"><?= translate('filter_tanggal1') ?></label>
      <input type="date" name="dari" class="form-control" value="<?= isset($filter_dari) ? $filter_dari : ''; ?>">
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label small text-muted mb-1"><?= translate('filter_tanggal2') ?></label>
      <input type="date" name="sampai" class="form-control" value="<?= isset($filter_sampai) ? $filter_sampai : ''; ?>">
    </div>
    <div class="col-6 col-md-1 d-grid">
      <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-funnel"></i></button>
    </div>
    <div class="col-12 col-md-2 d-grid">
      <a href="<?= site_url('penawaran/tambah'); ?>" class="btn btn-brand">
        <i class="bi bi-plus-lg me-1"></i><?= translate('add') ?>
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
            <th style="width:14%"><?= translate('no_penawaran') ?></th>
            <th style="width:10%"><?= translate('tanggal') ?></th>
            <th><?= translate('pelanggan') ?></th>
            <th style="width:8%" class="text-center"><?= translate('item') ?></th>
            <th style="width:14%" class="text-end"><?= translate('total') ?></th>
            <th style="width:9%" class="text-center"><?= translate('aksi') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($penawaran_list)): ?>
            <?php $no = isset($nomor_awal) ? $nomor_awal : 1; ?>
            <?php foreach ($penawaran_list as $row): ?>
              <tr>
                <td><?= $no++; ?></td>
                <td class="fw-semibold"><?= html_escape($row->no_surat); ?></td>
                <td><?= date('d M Y', strtotime($row->tanggal)); ?></td>
                <td><?= html_escape($row->customer); ?></td>
                <td class="text-center"><?= (int) $row->total_item; ?></td>
                <td class="text-end">Rp <?= number_format((float) $row->grand_total, 0, ',', '.'); ?></td>
                <td class="text-center">
                  <a href="<?= site_url('penawaran/detail/' . $row->id); ?>"
                    class="btn btn-icon-sm btn-outline-secondary" title="Lihat Detail">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="<?= site_url('penawaran/cetak/' . $row->id); ?>"
                    class="btn btn-icon-sm btn-outline-secondary" title="Cetak">
                    <i class="bi bi-printer"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr class="empty-row">
              <td colspan="7">
                <i class="bi bi-inbox"></i>
                Belum ada data penawaran<?= (isset($filter_q) && $filter_q !== '') ? ' untuk pencarian "' . html_escape($filter_q) . '"' : ''; ?>.
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