<!-- View ini HANYA berisi konten halaman dashboard.
     Sidebar, topbar, dan menu role sudah diambil dari templates/header.php
     lewat controller (lihat Dashboard.php), jadi tidak perlu ditulis ulang di sini. -->

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