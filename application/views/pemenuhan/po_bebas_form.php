<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');
$label_barang = function ($b) {
  return trim(($b->kode_barang ? $b->kode_barang . ' - ' : '') . $b->nama . ($b->warna && $b->warna !== '-' ? ' - ' . $b->warna : ''));
};
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <div class="judul"><i class="bi bi-box2 me-2"></i>PO Bahan / Stok</div>
      <div class="meta">Pembelian di luar penawaran / SO, mis. aluminium, kaca, aksesoris untuk produksi sendiri</div>
    </div>
    <a href="<?= site_url('pemenuhan/po') ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i><?= translate('kembali') ?></a>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <?php if (!$siap): ?>
    <div class="alert alert-warning">
      <i class="bi bi-exclamation-triangle me-1"></i>Fitur ini belum aktif. Jalankan file <b>database/pm_po_tanpa_so.sql</b> di database (phpMyAdmin &rarr; Impor) lalu muat ulang halaman ini.
    </div>
  <?php endif; ?>

  <div class="alert alert-info">
    <i class="bi bi-info-circle me-1"></i>Alurnya sama dengan PO biasa: <b>draft &rarr; disetujui atasan &rarr; dikirim ke supplier &rarr; diterima gudang &rarr; dibayar finance</b>.
    Baris yang dipilih dari <b>master barang</b> otomatis menambah stok gudang saat diterima, sehingga bisa dipakai SPK produksi.
    Baris tanpa master barang (mis. jasa, ongkos) hanya tercatat sebagai pembelian/hutang.
  </div>

  <?= form_open('pemenuhan/po_bebas_simpan', array('id' => 'form_po', 'autocomplete' => 'off')) ?>

  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-info-circle me-2"></i><?= translate('i18n_data_po') ?></div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label"><?= translate('i18n_supplier') ?> <span class="text-danger">*</span></label>
          <select name="id_supplier" class="form-select" required>
            <option value=""><?= translate('i18n_pilih_supplier') ?></option>
            <?php foreach ($supplier_list as $s): ?>
              <option value="<?= (int) $s->id ?>"><?= html_escape($s->nama) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (!$supplier_list): ?><div class="muted mt-1"><a href="<?= site_url('supplier') ?>"><?= translate('i18n_tambah_supplier_dulu') ?></a></div><?php endif; ?>
        </div>
        <div class="col-md-2">
          <label class="form-label"><?= translate('i18n_tanggal_po') ?></label>
          <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-2">
          <label class="form-label"><?= translate('i18n_dibutuhkan_tanggal') ?></label>
          <input type="date" name="tgl_butuh" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label"><?= translate('i18n_alamat_kirim') ?></label>
          <textarea name="alamat_kirim" class="form-control" rows="2"><?= html_escape($alamat_default) ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label"><?= translate('i18n_catatan_untuk_supplier') ?></label>
          <textarea name="catatan" class="form-control" rows="2" placeholder="<?= translate('i18n_mis_syarat_pembayaran_jam_bongkar_kontak') ?>"></textarea>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="bi bi-list-ul me-2"></i><?= translate('i18n_barang_dipesan') ?></span>
      <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_tambah"><i class="bi bi-plus-lg me-1"></i>Tambah baris</button>
    </div>
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle mb-0">
        <thead class="table-light text-center">
          <tr>
            <th style="width:36px"><?= translate('no') ?></th>
            <th style="min-width:230px">Barang (master)</th>
            <th style="min-width:200px"><?= translate('i18n_nama_barang') ?></th>
            <th style="min-width:170px"><?= translate('deskripsi') ?></th>
            <th style="width:90px"><?= translate('i18n_qty_2') ?></th>
            <th style="width:90px"><?= translate('satuan') ?></th>
            <th style="width:140px"><?= translate('harga') ?></th>
            <th style="width:140px"><?= translate('i18n_total_harga') ?></th>
            <th style="width:36px"></th>
          </tr>
        </thead>
        <tbody id="tbody_po"></tbody>
        <tfoot>
          <tr class="fw-semibold">
            <td colspan="7" class="text-end"><?= translate('i18n_subtotal_barang') ?></td>
            <td class="text-end" id="out_total">Rp 0</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <div class="card-body border-top muted">
      Pilih barang dari master supaya stok bertambah otomatis saat diterima (qty harus bilangan bulat).
      Barang belum ada di master? Tambahkan dulu di <a href="<?= site_url('barang') ?>" target="_blank">Master Barang</a>, atau ketik nama saja (tidak masuk stok).
    </div>
  </div>

  <?php if (!empty($ada_tabel_biaya)): ?>
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-plus-slash-minus me-2"></i><?= translate('i18n_biaya_tambahan') ?> <span class="muted"><?= translate('i18n_biaya_kirim_biaya_gosok_packing_dll') ?></span></span>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_tambah_biaya"><i class="bi bi-plus-lg me-1"></i><?= translate('i18n_tambah_biaya') ?></button>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0">
          <thead class="table-light text-center">
            <tr><th style="min-width:200px"><?= translate('i18n_jenis_biaya') ?></th><th style="width:170px"><?= translate('i18n_total_rp') ?></th><th style="min-width:200px"><?= translate('deskripsi') ?></th><th style="width:36px"></th></tr>
          </thead>
          <tbody id="tbody_biaya"></tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <div class="card mb-3">
    <div class="card-body">
      <div class="row justify-content-end">
        <div class="col-md-5">
          <div class="d-flex justify-content-between"><span class="muted"><?= translate('i18n_subtotal_barang') ?></span><span id="sum_barang">Rp 0</span></div>
          <div class="d-flex justify-content-between"><span class="muted"><?= translate('i18n_biaya_tambahan') ?></span><span id="sum_biaya">Rp 0</span></div>
          <div class="d-flex justify-content-between align-items-center mt-1">
            <div class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" role="switch" id="pakai_ppn" name="pakai_ppn" value="1" <?= empty($ada_kolom_ppn) ? 'disabled' : '' ?>>
              <label class="form-check-label muted" for="pakai_ppn"><?= translate('ppn') ?> <?= (int) $tarif_ppn ?>%</label>
            </div>
            <span id="sum_ppn">Rp 0</span>
          </div>
          <div class="d-flex justify-content-between border-top mt-1 pt-1 fs-5 fw-bold"><span><?= translate('i18n_total_po') ?></span><span id="sum_total">Rp 0</span></div>
          <div class="muted text-end"><?= translate('i18n_total_po_inilah_yang_menjadi_hutang_ke') ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2 mb-4">
    <a href="<?= site_url('pemenuhan/po') ?>" class="btn btn-outline-secondary"><?= translate('btn_batal') ?></a>
    <button type="submit" class="btn btn-success" id="btn_simpan" <?= $siap ? '' : 'disabled' ?>><i class="bi bi-check2-square me-1"></i><?= translate('i18n_simpan_po') ?></button>
  </div>
  <?= form_close() ?>
