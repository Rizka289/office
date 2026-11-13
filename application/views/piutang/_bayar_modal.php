<?php defined('BASEPATH') or exit('No direct script access allowed');
// Modal catat pembayaran customer. Dibuka dari tombol .btn-bayar-piutang dengan data-*:
// data-id, data-no, data-customer, data-sisa, data-kurang-dp
// $kembali = URL tujuan setelah simpan (relatif, mis. 'piutang?tahap=tagih')
?>
<div class="modal fade" id="modalBayarPiutang" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <?= form_open('piutang/bayar/0', array('id' => 'formBayarPiutang')) ?>
      <input type="hidden" name="kembali" value="<?= html_escape($kembali) ?>">
      <div class="modal-header">
        <h6 class="modal-title"><i class="bi bi-wallet2 me-2"></i>Catat pembayaran <span id="bp_no" class="fw-semibold"></span></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="note mb-3 small">
          <div id="bp_customer" class="fw-semibold"></div>
          Sisa tagihan: <b id="bp_sisa"></b><span id="bp_dp_wrap"> · Kurang DP: <b id="bp_dp"></b></span>
        </div>
        <div class="row g-2">
          <div class="col-6">
            <label class="form-label small mb-1">Tanggal terima</label>
            <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label small mb-1">Jenis</label>
            <select name="jenis" id="bp_jenis" class="form-select form-select-sm">
              <option value="dp">DP</option>
              <option value="pelunasan">Pelunasan</option>
              <option value="lainnya">Lainnya</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label small mb-1">Nominal (Rp)</label>
            <input type="number" name="nominal" id="bp_nominal" class="form-control form-control-sm" min="1" step="0.01" required>
          </div>
          <div class="col-6">
            <label class="form-label small mb-1">Metode</label>
            <input type="text" name="metode" class="form-control form-control-sm" maxlength="50" placeholder="Transfer / Tunai / Giro">
          </div>
          <div class="col-6">
            <label class="form-label small mb-1">No. referensi</label>
            <input type="text" name="no_referensi" class="form-control form-control-sm" maxlength="100" placeholder="No. bukti transfer">
          </div>
          <div class="col-12">
            <input type="text" name="catatan" class="form-control form-control-sm" maxlength="255" placeholder="Catatan">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check2 me-1"></i>Simpan pembayaran</button>
      </div>
      <?= form_close() ?>
    </div>
  </div>
</div>
<script>
  (function () {
    var form = document.getElementById('formBayarPiutang');
    var jenis = document.getElementById('bp_jenis');
    var nominal = document.getElementById('bp_nominal');
    var base = <?= json_encode(site_url('piutang/bayar/')) ?>;
    var sisa = 0, kurangDp = 0;
    var rp = function (n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); };

    document.querySelectorAll('.btn-bayar-piutang').forEach(function (btn) {
      btn.addEventListener('click', function () {
        sisa = parseFloat(this.dataset.sisa) || 0;
        kurangDp = parseFloat(this.dataset.kurangDp) || 0;
        form.action = base + this.dataset.id;
        document.getElementById('bp_no').textContent = this.dataset.no;
        document.getElementById('bp_customer').textContent = this.dataset.customer;
        document.getElementById('bp_sisa').textContent = rp(sisa);
        document.getElementById('bp_dp').textContent = rp(kurangDp);
        document.getElementById('bp_dp_wrap').style.display = kurangDp > 0 ? '' : 'none';
        jenis.value = kurangDp > 0 ? 'dp' : 'pelunasan';
        nominal.max = sisa;
        nominal.value = kurangDp > 0 ? kurangDp : sisa;
      });
    });
    jenis.addEventListener('change', function () {
      nominal.value = this.value === 'dp' && kurangDp > 0 ? kurangDp : sisa;
    });
  })();
</script>
