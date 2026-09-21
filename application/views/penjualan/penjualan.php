<?php defined('BASEPATH') or exit('No direct script access allowed');

// ---------------------------------------------------------------
// Tarif pajak (persen) dikirim dari controller (Penjualan::TARIF_PPN dan
// Penjualan::TARIF_PPH23), jadi tampilan & perhitungan server selalu sama.
// Ubah tarif di controller saja. Angka di bawah hanya cadangan.
// ---------------------------------------------------------------
$tarif_ppn   = isset($tarif_ppn)   ? $tarif_ppn   : 11;  // PPN, ditambahkan ke total
$tarif_pph23 = isset($tarif_pph23) ? $tarif_pph23 : 2;   // PPh 23, dipotong oleh customer dari pembayaran

$list_customer = isset($list_customer) ? (array) $list_customer : [];
$list_stok     = isset($list_stok) ? (array) $list_stok : [];

$ada_stok     = !empty($list_stok);
$ada_customer = !empty($list_customer);
$dis          = ($ada_stok && $ada_customer) ? '' : 'disabled';

// Data stok untuk dropdown barang di setiap baris (dibuat di JavaScript).
// Nilai 'v' sama seperti sebelumnya: id_barang_id_location
$stok_js = [];
foreach ($list_stok as $s) {
    $stok_js[] = [
        'v'      => $s['id_barang'] . '_' . $s['id_location'],
        'kode'   => $s['kode_barang'],
        'nama'   => $s['nama'],
        'gudang' => $s['zone_name'],
        'stok'   => (int) $s['stok'],
    ];
}
$json_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;

// Tanggal transaksi: boleh dipilih, tidak boleh lewat hari ini.
// Batas mundur (dalam hari) bisa dikirim dari controller lewat $maks_mundur_hari; 0 = tanpa batas.
$maks_mundur_hari = isset($maks_mundur_hari) ? (int) $maks_mundur_hari : 0;
$tanggal_maks     = date('Y-m-d');
$tanggal_min      = $maks_mundur_hari > 0 ? date('Y-m-d', strtotime('-' . $maks_mundur_hari . ' days')) : '';
?>

