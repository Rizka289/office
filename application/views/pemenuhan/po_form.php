<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');
$dropship = $jenis === 'dropship';
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <div class="judul"><i class="bi <?= $dropship ? 'bi-truck' : 'bi-cart-plus' ?> me-2"></i><?= $dropship ? translate('i18n_po_dropship') : translate('i18n_po_beli_dikirim_ke_gudang') ?></div>
      <div class="meta"><?= translate('i18n_untuk') ?> <?= html_escape($h->no_so) ?> &middot; <?= html_escape($h->nama_customer) ?></div>
    </div>
    <a href="<?= site_url('pemenuhan/so/' . (int) $h->id) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i><?= translate('kembali') ?></a>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <?php if ($dropship): ?>
    <div class="alert alert-info">
      <i class="bi bi-info-circle me-1"></i><?= translate('i18n_supplier_mengirim_barang') ?> <b><?= translate('i18n_langsung_ke_alamat_customer') ?></b><?= translate('i18n_barang_tidak_masuk_gudang_kita_cetak') ?> <b><?= translate('i18n_surat_jalan_dropship') ?></b> <?= translate('i18n_tanpa_harga_untuk_dibawa_supplier_dan') ?>
    </div>
  <?php endif; ?>

  <!-- Pilih supplier (reload halaman) -->
  <div class="card mb-3">
    <div class="card-body">
      <form method="get" action="<?= site_url('pemenuhan/po_buat/' . (int) $h->id) ?>" class="row g-2 align-items-end">
        <input type="hidden" name="jenis" value="<?= html_escape($jenis) ?>">
        <div class="col-md-5">
          <label class="form-label"><?= translate('i18n_supplier') ?> <span class="text-danger">*</span></label>
          <select name="id_supplier" class="form-select" onchange="this.form.submit()">
            <option value="0"><?= translate('i18n_pilih_supplier') ?></option>
            <?php foreach ($supplier_list as $s): ?>
              <option value="<?= (int) $s->id ?>" <?= (int) $s->id === (int) $id_supplier ? 'selected' : '' ?>><?= html_escape($s->nama) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-7 muted">
          <?= translate('i18n_item_yang_tampil_sumber') ?> <b><?= $dropship ? translate('i18n_dropship') : translate('i18n_beli') ?></b> <?= translate('i18n_yang_belum_dipesan_dengan_supplier') ?>
          <?php if (!$supplier_list): ?><br><a href="<?= site_url('supplier') ?>"><?= translate('i18n_tambah_supplier_dulu') ?></a>.<?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <?= form_open('pemenuhan/po_simpan/' . (int) $h->id, array('id' => 'form_po', 'autocomplete' => 'off')) ?>
  <input type="hidden" name="jenis" value="<?= html_escape($jenis) ?>">
  <input type="hidden" name="id_supplier" value="<?= (int) $id_supplier ?>">

  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-info-circle me-2"></i><?= translate('i18n_data_po') ?></div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label"><?= translate('i18n_tanggal_po') ?></label>
          <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label"><?= translate('i18n_dibutuhkan_tanggal') ?></label>
          <input type="date" name="tgl_butuh" class="form-control" value="<?= html_escape((string) $h->tgl_kirim) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label"><?= translate('i18n_alamat_kirim') ?> <?= $dropship ? translate('i18n_alamat_customer') : translate('i18n_gudang_2') ?></label>
          <textarea name="alamat_kirim" class="form-control" rows="2"><?= html_escape($alamat_default) ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label"><?= translate('i18n_catatan_untuk_supplier') ?></label>
          <textarea name="catatan" class="form-control" rows="2" placeholder="<?= translate('i18n_mis_syarat_pembayaran_jam_bongkar_kontak') ?>"><?= $dropship ? translate('i18n_dropship_kirim_langsung_ke_customer') : '' ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="bi bi-list-ul me-2"></i><?= translate('i18n_barang_dipesan') ?></span>
      <?php if (!$dropship): ?>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_tambah"><i class="bi bi-plus-lg me-1"></i><?= translate('i18n_baris_tambahan_bahan_baku_lainnya') ?></button>
      <?php endif; ?>
    </div>
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle mb-0 tbl-po">
        <thead class="table-light text-center">
          <tr>
            <th style="width:36px"><?= translate('no') ?></th>
            <th style="min-width:200px"><?= translate('i18n_nama_barang') ?></th>
            <th style="width:95px"><?= translate('i18n_lebar_mm') ?></th>
            <th style="width:95px"><?= translate('i18n_tinggi_mm') ?></th>
            <th style="width:85px"><?= translate('i18n_qty_2') ?></th>
            <th style="width:100px"><?= translate('i18n_luas_m2') ?></th>
            <th style="min-width:190px"><?= translate('deskripsi') ?></th>
            <th style="width:140px"><?= translate('harga') ?></th>
            <th style="width:150px"><?= translate('i18n_total_harga') ?></th>
            <th style="width:36px"></th>
          </tr>
        </thead>
        <tbody id="tbody_po">
          <?php foreach ($items as $it):
            $spek = array();
            foreach (array('finishing', 'komponen', 'kelengkapan', 'keterangan') as $k) {
              if (trim((string) ($it->$k ?? '')) !== '' && trim((string) $it->$k) !== '-') {
                $spek[] = trim($it->$k);
              }
            }
            list($uk_l, $uk_t) = $this->Pemenuhan_model->ukuran($it);
            $ada_ukuran = $uk_l > 0 && $uk_t > 0;
          ?>
            <tr>
              <td class="text-center no-baris"></td>
              <td>
                <input type="hidden" name="line_id[]" value="<?= (int) $it->id ?>">
                <input type="hidden" name="line_satuan[]" value="unit">
                <input type="text" name="line_nama[]" class="form-control form-control-sm" value="<?= html_escape($this->Pemenuhan_model->nama_item_polos($it)) ?>">
                <div class="muted"><?= translate('i18n_item_so_sisa_belum_dipesan') ?> <?= $num($it->p_sisa_proses) ?></div>
              </td>
              <td><input type="number" name="line_lebar[]" class="form-control form-control-sm text-end in-l" min="0" step="1" value="<?= $ada_ukuran ? $uk_l : '' ?>"></td>
              <td><input type="number" name="line_tinggi[]" class="form-control form-control-sm text-end in-t" min="0" step="1" value="<?= $ada_ukuran ? $uk_t : '' ?>"></td>
              <td><input type="number" name="line_qty[]" class="form-control form-control-sm text-end in-qty" min="0" max="<?= (float) $it->p_sisa_proses ?>" step="0.01" value="<?= (float) $it->p_sisa_proses ?>"></td>
              <td class="text-end out-luas">-</td>
              <td><textarea name="line_spek[]" class="form-control form-control-sm" rows="2"><?= html_escape(implode("\n", $spek)) ?></textarea></td>
              <td>
                <input type="number" name="line_harga[]" class="form-control form-control-sm text-end in-harga" min="0" step="1" value="" placeholder="0" required>
                <div class="muted ket-per text-end"></div>
              </td>
              <td class="text-end fw-semibold out-jml">Rp 0</td>
              <td></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$items): ?>
            <tr class="baris-kosong"><td colspan="10" class="text-center text-muted py-3">
              <?= translate('i18n_tidak_ada_item_so_untuk_supplier_ini') ?><?= $dropship ? '.' : '. Anda tetap bisa menambah baris bahan baku.' ?>
            </td></tr>
          <?php endif; ?>
        </tbody>
        <tfoot>
          <tr class="fw-semibold">
            <td colspan="4" class="text-end"><?= translate('total') ?></td>
            <td class="text-end" id="out_qty">0</td>
            <td class="text-end" id="out_luas">0</td>
            <td colspan="2" class="text-end"><?= translate('i18n_subtotal_barang') ?></td>
            <td class="text-end fw-bold" id="out_total">Rp 0</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <div class="card-body border-top muted">
      <?= translate('i18n_luas_lebar_tinggi_otomatis_total_harga') ?> <b><?= translate('i18n_luas_qty_harga_m2') ?></b><?= translate('i18n_jika_lebar_tinggi_dikosongkan_total') ?> <b><?= translate('i18n_qty_harga') ?></b><?= translate('i18n_isi_qty_0_untuk_barang_yang_tidak') ?> <b><?= translate('i18n_draft') ?></b> <?= translate('i18n_dan_menunggu_persetujuan_atasan') ?>
    </div>
  </div>

  <!-- Biaya tambahan: ongkir, gosok, packing, bongkar, dll -->
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="bi bi-plus-slash-minus me-2"></i><?= translate('i18n_biaya_tambahan') ?> <span class="muted"><?= translate('i18n_biaya_kirim_biaya_gosok_packing_dll') ?></span></span>
      <?php if (!empty($ada_tabel_biaya)): ?>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_tambah_biaya"><i class="bi bi-plus-lg me-1"></i><?= translate('i18n_tambah_biaya') ?></button>
      <?php endif; ?>
    </div>
    <?php if (empty($ada_tabel_biaya)): ?>
      <div class="card-body">
        <div class="alert alert-warning mb-0 py-2">
          <i class="bi bi-exclamation-triangle me-1"></i><?= translate('i18n_fitur_biaya_tambahan_belum_aktif') ?> <b><?= translate('i18n_database_pm_po_biaya_sql') ?></b> <?= translate('i18n_di_database_dulu') ?>
        </div>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0">
          <thead class="table-light text-center">
            <tr>
              <th style="min-width:180px"><?= translate('i18n_jenis_biaya') ?></th>
              <th style="width:130px"><?= translate('i18n_cara_hitung') ?></th>
              <th style="width:110px"><?= translate('i18n_luas_m2') ?></th>
              <th style="width:140px"><?= translate('i18n_harga_m2') ?></th>
              <th style="width:160px"><?= translate('i18n_total_rp') ?></th>
              <th style="min-width:180px"><?= translate('deskripsi') ?></th>
              <th style="width:36px"></th>
            </tr>
          </thead>
          <tbody id="tbody_biaya">
            <tr class="biaya-kosong"><td colspan="7" class="text-center text-muted py-2"><?= translate('i18n_belum_ada_biaya_tambahan_klik') ?> <b><?= translate('i18n_tambah_biaya') ?></b> <?= translate('i18n_kalau_ada_ongkir_biaya_gosok_dll') ?></td></tr>
          </tbody>
          <tfoot>
            <tr class="fw-semibold">
              <td colspan="4" class="text-end"><?= translate('i18n_total_biaya_tambahan') ?></td>
              <td class="text-end" id="out_biaya">Rp 0</td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <div class="card-body border-top muted">
        <b><?= translate('i18n_borongan') ?></b><?= translate('i18n_total_diketik_langsung_mis_biaya_kirim') ?> <b><?= translate('i18n_per_m2_2') ?></b><?= translate('i18n_total') ?> <b><?= translate('i18n_luas_harga_m2') ?></b> <?= translate('i18n_mis_biaya_gosok_luas_otomatis_diisi') ?>
      </div>
      <datalist id="list_jenis_biaya">
        <option value="Biaya kirim"></option>
        <option value="Biaya gosok"></option>
        <option value="Biaya packing"></option>
        <option value="Biaya bongkar muat"></option>
        <option value="Biaya potong"></option>
        <option value="Biaya cat / finishing"></option>
      </datalist>
    <?php endif; ?>
  </div>

  <!-- Ringkasan total PO -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="row justify-content-end">
        <div class="col-md-5">
          <div class="d-flex justify-content-between"><span class="muted"><?= translate('i18n_subtotal_barang') ?></span><span id="sum_barang">Rp 0</span></div>
          <div class="d-flex justify-content-between"><span class="muted"><?= translate('i18n_biaya_tambahan') ?></span><span id="sum_biaya">Rp 0</span></div>
          <div class="d-flex justify-content-between align-items-center mt-1">
            <div class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" role="switch" id="pakai_ppn" name="pakai_ppn" value="1" <?= empty($ada_kolom_ppn) ? 'disabled' : '' ?>>
              <label class="form-check-label muted" for="pakai_ppn"><?= translate('ppn') ?> <?= (int) ($tarif_ppn ?? 11) ?>%</label>
            </div>
            <span id="sum_ppn">Rp 0</span>
          </div>
          <?php if (empty($ada_kolom_ppn)): ?>
            <div class="text-warning small"><?= translate('i18n_ppn_belum_aktif_jalankan_database_pm_po') ?></div>
          <?php endif; ?>
          <div class="d-flex justify-content-between border-top mt-1 pt-1 fs-5 fw-bold"><span><?= translate('i18n_total_po') ?></span><span id="sum_total">Rp 0</span></div>
          <div class="muted text-end"><?= translate('i18n_total_po_inilah_yang_menjadi_hutang_ke') ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <a href="<?= site_url('pemenuhan/so/' . (int) $h->id) ?>" class="btn btn-outline-secondary"><?= translate('btn_batal') ?></a>
    <button type="submit" class="btn btn-success" id="btn_simpan" <?= $id_supplier ? '' : 'disabled title="' . translate('i18n_pilih_supplier_dulu') . '"' ?>>
      <i class="bi bi-check2-square me-1"></i><?= translate('i18n_simpan_po') ?>
    </button>
  </div>
  <?= form_close() ?>
