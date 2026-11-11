<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');

$izin    = isset($izin) ? $izin : array();
$kembali = 'pemenuhan/po';
$tombol_po = function ($id, $aksi, $label, $kelas, $tanya) use ($kembali) {
  return form_open('pemenuhan/po_aksi/' . (int) $id . '/' . $aksi, array('class' => 'd-inline', 'onsubmit' => "return confirm('" . $tanya . "')"))
    . form_hidden('kembali', $kembali)
    . '<button type="submit" class="btn btn-sm ' . $kelas . ' py-0 px-2">' . $label . '</button>'
    . form_close();
};
// jumlah per tab filter
$hitung = function ($kode) use ($filter_list, $jumlah) {
  if ($kode === 'semua') { return array_sum($jumlah); }
  $n = 0;
  foreach ($filter_list[$kode]['status'] as $st) { $n += (int) ($jumlah[$st] ?? 0); }
  return $n;
};
$url_filter = function ($kode) use ($q) {
  $p = array();
  if ($kode !== 'semua') { $p['status'] = $kode; }
  if ($q !== '') { $p['q'] = $q; }
  return site_url('pemenuhan/po') . ($p ? '?' . http_build_query($p) : '');
};
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <div class="judul"><i class="bi bi-cart me-2"></i>Purchase Order</div>
      <div class="meta">Pesanan ke supplier: approval atasan, pengiriman, penerimaan &amp; pembayaran</div>
    </div>
    <?php if (!empty($izin['atur'])): ?>
      <a href="<?= site_url('pemenuhan') ?>" class="btn btn-light btn-sm"><i class="bi bi-plus-lg me-1"></i>Buat PO dari Proses Pesanan</a>
    <?php endif; ?>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <ul class="nav nav-pills nav-sm flex-wrap gap-1">
        <?php foreach ($filter_list as $kode => $fl): $n = $hitung($kode); ?>
          <li class="nav-item">
            <a class="nav-link py-1 px-2 <?= $filter === $kode ? 'active' : '' ?>" href="<?= $url_filter($kode) ?>">
              <?= html_escape($fl['label']) ?>
              <span class="badge <?= ($kode === 'draft' && $n > 0) ? 'text-bg-danger' : 'text-bg-light' ?>"><?= $n ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
      <form method="get" action="<?= site_url('pemenuhan/po') ?>" class="d-flex gap-1">
        <?php if ($filter !== 'semua'): ?><input type="hidden" name="status" value="<?= html_escape($filter) ?>"><?php endif; ?>
        <input type="text" name="q" value="<?= html_escape($q) ?>" class="form-control form-control-sm" placeholder="Cari no PO / supplier / SO / customer" style="min-width:240px">
        <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
        <?php if ($q !== ''): ?><a href="<?= site_url('pemenuhan/po') . ($filter !== 'semua' ? '?status=' . rawurlencode($filter) : '') ?>" class="btn btn-sm btn-link">Reset</a><?php endif; ?>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-light">
          <tr><th>No. PO</th><th>Supplier</th><th>SO / Customer</th><th>Jenis</th><th class="text-end">Total</th><th>Status</th><th>Pembayaran</th><th class="text-end">Aksi</th></tr>
        </thead>
        <tbody>
          <?php if (!$po_list): ?>
            <tr><td colspan="8" class="text-center text-muted py-4"><?= $q !== '' ? 'Tidak ada PO yang cocok dengan pencarian.' : 'Tidak ada PO di status ini.' ?></td></tr>
          <?php endif; ?>
          <?php foreach ($po_list as $p): $dibayar = (float) ($p->total_bayar ?? 0); ?>
            <tr>
              <td class="fw-semibold">
                <a href="<?= site_url('pemenuhan/po_detail/' . (int) $p->id) ?>"><?= html_escape($p->no_po) ?></a>
                <div class="muted"><?= $tgl($p->tanggal) ?><?= $p->nama_pembuat ? ' &middot; ' . html_escape($p->nama_pembuat) : '' ?></div>
              </td>
              <td><?= html_escape($p->nama_supplier) ?></td>
              <td><?= html_escape($p->no_so) ?><div class="muted"><?= html_escape($p->nama_customer) ?></div></td>
              <td><?= $p->jenis === 'dropship' ? '<span class="badge text-bg-info fw-normal">Dropship</span>' : '<span class="badge text-bg-light fw-normal">Ke gudang</span>' ?></td>
              <td class="text-end"><?= $rp($p->total) ?></td>
              <td>
                <?= $badge('po', $p->status) ?>
                <?php if ($p->status === 'batal' && !empty($p->alasan_batal)): ?><div class="muted"><?= html_escape($p->alasan_batal) ?></div><?php endif; ?>
              </td>
              <td>
                <?php if ($p->status !== 'batal'): ?>
                  <?= $badge('bayar', $p->status_bayar ?? 'belum') ?>
                  <?php if ($dibayar > 0): ?><div class="muted"><?= $rp($dibayar) ?></div><?php endif; ?>
                <?php else: ?>-<?php endif; ?>
              </td>
              <td class="text-end text-nowrap">
                <?php if (!empty($izin['approve']) && $p->status === 'draft'): ?>
                  <?= $tombol_po($p->id, 'setujui', 'Setujui', 'btn-outline-success', 'Setujui PO ' . $p->no_po . '?') ?>
                  <a href="<?= site_url('pemenuhan/po_detail/' . (int) $p->id) ?>#tolak" class="btn btn-sm btn-outline-danger py-0 px-2">Tolak</a>
                <?php endif; ?>
                <?php if (!empty($izin['atur']) && $p->status === 'approved'): ?>
                  <?= $tombol_po($p->id, 'kirim', 'Kirim ke supplier', 'btn-outline-primary', 'PO sudah dikirim / dipesan ke supplier?') ?>
                <?php endif; ?>
                <?php if (!empty($izin['terima']) && in_array($p->status, array('dipesan', 'sebagian'), true)): ?>
                  <a href="<?= site_url('pemenuhan/po_terima/' . (int) $p->id) ?>" class="btn btn-sm btn-outline-success py-0 px-2"><?= $p->jenis === 'dropship' ? 'Diterima customer' : 'Terima' ?></a>
                <?php endif; ?>
                <?php if (!empty($izin['bayar']) && $p->status !== 'draft' && $p->status !== 'batal' && ($p->status_bayar ?? 'belum') !== 'lunas'): ?>
                  <a href="<?= site_url('pemenuhan/po_detail/' . (int) $p->id) ?>#pembayaran" class="btn btn-sm btn-outline-success py-0 px-2">Bayar</a>
                <?php endif; ?>
                <a href="<?= site_url('pemenuhan/po_detail/' . (int) $p->id) ?>" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Detail"><i class="bi bi-eye"></i></a>
                <a href="<?= site_url('pemenuhan/po_cetak/' . (int) $p->id) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Cetak PO"><i class="bi bi-printer"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (count($po_list) >= 300): ?>
      <div class="card-body border-top muted">Menampilkan 300 PO terbaru. Gunakan filter atau pencarian untuk PO yang lebih lama.</div>
    <?php endif; ?>
  </div>
</div>
