<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';   // $rp
$this->load->view('pemenuhan/_style');

$bln_nama = array(1 => translate('i18n_jan'), translate('i18n_feb'), translate('i18n_mar'), translate('i18n_apr'), translate('i18n_mei'), translate('i18n_jun'), translate('i18n_jul'), translate('i18n_agu'), translate('i18n_sep'), translate('i18n_okt'), translate('i18n_nov'), translate('i18n_des'));

// Bulan terakhir yang sudah berjalan (bulan sesudahnya ditampilkan "-" untuk saldo)
$bln_akhir = ((int) $tahun === (int) date('Y')) ? (int) date('n') : ((int) $tahun > (int) date('Y') ? 0 : 12);

$jumlah = function ($keys) use ($baris) {
  $h = array_fill(1, 12, 0.0);
  foreach ($keys as $k) { foreach ($baris[$k] as $b => $n) { $h[$b] += $n; } }
  return $h;
};
$masuk   = $jumlah(array('customer', 'modal', 'pinjaman', 'pendapatan'));
$keluar  = $jumlah(array('supplier', 'biaya'));
$operasi = array();   // tanpa modal & pinjaman
$bersih  = array();
$s_awal  = array();
$s_akhir = array();
$saldo   = (float) $saldo_awal;
for ($b = 1; $b <= 12; $b++) {
  $operasi[$b] = $baris['customer'][$b] + $baris['pendapatan'][$b] - $keluar[$b];
  $bersih[$b]  = $masuk[$b] - $keluar[$b];
  $s_awal[$b]  = $saldo;
  $saldo      += $bersih[$b];
  $s_akhir[$b] = $saldo;
}
$tot = function ($arr) { return array_sum($arr); };
$saldo_akhir_tahun = $bln_akhir ? $s_akhir[$bln_akhir] : (float) $saldo_awal;

$sel = function ($n, $kelas = '') use ($rp) {
  if (!$n) { return '<span class="muted">-</span>'; }
  return '<span class="' . $kelas . '">' . $rp($n) . '</span>';
};
$sel_tanda = function ($n) use ($rp) {
  if (!$n) { return '<span class="muted">-</span>'; }
  return '<span class="' . ($n < 0 ? 'text-danger' : 'text-success') . '">' . $rp($n) . '</span>';
};

