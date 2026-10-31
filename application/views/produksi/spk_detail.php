<?php defined('BASEPATH') or exit('No direct script access allowed');

$h     = $spk['header'];
$items = $spk['items'];

$num   = function ($n, $d = 0) { return number_format((float) $n, $d, ',', '.'); };
$isi   = function ($v) { $v = trim((string) $v); return $v !== '' && $v !== '-'; };
$multi = function ($v) { return nl2br(html_escape(trim((string) $v))); };

$status_map = array(
    'draft'   => array('secondary', 'Draft'),
    'proses'  => array('warning text-dark', 'Sedang Dikerjakan'),
    'selesai' => array('success', 'Selesai'),
    'batal'   => array('danger', 'Batal'),
);
$st = isset($status_map[$h->status]) ? $status_map[$h->status] : array('secondary', ucfirst($h->status));

$persen_progres = isset($spk['persen']) ? (int) $spk['persen'] : 0;
$jml_custom = 0; $jml_custom_selesai = 0;
foreach ($items as $it) {
    if ((isset($it->tipe) ? $it->tipe : 'custom') === 'custom') {
        $jml_custom++;
        if ((int) (isset($it->persen) ? $it->persen : 0) >= 100) {
            $jml_custom_selesai++;
        }
    }
}
?>

