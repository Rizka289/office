<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php

// ---- MODE EXCEL (Penawaran::excel) ----
// View yang sama dipakai untuk export .xls. Bedanya di mode Excel:
// - tanpa kolom GAMBAR & tombol cetak,
// - spesifikasi ditulis sebagai baris teks (Excel tidak bisa membaca tabel di dalam tabel),
// - sel angka diberi x:num (nilai mentah) supaya terbaca sebagai ANGKA (bisa dijumlah)
//   apa pun setelan regional komputer pengguna.
$mode_excel = !empty($mode_excel);
$xn = function ($v, $dec = 2) use ($mode_excel) {
  if (!$mode_excel || $v === null || $v === '' || (float) $v == 0) return '';
  return ' x:num="' . number_format((float) $v, $dec, '.', '') . '"';
};
// Pindah baris di dalam satu sel Excel
$br_xl = $mode_excel ? '<br style="mso-data-placement:same-cell">' : '<br>';
$nl_xl = function ($s) use ($br_xl) {
  return str_replace("\n", $br_xl, html_escape(str_replace("\r", '', trim((string) $s))));
};

// Luas 2 angka di belakang koma, pembulatan biasa (angka ke-3 >= 5 naik), sama dengan form & server.
// Contoh: 0.4767 -> 0.48, 1.752 -> 1.75, 10 -> 10.00
$luas2 = function ($v) {
  $v = (float) $v;
  if ($v <= 0) return 0;
  return round(round($v * 1000000) / 10000) / 100;
};
$fmt_luas = function ($v) use ($luas2) {
  return number_format($luas2($v), 2, '.', '');
};

// TOTAL luas = luas per unit x qty, BARU dibulatkan 2 angka (sama dengan Penawaran::_total_luas).
// 1095 x 800 x 2 = 1.752 -> 1.75
$total_luas = function ($luas_unit, $qty) use ($luas2) {
  return $luas2((float) $luas_unit * (float) $qty);
};

// Ukuran yang ditampilkan di cetak = dasar perhitungan luas penagihan.
// Rolling door (tahan api / baja / otomatis) sesuai rumus ukuran item:
//   l200_t600 (default) : lebar +200 mm, tinggi +600 mm
//   t500                : lebar tetap,   tinggi +500 mm
// Kategori lain tampil apa adanya.
$ukuran_cetak = function ($row) {
  $l = (int) $row->lebar_mm;
  $t = (int) $row->tinggi_mm;
  if (preg_match('/^ro{1,2}l{1,2}ing_(door_)?(tahan_api|baja|otomatis)$/', (string) ($row->rumus ?? ''))) {
    if (($row->rumus_ukuran ?? '') === 't500') {
      $t += 500;
    } else {
      $l += 200;
      $t += 600;
    }
  }
  return [$l, $t];
};

// CETAK PER PRODUK: setiap item penawaran tampil sebagai blok sendiri
// (gambar, deskripsi, ukuran, aksesoris & keterangannya masing-masing),
// walaupun produknya sama. Ubah ke TRUE kalau mau kembali digabung
// (produk yang sama jadi satu deskripsi dengan beberapa baris ukuran).
$gabung_produk_sama = FALSE;

// Kelompokkan baris item (per item, atau per "jenis barang" kalau digabung)
$kelompok = [];
$items_aks_saja = []; // item tanpa nama barang (penawaran manual: hanya aksesoris / kaca)

