<!-- ================= GRID DATA KATEGORI BARANG ================= -->
<div class="card card-custom p-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="fw-bold m-0"><?= translate('list_kategori') ?></h6>
        <!-- Trigger Modal Tambah Kategori Barang -->
        <button type="button" class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#modalTambahKatBarang">
            <i class="bi bi-plus-lg"></i> <?= translate('add') ?>
        </button>
    </div>

    <!-- Search -->
    <div class="row mb-3">
        <div class="col-12 col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" id="searchKatBarang" class="form-control" placeholder="Cari kode, nama, atau deskripsi...">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th><?= translate('no') ?></th>
                    <th><?= translate('kode') ?></th>
                    <th><?= translate('nama_ket') ?></th>
                    <th><?= translate('deskripsi') ?></th>
                    <th><?= translate('tanggal') ?></th>
                    <th class="text-center"><?= translate('aksi') ?></th>
                </tr>
            </thead>
            <tbody id="tbodyKatBarang">
                <tr>
                    <td colspan="6" class="text-center text-muted">Memuat data...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Info jumlah data & pagination -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted" id="infoKatBarang"></small>
        <nav>
            <ul class="pagination pagination-sm mb-0" id="paginationKatBarang"></ul>
        </nav>
    </div>
</div>

<!-- ================= MODAL TAMBAH KATEGORI BARANG ================= -->
<div class="modal fade" id="modalTambahKatBarang" tabindex="-1" aria-labelledby="modalTambahKatBarangLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--brand); color: #fff;">
                <h5 class="modal-title fs-6" id="modalTambahKatBarangLabel"><i class="bi bi-person-plus"></i> Tambah Kategori Barang Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formTambahKatBarang" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" class="csrf-field" value="<?= $this->security->get_csrf_hash(); ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kode Kategori</label>
                        <input type="text" name="kode" class="form-control form-control-sm" placeholder="Masukkan kode kategori" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Kategori</label>
                        <input type="text" name="nama" class="form-control form-control-sm" placeholder="Masukkan nama kategori" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control form-control-sm" rows="3" placeholder="Masukkan deskripsi" autocomplete="off" required></textarea>
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

<!-- ================= MODAL EDIT KATEGORI BARANG ================= -->
<div class="modal fade" id="modalEditKatBarang" tabindex="-1" aria-labelledby="modalEditKatBarangLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--brand); color: #fff;">
                <h5 class="modal-title fs-6" id="modalEditKatBarangLabel"><i class="bi bi-pencil-square"></i> Edit Data Kategori Barang</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditKatBarang" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" class="csrf-field" value="<?= $this->security->get_csrf_hash(); ?>">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kode Kategori</label>
                        <input type="text" name="kode" id="edit_kode" class="form-control form-control-sm" placeholder="Masukkan kode kategori" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Kategori</label>
                        <input type="text" name="nama" id="edit_nama" class="form-control form-control-sm" placeholder="Masukkan Nama Kategori" required>
                    </div>
                     <div class="mb-3">
                        <label class="form-label small fw-bold">Deskripsi</label>
                        <textarea name="deskripsi" id="edit_deskripsi" class="form-control form-control-sm" rows="3" placeholder="Masukkan deskripsi" required></textarea>
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

