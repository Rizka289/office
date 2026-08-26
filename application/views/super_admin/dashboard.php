<!DOCTYPE html>
<html lang="<?= $this->config->item('current_lang') ?: 'id'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= lang('dash_title'); ?></title>
    <!-- Bootstrap 5 & Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">

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

        /* ===== Sidebar Styling ===== */
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

        .sidebar .nav-link,
        .sidebar .nav-sub-link {
            color: rgba(255, 255, 255, .8);
            padding: .65rem 1.25rem;
            font-size: .92rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, .12);
            color: #fff;
            border-left: 3px solid var(--accent);
        }

        .sidebar .nav-link i.main-icon {
            width: 22px;
            text-align: center;
            margin-right: .5rem;
        }

        /* Sub-bab Accordion Styling */
        .sidebar .sub-menu {
            background: rgba(0, 0, 0, 0.15);
            padding-left: 0;
            list-style: none;
            margin-bottom: 0;
        }

        .sidebar .nav-sub-link {
            padding: .55rem 1.25rem .55rem 2.8rem;
            font-size: .85rem;
            color: rgba(255, 255, 255, .7);
            display: flex;
            /* tambahan */
            align-items: center;
            /* tambahan */
            justify-content: flex-start;
            /* tambahan */
            gap: 8px;
            /* tambahan */
        }

        .sidebar .nav-sub-link:hover,
        .sidebar .nav-sub-link.active {
            color: #fff;
            background: rgba(255, 255, 255, .08);
        }

        .sidebar .arrow-icon {
            font-size: .75rem;
            transition: transform 0.3s ease;
        }

        .sidebar [aria-expanded="true"] .arrow-icon {
            transform: rotate(90deg);
        }

        /* ===== Topbar & Profile Menu ===== */
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
        }

        .profile-menu {
            position: relative;
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

        .profile-dropdown-item:hover {
            background: var(--brand-light);
            color: var(--brand);
        }

        /* ===== KPI Cards ===== */
        .kpi-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #edf0f1;
            padding: 1rem 1.1rem;
            box-shadow: 0 2px 10px rgba(15, 42, 41, .04);
            height: 100%;
        }

        .kpi-value {
            font-size: 1.3rem;
            font-weight: 700;
            margin: .3rem 0 .1rem;
        }

        .kpi-label {
            color: var(--text-muted);
            font-size: .78rem;
        }

      

        .nav-sub-link i {
            width: 16px;
            text-align: center;
            flex-shrink: 0;
        }
    </style>
</head>

<body>

    <!-- Navigasi Dinamis (Bab & Sub-Bab dengan Multi-Bahasa) -->
    <?php function render_navigation($menu_id = 'menuDesktop')
    { ?>
        <ul class="nav flex-column mb-auto" id="<?= $menu_id; ?>">


            <!-- Bab 1: Master Data (Collapsible Accordion) -->
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-toggle="collapse" href="#masterSubmenu_<?= $menu_id; ?>" role="button" aria-expanded="false" aria-controls="masterSubmenu_<?= $menu_id; ?>">
                    <div><i class="bi bi-database-fill-gear main-icon"></i> <?= lang('menu_master'); ?></div>
                    <i class="bi bi-chevron-right arrow-icon"></i>
                </a>
                <div class="collapse" id="masterSubmenu_<?= $menu_id; ?>" data-bs-parent="#<?= $menu_id; ?>">
                    <ul class="sub-menu">
                        <!-- Sub-bab: Supplier -->
                        <li>
                            <a class="nav-sub-link" href="<?= site_url('user'); ?>">
                                <i class="bi bi-truck"></i> <?= lang('menu_user'); ?>
                            </a>
                        </li>
                        <li>
                            <a class="nav-sub-link" href="<?= site_url('supplier'); ?>">
                                <i class="bi bi-truck"></i> <?= lang('menu_supplier'); ?>
                            </a>
                        </li>
                        <!-- Sub-bab: Pelanggan -->
                        <li>
                            <a class="nav-sub-link" href="<?= site_url('pelanggan'); ?>">
                                <i></i> <?= lang('menu_customer'); ?>
                            </a>
                        </li>
                        <!-- Sub-bab: Lokasi Gudang -->
                        <li>
                            <a class="nav-sub-link" href="<?= site_url('gudang'); ?>">
                                <i class="bi bi-dot"></i> <?= lang('menu_warehouse'); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>


        </ul>
    <?php } ?>

    <!-- Mobile Offcanvas Sidebar -->
    <div class="offcanvas offcanvas-start sidebar text-bg-dark" tabindex="-1" id="mobileSidebar">
        <div class="d-flex justify-content-between align-items-center px-3 pt-3">
            <div class="brand mb-0 pb-0 border-0">

                <div>PT Oupai Pintu<br>Jendela Indonesia</div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <hr class="text-white-50 mx-3">
        <?php render_navigation('menuMobile'); ?>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Desktop Sidebar -->
            <nav class="col-lg-2 sidebar d-none d-lg-flex flex-column p-0">
                <div class="brand">

                    <div>PT Oupai Pintu<br>Jendela Indonesia</div>
                </div>
                <?php render_navigation('menuDesktop'); ?>
                <div class="p-3 small text-white-50 border-top border-white border-opacity-25">
                    &copy; 2026 PT Oupai Pintu Jendela Indonesia
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-12 col-lg-10 px-0">
                <!-- Topbar -->
                <div class="topbar d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn-hamburger d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                            <i class="bi bi-list fs-4"></i>
                        </button>
                        <div>
                            <h5 class="mb-0"><?= lang('dash_title'); ?></h5>
                            <small class="text-muted d-none d-sm-block"><?= lang('dash_subtitle'); ?></small>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <!-- Profile User -->
                        <div class="profile-menu" tabindex="0">
                            <div class="profile-trigger d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-secondary bg-opacity-25 d-flex align-items-center justify-content-center" style="width:34px;height:34px;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div class="d-none d-md-block text-end lh-sm">
                                    <div class="fw-semibold" style="font-size:.85rem;"><?= $this->session->userdata('nama') ?: 'User'; ?></div>
                                    <div class="text-muted" style="font-size:.72rem;"><?= $this->session->userdata('username'); ?></div>
                                </div>
                            </div>
                            <div class="profile-dropdown">
                                <div class="profile-dropdown-inner">
                                    <a href="#" class="profile-dropdown-item"><i class="bi bi-person"></i> <?= lang('profile'); ?></a>
                                    <a href="<?= site_url('login/logout'); ?>" class="profile-dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> <?= lang('logout'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Content / KPI Cards -->
                <div class="p-3 p-lg-4">
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-lg-3">
                            <div class="kpi-card">
                                <div class="kpi-value">Rp 18,4 M</div>
                                <div class="kpi-label"><?= lang('kpi_revenue'); ?></div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="kpi-card">
                                <div class="kpi-value">Rp 3,26 M</div>
                                <div class="kpi-label"><?= lang('kpi_net_profit'); ?></div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="kpi-card">
                                <div class="kpi-value">Rp 22,9 M</div>
                                <div class="kpi-label"><?= lang('kpi_total_assets'); ?></div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="kpi-card">
                                <div class="kpi-value">Rp 9,1 M</div>
                                <div class="kpi-label"><?= lang('kpi_liabilities'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>

</html>