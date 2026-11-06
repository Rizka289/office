<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3">
    <div class="judul"><i class="bi bi-box-seam me-2"></i>Surat Jalan</div>
    <div class="meta">Pengiriman barang ke customer, dari gudang maupun dropship supplier</div>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <?php if ($so_siap && !empty($izin['kirim'])): ?>
    <div class="card mb-3 border-start border-4 border-success">
      <div class="card-header"><i class="bi bi-box-seam me-2"></i>Barang siap dikirim dari gudang <span class="badge text-bg-success"><?= count($so_siap) ?> SO</span></div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light"><tr><th>No. SO</th><th>Customer</th><th>Target kirim</th><th class="text-end">Qty siap kirim</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($so_siap as $s): $telat = $s->tgl_kirim && strtotime($s->tgl_kirim) < strtotime(date('Y-m-d')); ?>
              <tr>
                <td class="fw-semibold"><?= html_escape($s->no_so) ?></td>
                <td><?= html_escape($s->nama_customer) ?></td>
                <td class="<?= $telat ? 'text-danger fw-semibold' : '' ?>"><?= $tgl($s->tgl_kirim) ?></td>
                <td class="text-end"><?= $num($s->qty_siap) ?></td>
                <td class="text-end"><a href="<?= site_url('pemenuhan/sj_buat/' . (int) $s->id) ?>" class="btn btn-sm btn-success py-0"><i class="bi bi-plus-lg me-1"></i>Buat surat jalan</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header"><i class="bi bi-list-ul me-2"></i>Daftar surat jalan</div>
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>No. SJ</th><th>SO / Customer</th><th>Tanggal</th><th>Dari</th><th class="text-end">Qty</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php if (!$sj_list): ?><tr><td colspan="7" class="text-center text-muted py-4">Belum ada surat jalan.</td></tr><?php endif; ?>
          <?php foreach ($sj_list as $s): ?>
            <tr>
              <td class="fw-semibold"><?= html_escape($s->no_sj) ?></td>
              <td><?= html_escape($s->no_so) ?><div class="muted"><?= html_escape($s->nama_customer) ?></div></td>
              <td><?= $tgl($s->tanggal) ?></td>
              <td><?= $s->jenis === 'dropship' ? 'Dropship: ' . html_escape($s->nama_supplier) : 'Gudang' ?></td>
              <td class="text-end"><?= $num($s->total_qty) ?></td>
              <td><?= $badge('sj', $s->status) ?></td>
              <td class="text-end text-nowrap">
                <a href="<?= site_url('pemenuhan/so/' . (int) $s->id_so) ?>" class="btn btn-sm btn-outline-success py-0">Buka SO</a>
                <a href="<?= site_url('pemenuhan/sj_cetak/' . (int) $s->id) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0" title="Cetak SJ"><i class="bi bi-printer"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (count($sj_list) >= 300): ?><div class="card-body border-top muted">Menampilkan 300 surat jalan terbaru.</div><?php endif; ?>
  </div>
</div>
