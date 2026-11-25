<?php defined('BASEPATH') or exit('No direct script access allowed');
/*
 * Baris aksesoris paket (mis. handle pintu) di form Surat Jalan / Terima Dropship.
 * Butuh: $aks_aktif, $aks_map (id_so_detail => list aksesoris), $aks_stok (pilihan stok gudang).
 * Pakai: include APPPATH . 'views/pemenuhan/_aksesoris.php';
 *        lalu di dalam loop item: $aks_baris($id_so_detail, $nama_input_qty_induk, $kolom_tengah);
 *        dan sekali di akhir halaman: echo $aks_script;
 */
$aks_aktif = !empty($aks_aktif);
$aks_map   = isset($aks_map) ? $aks_map : array();
$aks_stok  = isset($aks_stok) ? $aks_stok : array();

$aks_label_stok = function ($r) {
  $lok = trim(($r->kode_lokasi ?? '') . ' ' . ($r->nama_lokasi ? '(' . $r->nama_lokasi . ')' : ''));
  $ops = $r->opsi !== '' ? ' · ' . str_replace('_', ' ', $r->opsi) : '';
  $uk  = $r->ukuran !== '' ? ' · ' . $r->ukuran : '';
  return trim(($r->nama_barang ?: '-') . ($r->warna ? ' ' . $r->warna : '')) . $ops . $uk . ' @ ' . ($lok ?: '-') . ' — tersedia ' . (float) $r->tersedia;
};
// pilihan awal: handle bawaan pintu (stok terbanyak), atau nama aksesoris yang cocok dengan nama barang stok
$aks_cocok = function ($a) use ($aks_stok) {
  if (!empty($a->id_barang_default)) {
    // handle per bukaan: utamakan stok handle dengan bukaan yang sama
    $pilih = ''; $maks = 0; $pilih_sama = ''; $maks_sama = 0;
    foreach ($aks_stok as $r) {
      if ((int) explode('|', $r->kunci)[0] !== (int) $a->id_barang_default || $r->tersedia <= 0) { continue; }
      if (!empty($a->opsi) && $r->opsi === $a->opsi && $r->tersedia > $maks_sama) {
        $pilih_sama = $r->kunci; $maks_sama = $r->tersedia;
      }
      if ($r->tersedia > $maks) { $pilih = $r->kunci; $maks = $r->tersedia; }
    }
    if ($pilih_sama !== '') { return $pilih_sama; }
    if ($pilih !== '' && empty($a->opsi)) { return $pilih; }
    if (!empty($a->opsi)) { return ''; }
  }
  $n = mb_strtolower(trim($a->nama));
  if ($n === '') { return ''; }
  foreach ($aks_stok as $r) {
    $b = mb_strtolower(trim((string) $r->nama_barang));
    if ($b !== '' && $r->tersedia > 0 && (mb_strpos($b, $n) !== false || mb_strpos($n, $b) !== false)) {
      return $r->kunci;
    }
  }
  return '';
};

$aks_baris = function ($id_detail, $induk, $kolom_tengah) use ($aks_map, $aks_stok, $aks_label_stok, $aks_cocok) {
  foreach ($aks_map[(int) $id_detail] ?? array() as $a):
    $pilih = $aks_cocok($a); ?>
    <tr class="table-light baris-aks">
      <td class="ps-4">
        <i class="bi bi-arrow-return-right text-muted"></i> <?= html_escape($a->nama) ?>
        <div class="muted"><?= (int) $a->id < 0 ? 'Handle bawaan pintu' : 'Aksesoris paket' ?> &middot; total <?= (float) $a->qty ?>, sudah dikirim <?= (float) $a->terkirim ?></div>
      </td>
      <td colspan="<?= (int) $kolom_tengah ?>">
        <select name="aks_stok[<?= (int) $a->id ?>]" class="form-select form-select-sm">
          <option value="">- tidak potong stok (aksesoris tidak dari gudang) -</option>
          <?php foreach ($aks_stok as $r): ?>
            <option value="<?= html_escape($r->kunci) ?>" <?= $r->kunci === $pilih ? 'selected' : '' ?>><?= html_escape($aks_label_stok($r)) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (!$aks_stok): ?><div class="muted text-danger">Tidak ada stok di gudang. Isi dulu lewat PO Bahan / Stok Opname, atau kirim tanpa potong stok.</div><?php endif; ?>
      </td>
      <td>
        <input type="number" name="aks_qty[<?= (int) $a->id ?>]" class="form-control form-control-sm text-end in-aks" min="0" step="1"
               max="<?= (float) $a->sisa ?>" value="0"
               data-induk="<?= html_escape($induk) ?>" data-total="<?= (float) $a->qty ?>" data-sisa="<?= (float) $a->sisa ?>" data-item="<?= (float) $a->qty_item ?>"
               <?php if (!empty($a->opsi)): ?>data-opsi="<?= html_escape($a->opsi) ?>" data-per="<?= (float) $a->per ?>" data-seg="<?= html_escape(json_encode($a->seg)) ?>"<?php endif; ?>>
      </td>
    </tr>
  <?php endforeach;
};

// Qty aksesoris otomatis mengikuti qty barang yang dikirim (proporsional), selama belum diubah manual
$aks_script = <<<'JS'
<script>
  (function () {
    var aks = document.querySelectorAll('.in-aks');
    if (!aks.length) { return; }
    function hitung(el) {
      if (el.dataset.manual) { return; }
      var induk = document.querySelector('[name="' + el.dataset.induk + '"]');
      var q = induk ? (Number(induk.value) || 0) : 0, item = Number(el.dataset.item) || 0, v;
      if (el.dataset.seg) {
        // pintu per bukaan: ikuti urutan stok yang keluar (sama dengan pemotongan stok pintu)
        var seg = JSON.parse(el.dataset.seg), sisa = q, n = 0;
        seg.forEach(function (x) {
          var ambil = Math.min(sisa, Number(x[1]) || 0);
          if (ambil > 0) { if (x[0] === el.dataset.opsi) { n += ambil; } sisa -= ambil; }
        });
        v = Math.round(n * (Number(el.dataset.per) || 1));
      } else {
        v = item > 0 ? Math.round(Number(el.dataset.total) * Math.min(1, q / item)) : 0;
      }
      el.value = Math.max(0, Math.min(v, Number(el.dataset.sisa) || 0));
    }
    aks.forEach(function (el) {
      hitung(el);
      el.addEventListener('input', function () { el.dataset.manual = '1'; });
      var induk = document.querySelector('[name="' + el.dataset.induk + '"]');
      if (induk) { induk.addEventListener('input', function () { hitung(el); }); }
    });
  })();
</script>
JS;
