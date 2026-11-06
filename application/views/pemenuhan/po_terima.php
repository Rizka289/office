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
        <?= $dropship ? 'Konfirmasi dropship diterima customer' : 'Terima barang dari supplier' ?></div>
      <div class="meta"><?= html_escape($h->no_po) ?> &middot; <?= html_escape($h->supplier ? $h->supplier->nama : '-') ?> &middot; <?= html_escape($h->no_so) ?> (<?= html_escape($h->nama_customer) ?>)</div>
    </div>
    <a href="<?= site_url('pemenuhan/so/' . (int) $h->id_so) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <div class="alert <?= $dropship ? 'alert-info' : 'alert-secondary' ?>">
    <?php if ($dropship): ?>
      Isi setelah ada bukti barang <b>sudah sampai di customer</b> (foto serah terima / resi dari supplier).
      Qty yang dikonfirmasi otomatis tercatat <b>terkirim</b> di SO dan dibuatkan surat jalan dropship.
    <?php else: ?>
      Barang yang diterima masuk gudang dan menjadi <b>siap kirim</b> ke customer (dibuatkan surat jalan).
    <?php endif; ?>
  </div>

  <?= form_open('pemenuhan/po_terima/' . (int) $h->id, array('id' => 'form_terima', 'autocomplete' => 'off')) ?>
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label"><?= $dropship ? 'Tanggal diterima customer' : 'Tanggal terima' ?></label>
          <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label"><?= $dropship ? 'No. resi / surat jalan supplier' : 'No. surat jalan supplier' ?></label>
          <input type="text" name="no_sj_supplier" class="form-control" maxlength="100">
        </div>
        <?php if ($dropship): ?>
          <div class="col-md-3">
            <label class="form-label">Ekspedisi</label>
            <input type="text" name="ekspedisi" class="form-control" maxlength="100" placeholder="Armada supplier / JNE / dll">
          </div>
          <div class="col-md-3">
            <label class="form-label">Diterima oleh (customer)</label>
            <input type="text" name="penerima" class="form-control" maxlength="100">
          </div>
        <?php endif; ?>
        <div class="col-12">
          <label class="form-label">Catatan</label>
          <input type="text" name="catatan" class="form-control" placeholder="Kondisi barang, kekurangan, dll">
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr><th>Barang</th><th class="text-end">Dipesan</th><th class="text-end">Sudah diterima</th><th class="text-end">Sisa</th><th class="text-end" style="width:140px">Diterima sekarang</th></tr>
        </thead>
        <tbody>
          <?php foreach ($po['items'] as $d):
            $sisa = max(0, (float) $d->qty - (float) $d->qty_diterima); ?>
            <tr>
              <td>
                <div class="fw-semibold"><?= html_escape($d->nama_barang) ?></div>
                <div class="muted"><?= $d->id_so_detail ? 'Item SO' : 'Baris tambahan' ?><?= trim((string) $d->spesifikasi) !== '' ? ' &middot; ' . html_escape($d->spesifikasi) : '' ?></div>
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
    <a href="<?= site_url('pemenuhan/so/' . (int) $h->id_so) ?>" class="btn btn-outline-secondary">Batal</a>
    <button type="submit" class="btn btn-success" id="btn_simpan"><i class="bi bi-check2-square me-1"></i>Simpan</button>
  </div>
  <?= form_close() ?>

  <?php if (!empty($po['penerimaan'])): ?>
    <div class="card">
      <div class="card-header">Riwayat penerimaan</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="table-light"><tr><th>Tanggal</th><th>No. SJ / resi supplier</th><th>Catatan</th></tr></thead>
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
