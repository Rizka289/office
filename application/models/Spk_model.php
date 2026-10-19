<?php defined('BASEPATH') or exit('No direct script access allowed');

class Spk_model extends CI_Model
{
    // Generasi Nomor SPK Otomatis (Contoh: 01/SPK-PROD/10/2026)
    public function generate_no($tanggal, $kunci = false)
    {
        $tahun = (int) date('Y', strtotime($tanggal));
        $bulan = date('m', strtotime($tanggal));

        $sql = 'SELECT COALESCE(MAX(no_urut), 0) AS maks FROM spk WHERE tahun = ?'
            . ($kunci ? ' FOR UPDATE' : '');
        $row = $this->db->query($sql, array($tahun))->row();

        $urut = ((int) $row->maks) + 1;
        return array(
            'no_urut' => $urut,
            'tahun'   => $tahun,
            'no_spk'  => sprintf('%02d/SPK-PROD/%s/%d', $urut, $bulan, $tahun),
        );
    }

    // Ambil daftar SO berstatus 'confirmed' yang belum dibuatkan SPK
    public function get_so_siap_spk()
    {
        return $this->db
            ->select('so.id, so.no_so, so.tanggal, c.nama AS nama_customer')
            ->from('sales_order so')
            ->join('customer c', 'c.id = so.id_customer', 'left')
            ->where('so.status', 'confirmed')
            ->where("so.id NOT IN (SELECT id_so FROM spk WHERE status <> 'batal')", null, false)
            ->order_by('so.tanggal', 'DESC')
            ->get()->result();
    }

    // Ambil semua daftar SPK dengan filter
    public function get_all_spk($filter = array())
    {
        $this->db->select('spk.*, so.no_so, c.nama AS nama_customer')
            ->from('spk')
            ->join('sales_order so', 'so.id = spk.id_so', 'left')
            ->join('customer c', 'c.id = so.id_customer', 'left');

        if (!empty($filter['q'])) {
            $this->db->group_start()
                ->like('spk.no_spk', $filter['q'])
                ->or_like('so.no_so', $filter['q'])
                ->or_like('c.nama', $filter['q'])
                ->group_end();
        }
        if (!empty($filter['dari'])) {
            $this->db->where('spk.tanggal_spk >=', $filter['dari']);
        }
        if (!empty($filter['sampai'])) {
            $this->db->where('spk.tanggal_spk <=', $filter['sampai']);
        }

        return $this->db->order_by('spk.tanggal_spk', 'DESC')
            ->order_by('spk.id', 'DESC')
            ->get()->result();
    }

    // Ambil detail SPK lengkap (Header + Detail Item dari SO)
    public function get_spk_lengkap($id)
    {
        $header = $this->db
            ->select('spk.*, so.no_so, so.no_po_customer, c.nama AS nama_customer, c.alamat AS alamat_customer')
            ->from('spk')
            ->join('sales_order so', 'so.id = spk.id_so', 'left')
            ->join('customer c', 'c.id = so.id_customer', 'left')
            ->where('spk.id', (int) $id)
            ->get()->row();

        if (!$header) {
            return null;
        }

        $items = $this->db
            ->select('sd.*, sod.jenis_item, sod.lebar_mm, sod.tinggi_mm, sod.luas_m2, sod.warna, sod.finishing, sod.keterangan')
            ->from('spk_detail sd')
            ->join('sales_order_detail sod', 'sod.id = sd.id_so_detail', 'left')
            ->where('sd.id_spk', (int) $id)
            ->order_by('sd.id', 'ASC')
            ->get()->result();

        // Riwayat bahan baku yang sudah dikeluarkan, dikelompokkan per item SPK
        foreach ($items as $it) {
            $it->material_pakai = array();
        }
        $mats = $this->db
            ->select('m.*, b.nama AS nama_barang, COALESCE((SELECT SUM(r.qty) FROM spk_material_retur r WHERE r.id_material = m.id), 0) AS qty_retur', false)
            ->from('spk_material_usage m')
            ->join('barang b', 'b.id = m.id_barang_bahan', 'left')
            ->where('m.id_spk', (int) $id)
            ->order_by('m.tanggal_keluar', 'ASC')
            ->get()->result();
        foreach ($mats as $m) {
            foreach ($items as $it) {
                if ((int) $it->id === (int) $m->id_spk_detail) {
                    $it->material_pakai[] = $m;
                    break;
                }
            }
        }

        // Tahapan produksi per item custom + persentase progres
        $master = null;
        foreach ($items as $it) {
            $it->tipe = isset($it->tipe) ? $it->tipe : 'custom';
            if ($it->tipe !== 'custom') {
                $it->tahap  = array();
                $it->persen = 100;
                continue;
            }
            $rows = $this->db->where('id_spk_detail', (int) $it->id)->order_by('urutan', 'ASC')->get('spk_detail_tahap')->result();
            if (empty($rows) && $header->status === 'proses') {
                // SPK lama yang belum punya tahapan: dibuatkan otomatis
                if ($master === null) {
                    $master = $this->_master_tahap();
                }
                $this->_isi_tahap($it->id, $master);
                $rows = $this->db->where('id_spk_detail', (int) $it->id)->order_by('urutan', 'ASC')->get('spk_detail_tahap')->result();
            }
            $it->tahap  = $rows;
            $it->persen = $this->hitung_persen($rows);
        }

        // Persen total SPK = rata-rata persen item custom, dibobot qty
        $sum = 0; $q = 0; $ada_custom = false;
        foreach ($items as $it) {
            if ($it->tipe === 'custom') {
                $ada_custom = true;
                $w = max(0, (float) $it->qty_produksi);
                $sum += $it->persen * $w;
                $q   += $w;
            }
        }
        $persen = $ada_custom ? ($q > 0 ? (int) round($sum / $q) : 0) : 100;

        return array('header' => $header, 'items' => $items, 'persen' => $persen, 'ada_custom' => $ada_custom);
    }

