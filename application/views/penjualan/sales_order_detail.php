<?php defined('BASEPATH') or exit('No direct script access allowed');

$h     = $so['header'];
$items = $so['items'];

$rp     = function ($n) { return 'Rp ' . number_format((float) $n, 0, ',', '.'); };
$num    = function ($n, $d = 0) { return number_format((float) $n, $d, ',', '.'); };
$isi    = function ($v) { $v = trim((string) $v); return $v !== '' && $v !== '-'; };
$multi  = function ($v) { return nl2br(html_escape(trim((string) $v))); };

$status_map = array(
    'draft'     => array('secondary', 'Draft'),
    'confirmed' => array('primary',   'Dikonfirmasi'),
    'produksi'  => array('warning',   'Produksi'),
    'terkirim'  => array('info',      'Terkirim'),
    'selesai'   => array('success',   'Selesai'),
    'batal'     => array('danger',    'Batal'),
);
$st = isset($status_map[$h->status]) ? $status_map[$h->status] : array('secondary', ucfirst($h->status));

// Termin & DP
$dp_persen = (float) $h->dp_persen;
$dp_nilai  = round((float) $h->grand_total * $dp_persen / 100);
$sisa      = (float) $h->grand_total - $dp_nilai;
if ($h->termin === '100' || $dp_persen <= 0) {
    $termin_teks = 'Lunas 100%';
} else {
    $termin_teks = 'DP ' . $num($dp_persen, 2) . '% / pelunasan ' . $num(100 - $dp_persen, 2) . '%';
}

