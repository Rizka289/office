<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');

$h        = $po['header'];
$dropship = $h->jenis === 'dropship';
$izin     = isset($izin) ? $izin : array();
$kembali  = 'pemenuhan/po_detail/' . (int) $h->id;

$total    = (float) $h->total;
$biaya_po = isset($po['biaya']) ? $po['biaya'] : array();
$ppn_po   = (float) ($h->ppn_nominal ?? 0);
$ppn_pct  = (float) ($h->ppn_persen ?? 0);
$rinci    = $biaya_po || $ppn_po > 0;   // tampilkan rincian subtotal / biaya / PPN
$sub_brg  = 0;
foreach ($po['items'] as $d_) { $sub_brg += (float) $d_->total; }
$dibayar  = (float) ($h->total_bayar ?? 0);
$sisa     = max(0, round($total - $dibayar, 2));
$pct      = $total > 0 ? min(100, round($dibayar / $total * 100)) : 0;

$bisa_appr = $bisa_proses && !empty($izin['approve']) && $h->status === 'draft';
$bisa_kirm = $bisa_proses && !empty($izin['atur']) && $h->status === 'approved';
$bisa_trm  = $bisa_proses && !empty($izin['terima']) && in_array($h->status, array('dipesan', 'sebagian'), true);
$bisa_btl  = $bisa_proses && !empty($izin['batal_po']) && in_array($h->status, array('draft', 'approved', 'dipesan'), true) && $dibayar <= 0;
$bisa_byr  = !empty($izin['bayar']) && $boleh_bayar && $sisa > 0;
$bisa_hps  = !empty($izin['bayar']);

