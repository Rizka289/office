<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');

$tot_po = 0; $tot_bayar = 0; $tot_sisa = 0; $per_sup = array();
foreach ($po_hutang as $p) {
  $sisa = (float) $p->total - (float) $p->total_bayar;
  $tot_po += (float) $p->total; $tot_bayar += (float) $p->total_bayar; $tot_sisa += $sisa;
  $k = (string) $p->nama_supplier;
  $per_sup[$k] = ($per_sup[$k] ?? 0) + $sisa;
}
arsort($per_sup);
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3">
    <div class="judul"><i class="bi bi-cash-coin me-2"></i>Hutang Supplier</div>
    <div class="meta">PO yang sudah disetujui atasan dan belum lunas dibayar</div>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <div class="row g-3 mb-3">
    <div class="col-sm-4"><div class="card h-100"><div class="card-body"><div class="muted">Total nilai PO</div><div class="fs-5 fw-semibold"><?= $rp($tot_po) ?></div><div class="muted"><?= count($po_hutang) ?> PO</div></div></div></div>
    <div class="col-sm-4"><div class="card h-100"><div class="card-body"><div class="muted">Sudah dibayar (DP / termin)</div><div class="fs-5 fw-semibold text-success"><?= $rp($tot_bayar) ?></div></div></div></div>
    <div class="col-sm-4"><div class="card h-100"><div class="card-body"><div class="muted">Sisa hutang</div><div class="fs-5 fw-semibold text-danger"><?= $rp($tot_sisa) ?></div></div></div></div>
  </div>

  <div class="row g-3">
    <div class="col-lg-9">
      <div class="card">
        <div class="card-header"><i class="bi bi-list-ul me-2"></i>Daftar PO belum lunas</div>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>No. PO</th><th>Supplier</th><th>SO / Customer</th><th>Status PO</th><th>Bayar</th><th class="text-end">Total</th><th class="text-end">Dibayar</th><th class="text-end">Sisa</th><th></th></tr></thead>
            <tbody>
              <?php if (!$po_hutang): ?><tr><td colspan="9" class="text-center text-muted py-4">Tidak ada hutang ke supplier.</td></tr><?php endif; ?>
              <?php foreach ($po_hutang as $p): ?>
                <tr>
                  <td class="fw-semibold"><a href="<?= site_url('pemenuhan/po_detail/' . (int) $p->id) ?>"><?= html_escape($p->no_po) ?></a><div class="muted"><?= $tgl($p->tanggal) ?></div></td>
                  <td><?= html_escape($p->nama_supplier) ?></td>
                  <td><?= html_escape($p->no_so) ?><div class="muted"><?= html_escape($p->nama_customer) ?></div></td>
                  <td><?= $badge('po', $p->status) ?></td>
                  <td><?= $badge('bayar', $p->status_bayar ?? 'belum') ?></td>
                  <td class="text-end"><?= $rp($p->total) ?></td>
                  <td class="text-end"><?= $rp($p->total_bayar) ?></td>
                  <td class="text-end fw-semibold text-danger"><?= $rp((float) $p->total - (float) $p->total_bayar) ?></td>
                  <td class="text-end">
                    <?php if (!empty($izin['bayar'])): ?>
                      <a href="<?= site_url('pemenuhan/po_detail/' . (int) $p->id) ?>#pembayaran" class="btn btn-sm btn-success py-0">Bayar</a>
                    <?php else: ?>
                      <a href="<?= site_url('pemenuhan/po_detail/' . (int) $p->id) ?>" class="btn btn-sm btn-outline-secondary py-0"><i class="bi bi-eye"></i></a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-lg-3">
      <div class="card">
        <div class="card-header"><i class="bi bi-building me-2"></i>Sisa per supplier</div>
        <ul class="list-group list-group-flush">
          <?php if (!$per_sup): ?><li class="list-group-item text-muted small">-</li><?php endif; ?>
          <?php foreach ($per_sup as $nama => $sisa): ?>
            <li class="list-group-item d-flex justify-content-between small"><span><?= html_escape($nama ?: '-') ?></span><b><?= $rp($sisa) ?></b></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</div>
