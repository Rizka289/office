<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
// Kelompokkan baris item per "jenis barang" (id_barang kalau ada, atau
// kombinasi jenis/warna/finishing/komponen kalau item diisi manual tanpa
// pilih dari master Barang). Tujuannya supaya foto & deskripsi cukup
// ditampilkan sekali, sedangkan ukuran/qty/harga tetap tampil per varian —
// persis seperti contoh dokumen surat penawaran aslinya.
$kelompok = [];

foreach ($items as $it) {
    $kunci = !empty($it->id_barang)
        ? 'b' . $it->id_barang
        : 't' . md5(
            ($it->jenis_item ?? '') . '|' . ($it->warna ?? '') . '|' .
            ($it->finishing ?? '') . '|' . ($it->komponen_kusen ?? '') . '|' .
            ($it->komponen_daun ?? '')
        );

    if (!isset($kelompok[$kunci])) {
        $kelompok[$kunci] = [
            'jenis_item'     => $it->jenis_item,
            'warna'          => $it->warna,
            'finishing'      => $it->finishing,
            'komponen_kusen' => $it->komponen_kusen,
            'komponen_daun'  => $it->komponen_daun,
            'gambar'         => $it->gambar_barang ?? null,
            'keterangan'     => [],
            'baris'          => [],
        ];
    }

    if (!empty($it->keterangan) && !in_array($it->keterangan, $kelompok[$kunci]['keterangan'], TRUE)) {
        $kelompok[$kunci]['keterangan'][] = $it->keterangan;
    }

    $kelompok[$kunci]['baris'][] = $it;
}