$tombol_po = function ($aksi, $label, $kelas, $tanya) use ($h, $kembali) {
  return form_open('pemenuhan/po_aksi/' . (int) $h->id . '/' . $aksi, array('class' => 'd-inline', 'onsubmit' => "return confirm('" . js_esc($tanya) . "')"))
    . form_hidden('kembali', $kembali)
    . '<button type="submit" class="btn btn-sm ' . $kelas . '">' . $label . '</button>'
    . form_close();
};
// Luas selalu 2 angka di belakang koma (dibulatkan). Contoh: 3,298 -> 3,30
$fmt_luas = function ($v) {
  return number_format(round((float) $v, 2), 2, ',', '.');
};
$per_m2 = function ($d) { return ($d->hitung_per ?? 'unit') === 'm2'; };
$tgljam = function ($d) { return $d ? date('d M Y H:i', strtotime($d)) : '-'; };
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
      <div class="judul"><i class="bi bi-cart me-2"></i><?= html_escape($h->no_po) ?></div>
      <div class="meta mt-1">
        <?= html_escape($h->supplier ? $h->supplier->nama : '-') ?> &middot;
        <?= $dropship ? translate('i18n_dropship_ke_customer') : translate('i18n_dikirim_ke_gudang') ?> &middot;
        <?= html_escape($h->no_so) ?> (<?= html_escape($h->nama_customer) ?>)
      </div>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= site_url('pemenuhan/po_cetak/' . (int) $h->id) ?>" target="_blank" class="btn btn-light btn-sm"><i class="bi bi-printer me-1"></i><?= translate('i18n_cetak_po') ?></a>
      <a href="<?= site_url('pemenuhan/so/' . (int) $h->id_so) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i><?= translate('i18n_proses_so') ?></a>
    </div>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <!-- Status & aksi -->
  <div class="row g-3 mb-3">
    <div class="col-lg-7">
      <div class="card h-100">
        <div class="card-header"><i class="bi bi-flag me-2"></i><?= translate('i18n_status_persetujuan') ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-sm-4">
              <div class="muted"><?= translate('i18n_status_po') ?></div>
              <div><?= $badge('po', $h->status) ?></div>
            </div>
            <div class="col-sm-4">
              <div class="muted"><?= translate('lbl_dbuat') ?></div>
              <div><?= html_escape($h->nama_pembuat ?: '-') ?></div>
              <div class="muted"><?= $tgl($h->tanggal) ?></div>
            </div>
            <div class="col-sm-4">
              <div class="muted"><?= ($h->status === 'batal' && $h->approved_by) ? translate('i18n_ditolak_oleh') : translate('i18n_disetujui_oleh') ?></div>
              <div><?= $h->approved_by ? html_escape($h->nama_approver ?: '-') : '<span class="muted">' . translate('i18n_belum') . '</span>' ?></div>
              <?php if ($h->approved_at): ?><div class="muted"><?= $tgljam($h->approved_at) ?></div><?php endif; ?>
            </div>
          </div>

          <?php if ($h->status === 'batal' && !empty($h->alasan_batal)): ?>
            <div class="alert alert-danger py-2 mt-3 mb-0"><?= html_escape($h->alasan_batal) ?></div>
          <?php elseif ($h->status === 'draft'): ?>
            <div class="note mt-3"><?= translate('i18n_po_menunggu_persetujuan_atasan_setelah') ?></div>
          <?php endif; ?>

          <?php if ($bisa_appr || $bisa_kirm || $bisa_trm || $bisa_btl): ?>
            <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
              <?php if ($bisa_appr): ?>
                <?= $tombol_po('setujui', '<i class="bi bi-check2-circle me-1"></i>' . translate('i18n_setujui_po') . '', 'btn-success', translate('i18n_setujui_po_ini')) ?>
              <?php endif; ?>
              <?php if ($bisa_kirm): ?>
                <?= $tombol_po('kirim', '<i class="bi bi-send me-1"></i>' . translate('i18n_kirim_ke_supplier') . '', 'btn-primary', translate('i18n_po_sudah_dikirim_dipesan_ke_supplier')) ?>
              <?php endif; ?>
              <?php if ($bisa_trm): ?>
                <a href="<?= site_url('pemenuhan/po_terima/' . (int) $h->id) ?>" class="btn btn-sm btn-outline-success">
                  <i class="bi bi-box-arrow-in-down me-1"></i><?= $dropship ? translate('i18n_konfirmasi_diterima_customer') : translate('i18n_terima_barang_2') ?>
                </a>
              <?php endif; ?>
              <?php if ($bisa_btl): ?>
                <?= $tombol_po('batal', translate('i18n_batalkan_po'), 'btn-outline-danger', translate('i18n_batalkan_po_ini')) ?>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if ($bisa_appr): ?>
            <div id="tolak" class="mt-3">
              <?= form_open('pemenuhan/po_aksi/' . (int) $h->id . '/tolak', array('onsubmit' => "return confirm('" . tj('i18n_tolak_po_ini_po_akan_dibatalkan') . "')")) ?>
              <?= form_hidden('kembali', $kembali) ?>
              <div class="input-group input-group-sm">
                <input type="text" name="alasan" class="form-control" maxlength="200" placeholder="<?= translate('i18n_alasan_penolakan_wajib_mis_harga_terlalu') ?>" required>
                <button type="submit" class="btn btn-outline-danger"><i class="bi bi-x-circle me-1"></i><?= translate('i18n_tolak') ?></button>
              </div>
              <?= form_close() ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card h-100">
        <div class="card-header"><i class="bi bi-info-circle me-2"></i><?= translate('i18n_info_pengiriman') ?></div>
        <div class="card-body">
          <div class="muted"><?= translate('i18n_tanggal_dibutuhkan') ?></div>
          <div class="mb-2"><?= $tgl($h->tgl_butuh) ?></div>
          <div class="muted"><?= translate('i18n_alamat_kirim') ?></div>
          <div class="mb-2" style="white-space:pre-line"><?= html_escape($h->alamat_kirim ?: '-') ?></div>
          <?php if (trim((string) $h->catatan) !== ''): ?>
            <div class="muted"><?= translate('i18n_catatan') ?></div>
            <div style="white-space:pre-line"><?= html_escape($h->catatan) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Item -->
  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-list-ul me-2"></i><?= translate('i18n_barang_dipesan') ?></div>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr><th>#</th><th><?= translate('i18n_barang') ?></th><th class="text-end"><?= translate('i18n_lebar_mm') ?></th><th class="text-end"><?= translate('i18n_tinggi_mm') ?></th><th class="text-end"><?= translate('i18n_qty_2') ?></th><th class="text-end"><?= translate('i18n_luas_m2') ?></th><th><?= translate('deskripsi') ?></th><th class="text-end"><?= translate('harga') ?></th><th class="text-end"><?= translate('i18n_total_harga') ?></th><th class="text-end"><?= translate('i18n_diterima') ?></th></tr>
        </thead>
        <tbody>
          <?php $no = 1; $tq = 0; $tl = 0; foreach ($po['items'] as $d): $tq += (float) $d->qty; $tl += (float) ($d->luas_m2 ?? 0) * (float) $d->qty; ?>
            <tr>
              <td><?= $no++ ?></td>
              <td class="fw-semibold"><?= html_escape($d->nama_barang) ?></td>
              <td class="text-end"><?= (int) ($d->lebar_mm ?? 0) > 0 ? $num($d->lebar_mm) : '-' ?></td>
              <td class="text-end"><?= (int) ($d->tinggi_mm ?? 0) > 0 ? $num($d->tinggi_mm) : '-' ?></td>
              <td class="text-end"><?= $num($d->qty) ?></td>
              <td class="text-end"><?= (float) ($d->luas_m2 ?? 0) > 0 ? $fmt_luas($d->luas_m2) : '-' ?></td>
              <td class="muted" style="white-space:pre-line"><?= html_escape((string) $d->spesifikasi) ?></td>
              <td class="text-end"><?= $rp($d->harga) ?><div class="muted"><?= $per_m2($d) ? translate('i18n_per_m2') : translate('i18n_per_unit') ?></div></td>
              <td class="text-end"><?= $rp($d->total) ?></td>
              <td class="text-end"><?= $num($d->qty_diterima) ?> / <?= $num($d->qty) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="fw-semibold"><td colspan="4" class="text-end"><?= translate('total') ?></td><td class="text-end"><?= $num($tq) ?></td><td class="text-end"><?= $tl > 0 ? $fmt_luas($tl) : '-' ?></td><td colspan="2" class="text-end"><?= $rinci ? translate('i18n_subtotal_barang') : translate('i18n_total_po') ?></td><td class="text-end"><?= $rp($rinci ? $sub_brg : $total) ?></td><td></td></tr>
          <?php if ($rinci): ?>
            <?php foreach ($biaya_po as $bp): ?>
              <tr>
                <td colspan="8" class="text-end"><?= html_escape($bp->nama) ?>
                  <?php if (($bp->hitung_per ?? '') === 'm2'): ?><span class="muted">(<?= $fmt_luas($bp->luas_m2) ?> m² &times; <?= $rp($bp->harga) ?>)</span><?php endif; ?>
                  <?= trim((string) ($bp->keterangan ?? '')) !== '' ? '<div class="muted">' . html_escape($bp->keterangan) . '</div>' : '' ?></td>
                <td class="text-end"><?= $rp($bp->nominal) ?></td><td></td>
              </tr>
            <?php endforeach; ?>
            <?php if ($ppn_po > 0): ?>
              <tr><td colspan="8" class="text-end"><?= translate('ppn') ?> <?= rtrim(rtrim(number_format($ppn_pct, 2, ',', ''), '0'), ',') ?>%</td><td class="text-end"><?= $rp($ppn_po) ?></td><td></td></tr>
            <?php endif; ?>
            <tr class="fw-bold"><td colspan="8" class="text-end"><?= translate('i18n_total_po') ?></td><td class="text-end"><?= $rp($total) ?></td><td></td></tr>
          <?php endif; ?>
        </tfoot>
      </table>
    </div>
    <?php if ($po['penerimaan']): ?>
      <div class="card-body border-top">
        <div class="muted mb-1"><?= translate('i18n_riwayat_penerimaan') ?></div>
        <?php foreach ($po['penerimaan'] as $t): ?>
          <div class="small"><?= $tgl($t->tanggal) ?> <?= translate('i18n_sj_supplier') ?> <?= html_escape($t->no_sj_supplier ?: '-') ?><?= trim((string) $t->catatan) !== '' ? ' &middot; ' . html_escape($t->catatan) : '' ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Pembayaran ke supplier -->
  <div class="card mb-3" id="pembayaran">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span><i class="bi bi-cash-coin me-2"></i><?= translate('i18n_pembayaran_ke_supplier') ?></span>
      <?= $h->status !== 'batal' ? $badge('bayar', $h->status_bayar ?? 'belum') : '' ?>
    </div>
    <div class="card-body">
      <div class="row g-3 mb-2">
        <div class="col-sm-4"><div class="muted"><?= translate('i18n_total_po') ?></div><div class="fs-5 fw-semibold"><?= $rp($total) ?></div></div>
        <div class="col-sm-4"><div class="muted"><?= translate('i18n_sudah_dibayar') ?></div><div class="fs-5 fw-semibold text-success"><?= $rp($dibayar) ?></div></div>
        <div class="col-sm-4"><div class="muted"><?= translate('i18n_sisa_hutang') ?></div><div class="fs-5 fw-semibold <?= $sisa > 0 ? 'text-danger' : '' ?>"><?= $rp($sisa) ?></div></div>
      </div>
      <div class="mini-bar mb-3"><div style="width:<?= $pct ?>%"></div></div>

      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead class="table-light">
            <tr><th><?= translate('tanggal') ?></th><th><?= translate('jenis') ?></th><th class="text-end"><?= translate('i18n_nominal_2') ?></th><th><?= translate('i18n_metode_rekening') ?></th><th><?= translate('deskripsi') ?></th><th><?= translate('i18n_dicatat_3') ?></th><th class="text-end"></th></tr>
          </thead>
          <tbody>
            <?php if (!$po['pembayaran']): ?>
              <tr><td colspan="7" class="text-center text-muted py-3"><?= translate('i18n_belum_ada_pembayaran') ?></td></tr>
            <?php endif; ?>
            <?php foreach ($po['pembayaran'] as $b): ?>
              <tr>
                <td><?= $tgl($b->tanggal) ?></td>
                <td><?= $badge('jenis_bayar', $b->jenis) ?></td>
                <td class="text-end"><?= $rp($b->nominal) ?></td>
                <td><?= html_escape(ucfirst($b->metode)) ?><?= $b->rekening_asal ? '<div class="muted">' . html_escape($b->rekening_asal) . '</div>' : '' ?></td>
                <td><?= html_escape((string) $b->keterangan) ?></td>
                <td><?= html_escape($b->nama_user ?: '-') ?></td>
                <td class="text-end text-nowrap">
                  <?php if ($b->bukti): ?>
                    <a href="<?= base_url($b->bukti) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2" title="<?= translate('i18n_lihat_bukti') ?>"><i class="bi bi-paperclip"></i></a>
                  <?php endif; ?>
                  <?php if ($bisa_hps): ?>
                    <?= form_open('pemenuhan/po_bayar_hapus/' . (int) $b->id, array('class' => 'd-inline', 'onsubmit' => "return confirm('" . tj('i18n_hapus_pembayaran_ini') . "')")) ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="<?= translate('hapus') ?>"><i class="bi bi-trash"></i></button>
                    <?= form_close() ?>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if ($bisa_byr): ?>
      <div class="card-body border-top">
        <div class="fw-semibold mb-2"><?= translate('i18n_catat_pembayaran') ?></div>
        <?= form_open_multipart('pemenuhan/po_bayar/' . (int) $h->id, array('autocomplete' => 'off', 'onsubmit' => "return confirm('" . tj('i18n_simpan_pembayaran_ini') . "')")) ?>
        <div class="row g-2">
          <div class="col-md-2">
            <label class="form-label small"><?= translate('tanggal') ?></label>
            <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-md-2">
            <label class="form-label small"><?= translate('jenis') ?></label>
            <select name="jenis" id="in_jenis" class="form-select form-select-sm" required>
              <option value="dp" <?= $dibayar <= 0 ? 'selected' : '' ?>><?= translate('i18n_dp') ?></option>
              <option value="termin"><?= translate('i18n_termin') ?></option>
              <option value="pelunasan" <?= $dibayar > 0 ? 'selected' : '' ?>><?= translate('pelunasan') ?></option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small"><?= translate('nominal(rp)') ?></label>
            <input type="number" name="nominal" id="in_nominal" class="form-control form-control-sm text-end" min="1" max="<?= $sisa ?>" step="1"
                   value="<?= $dibayar > 0 ? $sisa : '' ?>" required>
            <div class="muted"><?= translate('i18n_sisa_2') ?> <?= $rp($sisa) ?></div>
          </div>
          <div class="col-md-2">
            <label class="form-label small"><?= translate('i18n_metode') ?></label>
            <select name="metode" class="form-select form-select-sm">
              <option value="transfer"><?= translate('i18n_transfer') ?></option>
              <option value="tunai"><?= translate('i18n_tunai') ?></option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small"><?= translate('i18n_dari_rekening') ?></label>
            <input type="text" name="rekening_asal" class="form-control form-control-sm" list="list_rek" maxlength="100" placeholder="<?= translate('i18n_mis_rekening_pt') ?>">
            <datalist id="list_rek">
              <option value="Rekening PT"></option>
              <option value="Rekening Mahmudi"></option>
            </datalist>
          </div>
          <div class="col-md-5">
            <label class="form-label small"><?= translate('i18n_bukti_transfer_jpg_png_pdf_maks_2mb') ?></label>
            <input type="file" name="bukti" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf">
          </div>
          <div class="col-md-5">
            <label class="form-label small"><?= translate('deskripsi') ?></label>
            <input type="text" name="keterangan" class="form-control form-control-sm" placeholder="<?= translate('i18n_mis_dp_50_sesuai_kesepakatan') ?>">
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-success btn-sm w-100"><i class="bi bi-save me-1"></i><?= translate('button_save') ?></button>
          </div>
        </div>
        <?= form_close() ?>
      </div>
      <script>
        // Pilih "Pelunasan" -> nominal otomatis = sisa hutang
        (function () {
          var j = document.getElementById('in_jenis'), n = document.getElementById('in_nominal'), sisa = <?= json_encode($sisa) ?>;
          j.addEventListener('change', function () { if (j.value === 'pelunasan') { n.value = sisa; } });
        })();
      </script>
    <?php elseif (!empty($izin['bayar']) && $h->status === 'draft'): ?>
      <div class="card-body border-top"><div class="note"><?= translate('i18n_pembayaran_bisa_dicatat_setelah_po') ?></div></div>
    <?php endif; ?>
  </div>

</div>
