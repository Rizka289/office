<!-- ================= GRID DATA USER ================= -->
<div class="card card-custom p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold m-0"><?= translate('app_list') ?></h6>
        <!-- Trigger Modal Tambah User -->
        <button type="button" class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
            <i class="bi bi-plus-lg"></i> <?= translate('add') ?>
        </button>
    </div>

    <!-- ================= SEARCH BOX ================= -->
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" id="searchUser" class="form-control" placeholder="Cari nama / username ...">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th><?= translate('no') ?></th>
                    <th><?= translate('nama_lengkap') ?></th>
                    <th><?= translate('username') ?></th>
                    <th><?= translate('hak_akses') ?></th>
                    <th class="text-center"><?= translate('aksi') ?></th>
                </tr>
            </thead>
            <tbody id="userTableBody">
                <tr>
                    <td colspan="5" class="text-center text-muted">Memuat data...</td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- ================= INFO + PAGINATION ================= -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted" id="customerInfo"></small>
        <nav aria-label="Pagination Users">
            <ul class="pagination pagination-sm mb-0" id="userPagination"></ul>
        </nav>
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
                            <option value="super_admin">super admin</option>
                            <option value="supervisor">supervisor</option>
                            <option value="staff_purchasing">staff purchasing</option>
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
                            <option value="super_admin">super admin</option>
                            <option value="supervisor">supervisor</option>
                            <option value="staff_purchasing">staff purchasing</option>
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

<!-- ================= JS Khusus Halaman User (bukan bagian layout) ================= -->
<script>
    $(document).ready(function() {
        var currentPage = 1;
        var currentSearch = '';
        var searchTimer = null;

        // Ambil url list_data sekali saja
        var listDataUrl = "<?= site_url('user/list_data'); ?>";

        function escapeHtml(str) {
            return $('<div>').text(str == null ? '' : str).html();
        }

        // Render baris tabel dari data JSON
        function renderRows(rows, page, perPage) {
            var $tbody = $('#userTableBody');
            $tbody.empty();

            if (!rows || rows.length === 0) {
                $tbody.append('<tr><td colspan="5" class="text-center text-muted">Data user tidak ditemukan.</td></tr>');
                return;
            }

            var startNo = (page - 1) * perPage;
            rows.forEach(function(user, idx) {
                var no = startNo + idx + 1;
                var tr = '<tr>' +
                    '<td>' + no + '</td>' +
                    '<td class="fw-semibold">' + escapeHtml(user.nama) + '</td>' +
                    '<td>' + escapeHtml(user.username) + '</td>' +
                    '<td>' + escapeHtml(user.role) + '</td>' +
                    '<td class="text-center">' +
                    '<button class="btn btn-sm btn-outline-warning btn-edit" data-id="' + user.id + '" title="Edit"><i class="bi bi-pencil"></i></button> ' +
                    '<button class="btn btn-sm btn-outline-danger btn-delete" data-id="' + user.id + '" data-nama="' + escapeHtml(user.nama) + '" title="Hapus"><i class="bi bi-trash"></i></button>' +
                    '</td>' +
                    '</tr>';
                $tbody.append(tr);
            });
        }

        // Render kontrol pagination
        function renderPagination(currentPage, totalPages) {
            var $pg = $('#userPagination');
            $pg.empty();

            if (totalPages <= 1) {
                return;
            }

            function pageItem(label, page, disabled, active) {
                return '<li class="page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '') + '">' +
                    '<a class="page-link" href="#" data-page="' + page + '">' + label + '</a></li>';
            }

            $pg.append(pageItem('&laquo;', currentPage - 1, currentPage <= 1, false));

            for (var i = 1; i <= totalPages; i++) {
                $pg.append(pageItem(i, i, false, i === currentPage));
            }

            $pg.append(pageItem('&raquo;', currentPage + 1, currentPage >= totalPages, false));
        }

        // Ambil data dari server (search + pagination)
        function loadData(page, search) {
            currentPage = page;
            currentSearch = search;

            $.ajax({
                url: listDataUrl,
                type: 'GET',
                data: {
                    page: page,
                    search: search
                },
                dataType: 'JSON',
                success: function(response) {
                    if (response.status) {
                        renderRows(response.data, response.current_page, response.per_page);
                        renderPagination(response.current_page, response.total_pages);

                        var start = response.total === 0 ? 0 : ((response.current_page - 1) * response.per_page) + 1;
                        var end = Math.min(response.current_page * response.per_page, response.total);
                        $('#userInfo').text('Menampilkan ' + start + '-' + end + ' dari ' + response.total + ' data');
                    } else {
                        $('#userTableBody').html('<tr><td colspan="5" class="text-center text-danger">Gagal memuat data.</td></tr>');
                    }
                },
                error: function() {
                    $('#userTableBody').html('<tr><td colspan="5" class="text-center text-danger">Terjadi kesalahan saat memuat data.</td></tr>');
                }
            });
        }

        // Muat data pertama kali (page 1, tanpa search)
        loadData(1, '');

        // Klik tombol pagination
        $(document).on('click', '#userPagination a.page-link', function(e) {
            e.preventDefault();
            var $li = $(this).closest('li');
            if ($li.hasClass('disabled') || $li.hasClass('active')) {
                return;
            }
            var page = parseInt($(this).data('page'), 10);
            loadData(page, currentSearch);
        });

        // Search dengan debounce (tunggu user berhenti ngetik 400ms), reset ke page 1
        $('#searchUser').on('keyup', function() {
            var keyword = $(this).val();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                loadData(1, keyword);
            }, 400);
        });


        // Reset modal Tambah User setiap kali dibuka/ditutup
        $('#modalTambahUser').on('show.bs.modal hidden.bs.modal', function() {
            $('#formTambahUser')[0].reset();
            $('#formTambahUser input').val('');
            $('.csrf-field').val($('#csrf_token_hash').val());
        });

        // 1. AJAX TAMBAH USER
        $('#formTambahUser').on('submit', function(e) {
            e.preventDefault();
            $('#btnSimpan').prop('disabled', true).text('Menyimpan...');

            $.ajax({
                url: "<?= site_url('user/simpan'); ?>",
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
                url: "<?= site_url('user/get_by_id/'); ?>" + id,
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
                url: "<?= site_url('user/update'); ?>",
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
                    url: "<?= site_url('user/delete/'); ?>" + id,
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