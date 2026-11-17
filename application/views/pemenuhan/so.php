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
  return form_open('pemenuhan/po_aksi/' . (int) $id . '/' . $aksi, array('class' => 'd-inline', 'onsubmit' => "return confirm('" . js_esc($tanya) . "')"))
    . form_hidden('kembali', $kembali)
    . '<button type="submit" class="btn btn-sm ' . $kelas . ' py-0 px-2">' . $label . '</button>'
    . form_close();
};
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
      <div class="judul"><i class="bi bi-diagram-3 me-2"></i><?= translate('app_purchasing') ?> <?= html_escape($h->no_so) ?></div>
      <div class="meta mt-1">
        <?= html_escape($h->nama_customer) ?> <?= translate('i18n_target_kirim_2') ?> <?= $tgl($h->tgl_kirim) ?>
        <?= translate('i18n_terkirim_2') ?> <?= $num($tot_kirim) ?> / <?= $num($tot_qty) ?> (<?= $pct_total ?>%)
      </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <?= $badge('so', $h->status) ?>
      <a href="<?= site_url('sales_order/detail/' . (int) $h->id) ?>" class="btn btn-light btn-sm"><i class="bi bi-file-text me-1"></i><?= translate('i18n_detail_so') ?></a>
      <a href="<?= site_url('pemenuhan') ?>" class="btn btn-outline-light btn-sm"><i class="bi bi-list-task me-1"></i><?= translate('i18n_semua_proses') ?></a>
    </div>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <?php if (!$bisa_proses): ?>
    <div class="alert alert-warning">
      <?php if ($h->status === 'draft'): ?>
        <?= translate('i18n_so_masih') ?> <b><?= translate('i18n_draft') ?></b><?= translate('i18n_catat_dp_lalu_konfirmasi_so_di_halaman') ?> <a href="<?= site_url('sales_order/detail/' . (int) $h->id) ?>"><?= translate('i18n_detail_so_2') ?></a> <?= translate('i18n_sebelum_diproses') ?>
      <?php elseif ($h->status === 'selesai'): ?>
        <?= translate('i18n_so_sudah') ?> <b><?= translate('selesai') ?></b> <?= translate('i18n_semua_barang_terkirim_lunas_halaman_ini') ?>
      <?php else: ?>
        <?= translate('i18n_so_berstatus') ?> <b><?= html_escape($h->status) ?></b><?= translate('i18n_tidak_bisa_diproses') ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Alur -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="langkah">
        <div><b><?= translate('i18n_1_tentukan_sumber') ?></b><?= translate('i18n_tiap_item_produksi_sendiri_beli_ke') ?></div>
        <div><b><?= translate('i18n_2_buat_spk_po') ?></b><?= translate('i18n_produksi_spk_beli_dropship_po_disetujui') ?></div>
        <div><b><?= translate('i18n_3_barang_siap') ?></b><?= translate('i18n_spk_selesai_barang_po_diterima_gudang') ?></div>
        <div><b><?= translate('i18n_4_kirim') ?></b><?= translate('i18n_buat_surat_jalan_dari_gudang_so_otomatis') ?></div>
      </div>
    </div>
  </div>

  <!-- 1. Item & sumber -->
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span><i class="bi bi-list-check me-2"></i><?= translate('i18n_item_pesanan_sumber_barang') ?></span>
      <?php if ($belum_sumber > 0): ?>
        <span class="badge text-bg-warning"><?= $belum_sumber ?> <?= translate('i18n_item_belum_ditentukan_sumbernya') ?></span>
      <?php endif; ?>
    </div>
    <?= form_open('pemenuhan/simpan_sumber/' . (int) $h->id) ?>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:36px">#</th>
            <th><?= translate('i18n_item_3') ?></th>
            <th class="text-end"><?= translate('i18n_qty_2') ?></th>
            <th style="min-width:190px"><?= translate('i18n_sumber') ?></th>
            <th style="min-width:190px"><?= translate('i18n_supplier') ?></th>
            <th class="text-end"><?= translate('i18n_spk_po_stok') ?></th>
            <th class="text-end"><?= translate('i18n_siap') ?></th>
            <th class="text-end" style="min-width:110px"><?= translate('kirim') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$items): ?>
            <tr><td colspan="8" class="text-center text-muted py-4"><?= translate('i18n_so_ini_tidak_punya_item') ?></td></tr>
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
                  <?php if ($it->p_terkunci && $bisa_atur): ?><div class="muted"><i class="bi bi-lock"></i> <?= translate('i18n_sudah_diproses') ?></div><?php endif; ?>
                <?php else: ?>
                  <select name="sumber[<?= $id ?>]" class="form-select form-select-sm in-sumber" data-row="<?= $id ?>">
                    <option value=""><?= translate('i18n_pilih_2') ?></option>
                    <option value="produksi" <?= $it->sumber === 'produksi' ? 'selected' : '' ?>><?= translate('i18n_produksi_sendiri') ?></option>
                    <option value="beli" <?= $it->sumber === 'beli' ? 'selected' : '' ?>><?= translate('i18n_beli_dikirim_ke_gudang') ?></option>
                    <option value="dropship" <?= $it->sumber === 'dropship' ? 'selected' : '' ?>><?= translate('i18n_dropship_supplier_customer') ?></option>
                    <option value="stok" <?= $it->sumber === 'stok' ? 'selected' : '' ?>><?= translate('i18n_ambil_dari_stok_gudang') ?></option>
                  </select>
                <?php endif; ?>
              </td>
              <td>
                <select name="id_supplier[<?= $id ?>]" class="form-select form-select-sm in-sup" id="sup_<?= $id ?>" <?= $bisa_atur ? '' : 'disabled' ?>>
                  <option value="0"><?= translate('i18n_supplier_3') ?></option>
                  <?php foreach ($supplier_list as $s): ?>
                    <option value="<?= (int) $s->id ?>" <?= (int) $it->id_supplier === (int) $s->id ? 'selected' : '' ?>><?= html_escape($s->nama) ?></option>
                  <?php endforeach; ?>
                  <?php if ((int) $it->id_supplier && $it->nama_supplier !== null):
                    $ada_di_list = false;
                    foreach ($supplier_list as $s) { if ((int) $s->id === (int) $it->id_supplier) { $ada_di_list = true; } }
                    if (!$ada_di_list): ?>
                      <option value="<?= (int) $it->id_supplier ?>" selected><?= html_escape($it->nama_supplier) ?> <?= translate('i18n_nonaktif') ?></option>
                  <?php endif; endif; ?>
                </select>
              </td>
              <td class="text-end">
                <?php if ($it->sumber): ?>
                  <?= $num($it->p_proses) ?>
                  <?php if ($it->p_sisa_proses > 0): ?><div class="muted"><?= translate('i18n_sisa_3') ?> <?= $num($it->p_sisa_proses) ?></div><?php endif; ?>
                  <?php if ($it->sumber === 'stok'): ?>
                    <a href="<?= site_url('pemenuhan/stok_ambil/' . $id) ?>" class="btn btn-sm <?= ($bisa_atur && $it->p_sisa_proses > 0) ? 'btn-success' : 'btn-outline-secondary' ?> py-0 px-2 mt-1">
                      <i class="bi bi-archive me-1"></i><?= ($bisa_atur && $it->p_sisa_proses > 0) ? translate('i18n_pilih_stok') : translate('i18n_lihat_stok') ?>
                    </a>
                  <?php endif; ?>
                <?php else: ?>-<?php endif; ?>
              </td>
              <td class="text-end">
                <?php if ($it->sumber === 'dropship'): ?>
                  <span class="muted"><?= translate('i18n_langsung_ke_customer_2') ?></span>
                <?php elseif ($it->sumber): ?>
                  <?= $num($it->p_siap) ?>
                  <?php if ($it->p_siap_kirim > 0): ?><div class="muted text-success"><?= translate('i18n_siap_kirim') ?> <?= $num($it->p_siap_kirim) ?></div><?php endif; ?>
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
          <?= translate('i18n_sumber_yang_sudah_punya_spk_po') ?>
          <?php if (!$supplier_list): ?><br><a href="<?= site_url('supplier') ?>"><?= translate('i18n_tambah_data_supplier_dulu') ?></a>.<?php endif; ?>
        </div>
        <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-save me-1"></i><?= translate('i18n_simpan_sumber') ?></button>
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
            <i class="bi bi-hammer me-1"></i><?= translate('i18n_buat_spk_produksi') ?>
          </a>
          <a href="<?= site_url('pemenuhan/po_buat/' . (int) $h->id . '?jenis=stok') ?>" class="btn btn-warning btn-sm">
            <i class="bi bi-cart-plus me-1"></i><?= translate('i18n_buat_po_beli_bahan') ?>
          </a>
          <a href="<?= site_url('pemenuhan/po_buat/' . (int) $h->id . '?jenis=dropship') ?>" class="btn btn-info btn-sm <?= $ada['dropship'] ? '' : 'disabled' ?>">
            <i class="bi bi-truck me-1"></i><?= translate('i18n_buat_po_dropship') ?>
          </a>
        <?php endif; ?>
        <?php if ($bisa_kirim): ?>
          <a href="<?= site_url('pemenuhan/sj_buat/' . (int) $h->id) ?>" class="btn btn-success btn-sm <?= $ada['kirim'] ? '' : 'disabled' ?>">
            <i class="bi bi-box-seam me-1"></i><?= translate('i18n_buat_surat_jalan') ?>
          </a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <!-- SPK -->
    <div class="col-12">
      <div class="card h-100">
        <div class="card-header"><i class="bi bi-hammer me-2"></i><?= translate('i18n_spk_produksi') ?><?= count($spk_list) ?>)</div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th><?= translate('i18n_no_spk') ?></th><th><?= translate('tanggal') ?></th><th><?= translate('i18n_target') ?></th><th class="text-end"><?= translate('i18n_qty_2') ?></th><th><?= translate('status') ?></th><th class="text-end"><?= translate('aksi') ?></th></tr></thead>
            <tbody>
              <?php if (!$spk_list): ?><tr><td colspan="6" class="text-center text-muted py-3"><?= translate('i18n_belum_ada_spk') ?></td></tr><?php endif; ?>
              <?php foreach ($spk_list as $s): ?>
                <tr>
                  <td class="fw-semibold"><?= html_escape($s->no_spk) ?></td>
                  <td><?= $tgl($s->tanggal) ?></td>
                  <td><?= $tgl($s->target_selesai) ?></td>
                  <td class="text-end"><?= $num($s->total_qty) ?></td>
                  <td><?= $badge('spk', $s->status) ?></td>
                  <td class="text-end text-nowrap">
                    <?php if ($bisa_prod && $s->status === 'antri'): ?>
                      <?= $tombol('pemenuhan/spk_aksi/' . (int) $s->id . '/mulai', translate('i18n_mulai'), 'btn-outline-primary', translate('i18n_mulai_kerjakan_spk_ini')) ?>
                    <?php endif; ?>
                    <?php if ($bisa_prod && in_array($s->status, array('antri', 'proses'), true)): ?>
                      <?= $tombol('pemenuhan/spk_aksi/' . (int) $s->id . '/selesai', translate('selesai'), 'btn-outline-success', translate('i18n_produksi_selesai_dan_barang_sudah_di')) ?>
                      <?= $tombol('pemenuhan/spk_aksi/' . (int) $s->id . '/batal', translate('btn_batal'), 'btn-outline-danger', translate('i18n_batalkan_spk_ini')) ?>
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
        <div class="card-header"><i class="bi bi-cart me-2"></i><?= translate('i18n_purchase_order_ke_supplier') ?><?= count($po_list) ?>)</div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th><?= translate('i18n_no_po_2') ?></th><th><?= translate('i18n_supplier') ?></th><th><?= translate('jenis') ?></th><th class="text-end"><?= translate('total') ?></th><th><?= translate('status') ?></th><th><?= translate('pembayaran') ?></th><th class="text-end"><?= translate('aksi') ?></th></tr></thead>
            <tbody>
              <?php if (!$po_list): ?><tr><td colspan="7" class="text-center text-muted py-3"><?= translate('i18n_belum_ada_po') ?></td></tr><?php endif; ?>
              <?php foreach ($po_list as $p): ?>
                <tr>
                  <td class="fw-semibold"><a href="<?= site_url('pemenuhan/po_detail/' . (int) $p->id) ?>"><?= html_escape($p->no_po) ?></a><div class="muted"><?= $tgl($p->tanggal) ?></div></td>
                  <td><?= html_escape($p->nama_supplier) ?></td>
                  <td><?= $p->jenis === 'dropship' ? '<span class="badge text-bg-info fw-normal">Dropship</span>' : '<span class="badge text-bg-light fw-normal">' . translate('i18n_ke_gudang') . '</span>' ?></td>
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
                      <?= $tombol_po($p->id, 'setujui', translate('i18n_setujui'), 'btn-outline-success', translate('i18n_setujui_po_ini')) ?>
                      <a href="<?= site_url('pemenuhan/po_detail/' . (int) $p->id) ?>#tolak" class="btn btn-sm btn-outline-danger py-0 px-2"><?= translate('i18n_tolak') ?></a>
                    <?php endif; ?>
                    <?php if ($bisa_atur && $p->status === 'approved'): ?>
                      <?= $tombol_po($p->id, 'kirim', translate('i18n_kirim_ke_supplier'), 'btn-outline-primary', translate('i18n_po_sudah_dikirim_dipesan_ke_supplier')) ?>
                    <?php endif; ?>
                    <?php if ($bisa_trm && in_array($p->status, array('dipesan', 'sebagian'), true)): ?>
                      <a href="<?= site_url('pemenuhan/po_terima/' . (int) $p->id) ?>" class="btn btn-sm btn-outline-success py-0 px-2">
                        <?= $p->jenis === 'dropship' ? 'Diterima customer' : translate('i18n_terima_barang_2') ?>
                      </a>
                    <?php endif; ?>
                    <?php if ($bisa_btl && in_array($p->status, array('draft', 'approved', 'dipesan'), true) && (float) ($p->total_bayar ?? 0) <= 0): ?>
                      <?= $tombol_po($p->id, 'batal', translate('btn_batal'), 'btn-outline-danger', translate('i18n_batalkan_po_ini')) ?>
                    <?php endif; ?>
                    <a href="<?= site_url('pemenuhan/po_detail/' . (int) $p->id) ?>" class="btn btn-sm btn-outline-secondary py-0 px-2" title="<?= translate('i18n_detail_pembayaran') ?>"><i class="bi bi-eye"></i></a>
                    <a href="<?= site_url('pemenuhan/po_cetak/' . (int) $p->id) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2" title="<?= translate('i18n_cetak_po') ?>"><i class="bi bi-printer"></i></a>
                    <?php if ($p->jenis === 'dropship'): ?>
                      <a href="<?= site_url('pemenuhan/po_cetak/' . (int) $p->id . '/sj') ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2" title="<?= translate('i18n_surat_jalan_untuk_dibawa_supplier_tanpa') ?>"><i class="bi bi-truck"></i></a>
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
        <div class="card-header"><i class="bi bi-box-seam me-2"></i><?= translate('i18n_pengiriman_surat_jalan') ?><?= count($sj_list) ?>)</div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th><?= translate('i18n_no_sj') ?></th><th><?= translate('tanggal') ?></th><th><?= translate('i18n_dikirim_dari') ?></th><th><?= translate('i18n_ekspedisi_resi') ?></th><th class="text-end"><?= translate('i18n_qty_2') ?></th><th><?= translate('status') ?></th><th class="text-end"><?= translate('aksi') ?></th></tr></thead>
            <tbody>
              <?php if (!$sj_list): ?><tr><td colspan="7" class="text-center text-muted py-3"><?= translate('i18n_belum_ada_pengiriman') ?></td></tr><?php endif; ?>
              <?php foreach ($sj_list as $s): ?>
                <tr>
                  <td class="fw-semibold"><?= html_escape($s->no_sj) ?></td>
                  <td><?= $tgl($s->tanggal) ?></td>
                  <td>
                    <?php if ($s->jenis === 'dropship'): ?>
                      <?= translate('i18n_supplier_4') ?> <?= html_escape($s->nama_supplier) ?><div class="muted"><?= translate('i18n_po') ?> <?= html_escape($s->no_po) ?></div>
                    <?php else: ?><?= translate('i18n_gudang') ?><?php endif; ?>
                  </td>
                  <td><?= html_escape(trim($s->ekspedisi . ' ' . $s->no_kendaraan . ' ' . $s->no_resi)) ?: '-' ?></td>
                  <td class="text-end"><?= $num($s->total_qty) ?></td>
                  <td><?= $badge('sj', $s->status) ?></td>
                  <td class="text-end text-nowrap">
                    <?php if ($bisa_kirim && $s->jenis === 'gudang' && $s->status === 'terkirim'): ?>
                      <?= $tombol('pemenuhan/sj_batal/' . (int) $s->id, translate('btn_batal'), 'btn-outline-danger', translate('i18n_batalkan_surat_jalan_ini_qty_kembali_ke')) ?>
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
