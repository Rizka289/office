<!DOCTYPE html>
<html lang="<?= $this->config->item('current_lang') ?: 'id'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data User - Dashboard</title>
    <!-- Bootstrap 5 & Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --brand: #0b5d5b;
            --brand-dark: #083f3e;
            --brand-light: #e6f2f1;
            --accent: #c98a3e;
            --bg: #f4f6f7;
            --text-muted: #6b7280;
        }

        body {
            background: var(--bg);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #1f2937;
        }

        /* ===== Sidebar Styling ===== */
        .sidebar {
            background: linear-gradient(180deg, var(--brand-dark), var(--brand));
            min-height: 100vh;
            color: #fff;
            padding-top: 1.25rem;
        }

        .sidebar .brand {
            font-weight: 700;
            font-size: 1.05rem;
            padding: 0 1.25rem 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, .15);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: .6rem;
        }

        .sidebar .nav-link,
        .sidebar .nav-sub-link {
            color: rgba(255, 255, 255, .8);
            padding: .65rem 1.25rem;
            font-size: .92rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, .12);
            color: #fff;
            border-left: 3px solid var(--accent);
        }

        .sidebar .nav-link i.main-icon {
            width: 22px;
            text-align: center;
            margin-right: .5rem;
        }

        .sidebar .sub-menu {
            background: rgba(0, 0, 0, 0.15);
            padding-left: 0;
            list-style: none;
            margin-bottom: 0;
        }

        .sidebar .nav-sub-link {
            padding: .55rem 1.25rem .55rem 2.8rem;
            font-size: .85rem;
            color: rgba(255, 255, 255, .7);
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 8px;
        }

        .sidebar .nav-sub-link:hover,
        .sidebar .nav-sub-link.active {
            color: #fff;
            background: rgba(255, 255, 255, .08);
        }

        .sidebar .arrow-icon {
            font-size: .75rem;
            transition: transform 0.3s ease;
        }

        .sidebar [aria-expanded="true"] .arrow-icon {
            transform: rotate(90deg);
        }

        /* ===== Topbar & Profile Menu ===== */
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: .75rem 1rem;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .btn-hamburger {
            border: 1px solid #e5e7eb;
            background: #fff;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-menu {
            position: relative;
        }

        .profile-menu .profile-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            padding-top: 10px;
            min-width: 190px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(4px);
            transition: opacity .15s ease, transform .15s ease, visibility .15s;
            z-index: 1050;
        }

        .profile-menu:hover .profile-dropdown,
        .profile-menu.show .profile-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile-dropdown-inner {
            background: #fff;
            border: 1px solid #edf0f1;
            border-radius: 10px;
            box-shadow: 0 10px 28px rgba(15, 42, 41, .14);
            padding: .4rem 0;
        }

        .profile-dropdown-item {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .5rem 1rem;
            font-size: .85rem;
            color: #1f2937;
            text-decoration: none;
        }

        .profile-dropdown-item:hover {
            background: var(--brand-light);
            color: var(--brand);
        }

        .card-custom {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #edf0f1;
            box-shadow: 0 2px 10px rgba(15, 42, 41, .04);
        }

        .btn-brand {
            background: var(--brand);
            color: #fff;
            border: none;
        }

        .btn-brand:hover {
            background: var(--brand-dark);
            color: #fff;
        }
    </style>
</head>

