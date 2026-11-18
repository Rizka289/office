<?php defined('BASEPATH') or exit('No direct script access allowed');
/*
 * Widget dashboard: NOTIFIKASI + RINGKASAN KEUANGAN (uang masuk vs keluar).
 * Panggil dari view dashboard:  <?php $this->load->view('dashboard/_keuangan'); ?>
 *
 * Variabel dari controller Dashboard:
 *   $notif : hasil get_notifikasi($role)            (opsional)
 *   $keu   : hasil Dashboard_keuangan_model->get_data() (hanya untuk super_admin/atasan/finance)
 */
$role = $this->session->userdata('role');

// translate() dengan cadangan teks kalau key belum ada di tabel interfaces
$tr = function ($key, $def) {
    $s = translate($key);
    return ($s === '' || $s === null || $s === $key) ? $def : $s;
};
$rpk = function ($n) { return 'Rp ' . number_format((float) $n, 0, ',', '.'); };
$bln_nama = array(1 => $tr('i18n_jan', 'Jan'), $tr('i18n_feb', 'Feb'), $tr('i18n_mar', 'Mar'), $tr('i18n_apr', 'Apr'),
    $tr('i18n_mei', 'Mei'), $tr('i18n_jun', 'Jun'), $tr('i18n_jul', 'Jul'), $tr('i18n_agu', 'Agu'),
    $tr('i18n_sep', 'Sep'), $tr('i18n_okt', 'Okt'), $tr('i18n_nov', 'Nov'), $tr('i18n_des', 'Des'));
// persentase naik/turun dibanding bulan lalu
$tren = function ($kini, $lalu, $naik_bagus) {
    if ($lalu === null || (float) $lalu == 0) { return ''; }
    $p = ($kini - $lalu) / abs($lalu) * 100;
    $bagus = $naik_bagus ? $p >= 0 : $p <= 0;
    return '<span class="' . ($bagus ? 'text-success' : 'text-danger') . '"><i class="bi bi-arrow-' . ($p >= 0 ? 'up' : 'down') . '-short"></i>'
        . number_format(abs($p), 0, ',', '.') . '%</span>';
};

