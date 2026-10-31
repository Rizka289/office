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
                <input type="text" id="searchCustomer" class="form-control" placeholder="<?= translate('p_search_sup') ?>">
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
                <h5 class="modal-title fs-6" id="modalTambahCustomerLabel"><i class="bi bi-person-plus"></i> <?= translate('add') . ' ' . translate('pelanggan') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formTambahCustomer" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" class="csrf-field" value="<?= $this->security->get_csrf_hash(); ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('nama') ?></label>
                        <input type="text" name="nama" class="form-control form-control-sm" placeholder="<?= translate('p_cus_nama') ?>" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('kontak') ?></label>
                        <input type="text" inputmode="numeric" pattern="[0-9]*" name="kontak" class="form-control form-control-sm input-numeric-only" placeholder="<?= translate('p_kontak') ?>" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('alamat') ?></label>
                        <textarea name="alamat" class="form-control form-control-sm" rows="3" placeholder="<?= translate('p_alamat') ?>" autocomplete="off" required></textarea>
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

<!-- ================= MODAL EDIT CUSTOMER ================= -->
<div class="modal fade" id="modalEditCustomer" tabindex="-1" aria-labelledby="modalEditCustomerLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--brand); color: #fff;">
                <h5 class="modal-title fs-6" id="modalEditCustomerLabel"><i class="bi bi-pencil-square"></i> <?= translate('update') . ' ' . translate('pelanggan') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditCustomer" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" class="csrf-field" value="<?= $this->security->get_csrf_hash(); ?>">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('nama') ?></label>
                        <input type="text" name="nama" id="edit_nama" class="form-control form-control-sm" placeholder="Masukkan nama customer" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('kontak') ?></label>
                        <input type="text" inputmode="numeric" pattern="[0-9]*" name="kontak" id="edit_kontak" class="form-control form-control-sm input-numeric-only" placeholder="Masukkan kontak" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('alamat') ?></label>
                        <textarea name="alamat" id="edit_alamat" class="form-control form-control-sm" rows="3" placeholder="Masukkan alamat" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"><?= translate('btn_batal') ?></button>
                    <button type="submit" class="btn btn-sm btn-brand" id="btnUpdate"><?= translate('button_save') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= JS Khusus Halaman Customer (bukan bagian layout) ================= -->
