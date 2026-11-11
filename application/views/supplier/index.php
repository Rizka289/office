<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('pemenuhan/_style');
?>
<div class="pm container-fluid py-3">

  <div class="hero mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <div class="judul"><i class="bi bi-building me-2"></i>Data Supplier</div>
      <div class="meta">Dipakai untuk PO beli dan dropship</div>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= site_url('pemenuhan') ?>" class="btn btn-outline-light btn-sm"><i class="bi bi-diagram-3 me-1"></i>Proses pesanan</a>
      <button type="button" class="btn btn-light btn-sm" id="btn_baru"><i class="bi bi-plus-lg me-1"></i>Tambah supplier</button>
    </div>
  </div>

  <?php $this->load->view('pemenuhan/_flash'); ?>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Nama</th><th>Kontak</th><th>Telp</th><th>Email</th><th>Alamat</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php if (!$supplier_list): ?><tr><td colspan="7" class="text-center text-muted py-4">Belum ada supplier.</td></tr><?php endif; ?>
          <?php foreach ($supplier_list as $s):
            $aktif = !isset($s->aktif) || (int) $s->aktif === 1; ?>
            <tr>
              <td class="fw-semibold"><?= html_escape($s->nama) ?></td>
              <td><?= html_escape($s->kontak ?? '') ?></td>
              <td><?= html_escape($s->telp ?? '') ?></td>
              <td><?= html_escape($s->email ?? '') ?></td>
              <td class="small"><?= nl2br(html_escape($s->alamat ?? '')) ?></td>
              <td><?= $aktif ? '<span class="badge text-bg-success fw-normal">Aktif</span>' : '<span class="badge text-bg-secondary fw-normal">Nonaktif</span>' ?></td>
              <td class="text-end text-nowrap">
                <button type="button" class="btn btn-sm btn-outline-primary py-0 btn-edit"
                  data-s='<?= html_escape(json_encode(array(
                    'id' => (int) $s->id, 'nama' => $s->nama, 'kontak' => $s->kontak ?? '', 'telp' => $s->telp ?? '',
                    'email' => $s->email ?? '', 'alamat' => $s->alamat ?? '', 'aktif' => $aktif ? 1 : 0,
                  ), JSON_UNESCAPED_UNICODE)) ?>'>Ubah</button>
                <?= form_open('supplier/hapus/' . (int) $s->id, array('class' => 'd-inline', 'onsubmit' => "return confirm('Hapus supplier ini?')")) ?>
                <button type="submit" class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
                <?= form_close() ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="modal_sup" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <?= form_open('supplier/simpan', array('autocomplete' => 'off')) ?>
      <input type="hidden" name="id" id="f_id" value="0">
      <div class="modal-header">
        <h5 class="modal-title" id="f_judul">Tambah supplier</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Nama <span class="text-danger">*</span></label><input type="text" name="nama" id="f_nama" class="form-control" maxlength="150" required></div>
        <div class="row g-2 mb-2">
          <div class="col-6"><label class="form-label">Kontak person</label><input type="text" name="kontak" id="f_kontak" class="form-control" maxlength="100"></div>
          <div class="col-6"><label class="form-label">Telp / WA</label><input type="text" name="telp" id="f_telp" class="form-control" maxlength="50"></div>
        </div>
        <div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" id="f_email" class="form-control" maxlength="100"></div>
        <div class="mb-2"><label class="form-label">Alamat</label><textarea name="alamat" id="f_alamat" class="form-control" rows="2"></textarea></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="aktif" value="1" id="f_aktif" checked><label class="form-check-label" for="f_aktif">Aktif</label></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
      <?= form_close() ?>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('modal_sup');
    var modal = new bootstrap.Modal(el);
    function isi(s) {
      document.getElementById('f_judul').textContent = s.id ? 'Ubah supplier' : 'Tambah supplier';
      ['id', 'nama', 'kontak', 'telp', 'email', 'alamat'].forEach(function (k) {
        document.getElementById('f_' + k).value = s[k] == null ? '' : s[k];
      });
      document.getElementById('f_aktif').checked = !!s.aktif;
      modal.show();
    }
    document.getElementById('btn_baru').addEventListener('click', function () {
      isi({id: 0, nama: '', kontak: '', telp: '', email: '', alamat: '', aktif: 1});
    });
    document.querySelectorAll('.btn-edit').forEach(function (b) {
      b.addEventListener('click', function () { isi(JSON.parse(this.getAttribute('data-s'))); });
    });
  });
</script>
