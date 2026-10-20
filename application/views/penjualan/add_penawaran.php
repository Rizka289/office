<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$tarif_ppn = isset($tarif_ppn) ? $tarif_ppn : 11;
$tarif_pph = isset($tarif_pph) ? $tarif_pph : 2.5;
$list_customer = isset($list_customer) ? (array) $list_customer : [];
$list_barang   = isset($list_barang) ? (array) $list_barang : [];
$ada_customer  = !empty($list_customer);
$tanggal_maks  = date('Y-m-d');

$is_edit     = (isset($mode) && $mode === 'edit' && !empty($penawaran_edit));
$pen         = $is_edit ? $penawaran_edit : null;
$tanggal_val = $is_edit ? substr((string) $pen->tanggal, 0, 10) : $tanggal_maks;
$form_action = $is_edit ? 'penawaran/update/' . (int) $pen->id : 'penawaran/simpan';
$hdr_lock    = ($ada_customer && !$is_edit) ? '' : 'disabled';
$pajak_lock  = $ada_customer ? '' : 'disabled';
$url_batal   = $is_edit ? site_url('penawaran/detail/' . (int) $pen->id) : site_url('penawaran');

$biaya_pasang_db = $is_edit ? ($pen->biaya_pasang ?? null) : null;
$biaya_ongkir_db = $is_edit ? ($pen->biaya_ongkir ?? null) : null;
$pasang_on  = ($biaya_pasang_db !== null);
$ongkir_on  = ($biaya_ongkir_db !== null);
$pasang_val = $pasang_on ? (string) round((float) $biaya_pasang_db) : '';
$ongkir_val = $ongkir_on ? (string) round((float) $biaya_ongkir_db) : '';

$items_js = [];
if ($is_edit && !empty($items_edit)) {
  foreach ($items_edit as $it) {
    $it = (array) $it;
    $items_js[] = [
      'id_barang'          => (int) ($it['id_barang'] ?? 0),
      'jenis_item'         => $it['jenis_item'] ?? '',
      'warna'              => $it['warna'] ?? '',
      'komponen'           => $it['komponen'] ?? '',
      'finishing'          => $it['finishing'] ?? '',
      'komponen_kusen'     => $it['komponen_kusen'] ?? '',
      'komponen_daun'      => $it['komponen_daun'] ?? '',
      'lebar_mm'           => (int) ($it['lebar_mm'] ?? 0),
      'tinggi_mm'          => (int) ($it['tinggi_mm'] ?? 0),
      'qty'                => (int) ($it['qty'] ?? 1),
      'harga_unit'         => (float) ($it['harga_unit'] ?? 0),
      'keterangan'         => $it['keterangan'] ?? '',
      'tipe_motor'         => $it['tipe_motor'] ?? '',
      'input_power'        => $it['input_power'] ?? '',
      'voltage'            => $it['voltage'] ?? '',
      'books_penutup'      => $it['books_penutup'] ?? '',
      'lebar_m2_books'     => $it['lebar_m2_books'] ?? '',
      'tinggi_m2_books'    => $it['tinggi_m2_books'] ?? '',
      'qty_books'          => $it['qty_books'] ?? '',
      'qty_instalasi'          => $it['qty_instalasi'] ?? '',
      'harga_m2_books'     => $it['harga_m2_books'] ?? '',
      'harga_m2_instalasi' => $it['harga_m2_instalasi'] ?? '',
      'kelengkapan'        => $it['kelengkapan'] ?? '',
      'aksesoris'          => $it['aksesoris'] ?? [],
      'kaca'               => $it['kaca'] ?? [],
    ];
  }
}
?>

