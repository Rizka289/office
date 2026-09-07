<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!--
  PENTING: File ini adalah FRAGMENT yang di-load DI DALAM header.php + footer.php
  (lihat main->load->view('header'), load->view('penerimaan_barang_view'), load->view('footer')).
  JANGAN tulis <!DOCTYPE>, <html>, <head>, <body>, atau link CDN Bootstrap/Icons di sini —
  semua itu SUDAH disediakan oleh header.php dan akan bentrok/menimpa milik header kalau ditulis ulang.
-->

<style>
  /* Variabel di-scope ke .rcv-page saja (BUKAN :root) supaya tidak menimpa
     --brand / --brand-dark milik header.php yang dipakai sidebar & topbar. */
  .rcv-page {
    --rcv-brand: #2f6f4f;
    --rcv-brand-dark: #234f38;
    --rcv-card-radius: 14px;
  }

  .rcv-page .page-header {
    background: linear-gradient(135deg, var(--rcv-brand), var(--rcv-brand-dark));
    color: #fff;
    padding: 22px 28px;
    border-radius: var(--rcv-card-radius);
    margin-bottom: 22px;
    box-shadow: 0 6px 18px rgba(35, 79, 56, .25);
  }

  .rcv-page .page-header h4 {
    margin: 0;
    font-weight: 600;
  }

  .rcv-page .page-header small {
    opacity: .85;
  }

  .rcv-page .card-modern {
    background: #fff;
    border: none;
    border-radius: var(--rcv-card-radius);
    box-shadow: 0 2px 10px rgba(0, 0, 0, .06);
    margin-bottom: 22px;
  }

  .rcv-page .card-modern .card-header {
    background: #fff;
    border-bottom: 1px solid #eef1ef;
    border-radius: var(--rcv-card-radius) var(--rcv-card-radius) 0 0;
    font-weight: 600;
    color: var(--rcv-brand-dark);
    padding: 16px 20px;
  }

  .rcv-page .card-modern .card-body {
    padding: 20px;
  }

  .rcv-page .form-label {
    font-weight: 500;
    font-size: .86rem;
    color: #4a4a4a;
  }

  .rcv-page .form-control:focus,
  .rcv-page .form-select:focus {
    border-color: var(--rcv-brand);
    box-shadow: 0 0 0 .2rem rgba(47, 111, 79, .15);
  }

  .rcv-page .form-control[readonly],
  .rcv-page .form-control:disabled {
    background: #f3f6f4;
    color: #5a5a5a;
  }

  .rcv-page #tabelItem thead {
    background: #eef4f0;
  }

  .rcv-page #tabelItem th {
    font-size: .8rem;
    text-transform: uppercase;
    letter-spacing: .03em;
    color: var(--rcv-brand-dark);
    font-weight: 700;
    white-space: nowrap;
    vertical-align: middle;
  }

  .rcv-page #tabelItem td {
    vertical-align: middle;
  }

  .rcv-page #tabelItem tbody tr:hover {
    background: #fbfdfc;
  }

  .rcv-page #tabelItem tr.row-extra {
    background: #fffdf5;
  }

  .rcv-page .badge-status {
    padding: 6px 14px;
    border-radius: 20px;
    font-weight: 600;
    font-size: .78rem;
  }

  .rcv-page .badge-draft {
    background: #fff4d6;
    color: #946c00;
  }

  .rcv-page .badge-lengkap {
    background: #dff3e6;
    color: #1f7a45;
  }

  .rcv-page .badge-kurang {
    background: #fde2e1;
    color: #b3261e;
  }

  .rcv-page .badge-po {
    background: #e7f0eb;
    color: var(--rcv-brand-dark);
    font-size: .7rem;
    padding: 3px 8px;
    border-radius: 6px;
  }

  .rcv-page .badge-tambahan {
    background: #fdf0d5;
    color: #946c00;
    font-size: .7rem;
    padding: 3px 8px;
    border-radius: 6px;
  }

  /* Prefiks rcv- dipakai supaya TIDAK menimpa .btn-brand global milik header.php */
  .rcv-page .btn-rcv-brand {
    background: var(--rcv-brand);
    border-color: var(--rcv-brand);
    color: #fff;
  }

  .rcv-page .btn-rcv-brand:hover {
    background: var(--rcv-brand-dark);
    border-color: var(--rcv-brand-dark);
    color: #fff;
  }

  .rcv-page .btn-outline-rcv-brand {
    border-color: var(--rcv-brand);
    color: var(--rcv-brand);
  }

  .rcv-page .btn-outline-rcv-brand:hover {
    background: var(--rcv-brand);
    color: #fff;
  }

  .rcv-page .btn-remove-row {
    color: #b3261e;
  }

  .rcv-page .btn-remove-row:hover {
    color: #7a1a15;
  }

  .rcv-page .summary-box {
    background: #f8faf9;
    border: 1px dashed #cfe0d6;
    border-radius: 10px;
    padding: 16px 18px;
  }

  .rcv-page .summary-box .val {
    font-weight: 700;
    color: var(--rcv-brand-dark);
    font-size: 1.05rem;
  }

  .rcv-page .qty-diff-ok {
    color: #1f7a45;
    font-weight: 600;
  }

  .rcv-page .qty-diff-bad {
    color: #b3261e;
    font-weight: 600;
  }

  .rcv-page .footer-actions {
    position: sticky;
    bottom: 0;
    background: #fff;
    padding: 14px 20px;
    border-radius: var(--rcv-card-radius);
    box-shadow: 0 -4px 14px rgba(0, 0, 0, .06);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
  }

  .rcv-page .empty-state {
    text-align: center;
    padding: 36px 20px;
    color: #8a938d;
  }

  .rcv-page .empty-state i {
    font-size: 2rem;
    display: block;
    margin-bottom: 8px;
    color: #c3d0c9;
  }

  .rcv-page .po-loading {
    display: none;
  }

  .rcv-page .po-loading.active {
    display: inline-flex;
  }

  @media (max-width:768px) {
    .rcv-page .page-header {
      padding: 16px 18px;
    }
  }
