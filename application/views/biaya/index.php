<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';   // $rp, $tgl
$this->load->view('pemenuhan/_style');

$total = 0;
foreach ($list as $b) { $total += (float) $b->nominal; }
$maks_kat = 0;
foreach ($per_kategori as $k) { $maks_kat = max($maks_kat, (float) $k->total); }

$qs = array_filter(array(
  'dari' => $f['dari'], 'sampai' => $f['sampai'],
  'kategori' => $f['id_kategori'] ?: '', 'q' => $f['q'],
), 'strlen');
$kembali = 'biaya' . ($qs ? '?' . http_build_query($qs) : '');
$is_img  = function ($p) { return (bool) preg_match('/\.(jpe?g|png|webp)$/i', (string) $p); };
?>
<style>
  .pm .foto-thumb { width:44px; height:44px; object-fit:cover; border-radius:6px; border:1px solid var(--line); }
  .pm .foto-pdf { width:44px; height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:6px; background:#fdecea; color:#b42318; font-size:1.2rem; }
  .pm .kat-row { font-size:.86rem; padding:6px 0; border-bottom:1px dashed var(--line); }
  .pm .kat-row:last-child { border-bottom:0; }
  #bf_preview img { max-height:160px; border-radius:8px; }
</style>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <div class="judul"><i class="bi bi-wallet2 me-2"></i><?= translate('i18n_biaya_operasional') ?></div>
      <div class="meta"><?= translate('i18n_pengeluaran_kantor_di_luar_po_supplier') ?></div>
    </div>
    <div class="d-flex flex-wrap gap-1">
      <?php if (!empty($izin['catat'])): ?>
        <button type="button" class="btn btn-light btn-sm fw-semibold btn-biaya-baru" data-bs-toggle="modal" data-bs-target="#modalBiaya"><i class="bi bi-plus-lg me-1"></i><?= translate('i18n_catat_pengeluaran') ?></button>
      <?php endif; ?>
      <?php if (!empty($izin['export'])): $qx = $qs ? '?' . http_build_query($qs) : ''; ?>
        <div class="dropdown d-inline-block">
          <button type="button" class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="<?= translate('i18n_bx_export_sesuai_filter') ?>">
            <i class="bi bi-download me-1"></i><?= translate('i18n_bx_export') ?>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><h6 class="dropdown-header"><?= translate('i18n_bx_export_sesuai_filter') ?></h6></li>
            <li><a class="dropdown-item" href="<?= site_url('biaya/export_excel') . $qx ?>"><i class="bi bi-file-earmark-excel text-success me-2"></i>Excel (.xlsx)</a></li>
            <li><a class="dropdown-item" href="<?= site_url('biaya/export_csv') . $qx ?>"><i class="bi bi-filetype-csv me-2"></i>CSV</a></li>
            <li><a class="dropdown-item" href="<?= site_url('biaya/cetak') . $qx ?>" target="_blank"><i class="bi bi-printer text-danger me-2"></i><?= translate('i18n_bx_cetak_pdf') ?></a></li>
          </ul>
        </div>
      <?php endif; ?>
      <a href="<?= site_url('biaya/rekap') ?>" class="btn btn-outline-light btn-sm"><i class="bi bi-bar-chart me-1"></i><?= translate('i18n_rekap') ?></a>
      <a href="<?= site_url('biaya/kategori') ?>" class="btn btn-outline-light btn-sm"><i class="bi bi-tags me-1"></i><?= translate('i18n_kategori_2') ?></a>
    </div>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <form method="get" action="<?= site_url('biaya') ?>" class="card mb-3">
    <div class="card-body row g-2 align-items-end">
      <div class="col-6 col-md-2"><label class="form-label small mb-1"><?= translate('dari') ?></label><input type="date" name="dari" value="<?= html_escape($f['dari']) ?>" class="form-control form-control-sm"></div>
      <div class="col-6 col-md-2"><label class="form-label small mb-1"><?= translate('i18n_sampai') ?></label><input type="date" name="sampai" value="<?= html_escape($f['sampai']) ?>" class="form-control form-control-sm"></div>
      <div class="col-md-3">
        <label class="form-label small mb-1"><?= translate('i18n_kategori_2') ?></label>
        <select name="kategori" class="form-select form-select-sm">
          <option value=""><?= translate('i18n_semua_kategori') ?></option>
          <?php foreach ($kategori as $k): ?>
            <option value="<?= (int) $k->id ?>" <?= (int) $f['id_kategori'] === (int) $k->id ? 'selected' : '' ?>><?= html_escape($k->nama) ?><?= $k->aktif ? '' : (' ' . translate('i18n_nonaktif')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3"><label class="form-label small mb-1"><?= translate('cari') ?></label><input type="search" name="q" value="<?= html_escape($f['q']) ?>" class="form-control form-control-sm" placeholder="<?= translate('i18n_no_bukti_keterangan_dibayar_ke') ?>"></div>
      <div class="col-md-2 d-grid"><button class="btn btn-sm btn-success"><i class="bi bi-funnel me-1"></i><?= translate('i18n_tampilkan') ?></button></div>
    </div>
  </form>

  <div class="row g-3 mb-3">
    <div class="col-sm-4"><div class="card h-100"><div class="card-body"><div class="muted"><?= translate('i18n_total_pengeluaran') ?></div><div class="fs-5 fw-semibold text-danger"><?= $rp($total) ?></div><div class="muted"><?= $tgl($f['dari']) ?> – <?= $tgl($f['sampai']) ?></div></div></div></div>
    <div class="col-sm-4"><div class="card h-100"><div class="card-body"><div class="muted"><?= translate('i18n_jumlah_transaksi') ?></div><div class="fs-5 fw-semibold"><?= count($list) ?></div></div></div></div>
    <div class="col-sm-4"><div class="card h-100"><div class="card-body"><div class="muted"><?= translate('i18n_kategori_terbesar') ?></div>
      <?php if ($per_kategori): ?>
        <div class="fs-6 fw-semibold"><?= html_escape($per_kategori[0]->nama ?: '-') ?></div><div class="muted"><?= $rp($per_kategori[0]->total) ?></div>
      <?php else: ?><div class="fs-6">-</div><?php endif; ?>
    </div></div></div>
  </div>

  <div class="row g-3">
    <div class="col-xl-9">
      <div class="card">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light"><tr><th><?= translate('i18n_nota') ?></th><th><?= translate('i18n_tanggal_no') ?></th><th><?= translate('i18n_kategori_2') ?></th><th><?= translate('deskripsi') ?></th><th><?= translate('i18n_metode') ?></th><th class="text-end"><?= translate('i18n_nominal_2') ?></th><th></th></tr></thead>
            <tbody>
              <?php if (!$list): ?><tr><td colspan="7" class="text-center text-muted py-4"><?= translate('i18n_belum_ada_pengeluaran_pada_periode_ini') ?></td></tr><?php endif; ?>
              <?php foreach ($list as $b): ?>
                <tr>
                  <td>
                    <?php if ($b->foto): ?>
                      <a href="<?= base_url($b->foto) ?>" target="_blank" title="<?= translate('i18n_lihat_nota') ?>">
                        <?php if ($is_img($b->foto)): ?><img src="<?= base_url($b->foto) ?>" class="foto-thumb" alt="<?= translate('i18n_nota_2') ?>" loading="lazy">
                        <?php else: ?><span class="foto-pdf"><i class="bi bi-file-earmark-pdf"></i></span><?php endif; ?>
                      </a>
                    <?php else: ?><span class="muted">-</span><?php endif; ?>
                  </td>
                  <td class="text-nowrap"><?= $tgl($b->tanggal) ?><div class="muted"><?= html_escape($b->no_bukti) ?></div></td>
                  <td><span class="badge text-bg-light fw-normal"><?= html_escape($b->nama_kategori ?: '-') ?></span></td>
                  <td>
                    <?= html_escape($b->keterangan) ?>
                    <?php if ($b->dibayar_ke): ?><div class="muted"><?= translate('i18n_ke') ?> <?= html_escape($b->dibayar_ke) ?></div><?php endif; ?>
                    <?php if (!empty($b->nama_user)): ?><div class="muted"><?= translate('i18n_dicatat_2') ?> <?= html_escape($b->nama_user) ?></div><?php endif; ?>
                  </td>
                  <td><?= html_escape($b->metode ? translate('i18n_metode_' . strtolower(str_replace(array(' ', '-'), '_', $b->metode))) : '-') ?></td>
                  <td class="text-end fw-semibold"><?= $rp($b->nominal) ?></td>
                  <td class="text-end text-nowrap">
                    <?php if (!empty($izin['catat'])): ?>
                      <button type="button" class="btn btn-sm btn-outline-secondary py-0 btn-biaya-edit" data-bs-toggle="modal" data-bs-target="#modalBiaya" title="<?= translate('i18n_ubah') ?>"
                        data-id="<?= (int) $b->id ?>" data-no="<?= html_escape($b->no_bukti) ?>" data-tanggal="<?= html_escape($b->tanggal) ?>"
                        data-kategori="<?= (int) $b->id_kategori ?>" data-keterangan="<?= html_escape($b->keterangan) ?>"
                        data-dibayar="<?= html_escape($b->dibayar_ke ?? '') ?>" data-nominal="<?= (float) $b->nominal ?>"
                        data-metode="<?= html_escape($b->metode ?? '') ?>" data-foto="<?= $b->foto ? html_escape(base_url($b->foto)) : '' ?>"><i class="bi bi-pencil"></i></button>
                      <?= form_open('biaya/hapus/' . (int) $b->id, array('class' => 'd-inline', 'onsubmit' => "return confirm('" . js_esc(translate('i18n_hapus_pengeluaran') . ' ' . $b->no_bukti . ' ' . translate('i18n_beserta_fotonya')) . "')")) ?>
                        <input type="hidden" name="kembali" value="<?= html_escape($kembali) ?>">
                        <button class="btn btn-sm btn-outline-danger py-0" title="<?= translate('hapus') ?>"><i class="bi bi-trash"></i></button>
                      <?= form_close() ?>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <?php if ($list): ?>
              <tfoot class="table-light"><tr><th colspan="5"><?= translate('total') ?></th><th class="text-end"><?= $rp($total) ?></th><th></th></tr></tfoot>
            <?php endif; ?>
          </table>
        </div>
      </div>
    </div>

    <div class="col-xl-3">
      <div class="card">
        <div class="card-header"><i class="bi bi-pie-chart me-2"></i><?= translate('i18n_per_kategori') ?></div>
        <div class="card-body py-2">
          <?php if (!$per_kategori): ?><div class="muted py-2">-</div><?php endif; ?>
          <?php foreach ($per_kategori as $k): ?>
            <div class="kat-row">
              <div class="d-flex justify-content-between"><span><?= html_escape($k->nama ?: '-') ?> <span class="muted">(<?= (int) $k->jumlah ?>)</span></span><b><?= $rp($k->total) ?></b></div>
              <div class="mini-bar"><div style="width:<?= $maks_kat > 0 ? round($k->total / $maks_kat * 100) : 0 ?>%"></div></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($izin['catat'])): ?>
<div class="modal fade" id="modalBiaya" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <?= form_open_multipart('biaya/simpan/0', array('id' => 'formBiaya')) ?>
      <input type="hidden" name="kembali" value="<?= html_escape($kembali) ?>">
      <div class="modal-header">
        <h6 class="modal-title"><i class="bi bi-wallet2 me-2"></i><span id="bf_judul"><?= translate('i18n_catat_pengeluaran') ?></span></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= translate('i18n_tutup') ?>"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2">
          <div class="col-6">
            <label class="form-label small mb-1"><?= translate('tanggal') ?></label>
            <input type="date" name="tanggal" id="bf_tanggal" class="form-control form-control-sm" max="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label small mb-1"><?= translate('i18n_kategori_2') ?></label>
            <select name="id_kategori" id="bf_kategori" class="form-select form-select-sm" required>
              <option value=""><?= translate('i18n_pilih') ?></option>
              <?php foreach ($kategori_form as $k): ?><option value="<?= (int) $k->id ?>"><?= html_escape($k->nama) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label small mb-1"><?= translate('deskripsi') ?></label>
            <input type="text" name="keterangan" id="bf_keterangan" class="form-control form-control-sm" maxlength="255" placeholder="<?= translate('i18n_mis_token_listrik_kantor_november') ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label small mb-1"><?= translate('nominal(rp)') ?></label>
            <input type="number" name="nominal" id="bf_nominal" class="form-control form-control-sm" min="1" step="0.01" required>
          </div>
          <div class="col-6">
            <label class="form-label small mb-1"><?= translate('i18n_metode') ?></label>
            <select name="metode" id="bf_metode" class="form-select form-select-sm" required>
              <?php foreach ($metode_list as $m): ?><option value="<?= html_escape($m) ?>"><?= html_escape(translate('i18n_metode_' . strtolower(str_replace(array(' ', '-'), '_', $m)))) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label small mb-1"><?= translate('i18n_dibayar_ke') ?> <span class="muted"><?= translate('i18n_opsional') ?></span></label>
            <input type="text" name="dibayar_ke" id="bf_dibayar" class="form-control form-control-sm" maxlength="100" placeholder="<?= translate('i18n_mis_pln_toko_atk_sinar_nama_karyawan') ?>">
          </div>
          <div class="col-12">
            <label class="form-label small mb-1"><?= translate('i18n_foto_nota_bukti') ?> <span class="muted"><?= translate('i18n_opsional') ?></span></label>
            <input type="file" name="foto" id="bf_foto" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp,application/pdf">
            <div class="muted mt-1" id="bf_foto_info"><?= translate('i18n_jpg_png_webp_pdf_maks_5mb_2') ?></div>
            <div id="bf_preview" class="mt-2"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal"><?= translate('btn_batal') ?></button>
        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check2 me-1"></i><?= translate('button_save') ?></button>
      </div>
      <?= form_close() ?>
    </div>
  </div>
</div>
<script>
  (function () {
    var form = document.getElementById('formBiaya');
    var base = <?= json_encode(site_url('biaya/simpan/')) ?>;
    var today = <?= json_encode(date('Y-m-d')) ?>;
    var $ = function (id) { return document.getElementById(id); };
    var foto = $('bf_foto'), preview = $('bf_preview');
    var MAX = 5 * 1024 * 1024;

    function tampilPreview(url, isPdf) {
      preview.innerHTML = '';
      if (!url) return;
      if (isPdf) {
        preview.innerHTML = '<a href="' + url + '" target="_blank"><i class="bi bi-file-earmark-pdf me-1"></i><?= tj('i18n_lihat_pdf') ?></a>';
      } else {
        var img = document.createElement('img'); img.src = url; preview.appendChild(img);
      }
    }

    document.querySelectorAll('.btn-biaya-baru').forEach(function (btn) {
      btn.addEventListener('click', function () {
        form.reset();
        form.action = base + '0';
        $('bf_judul').textContent = '<?= tj('i18n_catat_pengeluaran') ?>';
        $('bf_tanggal').value = today;
        $('bf_foto_info').textContent = '<?= tj('i18n_jpg_png_webp_pdf_maks_5mb_2') ?>';
        tampilPreview('');
      });
    });

    document.querySelectorAll('.btn-biaya-edit').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var d = this.dataset;
        form.reset();
        form.action = base + d.id;
        $('bf_judul').textContent = '<?= tj('i18n_ubah') ?> ' + d.no;
        $('bf_tanggal').value = d.tanggal;
        $('bf_kategori').value = d.kategori;
        if ($('bf_kategori').value !== d.kategori) {   // kategori sudah nonaktif: tetap tampilkan
          var o = document.createElement('option'); o.value = d.kategori; o.textContent = '<?= tj('i18n_kategori_nonaktif') ?>';
          $('bf_kategori').appendChild(o); $('bf_kategori').value = d.kategori;
        }
        $('bf_keterangan').value = d.keterangan;
        $('bf_nominal').value = d.nominal;
        $('bf_metode').value = d.metode;
        $('bf_dibayar').value = d.dibayar;
        $('bf_foto_info').textContent = '<?= tj('i18n_kosongkan_kalau_tidak_ganti_foto') ?>';
        tampilPreview(d.foto, /\.pdf$/i.test(d.foto));
      });
    });

    foto.addEventListener('change', function () {
      var f = this.files[0];
      if (!f) { tampilPreview(''); return; }
      if (f.size > MAX) {
        alert('<?= tj('i18n_ukuran_file_maksimal_5mb') ?>');
        this.value = ''; tampilPreview(''); return;
      }
      tampilPreview(URL.createObjectURL(f), f.type === 'application/pdf');
    });
  })();
</script>
<?php endif; ?>
