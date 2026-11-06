<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');

$h        = $po['header'];
$dropship = $h->jenis === 'dropship';
$izin     = isset($izin) ? $izin : array();
$kembali  = 'pemenuhan/po_detail/' . (int) $h->id;

$total    = (float) $h->total;
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
  return form_open('pemenuhan/po_aksi/' . (int) $h->id . '/' . $aksi, array('class' => 'd-inline', 'onsubmit' => "return confirm('" . $tanya . "')"))
    . form_hidden('kembali', $kembali)
    . '<button type="submit" class="btn btn-sm ' . $kelas . '">' . $label . '</button>'
    . form_close();
};
$fmt_luas = function ($v) {
  $s   = number_format((float) $v, 4, '.', '');
  $dec = max(2, strlen(rtrim(substr($s, strpos($s, '.') + 1), '0')));
  return number_format((float) $v, $dec, ',', '.');
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
        <?= $dropship ? 'Dropship ke customer' : 'Dikirim ke gudang' ?> &middot;
        <?= html_escape($h->no_so) ?> (<?= html_escape($h->nama_customer) ?>)
      </div>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= site_url('pemenuhan/po_cetak/' . (int) $h->id) ?>" target="_blank" class="btn btn-light btn-sm"><i class="bi bi-printer me-1"></i>Cetak PO</a>
      <a href="<?= site_url('pemenuhan/so/' . (int) $h->id_so) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Proses SO</a>
    </div>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <!-- Status & aksi -->
  <div class="row g-3 mb-3">
    <div class="col-lg-7">
      <div class="card h-100">
        <div class="card-header"><i class="bi bi-flag me-2"></i>Status &amp; persetujuan</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-sm-4">
              <div class="muted">Status PO</div>
              <div><?= $badge('po', $h->status) ?></div>
            </div>
            <div class="col-sm-4">
              <div class="muted">Dibuat oleh</div>
              <div><?= html_escape($h->nama_pembuat ?: '-') ?></div>
              <div class="muted"><?= $tgl($h->tanggal) ?></div>
            </div>
            <div class="col-sm-4">
              <div class="muted"><?= ($h->status === 'batal' && $h->approved_by) ? 'Ditolak oleh' : 'Disetujui oleh' ?></div>
              <div><?= $h->approved_by ? html_escape($h->nama_approver ?: '-') : '<span class="muted">belum</span>' ?></div>
              <?php if ($h->approved_at): ?><div class="muted"><?= $tgljam($h->approved_at) ?></div><?php endif; ?>
            </div>
          </div>

          <?php if ($h->status === 'batal' && !empty($h->alasan_batal)): ?>
            <div class="alert alert-danger py-2 mt-3 mb-0"><?= html_escape($h->alasan_batal) ?></div>
          <?php elseif ($h->status === 'draft'): ?>
            <div class="note mt-3">PO menunggu persetujuan atasan. Setelah disetujui, purchasing bisa mengirim PO ke supplier dan finance bisa mencatat DP.</div>
          <?php endif; ?>

          <?php if ($bisa_appr || $bisa_kirm || $bisa_trm || $bisa_btl): ?>
            <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
              <?php if ($bisa_appr): ?>
                <?= $tombol_po('setujui', '<i class="bi bi-check2-circle me-1"></i>Setujui PO', 'btn-success', 'Setujui PO ini?') ?>
              <?php endif; ?>
              <?php if ($bisa_kirm): ?>
                <?= $tombol_po('kirim', '<i class="bi bi-send me-1"></i>Kirim ke supplier', 'btn-primary', 'PO sudah dikirim / dipesan ke supplier?') ?>
              <?php endif; ?>
              <?php if ($bisa_trm): ?>
                <a href="<?= site_url('pemenuhan/po_terima/' . (int) $h->id) ?>" class="btn btn-sm btn-outline-success">
                  <i class="bi bi-box-arrow-in-down me-1"></i><?= $dropship ? 'Konfirmasi diterima customer' : 'Terima barang' ?>
                </a>
              <?php endif; ?>
              <?php if ($bisa_btl): ?>
                <?= $tombol_po('batal', 'Batalkan PO', 'btn-outline-danger', 'Batalkan PO ini?') ?>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if ($bisa_appr): ?>
            <div id="tolak" class="mt-3">
              <?= form_open('pemenuhan/po_aksi/' . (int) $h->id . '/tolak', array('onsubmit' => "return confirm('Tolak PO ini? PO akan dibatalkan.')")) ?>
              <?= form_hidden('kembali', $kembali) ?>
              <div class="input-group input-group-sm">
                <input type="text" name="alasan" class="form-control" maxlength="200" placeholder="Alasan penolakan (wajib), mis. harga terlalu mahal, ganti supplier" required>
                <button type="submit" class="btn btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Tolak</button>
              </div>
              <?= form_close() ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card h-100">
        <div class="card-header"><i class="bi bi-info-circle me-2"></i>Info pengiriman</div>
        <div class="card-body">
          <div class="muted">Tanggal dibutuhkan</div>
          <div class="mb-2"><?= $tgl($h->tgl_butuh) ?></div>
          <div class="muted">Alamat kirim</div>
          <div class="mb-2" style="white-space:pre-line"><?= html_escape($h->alamat_kirim ?: '-') ?></div>
          <?php if (trim((string) $h->catatan) !== ''): ?>
            <div class="muted">Catatan</div>
            <div style="white-space:pre-line"><?= html_escape($h->catatan) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Item -->
  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-list-ul me-2"></i>Barang dipesan</div>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr><th>#</th><th>Barang</th><th class="text-end">Lebar (mm)</th><th class="text-end">Tinggi (mm)</th><th class="text-end">Qty</th><th class="text-end">Luas (m²)</th><th>Keterangan</th><th class="text-end">Harga</th><th class="text-end">Total Harga</th><th class="text-end">Diterima</th></tr>
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
              <td class="text-end"><?= $rp($d->harga) ?><div class="muted"><?= $per_m2($d) ? 'per m²' : 'per unit' ?></div></td>
              <td class="text-end"><?= $rp($d->total) ?></td>
              <td class="text-end"><?= $num($d->qty_diterima) ?> / <?= $num($d->qty) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="fw-semibold"><td colspan="4" class="text-end">Total</td><td class="text-end"><?= $num($tq) ?></td><td class="text-end"><?= $tl > 0 ? $fmt_luas($tl) : '-' ?></td><td colspan="2" class="text-end">Total PO</td><td class="text-end"><?= $rp($total) ?></td><td></td></tr>
        </tfoot>
      </table>
    </div>
    <?php if ($po['penerimaan']): ?>
      <div class="card-body border-top">
        <div class="muted mb-1">Riwayat penerimaan</div>
        <?php foreach ($po['penerimaan'] as $t): ?>
          <div class="small"><?= $tgl($t->tanggal) ?> &middot; SJ supplier: <?= html_escape($t->no_sj_supplier ?: '-') ?><?= trim((string) $t->catatan) !== '' ? ' &middot; ' . html_escape($t->catatan) : '' ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Pembayaran ke supplier -->
  <div class="card mb-3" id="pembayaran">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span><i class="bi bi-cash-coin me-2"></i>Pembayaran ke supplier</span>
      <?= $h->status !== 'batal' ? $badge('bayar', $h->status_bayar ?? 'belum') : '' ?>
    </div>
    <div class="card-body">
      <div class="row g-3 mb-2">
        <div class="col-sm-4"><div class="muted">Total PO</div><div class="fs-5 fw-semibold"><?= $rp($total) ?></div></div>
        <div class="col-sm-4"><div class="muted">Sudah dibayar</div><div class="fs-5 fw-semibold text-success"><?= $rp($dibayar) ?></div></div>
        <div class="col-sm-4"><div class="muted">Sisa hutang</div><div class="fs-5 fw-semibold <?= $sisa > 0 ? 'text-danger' : '' ?>"><?= $rp($sisa) ?></div></div>
      </div>
      <div class="mini-bar mb-3"><div style="width:<?= $pct ?>%"></div></div>

      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead class="table-light">
            <tr><th>Tanggal</th><th>Jenis</th><th class="text-end">Nominal</th><th>Metode / rekening</th><th>Keterangan</th><th>Dicatat</th><th class="text-end"></th></tr>
          </thead>
          <tbody>
            <?php if (!$po['pembayaran']): ?>
              <tr><td colspan="7" class="text-center text-muted py-3">Belum ada pembayaran.</td></tr>
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
                    <a href="<?= base_url($b->bukti) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Lihat bukti"><i class="bi bi-paperclip"></i></a>
                  <?php endif; ?>
                  <?php if ($bisa_hps): ?>
                    <?= form_open('pemenuhan/po_bayar_hapus/' . (int) $b->id, array('class' => 'd-inline', 'onsubmit' => "return confirm('Hapus pembayaran ini?')")) ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Hapus"><i class="bi bi-trash"></i></button>
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
        <div class="fw-semibold mb-2">Catat pembayaran</div>
        <?= form_open_multipart('pemenuhan/po_bayar/' . (int) $h->id, array('autocomplete' => 'off', 'onsubmit' => "return confirm('Simpan pembayaran ini?')")) ?>
        <div class="row g-2">
          <div class="col-md-2">
            <label class="form-label small">Tanggal</label>
            <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-md-2">
            <label class="form-label small">Jenis</label>
            <select name="jenis" id="in_jenis" class="form-select form-select-sm" required>
              <option value="dp" <?= $dibayar <= 0 ? 'selected' : '' ?>>DP</option>
              <option value="termin">Termin</option>
              <option value="pelunasan" <?= $dibayar > 0 ? 'selected' : '' ?>>Pelunasan</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small">Nominal (Rp)</label>
            <input type="number" name="nominal" id="in_nominal" class="form-control form-control-sm text-end" min="1" max="<?= $sisa ?>" step="1"
                   value="<?= $dibayar > 0 ? $sisa : '' ?>" required>
            <div class="muted">Sisa: <?= $rp($sisa) ?></div>
          </div>
          <div class="col-md-2">
            <label class="form-label small">Metode</label>
            <select name="metode" class="form-select form-select-sm">
              <option value="transfer">Transfer</option>
              <option value="tunai">Tunai</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small">Dari rekening</label>
            <input type="text" name="rekening_asal" class="form-control form-control-sm" list="list_rek" maxlength="100" placeholder="mis. Rekening PT">
            <datalist id="list_rek">
              <option value="Rekening PT"></option>
              <option value="Rekening Mahmudi"></option>
            </datalist>
          </div>
          <div class="col-md-5">
            <label class="form-label small">Bukti transfer (jpg/png/pdf, maks 2MB)</label>
            <input type="file" name="bukti" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf">
          </div>
          <div class="col-md-5">
            <label class="form-label small">Keterangan</label>
            <input type="text" name="keterangan" class="form-control form-control-sm" placeholder="mis. DP 50% sesuai kesepakatan">
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-success btn-sm w-100"><i class="bi bi-save me-1"></i>Simpan</button>
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
      <div class="card-body border-top"><div class="note">Pembayaran bisa dicatat setelah PO disetujui atasan.</div></div>
    <?php endif; ?>
  </div>

</div>