<style>
  .spkd { --brand: #2f7a57; --brand-dark: #1f5a3f; --brand-soft: #eaf4f0; --line: #e9edeb; }
  .spkd .hero { background: linear-gradient(135deg, var(--brand), var(--brand-dark)); color: #fff; border-radius: 14px; padding: 20px 24px; }
  .spkd .hero .no-spk { font-size: 1.5rem; font-weight: 700; letter-spacing: .01em; }
  .spkd .hero .meta { opacity: .88; font-size: .9rem; }
  .spkd .panel { background: #fff; border: 1px solid var(--line); border-radius: 12px; }
  .spkd .panel-title { font-weight: 600; padding: 12px 18px; border-bottom: 1px solid var(--line); display: flex; align-items: center; gap: 8px; }
  .spkd .panel-body { padding: 16px 18px; }
  .spkd .kv { display: grid; grid-template-columns: 150px 1fr; gap: 6px 12px; font-size: .9rem; margin: 0; }
  .spkd .kv dt { color: #7b857f; font-weight: 400; }
  .spkd .kv dd { margin: 0; word-break: break-word; }

  .spkd .item-box { border: 1px solid var(--line); border-radius: 12px; background: #fff; margin-bottom: 14px; overflow: hidden; }
  .spkd .item-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; padding: 14px 18px; background: #fafcfb; border-bottom: 1px solid var(--line); }
  .spkd .item-name { font-weight: 600; font-size: 1.02rem; }
  .spkd .item-sub { color: #7b857f; font-size: .85rem; }
  .spkd .specs { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 12px 18px; margin-bottom: 4px; }
  .spkd .spec .lbl { font-size: .78rem; color: #7b857f; }
  .spkd .spec .val { font-size: .9rem; }
  .spkd .note { background: #f6f8f7; border-radius: 8px; padding: 8px 12px; font-size: .86rem; color: #4a5650; }
  .spkd .mini-table { width: 100%; font-size: .85rem; margin-top: 8px; }
  .spkd .mini-table th { color: #7b857f; font-weight: 500; padding: 4px 8px; background: #f8faf9; border-bottom: 1px solid var(--line); }
  .spkd .mini-table td { padding: 6px 8px; border-bottom: 1px solid #f1f3f2; }

  @media (min-width: 992px) { .spkd .sticky-side { position: sticky; top: 16px; } }
  @media print { .spkd .no-print, .spkd .alert { display: none !important; } }
</style>

<div class="spkd container-fluid py-3">

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?= html_escape($this->session->flashdata('error')) ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <i class="bi bi-check-circle me-1"></i><?= html_escape($this->session->flashdata('success')) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endif; ?>

  <!-- Banner Header SPK -->
  <div class="hero mb-3">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="no-spk"><i class="bi bi-tools me-2"></i><?= html_escape($h->no_spk) ?></div>
        <div class="meta mt-1">
          Customer: <strong><?= html_escape($h->nama_customer) ?></strong> &nbsp;|&nbsp; Tanggal SPK: <?= date('d M Y', strtotime($h->tanggal_spk)) ?>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2 no-print">
        <span class="badge bg-<?= $st[0] ?> fs-6 fw-normal px-3 py-2"><?= $st[1] ?></span>
        <a href="<?= site_url('spk') ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
        <button type="button" class="btn btn-outline-light btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Cetak SPK</button>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <!-- Kiri: Informasi & Daftar Item Produksi -->
    <div class="col-lg-8">

      <div class="panel mb-3">
        <div class="panel-title"><i class="bi bi-info-circle"></i>Informasi Pengerjaan</div>
        <div class="panel-body">
          <div class="row g-3">
            <div class="col-md-6">
              <dl class="kv">
                <dt>No. Sales Order</dt>
                <dd><a href="<?= site_url('sales_order/detail/' . (int) $h->id_so) ?>" target="_blank"><strong><?= html_escape($h->no_so) ?></strong> <i class="bi bi-box-arrow-up-right small"></i></a></dd>
                <dt>No. PO Customer</dt>
                <dd><?= $isi($h->no_po_customer) ? html_escape($h->no_po_customer) : '<span class="text-muted">Tidak ada</span>' ?></dd>
              </dl>
            </div>
            <div class="col-md-6">
              <dl class="kv">
                <dt>Target Selesai</dt>
                <dd><?= $isi($h->target_selesai) ? '<span class="text-danger fw-semibold">' . date('d M Y', strtotime($h->target_selesai)) . '</span>' : '<span class="text-muted">Belum ditentukan</span>' ?></dd>
                <dt>Alamat Pengiriman</dt>
                <dd><?= $isi($h->alamat_customer) ? $multi($h->alamat_customer) : '<span class="text-muted">-</span>' ?></dd>
              </dl>
            </div>
            <?php if ($isi($h->catatan_produksi)): ?>
              <div class="col-12">
                <div class="note border-start border-success border-4">
                  <strong><i class="bi bi-journal-text me-1"></i>Instruksi Khusus Produksi:</strong><br>
                  <?= $multi($h->catatan_produksi) ?>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0"><i class="bi bi-list-check me-1"></i>Daftar Item Produksi (<?= count($items) ?>)</h6>
        <span class="text-muted small">Item custom selesai: <?= $jml_custom_selesai ?> dari <?= $jml_custom ?></span>
      </div>

      <?php if (empty($items)): ?>
        <div class="panel"><div class="panel-body text-center text-muted py-5">Item pesanan kosong.</div></div>
      <?php endif; ?>

      <?php $no = 1; foreach ($items as $it): 
        $qty_p  = (float) $it->qty_produksi;
        $qty_s  = (float) $it->qty_selesai;
        $is_custom = ((isset($it->tipe) ? $it->tipe : 'custom') === 'custom');
        $pct_it    = $is_custom ? (int) (isset($it->persen) ? $it->persen : 0) : 100;
        
        // Ambil data bahan baku yang sudah ditarik khusus untuk item spk_detail ini (jika ada di database)
        $list_material_item = isset($it->material_pakai) ? $it->material_pakai : array();
      ?>
      <div class="item-box">
        <div class="item-head">
          <div>
            <div class="item-name"><?= $no++ ?>. <?= html_escape($it->jenis_item) ?></div>
            <div class="item-sub">
              Target Qty: <strong><?= $num($qty_p, 2) ?> unit</strong>
              <?php if ($isi($it->warna)): ?> &middot; Warna: <?= html_escape($it->warna) ?><?php endif; ?>
              <?php if ($isi($it->finishing)): ?> &middot; Finishing: <?= html_escape($it->finishing) ?><?php endif; ?>
            </div>
          </div>
          <div>
            <?php if (!$is_custom): ?>
              <span class="badge bg-info text-dark"><i class="bi bi-box-seam me-1"></i>Ambil dari stok</span>
            <?php elseif ($pct_it >= 100): ?>
              <span class="badge bg-success"><i class="bi bi-check-all me-1"></i>Selesai</span>
            <?php elseif ($pct_it > 0): ?>
              <span class="badge bg-warning text-dark"><i class="bi bi-gear-fill me-1"></i>Proses <?= $pct_it ?>%</span>
            <?php else: ?>
              <span class="badge bg-secondary">Belum dimulai</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="item-body">
          <div class="specs">
            <div class="spec"><div class="lbl">Ukuran (L x T)</div><div class="val"><?= $num($it->lebar_mm) ?> x <?= $num($it->tinggi_mm) ?> mm</div></div>
            <div class="spec"><div class="lbl">Luas</div><div class="val"><?= $num($it->luas_m2, 4) ?> m&sup2;</div></div>
          </div>

          <?php if ($isi($it->keterangan)): ?>
            <div class="note mt-3"><?= $multi($it->keterangan) ?></div>
          <?php endif; ?>

          <?php if ($is_custom): ?>
          <!-- Tahapan Produksi -->
          <div class="mt-3 pt-2 border-top">
            <div class="small fw-semibold text-secondary mb-2"><i class="bi bi-diagram-3 me-1"></i>Tahapan Produksi:</div>
            <?php if (!empty($it->tahap)): ?>
              <ul class="list-unstyled mb-0">
                <?php foreach ($it->tahap as $th): ?>
                  <?php
                    if ($th->status === 'selesai')    { $tc = 'success';          $tl = 'Selesai';    $ti = 'check-circle-fill'; }
                    elseif ($th->status === 'proses') { $tc = 'warning text-dark'; $tl = 'Dikerjakan'; $ti = 'gear-fill'; }
                    else                              { $tc = 'secondary';        $tl = 'Belum';      $ti = 'circle'; }
                  ?>
                  <li class="d-flex justify-content-between align-items-center gap-2 flex-wrap py-2 border-bottom">
                    <div>
                      <i class="bi bi-<?= $ti ?> me-1 text-<?= explode(' ', $tc)[0] ?>"></i>
                      <span class="fw-semibold"><?= html_escape($th->nama_tahap) ?></span>
                      <span class="text-muted small">(<?= $num($th->bobot, 0) ?>%)</span>
                      <?php if (!empty($th->tgl_selesai)): ?>
                        <div class="text-muted small ms-4">Selesai: <?= date('d M Y H:i', strtotime($th->tgl_selesai)) ?></div>
                      <?php elseif (!empty($th->tgl_mulai)): ?>
                        <div class="text-muted small ms-4">Mulai: <?= date('d M Y H:i', strtotime($th->tgl_mulai)) ?></div>
                      <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                      <span class="badge bg-<?= $tc ?>"><?= $tl ?></span>
                      <?php if ($h->status === 'proses'): ?>
                        <?= form_open('spk/ubah_tahap/' . (int) $h->id, ['class' => 'd-inline no-print']) ?>
                          <input type="hidden" name="id_tahap" value="<?= (int) $th->id ?>">
                          <?php if ($th->status === 'belum'): ?>
                            <button type="submit" name="aksi" value="mulai" class="btn btn-sm btn-outline-warning py-0 px-2">Mulai</button>
                          <?php elseif ($th->status === 'proses'): ?>
                            <button type="submit" name="aksi" value="selesai" class="btn btn-sm btn-outline-success py-0 px-2">Selesai</button>
                          <?php else: ?>
                            <button type="submit" name="aksi" value="buka" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="return confirm('Buka kembali tahapan ini?')">Buka lagi</button>
                          <?php endif; ?>
                        <?= form_close() ?>
                      <?php endif; ?>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <div class="text-muted small fst-italic">Belum ada tahapan produksi.</div>
            <?php endif; ?>
          </div>

          <!-- Riwayat Material Terpakai untuk Item Ini -->
          <div class="mt-3 pt-2 border-top">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="small fw-semibold text-secondary"><i class="bi bi-box-seam me-1"></i>Bahan Baku Gudang yang Dikeluarkan:</span>
              <!-- TOMBOL OPSI B: Ambil bahan baku spesifik untuk item ini -->
              <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" 
                      data-bs-toggle="modal" 
                      data-bs-target="#modalAmbilBahan" 
                      data-id-spk-detail="<?= (int) $it->id ?>" 
                      data-nama-item="<?= html_escape($it->jenis_item) ?>">
                <i class="bi bi-plus-lg me-1"></i> Ambil Bahan Baku
              </button>
            </div>

            <?php if (!empty($list_material_item)): ?>
              <table class="mini-table">
                <thead>
                  <tr>
                    <th>Nama Bahan Baku</th>
                    <th class="text-end">Qty Keluar</th>
                    <th class="text-end">Retur</th>
                    <th class="text-end">Terpakai</th>
                    <th>Satuan</th>
                    <th>Keterangan</th>
                    <th class="no-print"></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($list_material_item as $mat): ?>
                    <?php $sisa_mat = (float) $mat->qty_pakai - (float) $mat->qty_retur; ?>
                    <tr>
                      <td><?= html_escape($mat->nama_barang); ?></td>
                      <td class="text-end fw-semibold"><?= $num($mat->qty_pakai, 0); ?></td>
                      <td class="text-end text-success"><?= $num($mat->qty_retur, 0); ?></td>
                      <td class="text-end"><?= $num($sisa_mat, 0); ?></td>
                      <td><?= html_escape($mat->satuan); ?></td>
                      <td class="text-muted"><?= html_escape($mat->keterangan); ?></td>
                      <td class="text-end no-print">
                        <?php if ($sisa_mat > 0 && in_array($h->status, array('proses', 'selesai'), true)): ?>
                          <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2"
                                  data-bs-toggle="modal" data-bs-target="#modalReturBahan"
                                  data-id-material="<?= (int) $mat->id ?>"
                                  data-nama-bahan="<?= html_escape($mat->nama_barang) ?>"
                                  data-maks="<?= (int) $sisa_mat ?>"
                                  data-satuan="<?= html_escape($mat->satuan) ?>">
                            <i class="bi bi-arrow-return-left me-1"></i>Retur Sisa
                          </button>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php else: ?>
              <div class="text-muted small fst-italic">Belum ada bahan baku yang dikeluarkan untuk item ini.</div>
            <?php endif; ?>
          </div>

          <!-- Progress per item -->
          <div class="mt-3 pt-2 border-top">
            <div class="d-flex justify-content-between small mb-1">
              <span class="text-muted">Progres perakitan</span>
              <span class="fw-semibold"><?= $num($qty_s, 2) ?> / <?= $num($qty_p, 2) ?> unit (<?= $pct_it ?>%)</span>
            </div>
            <div class="progress" style="height: 6px;">
              <div class="progress-bar bg-success" style="width: <?= $pct_it ?>%"></div>
            </div>
          </div>
          <?php else: ?>
          <div class="note mt-3"><i class="bi bi-box-seam me-1"></i>Barang jadi: diambil dari stok gudang, tidak melalui proses produksi.</div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>

    </div>

    <!-- Kanan: Kontrol Workshop & Status -->
    <div class="col-lg-4">
      <div class="sticky-side">

        <div class="panel mb-3">
          <div class="panel-title"><i class="bi bi-activity"></i>Status Pengerjaan</div>
          <div class="panel-body text-center">
            <div class="mb-3">
              <div class="display-6 fw-bold text-success"><?= $persen_progres ?>%</div>
              <div class="text-muted small">Total Progres Workshop</div>
            </div>
            <div class="progress mb-3" style="height: 10px;">
              <div class="progress-bar bg-success" style="width: <?= $persen_progres ?>%"></div>
            </div>

            <?php if ($h->status === 'proses'): ?>
              <?= form_open('spk/ubah_status/' . (int) $h->id . '/selesai', ['onsubmit' => "return confirm('Ubah status SPK menjadi SELESAI? Pastikan sisa bahan sudah diretur. Status Sales Order akan menjadi Siap Kirim.')"]) ?>
                <button type="submit" class="btn btn-success w-100" <?= $persen_progres < 100 ? 'disabled' : '' ?>>
                  <i class="bi bi-check-circle-fill me-1"></i> Tandai Produksi Selesai
                </button>
                <?php if ($persen_progres < 100): ?><div class="form-text mt-2">Selesaikan semua tahapan produksi terlebih dahulu.</div><?php else: ?><div class="form-text mt-2">Pastikan sisa bahan sudah diretur ke gudang. Status Sales Order akan menjadi Siap Kirim.</div><?php endif; ?>
              <?= form_close() ?>
            <?php elseif ($h->status === 'selesai'): ?>
              <div class="badge bg-success-subtle text-success border border-success-subtle p-2 w-100 fw-semibold">
                <i class="bi bi-patch-check-fill me-1"></i> Pengerjaan Selesai 100%
              </div>
              <div class="form-text mt-2">Status Sales Order sudah diperbarui menjadi Siap Kirim.</div>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- MODAL POPUP: Ambil / Keluarkan Bahan Baku (Opsi B) -->
<div class="modal fade" id="modalAmbilBahan" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <?= form_open('spk/keluarkan_material/' . (int) $h->id) ?>
      <input type="hidden" name="id_spk_detail" id="modal_id_spk_detail" value="">
      
      <div class="modal-header">
        <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-box-seam me-2 text-success"></i>Keluar Bahan Baku untuk: <span id="modal_label_item" class="text-success"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Pilih Bahan Baku (Gudang) <span class="text-danger">*</span></label>
          <select name="id_barang_bahan" id="modal_id_barang" class="form-select form-select-sm" required>
            <option value="">-- Pilih Barang / Bahan Baku --</option>
            <?php if (!empty($list_barang_gudang)): ?>
              <?php foreach ($list_barang_gudang as $bg): ?>
                <?php $habis = ((float) $bg->jumlah <= 0); ?>
                <option value="<?= (int) $bg->id_barang ?>"
                        data-satuan="<?= html_escape($bg->satuan) ?>"
                        data-stok="<?= (float) $bg->jumlah ?>"
                        <?= $habis ? 'disabled' : '' ?>>
                  <?= html_escape($bg->kode_barang . ' — ' . $bg->nama . ' (Stok: ' . $num($bg->jumlah, 2) . ' ' . $bg->satuan . ')' . ($habis ? ' - habis' : '')) ?>
                </option>
              <?php endforeach; ?>
            <?php else: ?>
              <option value="" disabled>Tidak ada data barang</option>
            <?php endif; ?>
          </select>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Jumlah (Qty) Keluar dari Gudang <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <input type="number" name="qty_pakai" id="modal_qty" class="form-control" min="1" step="1" placeholder="0" required>
              <span class="input-group-text" id="modal_satuan">-</span>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Tanggal Keluar <span class="text-danger">*</span></label>
            <input type="date" name="tanggal_keluar" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Keterangan / Catatan</label>
          <input type="text" name="keterangan" class="form-control form-control-sm" placeholder="Mis. Potongan plat untuk sisi kanan...">
        </div>
        <div class="form-text">Dicatat dulu di Kartu Stok (Pemakaian Produksi), lalu saldo stok gudang berkurang. Sisa bahan nanti dikembalikan lewat tombol Retur Sisa.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check2 me-1"></i>Simpan &amp; Keluarkan dari Gudang</button>
      </div>
      <?= form_close() ?>
    </div>
  </div>
</div>

<!-- MODAL POPUP: Retur Sisa Bahan ke Gudang -->
<div class="modal fade" id="modalReturBahan" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <?= form_open('spk/retur_material/' . (int) $h->id) ?>
      <input type="hidden" name="id_material" id="retur_id_material" value="">
      <div class="modal-header">
        <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-arrow-return-left me-2 text-success"></i>Retur Sisa Bahan: <span id="retur_label" class="text-success"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2 mb-3">
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Qty Sisa Dikembalikan <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <input type="number" name="qty_retur" id="retur_qty" class="form-control" min="1" step="1" required>
              <span class="input-group-text" id="retur_satuan">-</span>
            </div>
            <div class="form-text" id="retur_maks"></div>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Tanggal Retur <span class="text-danger">*</span></label>
            <input type="date" name="tanggal_retur" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Keterangan</label>
          <input type="text" name="keterangan" class="form-control form-control-sm" placeholder="Mis. Sisa potongan plat masih layak pakai">
        </div>
        <div class="form-text">Bahan kembali ke lokasi gudang asalnya dan dicatat sebagai Retur di Kartu Stok. Sisa yang rusak/terbuang tidak perlu diretur (dianggap terpakai).</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check2 me-1"></i>Simpan Retur</button>
      </div>
      <?= form_close() ?>
    </div>
  </div>
</div>

<script>
  var modalRetur = document.getElementById('modalReturBahan');
  if (modalRetur) {
    modalRetur.addEventListener('show.bs.modal', function (event) {
      var b = event.relatedTarget;
      var maks = b.getAttribute('data-maks');
      document.getElementById('retur_id_material').value = b.getAttribute('data-id-material');
      document.getElementById('retur_label').textContent = b.getAttribute('data-nama-bahan');
      document.getElementById('retur_satuan').textContent = b.getAttribute('data-satuan') || '-';
      document.getElementById('retur_maks').textContent = 'Maksimal: ' + maks;
      document.getElementById('retur_qty').max = maks;
    });
    modalRetur.addEventListener('hidden.bs.modal', function () { modalRetur.querySelector('form').reset(); });
  }

  // Skrip JavaScript untuk melempar ID item ke Modal saat tombol "Ambil Bahan Baku" diklik
  var modalAmbilBahan = document.getElementById('modalAmbilBahan');
  if (modalAmbilBahan) {
    modalAmbilBahan.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;
      var idSpkDetail = button.getAttribute('data-id-spk-detail');
      var namaItem = button.getAttribute('data-nama-item');

      document.getElementById('modal_id_spk_detail').value = idSpkDetail;
      document.getElementById('modal_label_item').textContent = namaItem;
    });
  }
  // Satuan otomatis & batas qty sesuai stok saat barang dipilih
  var selBarang = document.getElementById('modal_id_barang');
  if (selBarang) {
    selBarang.addEventListener('change', function () {
      var opt = this.options[this.selectedIndex];
      var qty = document.getElementById('modal_qty');
      document.getElementById('modal_satuan').textContent = opt.getAttribute('data-satuan') || '-';
      if (opt.value) { qty.max = opt.getAttribute('data-stok'); } else { qty.removeAttribute('max'); }
    });
  }

  // Bersihkan form setiap modal ditutup
  if (modalAmbilBahan) {
    modalAmbilBahan.addEventListener('hidden.bs.modal', function () {
      modalAmbilBahan.querySelector('form').reset();
      document.getElementById('modal_satuan').textContent = '-';
    });
  }
</script>