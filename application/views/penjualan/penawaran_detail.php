<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
  .pnw-detail-page .info-card,
  .pnw-detail-page .table-wrap,
  .pnw-detail-page .summary-card {
    background: #fff;
    border: 1px solid #edf0f1;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(15, 42, 41, .04);
  }

  .pnw-detail-page .status-lock {
    font-size: .8rem;
  }

  .pnw-detail-page .info-card {
    padding: 18px 20px;
    margin-bottom: 18px;
  }

  .pnw-detail-page .info-label {
    font-size: .74rem;
    text-transform: uppercase;
    letter-spacing: .03em;
    color: #8a938d;
    margin-bottom: 2px;
  }

  .pnw-detail-page .info-value {
    font-size: .95rem;
    font-weight: 600;
    margin-bottom: 12px;
  }

  .pnw-detail-page .table-wrap {
    overflow: hidden;
    margin-bottom: 18px;
  }

  .pnw-detail-page table thead th {
    background: var(--brand-light);
    color: var(--brand-dark);
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .03em;
    font-weight: 700;
    white-space: nowrap;
    border: none;
    padding: .85rem 1rem;
  }

  .pnw-detail-page table tbody td {
    padding: .8rem 1rem;
    vertical-align: top;
    font-size: .88rem;
    border-color: #f1f3f2;
  }

  .pnw-detail-page .spek-line {
    display: block;
    font-size: .8rem;
    color: #6c757d;
  }

  .pnw-detail-page .spek-multi {
    margin-top: 2px;
  }

  .pnw-detail-page .keterangan-box {
    font-size: .78rem;
    color: #8a938d;
    font-style: italic;
    margin-top: 4px;
  }

  .pnw-detail-page .summary-card {
    padding: 18px 20px;
    max-width: 360px;
    margin-left: auto;
  }

  .pnw-detail-page .summary-row {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    font-size: .9rem;
  }

  .pnw-detail-page .summary-row.total {
    border-top: 2px solid var(--brand-light);
    margin-top: 6px;
    padding-top: 12px;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--brand-dark);
  }
</style>

<?php
$subtotal = isset($header->subtotal) ? (float) $header->subtotal : 0;
$ppn      = isset($header->ppn_nominal) ? (float) $header->ppn_nominal : 0;
$pph      = isset($header->pph_nominal) ? (float) $header->pph_nominal : 0;
$grand    = isset($header->grand_total) ? (float) $header->grand_total : 0;
// Luas selalu ditampilkan 2 angka di belakang koma (dibulatkan). Contoh: 1.649 -> 1.65, 10 -> 10.00
$fmt_luas = function ($v) {
  return number_format(round((float) $v, 2), 2, '.', '');
};
$rp = function ($v) {
  return 'Rp ' . number_format((float) $v, 0, ',', '.');
};
$approved = (($header->status ?? 'draft') === 'approved');
$batal    = (($header->status ?? 'draft') === 'batal');
$is_manual = (($header->tipe ?? 'rumus') === 'manual');
$is_konsinyasi = (($header->jenis_penjualan ?? 'biasa') === 'konsinyasi');
$masa_konsinyasi = in_array((int) ($header->masa_konsinyasi ?? 0), [30, 60], true) ? (int) $header->masa_konsinyasi : 60;
?>

