<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!-- Konten saja. Bungkus dengan layout/sidebar yang sama seperti add_penawaran.php -->
<style>
  .so-hero { background: linear-gradient(135deg, #2f7a57, #1f5a3f); color: #fff; border-radius: 14px; padding: 18px 24px; }
  .so-hero small { opacity: .85; }
  .so-card { border: 0; border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
  .so-card .card-header { background: #fff; border-bottom: 1px solid #eee; font-weight: 600; }
  .so-locked { background: #f1f3f5; }
  .so-total { border-left: 4px solid #2f7a57; background: #f8faf9; border-radius: 8px; padding: 12px 16px; }
</style>

<div class="container-fluid py-3">

  <div class="so-hero mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="mb-1"><i class="bi bi-cart-check me-2"></i>Buat Sales Order</h5>
      <small>Item, harga, dan pajak diambil dari penawaran yang sudah di-approve dan tidak bisa diubah di sini.</small>
    </div>
    <div class="fs-5 fw-semibold">No. SO: <span id="no_so_label"><?= html_escape($no_so) ?></span></div>
  </div>

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?= html_escape($this->session->flashdata('error')) ?></div>
  <?php endif; ?>

  <form action="<?= site_url('sales_order/simpan') ?>" method="post" id="form_so" autocomplete="off">
    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">

    <!-- 1. Sumber penawaran -->
    <div class="card so-card mb-3">
      <div class="card-header"><i class="bi bi-file-earmark-text me-2"></i>Penawaran</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Pilih penawaran (sudah di-approve) <span class="text-danger">*</span></label>
            <input type="text" id="cari_penawaran" class="form-control" list="dl_penawaran"
                   placeholder="Ketik no. surat / nama customer, lalu pilih dari daftar" autocomplete="off" required>
            <input type="hidden" name="id_penawaran" id="id_penawaran" value="">
            <datalist id="dl_penawaran">
              <?php foreach ($penawaran_list as $p): ?>
                <option value="<?= html_escape($p->no_surat . ' - ' . $p->nama_customer) ?>" data-id="<?= (int) $p->id ?>"></option>
              <?php endforeach; ?>
            </datalist>
            <?php if (empty($penawaran_list)): ?>
              <div class="form-text text-warning">Belum ada penawaran yang di-approve dan belum jadi SO.</div>
            <?php endif; ?>
          </div>
          <div class="col-md-3">
            <label class="form-label">Customer</label>
            <input type="text" id="v_customer" class="form-control so-locked" readonly>
          </div>
          <div class="col-md-3">
            <label class="form-label">Tanggal penawaran</label>
            <input type="text" id="v_tgl_penawaran" class="form-control so-locked" readonly>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Data order -->
    <div class="card so-card mb-3">
      <div class="card-header"><i class="bi bi-info-circle me-2"></i>Data Order</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Tanggal SO <span class="text-danger">*</span></label>
            <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">No. PO customer</label>
            <input type="text" name="no_po_customer" class="form-control" maxlength="50" placeholder="Jika customer kirim PO">
          </div>
          <div class="col-md-3">
            <label class="form-label">Tanggal kirim / target selesai</label>
            <input type="date" name="tgl_kirim" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label">Termin pembayaran <span class="text-danger">*</span></label>
            <select name="termin" id="termin" class="form-select" required>
              <option value="100">100% (lunas)</option>
              <option value="50-50">DP 50% / pelunasan 50%</option>
              <option value="30-70">DP 30% / pelunasan 70%</option>
              <option value="custom">Custom</option>
            </select>
          </div>
          <div class="col-md-3 d-none" id="wrap_dp">
            <label class="form-label">DP (%)</label>
            <input type="number" name="dp_persen" id="dp_persen" class="form-control" min="0" max="100" step="0.01" value="0">
          </div>
          <div class="col-md-6">
            <label class="form-label">Alamat pengiriman / pemasangan</label>
            <textarea name="alamat_kirim" id="alamat_kirim" class="form-control" rows="2" placeholder="Diisi otomatis dari alamat customer, boleh diubah"></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Catatan (opsional)</label>
            <textarea name="catatan" class="form-control" rows="2" placeholder="Mis. akses lokasi, jam kerja, permintaan khusus"></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. Item (read-only dari penawaran) -->
    <div class="card so-card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul me-2"></i>Item Order</span>
        <span class="badge text-bg-secondary"><i class="bi bi-lock me-1"></i>Terkunci dari penawaran</span>
      </div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:40px">#</th>
              <th>Barang</th>
              <th>Warna / Finishing</th>
              <th class="text-end">L x T (mm)</th>
              <th class="text-end">Luas (m²)</th>
              <th class="text-end">Qty</th>
              <th class="text-end">Harga satuan</th>
              <th class="text-end">Subtotal</th>
            </tr>
          </thead>
          <tbody id="tbody_item">
            <tr><td colspan="8" class="text-center text-muted py-4">Pilih penawaran untuk menampilkan item.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 4. Ringkasan -->
    <div class="row justify-content-end">
      <div class="col-lg-5">
        <div class="card so-card mb-3">
          <div class="card-header"><i class="bi bi-receipt me-2"></i>Ringkasan</div>
          <div class="card-body">
            <div class="d-flex justify-content-between mb-1"><span>Subtotal</span><span id="r_subtotal">Rp 0</span></div>
            <div class="d-flex justify-content-between mb-1"><span id="l_ppn">PPN</span><span id="r_ppn">Rp 0</span></div>
            <div class="d-flex justify-content-between mb-3"><span id="l_pph">PPh</span><span id="r_pph">Rp 0</span></div>
            <div class="so-total">
              <div class="small text-muted">Grand total</div>
              <div class="fs-4 fw-bold" id="r_grand">Rp 0</div>
            </div>
            <div class="d-flex justify-content-between mt-3 small" id="wrap_dp_info" style="display:none!important">
              <span>DP</span><span id="r_dp">Rp 0</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card so-card">
      <div class="card-body d-flex justify-content-end gap-2">
        <a href="<?= site_url('sales_order') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Batal</a>
        <button type="submit" class="btn btn-success" id="btn_simpan" disabled>
          <i class="bi bi-check2-square me-1"></i>Simpan Sales Order
        </button>
      </div>
    </div>
  </form>
</div>

<script>
(function () {
  var urlGet = '<?= site_url('sales_order/get_penawaran/') ?>';
  var urlNo  = '<?= site_url('sales_order/preview_no') ?>';
  var grand  = 0;

  function rp(n) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(Number(n) || 0)); }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
  function num(n, d) { return new Intl.NumberFormat('id-ID', {maximumFractionDigits: d}).format(Number(n) || 0); }

  // alamat ikut customer penawaran, kecuali user sudah mengetik sendiri
  var alamatDiubah = false;
  document.getElementById('alamat_kirim').addEventListener('input', function () { alamatDiubah = true; });

  var sel  = document.getElementById('id_penawaran');   // hidden, berisi id
  var cari = document.getElementById('cari_penawaran');  // kotak ketik
  var peta = {}, lastId = '';
  document.querySelectorAll('#dl_penawaran option').forEach(function (o) {
    peta[o.value] = o.getAttribute('data-id');
  });
  cari.addEventListener('input', function () {
    var id = peta[this.value] || '';
    if (id === lastId) { return; }
    lastId = id;
    sel.value = id;
    loadPenawaran(id);
  });
  document.getElementById('form_so').addEventListener('submit', function (e) {
    if (!sel.value) { e.preventDefault(); alert('Pilih penawaran dari daftar yang muncul.'); cari.focus(); }
  });
  var btn = document.getElementById('btn_simpan');

  function loadPenawaran(id) {
    btn.disabled = true;
    if (!id) { reset(); return; }
    fetch(urlGet + id).then(function (r) { return r.json(); }).then(function (d) {
      if (d.error) { alert(d.error); reset(); return; }
      var h = d.header;
      document.getElementById('v_customer').value = h.nama_customer || '';
      document.getElementById('v_tgl_penawaran').value = h.tanggal || '';
      var alamat = document.getElementById('alamat_kirim');
      if (!alamatDiubah) { alamat.value = h.alamat_customer || ''; }

      var rows = '';
      d.items.forEach(function (it, i) {
        rows += '<tr>' +
          '<td>' + (i + 1) + '</td>' +
          '<td>' + esc(it.jenis_item) +
            (it.keterangan ? '<div class="small text-muted">' + esc(it.keterangan) + '</div>' : '') + '</td>' +
          '<td>' + esc(it.warna) + '<div class="small text-muted">' + esc(it.finishing) + '</div></td>' +
          '<td class="text-end">' + num(it.lebar_mm, 0) + ' x ' + num(it.tinggi_mm, 0) + '</td>' +
          '<td class="text-end">' + num(it.luas_m2, 4) + '</td>' +
          '<td class="text-end">' + num(it.qty, 2) + '</td>' +
          '<td class="text-end">' + rp(it.harga_unit) + '</td>' +
          '<td class="text-end">' + rp(it.total_harga) + '</td></tr>';
      });
      document.getElementById('tbody_item').innerHTML = rows ||
        '<tr><td colspan="8" class="text-center text-muted py-4">Penawaran ini belum punya item.</td></tr>';

      document.getElementById('r_subtotal').textContent = rp(h.subtotal);
      document.getElementById('l_ppn').textContent = 'PPN ' + num(h.ppn_persen, 2) + '%';
      document.getElementById('r_ppn').textContent = rp(h.ppn_nominal);
      document.getElementById('l_pph').textContent = 'PPh ' + num(h.pph_persen, 2) + '%';
      document.getElementById('r_pph').textContent = rp(h.pph_nominal);
      document.getElementById('r_grand').textContent = rp(h.grand_total);
      grand = Number(h.grand_total) || 0;
      updateDp();
      btn.disabled = !d.items.length;
    }).catch(function () { alert('Gagal mengambil data penawaran.'); reset(); });
  }

  function reset() {
    ['v_customer', 'v_tgl_penawaran'].forEach(function (i) { document.getElementById(i).value = ''; });
    document.getElementById('tbody_item').innerHTML =
      '<tr><td colspan="8" class="text-center text-muted py-4">Pilih penawaran untuk menampilkan item.</td></tr>';
    ['r_subtotal', 'r_ppn', 'r_pph', 'r_grand'].forEach(function (i) { document.getElementById(i).textContent = 'Rp 0'; });
    grand = 0; updateDp();
  }

  // Termin & DP
  var termin = document.getElementById('termin');
  var dp = document.getElementById('dp_persen');
  function dpPersen() {
    if (termin.value === '50-50') return 50;
    if (termin.value === '30-70') return 30;
    if (termin.value === 'custom') return Number(dp.value) || 0;
    return 0;
  }
  function updateDp() {
    document.getElementById('wrap_dp').classList.toggle('d-none', termin.value !== 'custom');
    var info = document.getElementById('wrap_dp_info');
    var p = dpPersen();
    if (p > 0 && grand > 0) {
      info.style.setProperty('display', 'flex', 'important');
      document.getElementById('r_dp').textContent = num(p, 2) + '% = ' + rp(grand * p / 100);
    } else {
      info.style.setProperty('display', 'none', 'important');
    }
  }
  termin.addEventListener('change', updateDp);
  dp.addEventListener('input', updateDp);

  // Preview nomor SO saat tanggal diganti
  document.getElementById('tanggal').addEventListener('change', function () {
    fetch(urlNo + '?tanggal=' + encodeURIComponent(this.value)).then(function (r) { return r.json(); })
      .then(function (d) { document.getElementById('no_so_label').textContent = d.no_so; });
  });
})();
</script>