</div>

<template id="tpl_baris">
  <tr>
    <td class="text-center no-baris"></td>
    <td>
      <input type="hidden" name="line_id[]" value="0">
      <input type="hidden" name="line_satuan[]" value="unit">
      <input type="text" name="line_nama[]" class="form-control form-control-sm" placeholder="<?= translate('i18n_mis_kaca_tempered_8mm') ?>" required>
      <div class="muted"><?= translate('i18n_baris_tambahan') ?></div>
    </td>
    <td><input type="number" name="line_lebar[]" class="form-control form-control-sm text-end in-l" min="0" step="1"></td>
    <td><input type="number" name="line_tinggi[]" class="form-control form-control-sm text-end in-t" min="0" step="1"></td>
    <td><input type="number" name="line_qty[]" class="form-control form-control-sm text-end in-qty" min="0" step="0.01" value="1"></td>
    <td class="text-end out-luas">-</td>
    <td><textarea name="line_spek[]" class="form-control form-control-sm" rows="2"></textarea></td>
    <td>
      <input type="number" name="line_harga[]" class="form-control form-control-sm text-end in-harga" min="0" step="1" placeholder="0" required>
      <div class="muted ket-per text-end"></div>
    </td>
    <td class="text-end fw-semibold out-jml">Rp 0</td>
    <td><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 btn-hapus"><i class="bi bi-trash"></i></button></td>
  </tr>
