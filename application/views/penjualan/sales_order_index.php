<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
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
    background: var(--brand-light, #eaf4f0);
    color: var(--brand, #2f7a57);
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
    background: var(--brand-light, #f1f7f4);
    color: var(--brand-dark, #1f5a3f);
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
  .rcv-index-page table tbody tr:hover { background: #fafcfb; }
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
</style>

<div class="rcv-index-page container-fluid py-3">

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle me-1"></i><?= html_escape($this->session->flashdata('success')) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- KPI Ringkasan -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-lg-6">
      <div class="kpi-card">
        <div class="kpi-icon bg-total"><i class="bi bi-cart-check"></i></div>
        <div>
          <div class="kpi-value fw-bold fs-5"><?= isset($total_bulan_ini) ? $total_bulan_ini : 0; ?></div>
          <div class="kpi-label text-muted small">Total Sales Order</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-6">
      <div class="kpi-card">
        <div class="kpi-icon bg-nilai"><i class="bi bi-cash-stack"></i></div>
        <div>
          <div class="kpi-value fw-bold fs-5">Rp <?= isset($total_nilai) ? number_format($total_nilai, 0, ',', '.') : 0; ?></div>
          <div class="kpi-label text-muted small">Nilai Sales Order</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Toolbar: filter + tombol tambah -->
  <div class="toolbar-card">
    <?php echo form_open('sales_order', ['method' => 'get', 'class' => 'row g-2 align-items-end']); ?>
    <div class="col-md-3">
      <label class="form-label small text-muted mb-1">Cari</label>
      <div class="input-group">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input type="text" name="q" class="form-control" placeholder="No. SO / PO / Customer"
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
    <div class="col-12 col-md-2 d-grid">
      <a href="<?= site_url('sales_order/tambah'); ?>" class="btn btn-success">
        <i class="bi bi-plus-lg me-1"></i>Buat SO Baru
      </a>
    </div>
    <?php echo form_close(); ?>
  </div>

  <!-- Tabel Daftar -->
  <div class="table-wrap">
    <div class="table-responsive">
      <table class="table mb-0 align-middle">
        <thead>
          <tr>
            <th style="width:3%">#</th>
            <th style="width:14%">No. SO</th>
            <th style="width:10%">Tanggal</th>
            <th>Customer</th>
            <th style="width:12%">No. PO Customer</th>
            <th style="width:8%" class="text-center">Item</th>
            <th style="width:14%" class="text-end">Grand Total</th>
            <th style="width:10%" class="text-center">Status</th>
            <th style="width:10%" class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($sales_order_list)): ?>
            <?php $no = 1; ?>
            <?php foreach ($sales_order_list as $row): ?>
              <tr>
                <td><?= $no++; ?></td>
                <td class="fw-semibold text-success"><?= html_escape($row->no_so); ?></td>
                <td><?= date('d M Y', strtotime($row->tanggal)); ?></td>
                <td><?= html_escape($row->nama_customer); ?></td>
                <td><?= !empty($row->no_po_customer) ? html_escape($row->no_po_customer) : '<span class="text-muted">-</span>'; ?></td>
                <td class="text-center"><?= (int) $row->total_item; ?></td>
                <td class="text-end fw-semibold">Rp <?= number_format((float) $row->grand_total, 0, ',', '.'); ?></td>
                <td class="text-center">
                  <span class="badge bg-secondary"><?= ucfirst($row->status ?? 'Draft'); ?></span>
                </td>
                <td class="text-center">
                  <a href="<?= site_url('sales_order/detail/' . $row->id); ?>"
                    class="btn btn-icon-sm btn-outline-secondary" title="Lihat Detail">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="<?= site_url('sales_order/cetak/' . $row->id); ?>"
                    class="btn btn-icon-sm btn-outline-secondary" title="Cetak SO">
                    <i class="bi bi-printer"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr class="empty-row">
              <td colspan="9">
                <i class="bi bi-inbox"></i>
                Belum ada data Sales Order<?= (isset($filter_q) && $filter_q !== '') ? ' untuk pencarian "' . html_escape($filter_q) . '"' : ''; ?>.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>