<!-- ================= GRID DATA NAMA BARANG ================= -->
<div class="card card-custom p-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="fw-bold m-0"> </h6>
        <!-- Trigger Modal Tambah Nama Barang -->
        <button type="button" class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#modalTambahNamaBarang">
            <i class="bi bi-plus-lg"></i><?= translate('add') ?>
        </button>
    </div>

    <!-- Search -->
    <div class="row mb-3">
        <div class="col-12 col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" id="searchNamaBarang" class="form-control" placeholder="Cari kode, nama, atau kategori...">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th><?= translate('no') ?></th>
                    <th><?= translate('kode') ?></th>
                    <th><?= translate('kategori_barang') ?></th>
                    <th><?= translate('nama_barang') ?></th>
                    <th><?= translate('jenis') ?></th>
                    <th><?= translate('satuan') ?></th>
                    <th><?= translate('dimensi') ?></th>
                    <th><?= translate('min_stok') ?></th>
                    <th class="text-center"><?= translate('aksi') ?></th>
                </tr>
            </thead>
            <tbody id="tbodyNamaBarang">
                <tr>
                    <td colspan="9" class="text-center text-muted">Memuat data...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Info jumlah data & pagination -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted" id="infoNamaBarang"></small>
        <nav>
            <ul class="pagination pagination-sm mb-0" id="paginationNamaBarang"></ul>
        </nav>
    </div>
</div>

