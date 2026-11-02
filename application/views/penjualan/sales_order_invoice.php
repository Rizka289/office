<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
 * View cetak INVOICE dari Sales Order (berkop surat, A4).
 * Letakkan di: application/views/penjualan/sales_order_invoice.php
 * Halaman ini berdiri sendiri (tanpa header/footer template aplikasi).
 */

$h          = $so['header'];
$items      = $so['items'];
$bayar_list = isset($so['pembayaran']) ? $so['pembayaran'] : array();
$total_bayar = isset($so['total_dibayar']) ? (float) $so['total_dibayar'] : 0;

// ================== DATA KOP PERUSAHAAN (EDIT DI SINI) ==================
$perusahaan = array(
  'nama'   => 'PT OUPAI PINTU JENDELA INDONESIA',
  'tagline' => 'Manufaktur Pintu & Jendela',                // kosongkan '' kalau tidak perlu
  'alamat' => 'Jl. Prof. Dr. Hamka No.45, Tambakaji, 50185, Kec. Ngaliyan Kota Semarang, Jawa Tengah, Indonesia',
  'telp'   => '085819570958',
  'email'  => 'ptoupaipintujendelaindonesia@gmail.com',
);
// Pilihan rekening tujuan pembayaran (dipilih di toolbar sebelum cetak)
$rekening_opsi = array(
  'pt' => array(
    'label'     => 'PT OUPAI PINTU JENDELA INDONESIA.',
    'bank'      => 'Mandiri',
    'no_rek'    => '1360077787882',
    'atas_nama' => 'PT OUPAI PINTU JENDELA INDONESIA.',
  ),
  'mahmudi' => array(
    'label'     => 'Mahmudi',
    'bank'      => 'BCA',
    'no_rek'    => '8715845471',
    'atas_nama' => 'Mahmudi',
  ),
);
$rek_default = 'pt';
// Logo (opsional). Taruh file di FCPATH/assets/img/logo.png, kalau tidak ada kop tampil tanpa logo.
$logo_file = FCPATH . 'uploads/logo/Logo.jpeg';
$logo_url  = base_url('uploads/logo/Logo.jpeg');
$ada_logo  = file_exists($logo_file);
// ========================================================================

$rp  = function ($n) {
  return 'Rp ' . number_format((float) $n, 0, ',', '.');
};
$num = function ($n, $d = 0) {
  return number_format((float) $n, $d, ',', '.');
};
$tgl = function ($d) {
  $bln = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
  $t = strtotime($d);
  return date('j', $t) . ' ' . $bln[(int) date('n', $t)] . ' ' . date('Y', $t);
};
$isi = function ($v) {
  $v = trim((string) $v);
  return $v !== '' && $v !== '-';
};
$multi = function ($v) {
  return nl2br(html_escape(trim((string) $v)));
};

// Ukuran tampil: rolling door +200 mm (lebar) & +600 mm (tinggi), sama seperti detail SO
$ukuran_tampil = function ($row) {
  $l = (int) $row->lebar_mm;
  $t = (int) $row->tinggi_mm;
  $rumus = (string) (isset($row->rumus) ? $row->rumus : '');
  $is_rd = preg_match('/^ro{1,2}l{1,2}ing_(door_)?(tahan_api|baja)$/', $rumus)
    || ($rumus === '' && preg_match('/ro{1,2}l{1,2}ing\s*door/i', (string) (isset($row->jenis_item) ? $row->jenis_item : '')));
  if ($is_rd) {
    $l += 200;
    $t += 600;
  }
  return array($l, $t);
};

