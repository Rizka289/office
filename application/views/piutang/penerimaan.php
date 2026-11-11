<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');

$label_jenis = array('dp' => array('warning', 'DP'), 'pelunasan' => array('success', 'Pelunasan'),
  'lainnya' => array('info', 'Lainnya'), 'pph' => array('secondary', 'PPh (data lama)'));
$total = 0; $per_jenis = array();
foreach ($list as $b) {
  $total += (float) $b->nominal;
  $per_jenis[$b->jenis] = ($per_jenis[$b->jenis] ?? 0) + (float) $b->nominal;
}
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <div class="judul"><i class="bi bi-clock-history me-2"></i>Penerimaan Pembayaran Customer</div>
      <div class="meta"><?= $tgl($dari) ?> s/d <?= $tgl($sampai) ?></div>
    </div>
    <a href="<?= site_url('piutang') ?>" class="btn btn-light btn-sm"><i class="bi bi-receipt me-1"></i>Daftar piutang</a>
  </div>

  <form method="get" action="<?= site_url('piutang/penerimaan') ?>" class="card mb-3">
    <div class="card-body row g-2 align-items-end">
      <div class="col-sm-3"><label class="form-label small mb-1">Dari</label><input type="date" name="dari" value="<?= html_escape($dari) ?>" class="form-control form-control-sm"></div>
      <div class="col-sm-3"><label class="form-label small mb-1">Sampai</label><input type="date" name="sampai" value="<?= html_escape($sampai) ?>" class="form-control form-control-sm"></div>
      <div class="col-sm-4"><label class="form-label small mb-1">Cari</label><input type="search" name="q" value="<?= html_escape($q) ?>" class="form-control form-control-sm" placeholder="No. SO / customer / no. referensi"></div>
      <div class="col-sm-2 d-grid"><button class="btn btn-sm btn-success"><i class="bi bi-funnel me-1"></i>Tampilkan</button></div>
    </div>
  </form>

  <div class="row g-3 mb-3">
    <div class="col-sm-4"><div class="card h-100"><div class="card-body"><div class="muted">Total uang masuk</div><div class="fs-5 fw-semibold text-success"><?= $rp($total) ?></div><div class="muted"><?= count($list) ?> transaksi</div></div></div></div>
    <?php foreach ($per_jenis as $j => $n): ?>
      <div class="col-sm-4 col-lg-2"><div class="card h-100"><div class="card-body"><div class="muted"><?= html_escape($label_jenis[$j][1] ?? ucfirst($j)) ?></div><div class="fw-semibold"><?= $rp($n) ?></div></div></div></div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Tanggal</th><th>No. SO</th><th>Customer</th><th>Jenis</th><th>Metode / Ref.</th><th>Catatan</th><th class="text-end">Nominal</th></tr></thead>
        <tbody>
          <?php if (!$list): ?><tr><td colspan="7" class="text-center text-muted py-4">Tidak ada pembayaran pada periode ini.</td></tr><?php endif; ?>
          <?php foreach ($list as $b): $x = $label_jenis[$b->jenis] ?? array('light', ucfirst($b->jenis)); ?>
            <tr>
              <td class="text-nowrap"><?= $tgl($b->tanggal) ?></td>
              <td class="fw-semibold"><a href="<?= site_url('sales_order/detail/' . (int) $b->id_so) ?>"><?= html_escape($b->no_so) ?></a></td>
              <td><a href="<?= site_url('piutang/customer/' . (int) $b->id_customer) ?>" class="text-reset"><?= html_escape($b->nama_customer ?: '-') ?></a></td>
              <td><span class="badge text-bg-<?= $x[0] ?> fw-normal"><?= html_escape($x[1]) ?></span></td>
              <td><?= html_escape($b->metode ?: '-') ?><?php if (!empty($b->no_referensi)): ?><div class="muted"><?= html_escape($b->no_referensi) ?></div><?php endif; ?></td>
              <td class="muted"><?= html_escape($b->catatan ?? '') ?></td>
              <td class="text-end fw-semibold"><?= $rp($b->nominal) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