<!-- ================= MODAL TAMBAH NAMA BARANG ================= -->
<div class="modal fade" id="modalTambahNamaBarang" tabindex="-1" aria-labelledby="modalTambahNamaBarangLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--brand); color: #fff;">
                <h5 class="modal-title fs-6" id="modalTambahNamaBarangLabel"><i class="bi bi-person-plus"></i> <?= translate('add') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formTambahNamaBarang" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" class="csrf-field" value="<?= $this->security->get_csrf_hash(); ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('kode') ?></label>
                        <input type="text" name="kode" class="form-control form-control-sm" placeholder="Masukkan kode barang" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('kategori_barang') ?></label>
                        <select name="id_kategori" class="form-select form-select-sm" required>
                            <option value="">-- <?= translate('label_choice') . ' ' . translate('kategori_barang') ?> --</option>
                            <?php foreach ($kategoriList as $kat): ?>
                                <option value="<?= $kat['id']; ?>"><?= htmlspecialchars($kat['nama_kategori']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('nama_barang') ?></label>
                        <input type="text" name="nama" class="form-control form-control-sm" placeholder="Masukkan nama barang" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('jenis') ?></label>
                        <select name="jenis" class="form-select form-select-sm" required>
                            <option value="">-- <?= translate('select')?> --</option>
                            <option value="bahan_baku">Bahan Baku</option>
                            <option value="aksesoris">Aksesoris</option>
                            <option value="finishing">Finishing</option>
                            <option value="setengah_jadi">Setengah Jadi</option>
                            <option value="produk_jadi_pintu">Produk Jadi Pintu</option>
                            <option value="produk_jadi_jendela">Produk Jadi Jendela</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('satuan') ?></label>
                        <select name="satuan" class="form-select form-select-sm" required>
                            <option value="">-- <?= translate('select')?> --</option>
                            <option value="pcs">Pcs</option>
                            <option value="set/padang">Set/pasang</option>
                            <option value="batang">Batang</option>
                            <option value="meter">Meter</option>
                            <option value="lembar">Lembar</option>
                            <option value="m2">m2</option>
                            <option value="m3">m3</option>
                            <option value="roll">Roll</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('dimensi') ?></label>
                        <input type="text" name="dimensi" class="form-control form-control-sm" placeholder="Masukkan Dimensi" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('min_stok') ?></label>
                        <input type="number" name="stok_minimum" min="0" class="form-control form-control-sm" placeholder="Masukkan stok minimum" autocomplete="off" required>
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

<!-- ================= MODAL EDIT NAMA BARANG ================= -->
<div class="modal fade" id="modalEditNamaBarang" tabindex="-1" aria-labelledby="modalEditNamaBarangLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--brand); color: #fff;">
                <h5 class="modal-title fs-6" id="modalEditNamaBarangLabel"><i class="bi bi-pencil-square"></i> <?= translate('update') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditNamaBarang" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" class="csrf-field" value="<?= $this->security->get_csrf_hash(); ?>">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('kode') ?></label>
                        <input type="text" name="kode" id="edit_kode" class="form-control form-control-sm" placeholder="Masukkan kode barang" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('kategori_barang') ?></label>
                        <select name="id_kategori" id="edit_id_kategori" class="form-select form-select-sm" required>
                            <option value="">-- <?= translate('label_choice') . ' ' . translate('kategori_barang') ?> --</option>
                            <?php foreach ($kategoriList as $kat): ?>
                                <option value="<?= $kat['id']; ?>"><?= htmlspecialchars($kat['nama_kategori']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('nama_barang') ?></label>
                        <input type="text" name="nama" id="edit_nama" class="form-control form-control-sm" placeholder="Masukkan Nama Barang" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('jenis') ?></label>
                        <select name="jenis" id="edit_jenis" class="form-select form-select-sm" required>
                            <option value="">--  <?= translate('select')?> --</option>
                            <option value="bahan_baku">Bahan Baku</option>
                            <option value="aksesoris">Aksesoris</option>
                            <option value="finishing">Finishing</option>
                            <option value="setengah_jadi">Setengah Jadi</option>
                            <option value="produk_jadi_pintu">Produk Jadi Pintu</option>
                            <option value="produk_jadi_jendela">Produk Jadi Jendela</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('satuan') ?></label>
                        <select name="satuan" id="edit_satuan" class="form-select form-select-sm" required>
                            <option value="">-- <?= translate('select')?>--</option>
                            <option value="pcs">Pcs</option>
                            <option value="set/padang">Set/pasang</option>
                            <option value="batang">Batang</option>
                            <option value="meter">Meter</option>
                            <option value="lembar">Lembar</option>
                            <option value="m2">m2</option>
                            <option value="m3">m3</option>
                            <option value="roll">Roll</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('dimensi') ?></label>
                        <input type="text" name="dimensi" id="edit_dimensi" class="form-control form-control-sm" placeholder="Masukkan Nama Barang" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('min_stok') ?></label>
                        <input type="number" name="stok_minimum" id="edit_stok" min="0" class="form-control form-control-sm" placeholder="Masukkan stok minimum" required>
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

<!-- ================= JS Khusus Halaman Nama Barang (bukan bagian layout) ================= -->
<script>
    $(document).ready(function() {

        var currentPage = 1;
        var currentSearch = '';
        var searchTimer = null;

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
            currentPage = page || 1;
            currentSearch = (search !== undefined) ? search : currentSearch;

            $.ajax({
                url: "<?= site_url('barang/list_data'); ?>",
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
                        $('#tbodyNamaBarang').html('<tr><td colspan="9" class="text-center text-danger">' + escapeHtml(response.message) + '</td></tr>');
                    }
                },
                error: function() {
                    $('#tbodyNamaBarang').html('<tr><td colspan="9" class="text-center text-danger">Gagal memuat data.</td></tr>');
                }
            });
        }

        function renderTable(rows, perPage, page) {
            var $tbody = $('#tbodyNamaBarang');
            $tbody.empty();

            if (!rows || rows.length === 0) {
                $tbody.html('<tr><td colspan="9" class="text-center text-muted">Data nama barang tidak ditemukan.</td></tr>');
                return;
            }

            var startNo = ((page - 1) * perPage) + 1;

            rows.forEach(function(brg, idx) {
                var tr = '' +
                    '<tr>' +
                    '<td>' + (startNo + idx) + '</td>' +
                    '<td class="fw-semibold">' + escapeHtml(brg.kode_barang) + '</td>' +
                    '<td>' + escapeHtml(brg.nama_kategori) + '</td>' +
                    '<td>' + escapeHtml(brg.nama) + '</td>' +
                    '<td>' + escapeHtml(brg.jenis_barang) + '</td>' +
                    '<td>' + escapeHtml(brg.satuan) + '</td>' +
                    '<td>' + escapeHtml(brg.dimensi) + '</td>' +
                    '<td>' + escapeHtml(brg.stok_minimum) + '</td>' +
                    '<td class="text-center">' +
                    '<button class="btn btn-sm btn-outline-warning btn-edit" data-id="' + brg.id + '" title="Edit"><i class="bi bi-pencil"></i></button> ' +
                    '<button class="btn btn-sm btn-outline-danger btn-delete" data-id="' + brg.id + '" data-nama="' + escapeHtml(brg.nama) + '" title="Hapus"><i class="bi bi-trash"></i></button>' +
                    '</td>' +
                    '</tr>';
                $tbody.append(tr);
            });
        }

        function renderPagination(totalPages, page) {
            var $pagination = $('#paginationNamaBarang');
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
                $('#infoNamaBarang').text('');
                return;
            }
            var start = ((page - 1) * perPage) + 1;
            var end = start + countInPage - 1;
            $('#infoNamaBarang').text('Menampilkan ' + start + '-' + end + ' dari ' + total + ' data');
        }

        // Klik nomor halaman pagination
        $(document).on('click', '#paginationNamaBarang .page-link', function(e) {
            e.preventDefault();
            var $li = $(this).closest('.page-item');
            if ($li.hasClass('disabled') || $li.hasClass('active')) return;

            var targetPage = parseInt($(this).data('page'), 10);
            loadData(targetPage, currentSearch);
        });

        // Input search (debounce 400ms), selalu kembali ke halaman 1
        $('#searchNamaBarang').on('keyup', function() {
            var keyword = $(this).val();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                loadData(1, keyword);
            }, 400);
        });

        // Reset modal Tambah Nama Barang setiap kali dibuka/ditutup
        $('#modalTambahNamaBarang').on('show.bs.modal hidden.bs.modal', function() {
            $('#formTambahNamaBarang')[0].reset();
        });

        // 1. AJAX TAMBAH NAMA BARANG
        $('#formTambahNamaBarang').on('submit', function(e) {
            e.preventDefault();
            $('#btnSimpan').prop('disabled', true).text('Menyimpan...');

            $.ajax({
                url: "<?= site_url('barang/simpan'); ?>",
                type: "POST",
                data: $(this).serialize(),
                dataType: "JSON",
                success: function(response) {
                    refreshCsrf(response.csrf_hash);
                    if (response.status) {
                        alert(response.message);
                        $('#modalTambahNamaBarang').modal('hide');
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

        // 2. AJAX AMBIL DATA NAMA BARANG BY ID (UNTUK EDIT)
        $(document).on('click', '.btn-edit', function() {
            var id = $(this).data('id');

            $.ajax({
                url: "<?= site_url('barang/get_by_id/'); ?>" + id,
                type: "GET",
                dataType: "JSON",
                success: function(response) {
                    if (response.status) {
                        $('#edit_id').val(response.data.id);
                        $('#edit_kode').val(response.data.kode_barang);
                        $('#edit_nama').val(response.data.nama);
                        $('#edit_id_kategori').val(response.data.id_kategori);
                        $('#edit_jenis').val(response.data.jenis_barang);
                        $('#edit_satuan').val(response.data.satuan);
                        $('#edit_dimensi').val(response.data.dimensi);
                        $('#edit_stok').val(response.data.stok_minimum);

                        $('#modalEditNamaBarang').modal('show');
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan saat mengambil data.');
                }
            });
        });

        // 3. AJAX UPDATE NAMA BARANG
        $('#formEditNamaBarang').on('submit', function(e) {
            e.preventDefault();
            $('#btnUpdate').prop('disabled', true).text('Memperbarui...');

            $.ajax({
                url: "<?= site_url('barang/update'); ?>",
                type: "POST",
                data: $(this).serialize(),
                dataType: "JSON",
                success: function(response) {
                    refreshCsrf(response.csrf_hash);
                    if (response.status) {
                        alert(response.message);
                        $('#modalEditNamaBarang').modal('hide');
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

        // 4. AJAX DELETE NAMA BARANG
        $(document).on('click', '.btn-delete', function() {
            var id = $(this).data('id');
            var nama = $(this).data('nama');

            if (confirm('Apakah Anda yakin ingin menghapus barang "' + nama + '"?')) {
                $.ajax({
                    url: "<?= site_url('barang/delete/'); ?>" + id,
                    type: "POST",
                    data: getCsrfData(),
                    dataType: "JSON",
                    success: function(response) {
                        refreshCsrf(response.csrf_hash);
                        if (response.status) {
                            alert(response.message);
                            // Kalau halaman saat ini jadi kosong setelah hapus (mis. hapus data terakhir
                            // di halaman terakhir), loadData akan otomatis mundur satu halaman.
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