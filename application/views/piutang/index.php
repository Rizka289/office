<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';
$this->load->view('pemenuhan/_style');

$tahap_badge = function ($t) use ($tahap_label) {
  $x = $tahap_label[$t] ?? array('light', $t);
  return '<span class="badge text-bg-' . $x[0] . ' fw-normal">' . html_escape($x[1]) . '</span>';
};
$tab_url = function ($t) use ($q) {
  $p = array();
  if ($t !== 'semua') { $p['tahap'] = $t; }
  if ($q !== '') { $p['q'] = $q; }
  return site_url('piutang') . ($p ? '?' . http_build_query($p) : '');
};
$kembali = 'piutang' . (($tahap !== 'semua' || $q !== '') ? '?' . http_build_query(array_filter(array('tahap' => $tahap !== 'semua' ? $tahap : '', 'q' => $q))) : '');
$tabs = array('semua' => array('Semua', $ring['total']));
foreach ($tahap_label as $k => $l) { $tabs[$k] = array($l[1], $ring['jumlah_tahap'][$k]); }
?>
<style>
  .pm .nav-tahap .nav-link { color:#5d6862; font-size:.9rem; }
  .pm .nav-tahap .nav-link.active { background:var(--brand); color:#fff; }
  .pm .aging-row { display:flex; justify-content:space-between; font-size:.85rem; padding:6px 0; border-bottom:1px dashed var(--line); }
  .pm .aging-row:last-child { border-bottom:0; }
  .pm .umur-lama { color:#b42318; font-weight:600; }
</style>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <div class="judul"><i class="bi bi-receipt me-2"></i>Piutang Customer</div>
      <div class="meta">Sales Order yang belum lunas: DP yang belum masuk sampai pelunasan setelah barang terkirim</div>
    </div>
    <a href="<?= site_url('piutang/penerimaan') ?>" class="btn btn-light btn-sm"><i class="bi bi-clock-history me-1"></i>Riwayat penerimaan</a>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <div class="langkah mb-3">
    <div><b>1. Sales Order</b>SO dibuat dari penawaran → tagihan sebesar grand total</div>
    <div><b>2. Tagih DP</b>DP sesuai termin harus masuk sebelum SO dikonfirmasi</div>
    <div><b>3. Proses & kirim</b>Barang diproduksi / dibeli lalu dikirim lewat Surat Jalan</div>
    <div><b>4. Tagih pelunasan</b>Semua terkirim + lunas → SO otomatis Selesai</div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="muted">Total tagihan SO</div><div class="fs-5 fw-semibold"><?= $rp($ring['tagihan']) ?></div><div class="muted"><?= (int) $ring['total'] ?> SO belum lunas</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="muted">Sudah diterima</div><div class="fs-5 fw-semibold text-success"><?= $rp($ring['diterima']) ?></div><div class="muted">DP & cicilan</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="muted">Sisa piutang</div><div class="fs-5 fw-semibold text-danger"><?= $rp($ring['sisa']) ?></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="muted">Bisa ditagih sekarang</div><div class="fs-5 fw-semibold text-warning-emphasis"><?= $rp($ring['tagih_sekarang']) ?></div><div class="muted">Kurang DP + pelunasan jatuh tempo</div></div></div></div>
  </div>

  <div class="row g-3">
    <div class="col-xl-9">
      <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
          <ul class="nav nav-pills nav-tahap">
            <?php foreach ($tabs as $k => $t): ?>
              <li class="nav-item"><a class="nav-link py-1 px-3 <?= $tahap === $k ? 'active' : '' ?>" href="<?= $tab_url($k) ?>"><?= html_escape($t[0]) ?> <span class="badge rounded-pill text-bg-light ms-1"><?= (int) $t[1] ?></span></a></li>
            <?php endforeach; ?>
          </ul>
          <form method="get" action="<?= site_url('piutang') ?>" class="d-flex gap-1">
            <?php if ($tahap !== 'semua'): ?><input type="hidden" name="tahap" value="<?= html_escape($tahap) ?>"><?php endif; ?>
            <input type="search" name="q" value="<?= html_escape($q) ?>" class="form-control form-control-sm" placeholder="No. SO / PO / customer">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
          </form>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>No. SO</th><th>Customer</th><th>Tahap</th><th>Kirim</th><th class="text-end">Tagihan</th><th class="text-end">Diterima</th><th class="text-end">Sisa</th><th class="text-end">Umur</th><th></th></tr></thead>
            <tbody>
              <?php if (!$piutang): ?><tr><td colspan="9" class="text-center text-muted py-4">Tidak ada piutang<?= $tahap !== 'semua' ? ' di tahap ini' : '' ?>.</td></tr><?php endif; ?>
              <?php foreach ($piutang as $p): ?>
                <tr>
                  <td class="fw-semibold">
                    <a href="<?= site_url('sales_order/detail/' . (int) $p->id) ?>"><?= html_escape($p->no_so) ?></a>
                    <div class="muted"><?= $tgl($p->tanggal) ?><?= $p->no_po_customer ? ' · PO ' . html_escape($p->no_po_customer) : '' ?></div>
                  </td>
                  <td>
                    <a href="<?= site_url('piutang/customer/' . (int) $p->id_customer) ?>" class="text-reset"><?= html_escape($p->nama_customer ?: '-') ?></a>
                    <?php if (!empty($p->kontak_customer)): ?><div class="muted"><?= html_escape($p->kontak_customer) ?></div><?php endif; ?>
                  </td>
                  <td>
                    <?= $tahap_badge($p->tahap) ?>
                    <div class="muted mt-1"><?= $badge('so', $p->status) ?> · termin <?= html_escape($p->termin) ?></div>
                    <?php if ($p->tahap === 'dp'): ?><div class="muted">Kurang DP <?= $rp($p->kurang_dp) ?></div><?php endif; ?>
                  </td>
                  <td style="min-width:80px"><span class="muted"><?= (int) $p->persen_kirim ?>%</span><div class="mini-bar"><div style="width:<?= (int) $p->persen_kirim ?>%"></div></div></td>
                  <td class="text-end"><?= $rp($p->grand_total) ?></td>
                  <td class="text-end"><?= $rp($p->total_bayar) ?><?php if ($p->tgl_bayar_terakhir): ?><div class="muted"><?= $tgl($p->tgl_bayar_terakhir) ?></div><?php endif; ?></td>
                  <td class="text-end fw-semibold text-danger"><?= $rp($p->sisa) ?></td>
                  <td class="text-end">
                    <span class="<?= $p->tahap === 'tagih' && $p->umur > 30 ? 'umur-lama' : '' ?>"><?= (int) $p->umur ?> hr</span>
                    <div class="muted"><?= $p->tahap === 'tagih' ? 'sejak kirim' : 'sejak SO' ?></div>
                  </td>
                  <td class="text-end text-nowrap">
                    <?php if (!empty($izin['bayar'])): ?>
                      <button type="button" class="btn btn-sm btn-success py-0 btn-bayar-piutang" data-bs-toggle="modal" data-bs-target="#modalBayarPiutang"
                        data-id="<?= (int) $p->id ?>" data-no="<?= html_escape($p->no_so) ?>" data-customer="<?= html_escape($p->nama_customer) ?>"
                        data-sisa="<?= (float) $p->sisa ?>" data-kurang-dp="<?= (float) $p->kurang_dp ?>">Terima</button>
                    <?php endif; ?>
                    <a href="<?= site_url('sales_order/cetak/' . (int) $p->id) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0" title="Cetak invoice"><i class="bi bi-printer"></i></a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-xl-3">
      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-hourglass-split me-2"></i>Umur pelunasan</div>
        <div class="card-body py-2">
          <div class="muted mb-1">Sisa piutang SO yang sudah terkirim, dihitung dari Surat Jalan terakhir</div>
          <?php foreach ($aging_label as $k => $l): ?>
            <div class="aging-row"><span><?= html_escape($l) ?></span><b class="<?= $k === '90_up' && $ring['aging'][$k] > 0 ? 'text-danger' : '' ?>"><?= $rp($ring['aging'][$k]) ?></b></div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><i class="bi bi-people me-2"></i>Sisa per customer</div>
        <ul class="list-group list-group-flush">
          <?php if (!$per_customer): ?><li class="list-group-item text-muted small">-</li><?php endif; ?>
          <?php foreach ($per_customer as $c): ?>
            <li class="list-group-item d-flex justify-content-between small">
              <a href="<?= site_url('piutang/customer/' . (int) $c['id']) ?>" class="text-reset"><?= html_escape($c['nama'] ?: '-') ?> <span class="muted">(<?= (int) $c['jumlah'] ?>)</span></a>
              <b><?= $rp($c['sisa']) ?></b>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</div>
<?php if (!empty($izin['bayar'])) { $this->load->view('piutang/_bayar_modal', array('kembali' => $kembali)); } ?>