// Terbilang (rupiah)
$terbilang = function ($x) use (&$terbilang) {
  $x = (int) abs($x);
  $satuan = array('', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas');
  if ($x < 12) return $satuan[$x];
  if ($x < 20) return $terbilang($x - 10) . ' belas';
  if ($x < 100) return $terbilang((int) ($x / 10)) . ' puluh' . ($x % 10 ? ' ' . $terbilang($x % 10) : '');
  if ($x < 200) return 'seratus' . ($x - 100 ? ' ' . $terbilang($x - 100) : '');
  if ($x < 1000) return $terbilang((int) ($x / 100)) . ' ratus' . ($x % 100 ? ' ' . $terbilang($x % 100) : '');
  if ($x < 2000) return 'seribu' . ($x - 1000 ? ' ' . $terbilang($x - 1000) : '');
  $unit = array(array(1000000000000, 'triliun'), array(1000000000, 'miliar'), array(1000000, 'juta'), array(1000, 'ribu'));
  foreach ($unit as $u) {
    if ($x >= $u[0]) {
      $q = (int) floor($x / $u[0]);
      $r = $x % $u[0];
      return $terbilang($q) . ' ' . $u[1] . ($r ? ' ' . $terbilang($r) : '');
    }
  }
  return '';
};

// Nomor invoice diturunkan dari nomor SO: 01/SO-OPJI/11/2026 -> 01/INV-OPJI/11/2026
$no_inv = str_replace('/SO-', '/INV-', (string) $h->no_so);

// Termin & pembayaran
$grand      = (float) $h->grand_total;
$dp_persen  = (float) $h->dp_persen;
$dp_nilai   = round($grand * $dp_persen / 100);
$sisa_tagih = max(0, $grand - $total_bayar);
$lunas      = $grand > 0 && $sisa_tagih <= 0.005;
if ($h->termin === '100' || $dp_persen <= 0) {
  $termin_teks = 'Pembayaran 100%';
} else {
  $termin_teks = 'DP ' . $num($dp_persen, 2) . '% / Pelunasan ' . $num(100 - $dp_persen, 2) . '%';
}

$dash = function ($n, $d = 2) use ($num) {
  return (float) $n > 0 ? $num($n, $d) : '-';
};
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Invoice <?= html_escape($no_inv) ?></title>
  <style>
    @page {
      size: A4;
      margin: 12mm 12mm 14mm 12mm;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: Arial, Helvetica, sans-serif;
      font-size: 11px;
      color: #111;
      background: #e9ecef;
    }

    .toolbar {
      max-width: 210mm;
      margin: 12px auto 0;
      text-align: right;
    }

    .toolbar button {
      font-size: 13px;
      padding: 6px 14px;
      margin-left: 6px;
      cursor: pointer;
    }

    .page {
      position: relative;
      width: 210mm;
      min-height: 297mm;
      margin: 10px auto;
      padding: 12mm;
      background: #fff;
    }

    /* ===== KOP ===== */
    .kop {
      display: flex;
      align-items: center;
      gap: 14px;
      padding-bottom: 8px;
      border-bottom: 3px double #1f3b57;
    }

    .kop img {
      height: 64px;
      width: auto;
    }

    .kop .nama {
      font-size: 19px;
      font-weight: 700;
      color: #1f3b57;
      letter-spacing: .02em;
    }

    .kop .tagline {
      font-size: 11px;
      font-style: italic;
      color: #555;
    }

    .kop .kontak {
      font-size: 10.5px;
      color: #333;
      margin-top: 3px;
      line-height: 1.45;
    }

    /* ===== JUDUL ===== */
    .judul {
      text-align: center;
      margin: 14px 0 10px;
    }

    .judul h1 {
      margin: 0;
      font-size: 20px;
      letter-spacing: .25em;
      color: #1f3b57;
    }

    .judul .no {
      font-size: 12px;
      margin-top: 2px;
    }

    .info {
      display: flex;
      justify-content: space-between;
      gap: 18px;
      margin-bottom: 12px;
    }

    .info .box {
      flex: 1;
      border: 1px solid #c9d1d9;
      border-radius: 4px;
      padding: 8px 10px;
    }

    .info .box h4 {
      margin: 0 0 5px;
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: .08em;
      color: #6b7782;
    }

    .info table td {
      padding: 1px 0;
      vertical-align: top;
    }

    .info table td:first-child {
      width: 85px;
      color: #555;
    }

    /* ===== TABEL ITEM ===== */
    table.item {
      width: 100%;
      border-collapse: collapse;
    }

    table.item th {
      background: #1f3b57;
      color: #fff;
      font-weight: 600;
      padding: 6px 6px;
      border: 1px solid #1f3b57;
      font-size: 10.5px;
    }

    table.item td {
      padding: 5px 6px;
      border: 1px solid #c9d1d9;
      vertical-align: top;
    }

    table.item tr.utama td {
      border-top: 1.5px solid #8a96a3;
    }

    table.item tr.sub td {
      font-size: 10px;
      color: #333;
      background: #fafbfc;
    }

    table.item tr.sub td.desc {
      padding-left: 16px;
    }

    .r {
      text-align: right;
    }

    .c {
      text-align: center;
    }

    .muted {
      color: #666;
      font-size: 10px;
    }

    tr {
      page-break-inside: avoid;
    }

    thead {
      display: table-header-group;
    }

    /* ===== TOTAL ===== */
    .bawah {
      display: flex;
      justify-content: space-between;
      gap: 18px;
      margin-top: 12px;
      page-break-inside: avoid;
    }

    .bawah .kiri {
      flex: 1.1;
    }

    .bawah .kanan {
      flex: 1;
    }

    .terbilang {
      border: 1px dashed #8a96a3;
      border-radius: 4px;
      padding: 7px 10px;
      font-style: italic;
      margin-bottom: 10px;
    }

    .tot td {
      padding: 3px 0;
    }

    .tot tr.grand td {
      border-top: 1.5px solid #1f3b57;
      border-bottom: 1.5px solid #1f3b57;
      font-weight: 700;
      font-size: 12.5px;
      padding: 6px 0;
    }

    .tot tr.sisa td {
      font-weight: 700;
      font-size: 12.5px;
      color: #8a1f1f;
      padding-top: 6px;
    }

    table.bayar {
      width: 100%;
      border-collapse: collapse;
      margin-top: 4px;
    }

    table.bayar th,
    table.bayar td {
      border: 1px solid #c9d1d9;
      padding: 3px 6px;
      font-size: 10px;
    }

    table.bayar th {
      background: #f1f4f7;
    }

    .judul-kecil {
      font-weight: 700;
      font-size: 10.5px;
      margin: 8px 0 2px;
    }

    .stempel {
      display: inline-block;
      border: 3px solid #1b7a3d;
      color: #1b7a3d;
      font-weight: 800;
      font-size: 20px;
      letter-spacing: .2em;
      padding: 2px 16px;
      transform: rotate(-8deg);
      margin-top: 8px;
      opacity: .85;
    }

    .stempel.batal {
      border-color: #b32121;
      color: #b32121;
    }

    /* ===== TTD ===== */
    .ttd {
      display: flex;
      justify-content: space-between;
      margin-top: 26px;
      page-break-inside: avoid;
    }

    .ttd .col {
      width: 40%;
      text-align: center;
    }

    .ttd .garis {
      height: 70px;
    }

    .ttd .nama {
      border-top: 1px solid #000;
      width: 75%;
      margin: 0 auto;
      padding-top: 3px;
    }

    .rek {
      display: none;
    }

    .rek.aktif {
      display: block;
    }

    .toolbar select {
      font-size: 13px;
      padding: 6px 8px;
    }

    .toolbar label {
      font-size: 13px;
    }

    .catatan {
      margin-top: 10px;
      font-size: 10px;
      color: #333;
    }

    @media print {
      body {
        background: #fff;
      }

      .toolbar {
        display: none;
      }

      .page {
        width: auto;
        min-height: 0;
        margin: 0;
        padding: 0;
      }

      * {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
    }
  </style>
</head>

<body>

  <div class="toolbar">
    <label for="pilih_rek">Kirim ke rekening:</label>
    <select id="pilih_rek">
      <?php foreach ($rekening_opsi as $kode => $r): ?>
        <option value="<?= $kode ?>" <?= $kode === $rek_default ? 'selected' : '' ?>><?= html_escape($r['label']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="button" onclick="window.print()">Cetak</button>
    <button type="button" onclick="window.close()">Tutup</button>
  </div>

  <div class="page">

    <!-- ================= KOP SURAT ================= -->
    <div class="kop">
      <?php if ($ada_logo): ?>
        <img src="<?= $logo_url ?>" alt="Logo">
      <?php endif; ?>
      <div>
        <div class="nama"><?= html_escape($perusahaan['nama']) ?></div>
        <?php if ($isi($perusahaan['tagline'])): ?><div class="tagline"><?= html_escape($perusahaan['tagline']) ?></div><?php endif; ?>
        <div class="kontak">
          <?= html_escape($perusahaan['alamat']) ?><br>
          Telp: <?= html_escape($perusahaan['telp']) ?> &nbsp;|&nbsp; Email: <?= html_escape($perusahaan['email']) ?>
        </div>
      </div>
    </div>

    <div class="judul">
      <h1>INVOICE</h1>
      <div class="no">No. <?= html_escape($no_inv) ?></div>
    </div>

    <!-- ================= INFO ================= -->
    <div class="info">
      <div class="box">
        <h4>Ditagihkan kepada</h4>
        <div style="font-weight:700;font-size:12px"><?= html_escape($h->nama_customer) ?></div>
        <?php if ($isi($h->alamat_customer)): ?><div><?= $multi($h->alamat_customer) ?></div><?php endif; ?>
        <?php if ($isi($h->alamat_kirim)): ?>
          <div class="muted" style="margin-top:4px">Alamat kirim: <?= $multi($h->alamat_kirim) ?></div>
        <?php endif; ?>
      </div>
      <div class="box">
        <h4>Detail invoice</h4>
        <table>
          <tr>
            <td>Tanggal</td>
            <td>: <?= $tgl($h->tanggal) ?></td>
          </tr>
          <tr>
            <td>Ref. SO</td>
            <td>: <?= html_escape($h->no_so) ?></td>
          </tr>
          <?php if ($isi($h->no_po_customer)): ?>
            <tr>
              <td>No. PO</td>
              <td>: <?= html_escape($h->no_po_customer) ?></td>
            </tr>
          <?php endif; ?>
          <tr>
            <td>Termin</td>
            <td>: <?= $termin_teks ?></td>
          </tr>
          <?php if ($isi($h->tgl_kirim)): ?>
            <tr>
              <td>Target kirim</td>
              <td>: <?= $tgl($h->tgl_kirim) ?></td>
            </tr>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <!-- ================= ITEM ================= -->
    <table class="item">
      <thead>
        <tr>
          <th style="width:4%">No</th>
          <th>Deskripsi</th>
          <th style="width:10%">Luas (m&sup2;)</th>
          <th style="width:7%">Qty</th>
          <th style="width:15%">Harga</th>
          <th style="width:17%">Jumlah</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $no = 1;
        foreach ($items as $it):
          $luas_bill = (float) $it->luas_billing_m2 > 0 ? (float) $it->luas_billing_m2 : (float) $it->luas_m2;
          list($uk_l, $uk_t) = $ukuran_tampil($it);
          $aks  = !empty($it->aksesoris) ? $it->aksesoris : array();
          $kaca = !empty($it->kaca) ? $it->kaca : array();

          $ada_books  = $isi($it->books_penutup) || (int) $it->qty_books > 0;
          $ada_instal = (int) $it->qty_instalasi > 0 && (float) $it->harga_m2_instalasi > 0;
          $total_books  = (float) $it->luas_m2_books * (int) $it->qty_books * (float) $it->harga_m2_books;
          $total_instal = $luas_bill * (float) $it->harga_m2_instalasi * (int) $it->qty_instalasi;
        ?>
          <tr class="utama">
            <td class="c"><?= $no++ ?></td>
            <td>
              <strong><?= html_escape($it->jenis_item) ?></strong>
              <div class="muted">
                <?php
                $ket = array();
                if ($isi($it->warna))     $ket[] = html_escape($it->warna);
                if ($isi($it->finishing)) $ket[] = html_escape($it->finishing);
                if ($uk_l > 0 && $uk_t > 0) $ket[] = $num($uk_l) . ' x ' . $num($uk_t) . ' mm';
                echo implode(' &middot; ', $ket);
                ?>
              </div>
              <?php if ($isi($it->keterangan)): ?><div class="muted"><?= $multi($it->keterangan) ?></div><?php endif; ?>
            </td>
            <td class="r"><?= $dash($luas_bill, 2) ?></td>
            <td class="c"><?= $num($it->qty, 2) ?></td>
            <td class="r"><?= $rp($it->harga_unit) ?></td>
            <td class="r"><?= $rp($it->total_harga) ?></td>
          </tr>

          <?php if ($ada_books): ?>
            <tr class="sub">
              <td></td>
              <td class="desc">Books penutup
                <?php if ((int) $it->qty_books > 0): ?>
                  (<?= $num($it->lebar_m2_books) ?> x <?= $num($it->tinggi_m2_books) ?> mm)
                <?php endif; ?>
                <?php if ($isi($it->books_penutup)): ?><div class="muted"><?= $multi($it->books_penutup) ?></div><?php endif; ?>
              </td>
              <td class="r"><?= $dash($it->luas_m2_books, 2) ?></td>
              <td class="c"><?= (int) $it->qty_books ?></td>
              <td class="r"><?= $rp($it->harga_m2_books) ?></td>
              <td class="r"><?= $rp($total_books) ?></td>
            </tr>
          <?php endif; ?>

          <?php if ($ada_instal): ?>
            <tr class="sub">
              <td></td>
              <td class="desc">Biaya instalasi</td>
              <td class="r"><?= $dash($luas_bill, 2) ?></td>
              <td class="c"><?= (int) $it->qty_instalasi ?></td>
              <td class="r"><?= $rp($it->harga_m2_instalasi) ?></td>
              <td class="r"><?= $rp($total_instal) ?></td>
            </tr>
          <?php endif; ?>

          <?php foreach ($aks as $a): ?>
            <tr class="sub">
              <td></td>
              <td class="desc">Aksesoris: <?= html_escape($a->nama) ?></td>
              <td class="r">-</td>
              <td class="c"><?= (int) $a->qty ?></td>
              <td class="r"><?= $rp($a->harga) ?></td>
              <td class="r"><?= $rp($a->total_harga) ?></td>
            </tr>
          <?php endforeach; ?>

          <?php foreach ($kaca as $k): ?>
            <tr class="sub">
              <td></td>
              <td class="desc">Kaca: <?= html_escape($k->nama) ?></td>
              <td class="r"><?= $dash($k->luas, 2) ?></td>
              <td class="c">-</td>
              <td class="r"><?= $rp($k->harga) ?></td>
              <td class="r"><?= $rp($k->total_harga) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- ================= TOTAL ================= -->
    <div class="bawah">
      <div class="kiri">
        <div class="terbilang">
          <span class="muted">Terbilang:</span><br>
          <strong><?= ucfirst(trim($terbilang(round($grand)))) ?> rupiah</strong>
        </div>

        <div class="judul-kecil">Pembayaran ke rekening:</div>
        <?php foreach ($rekening_opsi as $kode => $r): ?>
          <table class="rek <?= $kode === $rek_default ? 'aktif' : '' ?>" data-rek="<?= $kode ?>">
            <tr>
              <td style="width:70px">Bank</td>
              <td>: <?= html_escape($r['bank']) ?></td>
            </tr>
            <tr>
              <td>No. Rek</td>
              <td>: <strong><?= html_escape($r['no_rek']) ?></strong></td>
            </tr>
            <tr>
              <td>A/N</td>
              <td>: <?= html_escape($r['atas_nama']) ?></td>
            </tr>
          </table>
        <?php endforeach; ?>

        <?php if ($isi($h->catatan)): ?>
          <div class="catatan"><strong>Catatan:</strong><br><?= $multi($h->catatan) ?></div>
        <?php endif; ?>
      </div>

      <div class="kanan">
        <table class="tot" width="100%">
          <tr>
            <td>Subtotal</td>
            <td class="r"><?= $rp($h->subtotal) ?></td>
          </tr>
          <?php if ((float) $h->biaya_pasang > 0): ?>
            <tr>
              <td>Biaya pasang</td>
              <td class="r"><?= $rp($h->biaya_pasang) ?></td>
            </tr>
          <?php endif; ?>
          <?php if ((float) $h->biaya_ongkir > 0): ?>
            <tr>
              <td>Biaya pengiriman</td>
              <td class="r"><?= $rp($h->biaya_ongkir) ?></td>
            </tr>
          <?php endif; ?>
          <?php if ((float) $h->ppn_persen > 0): ?>
            <tr>
              <td>PPN <?= $num($h->ppn_persen, 2) ?>%</td>
              <td class="r"><?= $rp($h->ppn_nominal) ?></td>
            </tr>
          <?php endif; ?>
          <?php if ((float) $h->pph_persen > 0): ?>
            <tr>
              <td>PPh <?= $num($h->pph_persen, 2) ?>%</td>
              <td class="r"><?= $rp($h->pph_nominal) ?></td>
            </tr>
          <?php endif; ?>
          <tr class="grand">
            <td>TOTAL TAGIHAN</td>
            <td class="r"><?= $rp($grand) ?></td>
          </tr>
          <tr>
            <td>Pembayaran diterima</td>
            <td class="r">- <?= $rp($total_bayar) ?></td>
          </tr>
          <tr class="sisa">
            <td>SISA TAGIHAN</td>
            <td class="r"><?= $rp($sisa_tagih) ?></td>
          </tr>
        </table>

        <?php if ($h->status === 'batal'): ?>
          <div class="stempel batal">BATAL</div>
        <?php elseif ($lunas): ?>
          <div class="stempel">LUNAS</div>
        <?php endif; ?>

        <?php if (!empty($bayar_list)): ?>
          <div class="judul-kecil">Riwayat pembayaran</div>
          <table class="bayar">
            <tr>
              <th>Tanggal</th>
              <th>Keterangan</th>
              <th>Jumlah</th>
            </tr>
            <?php foreach ($bayar_list as $b): ?>
              <tr>
                <td><?= date('d/m/Y', strtotime($b->tanggal)) ?></td>
                <td>
                  <?= html_escape(strtoupper($b->jenis)) ?>
                  <?= $isi($b->metode) ? ' - ' . html_escape($b->metode) : '' ?>
                  <?= $isi($b->no_referensi) ? ' (' . html_escape($b->no_referensi) . ')' : '' ?>
                </td>
                <td class="r"><?= $rp($b->nominal) ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- ================= TANDA TANGAN ================= -->
    <div class="ttd">
      <div class="col">
        <div>Dibuat oleh,</div>
        <div class="garis"></div>
        <div class="nama">Keuangan</div>
      </div>
      <div class="col">
        <div>Hormat kami,</div>
        <div class="garis"></div>
        <div class="nama">Atasan</div>
      </div>
    </div>

  </div>

  <script>
    // Tampilkan hanya rekening yang dipilih; yang tercetak sesuai pilihan
    (function() {
      var sel = document.getElementById('pilih_rek');
      function terapkan() {
        var tabel = document.querySelectorAll('table.rek');
        for (var i = 0; i < tabel.length; i++) {
          tabel[i].classList.toggle('aktif', tabel[i].getAttribute('data-rek') === sel.value);
        }
      }
      sel.addEventListener('change', terapkan);
      terapkan();
    })();
  </script>
</body>

</html>