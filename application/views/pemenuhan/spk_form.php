<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <div class="judul"><i class="bi bi-hammer me-2"></i>Buat SPK Produksi</div>
      <div class="meta">Untuk <?= html_escape($h->no_so) ?> &middot; <?= html_escape($h->nama_customer) ?></div>
    </div>
    <a href="<?= site_url('pemenuhan/so/' . (int) $h->id) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <?= form_open('pemenuhan/spk_simpan/' . (int) $h->id, array('id' => 'form_spk', 'autocomplete' => 'off')) ?>
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Tanggal SPK</label>
          <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Target selesai</label>
          <input type="date" name="target_selesai" class="form-control" value="<?= html_escape((string) $h->tgl_kirim) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Catatan untuk produksi</label>
          <input type="text" name="catatan" class="form-control" placeholder="Prioritas, instruksi khusus, dll">
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr><th>Item</th><th class="text-end">Qty SO</th><th class="text-end">Sudah SPK</th><th class="text-end" style="width:140px">Qty diproduksi</th></tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td>
                <div class="fw-semibold"><?= html_escape($it->p_nama) ?></div>
                <div class="muted"><?= html_escape(trim(($it->finishing ?? '') . ' ' . ($it->keterangan ?? ''))) ?></div>
              </td>
              <td class="text-end"><?= $num($it->qty) ?></td>
              <td class="text-end"><?= $num($it->p_proses) ?></td>
              <td><input type="number" name="qty[<?= (int) $it->id ?>]" class="form-control form-control-sm text-end" min="0" max="<?= (float) $it->p_sisa_proses ?>" step="0.01" value="<?= (float) $it->p_sisa_proses ?>"></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-body border-top muted">Detail spesifikasi lengkap (aksesoris, kaca, motor, dll) ikut tercetak di dokumen SPK.</div>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <a href="<?= site_url('pemenuhan/so/' . (int) $h->id) ?>" class="btn btn-outline-secondary">Batal</a>
    <button type="submit" class="btn btn-primary" id="btn_simpan"><i class="bi bi-check2-square me-1"></i>Simpan SPK</button>
  </div>
  <?= form_close() ?>
</div>
<script>
  (function () {
    var kirim = false;
    document.getElementById('form_spk').addEventListener('submit', function (e) {
      if (kirim) { e.preventDefault(); return; }
      kirim = true;
      document.getElementById('btn_simpan').disabled = true;
    });
  })();
</script>
