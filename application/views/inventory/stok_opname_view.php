<style>
    .so-wrapper {
        max-width: 640px;
        margin: 0 auto;
    }
    .so-header {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        border-radius: 14px 14px 0 0;
        padding: 1.75rem 2rem;
        color: #fff;
    }
    .so-header h1 {
        font-size: 1.35rem;
        font-weight: 700;
        margin: 0;
        color: #fff;
    }
    .so-header p {
        margin: 0.25rem 0 0;
        font-size: 0.85rem;
        opacity: 0.85;
    }
    .so-card {
        border: none;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 0.5rem 1.5rem rgba(58, 59, 69, 0.12);
    }
    .so-card .card-body {
        padding: 2rem;
    }
    .so-label {
        font-weight: 600;
        font-size: 0.85rem;
        color: #5a5c69;
        margin-bottom: 0.4rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .so-label i {
        color: #4e73df;
        font-size: 0.95rem;
    }
    .so-control {
        border-radius: 8px;
        border: 1.5px solid #dcdfe6;
        padding: 0.65rem 0.9rem;
        font-size: 0.95rem;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .so-control:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.15rem rgba(78, 115, 223, 0.15);
    }
    .so-form-group {
        margin-bottom: 1.4rem;
    }
    .so-btn {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        border: none;
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        font-size: 0.95rem;
        color: #fff;
        width: 100%;
        transition: transform 0.12s ease, box-shadow 0.12s ease;
    }
    .so-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 0.4rem 1rem rgba(78, 115, 223, 0.35);
        color: #fff;
    }
    .so-alert {
        border-radius: 10px;
        border: none;
        font-size: 0.9rem;
        padding: 0.9rem 1.1rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }
    .so-alert-success { background: #e3f9ee; color: #14683a; }
    .so-alert-danger  { background: #fdecec; color: #9c2b2b; }
</style>

<div class="container-fluid">
    <div class="so-wrapper">

        <?php if ($this->session->flashdata('success')): ?>
            <div class="so-alert so-alert-success mb-3">
                <i class="fas fa-check-circle"></i>
                <span><?= $this->session->flashdata('success'); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($this->session->flashdata('error')): ?>
            <div class="so-alert so-alert-danger mb-3">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= $this->session->flashdata('error'); ?></span>
            </div>
        <?php endif; ?>

        <div class="card so-card">
            <div class="so-header">
                <h1><i class="fas fa-clipboard-list mr-2"></i><?= $title; ?></h1>
                <p>Catat hasil pengecekan fisik stok barang di gudang</p>
            </div>

            <div class="card-body">
                <?= form_open('stok_opname/simpan'); ?>

                <div class="so-form-group">
                    <label class="so-label"><i class="fas fa-box"></i>Pilih Barang <span class="text-danger">*</span></label>
                    <select name="id_barang" class="form-control so-control" required>
                        <option value="">-- Pilih Barang --</option>
                        <?php foreach ($list_barang as $b): ?>
                            <option value="<?= $b['id']; ?>"><?= $b['kode_barang'] . ' - ' . $b['nama']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="so-form-group">
                    <label class="so-label"><i class="fas fa-warehouse"></i>Pilih Lokasi/Gudang <span class="text-danger">*</span></label>
                    <select name="id_location" class="form-control so-control" required>
                        <option value="">-- Pilih Lokasi --</option>
                        <?php foreach ($list_location as $l): ?>
                            <option value="<?= $l['id']; ?>"><?= $l['zone_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="so-form-group">
                    <label class="so-label"><i class="fas fa-sort-numeric-up"></i>Jumlah Fisik Real (Qty Fisik) <span class="text-danger">*</span></label>
                    <input type="number" min="0" name="qty_fisik" class="form-control so-control" placeholder="Masukkan jumlah fisik barang saat ini" required>
                </div>

                <div class="so-form-group">
                    <label class="so-label"><i class="fas fa-comment-dots"></i>Keterangan / Alasan Selisih</label>
                    <textarea name="keterangan" class="form-control so-control" rows="3" placeholder="Contoh: Penyesuaian stok awal gudang / Barang rusak 2 pcs"></textarea>
                </div>

                <button type="submit" class="so-btn">
                    <i class="fas fa-save mr-1"></i> Simpan Stok Opname
                </button>

                </form>
            </div>
        </div>
    </div>
</div>