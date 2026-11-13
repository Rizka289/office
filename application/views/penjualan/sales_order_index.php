<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
  .rcv-index-page .toolbar-card { background:#fff; border:1px solid #edf0f1; border-radius:12px; padding:14px 16px; box-shadow:0 2px 10px rgba(15,42,41,.04); margin-bottom:18px; }
  .rcv-index-page .kpi-card { display:flex; align-items:center; gap:12px; background:#fff; border:1px solid #edf0f1; border-radius:12px; padding:14px 16px; box-shadow:0 2px 10px rgba(15,42,41,.04); height:100%; }
  .rcv-index-page .kpi-icon.bg-terima { background:#e3effb; color:#1f5f9e; }
  .rcv-index-page .sisa-note { font-size:.72rem; font-weight:400; }
  .rcv-index-page .kpi-icon { width:44px; height:44px; border-radius:10px; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:1.15rem; }
  .rcv-index-page .kpi-icon.bg-total { background:var(--brand-light,#eaf4f0); color:var(--brand,#2f7a57); }
  .rcv-index-page .kpi-icon.bg-nilai { background:#dff3e6; color:#1f7a45; }
  .rcv-index-page .kpi-icon.bg-sisa  { background:#fff3dd; color:#b7791f; }
  .rcv-index-page .table-wrap { background:#fff; border-radius:12px; border:1px solid #edf0f1; box-shadow:0 2px 10px rgba(15,42,41,.04); overflow:hidden; }
  .rcv-index-page table thead th { background:var(--brand-light,#f1f7f4); color:var(--brand-dark,#1f5a3f); font-size:.78rem; text-transform:uppercase; letter-spacing:.03em; font-weight:700; white-space:nowrap; border:none; padding:.85rem 1rem; }
  .rcv-index-page table tbody td { padding:.8rem 1rem; vertical-align:middle; font-size:.88rem; border-color:#f1f3f2; }
  .rcv-index-page table tbody tr:hover { background:#fafcfb; }
  .rcv-index-page .btn-icon-sm { width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; border-radius:8px; font-size:.9rem; }
  .rcv-index-page .empty-row td { padding:48px 20px; text-align:center; color:#8a938d; }
  .rcv-index-page .empty-row i { font-size:2.2rem; display:block; margin-bottom:10px; color:#c3d0c9; }
</style>

<?php
// warna & label badge per status (sama dengan halaman detail SO)
$status_badge = array(
    'draft'     => array('bg-secondary',         'Draft'),
    'confirmed' => array('bg-primary',           'Dikonfirmasi'),
    'produksi'  => array('bg-warning text-dark', 'Produksi'),
    'terkirim'  => array('bg-info text-dark',    'Terkirim'),
    'selesai'   => array('bg-success',           'Selesai'),
    'batal'     => array('bg-danger',            'Batal'),
);
$rp = function ($n) {
    return 'Rp ' . number_format((float) $n, 0, ',', '.');
};
?>

<div class="rcv-index-page container-fluid py-3">

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle me-1"></i><?= html_escape($this->session->flashdata('success')) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-circle me-1"></i><?= html_escape($this->session->flashdata('error')) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Periode ringkasan: kotak KPI & tabel di bawah selalu memakai periode yang sama -->
  <?php
    $periode   = $periode ?? 'semua';
    $q_periode = function ($p) use ($filter_q) {
      $qs = array('periode' => $p);
      if (!empty($filter_q)) { $qs['q'] = $filter_q; }
      return site_url('sales_order') . '?' . http_build_query($qs);
    };
    $tombol_periode = array('bulan_ini' => 'Bulan ini', 'bulan_lalu' => 'Bulan lalu', 'tahun_ini' => 'Tahun ini', 'semua' => 'Semua');
  ?>
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
    <div class="small text-muted">
      <i class="bi bi-calendar3 me-1"></i>Ringkasan periode:
      <span class="fw-semibold text-dark"><?= html_escape($label_periode ?? 'Semua periode'); ?></span>
      <?php if (!empty($filter_q)): ?>
        · pencarian "<span class="fw-semibold text-dark"><?= html_escape($filter_q); ?></span>"
      <?php endif; ?>
    </div>
    <div class="btn-group btn-group-sm" role="group" aria-label="Pilih periode">
      <?php foreach ($tombol_periode as $kode => $teks): ?>
        <a href="<?= $q_periode($kode); ?>" class="btn <?= $periode === $kode ? 'btn-success' : 'btn-outline-secondary'; ?>"><?= $teks; ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- KPI Ringkasan
       1) SO dibuat      : menurut TANGGAL SO pada periode
       2) Uang masuk     : menurut TANGGAL BAYAR pada periode (termasuk pelunasan SO bulan-bulan lalu)
       3) Piutang        : SEMUA SO belum lunas s/d hari ini (tidak ikut periode)
       4) Sisa tagihan   : sisa dari SO yang dibuat pada periode (sama dengan tabel di bawah) -->
  <?php
    $um = isset($uang_masuk) ? $uang_masuk : array('total' => 0, 'dp' => 0, 'pelunasan' => 0, 'lainnya' => 0, 'pph' => 0);
    $pt = isset($piutang) ? $piutang : array('total' => 0, 'jml_so' => 0, 'lama' => 0, 'jml_lama' => 0);
    $boleh_piutang = in_array($this->session->userdata('role'), array('super_admin', 'atasan', 'finance'), true);
  ?>
  <div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
      <div class="kpi-card">
        <div class="kpi-icon bg-total"><i class="bi bi-cart-check"></i></div>
        <div>
          <div class="kpi-value fw-bold fs-5"><?= $rp($total_nilai ?? 0); ?></div>
          <div class="kpi-label text-muted small">Nilai SO dibuat</div>
          <div class="text-muted small"><?= (int) ($total_so ?? 0); ?> SO · menurut tgl SO</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="kpi-card">
        <div class="kpi-icon bg-nilai"><i class="bi bi-cash-stack"></i></div>
        <div>
          <div class="kpi-value fw-bold fs-5"><?= $rp($um['total']); ?></div>
          <div class="kpi-label text-muted small">Uang masuk</div>
          <div class="text-muted small" title="Dihitung dari tanggal pembayaran, termasuk pelunasan SO bulan sebelumnya">
            DP <?= $rp($um['dp']); ?> · Pelunasan <?= $rp($um['pelunasan']); ?><?= $um['lainnya'] > 0 ? ' · Lainnya ' . $rp($um['lainnya']) : ''; ?>
          </div>
          <?php if ($um['pph'] > 0): ?>
            <div class="text-muted" style="font-size:.72rem">+ bukti potong PPh <?= $rp($um['pph']); ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="kpi-card">
        <div class="kpi-icon bg-sisa"><i class="bi bi-hourglass-split"></i></div>
        <div>
          <div class="kpi-value fw-bold fs-5"><?= $rp($pt['total']); ?></div>
          <div class="kpi-label text-muted small">
            Total piutang s/d hari ini
            <?php if ($boleh_piutang): ?><a href="<?= site_url('piutang'); ?>" class="ms-1" title="Buka menu Piutang"><i class="bi bi-box-arrow-up-right"></i></a><?php endif; ?>
          </div>
          <div class="text-muted small"><?= (int) $pt['jml_so']; ?> SO belum lunas<?php if ($pt['lama'] > 0): ?> · <?= $rp($pt['lama']); ?> dari periode sebelumnya<?php endif; ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="kpi-card">
        <div class="kpi-icon bg-terima"><i class="bi bi-wallet2"></i></div>
        <div>
          <div class="kpi-value fw-bold fs-5"><?= $rp($sisa_diterima ?? 0); ?></div>
          <div class="kpi-label text-muted small">Sisa tagihan SO periode ini</div>
          <div class="text-muted small"><?= (int) ($jml_belum_lunas ?? 0); ?> SO belum lunas</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Toolbar: filter + tombol tambah -->
  <div class="toolbar-card">
    <?= form_open('sales_order', array('method' => 'get', 'class' => 'row g-2 align-items-end')); ?>
    <?php if ($periode !== 'custom'): ?>
      <!-- Pertahankan periode yang sedang dipilih saat mencari tanpa mengisi tanggal -->
      <input type="hidden" name="periode" value="<?= html_escape($periode); ?>">
    <?php endif; ?>
    <div class="col-md-3">
      <label class="form-label small text-muted mb-1"><?= translate('cari') ?></label>
      <div class="input-group">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input type="text" name="q" class="form-control" placeholder="<?= translate('p_search_so') ?>"
          value="<?= html_escape($filter_q ?? ''); ?>">
      </div>
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label small text-muted mb-1"><?= translate('filter_tanggal1') ?></label>
      <input type="date" name="dari" class="form-control" value="<?= html_escape($filter_dari ?? ''); ?>">
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label small text-muted mb-1"><?= translate('filter_tanggal2') ?></label>
      <input type="date" name="sampai" class="form-control" value="<?= html_escape($filter_sampai ?? ''); ?>">
    </div>
    <div class="col-6 col-md-1 d-grid">
      <button type="submit" class="btn btn-outline-secondary" title="Filter"><i class="bi bi-funnel"></i></button>
    </div>
    <div class="col-12 col-md-2 d-grid">
      <a href="<?= site_url('sales_order/tambah'); ?>" class="btn btn-success">
        <i class="bi bi-plus-lg me-1"></i><?= translate('add') ?>
      </a>
    </div>
    <?= form_close(); ?>
  </div>

  <!-- Tabel Daftar -->
  <div class="table-wrap">
    <div class="table-responsive">
      <table class="table mb-0 align-middle">
        <thead>
          <tr>
            <th style="width:3%"><?= translate('no') ?></th>
            <th style="width:14%"><?= translate('no_so') ?></th>
            <th style="width:10%"><?= translate('tanggal') ?></th>
            <th><?= translate('pelanggan') ?></th>
            <th style="width:12%"><?= translate('no_po_pelanggan') ?></th>
            <th style="width:6%" class="text-center"><?= translate('item') ?></th>
            <th style="width:12%" class="text-end"><?= translate('grand_total') ?></th>
            <th style="width:12%" class="text-end"><?= translate('lbl_dp') ?></th>
            <th style="width:12%" class="text-end"><?= translate('lbl_tagihan') ?></th>
            <th style="width:8%" class="text-center"><?= translate('status') ?></th>
            <th style="width:8%" class="text-center"><?= translate('aksi') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($sales_order_list)): ?>
            <?php $no = 1; ?>
            <?php foreach ($sales_order_list as $row):
              $st    = $row->status ? $row->status : 'draft';
              $badge = isset($status_badge[$st]) ? $status_badge[$st] : array('bg-secondary', ucfirst($st));
              $grand = (float) $row->grand_total;
              $sisa  = isset($row->sisa_bayar) ? (float) $row->sisa_bayar : $grand - (float) $row->total_diterima;
              $lunas = $grand > 0 && $sisa <= 0.005;
            ?>
              <tr>
                <td><?= $no++; ?></td>
                <td class="fw-semibold text-success"><?= html_escape($row->no_so); ?></td>
                <td><?= date('d M Y', strtotime($row->tanggal)); ?></td>
                <td><?= html_escape($row->nama_customer); ?></td>
                <td><?= !empty($row->no_po_customer) ? html_escape($row->no_po_customer) : '<span class="text-muted">-</span>'; ?></td>
                <td class="text-center"><?= (int) $row->total_item; ?></td>
                <td class="text-end fw-semibold"><?= $rp($row->grand_total); ?></td>
                <td class="text-end"><?= $rp($row->total_dp); ?></td>
                <td class="text-end fw-semibold">
                  <?php if ($st === 'batal'): ?>
                    <span class="text-muted">-</span>
                  <?php elseif ($lunas): ?>
                    <span class="badge bg-success">LUNAS</span>
                  <?php else: ?>
                    <?= $rp($sisa); ?>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <span class="badge <?= $badge[0]; ?>"><?= html_escape($badge[1]); ?></span>
                </td>
                <td class="text-center text-nowrap">
                  <a href="<?= site_url('sales_order/detail/' . (int) $row->id); ?>"
                    class="btn btn-icon-sm btn-outline-secondary" title="Lihat Detail">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="<?= site_url('sales_order/cetak/' . (int) $row->id); ?>" target="_blank"
                    class="btn btn-icon-sm btn-outline-secondary" title="Cetak Invoice">
                    <i class="bi bi-printer"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr class="empty-row">
              <td colspan="11">
                <i class="bi bi-inbox"></i>
                Belum ada data Sales Order<?= (!empty($filter_q)) ? ' untuk pencarian "' . html_escape($filter_q) . '"' : ''; ?> pada periode <?= html_escape($label_periode ?? '-'); ?>.
                <?php if (($periode ?? '') !== 'semua'): ?>
                  <a href="<?= $q_periode('semua'); ?>">Tampilkan semua periode</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>