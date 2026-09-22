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
                <input type="text" id="searchStok" class="form-control" placeholder="Cari nama barang / lokasi ...">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;"><?= translate('no') ?></th>
                    <th><?= translate('nama_barang') ?></th>
                    <th><?= translate('lokasi_gudang') ?></th>
                    <th><?= translate('stok') ?></th>
                    <th class="text-center"><?= translate('aksi') ?></th>
                </tr>
            </thead>
            <tbody id="stokTableBody">
                <tr>
                    <td colspan="5" class="text-center text-muted">Memuat data...</td>
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

        // Endpoint diarahkan ke Controller Stok
        var listDataUrl = "<?= site_url('stok/list_data'); ?>"; 

        function escapeHtml(str) {
            return $('<div>').text(str == null ? '' : str).html();
        }

        // Render baris tabel dari data JSON
        function renderRows(rows, page, perPage) {
            var $tbody = $('#stokTableBody');
            $tbody.empty();

            if (!rows || rows.length === 0) {
                $tbody.append('<tr><td colspan="5" class="text-center text-muted">Data stok tidak ditemukan.</td></tr>');
                return;
            }

            var startNo = (page - 1) * perPage;
            rows.forEach(function(stok, idx) {
                var no = startNo + idx + 1;
                var tr = '<tr>' +
                    '<td>' + no + '</td>' +
                    '<td class="fw-semibold">' + escapeHtml(stok.nama_barang) + '</td>' +
                    '<td>' + escapeHtml(stok.kode_lokasi) + '-' + (stok.nama_lokasi ? escapeHtml(stok.nama_lokasi) : '<span class="text-muted">-</span>') + '</td>' +
                    '<td><span class="badge bg-info text-dark">' + escapeHtml(stok.total_stok) + '</span></td>' +
                    '<td class="text-center">' +
                    '<button class="btn btn-sm btn-outline-primary btn-detail" data-barang="' + stok.id_barang + '" data-location="' + stok.id_location + '" title="Detail"><i class="bi bi-eye"></i></button>' +
                    '</td>' +
                    '</tr>';
                $tbody.append(tr);
            });
        }

        // Render kontrol pagination
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

        // Ambil data dari server
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
                        $('#stokInfo').text('Menampilkan ' + start + '-' + end + ' dari ' + response.total + ' data');
                    } else {
                        $('#stokTableBody').html('<tr><td colspan="5" class="text-center text-danger">Gagal memuat data.</td></tr>');
                    }
                },
                error: function() {
                    $('#stokTableBody').html('<tr><td colspan="5" class="text-center text-danger">Terjadi kesalahan saat memuat data.</td></tr>');
                }
            });
        }

        // Load data awal
        loadData(1, '');

        // Click Pagination
        $(document).on('click', '#stokPagination a.page-link', function(e) {
            e.preventDefault();
            var $li = $(this).closest('li');
            if ($li.hasClass('disabled') || $li.hasClass('active')) return;
            var page = parseInt($(this).data('page'), 10);
            loadData(page, currentSearch);
        });

        // Live Search Debounce
        $('#searchStok').on('keyup', function() {
            var keyword = $(this).val();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                loadData(1, keyword);
            }, 400);
        });
    });
</script>