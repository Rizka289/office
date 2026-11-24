<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('pemenuhan/_style');
?>
<div class="pm container-fluid py-3">
  <div class="hero mb-3">
    <div class="judul"><i class="bi bi-box-seam me-2"></i><?= translate('i18n_ksy_menu') ?></div>
  </div>
  <div class="alert alert-warning">
    <div class="fw-semibold mb-1"><i class="bi bi-database-exclamation me-1"></i>Database konsinyasi belum disiapkan</div>
    Jalankan file <code>database/konsinyasi.sql</code> di phpMyAdmin (tab SQL, pilih database aplikasi), lalu buka menu ini lagi.
  </div>
</div>