<style>
  .pjl-page {
    --pjl-brand: #2f6f4f;
    --pjl-brand-dark: #234f38;
    --pjl-card-radius: 14px;
  }

  .pjl-page .page-header {
    background: linear-gradient(135deg, var(--pjl-brand), var(--pjl-brand-dark));
    color: #fff;
    padding: 22px 28px;
    border-radius: var(--pjl-card-radius);
    margin-bottom: 22px;
    box-shadow: 0 6px 18px rgba(35, 79, 56, .25);
  }

  .pjl-page .page-header h4 { margin: 0; font-weight: 600; }
  .pjl-page .page-header small { opacity: .85; }

  .pjl-page .card-modern {
    background: #fff;
    border: none;
    border-radius: var(--pjl-card-radius);
    box-shadow: 0 2px 10px rgba(0, 0, 0, .06);
    margin-bottom: 22px;
  }

  .pjl-page .card-modern .card-header {
    background: #fff;
    border-bottom: 1px solid #eef1ef;
    border-radius: var(--pjl-card-radius) var(--pjl-card-radius) 0 0;
    font-weight: 600;
    color: var(--pjl-brand-dark);
    padding: 16px 20px;
  }

  .pjl-page .card-modern .card-body { padding: 20px; }

  .pjl-page .form-label {
    font-weight: 500;
    font-size: .86rem;
    color: #4a4a4a;
  }

  .pjl-page .form-control:focus,
  .pjl-page .form-select:focus {
    border-color: var(--pjl-brand);
    box-shadow: 0 0 0 .2rem rgba(47, 111, 79, .15);
  }

  .pjl-page .form-control[readonly],
  .pjl-page .form-control:disabled {
    background: #f3f6f4;
    color: #5a5a5a;
  }

  .pjl-page #tabelItem thead { background: #eef4f0; }

  .pjl-page #tabelItem th {
    font-size: .8rem;
    text-transform: uppercase;
    letter-spacing: .03em;
    color: var(--pjl-brand-dark);
    font-weight: 700;
    white-space: nowrap;
    vertical-align: middle;
  }

  .pjl-page #tabelItem td { vertical-align: top; }
  .pjl-page #tabelItem tbody tr:hover { background: #fbfdfc; }
  .pjl-page #tabelItem .pjl-subtotal { vertical-align: middle; font-weight: 600; white-space: nowrap; }
  .pjl-page #tabelItem .row-index { vertical-align: middle; }

  .pjl-page .badge-status {
    padding: 6px 14px;
    border-radius: 20px;
    font-weight: 600;
    font-size: .78rem;
    background: #fff4d6;
    color: #946c00;
  }

  .pjl-page .btn-pjl-brand {
    background: var(--pjl-brand);
    border-color: var(--pjl-brand);
    color: #fff;
  }

  .pjl-page .btn-pjl-brand:hover,
  .pjl-page .btn-pjl-brand:focus {
    background: var(--pjl-brand-dark);
    border-color: var(--pjl-brand-dark);
    color: #fff;
  }

  .pjl-page .btn-pjl-brand:disabled { background: var(--pjl-brand); border-color: var(--pjl-brand); opacity: .55; }

  .pjl-page .btn-outline-pjl-brand {
    border-color: var(--pjl-brand);
    color: var(--pjl-brand);
  }

  .pjl-page .btn-outline-pjl-brand:hover {
    background: var(--pjl-brand);
    color: #fff;
  }

  .pjl-page .btn-remove-row { color: #b3261e; }
  .pjl-page .btn-remove-row:hover { color: #7a1a15; }
  .pjl-page .btn-remove-row:disabled { color: #c9c9c9; }

  .pjl-page .summary-box {
    background: #f8faf9;
    border: 1px dashed #cfe0d6;
    border-radius: 10px;
    padding: 16px 18px;
  }

  .pjl-page .summary-box .val {
    font-weight: 700;
    color: var(--pjl-brand-dark);
    font-size: 1.05rem;
  }

  .pjl-page .total-box {
    background: #f8faf9;
    border-left: 4px solid var(--pjl-brand);
    border-radius: 10px;
    padding: 16px 18px;
  }

  .pjl-page .footer-actions {
    position: sticky;
    bottom: 0;
    background: #fff;
    padding: 14px 20px;
    border-radius: var(--pjl-card-radius);
    box-shadow: 0 -4px 14px rgba(0, 0, 0, .06);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
    z-index: 5;
  }

  @media (max-width: 768px) {
    .pjl-page .page-header { padding: 16px 18px; }
  }
</style>

<div class="pjl-page">

  <div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <h4><i class="bi bi-cart-check me-2"></i>Penjualan Barang</h4>
      <small>Catat penjualan barang jadi. Satu transaksi bisa berisi beberapa barang.</small>
    </div>
    <div class="text-end">
      <div class="fw-bold fs-5">No. Faktur: <?= html_escape($no_faktur); ?></div>
      <span class="badge-status"><i class="bi bi-clock-history me-1"></i>Draft</span>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i>
      <?= $this->session->flashdata('success'); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      <?= $this->session->flashdata('error'); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endif; ?>

  <?php if (!$ada_stok): ?>
    <div class="alert alert-warning">
      Belum ada barang jadi dengan stok tersedia, jadi penjualan belum bisa dibuat.
      Pastikan barang sudah diterima dan tercatat di stok.
    </div>
  <?php endif; ?>
  <?php if (!$ada_customer): ?>
    <div class="alert alert-warning">
      Belum ada data customer, jadi penjualan belum bisa dibuat. Tambahkan customer terlebih dahulu.
    </div>
  <?php endif; ?>

  <?= form_open('penjualan/simpan', ['id' => 'formPenjualan', 'autocomplete' => 'off']); ?>

  <!-- Informasi penjualan -->
  <div class="card-modern">
    <div class="card-header"><i class="bi bi-info-circle me-2"></i>Informasi Penjualan</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label for="no_faktur" class="form-label">No. Faktur</label>
          <input type="text" id="no_faktur" name="no_faktur" value="<?= html_escape($no_faktur); ?>" class="form-control" readonly>
          <div class="form-text">Dibuat otomatis oleh sistem.</div>
        </div>
        <div class="col-md-3">
          <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
          <input type="date" id="tanggal" name="tanggal" class="form-control"
                 value="<?= $tanggal_maks; ?>"
                 max="<?= $tanggal_maks; ?>"
                 <?= $tanggal_min !== '' ? 'min="' . $tanggal_min . '"' : ''; ?>
                 required <?= $dis; ?>>
          <div class="form-text">
            <?= $tanggal_min !== ''
                ? 'Maksimal ' . (int) $maks_mundur_hari . ' hari ke belakang, tidak boleh lewat hari ini.'
                : 'Tidak boleh lewat hari ini.'; ?>
          </div>
        </div>
        <div class="col-md-5">
          <label for="id_customer" class="form-label">Customer <span class="text-danger">*</span></label>
          <select name="id_customer" id="id_customer" class="form-select" required <?= $dis; ?>>
            <option value="">Pilih customer</option>
            <?php foreach ($list_customer as $c): ?>
              <option value="<?= html_escape($c['id']); ?>" data-nama="<?= html_escape($c['nama']); ?>">
                <?= html_escape($c['nama']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Pembeli semua barang di transaksi ini.</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Detail barang -->
  <div class="card-modern">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span><i class="bi bi-list-check me-2"></i>Detail Barang Dijual</span>
      <button type="button" class="btn btn-sm btn-outline-pjl-brand" id="btnTambahBaris" <?= $dis; ?>>
        <i class="bi bi-plus-lg me-1"></i>Tambah Barang
      </button>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table align-middle mb-0" id="tabelItem" style="min-width: 760px;">
          <thead>
            <tr>
              <th style="width:4%">#</th>
              <th style="width:36%">Barang <span class="text-danger">*</span></th>
              <th style="width:20%">Jumlah <span class="text-danger">*</span></th>
              <th style="width:20%">Harga Satuan <span class="text-danger">*</span></th>
              <th style="width:16%" class="text-end">Subtotal</th>
              <th style="width:4%"></th>
            </tr>
          </thead>
          <tbody id="bodyItem"></tbody>
        </table>
      </div>
      <div class="form-text mt-2" id="itemHelp">Barang yang sudah dipilih di baris lain tidak muncul lagi di daftar. Barang dengan stok habis tidak bisa dipilih.</div>
    </div>
  </div>

  <!-- Pajak + ringkasan -->
  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="card-modern h-100 mb-0">
        <div class="card-header"><i class="bi bi-percent me-2"></i>Pajak</div>
        <div class="card-body">
          <div class="text-muted small mb-3">Berlaku untuk seluruh barang di transaksi ini.</div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="pakai_ppn" name="pakai_ppn" value="1" <?= $dis; ?>>
            <label class="form-check-label" for="pakai_ppn">
              Kenakan PPN <?= html_escape($tarif_ppn); ?>%
              <span class="text-muted">(ditambahkan ke total)</span>
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="pakai_pph23" name="pakai_pph23" value="1" <?= $dis; ?>>
            <label class="form-check-label" for="pakai_pph23">
              Kenakan PPh 23 (<?= html_escape($tarif_pph23); ?>%)
              <span class="text-muted">(dipotong dari pembayaran customer)</span>
            </label>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card-modern h-100 mb-0">
        <div class="card-header"><i class="bi bi-receipt me-2"></i>Ringkasan</div>
        <div class="card-body">
          <div class="row g-3 mb-3">
            <div class="col-6">
              <div class="summary-box">
                <div class="text-muted small">Jenis barang</div>
                <div class="val" id="sumJenis">0</div>
              </div>
            </div>
            <div class="col-6">
              <div class="summary-box">
                <div class="text-muted small">Total jumlah</div>
                <div class="val" id="sumQty">0</div>
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-between small mb-1">
            <span class="text-muted">Tanggal</span>
            <span id="sumTanggal">-</span>
          </div>
          <div class="d-flex justify-content-between small mb-1">
            <span class="text-muted">Customer</span>
            <span id="sumCustomer">-</span>
          </div>
          <div class="d-flex justify-content-between small mb-1">
            <span class="text-muted">Subtotal</span>
            <span id="sumSubtotal">Rp 0</span>
          </div>
          <div id="rowPpn" class="d-none d-flex justify-content-between small mb-1">
            <span class="text-muted">PPN <?= html_escape($tarif_ppn); ?>%</span>
            <span id="sumPpn">Rp 0</span>
          </div>
          <div id="rowPph" class="d-none d-flex justify-content-between small mb-1">
            <span class="text-muted">PPh 23 (<?= html_escape($tarif_pph23); ?>%)</span>
            <span id="sumPph">- Rp 0</span>
          </div>

          <div class="total-box mt-3">
            <div class="text-muted small">Total dibayar customer</div>
            <div class="h3 fw-bold mb-0" id="sumTotal">Rp 0</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Aksi -->
  <div class="footer-actions">
    <button type="reset" class="btn btn-outline-secondary" <?= $dis; ?>>
      <i class="bi bi-arrow-counterclockwise me-1"></i>Kosongkan
    </button>
    <button type="submit" class="btn btn-pjl-brand fw-bold" id="btnSimpan" <?= $dis; ?>>
      <i class="bi bi-check2-circle me-1"></i>Simpan dan kurangi stok
    </button>
  </div>

  <?= form_close(); ?>
</div>

<!-- Konfirmasi sebelum stok dipotong -->
<div class="modal fade" id="modalKonfirmasi" tabindex="-1" aria-labelledby="modalKonfirmasiLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalKonfirmasiLabel">Simpan penjualan ini?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">Stok akan langsung berkurang setelah disimpan. Periksa data berikut:</p>
        <div class="mb-1"><span class="text-muted">Tanggal:</span> <strong id="cfTanggal"></strong></div>
        <div class="mb-2"><span class="text-muted">Customer:</span> <strong id="cfCustomer"></strong></div>

        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>Barang</th>
                <th>Gudang</th>
                <th class="text-end">Jumlah</th>
                <th class="text-end">Harga satuan</th>
                <th class="text-end">Subtotal</th>
              </tr>
            </thead>
            <tbody id="cfItems"></tbody>
          </table>
        </div>

        <table class="table table-sm mb-0">
          <tr><td class="text-muted">Subtotal</td><td class="text-end" id="cfSubtotal"></td></tr>
          <tr id="cfRowPpn" class="d-none"><td class="text-muted">PPN <?= html_escape($tarif_ppn); ?>%</td><td class="text-end" id="cfPpn"></td></tr>
          <tr id="cfRowPph" class="d-none"><td class="text-muted">PPh 23 (<?= html_escape($tarif_pph23); ?>%)</td><td class="text-end" id="cfPph"></td></tr>
          <tr><td class="fw-bold">Total dibayar customer</td><td class="fw-bold text-end" id="cfTotal"></td></tr>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Periksa lagi</button>
        <button type="button" class="btn btn-pjl-brand fw-bold" id="btnKonfirmasi">Ya, simpan</button>
      </div>
    </div>
  </div>
</div>

<script>
// Dijalankan setelah halaman selesai dimuat, supaya jQuery & Bootstrap yang dimuat di footer sudah tersedia
document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  const TARIF_PPN   = <?= (float) $tarif_ppn; ?>;
  const TARIF_PPH23 = <?= (float) $tarif_pph23; ?>;
  const BISA        = <?= ($dis === '') ? 'true' : 'false'; ?>;
  const STOK        = <?= json_encode($stok_js, $json_flags); ?>;

  const STOK_MAP = {};
  STOK.forEach(function (s) { STOK_MAP[s.v] = s; });
  const JUMLAH_TERSEDIA = STOK.filter(function (s) { return s.stok > 0; }).length;

  const form        = document.getElementById('formPenjualan');
  const selCustomer = document.getElementById('id_customer');
  const inTanggal   = document.getElementById('tanggal');
  const cbPpn       = document.getElementById('pakai_ppn');
  const cbPph       = document.getElementById('pakai_pph23');
  const bodyItem    = document.getElementById('bodyItem');
  const btnTambah   = document.getElementById('btnTambahBaris');
  const btnSimpan   = document.getElementById('btnSimpan');
  const modalEl     = document.getElementById('modalKonfirmasi');
  const modal       = window.bootstrap ? new bootstrap.Modal(modalEl) : null;
  let sudahKonfirmasi = false;

  function $id(id)   { return document.getElementById(id); }
  function rupiah(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); }
  function angka(n)  { return Number(n || 0).toLocaleString('id-ID'); }
  function baris()   { return Array.prototype.slice.call(bodyItem.querySelectorAll('tr')); }

  // Nilai input date berformat yyyy-mm-dd, ditampilkan sebagai dd/mm/yyyy
  function formatTanggal(v) {
    if (!v) return '-';
    const p = v.split('-');
    return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : v;
  }

  function namaCustomer() {
    if (!selCustomer.value) return null;
    return selCustomer.options[selCustomer.selectedIndex].getAttribute('data-nama');
  }

  // ---------- Baris barang ----------

  function isiPilihanBarang(sel) {
    sel.innerHTML = '';
    sel.add(new Option('Pilih barang yang dijual', ''));

    const perGudang = {};
    STOK.forEach(function (s) {
      (perGudang[s.gudang] = perGudang[s.gudang] || []).push(s);
    });

    Object.keys(perGudang).forEach(function (gudang) {
      const og = document.createElement('optgroup');
      og.label = 'Gudang: ' + gudang;
      perGudang[gudang].forEach(function (s) {
        const o = new Option(s.kode + ' - ' + s.nama + ' (stok: ' + s.stok + ')', s.v);
        if (s.stok <= 0) o.disabled = true;
        og.appendChild(o);
      });
      sel.appendChild(og);
    });
  }

  function templateBaris() {
    return '' +
      '<tr>' +
        '<td class="row-index"></td>' +
        '<td>' +
          '<select name="id_stok[]" class="form-select form-select-sm pjl-barang" aria-label="Barang" required></select>' +
          '<div class="form-text pjl-info">Pilih barang dulu untuk melihat stok.</div>' +
        '</td>' +
        '<td>' +
          '<div class="input-group input-group-sm">' +
            '<input type="number" name="qty[]" min="1" step="1" class="form-control pjl-qty" placeholder="0" aria-label="Jumlah" required>' +
            '<button type="button" class="btn btn-outline-secondary pjl-maks" disabled title="Isi dengan seluruh stok yang tersedia">Maks</button>' +
          '</div>' +
          '<div class="form-text text-danger d-none pjl-qty-hint"></div>' +
        '</td>' +
        '<td>' +
          '<div class="input-group input-group-sm">' +
            '<span class="input-group-text">Rp</span>' +
            '<input type="number" name="harga_satuan[]" min="0" step="1" class="form-control pjl-harga" placeholder="0" aria-label="Harga satuan" required>' +
          '</div>' +
        '</td>' +
        '<td class="text-end pjl-subtotal">Rp 0</td>' +
        '<td class="text-center">' +
          '<button type="button" class="btn btn-sm btn-remove-row" title="Hapus baris" aria-label="Hapus baris"><i class="bi bi-trash3"></i></button>' +
        '</td>' +
      '</tr>';
  }

  function tambahBaris(fokus) {
    if (!BISA) return;
    bodyItem.insertAdjacentHTML('beforeend', templateBaris());
    const tr = bodyItem.lastElementChild;
    isiPilihanBarang(tr.querySelector('.pjl-barang'));
    segarkan();
    if (fokus) tr.querySelector('.pjl-barang').focus();
  }

  // Satu barang (per gudang) hanya boleh muncul di satu baris
  function sinkronPilihan() {
    const dipakai = {};
    baris().forEach(function (tr) {
      const v = tr.querySelector('.pjl-barang').value;
      if (v) dipakai[v] = true;
    });

    baris().forEach(function (tr) {
      const sel = tr.querySelector('.pjl-barang');
      Array.prototype.forEach.call(sel.options, function (o) {
        if (!o.value) return;
        const s = STOK_MAP[o.value];
        o.disabled = (s.stok <= 0) || (dipakai[o.value] && o.value !== sel.value);
      });
    });
  }

  function perbaruiBaris(tr) {
    const sel  = tr.querySelector('.pjl-barang');
    const info = tr.querySelector('.pjl-info');
    const qty  = tr.querySelector('.pjl-qty');
    const maks = tr.querySelector('.pjl-maks');
    const hint = tr.querySelector('.pjl-qty-hint');
    const s    = STOK_MAP[sel.value] || null;
    const jml  = parseInt(qty.value, 10) || 0;

    if (s) {
      info.textContent = 'Gudang: ' + s.gudang + ' | Stok tersedia: ' + angka(s.stok);
      qty.max = s.stok;
      maks.disabled = false;
    } else {
      info.textContent = 'Pilih barang dulu untuk melihat stok.';
      qty.removeAttribute('max');
      maks.disabled = true;
    }

    const melebihi = !!(s && jml > s.stok);
    qty.setCustomValidity(melebihi ? 'Jumlah melebihi stok tersedia' : '');
    qty.classList.toggle('is-invalid', melebihi);
    hint.classList.toggle('d-none', !melebihi);
    if (melebihi) hint.textContent = 'Melebihi stok (' + angka(s.stok) + ').';
  }

  // ---------- Perhitungan ----------
  // PPN dihitung dari subtotal seluruh barang lalu ditambahkan,
  // PPh 23 dihitung dari subtotal (sebelum PPN) lalu dipotong dari pembayaran.
  function hitung() {
    let subtotal = 0, totalQty = 0, jenis = 0;
    const items = [];

    baris().forEach(function (tr) {
      const sel   = tr.querySelector('.pjl-barang');
      const s     = STOK_MAP[sel.value] || null;
      const qty   = parseInt(tr.querySelector('.pjl-qty').value, 10) || 0;
      const harga = parseFloat(tr.querySelector('.pjl-harga').value) || 0;
      const sub   = qty * harga;

      tr.querySelector('.pjl-subtotal').textContent = rupiah(sub);
      subtotal += sub;
      totalQty += qty;
      if (s) jenis++;
      items.push({ s: s, qty: qty, harga: harga, sub: sub });
    });

    const ppn = cbPpn.checked ? Math.round(subtotal * TARIF_PPN / 100) : 0;
    const pph = cbPph.checked ? Math.round(subtotal * TARIF_PPH23 / 100) : 0;

    return { items: items, jenis: jenis, qty: totalQty, subtotal: subtotal, ppn: ppn, pph: pph, total: subtotal + ppn - pph };
  }

  function segarkan() {
    const rows = baris();

    rows.forEach(function (tr, i) {
      tr.querySelector('.row-index').textContent = i + 1;
      tr.querySelector('.btn-remove-row').disabled = (rows.length === 1);
    });

    sinkronPilihan();
    rows.forEach(perbaruiBaris);

    if (btnTambah) btnTambah.disabled = !BISA || rows.length >= JUMLAH_TERSEDIA;

    const h = hitung();
    $id('sumTanggal').textContent  = formatTanggal(inTanggal.value);
    $id('sumCustomer').textContent = namaCustomer() || '-';
    $id('sumJenis').textContent    = angka(h.jenis);
    $id('sumQty').textContent      = angka(h.qty);
    $id('sumSubtotal').textContent = rupiah(h.subtotal);
    $id('sumPpn').textContent      = '+ ' + rupiah(h.ppn);
    $id('sumPph').textContent      = '- ' + rupiah(h.pph);
    $id('rowPpn').classList.toggle('d-none', !cbPpn.checked);
    $id('rowPph').classList.toggle('d-none', !cbPph.checked);
    $id('sumTotal').textContent    = rupiah(h.total);
  }

  // ---------- Event ----------

  btnTambah.addEventListener('click', function () { tambahBaris(true); });

  bodyItem.addEventListener('change', function (e) {
    if (e.target.classList.contains('pjl-barang')) segarkan();
  });

  bodyItem.addEventListener('input', function (e) {
    if (e.target.classList.contains('pjl-qty') || e.target.classList.contains('pjl-harga')) segarkan();
  });

  bodyItem.addEventListener('click', function (e) {
    const btnHapus = e.target.closest('.btn-remove-row');
    if (btnHapus && !btnHapus.disabled) {
      btnHapus.closest('tr').remove();
      segarkan();
      return;
    }

    const btnMaks = e.target.closest('.pjl-maks');
    if (btnMaks && !btnMaks.disabled) {
      const tr = btnMaks.closest('tr');
      const s  = STOK_MAP[tr.querySelector('.pjl-barang').value];
      if (s) {
        const qty = tr.querySelector('.pjl-qty');
        qty.value = s.stok;
        segarkan();
        qty.focus();
      }
    }
  });

  selCustomer.addEventListener('change', segarkan);
  inTanggal.addEventListener('input', segarkan);
  inTanggal.addEventListener('change', segarkan);
  cbPpn.addEventListener('change', segarkan);
  cbPph.addEventListener('change', segarkan);

  // Kosongkan: kembali ke satu baris kosong
  form.addEventListener('reset', function () {
    setTimeout(function () {
      bodyItem.innerHTML = '';
      tambahBaris(false);
      segarkan();
    }, 0);
  });

  function kirim() {
    sudahKonfirmasi = true;
    // Cegah klik ganda supaya stok tidak terpotong dua kali
    btnSimpan.disabled = true;
    btnSimpan.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
    form.submit();
  }

  function tdTeks(teks, kelas) {
    const td = document.createElement('td');
    td.textContent = teks;
    if (kelas) td.className = kelas;
    return td;
  }

  // Submit: browser sudah memvalidasi field wajib, lalu minta konfirmasi dulu
  form.addEventListener('submit', function (e) {
    if (sudahKonfirmasi) return;
    e.preventDefault();

    const h = hitung();

    if (modal) {
      $id('cfTanggal').textContent  = formatTanggal(inTanggal.value);
      $id('cfCustomer').textContent = namaCustomer() || '-';

      const cfItems = $id('cfItems');
      cfItems.innerHTML = '';
      h.items.forEach(function (it) {
        const tr = document.createElement('tr');
        tr.appendChild(tdTeks(it.s.kode + ' - ' + it.s.nama));
        tr.appendChild(tdTeks(it.s.gudang));
        tr.appendChild(tdTeks(angka(it.qty), 'text-end'));
        tr.appendChild(tdTeks(rupiah(it.harga), 'text-end'));
        tr.appendChild(tdTeks(rupiah(it.sub), 'text-end'));
        cfItems.appendChild(tr);
      });

      $id('cfSubtotal').textContent = rupiah(h.subtotal);
      $id('cfPpn').textContent      = '+ ' + rupiah(h.ppn);
      $id('cfPph').textContent      = '- ' + rupiah(h.pph);
      $id('cfRowPpn').classList.toggle('d-none', !cbPpn.checked);
      $id('cfRowPph').classList.toggle('d-none', !cbPph.checked);
      $id('cfTotal').textContent    = rupiah(h.total);
      modal.show();
    } else if (confirm('Simpan penjualan ' + h.jenis + ' jenis barang untuk ' + namaCustomer() + ' (total ' + rupiah(h.total) + ')? Stok akan langsung berkurang.')) {
      kirim(); // cadangan jika script modal Bootstrap tidak tersedia
    }
  });

  $id('btnKonfirmasi').addEventListener('click', function () {
    if (modal) modal.hide();
    kirim();
  });

  // Mulai dengan satu baris kosong
  tambahBaris(false);
  segarkan();
});
</script>