$notif = isset($notif) ? $notif : array('list' => array(), 'count' => array('penawaran' => 0, 'po' => 0));
$tampil_notif = in_array($role, array('atasan', 'super_admin'), true);
$tampil_keu   = isset($keu) && in_array($role, array('super_admin', 'atasan', 'finance'), true);
?>
<style>
  .dk-card { background:#fff; border:1px solid #edf0f1; border-radius:14px; box-shadow:0 2px 10px rgba(15,42,41,.04); }
  .dk-kpi { padding:1rem 1.1rem; height:100%; }
  .dk-kpi .ikon { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; }
  .dk-kpi .nilai { font-size:1.25rem; font-weight:700; margin:.35rem 0 .1rem; white-space:nowrap; }
  .dk-kpi .lbl { color:#6b7280; font-size:.78rem; }
  .dk-in  { background:#e7f6ec; color:#15803d; }
  .dk-out { background:#fdecec; color:#c62828; }
  .dk-net { background:var(--brand-light); color:var(--brand); }
  .dk-head { padding:.8rem 1.1rem; border-bottom:1px solid #edf0f1; font-weight:600; display:flex; justify-content:space-between; align-items:center; gap:.5rem; flex-wrap:wrap; }
  .dk-chart { position:relative; height:320px; padding:1rem; }
  .dk-row { padding:.55rem 1.1rem; border-bottom:1px dashed #edf0f1; font-size:.85rem; }
  .dk-row:last-child { border-bottom:0; }
  .dk-bar { height:5px; background:#f1f3f4; border-radius:3px; margin-top:5px; overflow:hidden; }
  .dk-bar > div { height:100%; border-radius:3px; }
  .dk-notif a.item { display:flex; gap:.6rem; padding:.6rem 1.1rem; border-bottom:1px solid #f1f3f4; color:inherit; text-decoration:none; }
  .dk-notif a.item:hover { background:var(--brand-light); }
  .dk-notif .scroll { max-height:300px; overflow-y:auto; }
  .dk-legend { display:flex; gap:1rem; font-size:.78rem; color:#6b7280; flex-wrap:wrap; }
  .dk-legend i { display:inline-block; width:10px; height:10px; border-radius:2px; margin-right:4px; vertical-align:-1px; }
</style>

<?php /* ===================== NOTIFIKASI ===================== */ ?>
<?php if ($tampil_notif): ?>
  <div class="dk-card dk-notif mb-3">
    <div class="dk-head">
      <span><i class="bi bi-bell<?= $notif['list'] ? '-fill text-warning' : '' ?> me-2"></i><?= $tr('i18n_perlu_persetujuan', 'Perlu Persetujuan') ?>
        <?php if ($notif['list']): ?><span class="badge rounded-pill text-bg-danger ms-1"><?= count($notif['list']) ?></span><?php endif; ?>
      </span>
      <span class="d-flex gap-2">
        <?php if ($notif['count']['penawaran']): ?>
          <a href="<?= site_url('penawaran?status=pending') ?>" class="btn btn-sm btn-outline-primary py-0"><?= (int) $notif['count']['penawaran'] ?> <?= $tr('i18n_penawaran_3', 'penawaran') ?></a>
        <?php endif; ?>
        <?php if ($notif['count']['po']): ?>
          <a href="<?= site_url('pemenuhan/po?status=draft') ?>" class="btn btn-sm btn-outline-warning py-0"><?= (int) $notif['count']['po'] ?> <?= $tr('i18n_po', 'PO') ?></a>
        <?php endif; ?>
      </span>
    </div>
    <?php if (!$notif['list']): ?>
      <div class="px-3 py-4 text-center text-muted small"><i class="bi bi-check2-circle d-block fs-4 mb-1 text-success"></i><?= $tr('i18n_tidak_ada_penawaran_po_yang_menunggu', 'Tidak ada penawaran / PO yang menunggu persetujuan') ?></div>
    <?php else: ?>
      <div class="scroll">
        <?php foreach ($notif['list'] as $n): ?>
          <a href="<?= $n['url'] ?>" class="item">
            <i class="bi <?= $n['ikon'] ?> mt-1"></i>
            <div class="flex-grow-1 lh-sm">
              <div class="fw-semibold small"><?= html_escape($n['judul']) ?></div>
              <div class="text-muted" style="font-size:.75rem"><?= html_escape($n['sub']) ?></div>
            </div>
            <div class="text-muted text-nowrap" style="font-size:.72rem"><?= html_escape($n['waktu']) ?></div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php /* ===================== KEUANGAN ===================== */ ?>
<?php if ($tampil_keu):
  $tahun = (int) $keu['tahun'];
  $bln   = (int) $keu['bulan_ini'];
  $net   = $keu['tot_masuk'] - $keu['tot_keluar'];
  $net_bln = $keu['masuk_bulan_ini'] - $keu['keluar_bulan_ini'];
?>
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-cash-coin me-2" style="color:var(--brand)"></i><?= $tr('i18n_ringkasan_keuangan', 'Ringkasan Keuangan') ?> <?= $tahun ?></h6>
    <form method="get" class="d-flex gap-1 align-items-center">
      <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
        <?php for ($t = (int) date('Y'); $t >= (int) date('Y') - 5; $t--): ?>
          <option value="<?= $t ?>" <?= $t === $tahun ? 'selected' : '' ?>><?= $t ?></option>
        <?php endfor; ?>
      </select>
      <a href="<?= site_url('arus_kas?tahun=' . $tahun) ?>" class="btn btn-sm btn-brand text-nowrap"><i class="bi bi-graph-up-arrow me-1"></i><?= $tr('i18n_ak_judul', 'Arus Kas') ?></a>
    </form>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-6 col-xl-3"><div class="dk-card dk-kpi">
      <div class="d-flex justify-content-between"><span class="lbl"><?= $tr('i18n_menu_uang_masuk', 'Uang Masuk') ?> <?= $tahun ?></span><span class="ikon dk-in"><i class="bi bi-arrow-down-left"></i></span></div>
      <div class="nilai text-success"><?= $rpk($keu['tot_masuk']) ?></div>
      <div class="lbl"><?= $bln_nama[$bln] ?>: <?= $rpk($keu['masuk_bulan_ini']) ?> <?= $tren($keu['masuk_bulan_ini'], $keu['masuk_bulan_lalu'], true) ?></div>
    </div></div>
    <div class="col-6 col-xl-3"><div class="dk-card dk-kpi">
      <div class="d-flex justify-content-between"><span class="lbl"><?= $tr('i18n_menu_uang_keluar', 'Uang Keluar') ?> <?= $tahun ?></span><span class="ikon dk-out"><i class="bi bi-arrow-up-right"></i></span></div>
      <div class="nilai text-danger"><?= $rpk($keu['tot_keluar']) ?></div>
      <div class="lbl"><?= $bln_nama[$bln] ?>: <?= $rpk($keu['keluar_bulan_ini']) ?> <?= $tren($keu['keluar_bulan_ini'], $keu['keluar_bulan_lalu'], false) ?></div>
    </div></div>
    <div class="col-6 col-xl-3"><div class="dk-card dk-kpi">
      <div class="d-flex justify-content-between"><span class="lbl"><?= $tr('i18n_selisih', 'Selisih') ?> <?= $tahun ?></span><span class="ikon dk-net"><i class="bi bi-wallet2"></i></span></div>
      <div class="nilai <?= $net < 0 ? 'text-danger' : '' ?>"><?= $rpk($net) ?></div>
      <div class="lbl"><?= $tr('i18n_masuk_dikurangi_keluar', 'Uang masuk − uang keluar') ?></div>
    </div></div>
    <div class="col-6 col-xl-3"><div class="dk-card dk-kpi">
      <div class="d-flex justify-content-between"><span class="lbl"><?= $tr('i18n_selisih', 'Selisih') ?> <?= $bln_nama[$bln] ?></span><span class="ikon dk-net"><i class="bi bi-calendar3"></i></span></div>
      <div class="nilai <?= $net_bln < 0 ? 'text-danger' : '' ?>"><?= $rpk($net_bln) ?></div>
      <div class="lbl"><?= $net_bln < 0 ? $tr('i18n_keluar_lebih_besar', 'Pengeluaran lebih besar') : $tr('i18n_masuk_lebih_besar', 'Pemasukan lebih besar') ?></div>
    </div></div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-xl-8">
      <div class="dk-card h-100">
        <div class="dk-head">
          <span><?= $tr('i18n_grafik_masuk_keluar', 'Uang Masuk vs Uang Keluar per Bulan') ?></span>
          <span class="dk-legend">
            <span><i style="background:#16a34a"></i><?= $tr('i18n_menu_uang_masuk', 'Uang Masuk') ?></span>
            <span><i style="background:#dc2626"></i><?= $tr('i18n_menu_uang_keluar', 'Uang Keluar') ?></span>
            <span><i style="background:#c98a3e;height:3px;vertical-align:3px"></i><?= $tr('i18n_saldo_berjalan', 'Saldo berjalan') ?></span>
          </span>
        </div>
        <div class="dk-chart"><canvas id="dkChart"></canvas></div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="dk-card h-100">
        <div class="dk-head"><span><?= $tr('i18n_rincian_sumber', 'Rincian') ?> <?= $tahun ?></span></div>
        <?php
        $maks = 0;
        foreach ($keu['sumber'] as $s) { $maks = max($maks, $s['total']); }
        foreach (array('masuk', 'keluar') as $arah): ?>
          <div class="px-3 pt-2 small text-uppercase text-muted" style="font-size:.68rem;letter-spacing:.06em">
            <?= $arah === 'masuk' ? $tr('i18n_menu_uang_masuk', 'Uang Masuk') : $tr('i18n_menu_uang_keluar', 'Uang Keluar') ?>
          </div>
          <?php foreach ($keu['sumber'] as $key => $s): if ($s['arah'] !== $arah) continue; ?>
            <div class="dk-row">
              <div class="d-flex justify-content-between gap-2">
                <a href="<?= site_url($s['url']) ?>" class="text-reset text-decoration-none"><?= $tr($s['label'], $s['label_def']) ?></a>
                <b class="<?= $arah === 'masuk' ? 'text-success' : 'text-danger' ?>"><?= $s['tabel'] ? $rpk($s['total']) : '<span class="text-muted fw-normal">-</span>' ?></b>
              </div>
              <div class="dk-bar"><div style="width:<?= $maks > 0 ? round($s['total'] / $maks * 100) : 0 ?>%;background:<?= $arah === 'masuk' ? '#16a34a' : '#dc2626' ?>"></div></div>
            </div>
          <?php endforeach; ?>
        <?php endforeach; ?>

        <?php if ($keu['tidak_ketemu'] && $role === 'super_admin'): ?>
          <div class="px-3 py-2 small text-warning-emphasis bg-warning-subtle rounded-bottom">
            <i class="bi bi-exclamation-triangle me-1"></i>Tabel untuk: <b><?= html_escape(implode(', ', $keu['tidak_ketemu'])) ?></b> belum ditemukan.
            Tambahkan nama tabelnya di <code>models/Dashboard_keuangan_model.php</code>.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
  <script>
    (function () {
      var el = document.getElementById('dkChart');
      if (!el || typeof Chart === 'undefined') return;
      var label  = <?= json_encode(array_values($bln_nama)) ?>;
      var masuk  = <?= json_encode(array_values($keu['masuk'])) ?>;
      var keluar = <?= json_encode(array_values($keu['keluar'])) ?>;
      var saldo  = <?= json_encode(array_values($keu['saldo'])) ?>;
      // Bulan yang belum terjadi tidak perlu digambar garis saldonya
      var batas = <?= ($tahun === (int) date('Y')) ? (int) date('n') : 12 ?>;
      saldo = saldo.map(function (v, i) { return i < batas ? v : null; });

      var rp = function (v) { return 'Rp ' + Math.round(v).toLocaleString('id-ID'); };
      var singkat = function (v) {
        var a = Math.abs(v);
        if (a >= 1e9) return (v / 1e9).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' M';
        if (a >= 1e6) return (v / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
        if (a >= 1e3) return (v / 1e3).toLocaleString('id-ID', { maximumFractionDigits: 0 }) + ' rb';
        return v;
      };

      new Chart(el, {
        data: {
          labels: label,
          datasets: [
            { type: 'bar', label: <?= json_encode($tr('i18n_menu_uang_masuk', 'Uang Masuk')) ?>, data: masuk, backgroundColor: '#16a34a', borderRadius: 4, maxBarThickness: 22, order: 2 },
            { type: 'bar', label: <?= json_encode($tr('i18n_menu_uang_keluar', 'Uang Keluar')) ?>, data: keluar, backgroundColor: '#dc2626', borderRadius: 4, maxBarThickness: 22, order: 2 },
            { type: 'line', label: <?= json_encode($tr('i18n_saldo_berjalan', 'Saldo berjalan')) ?>, data: saldo, borderColor: '#c98a3e', backgroundColor: '#c98a3e',
              borderWidth: 2, pointRadius: 3, tension: .3, spanGaps: false, order: 1 }
          ]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: function (c) { return ' ' + c.dataset.label + ': ' + rp(c.parsed.y || 0); },
                footer: function (items) {
                  var i = items[0].dataIndex, s = masuk[i] - keluar[i];
                  return <?= json_encode($tr('i18n_selisih', 'Selisih')) ?> + ': ' + rp(s);
                }
              }
            }
          },
          scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, grid: { color: '#f1f3f4' }, ticks: { callback: singkat } }
          },
          onClick: function (e, el) {
            if (!el.length) return;
            window.location = <?= json_encode(site_url('arus_kas?tahun=' . $tahun)) ?>;
          }
        }
      });
    })();
  </script>
<?php endif; ?>
