<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Purchase Order</title>
<style>
  :root{
    --ink:#1b2430;
    --slate:#4a5568;
    --mist:#8b98a9;
    --paper:#f5f6f4;
    --panel:#ffffff;
    --line:#dde1e6;
    --brand:#1f4b43;
    --brand-ink:#e9f2ef;
    --rust:#b5622a;
    --rust-ink:#fbeee2;
    --ok:#2f7a4f;
    --danger:#b23b3b;
    --mono: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
  }
  *{box-sizing:border-box;}
  body{
    margin:0;
    background:var(--paper);
    color:var(--ink);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Inter, Roboto, sans-serif;
    -webkit-font-smoothing:antialiased;
  }
  .wrap{max-width:1040px;margin:0 auto;padding:28px 20px 60px;}

  /* ---- header ---- */
  .topbar{
    display:flex;justify-content:space-between;align-items:flex-end;
    border-bottom:2px solid var(--ink);
    padding-bottom:14px;margin-bottom:22px;
  }
  .topbar .eyebrow{
    font-family:var(--mono);font-size:11px;letter-spacing:.14em;
    text-transform:uppercase;color:var(--mist);margin:0 0 4px;
  }
  .topbar h1{margin:0;font-size:24px;font-weight:700;letter-spacing:-.01em;}
  .newbtn{
    background:var(--brand);color:#fff;border:none;border-radius:6px;
    padding:11px 18px;font-size:14px;font-weight:600;cursor:pointer;
    display:flex;align-items:center;gap:8px;transition:background .15s;
  }
  .newbtn:hover{background:#173a34;}
  .newbtn svg{width:15px;height:15px;}

  /* ---- list view ---- */
  .stats{display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;}
  .stat{
    background:var(--panel);border:1px solid var(--line);border-radius:8px;
    padding:12px 16px;min-width:120px;
  }
  .stat .n{font-size:20px;font-weight:700;font-family:var(--mono);}
  .stat .l{font-size:11px;color:var(--mist);text-transform:uppercase;letter-spacing:.08em;margin-top:2px;}

  table{width:100%;border-collapse:collapse;background:var(--panel);border:1px solid var(--line);border-radius:8px;overflow:hidden;}
  thead th{
    text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.08em;
    color:var(--mist);font-weight:600;padding:12px 14px;border-bottom:1px solid var(--line);
    background:#fafafa;
  }
  tbody td{padding:14px;border-bottom:1px solid var(--line);font-size:14px;vertical-align:middle;}
  tbody tr:last-child td{border-bottom:none;}
  tbody tr{cursor:pointer;transition:background .12s;}
  tbody tr:hover{background:#f9faf9;}
  .po-no{font-family:var(--mono);font-weight:700;color:var(--brand);}
  .badge{
    display:inline-block;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;
    text-transform:uppercase;letter-spacing:.04em;
  }
  .badge.draft{background:#eef0f2;color:var(--slate);}
  .badge.sent{background:var(--rust-ink);color:var(--rust);}
  .badge.received{background:#e6f2ea;color:var(--ok);}
  .amount{font-family:var(--mono);font-weight:600;text-align:right;}
  .empty{
    text-align:center;padding:60px 20px;color:var(--mist);
  }
  .empty p{margin:6px 0 18px;font-size:14px;}

  /* ---- form / detail view ---- */
  .hidden{display:none !important;}
  .formhead{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;}
  .backlink{
    background:none;border:none;color:var(--slate);font-size:13px;cursor:pointer;
    display:flex;align-items:center;gap:6px;padding:0;font-weight:600;
  }
  .backlink:hover{color:var(--ink);}

  .card{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:24px;margin-bottom:16px;}
  .card h2{
    margin:0 0 16px;font-size:13px;text-transform:uppercase;letter-spacing:.08em;
    color:var(--mist);border-bottom:1px solid var(--line);padding-bottom:10px;
  }
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
  .field label{
    display:block;font-size:12px;font-weight:600;color:var(--slate);margin-bottom:6px;
  }
  .field input, .field select, .field textarea{
    width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:6px;
    font-size:14px;font-family:inherit;color:var(--ink);background:#fff;
  }
  .field input:focus, .field select:focus, .field textarea:focus{
    outline:none;border-color:var(--brand);box-shadow:0 0 0 3px var(--brand-ink);
  }
  .field.readonly input{background:#f5f6f4;color:var(--mist);}

  /* item table */
  .items-table{width:100%;border-collapse:collapse;}
  .items-table th{
    text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--mist);
    padding:0 8px 8px;font-weight:600;
  }
  .items-table td{padding:6px 8px;vertical-align:top;}
  .items-table input{padding:8px 9px;font-size:13.5px;}
  .items-table .col-qty input, .items-table .col-price input{text-align:right;font-family:var(--mono);}
  .items-table .col-sub{font-family:var(--mono);text-align:right;padding-top:14px;font-size:13.5px;font-weight:600;white-space:nowrap;}
  .items-table .col-del{width:34px;text-align:center;padding-top:10px;}
  .rmv{
    background:none;border:none;color:var(--mist);cursor:pointer;font-size:16px;
    width:26px;height:26px;border-radius:5px;line-height:1;
  }
  .rmv:hover{background:var(--rust-ink);color:var(--danger);}

  .addrow{
    margin-top:10px;background:none;border:1px dashed var(--line);color:var(--slate);
    padding:9px 14px;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;width:100%;
  }
  .addrow:hover{border-color:var(--brand);color:var(--brand);background:var(--brand-ink);}

  .totalrow{
    display:flex;justify-content:flex-end;gap:28px;margin-top:16px;padding-top:14px;
    border-top:1px solid var(--line);
  }
  .totalrow .tl{font-size:13px;color:var(--slate);align-self:center;}
  .totalrow .tv{font-family:var(--mono);font-size:20px;font-weight:700;color:var(--brand);}

  .formfoot{display:flex;justify-content:space-between;align-items:center;margin-top:4px;}
  .statusrow{display:flex;gap:8px;align-items:center;}
  .statusrow select{width:auto;padding:8px 30px 8px 12px;}
  .btnrow{display:flex;gap:10px;}
  .btn{padding:10px 20px;border-radius:6px;font-size:14px;font-weight:600;cursor:pointer;border:1px solid transparent;}
  .btn.primary{background:var(--brand);color:#fff;}
  .btn.primary:hover{background:#173a34;}
  .btn.ghost{background:#fff;color:var(--slate);border-color:var(--line);}
  .btn.ghost:hover{border-color:var(--slate);color:var(--ink);}
  .btn.danger{background:#fff;color:var(--danger);border-color:#ecc7c7;}
  .btn.danger:hover{background:var(--rust-ink);}

  .toast{
    position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(20px);
    background:var(--ink);color:#fff;padding:12px 20px;border-radius:8px;font-size:13.5px;
    opacity:0;pointer-events:none;transition:all .25s ease;font-weight:600;
  }
  .toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
</style>
</head>
<body>
<div class="wrap">

  <!-- ============ LIST VIEW ============ -->
  <div id="listView">
    <div class="topbar">
      <div>
        <p class="eyebrow">Procurement</p>
        <h1>Purchase Order</h1>
      </div>
      <button class="newbtn" onclick="openForm()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 5v14M5 12h14"/></svg>
        PO Baru
      </button>
    </div>

    <div class="stats">
      <div class="stat"><div class="n" id="statTotal">0</div><div class="l">Total PO</div></div>
      <div class="stat"><div class="n" id="statDraft">0</div><div class="l">Draft</div></div>
      <div class="stat"><div class="n" id="statSent">0</div><div class="l">Terkirim</div></div>
      <div class="stat"><div class="n" id="statValue">Rp 0</div><div class="l">Total Nilai</div></div>
    </div>

    <table>
      <thead>
        <tr>
          <th>No. PO</th><th>Supplier</th><th>Tanggal</th><th>Status</th><th style="text-align:right">Nilai</th>
        </tr>
      </thead>
      <tbody id="poTableBody"></tbody>
    </table>

    <div class="empty hidden" id="emptyState">
      <p>Belum ada purchase order.</p>
      <button class="newbtn" style="margin:0 auto" onclick="openForm()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 5v14M5 12h14"/></svg>
        Buat PO Pertama
      </button>
    </div>
  </div>

  <!-- ============ FORM / DETAIL VIEW ============ -->
  <div id="formView" class="hidden">
    <div class="formhead">
      <button class="backlink" onclick="closeForm()">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M15 18l-6-6 6-6"/></svg>
        Kembali ke daftar
      </button>
    </div>

    <div class="card">
      <h2>Informasi Purchase Order</h2>
      <div class="grid">
        <div class="field readonly">
          <label>No. PO</label>
          <input type="text" id="f_pono" readonly>
        </div>
        <div class="field">
          <label>Tanggal PO</label>
          <input type="date" id="f_date">
        </div>
        <div class="field">
          <label>Nama Supplier</label>
          <input type="text" id="f_supplier" placeholder="cth. PT Sumber Makmur">
        </div>
        <div class="field">
          <label>Diminta Oleh</label>
          <input type="text" id="f_requester" placeholder="cth. Bagian Gudang">
        </div>
        <div class="field" style="grid-column:1/-1">
          <label>Catatan</label>
          <input type="text" id="f_note" placeholder="Catatan tambahan (opsional)">
        </div>
      </div>
    </div>

    <div class="card">
      <h2>Detail Barang</h2>
      <table class="items-table">
        <thead>
          <tr>
            <th style="width:34%">Nama Barang</th>
            <th style="width:14%">Qty</th>
            <th style="width:10%">Satuan</th>
            <th style="width:18%">Harga Satuan</th>
            <th style="width:18%">Subtotal</th>
            <th class="col-del"></th>
          </tr>
        </thead>
        <tbody id="itemsBody"></tbody>
      </table>
      <button class="addrow" onclick="addItemRow()">+ Tambah Baris Barang</button>

      <div class="totalrow">
        <div class="tl">Total Purchase Order</div>
        <div class="tv" id="grandTotal">Rp 0</div>
      </div>
    </div>

    <div class="formfoot">
      <div class="statusrow">
        <label style="font-size:12px;font-weight:600;color:var(--slate)">Status:</label>
        <select id="f_status">
          <option value="draft">Draft</option>
          <option value="sent">Terkirim ke Supplier</option>
          <option value="received">Barang Diterima</option>
        </select>
      </div>
      <div class="btnrow">
        <button class="btn danger hidden" id="deleteBtn" onclick="deleteCurrentPO()">Hapus PO</button>
        <button class="btn ghost" onclick="closeForm()">Batal</button>
        <button class="btn primary" onclick="savePO()">Simpan Purchase Order</button>
      </div>
    </div>
  </div>

</div>

<div class="toast" id="toast"></div>

<script>
let purchaseOrders = [];
let currentEditId = null;
let itemSeq = 0;

function fmtRupiah(n){
  return 'Rp ' + Math.round(n).toLocaleString('id-ID');
}
function todayISO(){
  return new Date().toISOString().slice(0,10);
}
function genPONumber(){
  const y = new Date().getFullYear();
  const seq = String(purchaseOrders.length + 1).padStart(4,'0');
  return `PO/${y}/${seq}`;
}
function showToast(msg){
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(()=> t.classList.remove('show'), 2200);
}

/* ---------- render list ---------- */
function renderList(){
  const body = document.getElementById('poTableBody');
  const empty = document.getElementById('emptyState');
  body.innerHTML = '';

  if(purchaseOrders.length === 0){
    empty.classList.remove('hidden');
  } else {
    empty.classList.add('hidden');
  }

  purchaseOrders.slice().reverse().forEach(po=>{
    const total = po.items.reduce((s,it)=> s + (it.qty * it.price), 0);
    const tr = document.createElement('tr');
    tr.onclick = () => openForm(po.id);
    tr.innerHTML = `
      <td class="po-no">${po.poNumber}</td>
      <td>${po.supplier || '<span style="color:var(--mist)">Belum diisi</span>'}</td>
      <td>${po.date ? formatDateID(po.date) : '-'}</td>
      <td><span class="badge ${po.status}">${statusLabel(po.status)}</span></td>
      <td class="amount">${fmtRupiah(total)}</td>
    `;
    body.appendChild(tr);
  });

  document.getElementById('statTotal').textContent = purchaseOrders.length;
  document.getElementById('statDraft').textContent = purchaseOrders.filter(p=>p.status==='draft').length;
  document.getElementById('statSent').textContent = purchaseOrders.filter(p=>p.status==='sent').length;
  const totalValue = purchaseOrders.reduce((s,po)=> s + po.items.reduce((a,it)=>a+it.qty*it.price,0), 0);
  document.getElementById('statValue').textContent = fmtRupiah(totalValue);
}
function statusLabel(s){
  return {draft:'Draft', sent:'Terkirim', received:'Diterima'}[s] || s;
}
function formatDateID(iso){
  const d = new Date(iso);
  return d.toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'});
}

/* ---------- open / close form (this is the "tampilkan lalu input detail" flow) ---------- */
function openForm(id){
  document.getElementById('listView').classList.add('hidden');
  document.getElementById('formView').classList.remove('hidden');

  const itemsBody = document.getElementById('itemsBody');
  itemsBody.innerHTML = '';

  if(id){
    // tampilkan PO yang sudah ada -> lalu bisa diinput ulang detailnya
    currentEditId = id;
    const po = purchaseOrders.find(p=>p.id === id);
    document.getElementById('f_pono').value = po.poNumber;
    document.getElementById('f_date').value = po.date;
    document.getElementById('f_supplier').value = po.supplier;
    document.getElementById('f_requester').value = po.requester;
    document.getElementById('f_note').value = po.note;
    document.getElementById('f_status').value = po.status;
    document.getElementById('deleteBtn').classList.remove('hidden');
    po.items.forEach(it => addItemRow(it));
  } else {
    // PO baru -> tampilkan form kosong untuk diisi detailnya
    currentEditId = null;
    document.getElementById('f_pono').value = genPONumber();
    document.getElementById('f_date').value = todayISO();
    document.getElementById('f_supplier').value = '';
    document.getElementById('f_requester').value = '';
    document.getElementById('f_note').value = '';
    document.getElementById('f_status').value = 'draft';
    document.getElementById('deleteBtn').classList.add('hidden');
    addItemRow();
  }
  updateGrandTotal();
}
function closeForm(){
  document.getElementById('formView').classList.add('hidden');
  document.getElementById('listView').classList.remove('hidden');
  renderList();
}

/* ---------- item rows ---------- */
function addItemRow(prefill){
  itemSeq++;
  const rowId = 'row_' + itemSeq;
  const tr = document.createElement('tr');
  tr.id = rowId;
  tr.innerHTML = `
    <td><input type="text" class="it-name" placeholder="Nama barang" value="${prefill ? escapeHTML(prefill.name) : ''}"></td>
    <td class="col-qty"><input type="number" min="0" class="it-qty" value="${prefill ? prefill.qty : 1}" oninput="updateGrandTotal()"></td>
    <td><input type="text" class="it-unit" placeholder="pcs" value="${prefill ? escapeHTML(prefill.unit || '') : ''}"></td>
    <td class="col-price"><input type="number" min="0" class="it-price" value="${prefill ? prefill.price : 0}" oninput="updateGrandTotal()"></td>
    <td class="col-sub" id="sub_${rowId}">Rp 0</td>
    <td class="col-del"><button class="rmv" onclick="removeItemRow('${rowId}')">&times;</button></td>
  `;
  document.getElementById('itemsBody').appendChild(tr);
  updateGrandTotal();
}
function removeItemRow(rowId){
  const row = document.getElementById(rowId);
  if(row) row.remove();
  updateGrandTotal();
}
function updateGrandTotal(){
  let grand = 0;
  document.querySelectorAll('#itemsBody tr').forEach(tr=>{
    const qty = parseFloat(tr.querySelector('.it-qty').value) || 0;
    const price = parseFloat(tr.querySelector('.it-price').value) || 0;
    const sub = qty * price;
    grand += sub;
    tr.querySelector('.col-sub').textContent = fmtRupiah(sub);
  });
  document.getElementById('grandTotal').textContent = fmtRupiah(grand);
}
function escapeHTML(s){
  return (s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

/* ---------- save / delete ---------- */
function savePO(){
  const supplier = document.getElementById('f_supplier').value.trim();
  if(!supplier){
    showToast('Nama supplier wajib diisi');
    document.getElementById('f_supplier').focus();
    return;
  }

  const items = [];
  document.querySelectorAll('#itemsBody tr').forEach(tr=>{
    const name = tr.querySelector('.it-name').value.trim();
    const qty = parseFloat(tr.querySelector('.it-qty').value) || 0;
    const unit = tr.querySelector('.it-unit').value.trim();
    const price = parseFloat(tr.querySelector('.it-price').value) || 0;
    if(name) items.push({name, qty, unit, price});
  });
  if(items.length === 0){
    showToast('Tambahkan minimal 1 barang');
    return;
  }

  const data = {
    poNumber: document.getElementById('f_pono').value,
    date: document.getElementById('f_date').value,
    supplier,
    requester: document.getElementById('f_requester').value.trim(),
    note: document.getElementById('f_note').value.trim(),
    status: document.getElementById('f_status').value,
    items
  };

  if(currentEditId){
    const idx = purchaseOrders.findIndex(p=>p.id === currentEditId);
    purchaseOrders[idx] = {...purchaseOrders[idx], ...data};
    showToast('Purchase order diperbarui');
  } else {
    purchaseOrders.push({id: 'po_' + Date.now(), ...data});
    showToast('Purchase order baru disimpan');
  }
  closeForm();
}
function deleteCurrentPO(){
  if(!currentEditId) return;
  if(!confirm('Hapus purchase order ini?')) return;
  purchaseOrders = purchaseOrders.filter(p=>p.id !== currentEditId);
  showToast('Purchase order dihapus');
  closeForm();
}

renderList();
</script>
</body>
</html>