</template>

<template id="tpl_biaya">
  <tr class="baris-biaya">
    <td><input type="text" name="biaya_nama[]" class="form-control form-control-sm b-nama" list="list_jenis_biaya" maxlength="100" placeholder="<?= translate('i18n_mis_biaya_kirim') ?>" required></td>
    <td>
      <select name="biaya_hitung[]" class="form-select form-select-sm b-hitung">
        <option value="borongan"><?= translate('i18n_borongan') ?></option>
        <option value="m2"><?= translate('i18n_per_m2_2') ?></option>
      </select>
    </td>
    <!-- Semua input selalu ikut terkirim (readonly, bukan disabled) supaya urutan array tetap sejajar -->
    <td><input type="number" name="biaya_luas[]" class="form-control form-control-sm text-end b-luas" min="0" step="0.01" placeholder="0"></td>
    <td><input type="number" name="biaya_harga[]" class="form-control form-control-sm text-end b-harga" min="0" step="1" placeholder="0"></td>
    <td><input type="number" name="biaya_nominal[]" class="form-control form-control-sm text-end in-biaya" min="0" step="1" placeholder="0"></td>
    <td><input type="text" name="biaya_ket[]" class="form-control form-control-sm" maxlength="255" placeholder="<?= translate('i18n_mis_semarang_jakarta_1_truk') ?>"></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 btn-hapus-biaya"><i class="bi bi-trash"></i></button></td>
  </tr>