</div>

<template id="tpl_baris">
  <tr>
    <td class="text-center no-baris"></td>
    <td>
      <select name="line_barang[]" class="form-select form-select-sm in-barang">
        <option value="0">- tanpa master (tidak masuk stok) -</option>
        <?php foreach ($barang_list as $b): ?>
          <option value="<?= (int) $b->id ?>" data-nama="<?= html_escape(trim($b->nama . ($b->warna && $b->warna !== '-' ? ' - ' . $b->warna : ''))) ?>" data-satuan="<?= html_escape((string) $b->satuan) ?>"><?= html_escape($label_barang($b)) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="muted ket-stok"></div>
    </td>
    <td><input type="text" name="line_nama[]" class="form-control form-control-sm in-nama" maxlength="255" placeholder="mis. Aluminium 4 inch 6m" required></td>
    <td><textarea name="line_spek[]" class="form-control form-control-sm" rows="1"></textarea></td>
    <td><input type="number" name="line_qty[]" class="form-control form-control-sm text-end in-qty" min="0" step="0.01" value="1"></td>
    <td><input type="text" name="line_satuan[]" class="form-control form-control-sm in-satuan" maxlength="30" placeholder="batang"></td>
    <td><input type="number" name="line_harga[]" class="form-control form-control-sm text-end in-harga" min="0" step="1" placeholder="0" required></td>
    <td class="text-end fw-semibold out-jml">Rp 0</td>
    <td><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 btn-hapus"><i class="bi bi-trash"></i></button></td>
  </tr>
</template>

