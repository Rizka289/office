<?php defined('BASEPATH') or exit('No direct script access allowed');
include APPPATH . 'views/pemenuhan/_status.php';   // $rp, $tgl
$this->load->view('pemenuhan/_style');

$total = 0;
foreach ($list as $m) { $total += (float) $m->nominal; }

$jenis_warna = array('modal' => 'primary', 'pinjaman' => 'warning', 'pendapatan' => 'success');
$jenis_ikon  = array('modal' => 'bi-piggy-bank', 'pinjaman' => 'bi-bank', 'pendapatan' => 'bi-plus-circle');
$jenis_label = function ($j) { return translate('i18n_km_jenis_' . $j); };
$jenis_badge = function ($j) use ($jenis_warna, $jenis_label) {
  if (!isset($jenis_warna[$j])) { return '<span class="badge text-bg-light fw-normal">-</span>'; }
  return '<span class="badge text-bg-' . $jenis_warna[$j] . ' fw-normal">' . html_escape($jenis_label($j)) . '</span>';
};

$qs = array_filter(array(
  'dari' => $f['dari'], 'sampai' => $f['sampai'], 'jenis' => $f['jenis'],
  'kategori' => $f['id_kategori'] ?: '', 'q' => $f['q'],
), 'strlen');
$kembali = 'kas_masuk' . ($qs ? '?' . http_build_query($qs) : '');
$is_img  = function ($p) { return (bool) preg_match('/\.(jpe?g|png|webp)$/i', (string) $p); };
$metode_label = function ($m) { return translate('i18n_metode_' . strtolower(str_replace(array(' ', '-'), '_', $m))); };
?>
<style>
  .pm .foto-thumb { width:44px; height:44px; object-fit:cover; border-radius:6px; border:1px solid var(--line); }
  .pm .foto-pdf { width:44px; height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:6px; background:#fdecea; color:#b42318; font-size:1.2rem; }
  .pm .kat-row { font-size:.86rem; padding:6px 0; border-bottom:1px dashed var(--line); }
  .pm .kat-row:last-child { border-bottom:0; }
  .pm .jenis-card { border-left:4px solid transparent !important; }
  .pm .jenis-card.modal { border-left-color:#0d6efd !important; }
  .pm .jenis-card.pinjaman { border-left-color:#ffc107 !important; }
  .pm .jenis-card.pendapatan { border-left-color:#198754 !important; }
  #kf_preview img { max-height:160px; border-radius:8px; }
</style>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <div class="judul"><i class="bi bi-box-arrow-in-down-left me-2"></i><?= translate('i18n_km_judul') ?></div>
      <div class="meta"><?= translate('i18n_km_subjudul') ?></div>
    </div>
    <div class="d-flex flex-wrap gap-1">
      <?php if (!empty($izin['catat'])): ?>
        <button type="button" class="btn btn-light btn-sm fw-semibold btn-km-baru" data-bs-toggle="modal" data-bs-target="#modalKasMasuk"><i class="bi bi-plus-lg me-1"></i><?= translate('i18n_km_catat') ?></button>
      <?php endif; ?>
      <a href="<?= site_url('arus_kas') ?>" class="btn btn-outline-light btn-sm"><i class="bi bi-graph-up-arrow me-1"></i><?= translate('i18n_ak_judul') ?></a>
      <a href="<?= site_url('kas_masuk/kategori') ?>" class="btn btn-outline-light btn-sm"><i class="bi bi-tags me-1"></i><?= translate('i18n_kategori_2') ?></a>
    </div>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <div class="note mb-3"><i class="bi bi-info-circle me-1"></i><?= translate('i18n_km_info') ?>
    <a href="<?= site_url('piutang/penerimaan') ?>"><?= translate('i18n_penerimaan_pembayaran') ?></a>.
  </div>

  <form method="get" action="<?= site_url('kas_masuk') ?>" class="card mb-3">
    <div class="card-body row g-2 align-items-end">
      <div class="col-6 col-md-2"><label class="form-label small mb-1"><?= translate('dari') ?></label><input type="date" name="dari" value="<?= html_escape($f['dari']) ?>" class="form-control form-control-sm"></div>
      <div class="col-6 col-md-2"><label class="form-label small mb-1"><?= translate('i18n_sampai') ?></label><input type="date" name="sampai" value="<?= html_escape($f['sampai']) ?>" class="form-control form-control-sm"></div>
      <div class="col-6 col-md-2">
        <label class="form-label small mb-1"><?= translate('i18n_km_jenis') ?></label>
        <select name="jenis" class="form-select form-select-sm">
          <option value=""><?= translate('i18n_km_semua_jenis') ?></option>
          <?php foreach ($jenis_list as $j): ?><option value="<?= $j ?>" <?= $f['jenis'] === $j ? 'selected' : '' ?>><?= html_escape($jenis_label($j)) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small mb-1"><?= translate('i18n_kategori_2') ?></label>
        <select name="kategori" class="form-select form-select-sm">
          <option value=""><?= translate('i18n_semua_kategori') ?></option>
          <?php foreach ($kategori as $k): ?>
            <option value="<?= (int) $k->id ?>" <?= (int) $f['id_kategori'] === (int) $k->id ? 'selected' : '' ?>><?= html_escape($k->nama) ?><?= $k->aktif ? '' : (' ' . translate('i18n_nonaktif')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2"><label class="form-label small mb-1"><?= translate('cari') ?></label><input type="search" name="q" value="<?= html_escape($f['q']) ?>" class="form-control form-control-sm" placeholder="<?= translate('i18n_km_cari_placeholder') ?>"></div>
      <div class="col-md-2 d-grid"><button class="btn btn-sm btn-success"><i class="bi bi-funnel me-1"></i><?= translate('i18n_tampilkan') ?></button></div>
    </div>
  </form>

  <div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body">
      <div class="muted"><?= translate('i18n_km_total') ?></div>
      <div class="fs-5 fw-semibold text-success"><?= $rp($total) ?></div>
      <div class="muted"><?= $tgl($f['dari']) ?> – <?= $tgl($f['sampai']) ?> · <?= count($list) ?> <?= translate('i18n_km_transaksi') ?></div>
    </div></div></div>
    <?php foreach ($jenis_list as $j): ?>
      <div class="col-sm-6 col-xl-3"><div class="card h-100 jenis-card <?= $j ?>"><div class="card-body">
        <div class="muted"><i class="bi <?= $jenis_ikon[$j] ?> me-1"></i><?= html_escape($jenis_label($j)) ?></div>
        <div class="fs-5 fw-semibold"><?= $rp($per_jenis[$j]) ?></div>
        <div class="muted"><?= translate('i18n_km_ket_jenis_' . $j) ?></div>
      </div></div></div>
    <?php endforeach; ?>
  </div>

  <div class="row g-3">
    <div class="col-xl-9">
      <div class="card">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light"><tr><th><?= translate('i18n_km_bukti') ?></th><th><?= translate('i18n_tanggal_no') ?></th><th><?= translate('i18n_kategori_2') ?></th><th><?= translate('deskripsi') ?></th><th><?= translate('i18n_metode') ?></th><th class="text-end"><?= translate('i18n_nominal_2') ?></th><th></th></tr></thead>
            <tbody>
              <?php if (!$list): ?><tr><td colspan="7" class="text-center text-muted py-4"><?= translate('i18n_km_kosong') ?></td></tr><?php endif; ?>
              <?php foreach ($list as $m): ?>
                <tr>
                  <td>
                    <?php if ($m->bukti): ?>
                      <a href="<?= base_url($m->bukti) ?>" target="_blank" title="<?= translate('i18n_km_lihat_bukti') ?>">
                        <?php if ($is_img($m->bukti)): ?><img src="<?= base_url($m->bukti) ?>" class="foto-thumb" alt="" loading="lazy">
                        <?php else: ?><span class="foto-pdf"><i class="bi bi-file-earmark-pdf"></i></span><?php endif; ?>
                      </a>
                    <?php else: ?><span class="muted">-</span><?php endif; ?>
                  </td>
                  <td class="text-nowrap"><?= $tgl($m->tanggal) ?><div class="muted"><?= html_escape($m->no_bukti) ?></div></td>
                  <td>
                    <div><?= html_escape($m->nama_kategori ?: '-') ?></div>
                    <?= $jenis_badge($m->jenis) ?>
                  </td>
                  <td>
                    <?= html_escape($m->keterangan) ?>
                    <div class="muted"><?= translate('i18n_km_dari') ?> <?= html_escape($m->sumber) ?></div>
                    <?php if (!empty($m->nama_user)): ?><div class="muted"><?= translate('i18n_dicatat_2') ?> <?= html_escape($m->nama_user) ?></div><?php endif; ?>
                  </td>
                  <td>
                    <?= html_escape($m->metode ? $metode_label($m->metode) : '-') ?>
                    <?php if ($m->rekening_tujuan): ?><div class="muted"><?= html_escape($m->rekening_tujuan) ?></div><?php endif; ?>
                  </td>
                  <td class="text-end fw-semibold text-success"><?= $rp($m->nominal) ?></td>
                  <td class="text-end text-nowrap">
                    <?php if (!empty($izin['catat'])): ?>
                      <button type="button" class="btn btn-sm btn-outline-secondary py-0 btn-km-edit" data-bs-toggle="modal" data-bs-target="#modalKasMasuk" title="<?= translate('i18n_ubah') ?>"
                        data-id="<?= (int) $m->id ?>" data-no="<?= html_escape($m->no_bukti) ?>" data-tanggal="<?= html_escape($m->tanggal) ?>"
                        data-kategori="<?= (int) $m->id_kategori ?>" data-keterangan="<?= html_escape($m->keterangan) ?>"
                        data-sumber="<?= html_escape($m->sumber) ?>" data-nominal="<?= (float) $m->nominal ?>"
                        data-metode="<?= html_escape($m->metode ?? '') ?>" data-rekening="<?= html_escape($m->rekening_tujuan ?? '') ?>"
                        data-bukti="<?= $m->bukti ? html_escape(base_url($m->bukti)) : '' ?>"><i class="bi bi-pencil"></i></button>
                      <?= form_open('kas_masuk/hapus/' . (int) $m->id, array('class' => 'd-inline', 'onsubmit' => "return confirm('" . js_esc(translate('i18n_km_konfirmasi_hapus') . ' ' . $m->no_bukti . '?') . "')")) ?>
                        <input type="hidden" name="kembali" value="<?= html_escape($kembali) ?>">
                        <button class="btn btn-sm btn-outline-danger py-0" title="<?= translate('hapus') ?>"><i class="bi bi-trash"></i></button>
                      <?= form_close() ?>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <?php if ($list): ?>
              <tfoot class="table-light"><tr><th colspan="5"><?= translate('total') ?></th><th class="text-end text-success"><?= $rp($total) ?></th><th></th></tr></tfoot>
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
              <div class="d-flex justify-content-between gap-2"><span><?= html_escape($k->nama ?: '-') ?> <span class="muted">(<?= (int) $k->jumlah ?>)</span></span><b><?= $rp($k->total) ?></b></div>
              <div class="mini-bar"><div style="width:<?= $total > 0 ? round($k->total / $total * 100) : 0 ?>%"></div></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($izin['catat'])): ?>
<div class="modal fade" id="modalKasMasuk" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <?= form_open_multipart('kas_masuk/simpan/0', array('id' => 'formKasMasuk')) ?>
      <input type="hidden" name="kembali" value="<?= html_escape($kembali) ?>">
      <div class="modal-header">
        <h6 class="modal-title"><i class="bi bi-box-arrow-in-down-left me-2"></i><span id="kf_judul"><?= translate('i18n_km_catat') ?></span></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= translate('i18n_tutup') ?>"></button>
      </div>
      <div class="modal-body">
        <?php if (!$kategori_form): ?>
          <div class="alert alert-warning py-2 small"><?= translate('i18n_km_belum_ada_kategori') ?> <a href="<?= site_url('kas_masuk/kategori') ?>"><?= translate('i18n_km_kategori_judul') ?></a></div>
        <?php endif; ?>
        <div class="row g-2">
          <div class="col-6">
            <label class="form-label small mb-1"><?= translate('tanggal') ?></label>
            <input type="date" name="tanggal" id="kf_tanggal" class="form-control form-control-sm" max="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label small mb-1"><?= translate('i18n_kategori_2') ?></label>
            <select name="id_kategori" id="kf_kategori" class="form-select form-select-sm" required>
              <option value=""><?= translate('i18n_pilih') ?></option>
              <?php $grup = null; foreach ($kategori_form as $k):
                if ($k->jenis !== $grup): if ($grup !== null) echo '</optgroup>'; $grup = $k->jenis; ?>
                  <optgroup label="<?= html_escape($jenis_label($k->jenis)) ?>">
                <?php endif; ?>
                <option value="<?= (int) $k->id ?>"><?= html_escape($k->nama) ?></option>
              <?php endforeach; if ($grup !== null) echo '</optgroup>'; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label small mb-1"><?= translate('i18n_km_sumber') ?></label>
            <input type="text" name="sumber" id="kf_sumber" class="form-control form-control-sm" maxlength="100" placeholder="<?= translate('i18n_km_sumber_placeholder') ?>" required>
          </div>
          <div class="col-12">
            <label class="form-label small mb-1"><?= translate('deskripsi') ?></label>
            <input type="text" name="keterangan" id="kf_keterangan" class="form-control form-control-sm" maxlength="255" placeholder="<?= translate('i18n_km_keterangan_placeholder') ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label small mb-1"><?= translate('nominal(rp)') ?></label>
            <input type="number" name="nominal" id="kf_nominal" class="form-control form-control-sm" min="1" step="0.01" required>
          </div>
          <div class="col-6">
            <label class="form-label small mb-1"><?= translate('i18n_metode') ?></label>
            <select name="metode" id="kf_metode" class="form-select form-select-sm" required>
              <?php foreach ($metode_list as $mt): ?><option value="<?= html_escape($mt) ?>"><?= html_escape($metode_label($mt)) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label small mb-1"><?= translate('i18n_km_rekening_tujuan') ?> <span class="muted"><?= translate('i18n_opsional') ?></span></label>
            <input type="text" name="rekening_tujuan" id="kf_rekening" class="form-control form-control-sm" maxlength="100" placeholder="<?= translate('i18n_km_rekening_placeholder') ?>">
          </div>
          <div class="col-12">
            <label class="form-label small mb-1"><?= translate('i18n_km_bukti_label') ?> <span class="muted"><?= translate('i18n_opsional') ?></span></label>
            <input type="file" name="bukti" id="kf_bukti" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp,application/pdf">
            <div class="muted mt-1" id="kf_bukti_info"><?= translate('i18n_jpg_png_webp_pdf_maks_5mb_2') ?></div>
            <div id="kf_preview" class="mt-2"></div>
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
    var form = document.getElementById('formKasMasuk');
    var base = <?= json_encode(site_url('kas_masuk/simpan/')) ?>;
    var today = <?= json_encode(date('Y-m-d')) ?>;
    var $ = function (id) { return document.getElementById(id); };
    var bukti = $('kf_bukti'), preview = $('kf_preview');
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

    document.querySelectorAll('.btn-km-baru').forEach(function (btn) {
      btn.addEventListener('click', function () {
        form.reset();
        form.action = base + '0';
        $('kf_judul').textContent = '<?= tj('i18n_km_catat') ?>';
        $('kf_tanggal').value = today;
        $('kf_bukti_info').textContent = '<?= tj('i18n_jpg_png_webp_pdf_maks_5mb_2') ?>';
        tampilPreview('');
      });
    });

    document.querySelectorAll('.btn-km-edit').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var d = this.dataset;
        form.reset();
        form.action = base + d.id;
        $('kf_judul').textContent = '<?= tj('i18n_ubah') ?> ' + d.no;
        $('kf_tanggal').value = d.tanggal;
        $('kf_kategori').value = d.kategori;
        if ($('kf_kategori').value !== d.kategori) {   // kategori sudah nonaktif: tetap tampilkan
          var o = document.createElement('option'); o.value = d.kategori; o.textContent = '<?= tj('i18n_kategori_nonaktif') ?>';
          $('kf_kategori').appendChild(o); $('kf_kategori').value = d.kategori;
        }
        $('kf_sumber').value = d.sumber;
        $('kf_keterangan').value = d.keterangan;
        $('kf_nominal').value = d.nominal;
        $('kf_metode').value = d.metode;
        $('kf_rekening').value = d.rekening;
        $('kf_bukti_info').textContent = d.bukti ? '<?= tj('i18n_kosongkan_kalau_tidak_ganti_foto') ?>' : '<?= tj('i18n_jpg_png_webp_pdf_maks_5mb_2') ?>';
        tampilPreview(d.bukti, /\.pdf$/i.test(d.bukti));
      });
    });

    bukti.addEventListener('change', function () {
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
