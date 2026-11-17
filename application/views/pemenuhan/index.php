<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <div class="judul"><i class="bi bi-diagram-3 me-2"></i><?= translate('app_purchasing') ?></div>
      <div class="meta"><?= translate('i18n_so_yang_sudah_dikonfirmasi_tentukan') ?></div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <?php if (!empty($izin['menu_po'])): ?><a href="<?= site_url('pemenuhan/po') ?>" class="btn btn-light btn-sm"><i class="bi bi-cart me-1"></i><?= translate('i18n_purchase_order') ?></a><?php endif; ?>
      <a href="<?= site_url('supplier') ?>" class="btn btn-light btn-sm"><i class="bi bi-building me-1"></i><?= translate('i18n_data_supplier') ?></a>
    </div>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <div class="card">
    <div class="card-header"><i class="bi bi-list-task me-2"></i><?= translate('i18n_so_berjalan') ?> <span class="badge text-bg-secondary"><?= count($so_list) ?></span></div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light"><tr><th><?= translate('i18n_no_so') ?></th><th><?= translate('i18n_customer') ?></th><th><?= translate('i18n_target_kirim') ?></th><th><?= translate('status') ?></th><th><?= translate('i18n_perlu_tindakan') ?></th><th style="min-width:150px"><?= translate('kirim') ?></th><th></th></tr></thead>
          <tbody>
            <?php if (!$so_list): ?><tr><td colspan="7" class="text-center text-muted py-4"><?= translate('i18n_tidak_ada_so_yang_sedang_diproses') ?></td></tr><?php endif; ?>
            <?php foreach ($so_list as $s):
              $pct = (float) $s->total_qty > 0 ? min(100, round((float) $s->total_kirim / (float) $s->total_qty * 100)) : 0;
              $telat = $s->tgl_kirim && strtotime($s->tgl_kirim) < strtotime(date('Y-m-d')) && $pct < 100;
            ?>
              <tr>
                <td class="fw-semibold"><?= html_escape($s->no_so) ?></td>
                <td><?= html_escape($s->nama_customer) ?></td>
                <td class="<?= $telat ? 'text-danger fw-semibold' : '' ?>"><?= $tgl($s->tgl_kirim) ?><?= $telat ? ' <i class="bi bi-exclamation-circle" title="' . translate('i18n_lewat_target') . '"></i>' : '' ?></td>
                <td><?= $badge('so', $s->status) ?></td>
                <td class="small">
                  <?php if ((int) $s->belum_sumber > 0): ?><span class="badge text-bg-warning fw-normal"><?= (int) $s->belum_sumber ?> <?= translate('i18n_item_belum_ada_sumber') ?></span><?php endif; ?>
                  <?php if ((int) $s->spk_jalan > 0): ?><span class="badge text-bg-light fw-normal"><?= (int) $s->spk_jalan ?> <?= translate('i18n_spk_jalan') ?></span><?php endif; ?>
                  <?php if ((int) $s->po_jalan > 0): ?><span class="badge text-bg-light fw-normal"><?= (int) $s->po_jalan ?> <?= translate('i18n_po_terbuka') ?></span><?php endif; ?>
                </td>
                <td>
                  <?= $num($s->total_kirim) ?> / <?= $num($s->total_qty) ?> (<?= $pct ?>%)
                  <div class="mini-bar"><div style="width:<?= $pct ?>%"></div></div>
                </td>
                <td class="text-end"><a href="<?= site_url('pemenuhan/so/' . (int) $s->id) ?>" class="btn btn-sm btn-success py-0"><?= translate('i18n_proses') ?></a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    
  </div>
</div>
