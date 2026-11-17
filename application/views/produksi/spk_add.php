<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid py-3">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3 border-bottom">
          <h5 class="mb-0 fw-bold text-success"><i class="bi bi-journal-plus me-2"></i><?= translate('i18n_buat_surat_perintah_kerja_spk') ?></h5>
          <small class="text-muted"><?= translate('i18n_pilih_sales_order_yang_sudah') ?></small>
        </div>
        <div class="card-body p-4">

          <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger"><?= html_escape($this->session->flashdata('error')) ?></div>
          <?php endif; ?>

          <?= form_open('spk/simpan', ['autocomplete' => 'off']); ?>
          
          <div class="mb-3">
            <label class="form-label fw-semibold"><?= translate('i18n_pilih_sales_order_confimed') ?> <span class="text-danger">*</span></label>
            <select name="id_so" class="form-select" required>
              <option value=""><?= translate('i18n_pilih_sales_order_siap_produksi') ?></option>
              <?php if (!empty($so_siap_list)): ?>
                <?php foreach ($so_siap_list as $so): ?>
                  <option value="<?= (int) $so->id ?>">
                    <?= html_escape($so->no_so . ' — ' . $so->nama_customer . (' ' . translate('i18n_tgl') . ' ') . date('d/m/Y', strtotime($so->tanggal)) . ')') ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
            <?php if (empty($so_siap_list)): ?>
              <div class="form-text text-warning"><?= translate('i18n_tidak_ada_sales_order_dengan_status') ?></div>
            <?php endif; ?>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold"><?= translate('i18n_tanggal_spk') ?> <span class="text-danger">*</span></label>
              <input type="date" name="tanggal_spk" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold"><?= translate('i18n_target_selesai_2') ?></label>
              <input type="date" name="target_selesai" class="form-control">
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold"><?= translate('i18n_catatan_instruksi_produksi') ?></label>
            <textarea name="catatan_produksi" class="form-control" rows="3" placeholder="<?= translate('i18n_instruksi_khusus_untuk_tim_workshop') ?>"></textarea>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <a href="<?= site_url('spk'); ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i><?= translate('btn_batal') ?></a>
            <button type="submit" class="btn btn-success" <?= empty($so_siap_list) ? 'disabled' : '' ?>>
              <i class="bi bi-check2-circle me-1"></i><?= translate('i18n_simpan_buat_spk') ?>
            </button>
          </div>

          <?= form_close(); ?>

        </div>
      </div>
    </div>
  </div>
</div>