</template>

<script>
  (function () {
    var tbody = document.getElementById('tbody_po');
    var tbodyBiaya = document.getElementById('tbody_biaya');
    var luasPo = 0; // total luas barang di PO (m²), untuk isi otomatis biaya per m²
    var TARIF_PPN = <?= (float) ($tarif_ppn ?? 11) ?>;
    var chkPpnEl = document.getElementById('pakai_ppn');
    if (chkPpnEl) { chkPpnEl.addEventListener('change', function () { hitung(); }); }
    function aturHitungBiaya(tr) {
      var sel = tr.querySelector('.b-hitung'), inL = tr.querySelector('.b-luas');
      if (sel.value === 'm2' && !(Number(inL.value) > 0) && luasPo > 0) {
        inL.value = luasPo;
      }
    }
    function rp(n) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n || 0)); }
    // Luas selalu 2 angka di belakang koma (dibulatkan). Contoh: 3,298 -> 3,30
    function bulat2(v) { return Math.round((v + Number.EPSILON) * 100) / 100; }
    function luasTxt(v) {
      return bulat2(v).toFixed(2).replace('.', ',');
    }
    function hitung() {
      var total = 0, tq = 0, tl = 0, no = 1;
      tbody.querySelectorAll('tr').forEach(function (tr) {
        var q = tr.querySelector('.in-qty'), h = tr.querySelector('.in-harga');
        if (!q || !h) { return; }
        tr.querySelector('.no-baris').textContent = no++;
        var L = Number(tr.querySelector('.in-l').value) || 0,
            T = Number(tr.querySelector('.in-t').value) || 0,
            Q = Number(q.value) || 0,
            luas = Math.round(L * T / 10000) / 100, // per unit, 2 desimal (sama dengan server & penawaran)
            j = (luas > 0 ? luas * Q : Q) * (Number(h.value) || 0);
        tr.querySelector('.out-luas').textContent = luas > 0 ? luasTxt(luas) : '-';
        tr.querySelector('.ket-per').textContent = luas > 0 ? '<?= tj('i18n_per_m2') ?>' : '<?= tj('i18n_per_unit') ?>';
        tr.querySelector('.out-jml').textContent = rp(j);
        // hanya salah satu (lebar / tinggi) yang diisi -> tandai
        tr.querySelector('.in-l').classList.toggle('is-invalid', T > 0 && !(L > 0));
        tr.querySelector('.in-t').classList.toggle('is-invalid', L > 0 && !(T > 0));
        total += j; tq += Q; tl += luas * Q;
      });
      document.getElementById('out_total').textContent = rp(total);
      document.getElementById('out_qty').textContent = new Intl.NumberFormat('id-ID').format(tq);
      document.getElementById('out_luas').textContent = tl > 0 ? luasTxt(tl) : '0';

      luasPo = bulat2(tl);
      var biaya = 0;
      if (tbodyBiaya) {
        tbodyBiaya.querySelectorAll('.baris-biaya').forEach(function (tr) {
          var m2 = tr.querySelector('.b-hitung').value === 'm2',
              inL = tr.querySelector('.b-luas'), inH = tr.querySelector('.b-harga'), inT = tr.querySelector('.in-biaya');
          // Per m²: luas & harga diisi, total otomatis. Borongan: total diketik, luas & harga tidak dipakai.
          [inL, inH].forEach(function (el) {
            el.readOnly = !m2; el.required = m2;
            el.style.visibility = m2 ? 'visible' : 'hidden';
          });
          inT.readOnly = m2; inT.required = !m2;
          inT.classList.toggle('bg-light', m2);
          if (m2) {
            var t = (Number(inL.value) || 0) * (Number(inH.value) || 0);
            inT.value = t > 0 ? Math.round(t) : '';
          }
          biaya += Math.max(0, Number(inT.value) || 0);
        });
        document.getElementById('out_biaya').textContent = rp(biaya);
      }
      document.getElementById('sum_barang').textContent = rp(total);
      document.getElementById('sum_biaya').textContent = rp(biaya);
      // PPN dari (subtotal barang + biaya tambahan), dibulatkan ke rupiah (sama dengan server)
      var chkPpn = document.getElementById('pakai_ppn');
      var ppn = (chkPpn && chkPpn.checked) ? Math.round((total + biaya) * TARIF_PPN / 100) : 0;
      document.getElementById('sum_ppn').textContent = rp(ppn);
      document.getElementById('sum_total').textContent = rp(total + biaya + ppn);
    }
    tbody.addEventListener('input', hitung);
    tbody.addEventListener('change', hitung);
    if (tbodyBiaya) {
      tbodyBiaya.addEventListener('input', function (e) {
        // Jenis "gosok" -> otomatis hitung per m²
        if (e.target.classList.contains('b-nama') && /gosok/i.test(e.target.value)) {
          var tr = e.target.closest('tr'), sel = tr.querySelector('.b-hitung');
          if (sel.value !== 'm2') { sel.value = 'm2'; aturHitungBiaya(tr); }
        }
        hitung();
      });
      tbodyBiaya.addEventListener('change', function (e) {
        if (e.target.classList.contains('b-hitung')) {
          var tr = e.target.closest('tr');
          aturHitungBiaya(tr);
          if (e.target.value === 'borongan') { tr.querySelector('.in-biaya').value = ''; }
        }
        hitung();
      });
      tbodyBiaya.addEventListener('click', function (e) {
        var b = e.target.closest('.btn-hapus-biaya');
        if (!b) { return; }
        b.closest('tr').remove();
        if (!tbodyBiaya.querySelector('.baris-biaya')) {
          tbodyBiaya.innerHTML = '<tr class="biaya-kosong"><td colspan="7" class="text-center text-muted py-2"><?= tj('i18n_belum_ada_biaya_tambahan') ?></td></tr>';
        }
        hitung();
      });
      document.getElementById('btn_tambah_biaya').addEventListener('click', function () {
        var kosong = tbodyBiaya.querySelector('.biaya-kosong');
        if (kosong) { kosong.remove(); }
        tbodyBiaya.appendChild(document.getElementById('tpl_biaya').content.cloneNode(true));
        var rows = tbodyBiaya.querySelectorAll('.baris-biaya');
        rows[rows.length - 1].querySelector('input').focus();
        hitung();
      });
    }
    tbody.addEventListener('click', function (e) {
      var b = e.target.closest('.btn-hapus');
      if (b) { b.closest('tr').remove(); hitung(); }
    });
    var btn = document.getElementById('btn_tambah');
    if (btn) {
      btn.addEventListener('click', function () {
        var kosong = tbody.querySelector('.baris-kosong');
        if (kosong) { kosong.remove(); }
        tbody.appendChild(document.getElementById('tpl_baris').content.cloneNode(true));
        hitung();
      });
    }
    var kirim = false;
    document.getElementById('form_po').addEventListener('submit', function (e) {
      if (kirim) { e.preventDefault(); return; }
      kirim = true;
      document.getElementById('btn_simpan').disabled = true;
    });
    hitung();
  })();
</script>
