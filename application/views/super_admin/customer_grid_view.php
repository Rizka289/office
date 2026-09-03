<!-- ================= GRID DATA CUSTOMER ================= -->
<div class="card card-custom p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold m-0"></h6>
        <!-- Trigger Modal Tambah Customer -->
        <button type="button" class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#modalTambahCustomer">
            <i class="bi bi-plus-lg"></i> <?= translate('add') ?>
        </button>
    </div>

    <!-- ================= SEARCH BOX ================= -->
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" id="searchCustomer" class="form-control" placeholder="Cari nama / kontak / alamat...">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th><?= translate('no') ?></th>
                    <th><?= translate('nama') ?></th>
                    <th><?= translate('kontak') ?></th>
                    <th><?= translate('alamat') ?></th>
                    <th class="text-center"><?= translate('aksi') ?></th>
                </tr>
            </thead>
            <tbody id="customerTableBody">
                <tr>
                    <td colspan="5" class="text-center text-muted">Memuat data...</td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- ================= INFO + PAGINATION ================= -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted" id="customerInfo"></small>
        <nav aria-label="Pagination Customer">
            <ul class="pagination pagination-sm mb-0" id="customerPagination"></ul>
        </nav>
    </div>
</div>

<!-- ================= MODAL TAMBAH CUSTOMER ================= -->
<div class="modal fade" id="modalTambahCustomer" tabindex="-1" aria-labelledby="modalTambahCustomerLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--brand); color: #fff;">
                <h5 class="modal-title fs-6" id="modalTambahCustomerLabel"><i class="bi bi-person-plus"></i> Tambah Customer Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formTambahCustomer" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" class="csrf-field" value="<?= $this->security->get_csrf_hash(); ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Customer</label>
                        <input type="text" name="nama" class="form-control form-control-sm" placeholder="Masukkan nama customer" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kontak</label>
                        <input type="text" inputmode="numeric" pattern="[0-9]*" name="kontak" class="form-control form-control-sm input-numeric-only" placeholder="Masukkan kontak" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Alamat</label>
                        <textarea name="alamat" class="form-control form-control-sm" rows="3" placeholder="Masukkan alamat" autocomplete="off" required></textarea>
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

