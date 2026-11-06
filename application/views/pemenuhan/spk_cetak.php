<?php defined('BASEPATH') or exit('No direct script access allowed');
// SPK produksi: $h (header SPK), $baris = array of array('item' => item SO lengkap, 'qty' => qty SPK)
include APPPATH . 'views/pemenuhan/_cetak.php';
$status_teks = array('antri' => 'Antri', 'proses' => 'Dikerjakan', 'selesai' => 'Selesai', 'batal' => 'BATAL');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>SPK <?= html_escape($h->no_spk) ?></title>
  <?= $css ?>
</head>
<body>
  <?= $toolbar ?>
  <div class="page">
    <?php $kop(); ?>

    <div class="judul">
      <h1>SURAT PERINTAH KERJA</h1>
      <div class="no">Produksi &middot; No. <?= html_escape($h->no_spk) ?></div>
    </div>

    <?php if ($h->status === 'batal'): ?><div class="peringatan">SPK INI DIBATALKAN</div><?php endif; ?>

    <div class="info">
      <div class="box">
        <h4>Pesanan</h4>
        <table>
          <tr><td>Ref. SO</td><td>: <?= html_escape($h->no_so) ?></td></tr>
          <tr><td>Customer</td><td>: <?= html_escape($h->nama_customer) ?></td></tr>
          <?php if ($isi($h->no_po_customer)): ?><tr><td>No. PO customer</td><td>: <?= html_escape($h->no_po_customer) ?></td></tr><?php endif; ?>
        </table>
      </div>
      <div class="box">
        <h4>Jadwal</h4>
        <table>
          <tr><td>Tanggal SPK</td><td>: <?= $tgl($h->tanggal) ?></td></tr>
          <tr><td>Target selesai</td><td>: <strong><?= $tgl($h->target_selesai) ?></strong></td></tr>
          <tr><td>Status</td><td>: <?= $status_teks[$h->status] ?? $h->status ?></td></tr>
        </table>
      </div>
    </div>

    <table class="item">
      <thead>
        <tr>
          <th style="width:4%">No</th>
          <th style="width:30%">Item</th>
          <th style="width:15%">Ukuran (mm)</th>
          <th style="width:7%">Qty</th>
          <th>Detail produksi</th>
          <th style="width:8%">Cek QC</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1; foreach ($baris as $b):
          $it = $b['item'];
          list($l, $t) = $this->Pemenuhan_model->ukuran($it);
          $detail = array();
          $label = array(
            'finishing' => 'Finishing', 'komponen' => 'Komponen', 'komponen_kusen' => 'Kusen', 'komponen_daun' => 'Daun',
            'tipe_motor' => 'Motor', 'input_power' => 'Daya', 'voltage' => 'Tegangan',
            'books_penutup' => 'Books penutup', 'kelengkapan' => 'Kelengkapan', 'keterangan' => 'Keterangan',
          );
          foreach ($label as $k => $lbl) {
            if ($isi($it->$k ?? '')) {
              $detail[] = '<b>' . $lbl . ':</b> ' . $multi($it->$k);
            }
          }
          if (!empty($it->aksesoris)) {
            $a = array();
            foreach ($it->aksesoris as $x) { $a[] = html_escape($x->nama) . ' (' . $num($x->qty) . ')'; }
            $detail[] = '<b>Aksesoris:</b> ' . implode(', ', $a);
          }
          if (!empty($it->kaca)) {
            $k = array();
            foreach ($it->kaca as $x) { $k[] = html_escape($x->nama) . ($isi($x->luas ?? '') ? ' (' . $num($x->luas) . ' m&sup2;)' : ''); }
            $detail[] = '<b>Kaca:</b> ' . implode(', ', $k);
          }
        ?>
          <tr>
            <td class="c"><?= $no++ ?></td>
            <td>
              <strong><?= html_escape($it->jenis_item) ?></strong>
              <?php if ($isi($it->warna ?? '')): ?><div class="muted">Warna: <?= html_escape($it->warna) ?></div><?php endif; ?>
            </td>
            <td class="c"><?= $l > 0 ? $num($l, 0) . ' x ' . $num($t, 0) : '-' ?></td>
            <td class="c"><strong><?= $num($b['qty']) ?></strong></td>
            <td><?= $detail ? implode('<br>', $detail) : '-' ?></td>
            <td></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ($isi($h->catatan)): ?>
      <div class="catatan"><strong>Catatan:</strong><br><?= $multi($h->catatan) ?></div>
    <?php endif; ?>

    <div class="ttd">
      <div class="col"><div>Dibuat oleh,</div><div class="garis"></div><div class="nama">PPIC / Admin</div></div>
      <div class="col"><div>Dikerjakan,</div><div class="garis"></div><div class="nama">Kepala Produksi</div></div>
      <div class="col"><div>Diperiksa,</div><div class="garis"></div><div class="nama">QC</div></div>
    </div>
  </div>
</body>
</html>
