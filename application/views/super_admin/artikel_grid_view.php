<!-- ================= GRID DATA ARTIKEL ================= -->
<div class="card card-custom p-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h6 class="fw-bold m-0"><i class="bi bi-journal-text me-1 text-success"></i> <?= function_exists('translate') ? translate('menu_artikel') : 'Master Data Artikel' ?></h6>
            <small class="text-muted">Kelola data artikel dengan auto-translate multi bahasa (ID, EN, ZH)</small>
        </div>
        <!-- Trigger Modal Tambah Artikel -->
        <button type="button" class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#modalTambahArtikel">
            <i class="bi bi-plus-lg me-1"></i> <?= function_exists('translate') ? translate('add') : 'Tambah Artikel' ?>
        </button>
    </div>

    <!-- ================= FILTER & SEARCH BOX ================= -->
    <div class="row g-2 mb-3 align-items-center">
        <!-- Search Input -->
        <div class="col-12 col-md-5">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" id="searchArtikel" class="form-control" placeholder="Cari judul atau deskripsi...">
                <button class="btn btn-outline-secondary btn-sm" type="button" id="btnResetSearch" title="Reset Search"><i class="bi bi-x-lg"></i></button>
            </div>
        </div>

        <!-- Filter Bahasa (Language Filter) -->
        <div class="col-12 col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white fw-semibold"><i class="bi bi-translate me-1 text-primary"></i> Bahasa:</span>
                <select id="filterLang" class="form-select form-select-sm">
                    <option value="id" selected>🇮🇩 Indonesia (Default)</option>
                    <option value="en">🇬🇧 English</option>
                    <option value="zh">🇨🇳 Chinese Simplified (中文)</option>
                </select>
            </div>
        </div>

        <!-- Filter Status -->
        <div class="col-12 col-md-3">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="bi bi-funnel me-1"></i> Status:</span>
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Banner Info Bahasa yang Sedang Ditampilkan -->
    <div class="alert alert-light border py-1 px-3 mb-3 d-flex align-items-center justify-content-between small">
        <span>
            <i class="bi bi-info-circle text-primary me-1"></i>
            Menampilkan teks dalam: <strong id="activeLangLabel" class="text-primary">Bahasa Indonesia (Default)</strong>
        </span>
        <span class="badge bg-light text-dark border">
            <i class="bi bi-magic me-1 text-warning"></i> Auto Translated via Google Translate
        </span>
    </div>

    <!-- ================= TABLE LIST ================= -->
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;"><?= function_exists('translate') ? translate('no') : 'No' ?></th>
                    <th style="min-width: 220px;">Judul Artikel</th>
                    <th style="min-width: 320px;">Deskripsi</th>
                    <th style="width: 140px;" class="text-center">Status</th>
                    <th style="width: 150px;">Tanggal</th>
                    <th style="width: 140px;" class="text-center"><?= function_exists('translate') ? translate('aksi') : 'Aksi' ?></th>
                </tr>
            </thead>
            <tbody id="artikelTableBody">
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <div class="spinner-border spinner-border-sm text-secondary me-2" role="status"></div>
                        Memuat data artikel...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ================= INFO + PAGINATION ================= -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
        <small class="text-muted" id="artikelInfo"></small>
        <nav aria-label="Pagination Artikel">
            <ul class="pagination pagination-sm mb-0" id="artikelPagination"></ul>
        </nav>
    </div>
</div>