<div class="pnw-detail-page">

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i><?= html_escape($this->session->flashdata('success')); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i><?= html_escape($this->session->flashdata('error')); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endif; ?>

  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
      <h5 class="mb-0">
        <?= html_escape($header->no_surat); ?>
        <?php if ($is_manual): ?><span class="badge bg-info text-dark ms-2 align-middle">Manual</span><?php endif; ?>
        <?php if ($is_konsinyasi): ?><span class="badge text-white ms-2 align-middle" style="background:#6f42c1">Konsinyasi / Sample &middot; <?= $masa_konsinyasi; ?> hari</span><?php endif; ?>
        <?php if ($batal): ?>
          <span class="badge bg-danger ms-2 align-middle"><i class="bi bi-x-circle me-1"></i>Batal</span>
        <?php elseif ($approved): ?>
          <span class="badge bg-success ms-2 align-middle"><i class="bi bi-lock-fill me-1"></i><?= translate('setuju') ?></span>
        <?php else: ?>
          <span class="badge bg-warning text-dark ms-2 align-middle"><?= translate('b_setuju') ?></span>
        <?php endif; ?>
      </h5>
      <div class="text-muted small"><?= translate('detail_penawaran') ?></div>
      <?php if ($is_konsinyasi): ?>
        <div class="small mt-1" style="color:#6f42c1">
          <i class="bi bi-box-arrow-right me-1"></i>
          Barang dikirim sebagai titipan / sample. Customer hanya membayar barang yang laku, barang yang tidak laku diambil kembali. Masa konsinyasi <strong><?= $masa_konsinyasi; ?> hari</strong> sejak tanggal surat jalan.
        </div>
      <?php endif; ?>
      <?php if ($approved): ?>
        <div class="text-muted status-lock mt-1">
          <i class="bi bi-info-circle me-1"></i>
          <?= translate('ket_approved') . (!empty($header->approved_at) ? ' ' . translate('pada') . ' ' . date('d M Y H:i', strtotime($header->approved_at)) : '') ?>, <?= translate('info_penawaran'); ?>
        </div>
      <?php endif; ?>
      <?php if ($batal): ?>
        <div class="text-danger status-lock mt-1">
          <i class="bi bi-x-circle me-1"></i>
          Penawaran dibatalkan<?= !empty($header->dibatalkan_at) ? ' pada ' . date('d M Y H:i', strtotime($header->dibatalkan_at)) : ''; ?><?= !empty($header->alasan_batal) ? ' &mdash; alasan: <strong>' . html_escape($header->alasan_batal) . '</strong>' : ''; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <?php if (!$approved && !$batal): ?>
        <a href="<?= site_url('penawaran/edit/' . (int) $header->id); ?>" class="btn btn-warning">
          <i class="bi bi-pencil-square me-1"></i> <?= translate('update') ?>
        </a>
        <?php if (in_array($this->session->userdata('role'), ['atasan', 'super_admin'], true)): ?>
        <?= form_open('penawaran/approve/' . (int) $header->id, [
          'class'    => 'd-inline',
          'onsubmit' => "return confirm('Approve penawaran ini? Setelah di-approve, data tidak bisa diedit lagi.');"
        ]); ?>
        <button type="submit" class="btn btn-success">
          <i class="bi bi-check2-circle me-1"></i> <?= translate('setuju') ?>
        </button>
        <?= form_close(); ?>
        <?php else: ?>
        <span class="btn btn-outline-secondary disabled"><i class="bi bi-hourglass-split me-1"></i> Menunggu approval atasan</span>
        <?php endif; ?>
      <?php endif; ?>
      <?php if (!empty($boleh_batal)): ?>
        <?php if ($batal): ?>
          <?= form_open('penawaran/aktifkan/' . (int) $header->id, [
            'class'    => 'd-inline',
            'onsubmit' => "return confirm('Aktifkan kembali penawaran ini? Status kembali ke draft dan perlu di-approve ulang.');"
          ]); ?>
          <button type="submit" class="btn btn-outline-success">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Aktifkan kembali
          </button>
          <?= form_close(); ?>
        <?php else: ?>
          <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalBatalPenawaran">
            <i class="bi bi-x-circle me-1"></i> Batalkan
          </button>
        <?php endif; ?>
      <?php endif; ?>
      <?php if (!empty($boleh_hapus)): ?>
        <?php
          $pesan_hapus = $approved
            ? 'Penawaran ' . $header->no_surat . ' SUDAH DI-APPROVE. Yakin hapus (mis. customer batal order)? Data yang dihapus tidak bisa dikembalikan.'
            : 'Hapus penawaran ' . $header->no_surat . '? Data yang dihapus tidak bisa dikembalikan.';
        ?>
        <?= form_open('penawaran/delete/' . (int) $header->id, [
          'class'    => 'd-inline',
          'onsubmit' => 'return confirm(' . html_escape(json_encode($pesan_hapus)) . ');'
        ]); ?>
        <button type="submit" class="btn btn-outline-danger">
          <i class="bi bi-trash me-1"></i> <?= translate('hapus') ?>
        </button>
        <?= form_close(); ?>
      <?php endif; ?>
      <div class="btn-group">
        <button type="button" class="btn btn-brand dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-printer me-1"></i> <?= translate('cetak') ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <?php if ($is_konsinyasi): ?>
          <li>
            <a class="dropdown-item" target="_blank" href="<?= site_url('penawaran/cetak/' . (int) $header->id); ?>">
              Penawaran konsinyasi (S&amp;K <?= $masa_konsinyasi; ?> hari)
            </a>
          </li>
          <?php else: ?>
          <li>
            <h6 class="dropdown-header"><?= translate('termin_pembayran') ?>(S&amp;K)</h6>
          </li>
          <li>
            <a class="dropdown-item" target="_blank" href="<?= site_url('penawaran/cetak/' . (int) $header->id . '?termin=50_50'); ?>">
             <?= translate('lbl_50%') ?>
            </a>
          </li>
          <li>
            <a class="dropdown-item" target="_blank" href="<?= site_url('penawaran/cetak/' . (int) $header->id . '?termin=100'); ?>">
             <?= translate('lbl_100%') ?>
            </a>
          </li>
          <?php endif; ?>
        </ul>
      </div>
      <a href="<?= site_url('penawaran'); ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i><?= translate('kembali') ?>
      </a>
    </div>
  </div>

  <!-- Info Header -->
  <div class="info-card">
    <div class="row">
      <div class="col-md-3">
        <div class="info-label"><?= translate('no_penawaran') ?></div>
        <div class="info-value"><?= html_escape($header->no_surat); ?></div>
      </div>
      <div class="col-md-3">
        <div class="info-label"><?= translate('tanggal') ?></div>
        <div class="info-value"><?= date('d M Y', strtotime($header->tanggal)); ?></div>
      </div>
      <div class="col-md-3">
        <div class="info-label"><?= translate('pelanggan') ?></div>
        <div class="info-value"><?= html_escape($header->customer ?? '-'); ?></div>
      </div>
      <div class="col-md-3">
        <div class="info-label"><?= translate('lbl_dbuat') ?></div>
        <div class="info-value"><?= html_escape($header->dibuat_oleh ?? '-'); ?></div>
      </div>
    </div>
    <?php if (!empty($header->catatan)): ?>
      <div class="mt-2">
        <div class="info-label"><?= translate('catatan') ?></div>
        <div class="text-muted small"><?= nl2br(html_escape($header->catatan)); ?></div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Tabel Item -->
  <div class="table-wrap">
    <div class="table-responsive">
      <table class="table mb-0">
        <thead>
          <tr>
            <th style="width:3%"><?= translate('no') ?></th>
            <th style="width:32%"><?= translate('lbl_deskripsi') ?></th>
            <th style="width:12%"><?= translate('ukuran') ?> (mm)</th>
            <th style="width:8%" class="text-center"><?= translate('luas') ?> m²</th>
            <th style="width:6%" class="text-center"><?= translate('qty') ?></th>
            <th style="width:12%" class="text-end"><?= translate('harga') ?></th>
            <th style="width:12%" class="text-end"><?= translate('subtotal') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($items)): $no = 1; ?>
            <?php foreach ($items as $it): ?>
              <tr>
                <td><?= $no++; ?></td>
                <td>
                  <?php if (!empty($it->foto)): ?>
                    <div class="mb-1">
                      <a href="<?= base_url('uploads/penawaran/' . $it->foto); ?>" target="_blank">
                        <img src="<?= base_url('uploads/penawaran/' . $it->foto); ?>" alt="" class="img-thumbnail" style="max-height:80px">
                      </a>
                    </div>
                  <?php endif; ?>
                  <?php if (trim((string) $it->jenis_item) !== ''): ?>
                    <strong><?= html_escape($it->jenis_item); ?></strong>
                  <?php else: ?>
                    <strong class="text-muted">Aksesoris</strong>
                  <?php endif; ?>
                  <?php if (!empty($it->warna)): ?>
                    <span class="spek-line">Warna: <?= html_escape($it->warna); ?></span>
                  <?php endif; ?>
                  <?php if (!empty($it->komponen)): ?>
                    <span class="spek-line">Komponen: <?= html_escape($it->komponen); ?></span>
                  <?php endif; ?>
                  <?php if (!empty($it->books_penutup)): ?>
                    <span class="spek-line spek-multi">Books Penutup: <?= nl2br(html_escape($it->books_penutup)); ?></span>
                  <?php endif; ?>
                  <?php if ((float) ($it->luas_m2_books ?? 0) > 0 && (int) ($it->qty_books ?? 0) > 0): ?>
                    <span class="spek-line">
                      Ukuran Books: <?= (int) round((float) $it->lebar_m2_books); ?> &times; <?= (int) round((float) $it->tinggi_m2_books); ?> mm
                      = <?= $fmt_luas($it->luas_m2_books); ?> m² &times; <?= (int) $it->qty_books; ?> &times; <?= $rp($it->harga_m2_books); ?>
                      = <?= $rp((float) $it->luas_m2_books * (int) $it->qty_books * (float) $it->harga_m2_books); ?>
                    </span>
                  <?php endif; ?>
                  <?php if ((float) ($it->qty_instalasi ?? 0) > 0 || (float) ($it->harga_m2_instalasi ?? 0) > 0): ?>
                    <?php $i_luas = (float) ($it->luas_billing_m2 ?? $it->luas_m2 ?? 0); ?>
                    <span class="spek-line">
                      Biaya Instalasi: <?= $fmt_luas($i_luas); ?> m² &times; <?= (int) $it->qty_instalasi; ?> &times; <?= $rp($it->harga_m2_instalasi); ?>
                      = <?= $rp($i_luas * (float) $it->qty_instalasi * (float) $it->harga_m2_instalasi); ?>
                    </span>
                  <?php endif; ?>
                  <?php if (!empty($it->kelengkapan)): ?>
                    <span class="spek-line spek-multi">Kelengkapan: <?= nl2br(html_escape($it->kelengkapan)); ?></span>
                  <?php endif; ?>
                  <?php if (!empty($it->finishing)): ?>
                    <span class="spek-line">Finishing: <?= html_escape($it->finishing); ?></span>
                  <?php endif; ?>
                  <?php if (!empty($it->komponen_kusen) || !empty($it->komponen_daun)): ?>
                    <span class="spek-line">
                      Plate Kusen/Daun: <?= html_escape($it->komponen_kusen ?: '-'); ?> / <?= html_escape($it->komponen_daun ?: '-'); ?> mm
                    </span>
                  <?php endif; ?>
                  <?php if (!empty($it->tebal)): ?>
                    <span class="spek-line">Tebal: <?= html_escape($it->tebal); ?> mm</span>
                  <?php endif; ?>
                  <?php if (!empty($it->aksesoris)): ?>
                    <span class="spek-line spek-multi">Aksesoris:</span>
                    <?php foreach ($it->aksesoris as $ak): ?>
                      <span class="spek-line">
                        &bull; <?= html_escape($ak->nama); ?> (<?= (int) $ak->qty; ?> &times; <?= $rp($ak->harga); ?> = <?= $rp(isset($ak->total_harga) ? $ak->total_harga : (int) $ak->qty * (float) $ak->harga); ?>)
                        <?php if (!empty($ak->foto)): ?>
                          <a href="<?= base_url('uploads/penawaran/' . rawurlencode($ak->foto)); ?>" target="_blank">
                            <img src="<?= base_url('uploads/penawaran/' . rawurlencode($ak->foto)); ?>" alt="" class="img-thumbnail ms-1 align-middle" style="max-height:48px">
                          </a>
                        <?php endif; ?>
                      </span>
                    <?php endforeach; ?>
                  <?php endif; ?>
                  <?php if (!empty($it->keterangan)): ?>
                    <div class="keterangan-box"><?= nl2br(html_escape($it->keterangan)); ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($is_manual && (int) $it->lebar_mm <= 0 && (int) $it->tinggi_mm <= 0): ?>
                    <?= html_escape(($it->ukuran_teks ?? '') !== '' ? $it->ukuran_teks : '-'); ?>
                  <?php else: ?>
                    <?= (int) $it->lebar_mm . ' x ' . (int) $it->tinggi_mm; ?>
                  <?php endif; ?>
                </td>
                <td class="text-center"><?= ($is_manual && (float) ($it->luas_m2 ?? 0) <= 0) ? '-' : $fmt_luas($it->luas_billing_m2 ?? $it->luas_m2); ?></td>
                <td class="text-center"><?= ((int) $it->qty > 0) ? (int) $it->qty : '-'; ?></td>
                <td class="text-end">
                  <?= $rp($it->harga_unit); ?>
                  <?php if ($is_manual && (float) $it->harga_unit > 0): ?>
                    <span class="spek-line"><?= (($it->satuan_harga ?? 'm2') === 'unit') ? '/ unit' : '/ m²'; ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-end"><?= $rp($it->total_harga); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4"><?= translate('info_detail_penawaran') ?></td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Ringkasan Nilai -->
  <div class="summary-card">
    <div class="summary-row">
      <span><?= translate('subtotal') ?></span>
      <span><?= $rp($subtotal); ?></span>
    </div>
    <?php if ((float) ($header->biaya_pasang ?? 0) > 0): ?>
      <div class="summary-row">
        <span><?= translate('rp_pasang') ?></span>
        <span><?= $rp($header->biaya_pasang); ?></span>
      </div>
    <?php endif; ?>
    <?php if ((float) ($header->biaya_ongkir ?? 0) > 0): ?>
      <div class="summary-row">
        <span><?= translate('rp_pengiriman') ?></span>
        <span><?= $rp($header->biaya_ongkir); ?></span>
      </div>
    <?php endif; ?>
    <div class="summary-row">
      <span><?= translate('ppn') ?> <?= isset($header->ppn_persen) ? rtrim(rtrim(number_format($header->ppn_persen, 2), '0'), '.') : '11'; ?>%</span>
      <span><?= $rp($ppn); ?></span>
    </div>
    <?php if ($pph > 0): ?>
      <div class="summary-row">
        <span><?= translate('pph') ?> <?= rtrim(rtrim(number_format($header->pph_persen, 2), '0'), '.'); ?>% (dipotong customer)<?= !empty($header->pph_ongkir) ? ' <small class="text-muted">· termasuk ongkir</small>' : ''; ?></span>
        <span>- <?= $rp($pph); ?></span>
      </div>
    <?php endif; ?>
    <div class="summary-row total">
      <span><?= translate('grand_total') ?></span>
      <span><?= $rp($grand); ?></span>
    </div>
  </div>

  <?php if (!empty($boleh_batal) && !$batal): ?>
    <!-- Modal: batalkan penawaran (customer tidak jadi deal) -->
    <div class="modal fade" id="modalBatalPenawaran" tabindex="-1" aria-labelledby="modalBatalPenawaranLabel" aria-hidden="true">
      <div class="modal-dialog">
        <?= form_open('penawaran/batal/' . (int) $header->id, ['class' => 'modal-content']); ?>
          <div class="modal-header">
            <h5 class="modal-title" id="modalBatalPenawaranLabel">Batalkan penawaran <?= html_escape($header->no_surat); ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <p class="small text-muted mb-2">
              Penawaran akan ditandai <strong>Batal</strong> (tidak dihapus), sehingga tetap tersimpan sebagai riwayat.
              Kalau nanti customer jadi deal, penawaran bisa diaktifkan kembali.
            </p>
            <label for="alasan_batal" class="form-label">Alasan batal <span class="text-danger">*</span></label>
            <textarea name="alasan_batal" id="alasan_batal" class="form-control" rows="3" maxlength="255" required
              placeholder="Contoh: harga tidak deal, customer pilih vendor lain, proyek ditunda"></textarea>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle me-1"></i> Batalkan penawaran</button>
          </div>
        <?= form_close(); ?>
      </div>
    </div>
  <?php endif; ?>

</div>