<?php defined('BASEPATH') or exit('No direct script access allowed');
// Hasil tarik otomatis (barang lewat masa titip). $auto dari Konsinyasi_model::auto_tarik()
if (empty($auto)) {
  return;
}
?>
<?php if ($auto['qty'] > 0): ?>
  <div class="alert alert-info"><i class="bi bi-arrow-return-left me-1"></i><?= sprintf(translate('i18n_ksy_auto_berhasil'), (int) $auto['qty'], (int) $auto['so']) ?></div>
<?php endif; ?>
<?php if (!empty($auto['manual'])): ?>
  <div class="alert alert-warning">
    <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i><?= translate('i18n_ksy_auto_manual') ?></div>
    <ul class="mb-0 small">
      <?php foreach ($auto['manual'] as $m): ?>
        <li><a href="<?= site_url('konsinyasi/so/' . (int) $m['id_so']) ?>"><?= html_escape($m['no_so']) ?></a> &middot; <?= html_escape($m['nama']) ?> (<?= (int) $m['qty'] ?>)</li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
<?php if (!empty($auto['error'])): ?>
  <div class="alert alert-danger small mb-3">
    <div class="fw-semibold"><?= translate('i18n_ksy_auto_gagal') ?></div>
    <?php foreach ($auto['error'] as $e): ?><div><?= html_escape($e) ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>