<!-- ================= MODAL TAMBAH ARTIKEL ================= -->
<div class="modal fade" id="modalTambahArtikel" tabindex="-1" aria-labelledby="modalTambahArtikelLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--brand); color: #fff;">
                <h5 class="modal-title fs-6" id="modalTambahArtikelLabel">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Artikel Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formTambahArtikel" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" class="csrf-field" value="<?= $this->security->get_csrf_hash(); ?>">

                    <div class="alert alert-info py-2 px-3 small d-flex align-items-center mb-3">
                        <i class="bi bi-lightbulb-fill text-warning fs-5 me-2"></i>
                        <div>
                            <strong>Otomatis Diterjemahkan:</strong> Anda cukup mengisi form dalam <u>Bahasa Indonesia</u>. Sistem akan otomatis menerjemahkan judul dan deskripsi ke dalam <strong>English</strong> dan <strong>Chinese (Simplified)</strong> menggunakan <em>Translate Service</em> saat disimpan.
                        </div>
                    </div>

                    <!-- Judul (Default: Indonesia) -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Judul Artikel (Bahasa Indonesia) <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control form-control-sm" placeholder="Contoh: Perkembangan Industri Pintu dan Jendela..." required>
                    </div>

                    <!-- Deskripsi (Default: Indonesia) -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Deskripsi / Konten (Bahasa Indonesia) <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control form-control-sm" rows="5" placeholder="Tuliskan isi atau ringkasan artikel di sini..." required></textarea>
                    </div>

                    <!-- Status Aktif -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Status Publikasi</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="tambah_is_active" value="1" checked>
                            <label class="form-check-label small" for="tambah_is_active">Aktifkan artikel ini (Dapat dilihat)</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-brand" id="btnSimpanArtikel">
                        <i class="bi bi-save me-1"></i> Simpan & Auto-Translate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= MODAL EDIT ARTIKEL ================= -->
<div class="modal fade" id="modalEditArtikel" tabindex="-1" aria-labelledby="modalEditArtikelLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--brand); color: #fff;">
                <h5 class="modal-title fs-6" id="modalEditArtikelLabel">
                    <i class="bi bi-pencil-square me-1"></i> Edit Data Artikel
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditArtikel" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" class="csrf-field" value="<?= $this->security->get_csrf_hash(); ?>">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="alert alert-warning py-2 px-3 small d-flex align-items-center mb-3">
                        <i class="bi bi-info-circle-fill text-warning fs-5 me-2"></i>
                        <div>
                            Mengubah judul atau deskripsi di bawah akan <strong>memperbarui ulang terjemahan</strong> bahasa Inggris dan Mandarin secara otomatis.
                        </div>
                    </div>

                    <!-- Judul (Default: Indonesia) -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Judul Artikel (Bahasa Indonesia) <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="edit_title" class="form-control form-control-sm" placeholder="Masukkan judul artikel" required>
                    </div>

                    <!-- Deskripsi (Default: Indonesia) -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Deskripsi / Konten (Bahasa Indonesia) <span class="text-danger">*</span></label>
                        <textarea name="description" id="edit_description" class="form-control form-control-sm" rows="5" placeholder="Masukkan deskripsi artikel" required></textarea>
                    </div>

                    <!-- Status Aktif -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Status Publikasi</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="edit_is_active" value="1">
                            <label class="form-check-label small" for="edit_is_active">Aktifkan artikel ini</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-brand" id="btnUpdateArtikel">
                        <i class="bi bi-check2-circle me-1"></i> Update & Retranslate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= MODAL DETAIL MULTI-BAHASA ================= -->
<div class="modal fade" id="modalDetailArtikel" tabindex="-1" aria-labelledby="modalDetailArtikelLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fs-6" id="modalDetailArtikelLabel">
                    <i class="bi bi-eye me-1"></i> Detail Terjemahan Artikel (3 Bahasa)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Nav Tabs 3 Bahasa -->
                <ul class="nav nav-pills mb-3 gap-2" id="detailLangTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active btn-sm" id="tab-id-btn" data-bs-toggle="pill" data-bs-target="#tab-id" type="button" role="tab">
                            🇮🇩 Indonesia (Original)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link btn-sm" id="tab-en-btn" data-bs-toggle="pill" data-bs-target="#tab-en" type="button" role="tab">
                            🇬🇧 English (Translated)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link btn-sm" id="tab-zh-btn" data-bs-toggle="pill" data-bs-target="#tab-zh" type="button" role="tab">
                            🇨🇳 Chinese Simplified (中文)
                        </button>
                    </li>
                </ul>

                <!-- Tab Panes -->
                <div class="tab-content border rounded p-3 bg-light">
                    <!-- Tab Indonesia -->
                    <div class="tab-pane fade show active" id="tab-id" role="tabpanel">
                        <h6 class="fw-bold text-dark mb-1" id="detail_title_id">-</h6>
                        <small class="text-muted d-block mb-3"><i class="bi bi-tag"></i> Versi Bahasa Indonesia</small>
                        <hr class="my-2">
                        <p class="mb-0 text-secondary" style="white-space: pre-line;" id="detail_desc_id">-</p>
                    </div>

                    <!-- Tab English -->
                    <div class="tab-pane fade" id="tab-en" role="tabpanel">
                        <h6 class="fw-bold text-primary mb-1" id="detail_title_en">-</h6>
                        <small class="text-muted d-block mb-3"><i class="bi bi-translate"></i> Translated to English</small>
                        <hr class="my-2">
                        <p class="mb-0 text-secondary" style="white-space: pre-line;" id="detail_desc_en">-</p>
                    </div>

                    <!-- Tab Chinese -->
                    <div class="tab-pane fade" id="tab-zh" role="tabpanel">
                        <h6 class="fw-bold text-danger mb-1" id="detail_title_zh">-</h6>
                        <small class="text-muted d-block mb-3"><i class="bi bi-translate"></i> Translated to Chinese (Simplified)</small>
                        <hr class="my-2">
                        <p class="mb-0 text-secondary" style="white-space: pre-line;" id="detail_desc_zh">-</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ================= JS Khusus Halaman Artikel ================= -->
