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
        overflow: visible;
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

    .so-alert-success {
        background: #e3f9ee;
        color: #14683a;
    }

    .so-alert-danger {
        background: #fdecec;
        color: #9c2b2b;
    }

    /* ===== Pencarian barang (gaya penawaran) ===== */
    .so-picker {
        position: relative;
    }

    .so-picker .so-search.is-locked {
        background: #eaf0ff;
        color: #224abe;
        font-weight: 600;
        cursor: not-allowed;
    }

    .so-picker .so-search.is-invalid-pick {
        border-color: #e74a3b;
        box-shadow: 0 0 0 0.15rem rgba(231, 74, 59, 0.15);
    }

    .so-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 50;
        background: #fff;
        border: 1px solid #dfe3ee;
        border-radius: 8px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, .12);
        max-height: 240px;
        overflow-y: auto;
        margin-top: 4px;
    }

    .so-dropdown .so-opt {
        padding: 8px 12px;
        cursor: pointer;
        font-size: .85rem;
        border-bottom: 1px solid #f2f4f8;
    }

    .so-dropdown .so-opt:last-child {
        border-bottom: none;
    }

    .so-dropdown .so-opt:hover,
    .so-dropdown .so-opt.active {
        background: #eef2ff;
    }

    .so-dropdown .so-opt .so-nama {
        font-weight: 600;
        color: #26332c;
        display: block;
    }

    .so-dropdown .so-opt .so-meta {
        color: #7a8a80;
        font-size: .76rem;
    }

    .so-dropdown .so-empty {
        padding: 8px 12px;
        font-size: .82rem;
        color: #9aa39d;
    }

    .so-ganti {
        font-size: .8rem;
        text-decoration: none;
    }

    .so-info {
        font-size: .78rem;
        color: #7a8a80;
        margin-top: .3rem;
    }
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
                <p><?= translate('lbl_stok_opname') ?></p>
            </div>

            <div class="card-body">
                <?= form_open('stok_opname/simpan', ['id' => 'formOpname']); ?>

                <!-- Data barang untuk pencarian (client side) -->
                <script id="dataBarangOpname" type="application/json">
                    <?= json_encode(array_map(function ($b) {
                        return [
                            'id'          => (int) $b['id'],
                            'kode_barang' => (string) ($b['kode_barang'] ?? ''),
                            'nama'        => (string) ($b['nama'] ?? ''),
                            'warna'       => (string) ($b['warna'] ?? ''),
                            'keterangan'  => (string) ($b['keterangan'] ?? ''),
                        ];
                    }, (array) $list_barang), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>
                </script>

                <div class="so-form-group">
                    <label class="so-label"><i class="fas fa-box"></i><?= translate('pilih_barang') ?> <span class="text-danger">*</span></label>
                    <div class="so-picker">
                        <input type="hidden" name="id_barang" id="soIdBarang" value="">
                        <input type="text" id="soSearch" class="form-control so-control so-search"
                            placeholder="<?= translate('p_stok_nb') ?>" autocomplete="off" required>
                        <div class="so-dropdown d-none" id="soDropdown"></div>
                    </div>
                    <a href="#" class="so-ganti d-none" id="soGanti"><i class="fas fa-sync-alt mr-1"></i><?= translate('ganti_barang') ?></a>
                    <div class="so-info d-none" id="soInfo"></div>
                </div>

                <div class="so-form-group">
                    <label class="so-label"><i class="fas fa-warehouse"></i><?= translate('pilih_lokasi') ?> <span class="text-danger">*</span></label>
                    <select name="id_location" class="form-control so-control" required>
                        <option value="">-- <?= translate('pilih_lokasi') ?> --</option>
                        <?php foreach ($list_location as $l): ?>
                            <option value="<?= $l['id']; ?>"><?= $l['zone_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="so-form-group">
                    <label class="so-label"><i class="fas fa-exchange-alt"></i><?= translate('jenis_input') ?><span class="text-danger">*</span></label>
                    <select name="mode" id="soMode" class="form-control so-control" required>
                        <option value="tambah" selected><?= translate('opsi_input1') ?></option>
                        <option value="opname"><?= translate('opsi_input2') ?></option>
                    </select>
                    <div class="so-info" id="soModeInfo"></div>
                </div>
                <div class="so-form-group">
                    <label class="so-label"><i class="fas fa-tags"></i><?= translate('opsi') ?> <span class="text-danger">*</span></label>
                    <select name="opsi" id="soOpsi" class="form-control so-control" required>
                        <option value="">-- <?= translate('opsi') ?>--</option>
                        <option value="bukaan_kanan"><?= translate('b_kanan') ?></option>
                        <option value="bukaan_kiri"><?= translate('b_kiri') ?></option>
                        <option value="aksesoris"><?= translate('aksesoris') ?></option>
                        <option value="bahan"><?= translate('bahan') ?></option>
                    </select>
                    <div class="so-info"><?= translate('lbl_opsi') ?></div>
                </div>

                <div class="so-form-group">
                    <label class="so-label"><i class="fas fa-sort-numeric-up"></i><span id="soQtyLabel"><?= translate('jml_stok') ?></span> <span class="text-danger">*</span></label>
                    <input type="number" min="0" name="qty_fisik" id="soQty" class="form-control so-control" placeholder="Masukkan jumlah" required>
                </div>

                <div class="so-form-group">
                    <label class="so-label"><i class="fas fa-ruler-combined"></i><?= translate('ukuran') ?></label>
                    <textarea name="ukuran" class="form-control so-control" rows="3" placeholder="<?= translate('p_uk') ?>"></textarea>
                </div>

                <div class="so-form-group">
                    <label class="so-label"><i class="fas fa-comment-dots"></i><?= translate('keterangan') ?></label>
                    <textarea name="keterangan" class="form-control so-control" rows="3" placeholder="<?= translate('p_ket_so') ?>"></textarea>
                </div>

                <button type="submit" class="so-btn">
                    <i class="fas fa-save mr-1"></i> <?= translate('button_save') ?>
                </button>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        var DATA = [];
        try {
            DATA = JSON.parse(document.getElementById('dataBarangOpname').textContent || '[]') || [];
        } catch (e) {
            DATA = [];
        }

        var form = document.getElementById('formOpname');
        var inpCari = document.getElementById('soSearch');
        var inpId = document.getElementById('soIdBarang');
        var dropdown = document.getElementById('soDropdown');
        var btnGanti = document.getElementById('soGanti');
        var info = document.getElementById('soInfo');
        var aktif = -1;

        function metaText(b) {
            var bagian = [];
            if (b.kode_barang) bagian.push(b.kode_barang);
            if (b.warna) bagian.push(b.warna);
            if (b.keterangan) bagian.push(b.keterangan);
            return bagian.join(' \u2022 ');
        }

        function cari(kata) {
            kata = (kata || '').trim().toLowerCase();
            if (kata === '') return DATA.slice(0, 15);
            return DATA.filter(function(b) {
                return b.nama.toLowerCase().indexOf(kata) !== -1 ||
                    b.kode_barang.toLowerCase().indexOf(kata) !== -1;
            }).slice(0, 15);
        }

        function tutup() {
            dropdown.classList.add('d-none');
            dropdown.innerHTML = '';
            aktif = -1;
        }

        function tampilkan(hasil) {
            dropdown.innerHTML = '';
            aktif = -1;
            if (!hasil.length) {
                var kosong = document.createElement('div');
                kosong.className = 'so-empty';
                kosong.textContent = 'Barang tidak ditemukan.';
                dropdown.appendChild(kosong);
                dropdown.classList.remove('d-none');
                return;
            }
            hasil.forEach(function(b) {
                var opt = document.createElement('div');
                opt.className = 'so-opt';
                opt.setAttribute('data-id', b.id);

                var nama = document.createElement('span');
                nama.className = 'so-nama';
                nama.textContent = b.nama;

                var meta = document.createElement('span');
                meta.className = 'so-meta';
                meta.textContent = metaText(b);

                opt.appendChild(nama);
                opt.appendChild(meta);
                dropdown.appendChild(opt);
            });
            dropdown.classList.remove('d-none');
        }

        function pilih(id) {
            var b = DATA.find(function(x) {
                return String(x.id) === String(id);
            });
            if (!b) return;

            inpId.value = b.id;
            inpCari.value = b.nama;
            inpCari.readOnly = true;
            inpCari.classList.add('is-locked');
            inpCari.classList.remove('is-invalid-pick');
            btnGanti.classList.remove('d-none');

            var meta = metaText(b);
            info.textContent = meta;
            info.classList.toggle('d-none', meta === '');
            tutup();
        }

        function ganti() {
            inpId.value = '';
            inpCari.value = '';
            inpCari.readOnly = false;
            inpCari.classList.remove('is-locked');
            btnGanti.classList.add('d-none');
            info.classList.add('d-none');
            inpCari.focus();
        }

        function geserAktif(arah) {
            var opts = dropdown.querySelectorAll('.so-opt');
            if (!opts.length) return;
            if (aktif >= 0) opts[aktif].classList.remove('active');
            aktif = (aktif + arah + opts.length) % opts.length;
            opts[aktif].classList.add('active');
            opts[aktif].scrollIntoView({
                block: 'nearest'
            });
        }

        inpCari.addEventListener('input', function() {
            tampilkan(cari(inpCari.value));
        });

        inpCari.addEventListener('focus', function() {
            if (!inpCari.readOnly) tampilkan(cari(inpCari.value));
        });

        inpCari.addEventListener('keydown', function(e) {
            if (inpCari.readOnly) return;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                geserAktif(1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                geserAktif(-1);
            } else if (e.key === 'Enter') {
                var opts = dropdown.querySelectorAll('.so-opt');
                if (!dropdown.classList.contains('d-none') && opts.length) {
                    e.preventDefault();
                    pilih(opts[aktif >= 0 ? aktif : 0].getAttribute('data-id'));
                }
            } else if (e.key === 'Escape') {
                tutup();
            }
        });

        dropdown.addEventListener('mousedown', function(e) {
            // mousedown supaya terpilih sebelum input kehilangan fokus
            var opt = e.target.closest('.so-opt');
            if (opt) {
                e.preventDefault();
                pilih(opt.getAttribute('data-id'));
            }
        });

        btnGanti.addEventListener('click', function(e) {
            e.preventDefault();
            ganti();
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.so-picker')) tutup();
        });

        // Label & petunjuk sesuai jenis input
        
        var selMode = document.getElementById('soMode');
        var lblQty = document.getElementById('soQtyLabel');
        var infoMode = document.getElementById('soModeInfo');
        var inpQty = document.getElementById('soQty');

        function updateMode() {
            if (selMode.value === 'opname') {
                lblQty.textContent = '<?= translate("jml_opname") ?>'; // Ubah sesuai key translate kamu
                inpQty.min = 0;
                inpQty.placeholder = '<?= translate("p_jml_fisik") ?>'; // Contoh key translate placeholder
                infoMode.textContent = '<?= translate("info_opname") ?>'; // Contoh key translate info
            } else {
                lblQty.textContent = '<?= translate("jml_stok") ?>';
                inpQty.min = 1;
                inpQty.placeholder = '<?= translate("p_jml_tambah") ?>'; // Contoh key translate placeholder
                infoMode.textContent = '<?= translate("info_tambah") ?>'; // Contoh key translate info
            }
        }
        selMode.addEventListener('change', updateMode);
        updateMode();

        // Pastikan barang benar-benar dipilih dari daftar (bukan hanya diketik)
        form.addEventListener('submit', function(e) {
            if (!inpId.value) {
                e.preventDefault();
                inpCari.classList.add('is-invalid-pick');
                inpCari.focus();
                tampilkan(cari(inpCari.value));
            }
        });
    })();
</script>