foreach ($items as $idx_item => $it) {
  if (trim((string) ($it->jenis_item ?? '')) === '') {
    $items_aks_saja[] = $it;
    continue;
  }
  // Barang master pun dipisah per kombinasi komponen/books penutup/kelengkapan,
  // karena ketiganya sekarang bisa diedit per item.
  $kunci = !$gabung_produk_sama
    ? 'i' . $idx_item . '_' . ($it->id ?? '')
    : (!empty($it->id_barang)
    ? 'b' . $it->id_barang . '_' . md5(($it->komponen ?? '') . '|' . ($it->books_penutup ?? '') . '|' . ($it->kelengkapan ?? '') . '|' . ($it->tebal ?? ''))
    : 't' . md5(
      ($it->jenis_item ?? '') . '|' . ($it->warna ?? '') . '|' . ($it->komponen ?? '') . '|' .
        ($it->finishing ?? '') . '|' . ($it->komponen_kusen ?? '') . '|' . ($it->tipe_motor ?? '')  .
        '|' . ($it->komponen_daun ?? '') . '|' . ($it->input_power ?? '') . '|' . ($it->voltage ?? '')
        . '|' . ($it->books_penutup ?? '') . '|' . ($it->kelengkapan ?? '') . '|' . ($it->foto ?? '')
        . '|' . ($it->tebal ?? '')
    ));

  if (!isset($kelompok[$kunci])) {
    $kelompok[$kunci] = [
      'jenis_item'     => $it->jenis_item,
      // Samakan ejaan kategori rolling door supaya cocok dengan kunci $spek_tampil
      'rumus'          => preg_match('/^ro{1,2}l{1,2}ing_(door_)?(baja|tahan_api|otomatis)$/', (string) ($it->rumus ?? ''), $m_rd)
        ? 'rolling_door_' . $m_rd[2]
        : ($it->rumus ?? ''),
      'warna'          => $it->warna,
      'komponen'       => $it->komponen ?? '',
      'finishing'      => $it->finishing,
      'komponen_kusen' => $it->komponen_kusen,
      'komponen_daun'  => $it->komponen_daun,
      'tebal'          => $it->tebal ?? '',
      'tipe_motor'     => $it->tipe_motor,
      'input_power'    => $it->input_power,
      'voltage'        => $it->voltage,
      'books_penutup'  => $it->books_penutup,
      'kelengkapan'    => $it->kelengkapan,
      'gambar'         => $it->gambar_barang ?? null,
      'foto'           => $it->foto ?? null,
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

// ---- Penawaran MANUAL: foto hanya dianggap ada kalau file benar-benar ada di server.
// Kolom GAMBAR hanya dicetak kalau minimal ada 1 foto (item / aksesoris); kalau tidak,
// tabel langsung dimulai dari Deskripsi. Baris tanpa foto dibiarkan kosong (tanpa ikon).
$is_manual_cetak = (($header->tipe ?? 'rumus') === 'manual');
$foto_ada = function ($nama) {
  $nama = trim((string) $nama);
  return $nama !== '' && preg_match('/^[A-Za-z0-9_.-]+$/', $nama) && is_file(FCPATH . 'uploads/penawaran/' . $nama);
};
// ---- Foto di Excel: file disematkan ke dalam .xls (format MHT) oleh Penawaran::excel().
// $foto_xl (ArrayObject dari controller) mengumpulkan daftar file; <img> cukup menyebut namanya.
$foto_xl = (isset($foto_xl) && $foto_xl instanceof ArrayObject) ? $foto_xl : new ArrayObject();
$img_xl = function ($rel_path, $alt, $maks = 80) use ($foto_xl) {
  // Hanya file di dalam folder uploads/ (cegah path traversal)
  $akar = realpath(FCPATH . 'uploads');
  $abs  = realpath(FCPATH . ltrim((string) $rel_path, '/'));
  if ($akar === false || $abs === false || strpos($abs, $akar . DIRECTORY_SEPARATOR) !== 0 || !is_file($abs)) {
    return '';
  }
  $info = @getimagesize($abs);
  if (!$info) return '';
  // File yang sama cukup disematkan sekali
  foreach ($foto_xl as $f) {
    if ($f['path'] === $abs) {
      $nama = $f['nama']; $lw = $f['w']; $lh = $f['h'];
      break;
    }
  }
  if (!isset($nama)) {
    $data = null;
    $ext  = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif'][$info[2]] ?? null;
    if ($ext !== null) {
      $data = file_get_contents($abs);
    } elseif (function_exists('imagecreatefromstring')) {
      // webp / bmp dll. -> PNG supaya terbaca di semua versi Excel
      $gd = @imagecreatefromstring(file_get_contents($abs));
      if ($gd) {
        ob_start(); imagepng($gd); $data = ob_get_clean(); imagedestroy($gd);
        $ext = 'png';
      }
    }
    if ($data === null || $data === false) return '';
    $nama = 'foto' . (count($foto_xl) + 1) . '.' . $ext;
    $lw = $info[0]; $lh = $info[1];
    $foto_xl[] = ['nama' => $nama, 'path' => $abs, 'data' => $data, 'w' => $lw, 'h' => $lh,
      'mime' => ['jpg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'][$ext]];
  }
  // Perkecil proporsional supaya muat di kotak $maks x $maks px
  $skala = min(1, $maks / max(1, $lw), $maks / max(1, $lh));
  $w = max(1, (int) round($lw * $skala));
  $h = max(1, (int) round($lh * $skala));
  return '<img src="penawaran_files/' . $nama . '" width="' . $w . '" height="' . $h . '" alt="' . html_escape($alt) . '">';
};

$ada_foto_manual = false;
if ($is_manual_cetak) {
  foreach ($items as $it_f) {
    if ($foto_ada($it_f->foto ?? '')) { $ada_foto_manual = true; break; }
    foreach (($it_f->aksesoris ?? []) as $ak_f) {
      if ($foto_ada($ak_f->foto ?? '')) { $ada_foto_manual = true; break 2; }
    }
  }
}

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
  'rolling_door_otomatis'  => [
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
  'unit_fix'               => [ 'nama', 'warna',  'komponen', 'kusen_daun', 'finishing','tebal'],


  'default'                => ['nama', 'warna', 'komponen', 'kusen_daun', 'tebal'],
];
// Kategori yang label "nama"-nya "Tipe" (selain itu "Jenis").
$rumus_label_tipe = ['rolling_door_baja', 'rolling_door_tahan_api', 'rolling_door_otomatis'];

// Render tabel spesifikasi untuk daftar baris tertentu ($kolom_list).
// Dipakai dua kali bila ada Books Penutup: bagian atas (sampai Dimensi lubang
// dinding) dan bagian bawah (Komponen dst.), dipisah oleh baris Ukuran Books Penutup.
$render_spek = function ($kolom_list, $grp, $label_nama, $tampil_ket) use ($mode_excel, $br_xl, $nl_xl) {
  if ($mode_excel) {
    // Excel: satu sel berisi baris-baris "Label: nilai"
    $baris = [];
    foreach ($kolom_list as $kolom) {
      switch ($kolom) {
        case 'nama':
          $baris[] = '<b>' . $label_nama . ' / 类型: ' . html_escape($grp['jenis_item']) . '</b>';
          break;
        case 'warna':
          if (!empty($grp['warna'])) $baris[] = 'Warna / 颜色: ' . html_escape($grp['warna']);
          break;
        case 'tebal':
          if (!empty($grp['tebal'])) $baris[] = 'Tebal (mm) / 厚的: ' . html_escape($grp['tebal']);
          break;
        case 'komponen':
          if (!empty($grp['komponen'])) $baris[] = 'Komponen / 成分: ' . $br_xl . $nl_xl($grp['komponen']);
          break;
        case 'dimensi':
          foreach ($grp['baris'] as $br) {
            $baris[] = 'Dimensi lubang dinding / 墙体开孔尺寸: ' . (int) $br->lebar_mm . ' x ' . (int) $br->tinggi_mm . ' mm';
          }
          break;
        case 'kusen_daun':
          if (!empty($grp['komponen_kusen']) || !empty($grp['komponen_daun'])) {
            $baris[] = 'Ketebalan Plate Aluminium / 铝板厚度: Kusen ' . html_escape($grp['komponen_kusen'] ?: '-')
              . ' mm | Daun ' . html_escape($grp['komponen_daun'] ?: '-') . ' mm';
          }
          break;
        case 'finishing':
          if (!empty($grp['finishing'])) $baris[] = 'Finishing / 精加工: ' . html_escape($grp['finishing']);
          break;
        case 'tipe_motor':
          if (!empty($grp['tipe_motor'])) $baris[] = 'Tipe Motor: ' . html_escape($grp['tipe_motor']);
          break;
        case 'input_power':
          if (!empty($grp['input_power'])) $baris[] = 'Input Power: ' . html_escape($grp['input_power']);
          break;
        case 'voltage':
          if (!empty($grp['voltage'])) $baris[] = 'Voltage: ' . html_escape($grp['voltage']);
          break;
        case 'books_penutup':
          if (!empty($grp['books_penutup'])) $baris[] = 'Books Penutup / 卷帘门罩壳: ' . $br_xl . $nl_xl($grp['books_penutup']);
          break;
        case 'kelengkapan':
          if (!empty($grp['kelengkapan'])) $baris[] = 'Kelengkapan: ' . $br_xl . $nl_xl($grp['kelengkapan']);
          break;
      }
    }
    if ($tampil_ket && !empty($grp['keterangan'])) {
      $baris[] = 'Keterangan / 信息: ' . $br_xl . $nl_xl(implode("\n", $grp['keterangan']));
    }
    echo implode($br_xl, $baris);
    return;
  }
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
      <?php elseif ($kolom === 'tebal' && !empty($grp['tebal'])): ?>
        <tr>
          <td class="spek-label">Tebal(mm)<span class="cn">/ 厚的</span></td>
          <td><?= html_escape($grp['tebal']); ?></td>
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

<?php if ($mode_excel): ?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="versi-excel" content="4">
  <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Penawaran</x:Name><x:WorksheetOptions><x:Print><x:ValidPrinterInfo/></x:Print></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
  <style>
    body, td, th { font-family: Arial, Helvetica, sans-serif; font-size: 11pt; color: #222; }
    table { border-collapse: collapse; }
    td { vertical-align: top; }
    .judul { font-size: 14pt; font-weight: bold; color: #14512f; text-align: center; }
    .tengah { text-align: center; }
    .cn { color: #6b8a76; font-size: 8pt; }
    .teks { mso-number-format: "\@"; }
    .lepas { white-space: nowrap; }
    table.item-table th { background: #eaf5ee; color: #14512f; font-weight: bold; text-align: center; vertical-align: middle; border: .5pt solid #9aa5a0; }
    table.item-table td { border: .5pt solid #9aa5a0; }
    td.deskripsi-cell { white-space: normal; mso-number-format: "\@"; }
    td.num { text-align: center; }
    td.luas { mso-number-format: "0.00"; }
    td.money, td.tval { text-align: right; mso-number-format: "\0022Rp \0022\#\,\#\#0"; }
    td.tlabel { text-align: right; }
    td.tval { font-weight: bold; }
    tr.grand td { font-weight: bold; color: #14512f; border-top: 1.5pt solid #1f7a45; }
  </style>
</head>
<?php else: ?>
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
      font-size: 13.5px;
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
      font-size: 23px;
      font-weight: 700;
      color: var(--brand-dark);
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }

    .kop-info .sub-title {
      font-size: 14px;
      color: #333;
      font-weight: 600;
      margin-bottom: 4px;
    }

    .kop-info .alamat-kontak {
      font-size: 12.5px;
      color: #555;
      line-height: 1.4;
    }

    .cn {
      display: inline-block;
      font-size: 11px;
      font-weight: 400;
      color: #6b8a76;
    }

    /* INFO SURAT */
    .info-surat {
      width: 100%;
      margin-bottom: 16px;
      font-size: 14px;
    }

    .info-surat td {
      padding: 2px 0;
      vertical-align: top;
    }

    .info-surat td.label {
      width: 200px;
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
      padding: 7px 9px;
      font-size: 13px;
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

    /* Kolom angka (lebar, tinggi, luas, qty, harga, total) di TENGAH baris */
    table.item-table>tbody>tr>td.num,
    table.item-table>tbody>tr>td.money {
      vertical-align: middle;
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
      width: 11%;
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

    .gambar-cell.aks img {
      max-width: 70px;
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
      width: 36%;
    }

    table.spek-table {
      width: 100%;
      border-collapse: collapse;
    }

    table.spek-table td {
      border: none;
      padding: 3px 0;
      font-size: 13px;
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
      font-size: 14.5px;
    }

    table.komponen-table {
      width: 100%;
      border-collapse: collapse;
      margin: 3px 0 4px;
    }

    table.komponen-table th,
    table.komponen-table td {
      border: 1px solid #e3e8e5;
      padding: 4px 6px;
      font-size: 12px;
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
      font-size: 14px;
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
      font-size: 16px;
      color: var(--brand-dark);
      padding-top: 8px;
    }

    .syarat {
      font-size: 12.5px;
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
      width: 290px;
    }

    .ttd .line {
      margin-top: 60px;
      border-top: 1px solid #333;
      padding-top: 4px;
      font-size: 13px;
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
<?php endif; ?>

<body>

  <div class="sheet">

  <?php
  // Jumlah kolom tabel item: 8 dengan kolom GAMBAR, 7 tanpa (penawaran manual tanpa foto)
  $jk = ($is_manual_cetak && !$ada_foto_manual) ? 7 : 8;
  ?>
  <?php if ($mode_excel): ?>
    <!-- Kop Surat (Excel). <col> di tabel pertama menentukan lebar kolom sheet. -->
    <table>
      <?php if ($jk === 8): ?><col width="100"><?php endif; ?>
      <col width="360"><col width="80"><col width="80"><col width="95"><col width="55"><col width="140"><col width="150">
      <?php
      // Logo di kolom A (4 baris kop), teks kop di kolom sisanya
      $logo_xl  = $img_xl('uploads/logo/Logo.jpeg', 'Logo', 70);
      $kop_span = $jk - 1;
      ?>
      <tr>
        <td rowspan="4" class="logo-kop"><?= $logo_xl; ?></td>
        <td colspan="<?= $kop_span; ?>" class="judul">PT OUPAI PINTU JENDELA INDONESIA</td>
      </tr>
      <tr><td colspan="<?= $kop_span; ?>" class="tengah"><b>Manufaktur Pintu &amp; Jendela</b> <span class="cn">| 门窗制造厂</span></td></tr>
      <tr><td colspan="<?= $kop_span; ?>" class="tengah">Jl. Prof. Dr. Hamka No.45, Tambakaji, 50185, Kec. Ngaliyan Kota Semarang, Jawa Tengah, Indonesia</td></tr>
      <tr><td colspan="<?= $kop_span; ?>" class="tengah">Telp: 085819570958 &bull; Email: ptoupaipintujendelaindonesia@gmail.com</td></tr>
      <tr><td colspan="<?= $jk; ?>" style="border-bottom: 2pt double #1f7a45;"></td></tr>
      <tr><td colspan="<?= $jk; ?>"></td></tr>
    </table>
  <?php else: ?>

    <!-- Tombol Aksi (Tidak Ikut Tercetak) -->
    <div class="action-bar no-print">
      <a href="<?= site_url('penawaran/excel/' . (int) $header->id . '?termin=' . rawurlencode($termin ?? '50_50')); ?>" class="btn-cetak btn-excel" style="text-decoration:none; background:#217346;">
        <i class="bi bi-file-earmark-excel"></i> Download Excel
      </a>
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
  <?php endif; ?>

    <?php
    // Excel: label 2 kolom kalau ada kolom GAMBAR (supaya tidak terpotong), ":" 1 kolom, nilai sisanya
    $span_label = ($mode_excel && $jk === 8) ? 2 : 1;
    $label_info = $span_label > 1 ? ' colspan="2"' : '';
    $nilai_info = $mode_excel ? ' colspan="' . ($jk - $span_label) . '" class="teks"' : '';
    ?>
    <!-- Info Surat -->
    <table class="info-surat">
      <tr>
        <td class="label"<?= $label_info ?? ''; ?>>Tanggal / 日期 </td>
        <?php if (!$mode_excel): ?><td class="sep">:</td><?php endif; ?>
        <td<?= $nilai_info; ?>><?= $mode_excel ? ': ' : ''; ?><?= date('d/m/Y', strtotime($header->tanggal)); ?></td>
      </tr>
      <tr>
        <td class="label"<?= $label_info ?? ''; ?>>No. Surat / 参考编号</td>
        <?php if (!$mode_excel): ?><td class="sep">:</td><?php endif; ?>
        <td<?= $nilai_info; ?>><?= $mode_excel ? ': ' : ''; ?><?= html_escape($header->no_surat); ?></td>
      </tr>
      <?php if (($header->jenis_penjualan ?? 'biasa') === 'konsinyasi'): ?>
      <tr>
        <td class="label"<?= $label_info ?? ''; ?>>Sifat / 性质</td>
        <?php if (!$mode_excel): ?><td class="sep">:</td><?php endif; ?>
        <td<?= $nilai_info; ?>><?= $mode_excel ? ': ' : ''; ?><strong>Konsinyasi (titip jual)</strong> &mdash; hanya barang yang terjual yang ditagihkan sesuai harga ini; barang yang tidak terjual ditarik kembali.</td>
      </tr>
      <?php endif; ?>
      <tr>
        <td class="label"<?= $label_info ?? ''; ?>>Nama Perusahaan Klien / 客户公司名称 </td>
        <?php if (!$mode_excel): ?><td class="sep">:</td><?php endif; ?>
        <td<?= $nilai_info; ?>><?= $mode_excel ? ': ' : ''; ?><?= html_escape($header->customer ?? '-'); ?></td>
      </tr>
      <tr>
        <td class="label"<?= $label_info ?? ''; ?>>Informasi Kontak / 联系信息</td>
        <?php if (!$mode_excel): ?><td class="sep">:</td><?php endif; ?>
        <td<?= $nilai_info; ?>><?= $mode_excel ? ': ' : ''; ?><?= !empty($header->customer_telepon) ? html_escape($header->customer_telepon) : '-'; ?></td>
      </tr>
      <tr>
        <td class="label"<?= $label_info ?? ''; ?>>Alamat Surat / 邮寄地址</td>
        <?php if (!$mode_excel): ?><td class="sep">:</td><?php endif; ?>
        <td<?= $nilai_info; ?>><?= $mode_excel ? ': ' : ''; ?><?= !empty($header->customer_alamat) ? html_escape($header->customer_alamat) : '-'; ?></td>
      </tr>
      <?php if ($mode_excel): ?><tr><td colspan="<?= $jk; ?>"></td></tr><?php endif; ?>
    </table>

    <!-- Tabel Item -->
    <table class="item-table">
      <thead>
        <tr>
          <?php if (!$is_manual_cetak || $ada_foto_manual): ?>
            <th>GAMBAR<br><span class="cn">图片</span></th>
          <?php endif; ?>
          <th>Deskripsi<br><span class="cn">描述</span></th>
          <th>Lebar (mm)<br><span class="cn">宽 (mm)</span></th>
          <th>Tinggi (mm)<br><span class="cn">高 (mm)</span></th>
          <th>Total Luas (m²)<br><span class="cn">总面积 (m²)</span></th>
          <th>Qty<br><span class="cn">数量</span></th>
          <th><?= $is_manual_cetak ? 'Harga / m²' : 'Harga / Unit'; ?></th>
          <th>Total Harga<br><span class="cn">总计</span></th>
        </tr>
      </thead>
      <tbody>
        <?php $jumlah_unit = 0; ?>
        <?php if ($is_manual_cetak): ?>
          <?php
          // Baris aksesoris: [foto] | nama | - | - | - | qty | harga | total
          $baris_aks_manual = function ($ak) use ($ada_foto_manual, $foto_ada, $fmt_luas, &$rp_isi, $xn, $mode_excel, $img_xl) {
            $ak_img_xl = ($mode_excel && $foto_ada($ak->foto ?? '')) ? $img_xl('uploads/penawaran/' . $ak->foto, $ak->nama, 55) : '';
            ?>
            <tr class="aksesoris-row"<?= $ak_img_xl !== '' ? ' style="height:45pt"' : ''; ?>>
              <?php if ($ada_foto_manual): ?>
                <td class="gambar-cell aks">
                  <?php if ($mode_excel): ?>
                    <?= $ak_img_xl; ?>
                  <?php elseif ($foto_ada($ak->foto ?? '')): ?>
                    <img src="<?= base_url('uploads/penawaran/' . rawurlencode($ak->foto)); ?>" alt="<?= html_escape($ak->nama); ?>">
                  <?php endif; ?>
                </td>
              <?php endif; ?>
              <td class="deskripsi-cell">
                <strong>Aksesoris<span class="cn">/ 配件</span>:</strong>
                <?= html_escape($ak->nama); ?>
              </td>
              <td class="num"></td>
              <td class="num"></td>
              <td class="num"></td>
              <td class="num"><?= (int) $ak->qty; ?></td>
              <?php $ak_tot = isset($ak->total_harga) ? (float) $ak->total_harga : (int) $ak->qty * (float) $ak->harga; ?>
              <td class="money"<?= $xn($ak->harga); ?>><?= $rp_isi($ak->harga); ?></td>
              <td class="money"<?= $xn($ak_tot); ?>><?= $rp_isi($ak_tot); ?></td>
            </tr>
            <?php
          };

          // Baris detail kaca: [kosong] | nama | - | - | luas | - | harga/m² | total
          $baris_kaca_manual = function ($kc) use ($ada_foto_manual, $fmt_luas, &$rp_isi, $xn) {
            ?>
            <tr class="kaca-row">
              <?php if ($ada_foto_manual): ?><td class="gambar-cell aks"></td><?php endif; ?>
              <td class="deskripsi-cell"><?= html_escape($kc->nama); ?></td>
              <td class="num"></td>
              <td class="num"></td>
              <?php $kc_tot = isset($kc->total_harga) ? (float) $kc->total_harga : (float) $kc->luas * (float) $kc->harga; ?>
              <td class="num luas"<?= $xn($kc->luas); ?>><?= (float) $kc->luas > 0 ? $fmt_luas($kc->luas) : ''; ?></td>
              <td class="num"></td>
              <td class="money"<?= $xn($kc->harga); ?>><?= $rp_isi($kc->harga); ?></td>
              <td class="money"<?= $xn($kc_tot); ?>><?= $rp_isi($kc_tot); ?></td>
            </tr>
            <?php
          };
          // Penawaran manual: tampilkan SEMUA isian form manual (urutan sama dengan form).
          // Baris yang isiannya kosong otomatis tidak dicetak (dicek di $render_spek).
          $spek_manual = ['nama', 'warna', 'finishing', 'komponen', 'tebal', 'kusen_daun'];

          // Rupiah untuk cetak manual: kalau nilainya 0 (tidak diisi), sel dibiarkan kosong
          $rp_isi = function ($v) {
            return ((float) $v > 0) ? 'Rp ' . number_format((float) $v, 0, ',', '.') : '';
          };
          ?>

          <?php foreach ($kelompok as $grp): ?>
            <?php
            $jumlah_baris = count($grp['baris']);
            $aks_grp = [];
            $kc_grp  = [];
            foreach ($grp['baris'] as $b_g) {
              foreach (($b_g->aksesoris ?? []) as $a_g) $aks_grp[] = $a_g;
              foreach (($b_g->kaca ?? []) as $k_g) $kc_grp[] = $k_g;
            }
            $baris_pertama = TRUE;
            ?>
            <?php foreach ($grp['baris'] as $it): $jumlah_unit += (int) $it->qty; ?>
              <?php
              $lebar_it = (int) $it->lebar_mm;
              $tinggi_it = (int) $it->tinggi_mm;
              // Total luas = luas per unit x qty, dibulatkan 2 angka di belakang koma
              $luas_it = $total_luas($it->luas_m2 ?? 0, max(1, (int) $it->qty));

              // total_harga di database = barang + aksesoris + kaca; baris barang
              // hanya menampilkan bagian barangnya (aksesoris & kaca punya baris sendiri).
              $total_lain = 0;
              foreach (($it->aksesoris ?? []) as $a_t) {
                $total_lain += isset($a_t->total_harga) ? (float) $a_t->total_harga : (int) $a_t->qty * (float) $a_t->harga;
              }
              foreach (($it->kaca ?? []) as $k_t) {
                $total_lain += isset($k_t->total_harga) ? (float) $k_t->total_harga : (float) $k_t->luas * (float) $k_t->harga;
              }
              $total_barang_it = max(0, (float) $it->total_harga - $total_lain);
              ?>
              <tr>
                <?php if ($baris_pertama): ?>
                  <?php if ($ada_foto_manual): ?>
                    <td class="gambar-cell" rowspan="<?= $jumlah_baris; ?>">
                      <?php if ($mode_excel): ?>
                        <?= $foto_ada($grp['foto'] ?? '') ? $img_xl('uploads/penawaran/' . $grp['foto'], $grp['jenis_item']) : ''; ?>
                      <?php elseif ($foto_ada($grp['foto'] ?? '')): ?>
                        <img src="<?= base_url('uploads/penawaran/' . rawurlencode($grp['foto'])); ?>" alt="<?= html_escape($grp['jenis_item']); ?>">
                      <?php endif; ?>
                    </td>
                  <?php endif; ?>
                  <td class="deskripsi-cell" rowspan="<?= $jumlah_baris; ?>">
                    <?php $render_spek($spek_manual, $grp, 'Jenis', TRUE); ?>
                  </td>
                <?php endif; ?>
                <?php if ($lebar_it <= 0 && $tinggi_it <= 0 && trim((string) ($it->ukuran_teks ?? '')) !== ''): ?>
                  <?php /* Data lama (sebelum ada input lebar x tinggi): tampilkan ukuran teks apa adanya */ ?>
                  <td class="num" colspan="3"><?= html_escape($it->ukuran_teks); ?></td>
                <?php else: ?>
                  <td class="num"><?= $lebar_it > 0 ? $lebar_it : ''; ?></td>
                  <td class="num"><?= $tinggi_it > 0 ? $tinggi_it : ''; ?></td>
                  <td class="num luas"<?= $xn($luas_it); ?>><?= $luas_it > 0 ? $fmt_luas($luas_it) : ''; ?></td>
                <?php endif; ?>
                <td class="num"><?= (int) $it->qty > 0 ? (int) $it->qty : ''; ?></td>
                <td class="money"<?= $xn($it->harga_unit); ?>><?= $rp_isi($it->harga_unit); ?></td>
                <td class="money"<?= $xn($total_barang_it); ?>><?= $rp_isi($total_barang_it); ?></td>
              </tr>
              <?php $baris_pertama = FALSE; ?>
            <?php endforeach; ?>

            <?php foreach ($aks_grp as $ak) $baris_aks_manual($ak); ?>
            <?php foreach ($kc_grp as $kc) $baris_kaca_manual($kc); ?>
          <?php endforeach; ?>

          <?php /* Item tanpa barang (hanya aksesoris / kaca) */ ?>
          <?php foreach ($items_aks_saja as $ik): ?>
            <?php foreach (($ik->aksesoris ?? []) as $ak) $baris_aks_manual($ak); ?>
            <?php foreach (($ik->kaca ?? []) as $kc) $baris_kaca_manual($kc); ?>
          <?php endforeach; ?>

        <?php else: ?>
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
              $b_luas_total = $total_luas($b_luas, $b_qty);
              $books_rows[] = (object) [
                'nama'      => 'Books Penutup',
                'lebar_mm'  => $br_aks->lebar_m2_books,
                'tinggi_mm' => $br_aks->tinggi_m2_books,
                'luas'      => $b_luas_total,
                'qty'       => $b_qty,
                'harga'     => $b_hrg,
                'total'     => $b_luas_total * $b_hrg,
              ];
            }
            // Biaya Instalasi (rolling door): luas billing x qty x tarif pasang per m²
            $i_qty = (float) ($br_aks->qty_instalasi ?? 0);
            $i_hrg = (float) ($br_aks->harga_m2_instalasi ?? 0);
            if ($i_qty > 0 || $i_hrg > 0) {
              $i_luas = $total_luas($br_aks->luas_billing_m2 ?? $br_aks->luas_m2 ?? 0, $i_qty);
              $inst_rows[] = (object) [
                'luas'  => $i_luas,
                'qty'   => $i_qty,
                'harga' => $i_hrg,
                'total' => $i_luas * $i_hrg,
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
                'total'     => isset($kaca_row->total_harga) ? (float) $kaca_row->total_harga : (float) $kaca_row->luas * (float) $kaca_row->harga,
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
          $jumlah_baris_total = $jumlah_baris + count($books_rows) + ($ada_bawah ? 1 : 0) + count($aks_rows) + count($kc_rows) + count($inst_rows);

          $baris_pertama = TRUE;
          ?>
          <?php foreach ($grp['baris'] as $it): $jumlah_unit += (int) $it->qty; ?>
            <tr>
              <?php if ($baris_pertama): ?>
                <td class="gambar-cell" rowspan="<?= $jumlah_baris_total; ?>">
                  <?php if ($mode_excel): ?>
                    <?php
                    $img_grp = !empty($grp['foto']) ? $img_xl('uploads/penawaran/' . $grp['foto'], $grp['jenis_item']) : '';
                    if ($img_grp === '' && !empty($grp['gambar'])) {
                      $img_grp = $img_xl($folder_gambar_barang . $grp['gambar'], $grp['jenis_item']);
                    }
                    echo $img_grp;
                    ?>
                  <?php elseif (!empty($grp['foto'])): ?>
                    <img src="<?= base_url('uploads/penawaran/' . $grp['foto']); ?>"
                      alt="<?= html_escape($grp['jenis_item']); ?>"
                      onerror="this.parentElement.innerHTML='<div class=&quot;gambar-kosong&quot;><i class=&quot;bi bi-image&quot;></i></div>'">
                  <?php elseif (!empty($grp['gambar'])): ?>
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
              <?php if (($header->tipe ?? 'rumus') === 'manual'): ?>
                <td class="num" colspan="3"><?= html_escape($it->ukuran_teks ?? ''); ?></td>
              <?php else: ?>
                <td class="num"><?= $uk_l; ?></td>
                <td class="num"><?= $uk_t; ?></td>
                <?php $luas_tot_it = $total_luas($it->luas_billing_m2 ?? $it->luas_m2, (int) $it->qty); ?>
                <td class="num luas"<?= $xn($luas_tot_it); ?>><?= $fmt_luas($luas_tot_it); ?></td>
              <?php endif; ?>
              <td class="num"><?= (int) $it->qty; ?></td>
              <td class="money"<?= $xn($it->harga_unit); ?>>Rp <?= number_format((float) $it->harga_unit, 0, ',', '.'); ?></td>
              <?php
              // total_harga di database sudah termasuk aksesoris; di baris barang
              // tampilkan bagian barangnya saja (aksesoris punya baris sendiri di bawah).
              $total_aks_it = 0;
              foreach (($it->aksesoris ?? []) as $ak_it) {
                $total_aks_it += isset($ak_it->total_harga) ? (float) $ak_it->total_harga : (int) $ak_it->qty * (float) $ak_it->harga;
              }
              // Books penutup juga sudah termasuk di total_harga; tampil di baris sendiri
              $total_aks_it += $total_luas($it->luas_m2_books ?? 0, (int) ($it->qty_books ?? 0)) * (float) ($it->harga_m2_books ?? 0);
              // Biaya instalasi juga sudah termasuk di total_harga; tampil di baris sendiri
              $total_aks_it += $total_luas($it->luas_billing_m2 ?? $it->luas_m2 ?? 0, (float) ($it->qty_instalasi ?? 0)) * (float) ($it->harga_m2_instalasi ?? 0);
              // Detail kaca juga sudah termasuk di total_harga; tampil di baris sendiri
              foreach (($it->kaca ?? []) as $kc_it) {
                $total_aks_it += isset($kc_it->total_harga) ? (float) $kc_it->total_harga : (float) $kc_it->luas * (float) $kc_it->harga;
              }
              $total_barang_it = max(0, (float) $it->total_harga - $total_aks_it);
              ?>
              <td class="money"<?= $xn($total_barang_it); ?>>Rp <?= number_format($total_barang_it, 0, ',', '.'); ?></td>
            </tr>
            <?php $baris_pertama = FALSE; ?>
          <?php endforeach; ?>

          <?php /* Baris Ukuran Books Penutup: label + lebar/tinggi di deskripsi, luas/qty/harga di kolom angka */ ?>
          <?php foreach ($books_rows as $bk): ?>
            <tr class="books-row">
              <td class="deskripsi-cell">
                <?php if ($mode_excel): ?>
                Ukuran Books Penutup / 卷帘门罩壳尺寸: <?= $bk->lebar_mm !== null ? (int) round((float) $bk->lebar_mm) : ''; ?> x <?= $bk->tinggi_mm !== null ? (int) round((float) $bk->tinggi_mm) : ''; ?> mm
                <?php else: ?>
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
                <?php endif; ?>
              </td>
              <td class="num"><?= $bk->lebar_mm !== null ? (int) round((float) $bk->lebar_mm) : ''; ?></td>
              <td class="num"><?= $bk->tinggi_mm !== null ? (int) round((float) $bk->tinggi_mm) : ''; ?></td>
              <td class="num luas"<?= $xn($bk->luas); ?>><?= $fmt_luas($bk->luas); ?></td>
              <td class="num"><?= (int) $bk->qty; ?></td>
              <td class="money"<?= $xn($bk->harga); ?>>Rp <?= number_format((float) $bk->harga, 0, ',', '.'); ?></td>
              <td class="money"<?= $xn($bk->total); ?>>Rp <?= number_format((float) $bk->total, 0, ',', '.'); ?></td>
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
              <td class="money"<?= $xn($ak->harga); ?>>Rp <?= number_format((float) $ak->harga, 0, ',', '.'); ?></td>
              <td class="money"<?= $xn($ak->total); ?>>Rp <?= number_format((float) $ak->total, 0, ',', '.'); ?></td>
            </tr>
          <?php endforeach; ?>

          <?php /* Baris kaca (paling bawah): nama -> Deskripsi, qty -> Qty, harga -> Harga / Unit */ ?>
          <?php foreach ($kc_rows as $kaca): ?>
            <tr class="kaca-row">
              <td class="deskripsi-cell" colspan="3" style="text-align: right;">
                <?= html_escape($kaca->nama); ?>
              </td>
             
              <td class="num luas"<?= $xn($kaca->luas); ?>><?=  $fmt_luas($kaca->luas) ; ?></td>
              <td class="num"></td>
              <td class="money"<?= $xn($kaca->harga); ?>>Rp <?= number_format((float) $kaca->harga, 0, ',', '.'); ?></td>
              <td class="money"<?= $xn($kaca->total); ?>>Rp <?= number_format((float) $kaca->total, 0, ',', '.'); ?></td>
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
              <?php $ins_tampil_luas = ($ins->qty > 0 && $ins->harga > 0 && $ins->luas > 0); ?>
              <td class="num luas"<?= $ins_tampil_luas ? $xn($ins->luas) : ''; ?>><?= $ins_tampil_luas ? $fmt_luas($ins->luas) : ''; ?></td>
              <td class="num"><?= $ins->qty > 0 ? (int) $ins->qty : ''; ?></td>
              <td class="money"<?= $xn($ins->harga); ?>><?= $ins->harga > 0 ? 'Rp ' . number_format($ins->harga, 0, ',', '.') : ''; ?></td>
              <td class="money"<?= $xn($ins->total); ?>><?= $ins->total > 0 ? 'Rp ' . number_format($ins->total, 0, ',', '.') : ''; ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>

        <?php /* Item tanpa barang (hanya aksesoris / kaca) - penawaran manual */ ?>
        <?php foreach ($items_aks_saja as $ik): ?>
          <?php foreach (($ik->aksesoris ?? []) as $ak): ?>
            <tr class="aksesoris-row">
              <td></td>
              <td class="deskripsi-cell">
                <strong>Aksesoris<span class="cn">/ 配件</span>:</strong>
                <?= html_escape($ak->nama); ?>
              </td>
              <td class="num"></td>
              <td class="num"></td>
              <td class="num"></td>
              <td class="num"><?= (int) $ak->qty; ?></td>
              <?php $ak_tot = isset($ak->total_harga) ? (float) $ak->total_harga : (int) $ak->qty * (float) $ak->harga; ?>
              <td class="money"<?= $xn($ak->harga); ?>>Rp <?= number_format((float) $ak->harga, 0, ',', '.'); ?></td>
              <td class="money"<?= $xn($ak_tot); ?>>Rp <?= number_format(isset($ak->total_harga) ? (float) $ak->total_harga : (int) $ak->qty * (float) $ak->harga, 0, ',', '.'); ?></td>
            </tr>
          <?php endforeach; ?>
          <?php foreach (($ik->kaca ?? []) as $kc): ?>
            <tr class="kaca-row">
              <td></td>
              <?php $kc_tot = isset($kc->total_harga) ? (float) $kc->total_harga : (float) $kc->luas * (float) $kc->harga; ?>
              <td class="deskripsi-cell"><?= html_escape($kc->nama); ?></td>
              <td class="num"></td>
              <td class="num"></td>
              <td class="num luas"<?= $xn($kc->luas); ?>><?= $fmt_luas($kc->luas); ?></td>
              <td class="num"></td>
              <td class="money"<?= $xn($kc->harga); ?>>Rp <?= number_format((float) $kc->harga, 0, ',', '.'); ?></td>
              <td class="money"<?= $xn($kc_tot); ?>>Rp <?= number_format(isset($kc->total_harga) ? (float) $kc->total_harga : (float) $kc->luas * (float) $kc->harga, 0, ',', '.'); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <?php // Excel: label menempati 6 kolom, nilai tepat di kolom Total Harga
    $tl = $mode_excel ? ' colspan="' . ($jk - 1) . '"' : ''; ?>
    <table class="total-table">
      <?php if ($mode_excel): ?><tr><td colspan="<?= $jk; ?>"></td></tr><?php endif; ?>
      <?php if ($mode_excel): ?>
      <tr>
        <td class="tlabel"<?= $tl; ?>>Jumlah unit / 单元数量</td>
        <td class="tqty"><?= (int) $jumlah_unit; ?></td>
      </tr>
      <?php endif; ?>
      <tr>
        <td class="tlabel"<?= $tl; ?>><?= $mode_excel ? 'Subtotal / 小计' : 'Jumlah unit / 单元数量 : ' . $jumlah_unit; ?></td>
        <td class="tval"<?= $xn($header->subtotal); ?>>Subtotal: Rp <?= number_format((float) $header->subtotal, 0, ',', '.'); ?></td>
      </tr>
      <?php if ((float) ($header->biaya_pasang ?? 0) > 0): ?>
          <tr>
            <td class="tlabel"<?= $tl; ?>>Biaya Pemasangan /  安装费用</td>
            <td class="tval"<?= $xn($header->biaya_pasang); ?>>Rp <?= number_format((float) $header->biaya_pasang, 0, ',', '.'); ?></td>
          </tr>
      <?php endif; ?>
      <?php if ((float) ($header->biaya_ongkir ?? 0) > 0): ?>
        <tr>
          <td class="tlabel"<?= $tl; ?>>Biaya Pengiriman / 运费</td>
          <td class="tval"<?= $xn($header->biaya_ongkir); ?>>Rp <?= number_format((float) $header->biaya_ongkir, 0, ',', '.'); ?></td>
        </tr>
      <?php endif; ?>
      <?php if ((float) $header->ppn_persen > 0): ?>
        <tr>
          <td class="tlabel"<?= $tl; ?>>PPN <?= rtrim(rtrim(number_format($header->ppn_persen, 2), '0'), '.'); ?>%</td>
          <td class="tval"<?= $xn($header->ppn_nominal); ?>>Rp <?= number_format((float) $header->ppn_nominal, 0, ',', '.'); ?></td>
        </tr>
      <?php endif; ?>
      <?php if ((float) $header->pph_persen > 0): ?>
        <tr>
          <td class="tlabel"<?= $tl; ?>>PPh <?= rtrim(rtrim(number_format($header->pph_persen, 2), '0'), '.'); ?>% (dipotong customer)<?= !empty($header->pph_ongkir) ? ', termasuk ongkir' : ''; ?></td>
          <td class="tval"<?= $xn(-(float) $header->pph_nominal); ?>>- Rp <?= number_format((float) $header->pph_nominal, 0, ',', '.'); ?></td>
        </tr>
      <?php endif; ?>
      <tr class="grand">
        <td class="tlabel"<?= $tl; ?>>Grand Total</td>
        <td class="tval"<?= $xn($header->grand_total); ?>>Rp <?= number_format((float) $header->grand_total, 0, ',', '.'); ?></td>
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

    <?php if ($mode_excel): ?>
    <?php /* Excel: teks panjang ditaruh di kolom A tanpa wrap supaya tampil melewati kolom kosong di kanannya */ ?>
    <?php
    $syarat_xl = [
      $teks_bayar,
      $teks_bank,
      'Barang pesanan yang sudah diproduksi tidak dapat dibatalkan / 已生产完毕的订单商品无法取消。',
      'Penawaran ini tidak bisa untuk dipilih pilih itemnya, apabila ada perubahan dibuatkan penawaran baru / 此优惠不适用于特定商品。如有变更，我们将提供新的优惠。',
      'Toleransi untuk produksi &plusmn; 2.0mm / 生产容差 &plusmn; 2.0mm',
      $teks_biaya,
    ];
    ?>
    <table>
      <tr><td colspan="<?= $jk; ?>"></td></tr>
      <tr><td class="lepas"><b>Syarat &amp; Ketentuan:</b></td></tr>
      <?php foreach ($syarat_xl as $no_s => $s_teks): ?>
        <tr><td colspan="<?= $jk; ?>" class="teks wrap"><?= ($no_s + 1) . '. ' . $s_teks; ?></td></tr>
      <?php endforeach; ?>
      <?php if (!empty($header->catatan)): ?>
        <tr><td colspan="<?= $jk; ?>"></td></tr>
        <tr><td colspan="<?= $jk; ?>" class="teks wrap"><b>Catatan tambahan:</b> <?= $nl_xl($header->catatan); ?></td></tr>
      <?php endif; ?>
      <tr><td colspan="<?= $jk; ?>"></td></tr>
      <tr>
        <td class="tengah"<?= $jk === 8 ? ' colspan="2"' : ''; ?>>Hormat kami,</td>
        <td colspan="2"></td>
        <td colspan="3" class="tengah">Disetujui oleh,</td>
      </tr>
      <tr><td colspan="<?= $jk; ?>" style="height:60px"></td></tr>
      <tr>
        <td class="tengah" style="border-top:.5pt solid #333;"<?= $jk === 8 ? ' colspan="2"' : ''; ?>>PT OUPAI PINTU JENDELA INDONESIA</td>
        <td colspan="2"></td>
        <td colspan="3" class="tengah" style="border-top:.5pt solid #333;"><?= html_escape($header->customer ?? '-'); ?></td>
      </tr>
    </table>
    <?php else: ?>
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
    <?php endif; ?>

  </div>

</body>

</html>