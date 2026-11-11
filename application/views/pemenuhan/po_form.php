<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');
$dropship = $jenis === 'dropship';
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <div class="judul"><i class="bi <?= $dropship ? 'bi-truck' : 'bi-cart-plus' ?> me-2"></i><?= $dropship ? 'PO Dropship' : 'PO Beli (dikirim ke gudang)' ?></div>
      <div class="meta">Untuk <?= html_escape($h->no_so) ?> &middot; <?= html_escape($h->nama_customer) ?></div>
    </div>
    <a href="<?= site_url('pemenuhan/so/' . (int) $h->id) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <?php if ($dropship): ?>
    <div class="alert alert-info">
      <i class="bi bi-info-circle me-1"></i>Supplier mengirim barang <b>langsung ke alamat customer</b>. Barang tidak masuk gudang kita.
      Cetak juga <b>surat jalan dropship</b> (tanpa harga) untuk dibawa supplier, dan minta supplier tidak menyertakan nota harga.
    </div>
  <?php endif; ?>

  <!-- Pilih supplier (reload halaman) -->
  <div class="card mb-3">
    <div class="card-body">
      <form method="get" action="<?= site_url('pemenuhan/po_buat/' . (int) $h->id) ?>" class="row g-2 align-items-end">
        <input type="hidden" name="jenis" value="<?= html_escape($jenis) ?>">
        <div class="col-md-5">
          <label class="form-label">Supplier <span class="text-danger">*</span></label>
          <select name="id_supplier" class="form-select" onchange="this.form.submit()">
            <option value="0">-- pilih supplier --</option>
            <?php foreach ($supplier_list as $s): ?>
              <option value="<?= (int) $s->id ?>" <?= (int) $s->id === (int) $id_supplier ? 'selected' : '' ?>><?= html_escape($s->nama) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-7 muted">
          Item yang tampil: sumber <b><?= $dropship ? 'Dropship' : 'Beli' ?></b> yang belum dipesan, dengan supplier default ini atau belum punya supplier.
          <?php if (!$supplier_list): ?><br><a href="<?= site_url('supplier') ?>">Tambah supplier dulu</a>.<?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <?= form_open('pemenuhan/po_simpan/' . (int) $h->id, array('id' => 'form_po', 'autocomplete' => 'off')) ?>
  <input type="hidden" name="jenis" value="<?= html_escape($jenis) ?>">
  <input type="hidden" name="id_supplier" value="<?= (int) $id_supplier ?>">

  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-info-circle me-2"></i>Data PO</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Tanggal PO</label>
          <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Dibutuhkan tanggal</label>
          <input type="date" name="tgl_butuh" class="form-control" value="<?= html_escape((string) $h->tgl_kirim) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Alamat kirim <?= $dropship ? '(alamat customer)' : '(gudang)' ?></label>
          <textarea name="alamat_kirim" class="form-control" rows="2"><?= html_escape($alamat_default) ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Catatan untuk supplier</label>
          <textarea name="catatan" class="form-control" rows="2" placeholder="Mis. syarat pembayaran, jam bongkar, kontak penerima"><?= $dropship ? 'DROPSHIP: kirim langsung ke customer, JANGAN sertakan nota / harga. Kirim foto bukti terima & no. resi ke kami.' : '' ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="bi bi-list-ul me-2"></i>Barang dipesan</span>
      <?php if (!$dropship): ?>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_tambah"><i class="bi bi-plus-lg me-1"></i>Baris tambahan (bahan baku / lainnya)</button>
      <?php endif; ?>
    </div>
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle mb-0 tbl-po">
        <thead class="table-light text-center">
          <tr>
            <th style="width:36px">No</th>
            <th style="min-width:200px">Nama barang</th>
            <th style="width:95px">Lebar (mm)</th>
            <th style="width:95px">Tinggi (mm)</th>
            <th style="width:85px">Qty</th>
            <th style="width:100px">Luas (m²)</th>
            <th style="min-width:190px">Keterangan</th>
            <th style="width:140px">Harga</th>
            <th style="width:150px">Total Harga</th>
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
                <div class="muted">Item SO &middot; sisa belum dipesan <?= $num($it->p_sisa_proses) ?></div>
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
              Tidak ada item SO untuk supplier ini<?= $dropship ? '.' : '. Anda tetap bisa menambah baris bahan baku.' ?>
            </td></tr>
          <?php endif; ?>
        </tbody>
        <tfoot>
          <tr class="fw-semibold">
            <td colspan="4" class="text-end">Total</td>
            <td class="text-end" id="out_qty">0</td>
            <td class="text-end" id="out_luas">0</td>
            <td colspan="2" class="text-end">Total PO</td>
            <td class="text-end fw-bold" id="out_total">Rp 0</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <div class="card-body border-top muted">
      Luas = Lebar &times; Tinggi (otomatis). Total Harga = <b>Luas &times; Qty &times; Harga / m²</b>. Jika lebar / tinggi dikosongkan, Total Harga = <b>Qty &times; Harga</b>.
      Isi qty 0 untuk barang yang tidak dipesan di PO ini. PO tersimpan sebagai <b>Draft</b> dan menunggu persetujuan atasan.
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <a href="<?= site_url('pemenuhan/so/' . (int) $h->id) ?>" class="btn btn-outline-secondary">Batal</a>
    <button type="submit" class="btn btn-success" id="btn_simpan" <?= $id_supplier ? '' : 'disabled title="Pilih supplier dulu"' ?>>
      <i class="bi bi-check2-square me-1"></i>Simpan PO
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
      <input type="text" name="line_nama[]" class="form-control form-control-sm" placeholder="Mis. Kaca tempered 8mm" required>
      <div class="muted">Baris tambahan</div>
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

<script>
  (function () {
    var tbody = document.getElementById('tbody_po');
    function rp(n) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n || 0)); }
    function luasTxt(v) {
      var t = (Math.round(v * 10000) / 10000).toFixed(4).replace(/0{1,2}$/, '');
      return t.replace('.', ',');
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
            luas = Math.round(L * T / 1000000 * 10000) / 10000,
            j = (luas > 0 ? luas * Q : Q) * (Number(h.value) || 0);
        tr.querySelector('.out-luas').textContent = luas > 0 ? luasTxt(luas) : '-';
        tr.querySelector('.ket-per').textContent = luas > 0 ? 'per m²' : 'per unit';
        tr.querySelector('.out-jml').textContent = rp(j);
        // hanya salah satu (lebar / tinggi) yang diisi -> tandai
        tr.querySelector('.in-l').classList.toggle('is-invalid', T > 0 && !(L > 0));
        tr.querySelector('.in-t').classList.toggle('is-invalid', L > 0 && !(T > 0));
        total += j; tq += Q; tl += luas * Q;
      });
      document.getElementById('out_total').textContent = rp(total);
      document.getElementById('out_qty').textContent = new Intl.NumberFormat('id-ID').format(tq);
      document.getElementById('out_luas').textContent = tl > 0 ? luasTxt(tl) : '0';
    }
    tbody.addEventListener('input', hitung);
    tbody.addEventListener('change', hitung);
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
