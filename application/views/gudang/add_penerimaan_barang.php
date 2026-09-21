<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!--
  PENTING: File ini adalah FRAGMENT yang di-load DI DALAM header.php + footer.php
-->

<style>
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

  <div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <h4><i class="bi bi-box-seam me-2"></i>Penerimaan Barang</h4>
      <small>Formulir pencatatan barang masuk dari Purchase Order</small>
    </div>
    <div class="text-end">
      <div class="fw-bold fs-5">No. Penerimaan: <?php echo isset($no_penerimaan) ? html_escape($no_penerimaan) : ''; ?></div>
      <span class="badge-status badge-draft"><i class="bi bi-clock-history me-1"></i>Draft</span>
    </div>
  </div>

  <!-- NOTIFIKASI ERROR/SUKSES DARI SERVER -->
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      <?= $this->session->flashdata('error'); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i>
      <?= $this->session->flashdata('success'); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php echo form_open('penerimaan_barang/simpan', ['id' => 'formPenerimaan', 'enctype' => 'multipart/form-data']); ?>
  <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

  <!-- Informasi Umum -->
  <div class="card-modern">
    <div class="card-header"><i class="bi bi-info-circle me-2"></i>Informasi Penerimaan</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">No. Purchase Order</label>
          <div class="input-group">
            <input type="text" id="rcvInputNoPO" name="no_po" class="form-control" placeholder="Ketik / pilih No. PO..." list="rcvPoList" autocomplete="off" required>
            <button type="button" class="btn btn-outline-rcv-brand" id="rcvBtnCariPO">
              <span class="spinner-border spinner-border-sm po-loading" id="rcvPoLoading"></span>
              <i class="bi bi-search" id="rcvIconCariPO"></i>
            </button>
          </div>
          <datalist id="rcvPoList"></datalist>
          <div class="form-text" id="rcvPoHint">Ketik "PO" untuk melihat daftar opsi PO atau tekan Enter untuk mencari.</div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Supplier</label>
          <input type="text" id="rcvInputSupplier" class="form-control" placeholder="Otomatis dari PO" readonly>
        </div>
        <div class="col-md-4">
          <label class="form-label">Tanggal Penerimaan</label>
          <input type="date" name="tanggal_terima" class="form-control" value="<?= date('Y-m-d'); ?>" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">No. Surat Jalan Supplier</label>
          <input type="text" name="no_surat_jalan" class="form-control" placeholder="Nomor surat jalan supplier">
        </div>

        <div class="col-8">
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
              <th style="width:6%" class="text-center">Terima?</th>
              <th style="width:10%">Qty Diterima</th>
              <th style="width:8%">Satuan</th>
              <th style="width:14%">Kondisi</th>
              <th style="width:16%">Lokasi Simpan</th>
              <th style="width:14%">Keterangan</th>
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
    <div class="col-md-6">
      <div class="summary-box">
        <div class="text-muted small">Total Jenis Barang</div>
        <div class="val" id="rcvTotalJenis">0</div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="summary-box">
        <div class="text-muted small">Total Qty Diterima</div>
        <div class="val" id="rcvTotalQty">0</div>
      </div>
    </div>
  </div>

  <!-- Aksi -->
  <div class="footer-actions">
    <a href="<?php echo site_url('penerimaan_barang'); ?>" class="btn btn-outline-secondary">
      <i class="bi bi-x-lg me-1"></i>Batal
    </a>
   
    <button type="submit" name="aksi" value="final" class="btn btn-rcv-brand">
      <i class="bi bi-check2-circle me-1"></i>Konfirmasi Penerimaan
    </button>
  </div>

  <?php echo form_close(); ?>
</div>