<!-- ================= MODAL EDIT CUSTOMER ================= -->
<div class="modal fade" id="modalEditCustomer" tabindex="-1" aria-labelledby="modalEditCustomerLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--brand); color: #fff;">
                <h5 class="modal-title fs-6" id="modalEditUserLabel"><i class="bi bi-pencil-square"></i> Edit Data Customer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditCustomer" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" class="csrf-field" value="<?= $this->security->get_csrf_hash(); ?>">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Customer</label>
                        <input type="text" name="nama" id="edit_nama" class="form-control form-control-sm" placeholder="Masukkan nama customer" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kontak</label>
                        <input type="text" inputmode="numeric" pattern="[0-9]*" name="kontak" id="edit_kontak" class="form-control form-control-sm input-numeric-only" placeholder="Masukkan kontak" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Alamat</label>
                        <textarea name="alamat" id="edit_alamat" class="form-control form-control-sm" rows="3" placeholder="Masukkan alamat" required></textarea>
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
        var listDataUrl = "<?= site_url('customer/list_data'); ?>";

        function escapeHtml(str) {
            return $('<div>').text(str == null ? '' : str).html();
        }

        // Render baris tabel dari data JSON
        function renderRows(rows, page, perPage) {
            var $tbody = $('#customerTableBody');
            $tbody.empty();

            if (!rows || rows.length === 0) {
                $tbody.append('<tr><td colspan="5" class="text-center text-muted">Data customer tidak ditemukan.</td></tr>');
                return;
            }

            var startNo = (page - 1) * perPage;
            rows.forEach(function(cus, idx) {
                var no = startNo + idx + 1;
                var tr = '<tr>' +
                    '<td>' + no + '</td>' +
                    '<td class="fw-semibold">' + escapeHtml(cus.nama) + '</td>' +
                    '<td>' + escapeHtml(cus.kontak) + '</td>' +
                    '<td>' + escapeHtml(cus.alamat) + '</td>' +
                    '<td class="text-center">' +
                    '<button class="btn btn-sm btn-outline-warning btn-edit" data-id="' + cus.id + '" title="Edit"><i class="bi bi-pencil"></i></button> ' +
                    '<button class="btn btn-sm btn-outline-danger btn-delete" data-id="' + cus.id + '" data-nama="' + escapeHtml(cus.nama) + '" title="Hapus"><i class="bi bi-trash"></i></button>' +
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
                        $('#customerInfo').text('Menampilkan ' + start + '-' + end + ' dari ' + response.total + ' data');
                    } else {
                        $('#customerTableBody').html('<tr><td colspan="5" class="text-center text-danger">Gagal memuat data.</td></tr>');
                    }
                },
                error: function() {
                    $('#customerTableBody').html('<tr><td colspan="5" class="text-center text-danger">Terjadi kesalahan saat memuat data.</td></tr>');
                }
            });
        }

        // Muat data pertama kali (page 1, tanpa search)
        loadData(1, '');

        // Klik tombol pagination
        $(document).on('click', '#customerPagination a.page-link', function(e) {
            e.preventDefault();
            var $li = $(this).closest('li');
            if ($li.hasClass('disabled') || $li.hasClass('active')) {
                return;
            }
            var page = parseInt($(this).data('page'), 10);
            loadData(page, currentSearch);
        });

        // Search dengan debounce (tunggu user berhenti ngetik 400ms), reset ke page 1
        $('#searchCustomer').on('keyup', function() {
            var keyword = $(this).val();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                loadData(1, keyword);
            }, 400);
        });


        // Reset modal Tambah Supplier setiap kali dibuka/ditutup
        // (jangan ikut mengosongkan field CSRF, hanya field input data)
        $('#modalTambahCustomer').on('show.bs.modal hidden.bs.modal', function() {
            $('#formTambahCustomer')[0].reset();
            $('#formTambahCustomer input:not(.csrf-field), #formTambahCustomer textarea').val('');
        });

        // Hanya izinkan angka pada semua field kontak (tambah & edit)
        $(document).on('input', '.input-numeric-only', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // 1. AJAX TAMBAH USER
        $('#formTambahCustomer').on('submit', function(e) {
            e.preventDefault();
            $('#btnSimpan').prop('disabled', true).text('Menyimpan...');

            $.ajax({
                url: "<?= site_url('customer/simpan'); ?>",
                type: "POST",
                data: $(this).serialize(),
                dataType: "JSON",
                success: function(response) {
                    refreshCsrf(response.csrf_hash);
                    if (response.status) {
                        alert(response.message);
                        $('#modalTambahCustomer').modal('hide');
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
                url: "<?= site_url('customer/get_by_id/'); ?>" + id,
                type: "GET",
                dataType: "JSON",
                success: function(response) {
                    if (response.status) {
                        $('#edit_id').val(response.data.id);
                        $('#edit_nama').val(response.data.nama);
                        $('#edit_kontak').val(response.data.kontak);
                        $('#edit_alamat').val(response.data.alamat);

                        $('#modalEditCustomer').modal('show');
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
        $('#formEditCustomer').on('submit', function(e) {
            e.preventDefault();
            $('#btnUpdate').prop('disabled', true).text('Memperbarui...');

            $.ajax({
                url: "<?= site_url('customer/update'); ?>",
                type: "POST",
                data: $(this).serialize(),
                dataType: "JSON",
                success: function(response) {
                    refreshCsrf(response.csrf_hash);
                    if (response.status) {
                        alert(response.message);
                        $('#modalEditCustomer').modal('hide');
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

            if (confirm('Apakah Anda yakin ingin menghapus customer "' + nama + '"?')) {
                $.ajax({
                    url: "<?= site_url('customer/delete/'); ?>" + id,
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