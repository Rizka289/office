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
      <h5 class="mb-1"><i class="bi bi-cart-check me-2"></i><?= translate('lbl_buat_so') ?></h5>
      <small><?= translate('info_so') ?></small>
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
      <div class="card-header"><i class="bi bi-file-earmark-text me-2"></i><?= translate('pesanan_penjualan') ?></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label"><?= translate('info_pilih_penawaran') ?><span class="text-danger">*</span></label>
            <?php /* Dropdown biasa (bukan datalist): pasti terpilih id-nya di semua browser */ ?>
            <input type="text" id="filter_penawaran" class="form-control form-control-sm mb-1"
                   placeholder="Cari no. surat / nama customer..." autocomplete="off" <?= empty($penawaran_list) ? 'disabled' : '' ?>>
            <select name="id_penawaran" id="id_penawaran" class="form-select" required <?= empty($penawaran_list) ? 'disabled' : '' ?>>
              <option value="">-- Pilih penawaran (<?= count($penawaran_list) ?>) --</option>
              <?php foreach ($penawaran_list as $p): ?>
                <option value="<?= (int) $p->id ?>"><?= html_escape($p->no_surat . ' - ' . $p->nama_customer) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (empty($penawaran_list)): ?>
              <div class="form-text text-warning">
                <?= translate('ket_search_penawaran') ?><br>
                Hanya penawaran berstatus <b>approved</b> yang belum dibuatkan SO yang muncul di sini.
              </div>
            <?php endif; ?>
            <div class="form-text text-danger d-none" id="info_error"></div>
          </div>
          <div class="col-md-3">
            <label class="form-label"><?= translate('pelanggan') ?></label>
            <input type="text" id="v_customer" class="form-control so-locked" readonly>
          </div>
          <div class="col-md-3">
            <label class="form-label"><?= translate('tanggal_penawaran') ?></label>
            <input type="text" id="v_tgl_penawaran" class="form-control so-locked" readonly>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Data order -->
    <div class="card so-card mb-3">
      <div class="card-header"><i class="bi bi-info-circle me-2"></i><?= translate('info_data_order') ?></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label"><?= translate('tanggal') ?> <span class="text-danger">*</span></label>
            <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label"><?= translate('no_po_pelanggan') ?></label>
            <input type="text" name="no_po_customer" class="form-control" maxlength="50" placeholder="Jika customer kirim PO">
          </div>
          <div class="col-md-3">
            <label class="form-label"><?= translate('target_kirim') ?></label>
            <input type="date" name="tgl_kirim" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label"><?= translate('termin_pembayran') ?> <span class="text-danger">*</span></label>
            <select name="termin" id="termin" class="form-select" required>
              <option value="100"><?= translate('100%') ?></option>
              <option value="50-50"><?= translate('dp_50') ?></option>
              <option value="30-70"><?= translate('dp_30') ?></option>
              <option value="custom"><?= translate('custom') ?></option>
            </select>
          </div>
          <div class="col-md-3 d-none" id="wrap_dp">
            <label class="form-label">DP (%)</label>
            <input type="number" name="dp_persen" id="dp_persen" class="form-control" min="0" max="100" step="0.01" value="0">
          </div>
          <div class="col-md-6">
            <label class="form-label"><?= translate('almt_kirim') ?></label>
            <textarea name="alamat_kirim" id="alamat_kirim" class="form-control" rows="2" placeholder="Diisi otomatis dari alamat customer, boleh diubah"></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label"><?= translate('catatan') ?></label>
            <textarea name="catatan" class="form-control" rows="2" placeholder="Mis. akses lokasi, jam kerja, permintaan khusus"></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. Item (read-only dari penawaran) -->
    <div class="card so-card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul me-2"></i><?= translate('item_list') ?></span>
        <span class="badge text-bg-secondary"><i class="bi bi-lock me-1"></i><?= translate('info_status_penawaran') ?></span>
      </div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:40px"><?= translate('no') ?></th>
              <th><?= translate('nama') ?></th>
              <th><?= translate('warna') ?></th>
              <th class="text-end"><?= translate('ukuran')?> (mm)</th>
              <th class="text-end"><?= translate('luas') ?>(m²)</th>
              <th class="text-end"><?= translate('qty') ?></th>
              <th class="text-end"><?= translate('harga') ?></th>
              <th class="text-end"><?= translate('subtotal') ?></th>
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
            <div class="d-flex justify-content-between mb-1" id="wrap_pasang" style="display:none!important"><span>Biaya pasang</span><span id="r_pasang">Rp 0</span></div>
            <div class="d-flex justify-content-between mb-1" id="wrap_ongkir" style="display:none!important"><span>Biaya ongkir</span><span id="r_ongkir">Rp 0</span></div>
            <div class="d-flex justify-content-between mb-1"><span id="l_ppn">PPN</span><span id="r_ppn">Rp 0</span></div>
            <div class="d-flex justify-content-between mb-1" id="wrap_pph" style="display:none!important"><span id="l_pph">PPh</span><span id="r_pph">Rp 0</span></div>
            <div class="mb-3"></div>
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
  function tampil(id, ya) { document.getElementById(id).style.setProperty('display', ya ? 'flex' : 'none', 'important'); }

  // alamat ikut customer penawaran, kecuali user sudah mengetik sendiri
  var alamatDiubah = false;
  var alamatEl = document.getElementById('alamat_kirim');
  alamatEl.addEventListener('input', function () { alamatDiubah = true; });

  var sel  = document.getElementById('id_penawaran');      // dropdown penawaran
  var cari = document.getElementById('filter_penawaran');  // kotak filter dropdown
  var btn  = document.getElementById('btn_simpan');
  var form = document.getElementById('form_so');
  var infoErr = document.getElementById('info_error');
  var reqSeq = 0, sedangKirim = false;

  function tampilError(msg) {
    infoErr.textContent = msg || '';
    infoErr.classList.toggle('d-none', !msg);
  }

  // Filter isi dropdown sesuai teks yang diketik
  cari.addEventListener('input', function () {
    var q = this.value.toLowerCase().trim(), pertama = null;
    Array.prototype.forEach.call(sel.options, function (o) {
      if (!o.value) { return; }
      var cocok = o.text.toLowerCase().indexOf(q) !== -1;
      o.hidden = !cocok;
      if (cocok && !pertama) { pertama = o; }
    });
    if (q && pertama && sel.selectedOptions[0] && sel.selectedOptions[0].hidden) {
      sel.value = pertama.value; loadPenawaran(sel.value);
    }
  });
  sel.addEventListener('change', function () { loadPenawaran(this.value); });

  form.addEventListener('submit', function (e) {
    if (sedangKirim) { e.preventDefault(); return; }            // cegah klik ganda
    if (!sel.value) { e.preventDefault(); alert('Pilih penawaran dulu.'); sel.focus(); return; }
    sedangKirim = true;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
  });

  function loadPenawaran(id) {
    btn.disabled = true;
    tampilError('');
    if (!id) { reset(); return; }
    var seq = ++reqSeq;   // abaikan respon lama kalau user keburu ganti pilihan
    fetch(urlGet + encodeURIComponent(id), {credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}})
      .then(function (r) {
        return r.text().then(function (t) {
          if (!r.ok) { throw new Error('Server error HTTP ' + r.status); }
          try { return JSON.parse(t); }
          catch (e) {
            // Biasanya karena ada PHP error/warning atau sesi login habis (redirect ke halaman login)
            throw new Error('Respon server bukan JSON. Cek log PHP / coba login ulang.');
          }
        });
      })
      .then(function (d) {
        if (seq !== reqSeq) { return; }
        if (d.error) { tampilError(d.error); reset(); return; }
        var h = d.header;
        document.getElementById('v_customer').value = h.nama_customer || '';
        document.getElementById('v_tgl_penawaran').value = h.tanggal || '';
        if (!alamatDiubah) { alamatEl.value = h.alamat_customer || ''; }

        var rows = '';
        d.items.forEach(function (it, i) {
          var tambahan = '';
          if (it.aksesoris && it.aksesoris.length) {
            tambahan += '<div class="small text-muted">Aksesoris: ' +
              it.aksesoris.map(function (a) { return esc(a.nama) + ' (' + num(a.qty, 2) + ')'; }).join(', ') + '</div>';
          }
          if (it.kaca && it.kaca.length) {
            tambahan += '<div class="small text-muted">Kaca: ' +
              it.kaca.map(function (k) { return esc(k.nama); }).join(', ') + '</div>';
          }
          rows += '<tr>' +
            '<td>' + (i + 1) + '</td>' +
            '<td>' + esc(it.jenis_item) +
              (it.keterangan ? '<div class="small text-muted">' + esc(it.keterangan) + '</div>' : '') +
              tambahan + '</td>' +
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
        document.getElementById('r_pasang').textContent = rp(h.biaya_pasang);
        document.getElementById('r_ongkir').textContent = rp(h.biaya_ongkir);
        tampil('wrap_pasang', Number(h.biaya_pasang) > 0);
        tampil('wrap_ongkir', Number(h.biaya_ongkir) > 0);
        document.getElementById('l_ppn').textContent = 'PPN ' + num(h.ppn_persen, 2) + '%';
        document.getElementById('r_ppn').textContent = rp(h.ppn_nominal);
        // PPh sudah dipotong di penawaran (grand total = dasar + PPN - PPh), hanya ditampilkan
        document.getElementById('l_pph').textContent = 'PPh ' + num(h.pph_persen, 2) + '% (dipotong customer)';
        document.getElementById('r_pph').textContent = '- ' + rp(h.pph_nominal);
        tampil('wrap_pph', Number(h.pph_nominal) > 0);
        document.getElementById('r_grand').textContent = rp(h.grand_total);
        grand = Number(h.grand_total) || 0;
        updateDp();
        btn.disabled = !(d.items && d.items.length);
      })
      .catch(function (err) {
        if (seq !== reqSeq) { return; }
        tampilError('Gagal mengambil data penawaran: ' + err.message);
        reset();
      });
  }

  function reset() {
    ['v_customer', 'v_tgl_penawaran'].forEach(function (i) { document.getElementById(i).value = ''; });
    if (!alamatDiubah) { alamatEl.value = ''; }
    document.getElementById('tbody_item').innerHTML =
      '<tr><td colspan="8" class="text-center text-muted py-4">Pilih penawaran untuk menampilkan item.</td></tr>';
    ['r_subtotal', 'r_pasang', 'r_ongkir', 'r_ppn', 'r_pph', 'r_grand'].forEach(function (i) {
      document.getElementById(i).textContent = 'Rp 0';
    });
    tampil('wrap_pasang', false);
    tampil('wrap_ongkir', false);
    tampil('wrap_pph', false);
    grand = 0; updateDp();
    btn.disabled = true;
  }

  // Termin & DP
  var termin = document.getElementById('termin');
  var dp = document.getElementById('dp_persen');
  function dpPersen() {
    if (termin.value === '50-50') return 50;
    if (termin.value === '30-70') return 30;
    if (termin.value === 'custom') return Math.min(100, Math.max(0, Number(dp.value) || 0));
    return 0;
  }
  function updateDp() {
    document.getElementById('wrap_dp').classList.toggle('d-none', termin.value !== 'custom');
    var p = dpPersen();
    if (p > 0 && grand > 0) {
      tampil('wrap_dp_info', true);
      document.getElementById('r_dp').textContent = num(p, 2) + '% = ' + rp(grand * p / 100);
    } else {
      tampil('wrap_dp_info', false);
    }
  }
  termin.addEventListener('change', updateDp);
  dp.addEventListener('input', updateDp);
  updateDp();

  // Preview nomor SO saat tanggal diganti
  document.getElementById('tanggal').addEventListener('change', function () {
    fetch(urlNo + '?tanggal=' + encodeURIComponent(this.value), {credentials: 'same-origin'})
      .then(function (r) { return r.json(); })
      .then(function (d) { document.getElementById('no_so_label').textContent = d.no_so; })
      .catch(function () { /* preview saja, nomor final dibuat saat simpan */ });
  });
})();
</script>