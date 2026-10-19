<?php defined('BASEPATH') or exit('No direct script access allowed');

class Penawaran_model extends CI_Model
{
    // Menyimpan pesan error DB terakhir (untuk debugging & ditampilkan ke user/log)
    protected $last_error = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    // Ringkasan penawaran pada bulan berjalan (tidak terpengaruh filter di toolbar)
    public function get_ringkasan_bulan_ini()
    {
        $row = $this->db->select("
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN status = 'approved' THEN 0 ELSE 1 END) AS belum,
                COALESCE(SUM(CASE WHEN status = 'approved' THEN grand_total ELSE 0 END), 0) AS nilai
            ", false)
            ->where('DATE(tanggal) >=', date('Y-m-01'))
            ->where('DATE(tanggal) <=', date('Y-m-t'))
            ->get('penawaran')
            ->row();

        return [
            'total'    => (int) ($row->total ?? 0),
            'approved' => (int) ($row->approved ?? 0),
            'belum'    => (int) ($row->belum ?? 0),
            'nilai'    => (float) ($row->nilai ?? 0),
        ];
    }

    // Rekap semua penawaran per bulan (bulan terbaru dulu)
    public function get_rekap_per_bulan($limit = 12)
    {
        return $this->db->select("
                DATE_FORMAT(tanggal, '%Y-%m') AS periode,
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN status = 'approved' THEN 0 ELSE 1 END) AS belum,
                COALESCE(SUM(CASE WHEN status = 'approved' THEN grand_total ELSE 0 END), 0) AS nilai
            ", false)
            ->group_by('periode')
            ->order_by('periode', 'DESC')
            ->limit((int) $limit)
            ->get('penawaran')
            ->result();
    }

    public function get_last_error()
    {
        return $this->last_error;
    }

    // Filter bersama untuk daftar & hitungan total (supaya selalu konsisten)
    private function _filter_penawaran($filter = [])
    {
        if (!empty($filter['q'])) {
            $this->db->group_start();
            $this->db->like('pen.no_surat', $filter['q']);
            $this->db->or_like('cus.nama', $filter['q']);
            $this->db->group_end();
        }

        if (!empty($filter['dari'])) {
            $this->db->where('DATE(pen.tanggal) >=', $filter['dari']);
        }

        if (!empty($filter['sampai'])) {
            $this->db->where('DATE(pen.tanggal) <=', $filter['sampai']);
        }
    }

    // Jumlah total penawaran (sesuai filter) untuk pagination
    public function count_penawaran($filter = [])
    {
        $this->db->from('penawaran pen');
        $this->db->join('customer cus', 'cus.id = pen.id_customer', 'left');
        $this->_filter_penawaran($filter);
        return (int) $this->db->count_all_results();
    }

