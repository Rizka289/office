<!-- Stylings Khusus Halaman PO -->
<style>
  .po-wrapper {
    --po-ink: #1b2430;
    --po-slate: #4a5568;
    --po-mist: #8b98a9;
    --po-line: #dde1e6;
    --po-brand: #0b5d5b;
    --po-brand-light: #e6f2f1;
    --po-rust: #b5622a;
    --po-rust-ink: #fbeee2;
    --po-ok: #198754;
    --po-mono: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
  }

  .stat-card {
    background: #fff;
    border: 1px solid var(--po-line);
    border-radius: 8px;
    padding: 12px 16px;
  }

  .stat-card .n {
    font-size: 20px;
    font-weight: 700;
    font-family: var(--po-mono);
  }

  .stat-card .l {
    font-size: 11px;
    color: var(--po-mist);
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-top: 2px;
  }

  .po-no {
    font-family: var(--po-mono);
    font-weight: 700;
    color: var(--po-brand);
  }

  .badge-status {
    display: inline-block;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: .04em;
  }

  .badge-status.menunggu {
    background: var(--po-rust-ink);
    color: var(--po-rust);
  }

  .badge-status.lolos {
    background: #e6f2ea;
    color: var(--po-ok);
  }

  .badge-status.ditolak {
    background: #fbe9e9;
    color: #b3261e;
  }

  .amount-col {
    font-family: var(--po-mono);
    font-weight: 600;
    text-align: right;
  }

  .items-table th {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--po-mist);
    font-weight: 600;
  }

  .items-table .col-qty input,
  .items-table .col-price input {
    text-align: right;
    font-family: var(--po-mono);
  }

  .items-table .col-sub {
    font-family: var(--po-mono);
    text-align: right;
    font-weight: 600;
    white-space: nowrap;
  }

  .addrow-btn {
    border: 1px dashed var(--po-line);
    background: transparent;
    color: var(--po-slate);
    padding: 9px 14px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    width: 100%;
    transition: all 0.2s;
  }

  .addrow-btn:hover {
    border-color: var(--po-brand);
    color: var(--po-brand);
    background: var(--po-brand-light);
  }

  .total-row-val {
    font-family: var(--po-mono);
    font-size: 20px;
    font-weight: 700;
    color: var(--po-brand);
  }

  .po-toast {
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%) translateY(20px);
    background: #1b2430;
    color: #fff;
    padding: 12px 20px;
    border-radius: 8px;
    font-size: 13.5px;
    opacity: 0;
    pointer-events: none;
    transition: all .25s ease;
    font-weight: 600;
    z-index: 9999;
  }

  .po-toast.show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }

  .supplier-search-wrap,
  .barang-search-wrap {
    position: relative;
  }

  .supplier-search-wrap .bi-search {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--po-mist);
    font-size: 13px;
    pointer-events: none;
  }

  .supplier-search-wrap input#f_supplier_search {
    padding-left: 34px;
  }

  .supplier-search-wrap input#f_supplier_search.is-linked,
  .barang-search-wrap input.it-barang-search.is-linked {
    border-color: var(--po-ok);
    background: #f4faf6;
  }

  .supplier-suggest-box {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    z-index: 50;
    background: #fff;
    border: 1px solid var(--po-line);
    border-radius: 8px;
    box-shadow: 0 8px 20px rgba(27, 36, 48, .12);
    max-height: 260px;
    overflow-y: auto;
  }

  /* .barang-suggest-box sengaja dibuat position:fixed (bukan absolute di dalam
     .barang-search-wrap) karena wrapper-nya berada di dalam .table-responsive.
     Bootstrap men-set overflow-x:auto pada .table-responsive, dan begitu salah
     satu axis di-set auto, browser otomatis meng-clip axis satunya juga —
     akibatnya dropdown saran barang kepotong/ketindih baris tabel dan baru
     kelihatan kalau discroll manual. Dengan position:fixed + posisi dihitung
     lewat JS (lihat positionBarangBox()), box ini "keluar" dari alur/clip
     table-responsive sehingga selalu tampil responsif mengikuti input aktif. */
  .barang-suggest-box {
    position: fixed;
    z-index: 2000;
    background: #fff;
    border: 1px solid var(--po-line);
    border-radius: 8px;
    box-shadow: 0 8px 20px rgba(27, 36, 48, .12);
    max-height: 260px;
    overflow-y: auto;
  }

  .supplier-suggest-item {
    padding: 9px 14px;
    cursor: pointer;
    border-bottom: 1px solid #f0f2f4;
  }

  .supplier-suggest-item:last-child {
    border-bottom: none;
  }

  .supplier-suggest-item:hover,
  .supplier-suggest-item.active {
    background: var(--po-brand-light);
  }

  .supplier-suggest-item .s-name {
    font-weight: 600;
    font-size: 13.5px;
    color: var(--po-ink);
  }

  .supplier-suggest-item .s-meta {
    font-size: 11.5px;
    color: var(--po-mist);
  }

  .supplier-suggest-empty {
    padding: 14px;
    font-size: 13px;
    color: var(--po-mist);
    text-align: center;
  }

  .supplier-linked-tag {
    font-size: 10.5px;
    font-weight: 700;
    color: var(--po-ok);
    text-transform: uppercase;
    letter-spacing: .04em;
  }