// Baris rincian: [kunci, label, link]
$rinci_masuk = array(
  array('customer',   translate('i18n_ak_pembayaran_customer'), 'piutang/penerimaan'),
  array('modal',      translate('i18n_km_jenis_modal'),         'kas_masuk?jenis=modal'),
  array('pinjaman',   translate('i18n_km_jenis_pinjaman'),      'kas_masuk?jenis=pinjaman'),
  array('pendapatan', translate('i18n_km_jenis_pendapatan'),    'kas_masuk?jenis=pendapatan'),
);
$rinci_keluar = array(
  array('supplier', translate('i18n_ak_pembayaran_supplier'), 'pemenuhan/hutang'),
  array('biaya',    translate('i18n_biaya_operasional'),      'biaya'),
);
?>
<style>
  .pm .ak th, .pm .ak td { font-size:.8rem; white-space:nowrap; }
  .pm .ak td:first-child, .pm .ak th:first-child { position:sticky; left:0; background:#fff; z-index:1; }
  .pm .ak .grup th { background:#f1f4f2 !important; text-transform:uppercase; letter-spacing:.04em; font-size:.72rem; color:#3e4a44; }
  .pm .ak .sub td { font-weight:600; border-top:2px solid #dfe5e2; }
  .pm .ak .rinci td:first-child { padding-left:1.4rem; }
  .pm .ak .saldo td { background:#f8faf9; font-weight:600; }
  .pm .ak .saldo td:first-child { background:#f8faf9; }
  @media print { .no-print, .sidebar, nav, header, .topbar { display:none !important; } .pm .card { box-shadow:none; } }
</style>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <div class="judul"><i class="bi bi-graph-up-arrow me-2"></i><?= translate('i18n_ak_judul') ?> <?= (int) $tahun ?></div>
      <div class="meta"><?= translate('i18n_ak_subjudul') ?></div>
    </div>
    <div class="no-print d-flex gap-1 align-items-center">
      <form method="get" action="<?= site_url('arus_kas') ?>" class="d-flex gap-1">
        <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
          <?php for ($t = (int) date('Y'); $t >= (int) date('Y') - 5; $t--): ?>
            <option value="<?= $t ?>" <?= $t === (int) $tahun ? 'selected' : '' ?>><?= $t ?></option>
          <?php endfor; ?>
        </select>
      </form>
      <button type="button" onclick="window.print()" class="btn btn-light btn-sm" title="Print"><i class="bi bi-printer"></i></button>
    </div>
  </div>

  <?php if ($tidak_ada): ?>
    <div class="alert alert-warning py-2 small no-print"><i class="bi bi-exclamation-triangle me-1"></i><?= translate('i18n_ak_tabel_belum_ada') ?> <code><?= html_escape(implode(', ', $tidak_ada)) ?></code></div>
  <?php endif; ?>

  <div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body">
      <div class="muted"><?= translate('i18n_ak_total_masuk') ?></div>
      <div class="fs-5 fw-semibold text-success"><?= $rp($tot($masuk)) ?></div>
      <div class="muted"><?= translate('i18n_ak_dari_modal_pinjaman') ?> <?= $rp($tot($baris['modal']) + $tot($baris['pinjaman'])) ?></div>
    </div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body">
      <div class="muted"><?= translate('i18n_ak_total_keluar') ?></div>
      <div class="fs-5 fw-semibold text-danger"><?= $rp($tot($keluar)) ?></div>
      <div class="muted"><?= translate('i18n_ak_supplier_biaya') ?></div>
    </div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body">
      <div class="muted"><?= translate('i18n_ak_operasional_bersih') ?></div>
      <div class="fs-5 fw-semibold <?= $tot($operasi) < 0 ? 'text-danger' : '' ?>"><?= $rp($tot($operasi)) ?></div>
      <div class="muted"><?= translate('i18n_ak_operasional_ket') ?></div>
    </div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body">
      <div class="muted"><?= translate('i18n_ak_saldo_akhir') ?></div>
      <div class="fs-5 fw-semibold <?= $saldo_akhir_tahun < 0 ? 'text-danger' : '' ?>"><?= $rp($saldo_akhir_tahun) ?></div>
      <div class="muted"><?= translate('i18n_ak_saldo_awal_tahun') ?> <?= $rp($saldo_awal) ?></div>
    </div></div></div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0 ak">
        <thead class="table-light">
          <tr><th></th>
            <?php foreach ($bln_nama as $n): ?><th class="text-end"><?= $n ?></th><?php endforeach; ?>
            <th class="text-end"><?= translate('total') ?></th></tr>
        </thead>
        <tbody>
          <tr class="grup"><th colspan="14"><i class="bi bi-arrow-down-left-circle text-success me-1"></i><?= translate('i18n_ak_uang_masuk') ?></th></tr>
          <?php foreach ($rinci_masuk as $r): list($k, $label, $url) = $r; ?>
            <tr class="rinci"><td><a href="<?= site_url($url) ?>" class="text-reset"><?= html_escape($label) ?></a></td>
              <?php foreach ($baris[$k] as $n): ?><td class="text-end"><?= $sel($n) ?></td><?php endforeach; ?>
              <td class="text-end fw-semibold"><?= $sel($tot($baris[$k])) ?></td></tr>
          <?php endforeach; ?>
          <tr class="sub"><td><?= translate('i18n_ak_total_masuk') ?></td>
            <?php foreach ($masuk as $n): ?><td class="text-end"><?= $sel($n, 'text-success') ?></td><?php endforeach; ?>
            <td class="text-end"><?= $sel($tot($masuk), 'text-success') ?></td></tr>

          <tr class="grup"><th colspan="14"><i class="bi bi-arrow-up-right-circle text-danger me-1"></i><?= translate('i18n_ak_uang_keluar') ?></th></tr>
          <?php foreach ($rinci_keluar as $r): list($k, $label, $url) = $r; ?>
            <tr class="rinci"><td><a href="<?= site_url($url) ?>" class="text-reset"><?= html_escape($label) ?></a></td>
              <?php foreach ($baris[$k] as $n): ?><td class="text-end"><?= $sel($n) ?></td><?php endforeach; ?>
              <td class="text-end fw-semibold"><?= $sel($tot($baris[$k])) ?></td></tr>
          <?php endforeach; ?>
          <tr class="sub"><td><?= translate('i18n_ak_total_keluar') ?></td>
            <?php foreach ($keluar as $n): ?><td class="text-end"><?= $sel($n, 'text-danger') ?></td><?php endforeach; ?>
            <td class="text-end"><?= $sel($tot($keluar), 'text-danger') ?></td></tr>

          <tr class="grup"><th colspan="14"><i class="bi bi-calculator me-1"></i><?= translate('i18n_ak_ringkasan') ?></th></tr>
          <tr class="rinci"><td title="<?= html_escape(translate('i18n_ak_operasional_ket')) ?>"><?= translate('i18n_ak_operasional_bersih') ?></td>
            <?php foreach ($operasi as $n): ?><td class="text-end"><?= $sel_tanda($n) ?></td><?php endforeach; ?>
            <td class="text-end fw-semibold"><?= $sel_tanda($tot($operasi)) ?></td></tr>
          <tr class="sub"><td><?= translate('i18n_ak_arus_bersih') ?></td>
            <?php foreach ($bersih as $n): ?><td class="text-end"><?= $sel_tanda($n) ?></td><?php endforeach; ?>
            <td class="text-end"><?= $sel_tanda($tot($bersih)) ?></td></tr>
          <tr class="saldo"><td><?= translate('i18n_ak_saldo_awal') ?></td>
            <?php for ($b = 1; $b <= 12; $b++): ?><td class="text-end"><?= $b <= $bln_akhir ? $rp($s_awal[$b]) : '<span class="muted">-</span>' ?></td><?php endfor; ?>
            <td class="text-end"><?= $rp($saldo_awal) ?></td></tr>
          <tr class="saldo"><td><?= translate('i18n_ak_saldo_akhir') ?></td>
            <?php for ($b = 1; $b <= 12; $b++): ?><td class="text-end <?= $s_akhir[$b] < 0 ? 'text-danger' : '' ?>"><?= $b <= $bln_akhir ? $rp($s_akhir[$b]) : '<span class="muted">-</span>' ?></td><?php endfor; ?>
            <td class="text-end <?= $saldo_akhir_tahun < 0 ? 'text-danger' : '' ?>"><?= $rp($saldo_akhir_tahun) ?></td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="note mt-3 small"><i class="bi bi-info-circle me-1"></i><?= translate('i18n_ak_catatan_saldo') ?></div>
</div>
