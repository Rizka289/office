<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
  $tarif_ppn = isset($tarif_ppn) ? $tarif_ppn : 11;
  $tarif_pph = isset($tarif_pph) ? $tarif_pph : 2.5;
  $list_customer = isset($list_customer) ? (array) $list_customer : [];
  $ada_customer  = !empty($list_customer);
  $tanggal_maks  = date('Y-m-d');
?>

<style>
  .pnw-page { --pnw-brand: #2f6f4f; --pnw-brand-dark: #234f38; --pnw-radius: 14px; }

  .pnw-page .page-header {
    background: linear-gradient(135deg, var(--pnw-brand), var(--pnw-brand-dark));
    color: #fff; padding: 22px 28px; border-radius: var(--pnw-radius);
    margin-bottom: 22px; box-shadow: 0 6px 18px rgba(35,79,56,.25);
  }
  .pnw-page .page-header h4 { margin: 0; font-weight: 600; }
  .pnw-page .page-header small { opacity: .85; }

  .pnw-page .card-modern {
    background: #fff; border: none; border-radius: var(--pnw-radius);
    box-shadow: 0 2px 10px rgba(0,0,0,.06); margin-bottom: 20px;
  }
  .pnw-page .card-modern .card-header {
    background: #fff; border-bottom: 1px solid #eef1ef;
    border-radius: var(--pnw-radius) var(--pnw-radius) 0 0;
    font-weight: 600; color: var(--pnw-brand-dark); padding: 14px 20px;
  }
  .pnw-page .card-modern .card-body { padding: 20px; }

  .pnw-page .form-label { font-weight: 500; font-size: .84rem; color: #4a4a4a; }
  .pnw-page .form-control[readonly] { background: #f3f6f4; color: #5a5a5a; }
  .pnw-page .form-control:focus, .pnw-page .form-select:focus {
    border-color: var(--pnw-brand); box-shadow: 0 0 0 .2rem rgba(47,111,79,.15);
  }

  .pnw-page .item-card {
    border: 1px solid #eef1ef; border-radius: 12px; padding: 16px;
    margin-bottom: 14px; position: relative; background: #fbfdfc;
  }
  .pnw-page .item-card .item-title { font-weight: 700; color: var(--pnw-brand-dark); font-size: .88rem; }
  .pnw-page .item-card .item-subtotal { font-weight: 700; color: var(--pnw-brand-dark); }
  .pnw-page .btn-remove-item { color: #b3261e; }
  .pnw-page .btn-remove-item:hover { color: #7a1a15; }
  .pnw-page .btn-remove-item:disabled { color: #c9c9c9; }

  .pnw-page .summary-box { background: #f8faf9; border: 1px dashed #cfe0d6; border-radius: 10px; padding: 16px 18px; }
  .pnw-page .total-box { background: #f8faf9; border-left: 4px solid var(--pnw-brand); border-radius: 10px; padding: 16px 18px; }

  .pnw-page .footer-actions {
    position: sticky; bottom: 0; background: #fff; padding: 14px 20px;
    border-radius: var(--pnw-radius); box-shadow: 0 -4px 14px rgba(0,0,0,.06);
    display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap; z-index: 5;
  }
</style>

<div class="pnw-page">

  <div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <h4><i class="bi bi-file-earmark-text me-2"></i>Buat Penawaran Baru</h4>
      <small>Isi data customer dan item barang yang ditawarkan.</small>
    </div>
    <div class="text-end">
      <div class="fw-bold fs-5">No. Surat: <?= html_escape($no_surat); ?></div>
    </div>
  </div>

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      <?= $this->session->flashdata('error'); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endif; ?>

  <?php if (!$ada_customer): ?>
    <div class="alert alert-warning">
      Belum ada data customer. Tambahkan customer terlebih dahulu sebelum membuat penawaran.
    </div>
  <?php endif; ?>

  <?= form_open('penawaran/simpan', ['id' => 'formPenawaran', 'autocomplete' => 'off']); ?>

  <!-- Informasi penawaran -->
  <div class="card-modern">
    <div class="card-header"><i class="bi bi-info-circle me-2"></i>Informasi Penawaran</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">No. Surat</label>
          <input type="text" name="no_surat" class="form-control" value="<?= html_escape($no_surat); ?>" readonly>
          <div class="form-text">Dibuat otomatis oleh sistem.</div>
        </div>
        <div class="col-md-3">
          <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
          <input type="date" id="tanggal" name="tanggal" class="form-control" value="<?= $tanggal_maks; ?>" required <?= $ada_customer ? '' : 'disabled'; ?>>
        </div>
        <div class="col-md-6">
          <label for="id_customer" class="form-label">Customer <span class="text-danger">*</span></label>
          <select name="id_customer" id="id_customer" class="form-select" required <?= $ada_customer ? '' : 'disabled'; ?>>
            <option value="">Pilih customer</option>
            <?php foreach ($list_customer as $c): ?>
              <option value="<?= html_escape($c['id']); ?>"><?= html_escape($c['nama']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12">
          <label for="catatan" class="form-label">Catatan (opsional)</label>
          <textarea id="catatan" name="catatan" class="form-control" rows="2" placeholder="Mis. estimasi pengerjaan, syarat pembayaran khusus, dll." <?= $ada_customer ? '' : 'disabled'; ?>></textarea>
        </div>
      </div>
    </div>
  </div>

  <!-- Item penawaran -->
  <div class="card-modern">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span><i class="bi bi-list-check me-2"></i>Item Penawaran</span>
      <button type="button" class="btn btn-sm btn-outline-success" id="btnTambahItem" <?= $ada_customer ? '' : 'disabled'; ?>>
        <i class="bi bi-plus-lg me-1"></i>Tambah Item
      </button>
    </div>
    <div class="card-body">
      <div id="wrapItem"></div>
      <div class="form-text">Ukuran dalam milimeter (mm). Luas m² dan Total dihitung otomatis.</div>
    </div>
  </div>

  <!-- Ringkasan -->
  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="card-modern h-100 mb-0">
        <div class="card-header"><i class="bi bi-percent me-2"></i>Pajak</div>
        <div class="card-body">
          <div class="d-flex justify-content-between small mb-2">
            <span class="text-muted">PPN</span><span><?= html_escape($tarif_ppn); ?>%</span>
          </div>
          <div class="d-flex justify-content-between small">
            <span class="text-muted">PPh</span><span><?= html_escape($tarif_pph); ?>%</span>
          </div>
          <div class="form-text mt-2">Tarif tetap, mengikuti pengaturan sistem.</div>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card-modern h-100 mb-0">
        <div class="card-header"><i class="bi bi-receipt me-2"></i>Ringkasan</div>
        <div class="card-body">
          <div class="summary-box mb-3">
            <div class="text-muted small">Jumlah item</div>
            <div class="fw-bold fs-5" id="sumJumlahItem">0</div>
          </div>
          <div class="d-flex justify-content-between small mb-1">
            <span class="text-muted">Subtotal</span><span id="sumSubtotal">Rp 0</span>
          </div>
          <div class="d-flex justify-content-between small mb-1">
            <span class="text-muted">PPN <?= html_escape($tarif_ppn); ?>%</span><span id="sumPpn">Rp 0</span>
          </div>
          <div class="d-flex justify-content-between small mb-1">
            <span class="text-muted">PPh <?= html_escape($tarif_pph); ?>%</span><span id="sumPph">Rp 0</span>
          </div>
          <div class="total-box mt-3">
            <div class="text-muted small">Grand Total</div>
            <div class="h3 fw-bold mb-0" id="sumGrandTotal">Rp 0</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="footer-actions">
    <a href="<?= site_url('penawaran'); ?>" class="btn btn-outline-secondary">
      <i class="bi bi-x-lg me-1"></i>Batal
    </a>
    <button type="submit" class="btn btn-success fw-bold" id="btnSimpan" <?= $ada_customer ? '' : 'disabled'; ?>>
      <i class="bi bi-check2-circle me-1"></i>Simpan Penawaran
    </button>
  </div>

  <?= form_close(); ?>
</div>

<template id="templateItem">
  <div class="item-card">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <span class="item-title">Item <span class="item-no">1</span></span>
      <button type="button" class="btn btn-sm btn-remove-item" title="Hapus item"><i class="bi bi-trash3"></i></button>
    </div>
    <div class="row g-2">
      <div class="col-md-4">
        <label class="form-label">Jenis Item <span class="text-danger">*</span></label>
        <input type="text" name="jenis_item[]" class="form-control it-jenis" placeholder="Mis. Pintu Kayu" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Warna</label>
        <input type="text" name="warna[]" class="form-control" placeholder="Mis. Coklat">
      </div>
      <div class="col-md-4">
        <label class="form-label">Finishing</label>
        <input type="text" name="finishing[]" class="form-control" placeholder="Mis. Powder Coating">
      </div>

      <div class="col-6 col-md-2">
        <label class="form-label">Lebar (mm)</label>
        <input type="number" name="lebar_mm[]" min="0" class="form-control it-lebar" placeholder="0">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label">Tinggi (mm)</label>
        <input type="number" name="tinggi_mm[]" min="0" class="form-control it-tinggi" placeholder="0">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label">Luas (m²)</label>
        <input type="text" class="form-control it-luas" value="0.0000" readonly>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label">Qty <span class="text-danger">*</span></label>
        <input type="number" name="qty[]" min="1" value="1" class="form-control it-qty" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Harga Satuan (Rp) <span class="text-danger">*</span></label>
        <input type="number" name="harga_unit[]" min="0" step="1" class="form-control it-harga" placeholder="0" required>
      </div>

      <div class="col-md-3">
        <label class="form-label">Plate Kusen (mm)</label>
        <input type="text" name="komponen_kusen[]" class="form-control" placeholder="Mis. 1,0">
      </div>
      <div class="col-md-3">
        <label class="form-label">Plate Daun (mm)</label>
        <input type="text" name="komponen_daun[]" class="form-control" placeholder="Mis. 1,0">
      </div>
      <div class="col-md-6 text-end">
        <label class="form-label d-block">Subtotal Item</label>
        <span class="item-subtotal">Rp 0</span>
      </div>

      <div class="col-12">
        <label class="form-label">Keterangan (opsional)</label>
        <textarea name="keterangan[]" class="form-control" rows="1" placeholder="Aksesoris, spesifikasi kaca, catatan khusus, dll."></textarea>
      </div>
    </div>
  </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var wrap        = document.getElementById('wrapItem');
  var tpl         = document.getElementById('templateItem');
  var btnTambah   = document.getElementById('btnTambahItem');
  var form        = document.getElementById('formPenawaran');
  var TARIF_PPN   = <?= (float) $tarif_ppn; ?>;
  var TARIF_PPH   = <?= (float) $tarif_pph; ?>;

  function rupiah(n) {
    return 'Rp ' + Math.round(n || 0).toLocaleString('id-ID');
  }

  function baris() {
    return Array.prototype.slice.call(wrap.querySelectorAll('.item-card'));
  }

  function tambahItem() {
    var node = tpl.content.cloneNode(true);
    wrap.appendChild(node);
    segarkan();
  }

  function hitungBaris(card) {
    var lebar  = parseFloat(card.querySelector('.it-lebar').value) || 0;
    var tinggi = parseFloat(card.querySelector('.it-tinggi').value) || 0;
    var qty    = parseFloat(card.querySelector('.it-qty').value) || 0;
    var harga  = parseFloat(card.querySelector('.it-harga').value) || 0;

    var luas  = (lebar * tinggi) / 1000000;
    var total = qty * harga;

    card.querySelector('.it-luas').value = luas.toFixed(4);
    card.querySelector('.item-subtotal').textContent = rupiah(total);

    return total;
  }

  function segarkan() {
    var rows = baris();
    var subtotal = 0;

    rows.forEach(function (card, i) {
      card.querySelector('.item-no').textContent = i + 1;
      var btnHapus = card.querySelector('.btn-remove-item');
      btnHapus.disabled = (rows.length === 1);
      subtotal += hitungBaris(card);
    });

    var ppn   = Math.round(subtotal * TARIF_PPN / 100);
    var pph   = Math.round(subtotal * TARIF_PPH / 100);
    var grand = subtotal + ppn + pph;

    document.getElementById('sumJumlahItem').textContent = rows.length;
    document.getElementById('sumSubtotal').textContent   = rupiah(subtotal);
    document.getElementById('sumPpn').textContent        = rupiah(ppn);
    document.getElementById('sumPph').textContent        = rupiah(pph);
    document.getElementById('sumGrandTotal').textContent = rupiah(grand);
  }

  btnTambah.addEventListener('click', tambahItem);

  wrap.addEventListener('input', function (e) {
    if (e.target.closest('.item-card')) segarkan();
  });

  wrap.addEventListener('click', function (e) {
    var btnHapus = e.target.closest('.btn-remove-item');
    if (btnHapus && !btnHapus.disabled) {
      btnHapus.closest('.item-card').remove();
      segarkan();
    }
  });

  form.addEventListener('submit', function (e) {
    var jenisTerisi = baris().some(function (card) {
      return card.querySelector('.it-jenis').value.trim() !== '';
    });
    if (!jenisTerisi) {
      e.preventDefault();
      alert('Minimal harus ada 1 item dengan Jenis Item diisi.');
      return;
    }
    if (!confirm('Simpan penawaran ini?')) {
      e.preventDefault();
    }
  });

  // Mulai dengan satu item kosong
  tambahItem();
});
</script>