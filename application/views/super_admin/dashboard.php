<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Keuangan | PT Cipta Jendela Pintu</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
    <style>
        :root {
            --brand: #0b5d5b;
            --brand-dark: #083f3e;
            --brand-light: #e6f2f1;
            --accent: #c98a3e;
            --bg: #f4f6f7;
            --text-muted: #6b7280;
        }

        body {
            background: var(--bg);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #1f2937;
        }

        /* ===== Sidebar (desktop) ===== */
        .sidebar {
            background: linear-gradient(180deg, var(--brand-dark), var(--brand));
            min-height: 100vh;
            color: #fff;
            padding-top: 1.25rem;
        }

        .sidebar .brand {
            font-weight: 700;
            font-size: 1.05rem;
            padding: 0 1.25rem 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, .15);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: .6rem;
        }

        .sidebar .brand i {
            font-size: 1.5rem;
            color: var(--accent);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, .8);
            padding: .65rem 1.25rem;
            border-radius: 0;
            font-size: .92rem;
            display: flex;
            align-items: center;
            gap: .65rem;
        }

        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, .12);
            color: #fff;
            border-left: 3px solid var(--accent);
        }

        .sidebar .nav-link i {
            width: 18px;
            text-align: center;
        }

        /* Offcanvas mobile menggunakan style sidebar yang sama */
        .offcanvas.sidebar {
            min-height: auto;
            width: 260px;
        }

        /* ===== Topbar ===== */
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: .75rem 1rem;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .btn-hamburger {
            border: 1px solid #e5e7eb;
            background: #fff;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* ===== Profile dropdown ===== */
        .profile-menu {
            position: relative;
        }

        .profile-trigger {
            cursor: pointer;
        }

        .profile-menu .profile-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            padding-top: 10px;
            min-width: 190px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(4px);
            transition: opacity .15s ease, transform .15s ease, visibility .15s;
            z-index: 1050;
        }

        .profile-menu:hover .profile-dropdown,
        .profile-menu:focus-within .profile-dropdown,
        .profile-menu.show .profile-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile-dropdown-inner {
            background: #fff;
            border: 1px solid #edf0f1;
            border-radius: 10px;
            box-shadow: 0 10px 28px rgba(15, 42, 41, .14);
            padding: .4rem 0;
            overflow: hidden;
        }

        .profile-dropdown-header {
            font-size: .72rem;
            color: var(--text-muted);
            padding: .55rem 1rem .35rem;
        }

        .profile-dropdown-item {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .5rem 1rem;
            font-size: .85rem;
            color: #1f2937;
            text-decoration: none;
        }

        .profile-dropdown-item i {
            font-size: 1rem;
            color: var(--text-muted);
            width: 18px;
            text-align: center;
        }

        .profile-dropdown-item:hover,
        .profile-dropdown-item.active {
            background: var(--brand-light);
            color: var(--brand);
        }

        .profile-dropdown-item:hover i,
        .profile-dropdown-item.active i {
            color: var(--brand);
        }

        /* ===== KPI cards ===== */
        .kpi-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #edf0f1;
            padding: 1rem 1.1rem;
            box-shadow: 0 2px 10px rgba(15, 42, 41, .04);
            height: 100%;
        }

        .kpi-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .kpi-value {
            font-size: 1.3rem;
            font-weight: 700;
            margin: .3rem 0 .1rem;
            word-break: break-word;
        }

        .kpi-label {
            color: var(--text-muted);
            font-size: .78rem;
        }

        .badge-trend {
            font-size: .7rem;
            font-weight: 600;
            padding: .25rem .5rem;
            border-radius: 20px;
            white-space: nowrap;
        }

        .trend-up {
            background: #e7f7ee;
            color: #0f9d58;
        }

        .trend-down {
            background: #fdecec;
            color: #d9534f;
        }

        .card-panel {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #edf0f1;
            box-shadow: 0 2px 10px rgba(15, 42, 41, .04);
        }

        .card-panel .card-header {
            background: #fff;
            border-bottom: 1px solid #f1f3f4;
            font-weight: 600;
            border-radius: 14px 14px 0 0 !important;
            font-size: .92rem;
        }

        .card-panel .card-body {
            padding: 1rem;
        }

        .chart-wrap {
            position: relative;
            width: 100%;
            height: 280px;
        }

        .chart-wrap canvas {
            max-width: 100%;
        }

        /* ===== Neraca table ===== */
        .table-neraca th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: var(--text-muted);
            background: var(--brand-light);
            border-bottom: none;
            white-space: nowrap;
        }

        .table-neraca td,
        .table-neraca th {
            padding: .55rem .75rem;
            font-size: .85rem;
        }

        .row-total {
            font-weight: 700;
            background: #fafbfb;
        }

        .row-group {
            font-weight: 600;
            color: var(--brand);
        }

        .chip {
            display: inline-block;
            font-size: .7rem;
            padding: .2rem .55rem;
            border-radius: 20px;
            background: var(--brand-light);
            color: var(--brand);
            font-weight: 600;
            white-space: nowrap;
        }

        .table-responsive {
            -webkit-overflow-scrolling: touch;
        }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-thumb {
            background: #c9d3d2;
            border-radius: 10px;
        }

        /* ===== Mobile breakpoints ===== */
        @media (max-width: 991.98px) {
            .sidebar {
                display: none;
            }

            /* sidebar statis disembunyikan, dipakai offcanvas */
            main {
                width: 100%;
            }
        }

        @media (max-width: 767.98px) {
            h5 {
                font-size: 1rem;
            }

            .topbar small {
                font-size: .72rem;
            }

            .kpi-value {
                font-size: 1.15rem;
            }

            .kpi-icon {
                width: 34px;
                height: 34px;
                font-size: 1rem;
            }

            .card-panel .card-header {
                font-size: .85rem;
                padding: .65rem .85rem;
            }

            .card-panel .card-body {
                padding: .75rem;
            }

            .chart-wrap {
                height: 220px;
            }

            .table-neraca td,
            .table-neraca th {
                font-size: .78rem;
                padding: .45rem .55rem;
            }

            .p-lg-4 {
                padding: .75rem !important;
            }

            .form-select-sm {
                font-size: .8rem;
            }
        }

        @media (max-width: 575.98px) {
            .kpi-card {
                padding: .85rem .9rem;
            }

            .kpi-value {
                font-size: 1.05rem;
            }

            .kpi-label {
                font-size: .72rem;
            }

            .badge-trend {
                font-size: .65rem;
            }

            .chart-wrap {
                height: 200px;
            }

            .topbar {
                padding: .6rem .75rem;
            }

            .table-neraca td,
            .table-neraca th {
                font-size: .72rem;
                padding: .4rem .45rem;
            }
        }
    </style>
