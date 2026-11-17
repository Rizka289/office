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
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="<?= translate('i18n_close') ?>"></button>
        </div>
        <hr class="text-white-50 mx-3">
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link active" href="#"><i class="bi bi-speedometer2"></i> <?= translate('i18n_dashboard') ?></a></li>
            <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-bar-chart-line"></i> <?= translate('i18n_laporan_laba_rugi') ?></a></li>
            <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-journal-text"></i> <?= translate('i18n_neraca_balance_sheet') ?></a></li>
            <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-cash-coin"></i> <?= translate('i18n_arus_kas') ?></a></li>
            <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-box-seam"></i> <?= translate('i18n_produksi_persediaan') ?></a></li>
            <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-people"></i> <?= translate('i18n_piutang_pelanggan') ?></a></li>
            <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-truck"></i> <?= translate('i18n_hutang_supplier') ?></a></li>
            <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-gear"></i> <?= translate('i18n_pengaturan') ?></a></li>
        </ul>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar statis (desktop only, lg ke atas) -->
            <nav class="col-lg-2 sidebar d-none d-lg-flex flex-column p-0">
                <div class="brand">
                    <i class="bi bi-columns-gap"></i>
                    <div>PT Ciptadfsfe<br>Jendelsfaa Pintu</div>
                </div>
                <ul class="nav flex-column mb-auto">
                    <li class="nav-item"><a class="nav-link active" href="#"><i class="bi bi-speedometer2"></i> <?= translate('i18n_dashboard') ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-bar-chart-line"></i> <?= translate('i18n_laporan_laba_rugi') ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-journal-text"></i> <?= translate('i18n_neraca_balance_sheet') ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-cash-coin"></i> <?= translate('i18n_arus_kas') ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-box-seam"></i> <?= translate('i18n_produksi_persediaan') ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-people"></i> <?= translate('i18n_piutang_pelanggan') ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-truck"></i> <?= translate('i18n_hutang_supplier') ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-gear"></i> <?= translate('i18n_pengaturan') ?></a></li>
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
                        <button class="btn-hamburger d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-label="<?= translate('i18n_buka_menu') ?>">
                            <i class="bi bi-list fs-4"></i>
                        </button>
                        <div>
                            <h5 class="mb-0"><?= translate('i18n_dashboard_keuangan') ?></h5>
                            <small class="text-muted d-none d-sm-block"><?= translate('i18n_divisi_produksi_jendela_pintu_aluminium') ?></small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm" style="width:auto;">
                            <option><?= translate('i18n_tahun_buku_2026') ?></option>
                            <option><?= translate('i18n_tahun_buku_2025') ?></option>
                            <option><?= translate('i18n_tahun_buku_2024') ?></option>
                        </select>
                        <div class="rounded-circle bg-secondary bg-opacity-25 d-flex align-items-center justify-content-center flex-shrink-0" style="width:34px;height:34px;">
                            <i class="bi bi-person-fill"></i>
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
                                <div class="kpi-label"><?= translate('i18n_total_pendapatan_ytd') ?></div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="kpi-card">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="kpi-icon" style="background:#fdf3e7; color:var(--accent);"><i class="bi bi-cash-stack"></i></div>
                                    <span class="badge-trend trend-up"><i class="bi bi-arrow-up-short"></i>8.1%</span>
                                </div>
                                <div class="kpi-value">Rp 3,26 M</div>
                                <div class="kpi-label"><?= translate('i18n_laba_bersih_ytd') ?></div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="kpi-card">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="kpi-icon" style="background:#e9f2fb; color:#2563eb;"><i class="bi bi-bank"></i></div>
                                    <span class="badge-trend trend-up"><i class="bi bi-arrow-up-short"></i>5.7%</span>
                                </div>
                                <div class="kpi-value">Rp 22,9 M</div>
                                <div class="kpi-label"><?= translate('i18n_total_aset') ?></div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="kpi-card">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="kpi-icon" style="background:#fdecec; color:#d9534f;"><i class="bi bi-exclamation-diamond"></i></div>
                                    <span class="badge-trend trend-down"><i class="bi bi-arrow-down-short"></i>3.2%</span>
                                </div>
                                <div class="kpi-value">Rp 9,1 M</div>
                                <div class="kpi-label"><?= translate('i18n_total_kewajiban') ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-lg-7">
                            <div class="card-panel h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-bar-chart-line me-1"></i> <?= translate('i18n_pendapatan_vs_laba_bersih_bulanan') ?></span>
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
                                    <i class="bi bi-pie-chart me-1"></i> <?= translate('i18n_komposisi_pendapatan_per_lini_produk') ?>
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
                                    <i class="bi bi-diagram-3 me-1"></i> <?= translate('i18n_aset_vs_kewajiban_vs_ekuitas') ?>
                                </div>
                                <div class="card-body">
                                    <div class="chart-wrap"><canvas id="chartBalance"></canvas></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="card-panel h-100">
                                <div class="card-header">
                                    <i class="bi bi-water me-1"></i> <?= translate('i18n_arus_kas_operasional_6_bulan_terakhir') ?>
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
                            <span><i class="bi bi-journal-text me-1"></i> <?= translate('i18n_neraca_balance_sheet_per_31_agustus_2026') ?></span>
                            <span class="chip"><?= translate('i18n_dalam_rupiah_rp') ?></span>
                        </div>
                        <div class="card-body p-0">
                            <div class="row g-0">
                                <!-- AKTIVA -->
                                <div class="col-12 col-lg-6 border-end-lg border-bottom border-lg-bottom-0">
                                    <div class="table-responsive">
                                        <table class="table table-neraca mb-0">
                                            <thead>
                                                <tr>
                                                    <th colspan="2"><?= translate('i18n_aktiva_assets') ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="row-group">
                                                    <td colspan="2"><?= translate('i18n_aset_lancar') ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4"><?= translate('i18n_kas_setara_kas') ?></td>
                                                    <td class="text-end">2.150.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4"><?= translate('i18n_piutang_usaha') ?></td>
                                                    <td class="text-end">3.420.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4"><?= translate('i18n_persediaan_bahan_baku_kaca_aluminium') ?></td>
                                                    <td class="text-end">4.680.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4"><?= translate('i18n_persediaan_barang_jadi') ?></td>
                                                    <td class="text-end">1.250.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4 fw-semibold"><?= translate('i18n_total_aset_lancar') ?></td>
                                                    <td class="text-end fw-semibold">11.500.000.000</td>
                                                </tr>

                                                <tr class="row-group">
                                                    <td colspan="2"><?= translate('i18n_aset_tetap') ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4"><?= translate('i18n_mesin_produksi_fabrikasi') ?></td>
                                                    <td class="text-end">6.200.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4"><?= translate('i18n_bangunan_pabrik_gudang') ?></td>
                                                    <td class="text-end">4.100.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4"><?= translate('i18n_kendaraan_operasional') ?></td>
                                                    <td class="text-end">1.100.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4"><?= translate('i18n_akumulasi_penyusutan') ?></td>
                                                    <td class="text-end text-danger">(1.400.000.000)</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4 fw-semibold"><?= translate('i18n_total_aset_tetap') ?></td>
                                                    <td class="text-end fw-semibold">10.000.000.000</td>
                                                </tr>

                                                <tr>
                                                    <td class="ps-4"><?= translate('i18n_aset_lain_lain') ?></td>
                                                    <td class="text-end">1.400.000.000</td>
                                                </tr>
                                                <tr class="row-total">
                                                    <td><?= translate('i18n_total_aktiva') ?></td>
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
                                                    <th colspan="2"><?= translate('i18n_pasiva_liabilities_equity') ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="row-group">
                                                    <td colspan="2"><?= translate('i18n_kewajiban_lancar') ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4"><?= translate('i18n_hutang_usaha_supplier_kaca_alumunium') ?></td>
                                                    <td class="text-end">2.850.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4"><?= translate('i18n_hutang_pajak') ?></td>
                                                    <td class="text-end">610.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4"><?= translate('i18n_biaya_yang_masih_harus_dibayar') ?></td>
                                                    <td class="text-end">540.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4 fw-semibold"><?= translate('i18n_total_kewajiban_lancar') ?></td>
                                                    <td class="text-end fw-semibold">4.000.000.000</td>
                                                </tr>

                                                <tr class="row-group">
                                                    <td colspan="2"><?= translate('i18n_kewajiban_jangka_panjang') ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4"><?= translate('i18n_pinjaman_bank_investasi_mesin') ?></td>
                                                    <td class="text-end">5.100.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4 fw-semibold"><?= translate('i18n_total_kewajiban_jangka_panjang') ?></td>
                                                    <td class="text-end fw-semibold">5.100.000.000</td>
                                                </tr>

                                                <tr class="row-total">
                                                    <td><?= translate('i18n_total_kewajiban_2') ?></td>
                                                    <td class="text-end">9.100.000.000</td>
                                                </tr>

                                                <tr class="row-group">
                                                    <td colspan="2"><?= translate('i18n_ekuitas') ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4"><?= translate('i18n_modal_disetor') ?></td>
                                                    <td class="text-end">8.000.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4"><?= translate('i18n_laba_ditahan') ?></td>
                                                    <td class="text-end">2.540.000.000</td>
                                                </tr>
                                                <tr>
                                                    <td class="ps-4"><?= translate('i18n_laba_tahun_berjalan') ?></td>
                                                    <td class="text-end">3.260.000.000</td>
                                                </tr>
                                                <tr class="row-total">
                                                    <td><?= translate('i18n_total_ekuitas') ?></td>
                                                    <td class="text-end">13.800.000.000</td>
                                                </tr>

                                                <tr class="row-total" style="background:var(--brand-light);">
                                                    <td><?= translate('i18n_total_kewajiban_ekuitas') ?></td>
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
                            <i class="bi bi-box-seam me-1"></i> <?= translate('i18n_ringkasan_penjualan_per_lini_produk') ?>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead style="background:var(--brand-light);">
                                        <tr>
                                            <th><?= translate('i18n_produk') ?></th>
                                            <th><?= translate('i18n_unit_terjual') ?></th>
                                            <th><?= translate('i18n_pendapatan') ?></th>
                                            <th><?= translate('i18n_margin_kotor') ?></th>
                                            <th><?= translate('status') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><?= translate('i18n_jendela_aluminium_casement') ?></td>
                                            <td>420 unit</td>
                                            <td>Rp 1.680.000.000</td>
                                            <td>34%</td>
                                            <td><span class="badge bg-success-subtle text-success"><?= translate('i18n_baik') ?></span></td>
                                        </tr>
                                        <tr>
                                            <td><?= translate('i18n_pintu_upvc_sliding') ?></td>
                                            <td>210 unit</td>
                                            <td>Rp 1.155.000.000</td>
                                            <td>29%</td>
                                            <td><span class="badge bg-success-subtle text-success"><?= translate('i18n_baik') ?></span></td>
                                        </tr>
                                        <tr>
                                            <td><?= translate('i18n_jendela_kaca_tempered') ?></td>
                                            <td>310 unit</td>
                                            <td>Rp 930.000.000</td>
                                            <td>22%</td>
                                            <td><span class="badge bg-warning-subtle text-warning"><?= translate('i18n_perhatian') ?></span></td>
                                        </tr>
                                        <tr>
                                            <td><?= translate('i18n_pintu_aluminium_swing') ?></td>
                                            <td>180 unit</td>
                                            <td>Rp 720.000.000</td>
                                            <td>31%</td>
                                            <td><span class="badge bg-success-subtle text-success"><?= translate('i18n_baik') ?></span></td>
                                        </tr>
                                        <tr>
                                            <td><?= translate('i18n_curtain_wall_custom_project') ?></td>
                                            <td>4 proyek</td>
                                            <td>Rp 2.100.000.000</td>
                                            <td>18%</td>
                                            <td><span class="badge bg-danger-subtle text-danger"><?= translate('i18n_perlu_review') ?></span></td>
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

        // Revenue vs Net Profit
        const chartRevenue = new Chart(document.getElementById('chartRevenue'), {
            type: 'bar',
            data: {
                labels: ['<?= tj('i18n_jan') ?>', '<?= tj('i18n_feb') ?>', '<?= tj('i18n_mar') ?>', '<?= tj('i18n_apr') ?>', '<?= tj('i18n_mei') ?>', '<?= tj('i18n_jun') ?>', '<?= tj('i18n_jul') ?>', '<?= tj('i18n_agu') ?>'],
                datasets: [{
                        label: '<?= tj('i18n_pendapatan') ?>',
                        data: [1.9, 2.0, 2.1, 2.3, 2.4, 2.5, 2.6, 2.6],
                        backgroundColor: brand,
                        borderRadius: 6,
                        yAxisID: 'y'
                    },
                    {
                        label: '<?= tj('i18n_laba_bersih') ?>',
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
                            text: '<?= tj('i18n_miliar_rp') ?>'
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
                            text: '<?= tj('i18n_laba_miliar') ?>'
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
                labels: ['<?= tj('i18n_jendela_aluminium') ?>', '<?= tj('i18n_pintu_upvc') ?>', '<?= tj('i18n_jendela_kaca_tempered') ?>', '<?= tj('i18n_pintu_aluminium') ?>', '<?= tj('i18n_curtain_wall_proyek') ?>'],
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
                labels: ['<?= tj('i18n_total_aset') ?>', '<?= tj('i18n_total_kewajiban') ?>', '<?= tj('i18n_total_ekuitas_2') ?>'],
                datasets: [{
                    label: '<?= tj('i18n_miliar_rp') ?>',
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
                            text: '<?= tj('i18n_miliar_rupiah') ?>'
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
                labels: ['<?= tj('i18n_mar') ?>', '<?= tj('i18n_apr') ?>', '<?= tj('i18n_mei') ?>', '<?= tj('i18n_jun') ?>', '<?= tj('i18n_jul') ?>', '<?= tj('i18n_agu') ?>'],
                datasets: [{
                    label: '<?= tj('i18n_arus_kas_operasional') ?>',
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
                            text: '<?= tj('i18n_miliar_rp') ?>'
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