</style>

<div class="po-wrapper">

  <!-- ============ LIST VIEW ============ -->
  <div id="listView">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>

      </div>
      <button class="btn btn-brand d-flex align-items-center gap-2" onclick="openForm()">
        <i class="bi bi-plus-lg"></i> <?= translate('add') ?>
      </button>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="n" id="statTotal">0</div>
          <div class="l">Total PO</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="n" id="statDraft">0</div>
          <div class="l">Menunggu QC</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="n" id="statSent">0</div>
          <div class="l">Lolos QC</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="n" id="statValue">Rp 0</div>
          <div class="l">Total Nilai</div>
        </div>
      </div>
    </div>

    <!-- Tabel Data PO -->
    <div class="card card-custom overflow-hidden">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th><?= translate('no') ?></th>
              <th><?= translate('list_pemasok') ?></th>
              <th><?= translate('tgl_tempo') ?></th>
              <th><?= translate('status') ?></th>
              <th class="text-end"><?= translate('nilai') ?></th>
              <th class="text-center" style="width: 130px;"><?= translate('aksi') ?></th>
            </tr>
          </thead>
          <tbody id="poTableBody"></tbody>
        </table>
      </div>

      <div class="text-center py-5 d-none" id="emptyState">
        <p class="text-muted mb-3"><?= translate('placeholder_po') ?></p>
        <button class="btn btn-brand" onclick="openForm()">
          <i class="bi bi-plus-lg me-1"></i> <?= translate('add_po') ?>
        </button>
      </div>
    </div>
  </div>

  <!-- ============ FORM / DETAIL VIEW ============ -->
  <div id="formView" class="d-none">
    <div class="mb-3 d-flex justify-content-between align-items-center">
      <button class="btn btn-link text-decoration-none text-secondary p-0 fw-semibold" onclick="closeForm()">
        <i class="bi bi-arrow-left me-1"></i> <?= translate('kembali') ?>
      </button>
      <h5 class="mb-0 fw-bold" id="formTitle">Form Purchase Order</h5>
    </div>

    <!-- Informasi PO -->
    <div class="card card-custom p-4 mb-4">
      <h6 class="text-uppercase text-muted fw-bold border-bottom pb-2 mb-3" style="font-size: 12px; letter-spacing: 0.08em;"><?= translate('informasi') . ' ' . translate('app_purchasing')?></h6>
      <input type="hidden" id="f_id_po" value="">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label small fw-semibold"><?= translate('no') . ' ' . translate('app_purchasing') ?></label>
          <input type="text" class="form-control bg-light" id="f_nopo" readonly>
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold"><?= translate('tgl_tempo') ?></label>
          <input type="date" class="form-control" id="f_date">
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold d-flex justify-content-between align-items-center">
            <span><?= translate('list_pemasok') ?></span>
            <span class="supplier-linked-tag d-none" id="supplierLinkedTag"><i class="bi bi-link-45deg"></i> Terhubung</span>
          </label>
          <div class="supplier-search-wrap">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" id="f_supplier_search" placeholder="<?= translate('placeholder_supplier') .' ...' ?>" autocomplete="off">
            <input type="hidden" id="f_id_supplier" value="">
            <div class="supplier-suggest-box d-none" id="supplierSuggestBox"></div>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold"><?= translate('deskripsi') ?></label>
          <textarea class="form-control" id="f_note" rows="3" placeholder="<?= translate('placeholder_deskripsi') ?>"></textarea>
        </div>
      </div>
    </div>

    <!-- Detail Barang -->
    <div class="card card-custom p-4 mb-4">
      <h6 class="text-uppercase text-muted fw-bold border-bottom pb-2 mb-3" style="font-size: 12px; letter-spacing: 0.08em;">Detail Barang</h6>
      <div class="table-responsive">
        <table class="table align-middle items-table mb-2">
          <thead>
            <tr>
              <th style="width:34%"><?= translate('nama_barang') ?></th>
              <th style="width:14%"><?= translate('qty') ?></th>
              <th style="width:14%"><?= translate('satuan') ?></th>
              <th style="width:14%"><?= translate('harga') ?></th>
              <th style="width:16%" class="text-end"><?= translate('subtotal') ?></th>
              <th style="width:4%"></th>
            </tr>
          </thead>
          <tbody id="itemsBody"></tbody>
        </table>
      </div>

      <button class="addrow-btn mb-4" id="btnAddRow" onclick="addItemRow()"><?= translate('+tambah') ?></button>

      <div class="d-flex justify-content-end align-items-center gap-3 pt-3 border-top">
        <span class="text-secondary small fw-semibold"><?= translate('total') . ' ' . translate('app_purchasing') ?></span>
        <span class="total-row-val" id="grandTotal">Rp 0</span>
      </div>
    </div>

    <!-- Tombol Aksi Form -->
    <div class="d-flex justify-content-between align-items-center">
      <div class="text-muted small">
        <i class="bi bi-info-circle me-1"></i> Pastikan semua data terisi dengan benar.
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-danger d-none" id="deleteBtn" onclick="deleteCurrentPO()">Hapus PO</button>
        <button class="btn btn-light border" onclick="closeForm()"><?= translate('btn_batal') ?></button>
        <button class="btn btn-brand" id="saveBtn" onclick="savePO()"><?= translate('button_save') ?></button>
      </div>
    </div>
  </div>

