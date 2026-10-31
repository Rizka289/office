<!-- Select2 (dropdown dengan kotak pencarian) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css" rel="stylesheet" />

<style>
    /* Jarak antar field filter dan ke tombol di bawahnya */
    .select2-filter + .select2-container { margin-bottom: .5rem; }
</style>

<div class="container-fluid">
    

    <!-- Form Filter -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= translate('lbl_filter_stok') ?></h6>
        </div>
        <div class="card-body">
            <form method="GET" action="<?= site_url('stok_riwayat'); ?>">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-5">
                        <label for="filter_barang"><?= translate('pilih_barang') ?></label>
                        <select name="id_barang" id="filter_barang" class="form-control select2-filter" style="width:100%">
                            <option value="">-- <?= translate('pilih_barang') ?> --</option>
                            <?php foreach ($list_barang as $b): ?>
                                <option value="<?= $b['id']; ?>" <?= ($filter_barang == $b['id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($b['kode_barang'] . ' - ' . $b['nama'] . (!empty($b['warna']) ? ' (' . $b['warna'] . ')' : '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="filter_location"><?= translate('pilih_lokasi') ?></label>
                        <select name="id_location" id="filter_location" class="form-control select2-filter" style="width:100%">
                            <option value="">-- <?= translate('pilih_lokasi') ?> --</option>
                            <?php foreach ($list_location as $l): ?>
                                <option value="<?= $l['id']; ?>" <?= ($filter_location == $l['id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars(($l['location_code'] ?? '') . ' - ' . ($l['zone_name'] ?? '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group col-md-3 mt-3 mt-md-0">
                        <button type="submit" class="btn btn-primary mr-2"><?= translate('cari') ?></button>
                        <a href="<?= site_url('stok_riwayat'); ?>" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
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
                            <th><?= translate('tanggal') ?></th>
                            <th><?= translate('kode') ?></th>
                            <th><?= translate('nama') ?></th>
                            <th><?= translate('warna') ?></th>
                            <th><?= translate('ukuran') ?></th>
                            <th><?= translate('opsi1') ?></th>
                            <th><?= translate('lokasi_gudang') ?></th>
                            <th><?= translate('jenis_transaksi') ?></th>
                            <th><?= translate('stok_in') ?></th>
                            <th><?= translate('stok_out') ?></th>
                            <th><?= translate('stok_sblm') ?></th>
                            <th><?= translate('stok_stlh') ?></th>
                            <th><?= translate('deskripsi') ?></th>
                            <th><?= translate('petugas') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($riwayat)): ?>
                            <?php foreach ($riwayat as $row): ?>
                                <tr>
                                    <td><?= date('d-m-Y H:i', strtotime($row['created_at'])); ?></td>
                                    <td><?= htmlspecialchars($row['kode_barang'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($row['nama'] ?? $row['id_barang']); ?></td>
                                    <td><?= htmlspecialchars($row['warna'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($row['ukuran'] ?? ''); ?></td>
                                    <td><?= $row['opsi']; ?></td>
                                    <td><?= htmlspecialchars(($row['location_code'] ?? '') . ' - ' . ($row['zone_name'] ?? $row['id_location'])); ?></td>
                                    <td><?= htmlspecialchars($row['jenis_transaksi'] ?? ''); ?></td>
                                    <td class="text-success font-weight-bold"><?= $row['qty_masuk'] > 0 ? '+' . $row['qty_masuk'] : '-'; ?></td>
                                    <td class="text-danger font-weight-bold"><?= $row['qty_keluar'] > 0 ? '-' . $row['qty_keluar'] : '-'; ?></td>
                                    <td><?= $row['stok_sebelum']; ?></td>
                                    <td><strong><?= $row['stok_sesudah']; ?></strong></td>
                                    <td><?= htmlspecialchars($row['keterangan'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($row['username'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="13" class="text-center">Belum ada data riwayat stok.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
// Dijalankan setelah semua script (termasuk jQuery di footer) selesai dimuat
window.addEventListener('load', function () {
    $('.select2-filter').select2({
        theme: 'bootstrap4',
        width: '100%',
        allowClear: true,
        placeholder: function () {
            return $(this).find('option[value=""]').text();
        },
        language: {
            noResults: function () { return 'Tidak ditemukan'; },
            searching: function () { return 'Mencari...'; }
        }
    });

    // Opsional: langsung filter saat pilihan berubah (hapus blok ini kalau tidak mau)
    // $('.select2-filter').on('change', function () {
    //     $(this).closest('form').submit();
    // });
});
</script>