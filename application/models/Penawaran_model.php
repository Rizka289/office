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

    public function get_last_error()
    {
        return $this->last_error;
    }

    // Mengambil daftar penawaran beserta relasinya, dengan filter pencarian
    // (no. surat / nama customer) dan rentang tanggal.
    public function get_all_penawaran($filter = [])
    {
        $this->db->select('
            pen.id,
            pen.no_surat,
            pen.tanggal,
            pen.grand_total,
            cus.nama AS customer,
            (
                SELECT COUNT(*)
                FROM penawaran_detail pd
                WHERE pd.id_penawaran = pen.id
            ) AS total_item
        ');
        $this->db->from('penawaran pen');
        $this->db->join('customer cus', 'cus.id = pen.id_customer', 'left');

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

        $this->db->order_by('pen.tanggal', 'DESC');
        $this->db->order_by('pen.id', 'DESC');

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
            b.nama AS nama_barang
        ');
        $this->db->from('penawaran_detail pd');
        $this->db->join('barang b', 'b.id = pd.id_barang', 'left');
        $this->db->where('pd.id_penawaran', $id);
        $this->db->order_by('pd.id', 'ASC');
        $items = $this->db->get()->result();

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

        $this->db->select('no_surat');
        $this->db->from('penawaran');
        $this->db->like('no_surat', '/OPJI/' . $bulan . '/' . $tahun, 'after');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $last = $this->db->get()->row();

        $urut = 1;
        if ($last && preg_match('/^(\d+)\//', $last->no_surat, $m)) {
            $urut = ((int) $m[1]) + 1;
        }

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

    // Simpan 1 penawaran baru: insert header ke `penawaran`, lalu semua
    // item ke `penawaran_detail`, dibungkus 1 transaksi supaya konsisten
    // (kalau salah satu gagal, semuanya dibatalkan).
    public function simpan_penawaran($header, $items)
    {
        $this->db->trans_start();

        $this->db->insert('penawaran', $header);
        $id_penawaran = $this->db->insert_id();

        foreach ($items as $item) {
            $item['id_penawaran'] = $id_penawaran;
            $this->db->insert('penawaran_detail', $item);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->last_error = 'Transaksi database gagal, penawaran tidak disimpan.';
            return false;
        }

        return $id_penawaran;
    }

    /*
     * Catatan pembersihan:
     * Sebelumnya file model ini berisi banyak method milik modul
     * Penerimaan Barang (generate_no_penerimaan, get_po_by_no,
     * get_all_active_po, update_status_po, get_locations,
     * get_default_location_id, location_exists, user_exists,
     * get_all_barang, get_sisa_qty_po_detail, upsert_stok_barang,
     * simpan_penerimaan, get_penerimaan_detail, dst) — semuanya
     * mengacu ke tabel purchase_order/po_detail/penerimaan_barang/
     * stok_barang yang TIDAK ADA hubungannya dengan fitur Penawaran.
     *
     * Sepertinya file ini awalnya di-copy dari Penerimaan_barang_model.php
     * lalu hanya ditambahkan get_all_penawaran() tanpa membersihkan isi
     * lamanya. Method-method itu sudah dihapus dari sini karena:
     * 1) tidak dipakai oleh controller Penawaran sama sekali, dan
     * 2) modul Penerimaan Barang seharusnya sudah punya model sendiri
     *    (Penerimaan_barang_model.php) yang berisi method-method asli itu.
     *
     * Jika ternyata ada bagian lain kode yang justru memanggil method-method
     * tersebut dari Penawaran_model (seharusnya tidak), beri tahu saya
     * supaya bisa disesuaikan lagi.
     */
}