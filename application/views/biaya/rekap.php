<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';   // $rp
$this->load->view('pemenuhan/_style');

$bln_nama = array(1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des');
$keluar = array_fill(1, 12, 0);
foreach ($rekap as $r) { foreach ($r['bulan'] as $b => $n) { $keluar[$b] += $n; } }
$tot_keluar = array_sum($keluar);
$tot_masuk  = array_sum($kas_masuk);
$sel = function ($n) use ($rp) { return $n ? $rp($n) : '<span class="muted">-</span>'; };
$link_bulan = function ($b) use ($tahun) {
  $dari = sprintf('%04d-%02d-01', $tahun, $b);
  return site_url('biaya') . '?' . http_build_query(array('dari' => $dari, 'sampai' => date('Y-m-t', strtotime($dari))));
};
?>
<style>
  .pm .rekap th, .pm .rekap td { font-size:.8rem; white-space:nowrap; }
  .pm .rekap td:first-child, .pm .rekap th:first-child { position:sticky; left:0; background:#fff; z-index:1; }
  .pm .rekap thead th:first-child, .pm .rekap tfoot th:first-child { background:#f8f9fa; }
  @media print { .no-print, .sidebar, nav, header { display:none !important; } .pm .card { box-shadow:none; } }
</style>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <div class="judul"><i class="bi bi-bar-chart me-2"></i>Rekap Biaya Operasional <?= (int) $tahun ?></div>
      <div class="meta">Pengeluaran per kategori per bulan, dibandingkan dengan uang masuk dari customer</div>
    </div>
    <div class="no-print d-flex gap-1 align-items-center">
      <form method="get" action="<?= site_url('biaya/rekap') ?>" class="d-flex gap-1">
        <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
          <?php for ($t = (int) date('Y'); $t >= (int) date('Y') - 5; $t--): ?>
            <option value="<?= $t ?>" <?= $t === (int) $tahun ? 'selected' : '' ?>><?= $t ?></option>
          <?php endfor; ?>
        </select>
      </form>
      <button type="button" onclick="window.print()" class="btn btn-light btn-sm"><i class="bi bi-printer"></i></button>
      <a href="<?= site_url('biaya') ?>" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-sm-4"><div class="card h-100"><div class="card-body"><div class="muted">Uang masuk (pembayaran customer)</div><div class="fs-5 fw-semibold text-success"><?= $rp($tot_masuk) ?></div></div></div></div>
    <div class="col-sm-4"><div class="card h-100"><div class="card-body"><div class="muted">Biaya operasional</div><div class="fs-5 fw-semibold text-danger"><?= $rp($tot_keluar) ?></div></div></div></div>
    <div class="col-sm-4"><div class="card h-100"><div class="card-body"><div class="muted">Selisih</div><div class="fs-5 fw-semibold <?= $tot_masuk - $tot_keluar < 0 ? 'text-danger' : '' ?>"><?= $rp($tot_masuk - $tot_keluar) ?></div><div class="muted">belum termasuk pembayaran hutang supplier</div></div></div></div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0 rekap">
        <thead class="table-light">
          <tr><th>Kategori</th>
            <?php foreach ($bln_nama as $b => $n): ?><th class="text-end"><a href="<?= $link_bulan($b) ?>" class="text-reset"><?= $n ?></a></th><?php endforeach; ?>
            <th class="text-end">Total</th></tr>
        </thead>
        <tbody>
          <?php if (!$rekap): ?><tr><td colspan="14" class="text-center text-muted py-4">Belum ada pengeluaran di tahun ini.</td></tr><?php endif; ?>
          <?php foreach ($rekap as $r): ?>
            <tr><td><?= html_escape($r['nama']) ?></td>
              <?php foreach ($r['bulan'] as $n): ?><td class="text-end"><?= $sel($n) ?></td><?php endforeach; ?>
              <td class="text-end fw-semibold"><?= $rp($r['total']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light">
          <tr><th>Total biaya</th><?php foreach ($keluar as $n): ?><th class="text-end text-danger"><?= $sel($n) ?></th><?php endforeach; ?><th class="text-end text-danger"><?= $rp($tot_keluar) ?></th></tr>
          <tr><th>Uang masuk</th><?php foreach ($kas_masuk as $n): ?><th class="text-end text-success"><?= $sel($n) ?></th><?php endforeach; ?><th class="text-end text-success"><?= $rp($tot_masuk) ?></th></tr>
          <tr><th>Selisih</th><?php foreach ($keluar as $b => $n): $s = $kas_masuk[$b] - $n; ?><th class="text-end <?= $s < 0 ? 'text-danger' : '' ?>"><?= $s ? $rp($s) : '<span class="muted">-</span>' ?></th><?php endforeach; ?><th class="text-end"><?= $rp($tot_masuk - $tot_keluar) ?></th></tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