// Folder tempat file gambar barang diupload. SESUAIKAN path ini kalau
// ternyata beda dengan folder upload yang dipakai modul master Barang.
$folder_gambar_barang = 'uploads/barang/';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Penawaran <?= html_escape($header->no_surat); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root { --brand: #1f7a45; --brand-dark: #14512f; --brand-light: #eaf5ee; }
  * { box-sizing: border-box; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #222; margin: 0; padding: 24px; background: #f2f4f3; }
  .sheet { max-width: 1000px; margin: 0 auto; background: #fff; padding: 28px 32px; border-radius: 6px; box-shadow: 0 2px 10px rgba(0,0,0,.08); }

  .no-print { text-align: right; margin-bottom: 14px; }
  .no-print button { background: var(--brand); color: #fff; border: none; padding: 8px 18px; border-radius: 6px; font-size: 13px; cursor: pointer; }

  .kop { display: flex; align-items: center; justify-content: center; gap: 14px; border-bottom: 3px solid var(--brand); padding-bottom: 12px; margin-bottom: 16px; }
  .kop-logo { width: 46px; height: 46px; border-radius: 8px; background: var(--brand-light); color: var(--brand-dark); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px; overflow: hidden; }
  .kop-logo img { width: 100%; height: 100%; object-fit: contain; }
  .kop-text { text-align: center; }
  .kop-text .nama { font-weight: 700; font-size: 16px; color: var(--brand-dark); }
  .kop-text .sub { font-size: 11px; color: #666; }

  .info-surat { width: 100%; margin-bottom: 16px; font-size: 12px; }
  .info-surat td { padding: 2px 0; vertical-align: top; }
  .info-surat td.label { width: 170px; color: #555; }
  .info-surat td.sep { width: 12px; }

  .cn { display: block; font-size: 9.5px; font-weight: 400; color: #6b8a76; }

  table.item-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
  table.item-table > thead > tr > th,
  table.item-table > tbody > tr > td { border: 1px solid #d7dbd9; padding: 6px 8px; font-size: 11px; }
  table.item-table > thead th { background: var(--brand-light); color: var(--brand-dark); text-align: center; font-weight: 700; }
  table.item-table > tbody > tr > td { vertical-align: top; }
  table.item-table .num { text-align: center; white-space: nowrap; }
  table.item-table .money { text-align: right; white-space: nowrap; }

  .gambar-cell { width: 12%; text-align: center; padding: 6px !important; }
  .gambar-cell img { width: 100%; max-width: 110px; height: auto; border-radius: 4px; display: block; margin: 0 auto; }
  .gambar-kosong {
    width: 90px; height: 90px; margin: 0 auto; border-radius: 4px;
    background: var(--brand-light); color: #9db8a6;
    display: flex; align-items: center; justify-content: center; font-size: 26px;
  }

  .deskripsi-cell { width: 28%; }
  table.spek-table { width: 100%; border-collapse: collapse; }
  table.spek-table td { border: none; padding: 2px 0; font-size: 11px; vertical-align: top; }
  table.spek-table td.spek-label { width: 40%; color: #555; white-space: nowrap; padding-right: 6px; }
  table.spek-table .spek-nama { font-weight: 700; color: var(--brand-dark); font-size: 12px; }

  table.komponen-table { width: 100%; border-collapse: collapse; margin: 3px 0 4px; }
  table.komponen-table th, table.komponen-table td {
    border: 1px solid #e3e8e5; padding: 3px 5px; font-size: 10px; text-align: center;
  }
  table.komponen-table th { background: #f5f8f6; color: #555; font-weight: 600; }

  .keterangan-block { margin-top: 2px; }
  .keterangan-block .spek-label { display: block; color: #555; margin-bottom: 2px; }

  table.total-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
  table.total-table td { padding: 5px 8px; font-size: 12px; }
  table.total-table td.tlabel { text-align: right; width: 82%; color: #444; }
  table.total-table td.tval { text-align: right; font-weight: 600; width: 18%; white-space: nowrap; }
  table.total-table tr.grand td { border-top: 2px solid var(--brand); font-weight: 700; font-size: 13px; color: var(--brand-dark); padding-top: 8px; }

  .syarat { font-size: 10.5px; color: #555; margin-bottom: 28px; }
  .syarat ol { margin: 4px 0 0 16px; padding: 0; }

  .ttd { display: flex; justify-content: space-between; margin-top: 24px; }
  .ttd .box { text-align: center; width: 220px; }
  .ttd .line { margin-top: 60px; border-top: 1px solid #333; padding-top: 4px; font-size: 11px; }

  @media print {
    body { background: #fff; padding: 0; }
    .sheet { box-shadow: none; padding: 0; }
    .no-print { display: none; }
    table.item-table { page-break-inside: auto; }
    table.item-table tr { page-break-inside: avoid; }
  }
</style>
</head>
<body>

<div class="sheet">

  <div class="no-print">
    <button onclick="window.print()"><i class="bi bi-printer"></i> Cetak / Simpan PDF</button>
  </div>

  <!-- Kop Surat -->
  <div class="kop">
    <div class="kop-logo">
      <img src="<?= base_url('uploads/logo/Logo.jpeg'); ?>" alt="Logo" onerror="this.style.display='none'">
    </div>
    <div class="kop-text">
      <div class="nama">PT OUPAI PINTU JENDELA INDONESIA</div>
      <div class="sub">Manufaktur Pintu &amp; Jendela</div>
    </div>
  </div>

  <!-- Info Surat -->
  <table class="info-surat">
    <tr>
      <td class="label">Tanggal</td><td class="sep">:</td>
      <td><?= date('d/m/Y', strtotime($header->tanggal)); ?></td>
    </tr>
    <tr>
      <td class="label">No. Surat</td><td class="sep">:</td>
      <td><?= html_escape($header->no_surat); ?></td>
    </tr>
    <tr>
      <td class="label">Nama Perusahaan Klien</td><td class="sep">:</td>
      <td><?= html_escape($header->customer ?? '-'); ?></td>
    </tr>
    <tr>
      <td class="label">Informasi Kontak</td><td class="sep">:</td>
      <td><?= html_escape($header->customer_telepon ?? '-'); ?></td>
    </tr>
    <tr>
      <td class="label">Alamat Surat</td><td class="sep">:</td>
      <td><?= html_escape($header->customer_alamat ?? '-'); ?></td>
    </tr>
  </table>

  <!-- Tabel Item -->
  <table class="item-table">
    <thead>
      <tr>
        <th>GAMBAR<span class="cn">图片</span></th>
        <th>Deskripsi<span class="cn">描述</span></th>
        <th>Lebar (mm)<span class="cn">宽 (mm)</span></th>
        <th>Tinggi (mm)<span class="cn">高 (mm)</span></th>
        <th>Total Luas (m²)<span class="cn">总面积 (m²)</span></th>
        <th>Qty<span class="cn">数量</span></th>
        <th>Harga / Unit</th>
        <th>Total Harga<span class="cn">总计</span></th>
      </tr>
    </thead>
    <tbody>
      <?php $jumlah_unit = 0; ?>
      <?php foreach ($kelompok as $grp): ?>
        <?php
          $jumlah_baris  = count($grp['baris']);
          $baris_pertama = TRUE;
        ?>
        <?php foreach ($grp['baris'] as $it): $jumlah_unit += (int) $it->qty; ?>
          <tr>
            <?php if ($baris_pertama): ?>
              <td class="gambar-cell" rowspan="<?= $jumlah_baris; ?>">
                <?php if (!empty($grp['gambar'])): ?>
                  <img src="<?= base_url($folder_gambar_barang . $grp['gambar']); ?>"
                       alt="<?= html_escape($grp['jenis_item']); ?>"
                       onerror="this.parentElement.innerHTML='<div class=&quot;gambar-kosong&quot;><i class=&quot;bi bi-image&quot;></i></div>'">
                <?php else: ?>
                  <div class="gambar-kosong"><i class="bi bi-image"></i></div>
                <?php endif; ?>
              </td>
              <td class="deskripsi-cell" rowspan="<?= $jumlah_baris; ?>">
                <table class="spek-table">
                  <tr>
                    <td class="spek-label">Jenis<span class="cn">类型</span></td>
                    <td class="spek-nama"><?= html_escape($grp['jenis_item']); ?></td>
                  </tr>
                  <?php if (!empty($grp['warna'])): ?>
                    <tr>
                      <td class="spek-label">Warna<span class="cn">颜色</span></td>
                      <td><?= html_escape($grp['warna']); ?></td>
                    </tr>
                  <?php endif; ?>
                  <?php if (!empty($grp['komponen_kusen']) || !empty($grp['komponen_daun'])): ?>
                    <tr>
                      <td colspan="2" class="spek-label">Komponen<span class="cn">成分</span></td>
                    </tr>
                    <tr>
                      <td colspan="2">
                        <table class="komponen-table">
                          <tr>
                            <th>Kusen (mm)<span class="cn">门框 (mm)</span></th>
                            <th>Daun (mm)<span class="cn">叶子 (mm)</span></th>
                          </tr>
                          <tr>
                            <td><?= html_escape($grp['komponen_kusen'] ?: '-'); ?></td>
                            <td><?= html_escape($grp['komponen_daun'] ?: '-'); ?></td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  <?php endif; ?>
                  <?php if (!empty($grp['finishing'])): ?>
                    <tr>
                      <td class="spek-label">Finishing<span class="cn">精加工</span></td>
                      <td><?= html_escape($grp['finishing']); ?></td>
                    </tr>
                  <?php endif; ?>
                  <?php if (!empty($grp['keterangan'])): ?>
                    <tr>
                      <td colspan="2" class="keterangan-block">
                        <span class="spek-label">Keterangan<span class="cn">信息</span></span>
                        <?= nl2br(html_escape(implode("\n", $grp['keterangan']))); ?>
                      </td>
                    </tr>
                  <?php endif; ?>
                </table>
              </td>
            <?php endif; ?>
            <td class="num"><?= (int) $it->lebar_mm; ?></td>
            <td class="num"><?= (int) $it->tinggi_mm; ?></td>
            <td class="num"><?= number_format((float) $it->luas_m2, 2); ?></td>
            <td class="num"><?= (int) $it->qty; ?></td>
            <td class="money">Rp <?= number_format((float) $it->harga_unit, 0, ',', '.'); ?></td>
            <td class="money">Rp <?= number_format((float) $it->total_harga, 0, ',', '.'); ?></td>
          </tr>
          <?php $baris_pertama = FALSE; ?>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </tbody>
  </table>

  <table class="total-table">
    <tr>
      <td class="tlabel">Jumlah unit<span class="cn" style="text-align:right">单元数量</span>: <?= $jumlah_unit; ?></td>
      <td class="tval">Subtotal: Rp <?= number_format((float) $header->subtotal, 0, ',', '.'); ?></td>
    </tr>
    <?php if ((float) $header->ppn_persen > 0): ?>
      <tr>
        <td class="tlabel">PPN <?= rtrim(rtrim(number_format($header->ppn_persen, 2), '0'), '.'); ?>%</td>
        <td class="tval">Rp <?= number_format((float) $header->ppn_nominal, 0, ',', '.'); ?></td>
      </tr>
    <?php endif; ?>
    <?php if ((float) $header->pph_persen > 0): ?>
      <tr>
        <td class="tlabel">PPh <?= rtrim(rtrim(number_format($header->pph_persen, 2), '0'), '.'); ?>%</td>
        <td class="tval">Rp <?= number_format((float) $header->pph_nominal, 0, ',', '.'); ?></td>
      </tr>
    <?php endif; ?>
    <tr class="grand">
      <td class="tlabel">Grand Total</td>
      <td class="tval">Rp <?= number_format((float) $header->grand_total, 0, ',', '.'); ?></td>
    </tr>
  </table>

  <!-- Syarat & Ketentuan -->
  <div class="syarat">
    <strong>Syarat &amp; Ketentuan:</strong>
    <ol>
      <li>Pembayaran dibayarkan 100% saat memesan.</li>
      <li>Pembayaran transfer ke Bank Mandiri 1360077787882 atas nama PT OUPAI PINTU JENDELA INDONESIA.</li>
      <li>Penawaran ini tidak bisa dipilih-pilih itemnya, apabila ada perubahan dibuatkan penawaran baru.</li>
      <li>Harga yang ditawarkan tidak termasuk biaya pengiriman.</li>
    </ol>
    <?php if (!empty($header->catatan)): ?>
      <p><strong>Catatan tambahan:</strong> <?= nl2br(html_escape($header->catatan)); ?></p>
    <?php endif; ?>
  </div>

  <!-- Tanda Tangan -->
  <div class="ttd">
    <div class="box">
      <div>Hormat kami,</div>
      <div class="line">PT OUPAI PINTU JENDELA INDONESIA</div>
    </div>
    <div class="box">
      <div>Disetujui oleh,</div>
      <div class="line"><?= html_escape($header->customer ?? '-'); ?></div>
    </div>
  </div>

</div>

</body>
</html>