    // Simpan SPK Baru dari SO Confirmed
    public function simpan_spk($id_so, $input, $id_user)
    {
        $this->db->trans_begin();

        $so = $this->db->query('SELECT * FROM sales_order WHERE id = ? FOR UPDATE', array((int) $id_so))->row();
        if (!$so || $so->status !== 'confirmed') {
            $this->db->trans_rollback();
            return array(false, 'Sales Order tidak ditemukan atau belum dikonfirmasi.');
        }

        if ($this->db->where('id_so', $so->id)->where('status !=', 'batal')->count_all_results('spk') > 0) {
            $this->db->trans_rollback();
            return array(false, 'Sales Order ini sudah dibuatkan SPK.');
        }

        $no = $this->generate_no($input['tanggal_spk'], true);

        // 1. Insert Header SPK
        $this->db->insert('spk', array(
            'no_spk'           => $no['no_spk'],
            'no_urut'         => $no['no_urut'],
            'tahun'           => $no['tahun'],
            'id_so'            => $so->id,
            'tanggal_spk'      => $input['tanggal_spk'],
            'target_selesai'   => $input['target_selesai'] !== '' ? $input['target_selesai'] : null,
            'status'           => 'proses',
            'catatan_produksi' => $input['catatan_produksi'],
            'id_user'          => $id_user,
        ));
        $id_spk = $this->db->insert_id();

        // 2. Insert Detail SPK dari Sales Order Detail
        $so_items = $this->db->where('id_so', $so->id)->get('sales_order_detail')->result();
        $master_tahap = $this->_master_tahap();
        foreach ($so_items as $item) {
            // Custom = ada ukuran (lebar & tinggi) dari penawaran; selain itu barang jadi dari stok
            $custom = ((float) $item->lebar_mm > 0 && (float) $item->tinggi_mm > 0);
            $this->db->insert('spk_detail', array(
                'id_spk'       => $id_spk,
                'id_so_detail' => $item->id,
                'tipe'         => $custom ? 'custom' : 'jadi',
                'qty_produksi' => $item->qty,
                'qty_selesai'  => $custom ? 0 : $item->qty,
                'status_item'  => $custom ? 'proses' : 'selesai',
            ));
            $id_spk_detail = $this->db->insert_id();
            if ($custom) {
                $this->_isi_tahap($id_spk_detail, $master_tahap);
            }
            $this->_sinkron_so_detail($id_spk_detail);
        }

        // 3. Update Status SO menjadi 'produksi'
        $this->db->where('id', $so->id)->update('sales_order', array('status' => 'produksi'));

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array(false, 'Gagal menyimpan data SPK.');
        }

