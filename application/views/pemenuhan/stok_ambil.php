<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');

$opsi_label = array('bukaan_kanan' => translate('i18n_bukaan_kanan'), 'bukaan_kiri' => translate('i18n_bukaan_kiri'), 'aksesoris' => translate('aksesoris'), 'bahan' => translate('bahan'));
$opsi = function ($o) use ($opsi_label) { return $opsi_label[$o] ?? ($o !== '' ? ucfirst(str_replace('_', ' ', $o)) : '-'); };
$lokasi = function ($r) { return trim(($r->kode_lokasi ?? '') . ' ' . ($r->nama_lokasi ? '(' . $r->nama_lokasi . ')' : '')) ?: '-'; };
list($uk_l, $uk_t) = $this->Pemenuhan_model->ukuran($it);
$sisa = (float) $it->p_sisa_proses;
$url  = site_url('pemenuhan/stok_ambil/' . (int) $it->id);
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
      <div class="judul"><i class="bi bi-archive me-2"></i><?= translate('i18n_ambil_dari_stok') ?></div>
      <div class="meta"><?= html_escape($h->no_so) ?> &middot; <?= html_escape($h->nama_customer) ?></div>
    </div>
    <a href="<?= site_url('pemenuhan/so/' . (int) $h->id) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i><?= translate('i18n_kembali_ke_so') ?></a>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <!-- Item yang dipesan customer -->
  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-box me-2"></i><?= translate('i18n_item_pesanan') ?></div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-5"><div class="muted"><?= translate('i18n_barang') ?></div><div class="fw-semibold"><?= html_escape($this->Pemenuhan_model->nama_item_polos($it)) ?></div>
          <?php if (trim((string) ($it->finishing ?? '')) !== ''): ?><div class="muted"><?= html_escape($it->finishing) ?></div><?php endif; ?></div>
        <div class="col-md-3"><div class="muted"><?= translate('i18n_ukuran_pesanan_l_t') ?></div><div class="fw-semibold"><?= $uk_l > 0 ? $num($uk_l) . ' &times; ' . $num($uk_t) . ' mm' : '-' ?></div></div>
        <div class="col-md-2"><div class="muted"><?= translate('i18n_qty_pesanan') ?></div><div class="fw-semibold"><?= $num($it->qty) ?></div></div>
        <div class="col-md-2"><div class="muted"><?= translate('i18n_belum_dipenuhi') ?></div><div class="fw-semibold <?= $sisa > 0 ? 'text-danger' : 'text-success' ?>"><?= $num($sisa) ?></div></div>
      </div>
      <div class="note mt-3"><?= translate('i18n_cocokkan_sendiri') ?> <b><?= translate('i18n_ukuran') ?></b> <?= translate('i18n_dan') ?> <b><?= translate('i18n_bukaan') ?></b> <?= translate('i18n_stok_dengan_pesanan_sebelum_memilih_stok') ?> <b><?= translate('i18n_berkurang_dari_gudang_saat_surat_jalan') ?></b>.</div>
    </div>
  </div>

  <!-- Stok yang sudah dipilih -->
  <?php if ($alokasi): ?>
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-check2-square me-2"></i><?= translate('i18n_stok_yang_sudah_dipilih_untuk_item_ini') ?></div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead class="table-light"><tr><th><?= translate('i18n_barang') ?></th><th><?= translate('i18n_bukaan_opsi') ?></th><th><?= translate('ukuran') ?></th><th><?= translate('i18n_lokasi') ?></th><th class="text-end"><?= translate('i18n_qty_2') ?></th><th class="text-end"><?= translate('i18n_sudah_dikirim') ?></th><th><?= translate('i18n_dipilih_oleh') ?></th><th></th></tr></thead>
          <tbody>
            <?php foreach ($alokasi as $a): ?>
              <tr>
                <td><?= html_escape($a->nama_barang ?: '-') ?><div class="muted"><?= html_escape($a->kode_barang) ?></div></td>
                <td><?= html_escape($opsi($a->opsi)) ?></td>
                <td><?= html_escape($a->ukuran !== '' ? $a->ukuran : '-') ?></td>
                <td><?= html_escape($lokasi($a)) ?></td>
                <td class="text-end"><?= $num($a->qty) ?></td>
                <td class="text-end"><?= $num($a->qty_keluar) ?></td>
                <td><?= html_escape($a->nama_user ?: '-') ?><div class="muted"><?= $tgl($a->created_at) ?></div></td>
                <td class="text-end">
                  <?php if ($bisa_proses && (float) $a->qty_keluar <= 0): ?>
                    <?= form_open('pemenuhan/stok_lepas/' . (int) $a->id, array('class' => 'd-inline', 'onsubmit' => "return confirm('" . tj('i18n_lepas_stok_ini_stok_kembali_tersedia') . "')")) ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2"><?= translate('i18n_lepas') ?></button>
                    <?= form_close() ?>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <!-- Pilih stok -->
  <?php if ($bisa_proses && $sisa > 0): ?>
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-archive me-2"></i><?= translate('i18n_stok_tersedia_di_gudang') ?>
          <?php if ($filter_brg): ?><span class="badge text-bg-light fw-normal"><?= translate('i18n_barang_sesuai_pesanan') ?></span><?php endif; ?>
        </span>
        <form method="get" action="<?= $url ?>" class="d-flex gap-1 align-items-center">
          <?php if ($semua): ?><input type="hidden" name="semua" value="1"><?php endif; ?>
          <input type="text" name="q" value="<?= html_escape($cari) ?>" class="form-control form-control-sm" placeholder="<?= translate('i18n_cari_barang_ukuran_lokasi') ?>" style="min-width:220px">
          <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
          <?php if ($ada_brg): ?>
            <a href="<?= $url . ($semua ? '' : '?semua=1') ?>" class="btn btn-sm btn-link text-nowrap"><?= $semua ? translate('i18n_hanya_barang_ini') : translate('i18n_tampilkan_semua_barang') ?></a>
          <?php endif; ?>
        </form>
      </div>

      <?= form_open('pemenuhan/stok_ambil/' . (int) $it->id, array('id' => 'form_stok', 'onsubmit' => "return confirm('" . tj('i18n_pakai_stok_yang_dipilih_untuk_item_ini') . "')")) ?>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr><th><?= translate('i18n_barang') ?></th><th><?= translate('i18n_bukaan_opsi') ?></th><th><?= translate('ukuran') ?></th><th><?= translate('i18n_lokasi') ?></th><th class="text-end"><?= translate('i18n_stok_fisik') ?></th><th class="text-end"><?= translate('i18n_dipesan_so_lain') ?></th><th class="text-end"><?= translate('i18n_tersedia') ?></th><th style="width:110px"><?= translate('i18n_ambil') ?></th></tr>
          </thead>
          <tbody>
            <?php $ada = false; foreach ($stok_list as $i => $r): if ($r->tersedia <= 0) { continue; } $ada = true; ?>
              <tr>
                <td class="fw-semibold"><?= html_escape($r->nama_barang ?: '-') ?><div class="muted"><?= html_escape(trim($r->kode_barang . ' ' . ($r->warna ? '· ' . $r->warna : ''))) ?></div></td>
                <td><?= html_escape($opsi($r->opsi)) ?></td>
                <td><?= html_escape($r->ukuran !== '' ? $r->ukuran : '-') ?></td>
                <td><?= html_escape($lokasi($r)) ?></td>
                <td class="text-end"><?= $num($r->stok) ?></td>
                <td class="text-end"><?= $r->dipesan > 0 ? $num($r->dipesan) : '-' ?></td>
                <td class="text-end fw-semibold text-success"><?= $num($r->tersedia) ?></td>
                <td>
                  <input type="hidden" name="kunci[<?= (int) $i ?>]" value="<?= html_escape($r->kunci) ?>">
                  <input type="number" name="qty[<?= (int) $i ?>]" class="form-control form-control-sm text-end in-ambil" min="0" step="1"
                         max="<?= (int) floor(min($r->tersedia, $sisa)) ?>" placeholder="0">
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$ada): ?>
              <tr><td colspan="8" class="text-center text-muted py-4">
                <?= translate('i18n_tidak_ada_stok_tersedia') ?><?= $cari !== '' ? ' untuk pencarian ini' : '' ?>.
                <?php if ($filter_brg): ?><a href="<?= $url ?>?semua=1"><?= translate('i18n_tampilkan_semua_barang') ?></a><?= translate('i18n_atau') ?><?php endif; ?>
                <?= translate('i18n_ubah_sumber_item_ke_produksi_beli') ?>
              </td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <?php if ($ada): ?>
        <div class="card-body border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div><?= translate('i18n_dipilih') ?> <b id="out_ambil">0</b> <?= translate('i18n_dari') ?> <?= $num($sisa) ?> <?= translate('i18n_yang_belum_dipenuhi') ?></div>
          <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check2 me-1"></i><?= translate('i18n_pakai_stok_ini') ?></button>
        </div>
      <?php endif; ?>
      <?= form_close() ?>
    </div>
    <script>
      (function () {
        var f = document.getElementById('form_stok'), out = document.getElementById('out_ambil'), sisa = <?= json_encode($sisa) ?>;
        if (!f || !out) { return; }
        f.addEventListener('input', function () {
          var t = 0;
          f.querySelectorAll('.in-ambil').forEach(function (el) { t += Number(el.value) || 0; });
          out.textContent = t;
          out.className = t > sisa ? 'text-danger' : '';
        });
      })();
    </script>
  <?php elseif ($sisa <= 0): ?>
    <div class="alert alert-success"><?= translate('i18n_qty_item_ini_sudah_terpenuhi_dari_stok') ?></div>
  <?php endif; ?>
</div>