    // Mengambil daftar penawaran beserta relasinya, dengan filter pencarian
    // (no. surat / nama customer) dan rentang tanggal.
    // $limit & $offset dipakai untuk pagination (null = ambil semua).
    public function get_all_penawaran($filter = [], $limit = null, $offset = 0)
    {
        $this->db->select('
            pen.id,
            pen.no_surat,
            pen.tanggal,
            pen.grand_total,
            pen.status,
            cus.nama AS customer,
            (
                SELECT COALESCE(SUM(pd.qty), 0)
                FROM penawaran_detail pd
                WHERE pd.id_penawaran = pen.id
            ) AS total_item
        ');
        $this->db->from('penawaran pen');
        $this->db->join('customer cus', 'cus.id = pen.id_customer', 'left');
        $this->_filter_penawaran($filter);

        $this->db->order_by('pen.tanggal', 'DESC');
        $this->db->order_by('pen.id', 'DESC');

        if ($limit !== null) {
            $this->db->limit((int) $limit, (int) $offset);
        }

        return $this->db->get()->result();
    }



    // Header + item lengkap 1 penawaran, untuk halaman detail & cetak.
    // Catatan: kolom customer.alamat / customer.telepon dan users.nama
    // adalah asumsi nama kolom umum — sesuaikan dengan skema tabel
    // `customer` dan `users` yang sebenarnya kalau namanya berbeda.
    public function get_penawaran_detail($id)
    {
        $this->db->select('
            pen.*,
            cus.nama    AS customer,
            cus.alamat  AS customer_alamat,
            cus.kontak AS customer_telepon,
            u.nama      AS dibuat_oleh
        ');
        $this->db->from('penawaran pen');
        $this->db->join('customer cus', 'cus.id = pen.id_customer', 'left');
        $this->db->join('users u', 'u.id = pen.id_user', 'left');
        $this->db->where('pen.id', $id);
        $header = $this->db->get()->row();

        if (!$header) {
            return NULL;
        }

        $this->db->select('
            pd.*,
            b.kode_barang,
            b.nama   AS nama_barang,
            b.gambar AS gambar_barang,
            b.kategori_rumus AS rumus,
            b.keterangan As keterangan
        ');
        $this->db->from('penawaran_detail pd');
        $this->db->join('barang b', 'b.id = pd.id_barang', 'left');
        $this->db->where('pd.id_penawaran', $id);
        $this->db->order_by('pd.id', 'ASC');
        $items = $this->db->get()->result();
        foreach ($items as &$it) {
            $it->aksesoris = $this->db->get_where('penawaran_detail_aksesoris', [
                'id_penawaran_detail' => $it->id
            ])->result();
            $it->kaca = $this->db->get_where('penawaran_detail_kaca', [
                'id_penawaran_detail' => $it->id
            ])->result();
        }

        return [
            'header' => $header,
            'items'  => $items,
        ];
    }

    // Nomor urut penawaran per bulan, format: NN/OPJI/MM/YYYY
    // (mengikuti pola data existing: 01/OPJI/08/2026, 02/OPJI/08/2026, dst).
    // Catatan: tidak ada UNIQUE constraint di kolom no_surat, jadi pada
    // kondisi sangat jarang (2 form dibuka & disimpan bersamaan persis di
    // waktu yang sama) nomor bisa dobel. Kalau mau 100% aman, tambahkan
    // `ADD UNIQUE KEY (no_surat)` di tabel `penawaran`.
    public function generate_no_penawaran($tanggal = null)
    {
        $bulan = $tanggal ? date('m', strtotime($tanggal)) : date('m');
        $tahun = $tanggal ? date('Y', strtotime($tanggal)) : date('Y');

        // no_surat berformat "NN/OPJI/MM/YYYY": bagian bulan/tahun ada di
        // BELAKANG, jadi pola LIKE-nya harus diawali '%' (bukan diakhiri '%').
        // Nomor urut terbesar diambil dari angka sebelum '/' pertama.
        $row = $this->db->query(
            "SELECT MAX(CAST(SUBSTRING_INDEX(no_surat, '/', 1) AS UNSIGNED)) AS maks
             FROM penawaran
             WHERE no_surat LIKE ?",
            ['%/OPJI/' . $bulan . '/' . $tahun]
        )->row();

        $urut = ($row && $row->maks !== null) ? ((int) $row->maks) + 1 : 1;

        return sprintf('%02d', $urut) . '/OPJI/' . $bulan . '/' . $tahun;
    }

    // Daftar customer untuk dropdown di form tambah penawaran.
    // Catatan: nama kolom diasumsikan `id` dan `nama` — sesuaikan kalau beda.
    public function get_all_customer()
    {
        $this->db->select('id, nama');
        $this->db->order_by('nama', 'ASC');
        return $this->db->get('customer')->result_array();
    }

    // Daftar barang untuk dipilih sebagai item penawaran (pencarian di
    // sisi client / JS). Warna & harga_satuan dipakai untuk auto-fill
    // saat barang dipilih di form tambah penawaran, tapi nilainya tetap
    // boleh diedit user sebelum disimpan (kecuali nama barangnya sendiri).
    public function get_all_barang()
    {
        $this->db->select('id, kode_barang, nama, warna, komponen, harga_satuan, satuan, kategori_rumus,keterangan');
        $this->db->order_by('nama', 'ASC');
        return $this->db->get('barang')->result_array();
    }

    // Ambil data master barang untuk banyak id sekaligus, hasilnya di-index
    // per id. Dipakai controller untuk MENIMPA nama barang & komponen di
    // sisi server, supaya nilai yang tersimpan selalu sama dengan master
    // (field di form hanya readonly - masih bisa diakali lewat DevTools).
    public function get_barang_by_ids(array $ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return [];
        }

        $this->db->select('id, nama, komponen, kategori_rumus');
        $this->db->where_in('id', $ids);
        $rows = $this->db->get('barang')->result_array();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['id']] = $r;
        }
        return $map;
    }

    // Hapus semua aksesoris milik detail-detail dari 1 penawaran.
    private function _hapus_aksesoris_by_penawaran($id_penawaran)
    {
        $this->db->query(
            'DELETE a FROM penawaran_detail_aksesoris a
             JOIN penawaran_detail d ON d.id = a.id_penawaran_detail
             WHERE d.id_penawaran = ?',
            [(int) $id_penawaran]
        );
        // Detail kaca juga ikut dihapus
        $this->db->query(
            'DELETE k FROM penawaran_detail_kaca k
             JOIN penawaran_detail d ON d.id = k.id_penawaran_detail
             WHERE d.id_penawaran = ?',
            [(int) $id_penawaran]
        );
    }

