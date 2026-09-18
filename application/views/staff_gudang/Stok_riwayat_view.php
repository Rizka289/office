<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>

    <!-- Form Filter -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Riwayat Stok</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="<?= site_url('stok_riwayat'); ?>" class="form-inline">
                <div class="form-group mr-3 mb-2">
                    <label class="mr-2">Barang:</label>
                    <select name="id_barang" class="form-control">
                        <option value="">-- Semua Barang --</option>
                        <?php foreach ($list_barang as $b): ?>
                            <option value="<?= $b['id']; ?>" <?= ($filter_barang == $b['id']) ? 'selected' : ''; ?>>
                                <?= $b['kode_barang'] . ' - ' . $b['nama']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group mr-3 mb-2">
                    <label class="mr-2">Lokasi/Gudang:</label>
                    <select name="id_location" class="form-control">
                        <option value="">-- Semua Lokasi --</option>
                        <?php foreach ($list_location as $l): ?>
                            <option value="<?= $l['id']; ?>" <?= ($filter_location == $l['id']) ? 'selected' : ''; ?>>
                                <?= $l['zone_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary mb-2 mr-2">Filter</button>
                <a href="<?= site_url('stok_riwayat'); ?>" class="btn btn-secondary mb-2">Reset</a>
            </form>
        </div>
    </div>

    <!-- Tabel Data Riwayat -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Barang</th>
                            <th>Lokasi</th>
                            <th>Jenis Transaksi</th>
                            <th>Masuk</th>
                            <th>Keluar</th>
                            <th>Stok Sebelum</th>
                            <th>Stok Sesudah</th>
                            <th>Keterangan</th>
                            <th>Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($riwayat)): ?>
                            <?php foreach ($riwayat as $row): ?>
                                <tr>
                                    <td><?= date('d-m-Y H:i', strtotime($row['created_at'])); ?></td>
                                    <td><?= htmlspecialchars($row['nama'] ?? $row['id_barang']); ?></td>
                                    <td><?= htmlspecialchars(($row['location_code'] ?? '') . ' - ' . ($row['zone_name'] ?? $row['id_location'])); ?></td>
                                    <td><?= $row['jenis_transaksi']; ?></span></td>
                                    <td class="text-success font-weight-bold"><?= $row['qty_masuk'] > 0 ? '+' . $row['qty_masuk'] : '-'; ?></td>
                                    <td class="text-danger font-weight-bold"><?= $row['qty_keluar'] > 0 ? '-' . $row['qty_keluar'] : '-'; ?></td>
                                    <td><?= $row['stok_sebelum']; ?></td>
                                    <td><strong><?= $row['stok_sesudah']; ?></strong></td>
                                    <td><?= htmlspecialchars($row['keterangan']); ?></td>
                                    <td><?= htmlspecialchars($row['username'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center">Belum ada data riwayat stok.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>