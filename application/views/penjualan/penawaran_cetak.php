<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php

// Luas ditampilkan apa adanya (tanpa pembulatan ke 2 desimal): minimal 2 desimal,
// maksimal 4 desimal sesuai presisi yang disimpan. Contoh: 34.72, 10.00, 7.0067
$fmt_luas = function ($v) {
  $s   = number_format((float) $v, 4, '.', '');
  $dec = max(2, strlen(rtrim(substr($s, strpos($s, '.') + 1), '0')));
  return number_format((float) $v, $dec, '.', '');
};

// Ukuran yang ditampilkan di cetak. Rolling door: lebar +200 mm & tinggi +600 mm
// (sama dengan dasar perhitungan luas penagihan). Kategori lain tampil apa adanya.
$ukuran_cetak = function ($row) {
  $l = (int) $row->lebar_mm;
  $t = (int) $row->tinggi_mm;
  if (preg_match('/^ro{1,2}l{1,2}ing_(door_)?(tahan_api|baja)$/', (string) ($row->rumus ?? ''))) {
    $l += 200;
    $t += 600;
  }
  return [$l, $t];
};

// Kelompokkan baris item per "jenis barang"
$kelompok = [];

foreach ($items as $it) {
  // Barang master pun dipisah per kombinasi komponen/books penutup/kelengkapan,
  // karena ketiganya sekarang bisa diedit per item.
  $kunci = !empty($it->id_barang)
    ? 'b' . $it->id_barang . '_' . md5(($it->komponen ?? '') . '|' . ($it->books_penutup ?? '') . '|' . ($it->kelengkapan ?? ''))
    : 't' . md5(
      ($it->jenis_item ?? '') . '|' . ($it->warna ?? '') . '|' . ($it->komponen ?? '') . '|' .
        ($it->finishing ?? '') . '|' . ($it->komponen_kusen ?? '') . '|' . ($it->tipe_motor ?? '')  .
        '|' . ($it->komponen_daun ?? '') . '|' . ($it->input_power ?? '') . '|' . ($it->voltage ?? '')
        . '|' . ($it->books_penutup ?? '') . '|' . ($it->kelengkapan ?? '')
    );

  if (!isset($kelompok[$kunci])) {
    $kelompok[$kunci] = [
      'jenis_item'     => $it->jenis_item,
      // Samakan ejaan kategori rolling door supaya cocok dengan kunci $spek_tampil
      'rumus'          => preg_match('/^ro{1,2}l{1,2}ing_(door_)?baja$/', (string) ($it->rumus ?? ''))
        ? 'rolling_door_baja'
        : (preg_match('/^ro{1,2}l{1,2}ing_(door_)?tahan_api$/', (string) ($it->rumus ?? ''))
          ? 'rolling_door_tahan_api'
          : ($it->rumus ?? '')),
      'warna'          => $it->warna,
      'komponen'       => $it->komponen ?? '',
      'finishing'      => $it->finishing,
      'komponen_kusen' => $it->komponen_kusen,
      'komponen_daun'  => $it->komponen_daun,
      'tipe_motor'     => $it->tipe_motor,
      'input_power'    => $it->input_power,
      'voltage'        => $it->voltage,
      'books_penutup'  => $it->books_penutup,
      'kelengkapan'    => $it->kelengkapan,
      'gambar'         => $it->gambar_barang ?? null,
      'keterangan'     => [],
      'baris'          => [],
    ];
  }

  // Keterangan per item (penawaran_detail.keterangan) - berlaku untuk semua kategori
  // (rolling door, unit fix, pintu, jendela, dst.). Hanya yang tidak kosong yang dicetak.
  $ket_item = trim((string) ($it->keterangan ?? ''));
  if ($ket_item !== '' && !in_array($ket_item, $kelompok[$kunci]['keterangan'], TRUE)) {
    $kelompok[$kunci]['keterangan'][] = $ket_item;
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
  'rolling_door_baja'      => [
    'nama',
    'warna',
    'finishing',
    'dimensi',
    'komponen',
    'tipe_motor',
    'input_power',
    'voltage',
    'books_penutup',
    'kelengkapan'
  ],
  'rolling_door_tahan_api' => [
    'nama',
    'warna',
    'finishing',
    'dimensi',
    'komponen',
    'tipe_motor',
    'input_power',
    'voltage',
    'books_penutup',
    'kelengkapan'
  ],

  // Pintu kayu: Jenis, Warna, Komponen, Ketebalan plate aluminium (kusen | daun)
  'pintu_kayu'             => ['nama', 'warna', 'komponen', 'kusen_daun'],

  // Fix unit: Warna, Jenis
  'unit_fix'               => ['warna', 'nama', 'komponen', 'kusen_daun', 'finishing'],


  'default'                => ['nama', 'warna', 'komponen', 'kusen_daun', 'finishing'],
];
// Kategori yang label "nama"-nya "Tipe" (selain itu "Jenis").
$rumus_label_tipe = ['rolling_door_baja', 'rolling_door_tahan_api'];

// Render tabel spesifikasi untuk daftar baris tertentu ($kolom_list).
// Dipakai dua kali bila ada Books Penutup: bagian atas (sampai Dimensi lubang
// dinding) dan bagian bawah (Komponen dst.), dipisah oleh baris Ukuran Books Penutup.
$render_spek = function ($kolom_list, $grp, $label_nama, $tampil_ket) {
?>
  <table class="spek-table">
    <?php foreach ($kolom_list as $kolom): ?>

      <?php if ($kolom === 'nama'): ?>
        <tr>
          <td class="spek-label"><?= $label_nama; ?><span class="cn">/ 类型</span></td>
          <td class="spek-nama"><?= html_escape($grp['jenis_item']); ?></td>
        </tr>

      <?php elseif ($kolom === 'warna' && !empty($grp['warna'])): ?>
        <tr>
          <td class="spek-label">Warna<span class="cn">/ 颜色</span></td>
          <td><?= html_escape($grp['warna']); ?></td>
        </tr>

      <?php elseif ($kolom === 'komponen' && !empty($grp['komponen'])): ?>
        <tr>
          <td class="spek-label">Komponen<span class="cn">/ 成分</span></td>
          <td><?= nl2br(html_escape($grp['komponen'])); ?></td>
        </tr>

      <?php elseif ($kolom === 'dimensi'): ?>
        <tr>
          <td class="spek-label" style="vertical-align: middle; width: 35%;">
            Dimensi lubang dinding<br>
            <span class="cn">/ 墙体开孔尺寸</span>
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
            <span class="cn">/ 铝板厚度</span>
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
          <td class="spek-label">Finishing<span class="cn">/ 精加工</span></td>
          <td><?= html_escape($grp['finishing']); ?></td>
        </tr>
      <?php elseif ($kolom === 'tipe_motor' && !empty($grp['tipe_motor'])): ?>
        <tr>
          <td class="spek-label">Tipe Motor</td>
          <td><?= html_escape($grp['tipe_motor']); ?></td>
        </tr>
      <?php elseif ($kolom === 'input_power' && !empty($grp['input_power'])): ?>
        <tr>
          <td class="spek-label">Input Power</td>
          <td><?= html_escape($grp['input_power']); ?></td>
        </tr>
      <?php elseif ($kolom === 'voltage' && !empty($grp['voltage'])): ?>
        <tr>
          <td class="spek-label">Voltage</td>
          <td><?= html_escape($grp['voltage']); ?></td>
        </tr>
      <?php elseif ($kolom === 'books_penutup' && !empty($grp['books_penutup'])): ?>
        <tr>
          <td class="spek-label">Books Penutup<span class="cn">/ 卷帘门罩壳</span></td>
          <td><?= nl2br(html_escape($grp['books_penutup'])); ?></td>
        </tr>
      <?php elseif ($kolom === 'kelengkapan' && !empty($grp['kelengkapan'])): ?>
        <tr>
          <td class="spek-label">Kelengkapan</td>
          <td><?= nl2br(html_escape($grp['kelengkapan'])); ?></td>
        </tr>
      <?php endif; ?>

    <?php endforeach; ?>

    <?php if ($tampil_ket && !empty($grp['keterangan'])): ?>
      <tr>
        <td class="spek-label" style="vertical-align: top; width: 35%;">
          Keterangan <br>
          <span class="cn">/ 信息</span>
        </td>
        <td class="keterangan-block" style="vertical-align: top;">
          <?= nl2br(html_escape(implode("\n", $grp['keterangan']))); ?>
        </td>
      </tr>
    <?php endif; ?>
  </table>
<?php
};
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

          // Kumpulkan aksesoris dari semua item dalam kelompok ini. Baris aksesoris
          // ditampilkan setelah baris barang, jadi kolom gambar harus mencakup keduanya.
          $books_rows = [];
          $aks_rows   = [];
          $kc_rows   = [];
          $inst_rows  = [];
          foreach ($grp['baris'] as $br_aks) {
            // Books Penutup (rolling door): luas x qty x harga
            $b_luas = (float) ($br_aks->luas_m2_books ?? 0);
            $b_qty  = (int) ($br_aks->qty_books ?? 0);
            $b_hrg  = (float) ($br_aks->harga_m2_books ?? 0);
            if ($b_luas > 0 && $b_qty > 0) {
              $books_rows[] = (object) [
                'nama'      => 'Books Penutup',
                'lebar_mm'  => $br_aks->lebar_m2_books,
                'tinggi_mm' => $br_aks->tinggi_m2_books,
                'luas'      => $b_luas,
                'qty'       => $b_qty,
                'harga'     => $b_hrg,
                'total'     => $b_luas * $b_qty * $b_hrg,
              ];
            }
            // Biaya Instalasi (rolling door): luas billing x qty x tarif pasang per m²
            $i_qty = (float) ($br_aks->qty_instalasi ?? 0);
            $i_hrg = (float) ($br_aks->harga_m2_instalasi ?? 0);
            if ($i_qty > 0 || $i_hrg > 0) {
              $i_luas = (float) ($br_aks->luas_billing_m2 ?? $br_aks->luas_m2 ?? 0);
              $inst_rows[] = (object) [
                'luas'  => $i_luas,
                'qty'   => $i_qty,
                'harga' => $i_hrg,
                'total' => $i_luas * $i_qty * $i_hrg,
              ];
            }
            foreach (($br_aks->aksesoris ?? []) as $ak_row) {
              $aks_rows[] = (object) [
                'nama'      => $ak_row->nama,
                'lebar_mm'  => null,
                'tinggi_mm' => null,
                'luas'      => null,
                'qty'       => (int) $ak_row->qty,
                'harga'     => (float) $ak_row->harga,
                'total'     => isset($ak_row->total_harga) ? (float) $ak_row->total_harga : (int) $ak_row->qty * (float) $ak_row->harga,
              ];
            }
            foreach (($br_aks->kaca ?? []) as $kaca_row) {
              $kc_rows[] = (object) [
                'nama'      => $kaca_row->nama,
                'lebar_mm'  => null,
                'tinggi_mm' => null,
                'luas'      => (float) $kaca_row->luas,
                'qty'       => null,
                'harga'     => (float) $kaca_row->harga,
                'total'     => isset($kaca_row->total_harga) ? (float) $kaca_row->total_harga : (float) $kaca_row->luas * (float) $ak_row->harga,
              ];
            }
          }
          // Susunan baris: baris barang -> baris Ukuran Books Penutup (tepat di bawah
          // Dimensi lubang dinding) -> sisa spesifikasi (Komponen dst.) -> aksesoris paling bawah.
          $baris_spek_grp = $spek_tampil[$grp['rumus']] ?? $spek_tampil['default'];
          $ada_bawah  = !empty($books_rows) && in_array('dimensi', $baris_spek_grp, TRUE);
          $spek_atas  = $baris_spek_grp;
          $spek_bawah = [];
          if ($ada_bawah) {
            $pos_dim    = array_search('dimensi', $baris_spek_grp, TRUE);
            $spek_atas  = array_slice($baris_spek_grp, 0, $pos_dim + 1);
            $spek_bawah = array_slice($baris_spek_grp, $pos_dim + 1);
          }
          $jumlah_baris_total = $jumlah_baris + count($books_rows) + ($ada_bawah ? 1 : 0) + count($aks_rows) + count($inst_rows);

          $baris_pertama = TRUE;
          ?>
          <?php foreach ($grp['baris'] as $it): $jumlah_unit += (int) $it->qty; ?>
            <tr>
              <?php if ($baris_pertama): ?>
                <td class="gambar-cell" rowspan="<?= $jumlah_baris_total; ?>">
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
                  <?php $render_spek($spek_atas, $grp, $label_nama, !$ada_bawah); ?>
                </td>
              <?php endif; ?>
              <?php list($uk_l, $uk_t) = $ukuran_cetak($it); ?>
              <td class="num"><?= $uk_l; ?></td>
              <td class="num"><?= $uk_t; ?></td>
              <td class="num"><?= $fmt_luas($it->luas_billing_m2 ?? $it->luas_m2); ?></td>
              <td class="num"><?= (int) $it->qty; ?></td>
              <td class="money">Rp <?= number_format((float) $it->harga_unit, 0, ',', '.'); ?></td>
              <?php
              // total_harga di database sudah termasuk aksesoris; di baris barang
              // tampilkan bagian barangnya saja (aksesoris punya baris sendiri di bawah).
              $total_aks_it = 0;
              foreach (($it->aksesoris ?? []) as $ak_it) {
                $total_aks_it += isset($ak_it->total_harga) ? (float) $ak_it->total_harga : (int) $ak_it->qty * (float) $ak_it->harga;
              }
              // Books penutup juga sudah termasuk di total_harga; tampil di baris sendiri
              $total_aks_it += (float) ($it->luas_m2_books ?? 0) * (int) ($it->qty_books ?? 0) * (float) ($it->harga_m2_books ?? 0);
              // Biaya instalasi juga sudah termasuk di total_harga; tampil di baris sendiri
              $total_aks_it += (float) ($it->luas_billing_m2 ?? $it->luas_m2 ?? 0) * (float) ($it->qty_instalasi ?? 0) * (float) ($it->harga_m2_instalasi ?? 0);
              $total_barang_it = max(0, (float) $it->total_harga - $total_aks_it);
              ?>
              <td class="money">Rp <?= number_format($total_barang_it, 0, ',', '.'); ?></td>
            </tr>
            <?php $baris_pertama = FALSE; ?>
          <?php endforeach; ?>

          <?php /* Baris Ukuran Books Penutup: label + lebar/tinggi di deskripsi, luas/qty/harga di kolom angka */ ?>
          <?php foreach ($books_rows as $bk): ?>
            <tr class="books-row">
              <td class="deskripsi-cell">
                <table class="spek-table">
                  <tr>
                    <td class="spek-label" style="vertical-align: middle; width: 35%; white-space: normal;">
                      Ukuran Books Penutup<br><span class="cn">/ 卷帘门罩壳尺寸</span>
                    </td>
                    <td>
                      <table class="komponen-table" style="width: 100%;">
                        <thead>
                          <tr>
                            <th>Lebar (mm) <br><span class="cn">宽度 (mm)</span></th>
                            <th>Tinggi (mm) <br><span class="cn">高度 (mm)</span></th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <td><?= $bk->lebar_mm !== null ? (int) round((float) $bk->lebar_mm) : ''; ?></td>
                            <td><?= $bk->tinggi_mm !== null ? (int) round((float) $bk->tinggi_mm) : ''; ?></td>
                          </tr>
                        </tbody>
                      </table>
                    </td>
                  </tr>
                </table>
              </td>
              <td class="num"><?= $bk->lebar_mm !== null ? (int) round((float) $bk->lebar_mm) : ''; ?></td>
              <td class="num"><?= $bk->tinggi_mm !== null ? (int) round((float) $bk->tinggi_mm) : ''; ?></td>
              <td class="num"><?= $fmt_luas($bk->luas); ?></td>
              <td class="num"><?= (int) $bk->qty; ?></td>
              <td class="money">Rp <?= number_format((float) $bk->harga, 0, ',', '.'); ?></td>
              <td class="money">Rp <?= number_format((float) $bk->total, 0, ',', '.'); ?></td>
            </tr>
          <?php endforeach; ?>

          <?php /* Sisa spesifikasi (Komponen dst. + Keterangan) setelah baris Books Penutup */ ?>
          <?php if ($ada_bawah): ?>
            <tr>
              <td class="deskripsi-cell">
                <?php $render_spek($spek_bawah, $grp, $label_nama, TRUE); ?>
              </td>
              <?php /* Sel kosong dipisah per kolom (bukan colspan) supaya garis kolom tetap menyambung sampai bawah */ ?>
              <td class="num"></td>
              <td class="num"></td>
              <td class="num"></td>
              <td class="num"></td>
              <td class="money"></td>
              <td class="money"></td>
            </tr>
          <?php endif; ?>

          <?php /* Baris aksesoris (paling bawah): nama -> Deskripsi, qty -> Qty, harga -> Harga / Unit */ ?>
          <?php foreach ($aks_rows as $ak): ?>
            <tr class="aksesoris-row">
              <td class="deskripsi-cell">
                <strong>Aksesoris<span class="cn">/ 配件</span>:</strong>
                <?= html_escape($ak->nama); ?>
              </td>
              <td class="num"></td>
              <td class="num"></td>
              <td class="num"></td>
              <td class="num"><?= (int) $ak->qty; ?></td>
              <td class="money">Rp <?= number_format((float) $ak->harga, 0, ',', '.'); ?></td>
              <td class="money">Rp <?= number_format((float) $ak->total, 0, ',', '.'); ?></td>
            </tr>
          <?php endforeach; ?>

          <?php /* Baris kaca (paling bawah): nama -> Deskripsi, qty -> Qty, harga -> Harga / Unit */ ?>
          <?php foreach ($kc_rows as $kaca): ?>
            <tr class="kaca-row">
              <td class="deskripsi-cell" colspan="4" style="text-align: right;">
                <?= html_escape($kaca->nama); ?>
              </td>
             
              <td class="num"><?=  $fmt_luas($kaca->luas) ; ?></td>
              <td class="num"></td>
              <td class="money">Rp <?= number_format((float) $kaca->harga, 0, ',', '.'); ?></td>
              <td class="money">Rp <?= number_format((float) $kaca->total, 0, ',', '.'); ?></td>
            </tr>
          <?php endforeach; ?>

          <?php /* Baris Biaya Instalasi rolling door (paling bawah). Kolom hanya terisi bila datanya ada:
                   luas+qty+harga/m² -> total; kolom yang datanya kosong dibiarkan kosong */ ?>
          <?php foreach ($inst_rows as $ins): ?>
            <tr class="instalasi-row">
              <td class="deskripsi-cell">
                <strong>Biaya Instalasi<span class="cn">/ 安装费</span></strong>
              </td>
              <td class="num"></td>
              <td class="num"></td>
              <td class="num"><?= ($ins->qty > 0 && $ins->harga > 0 && $ins->luas > 0) ? $fmt_luas($ins->luas) : ''; ?></td>
              <td class="num"><?= $ins->qty > 0 ? (int) $ins->qty : ''; ?></td>
              <td class="money"><?= $ins->harga > 0 ? 'Rp ' . number_format($ins->harga, 0, ',', '.') : ''; ?></td>
              <td class="money"><?= $ins->total > 0 ? 'Rp ' . number_format($ins->total, 0, ',', '.') : ''; ?></td>
            </tr>
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
            <td class="tlabel">Biaya Pemasangan /  安装费用</td>
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