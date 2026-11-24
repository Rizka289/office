<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');

$so    = $d['so'];
$items = $d['items'];
$r     = $d['r'];
$nama_item = array();
$ada_di_customer = false;
foreach ($items as $it) {
  $nama_item[(int) $it->id] = $it->p_nama;
  if ($it->di_customer > 0) {
    $ada_di_customer = true;
  }
}
$opsi_label = array('' => '-', 'bukaan_kanan' => translate('i18n_bukaan_kanan'), 'bukaan_kiri' => translate('i18n_bukaan_kiri'), 'aksesoris' => translate('aksesoris'), 'bahan' => translate('bahan'));
$lokasi_teks = function ($l) { return trim(($l->location_code ?? '') . ' - ' . ($l->zone_name ?? ''), ' -'); };
$sisa_hari = function ($n) {
  if ($n < 0) {
    return '<span class="text-danger fw-semibold">' . sprintf(translate('i18n_ksy_lewat_hari'), abs($n)) . '</span>';
  }
  return '<span class="' . ($n <= 7 ? 'text-warning fw-semibold' : 'muted') . '">' . sprintf(translate('i18n_ksy_sisa_hari'), $n) . '</span>';
};
$step = function ($n) { return abs($n - round($n)) < 0.0001 ? '1' : '0.01'; };
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
      <div class="judul"><i class="bi bi-box-seam me-2"></i><?= translate('i18n_barang_titipan') ?> &middot; <?= html_escape($so->no_so) ?></div>
      <div class="meta"><?= html_escape($so->nama_customer ?: '-') ?> &middot; <?= translate('i18n_masa_konsinyasi') ?> <?= (int) $d['masa'] ?> <?= translate('i18n_hari') ?> <?= translate('i18n_sejak_tanggal_surat_jalan') ?> &middot; <?= $badge('so', $so->status) ?></div>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= site_url('konsinyasi') ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i><?= translate('kembali') ?></a>
      <a href="<?= site_url('sales_order/detail/' . (int) $so->id) ?>" class="btn btn-outline-light btn-sm"><i class="bi bi-receipt me-1"></i><?= translate('i18n_ksy_lihat_so') ?></a>
      <a href="<?= site_url('pemenuhan/so/' . (int) $so->id) ?>" class="btn btn-outline-light btn-sm"><i class="bi bi-diagram-3 me-1"></i><?= translate('app_purchasing') ?></a>
    </div>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>
  <?php $this->load->view('konsinyasi/_auto', array('auto' => $auto)); ?>

  <!-- Ringkasan -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-lg-2"><div class="card h-100"><div class="card-body"><div class="muted"><?= translate('i18n_ksy_dititip') ?></div><div class="fs-5 fw-semibold"><?= $num($r['terkirim']) ?> <span class="muted">/ <?= $num($r['qty']) ?></span></div></div></div></div>
    <div class="col-6 col-lg-2"><div class="card h-100"><div class="card-body"><div class="muted"><?= translate('i18n_ksy_laku') ?></div><div class="fs-5 fw-semibold text-success"><?= $num($r['laku']) ?></div></div></div></div>
    <div class="col-6 col-lg-2"><div class="card h-100"><div class="card-body"><div class="muted"><?= translate('i18n_ksy_ditarik') ?></div><div class="fs-5 fw-semibold"><?= $num($r['retur']) ?></div></div></div></div>
    <div class="col-6 col-lg-2"><div class="card h-100"><div class="card-body"><div class="muted"><?= translate('i18n_ksy_di_customer') ?></div><div class="fs-5 fw-semibold"><?= $num($r['di_customer']) ?></div>
      <?php if ($r['jt_terdekat']): ?><div class="small"><?= translate('i18n_ksy_jatuh_tempo') ?> <?= $tgl($r['jt_terdekat']) ?></div><?php endif; ?></div></div></div>
    <div class="col-12 col-lg-4"><div class="card h-100"><div class="card-body">
      <div class="d-flex justify-content-between"><span class="muted"><?= translate('i18n_nilai_barang_dititip') ?></span><span><?= $rp($so->grand_total) ?></span></div>
      <div class="d-flex justify-content-between"><span class="muted"><?= translate('i18n_tagihan_barang_laku') ?></span><strong><?= $rp($r['tagihan']) ?></strong></div>
      <div class="d-flex justify-content-between"><span class="muted"><?= translate('i18n_uang_diterima') ?></span><span><?= $rp($r['dibayar']) ?></span></div>
      <div class="d-flex justify-content-between border-top mt-1 pt-1"><span class="fw-semibold"><?= translate('i18n_ksy_sisa_tagih') ?></span>
        <strong class="<?= $r['sisa_tagih'] > 0.5 ? 'text-danger' : 'text-success' ?>"><?= $r['sisa_tagih'] > 0.5 ? $rp($r['sisa_tagih']) : translate('i18n_lunas') ?></strong></div>
      <?php if ($r['sisa_tagih'] > 0.5): ?><a href="<?= site_url('sales_order/detail/' . (int) $so->id) ?>" class="small"><?= translate('i18n_ksy_catat_bayar_di_so') ?></a><?php endif; ?>
    </div></div></div>
  </div>

  <?php if (!$aktif): ?>
    <div class="alert alert-secondary"><i class="bi bi-info-circle me-1"></i><?= $so->status === 'selesai' ? translate('i18n_ksy_info_selesai') : translate('i18n_ksy_info_belum_kirim') ?></div>
  <?php endif; ?>

  <!-- Posisi barang per item -->
  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-list-check me-2"></i><?= translate('i18n_ksy_posisi_barang') ?></div>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th><?= translate('i18n_barang') ?></th>
            <th class="text-end"><?= translate('i18n_qty_pesanan') ?></th>
            <th class="text-end"><?= translate('i18n_ksy_dititip') ?></th>
            <th class="text-end"><?= translate('i18n_ksy_laku') ?></th>
            <th class="text-end"><?= translate('i18n_ksy_ditarik') ?></th>
            <th class="text-end"><?= translate('i18n_ksy_di_customer') ?></th>
            <th class="text-end"><?= translate('i18n_ksy_harga_tagih_unit') ?></th>
            <th><?= translate('i18n_ksy_per_surat_jalan') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><?= html_escape($it->p_nama) ?><div class="muted"><?= $badge('sumber', $it->sumber) ?></div></td>
              <td class="text-end"><?= $num($it->qty) ?></td>
              <td class="text-end"><?= $num($it->qty_terkirim) ?></td>
              <td class="text-end text-success"><?= $num($it->qty_laku) ?></td>
              <td class="text-end"><?= $num($it->qty_retur) ?></td>
              <td class="text-end fw-semibold"><?= $num($it->di_customer) ?></td>
              <td class="text-end"><?= $rp($it->harga_net) ?></td>
              <td class="small">
                <?php $ada = false; foreach ($it->batch as $b): if ($b['sisa'] <= 0) { continue; } $ada = true; ?>
                  <div><?= html_escape($b['no_sj']) ?> &middot; <?= $tgl($b['tanggal']) ?> &middot; <?= $num($b['sisa']) ?> <?= translate('i18n_ksy_unit') ?> &rarr; <?= $tgl($b['jatuh_tempo']) ?> (<?= $sisa_hari($b['sisa_hari']) ?>)</div>
                <?php endforeach; ?>
                <?php if (!$ada): ?><span class="muted">-</span><?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if ($aktif && $ada_di_customer): ?>
    <div class="row g-3 mb-3">

      <?php if ($izin['laku']): ?>
        <!-- Lapor laku -->
        <div class="col-xl-5">
          <div class="card h-100">
            <div class="card-header"><i class="bi bi-cart-check me-2 text-success"></i><?= translate('i18n_ksy_form_laku') ?></div>
            <div class="card-body">
              <div class="note mb-3"><?= translate('i18n_ksy_form_laku_ket') ?></div>
              <?= form_open('konsinyasi/laku/' . (int) $so->id, array('onsubmit' => "return confirm('" . tj('i18n_ksy_konfirmasi_laku') . "')")) ?>
              <table class="table table-sm align-middle">
                <thead class="table-light"><tr><th><?= translate('i18n_barang') ?></th><th class="text-end"><?= translate('i18n_ksy_di_customer') ?></th><th style="width:110px"><?= translate('i18n_ksy_qty_laku') ?></th></tr></thead>
                <tbody>
                  <?php foreach ($items as $it): if ($it->di_customer <= 0) { continue; } ?>
                    <tr>
                      <td class="small"><?= html_escape($it->p_nama) ?></td>
                      <td class="text-end"><?= $num($it->di_customer) ?></td>
                      <td><input type="number" name="laku[<?= (int) $it->id ?>]" class="form-control form-control-sm" min="0" max="<?= $it->di_customer ?>" step="<?= $step($it->di_customer) ?>" placeholder="0"></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              <div class="row g-2">
                <div class="col-sm-5"><label class="form-label small"><?= translate('tanggal') ?></label><input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required></div>
                <div class="col-sm-7"><label class="form-label small"><?= translate('i18n_ksy_catatan') ?></label><input type="text" name="catatan" maxlength="255" class="form-control form-control-sm" placeholder="<?= translate('i18n_ksy_catatan_laku_ph') ?>"></div>
              </div>
              <button type="submit" class="btn btn-success btn-sm mt-3"><i class="bi bi-check2-circle me-1"></i><?= translate('i18n_ksy_simpan_laku') ?></button>
              <?= form_close() ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($izin['tarik']): ?>
        <!-- Tarik ke gudang -->
        <div class="col-xl-7">
          <div class="card h-100">
            <div class="card-header"><i class="bi bi-arrow-return-left me-2 text-primary"></i><?= translate('i18n_ksy_form_tarik') ?></div>
            <div class="card-body">
              <div class="note mb-3"><?= translate('i18n_ksy_form_tarik_ket') ?></div>
              <?= form_open('konsinyasi/tarik/' . (int) $so->id, array('onsubmit' => "return confirm('" . tj('i18n_ksy_konfirmasi_tarik') . "')")) ?>
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead class="table-light"><tr><th><?= translate('i18n_barang') ?></th><th style="width:90px"><?= translate('i18n_ksy_qty_tarik') ?></th><th><?= translate('i18n_ksy_masuk_ke_stok') ?></th></tr></thead>
                  <tbody>
                    <?php foreach ($items as $it): if ($it->di_customer <= 0) { continue; } $s = $it->stok; $k = (int) $it->id; ?>
                      <tr>
                        <td class="small"><?= html_escape($it->p_nama) ?><div class="muted"><?= translate('i18n_ksy_di_customer') ?>: <?= $num($it->di_customer) ?><?= $it->qty_lewat > 0 ? ' &middot; <span class="text-danger">' . $num($it->qty_lewat) . ' ' . translate('i18n_ksy_lewat_masa') . '</span>' : '' ?></div></td>
                        <td><input type="number" name="tarik[<?= $k ?>]" class="form-control form-control-sm" min="0" max="<?= floor($it->di_customer) ?>" step="1" placeholder="0"></td>
                        <td>
                          <div class="row g-1">
                            <div class="col-md-6">
                              <select name="barang[<?= $k ?>]" class="form-select form-select-sm" title="<?= translate('i18n_barang') ?>">
                                <option value="0">-- <?= translate('pilih_barang') ?> --</option>
                                <?php foreach ($barang as $b): ?>
                                  <option value="<?= (int) $b->id ?>" <?= (int) $b->id === (int) $s['id_barang'] ? 'selected' : '' ?>><?= html_escape(trim($b->kode_barang . ' - ' . $b->nama . ($b->warna ? ' (' . $b->warna . ')' : ''), ' -')) ?></option>
                                <?php endforeach; ?>
                              </select>
                            </div>
                            <div class="col-md-6">
                              <select name="lokasi[<?= $k ?>]" class="form-select form-select-sm" title="<?= translate('i18n_lokasi') ?>">
                                <option value="0">-- <?= translate('pilih_lokasi') ?> --</option>
                                <?php foreach ($lokasi as $l): ?>
                                  <option value="<?= (int) $l->id ?>" <?= (int) $l->id === (int) $s['id_location'] ? 'selected' : '' ?>><?= html_escape($lokasi_teks($l)) ?></option>
                                <?php endforeach; ?>
                              </select>
                            </div>
                            <div class="col-md-6">
                              <select name="opsi[<?= $k ?>]" class="form-select form-select-sm" title="<?= translate('opsi') ?>">
                                <?php foreach ($opsi_label as $ov => $ol): ?>
                                  <option value="<?= html_escape($ov) ?>" <?= $ov === (string) $s['opsi'] ? 'selected' : '' ?>><?= html_escape($ov === '' ? '-- ' . translate('opsi') . ' --' : $ol) ?></option>
                                <?php endforeach; ?>
                              </select>
                            </div>
                            <div class="col-md-6">
                              <input type="text" name="ukuran[<?= $k ?>]" maxlength="100" class="form-control form-control-sm" value="<?= html_escape($s['ukuran']) ?>" placeholder="<?= translate('ukuran') ?>">
                            </div>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <div class="row g-2">
                <div class="col-sm-4"><label class="form-label small"><?= translate('tanggal') ?></label><input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required></div>
                <div class="col-sm-8"><label class="form-label small"><?= translate('i18n_ksy_catatan') ?></label><input type="text" name="catatan" maxlength="255" class="form-control form-control-sm" placeholder="<?= translate('i18n_ksy_catatan_tarik_ph') ?>"></div>
              </div>
              <button type="submit" class="btn btn-primary btn-sm mt-3"><i class="bi bi-box-arrow-in-down me-1"></i><?= translate('i18n_ksy_simpan_tarik') ?></button>
              <?= form_close() ?>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Riwayat -->
  <div class="card">
    <div class="card-header"><i class="bi bi-clock-history me-2"></i><?= translate('i18n_ksy_riwayat') ?></div>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th><?= translate('tanggal') ?></th>
            <th><?= translate('i18n_ksy_jenis') ?></th>
            <th><?= translate('i18n_barang') ?></th>
            <th class="text-end"><?= translate('i18n_qty_2') ?></th>
            <th><?= translate('i18n_ksy_keterangan') ?></th>
            <th><?= translate('petugas') ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$d['mutasi']): ?>
            <tr><td colspan="7" class="text-center text-muted py-3"><?= translate('i18n_ksy_riwayat_kosong') ?></td></tr>
          <?php endif; ?>
          <?php foreach ($d['mutasi'] as $m): $batal = $m->status !== 'aktif'; ?>
            <tr class="<?= $batal ? 'text-muted text-decoration-line-through' : '' ?>">
              <td class="text-nowrap"><?= $tgl($m->tanggal) ?></td>
              <td>
                <?php if ($m->jenis === 'laku'): ?>
                  <span class="badge text-bg-success fw-normal"><?= translate('i18n_ksy_laku') ?></span>
                <?php else: ?>
                  <span class="badge text-bg-primary fw-normal"><?= translate('i18n_ksy_ditarik') ?></span>
                  <?php if ((int) $m->otomatis === 1): ?><span class="badge text-bg-warning fw-normal"><?= translate('i18n_ksy_otomatis') ?></span><?php endif; ?>
                <?php endif; ?>
                <?php if ($batal): ?><span class="badge text-bg-danger fw-normal"><?= translate('btn_batal') ?></span><?php endif; ?>
              </td>
              <td class="small"><?= html_escape($nama_item[(int) $m->id_so_detail] ?? '-') ?></td>
              <td class="text-end"><?= $num($m->qty) ?></td>
              <td class="small">
                <?php if ($m->jenis === 'laku'): ?>
                  <?= translate('i18n_ksy_nilai') ?> <?= $rp($m->nilai) ?>
                <?php else: ?>
                  &rarr; <?= html_escape(trim(($m->kode_barang ?? '') . ' ' . ($m->nama_barang ?? ''))) ?>
                  &middot; <?= html_escape(trim(($m->kode_lokasi ?? '') . ' ' . ($m->nama_lokasi ?? ''))) ?>
                  <?= $m->opsi !== '' ? '&middot; ' . html_escape($opsi_label[$m->opsi] ?? $m->opsi) : '' ?>
                  <?= $m->ukuran !== '' ? '&middot; ' . html_escape($m->ukuran) : '' ?>
                <?php endif; ?>
                <?php if (trim((string) $m->catatan) !== ''): ?><div class="muted"><?= html_escape($m->catatan) ?></div><?php endif; ?>
              </td>
              <td class="small"><?= html_escape($m->nama_user ?: ((int) $m->otomatis === 1 ? translate('i18n_ksy_sistem') : '-')) ?></td>
              <td class="text-end">
                <?php if (!$batal && $izin['batal']): ?>
                  <?= $tombol('konsinyasi/batal/' . (int) $m->id, translate('btn_batal'), 'btn-outline-danger', translate('i18n_ksy_konfirmasi_batal')) ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
