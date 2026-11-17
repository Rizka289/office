<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');
$hari_ini = strtotime(date('Y-m-d'));
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3">
    <div class="judul"><i class="bi bi-box-arrow-in-down me-2"></i><?= translate('penerimaan_barang') ?></div>
    <div class="meta"><?= translate('i18n_po_yang_sudah_dipesan_ke_supplier_dan') ?></div>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <div class="card">
    <div class="card-header"><i class="bi bi-truck me-2"></i><?= translate('i18n_barang_akan_datang') ?> <span class="badge text-bg-secondary"><?= count($po_list) ?></span></div>
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-light">
          <tr><th><?= translate('i18n_no_po_2') ?></th><th><?= translate('i18n_supplier') ?></th><th><?= translate('i18n_so_customer') ?></th><th><?= translate('i18n_kirim_ke') ?></th><th><?= translate('i18n_dibutuhkan') ?></th><th style="min-width:150px"><?= translate('i18n_diterima') ?></th><th><?= translate('status') ?></th><th class="text-end"></th></tr>
        </thead>
        <tbody>
          <?php if (!$po_list): ?>
            <tr><td colspan="8" class="text-center text-muted py-4"><?= translate('i18n_tidak_ada_barang_yang_sedang_ditunggu') ?></td></tr>
          <?php endif; ?>
          <?php foreach ($po_list as $p):
            $pct   = (float) $p->total_qty > 0 ? min(100, round((float) $p->total_terima / (float) $p->total_qty * 100)) : 0;
            $telat = $p->tgl_butuh && strtotime($p->tgl_butuh) < $hari_ini;
            $drop  = $p->jenis === 'dropship';
          ?>
            <tr>
              <td class="fw-semibold"><a href="<?= site_url('pemenuhan/po_detail/' . (int) $p->id) ?>"><?= html_escape($p->no_po) ?></a><div class="muted"><?= $tgl($p->tanggal) ?></div></td>
              <td><?= html_escape($p->nama_supplier) ?></td>
              <td><?= html_escape($p->no_so) ?><div class="muted"><?= html_escape($p->nama_customer) ?></div></td>
              <td><?= $drop ? '<span class="badge text-bg-info fw-normal">' . translate('i18n_langsung_ke_customer') . '</span>' : '<span class="badge text-bg-light fw-normal">' . translate('i18n_gudang') . '</span>' ?></td>
              <td class="<?= $telat ? 'text-danger fw-semibold' : '' ?>"><?= $tgl($p->tgl_butuh) ?><?= $telat ? ' <i class="bi bi-exclamation-circle" title="' . translate('i18n_lewat_tanggal_dibutuhkan') . '"></i>' : '' ?></td>
              <td>
                <?= $num($p->total_terima) ?> / <?= $num($p->total_qty) ?> (<?= $pct ?>%)
                <div class="mini-bar"><div style="width:<?= $pct ?>%"></div></div>
              </td>
              <td><?= $badge('po', $p->status) ?></td>
              <td class="text-end text-nowrap">
                <?php if (!empty($izin['terima'])): ?>
                  <a href="<?= site_url('pemenuhan/po_terima/' . (int) $p->id) ?>" class="btn btn-sm btn-success py-0"><?= $drop ? translate('i18n_diterima_customer') : translate('i18n_terima_barang_2') ?></a>
                <?php endif; ?>
                <a href="<?= site_url('pemenuhan/po_cetak/' . (int) $p->id) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0" title="<?= translate('i18n_cetak_po') ?>"><i class="bi bi-printer"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
