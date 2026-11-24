<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');

$tahap_label = array(
  'aktif'       => array('primary', translate('i18n_ksy_tab_aktif')),
  'jatuh_tempo' => array('danger', translate('i18n_ksy_tab_jatuh_tempo')),
  'berjalan'    => array('info', translate('i18n_ksy_tab_berjalan')),
  'belum_kirim' => array('secondary', translate('i18n_ksy_tab_belum_kirim')),
  'selesai'     => array('success', translate('selesai')),
  'semua'       => array('dark', translate('i18n_semua')),
);
$sisa_hari = function ($jt) {
  if (!$jt) {
    return '';
  }
  $h = (int) round((strtotime($jt) - strtotime(date('Y-m-d'))) / 86400);
  if ($h < 0) {
    return '<span class="text-danger fw-semibold">' . sprintf(translate('i18n_ksy_lewat_hari'), abs($h)) . '</span>';
  }
  return '<span class="' . ($h <= 7 ? 'text-warning fw-semibold' : 'muted') . '">' . sprintf(translate('i18n_ksy_sisa_hari'), $h) . '</span>';
};
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3">
    <div class="judul"><i class="bi bi-box-seam me-2"></i><?= translate('i18n_ksy_menu') ?></div>
    <div class="meta"><?= translate('i18n_ksy_subjudul') ?></div>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>
  <?php $this->load->view('konsinyasi/_auto', array('auto' => $auto)); ?>

  <div class="card mb-3">
    <div class="card-body">
      <div class="langkah">
        <div><b>1. <?= translate('i18n_ksy_l1') ?></b><?= translate('i18n_ksy_l1_ket') ?></div>
        <div><b>2. <?= translate('i18n_ksy_l2') ?></b><?= translate('i18n_ksy_l2_ket') ?></div>
        <div><b>3. <?= translate('i18n_ksy_l3') ?></b><?= translate('i18n_ksy_l3_ket') ?></div>
        <div><b>4. <?= translate('i18n_ksy_l4') ?></b><?= translate('i18n_ksy_l4_ket') ?></div>
        <div><b>5. <?= translate('i18n_ksy_l5') ?></b><?= translate('i18n_ksy_l5_ket') ?></div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
    <div class="btn-group btn-group-sm flex-wrap">
      <?php foreach ($tahap_label as $k => $lbl): ?>
        <a href="<?= site_url('konsinyasi?tahap=' . $k . ($q !== '' ? '&q=' . rawurlencode($q) : '')) ?>"
           class="btn <?= $tahap === $k ? 'btn-' . $lbl[0] : 'btn-outline-' . $lbl[0] ?>">
          <?= html_escape($lbl[1]) ?> <span class="badge bg-light text-dark ms-1"><?= (int) ($jumlah[$k] ?? 0) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
    <form method="get" action="<?= site_url('konsinyasi') ?>" class="d-flex gap-2">
      <input type="hidden" name="tahap" value="<?= html_escape($tahap) ?>">
      <input type="text" name="q" value="<?= html_escape($q) ?>" class="form-control form-control-sm" placeholder="<?= translate('i18n_ksy_cari') ?>">
      <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th><?= translate('i18n_ksy_so_customer') ?></th>
            <th class="text-end"><?= translate('i18n_ksy_dititip') ?></th>
            <th class="text-end"><?= translate('i18n_ksy_laku') ?></th>
            <th class="text-end"><?= translate('i18n_ksy_ditarik') ?></th>
            <th class="text-end"><?= translate('i18n_ksy_di_customer') ?></th>
            <th><?= translate('i18n_ksy_jatuh_tempo') ?></th>
            <th class="text-end"><?= translate('i18n_ksy_tagihan_laku') ?></th>
            <th class="text-end"><?= translate('i18n_ksy_sisa_tagih') ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$list): ?>
            <tr><td colspan="9" class="text-center text-muted py-4"><?= translate('i18n_ksy_kosong') ?></td></tr>
          <?php endif; ?>
          <?php foreach ($list as $so): $r = $so->r; $tl = $tahap_label[$r['tahap']] ?? array('light', $r['tahap']); ?>
            <tr>
              <td>
                <div class="fw-semibold"><?= html_escape($so->no_so) ?> <span class="badge text-bg-<?= $tl[0] ?> fw-normal"><?= html_escape($tl[1]) ?></span></div>
                <div class="muted"><?= html_escape($so->nama_customer ?: '-') ?> &middot; <?= (int) ($so->masa_konsinyasi ?: 60) ?> <?= translate('i18n_hari') ?></div>
              </td>
              <td class="text-end"><?= $num($r['terkirim']) ?><div class="muted">/ <?= $num($r['qty']) ?></div></td>
              <td class="text-end text-success"><?= $num($r['laku']) ?></td>
              <td class="text-end"><?= $num($r['retur']) ?></td>
              <td class="text-end fw-semibold"><?= $num($r['di_customer']) ?>
                <?php if ($r['lewat'] > 0): ?><div class="text-danger small"><?= $num($r['lewat']) ?> <?= translate('i18n_ksy_lewat_masa') ?></div><?php endif; ?>
              </td>
              <td><?= $r['jt_terdekat'] ? $tgl($r['jt_terdekat']) . '<div class="small">' . $sisa_hari($r['jt_terdekat']) . '</div>' : '<span class="muted">-</span>' ?></td>
              <td class="text-end"><?= $rp($r['tagihan']) ?><div class="muted"><?= translate('i18n_ksy_dari') ?> <?= $rp($so->grand_total) ?></div></td>
              <td class="text-end <?= $r['sisa_tagih'] > 0.5 ? 'text-danger fw-semibold' : 'text-success' ?>"><?= $r['sisa_tagih'] > 0.5 ? $rp($r['sisa_tagih']) : translate('i18n_lunas') ?></td>
              <td class="text-end text-nowrap">
                <a href="<?= site_url('konsinyasi/so/' . (int) $so->id) ?>" class="btn btn-sm btn-primary"><i class="bi bi-box-seam me-1"></i><?= translate('i18n_ksy_kelola') ?></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
