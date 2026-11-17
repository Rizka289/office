<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <div class="judul"><i class="bi bi-box-seam me-2"></i><?= translate('i18n_buat_surat_jalan') ?></div>
      <div class="meta"><?= translate('i18n_untuk') ?> <?= html_escape($h->no_so) ?> &middot; <?= html_escape($h->nama_customer) ?></div>
    </div>
    <a href="<?= site_url('pemenuhan/so/' . (int) $h->id) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i><?= translate('kembali') ?></a>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <?= form_open('pemenuhan/sj_simpan/' . (int) $h->id, array('id' => 'form_sj', 'autocomplete' => 'off')) ?>
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label"><?= translate('i18n_tanggal_kirim') ?></label>
          <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label"><?= translate('i18n_ekspedisi_sopir') ?></label>
          <input type="text" name="ekspedisi" class="form-control" maxlength="100" placeholder="<?= translate('i18n_armada_sendiri_nama_ekspedisi') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label"><?= translate('i18n_no_kendaraan') ?></label>
          <input type="text" name="no_kendaraan" class="form-control" maxlength="30">
        </div>
        <div class="col-md-4">
          <label class="form-label"><?= translate('i18n_alamat_kirim') ?></label>
          <textarea name="alamat_kirim" class="form-control" rows="2"><?= html_escape((string) ($h->alamat_kirim ?: $h->alamat_customer)) ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label"><?= translate('i18n_catatan') ?></label>
          <input type="text" name="catatan" class="form-control">
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr><th><?= translate('i18n_item_3') ?></th><th><?= translate('i18n_sumber') ?></th><th class="text-end"><?= translate('i18n_qty_so') ?></th><th class="text-end"><?= translate('kirim') ?></th><th class="text-end"><?= translate('i18n_siap_di_gudang') ?></th><th class="text-end" style="width:140px"><?= translate('i18n_qty_dikirim') ?></th></tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td class="fw-semibold"><?= html_escape($it->p_nama) ?></td>
              <td><?= $badge('sumber', $it->sumber) ?></td>
              <td class="text-end"><?= $num($it->qty) ?></td>
              <td class="text-end"><?= $num($it->qty_terkirim) ?></td>
              <td class="text-end"><?= $num($it->p_siap_kirim) ?></td>
              <td><input type="number" name="qty[<?= (int) $it->id ?>]" class="form-control form-control-sm text-end" min="0" max="<?= (float) $it->p_siap_kirim ?>" step="0.01" value="<?= (float) $it->p_siap_kirim ?>"></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-body border-top muted"><?= translate('i18n_hanya_barang_yang_sudah_selesai_produksi') ?> <b><?= translate('i18n_stok') ?></b> <?= translate('i18n_otomatis_dikurangi_dari_stok_gudang') ?></div>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <a href="<?= site_url('pemenuhan/so/' . (int) $h->id) ?>" class="btn btn-outline-secondary"><?= translate('btn_batal') ?></a>
    <button type="submit" class="btn btn-success" id="btn_simpan"><i class="bi bi-check2-square me-1"></i><?= translate('i18n_simpan_surat_jalan') ?></button>
  </div>
  <?= form_close() ?>
</div>
<script>
  (function () {
    var kirim = false;
    document.getElementById('form_sj').addEventListener('submit', function (e) {
      if (kirim) { e.preventDefault(); return; }
      kirim = true;
      document.getElementById('btn_simpan').disabled = true;
    });
  })();
</script>
