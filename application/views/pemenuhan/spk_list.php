<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3">
    <div class="judul"><i class="bi bi-hammer me-2"></i>SPK Pesanan</div>
    <div class="meta">Surat perintah kerja produksi yang dibuat dari Proses Pesanan</div>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <div class="card">
    <div class="card-header"><i class="bi bi-list-ul me-2"></i>Daftar SPK</div>
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>No. SPK</th><th>SO / Customer</th><th>Tanggal</th><th>Target</th><th class="text-end">Qty</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php if (!$spk_list): ?><tr><td colspan="7" class="text-center text-muted py-4">Belum ada SPK.</td></tr><?php endif; ?>
          <?php foreach ($spk_list as $s):
            $telat = $s->target_selesai && in_array($s->status, array('antri', 'proses'), true) && strtotime($s->target_selesai) < strtotime(date('Y-m-d'));
          ?>
            <tr>
              <td class="fw-semibold"><?= html_escape($s->no_spk) ?></td>
              <td><?= html_escape($s->no_so) ?><div class="muted"><?= html_escape($s->nama_customer) ?></div></td>
              <td><?= $tgl($s->tanggal) ?></td>
              <td class="<?= $telat ? 'text-danger fw-semibold' : '' ?>"><?= $tgl($s->target_selesai) ?><?= $telat ? ' <i class="bi bi-exclamation-circle" title="Lewat target"></i>' : '' ?></td>
              <td class="text-end"><?= $num($s->total_qty) ?></td>
              <td><?= $badge('spk', $s->status) ?></td>
              <td class="text-end text-nowrap">
                <a href="<?= site_url('pemenuhan/so/' . (int) $s->id_so) ?>" class="btn btn-sm btn-outline-success py-0">Buka</a>
                <a href="<?= site_url('pemenuhan/spk_cetak/' . (int) $s->id) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0" title="Cetak SPK"><i class="bi bi-printer"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (count($spk_list) >= 300): ?><div class="card-body border-top muted">Menampilkan 300 SPK terbaru.</div><?php endif; ?>
  </div>
</div>
