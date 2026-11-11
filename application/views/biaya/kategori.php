<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('pemenuhan/_style');
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <div class="judul"><i class="bi bi-tags me-2"></i>Kategori Biaya</div>
      <div class="meta">Kategori yang sudah dipakai tidak bisa dihapus, cukup dinonaktifkan supaya tidak muncul di form.</div>
    </div>
    <a href="<?= site_url('biaya') ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Biaya Operasional</a>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <div class="row g-3">
    <?php if (!empty($izin['catat'])): ?>
      <div class="col-lg-4">
        <div class="card">
          <div class="card-header"><i class="bi bi-plus-lg me-2"></i>Tambah kategori</div>
          <div class="card-body">
            <?= form_open('biaya/kategori_simpan/0') ?>
              <div class="input-group input-group-sm">
                <input type="text" name="nama" class="form-control" maxlength="100" placeholder="mis. Asuransi" required>
                <button class="btn btn-success">Simpan</button>
              </div>
            <?= form_close() ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="<?= !empty($izin['catat']) ? 'col-lg-8' : 'col-12' ?>">
      <div class="card">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>Nama</th><th class="text-center">Dipakai</th><th>Status</th><th></th></tr></thead>
            <tbody>
              <?php if (!$kategori): ?><tr><td colspan="4" class="text-center text-muted py-4">Belum ada kategori.</td></tr><?php endif; ?>
              <?php foreach ($kategori as $k): ?>
                <tr class="<?= $k->aktif ? '' : 'text-muted' ?>">
                  <td>
                    <?php if (!empty($izin['catat'])): ?>
                      <?= form_open('biaya/kategori_simpan/' . (int) $k->id, array('class' => 'd-flex gap-1')) ?>
                        <input type="text" name="nama" value="<?= html_escape($k->nama) ?>" class="form-control form-control-sm" maxlength="100" required>
                        <button class="btn btn-sm btn-outline-secondary" title="Simpan nama"><i class="bi bi-check2"></i></button>
                      <?= form_close() ?>
                    <?php else: ?>
                      <?= html_escape($k->nama) ?>
                    <?php endif; ?>
                  </td>
                  <td class="text-center"><?= (int) $k->dipakai ?>×</td>
                  <td><span class="badge <?= $k->aktif ? 'text-bg-success' : 'text-bg-secondary' ?> fw-normal"><?= $k->aktif ? 'Aktif' : 'Nonaktif' ?></span></td>
                  <td class="text-end text-nowrap">
                    <?php if (!empty($izin['catat'])): ?>
                      <?= form_open('biaya/kategori_aktif/' . (int) $k->id, array('class' => 'd-inline')) ?>
                        <button class="btn btn-sm btn-outline-secondary py-0"><?= $k->aktif ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                      <?= form_close() ?>
                      <?php if (!(int) $k->dipakai): ?>
                        <?= form_open('biaya/kategori_hapus/' . (int) $k->id, array('class' => 'd-inline', 'onsubmit' => "return confirm('Hapus kategori ini?')")) ?>
                          <button class="btn btn-sm btn-outline-danger py-0" title="Hapus"><i class="bi bi-trash"></i></button>
                        <?= form_close() ?>
                      <?php endif; ?>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
