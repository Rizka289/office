<!-- ================= GRID DATA NAMA BARANG ================= -->
<div class="card card-custom p-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="fw-bold m-0"> </h6>
        <button type="button" class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#modalTambahNamaBarang">
            <i class="bi bi-plus-lg"></i><?= translate('add') ?>
        </button>
    </div>

    <!-- Search -->
    <div class="row mb-3">
        <div class="col-12 col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" id="searchNamaBarang" class="form-control" placeholder= " <?= translate('cari')?> ">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th><?= translate('no') ?></th>
                    <th>Foto</th>
                    <th><?= translate('kode') ?></th>
                    <th><?= translate('kategori_barang') ?></th>
                    <th><?= translate('nama_barang') ?></th>
                    <th><?= translate('warna') ?></th>
                    <th><?= translate('jenis') ?></th>
                    <th><?= translate('satuan') ?></th>
                    <th><?= translate('dimensi') ?></th>
                    <th><?= translate('harga') ?></th>
                    <th><?= translate('min_stok') ?></th>
                    <th class="text-center"><?= translate('aksi') ?></th>
                </tr>
            </thead>
            <tbody id="tbodyNamaBarang">
                <tr>
                    <td colspan="11" class="text-center text-muted">Memuat data...</td>
                </tr>
            </tbody>
        </table>
    </div>

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
                <h5 class="modal-title fs-6" id="modalTambahNamaBarangLabel"><i class="bi bi-plus-circle"></i> <?= translate('add') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formTambahNamaBarang" autocomplete="off" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" class="csrf-field" value="<?= $this->security->get_csrf_hash(); ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('fb') ?></label>
                        <input type="file" name="gambar" class="form-control form-control-sm" accept="image/*">
                        <div class="form-text extra-small text-muted">Format: JPG, PNG, WEBP (Maksimal 2MB)</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('kode') ?></label>
                        <input type="text" name="kode" class="form-control form-control-sm" placeholder="<?= translate('p_bar_kode') ?>" autocomplete="off" required>
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
                        <input type="text" name="nama" class="form-control form-control-sm" placeholder="<?= translate('p_bar_nama') ?>" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('warna') ?></label>
                        <input type="text" name="warna" class="form-control form-control-sm" placeholder="<?= translate('p_bar_warna') ?>" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('jenis') ?></label>
                        <select name="jenis" class="form-select form-select-sm" required>
                            <option value="">-- <?= translate('select') ?> --</option>
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
                            <option value="">-- <?= translate('select') ?> --</option>
                            <option value="pcs">Pcs</option>
                            <option value="set/padang">Set/pasang</option>
                            <option value="unit">Unit</option>
                            <option value="batang">Batang</option>
                            <option value="meter">Meter</option>
                            <option value="lembar">Lembar</option>
                            <option value="m2">m2</option>
                            <option value="m3">m3</option>
                            <option value="kaleng">Kaleng</option>
                            <option value="kg">Kg</option>
                            <option value="liter">Liter</option>
                            <option value="roll">Roll</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('harga') ?></label>
                        <input type="text" name="harga_satuan" class="form-control form-control-sm input-harga" placeholder="Contoh: 15000,50 atau 15000.50" autocomplete="off" required>
                        <div class="form-text extra-small text-muted"><?= translate('p_bar_harga') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('Dimensi') ?></label>
                        <input type="text" name="dimensi" class="form-control form-control-sm" placeholder="<?= translate('p_bar_dimensi') ?>" autocomplete="off" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('label_bar') ?></label>
                        <select name="is_produced" id="is_produced" class="form-select form-select-sm" required>
                            <option value="0">Dibeli / Non-Produksi (Bahan Baku / Aksesori / Trading)</option>
                            <option value="1">Diproduksi Sendiri / Custom (Pintu & Jendela)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('min_stok') ?></label>
                        <input type="number" name="stok_minimum" min="0" class="form-control form-control-sm" placeholder="<?= translate('p_bar_min') ?>" autocomplete="off" required>
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
            <form id="formEditNamaBarang" autocomplete="off" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" class="csrf-field" value="<?= $this->security->get_csrf_hash(); ?>">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="mb-3 text-center">
                        <img id="edit_preview_gambar" src="" class="img-thumbnail mb-2" style="max-height: 120px;">
                        <div>
                            <label class="form-label small fw-bold d-block">Ubah Foto Barang (Opsional)</label>
                            <input type="file" name="gambar" class="form-control form-control-sm" accept="image/*">
                        </div>
                    </div>

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
                        <label class="form-label small fw-bold"><?= translate('warna') ?></label>
                        <input type="text" name="warna" id="edit_warna" class="form-control form-control-sm" placeholder="Masukkan Nama Barang" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('jenis') ?></label>
                        <select name="jenis" id="edit_jenis" class="form-select form-select-sm" required>
                            <option value="">-- <?= translate('select') ?> --</option>
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
                            <option value="">-- <?= translate('select') ?>--</option>
                            <option value="pcs">Pcs</option>
                            <option value="set/padang">Set/pasang</option>
                            <option value="unit">Unit</option>
                            <option value="batang">Batang</option>
                            <option value="meter">Meter</option>
                            <option value="lembar">Lembar</option>
                            <option value="m2">m2</option>
                            <option value="m3">m3</option>
                            <option value="kaleng">Kaleng</option>
                            <option value="kg">Kg</option>
                            <option value="liter">Liter</option>
                            <option value="roll">Roll</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('harga') ?></label>
                        <input type="text" name="harga_satuan" id="edit_harga" class="form-control form-control-sm input-harga" placeholder="Contoh: 15000,50 atau 15000.50" required>
                        <div class="form-text extra-small text-muted">Gunakan tanda koma (,) atau titik (.) untuk desimal.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= translate('dimensi') ?></label>
                        <input type="text" name="dimensi" id="edit_dimensi" class="form-control form-control-sm" placeholder="Masukkan Dimensi" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Metode Perolehan / Tipe Barang</label>
                        <select name="is_produced" id="edit_is_produced" class="form-select form-select-sm" required>
                            <option value="0">Dibeli / Non-Produksi (Bahan Baku / Aksesori / Trading)</option>
                            <option value="1">Diproduksi Sendiri / Custom (Pintu & Jendela)</option>
                        </select>
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

