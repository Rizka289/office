<!-- ================= GRID DATA STOK ================= -->
<div class="card card-custom p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold m-0"><?= translate('stok') ?></h6>
    </div>

    <!-- ================= SEARCH BOX ================= -->
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" id="searchStok" class="form-control" placeholder="Cari kode / nama / warna / ukuran / opsi / lokasi / keterangan ...">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;"><?= translate('no') ?></th>
                    <th><?= translate('kode') ?></th>
                    <th><?= translate('nama_barang') ?></th>
                    <th><?= translate('warna') ?></th>
                    <th>Ukuran</th>
                    <th>Opsi</th>
                    <th><?= translate('keterangan') ?></th>
                    <th><?= translate('lokasi_gudang') ?></th>
                    <th><?= translate('stok') ?></th>
                    <th class="text-center"><?= translate('aksi') ?></th>
                </tr>
            </thead>
            <tbody id="stokTableBody">
                <tr>
                    <td colspan="10" class="text-center text-muted">Memuat data...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ================= INFO + PAGINATION ================= -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted" id="stokInfo"></small>
        <nav aria-label="Pagination Stok">
            <ul class="pagination pagination-sm mb-0" id="stokPagination"></ul>
        </nav>
    </div>
</div>

<script>
    $(document).ready(function() {
        var currentPage = 1;
        var currentSearch = '';
        var searchTimer = null;

        var listDataUrl = "<?= site_url('stok/list_data'); ?>";
        var riwayatUrl  = "<?= site_url('stok_riwayat'); ?>";
        var COLS = 10; // jumlah kolom tabel

        function escapeHtml(str) {
            return $('<div>').text(str == null ? '' : str).html();
        }

        // Tampilkan "-" kalau nilai kosong
        function textOrDash(str) {
            return (str == null || str === '') ? '<span class="text-muted">-</span>' : escapeHtml(str);
        }

        // bukaan_kanan -> "bukaan kanan"
        function formatOpsi(str) {
            if (str == null || str === '') return '<span class="text-muted">-</span>';
            return '<span class="badge bg-secondary">' + escapeHtml(String(str).replace(/_/g, ' ')) + '</span>';
        }

        function renderRows(rows, page, perPage) {
            var $tbody = $('#stokTableBody');
            $tbody.empty();

            if (!rows || rows.length === 0) {
                $tbody.append('<tr><td colspan="' + COLS + '" class="text-center text-muted">Data stok tidak ditemukan.</td></tr>');
                return;
            }

            var startNo = (page - 1) * perPage;
            rows.forEach(function(stok, idx) {
                var no = startNo + idx + 1;
                var lokasi = escapeHtml(stok.kode_lokasi) + '-' +
                    (stok.nama_lokasi ? escapeHtml(stok.nama_lokasi) : '<span class="text-muted">-</span>');

                // Detail -> buka Kartu Stok dengan filter barang + lokasi
                var href = riwayatUrl + '?id_barang=' + encodeURIComponent(stok.id_barang) +
                    '&id_location=' + encodeURIComponent(stok.id_location);

                var tr = '<tr>' +
                    '<td>' + no + '</td>' +
                    '<td class="fw-semibold">' + textOrDash(stok.kode_barang) + '</td>' +
                    '<td class="fw-semibold">' + textOrDash(stok.nama_barang) + '</td>' +
                    '<td>' + textOrDash(stok.warna) + '</td>' +
                    '<td>' + textOrDash(stok.ukuran) + '</td>' +
                    '<td>' + formatOpsi(stok.opsi) + '</td>' +
                    '<td>' + textOrDash(stok.keterangan) + '</td>' +
                    '<td>' + lokasi + '</td>' +
                    '<td><span class="badge bg-info text-dark">' + escapeHtml(stok.total_stok) + '</span></td>' +
                    '<td class="text-center">' +
                    '<a class="btn btn-sm btn-outline-primary" href="' + href + '" title="Lihat riwayat"><i class="bi bi-eye"></i></a>' +
                    '</td>' +
                    '</tr>';
                $tbody.append(tr);
            });
        }

        function renderPagination(currentPage, totalPages) {
            var $pg = $('#stokPagination');
            $pg.empty();

            if (totalPages <= 1) return;

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

        function loadData(page, search) {
            currentPage = page;
            currentSearch = search;

            $.ajax({
                url: listDataUrl,
                type: 'GET',
                data: { page: page, search: search },
                dataType: 'JSON',
                success: function(response) {
                    if (response.status) {
                        renderRows(response.data, response.current_page, response.per_page);
                        renderPagination(response.current_page, response.total_pages);

                        var start = response.total === 0 ? 0 : ((response.current_page - 1) * response.per_page) + 1;
                        var end = Math.min(response.current_page * response.per_page, response.total);
                        $('#stokInfo').text('Menampilkan ' + start + '-' + end + ' dari ' + response.total + ' data');
                    } else {
                        $('#stokTableBody').html('<tr><td colspan="' + COLS + '" class="text-center text-danger">Gagal memuat data.</td></tr>');
                    }
                },
                error: function() {
                    $('#stokTableBody').html('<tr><td colspan="' + COLS + '" class="text-center text-danger">Terjadi kesalahan saat memuat data.</td></tr>');
                }
            });
        }

        loadData(1, '');

        $(document).on('click', '#stokPagination a.page-link', function(e) {
            e.preventDefault();
            var $li = $(this).closest('li');
            if ($li.hasClass('disabled') || $li.hasClass('active')) return;
            loadData(parseInt($(this).data('page'), 10), currentSearch);
        });

        // Live search (debounce)
        $('#searchStok').on('keyup', function() {
            var keyword = $(this).val();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                loadData(1, keyword);
            }, 400);
        });
    });
</script>