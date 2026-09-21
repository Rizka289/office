<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card card-custom p-4">
            <h4 class="mb-3 fw-bold"><i class="bi bi-person-lines-fill me-2"></i><?= translate('profile') ?></h4>

            <?php if ($this->session->flashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                    <?= $this->session->flashdata('success'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    <?= $this->session->flashdata('error'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="<?= site_url('profile/update'); ?>" method="post" enctype="multipart/form-data">
                <!-- CSRF Token -->
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

                <!-- Tampilan Foto Profil saat ini -->
                <div class="text-center mb-4">
                    <?php 
                        $foto_path = (!empty($user->foto) && file_exists(FCPATH . 'uploads/profiles/' . $user->foto)) 
                            ? base_url('uploads/profiles/' . $user->foto) 
                            : base_url('assets/images/default-avatar.png'); // Siapkan foto default jika kosong
                    ?>
                    <img src="<?= $foto_path; ?>" alt="Foto Profil" class="rounded-circle img-thumbnail mb-2" style="width: 120px; height: 120px; object-fit: cover;">
                    <div>
                        <label for="foto" class="btn btn-sm btn-outline-secondary mt-1">
                            <i class="bi bi-camera me-1"></i> Ubah Foto Profil
                        </label>
                        <input type="file" name="foto" id="foto" class="d-none" accept="image/*" onchange="previewImage(this)">
                    </div>
                    <small class="text-muted d-block mt-1">Format: JPG, PNG, WEBP (Max: 2MB)</small>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold"><?= translate('form_full_name') ?></label>
                    <input type="text" name="nama" class="form-control" value="<?= set_value('nama', $user->nama); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold"><?= translate('username') ?></label>
                    <input type="text" name="username" class="form-control" value="<?= set_value('username', $user->username); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold"><?= translate('hak_akses') ?></label>
                    <input type="text" class="form-control bg-light" value="<?= strtoupper($user->role); ?>" readonly>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold"><?= translate('new_pwd') ?> <span class="text-muted fw-normal">(Opsional)</span></label>
                    <input type="password" name="password" class="form-control" placeholder="<?= translate('placeholder') ?>">
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= site_url('dashboard'); ?>" class="btn btn-light"><?= translate('btn_batal') ?></a>
                    <button type="submit" class="btn btn-brand px-4"><?= translate('button_save') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Script sederhana untuk preview foto sebelum di-submit
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('.img-thumbnail').attr('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>