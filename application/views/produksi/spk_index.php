<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
  .spk-index-page .toolbar-card {
    background: #fff; border: 1px solid #edf0f1; border-radius: 12px; padding: 14px 16px;
    box-shadow: 0 2px 10px rgba(15, 42, 41, .04); margin-bottom: 18px;
  }
  .spk-index-page .table-wrap {
    background: #fff; border-radius: 12px; border: 1px solid #edf0f1;
    box-shadow: 0 2px 10px rgba(15, 42, 41, .04); overflow: hidden;
  }
  .spk-index-page table thead th {
    background: #f1f7f4; color: #1f5a3f; font-size: .78rem; text-transform: uppercase;
    letter-spacing: .03em; font-weight: 700; white-space: nowrap; border: none; padding: .85rem 1rem;
  }
  .spk-index-page table tbody td { padding: .8rem 1rem; vertical-align: middle; font-size: .88rem; border-color: #f1f3f2; }
  .spk-index-page table tbody tr:hover { background: #fafcfb; }
  .spk-index-page .btn-icon-sm {
    width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;
    border-radius: 8px; font-size: .9rem;
  }
  .spk-index-page .empty-row td { padding: 48px 20px; text-align: center; color: #8a938d; }
  .spk-index-page .empty-row i { font-size: 2.2rem; display: block; margin-bottom: 10px; color: #c3d0c9; }
</style>

<div class="spk-index-page container-fluid py-3">

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle me-1"></i><?= html_escape($this->session->flashdata('success')) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle me-1"></i><?= html_escape($this->session->flashdata('error')) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Toolbar: Filter & Tombol Tambah SPK dari SO Confirmed -->
  <div class="toolbar-card">
    <?php echo form_open('spk', ['method' => 'get', 'class' => 'row g-2 align-items-end']); ?>
    <div class="col-md-4">
      <label class="form-label small text-muted mb-1">Cari</label>
      <div class="input-group">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input type="text" name="q" class="form-control" placeholder="No. SPK / No. SO / Customer"
          value="<?= isset($filter_q) ? html_escape($filter_q) : ''; ?>">
      </div>
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label small text-muted mb-1">Dari Tanggal</label>
      <input type="date" name="dari" class="form-control" value="<?= isset($filter_dari) ? $filter_dari : ''; ?>">
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label small text-muted mb-1">Sampai Tanggal</label>
      <input type="date" name="sampai" class="form-control" value="<?= isset($filter_sampai) ? $filter_sampai : ''; ?>">
    </div>
    <div class="col-6 col-md-1 d-grid">
      <button type="submit" class="btn btn-outline-secondary" title="Filter"><i class="bi bi-funnel"></i></button>
    </div>
    <div class="col-6 col-md-1 d-grid">
      <a href="<?= site_url('spk/tambah'); ?>" class="btn btn-success" title="Buat SPK Baru dari SO">
        <i class="bi bi-plus-lg"></i>
      </a>
    </div>
    <?php echo form_close(); ?>
  </div>

  <!-- Tabel Daftar SPK -->
  <div class="table-wrap">
    <div class="table-responsive">
      <table class="table mb-0 align-middle">
        <thead>
          <tr>
            <th style="width:3%">#</th>
            <th style="width:15%">No. SPK</th>
            <th style="width:14%">No. Sales Order</th>
            <th style="width:10%">Tanggal SPK</th>
            <th>Customer</th>
            <th style="width:12%">Target Selesai</th>
            <th style="width:10%" class="text-center">Status</th>
            <th style="width:10%" class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($spk_list)): ?>
            <?php $no = 1; ?>
            <?php foreach ($spk_list as $row): ?>
              <?php 
                $badge_bg = 'secondary';
                if ($row->status === 'proses') $badge_bg = 'warning text-dark';
                if ($row->status === 'selesai') $badge_bg = 'success';
                if ($row->status === 'batal') $badge_bg = 'danger';
              ?>
              <tr>
                <td><?= $no++; ?></td>
                <td class="fw-semibold text-success"><?= html_escape($row->no_spk); ?></td>
                <td><a href="<?= site_url('sales_order/detail/' . $row->id_so); ?>"><?= html_escape($row->no_so); ?></a></td>
                <td><?= date('d M Y', strtotime($row->tanggal_spk)); ?></td>
                <td><?= html_escape($row->nama_customer); ?></td>
                <td><?= !empty($row->target_selesai) ? date('d M Y', strtotime($row->target_selesai)) : '<span class="text-muted">-</span>'; ?></td>
                <td class="text-center">
                  <span class="badge bg-<?= $badge_bg ?>"><?= ucfirst($row->status); ?></span>
                </td>
                <td class="text-center">
                  <a href="<?= site_url('spk/detail/' . $row->id); ?>"
                    class="btn btn-icon-sm btn-outline-secondary" title="Lihat Detail SPK">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="<?= site_url('spk/cetak/' . $row->id); ?>"
                    class="btn btn-icon-sm btn-outline-secondary" title="Cetak SPK">
                    <i class="bi bi-printer"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr class="empty-row">
              <td colspan="8">
                <i class="bi bi-inbox"></i>
                Belum ada data Surat Perintah Kerja (SPK).
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>