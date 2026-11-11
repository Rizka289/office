<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');
$hari_ini = strtotime(date('Y-m-d'));
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3">
    <div class="judul"><i class="bi bi-box-arrow-in-down me-2"></i>Penerimaan Barang</div>
    <div class="meta">PO yang sudah dipesan ke supplier dan barangnya sedang ditunggu</div>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <div class="card">
    <div class="card-header"><i class="bi bi-truck me-2"></i>Barang akan datang <span class="badge text-bg-secondary"><?= count($po_list) ?></span></div>
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-light">
          <tr><th>No. PO</th><th>Supplier</th><th>SO / Customer</th><th>Kirim ke</th><th>Dibutuhkan</th><th style="min-width:150px">Diterima</th><th>Status</th><th class="text-end"></th></tr>
        </thead>
        <tbody>
          <?php if (!$po_list): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada barang yang sedang ditunggu dari supplier.</td></tr>
          <?php endif; ?>
          <?php foreach ($po_list as $p):
            $pct   = (float) $p->total_qty > 0 ? min(100, round((float) $p->total_terima / (float) $p->total_qty * 100)) : 0;
            $telat = $p->tgl_butuh && strtotime($p->tgl_butuh) < $hari_ini;
            $drop  = $p->jenis === 'dropship';
          ?>
            <tr>
              <td class="fw-semibold"><a href="<?= site_url('pemenuhan/po_detail/' . (int) $p->id) ?>"><?= html_escape($p->no_po) ?></a><div class="muted"><?= $tgl($p->tanggal) ?></div></td>
              <td><?= html_escape($p->nama_supplier) ?></td>
              <td><?= html_escape($p->no_so) ?><div class="muted"><?= html_escape($p->nama_customer) ?></div></td>
              <td><?= $drop ? '<span class="badge text-bg-info fw-normal">Langsung ke customer</span>' : '<span class="badge text-bg-light fw-normal">Gudang</span>' ?></td>
              <td class="<?= $telat ? 'text-danger fw-semibold' : '' ?>"><?= $tgl($p->tgl_butuh) ?><?= $telat ? ' <i class="bi bi-exclamation-circle" title="Lewat tanggal dibutuhkan"></i>' : '' ?></td>
              <td>
                <?= $num($p->total_terima) ?> / <?= $num($p->total_qty) ?> (<?= $pct ?>%)
                <div class="mini-bar"><div style="width:<?= $pct ?>%"></div></div>
              </td>
              <td><?= $badge('po', $p->status) ?></td>
              <td class="text-end text-nowrap">
                <?php if (!empty($izin['terima'])): ?>
                  <a href="<?= site_url('pemenuhan/po_terima/' . (int) $p->id) ?>" class="btn btn-sm btn-success py-0"><?= $drop ? 'Diterima customer' : 'Terima barang' ?></a>
                <?php endif; ?>
                <a href="<?= site_url('pemenuhan/po_cetak/' . (int) $p->id) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0" title="Cetak PO"><i class="bi bi-printer"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
