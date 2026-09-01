<!DOCTYPE html>
<html lang="<?= $this->config->item('current_lang') ?: 'id'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - Dashboard' : 'Dashboard'; ?></title>
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

        .sidebar .brand,
        .sidebar .brand a,
        .sidebar .brand a:hover,
        .sidebar .brand a:visited,
        .sidebar .brand a:active {
            color: #fff !important;
            text-decoration: none !important;
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
            align-items: center;
            justify-content: flex-start;
            gap: 8px;
        }

        .sidebar .nav-sub-link i {
            width: 16px;
            text-align: center;
            flex-shrink: 0;
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

        /* ===== Komponen umum yang dipakai di semua halaman konten ===== */
        .card-custom {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #edf0f1;
            box-shadow: 0 2px 10px rgba(15, 42, 41, .04);
        }

        .btn-brand {
            background: var(--brand);
            color: #fff;
            border: none;
        }

        .btn-brand:hover {
            background: var(--brand-dark);
            color: #fff;
        }

        /* ===== KPI Cards (dipakai di dashboard) ===== */
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
    </style>
</head>

<body>

    <!-- Hidden input CSRF token global, dipakai & di-refresh oleh semua request AJAX di semua halaman -->
    <input type="hidden" id="csrf_token_name" value="<?= $this->security->get_csrf_token_name(); ?>">
    <input type="hidden" id="csrf_token_hash" value="<?= $this->security->get_csrf_hash(); ?>">

    <!-- jQuery & Bootstrap dimuat di awal supaya "$" sudah siap dipakai oleh script di halaman manapun -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>

    <?php
    /**
     * ==========================================================================
     *  KONFIGURASI MENU BERBASIS ROLE
     * ==========================================================================
     * Setiap item menu punya daftar role yang boleh melihatnya di 'roles'.
     * Tambah/ubah menu cukup di array ini saja — tidak perlu edit HTML di bawah,
     * dan tidak perlu bikin file header terpisah per role.
     *
     * Sesuaikan nama role di 'roles' dengan nilai yang disimpan di session
     * (lihat MY_Controller::requireRole(), mis. 'super_admin', 'staff_purchasing').
     */
    $menu_groups = [

        [
            'key'   => 'master',
            'label' => function_exists('translate') ? translate('menu_master') : 'Master Data',
            'icon'  => 'bi-database-fill-gear',
            'roles' => ['super_admin'], // hanya super_admin yang lihat grup Master Data
            'items' => [
                [
                    'key'   => 'user',
                    'label' => function_exists('translate') ? translate('menu_user') : 'User',
                    'icon'  => 'bi-people',
                    'url'   => 'user',
                    'roles' => ['super_admin'],
                ],
                [
                    'key'   => 'supplier',
                    'label' => function_exists('translate') ? translate('menu_supplier') : 'Supplier',
                    'icon'  => 'bi-people',
                    'url'   => 'supplier',
                    'roles' => ['super_admin', 'staff_purchasing'],
                ],
                [
                    'key'   => 'customer',
                    'label' => function_exists('translate') ? translate('menu_customer') : 'Customer',
                    'icon'  => 'bi-people',
                    'url'   => 'customer',
                    'roles' => ['super_admin'],
                ],
                [
                    'key'   => 'kategori_barang',
                    'label' => function_exists('translate') ? translate('kategori_barang') : 'Kategori Barang',
                    'icon'  => 'bi-list-check',
                    'url'   => 'kategori_barang',
                    'roles' => ['super_admin'],
                ],
                [
                    'key'   => 'barang',
                    'label' => function_exists('translate') ? translate('nama_barang') : 'Barang',
                    'icon'  => 'bi-list-check',
                    'url'   => 'barang',
                    'roles' => ['super_admin'],
                ],
                [
                    'key'   => 'locations',
                    'label' => function_exists('translate') ? translate('app_rak') : 'Lokasi Rak',
                    'icon'  => 'bi bi-archive',
                    'url'   => 'locations',
                    'roles' => ['super_admin'],
                ],
            ],
        ],
        [
            'key'   => 'purchasing',
            'label' => function_exists('translate') ? translate('purchasing') : 'Purchasing',
            'icon'  => 'bi-receipt-cutoff',
            'roles' => ['super_admin', 'staff_purchasing'], // yang boleh lihat grup ini
            'items' => [
                [
                    'key'   => 'purchase_order',
                    'label' => function_exists('translate') ? translate('app_purchasing') : 'Pesanan Pembelian',
                    'icon'  => 'bi-box',
                    'url'   => 'purchase_order',
                    'roles' => ['super_admin', 'staff_purchasing'],
                ],
                [
                    'key'   => 'supplier',
                    'label' => function_exists('translate') ? translate('menu_supplier') : 'Supplier',
                    'icon'  => 'bi-people',
                    'url'   => 'supplier',
                    'roles' => ['super_admin', 'staff_purchasing'],
                ],
            ],
        ],
    ];

    // Role user yang sedang login. Sesuaikan key session-nya jika berbeda di project ini.
    $current_role = $this->session->userdata('role');

    // $active_menu dikirim dari controller lewat $data['active_menu']
    $active_menu = isset($active_menu) ? $active_menu : '';

    function render_navigation($menu_id, $menu_groups, $current_role, $active_menu = '')
    {
        $is_first = true;
    ?>
        <ul class="nav flex-column mb-auto" id="<?= $menu_id; ?>">
            <?php foreach ($menu_groups as $group):
                // Lewati grup ini kalau role user tidak ada di daftar 'roles' grup
                if (!in_array($current_role, $group['roles'], true)) {
                    continue;
                }

                // Saring item di dalam grup sesuai role juga (jaga-jaga jika berbeda level)
                $visible_items = array_filter($group['items'], function ($item) use ($current_role) {
                    return in_array($current_role, $item['roles'], true);
                });
                if (empty($visible_items)) {
                    continue;
                }

                $group_dom_id  = 'submenu_' . $group['key'] . '_' . $menu_id;
                $group_is_open = $is_first; // grup pertama yang muncul dibuka otomatis
                $is_first      = false;
            ?>
                <li class="nav-item">
                    <a class="nav-link <?= $group_is_open ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" href="#<?= $group_dom_id; ?>" role="button" aria-expanded="<?= $group_is_open ? 'true' : 'false'; ?>" aria-controls="<?= $group_dom_id; ?>">
                        <div><i class="bi <?= $group['icon']; ?> main-icon"></i> <?= $group['label']; ?></div>
                        <i class="bi bi-chevron-right arrow-icon"></i>
                    </a>
                    <div class="collapse <?= $group_is_open ? 'show' : ''; ?>" id="<?= $group_dom_id; ?>" data-bs-parent="#<?= $menu_id; ?>">
                        <ul class="sub-menu">
                            <?php foreach ($visible_items as $item): ?>
                                <li>
                                    <a class="nav-sub-link <?= $active_menu === $item['key'] ? 'active' : ''; ?>" href="<?= site_url($item['url']); ?>">
                                        <i class="bi <?= $item['icon']; ?>"></i> <?= $item['label']; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php
    }
    ?>

    <!-- Mobile Offcanvas Sidebar -->
    <div class="offcanvas offcanvas-start sidebar text-bg-dark" tabindex="-1" id="mobileSidebar">
        <div class="d-flex justify-content-between align-items-center px-3 pt-3">
            <div class="brand mb-0 pb-0 border-0">
                <a href="<?= site_url('dashboard'); ?>">PT Oupai Pintu<br>Jendela Indonesia</a>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <hr class="text-white-50 mx-3">
        <?php render_navigation('menuMobile', $menu_groups, $current_role, $active_menu); ?>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Desktop Sidebar -->
            <nav class="col-lg-2 sidebar d-none d-lg-flex flex-column p-0">
                <div class="brand">
                    <a href="<?= site_url('dashboard'); ?>">PT Oupai Pintu<br>Jendela Indonesia</a>
                </div>
                <?php render_navigation('menuDesktop', $menu_groups, $current_role, $active_menu); ?>
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
                            <h5 class="mb-0"><?= isset($page_title) ? $page_title : 'Dashboard'; ?></h5>
                            <?php if (isset($page_subtitle)): ?>
                                <small class="text-muted d-none d-sm-block"><?= $page_subtitle; ?></small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
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
                                    <a href="#" class="profile-dropdown-item"><i class="bi bi-person"></i> <?= function_exists('translate') ? translate('profile') : 'Profil'; ?></a>
                                    <a href="<?= site_url('login/logout'); ?>" class="profile-dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> <?= function_exists('translate') ? translate('app_logout') : 'Keluar'; ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============ Konten halaman (view spesifik) dimulai di sini ============ -->
                <div class="p-3 p-lg-4">