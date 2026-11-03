<!-- 
  View ini HANYA berisi konten halaman dashboard.
  Sidebar, topbar, dan menu role sudah diambil dari templates/header.php
  lewat controller (lihat Dashboard.php), jadi tidak perlu ditulis ulang di sini. 
-->

<!-- 1. ROW KODE KPI CARDS -->
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

<!-- 2. ROW NOTIFIKASI PENDING APPROVALS -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3 border-start border-warning border-4">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                        <i class="bi bi-bell-fill fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-1">Persetujuan Tertunda (Pending Approvals)</h6>
                        <p class="text-muted small mb-0">Ada <span class="badge bg-danger">5 dokumen</span> (Sales Order / Purchase) yang memerlukan persetujuan Anda.</p>
                    </div>
                </div>
                <div>
                    <button type="button" class="btn btn-warning text-dark fw-semibold btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#approvalModal">
                        <i class="bi bi-check2-square me-1"></i> Lihat & Setujui
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 3. ROW GRAFIK PERFORMA BISNIS & FILTER BULAN -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="m-0 fw-bold text-dark">
                    <i class="bi bi-graph-up-arrow me-2 text-primary"></i>Grafik Performa Bisnis
                </h5>
                <!-- Filter Bulan -->
                <div class="d-flex align-items-center gap-2">
                    <label for="monthFilter" class="form-label mb-0 text-muted small fw-semibold">Pilih Bulan:</label>
                    <select id="monthFilter" class="form-select form-select-sm" style="width: 160px;" onchange="updateChartData()">
                        <option value="all">Semua Bulan (2026)</option>
                        <option value="0">Januari</option>
                        <option value="1">Februari</option>
                        <option value="2">Maret</option>
                        <option value="3">April</option>
                        <option value="4">Mei</option>
                        <option value="5">Juni</option>
                        <option value="6">Juli</option>
                        <option value="7">Agustus</option>
                        <option value="8">September</option>
                        <option value="9">Oktober</option>
                        <option value="10">November</option>
                        <option value="11">Desember</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <!-- Wrapper dengan tinggi mutlak agar canvas ter-render sempurna -->
                <div style="position: relative; height: 350px; width: 100%;">
                    <canvas id="businessChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 4. MODAL POP-UP DAFTAR APPROVAL -->
<div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="approvalModalLabel">
                    <i class="bi bi-clipboard-check me-2 text-primary"></i>Daftar Pending Approval
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No. Dokumen</th>
                                <th>Tipe</th>
                                <th>Pemohon</th>
                                <th>Total Nilai</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-semibold">SO-2026-089</td>
                                <td><span class="badge bg-primary">Penjualan</span></td>
                                <td>Budi (Sales)</td>
                                <td>Rp 45.000.000</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-success px-2 py-1 me-1" title="Setujui"><i class="bi bi-check-lg"></i></button>
                                    <button class="btn btn-sm btn-danger px-2 py-1" title="Tolak"><i class="bi bi-x-lg"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">PO-2026-042</td>
                                <td><span class="badge bg-danger">Pembelian</span></td>
                                <td>Siti (Purchasing)</td>
                                <td>Rp 120.000.000</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-success px-2 py-1 me-1" title="Setujui"><i class="bi bi-check-lg"></i></button>
                                    <button class="btn btn-sm btn-danger px-2 py-1" title="Tolak"><i class="bi bi-x-lg"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- 5. INISIALISASI SCRIPT GRAFIK DENGAN SAFETY LOADER -->
<script>
    // Data dummy 12 bulan (Nominal dalam juta / Miliar Rp)
    const monthlyData = {
        months: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'],
        penjualan: [1200, 1500, 1800, 1400, 1600, 2100, 1900, 2300, 2000, 2500, 2200, 2800],
        penawaran: [1500, 1900, 2200, 1800, 2000, 2500, 2300, 2700, 2400, 3000, 2600, 3200],
        pembelian: [800,  950,  1100,  900, 1000, 1300, 1200, 1400, 1250, 1500, 1350, 1600]
    };

    let businessChart;

    // Fungsi otomatis untuk memuat Chart.js jika belum tersedia di halaman
    function loadChartJsAndInit() {
        if (typeof Chart === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = initDashboardChart;
            document.head.appendChild(script);
        } else {
            initDashboardChart();
        }
    }

    function initDashboardChart() {
        const canvasElement = document.getElementById('businessChart');
        if (!canvasElement) return;

        const ctx = canvasElement.getContext('2d');

        businessChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyData.months,
                datasets: [
                    {
                        label: 'Penjualan (Sales)',
                        data: monthlyData.penjualan,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Penawaran (Quotation)',
                        data: monthlyData.penawaran,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Pembelian (Purchase)',
                        data: monthlyData.pembelian,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let value = context.raw || 0;
                                return context.dataset.label + ': Rp ' + value.toLocaleString('id-ID') + ' Juta';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value + ' Jt';
                            }
                        }
                    }
                }
            }
        });
    }

    // Jalankan fungsi saat DOM siap
    document.addEventListener("DOMContentLoaded", loadChartJsAndInit);

    // Fungsi untuk memperbarui chart saat dropdown bulan diubah
    function updateChartData() {
        if (!businessChart) return;
        
        const selectedValue = document.getElementById('monthFilter').value;

        if (selectedValue === 'all') {
            businessChart.data.labels = monthlyData.months;
            businessChart.data.datasets[0].data = monthlyData.penjualan;
            businessChart.data.datasets[1].data = monthlyData.penawaran;
            businessChart.data.datasets[2].data = monthlyData.pembelian;
        } else {
            const monthIdx = parseInt(selectedValue);
            const monthName = monthlyData.months[monthIdx];
            
            businessChart.data.labels = [`Minggu 1 (${monthName})`, `Minggu 2 (${monthName})`, `Minggu 3 (${monthName})`, `Minggu 4 (${monthName})`];
            
            const salesVal = monthlyData.penjualan[monthIdx];
            const quoteVal = monthlyData.penawaran[monthIdx];
            const purchVal = monthlyData.pembelian[monthIdx];

            businessChart.data.datasets[0].data = [salesVal * 0.2, salesVal * 0.25, salesVal * 0.3, salesVal * 0.25];
            businessChart.data.datasets[1].data = [quoteVal * 0.22, quoteVal * 0.28, quoteVal * 0.25, quoteVal * 0.25];
            businessChart.data.datasets[2].data = [purchVal * 0.18, purchVal * 0.22, purchVal * 0.35, purchVal * 0.25];
        }

        businessChart.update();
    }
</script>