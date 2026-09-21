<?php defined('BASEPATH') or exit('No direct script access allowed');

class Kategori_barang_model extends CI_Model
{
    // Menyimpan error DB terakhir dari insert/update/delete (dipakai get_error_message)
    private $lastError = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    // Mengambil semua data dari tabel 'kategori_barang'
    public function get_all_kat_barang()
    {
        $query = $this->db->get('kategori_barang');
        return $query->result_array();
    }

    // Ambil semua kategori, diurutkan berdasarkan nama (untuk isi <select>)
    public function get_all()
    {
        $this->db->order_by('nama_kategori', 'ASC');
        return $this->db->get('kategori_barang')->result_array();
    }

    // Terapkan filter search ke query builder (dipakai bareng oleh get & count)
    private function applySearchFilter($search)
    {
        if (!empty($search)) {
            $this->db->group_start()
                ->like('kode_kategori', $search)
                ->or_like('nama_kategori', $search)
                ->or_like('deskripsi', $search)
                ->group_end();
        }
    }

    // Mengambil data kategori barang dengan pagination & search (untuk grid)
    public function get_kat_barang_paginated($search = '', $limit = 5, $offset = 0)
    {
        $this->applySearchFilter($search);
        $this->db->order_by('id', 'DESC');
        $this->db->limit($limit, $offset);

        return $this->db->get('kategori_barang')->result_array();
    }

    // Hitung total data kategori barang (dengan search yang sama) untuk pagination
    public function count_kat_barang($search = '')
    {
        $this->applySearchFilter($search);
        return $this->db->count_all_results('kategori_barang');
    }

    // Cek apakah kode kategori sudah dipakai (opsional: abaikan id tertentu saat edit)
    public function kode_exists($kode, $exclude_id = null)
    {
        $this->db->where('kode_kategori', $kode);
        if (!empty($exclude_id)) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->count_all_results('kategori_barang') > 0;
    }

    // Mengambil satu data kategori barang berdasarkan id (untuk isi form edit)
    public function get_by_id($id)
    {
        return $this->db->get_where('kategori_barang', ['id' => $id])->row_array();
    }

    public function insert_kategori_barang($data)
    {
        $db = $this->db;
        return $this->safeExecute(function () use ($db, $data) {
            return $db->insert('kategori_barang', $data);
        });
    }

    // Update data kategori barang
    public function update_kategori_barang($id, $data)
    {
        $db = $this->db;
        return $this->safeExecute(function () use ($db, $id, $data) {
            $db->where('id', $id);
            return $db->update('kategori_barang', $data);
        });
    }

    // Hapus data kategori barang
    public function delete_kategori_barang($id)
    {
        $db = $this->db;
        return $this->safeExecute(function () use ($db, $id) {
            return $db->delete('kategori_barang', ['id' => $id]);
        });
    }

    // ------------------------------------------------------------------
    // Penanganan error DB
    // ------------------------------------------------------------------

    // Jalankan query tulis dengan db_debug dimatikan sementara, supaya error DB
    // (duplikat kode, foreign key, dll) tidak menghasilkan halaman HTML error yang
    // merusak response JSON, tapi bisa kita tangkap dan tampilkan pesannya.
    private function safeExecute($callback)
    {
        $this->lastError = null;

        $oldDebug = $this->db->db_debug;
        $this->db->db_debug = false;

        try {
            $result = call_user_func($callback);
        } catch (Exception $e) {
            // PHP 8.1+ (mysqli) bisa melempar exception, bukan return FALSE
            $result = false;
            $this->lastError = array('code' => (int) $e->getCode(), 'message' => $e->getMessage());
        }

        $this->db->db_debug = $oldDebug;

        if ($result === false && $this->lastError === null) {
            $err = $this->db->error();
            $this->lastError = array('code' => (int) $err['code'], 'message' => $err['message']);
        }

        return (bool) $result;
    }

    // Terjemahkan error DB terakhir jadi pesan yang ramah untuk user.
    public function get_error_message($default)
    {
        if (empty($this->lastError)) {
            return $default;
        }

        switch ($this->lastError['code']) {
            case 1062: // Duplicate entry
                return 'Kode kategori sudah digunakan, gunakan kode lain.';
            case 1451: // FK: masih dipakai tabel lain (mis. barang)
                return 'Kategori tidak bisa dihapus karena masih dipakai oleh data barang.';
            case 1452: // FK: referensi tidak valid
                return 'Data referensi tidak valid.';
            case 1406: // Data too long
                return 'Salah satu isian terlalu panjang.';
        }

        // Error lain: catat di application/logs supaya bisa dilacak
        log_message('error', 'Kategori_barang_model DB error [' . $this->lastError['code'] . ']: ' . $this->lastError['message']);
        return $default;
    }
}