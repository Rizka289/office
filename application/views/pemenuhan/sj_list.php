<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3">
    <div class="judul"><i class="bi bi-box-seam me-2"></i><?= translate('i18n_surat_jalan') ?></div>
    <div class="meta"><?= translate('i18n_pengiriman_barang_ke_customer_dari') ?></div>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <?php if ($so_siap && !empty($izin['kirim'])): ?>
    <div class="card mb-3 border-start border-4 border-success">
      <div class="card-header"><i class="bi bi-box-seam me-2"></i><?= translate('i18n_barang_siap_dikirim_dari_gudang') ?> <span class="badge text-bg-success"><?= count($so_siap) ?> <?= translate('i18n_so') ?></span></div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light"><tr><th><?= translate('i18n_no_so') ?></th><th><?= translate('i18n_customer') ?></th><th><?= translate('i18n_target_kirim') ?></th><th class="text-end"><?= translate('i18n_qty_siap_kirim') ?></th><th></th></tr></thead>
          <tbody>
            <?php foreach ($so_siap as $s): $telat = $s->tgl_kirim && strtotime($s->tgl_kirim) < strtotime(date('Y-m-d')); ?>
              <tr>
                <td class="fw-semibold"><?= html_escape($s->no_so) ?></td>
                <td><?= html_escape($s->nama_customer) ?></td>
                <td class="<?= $telat ? 'text-danger fw-semibold' : '' ?>"><?= $tgl($s->tgl_kirim) ?></td>
                <td class="text-end"><?= $num($s->qty_siap) ?></td>
                <td class="text-end"><a href="<?= site_url('pemenuhan/sj_buat/' . (int) $s->id) ?>" class="btn btn-sm btn-success py-0"><i class="bi bi-plus-lg me-1"></i><?= translate('i18n_buat_surat_jalan_2') ?></a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header"><i class="bi bi-list-ul me-2"></i><?= translate('i18n_daftar_surat_jalan') ?></div>
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-light"><tr><th><?= translate('i18n_no_sj') ?></th><th><?= translate('i18n_so_customer') ?></th><th><?= translate('tanggal') ?></th><th><?= translate('dari') ?></th><th class="text-end"><?= translate('i18n_qty_2') ?></th><th><?= translate('status') ?></th><th></th></tr></thead>
        <tbody>
          <?php if (!$sj_list): ?><tr><td colspan="7" class="text-center text-muted py-4"><?= translate('i18n_belum_ada_surat_jalan') ?></td></tr><?php endif; ?>
          <?php foreach ($sj_list as $s): ?>
            <tr>
              <td class="fw-semibold"><?= html_escape($s->no_sj) ?></td>
              <td><?= html_escape($s->no_so) ?><div class="muted"><?= html_escape($s->nama_customer) ?></div></td>
              <td><?= $tgl($s->tanggal) ?></td>
              <td><?= $s->jenis === 'dropship' ? 'Dropship: ' . html_escape($s->nama_supplier) : translate('i18n_gudang') ?></td>
              <td class="text-end"><?= $num($s->total_qty) ?></td>
              <td><?= $badge('sj', $s->status) ?></td>
              <td class="text-end text-nowrap">
                <a href="<?= site_url('pemenuhan/so/' . (int) $s->id_so) ?>" class="btn btn-sm btn-outline-success py-0"><?= translate('i18n_buka_so') ?></a>
                <a href="<?= site_url('pemenuhan/sj_cetak/' . (int) $s->id) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0" title="<?= translate('i18n_cetak_sj') ?>"><i class="bi bi-printer"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (count($sj_list) >= 300): ?><div class="card-body border-top muted"><?= translate('i18n_menampilkan_300_surat_jalan_terbaru') ?></div><?php endif; ?>
  </div>
</div>