        $this->db->trans_commit();
        return array(true, $no['no_spk']);
    }

    // Daftar barang + total stok semua lokasi (barang tanpa baris stok dihitung 0)
    public function get_all_stok()
    {
        return $this->db
            ->select('b.id AS id_barang, b.kode_barang, b.nama, b.satuan, b.jenis_barang,
                      COALESCE(SUM(s.stok), 0) AS jumlah', false)
            ->from('barang b')
            ->join('stok_barang s', 's.id_barang = b.id', 'left')
            ->group_by('b.id, b.kode_barang, b.nama, b.satuan, b.jenis_barang')
            ->order_by('b.nama', 'ASC')
            ->get()->result();
    }

    // ===================== MUTASI STOK =====================
    // Aturan: SETIAP perubahan stok dicatat dulu di stok_riwayat (kartu stok),
    // baru saldo stok_barang diperbarui. Wajib dipanggil di dalam transaksi.
    private function _mutasi_stok($id_barang, $id_location, $jenis, $id_ref, $masuk, $keluar, $ket, $id_user)
    {
        $row = $this->db->query(
            'SELECT id, stok FROM stok_barang WHERE id_barang = ? AND id_location = ? FOR UPDATE',
            array((int) $id_barang, (int) $id_location)
        )->row();

        if (!$row) {
            if ($masuk <= 0) {
                return false;
            }
            $this->db->insert('stok_barang', array(
                'id_barang'   => (int) $id_barang,
                'id_location' => (int) $id_location,
                'stok'        => 0,
            ));
            $row = (object) array('id' => $this->db->insert_id(), 'stok' => 0);
        }

        $sebelum = (int) $row->stok;
        $sesudah = $sebelum + (int) $masuk - (int) $keluar;
        if ($sesudah < 0) {
            return false;
        }

        // 1) catat ke stok_riwayat
        $this->db->insert('stok_riwayat', array(
            'id_barang'       => (int) $id_barang,
            'id_location'     => (int) $id_location,
            'jenis_transaksi' => $jenis,
            'id_referensi'    => $id_ref ? (int) $id_ref : null,
            'qty_masuk'       => (int) $masuk,
            'qty_keluar'      => (int) $keluar,
            'stok_sebelum'    => $sebelum,
            'stok_sesudah'    => $sesudah,
            'keterangan'      => $ket,
            'created_by'      => $id_user,
        ));

        // 2) baru perbarui saldo stok_barang
        $this->db->where('id', (int) $row->id)->update('stok_barang', array('stok' => $sesudah));
        return true;
    }

    // Catat pengeluaran bahan baku untuk satu item SPK (riwayat dulu, lalu stok_barang)
    public function catat_pengeluaran_material($id_spk, $data_material, $id_user)
    {
        $id_spk    = (int) $id_spk;
        $id_detail = (int) $data_material['id_spk_detail'];
        $id_barang = (int) $data_material['id_barang_bahan'];
        $qty       = (float) $data_material['qty_pakai'];

        // stok_barang.stok & stok_riwayat.qty_* bertipe INT: qty pecahan ditolak.
        // Hapus pengecekan ini jika kolom-kolom tsb sudah diubah menjadi DECIMAL.
        if (abs($qty - round($qty)) > 0.00001) {
            return array(false, 'Qty harus bilangan bulat (kolom stok bertipe integer).');
        }
        $qty = (int) round($qty);
        if ($qty <= 0) {
            return array(false, 'Qty harus lebih dari 0.');
        }

        $this->db->trans_begin();

        $spk = $this->db->query('SELECT id, no_spk, status FROM spk WHERE id = ? FOR UPDATE', array($id_spk))->row();
        if (!$spk || $spk->status !== 'proses') {
            $this->db->trans_rollback();
            return array(false, 'SPK tidak ditemukan atau sudah tidak dalam status proses.');
        }

        $ada = $this->db->where('id', $id_detail)->where('id_spk', $id_spk)->count_all_results('spk_detail');
        if ($ada < 1) {
            $this->db->trans_rollback();
            return array(false, 'Item SPK tidak valid.');
        }

        $barang = $this->db->select('id, nama, satuan')->where('id', $id_barang)->get('barang')->row();
        if (!$barang) {
            $this->db->trans_rollback();
            return array(false, 'Barang tidak ditemukan.');
        }

        // Kunci baris stok barang ini (semua lokasi yang masih ada stok)
        $rows = $this->db->query(
            'SELECT id, id_location, stok FROM stok_barang WHERE id_barang = ? AND stok > 0 ORDER BY id_location ASC FOR UPDATE',
            array($id_barang)
        )->result();

        $tersedia = 0;
        foreach ($rows as $r) {
            $tersedia += (int) $r->stok;
        }
        if ($tersedia < $qty) {
            $this->db->trans_rollback();
            return array(false, 'Stok ' . $barang->nama . ' tidak cukup. Tersedia: ' . $tersedia . ' ' . $barang->satuan . '.');
        }

        // Log pemakaian bahan dibuat dulu supaya id-nya bisa jadi id_referensi di stok_riwayat
        $this->db->insert('spk_material_usage', array(
            'id_spk'          => $id_spk,
            'id_spk_detail'   => $id_detail,
            'id_barang_bahan' => $id_barang,
            'qty_pakai'       => $qty,
            'satuan'          => $barang->satuan,
            'tanggal_keluar'  => $data_material['tanggal_keluar'],
            'keterangan'      => $data_material['keterangan'],
            'id_user'         => $id_user,
        ));
        $id_usage = $this->db->insert_id();

        $ket = 'Keluar bahan untuk produksi ' . $spk->no_spk
             . ($data_material['keterangan'] !== '' ? ' - ' . $data_material['keterangan'] : '');

        // Ambil dari lokasi dengan id terkecil lebih dulu; tiap lokasi = 1 baris riwayat
        $sisa = $qty;
        foreach ($rows as $r) {
            if ($sisa <= 0) {
                break;
            }
            $ambil = min((int) $r->stok, $sisa);
            if (!$this->_mutasi_stok($id_barang, $r->id_location, 'PEMAKAIAN_PRODUKSI', $id_usage, 0, $ambil, $ket, $id_user)) {
                $this->db->trans_rollback();
                return array(false, 'Gagal mencatat riwayat stok.');
            }
            $sisa -= $ambil;
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array(false, 'Gagal mencatat pengeluaran bahan baku.');
        }

        $this->db->trans_commit();
        return array(true, 'Pengeluaran ' . $barang->nama . ' sebanyak ' . $qty . ' ' . $barang->satuan . ' dicatat di kartu stok dan stok gudang diperbarui.');
    }

    // Kembalikan SISA bahan ke gudang (lokasi asal). Dicatat sebagai RETUR di stok_riwayat.
    // $data: id_material (id spk_material_usage), qty_retur, tanggal_retur, keterangan
    public function retur_material($id_spk, $data, $id_user)
    {
        $id_spk = (int) $id_spk;
        $id_mat = (int) $data['id_material'];
        $qty    = (float) $data['qty_retur'];

        if (abs($qty - round($qty)) > 0.00001) {
            return array(false, 'Qty harus bilangan bulat (kolom stok bertipe integer).');
        }
        $qty = (int) round($qty);
        if ($qty <= 0) {
            return array(false, 'Qty retur harus lebih dari 0.');
        }

        $this->db->trans_begin();

        $spk = $this->db->query('SELECT id, no_spk, status FROM spk WHERE id = ? FOR UPDATE', array($id_spk))->row();
        if (!$spk || !in_array($spk->status, array('proses', 'selesai'), true)) {
            $this->db->trans_rollback();
            return array(false, 'SPK tidak ditemukan atau sudah dibatalkan.');
        }

        $m = $this->db->query(
            'SELECT * FROM spk_material_usage WHERE id = ? AND id_spk = ? FOR UPDATE',
            array($id_mat, $id_spk)
        )->row();
        if (!$m) {
            $this->db->trans_rollback();
            return array(false, 'Data bahan tidak ditemukan.');
        }

        $sudah = (int) $this->db->query(
            'SELECT COALESCE(SUM(qty), 0) AS n FROM spk_material_retur WHERE id_material = ?', array($id_mat)
        )->row()->n;
        $maks = (int) round((float) $m->qty_pakai) - $sudah;
        if ($qty > $maks) {
            $this->db->trans_rollback();
            return array(false, 'Qty retur melebihi sisa. Maksimal yang bisa dikembalikan: ' . $maks . ' ' . $m->satuan . '.');
        }

        // Lokasi asal bahan (dari riwayat pengeluaran) dikurangi yang sudah pernah diretur
        $lok = $this->db->query(
            "SELECT r.id_location, SUM(r.qty_keluar) AS keluar,
                    COALESCE((SELECT SUM(t.qty) FROM spk_material_retur t
                              WHERE t.id_material = ? AND t.id_location = r.id_location), 0) AS kembali
             FROM stok_riwayat r
             WHERE r.jenis_transaksi = 'PEMAKAIAN_PRODUKSI' AND r.id_referensi = ?
             GROUP BY r.id_location ORDER BY r.id_location ASC",
            array($id_mat, $id_mat)
        )->result();

        $ket = 'Retur sisa bahan dari produksi ' . $spk->no_spk
             . (!empty($data['keterangan']) ? ' - ' . $data['keterangan'] : '');

        $sisa = $qty;
        foreach ($lok as $l) {
            if ($sisa <= 0) {
                break;
            }
            $ambil = min((int) $l->keluar - (int) $l->kembali, $sisa);
            if ($ambil <= 0) {
                continue;
            }
            $this->db->insert('spk_material_retur', array(
                'id_spk'        => $id_spk,
                'id_material'   => $id_mat,
                'id_barang'     => (int) $m->id_barang_bahan,
                'id_location'   => (int) $l->id_location,
                'qty'           => $ambil,
                'tanggal_retur' => $data['tanggal_retur'],
                'keterangan'    => isset($data['keterangan']) ? $data['keterangan'] : '',
                'id_user'       => $id_user,
            ));
            $id_retur = $this->db->insert_id();

            if (!$this->_mutasi_stok($m->id_barang_bahan, $l->id_location, 'RETUR', $id_retur, $ambil, 0, $ket, $id_user)) {
                $this->db->trans_rollback();
                return array(false, 'Gagal mencatat riwayat stok.');
            }
            $sisa -= $ambil;
        }

        if ($sisa > 0) {
            // Pemakaian lama (sebelum perbaikan) tidak punya riwayat lokasi
            $this->db->trans_rollback();
            return array(false, 'Lokasi asal bahan tidak ditemukan di kartu stok (pengeluaran lama). Kembalikan lewat Stok Opname.');
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array(false, 'Gagal mencatat retur bahan.');
        }

        $this->db->trans_commit();
        return array(true, 'Sisa bahan sebanyak ' . $qty . ' ' . $m->satuan . ' dikembalikan ke gudang dan dicatat di kartu stok.');
    }

    // ===================== TAHAPAN PRODUKSI =====================

    private function _master_tahap()
    {
        return $this->db->where('aktif', 1)->order_by('urutan', 'ASC')->get('spk_tahapan_master')->result();
    }

    // Salin tahapan master ke item SPK (snapshot, jadi perubahan master tidak mengubah SPK lama)
    private function _isi_tahap($id_spk_detail, $master)
    {
        foreach ($master as $m) {
            $this->db->insert('spk_detail_tahap', array(
                'id_spk_detail' => (int) $id_spk_detail,
                'nama_tahap'    => $m->nama,
                'urutan'        => (int) $m->urutan,
                'bobot'         => (float) $m->bobot,
                'status'        => 'belum',
            ));
        }
    }

    // Persen = total bobot tahapan berstatus selesai / total bobot
    public function hitung_persen($tahap)
    {
        $total = 0; $selesai = 0;
        foreach ($tahap as $t) {
            $total += (float) $t->bobot;
            if ($t->status === 'selesai') {
                $selesai += (float) $t->bobot;
            }
        }
        return $total > 0 ? (int) round($selesai / $total * 100) : 0;
    }

    // Item dianggap selesai bila semua tahapannya selesai
    private function _sinkron_item($id_spk_detail)
    {
        $rows  = $this->db->where('id_spk_detail', (int) $id_spk_detail)->get('spk_detail_tahap')->result();
        $semua = !empty($rows);
        foreach ($rows as $t) {
            if ($t->status !== 'selesai') {
                $semua = false;
                break;
            }
        }
        $this->db->query(
            'UPDATE spk_detail SET qty_selesai = IF(? = 1, qty_produksi, 0), status_item = ? WHERE id = ?',
            array($semua ? 1 : 0, $semua ? 'selesai' : 'proses', (int) $id_spk_detail)
        );
        $this->_sinkron_so_detail($id_spk_detail);
    }

    // Salin qty selesai SPK ke item Sales Order (kolom sales_order_detail.qty_siap)
    private function _sinkron_so_detail($id_spk_detail)
    {
        $this->db->query(
            'UPDATE sales_order_detail sod JOIN spk_detail sd ON sd.id_so_detail = sod.id
             SET sod.qty_siap = sd.qty_selesai WHERE sd.id = ?',
            array((int) $id_spk_detail)
        );
    }

    // aksi: mulai | selesai | buka (buka lagi tahapan yang sudah selesai)
    public function ubah_tahap($id_spk, $id_tahap, $aksi, $id_user)
    {
        $id_spk = (int) $id_spk;
        $this->db->trans_begin();

        $spk = $this->db->query('SELECT id, status FROM spk WHERE id = ? FOR UPDATE', array($id_spk))->row();
        if (!$spk || $spk->status !== 'proses') {
            $this->db->trans_rollback();
            return array(false, 'SPK tidak ditemukan atau sudah tidak dalam status proses.');
        }

        $t = $this->db->query(
            'SELECT t.*, sd.id_spk FROM spk_detail_tahap t JOIN spk_detail sd ON sd.id = t.id_spk_detail WHERE t.id = ? FOR UPDATE',
            array((int) $id_tahap)
        )->row();
        if (!$t || (int) $t->id_spk !== $id_spk) {
            $this->db->trans_rollback();
            return array(false, 'Tahapan tidak valid.');
        }

        $now = date('Y-m-d H:i:s');
        if ($aksi === 'mulai') {
            $upd = array('status' => 'proses', 'tgl_mulai' => $t->tgl_mulai ? $t->tgl_mulai : $now, 'tgl_selesai' => null);
        } elseif ($aksi === 'selesai') {
            $upd = array('status' => 'selesai', 'tgl_mulai' => $t->tgl_mulai ? $t->tgl_mulai : $now, 'tgl_selesai' => $now);
        } elseif ($aksi === 'buka') {
            $upd = array('status' => 'proses', 'tgl_selesai' => null);
        } else {
            $this->db->trans_rollback();
            return array(false, 'Aksi tidak dikenal.');
        }
        $upd['id_user'] = $id_user;

        $this->db->where('id', (int) $t->id)->update('spk_detail_tahap', $upd);
        $this->_sinkron_item($t->id_spk_detail);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array(false, 'Gagal memperbarui tahapan.');
        }
        $this->db->trans_commit();
        return array(true, 'Tahapan "' . $t->nama_tahap . '" diperbarui.');
    }

    // Tandai SPK selesai: hanya bila semua tahapan item custom sudah selesai
    public function ubah_status_spk($id_spk, $status)
    {
        $id_spk = (int) $id_spk;
        if ($status !== 'selesai') {
            return array(false, 'Status tidak dikenal.');
        }

        $this->db->trans_begin();

        $spk = $this->db->query('SELECT id, id_so, status FROM spk WHERE id = ? FOR UPDATE', array($id_spk))->row();
        if (!$spk || $spk->status !== 'proses') {
            $this->db->trans_rollback();
            return array(false, 'SPK tidak ditemukan atau sudah tidak dalam status proses.');
        }

        $belum = (int) $this->db->query(
            "SELECT COUNT(*) AS n FROM spk_detail_tahap t JOIN spk_detail sd ON sd.id = t.id_spk_detail
             WHERE sd.id_spk = ? AND t.status <> 'selesai'", array($id_spk)
        )->row()->n;

        $tanpa = (int) $this->db->query(
            "SELECT COUNT(*) AS n FROM spk_detail sd
             WHERE sd.id_spk = ? AND sd.tipe = 'custom'
               AND NOT EXISTS (SELECT 1 FROM spk_detail_tahap t WHERE t.id_spk_detail = sd.id)", array($id_spk)
        )->row()->n;

        if ($belum > 0 || $tanpa > 0) {
            $this->db->trans_rollback();
            return array(false, 'Masih ada tahapan produksi yang belum selesai.');
        }

        $this->db->where('id', $id_spk)->update('spk', array('status' => 'selesai'));

        // Sinkron ke menu Penjualan: SO dari status 'produksi' menjadi 'siap_kirim'
        $this->db->where('id', (int) $spk->id_so)->where('status', 'produksi')
            ->update('sales_order', array('status' => 'siap_kirim'));

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array(false, 'Gagal mengubah status SPK.');
        }
        $this->db->trans_commit();
        return array(true, 'SPK ditandai selesai. Status Sales Order otomatis menjadi Siap Kirim.');
    }
}