</style>

<div class="rcv-page">

  <!-- Header halaman -->
  <div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <h4><i class="bi bi-box-seam me-2"></i>Penerimaan Barang</h4>
      <small>Formulir pencatatan barang masuk dari Purchase Order</small>
    </div>
    <div class="text-end">
      <div class="fw-bold fs-5">No. Penerimaan: RCV-<?php echo isset($no_penerimaan) ? $no_penerimaan : '2026-0001'; ?></div>
      <span class="badge-status badge-draft"><i class="bi bi-clock-history me-1"></i>Draft</span>
    </div>
  </div>

  <?php echo form_open('penerimaan_barang/simpan', ['id' => 'formPenerimaan']); ?>

  <!-- Informasi Umum -->
  <div class="card-modern">
    <div class="card-header"><i class="bi bi-info-circle me-2"></i>Informasi Penerimaan</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">No. Purchase Order</label>
          <div class="input-group">
            <input type="text" id="rcvInputNoPO" name="no_po" class="form-control" placeholder="Ketik / scan No. PO lalu tekan Enter" required>
            <button type="button" class="btn btn-outline-rcv-brand" id="rcvBtnCariPO">
              <span class="spinner-border spinner-border-sm po-loading" id="rcvPoLoading"></span>
              <i class="bi bi-search" id="rcvIconCariPO"></i>
            </button>
          </div>
          <div class="form-text" id="rcvPoHint">Detail barang akan otomatis terisi dari data PO.</div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Supplier</label>
          <input type="text" id="rcvInputSupplier" name="supplier" class="form-control" placeholder="Otomatis dari PO" readonly>
        </div>
        <div class="col-md-4">
          <label class="form-label">Tanggal Penerimaan</label>
          <input type="date" name="tanggal_terima" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">No. Surat Jalan</label>
          <input type="text" name="no_surat_jalan" class="form-control" placeholder="Nomor surat jalan supplier">
        </div>
        <div class="col-md-4">
          <label class="form-label">Diterima Oleh</label>
          <input type="text" name="diterima_oleh" class="form-control" placeholder="Nama penerima">
        </div>
        <div class="col-md-4">
          <label class="form-label">Gudang Tujuan</label>
          <select name="gudang" class="form-select">
            <option value="">-- Pilih Gudang --</option>
            <option value="gudang_utama">Gudang Utama</option>
            <option value="gudang_cabang">Gudang Cabang</option>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label">Catatan</label>
          <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan tambahan (opsional)"></textarea>
        </div>
      </div>
    </div>
  </div>

  <!-- Detail Item -->
  <div class="card-modern">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span><i class="bi bi-list-check me-2"></i>Detail Barang Diterima</span>
      <button type="button" class="btn btn-sm btn-outline-rcv-brand" id="rcvBtnTambahExtra" disabled>
        <i class="bi bi-plus-lg me-1"></i>Tambah Barang Diluar PO
      </button>
    </div>
    <div class="card-body">

      <div id="rcvEmptyState" class="empty-state">
        <i class="bi bi-file-earmark-text"></i>
        Masukkan No. Purchase Order lalu klik <strong>Cari</strong> untuk memuat daftar barang.
      </div>

      <div class="table-responsive d-none" id="rcvWrapperTabel">
        <table class="table align-middle mb-0" id="tabelItem">
          <thead>
            <tr>
              <th style="width:4%">#</th>
              <th style="width:12%">Kode Barang</th>
              <th>Nama Barang</th>
              <th style="width:8%">Qty PO</th>
              <th style="width:10%">Qty Diterima</th>
              <th style="width:8%">Satuan</th>
              <th style="width:11%">Kondisi</th>
              <th style="width:13%">Keterangan</th>
              <th style="width:4%"></th>
            </tr>
          </thead>
          <tbody id="rcvBodyItem"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Ringkasan -->
  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <div class="summary-box">
        <div class="text-muted small">Total Jenis Barang</div>
        <div class="val" id="rcvTotalJenis">0</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="summary-box">
        <div class="text-muted small">Total Qty Diterima</div>
        <div class="val" id="rcvTotalQty">0</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="summary-box">
        <div class="text-muted small">Status Kesesuaian</div>
        <div class="val" id="rcvStatusSesuai">-</div>
      </div>
    </div>
  </div>

  <!-- Aksi -->
  <div class="footer-actions">
    <!-- Diubah dari <button type="button"> menjadi tag <a> -->
    <a href="<?php echo site_url('penerimaan_barang'); ?>" class="btn btn-outline-secondary">
      <i class="bi bi-x-lg me-1"></i><?= translate('btn_batal') ?>
    </a>
    <button type="submit" name="aksi" value="draft" class="btn btn-outline-rcv-brand">
      <i class="bi bi-save me-1"></i>Simpan Draft
    </button>
    <button type="submit" name="aksi" value="final" class="btn btn-rcv-brand">
      <i class="bi bi-check2-circle me-1"></i>Konfirmasi Penerimaan
    </button>
  </div>

  <?php echo form_close(); ?>
