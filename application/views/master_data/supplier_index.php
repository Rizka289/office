<!-- ================= GRID DATA SUPPLIER ================= -->
<div class="card card-custom p-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="fw-bold m-0"></h6>
        <!-- Trigger Modal Tambah User -->
        <button type="button" class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#modalTambahSup">
            <i class="bi bi-plus-lg"></i> <?= translate('add') ?>
        </button>
    </div>

    <!-- ================= SEARCH BOX ================= -->
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" id="searchSupplier" class="form-control" placeholder="Cari nama / kontak / alamat...">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:60px;"><?= translate('no') ?></th>
                    <th><?= translate('nama') ?></th>
                    <th><?= translate('kontak') ?></th>
                    <th><?= translate('alamat') ?></th>
                    <th><?= translate('deskripsi') ?></th>
                    <th class="text-center" style="width:120px;"><?= translate('aksi') ?></th>
                </tr>
            </thead>
            <tbody id="supplierTableBody">
                <tr>
                    <td colspan="5" class="text-center text-muted">Memuat data...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ================= INFO + PAGINATION ================= -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted" id="supplierInfo"></small>
        <nav aria-label="Pagination Supplier">
            <ul class="pagination pagination-sm mb-0" id="supplierPagination"></ul>
        </nav>
    </div>
</div>

<!-- ================= MODAL TAMBAH USER ================= -->
<div class="modal fade" id="modalTambahSup" tabindex="-1" aria-labelledby="modalTambahSupLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--brand); color: #fff;">
                <h5 class="modal-title fs-6" id="modalTambahSupLabel"><i class="bi bi-person-plus"></i> <?= translate('add') . ' ' . translate('list_pemasok') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formTambahSup" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" class="csrf-field" value="<?= $this->security->get_csrf_hash(); ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('nama') ?></label>
                        <input type="text" name="nama" class="form-control form-control-sm" placeholder="Masukkan nama supplier" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('kontak') ?></label>
                        <input type="text" inputmode="numeric" pattern="[0-9]*" name="kontak" class="form-control form-control-sm input-numeric-only" placeholder="Masukkan kontak" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('deskripsi') ?></label>
                        <textarea name="deskripsi" class="form-control form-control-sm" rows="3" placeholder="Masukkan Deskripsi" autocomplete="off" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('alamat') ?></label>
                        <textarea name="alamat" class="form-control form-control-sm" rows="3" placeholder="Masukkan alamat" autocomplete="off" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"><?= translate('btn_batal') ?></button>
                    <button type="submit" class="btn btn-sm btn-brand" id="btnSimpan"><?= translate('button_save') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= MODAL EDIT USER ================= -->
<div class="modal fade" id="modalEditSup" tabindex="-1" aria-labelledby="modalEditSupLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--brand); color: #fff;">
                <h5 class="modal-title fs-6" id="modalEditUserLabel"><i class="bi bi-pencil-square"></i><?= translate('update') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditSup" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" class="csrf-field" value="<?= $this->security->get_csrf_hash(); ?>">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('nama') ?></label>
                        <input type="text" name="nama" id="edit_nama" class="form-control form-control-sm" placeholder="Masukkan nama supplier" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('kontak') ?></label>
                        <input type="text" inputmode="numeric" pattern="[0-9]*" name="kontak" id="edit_kontak" class="form-control form-control-sm input-numeric-only" placeholder="Masukkan kontak" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('deskripsi') ?></label>
                        <textarea name="deskripsi" id="edit_deskripsi" class="form-control form-control-sm" rows="3" placeholder="Masukkan deskripsi" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('alamat') ?></label>
                        <textarea name="alamat" id="edit_alamat" class="form-control form-control-sm" rows="3" placeholder="Masukkan alamat" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"><?= translate('btn_batal') ?></button>
                    <button type="submit" class="btn btn-sm btn-brand" id="btnUpdate"><?= translate('update') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= JS Khusus Halaman Supplier (bukan bagian layout) ================= -->
