<!-- ================= GRID DATA LOCATION ================= -->
<div class="card card-custom p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold m-0"><?= translate('app_location') ?></h6>
        <!-- Trigger Modal Tambah Location -->
        <button type="button" class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#modalTambahLocation">
            <i class="bi bi-plus-lg"></i> <?= translate('add') ?>
        </button>
    </div>

    <!-- ================= SEARCH BOX ================= -->
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" id="searchLocation" class="form-control" placeholder="Cari lokasi rak ...">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th><?= translate('no') ?></th>
                    <th><?= translate('kode') ?></th>
                    <th><?= translate('nama') ?></th>
                    <th>Lorong</th>
                    <th>Nomor Rak</th>
                    <th>Tingkat</th>
                    <th>Jenis Lokasi</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody id="locationTableBody">
                <tr>
                    <td colspan="8" class="text-center text-muted">Memuat data...</td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- ================= INFO + PAGINATION ================= -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted" id="locationInfo"></small>
        <nav aria-label="Pagination Locations">
            <ul class="pagination pagination-sm mb-0" id="locationPagination"></ul>
        </nav>
    </div>
</div>

<!-- ================= MODAL TAMBAH LOCATION ================= -->
<div class="modal fade" id="modalTambahLocation" tabindex="-1" aria-labelledby="modalTambahLocationLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--brand); color: #fff;">
                <h5 class="modal-title fs-6" id="modalTambahLocationLabel"><i class="bi bi-geo-alt"></i> Tambah Lokasi Rak Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formTambahLocation" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" class="csrf-field" value="<?= $this->security->get_csrf_hash(); ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kode Lokasi</label>
                        <input type="text" name="location_code" class="form-control form-control-sm" placeholder="Contoh: A-01-01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Zona</label>
                        <input type="text" name="zone_name" class="form-control form-control-sm" placeholder="Contoh: Zona A" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Lorong</label>
                        <input type="text" name="aisle" class="form-control form-control-sm" placeholder="Contoh: Lorong 1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nomor Rak</label>
                        <input type="text" name="rack_number" class="form-control form-control-sm" placeholder="Contoh: Rak 01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tingkat</label>
                        <input type="text" name="level" class="form-control form-control-sm" placeholder="Contoh: 1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Jenis Lokasi</label>
                        <input type="text" name="location_type" class="form-control form-control-sm" placeholder="Contoh: Rak / Gudang / Transit">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-brand" id="btnSimpanLocation">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= MODAL EDIT LOCATION ================= -->
<div class="modal fade" id="modalEditLocation" tabindex="-1" aria-labelledby="modalEditLocationLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--brand); color: #fff;">
                <h5 class="modal-title fs-6" id="modalEditLocationLabel"><i class="bi bi-pencil-square"></i> Edit Data Lokasi Rak</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditLocation" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" class="csrf-field" value="<?= $this->security->get_csrf_hash(); ?>">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kode Lokasi</label>
                        <input type="text" name="location_code" id="edit_location_code" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Zona</label>
                        <input type="text" name="zone_name" id="edit_zone_name" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Lorong</label>
                        <input type="text" name="aisle" id="edit_aisle" class="form-control form-control-sm">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nomor Rak</label>
                        <input type="text" name="rack_number" id="edit_rack_number" class="form-control form-control-sm">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tingkat</label>
                        <input type="text" name="level" id="edit_level" class="form-control form-control-sm">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Jenis Lokasi</label>
                        <input type="text" name="location_type" id="edit_location_type" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-brand" id="btnUpdateLocation">Update Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= JS Khusus Halaman Location (bukan bagian layout) ================= -->
