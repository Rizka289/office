<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');

// Ringkasan untuk tombol aksi
$ada = array('produksi' => false, 'beli' => false, 'dropship' => false, 'kirim' => false);
$belum_sumber = 0;
$tot_qty = 0;
$tot_kirim = 0;
foreach ($items as $it) {
  if ($it->sumber && $it->p_sisa_proses > 0) {
    $ada[$it->sumber] = true;
  }
  if ($it->p_siap_kirim > 0) {
    $ada['kirim'] = true;
  }
  if (!$it->sumber) {
    $belum_sumber++;
  }
  $tot_qty += (float) $it->qty;
  $tot_kirim += (float) $it->qty_terkirim;
}
$pct_total = $tot_qty > 0 ? min(100, round($tot_kirim / $tot_qty * 100)) : 0;

// Hak akses (dari controller Pemenuhan::$izin)
$izin       = isset($izin) ? $izin : array();
$bisa_atur  = $bisa_proses && !empty($izin['atur']);
$bisa_prod  = $bisa_proses && !empty($izin['produksi']);
$bisa_kirim = $bisa_proses && !empty($izin['kirim']);
$bisa_appr  = $bisa_proses && !empty($izin['approve']);
$bisa_trm   = $bisa_proses && !empty($izin['terima']);
$bisa_btl   = $bisa_proses && !empty($izin['batal_po']);
$kembali    = 'pemenuhan/so/' . (int) $h->id;
// Tombol aksi PO dengan hidden "kembali" supaya redirect ke halaman ini
$tombol_po = function ($id, $aksi, $label, $kelas, $tanya) use ($kembali) {
  return form_open('pemenuhan/po_aksi/' . (int) $id . '/' . $aksi, array('class' => 'd-inline', 'onsubmit' => "return confirm('" . $tanya . "')"))
    . form_hidden('kembali', $kembali)
    . '<button type="submit" class="btn btn-sm ' . $kelas . ' py-0 px-2">' . $label . '</button>'
    . form_close();
};
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
      <div class="judul"><i class="bi bi-diagram-3 me-2"></i>Proses Pesanan <?= html_escape($h->no_so) ?></div>
      <div class="meta mt-1">
        <?= html_escape($h->nama_customer) ?> &nbsp;|&nbsp; Target kirim: <?= $tgl($h->tgl_kirim) ?>
        &nbsp;|&nbsp; Terkirim <?= $num($tot_kirim) ?> / <?= $num($tot_qty) ?> (<?= $pct_total ?>%)
      </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <?= $badge('so', $h->status) ?>
      <a href="<?= site_url('sales_order/detail/' . (int) $h->id) ?>" class="btn btn-light btn-sm"><i class="bi bi-file-text me-1"></i>Detail SO</a>
      <a href="<?= site_url('pemenuhan') ?>" class="btn btn-outline-light btn-sm"><i class="bi bi-list-task me-1"></i>Semua proses</a>
    </div>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <?php if (!$bisa_proses): ?>
    <div class="alert alert-warning">
      <?php if ($h->status === 'draft'): ?>
        SO masih <b>Draft</b>. Catat DP lalu konfirmasi SO di halaman <a href="<?= site_url('sales_order/detail/' . (int) $h->id) ?>">detail SO</a> sebelum diproses.
      <?php elseif ($h->status === 'selesai'): ?>
        SO sudah <b>Selesai</b> (semua barang terkirim &amp; lunas). Halaman ini hanya untuk melihat riwayat.
      <?php else: ?>
        SO berstatus <b><?= html_escape($h->status) ?></b>, tidak bisa diproses.
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Alur -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="langkah">
        <div><b>1. Tentukan sumber</b>Tiap item: produksi sendiri, beli ke gudang, dropship, atau ambil dari stok gudang.</div>
        <div><b>2. Buat SPK / PO</b>Produksi &rarr; SPK. Beli / Dropship &rarr; PO, disetujui atasan, lalu dikirim ke supplier. Finance mencatat DP / pelunasan di detail PO.</div>
        <div><b>3. Barang siap</b>SPK selesai / barang PO diterima gudang / stok dipilih. Dropship: konfirmasi diterima customer.</div>
        <div><b>4. Kirim</b>Buat surat jalan dari gudang. SO otomatis jadi Terkirim, lalu Selesai jika lunas.</div>
      </div>
    </div>
  </div>

  <!-- 1. Item & sumber -->
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span><i class="bi bi-list-check me-2"></i>Item pesanan &amp; sumber barang</span>
      <?php if ($belum_sumber > 0): ?>
        <span class="badge text-bg-warning"><?= $belum_sumber ?> item belum ditentukan sumbernya</span>
      <?php endif; ?>
    </div>
    <?= form_open('pemenuhan/simpan_sumber/' . (int) $h->id) ?>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:36px">#</th>
            <th>Item</th>
            <th class="text-end">Qty</th>
            <th style="min-width:190px">Sumber</th>
            <th style="min-width:190px">Supplier</th>
            <th class="text-end">SPK / PO / Stok</th>
            <th class="text-end">Siap</th>
            <th class="text-end" style="min-width:110px">Terkirim</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$items): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">SO ini tidak punya item.</td></tr>
          <?php endif; ?>
          <?php $no = 1; foreach ($items as $it):
            $id = (int) $it->id;
            $pct = (float) $it->qty > 0 ? min(100, round($it->qty_terkirim / (float) $it->qty * 100)) : 0;
          ?>
            <tr>
              <td><?= $no++ ?></td>
              <td>
                <div class="fw-semibold"><?= html_escape($it->p_nama) ?></div>
                <?php if (trim((string) ($it->finishing ?? '')) !== ''): ?><div class="muted"><?= html_escape($it->finishing) ?></div><?php endif; ?>
              </td>
              <td class="text-end"><?= $num($it->qty) ?></td>
              <td>
                <?php if ($it->p_terkunci || !$bisa_atur): ?>
                  <?= $badge('sumber', $it->sumber) ?>
                  <input type="hidden" name="sumber[<?= $id ?>]" value="<?= html_escape((string) $it->sumber) ?>" class="in-sumber" data-row="<?= $id ?>">
                  <?php if ($it->p_terkunci && $bisa_atur): ?><div class="muted"><i class="bi bi-lock"></i> sudah diproses</div><?php endif; ?>
                <?php else: ?>
                  <select name="sumber[<?= $id ?>]" class="form-select form-select-sm in-sumber" data-row="<?= $id ?>">
                    <option value="">-- pilih --</option>
                    <option value="produksi" <?= $it->sumber === 'produksi' ? 'selected' : '' ?>>Produksi sendiri</option>
                    <option value="beli" <?= $it->sumber === 'beli' ? 'selected' : '' ?>>Beli (dikirim ke gudang)</option>
                    <option value="dropship" <?= $it->sumber === 'dropship' ? 'selected' : '' ?>>Dropship (supplier &rarr; customer)</option>
                    <option value="stok" <?= $it->sumber === 'stok' ? 'selected' : '' ?>>Ambil dari stok gudang</option>
                  </select>
                <?php endif; ?>
              </td>
              <td>
                <select name="id_supplier[<?= $id ?>]" class="form-select form-select-sm in-sup" id="sup_<?= $id ?>" <?= $bisa_atur ? '' : 'disabled' ?>>
                  <option value="0">-- supplier --</option>
                  <?php foreach ($supplier_list as $s): ?>
                    <option value="<?= (int) $s->id ?>" <?= (int) $it->id_supplier === (int) $s->id ? 'selected' : '' ?>><?= html_escape($s->nama) ?></option>
                  <?php endforeach; ?>
                  <?php if ((int) $it->id_supplier && $it->nama_supplier !== null):
                    $ada_di_list = false;
                    foreach ($supplier_list as $s) { if ((int) $s->id === (int) $it->id_supplier) { $ada_di_list = true; } }
                    if (!$ada_di_list): ?>
                      <option value="<?= (int) $it->id_supplier ?>" selected><?= html_escape($it->nama_supplier) ?> (nonaktif)</option>
                  <?php endif; endif; ?>
                </select>
              </td>
              <td class="text-end">
                <?php if ($it->sumber): ?>
                  <?= $num($it->p_proses) ?>
                  <?php if ($it->p_sisa_proses > 0): ?><div class="muted">sisa <?= $num($it->p_sisa_proses) ?></div><?php endif; ?>
                  <?php if ($it->sumber === 'stok'): ?>
                    <a href="<?= site_url('pemenuhan/stok_ambil/' . $id) ?>" class="btn btn-sm <?= ($bisa_atur && $it->p_sisa_proses > 0) ? 'btn-success' : 'btn-outline-secondary' ?> py-0 px-2 mt-1">
                      <i class="bi bi-archive me-1"></i><?= ($bisa_atur && $it->p_sisa_proses > 0) ? 'Pilih stok' : 'Lihat stok' ?>
                    </a>
                  <?php endif; ?>
                <?php else: ?>-<?php endif; ?>
              </td>
              <td class="text-end">
                <?php if ($it->sumber === 'dropship'): ?>
                  <span class="muted">langsung ke customer</span>
                <?php elseif ($it->sumber): ?>
                  <?= $num($it->p_siap) ?>
                  <?php if ($it->p_siap_kirim > 0): ?><div class="muted text-success">siap kirim <?= $num($it->p_siap_kirim) ?></div><?php endif; ?>
                <?php else: ?>-<?php endif; ?>
              </td>
              <td class="text-end">
                <?= $num($it->qty_terkirim) ?> / <?= $num($it->qty) ?>
                <div class="mini-bar"><div style="width:<?= $pct ?>%"></div></div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($bisa_atur && $items): ?>
      <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2 border-top">
        <div class="muted">
          Sumber yang sudah punya SPK/PO/pengiriman dikunci. Supplier hanya untuk item Beli / Dropship.
          <?php if (!$supplier_list): ?><br><a href="<?= site_url('supplier') ?>">Tambah data supplier dulu</a>.<?php endif; ?>
        </div>
        <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-save me-1"></i>Simpan sumber</button>
      </div>
    <?php endif; ?>
    <?= form_close() ?>
  </div>

  <!-- 2. Aksi -->
  <?php if ($bisa_atur || $bisa_kirim): ?>
    <div class="card mb-3">
      <div class="card-body d-flex flex-wrap gap-2">
        <?php if ($bisa_atur): ?>
          <a href="<?= site_url('pemenuhan/spk_buat/' . (int) $h->id) ?>" class="btn btn-primary btn-sm <?= $ada['produksi'] ? '' : 'disabled' ?>">
            <i class="bi bi-hammer me-1"></i>Buat SPK Produksi
          </a>
          <a href="<?= site_url('pemenuhan/po_buat/' . (int) $h->id . '?jenis=stok') ?>" class="btn btn-warning btn-sm">
            <i class="bi bi-cart-plus me-1"></i>Buat PO Beli / Bahan
          </a>
          <a href="<?= site_url('pemenuhan/po_buat/' . (int) $h->id . '?jenis=dropship') ?>" class="btn btn-info btn-sm <?= $ada['dropship'] ? '' : 'disabled' ?>">
            <i class="bi bi-truck me-1"></i>Buat PO Dropship
          </a>
        <?php endif; ?>
        <?php if ($bisa_kirim): ?>
          <a href="<?= site_url('pemenuhan/sj_buat/' . (int) $h->id) ?>" class="btn btn-success btn-sm <?= $ada['kirim'] ? '' : 'disabled' ?>">
            <i class="bi bi-box-seam me-1"></i>Buat Surat Jalan
          </a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <!-- SPK -->
    <div class="col-12">
      <div class="card h-100">
        <div class="card-header"><i class="bi bi-hammer me-2"></i>SPK Produksi (<?= count($spk_list) ?>)</div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>No. SPK</th><th>Tanggal</th><th>Target</th><th class="text-end">Qty</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
              <?php if (!$spk_list): ?><tr><td colspan="6" class="text-center text-muted py-3">Belum ada SPK.</td></tr><?php endif; ?>
              <?php foreach ($spk_list as $s): ?>
                <tr>
                  <td class="fw-semibold"><?= html_escape($s->no_spk) ?></td>
                  <td><?= $tgl($s->tanggal) ?></td>
                  <td><?= $tgl($s->target_selesai) ?></td>
                  <td class="text-end"><?= $num($s->total_qty) ?></td>
                  <td><?= $badge('spk', $s->status) ?></td>
                  <td class="text-end text-nowrap">
                    <?php if ($bisa_prod && $s->status === 'antri'): ?>
                      <?= $tombol('pemenuhan/spk_aksi/' . (int) $s->id . '/mulai', 'Mulai', 'btn-outline-primary', 'Mulai kerjakan SPK ini?') ?>
                    <?php endif; ?>
                    <?php if ($bisa_prod && in_array($s->status, array('antri', 'proses'), true)): ?>
                      <?= $tombol('pemenuhan/spk_aksi/' . (int) $s->id . '/selesai', 'Selesai', 'btn-outline-success', 'Produksi selesai dan barang sudah di gudang?') ?>
                      <?= $tombol('pemenuhan/spk_aksi/' . (int) $s->id . '/batal', 'Batal', 'btn-outline-danger', 'Batalkan SPK ini?') ?>
                    <?php endif; ?>
                    <a href="<?= site_url('pemenuhan/spk_cetak/' . (int) $s->id) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2"><i class="bi bi-printer"></i></a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- PO -->
    <div class="col-12">
      <div class="card h-100">
        <div class="card-header"><i class="bi bi-cart me-2"></i>Purchase Order ke supplier (<?= count($po_list) ?>)</div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>No. PO</th><th>Supplier</th><th>Jenis</th><th class="text-end">Total</th><th>Status</th><th>Pembayaran</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
              <?php if (!$po_list): ?><tr><td colspan="7" class="text-center text-muted py-3">Belum ada PO.</td></tr><?php endif; ?>
              <?php foreach ($po_list as $p): ?>
                <tr>
                  <td class="fw-semibold"><a href="<?= site_url('pemenuhan/po_detail/' . (int) $p->id) ?>"><?= html_escape($p->no_po) ?></a><div class="muted"><?= $tgl($p->tanggal) ?></div></td>
                  <td><?= html_escape($p->nama_supplier) ?></td>
                  <td><?= $p->jenis === 'dropship' ? '<span class="badge text-bg-info fw-normal">Dropship</span>' : '<span class="badge text-bg-light fw-normal">Ke gudang</span>' ?></td>
                  <td class="text-end"><?= $rp($p->total) ?></td>
                  <td>
                    <?= $badge('po', $p->status) ?>
                    <?php if ($p->status === 'batal' && !empty($p->alasan_batal)): ?><div class="muted"><?= html_escape($p->alasan_batal) ?></div><?php endif; ?>
                  </td>
                  <td>
                    <?php if ($p->status !== 'batal'): ?>
                      <?= $badge('bayar', $p->status_bayar ?? 'belum') ?>
                      <?php if ((float) ($p->total_bayar ?? 0) > 0): ?><div class="muted"><?= $rp($p->total_bayar) ?></div><?php endif; ?>
                    <?php else: ?>-<?php endif; ?>
                  </td>
                  <td class="text-end text-nowrap">
                    <?php if ($bisa_appr && $p->status === 'draft'): ?>
                      <?= $tombol_po($p->id, 'setujui', 'Setujui', 'btn-outline-success', 'Setujui PO ini?') ?>
                      <a href="<?= site_url('pemenuhan/po_detail/' . (int) $p->id) ?>#tolak" class="btn btn-sm btn-outline-danger py-0 px-2">Tolak</a>
                    <?php endif; ?>
                    <?php if ($bisa_atur && $p->status === 'approved'): ?>
                      <?= $tombol_po($p->id, 'kirim', 'Kirim ke supplier', 'btn-outline-primary', 'PO sudah dikirim / dipesan ke supplier?') ?>
                    <?php endif; ?>
                    <?php if ($bisa_trm && in_array($p->status, array('dipesan', 'sebagian'), true)): ?>
                      <a href="<?= site_url('pemenuhan/po_terima/' . (int) $p->id) ?>" class="btn btn-sm btn-outline-success py-0 px-2">
                        <?= $p->jenis === 'dropship' ? 'Diterima customer' : 'Terima barang' ?>
                      </a>
                    <?php endif; ?>
                    <?php if ($bisa_btl && in_array($p->status, array('draft', 'approved', 'dipesan'), true) && (float) ($p->total_bayar ?? 0) <= 0): ?>
                      <?= $tombol_po($p->id, 'batal', 'Batal', 'btn-outline-danger', 'Batalkan PO ini?') ?>
                    <?php endif; ?>
                    <a href="<?= site_url('pemenuhan/po_detail/' . (int) $p->id) ?>" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Detail &amp; pembayaran"><i class="bi bi-eye"></i></a>
                    <a href="<?= site_url('pemenuhan/po_cetak/' . (int) $p->id) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Cetak PO"><i class="bi bi-printer"></i></a>
                    <?php if ($p->jenis === 'dropship'): ?>
                      <a href="<?= site_url('pemenuhan/po_cetak/' . (int) $p->id . '/sj') ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Surat jalan untuk dibawa supplier (tanpa harga)"><i class="bi bi-truck"></i></a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Surat jalan -->
    <div class="col-12">
      <div class="card">
        <div class="card-header"><i class="bi bi-box-seam me-2"></i>Pengiriman / Surat Jalan (<?= count($sj_list) ?>)</div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>No. SJ</th><th>Tanggal</th><th>Dikirim dari</th><th>Ekspedisi / Resi</th><th class="text-end">Qty</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
              <?php if (!$sj_list): ?><tr><td colspan="7" class="text-center text-muted py-3">Belum ada pengiriman.</td></tr><?php endif; ?>
              <?php foreach ($sj_list as $s): ?>
                <tr>
                  <td class="fw-semibold"><?= html_escape($s->no_sj) ?></td>
                  <td><?= $tgl($s->tanggal) ?></td>
                  <td>
                    <?php if ($s->jenis === 'dropship'): ?>
                      Supplier: <?= html_escape($s->nama_supplier) ?><div class="muted">PO <?= html_escape($s->no_po) ?></div>
                    <?php else: ?>Gudang<?php endif; ?>
                  </td>
                  <td><?= html_escape(trim($s->ekspedisi . ' ' . $s->no_kendaraan . ' ' . $s->no_resi)) ?: '-' ?></td>
                  <td class="text-end"><?= $num($s->total_qty) ?></td>
                  <td><?= $badge('sj', $s->status) ?></td>
                  <td class="text-end text-nowrap">
                    <?php if ($bisa_kirim && $s->jenis === 'gudang' && $s->status === 'terkirim'): ?>
                      <?= $tombol('pemenuhan/sj_batal/' . (int) $s->id, 'Batal', 'btn-outline-danger', 'Batalkan surat jalan ini? Qty kembali ke siap kirim.') ?>
                    <?php endif; ?>
                    <a href="<?= site_url('pemenuhan/sj_cetak/' . (int) $s->id) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2"><i class="bi bi-printer"></i></a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Supplier hanya aktif untuk sumber Beli / Dropship
  (function () {
    function atur(row) {
      var s = document.querySelector('.in-sumber[data-row="' + row + '"]');
      var sup = document.getElementById('sup_' + row);
      if (!s || !sup || sup.hasAttribute('data-kunci')) { return; }
      var pakai = s.value === 'beli' || s.value === 'dropship';
      sup.disabled = !pakai;
      if (!pakai) { sup.value = '0'; }
    }
    <?php if ($bisa_proses): ?>
    document.querySelectorAll('.in-sumber').forEach(function (el) {
      atur(el.getAttribute('data-row'));
      el.addEventListener('change', function () { atur(this.getAttribute('data-row')); });
    });
    <?php endif; ?>
  })();
</script>