<!-- ================= JS Khusus Halaman Kategori Barang (bukan bagian layout) ================= -->
<script>
    $(document).ready(function() {

        var currentPage   = 1;
        var currentSearch = '';
        var searchTimer   = null;

        // Helper ambil ulang field csrf terbaru dari salah satu form di halaman ini
        function refreshCsrf(hash) {
            if (hash) {
                $('.csrf-field').val(hash);
            }
        }

        // Helper untuk request yang butuh csrf tapi tanpa serialize form (mis. delete)
        function getCsrfData() {
            var data = {};
            data[$('.csrf-field').attr('name')] = $('.csrf-field').val();
            return data;
        }

        // Escape HTML sederhana untuk data yang dirender lewat JS (hindari XSS)
        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return $('<div>').text(str).html();
        }

        // ================= LOAD DATA (dipakai untuk load awal, search, & pindah halaman) =================
        function loadData(page, search) {
            currentPage   = page || 1;
            currentSearch = (search !== undefined) ? search : currentSearch;

            $.ajax({
                url: "<?= site_url('kategori_barang/list_data'); ?>",
                type: "GET",
                data: {
                    page: currentPage,
                    search: currentSearch
                },
                dataType: "JSON",
                success: function(response) {
                    if (response.status) {
                        // Kalau halaman yang diminta ternyata sudah tidak ada isinya
                        // (mis. setelah menghapus data terakhir di halaman terakhir), mundur satu halaman.
                        if (response.data.length === 0 && response.current_page > 1 && response.total > 0) {
                            loadData(response.current_page - 1, currentSearch);
                            return;
                        }
                        renderTable(response.data, response.per_page, response.current_page);
                        renderPagination(response.total_pages, response.current_page);
                        renderInfo(response.total, response.per_page, response.current_page, response.data.length);
                    } else {
                        $('#tbodyKatBarang').html('<tr><td colspan="6" class="text-center text-danger">' + escapeHtml(response.message) + '</td></tr>');
                    }
                },
                error: function() {
                    $('#tbodyKatBarang').html('<tr><td colspan="6" class="text-center text-danger">Gagal memuat data.</td></tr>');
                }
            });
        }

        function renderTable(rows, perPage, page) {
            var $tbody = $('#tbodyKatBarang');
            $tbody.empty();

            if (!rows || rows.length === 0) {
                $tbody.html('<tr><td colspan="6" class="text-center text-muted">Data kategori barang tidak ditemukan.</td></tr>');
                return;
            }

            var startNo = ((page - 1) * perPage) + 1;

            rows.forEach(function(kat, idx) {
                var tr = '' +
                    '<tr>' +
                        '<td>' + (startNo + idx) + '</td>' +
                        '<td class="fw-semibold">' + escapeHtml(kat.kode_kategori) + '</td>' +
                        '<td>' + escapeHtml(kat.nama_kategori) + '</td>' +
                        '<td>' + escapeHtml(kat.deskripsi) + '</td>' +
                        '<td>' + escapeHtml(kat.created_at || '-') + '</td>' +
                        '<td class="text-center">' +
                            '<button class="btn btn-sm btn-outline-warning btn-edit" data-id="' + kat.id + '" title="Edit"><i class="bi bi-pencil"></i></button> ' +
                            '<button class="btn btn-sm btn-outline-danger btn-delete" data-id="' + kat.id + '" data-nama="' + escapeHtml(kat.nama_kategori) + '" title="Hapus"><i class="bi bi-trash"></i></button>' +
                        '</td>' +
                    '</tr>';
                $tbody.append(tr);
            });
        }

        function renderPagination(totalPages, page) {
            var $pagination = $('#paginationKatBarang');
            $pagination.empty();

            if (totalPages <= 1) return;

            function pageItem(label, targetPage, disabled, active) {
                return '<li class="page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '') + '">' +
                    '<a href="#" class="page-link" data-page="' + targetPage + '">' + label + '</a>' +
                '</li>';
            }

            $pagination.append(pageItem('&laquo;', page - 1, page <= 1, false));

            for (var i = 1; i <= totalPages; i++) {
                $pagination.append(pageItem(i, i, false, i === page));
            }

            $pagination.append(pageItem('&raquo;', page + 1, page >= totalPages, false));
        }

        function renderInfo(total, perPage, page, countInPage) {
            if (total === 0) {
                $('#infoKatBarang').text('');
                return;
            }
            var start = ((page - 1) * perPage) + 1;
            var end   = start + countInPage - 1;
            $('#infoKatBarang').text('Menampilkan ' + start + '-' + end + ' dari ' + total + ' data');
        }

        // Klik nomor halaman pagination
        $(document).on('click', '#paginationKatBarang .page-link', function(e) {
            e.preventDefault();
            var $li = $(this).closest('.page-item');
            if ($li.hasClass('disabled') || $li.hasClass('active')) return;

            var targetPage = parseInt($(this).data('page'), 10);
            loadData(targetPage, currentSearch);
        });

        // Input search (debounce 400ms), selalu kembali ke halaman 1
        $('#searchKatBarang').on('keyup', function() {
            var keyword = $(this).val();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                loadData(1, keyword);
            }, 400);
        });

        // Reset modal Tambah Kategori Barang setiap kali dibuka/ditutup
        $('#modalTambahKatBarang').on('show.bs.modal hidden.bs.modal', function() {
            $('#formTambahKatBarang')[0].reset();
        });

        // 1. AJAX TAMBAH KATEGORI BARANG
        $('#formTambahKatBarang').on('submit', function(e) {
            e.preventDefault();
            $('#btnSimpan').prop('disabled', true).text('Menyimpan...');

            $.ajax({
                url: "<?= site_url('kategori_barang/simpan'); ?>",
                type: "POST",
                data: $(this).serialize(),
                dataType: "JSON",
                success: function(response) {
                    refreshCsrf(response.csrf_hash);
                    if (response.status) {
                        alert(response.message);
                        $('#modalTambahKatBarang').modal('hide');
                        // Data baru selalu masuk ke halaman pertama (urutan terbaru di atas)
                        loadData(1, currentSearch);
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

        // 2. AJAX AMBIL DATA KATEGORI BARANG BY ID (UNTUK EDIT)
        $(document).on('click', '.btn-edit', function() {
            var id = $(this).data('id');

            $.ajax({
                url: "<?= site_url('kategori_barang/get_by_id/'); ?>" + id,
                type: "GET",
                dataType: "JSON",
                success: function(response) {
                    if (response.status) {
                        $('#edit_id').val(response.data.id);
                        $('#edit_kode').val(response.data.kode_kategori);
                        $('#edit_nama').val(response.data.nama_kategori);
                        $('#edit_deskripsi').val(response.data.deskripsi);

                        $('#modalEditKatBarang').modal('show');
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan saat mengambil data.');
                }
            });
        });

        // 3. AJAX UPDATE KATEGORI BARANG
        $('#formEditKatBarang').on('submit', function(e) {
            e.preventDefault();
            $('#btnUpdate').prop('disabled', true).text('Memperbarui...');

            $.ajax({
                url: "<?= site_url('kategori_barang/update'); ?>",
                type: "POST",
                data: $(this).serialize(),
                dataType: "JSON",
                success: function(response) {
                    refreshCsrf(response.csrf_hash);
                    if (response.status) {
                        alert(response.message);
                        $('#modalEditKatBarang').modal('hide');
                        loadData(currentPage, currentSearch);
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

        // 4. AJAX DELETE KATEGORI BARANG
        $(document).on('click', '.btn-delete', function() {
            var id = $(this).data('id');
            var nama = $(this).data('nama');

            if (confirm('Apakah Anda yakin ingin menghapus kategori "' + nama + '"?')) {
                $.ajax({
                    url: "<?= site_url('kategori_barang/delete/'); ?>" + id,
                    type: "POST",
                    data: getCsrfData(),
                    dataType: "JSON",
                    success: function(response) {
                        refreshCsrf(response.csrf_hash);
                        if (response.status) {
                            alert(response.message);
                            // Kalau halaman saat ini jadi kosong setelah hapus (mis. hapus data terakhir
                            // di halaman terakhir), mundur satu halaman.
                            var targetPage = currentPage;
                            loadData(targetPage, currentSearch);
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        // Coba baca pesan JSON dari server jika ada (mis. dari exception handler),
                        // supaya user melihat alasan spesifik alih-alih pesan generik.
                        var msg = 'Terjadi kesalahan saat menghapus data.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        alert(msg);
                        console.error('Delete error:', status, error, xhr.responseText);
                    }
                });
            }
        });

        // Load data pertama kali halaman dibuka
        loadData(1, '');
    });
</script>