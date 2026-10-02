<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php

// Kelompokkan baris item per "jenis barang"
$kelompok = [];

foreach ($items as $it) {
  $kunci = !empty($it->id_barang)
    ? 'b' . $it->id_barang
    : 't' . md5(
      ($it->jenis_item ?? '') . '|' . ($it->warna ?? '') . '|' . ($it->komponen ?? '') . '|' .
        ($it->finishing ?? '') . '|' . ($it->komponen_kusen ?? '') . '|' .
        ($it->komponen_daun ?? '')
    );

  if (!isset($kelompok[$kunci])) {
    $kelompok[$kunci] = [
      'jenis_item'     => $it->jenis_item,
      'rumus'          => $it->rumus ?? '',
      'warna'          => $it->warna,
      'komponen'       => $it->komponen ?? '',
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

$folder_gambar_barang = 'uploads/barang/';

// Baris spesifikasi yang tampil per kategori rumus (urutan = urutan tampil).
// Kunci baris: nama (Tipe/Jenis), warna, finishing, dimensi, komponen, kusen_daun.
// Kategori yang tidak terdaftar memakai 'default' (tampilan lama).
// Ejaan kunci HARUS sama persis dengan isi kolom barang.kategori_rumus.
$spek_tampil = [
  // Rolling door: Tipe, Warna, Finishing, Dimensi lubang dinding, Komponen
  'rolling_door_baja'      => ['nama', 'warna', 'finishing', 'dimensi', 'komponen'],
  'rolling_door_tahan_api' => ['nama', 'warna', 'finishing', 'dimensi', 'komponen'],

  // Pintu kayu: Jenis, Warna, Komponen, Ketebalan plate aluminium (kusen | daun)
  'pintu_kayu'             => ['nama', 'warna', 'komponen', 'kusen_daun'],

  // Fix unit: Warna, Jenis
  'unit_fix'               => ['warna', 'nama'],

  'default'                => ['nama', 'warna', 'komponen', 'kusen_daun', 'finishing'],
];
// Kategori yang label "nama"-nya "Tipe" (selain itu "Jenis").
$rumus_label_tipe = ['rolling_door_baja', 'rolling_door_tahan_api'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Penawaran <?= html_escape($header->no_surat); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    @media print {

      /* Hindari baris/tabel terputus secara tidak rapi */
      tr {
        page-break-inside: avoid;
        page-break-after: auto;
      }

      thead {
        display: table-header-group;
        /* Otomatis ulang header tabel di halaman berikutnya */
      }

      .total-table,
      .syarat,
      .ttd {
        page-break-inside: avoid;
      }
    }

    :root {
      --brand: #1f7a45;
      --brand-dark: #14512f;
      --brand-light: #eaf5ee;
    }

    * {
      box-sizing: border-box;
    }

    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 12px;
      color: #222;
      margin: 0;
      padding: 24px;
      background: #f2f4f3;
    }

    .sheet {
      max-width: 1000px;
      margin: 0 auto;
      background: #fff;
      padding: 28px 32px;
      border-radius: 6px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, .08);
      position: relative;
    }

    /* TOMBOL CETAK DIPISAH KE POJOK KANAN ATAS */
    .action-bar {
      text-align: right;
      margin-bottom: 10px;
    }

    .btn-cetak {
      background: var(--brand);
      color: #fff;
      border: none;
      padding: 8px 16px;
      border-radius: 5px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: background 0.2s;
    }

    .btn-cetak:hover {
      background: var(--brand-dark);
    }

    /* KOP SURAT (GAMBAR DI KIRI, TEKS DITENGAH) */
    .kop {
      display: flex;
      align-items: center;
      border-bottom: 3px double var(--brand);
      padding-bottom: 14px;
      margin-bottom: 20px;
      position: relative;
    }

    .kop-logo {
      width: 80px;
      height: 80px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .kop-logo img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
    }

    .kop-info {
      flex: 1;
      text-align: center;
      padding-right: 80px;
      /* Menjaga agar teks tetap tepat di tengah halaman */
    }

    .kop-info h2 {
      margin: 0 0 3px 0;
      font-size: 20px;
      font-weight: 700;
      color: var(--brand-dark);
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }

    .kop-info .sub-title {
      font-size: 12px;
      color: #333;
      font-weight: 600;
      margin-bottom: 4px;
    }

    .kop-info .alamat-kontak {
      font-size: 11px;
      color: #555;
      line-height: 1.4;
    }

    .cn {
      display: inline-block;
      font-size: 10px;
      font-weight: 400;
      color: #6b8a76;
    }

    /* INFO SURAT */
    .info-surat {
      width: 100%;
      margin-bottom: 16px;
      font-size: 12px;
    }

    .info-surat td {
      padding: 2px 0;
      vertical-align: top;
    }

    .info-surat td.label {
      width: 170px;
      color: #555;
    }

    .info-surat td.sep {
      width: 12px;
    }

    /* TABEL ITEM */
    table.item-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 4px;
    }

    table.item-table>thead>tr>th,
    table.item-table>tbody>tr>td {
      border: 1px solid #d7dbd9;
      padding: 6px 8px;
      font-size: 11px;
    }

    table.item-table>thead th {
      background: var(--brand-light);
      color: var(--brand-dark);
      text-align: center;
      font-weight: 700;
    }

    table.item-table>tbody>tr>td {
      vertical-align: top;
    }

    table.item-table .num {
      text-align: center;
      white-space: nowrap;
    }

    table.item-table .money {
      text-align: right;
      white-space: nowrap;
    }

    .gambar-cell {
      width: 12%;
      text-align: center;
      padding: 6px !important;
    }

    .gambar-cell img {
      width: 100%;
      max-width: 110px;
      height: auto;
      border-radius: 4px;
      display: block;
      margin: 0 auto;
    }

    .gambar-kosong {
      width: 90px;
      height: 90px;
      margin: 0 auto;
      border-radius: 4px;
      background: var(--brand-light);
      color: #9db8a6;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 26px;
    }

    .deskripsi-cell {
      width: 28%;
    }

    table.spek-table {
      width: 100%;
      border-collapse: collapse;
    }

    table.spek-table td {
      border: none;
      padding: 2px 0;
      font-size: 11px;
      vertical-align: top;
    }

    table.spek-table td.spek-label {
      width: 40%;
      color: #555;
      white-space: nowrap;
      padding-right: 6px;
    }

    table.spek-table .spek-nama {
      font-weight: 700;
      color: var(--brand-dark);
      font-size: 12px;
    }

    table.komponen-table {
      width: 100%;
      border-collapse: collapse;
      margin: 3px 0 4px;
    }

    table.komponen-table th,
    table.komponen-table td {
      border: 1px solid #e3e8e5;
      padding: 3px 5px;
      font-size: 10px;
      text-align: center;
    }

    table.komponen-table th {
      background: #f5f8f6;
      color: #555;
      font-weight: 600;
    }

    .keterangan-block {
      margin-top: 2px;
    }

    /* TOTAL TABLE */
    table.total-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 18px;
    }

    table.total-table td {
      padding: 5px 8px;
      font-size: 12px;
    }

    table.total-table td.tlabel {
      text-align: right;
      width: 82%;
      color: #444;
    }

    table.total-table td.tval {
      text-align: right;
      font-weight: 600;
      width: 18%;
      white-space: nowrap;
    }

    table.total-table tr.grand td {
      border-top: 2px solid var(--brand);
      font-weight: 700;
      font-size: 13px;
      color: var(--brand-dark);
      padding-top: 8px;
    }

    .syarat {
      font-size: 10.5px;
      color: #555;
      margin-bottom: 28px;
    }

    .syarat ol {
      margin: 4px 0 0 16px;
      padding: 0;
    }

    .ttd {
      display: flex;
      justify-content: space-between;
      margin-top: 24px;
    }

    .ttd .box {
      text-align: center;
      width: 220px;
    }

    .ttd .line {
      margin-top: 60px;
      border-top: 1px solid #333;
      padding-top: 4px;
      font-size: 11px;
    }

    @media print {
      body {
        background: #fff;
        padding: 0;
      }

      .sheet {
        box-shadow: none;
        padding: 0;
      }

      .no-print {
        display: none !important;
      }

      table.item-table {
        page-break-inside: auto;
      }

      table.item-table tr {
        page-break-inside: avoid;
      }
    }
  </style>