<script>
    $(document).ready(function() {
        var currentPage = 1;
        var currentSearch = '';
        var searchTimer = null;

        // Ambil url endpoint sekali saja
        var listDataUrl = "<?= site_url('locations/list_data'); ?>";
        var simpanUrl   = "<?= site_url('locations/simpan'); ?>";
        var updateUrl   = "<?= site_url('locations/update'); ?>";
        var getByIdUrl  = "<?= site_url('locations/get_by_id/'); ?>";
        var deleteUrl   = "<?= site_url('locations/delete/'); ?>";

        function escapeHtml(str) {
            return $('<div>').text(str == null ? '' : str).html();
        }

        // Render baris tabel dari data JSON
        function renderRows(rows, page, perPage) {
            var $tbody = $('#locationTableBody');
            $tbody.empty();

            if (!rows || rows.length === 0) {
                $tbody.append('<tr><td colspan="8" class="text-center text-muted">Data lokasi rak tidak ditemukan.</td></tr>');
                return;
            }

            var startNo = (page - 1) * perPage;
            rows.forEach(function(locations, idx) {
                var no = startNo + idx + 1;
                var tr = '<tr>' +
                    '<td>' + no + '</td>' +
                    '<td class="fw-semibold">' + escapeHtml(locations.location_code) + '</td>' +
                    '<td>' + escapeHtml(locations.zone_name) + '</td>' +
                    '<td>' + escapeHtml(locations.aisle) + '</td>' +
                    '<td>' + escapeHtml(locations.rack_number) + '</td>' +
                    '<td>' + escapeHtml(locations.level) + '</td>' +
                    '<td>' + escapeHtml(locations.location_type) + '</td>' +
                    '<td class="text-center">' +
                    '<button class="btn btn-sm btn-outline-warning btn-edit" data-id="' + locations.id + '" title="Edit"><i class="bi bi-pencil"></i></button> ' +
                    '<button class="btn btn-sm btn-outline-danger btn-delete" data-id="' + locations.id + '" data-nama="' + escapeHtml(locations.location_code) + '" title="Hapus"><i class="bi bi-trash"></i></button>' +
                    '</td>' +
                    '</tr>';
                $tbody.append(tr);
            });
        }

        // Render kontrol pagination
        function renderPagination(currentPage, totalPages) {
            var $pg = $('#locationPagination');
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
                        $('#locationInfo').text('Menampilkan ' + start + '-' + end + ' dari ' + response.total + ' data');
                    } else {
                        $('#locationTableBody').html('<tr><td colspan="8" class="text-center text-danger">Gagal memuat data.</td></tr>');
                    }
                },
                error: function() {
                    $('#locationTableBody').html('<tr><td colspan="8" class="text-center text-danger">Terjadi kesalahan saat memuat data.</td></tr>');
                }
            });
        }

        // Muat data pertama kali (page 1, tanpa search)
        loadData(1, '');

        // Klik tombol pagination
        $(document).on('click', '#locationPagination a.page-link', function(e) {
            e.preventDefault();
            var $li = $(this).closest('li');
            if ($li.hasClass('disabled') || $li.hasClass('active')) {
                return;
            }
            var page = parseInt($(this).data('page'), 10);
            loadData(page, currentSearch);
        });

        // Search dengan debounce (tunggu user berhenti ngetik 400ms), reset ke page 1
        $('#searchLocation').on('keyup', function() {
            var keyword = $(this).val();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                loadData(1, keyword);
            }, 400);
        });

        // Reset modal Tambah Location setiap kali dibuka/ditutup
        $('#modalTambahLocation').on('show.bs.modal hidden.bs.modal', function() {
            $('#formTambahLocation')[0].reset();
            $('.csrf-field').val($('#csrf_token_hash').val());
        });

        // 1. AJAX TAMBAH LOCATION
        $('#formTambahLocation').on('submit', function(e) {
            e.preventDefault();
            $('#btnSimpanLocation').prop('disabled', true).text('Menyimpan...');

            $.ajax({
                url: simpanUrl,
                type: "POST",
                data: $(this).serialize(),
                dataType: "JSON",
                success: function(response) {
                    refreshCsrf(response.csrf_hash);
                    if (response.status) {
                        alert(response.message);
                        $('#modalTambahLocation').modal('hide');
                        loadData(1, currentSearch);
                    } else {
                        alert(response.message);
                    }
                    $('#btnSimpanLocation').prop('disabled', false).text('Simpan Data');
                },
                error: function(xhr, status, error) {
                    alert('Terjadi kesalahan saat menyimpan data.');
                    console.error(error);
                    $('#btnSimpanLocation').prop('disabled', false).text('Simpan Data');
                }
            });
        });

        // 2. AJAX AMBIL DATA LOCATION BY ID (UNTUK EDIT)
        $(document).on('click', '.btn-edit', function() {
            var id = $(this).data('id');

            $.ajax({
                url: getByIdUrl + id,
                type: "GET",
                dataType: "JSON",
                success: function(response) {
                    if (response.status) {
                        $('#edit_id').val(response.data.id);
                        $('#edit_location_code').val(response.data.location_code);
                        $('#edit_zone_name').val(response.data.zone_name);
                        $('#edit_aisle').val(response.data.aisle);
                        $('#edit_rack_number').val(response.data.rack_number);
                        $('#edit_level').val(response.data.level);
                        $('#edit_location_type').val(response.data.location_type);

                        $('#modalEditLocation').modal('show');
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan saat mengambil data.');
                }
            });
        });

        // 3. AJAX UPDATE LOCATION
        $('#formEditLocation').on('submit', function(e) {
            e.preventDefault();
            $('#btnUpdateLocation').prop('disabled', true).text('Memperbarui...');

            $.ajax({
                url: updateUrl,
                type: "POST",
                data: $(this).serialize(),
                dataType: "JSON",
                success: function(response) {
                    refreshCsrf(response.csrf_hash);
                    if (response.status) {
                        alert(response.message);
                        $('#modalEditLocation').modal('hide');
                        loadData(currentPage, currentSearch);
                    } else {
                        alert(response.message);
                    }
                    $('#btnUpdateLocation').prop('disabled', false).text('Update Data');
                },
                error: function(xhr, status, error) {
                    alert('Terjadi kesalahan saat memperbarui data.');
                    console.error(error);
                    $('#btnUpdateLocation').prop('disabled', false).text('Update Data');
                }
            });
        });

        // 4. AJAX DELETE LOCATION
        $(document).on('click', '.btn-delete', function() {
            var id = $(this).data('id');
            var nama = $(this).data('nama');

            if (confirm('Apakah Anda yakin ingin menghapus lokasi "' + nama + '"?')) {
                $.ajax({
                    url: deleteUrl + id,
                    type: "POST",
                    data: getCsrfData(),
                    dataType: "JSON",
                    success: function(response) {
                        refreshCsrf(response.csrf_hash);
                        if (response.status) {
                            alert(response.message);
                            loadData(currentPage, currentSearch);
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