<body>

    <!-- Hidden input CSRF token global, dipakai & di-refresh oleh semua request AJAX -->
    <input type="hidden" id="csrf_token_name" value="<?= $this->security->get_csrf_token_name(); ?>">
    <input type="hidden" id="csrf_token_hash" value="<?= $this->security->get_csrf_hash(); ?>">

    <!-- Navigasi Dinamis Sidebar -->
    <?php function render_navigation($menu_id = 'menuDesktop') { ?>
        <ul class="nav flex-column mb-auto" id="<?= $menu_id; ?>">
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#masterSubmenu_<?= $menu_id; ?>" role="button" aria-expanded="true" aria-controls="masterSubmenu_<?= $menu_id; ?>">
                    <div><i class="bi bi-database-fill-gear main-icon"></i> <?= function_exists('lang') ? lang('menu_master') : 'Master Data'; ?></div>
                    <i class="bi bi-chevron-right arrow-icon"></i>
                </a>
                <div class="collapse show" id="masterSubmenu_<?= $menu_id; ?>" data-bs-parent="#<?= $menu_id; ?>">
                    <ul class="sub-menu">
                        <li>
                            <a class="nav-sub-link active" href="<?= site_url('user'); ?>">
                                <i class="bi bi-people"></i> Data User
                            </a>
                        </li>
                        <li>
                            <a class="nav-sub-link" href="<?= site_url('supplier'); ?>">
                                <i class="bi bi-truck"></i> <?= function_exists('lang') ? lang('menu_supplier') : 'Supplier'; ?>
                            </a>
                        </li>
                        <li>
                            <a class="nav-sub-link" href="<?= site_url('pelanggan'); ?>">
                                <i class="bi bi-person"></i> <?= function_exists('lang') ? lang('menu_customer') : 'Pelanggan'; ?>
                            </a>
                        </li>
                        <li>
                            <a class="nav-sub-link" href="<?= site_url('gudang'); ?>">
                                <i class="bi bi-dot"></i> <?= function_exists('lang') ? lang('menu_warehouse') : 'Lokasi Gudang'; ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
    <?php } ?>

    <!-- Mobile Offcanvas Sidebar -->
    <div class="offcanvas offcanvas-start sidebar text-bg-dark" tabindex="-1" id="mobileSidebar">
        <div class="d-flex justify-content-between align-items-center px-3 pt-3">
            <div class="brand mb-0 pb-0 border-0">
                <div>PT Oupai Pintu<br>Jendela Indonesia</div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <hr class="text-white-50 mx-3">
        <?php render_navigation('menuMobile'); ?>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Desktop Sidebar -->
            <nav class="col-lg-2 sidebar d-none d-lg-flex flex-column p-0">
                <div class="brand">
                    <div>PT Oupai Pintu<br>Jendela Indonesia</div>
                </div>
                <?php render_navigation('menuDesktop'); ?>
                <div class="p-3 small text-white-50 border-top border-white border-opacity-25">
                    &copy; 2026 PT Oupai Pintu Jendela Indonesia
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-12 col-lg-10 px-0">
                <!-- Topbar -->
                <div class="topbar d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn-hamburger d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                            <i class="bi bi-list fs-4"></i>
                        </button>
                        <div>
                            <h5 class="mb-0">Data User</h5>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div class="profile-menu" tabindex="0">
                            <div class="profile-trigger d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-secondary bg-opacity-25 d-flex align-items-center justify-content-center" style="width:34px;height:34px;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div class="d-none d-md-block text-end lh-sm">
                                    <div class="fw-semibold" style="font-size:.85rem;"><?= $this->session->userdata('nama') ?: 'User'; ?></div>
                                    <div class="text-muted" style="font-size:.72rem;"><?= $this->session->userdata('username'); ?></div>
                                </div>
                            </div>
                            <div class="profile-dropdown">
                                <div class="profile-dropdown-inner">
                                    <a href="#" class="profile-dropdown-item"><i class="bi bi-person"></i> Profile</a>
                                    <a href="<?= site_url('login/logout'); ?>" class="profile-dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Grid View Content -->
                <div class="p-3 p-lg-4">
                    <div class="card card-custom p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold m-0">Daftar Pengguna</h6>
                            <!-- Trigger Modal Tambah User -->
                            <button type="button" class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
                                <i class="bi bi-plus-lg"></i> Tambah User
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Nama Lengkap</th>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>Tanggal Dibuat</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($users)): ?>
                                        <?php $no = 1; foreach ($users as $u): ?>
                                            <tr>
                                                <td><?= $no++; ?></td>
                                                <td class="fw-semibold"><?= html_escape($u['nama']); ?></td>
                                                <td><?= html_escape($u['username']); ?></td>
                                                <td><span class="badge bg-secondary"><?= html_escape($u['role'] ?? 'User'); ?></span></td>
                                                <td><?= html_escape($u['created_at'] ?? '-'); ?></td>
                                                <td class="text-center">
                                                    <!-- Tombol Edit -->
                                                    <button class="btn btn-sm btn-outline-warning btn-edit" data-id="<?= $u['id']; ?>" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <!-- Tombol Delete -->
                                                    <button class="btn btn-sm btn-outline-danger btn-delete" data-id="<?= $u['id']; ?>" data-nama="<?= html_escape($u['nama']); ?>" title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">Data user tidak ditemukan.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- ================= MODAL TAMBAH USER ================= -->
    <div class="modal fade" id="modalTambahUser" tabindex="-1" aria-labelledby="modalTambahUserLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--brand); color: #fff;">
                    <h5 class="modal-title fs-6" id="modalTambahUserLabel"><i class="bi bi-person-plus"></i> Tambah User Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formTambahUser" autocomplete="off">
                    <div class="modal-body">
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" class="csrf-field" value="<?= $this->security->get_csrf_hash(); ?>">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control form-control-sm" placeholder="Masukkan nama lengkap" autocomplete="off" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Username</label>
                            <input type="text" name="username" class="form-control form-control-sm" placeholder="Masukkan username" autocomplete="new-password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" name="password" class="form-control form-control-sm" placeholder="Masukkan password" autocomplete="new-password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Role</label>
                            <select name="role" class="form-select form-select-sm" required>
                                <option value="">-- Pilih Role --</option>
                                <option value="super_admin">super_admin</option>
                                <option value="supervisor">supervisor</option>
                                <option value="staff_gudang">staff_gudang</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-brand" id="btnSimpan">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================= MODAL EDIT USER ================= -->
    <div class="modal fade" id="modalEditUser" tabindex="-1" aria-labelledby="modalEditUserLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--brand); color: #fff;">
                    <h5 class="modal-title fs-6" id="modalEditUserLabel"><i class="bi bi-pencil-square"></i> Edit Data User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formEditUser" autocomplete="off">
                    <div class="modal-body">
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" class="csrf-field" value="<?= $this->security->get_csrf_hash(); ?>">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nama Lengkap</label>
                            <input type="text" name="nama" id="edit_nama" class="form-control form-control-sm" placeholder="Masukkan nama lengkap" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Username</label>
                            <input type="text" name="username" id="edit_username" class="form-control form-control-sm" placeholder="Masukkan username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Password Baru</label>
                            <input type="password" name="password" id="edit_password" class="form-control form-control-sm" placeholder="Kosongkan jika tidak ingin mengubah password" autocomplete="new-password">
                            <small class="text-muted" style="font-size: 0.75rem;">*Isi hanya jika ingin mengganti password</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Role</label>
                            <select name="role" id="edit_role" class="form-select form-select-sm" required>
                                <option value="">-- Pilih Role --</option>
                                <option value="super_admin">super_admin</option>
                                <option value="supervisor">supervisor</option>
                                <option value="staff_gudang">staff_gudang</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-brand" id="btnUpdate">Update Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JS Scripts (JQuery & Bootstrap 5) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {

            // Helper: ambil object {csrf_token_name: hash} untuk request tanpa form (mis. Delete)
            function getCsrfData() {
                var data = {};
                data[$('#csrf_token_name').val()] = $('#csrf_token_hash').val();
                return data;
            }

            // Helper: CI3 regenerate CSRF hash baru tiap request selesai.
            // Update semua hidden field csrf di halaman supaya request berikutnya tidak ditolak.
            function refreshCsrf(newHash) {
                if (!newHash) return;
                $('#csrf_token_hash').val(newHash);
                $('.csrf-field').val(newHash);
            }

            // Reset modal Tambah User setiap kali dibuka/ditutup
            $('#modalTambahUser').on('show.bs.modal hidden.bs.modal', function () {
                $('#formTambahUser')[0].reset();
                $('#formTambahUser input').val('');
                $('.csrf-field').val($('#csrf_token_hash').val());
            });

            // 1. AJAX TAMBAH USER
            $('#formTambahUser').on('submit', function(e) {
                e.preventDefault();
                $('#btnSimpan').prop('disabled', true).text('Menyimpan...');

                $.ajax({
                    url: "<?= site_url('super_admin/user/simpan'); ?>",
                    type: "POST",
                    data: $(this).serialize(),
                    dataType: "JSON",
                    success: function(response) {
                        refreshCsrf(response.csrf_hash);
                        if (response.status) {
                            alert(response.message);
                            $('#modalTambahUser').modal('hide');
                            location.reload();
                        } else {
                            alert(response.message);
                            $('#btnSimpan').prop('disabled', false).text('Simpan Data');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Terjadi kesalahan saat menyimpan data.');
                        console.error(error);
                        $('#btnSimpan').prop('disabled', false).text('Simpan Data');
                    }
                });
            });

            // 2. AJAX AMBIL DATA USER BY ID (AMBIL UNTUK EDIT)
            $(document).on('click', '.btn-edit', function() {
                var id = $(this).data('id');

                $.ajax({
                    url: "<?= site_url('super_admin/user/get_by_id/'); ?>" + id,
                    type: "GET",
                    dataType: "JSON",
                    success: function(response) {
                        if (response.status) {
                            $('#edit_id').val(response.data.id);
                            $('#edit_nama').val(response.data.nama);
                            $('#edit_username').val(response.data.username);
                            $('#edit_password').val(''); 
                            $('#edit_role').val(response.data.role);

                            $('#modalEditUser').modal('show');
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert('Terjadi kesalahan saat mengambil data.');
                    }
                });
            });

            // 3. AJAX UPDATE USER
            $('#formEditUser').on('submit', function(e) {
                e.preventDefault();
                $('#btnUpdate').prop('disabled', true).text('Memperbarui...');

                $.ajax({
                    url: "<?= site_url('super_admin/user/update'); ?>",
                    type: "POST",
                    data: $(this).serialize(),
                    dataType: "JSON",
                    success: function(response) {
                        refreshCsrf(response.csrf_hash);
                        if (response.status) {
                            alert(response.message);
                            $('#modalEditUser').modal('hide');
                            location.reload();
                        } else {
                            alert(response.message);
                            $('#btnUpdate').prop('disabled', false).text('Update Data');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Terjadi kesalahan saat memperbarui data.');
                        console.error(error);
                        $('#btnUpdate').prop('disabled', false).text('Update Data');
                    }
                });
            });

            // 4. AJAX DELETE USER
            $(document).on('click', '.btn-delete', function() {
                var id = $(this).data('id');
                var nama = $(this).data('nama');

                if (confirm('Apakah Anda yakin ingin menghapus user "' + nama + '"?')) {
                    $.ajax({
                        url: "<?= site_url('super_admin/user/delete/'); ?>" + id,
                        type: "POST",
                        data: getCsrfData(),
                        dataType: "JSON",
                        success: function(response) {
                            refreshCsrf(response.csrf_hash);
                            if (response.status) {
                                alert(response.message);
                                location.reload();
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            alert('Terjadi kesalahan saat menghapus data.');
                            console.error(error);
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>