</div>

<!-- Element Toast -->
<div class="po-toast" id="toast"></div>

<!-- Box saran pencarian barang: sengaja diletakkan di luar .table-responsive
     (satu elemen dipakai bergantian oleh semua baris) supaya tidak ke-clip
     oleh overflow tabel. Posisinya diatur lewat JS mengikuti input aktif. -->
<div class="barang-suggest-box d-none" id="barangSuggestBox"></div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-body text-center p-4">
        <i class="bi bi-exclamation-triangle text-danger display-4 mb-2"></i>
        <h6 class="fw-bold">Konfirmasi Hapus</h6>
        <p class="small text-muted mb-4">Apakah Anda yakin ingin menghapus PO ini? Data yang dihapus tidak bisa dikembalikan.</p>
        <div class="d-flex justify-content-center gap-2">
          <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-danger btn-sm px-3" id="confirmDeleteBtn">Ya, Hapus</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const supplierList = <?php echo json_encode($supplier ?? [], JSON_UNESCAPED_UNICODE); ?>;
  const barangList = <?php echo json_encode($barang ?? [], JSON_UNESCAPED_UNICODE); ?>;
  const satuanOptions = <?php echo json_encode($satuan_options ?? [], JSON_UNESCAPED_UNICODE); ?>;

  const SITE_URL = "<?php echo site_url(); ?>/";
  const CSRF_NAME = "<?php echo $csrf_name ?? 'csrf_test_name'; ?>";
  let csrfHash = "<?php echo $csrf_hash ?? ''; ?>";

  let purchaseOrders = [];
  let itemSeq = 0;
  let supplierActiveIndex = -1;
  let currentSearch = '';
  let currentPage = 1;
  let totalRecords = 0;
  let targetDeleteId = null;

  function fmtRupiah(n) {
    return 'Rp ' + Math.round(n).toLocaleString('id-ID');
  }

  function todayISO() {
    return new Date().toISOString().slice(0, 10);
  }

  function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2200);
  }

  async function fetchPOList(search = '', page = 1) {
    const url = `${SITE_URL}purchase_order/list_data?search=${encodeURIComponent(search)}&page=${page}`;
    const res = await fetch(url, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    const json = await res.json();
    if (json.csrf_hash) csrfHash = json.csrf_hash;
    return json;
  }

  async function fetchPODetail(id) {
    const res = await fetch(`${SITE_URL}purchase_order/get_detail/${id}`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    const json = await res.json();
    if (json.csrf_hash) csrfHash = json.csrf_hash;
    return json;
  }

  async function submitPO(payloadData) {
    payloadData[CSRF_NAME] = csrfHash;
    const res = await fetch(`${SITE_URL}purchase_order/simpan`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: new URLSearchParams(buildFormDataParams(payloadData)).toString()
    });
    const json = await res.json();
    if (json.csrf_hash) csrfHash = json.csrf_hash;
    return json;
  }

  async function requestDeletePO(id) {
    const res = await fetch(`${SITE_URL}purchase_order/hapus/${id}`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: `${CSRF_NAME}=${csrfHash}`
    });
    const json = await res.json();
    if (json.csrf_hash) csrfHash = json.csrf_hash;
    return json;
  }

  async function loadAndRenderList() {
    try {
      const json = await fetchPOList(currentSearch, currentPage);
      if (json.status) {
        purchaseOrders = json.data || [];
        totalRecords = json.total || purchaseOrders.length;
        renderList();
      } else {
        showToast(json.message || 'Gagal memuat data PO');
      }
    } catch (err) {
      console.error(err);
      showToast('Tidak bisa terhubung ke server');
    }
  }

  function renderList() {
    const body = document.getElementById('poTableBody');
    const empty = document.getElementById('emptyState');
    body.innerHTML = '';

    if (purchaseOrders.length === 0) {
      empty.classList.remove('d-none');
    } else {
      empty.classList.add('d-none');
    }

    purchaseOrders.forEach(po => {
      const total = parseFloat(po.total_nilai) || 0;
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="po-no">${escapeHTML(po.no_po)}</td>
        <td>${po.nama_supplier ? escapeHTML(po.nama_supplier) : '<span class="text-muted">Belum diisi</span>'}</td>
        <td>${po.tanggal_jatuh_tempo ? formatDateID(po.tanggal_jatuh_tempo) : '-'}</td>
        <td><span class="badge-status ${po.status_qc}">${statusLabel(po.status_qc)}</span></td>
        <td class="amount-col">${fmtRupiah(total)}</td>
        <td class="text-center">
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary" title="Detail PO" onclick="openViewPO(${po.id})"><i class="bi bi-eye"></i></button>
            <button class="btn btn-outline-primary" title="Edit PO" onclick="openEditPO(${po.id})"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-outline-danger" title="Hapus PO" onclick="confirmDelete(${po.id})"><i class="bi bi-trash"></i></button>
          </div>
        </td>
      `;
      body.appendChild(tr);
    });

    document.getElementById('statTotal').textContent = totalRecords;
    document.getElementById('statDraft').textContent = purchaseOrders.filter(p => p.status_qc === 'menunggu').length;
    document.getElementById('statSent').textContent = purchaseOrders.filter(p => p.status_qc === 'lolos').length;
    const totalValue = purchaseOrders.reduce((s, po) => s + (parseFloat(po.total_nilai) || 0), 0);
    document.getElementById('statValue').textContent = fmtRupiah(totalValue);
  }

  /* ---------- Mode Form (Baru, View, Edit) ---------- */
  function setFormReadonly(readonly) {
    document.getElementById('f_date').disabled = readonly;
    document.getElementById('f_supplier_search').disabled = readonly;
    document.getElementById('f_note').disabled = readonly;
    document.getElementById('btnAddRow').style.display = readonly ? 'none' : 'block';
    document.getElementById('saveBtn').style.display = readonly ? 'none' : 'inline-block';
  }

  function openForm() {
    document.getElementById('listView').classList.add('d-none');
    document.getElementById('formView').classList.remove('d-none');
    document.getElementById('formTitle').textContent = <?= json_encode(translate('add') . ' ' . translate('app_purchasing')) ?>;
    setFormReadonly(false);

    document.getElementById('itemsBody').innerHTML = '';
    document.getElementById('f_id_po').value = '';
    document.getElementById('f_nopo').value = 'Dibuat otomatis saat disimpan';
    document.getElementById('f_date').value = todayISO();
    document.getElementById('f_supplier_search').value = '';
    document.getElementById('f_note').value = '';
    document.getElementById('deleteBtn').classList.add('d-none');
    clearSupplierLink();
    addItemRow();
    updateGrandTotal();
  }

  async function openViewPO(id) {
    const json = await fetchPODetail(id);
    if (!json.status) {
      showToast(json.message);
      return;
    }

    openForm();
    document.getElementById('formTitle').textContent = 'Detail Purchase Order';
    setFormReadonly(true);

    fillFormWithData(json.data);
  }

  async function openEditPO(id) {
    const json = await fetchPODetail(id);
    if (!json.status) {
      showToast(json.message);
      return;
    }

    openForm();
    document.getElementById('formTitle').textContent = 'Edit Purchase Order';
    setFormReadonly(false);
    document.getElementById('deleteBtn').classList.remove('d-none');

    fillFormWithData(json.data);
  }

  function fillFormWithData(data) {
    const header = data.header;
    const items = data.items || [];

    document.getElementById('f_id_po').value = header.id;
    document.getElementById('f_nopo').value = header.no_po;
    document.getElementById('f_date').value = header.tanggal_jatuh_tempo;
    document.getElementById('f_note').value = header.keterangan || '';

    selectSupplier(header.id_supplier, header.nama_supplier);

    const itemsBody = document.getElementById('itemsBody');
    itemsBody.innerHTML = '';

    items.forEach(item => {
      addItemRow({
        id_barang: item.id_barang,
        nama: item.nama_barang,
        qty: item.qty,
        unit: item.satuan,
        price: item.harga
      });
    });

    // Sesuaikan status disabled input barang jika sedang mode readonly/view
    const isReadonly = document.getElementById('saveBtn').style.display === 'none';
    if (isReadonly) {
      itemsBody.querySelectorAll('input, select, button').forEach(el => el.disabled = true);
    }
  }

  function closeForm() {
    document.getElementById('formView').classList.add('d-none');
    document.getElementById('listView').classList.remove('d-none');
    loadAndRenderList();
  }

  /* ---------- Hapus Data ---------- */
  function confirmDelete(id) {
    targetDeleteId = id;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
  }

  document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
    if (!targetDeleteId) return;
    const json = await requestDeletePO(targetDeleteId);
    const modalEl = document.getElementById('deleteModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    modal.hide();

    if (json.status) {
      showToast(json.message);
      loadAndRenderList();
    } else {
      showToast(json.message);
    }
  });

  function deleteCurrentPO() {
    const id = document.getElementById('f_id_po').value;
    if (id) {
      closeForm();
      confirmDelete(id);
    }
  }

  /* ---------- Handling Supplier Autocomplete ---------- */
  function normalizeSupplier(s) {
    return {
      id: s.id ?? s.id_supplier ?? '',
      nama: s.nama_supplier ?? s.nama ?? '',
      meta: [s.no_telp || s.telepon || s.kontak || '', s.alamat || ''].filter(Boolean).join(' • ')
    };
  }

  function searchSupplier(keyword) {
    const kw = (keyword || '').trim().toLowerCase();
    const list = (supplierList || []).map(normalizeSupplier);
    if (!kw) return list.slice(0, 8);
    return list.filter(s => s.nama.toLowerCase().includes(kw)).slice(0, 8);
  }

  function renderSupplierSuggestions(list) {
    const box = document.getElementById('supplierSuggestBox');
    supplierActiveIndex = -1;

    if (list.length === 0) {
      box.innerHTML = `<div class="supplier-suggest-empty">Supplier tidak ditemukan</div>`;
    } else {
      box.innerHTML = list.map((s, i) => `
        <div class="supplier-suggest-item" data-idx="${i}" data-id="${s.id}" data-nama="${escapeHTML(s.nama)}"
             onmousedown="selectSupplier('${s.id}', '${escapeHTML(s.nama).replace(/'/g, "\\'")}')">
          <div class="s-name">${escapeHTML(s.nama)}</div>
          ${s.meta ? `<div class="s-meta">${escapeHTML(s.meta)}</div>` : ''}
        </div>
      `).join('');
    }
    box.classList.remove('d-none');
  }

  function selectSupplier(id, nama) {
    document.getElementById('f_id_supplier').value = id;
    document.getElementById('f_supplier_search').value = nama;
    document.getElementById('f_supplier_search').classList.add('is-linked');
    document.getElementById('supplierLinkedTag').classList.remove('d-none');
    document.getElementById('supplierSuggestBox').classList.add('d-none');
  }

  function clearSupplierLink() {
    document.getElementById('f_id_supplier').value = '';
    document.getElementById('f_supplier_search').classList.remove('is-linked');
    document.getElementById('supplierLinkedTag').classList.add('d-none');
  }

  function initSupplierSearch() {
    const input = document.getElementById('f_supplier_search');
    const box = document.getElementById('supplierSuggestBox');

    input.addEventListener('input', () => {
      clearSupplierLink();
      renderSupplierSuggestions(searchSupplier(input.value));
    });

    input.addEventListener('focus', () => renderSupplierSuggestions(searchSupplier(input.value)));

    document.addEventListener('click', (e) => {
      if (!e.target.closest('.supplier-search-wrap')) box.classList.add('d-none');
    });
  }
  initSupplierSearch();

  /* ---------- Item Rows (Detail Barang) ---------- */
  function addItemRow(prefill) {
    itemSeq++;
    const rowId = 'row_' + itemSeq;
    const tr = document.createElement('tr');
    tr.id = rowId;
    tr.innerHTML = `
      <td>
        <div class="barang-search-wrap">
          <input type="text" class="form-control form-control-sm it-barang-search" placeholder="Ketik untuk cari barang..." autocomplete="off"
                 value="${prefill ? escapeHTML(prefill.nama) : ''}">
          <input type="hidden" class="it-id-barang" value="${prefill ? prefill.id_barang : ''}">
        </div>
      </td>
      <td class="col-qty"><input type="number" min="0" class="form-control form-control-sm it-qty" value="${prefill ? prefill.qty : 1}" oninput="updateGrandTotal()"></td>
      <td><select class="form-select form-select-sm it-unit">${renderSatuanOptions(prefill ? prefill.unit : '')}</select></td>
      <td class="col-price"><input type="number" min="0" class="form-control form-control-sm it-price" value="${prefill ? prefill.price : 0}" oninput="updateGrandTotal()"></td>
      <td class="col-sub" id="sub_${rowId}">Rp 0</td>
      <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-row" onclick="removeItemRow('${rowId}')"><i class="bi bi-trash"></i></button></td>
    `;
    document.getElementById('itemsBody').appendChild(tr);

    if (prefill && prefill.id_barang) {
      tr.querySelector('.it-barang-search').classList.add('is-linked');
    }
    initBarangRowSearch(tr);
    updateGrandTotal();
  }

  function normalizeBarang(b) {
    return {
      id: b.id,
      kode: b.kode_barang || '',
      nama: b.nama || '',
      kategori: b.nama_kategori || ''
    };
  }

  function searchBarang(keyword) {
    const kw = (keyword || '').trim().toLowerCase();
    const list = (barangList || []).map(normalizeBarang);
    if (!kw) return list.slice(0, 8);
    return list.filter(b => b.nama.toLowerCase().includes(kw) || b.kode.toLowerCase().includes(kw)).slice(0, 8);
  }

  /* Satu box saran barang dipakai bergantian oleh semua baris (lihat komentar
     CSS/HTML-nya). activeBarangInput menyimpan input mana yang sedang aktif
     supaya box tahu harus reposisi/hide mengikuti input yang mana. */
  let activeBarangInput = null;

  function positionBarangBox() {
    const box = document.getElementById('barangSuggestBox');
    if (!activeBarangInput || box.classList.contains('d-none')) return;
    const rect = activeBarangInput.getBoundingClientRect();
    box.style.left = rect.left + 'px';
    box.style.top = (rect.bottom + 4) + 'px';
    box.style.width = rect.width + 'px';
  }

  function hideBarangBox() {
    document.getElementById('barangSuggestBox').classList.add('d-none');
    activeBarangInput = null;
  }

  // Reposisi tiap kali di-scroll (termasuk scroll horizontal/vertikal di
  // dalam .table-responsive) atau saat window di-resize, supaya box selalu
  // menempel pas di bawah input yang sedang dicari — bukan ketinggalan/ketindih.
  window.addEventListener('scroll', positionBarangBox, true);
  window.addEventListener('resize', positionBarangBox);

  // Klik di luar wrapper pencarian barang ATAU di luar box saran -> tutup box.
  document.addEventListener('mousedown', (e) => {
    if (!e.target.closest('.barang-search-wrap') && !e.target.closest('#barangSuggestBox')) {
      hideBarangBox();
    }
  });

  function initBarangRowSearch(tr) {
    const input = tr.querySelector('.it-barang-search');
    const box = document.getElementById('barangSuggestBox');

    function renderSuggest(list) {
      activeBarangInput = input;
      if (list.length === 0) {
        box.innerHTML = `<div class="supplier-suggest-empty">Barang tidak ditemukan</div>`;
      } else {
        box.innerHTML = list.map((b, i) => `
          <div class="supplier-suggest-item" data-id="${b.id}" data-nama="${escapeHTML(b.nama)}">
            <div class="s-name">${b.kode ? escapeHTML(b.kode) + ' — ' : ''}${escapeHTML(b.nama)}</div>
            ${b.kategori ? `<div class="s-meta">${escapeHTML(b.kategori)}</div>` : ''}
          </div>
        `).join('');

        box.querySelectorAll('.supplier-suggest-item').forEach(el => {
          el.addEventListener('mousedown', (e) => {
            e.preventDefault();
            selectBarangForRow(tr, el.dataset.id, el.dataset.nama);
          });
        });
      }
      box.classList.remove('d-none');
      positionBarangBox();
    }

    input.addEventListener('input', () => {
      tr.querySelector('.it-id-barang').value = '';
      input.classList.remove('is-linked');
      renderSuggest(searchBarang(input.value));
    });

    input.addEventListener('focus', () => renderSuggest(searchBarang(input.value)));
  }

  function selectBarangForRow(tr, id, nama) {
    tr.querySelector('.it-id-barang').value = id;
    const input = tr.querySelector('.it-barang-search');
    input.value = nama;
    input.classList.add('is-linked');
    hideBarangBox();
  }

  function removeItemRow(rowId) {
    const row = document.getElementById(rowId);
    if (row) {
      // Kalau baris yang dihapus sedang jadi baris aktif box saran, tutup dulu
      // supaya box tidak "menggantung" nunjuk ke input yang sudah tidak ada.
      if (activeBarangInput && row.contains(activeBarangInput)) hideBarangBox();
      row.remove();
    }
    updateGrandTotal();
  }

  function updateGrandTotal() {
    let grand = 0;
    document.querySelectorAll('#itemsBody tr').forEach(tr => {
      const qty = parseFloat(tr.querySelector('.it-qty').value) || 0;
      const price = parseFloat(tr.querySelector('.it-price').value) || 0;
      const sub = qty * price;
      grand += sub;
      tr.querySelector('.col-sub').textContent = fmtRupiah(sub);
    });
    document.getElementById('grandTotal').textContent = fmtRupiah(grand);
  }

  function escapeHTML(s) {
    return (s || '').replace(/[&<>"']/g, c => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    } [c]));
  }

  function renderSatuanOptions(selected) {
    if (!satuanOptions || satuanOptions.length === 0) {
      return `<option value="">-- satuan belum tersedia --</option>`;
    }
    return satuanOptions.map(u => {
      const isSelected = selected ? (u === selected) : false;
      return `<option value="${escapeHTML(u)}" ${isSelected ? 'selected' : ''}>${escapeHTML(u)}</option>`;
    }).join('');
  }

  function statusLabel(s) {
    return {
      menunggu: 'Menunggu QC',
      lolos: 'Lolos QC',
      ditolak: 'Ditolak QC'
    } [s] || s;
  }

  function formatDateID(iso) {
    const d = new Date(iso);
    return d.toLocaleDateString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    });
  }

  function buildFormDataParams(obj, prefix = '') {
    let str = [];
    for (let p in obj) {
      if (obj.hasOwnProperty(p)) {
        let k = prefix ? prefix + "[" + p + "]" : p,
          v = obj[p];
        if (v !== null && typeof v === "object") {
          str.push(buildFormDataParams(v, k));
        } else {
          str.push(encodeURIComponent(k) + "=" + encodeURIComponent(v));
        }
      }
    }
    return str.join("&");
  }

  /* ---------- Simpan Data (Add / Edit) ---------- */
  async function savePO() {
    const id_po = document.getElementById('f_id_po')?.value;
    const id_supplier = document.getElementById('f_id_supplier')?.value;
    if (!id_supplier) {
      showToast('Pilih supplier dari hasil pencarian');
      document.getElementById('f_supplier_search')?.focus();
      return;
    }

    const tanggal = document.getElementById('f_date')?.value;
    if (!tanggal) {
      showToast('Tanggal wajib diisi');
      return;
    }

    const items = [];
    let hasUnlinkedBarang = false;
    let hasInvalidQty = false;

    document.querySelectorAll('#itemsBody tr').forEach(tr => {
      const id_barang = tr.querySelector('.it-id-barang')?.value;
      const qty = parseFloat(tr.querySelector('.it-qty')?.value) || 0;
      const unit = tr.querySelector('.it-unit')?.value;
      const price = parseFloat(tr.querySelector('.it-price')?.value) || 0;

      if (!id_barang && qty === 0 && price === 0) return;

      if (!id_barang) hasUnlinkedBarang = true;
      if (qty <= 0) hasInvalidQty = true;

      items.push({
        id_barang,
        qty,
        unit,
        price
      });
    });

    if (items.length === 0) {
      showToast('Tambahkan minimal 1 baris detail barang');
      return;
    }
    if (hasUnlinkedBarang) {
      showToast('Setiap baris wajib memilih barang dari daftar');
      return;
    }
    if (hasInvalidQty) {
      showToast('Qty barang harus lebih dari 0');
      return;
    }

    const saveBtn = document.getElementById('saveBtn');
    saveBtn.disabled = true;
    saveBtn.textContent = 'Menyimpan...';

    try {
      const payload = {
        id_po: id_po,
        id_supplier: id_supplier,
        tanggal: tanggal,
        note: document.getElementById('f_note')?.value.trim() || '',
        items: items
      };

      const json = await submitPO(payload);
      if (json.status) {
        showToast(json.message);
        closeForm();
      } else {
        showToast(json.message);
      }
    } catch (err) {
      console.error(err);
      showToast('Terjadi kesalahan koneksi atau server');
    } finally {
      saveBtn.disabled = false;
      saveBtn.textContent = 'Simpan Purchase Order';
    }
  }

  loadAndRenderList();
</script>