</head>

<body>

    <!-- Offcanvas Sidebar (mobile & tablet) -->
    <div class="offcanvas offcanvas-start sidebar text-bg-dark" tabindex="-1" id="mobileSidebar">
        <div class="d-flex justify-content-between align-items-center px-3 pt-3">
            <div class="brand mb-0 pb-0 border-0">
                <i class="bi bi-columns-gap"></i>
                <div>PT Cipta<br>Jendela Pintu</div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <hr class="text-white-50 mx-3">
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link active" href="#"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-bar-chart-line"></i> Laporan Laba Rugi</a></li>
            <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-journal-text"></i> Neraca (Balance Sheet)</a></li>
            <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-cash-coin"></i> Arus Kas</a></li>
            <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-box-seam"></i> Produksi &amp; Persediaan</a></li>
            <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-people"></i> Piutang Pelanggan</a></li>
            <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-truck"></i> Hutang Supplier</a></li>
            <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-gear"></i> Pengaturan</a></li>
        </ul>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar statis (desktop only, lg ke atas) -->
            <nav class="col-lg-2 sidebar d-none d-lg-flex flex-column p-0">
                <div class="brand">
                    <i class="bi bi-columns-gap"></i>
                    <div>PT Cipta<br>Jendela Pintu</div>
                </div>
                <ul class="nav flex-column mb-auto">
                    <li class="nav-item"><a class="nav-link active" href="#"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-bar-chart-line"></i> Laporan Laba Rugi</a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-journal-text"></i> Neraca (Balance Sheet)</a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-cash-coin"></i> Arus Kas</a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-box-seam"></i> Produksi &amp; Persediaan</a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-people"></i> Piutang Pelanggan</a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-truck"></i> Hutang Supplier</a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-gear"></i> Pengaturan</a></li>
                </ul>
                <div class="p-3 small text-white-50 border-top border-white border-opacity-25">
                    &copy; 2026 PT Cipta Jendela Pintu
                </div>
            </nav>

            <!-- Main -->
            <main class="col-12 col-lg-10 px-0">
                <!-- Topbar -->
                <div class="topbar d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn-hamburger d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-label="Buka menu">
                            <i class="bi bi-list fs-4"></i>
                        </button>
                        <div>
                            <h5 class="mb-0">Dashboard Keuangan</h5>
                            <small class="text-muted d-none d-sm-block">Divisi Produksi Jendela &amp; Pintu Aluminium/UPVC</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm" style="width:auto;">
                            <option>Tahun Buku 2026</option>
                            <option>Tahun Buku 2025</option>
                            <option>Tahun Buku 2024</option>
                        </select>

                        <button class="btn-hamburger" type="button" id="langToggle" title="Ganti bahasa" aria-label="Ganti bahasa">
                            <i class="bi bi-translate"></i>
                        </button>
                       

                        <div class="profile-menu" tabindex="0">
                            <div class="profile-trigger d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-secondary bg-opacity-25 d-flex align-items-center justify-content-center flex-shrink-0" style="width:34px;height:34px;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div class="d-none d-md-block text-end lh-sm">
                                    <div class="fw-semibold" style="font-size:.85rem;">Admin</div>
                                    <div class="text-muted" style="font-size:.72rem;">admin</div>
                                </div>
                            </div>
                            <div class="profile-dropdown">
                                <div class="profile-dropdown-inner">
                                    <div class="profile-dropdown-header">Welcome!</div>
                                    <a href="#" class="profile-dropdown-item active"><i class="bi bi-person"></i> Profil</a>
                                    <a href="<?= site_url('login/logout'); ?>" class="profile-dropdown-item"><i class="bi bi-box-arrow-right"></i> Logout</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-3 p-lg-4">

                    <!-- KPI Row -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-lg-3">
                            <div class="kpi-card">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="kpi-icon" style="background:var(--brand-light); color:var(--brand);"><i class="bi bi-graph-up-arrow"></i></div>
                                    <span class="badge-trend trend-up"><i class="bi bi-arrow-up-short"></i>12.4%</span>
                                </div>
                                <div class="kpi-value">Rp 18,4 M</div>
                                <div class="kpi-label">Total Pendapatan (YTD)</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="kpi-card">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="kpi-icon" style="background:#fdf3e7; color:var(--accent);"><i class="bi bi-cash-stack"></i></div>
                                    <span class="badge-trend trend-up"><i class="bi bi-arrow-up-short"></i>8.1%</span>
                                </div>
                                <div class="kpi-value">Rp 3,26 M</div>
                                <div class="kpi-label">Laba Bersih (YTD)</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="kpi-card">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="kpi-icon" style="background:#e9f2fb; color:#2563eb;"><i class="bi bi-bank"></i></div>
                                    <span class="badge-trend trend-up"><i class="bi bi-arrow-up-short"></i>5.7%</span>
                                </div>
                                <div class="kpi-value">Rp 22,9 M</div>
                                <div class="kpi-label">Total Aset</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="kpi-card">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="kpi-icon" style="background:#fdecec; color:#d9534f;"><i class="bi bi-exclamation-diamond"></i></div>
                                    <span class="badge-trend trend-down"><i class="bi bi-arrow-down-short"></i>3.2%</span>
                                </div>
                                <div class="kpi-value">Rp 9,1 M</div>
                                <div class="kpi-label">Total Kewajiban</div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-lg-7">
                            <div class="card-panel h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-bar-chart-line me-1"></i> Pendapatan vs Laba Bersih Bulanan</span>
                                    <span class="chip">2026</span>
                                </div>
                                <div class="card-body">
                                    <div class="chart-wrap"><canvas id="chartRevenue"></canvas></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-5">
                            <div class="card-panel h-100">
                                <div class="card-header">
                                    <i class="bi bi-pie-chart me-1"></i> Komposisi Pendapatan per Lini Produk
                                </div>
                                <div class="card-body">
                                    <div class="chart-wrap"><canvas id="chartProduct"></canvas></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-lg-6">
                            <div class="card-panel h-100">
                                <div class="card-header">
                                    <i class="bi bi-diagram-3 me-1"></i> Aset vs Kewajiban vs Ekuitas
                                </div>
                                <div class="card-body">
                                    <div class="chart-wrap"><canvas id="chartBalance"></canvas></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="card-panel h-100">
                                <div class="card-header">
                                    <i class="bi bi-water me-1"></i> Arus Kas Operasional (6 Bulan Terakhir)
                                </div>
                                <div class="card-body">
                                    <div class="chart-wrap"><canvas id="chartCashflow"></canvas></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- NERACA -->
                    <div class="card-panel mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span><i class="bi bi-journal-text me-1"></i> Neraca (Balance Sheet) — per 31 Agustus 2026</span>
                            <span class="chip">Dalam Rupiah (Rp)</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="row g-0">
                                <!-- AKTIVA -->
                                <div class="col-12 col-lg-6 border-end-lg border-bottom border-lg-bottom-0">
                                    <div class="table-responsive">
                                        <table class="table table-neraca mb-0">
                                            <thead>
                                                <tr>
                                                    <th colspan="2">Aktiva (Assets)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="row-group">
                                                    <td colspan="2">Aset Lancar</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4">Kas &amp; Setara Kas</td>
                                                    <td class="text-end">2.150.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4">Piutang Usaha</td>
                                                    <td class="text-end">3.420.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4">Persediaan Bahan Baku (Kaca, Aluminium, UPVC)</td>
                                                    <td class="text-end">4.680.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4">Persediaan Barang Jadi</td>
                                                    <td class="text-end">1.250.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4 fw-semibold">Total Aset Lancar</td>
                                                    <td class="text-end fw-semibold">11.500.000.000</td>
                                                </tr>

                                                <tr class="row-group">
                                                    <td colspan="2">Aset Tetap</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4">Mesin Produksi &amp; Fabrikasi</td>
                                                    <td class="text-end">6.200.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4">Bangunan Pabrik &amp; Gudang</td>
                                                    <td class="text-end">4.100.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4">Kendaraan Operasional</td>
                                                    <td class="text-end">1.100.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4">Akumulasi Penyusutan</td>
                                                    <td class="text-end text-danger">(1.400.000.000)</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4 fw-semibold">Total Aset Tetap</td>
                                                    <td class="text-end fw-semibold">10.000.000.000</td>
                                                </tr>

                                                <tr>
                                                    <td class="ps-4">Aset Lain-lain</td>
                                                    <td class="text-end">1.400.000.000</td>
                                                </tr>
                                                <tr class="row-total">
                                                    <td>TOTAL AKTIVA</td>
                                                    <td class="text-end">22.900.000.000</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- PASIVA -->
                                <div class="col-12 col-lg-6">
                                    <div class="table-responsive">
                                        <table class="table table-neraca mb-0">
                                            <thead>
                                                <tr>
                                                    <th colspan="2">Pasiva (Liabilities &amp; Equity)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="row-group">
                                                    <td colspan="2">Kewajiban Lancar</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4">Hutang Usaha (Supplier Kaca/Alumunium)</td>
                                                    <td class="text-end">2.850.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4">Hutang Pajak</td>
                                                    <td class="text-end">610.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4">Biaya Yang Masih Harus Dibayar</td>
                                                    <td class="text-end">540.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4 fw-semibold">Total Kewajiban Lancar</td>
                                                    <td class="text-end fw-semibold">4.000.000.000</td>
                                                </tr>

                                                <tr class="row-group">
                                                    <td colspan="2">Kewajiban Jangka Panjang</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4">Pinjaman Bank (Investasi Mesin)</td>
                                                    <td class="text-end">5.100.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4 fw-semibold">Total Kewajiban Jangka Panjang</td>
                                                    <td class="text-end fw-semibold">5.100.000.000</td>
                                                </tr>

                                                <tr class="row-total">
                                                    <td>TOTAL KEWAJIBAN</td>
                                                    <td class="text-end">9.100.000.000</td>
                                                </tr>

                                                <tr class="row-group">
                                                    <td colspan="2">Ekuitas</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4">Modal Disetor</td>
                                                    <td class="text-end">8.000.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4">Laba Ditahan</td>
                                                    <td class="text-end">2.540.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4">Laba Tahun Berjalan</td>
                                                    <td class="text-end">3.260.000.000</td>
                                                </tr>
                                                <tr class="row-total">
                                                    <td>TOTAL EKUITAS</td>
                                                    <td class="text-end">13.800.000.000</td>
                                                </tr>

                                                <tr class="row-total" style="background:var(--brand-light);">
                                                    <td>TOTAL KEWAJIBAN + EKUITAS</td>
                                                    <td class="text-end">22.900.000.000</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ringkasan Produk -->
                    <div class="card-panel mb-4">
                        <div class="card-header">
                            <i class="bi bi-box-seam me-1"></i> Ringkasan Penjualan per Lini Produk (Bulan Berjalan)
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead style="background:var(--brand-light);">
                                        <tr>
                                            <th>Produk</th>
                                            <th>Unit Terjual</th>
                                            <th>Pendapatan</th>
                                            <th>Margin Kotor</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Jendela Aluminium Casement</td>
                                            <td>420 unit</td>
                                            <td>Rp 1.680.000.000</td>
                                            <td>34%</td>
                                            <td><span class="badge bg-success-subtle text-success">Baik</span></td>
                                        </tr>
                                        <tr>
                                            <td>Pintu UPVC Sliding</td>
                                            <td>210 unit</td>
                                            <td>Rp 1.155.000.000</td>
                                            <td>29%</td>
                                            <td><span class="badge bg-success-subtle text-success">Baik</span></td>
                                        </tr>
                                        <tr>
                                            <td>Jendela Kaca Tempered</td>
                                            <td>310 unit</td>
                                            <td>Rp 930.000.000</td>
                                            <td>22%</td>
                                            <td><span class="badge bg-warning-subtle text-warning">Perhatian</span></td>
                                        </tr>
                                        <tr>
                                            <td>Pintu Aluminium Swing</td>
                                            <td>180 unit</td>
                                            <td>Rp 720.000.000</td>
                                            <td>31%</td>
                                            <td><span class="badge bg-success-subtle text-success">Baik</span></td>
                                        </tr>
                                        <tr>
                                            <td>Curtain Wall Custom Project</td>
                                            <td>4 proyek</td>
                                            <td>Rp 2.100.000.000</td>
                                            <td>18%</td>
                                            <td><span class="badge bg-danger-subtle text-danger">Perlu Review</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
    <script>
        const brand = '#0b5d5b';
        const accent = '#c98a3e';
        const blue = '#2563eb';
        const red = '#d9534f';

        const isMobile = () => window.innerWidth < 576;

        // Profile dropdown: hover bekerja otomatis lewat CSS,
        // tambahan klik ini supaya tetap bisa dipakai di layar sentuh (mobile/tablet)
        document.querySelectorAll('.profile-menu').forEach((menu) => {
            const trigger = menu.querySelector('.profile-trigger');
            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                menu.classList.toggle('show');
            });
        });
        document.addEventListener('click', () => {
            document.querySelectorAll('.profile-menu.show').forEach((m) => m.classList.remove('show'));
        });

        // Fullscreen toggle
        const fullscreenBtn = document.getElementById('fullscreenToggle');
        fullscreenBtn.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
                fullscreenBtn.querySelector('i').className = 'bi bi-fullscreen-exit';
            } else {
                document.exitFullscreen();
                fullscreenBtn.querySelector('i').className = 'bi bi-arrows-fullscreen';
            }
        });

        // Dark mode toggle (ikon saja, sesuaikan dengan tema dark Anda sendiri)
        const darkModeBtn = document.getElementById('darkModeToggle');
        darkModeBtn.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const icon = darkModeBtn.querySelector('i');
            icon.className = document.body.classList.contains('dark-mode') ? 'bi bi-sun' : 'bi bi-moon';
        });

        // Revenue vs Net Profit
        const chartRevenue = new Chart(document.getElementById('chartRevenue'), {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu'],
                datasets: [{
                        label: 'Pendapatan',
                        data: [1.9, 2.0, 2.1, 2.3, 2.4, 2.5, 2.6, 2.6],
                        backgroundColor: brand,
                        borderRadius: 6,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Laba Bersih',
                        type: 'line',
                        data: [0.32, 0.34, 0.35, 0.38, 0.40, 0.41, 0.43, 0.45],
                        borderColor: accent,
                        backgroundColor: accent,
                        tension: 0.35,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: 11
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        position: 'left',
                        title: {
                            display: !isMobile(),
                            text: 'Miliar Rp'
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    },
                    y1: {
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        title: {
                            display: !isMobile(),
                            text: 'Laba (Miliar)'
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });

        // Product composition
        const chartProduct = new Chart(document.getElementById('chartProduct'), {
            type: 'doughnut',
            data: {
                labels: ['Jendela Aluminium', 'Pintu UPVC', 'Jendela Kaca Tempered', 'Pintu Aluminium', 'Curtain Wall Proyek'],
                datasets: [{
                    data: [1680, 1155, 930, 720, 2100],
                    backgroundColor: [brand, '#1a8783', accent, '#e0a95f', blue]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });

        // Assets vs Liabilities vs Equity
        const chartBalance = new Chart(document.getElementById('chartBalance'), {
            type: 'bar',
            data: {
                labels: ['Total Aset', 'Total Kewajiban', 'Total Ekuitas'],
                datasets: [{
                    label: 'Miliar Rp',
                    data: [22.9, 9.1, 13.8],
                    backgroundColor: [blue, red, brand],
                    borderRadius: 8
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: !isMobile(),
                            text: 'Miliar Rupiah'
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    },
                    y: {
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });

        // Cashflow
        const chartCashflow = new Chart(document.getElementById('chartCashflow'), {
            type: 'line',
            data: {
                labels: ['Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu'],
                datasets: [{
                    label: 'Arus Kas Operasional',
                    data: [0.45, 0.52, 0.48, 0.61, 0.58, 0.66],
                    fill: true,
                    backgroundColor: 'rgba(11,93,91,0.12)',
                    borderColor: brand,
                    tension: 0.35,
                    pointBackgroundColor: brand
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        title: {
                            display: !isMobile(),
                            text: 'Miliar Rp'
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });

        // Update chart label visibility on resize (debounced)
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                const mobile = isMobile();
                chartRevenue.options.scales.y.title.display = !mobile;
                chartRevenue.options.scales.y1.title.display = !mobile;
                chartBalance.options.scales.x.title.display = !mobile;
                chartCashflow.options.scales.y.title.display = !mobile;
                chartRevenue.update();
                chartBalance.update();
                chartCashflow.update();
            }, 200);
        });
    </script>
</body>

</html>