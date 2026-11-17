<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');
$h        = $po['header'];
$dropship = $h->jenis === 'dropship';
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <div class="judul"><i class="bi <?= $dropship ? 'bi-truck' : 'bi-box-arrow-in-down' ?> me-2"></i>
        <?= $dropship ? translate('i18n_konfirmasi_dropship_diterima_customer') : translate('i18n_terima_barang_dari_supplier') ?></div>
      <div class="meta"><?= html_escape($h->no_po) ?> &middot; <?= html_escape($h->supplier ? $h->supplier->nama : '-') ?> &middot; <?= html_escape($h->no_so) ?> (<?= html_escape($h->nama_customer) ?>)</div>
    </div>
    <a href="<?= site_url('pemenuhan/so/' . (int) $h->id_so) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i><?= translate('kembali') ?></a>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <div class="alert <?= $dropship ? 'alert-info' : 'alert-secondary' ?>">
    <?php if ($dropship): ?>
      <?= translate('i18n_isi_setelah_ada_bukti_barang') ?> <b><?= translate('i18n_sudah_sampai_di_customer') ?></b> <?= translate('i18n_foto_serah_terima_resi_dari_supplier_qty') ?> <b><?= translate('i18n_terkirim') ?></b> <?= translate('i18n_di_so_dan_dibuatkan_surat_jalan_dropship') ?>
    <?php else: ?>
      <?= translate('i18n_barang_yang_diterima_masuk_gudang_dan') ?> <b><?= translate('i18n_siap_kirim') ?></b> <?= translate('i18n_ke_customer_dibuatkan_surat_jalan') ?>
    <?php endif; ?>
  </div>

  <?= form_open('pemenuhan/po_terima/' . (int) $h->id, array('id' => 'form_terima', 'autocomplete' => 'off')) ?>
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label"><?= $dropship ? translate('i18n_tanggal_diterima_customer') : translate('i18n_tanggal_terima') ?></label>
          <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label"><?= $dropship ? translate('i18n_no_resi_surat_jalan_supplier') : translate('i18n_no_surat_jalan_supplier') ?></label>
          <input type="text" name="no_sj_supplier" class="form-control" maxlength="100">
        </div>
        <?php if ($dropship): ?>
          <div class="col-md-3">
            <label class="form-label"><?= translate('i18n_ekspedisi') ?></label>
            <input type="text" name="ekspedisi" class="form-control" maxlength="100" placeholder="<?= translate('i18n_armada_supplier_jne_dll') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label"><?= translate('i18n_diterima_oleh_customer') ?></label>
            <input type="text" name="penerima" class="form-control" maxlength="100">
          </div>
        <?php endif; ?>
        <div class="col-12">
          <label class="form-label"><?= translate('i18n_catatan') ?></label>
          <input type="text" name="catatan" class="form-control" placeholder="<?= translate('i18n_kondisi_barang_kekurangan_dll') ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr><th><?= translate('i18n_barang') ?></th><th class="text-end"><?= translate('i18n_dipesan') ?></th><th class="text-end"><?= translate('i18n_sudah_diterima') ?></th><th class="text-end"><?= translate('i18n_sisa') ?></th><th class="text-end" style="width:140px"><?= translate('i18n_diterima_sekarang') ?></th></tr>
        </thead>
        <tbody>
          <?php foreach ($po['items'] as $d):
            $sisa = max(0, (float) $d->qty - (float) $d->qty_diterima); ?>
            <tr>
              <td>
                <div class="fw-semibold"><?= html_escape($d->nama_barang) ?></div>
                <div class="muted"><?= $d->id_so_detail ? translate('i18n_item_so') : translate('i18n_baris_tambahan') ?><?= trim((string) $d->spesifikasi) !== '' ? ' &middot; ' . html_escape($d->spesifikasi) : '' ?></div>
              </td>
              <td class="text-end"><?= $num($d->qty) ?> <?= html_escape($d->satuan) ?></td>
              <td class="text-end"><?= $num($d->qty_diterima) ?></td>
              <td class="text-end"><?= $num($sisa) ?></td>
              <td>
                <input type="number" name="qty[<?= (int) $d->id ?>]" class="form-control form-control-sm text-end" min="0" max="<?= $sisa ?>" step="0.01" value="<?= $sisa ?>" <?= $sisa > 0 ? '' : 'disabled' ?>>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2 mb-4">
    <a href="<?= site_url('pemenuhan/so/' . (int) $h->id_so) ?>" class="btn btn-outline-secondary"><?= translate('btn_batal') ?></a>
    <button type="submit" class="btn btn-success" id="btn_simpan"><i class="bi bi-check2-square me-1"></i><?= translate('button_save') ?></button>
  </div>
  <?= form_close() ?>

  <?php if (!empty($po['penerimaan'])): ?>
    <div class="card">
      <div class="card-header"><?= translate('i18n_riwayat_penerimaan') ?></div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="table-light"><tr><th><?= translate('tanggal') ?></th><th><?= translate('i18n_no_sj_resi_supplier') ?></th><th><?= translate('i18n_catatan') ?></th></tr></thead>
          <tbody>
            <?php foreach ($po['penerimaan'] as $r): ?>
              <tr><td><?= $tgl($r->tanggal) ?></td><td><?= html_escape($r->no_sj_supplier ?: '-') ?></td><td><?= html_escape($r->catatan ?: '-') ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>
<script>
  (function () {
    var kirim = false;
    document.getElementById('form_terima').addEventListener('submit', function (e) {
      if (kirim) { e.preventDefault(); return; }
      kirim = true;
      document.getElementById('btn_simpan').disabled = true;
    });
  })();
</script>