<script>
    $(document).ready(function() {
        var currentPage = 1;
        var currentSearch = '';
        var searchTimer = null;

        // Ambil url list_data sekali saja
        var listDataUrl = "<?= site_url('customer/list_data'); ?>";

        // Nama token & cookie CSRF dari konfigurasi CodeIgniter
        var csrfName = '<?= $this->security->get_csrf_token_name(); ?>';
        var csrfCookieName = '<?= $this->config->item('cookie_prefix') . $this->config->item('csrf_cookie_name'); ?>';

        function escapeHtml(str) {
            return $('<div>').text(str == null ? '' : str).html();
        }

        // ===== CSRF HELPER =====
        // Ambil token terbaru dari cookie CSRF (selalu diperbarui CI di setiap respons).
        // Jika cookie tidak terbaca (mis. cookie_httponly = TRUE), pakai nilai input hidden.
        function getCsrfToken() {
            var match = document.cookie.match(new RegExp('(?:^|; )' + csrfCookieName + '=([^;]*)'));
            return match ? decodeURIComponent(match[1]) : $('.csrf-field').first().val();
        }

        // Samakan semua input hidden CSRF dengan token terbaru
        function syncCsrf(hash) {
            var token = hash || getCsrfToken();
            if (token) {
                $('.csrf-field').val(token);
            }
            return token;
        }

        // Dipanggil di setiap respons (sukses maupun error) agar token tetap segar
        function handleCsrfFromResponse(response) {
            if (response && response.csrf_hash) {
                if (typeof refreshCsrf === 'function') {
                    refreshCsrf(response.csrf_hash);
                }
                syncCsrf(response.csrf_hash);
            } else {
                syncCsrf();
            }
        }

        // Penanganan error AJAX yang seragam. Return true jika sudah ditangani (403).
        function handleAjaxError(xhr, defaultMessage) {
            var res = xhr.responseJSON || {};
            handleCsrfFromResponse(res);

            if (xhr.status === 403) {
                alert('Token keamanan kedaluwarsa. Halaman akan dimuat ulang, silakan coba lagi.');
                location.reload();
                return true;
            }

            alert(res.message || (defaultMessage + ' (Status: ' + xhr.status + ')'));
            return false;
        }

        // Render baris tabel dari data JSON
        function renderRows(rows, page, perPage) {
            var $tbody = $('#customerTableBody');
            $tbody.empty();

            if (!rows || rows.length === 0) {
                var emptyMessage = "<?= translate('p_customer'); ?>";
                $tbody.append('<tr><td colspan="5" class="text-center text-muted">' + emptyMessage + '</td></tr>');
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
            var $pg = $('#customerPagination');
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

        // Reset modal Tambah Customer setiap kali dibuka/ditutup
        // (jangan ikut mengosongkan field CSRF, hanya field input data)
        $('#modalTambahCustomer').on('show.bs.modal hidden.bs.modal', function() {
            $('#formTambahCustomer')[0].reset();
            $('#formTambahCustomer input:not(.csrf-field), #formTambahCustomer textarea').val('');
        });

        // Hanya izinkan angka pada semua field kontak (tambah & edit)
        $(document).on('input', '.input-numeric-only', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // 1. AJAX TAMBAH CUSTOMER
        $('#formTambahCustomer').on('submit', function(e) {
            e.preventDefault();
            syncCsrf(); // pastikan token yang dikirim adalah yang terbaru
            $('#btnSimpan').prop('disabled', true).text('Menyimpan...');

            $.ajax({
                url: "<?= site_url('customer/simpan'); ?>",
                type: "POST",
                data: $(this).serialize(),
                dataType: "JSON",
                success: function(response) {
                    handleCsrfFromResponse(response);
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
                    console.error(error);
                    if (!handleAjaxError(xhr, 'Terjadi kesalahan saat menyimpan data')) {
                        $('#btnSimpan').prop('disabled', false).text('Simpan Data');
                    }
                }
            });
        });

        // 2. AJAX AMBIL DATA CUSTOMER BY ID (UNTUK EDIT)
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

        // 3. AJAX UPDATE CUSTOMER
        $('#formEditCustomer').on('submit', function(e) {
            e.preventDefault();
            syncCsrf(); // pastikan token yang dikirim adalah yang terbaru
            $('#btnUpdate').prop('disabled', true).text('Memperbarui...');

            $.ajax({
                url: "<?= site_url('customer/update'); ?>",
                type: "POST",
                data: $(this).serialize(),
                dataType: "JSON",
                success: function(response) {
                    handleCsrfFromResponse(response);
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
                    console.error(error);
                    if (!handleAjaxError(xhr, 'Terjadi kesalahan saat memperbarui data')) {
                        $('#btnUpdate').prop('disabled', false).text('Update Data');
                    }
                }
            });
        });

        // 4. AJAX DELETE CUSTOMER
        $(document).on('click', '.btn-delete', function() {
            var id = $(this).data('id');
            var nama = $(this).data('nama');

            var confirmText = "<?= translate('p_delete_sup'); ?> \"" + nama + "\"?";
            if (!confirm(confirmText)) {
                return;
            }

            // Token CSRF terbaru (dari cookie), bukan dari input hidden yang bisa basi
            var postData = {};
            postData[csrfName] = getCsrfToken();

            $.ajax({
                url: "<?= site_url('customer/delete/'); ?>" + id,
                type: "POST",
                data: postData,
                dataType: "JSON",
                success: function(response) {
                    handleCsrfFromResponse(response);

                    alert(response.message);
                    if (response.status) {
                        location.reload();
                    }
                },
                error: function(xhr, status, error) {
                    console.error(error);
                    handleAjaxError(xhr, 'Terjadi kesalahan saat menghapus data');
                }
            });
        });
    });
</script>