<script>
    $(document).ready(function() {

        var currentPage = 1;
        var currentSearch = '';
        var searchTimer = null;

        // Ambil url list_data sekali saja
        var listDataUrl = "<?= site_url('supplier/list_data'); ?>";

        function escapeHtml(str) {
            return $('<div>').text(str == null ? '' : str).html();
        }

        // Render baris tabel dari data JSON
        function renderRows(rows, page, perPage) {
            var $tbody = $('#supplierTableBody');
            $tbody.empty();

            if (!rows || rows.length === 0) {
                $tbody.append('<tr><td colspan="5" class="text-center text-muted">Data supplier tidak ditemukan.</td></tr>');
                return;
            }

            var startNo = (page - 1) * perPage;
            rows.forEach(function(sup, idx) {
                var no = startNo + idx + 1;
                var tr = '<tr>' +
                    '<td>' + no + '</td>' +
                    '<td class="fw-semibold">' + escapeHtml(sup.nama) + '</td>' +
                    '<td>' + escapeHtml(sup.kontak) + '</td>' +
                    '<td>' + escapeHtml(sup.alamat) + '</td>' +
                    '<td>' + escapeHtml(sup.deskripsi) + '</td>' +
                    '<td class="text-center">' +
                    '<button class="btn btn-sm btn-outline-warning btn-edit" data-id="' + sup.id + '" title="Edit"><i class="bi bi-pencil"></i></button> ' +
                    '<button class="btn btn-sm btn-outline-danger btn-delete" data-id="' + sup.id + '" data-nama="' + escapeHtml(sup.nama) + '" title="Hapus"><i class="bi bi-trash"></i></button>' +
                    '</td>' +
                    '</tr>';
                $tbody.append(tr);
            });
        }

        // Render kontrol pagination
        function renderPagination(currentPage, totalPages) {
            var $pg = $('#supplierPagination');
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
                        $('#supplierInfo').text('Menampilkan ' + start + '-' + end + ' dari ' + response.total + ' data');
                    } else {
                        $('#supplierTableBody').html('<tr><td colspan="5" class="text-center text-danger">Gagal memuat data.</td></tr>');
                    }
                },
                error: function() {
                    $('#supplierTableBody').html('<tr><td colspan="5" class="text-center text-danger">Terjadi kesalahan saat memuat data.</td></tr>');
                }
            });
        }

        // Muat data pertama kali (page 1, tanpa search)
        loadData(1, '');

        // Klik tombol pagination
        $(document).on('click', '#supplierPagination a.page-link', function(e) {
            e.preventDefault();
            var $li = $(this).closest('li');
            if ($li.hasClass('disabled') || $li.hasClass('active')) {
                return;
            }
            var page = parseInt($(this).data('page'), 10);
            loadData(page, currentSearch);
        });

        // Search dengan debounce (tunggu user berhenti ngetik 400ms), reset ke page 1
        $('#searchSupplier').on('keyup', function() {
            var keyword = $(this).val();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                loadData(1, keyword);
            }, 400);
        });

        // Reset modal Tambah Supplier setiap kali dibuka/ditutup
        // (jangan ikut mengosongkan field CSRF, hanya field input data)
        $('#modalTambahSup').on('show.bs.modal hidden.bs.modal', function() {
            $('#formTambahSup')[0].reset();
            $('#formTambahSup input:not(.csrf-field), #formTambahSup textarea').val('');
        });

        // Hanya izinkan angka pada semua field kontak (tambah & edit)
        $(document).on('input', '.input-numeric-only', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // 1. AJAX TAMBAH USER
        $('#formTambahSup').on('submit', function(e) {
            e.preventDefault();
            $('#btnSimpan').prop('disabled', true).text('Menyimpan...');

            $.ajax({
                url: "<?= site_url('supplier/simpan'); ?>",
                type: "POST",
                data: $(this).serialize(),
                dataType: "JSON",
                success: function(response) {
                    refreshCsrf(response.csrf_hash);
                    if (response.status) {
                        alert(response.message);
                        $('#modalTambahSup').modal('hide');
                        loadData(1, currentSearch); // kembali ke halaman 1 supaya data baru terlihat
                    } else {
                        alert(response.message);
                    }
                    $('#btnSimpan').prop('disabled', false).text('Simpan Data');
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
                url: "<?= site_url('supplier/get_by_id/'); ?>" + id,
                type: "GET",
                dataType: "JSON",
                success: function(response) {
                    if (response.status) {
                        $('#edit_id').val(response.data.id);
                        $('#edit_nama').val(response.data.nama);
                        $('#edit_kontak').val(response.data.kontak);
                        $('#edit_deskripsi').val(response.data.deskripsi);
                        $('#edit_alamat').val(response.data.alamat);

                        $('#modalEditSup').modal('show');
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
        $('#formEditSup').on('submit', function(e) {
            e.preventDefault();
            $('#btnUpdate').prop('disabled', true).text('Memperbarui...');

            $.ajax({
                url: "<?= site_url('supplier/update'); ?>",
                type: "POST",
                data: $(this).serialize(),
                dataType: "JSON",
                success: function(response) {
                    refreshCsrf(response.csrf_hash);
                    if (response.status) {
                        alert(response.message);
                        $('#modalEditSup').modal('hide');
                        loadData(currentPage, currentSearch); // tetap di halaman yang sama
                    } else {
                        alert(response.message);
                    }
                    $('#btnUpdate').prop('disabled', false).text('Update Data');
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

            if (confirm('Apakah Anda yakin ingin menghapus supplier "' + nama + '"?')) {
                $.ajax({
                    url: "<?= site_url('supplier/delete/'); ?>" + id,
                    type: "POST",
                    data: getCsrfData(),
                    dataType: "JSON",
                    success: function(response) {
                        refreshCsrf(response.csrf_hash);
                        if (response.status) {
                            alert(response.message);
                            // Jika ini item terakhir di halaman & bukan halaman 1, mundur 1 halaman
                            var rowsLeft = $('#supplierTableBody tr').length - 1;
                            var targetPage = (rowsLeft <= 0 && currentPage > 1) ? currentPage - 1 : currentPage;
                            loadData(targetPage, currentSearch);
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