<style>
  .pnw-page {
    --pnw-brand: #2f6f4f;
    --pnw-brand-dark: #234f38;
    --pnw-radius: 14px;
  }
  .pnw-page .page-header {
    background: linear-gradient(135deg, var(--pnw-brand), var(--pnw-brand-dark));
    color: #fff;
    padding: 22px 28px;
    border-radius: var(--pnw-radius);
    margin-bottom: 22px;
    box-shadow: 0 6px 18px rgba(35, 79, 56, .25);
  }
  .pnw-page .page-header h4 { margin: 0; font-weight: 600; }
  .pnw-page .page-header small { opacity: .85; }
  .pnw-page .card-modern {
    background: #fff;
    border: none;
    border-radius: var(--pnw-radius);
    box-shadow: 0 2px 10px rgba(0, 0, 0, .06);
    margin-bottom: 20px;
  }
  .pnw-page .card-modern .card-header {
    background: #fff;
    border-bottom: 1px solid #eef1ef;
    border-radius: var(--pnw-radius) var(--pnw-radius) 0 0;
    font-weight: 600;
    color: var(--pnw-brand-dark);
    padding: 14px 20px;
  }
  .pnw-page .card-modern .card-body { padding: 20px; }
  .pnw-page .form-label { font-weight: 500; font-size: .84rem; color: #4a4a4a; }
  .pnw-page .form-control[readonly] { background: #f3f6f4; color: #5a5a5a; }
  .pnw-page .form-control:focus, .pnw-page .form-select:focus {
    border-color: var(--pnw-brand);
    box-shadow: 0 0 0 .2rem rgba(47, 111, 79, .15);
  }
  .pnw-page .item-card {
    border: 1px solid #eef1ef;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 14px;
    position: relative;
    background: #fbfdfc;
  }
  .pnw-page .item-card .item-title { font-weight: 700; color: var(--pnw-brand-dark); font-size: .88rem; }
  .pnw-page .item-card .item-subtotal { font-weight: 700; color: var(--pnw-brand-dark); }
  .pnw-page .btn-remove-item { color: #b3261e; }
  .pnw-page .btn-remove-item:hover { color: #7a1a15; }
  .pnw-page .btn-remove-item:disabled { color: #c9c9c9; }
  .pnw-page .barang-picker { position: relative; }
  .pnw-page .it-books-penutup, .pnw-page .it-kelengkapan { min-height: 130px; resize: vertical; }
  .pnw-page .it-jenis.is-locked { background: #eef6f1; color: var(--pnw-brand-dark); font-weight: 600; cursor: not-allowed; }
  .pnw-page .barang-dropdown-list {
    position: absolute; top: 100%; left: 0; right: 0; z-index: 20;
    background: #fff; border: 1px solid #dfe6e2; border-radius: 8px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, .12); max-height: 220px; overflow-y: auto; margin-top: 4px;
  }
  .pnw-page .barang-dropdown-list .barang-opt { padding: 8px 12px; cursor: pointer; font-size: .85rem; border-bottom: 1px solid #f2f4f3; }
  .pnw-page .barang-dropdown-list .barang-opt:hover { background: #eef6f1; }
  .pnw-page .barang-dropdown-list .barang-opt .bo-nama { font-weight: 600; color: #26332c; display: block; }
  .pnw-page .barang-dropdown-list .barang-opt .bo-meta { color: #7a8a80; font-size: .76rem; }
  .pnw-page .customer-picker { position: relative; }
  .pnw-page .customer-picker .form-control { padding-right: 34px; }
  .pnw-page .customer-picker .customer-caret { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #7a8a80; font-size: .8rem; pointer-events: none; }
  .pnw-page .customer-dropdown-list {
    position: absolute; top: 100%; left: 0; right: 0; z-index: 30;
    background: #fff; border: 1px solid #dfe6e2; border-radius: 8px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, .12); max-height: 240px; overflow-y: auto; margin-top: 4px;
  }
  .pnw-page .customer-dropdown-list .customer-opt { padding: 8px 12px; cursor: pointer; font-size: .88rem; font-weight: 600; color: #26332c; border-bottom: 1px solid #f2f4f3; }
  .pnw-page .customer-dropdown-list .customer-opt:hover { background: #eef6f1; }
  .pnw-page .summary-box { background: #f8faf9; border: 1px dashed #cfe0d6; border-radius: 10px; padding: 16px 18px; }
  .pnw-page .total-box { background: #f8faf9; border-left: 4px solid var(--pnw-brand); border-radius: 10px; padding: 16px 18px; }
  .pnw-page .footer-actions {
    position: sticky; bottom: 0; background: #fff; padding: 14px 20px;
    border-radius: var(--pnw-radius); box-shadow: 0 -4px 14px rgba(0, 0, 0, .06);
    display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap; z-index: 5;
  }
</style>

<div class="pnw-page">

  <script id="dataCustomer" type="application/json">
    <?= json_encode(array_map(function ($c) {
      return ['id' => (int) $c['id'], 'nama' => (string) $c['nama']];
    }, $list_customer), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>
  </script>

  <script id="dataBarang" type="application/json">
    <?= json_encode($list_barang, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>
  </script>

  <div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <h4><i class="bi bi-file-earmark-text me-2"></i><?= $is_edit ? translate('inf_edit_penawaran') : translate('info_add_penawaran'); ?></h4>
      <small><?= $is_edit ? 'Penawaran belum di-approve: item, biaya pemasangan/pengiriman, dan pajak masih bisa diubah.' : 'Isi data customer dan item barang yang ditawarkan.'; ?></small>
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

  <?= form_open($form_action, ['id' => 'formPenawaran', 'autocomplete' => 'off']); ?>

  <div class="card-modern">
    <div class="card-header"><i class="bi bi-info-circle me-2"></i><?= translate('informasi_penawaran') ?></div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">No. Surat</label>
          <input type="text" name="no_surat" class="form-control" value="<?= html_escape($no_surat); ?>" readonly>
        </div>
        <div class="col-md-3">
          <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
          <input type="date" id="tanggal" name="tanggal" class="form-control" value="<?= html_escape($tanggal_val); ?>" required <?= $hdr_lock; ?>>
        </div>
        <div class="col-md-6">
          <label for="customer_search" class="form-label">Customer <span class="text-danger">*</span></label>
          <?php
          $cust_id_val   = $is_edit ? (int) $pen->id_customer : 0;
          $cust_nama_val = '';
          foreach ($list_customer as $c) {
            if ((int) $c['id'] === $cust_id_val) {
              $cust_nama_val = $c['nama'];
              break;
            }
          }
          ?>
          <div class="customer-picker">
            <input type="hidden" name="id_customer" id="id_customer" value="<?= $cust_id_val > 0 ? $cust_id_val : ''; ?>" <?= $hdr_lock; ?>>
            <input type="text" id="customer_search" class="form-control" placeholder="Ketik nama customer atau pilih dari daftar..."
              autocomplete="off" value="<?= html_escape($cust_nama_val); ?>" <?= $hdr_lock; ?>>
            <i class="bi bi-chevron-down customer-caret"></i>
            <div class="customer-dropdown-list d-none"></div>
          </div>
        </div>
        <div class="col-12">
          <label for="catatan" class="form-label">Catatan (opsional)</label>
          <textarea id="catatan" name="catatan" class="form-control" rows="2" placeholder="Mis. estimasi pengerjaan, syarat pembayaran khusus, dll." <?= $hdr_lock; ?>><?= $is_edit ? html_escape($pen->catatan) : ''; ?></textarea>
        </div>
      </div>
    </div>
  </div>

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

  <div class="card-modern">
    <div class="card-header"><i class="bi bi-truck me-2"></i>Biaya Pemasangan &amp; Pengiriman Global</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" role="switch" id="pakai_pasang" name="pakai_pasang" value="1" <?= $pasang_on ? 'checked' : ''; ?> <?= $pajak_lock; ?>>
            <label class="form-check-label" for="pakai_pasang">Sudah termasuk biaya pemasangan umum</label>
          </div>
          <div id="wrapPasang" class="<?= $pasang_on ? '' : 'd-none'; ?>">
            <label for="biaya_pasang" class="form-label">Nominal biaya pemasangan (Rp)</label>
            <input type="number" min="0" step="1" id="biaya_pasang" name="biaya_pasang" class="form-control" placeholder="0" value="<?= html_escape($pasang_val); ?>" <?= $pasang_on ? '' : 'disabled'; ?>>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" role="switch" id="pakai_ongkir" name="pakai_ongkir" value="1" <?= $ongkir_on ? 'checked' : ''; ?> <?= $pajak_lock; ?>>
            <label class="form-check-label" for="pakai_ongkir">Sudah termasuk biaya pengiriman (ongkir)</label>
          </div>
          <div id="wrapOngkir" class="<?= $ongkir_on ? '' : 'd-none'; ?>">
            <label for="biaya_ongkir" class="form-label">Nominal biaya pengiriman (Rp)</label>
            <input type="number" min="0" step="1" id="biaya_ongkir" name="biaya_ongkir" class="form-control" placeholder="0" value="<?= html_escape($ongkir_val); ?>" <?= $ongkir_on ? '' : 'disabled'; ?>>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="card-modern h-100 mb-0">
        <div class="card-header"><i class="bi bi-percent me-2"></i>Pajak</div>
        <div class="card-body">
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch" id="pakai_ppn" name="pakai_ppn" value="1" <?= ($is_edit && (float) $pen->ppn_persen > 0) ? 'checked' : ''; ?> <?= $pajak_lock; ?>>
            <label class="form-check-label" for="pakai_ppn">Kenakan PPN <?= html_escape($tarif_ppn); ?>%</label>
          </div>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch" id="pakai_pph" name="pakai_pph" value="1" <?= ($is_edit && (float) $pen->pph_persen > 0) ? 'checked' : ''; ?> <?= $pajak_lock; ?>>
            <label class="form-check-label" for="pakai_pph">Kenakan PPh <?= html_escape($tarif_pph); ?>%</label>
          </div>
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
    <a href="<?= $url_batal; ?>" class="btn btn-outline-secondary">
      <i class="bi bi-x-lg me-1"></i>Batal
    </a>
    <button type="submit" class="btn btn-success fw-bold" id="btnSimpan" <?= $ada_customer ? '' : 'disabled'; ?>>
      <i class="bi bi-check2-circle me-1"></i><?= $is_edit ? 'Simpan Perubahan' : 'Simpan Penawaran'; ?>
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
        <label class="form-label">Barang <span class="text-danger">*</span></label>
        <div class="barang-picker">
          <input type="hidden" name="id_barang[]" class="it-id-barang" value="">
          <input type="text" name="jenis_item[]" class="form-control it-jenis it-barang-search"
            placeholder="Ketik nama/kode barang..." autocomplete="off" required>
          <div class="barang-dropdown-list d-none"></div>
        </div>
        <button type="button" class="btn btn-link btn-ganti-barang p-0 mt-1 d-none">
          <i class="bi bi-arrow-repeat me-1"></i>Ganti barang
        </button>
      </div>
      <div class="col-md-4">
        <label class="form-label">Warna</label>
        <input type="text" name="warna[]" class="form-control it-warna" placeholder="Mis. Coklat">
      </div>
      <div class="col-md-4">
        <label class="form-label">Finishing</label>
        <input type="text" name="finishing[]" class="form-control it-finishing" placeholder="Powder Coating / 粉末涂料">
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
      <div class="col-md-2">
        <label class="form-label">Komponen</label>
        <input type="text" name="komponen[]" class="form-control it-komponen" placeholder="Otomatis dari data barang">
      </div>

      <div class="col-md-2">
        <label class="form-label">Kusen (mm)</label>
        <input type="text" name="komponen_kusen[]" class="form-control" placeholder="Mis. 1,0">
      </div>
      <div class="col-md-2">
        <label class="form-label">Daun (mm)</label>
        <input type="text" name="komponen_daun[]" class="form-control" placeholder="Mis. 1,0">
      </div>
      <div class="col-md-2">
        <label class="form-label">Tipe Motor</label>
        <input type="text" name="tipe_motor[]" class="form-control it-tipe-motor" placeholder="Mis. Electra 600">
      </div>
      <div class="col-md-2">
        <label class="form-label">Input Power</label>
        <input type="text" name="input_power[]" class="form-control it-input-power" placeholder="Mis. 250 W">
      </div>

      <div class="col-md-2">
        <label class="form-label">Voltage</label>
        <input type="text" name="voltage[]" class="form-control it-voltage" placeholder="Mis. 220 V - 50 Hz">
      </div>
      <div class="col-md-6">
        <label class="form-label">Books Penutup</label>
        <textarea name="books_penutup[]" class="form-control it-books-penutup" rows="5" placeholder="Tulis spesifikasi books penutup"></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Kelengkapan</label>
        <textarea name="kelengkapan[]" class="form-control it-kelengkapan" rows="5" placeholder="Tulis kelengkapan"></textarea>
      </div>

      <!-- Books Penutup (khusus rolling door) -->
      <div class="col-12 books-blok d-none"><div class="fw-bold text-success small">Ukuran &amp; Harga Books Penutup</div></div>
      <div class="col-md-3 books-blok d-none">
        <label class="form-label">Lebar (mm)</label>
        <input type="number" min="0" step="1" name="lebar_m2_books[]" class="form-control it-books-lebar" placeholder="Mis. 2000">
      </div>
      <div class="col-md-3 books-blok d-none">
        <label class="form-label">Tinggi (mm)</label>
        <input type="number" min="0" step="1" name="tinggi_m2_books[]" class="form-control it-books-tinggi" placeholder="Mis. 600">
      </div>
      <div class="col-md-3 books-blok d-none">
        <label class="form-label">Luas (m²)</label>
        <input type="text" class="form-control it-books-luas" value="0.0000" readonly tabindex="-1">
      </div>
      <div class="col-md-3 books-blok d-none">
        <label class="form-label">Qty</label>
        <input type="number" min="0" step="1" name="qty_books[]" class="form-control it-books-qty" placeholder="Mis. 1">
      </div>
      <div class="col-md-3 books-blok d-none">
        <label class="form-label">Harga (Rp / m²)</label>
        <input type="number" min="0" step="any" name="harga_m2_books[]" class="form-control it-books-harga" placeholder="Mis. 500000">
      </div>
      <div class="col-md-3 books-blok d-none">
        <label class="form-label">Total Books</label>
        <input type="text" class="form-control it-books-total" value="Rp 0" readonly tabindex="-1">
      </div>

      <!-- Biaya Instalasi Rolling Door (Luas x Qty x Harga Pasang per m²) -->
      <div class="col-12 instalasi-blok d-none"><div class="fw-bold text-success small">Biaya Instalasi / Pemasangan Rolling Door</div></div>
      <div class="col-md-4 instalasi-blok d-none">
        <label class="form-label">Qty Instalasi</label>
        <input type="number" min="0" step="1" name="qty_instalasi[]" class="form-control it-instalasi-qty" placeholder="Mis. 1">
      </div>
      <div class="col-md-4 instalasi-blok d-none">
        <label class="form-label">Tarif Pasang (Rp / m²)</label>
        <input type="number" min="0" step="any" name="harga_m2_instalasi[]" class="form-control it-instalasi-harga" placeholder="Mis. 50000">
      </div>
      <div class="col-md-4 instalasi-blok d-none">
        <label class="form-label">Total Biaya Instalasi</label>
        <input type="text" class="form-control it-instalasi-total" value="Rp 0" readonly tabindex="-1">
      </div>

      <!-- Biaya untuk kaca (Luas x Harga Pasang per m²) -->
     <div class="col-12 mt-2 wrap-kaca-container">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <label class="form-label fw-bold text-success m-0"><i class="bi bi-plus-circle me-1"></i>Biaya Detail Kaca</label>
          <button type="button" class="btn btn-xs btn-outline-success btn-tambah-kaca"><i class="bi bi-plus"></i> Tambah Detail Kaca</button>
        </div>
        <div class="list-kaca"></div>
      </div>

      <!-- Aksesoris Tambahan -->
      <div class="col-12 mt-2 wrap-aksesoris-container">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <label class="form-label fw-bold text-success m-0"><i class="bi bi-plus-circle me-1"></i>Aksesoris Tambahan</label>
          <button type="button" class="btn btn-xs btn-outline-success btn-tambah-aksesoris"><i class="bi bi-plus"></i> Tambah Aksesoris</button>
        </div>
        <div class="list-aksesoris"></div>
      </div>

      <div class="col-12">
        <label class="form-label">Keterangan (opsional)</label>
        <textarea name="keterangan[]" class="form-control" rows="1" placeholder="Aksesoris, spesifikasi kaca, catatan khusus, dll."></textarea>
      </div>

      <div class="col-12 text-end">
        <label class="form-label d-block">Subtotal Item</label>
        <span class="item-subtotal">Rp 0</span>
      </div>
    </div>
  </div>
</template>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var wrap = document.getElementById('wrapItem');
    var tpl = document.getElementById('templateItem');
    var btnTambah = document.getElementById('btnTambahItem');
    var form = document.getElementById('formPenawaran');
    var chkPpn = document.getElementById('pakai_ppn');
    var chkPph = document.getElementById('pakai_pph');
    var chkPasang = document.getElementById('pakai_pasang');
    var chkOngkir = document.getElementById('pakai_ongkir');
    var inpPasang = document.getElementById('biaya_pasang');
    var inpOngkir = document.getElementById('biaya_ongkir');
    var wrapPasang = document.getElementById('wrapPasang');
    var wrapOngkir = document.getElementById('wrapOngkir');
    var TARIF_PPN = <?= (float) $tarif_ppn; ?>;
    var TARIF_PPH = <?= (float) $tarif_pph; ?>;

    var ITEMS_EDIT = <?= json_encode($items_js, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
    var DATA_BARANG = [];
    try {
      DATA_BARANG = JSON.parse(document.getElementById('dataBarang').textContent || '[]') || [];
    } catch (e) {
      DATA_BARANG = [];
    }

    function rupiah(n) {
      return 'Rp ' + Math.round(n || 0).toLocaleString('id-ID');
    }

    function nominalBiaya(chk, inp) {
      if (!chk.checked) return 0;
      return Math.max(0, parseFloat(inp.value) || 0);
    }

    function toggleBiaya(chk, wrapEl, inp) {
      wrapEl.classList.toggle('d-none', !chk.checked);
      inp.disabled = !chk.checked;
    }

    function baris() {
      return Array.prototype.slice.call(wrap.querySelectorAll('.item-card'));
    }

    function templateBarisAksoris(itemIndex) {
      return `
      <div class="row g-2 align-items-center mb-1 baris-aksesoris">
        <div class="col-md-5">
          <input type="text" name="aks_nama[${itemIndex}][]" class="form-control form-control-sm aks-nama" placeholder="Nama Aksesoris">
        </div>
        <div class="col-md-2">
          <input type="number" name="aks_qty[${itemIndex}][]" min="1" value="1" class="form-control form-control-sm aks-qty" placeholder="Qty">
        </div>
        <div class="col-md-4">
          <input type="number" name="aks_harga[${itemIndex}][]" min="0" step="1" value="0" class="form-control form-control-sm aks-harga" placeholder="Harga">
        </div>
        <div class="col-md-1 text-end">
          <button type="button" class="btn btn-sm btn-link text-danger btn-hapus-aksesoris"><i class="bi bi-x-circle"></i></button>
        </div>
      </div>`;
    }
    function templateBarisKaca(itemIndex) {
      return `
      <div class="row g-2 align-items-center mb-1 baris-kaca">
        <div class="col-md-4">
          <input type="text" name="kaca_nama[${itemIndex}][]" class="form-control form-control-sm kaca-nama" placeholder="Nama Detail Kaca">
        </div>
        <div class="col-md-2">
          <input type="text" inputmode="decimal" name="kaca_luas[${itemIndex}][]" value="1" class="form-control form-control-sm kaca-luas" placeholder="Luas m² (mis. 1,25)">
        </div>
        <div class="col-md-3">
          <input type="number" name="kaca_harga[${itemIndex}][]" min="0" step="any" value="0" class="form-control form-control-sm kaca-harga" placeholder="Harga / m²">
        </div>
        <div class="col-md-2">
          <input type="text" class="form-control form-control-sm kaca-total" value="Rp 0" readonly tabindex="-1">
        </div>
        <div class="col-md-1 text-end">
          <button type="button" class="btn btn-sm btn-link text-danger btn-hapus-kaca"><i class="bi bi-x-circle"></i></button>
        </div>
      </div>`;
    }

    // Parser angka: menerima "1,25" maupun "1.25" (juga "1.234,56")
    function toNum(v) {
      v = String(v === null || v === undefined ? '' : v).trim().replace(/\s/g, '');
      if (v.indexOf(',') !== -1) v = v.replace(/\./g, '').replace(',', '.');
      return parseFloat(v) || 0;
    }

    function isiItem(card, d, index) {
      function set(sel, v) {
        var el = card.querySelector(sel);
        if (el) el.value = (v === null || v === undefined) ? '' : v;
      }
      set('.it-jenis', d.jenis_item);
      set('.it-id-barang', d.id_barang > 0 ? d.id_barang : '');
      set('.it-warna', d.warna);
      set('.it-komponen', d.komponen);
      set('[name="komponen_kusen[]"]', d.komponen_kusen);
      set('[name="komponen_daun[]"]', d.komponen_daun);
      set('.it-lebar', d.lebar_mm);
      set('.it-tinggi', d.tinggi_mm);
      set('.it-qty', d.qty);
      set('.it-harga', d.harga_unit);
      set('[name="keterangan[]"]', d.keterangan);
      set('[name="tipe_motor[]"]', d.tipe_motor);
      set('[name="input_power[]"]', d.input_power);
      set('[name="voltage[]"]', d.voltage);
      set('[name="books_penutup[]"]', d.books_penutup);
      set('.it-books-lebar', (parseInt(d.lebar_m2_books, 10) || ''));
      set('.it-books-tinggi', (parseInt(d.tinggi_m2_books, 10) || ''));
      set('.it-books-qty', (parseInt(d.qty_books, 10) || ''));
      set('.it-books-harga', (parseFloat(d.harga_m2_books) || ''));
      set('.it-instalasi-qty', (parseFloat(d.qty_instalasi) || ''));
      set('.it-instalasi-harga', (parseFloat(d.harga_m2_instalasi) || ''));
      set('[name="kelengkapan[]"]', d.kelengkapan);

      var finValue = (d && d.finishing && d.finishing.trim() !== '') ? d.finishing : 'Powder Coating / 粉末涂料';
      set('.it-finishing', finValue);

      if (d.id_barang > 0) {
        var cari = card.querySelector('.it-barang-search');
        cari.readOnly = true;
        cari.classList.add('is-locked');
        card.querySelector('.btn-ganti-barang').classList.remove('d-none');
      }

      if (d.aksesoris && Array.isArray(d.aksesoris)) {
        var listAks = card.querySelector('.list-aksesoris');
        d.aksesoris.forEach(function(ak) {
          listAks.insertAdjacentHTML('beforeend', templateBarisAksoris(index));
          var lastRow = listAks.lastElementChild;
          lastRow.querySelector('.aks-nama').value = ak.nama || '';
          lastRow.querySelector('.aks-qty').value = ak.qty || 1;
          lastRow.querySelector('.aks-harga').value = ak.harga || 0;
        });
      }

      if (d.kaca && Array.isArray(d.kaca)) {
        var listKc = card.querySelector('.list-kaca');
        d.kaca.forEach(function(kc) {
          listKc.insertAdjacentHTML('beforeend', templateBarisKaca(index));
          var lastRow = listKc.lastElementChild;
          lastRow.querySelector('.kaca-nama').value = kc.nama || '';
          lastRow.querySelector('.kaca-luas').value = String(parseFloat(kc.luas) || 1).replace('.', ',');
          lastRow.querySelector('.kaca-harga').value = parseFloat(kc.harga) || 0;
        });
      }
    }

    function reindexAksesoris() {
      baris().forEach(function(card, idx) {
        card.querySelectorAll('.baris-aksesoris').forEach(function(row) {
          row.querySelector('.aks-nama').name = `aks_nama[${idx}][]`;
          row.querySelector('.aks-qty').name = `aks_qty[${idx}][]`;
          row.querySelector('.aks-harga').name = `aks_harga[${idx}][]`;
        });
      });
    }
    function reindexKaca() {
      baris().forEach(function(card, idx) {
        card.querySelectorAll('.baris-kaca').forEach(function(row) {
          row.querySelector('.kaca-nama').name = `kaca_nama[${idx}][]`;
          row.querySelector('.kaca-luas').name = `kaca_luas[${idx}][]`;
          row.querySelector('.kaca-harga').name = `kaca_harga[${idx}][]`;
        });
      });
    }

    function tambahItem(data) {
      var idx = baris().length;
      var node = tpl.content.cloneNode(true);
      wrap.appendChild(node);
      var newCard = wrap.lastElementChild;
      if (data) isiItem(newCard, data, idx);
      reindexAksesoris();
      reindexKaca();
      segarkan();
    }

    function formatHargaBarang(n) {
      return Math.round(n || 0).toLocaleString('id-ID');
    }

    function cariBarang(kata) {
      kata = (kata || '').trim().toLowerCase();
      if (kata === '') return [];
      return DATA_BARANG.filter(function(b) {
        return (b.nama || '').toLowerCase().indexOf(kata) !== -1 ||
          (b.kode_barang || '').toLowerCase().indexOf(kata) !== -1;
      }).slice(0, 15);
    }

    function tutupSemuaDropdown() {
      wrap.querySelectorAll('.barang-dropdown-list').forEach(function(el) {
        el.classList.add('d-none');
        el.innerHTML = '';
      });
    }

    function tampilkanDropdown(card, hasil) {
      var list = card.querySelector('.barang-dropdown-list');
      if (!hasil.length) {
        list.innerHTML = '<div class="barang-empty">Barang tidak ditemukan.</div>';
        list.classList.remove('d-none');
        return;
      }
      list.innerHTML = hasil.map(function(b) {
        return '<div class="barang-opt" data-id="' + b.id + '">' +
          '<span class="bo-nama"></span><span class="bo-meta"></span></div>';
      }).join('');
      var opts = list.querySelectorAll('.barang-opt');
      opts.forEach(function(el, i) {
        var b = hasil[i];
        el.querySelector('.bo-nama').textContent = b.nama;
        el.querySelector('.bo-meta').textContent = (b.kode_barang || '') + ' • Rp ' + formatHargaBarang(b.harga_satuan) + (b.warna ? ' • ' + b.warna : '');
      });
      list.classList.remove('d-none');
    }

    function pilihBarang(card, idBarang) {
      var b = DATA_BARANG.find(function(x) {
        return String(x.id) === String(idBarang);
      });
      if (!b) return;

      var inputCari = card.querySelector('.it-barang-search');
      var inputId = card.querySelector('.it-id-barang');
      
      var elWarna = card.querySelector('.it-warna');
      if (elWarna) elWarna.value = b.warna || '';
      var elHarga = card.querySelector('.it-harga');
      if (elHarga) elHarga.value = parseFloat(b.harga_satuan) || 0;
      var elKomponen = card.querySelector('.it-komponen');
      if (elKomponen) elKomponen.value = b.komponen || '';
      var elKet = card.querySelector('.it-keterangan');
      if (elKet) elKet.value = b.keterangan || '';

      if (inputCari) {
        inputCari.value = b.nama;
        inputCari.readOnly = true;
        inputCari.classList.add('is-locked');
      }
      if (inputId) inputId.value = b.id;

      var btnGanti = card.querySelector('.btn-ganti-barang');
      if (btnGanti) btnGanti.classList.remove('d-none');

      tutupSemuaDropdown();
      segarkan();
    }

    function gantiBarang(card) {
      var inputCari = card.querySelector('.it-barang-search');
      card.querySelector('.it-id-barang').value = '';
      card.querySelector('.it-komponen').value = '';
      inputCari.readOnly = false;
      inputCari.classList.remove('is-locked');
      inputCari.value = '';
      card.querySelector('.btn-ganti-barang').classList.add('d-none');
      inputCari.focus();
    }

    function kategoriBaris(card) {
      var id = card.querySelector('.it-id-barang').value;
      var b = DATA_BARANG.find(function(x) {
        return String(x.id) === String(id);
      });
      return (b && b.kategori_rumus) ? b.kategori_rumus : 'unit_fix';
    }

    function isRollingDoor(kategori) {
      return /^ro{1,2}l{1,2}ing_(door_)?(tahan_api|baja)$/.test(String(kategori || ''));
    }

    function luasBilling(kategori, luasRiil, lebarMm, tinggiMm) {
      var luas = luasRiil;
      if (kategori === 'jendela_sliding') {
        luas = Math.max(luasRiil, 1.3);
      } else if (kategori === 'jendela_swing_b1' || kategori === 'jendela_swing_b2') {
        luas = Math.max(luasRiil, 1.4);
        if (luas > 3 && luas < 5) luas += 1.3;
      } else if (kategori === 'pintu_baja') {
        luas = Math.max(luasRiil, 2);
      } else if (isRollingDoor(kategori)) {
        luas = Math.max(Math.round(((lebarMm + 200) / 1000) * ((tinggiMm + 600) / 1000) * 10000) / 10000, 10);
      }
      return luas;
    }

    function hitungBaris(card) {
      var lebar = parseFloat(card.querySelector('.it-lebar').value) || 0;
      var tinggi = parseFloat(card.querySelector('.it-tinggi').value) || 0;
      var qty = parseFloat(card.querySelector('.it-qty').value) || 0;
      var harga = parseFloat(card.querySelector('.it-harga').value) || 0;

      var kategori = kategoriBaris(card);
      var luasRiil = Math.round((lebar / 1000) * (tinggi / 1000) * 10000) / 10000;
      var luas = luasBilling(kategori, luasRiil, lebar, tinggi);

      var inpLuas = card.querySelector('.it-luas');
      if (inpLuas) inpLuas.value = luas.toFixed(4);

      var totalAksesoris = 0;
      card.querySelectorAll('.baris-aksesoris').forEach(function(row) {
        var qAks = parseFloat(row.querySelector('.aks-qty').value) || 0;
        var hAks = parseFloat(row.querySelector('.aks-harga').value) || 0;
        totalAksesoris += (qAks * hAks);
      });

      // Detail Kaca: Luas (boleh koma) x Harga per m²
      var totalKaca = 0;
      card.querySelectorAll('.baris-kaca').forEach(function(row) {
        var lKaca = toNum(row.querySelector('.kaca-luas').value);
        var hKaca = parseFloat(row.querySelector('.kaca-harga').value) || 0;
        var tKaca = lKaca * hKaca;
        var elKTotal = row.querySelector('.kaca-total');
        if (elKTotal) elKTotal.value = rupiah(tKaca);
        totalKaca += tKaca;
      });

      var rolling = isRollingDoor(kategori);
      
      // Toggle Books Penutup
      card.querySelectorAll('.books-blok').forEach(function(el) {
        el.classList.toggle('d-none', !rolling);
      });
      var totalBooks = 0;
      if (rolling) {
        var bLebar  = parseFloat(card.querySelector('.it-books-lebar').value) || 0;
        var bTinggi = parseFloat(card.querySelector('.it-books-tinggi').value) || 0;
        var bQty    = parseFloat(card.querySelector('.it-books-qty').value) || 0;
        var bHarga  = parseFloat(card.querySelector('.it-books-harga').value) || 0;
        var bLuas   = Math.round((bLebar / 1000) * (bTinggi / 1000) * 10000) / 10000;
        totalBooks  = bLuas * bQty * bHarga;
        var elBLuas = card.querySelector('.it-books-luas');
        if (elBLuas) elBLuas.value = bLuas.toFixed(4);
        var elBTotal = card.querySelector('.it-books-total');
        if (elBTotal) elBTotal.value = rupiah(totalBooks);
      }

      // Toggle Biaya Instalasi Rolling Door
      card.querySelectorAll('.instalasi-blok').forEach(function(el) {
        el.classList.toggle('d-none', !rolling);
      });
      var totalInstalasi = 0;
      if (rolling) {
        var hInstalasi = parseFloat(card.querySelector('.it-instalasi-harga').value) || 0;
        var qInstalasi = parseFloat(card.querySelector('.it-instalasi-qty').value) || 0;
        // Rumus: Luas Billing x Qty x Harga Pasang per m²
        totalInstalasi = luas * qInstalasi * hInstalasi;
        var elInstTotal = card.querySelector('.it-instalasi-total');
        if (elInstTotal) elInstTotal.value = rupiah(totalInstalasi);
      }

      var total = ((kategori === 'unit_fix') ? (qty * harga) : (luas * qty * harga)) + totalAksesoris + totalKaca + totalBooks + totalInstalasi;
      card.querySelector('.item-subtotal').textContent = rupiah(total);

      return total;
    }

    function segarkan() {
      var rows = baris();
      var subtotal = 0;
      var totalQty = 0;

      rows.forEach(function(card, i) {
        card.querySelector('.item-no').textContent = i + 1;
        card.querySelector('.btn-remove-item').disabled = (rows.length === 1);
        subtotal += hitungBaris(card);
        totalQty += Math.max(0, parseInt(card.querySelector('.it-qty').value, 10) || 0);
      });

      var pasang = nominalBiaya(chkPasang, inpPasang);
      var ongkir = nominalBiaya(chkOngkir, inpOngkir);
      var dasar = subtotal + pasang + ongkir;

      var ppn = chkPpn.checked ? Math.round(dasar * TARIF_PPN / 100) : 0;
      var pph = chkPph.checked ? Math.round(dasar * TARIF_PPH / 100) : 0;
      var grand = dasar + ppn + pph;

      document.getElementById('sumJumlahItem').textContent = totalQty;
      document.getElementById('sumSubtotal').textContent = rupiah(subtotal);
      document.getElementById('sumPpn').textContent = rupiah(ppn);
      document.getElementById('sumPph').textContent = rupiah(pph);
      document.getElementById('sumGrandTotal').textContent = rupiah(grand);
    }

    btnTambah.addEventListener('click', function() { tambahItem(); });
    chkPpn.addEventListener('change', segarkan);
    chkPph.addEventListener('change', segarkan);
    chkPasang.addEventListener('change', function() { toggleBiaya(chkPasang, wrapPasang, inpPasang); segarkan(); });
    chkOngkir.addEventListener('change', function() { toggleBiaya(chkOngkir, wrapOngkir, inpOngkir); segarkan(); });
    inpPasang.addEventListener('input', segarkan);
    inpOngkir.addEventListener('input', segarkan);

    wrap.addEventListener('input', function(e) {
      if (e.target.classList.contains('it-barang-search')) {
        tampilkanDropdown(e.target.closest('.item-card'), cariBarang(e.target.value));
        return;
      }
      if (e.target.closest('.item-card')) segarkan();
    });

    wrap.addEventListener('focusin', function(e) {
      if (e.target.classList.contains('it-barang-search') && !e.target.readOnly) {
        tampilkanDropdown(e.target.closest('.item-card'), cariBarang(e.target.value));
      }
    });

    wrap.addEventListener('click', function(e) {
      var card = e.target.closest('.item-card');
      if (e.target.closest('.btn-tambah-aksesoris')) {
        var idx = baris().indexOf(card);
        card.querySelector('.list-aksesoris').insertAdjacentHTML('beforeend', templateBarisAksoris(idx));
        segarkan();
        return;
      }
      if (e.target.closest('.btn-tambah-kaca')) {
        var idxK = baris().indexOf(card);
        card.querySelector('.list-kaca').insertAdjacentHTML('beforeend', templateBarisKaca(idxK));
        segarkan();
        return;
      }
      if (e.target.closest('.btn-hapus-kaca')) {
        e.target.closest('.baris-kaca').remove();
        segarkan();
        return;
      }
      if (e.target.closest('.btn-hapus-aksesoris')) {
        e.target.closest('.baris-aksesoris').remove();
        segarkan();
        return;
      }
      var opt = e.target.closest('.barang-opt');
      if (opt) { pilihBarang(card, opt.getAttribute('data-id')); return; }
      var btnGanti = e.target.closest('.btn-ganti-barang');
      if (btnGanti) { gantiBarang(card); return; }
      var btnHapus = e.target.closest('.btn-remove-item');
      if (btnHapus && !btnHapus.disabled) { card.remove(); reindexAksesoris(); reindexKaca(); segarkan(); return; }
    });

    // Customer Auto-complete
    var DATA_CUSTOMER = [];
    try {
      DATA_CUSTOMER = JSON.parse(document.getElementById('dataCustomer').textContent || '[]') || [];
    } catch (e) { DATA_CUSTOMER = []; }

    var custInput = document.getElementById('customer_search');
    var custId = document.getElementById('id_customer');
    var custList = custInput ? custInput.parentNode.querySelector('.customer-dropdown-list') : null;

    function cariCustomer(kata) {
      kata = (kata || '').trim().toLowerCase();
      if (kata === '') return DATA_CUSTOMER;
      return DATA_CUSTOMER.filter(function(c) { return c.nama.toLowerCase().indexOf(kata) !== -1; });
    }

    function tutupDropdownCustomer() {
      if (!custList) return;
      custList.classList.add('d-none');
      custList.innerHTML = '';
    }

    function tampilkanCustomer(hasil) {
      custList.innerHTML = '';
      if (!hasil.length) {
        var kosong = document.createElement('div');
        kosong.className = 'customer-empty';
        kosong.textContent = 'Customer tidak ditemukan.';
        custList.appendChild(kosong);
      } else {
        hasil.forEach(function(c) {
          var el = document.createElement('div');
          el.className = 'customer-opt';
          el.setAttribute('data-id', c.id);
          el.textContent = c.nama;
          custList.appendChild(el);
        });
      }
      custList.classList.remove('d-none');
    }

    function pilihCustomer(id) {
      var c = DATA_CUSTOMER.find(function(x) { return String(x.id) === String(id); });
      if (!c) return;
      custId.value = c.id;
      custInput.value = c.nama;
      tutupDropdownCustomer();
    }

    if (custInput && !custInput.disabled) {
      custInput.addEventListener('focus', function() { tampilkanCustomer(cariCustomer(custId.value ? '' : custInput.value)); });
      custInput.addEventListener('input', function() { custId.value = ''; tampilkanCustomer(cariCustomer(custInput.value)); });
      custList.addEventListener('click', function(e) {
        var opt = e.target.closest('.customer-opt');
        if (opt) pilihCustomer(opt.getAttribute('data-id'));
      });
      document.addEventListener('click', function(e) {
        if (!e.target.closest('.customer-picker')) tutupDropdownCustomer();
        if (!e.target.closest('.barang-picker')) tutupSemuaDropdown();
      });
    }

    form.addEventListener('submit', function(e) {
      if (custInput && !custInput.disabled && !custId.value) {
        e.preventDefault();
        alert('Customer harus dipilih dari daftar.');
        custInput.focus();
        return;
      }
      var jenisTerisi = baris().some(function(card) {
        return card.querySelector('.it-jenis').value.trim() !== '';
      });
      if (!jenisTerisi) {
        e.preventDefault();
        alert('Minimal harus ada 1 item dengan Jenis Item diisi.');
        return;
      }
      if (!confirm(<?= $is_edit ? "'Simpan perubahan penawaran ini?'" : "'Simpan penawaran ini?'"; ?>)) {
        e.preventDefault();
      }
    });

    if (ITEMS_EDIT.length) {
      ITEMS_EDIT.forEach(function(d) { tambahItem(d); });
    } else {
      tambahItem();
    }
  });
</script>