</head>

<body>

  <div class="sheet">

    <!-- Tombol Aksi (Tidak Ikut Tercetak) -->
    <div class="action-bar no-print">
      <button onclick="window.print()" class="btn-cetak">
        <i class="bi bi-printer"></i> Cetak / Simpan PDF
      </button>
    </div>

    <!-- Kop Surat: Gambar di Kiri, Teks Rata Tengah -->
    <div class="kop">
      <div class="kop-logo">
        <img src="<?= base_url('uploads/logo/Logo.jpeg'); ?>" alt="Logo PT Oupai" onerror="this.style.display='none'">
      </div>
      <div class="kop-info">
        <h2>PT OUPAI PINTU JENDELA INDONESIA</h2>
        <div class="sub-title">Manufaktur Pintu & Jendela <span class="cn">| 门窗制造厂</span></div>
        <div class="alamat-kontak">
          Jl. Prof. Dr. Hamka No.45, Tambakaji, 50185, Kec. Ngaliyan Kota Semarang, Jawa Tengah, Indonesia<br>
          Telp: 085819570958 &bull; Email: ptoupaipintujendelaindonesia@gmail.com
        </div>
      </div>
    </div>

    <!-- Info Surat -->
    <table class="info-surat">
      <tr>
        <td class="label">Tanggal / 日期 </td>
        <td class="sep">:</td>
        <td><?= date('d/m/Y', strtotime($header->tanggal)); ?></td>
      </tr>
      <tr>
        <td class="label">No. Surat / 参考编号</td>
        <td class="sep">:</td>
        <td><?= html_escape($header->no_surat); ?></td>
      </tr>
      <tr>
        <td class="label">Nama Perusahaan Klien / 客户公司名称 </td>
        <td class="sep">:</td>
        <td><?= html_escape($header->customer ?? '-'); ?></td>
      </tr>
      <tr>
        <td class="label">Informasi Kontak / 联系信息</td>
        <td class="sep">:</td>
        <td><?= !empty($header->customer_telepon) ? html_escape($header->customer_telepon) : '-'; ?></td>
      </tr>
      <tr>
        <td class="label">Alamat Surat / 邮寄地址</td>
        <td class="sep">:</td>
        <td><?= !empty($header->customer_alamat) ? html_escape($header->customer_alamat) : '-'; ?></td>
      </tr>
    </table>

    <!-- Tabel Item -->
    <table class="item-table">
      <thead>
        <tr>
          <th>GAMBAR<br><span class="cn">图片</span></th>
          <th>Deskripsi<br><span class="cn">描述</span></th>
          <th>Lebar (mm)<br><span class="cn">宽 (mm)</span></th>
          <th>Tinggi (mm)<br><span class="cn">高 (mm)</span></th>
          <th>Total Luas (m²)<br><span class="cn">总面积 (m²)</span></th>
          <th>Qty<br><span class="cn">数量</span></th>
          <th>Harga / Unit</th>
          <th>Total Harga<br><span class="cn">总计</span></th>
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
                  <?php
                  $rumus_grp  = $grp['rumus'];
                  $baris_spek = $spek_tampil[$rumus_grp] ?? $spek_tampil['default'];
                  $label_nama = in_array($rumus_grp, $rumus_label_tipe, TRUE) ? 'Tipe' : 'Jenis';
                  ?>
                  <table class="spek-table">
                    <?php foreach ($baris_spek as $kolom): ?>

                      <?php if ($kolom === 'nama'): ?>
                        <tr>
                          <td class="spek-label"><?= $label_nama; ?><span class="cn">类型</span></td>
                          <td class="spek-nama"><?= html_escape($grp['jenis_item']); ?></td>
                        </tr>

                      <?php elseif ($kolom === 'warna' && !empty($grp['warna'])): ?>
                        <tr>
                          <td class="spek-label">Warna<span class="cn">颜色</span></td>
                          <td><?= html_escape($grp['warna']); ?></td>
                        </tr>

                      <?php elseif ($kolom === 'komponen' && !empty($grp['komponen'])): ?>
                        <tr>
                          <td class="spek-label">Komponen<span class="cn">成分</span></td>
                          <td><?= html_escape($grp['komponen']); ?></td>
                        </tr>

                      <?php elseif ($kolom === 'dimensi'): ?>
                        <tr>
                          <td class="spek-label" style="vertical-align: middle; width: 35%;">
                            Dimensi lubang dinding<br>
                            <span class="cn">墙体开孔尺寸</span>
                          </td>
                          <td colspan="2">
                            <table class="komponen-table" style="width: 100%;">
                              <thead>
                                <tr>
                                  <th>Lebar (mm) <br><span class="cn">宽度 (mm)</span></th>
                                  <th>Tinggi (mm) <br><span class="cn">高度 (mm)</span></th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($grp['baris'] as $br): ?>
                                  <tr>
                                    <td><?= (int) $br->lebar_mm; ?></td>
                                    <td><?= (int) $br->tinggi_mm; ?></td>
                                  </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          </td>
                        </tr>

                      <?php elseif ($kolom === 'kusen_daun' && (!empty($grp['komponen_kusen']) || !empty($grp['komponen_daun']))): ?>
                        <tr>
                          <td class="spek-label" style="vertical-align: middle; width: 35%;">
                            Ketebalan Plate<br> Aluminium <br>
                            <span class="cn">铝板厚度</span>
                          </td>
                          <td colspan="2">
                            <table class="komponen-table" style="width: 100%;">
                              <thead>
                                <tr>
                                  <th>Kusen (mm) <br><span class="cn">门框 (mm)</span></th>
                                  <th>Daun (mm) <br><span class="cn">叶子 (mm)</span></th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr>
                                  <td><?= html_escape($grp['komponen_kusen'] ?: '-'); ?></td>
                                  <td><?= html_escape($grp['komponen_daun'] ?: '-'); ?></td>
                                </tr>
                              </tbody>
                            </table>
                          </td>
                        </tr>

                      <?php elseif ($kolom === 'finishing' && !empty($grp['finishing'])): ?>
                        <tr>
                          <td class="spek-label">Finishing<span class="cn">精加工</span></td>
                          <td><?= html_escape($grp['finishing']); ?></td>
                        </tr>
                      <?php endif; ?>

                    <?php endforeach; ?>

                    <?php if (!empty($grp['keterangan'])): ?>
                      <tr>
                        <td class="spek-label" style="vertical-align: top; width: 35%;">
                          Keterangan <br>
                          <span class="cn">信息</span>
                        </td>
                        <td class="keterangan-block" style="vertical-align: top;">
                          <?= nl2br(html_escape(implode("\n", $grp['keterangan']))); ?>
                        </td>
                      </tr>
                    <?php endif; ?>
                  </table>
                </td>
              <?php endif; ?>
              <td class="num"><?= (int) $it->lebar_mm; ?></td>
              <td class="num"><?= (int) $it->tinggi_mm; ?></td>
              <td class="num"><?= number_format((float) ($it->luas_billing_m2 ?? $it->luas_m2), 2); ?></td>
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
        <td class="tlabel">Jumlah unit / 单元数量 : <?= $jumlah_unit; ?></td>
        <td class="tval">Subtotal: Rp <?= number_format((float) $header->subtotal, 0, ',', '.'); ?></td>
      </tr>
      <?php if ((float) ($header->biaya_pasang ?? 0) > 0): ?>
        <tr>
          <td class="tlabel">Biaya Pemasangan / 安装费</td>
          <td class="tval">Rp <?= number_format((float) $header->biaya_pasang, 0, ',', '.'); ?></td>
        </tr>
      <?php endif; ?>
      <?php if ((float) ($header->biaya_ongkir ?? 0) > 0): ?>
        <tr>
          <td class="tlabel">Biaya Pengiriman / 运费</td>
          <td class="tval">Rp <?= number_format((float) $header->biaya_ongkir, 0, ',', '.'); ?></td>
        </tr>
      <?php endif; ?>
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
    <?php
    // Teks termin pembayaran sesuai pilihan saat cetak (?termin=50_50 | 100).
    // Tambah opsi baru di sini + di whitelist Penawaran::cetak().
    $termin_teks = [
      '50_50' => 'Pembayaran dibayarkan 50% saat memesan dan 50% sebelum barang dikirim / 付款方式为：下单时支付50%，发货前支付剩余50%。',
      '100'   => 'Pembayaran dibayarkan 100% saat memesan / 付款方式为：下单时一次性支付100%。',
    ];
    $teks_bayar = $termin_teks[isset($termin) && isset($termin_teks[$termin]) ? $termin : '50_50'];
    $is_ppn_exist = !empty($header->ppn_persen) && $header->ppn_persen !== 'null' && $header->ppn_persen > 0;
    ?>

    <?php
    // Biaya pemasangan & pengiriman: NULL = tidak dipilih ("belum termasuk"),
    // angka (termasuk 0) = dipilih ("sudah termasuk").
    $ada_pasang = (($header->biaya_pasang ?? null) !== null);
    $ada_ongkir = (($header->biaya_ongkir ?? null) !== null);

    if ($ada_pasang && $ada_ongkir) {
      $teks_biaya = 'Harga yang ditawarkan sudah termasuk biaya pemasangan dan pengiriman / 所报价格已包含安装和运费。';
    } elseif ($ada_pasang) {
      $teks_biaya = 'Harga yang ditawarkan sudah termasuk biaya pemasangan, belum termasuk biaya pengiriman / 所报价格已包含安装费，不含运费。';
    } elseif ($ada_ongkir) {
      $teks_biaya = 'Harga yang ditawarkan sudah termasuk biaya pengiriman, belum termasuk biaya pemasangan / 所报价格已包含运费，不含安装费。';
    } else {
      $teks_biaya = 'Harga yang ditawarkan belum termasuk biaya pemasangan dan pengiriman / 所报价格不包含安装和运费。';
    }

    // Hanya rekening tujuan yang berbeda antara penawaran ber-PPN dan tanpa PPN
    $teks_bank = $is_ppn_exist
      ? 'Pembayaran transfer ke Bank Mandiri 1360077787882 atas nama PT OUPAI PINTU JENDELA INDONESIA.'
      : 'Pembayaran transfer ke Bank BCA 8715845471 atas nama MAHMUDI / 请将款项转账至 BCA 银行 8715845471 账户，收款人为 MAHMUDI。';
    ?>

    <div class="syarat">
      <strong>Syarat &amp; Ketentuan:</strong>
      <ol>
        <li><?= $teks_bayar; ?></li>
        <li><?= $teks_bank; ?></li>
        <li>Barang pesanan yang sudah diproduksi tidak dapat dibatalkan / 已生产完毕的订单商品无法取消。</li>
        <li>Penawaran ini tidak bisa untuk dipilih pilih itemnya, apabila ada perubahan dibuatkan penawaran baru / 此优惠不适用于特定商品。如有变更，我们将提供新的优惠。 </li>
        <li>Toleransi untuk produksi &plusmn; 2.0mm / 生产容差 &plusmn; 2.0mm </li>
        <li><?= $teks_biaya; ?></li>
      </ol>
    </div>

    <?php if (!empty($header->catatan)): ?>
      <p><strong>Catatan tambahan:</strong> <?= nl2br(html_escape($header->catatan)); ?></p>
    <?php endif; ?>

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