<script>
$(document).ready(function() {
    var currentPage = 1;
    var currentSearch = '';
    var currentLang = 'id';
    var currentStatus = '';
    var searchTimer = null;

    var langLabels = {
        'id': 'Bahasa Indonesia (Default)',
        'en': 'English',
        'zh': 'Chinese Simplified (简体中文)'
    };

    // Fungsi refresh CSRF Hash di semua form
    function updateCsrf(newHash) {
        if (newHash) {
            $('.csrf-field').val(newHash);
            $('#csrf_token_hash').val(newHash);
        }
    }

    function getCsrfData() {
        var tokenName = $('#csrf_token_name').val() || 'csrf_test_name';
        var tokenHash = $('#csrf_token_hash').val() || $('.csrf-field').first().val();
        var obj = {};
        obj[tokenName] = tokenHash;
        return obj;
    }

    // Helper escape HTML
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Memuat data artikel via AJAX
    function loadArtikel(page) {
        if (page) currentPage = page;

        var url = "<?= site_url('artikel/list_data'); ?>";
        var params = {
            search: currentSearch,
            lang: currentLang,
            is_active: currentStatus,
            page: currentPage
        };

        $('#artikelTableBody').html(`
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    <div class="spinner-border spinner-border-sm text-secondary me-2" role="status"></div>
                    Memuat data artikel...
                </td>
            </tr>
        `);

        $.getJSON(url, params, function(res) {
            if (res.csrf_hash) updateCsrf(res.csrf_hash);

            if (!res.status || !res.data || res.data.length === 0) {
                $('#artikelTableBody').html(`
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary"></i>
                            Tidak ada data artikel yang ditemukan.
                        </td>
                    </tr>
                `);
                $('#artikelInfo').text('Menampilkan 0 data');
                $('#artikelPagination').empty();
                return;
            }

            var html = '';
            var no = (res.current_page - 1) * res.per_page + 1;

            res.data.forEach(function(item) {
                // Badge status
                var statusBadge = item.is_active === 1
                    ? `<span class="badge bg-success-subtle text-success border border-success-subtle cursor-pointer btn-toggle-status" data-id="${item.id}" title="Klik untuk nonaktifkan" style="cursor: pointer;"><i class="bi bi-check-circle me-1"></i>Aktif</span>`
                    : `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle cursor-pointer btn-toggle-status" data-id="${item.id}" title="Klik untuk aktifkan" style="cursor: pointer;"><i class="bi bi-x-circle me-1"></i>Nonaktif</span>`;

                // Translation pills preview
                var langPills = `
                    <div class="d-flex gap-1 mt-1">
                        <span class="badge ${item.title ? 'bg-info-subtle text-info border' : 'bg-light text-muted'}" style="font-size: 0.68rem;" title="ID: ${escapeHtml(item.title)}">ID</span>
                        <span class="badge ${item.title_en ? 'bg-primary-subtle text-primary border' : 'bg-light text-muted'}" style="font-size: 0.68rem;" title="EN: ${escapeHtml(item.title_en)}">EN</span>
                        <span class="badge ${item.title_zh ? 'bg-danger-subtle text-danger border' : 'bg-light text-muted'}" style="font-size: 0.68rem;" title="ZH: ${escapeHtml(item.title_zh)}">ZH</span>
                    </div>
                `;

                // Potong deskripsi jika terlalu panjang
                var shortDesc = item.display_desc ? item.display_desc : '-';
                if (shortDesc.length > 120) {
                    shortDesc = shortDesc.substring(0, 120) + '...';
                }

                // Format tanggal
                var dateStr = item.created_at ? item.created_at.substring(0, 10) : '-';

                html += `
                    <tr>
                        <td class="text-muted">${no++}</td>
                        <td>
                            <div class="fw-bold text-dark">${escapeHtml(item.display_title)}</div>
                            ${langPills}
                        </td>
                        <td>
                            <div class="text-secondary small" style="max-width: 380px;">${escapeHtml(shortDesc)}</div>
                        </td>
                        <td class="text-center">${statusBadge}</td>
                        <td class="text-muted small">${dateStr}</td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-info btn-sm btn-detail" data-id="${item.id}" title="Lihat 3 Bahasa">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm btn-edit" data-id="${item.id}" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm btn-delete" data-id="${item.id}" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            $('#artikelTableBody').html(html);

            // Update Info
            var from = (res.current_page - 1) * res.per_page + 1;
            var to = Math.min(res.current_page * res.per_page, res.total);
            $('#artikelInfo').text(`Menampilkan ${from}-${to} dari total ${res.total} artikel`);

            // Render Pagination
            renderPagination(res.current_page, res.total_pages);
        }).fail(function() {
            $('#artikelTableBody').html(`
                <tr>
                    <td colspan="6" class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle me-1"></i> Gagal memuat data artikel dari server.
                    </td>
                </tr>
            `);
        });
    }

    // Render tombol pagination
    function renderPagination(current, total) {
        var $pag = $('#artikelPagination').empty();
        if (total <= 1) return;

        // Tombol Prev
        var prevDisabled = current === 1 ? 'disabled' : '';
        $pag.append(`
            <li class="page-item ${prevDisabled}">
                <a class="page-link" href="#" data-page="${current - 1}">&laquo;</a>
            </li>
        `);

        for (var i = 1; i <= total; i++) {
            if (i === 1 || i === total || (i >= current - 2 && i <= current + 2)) {
                var active = i === current ? 'active' : '';
                $pag.append(`
                    <li class="page-item ${active}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `);
            } else if (i === current - 3 || i === current + 3) {
                $pag.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
            }
        }

        // Tombol Next
        var nextDisabled = current === total ? 'disabled' : '';
        $pag.append(`
            <li class="page-item ${nextDisabled}">
                <a class="page-link" href="#" data-page="${current + 1}">&raquo;</a>
            </li>
        `);
    }

    // Klik Pagination
    $(document).on('click', '#artikelPagination .page-link', function(e) {
        e.preventDefault();
        var page = parseInt($(this).data('page'));
        if (page && page !== currentPage) {
            loadArtikel(page);
        }
    });

    // Event Filter Bahasa
    $('#filterLang').on('change', function() {
        currentLang = $(this).val();
        $('#activeLangLabel').text(langLabels[currentLang] || currentLang);
        loadArtikel(1);
    });

    // Event Filter Status
    $('#filterStatus').on('change', function() {
        currentStatus = $(this).val();
        loadArtikel(1);
    });

    // Event Search dengan Debounce
    $('#searchArtikel').on('keyup', function() {
        clearTimeout(searchTimer);
        var val = $(this).val().trim();
        searchTimer = setTimeout(function() {
            currentSearch = val;
            loadArtikel(1);
        }, 400);
    });

    $('#btnResetSearch').on('click', function() {
        $('#searchArtikel').val('');
        currentSearch = '';
        loadArtikel(1);
    });

    // ================= TAMBAH ARTIKEL =================
    $('#formTambahArtikel').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#btnSimpanArtikel');
        var originalText = $btn.html();

        $btn.prop('disabled', true).html(`
            <span class="spinner-border spinner-border-sm me-1" role="status"></span> Menerjemahkan & Menyimpan...
        `);

        var formData = $(this).serialize();

        $.ajax({
            url: "<?= site_url('artikel/simpan'); ?>",
            type: "POST",
            data: formData,
            dataType: "json",
            success: function(res) {
                if (res.csrf_hash) updateCsrf(res.csrf_hash);

                if (res.status) {
                    $('#modalTambahArtikel').modal('hide');
                    $('#formTambahArtikel')[0].reset();
                    $('#tambah_is_active').prop('checked', true);
                    alert(res.message);
                    loadArtikel(1);
                } else {
                    alert('Gagal: ' + res.message);
                }
            },
            error: function(xhr) {
                alert('Terjadi kesalahan saat memproses data!');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // ================= EDIT ARTIKEL =================
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');

        $.getJSON("<?= site_url('artikel/get_by_id/'); ?>" + id, function(res) {
            if (res.csrf_hash) updateCsrf(res.csrf_hash);

            if (res.status && res.data) {
                var d = res.data;
                $('#edit_id').val(d.id);
                $('#edit_title').val(d.title);
                $('#edit_description').val(d.description);
                $('#edit_is_active').prop('checked', parseInt(d.is_active) === 1);

                $('#modalEditArtikel').modal('show');
            } else {
                alert('Data artikel tidak ditemukan!');
            }
        }).fail(function() {
            alert('Gagal mengambil data artikel!');
        });
    });

    $('#formEditArtikel').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#btnUpdateArtikel');
        var originalText = $btn.html();

        $btn.prop('disabled', true).html(`
            <span class="spinner-border spinner-border-sm me-1" role="status"></span> Menerjemahkan & Memperbarui...
        `);

        var formData = $(this).serialize();

        $.ajax({
            url: "<?= site_url('artikel/update'); ?>",
            type: "POST",
            data: formData,
            dataType: "json",
            success: function(res) {
                if (res.csrf_hash) updateCsrf(res.csrf_hash);

                if (res.status) {
                    $('#modalEditArtikel').modal('hide');
                    alert(res.message);
                    loadArtikel(currentPage);
                } else {
                    alert('Gagal: ' + res.message);
                }
            },
            error: function(xhr) {
                alert('Terjadi kesalahan saat memperbarui data!');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // ================= DETAIL 3 BAHASA =================
    $(document).on('click', '.btn-detail', function() {
        var id = $(this).data('id');

        $.getJSON("<?= site_url('artikel/get_by_id/'); ?>" + id, function(res) {
            if (res.csrf_hash) updateCsrf(res.csrf_hash);

            if (res.status && res.data) {
                var d = res.data;
                $('#detail_title_id').text(d.title || '(Kosong)');
                $('#detail_desc_id').text(d.description || '(Kosong)');

                $('#detail_title_en').text(d.title_en || '(Belum ada terjemahan English)');
                $('#detail_desc_en').text(d.description_en || '(Belum ada terjemahan English)');

                $('#detail_title_zh').text(d.title_zh || '(Belum ada terjemahan Chinese)');
                $('#detail_desc_zh').text(d.description_zh || '(Belum ada terjemahan Chinese)');

                // Reset to ID tab
                $('#tab-id-btn').tab('show');
                $('#modalDetailArtikel').modal('show');
            } else {
                alert('Data artikel tidak ditemukan!');
            }
        });
    });

    // ================= HAPUS ARTIKEL =================
    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        if (!confirm('Apakah Anda yakin ingin menghapus artikel ini? Data terjemahan juga akan terhapus.')) {
            return;
        }

        var postData = getCsrfData();

        $.ajax({
            url: "<?= site_url('artikel/delete/'); ?>" + id,
            type: "POST",
            data: postData,
            dataType: "json",
            success: function(res) {
                if (res.csrf_hash) updateCsrf(res.csrf_hash);

                if (res.status) {
                    alert(res.message);
                    loadArtikel(currentPage);
                } else {
                    alert('Gagal: ' + res.message);
                }
            },
            error: function() {
                alert('Terjadi kesalahan saat menghapus data!');
            }
        });
    });

    // ================= TOGGLE STATUS AKTIF =================
    $(document).on('click', '.btn-toggle-status', function() {
        var id = $(this).data('id');
        var postData = getCsrfData();

        $.ajax({
            url: "<?= site_url('artikel/toggle_status/'); ?>" + id,
            type: "POST",
            data: postData,
            dataType: "json",
            success: function(res) {
                if (res.csrf_hash) updateCsrf(res.csrf_hash);

                if (res.status) {
                    loadArtikel(currentPage);
                } else {
                    alert('Gagal mengubah status: ' + res.message);
                }
            },
            error: function() {
                alert('Terjadi kesalahan saat mengubah status!');
            }
        });
    });

    // Initial Load
    loadArtikel(1);
});
</script>
