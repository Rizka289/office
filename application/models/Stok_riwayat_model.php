<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stok_riwayat_model extends CI_Model {

    public function get_riwayat($id_barang = NULL, $id_location = NULL)
    {
        $this->db->select('sr.*, b.nama, b.kode_barang, l.zone_name, u.username, l.location_code, b.warna');
        $this->db->from('stok_riwayat sr');
        // JOIN ke tabel master barang, lokasi, dan user (sesuaikan nama tabel/kolom master kamu)
        $this->db->join('barang b', 'b.id = sr.id_barang', 'left');
        $this->db->join('locations l', 'l.id = sr.id_location', 'left');
        $this->db->join('users u', 'u.id = sr.created_by', 'left');

        if (!empty($id_barang)) {
            $this->db->where('sr.id_barang', $id_barang);
        }
        if (!empty($id_location)) {
            $this->db->where('sr.id_location', $id_location);
        }

        $this->db->order_by('sr.created_at', 'DESC');
        $this->db->order_by('sr.id', 'DESC');   // kalau jamnya sama, yang terakhir diinput di atas
        return $this->db->get()->result_array();
    }

    public function get_all_barang()
    {
        return $this->db->get('barang')->result_array(); // Sesuaikan nama tabel master barang
    }

    public function get_all_location()
    {
        return $this->db->get('locations')->result_array(); // Sesuaikan nama tabel master lokasi
    }

    /**
     * ID riwayat yang BOLEH dihapus:
     *  - jenis_transaksi = 'STOK_OPNAME'
     *  - merupakan transaksi TERAKHIR untuk barang + lokasi + opsi + ukuran yang sama
     *    (belum ada transaksi lain sesudahnya = barang belum digunakan)
     */
    public function get_id_bisa_hapus()
    {
        $sql = "SELECT sr.id
                FROM stok_riwayat sr
                JOIN (
                    SELECT MAX(id) AS max_id
                    FROM stok_riwayat
                    GROUP BY id_barang, id_location, COALESCE(opsi, ''), COALESCE(ukuran, '')
                ) akhir ON akhir.max_id = sr.id
                WHERE sr.jenis_transaksi = 'STOK_OPNAME'";

        return array_map('intval', array_column($this->db->query($sql)->result_array(), 'id'));
    }

    /**
     * Hapus 1 riwayat stok opname DAN kembalikan stok di stok_barang
     * ke kondisi sebelum input tersebut.
     *
     * @return array ['status' => bool, 'pesan' => string]
     */
    public function hapus_stok_opname($id)
    {
        $id = (int) $id;

        // Ambil kunci stoknya dulu (tanpa lock)
        $awal = $this->db->get_where('stok_riwayat', ['id' => $id])->row_array();
        if (!$awal) {
            return ['status' => false, 'pesan' => 'Data riwayat tidak ditemukan.'];
        }

        $id_barang   = $awal['id_barang'];
        $id_location = $awal['id_location'];
        $opsi        = (string) ($awal['opsi'] ?? '');
        $ukuran      = (string) ($awal['ukuran'] ?? '');

        $this->db->trans_begin();

        // 1. Kunci baris stok_barang dulu (urutan lock sama dengan proses_stok_opname
        //    supaya tidak deadlock kalau ada yang input bersamaan)
        $stok_rows = $this->db->query(
            'SELECT id, stok FROM stok_barang
             WHERE id_barang = ? AND id_location = ? AND opsi = ? AND ukuran = ?
             ORDER BY id ASC FOR UPDATE',
            [$id_barang, $id_location, $opsi, $ukuran]
        )->result();

        // 2. Baca ulang riwayat dengan lock
        $r = $this->db->query('SELECT * FROM stok_riwayat WHERE id = ? FOR UPDATE', [$id])->row_array();
        if (!$r) {
            return $this->_gagal('Data riwayat tidak ditemukan.');
        }

        // 3. Hanya data dari Stok Opname
        if ($r['jenis_transaksi'] !== 'STOK_OPNAME') {
            return $this->_gagal('Hanya data dari Stok Opname yang boleh dihapus.');
        }

        // 4. Barang belum digunakan: tidak boleh ada transaksi lain SESUDAH data ini
        $sesudahnya = (int) $this->db->query(
            "SELECT COUNT(*) AS jml FROM stok_riwayat
             WHERE id_barang = ? AND id_location = ?
               AND COALESCE(opsi, '') = ? AND COALESCE(ukuran, '') = ?
               AND id > ?",
            [$id_barang, $id_location, $opsi, $ukuran, $id]
        )->row()->jml;

        if ($sesudahnya > 0) {
            return $this->_gagal('Barang ini sudah digunakan di transaksi berikutnya, data tidak bisa dihapus.');
        }

        // 5. Stok sekarang harus masih sama dengan stok sesudah input ini.
        //    Kalau beda, berarti stok sudah berubah lewat proses lain (sudah digunakan).
        $stok_sekarang = 0;
        foreach ($stok_rows as $s) {
            $stok_sekarang += (int) $s->stok;
        }

        if ($stok_sekarang !== (int) $r['stok_sesudah']) {
            return $this->_gagal('Stok barang ini sudah berubah sejak input tersebut (sudah digunakan), data tidak bisa dihapus.');
        }

        // 6. Kembalikan stok: batalkan selisih dari input ini
        $selisih    = (int) $r['qty_masuk'] - (int) $r['qty_keluar'];
        $stok_baru  = $stok_sekarang - $selisih;   // = stok_sebelum

        if ($stok_baru < 0) {
            return $this->_gagal('Stok akan menjadi minus jika data ini dihapus.');
        }

        // 7. Hapus riwayatnya
        $this->db->where('id', $id)->delete('stok_riwayat');

        // 8. Update / bersihkan stok_barang
        $sisa_riwayat = (int) $this->db->query(
            "SELECT COUNT(*) AS jml FROM stok_riwayat
             WHERE id_barang = ? AND id_location = ?
               AND COALESCE(opsi, '') = ? AND COALESCE(ukuran, '') = ?",
            [$id_barang, $id_location, $opsi, $ukuran]
        )->row()->jml;

        if (!empty($stok_rows)) {
            if ($sisa_riwayat === 0 && $stok_baru === 0) {
                // Tidak ada riwayat lain & stok 0 -> hapus baris stoknya supaya tidak muncul di grid
                $this->db->where_in('id', array_map(function ($s) { return (int) $s->id; }, $stok_rows));
                $this->db->delete('stok_barang');
            } elseif ($selisih !== 0) {
                $this->db->set('stok', 'stok - (' . (int) $selisih . ')', false);
                $this->db->where('id', $stok_rows[0]->id);
                $this->db->update('stok_barang');
            }
        }

        if ($this->db->trans_status() === FALSE) {
            return $this->_gagal('Gagal menghapus data, silakan coba lagi.');
        }

        $this->db->trans_commit();
        return ['status' => true, 'pesan' => 'Data stok opname berhasil dihapus dan stok sudah dikembalikan.'];
    }

    private function _gagal($pesan)
    {
        $this->db->trans_rollback();
        return ['status' => false, 'pesan' => $pesan];
    }
}