<script>
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
    const defaultLokasi = document.getElementById('rcvDefaultLocation');
    const poDatalist = document.getElementById('rcvPoList');

    if (!inputNoPO) return;

    const LOCATIONS = <?php echo json_encode($locations ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const BARANG_ALL = <?php echo json_encode($barang_list ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    const URL_GET_PO = '<?= site_url("penerimaan_barang/get_po_items"); ?>';
    const URL_GET_PO_LIST = '<?= site_url("penerimaan_barang/get_po_list"); ?>';

    const DEFAULT_LOCATION_ID = <?= json_encode($default_location_id ?? null); ?>;
    const MAX_FOTO = 5;

    function muatDatalistPO() {
      fetch(URL_GET_PO_LIST)
        .then(res => res.json())
        .then(data => {
          if (Array.isArray(data) && poDatalist) {
            poDatalist.innerHTML = '';
            data.forEach(po => {
              const option = document.createElement('option');
              const noPoVal = po.no_po || po.no_purchase_order || po;
              const suppVal = po.supplier_nama || po.supplier || '';
              option.value = noPoVal;
              option.textContent = suppVal ? `${noPoVal} - ${suppVal}` : noPoVal;
              poDatalist.appendChild(option);
            });
          }
        })
        .catch(err => console.error('Gagal memuat list PO:', err));
    }

    muatDatalistPO();

    function buildLocationOptions(selectedId) {
      let html = '<option value="">-- Pilih Lokasi --</option>';
      LOCATIONS.forEach(loc => {
        const sel = String(loc.id) === String(selectedId) ? 'selected' : '';
        html += `<option value="${loc.id}" ${sel}>${loc.location_code} - ${loc.zone_name}</option>`;
      });
      return html;
    }

    function buildBarangOptions() {
      let html = '<option value="">-- Pilih Barang --</option>';
      BARANG_ALL.forEach(b => {
        const namaBarang = b.nama_barang || b.nama || '-';
        html += `<option value="${b.id}" data-kode="${b.kode_barang}">${namaBarang}</option>`;
      });
      return html;
    }

    function rowPOTemplate(index, item) {
      const namaBarang = item.nama || item.nama_barang || '-';
      const kodeBarang = item.kode || item.kode_barang || '-';
      const qtyPesan = item.qty_pesan !== undefined ? Number(item.qty_pesan) : null;
      const sisa = item.sisa !== undefined ? Number(item.sisa) : qtyPesan;
      const sisaHint = (sisa !== null && !isNaN(sisa))
        ? `<div class="form-text rcv-sisa-hint" data-sisa="${sisa}">Sisa PO: <strong>${sisa}</strong> dari ${qtyPesan}</div>`
        : '';

      const qtyRusakSebelumnya = item.qty_rusak_sebelumnya !== undefined ? Number(item.qty_rusak_sebelumnya) : 0;
      const rusakHint = (qtyRusakSebelumnya > 0)
        ? `<div class="form-text text-danger rcv-rusak-hint">⚠ Sebelumnya diterima rusak: <strong>${qtyRusakSebelumnya}</strong> (perlu diganti/diterima ulang)</div>`
        : '';

      return `
  <tr class="row-po">
    <td class="row-index">${index}</td>
    <td>
      <input type="hidden" name="id_po_detail[]" value="${item.id_po_detail || ''}">
      <input type="hidden" name="id_barang[]" value="${item.id_barang || ''}">
      <input type="text" class="form-control form-control-sm" value="${kodeBarang}" readonly>
    </td>
    <td>
      <div>${namaBarang} <span class="badge bg-info ms-1">dari PO</span></div>
    </td>
    <td class="text-center">
      <input type="checkbox" class="form-check-input rcv-item-check" checked>
    </td>
    <td>
      <input type="number" step="any" name="qty_diterima[]" class="form-control form-control-sm qty-terima" value="0" min="0" ${sisa !== null && !isNaN(sisa) ? `max="${sisa}"` : ''}>
      ${sisaHint}
      ${rusakHint}
      <div class="form-text text-danger d-none rcv-qty-hint">Melebihi sisa PO!</div>
    </td>
    <td>
      <input type="hidden" name="satuan[]" value="${item.satuan || ''}">
      <input type="text" class="form-control form-control-sm" value="${item.satuan || ''}" readonly>
    </td>
    <td>
      <select name="kondisi[]" class="form-select form-select-sm rcv-kondisi">
        <option value="baik">Baik</option>
        <option value="rusak">Rusak</option>
        <option value="sebagian">Sebagian Rusak</option>
      </select>
      <input type="file" name="foto_kondisi[${index - 1}][]" multiple accept="image/jpeg,image/png" class="form-control form-control-sm mt-1 rcv-foto d-none">
      <div class="form-text text-danger d-none rcv-foto-hint">Wajib foto barang rusak &mdash; boleh pilih lebih dari 1 file sekaligus (jpg/png, maks 2MB per file, maks ${MAX_FOTO} foto)</div>
      <div class="form-text text-success d-none rcv-foto-list"></div>
    </td>
    <td><select name="id_location[]" class="form-select form-select-sm rcv-lokasi" required>${buildLocationOptions(DEFAULT_LOCATION_ID)}</select></td>
    <td><input type="text" name="keterangan_item[]" class="form-control form-control-sm" placeholder="-"></td>
    <td></td>
  </tr>`;
    }

    function rowExtraTemplate(index) {
      return `
      <tr class="row-extra">
        <td class="row-index">${index}</td>
        <td>
          <input type="hidden" name="id_po_detail[]" value="">
          <input type="text" class="form-control form-control-sm rcv-kode-extra" placeholder="Otomatis" readonly>
        </td>
        <td>
          <select name="id_barang[]" class="form-select form-select-sm rcv-barang-extra">${buildBarangOptions()}</select>
          <span class="badge-tambahan mt-1 d-inline-block">Diluar PO</span>
        </td>
        <td class="text-center">
          <input type="checkbox" class="form-check-input rcv-item-check" checked title="Centang jika barang ini benar-benar diterima">
        </td>
        <td><input type="number" step="any" name="qty_diterima[]" class="form-control form-control-sm qty-terima" value="0" min="0"></td>
        <td><input type="text" name="satuan[]" class="form-control form-control-sm" placeholder="pcs"></td>
        <td>
          <select name="kondisi[]" class="form-select form-select-sm rcv-kondisi">
            <option value="baik">Baik</option>
            <option value="rusak">Rusak</option>
            <option value="sebagian">Sebagian Rusak</option>
          </select>
          <input type="file" name="foto_kondisi[${index - 1}][]" multiple accept="image/jpeg,image/png" class="form-control form-control-sm mt-1 rcv-foto d-none">
          <div class="form-text text-danger d-none rcv-foto-hint">Wajib foto barang rusak &mdash; boleh pilih lebih dari 1 file sekaligus (jpg/png, maks 2MB per file, maks ${MAX_FOTO} foto)</div>
          <div class="form-text text-success d-none rcv-foto-list"></div>
        </td>
        <td><select name="id_location[]" class="form-select form-select-sm rcv-lokasi" required>${buildLocationOptions(DEFAULT_LOCATION_ID)}</select></td>
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
      reindexFotoInputs();
    }

    function reindexFotoInputs() {
      bodyItem.querySelectorAll('tr').forEach((tr, i) => {
        const fileInput = tr.querySelector('.rcv-foto');
        if (fileInput) fileInput.setAttribute('name', `foto_kondisi[${i}][]`);
      });
    }

    function hitungRingkasan() {
      const rows = bodyItem.querySelectorAll('tr');
      let totalQty = 0;

      rows.forEach(tr => {
        const qtyTerima = parseFloat(tr.querySelector('.qty-terima').value) || 0;
        totalQty += qtyTerima;
      });

      document.getElementById('rcvTotalJenis').textContent = rows.length;
      document.getElementById('rcvTotalQty').textContent = totalQty;
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
        .then(response => {
          if (!response.ok) {
            throw new Error('HTTP status ' + response.status);
          }
          return response.json();
        })
        .then(data => {
          setLoading(false);

          if (data.status) {
            poHint.textContent = 'Data PO berhasil dimuat.';
            inputSupplier.value = data.supplier || '-';

            if (data.items && data.items.length > 0) {
              bodyItem.innerHTML = '';

              data.items.forEach((item, index) => {
                bodyItem.insertAdjacentHTML('beforeend', rowPOTemplate(index + 1, item));
              });

              reindexFotoInputs();

              emptyState.classList.add('d-none');
              wrapperTabel.classList.remove('d-none');
              btnTambahExtra.disabled = false;
              poHint.textContent = data.items.length + ' item dimuat otomatis dari PO. Isi "Qty Diterima" sesuai barang fisik.';

              hitungRingkasan();
            } else {
              poHint.textContent = 'PO ditemukan, tetapi tidak ada item di dalamnya.';
              poHint.classList.add('text-danger');
              emptyState.classList.remove('d-none');
              wrapperTabel.classList.add('d-none');
              btnTambahExtra.disabled = true;
            }

          } else {
            poHint.classList.add('text-danger');
            poHint.textContent = data.message || 'PO tidak ditemukan.';
            emptyState.classList.remove('d-none');
            wrapperTabel.classList.add('d-none');
            btnTambahExtra.disabled = true;
          }
        })
        .catch(err => {
          setLoading(false);
          poHint.classList.add('text-danger');
          poHint.textContent = 'Gagal memuat data PO (Server Error/URL tidak valid).';
          console.error('Fetch Error:', err);
        });
    }

    btnCariPO.addEventListener('click', muatDataPO);

    inputNoPO.addEventListener('change', function() {
      if (this.value.trim() !== '') {
        muatDataPO();
      }
    });

    inputNoPO.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        muatDataPO();
      }
    });

    btnTambahExtra.addEventListener('click', function() {
      const count = bodyItem.querySelectorAll('tr').length + 1;
      bodyItem.insertAdjacentHTML('beforeend', rowExtraTemplate(count));
      reindexFotoInputs();
      hitungRingkasan();
    });

    bodyItem.addEventListener('click', function(e) {
      const btn = e.target.closest('.btn-remove-row');
      if (!btn) return;
      btn.closest('tr').remove();
      reindexRows();
      hitungRingkasan();
    });

    function validasiQtyRow(qtyInput) {
      const td = qtyInput.closest('td');
      const hint = td ? td.querySelector('.rcv-qty-hint') : null;
      const max = qtyInput.getAttribute('max');

      if (max === null || max === '') {
        return true;
      }

      const melebihi = parseFloat(qtyInput.value || 0) > parseFloat(max);

      qtyInput.classList.toggle('is-invalid', melebihi);
      if (hint) hint.classList.toggle('d-none', !melebihi);

      return !melebihi;
    }

    bodyItem.addEventListener('input', function(e) {
      if (e.target.classList.contains('qty-terima')) {
        hitungRingkasan();
        validasiQtyRow(e.target);
      }
    });

    const formPenerimaan = document.getElementById('formPenerimaan');
    if (formPenerimaan) {
      formPenerimaan.addEventListener('submit', function(e) {
        // Wajib Sinkronkan Index Foto Sebelum Form Dikirim
        reindexFotoInputs();

        let semuaValid = true;
        let pesanError = 'Ada kesalahan input data. Perbaiki dulu sebelum menyimpan.';

        bodyItem.querySelectorAll('.qty-terima').forEach(function(input) {
          if (!validasiQtyRow(input)) {
            semuaValid = false;
            pesanError = 'Ada baris dengan qty diterima melebihi sisa PO.';
          }
        });

        bodyItem.querySelectorAll('tr').forEach(function(tr, i) {
          const kondisiEl = tr.querySelector('.rcv-kondisi');
          const fileInput = tr.querySelector('.rcv-foto');
          const checkEl = tr.querySelector('.rcv-item-check');

          if (!kondisiEl || !fileInput) return;
          if (checkEl && !checkEl.checked) return;

          // Cek jika kondisi barang rusak atau sebagian rusak
          if (kondisiEl.value === 'rusak' || kondisiEl.value === 'sebagian') {
            const jml = fileInput.files ? fileInput.files.length : 0;

            if (jml < 1) {
              semuaValid = false;
              pesanError = 'Baris ke-' + (i + 1) + ': Minimal 1 foto wajib diupload untuk barang berkondisi "Rusak".';
            } else if (jml > MAX_FOTO) {
              semuaValid = false;
              pesanError = 'Baris ke-' + (i + 1) + ': Maksimal ' + MAX_FOTO + ' foto per barang.';
            }
          }
        });

        if (!semuaValid) {
          e.preventDefault();
          alert(pesanError); // Notifikasi langsung ke user
          poHint.classList.add('text-danger');
          poHint.textContent = pesanError;
          poHint.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
          });
        }
      });
    }

    function toggleFotoRequirement(selectEl) {
      const td = selectEl.closest('td');
      const fileInput = td.querySelector('.rcv-foto');
      const hint = td.querySelector('.rcv-foto-hint');
      const list = td.querySelector('.rcv-foto-list');
      if (!fileInput) return;

      const wajibFoto = (selectEl.value === 'rusak' || selectEl.value === 'sebagian');
      fileInput.classList.toggle('d-none', !wajibFoto);
      fileInput.required = wajibFoto;
      if (hint) hint.classList.toggle('d-none', !wajibFoto);

      if (!wajibFoto) {
        fileInput.value = '';
        if (list) {
          list.textContent = '';
          list.classList.add('d-none');
        }
      }
    }

    function tampilkanDaftarFoto(fileInput) {
      const td = fileInput.closest('td');
      const list = td ? td.querySelector('.rcv-foto-list') : null;
      if (!list) return;

      const files = fileInput.files;

      if (!files || files.length === 0) {
        list.textContent = '';
        list.classList.add('d-none');
        return;
      }

      if (files.length > MAX_FOTO) {
        list.classList.remove('d-none', 'text-success');
        list.classList.add('text-danger');
        list.textContent = 'Terlalu banyak foto (' + files.length + '). Maksimal ' + MAX_FOTO + ' foto per barang.';
        return;
      }

      const nama = Array.from(files).map(f => f.name).join(', ');
      list.classList.remove('d-none', 'text-danger');
      list.classList.add('text-success');
      list.textContent = files.length + ' foto dipilih: ' + nama;
    }

    bodyItem.addEventListener('change', function(e) {
      if (e.target.classList.contains('rcv-kondisi')) {
        toggleFotoRequirement(e.target);
      }

      if (e.target.classList.contains('rcv-foto')) {
        tampilkanDaftarFoto(e.target);
      }
    });

    bodyItem.addEventListener('change', function(e) {
      if (!e.target.classList.contains('rcv-item-check')) return;

      const tr = e.target.closest('tr');
      const enabled = e.target.checked;
      const qtyInput = tr.querySelector('.qty-terima');
      const kondisiEl = tr.querySelector('.rcv-kondisi');
      const fileInput = tr.querySelector('.rcv-foto');

      qtyInput.readOnly = !enabled;
      tr.classList.toggle('opacity-50', !enabled);

      if (!enabled) {
        qtyInput.value = 0;
        if (fileInput) fileInput.required = false;
      } else if (kondisiEl) {
        toggleFotoRequirement(kondisiEl);
      }

      hitungRingkasan();
    });

    bodyItem.addEventListener('change', function(e) {
      if (e.target.classList.contains('rcv-barang-extra')) {
        const opt = e.target.selectedOptions[0];
        const kodeInput = e.target.closest('tr').querySelector('.rcv-kode-extra');
        if (kodeInput) kodeInput.value = opt ? (opt.dataset.kode || '') : '';
      }
    });

    if (defaultLokasi) {
      defaultLokasi.addEventListener('change', function() {
        if (!this.value) return;
        bodyItem.querySelectorAll('.rcv-lokasi').forEach(sel => {
          sel.value = this.value;
        });
      });
    }
  })();
</script>