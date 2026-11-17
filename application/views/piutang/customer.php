<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');

$tahap_badge = function ($t) use ($tahap_label) {
  $x = $tahap_label[$t] ?? array('light', $t);
  return '<span class="badge text-bg-' . $x[0] . ' fw-normal">' . html_escape($x[1]) . '</span>';
};
$tot_debit = 0; $tot_kredit = 0;
foreach ($kartu['mutasi'] as $m) { $tot_debit += $m->debit; $tot_kredit += $m->kredit; }
?>
<style>@media print { .no-print, .sidebar, nav, header { display:none !important; } .pm .card { box-shadow:none; } }</style>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <div class="judul"><i class="bi bi-person-vcard me-2"></i><?= translate('i18n_kartu_piutang_3') ?> <?= html_escape($customer->nama) ?></div>
      <div class="meta"><?= html_escape($customer->kontak ?? '') ?><?= !empty($customer->alamat) ? ' · ' . html_escape($customer->alamat) : '' ?></div>
    </div>
    <div class="no-print d-flex gap-1">
      <button type="button" onclick="window.print()" class="btn btn-light btn-sm"><i class="bi bi-printer me-1"></i><?= translate('cetak') ?></button>
      <a href="<?= site_url('piutang') ?>" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left me-1"></i><?= translate('kembali') ?></a>
    </div>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <div class="row g-3 mb-3">
    <div class="col-sm-4"><div class="card h-100"><div class="card-body"><div class="muted"><?= translate('i18n_total_tagihan_semua_so') ?></div><div class="fs-5 fw-semibold"><?= $rp($tot_debit) ?></div><div class="muted"><?= count($kartu['so']) ?> <?= translate('i18n_so') ?></div></div></div></div>
    <div class="col-sm-4"><div class="card h-100"><div class="card-body"><div class="muted"><?= translate('t_dbayr') ?></div><div class="fs-5 fw-semibold text-success"><?= $rp($tot_kredit) ?></div></div></div></div>
    <div class="col-sm-4"><div class="card h-100"><div class="card-body"><div class="muted"><?= translate('i18n_saldo_piutang') ?></div><div class="fs-5 fw-semibold <?= $kartu['saldo'] > 0.005 ? 'text-danger' : 'text-success' ?>"><?= $rp($kartu['saldo']) ?></div></div></div></div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-exclamation-circle me-2"></i><?= translate('i18n_so_belum_lunas') ?></div>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light"><tr><th><?= translate('i18n_no_so') ?></th><th><?= translate('i18n_tahap') ?></th><th class="text-end"><?= translate('i18n_tagihan') ?></th><th class="text-end"><?= translate('i18n_diterima') ?></th><th class="text-end"><?= translate('i18n_sisa') ?></th><th class="text-end"><?= translate('i18n_umur') ?></th><th class="no-print"></th></tr></thead>
        <tbody>
          <?php if (!$terbuka): ?><tr><td colspan="7" class="text-center text-muted py-3"><?= translate('i18n_semua_so_customer_ini_sudah_lunas') ?></td></tr><?php endif; ?>
          <?php foreach ($terbuka as $p): ?>
            <tr>
              <td class="fw-semibold"><a href="<?= site_url('sales_order/detail/' . (int) $p->id) ?>"><?= html_escape($p->no_so) ?></a><div class="muted"><?= $tgl($p->tanggal) ?></div></td>
              <td><?= $tahap_badge($p->tahap) ?><?php if ($p->tahap === 'dp'): ?><div class="muted"><?= translate('i18n_kurang_dp_2') ?> <?= $rp($p->kurang_dp) ?></div><?php endif; ?></td>
              <td class="text-end"><?= $rp($p->grand_total) ?></td>
              <td class="text-end"><?= $rp($p->total_bayar) ?></td>
              <td class="text-end fw-semibold text-danger"><?= $rp($p->sisa) ?></td>
              <td class="text-end"><?= (int) $p->umur ?> <?= translate('i18n_hr') ?></td>
              <td class="text-end no-print">
                <?php if (!empty($izin['bayar'])): ?>
                  <button type="button" class="btn btn-sm btn-success py-0 btn-bayar-piutang" data-bs-toggle="modal" data-bs-target="#modalBayarPiutang"
                    data-id="<?= (int) $p->id ?>" data-no="<?= html_escape($p->no_so) ?>" data-customer="<?= html_escape($customer->nama) ?>"
                    data-sisa="<?= (float) $p->sisa ?>" data-kurang-dp="<?= (float) $p->kurang_dp ?>"><?= translate('i18n_terima') ?></button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><i class="bi bi-journal-text me-2"></i><?= translate('i18n_mutasi_piutang') ?></div>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light"><tr><th><?= translate('tanggal') ?></th><th><?= translate('deskripsi') ?></th><th class="text-end"><?= translate('i18n_tagihan') ?></th><th class="text-end"><?= translate('pembayaran') ?></th><th class="text-end"><?= translate('i18n_saldo') ?></th></tr></thead>
        <tbody>
          <?php if (!$kartu['mutasi']): ?><tr><td colspan="5" class="text-center text-muted py-3"><?= translate('i18n_belum_ada_transaksi') ?></td></tr><?php endif; ?>
          <?php foreach ($kartu['mutasi'] as $m): ?>
            <tr>
              <td class="text-nowrap"><?= $tgl($m->tanggal) ?></td>
              <td><?= html_escape($m->ket) ?></td>
              <td class="text-end"><?= $m->debit ? $rp($m->debit) : '' ?></td>
              <td class="text-end text-success"><?= $m->kredit ? $rp($m->kredit) : '' ?></td>
              <td class="text-end fw-semibold"><?= $rp($m->saldo) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <?php if ($kartu['mutasi']): ?>
          <tfoot class="table-light"><tr><th colspan="2"><?= translate('total') ?></th><th class="text-end"><?= $rp($tot_debit) ?></th><th class="text-end"><?= $rp($tot_kredit) ?></th><th class="text-end"><?= $rp($kartu['saldo']) ?></th></tr></tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>
</div>
<?php if (!empty($izin['bayar'])) { $this->load->view('piutang/_bayar_modal', array('kembali' => 'piutang/customer/' . (int) $customer->id)); } ?>
