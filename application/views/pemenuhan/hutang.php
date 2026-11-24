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
    <div class="judul"><i class="bi bi-cash-coin me-2"></i><?= translate('i18n_hutang_supplier') ?></div>
    <div class="meta"><?= translate('i18n_po_yang_sudah_disetujui_atasan_dan_belum') ?></div>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <div class="row g-3 mb-3">
    <div class="col-sm-4"><div class="card h-100"><div class="card-body"><div class="muted"><?= translate('i18n_total_nilai_po') ?></div><div class="fs-5 fw-semibold"><?= $rp($tot_po) ?></div><div class="muted"><?= count($po_hutang) ?> <?= translate('i18n_po') ?></div></div></div></div>
    <div class="col-sm-4"><div class="card h-100"><div class="card-body"><div class="muted"><?= translate('i18n_sudah_dibayar_dp_termin') ?></div><div class="fs-5 fw-semibold text-success"><?= $rp($tot_bayar) ?></div></div></div></div>
    <div class="col-sm-4"><div class="card h-100"><div class="card-body"><div class="muted"><?= translate('i18n_sisa_hutang') ?></div><div class="fs-5 fw-semibold text-danger"><?= $rp($tot_sisa) ?></div></div></div></div>
  </div>

  <div class="row g-3">
    <div class="col-lg-9">
      <div class="card">
        <div class="card-header"><i class="bi bi-list-ul me-2"></i><?= translate('i18n_daftar_po_belum_lunas') ?></div>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light"><tr><th><?= translate('i18n_no_po_2') ?></th><th><?= translate('i18n_supplier') ?></th><th><?= translate('i18n_so_customer') ?></th><th><?= translate('i18n_status_po') ?></th><th><?= translate('i18n_bayar') ?></th><th class="text-end"><?= translate('total') ?></th><th class="text-end"><?= translate('i18n_dibayar') ?></th><th class="text-end"><?= translate('i18n_sisa') ?></th><th></th></tr></thead>
            <tbody>
              <?php if (!$po_hutang): ?><tr><td colspan="9" class="text-center text-muted py-4"><?= translate('i18n_tidak_ada_hutang_ke_supplier') ?></td></tr><?php endif; ?>
              <?php foreach ($po_hutang as $p): ?>
                <tr>
                  <td class="fw-semibold"><a href="<?= site_url('pemenuhan/po_detail/' . (int) $p->id) ?>"><?= html_escape($p->no_po) ?></a><div class="muted"><?= $tgl($p->tanggal) ?></div></td>
                  <td><?= html_escape($p->nama_supplier) ?></td>
                  <td><?php if ($p->id_so): ?><?= html_escape($p->no_so) ?><div class="muted"><?= html_escape((string) $p->nama_customer) ?></div><?php else: ?><span class="badge text-bg-secondary fw-normal">Bahan / Stok</span><?php endif; ?></td>
                  <td><?= $badge('po', $p->status) ?></td>
                  <td><?= $badge('bayar', $p->status_bayar ?? 'belum') ?></td>
                  <td class="text-end"><?= $rp($p->total) ?></td>
                  <td class="text-end"><?= $rp($p->total_bayar) ?></td>
                  <td class="text-end fw-semibold text-danger"><?= $rp((float) $p->total - (float) $p->total_bayar) ?></td>
                  <td class="text-end">
                    <?php if (!empty($izin['bayar'])): ?>
                      <a href="<?= site_url('pemenuhan/po_detail/' . (int) $p->id) ?>#pembayaran" class="btn btn-sm btn-success py-0"><?= translate('i18n_bayar') ?></a>
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
        <div class="card-header"><i class="bi bi-building me-2"></i><?= translate('i18n_sisa_per_supplier') ?></div>
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