</div>

<script>
  // Dibungkus IIFE + prefiks 'rcv' pada semua id/variabel supaya tidak bentrok
  // dengan script global di footer.php atau view lain yang mungkin ikut ter-load.
  (function() {
    const inputNoPO = document.getElementById('rcvInputNoPO');
    const inputSupplier = document.getElementById('rcvInputSupplier');
    const btnCariPO = document.getElementById('rcvBtnCariPO');
    const poLoading = document.getElementById('rcvPoLoading');
    const iconCariPO = document.getElementById('rcvIconCariPO');
    const poHint = document.getElementById('rcvPoHint');
    const emptyState = document.getElementById('rcvEmptyState');
    const wrapperTabel = document.getElementById('rcvWrapperTabel');
    const bodyItem = document.getElementById('rcvBodyItem');
    const btnTambahExtra = document.getElementById('rcvBtnTambahExtra');

    if (!inputNoPO) return; // guard kalau fragment ini ke-load tanpa elemen terkait

    // Endpoint controller CI3 yang mengembalikan JSON detail PO.
    // Contoh response: { "supplier": "PT Sumber Makmur", "items": [
    //   { "kode": "BRG-001", "nama": "Kertas A4", "qty_po": 50, "satuan": "rim" }, ... ] }
    const URL_GET_PO = '<?php echo base_url("penerimaan_barang/get_po_items"); ?>';

    function rowPOTemplate(index, item) {
      return `
      <tr class="row-po">
        <td class="row-index">${index}</td>
        <td>
          <input type="hidden" name="kode_barang[]" value="${item.kode}">
          <input type="text" class="form-control form-control-sm" value="${item.kode}" readonly>
        </td>
        <td>
          <input type="hidden" name="nama_barang[]" value="${item.nama}">
          <div>${item.nama} <span class="badge-po ms-1">dari PO</span></div>
        </td>
        <td>
          <input type="hidden" name="qty_po[]" value="${item.qty_po}">
          <input type="text" class="form-control form-control-sm qty-po" value="${item.qty_po}" readonly>
        </td>
        <td><input type="number" name="qty_terima[]" class="form-control form-control-sm qty-terima" value="${item.qty_po}" min="0"></td>
        <td>
          <input type="hidden" name="satuan[]" value="${item.satuan}">
          <input type="text" class="form-control form-control-sm" value="${item.satuan}" readonly>
        </td>
        <td>
          <select name="kondisi[]" class="form-select form-select-sm">
            <option value="baik">Baik</option>
            <option value="rusak">Rusak</option>
            <option value="sebagian">Sebagian Rusak</option>
          </select>
        </td>
        <td><input type="text" name="keterangan_item[]" class="form-control form-control-sm" placeholder="-"></td>
        <td></td>
      </tr>`;
    }

    function rowExtraTemplate(index) {
      return `
      <tr class="row-extra">
        <td class="row-index">${index}</td>
        <td><input type="text" name="kode_barang[]" class="form-control form-control-sm" placeholder="BRG-XXX"></td>
        <td>
          <input type="text" name="nama_barang[]" class="form-control form-control-sm" placeholder="Nama barang">
          <span class="badge-tambahan mt-1 d-inline-block">Diluar PO</span>
        </td>
        <td><input type="text" class="form-control form-control-sm" value="-" disabled><input type="hidden" name="qty_po[]" value="0"></td>
        <td><input type="number" name="qty_terima[]" class="form-control form-control-sm qty-terima" value="0" min="0"></td>
        <td><input type="text" name="satuan[]" class="form-control form-control-sm" placeholder="pcs"></td>
        <td>
          <select name="kondisi[]" class="form-select form-select-sm">
            <option value="baik">Baik</option>
            <option value="rusak">Rusak</option>
            <option value="sebagian">Sebagian Rusak</option>
          </select>
        </td>
        <td><input type="text" name="keterangan_item[]" class="form-control form-control-sm" placeholder="-"></td>
        <td class="text-center">
          <button type="button" class="btn btn-sm btn-remove-row" title="Hapus baris">
            <i class="bi bi-trash3"></i>
          </button>
        </td>
      </tr>`;
    }

    function reindexRows() {
      bodyItem.querySelectorAll('tr').forEach((tr, i) => {
        tr.querySelector('.row-index').textContent = i + 1;
      });
    }

    function hitungRingkasan() {
      const rows = bodyItem.querySelectorAll('tr');
      let totalQty = 0;
      let semuaSesuai = true;
      let adaTerisi = false;

      rows.forEach(tr => {
        const qtyPoInput = tr.querySelector('input[name="qty_po[]"]');
        const qtyPo = qtyPoInput ? (parseFloat(qtyPoInput.value) || 0) : 0;
        const qtyTerima = parseFloat(tr.querySelector('.qty-terima').value) || 0;
        totalQty += qtyTerima;
        if (qtyPo > 0 || qtyTerima > 0) adaTerisi = true;
        if (qtyTerima < qtyPo) semuaSesuai = false;
      });

      document.getElementById('rcvTotalJenis').textContent = rows.length;
      document.getElementById('rcvTotalQty').textContent = totalQty;

      const statusEl = document.getElementById('rcvStatusSesuai');
      if (!adaTerisi) {
        statusEl.textContent = '-';
        statusEl.className = 'val';
      } else if (semuaSesuai) {
        statusEl.textContent = 'Sesuai PO';
        statusEl.className = 'val qty-diff-ok';
      } else {
        statusEl.textContent = 'Kurang dari PO';
        statusEl.className = 'val qty-diff-bad';
      }
    }

    function renderItemsFromPO(data) {
      bodyItem.innerHTML = '';
      inputSupplier.value = data.supplier || '';

      if (!data.items || data.items.length === 0) {
        poHint.textContent = 'PO ditemukan, tetapi tidak ada item di dalamnya.';
        poHint.classList.add('text-danger');
        emptyState.classList.remove('d-none');
        wrapperTabel.classList.add('d-none');
        btnTambahExtra.disabled = true;
        return;
      }

      data.items.forEach((item, i) => {
        bodyItem.insertAdjacentHTML('beforeend', rowPOTemplate(i + 1, item));
      });

      emptyState.classList.add('d-none');
      wrapperTabel.classList.remove('d-none');
      btnTambahExtra.disabled = false;
      poHint.classList.remove('text-danger');
      poHint.textContent = data.items.length + ' item dimuat otomatis dari PO. Ubah "Qty Diterima" sesuai barang fisik.';

      hitungRingkasan();
    }

    function setLoading(isLoading) {
      poLoading.classList.toggle('active', isLoading);
      iconCariPO.classList.toggle('d-none', isLoading);
      btnCariPO.disabled = isLoading;
    }

    function muatDataPO() {
      const noPO = inputNoPO.value.trim();
      if (!noPO) {
        inputNoPO.focus();
        return;
      }

      setLoading(true);
      poHint.classList.remove('text-danger');
      poHint.textContent = 'Mencari data PO ' + noPO + ' ...';

      fetch(URL_GET_PO + '?no_po=' + encodeURIComponent(noPO), {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(res => {
          if (!res.ok) throw new Error('PO tidak ditemukan');
          return res.json();
        })
        .then(data => renderItemsFromPO(data))
        .catch(() => {
          poHint.classList.add('text-danger');
          poHint.textContent = 'PO tidak ditemukan atau gagal memuat data.';
        })
        .finally(() => setLoading(false));
    }

    btnCariPO.addEventListener('click', muatDataPO);
    inputNoPO.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        muatDataPO();
      }
    });

    btnTambahExtra.addEventListener('click', function() {
      const count = bodyItem.querySelectorAll('tr').length + 1;
      bodyItem.insertAdjacentHTML('beforeend', rowExtraTemplate(count));
      hitungRingkasan();
    });

    bodyItem.addEventListener('click', function(e) {
      const btn = e.target.closest('.btn-remove-row');
      if (!btn) return;
      btn.closest('tr').remove();
      reindexRows();
      hitungRingkasan();
    });

    bodyItem.addEventListener('input', function(e) {
      if (e.target.classList.contains('qty-terima')) hitungRingkasan();
    });
  })();
</script>