<script>
    $(document).ready(function() {

        var currentPage = 1;
        var currentSearch = '';
        var searchTimer = null;

        function refreshCsrf(hash) {
            if (hash) {
                $('.csrf-field').val(hash);
            }
        }

        function getCsrfData() {
            var data = {};
            data[$('.csrf-field').attr('name')] = $('.csrf-field').val();
            return data;
        }

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return $('<div>').text(str).html();
        }

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
                        if (response.data.length === 0 && response.current_page > 1 && response.total > 0) {
                            loadData(response.current_page - 1, currentSearch);
                            return;
                        }
                        renderTable(response.data, response.per_page, response.current_page);
                        renderPagination(response.total_pages, response.current_page);
                        renderInfo(response.total, response.per_page, response.current_page, response.data.length);
                    } else {
                        $('#tbodyNamaBarang').html('<tr><td colspan="11" class="text-center text-danger">' + escapeHtml(response.message) + '</td></tr>');
                    }
                },
                error: function() {
                    $('#tbodyNamaBarang').html('<tr><td colspan="11" class="text-center text-danger">Gagal memuat data.</td></tr>');
                }
            });
        }

        $(document).on('input', '.input-harga, #edit_harga', function() {
            this.value = this.value.replace(/[^0-9.,]/g, '');
        });

        function formatHarga(angka) {
            if (angka === null || angka === undefined || angka === '') return '0';
            var number = parseFloat(angka);
            if (isNaN(number)) return angka;

            return number.toLocaleString('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            });
        }

        function renderTable(rows, perPage, page) {
            var $tbody = $('#tbodyNamaBarang');
            $tbody.empty();

            if (!rows || rows.length === 0) {
                $tbody.html('<tr><td colspan="11" class="text-center text-muted">Data nama barang tidak ditemukan.</td></tr>');
                return;
            }

            var startNo = ((page - 1) * perPage) + 1;

            rows.forEach(function(brg, idx) {
                var badgeProduksi = (brg.is_produced == 1) ?
                    '<span class="badge bg-success">Diproduksi / Custom</span>' :
                    '<span class="badge bg-secondary">Dibeli / Trading</span>';

                var imgHtml = '<img src="' + brg.gambar_url + '" class="rounded" style="width: 40px; height: 40px; object-fit: cover;">';

                var tr = '' +
                    '<tr>' +
                    '<td>' + (startNo + idx) + '</td>' +
                    '<td>' + imgHtml + '</td>' +
                    '<td class="fw-semibold">' + escapeHtml(brg.kode_barang) + '</td>' +
                    '<td>' + escapeHtml(brg.nama_kategori) + '</td>' +
                    '<td>' + escapeHtml(brg.nama) + '<br>' + badgeProduksi + '</td>' +
                    '<td>' + escapeHtml(brg.warna) + '</td>' +
                    '<td>' + escapeHtml(brg.jenis_barang) + '</td>' +
                    '<td>' + escapeHtml(brg.satuan) + '</td>' +
                    '<td>' + escapeHtml(brg.dimensi) + '</td>' +
                    '<td>Rp ' + formatHarga(brg.harga_satuan) + '</td>' +
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

        $(document).on('click', '#paginationNamaBarang .page-link', function(e) {
            e.preventDefault();
            var $li = $(this).closest('.page-item');
            if ($li.hasClass('disabled') || $li.hasClass('active')) return;

            var targetPage = parseInt($(this).data('page'), 10);
            loadData(targetPage, currentSearch);
        });

        $('#searchNamaBarang').on('keyup', function() {
            var keyword = $(this).val();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                loadData(1, keyword);
            }, 400);
        });

        $('#modalTambahNamaBarang').on('show.bs.modal hidden.bs.modal', function() {
            $('#formTambahNamaBarang')[0].reset();
        });

        // 1. AJAX TAMBAH (FormData)
        $('#formTambahNamaBarang').on('submit', function(e) {
            e.preventDefault();
            $('#btnSimpan').prop('disabled', true).text('Menyimpan...');

            var formData = new FormData(this);

            $.ajax({
                url: "<?= site_url('barang/simpan'); ?>",
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                dataType: "JSON",
                success: function(response) {
                    refreshCsrf(response.csrf_hash);
                    if (response.status) {
                        alert(response.message);
                        $('#modalTambahNamaBarang').modal('hide');
                        loadData(1, currentSearch);
                    } else {
                        alert(response.message);
                    }
                    $('#btnSimpan').prop('disabled', false).text('Simpan Data');
                },
                error: function(xhr, status, error) {
                    var msg = 'Terjadi kesalahan saat menyimpan data.';
                    if (xhr.status === 403) msg = 'Token CSRF kadaluwarsa, silakan muat ulang halaman.';
                    alert(msg);
                    $('#btnSimpan').prop('disabled', false).text('Simpan Data');
                }
            });
        });

        // 2. AJAX FETCH EDIT DATA
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
                        $('#edit_warna').val(response.data.warna);
                        $('#edit_id_kategori').val(response.data.id_kategori);
                        $('#edit_jenis').val(response.data.jenis_barang);
                        $('#edit_satuan').val(response.data.satuan);
                        $('#edit_dimensi').val(response.data.dimensi);
                        $('#edit_is_produced').val(response.data.is_produced);
                        $('#edit_harga').val(response.data.harga_satuan);
                        $('#edit_stok').val(response.data.stok_minimum);

                        $('#edit_preview_gambar').attr('src', response.data.gambar_url);

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

        // 3. AJAX UPDATE (FormData)
        $('#formEditNamaBarang').on('submit', function(e) {
            e.preventDefault();
            $('#btnUpdate').prop('disabled', true).text('Memperbarui...');

            var formData = new FormData(this);

            $.ajax({
                url: "<?= site_url('barang/update'); ?>",
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
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
                error: function() {
                    alert('Terjadi kesalahan saat memperbarui data.');
                    $('#btnUpdate').prop('disabled', false).text('Update Data');
                }
            });
        });

        // 4. AJAX DELETE
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
                            loadData(currentPage, currentSearch);
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert('Terjadi kesalahan saat menghapus data.');
                    }
                });
            }
        });

        loadData(1, '');
    });
</script>