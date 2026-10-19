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

  .rcv-index-page .kpi-icon.bg-belum {
    background: #fff3cd;
    color: #a06a00;
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

  <!-- KPI Ringkasan (bulan ini) -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
      <div class="kpi-card">
        <div class="kpi-icon bg-total"><i class="bi bi-file-earmark-text"></i></div>
        <div>
          <div class="kpi-value"><?= (int) ($total_semua ?? 0); ?></div>
          <div class="kpi-label">Penawaran Bulan Ini</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="kpi-card">
        <div class="kpi-icon bg-nilai"><i class="bi bi-check-circle"></i></div>
        <div>
          <div class="kpi-value"><?= (int) ($total_approved ?? 0); ?></div>
          <div class="kpi-label">Sudah Approved</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="kpi-card">
        <div class="kpi-icon bg-belum"><i class="bi bi-hourglass-split"></i></div>
        <div>
          <div class="kpi-value"><?= (int) ($total_belum ?? 0); ?></div>
          <div class="kpi-label">Belum Approved</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="kpi-card">
        <div class="kpi-icon bg-nilai"><i class="bi bi-cash-stack"></i></div>
        <div>
          <div class="kpi-value">Rp <?= number_format((float) ($total_nilai ?? 0), 0, ',', '.'); ?></div>
          <div class="kpi-label">Nilai Approved Bulan Ini</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Rekap penawaran per bulan -->
  <?php
    $nama_bulan = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni',
                   '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
  ?>
  <div class="table-wrap mb-3">
    <div class="px-3 py-2 fw-semibold border-bottom"><i class="bi bi-calendar3 me-1"></i>Rekap Penawaran per Bulan</div>
    <div class="table-responsive">
      <table class="table mb-0">
        <thead>
          <tr>
            <th>Bulan</th>
            <th class="text-center">Total Surat</th>
            <th class="text-center">Approved</th>
            <th class="text-center">Belum Approved</th>
            <th class="text-end">Nilai Approved</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($rekap_bulanan)): ?>
            <?php foreach ($rekap_bulanan as $r):
              list($thn, $bln) = explode('-', $r->periode); ?>
              <tr>
                <td class="fw-semibold"><?= $nama_bulan[$bln] . ' ' . $thn; ?></td>
                <td class="text-center"><?= (int) $r->total; ?></td>
                <td class="text-center"><?= (int) $r->approved; ?></td>
                <td class="text-center"><?= (int) $r->belum; ?></td>
                <td class="text-end">Rp <?= number_format((float) $r->nilai, 0, ',', '.'); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr class="empty-row"><td colspan="5"><i class="bi bi-inbox"></i>Belum ada data penawaran.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
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
            <th style="width:10%" class="text-center"><?= translate('status') ?></th>
            <th style="width:12%" class="text-center"><?= translate('aksi') ?></th>
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
                  <?php if (($row->status ?? 'draft') === 'approved'): ?>
                    <span class="badge bg-success"><i class="bi bi-lock-fill me-1"></i>Approved</span>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark">Belum Approve</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <a href="<?= site_url('penawaran/detail/' . $row->id); ?>"
                    class="btn btn-icon-sm btn-outline-secondary" title="Lihat Detail">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="<?= site_url('penawaran/cetak/' . $row->id); ?>"
                    class="btn btn-icon-sm btn-outline-secondary" title="Cetak">
                    <i class="bi bi-printer"></i>
                  </a>
                  <?php if (($row->status ?? 'draft') !== 'approved'): ?>
                    <a href="<?= site_url('penawaran/edit/' . $row->id); ?>"
                      class="btn btn-icon-sm btn-outline-warning" title="Edit">
                      <i class="bi bi-pencil-square"></i>
                    </a>
                    <?= form_open('penawaran/delete/' . (int) $row->id, [
                          'class'    => 'd-inline',
                          'onsubmit' => "return confirm('Hapus penawaran " . html_escape($row->no_surat) . "? Data yang dihapus tidak bisa dikembalikan.');"
                        ]); ?>
                      <button type="submit" class="btn btn-icon-sm btn-outline-danger" title="Hapus">
                        <i class="bi bi-trash"></i>
                      </button>
                    <?= form_close(); ?>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr class="empty-row">
              <td colspan="8">
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