<?php defined('BASEPATH') or exit('No direct script access allowed');
/*
 * Bagian bersama dokumen cetak (A4): CSS + helper + kop surat.
 * Dipakai dengan: include APPPATH . 'views/pemenuhan/_cetak.php';
 * lalu panggil $kop() di dalam <div class="page">.
 */
$perusahaan = $this->Pemenuhan_model->perusahaan();
$ada_logo   = file_exists($perusahaan['logo_file']);

$rp  = function ($n) { return 'Rp ' . number_format((float) $n, 0, ',', '.'); };
$num = function ($n, $d = 2) {
  $s = number_format((float) $n, $d, ',', '.');
  return $d > 0 ? rtrim(rtrim($s, '0'), ',') : $s;
};
$tgl = function ($d) {
  if (!$d) { return '-'; }
  $bln = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
  $t = strtotime($d);
  return date('j', $t) . ' ' . $bln[(int) date('n', $t)] . ' ' . date('Y', $t);
};
$isi   = function ($v) { $v = trim((string) $v); return $v !== '' && $v !== '-'; };
$multi = function ($v) { return nl2br(html_escape(trim((string) $v))); };

$kop = function () use ($perusahaan, $ada_logo) { ?>
  <div class="kop">
    <?php if ($ada_logo): ?><img src="<?= $perusahaan['logo_url'] ?>" alt="Logo"><?php endif; ?>
    <div>
      <div class="nama"><?= html_escape($perusahaan['nama']) ?></div>
      <?php if ($perusahaan['tagline'] !== ''): ?><div class="tagline"><?= html_escape($perusahaan['tagline']) ?></div><?php endif; ?>
      <div class="kontak">
        <?= html_escape($perusahaan['alamat']) ?><br>
        Telp: <?= html_escape($perusahaan['telp']) ?> &nbsp;|&nbsp; Email: <?= html_escape($perusahaan['email']) ?>
      </div>
    </div>
  </div>
<?php };

$css = <<<CSS
<style>
  @page { size: A4; margin: 12mm; }
  * { box-sizing: border-box; }
  body { margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; background: #e9ecef; }
  .toolbar { max-width: 210mm; margin: 12px auto 0; text-align: right; }
  .toolbar button { font-size: 13px; padding: 6px 14px; margin-left: 6px; cursor: pointer; }
  .page { width: 210mm; min-height: 297mm; margin: 10px auto; padding: 12mm; background: #fff; }
  .kop { display: flex; align-items: center; gap: 14px; padding-bottom: 8px; border-bottom: 3px double #1f3b57; }
  .kop img { height: 64px; width: auto; }
  .kop .nama { font-size: 19px; font-weight: 700; color: #1f3b57; letter-spacing: .02em; }
  .kop .tagline { font-size: 11px; font-style: italic; color: #555; }
  .kop .kontak { font-size: 10.5px; color: #333; margin-top: 3px; line-height: 1.45; }
  .judul { text-align: center; margin: 14px 0 10px; }
  .judul h1 { margin: 0; font-size: 19px; letter-spacing: .2em; color: #1f3b57; }
  .judul .no { font-size: 12px; margin-top: 2px; }
  .info { display: flex; gap: 18px; margin-bottom: 12px; }
  .info .box { flex: 1; border: 1px solid #c9d1d9; border-radius: 4px; padding: 8px 10px; }
  .info .box h4 { margin: 0 0 5px; font-size: 10px; text-transform: uppercase; letter-spacing: .08em; color: #6b7782; }
  .info table td { padding: 1px 0; vertical-align: top; }
  .info table td:first-child { width: 95px; color: #555; }
  table.item { width: 100%; border-collapse: collapse; }
  table.item th { background: #1f3b57; color: #fff; font-weight: 600; padding: 6px; border: 1px solid #1f3b57; font-size: 10.5px; }
  table.item td { padding: 5px 6px; border: 1px solid #c9d1d9; vertical-align: top; }
  table.item tfoot td { font-weight: 700; }
  .r { text-align: right; } .c { text-align: center; }
  .muted { color: #666; font-size: 10px; }
  tr { page-break-inside: avoid; } thead { display: table-header-group; }
  .catatan { margin-top: 10px; border: 1px dashed #8a96a3; border-radius: 4px; padding: 7px 10px; }
  .peringatan { margin: 8px 0; border: 2px solid #b32121; color: #b32121; font-weight: 700; padding: 6px 10px; text-align: center; letter-spacing: .03em; }
  .ttd { display: flex; justify-content: space-between; gap: 12px; margin-top: 26px; page-break-inside: avoid; }
  .ttd .col { flex: 1; text-align: center; }
  .ttd .garis { height: 70px; }
  .ttd .nama { border-top: 1px solid #000; width: 80%; margin: 0 auto; padding-top: 3px; }
  @media print {
    body { background: #fff; }
    .toolbar { display: none; }
    .page { width: auto; min-height: 0; margin: 0; padding: 0; }
    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
CSS;

$toolbar = '<div class="toolbar"><button type="button" onclick="window.print()">Cetak</button>'
  . '<button type="button" onclick="window.close()">Tutup</button></div>';