<template id="tpl_biaya">
  <tr class="baris-biaya">
    <td><input type="text" name="biaya_nama[]" class="form-control form-control-sm" list="list_jenis_biaya" maxlength="100" placeholder="<?= translate('i18n_mis_biaya_kirim') ?>" required></td>
    <td><input type="number" name="biaya_nominal[]" class="form-control form-control-sm text-end in-biaya" min="1" step="1" placeholder="0" required></td>
    <td><input type="text" name="biaya_ket[]" class="form-control form-control-sm" maxlength="255"></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 btn-hapus-biaya"><i class="bi bi-trash"></i></button></td>
  </tr>
</template>
<datalist id="list_jenis_biaya">
  <option value="Biaya kirim"></option>
  <option value="Biaya packing"></option>
  <option value="Biaya bongkar muat"></option>
  <option value="Biaya potong"></option>
</datalist>

<script>
  (function () {
    var tbody = document.getElementById('tbody_po');
    var tbodyBiaya = document.getElementById('tbody_biaya');
    var TARIF_PPN = <?= (float) $tarif_ppn ?>;
    function rp(n) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n || 0)); }

    function hitung() {
      var total = 0, no = 1;
      tbody.querySelectorAll('tr').forEach(function (tr) {
        tr.querySelector('.no-baris').textContent = no++;
        var q = Number(tr.querySelector('.in-qty').value) || 0, h = Number(tr.querySelector('.in-harga').value) || 0;
        tr.querySelector('.out-jml').textContent = rp(q * h);
        total += q * h;
      });
      var biaya = 0;
      if (tbodyBiaya) {
        tbodyBiaya.querySelectorAll('.in-biaya').forEach(function (el) { biaya += Math.max(0, Number(el.value) || 0); });
      }
      var chk = document.getElementById('pakai_ppn');
      var ppn = (chk && chk.checked) ? Math.round((total + biaya) * TARIF_PPN / 100) : 0;
      document.getElementById('out_total').textContent = rp(total);
      document.getElementById('sum_barang').textContent = rp(total);
      document.getElementById('sum_biaya').textContent = rp(biaya);
      document.getElementById('sum_ppn').textContent = rp(ppn);
      document.getElementById('sum_total').textContent = rp(total + biaya + ppn);
    }

    // Pilih barang master -> isi nama & satuan, qty wajib bulat (masuk stok)
    function aturBarang(tr) {
      var sel = tr.querySelector('.in-barang'), opt = sel.options[sel.selectedIndex];
      var pakai = sel.value !== '0', qty = tr.querySelector('.in-qty');
      if (pakai) {
        tr.querySelector('.in-nama').value = opt.getAttribute('data-nama') || '';
        tr.querySelector('.in-satuan').value = opt.getAttribute('data-satuan') || '';
      }
      qty.step = pakai ? '1' : '0.01';
      tr.querySelector('.ket-stok').innerHTML = pakai ? '<span class="text-success"><i class="bi bi-box-arrow-in-down"></i> masuk stok saat diterima</span>' : '';
    }

    function tambahBaris() {
      tbody.appendChild(document.getElementById('tpl_baris').content.cloneNode(true));
      hitung();
    }

    tbody.addEventListener('input', hitung);
    tbody.addEventListener('change', function (e) {
      if (e.target.classList.contains('in-barang')) { aturBarang(e.target.closest('tr')); }
      hitung();
    });
    tbody.addEventListener('click', function (e) {
      var b = e.target.closest('.btn-hapus');
      if (!b) { return; }
      if (tbody.querySelectorAll('tr').length > 1) { b.closest('tr').remove(); }
      hitung();
    });
    document.getElementById('btn_tambah').addEventListener('click', tambahBaris);

    if (tbodyBiaya) {
      tbodyBiaya.addEventListener('input', hitung);
      tbodyBiaya.addEventListener('click', function (e) {
        var b = e.target.closest('.btn-hapus-biaya');
        if (b) { b.closest('tr').remove(); hitung(); }
      });
      document.getElementById('btn_tambah_biaya').addEventListener('click', function () {
        tbodyBiaya.appendChild(document.getElementById('tpl_biaya').content.cloneNode(true));
        hitung();
      });
    }
    var chkPpn = document.getElementById('pakai_ppn');
    if (chkPpn) { chkPpn.addEventListener('change', hitung); }

    var kirim = false;
    document.getElementById('form_po').addEventListener('submit', function (e) {
      if (kirim) { e.preventDefault(); return; }
      kirim = true;
      document.getElementById('btn_simpan').disabled = true;
    });

    tambahBaris();
  })();
</script>