    // Insert item ke penawaran_detail beserta aksesorisnya ke
    // penawaran_detail_aksesoris (dipakai oleh simpan & update).
    private function _insert_items($id_penawaran, $items)
    {
        // Hanya kolom yang benar-benar ada di tabel aksesoris yang diinsert,
        // supaya insert tidak error kalau ada key yang kolomnya belum dibuat.
        $kolom_aks = $this->db->list_fields('penawaran_detail_aksesoris');
        $kolom_det = $this->db->list_fields('penawaran_detail');
        $kolom_kaca = $this->db->list_fields('penawaran_detail_kaca');

        foreach ($items as $item) {
            $aksesoris_list = $item['aksesoris'] ?? [];
            unset($item['aksesoris']);

            $kaca_list = $item['kaca'] ?? [];
            unset($item['kaca']);

            $item['id_penawaran'] = $id_penawaran;

            // Key yang kolomnya belum ada di penawaran_detail dilewati (dicatat di log),
            // supaya insert tidak error kalau ada kolom yang belum dibuat.
            $det_tidak_ada = array_diff(array_keys($item), $kolom_det);
            if (!empty($det_tidak_ada)) {
                log_message('error', 'penawaran_detail: kolom tidak ada -> ' . implode(', ', $det_tidak_ada));
            }
            $item = array_intersect_key($item, array_flip($kolom_det));

            $this->db->insert('penawaran_detail', $item);
            $id_detail = $this->db->insert_id();

            foreach ($aksesoris_list as $aks) {
                $aks['id_penawaran_detail'] = $id_detail;
                $aks = array_intersect_key($aks, array_flip($kolom_aks));
                $this->db->insert('penawaran_detail_aksesoris', $aks);
            }

            foreach ($kaca_list as $kc) {
                $kc['id_penawaran_detail'] = $id_detail;
                $kc = array_intersect_key($kc, array_flip($kolom_kaca));
                $this->db->insert('penawaran_detail_kaca', $kc);
            }
        }
    }

    // Simpan 1 penawaran baru: insert header ke `penawaran`, lalu semua
    // item ke `penawaran_detail`, dibungkus 1 transaksi supaya konsisten
    // (kalau salah satu gagal, semuanya dibatalkan).
    public function simpan_penawaran($header, $items)
    {
        $this->db->trans_start();

        $this->db->insert('penawaran', $header);
        $id_penawaran = $this->db->insert_id();

        $this->_insert_items($id_penawaran, $items);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->last_error = 'Transaksi database gagal, penawaran tidak disimpan.';
            return false;
        }

        return $id_penawaran;
    }
    // Update penawaran yang MASIH belum di-approve: header di-update, item
    // lama dihapus lalu diinsert ulang dari form (semua dalam 1 transaksi).
    // Status dicek ulang di dalam transaksi dengan FOR UPDATE, jadi kalau
    // di saat yang sama penawaran ini di-approve, update akan ditolak.
    public function update_penawaran($id, $header, $items)
    {
        $id = (int) $id;

        $this->db->trans_start();

        $row = $this->db->query('SELECT status FROM penawaran WHERE id = ? FOR UPDATE', [$id])->row();

        if (!$row || $row->status === 'approved') {
            $this->db->trans_complete();
            $this->last_error = !$row
                ? 'Data penawaran tidak ditemukan.'
                : 'Penawaran sudah di-approve dan tidak bisa diubah.';
            return false;
        }

        $this->db->where('id', $id);
        $this->db->update('penawaran', $header);

        // Aksesoris dihapus dulu (anak dari penawaran_detail), baru detail-nya
        $this->_hapus_aksesoris_by_penawaran($id);
        $this->db->where('id_penawaran', $id);
        $this->db->delete('penawaran_detail');

        $this->_insert_items($id, $items);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->last_error = 'Transaksi database gagal, perubahan tidak disimpan.';
            return false;
        }

        return true;
    }

    // Hapus penawaran yang MASIH belum di-approve: item (penawaran_detail)
    // lalu header (penawaran), dalam 1 transaksi. Status dicek ulang di
    // dalam transaksi dengan FOR UPDATE, jadi kalau di saat yang sama
    // penawaran ini di-approve, penghapusan akan ditolak.
    public function hapus_penawaran($id)
    {
        $id = (int) $id;

        $this->db->trans_start();

        $row = $this->db->query('SELECT status FROM penawaran WHERE id = ? FOR UPDATE', [$id])->row();

        if (!$row || $row->status === 'approved') {
            $this->db->trans_complete();
            $this->last_error = !$row
                ? 'Data penawaran tidak ditemukan.'
                : 'Penawaran sudah di-approve dan tidak bisa dihapus.';
            return false;
        }

        $this->_hapus_aksesoris_by_penawaran($id);
        $this->db->where('id_penawaran', $id);
        $this->db->delete('penawaran_detail');

        $this->db->where('id', $id);
        $this->db->delete('penawaran');

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->last_error = 'Transaksi database gagal, penawaran tidak dihapus.';
            return false;
        }

        return true;
    }

    // Approve penawaran. Hanya berhasil kalau statusnya belum approved
    // (return false kalau sudah approved / id tidak ada).
    public function approve_penawaran($id, $id_user)
    {
        $this->db->where('id', (int) $id);
        $this->db->where('status <>', 'approved');
        $this->db->update('penawaran', [
            'status'      => 'approved',
            'approved_by' => (int) $id_user,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->db->affected_rows() > 0;
    }
}