// Ringkasan pengiriman
$total_qty = 0; $total_kirim = 0;
foreach ($items as $it) { $total_qty += (float) $it->qty; $total_kirim += (float) $it->qty_terkirim; }
$persen_kirim = $total_qty > 0 ? min(100, round($total_kirim / $total_qty * 100)) : 0;
?>
<style>
  .sod { --brand: #2f7a57; --brand-dark: #1f5a3f; --brand-soft: #eaf4f0; --line: #e9edeb; }
  .sod .hero { background: linear-gradient(135deg, var(--brand), var(--brand-dark)); color: #fff; border-radius: 14px; padding: 20px 24px; }
  .sod .hero .no-so { font-size: 1.5rem; font-weight: 700; letter-spacing: .01em; }
  .sod .hero .meta { opacity: .88; font-size: .9rem; }
  .sod .panel { background: #fff; border: 1px solid var(--line); border-radius: 12px; }
  .sod .panel-title { font-weight: 600; padding: 12px 18px; border-bottom: 1px solid var(--line); display: flex; align-items: center; gap: 8px; }
  .sod .panel-body { padding: 16px 18px; }
  .sod .kv { display: grid; grid-template-columns: 150px 1fr; gap: 6px 12px; font-size: .9rem; margin: 0; }
  .sod .kv dt { color: #7b857f; font-weight: 400; }
  .sod .kv dd { margin: 0; word-break: break-word; }

  .sod .item { border: 1px solid var(--line); border-radius: 12px; background: #fff; margin-bottom: 14px; overflow: hidden; }
  .sod .item-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; padding: 14px 18px; background: #fafcfb; border-bottom: 1px solid var(--line); }
  .sod .item-name { font-weight: 600; font-size: 1.02rem; }
  .sod .item-sub { color: #7b857f; font-size: .85rem; }
  .sod .item-total { text-align: right; white-space: nowrap; }
  .sod .item-total .amt { font-weight: 700; color: var(--brand-dark); font-size: 1.05rem; }
  .sod .item-body { padding: 14px 18px; }
  .sod .specs { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 12px 18px; margin-bottom: 4px; }
  .sod .spec .lbl { font-size: .78rem; color: #7b857f; }
  .sod .spec .val { font-size: .9rem; }
  .sod .block { margin-top: 14px; padding-top: 12px; border-top: 1px dashed var(--line); }
  .sod .block-title { font-size: .85rem; font-weight: 600; color: var(--brand-dark); margin-bottom: 8px; }
  .sod .mini-table { width: 100%; font-size: .86rem; }
  .sod .mini-table th { color: #7b857f; font-weight: 500; padding: 4px 8px 4px 0; }
  .sod .mini-table td { padding: 4px 8px 4px 0; }
  .sod .mini-table .r { text-align: right; }
  .sod .note { background: #f6f8f7; border-radius: 8px; padding: 8px 12px; font-size: .86rem; color: #4a5650; }

  .sod .sum-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: .92rem; }
  .sod .sum-grand { border-left: 4px solid var(--brand); background: #f8faf9; border-radius: 8px; padding: 12px 16px; margin: 12px 0; }
  .sod .sum-grand .amt { font-size: 1.5rem; font-weight: 700; }
  .sod .pay-bar { display: flex; height: 12px; border-radius: 6px; overflow: hidden; background: #e6ebe8; }
  .sod .pay-bar .dp { background: var(--brand); }
  .sod .pay-bar .sisa { background: #9fc9b4; }
  .sod .pay-legend { display: flex; justify-content: space-between; font-size: .84rem; margin-top: 8px; }
  .sod .dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; margin-right: 6px; }

  @media (min-width: 992px) { .sod .sticky-side { position: sticky; top: 16px; } }
  @media (max-width: 575px) { .sod .kv { grid-template-columns: 1fr; gap: 0; } .sod .kv dd { margin-bottom: 8px; } }

  @media print {
    .sod .no-print, .sod .alert { display: none !important; }
    .sod .hero { background: #fff !important; color: #000 !important; border: 1px solid #000; }
    .sod .sticky-side { position: static; }
    .sod .item, .sod .panel { break-inside: avoid; }
  }
</style>

<div class="sod container-fluid py-3">

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <i class="bi bi-check-circle me-1"></i><?= html_escape($this->session->flashdata('success')) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endif; ?>

  <!-- Judul -->
  <div class="hero mb-3">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="no-so"><?= html_escape($h->no_so) ?></div>
        <div class="meta mt-1">
          <?= html_escape($h->nama_customer) ?> &nbsp;|&nbsp; Tanggal SO <?= date('d M Y', strtotime($h->tanggal)) ?>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2 no-print">
        <span class="badge bg-<?= $st[0] ?> fs-6 fw-normal px-3 py-2"><?= $st[1] ?></span>
        <a href="<?= site_url('sales_order') ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
        <button type="button" class="btn btn-outline-light btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Cetak halaman</button>
      </div>
    </div>
  </div>

  <div class="row g-3">

    <!-- Kiri: data order + item -->
    <div class="col-lg-8">

      <div class="panel mb-3">
        <div class="panel-title"><i class="bi bi-info-circle"></i>Data order</div>
        <div class="panel-body">
          <div class="row g-3">
            <div class="col-md-6">
              <dl class="kv">
                <dt>Customer</dt><dd><?= html_escape($h->nama_customer) ?></dd>
                <dt>No. PO customer</dt><dd><?= $isi($h->no_po_customer) ? html_escape($h->no_po_customer) : '<span class="text-muted">Tidak ada</span>' ?></dd>
                <dt>Dari penawaran</dt>
                <dd><a href="<?= site_url('penawaran/edit/' . (int) $h->id_penawaran) ?>">Lihat penawaran asal</a></dd>
              </dl>
            </div>
            <div class="col-md-6">
              <dl class="kv">
                <dt>Target kirim</dt><dd><?= $isi($h->tgl_kirim) ? date('d M Y', strtotime($h->tgl_kirim)) : '<span class="text-muted">Belum ditentukan</span>' ?></dd>
                <dt>Alamat kirim</dt><dd><?= $isi($h->alamat_kirim) ? $multi($h->alamat_kirim) : '<span class="text-muted">-</span>' ?></dd>
              </dl>
            </div>
            <?php if ($isi($h->catatan)): ?>
              <div class="col-12"><div class="note"><i class="bi bi-chat-left-text me-1"></i><?= $multi($h->catatan) ?></div></div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Item -->
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0"><i class="bi bi-list-ul me-1"></i>Item order (<?= count($items) ?>)</h6>
        <span class="text-muted small">Terkirim <?= $num($total_kirim, 2) ?> dari <?= $num($total_qty, 2) ?> unit</span>
      </div>

      <?php if (empty($items)): ?>
        <div class="panel"><div class="panel-body text-center text-muted py-5">
          <i class="bi bi-inbox fs-2 d-block mb-2"></i>Sales Order ini belum punya item.</div></div>
      <?php endif; ?>

      <?php $no = 1; foreach ($items as $it):
        $luas_bill  = (float) $it->luas_billing_m2 > 0 ? (float) $it->luas_billing_m2 : (float) $it->luas_m2;
        $ada_books  = $isi($it->books_penutup) || (int) $it->qty_books > 0;
        $ada_instal = (int) $it->qty_instalasi > 0 && (float) $it->harga_m2_instalasi > 0;
        $ada_motor  = $isi($it->tipe_motor) || $isi($it->input_power) || $isi($it->voltage);
        $ada_komp   = $isi($it->komponen) || $isi($it->komponen_kusen) || $isi($it->komponen_daun);
        $aks        = !empty($it->aksesoris) ? $it->aksesoris : array();

        $total_books  = (float) $it->luas_m2_books * (int) $it->qty_books * (float) $it->harga_m2_books;
        $total_instal = $luas_bill * (float) $it->harga_m2_instalasi * (int) $it->qty_instalasi;
        $total_aks    = 0; foreach ($aks as $a) { $total_aks += (float) $a->total_harga; }

        $qty_it   = (float) $it->qty;
        $kirim_it = (float) $it->qty_terkirim;
        $pct_it   = $qty_it > 0 ? min(100, round($kirim_it / $qty_it * 100)) : 0;
      ?>
      <div class="item">
        <div class="item-head">
          <div>
            <div class="item-name"><?= $no++ ?>. <?= html_escape($it->jenis_item) ?></div>
            <div class="item-sub">
              <?= $num($it->qty, 2) ?> unit
              <?php if ($isi($it->warna)): ?> &middot; <?= html_escape($it->warna) ?><?php endif; ?>
            </div>
          </div>
          <div class="item-total">
            <div class="amt"><?= $rp($it->total_harga) ?></div>
            <div class="item-sub">Total item</div>
          </div>
        </div>

        <div class="item-body">
          <div class="specs">
            <div class="spec"><div class="lbl">Ukuran (L x T)</div><div class="val"><?= $num($it->lebar_mm) ?> x <?= $num($it->tinggi_mm) ?> mm</div></div>
            <div class="spec"><div class="lbl">Luas</div><div class="val"><?= $num($it->luas_m2, 4) ?> m&sup2;</div></div>
            <?php if ((float) $it->luas_billing_m2 > 0 && (float) $it->luas_billing_m2 != (float) $it->luas_m2): ?>
              <div class="spec"><div class="lbl">Luas untuk tagihan</div><div class="val"><?= $num($it->luas_billing_m2, 4) ?> m&sup2;</div></div>
            <?php endif; ?>
            <div class="spec"><div class="lbl">Harga satuan</div><div class="val"><?= $rp($it->harga_unit) ?></div></div>
            <?php if ($isi($it->finishing)): ?>
              <div class="spec"><div class="lbl">Finishing</div><div class="val"><?= html_escape($it->finishing) ?></div></div>
            <?php endif; ?>
            <?php if ($isi($it->komponen)): ?>
              <div class="spec"><div class="lbl">Komponen</div><div class="val"><?= html_escape($it->komponen) ?></div></div>
            <?php endif; ?>
            <?php if ($isi($it->komponen_kusen)): ?>
              <div class="spec"><div class="lbl">Kusen</div><div class="val"><?= html_escape($it->komponen_kusen) ?></div></div>
            <?php endif; ?>
            <?php if ($isi($it->komponen_daun)): ?>
              <div class="spec"><div class="lbl">Daun</div><div class="val"><?= html_escape($it->komponen_daun) ?></div></div>
            <?php endif; ?>
          </div>

          <?php if ($isi($it->keterangan)): ?>
            <div class="note mt-3"><?= $multi($it->keterangan) ?></div>
          <?php endif; ?>

          <?php if ($ada_motor): ?>
            <div class="block">
              <div class="block-title"><i class="bi bi-gear me-1"></i>Motor</div>
              <div class="specs">
                <?php if ($isi($it->tipe_motor)): ?><div class="spec"><div class="lbl">Tipe</div><div class="val"><?= html_escape($it->tipe_motor) ?></div></div><?php endif; ?>
                <?php if ($isi($it->input_power)): ?><div class="spec"><div class="lbl">Daya</div><div class="val"><?= html_escape($it->input_power) ?></div></div><?php endif; ?>
                <?php if ($isi($it->voltage)): ?><div class="spec"><div class="lbl">Tegangan</div><div class="val"><?= html_escape($it->voltage) ?></div></div><?php endif; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($ada_books): ?>
            <div class="block">
              <div class="block-title"><i class="bi bi-layers me-1"></i>Books penutup</div>
              <?php if ($isi($it->books_penutup)): ?><div class="mb-2 small"><?= $multi($it->books_penutup) ?></div><?php endif; ?>
              <?php if ((int) $it->qty_books > 0): ?>
                <table class="mini-table">
                  <tr><th>Ukuran</th><th class="r">Luas</th><th class="r">Qty</th><th class="r">Harga / m&sup2;</th><th class="r">Subtotal</th></tr>
                  <tr>
                    <td><?= $num($it->lebar_m2_books) ?> x <?= $num($it->tinggi_m2_books) ?> mm</td>
                    <td class="r"><?= $num($it->luas_m2_books, 4) ?> m&sup2;</td>
                    <td class="r"><?= (int) $it->qty_books ?></td>
                    <td class="r"><?= $rp($it->harga_m2_books) ?></td>
                    <td class="r fw-semibold"><?= $rp($total_books) ?></td>
                  </tr>
                </table>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if ($isi($it->kelengkapan)): ?>
            <div class="block">
              <div class="block-title"><i class="bi bi-check2-square me-1"></i>Kelengkapan</div>
              <div class="small"><?= $multi($it->kelengkapan) ?></div>
            </div>
          <?php endif; ?>

          <?php if ($ada_instal): ?>
            <div class="block">
              <div class="block-title"><i class="bi bi-tools me-1"></i>Biaya instalasi</div>
              <table class="mini-table">
                <tr><th>Luas</th><th class="r">Qty</th><th class="r">Tarif / m&sup2;</th><th class="r">Subtotal</th></tr>
                <tr>
                  <td><?= $num($luas_bill, 4) ?> m&sup2;</td>
                  <td class="r"><?= (int) $it->qty_instalasi ?></td>
                  <td class="r"><?= $rp($it->harga_m2_instalasi) ?></td>
                  <td class="r fw-semibold"><?= $rp($total_instal) ?></td>
                </tr>
              </table>
            </div>
          <?php endif; ?>

          <?php if (!empty($aks)): ?>
            <div class="block">
              <div class="block-title"><i class="bi bi-plus-square me-1"></i>Aksesoris (<?= count($aks) ?>)</div>
              <table class="mini-table">
                <tr><th>Nama</th><th class="r">Qty</th><th class="r">Harga</th><th class="r">Subtotal</th></tr>
                <?php foreach ($aks as $a): ?>
                  <tr>
                    <td><?= html_escape($a->nama) ?></td>
                    <td class="r"><?= (int) $a->qty ?></td>
                    <td class="r"><?= $rp($a->harga) ?></td>
                    <td class="r fw-semibold"><?= $rp($a->total_harga) ?></td>
                  </tr>
                <?php endforeach; ?>
              </table>
            </div>
          <?php endif; ?>

          <!-- Progres kirim per item -->
          <div class="block">
            <div class="d-flex justify-content-between small mb-1">
              <span class="text-muted">Pengiriman</span>
              <span><?= $num($kirim_it, 2) ?> dari <?= $num($qty_it, 2) ?> unit</span>
            </div>
            <div class="progress" style="height:6px" role="progressbar" aria-valuenow="<?= $pct_it ?>" aria-valuemin="0" aria-valuemax="100">
              <div class="progress-bar bg-success" style="width: <?= $pct_it ?>%"></div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Kanan: ringkasan + pembayaran -->
    <div class="col-lg-4">
      <div class="sticky-side">

        <div class="panel mb-3">
          <div class="panel-title"><i class="bi bi-receipt"></i>Ringkasan biaya</div>
          <div class="panel-body">
            <div class="sum-row"><span>Subtotal</span><span><?= $rp($h->subtotal) ?></span></div>
            <?php if ((float) $h->biaya_pasang > 0): ?>
              <div class="sum-row"><span>Biaya pemasangan</span><span><?= $rp($h->biaya_pasang) ?></span></div>
            <?php endif; ?>
            <?php if ((float) $h->biaya_ongkir > 0): ?>
              <div class="sum-row"><span>Biaya pengiriman</span><span><?= $rp($h->biaya_ongkir) ?></span></div>
            <?php endif; ?>
            <?php if ((float) $h->ppn_persen > 0): ?>
              <div class="sum-row"><span>PPN <?= $num($h->ppn_persen, 2) ?>%</span><span><?= $rp($h->ppn_nominal) ?></span></div>
            <?php endif; ?>
            <?php if ((float) $h->pph_persen > 0): ?>
              <div class="sum-row"><span>PPh <?= $num($h->pph_persen, 2) ?>%</span><span><?= $rp($h->pph_nominal) ?></span></div>
            <?php endif; ?>
            <div class="sum-grand">
              <div class="small text-muted">Grand total</div>
              <div class="amt"><?= $rp($h->grand_total) ?></div>
            </div>
            <div class="small text-muted"><i class="bi bi-lock me-1"></i>Angka diambil dari penawaran dan tidak bisa diubah di sini.</div>
          </div>
        </div>

        <div class="panel mb-3">
          <div class="panel-title"><i class="bi bi-wallet2"></i>Pembayaran</div>
          <div class="panel-body">
            <div class="fw-semibold mb-2"><?= $termin_teks ?></div>
            <?php if ($dp_persen > 0): ?>
              <div class="pay-bar" aria-hidden="true">
                <div class="dp" style="width: <?= $dp_persen ?>%"></div>
                <div class="sisa" style="width: <?= 100 - $dp_persen ?>%"></div>
              </div>
              <div class="pay-legend">
                <div><span class="dot" style="background:#2f7a57"></span>DP<br><strong><?= $rp($dp_nilai) ?></strong></div>
                <div class="text-end"><span class="dot" style="background:#9fc9b4"></span>Pelunasan<br><strong><?= $rp($sisa) ?></strong></div>
              </div>
            <?php else: ?>
              <div class="small text-muted">Dibayar penuh sebesar <?= $rp($h->grand_total) ?>.</div>
            <?php endif; ?>
          </div>
        </div>

        <div class="panel">
          <div class="panel-title"><i class="bi bi-truck"></i>Progres pengiriman</div>
          <div class="panel-body">
            <div class="d-flex justify-content-between small mb-1">
              <span><?= $persen_kirim ?>% terkirim</span>
              <span class="text-muted"><?= $num($total_kirim, 2) ?> / <?= $num($total_qty, 2) ?> unit</span>
            </div>
            <div class="progress" style="height:8px" role="progressbar" aria-valuenow="<?= $persen_kirim ?>" aria-valuemin="0" aria-valuemax="100">
              <div class="progress-bar bg-success" style="width: <?= $persen_kirim ?>%"></div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>