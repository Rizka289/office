<?php defined('BASEPATH') or exit('No direct script access allowed');

class Barang_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    // Join standar ke kategori_barang, dipakai bareng oleh get_barang_paginated & get_by_id
    // supaya nama kategori ikut terbawa (untuk ditampilkan), bukan cuma id_kategori.
    private function selectWithKategori()
    {
        $this->db->select('barang.*, kategori_barang.nama_kategori');
        $this->db->join('kategori_barang', 'kategori_barang.id = barang.id_kategori', 'left');
    }

    // Mengambil semua data dari tabel 'nama_barang'
    public function get_all_barang()
    {
        $query = $this->db->get('barang');
        return $query->result_array();
    }


    // Terapkan filter search ke query builder (dipakai bareng oleh get & count)
    // Search sekarang juga menjangkau nama_kategori (hasil join), bukan cuma kode & nama barang.
    private function applySearchFilter($search)
    {
        if (!empty($search)) {
            $this->db->group_start()
                ->like('barang.kode_barang', $search)
                ->or_like('barang.nama', $search)
                ->or_like('kategori_barang.nama_kategori', $search)
                ->group_end();
        }
    }

    // Mengambil data nama barang dengan pagination & search (untuk grid)
    public function get_barang_paginated($search = '', $limit = 5, $offset = 0)
    {
        // Tambahkan baris ini agar menyertakan JOIN dan SELECT nama_kategori
        $this->selectWithKategori();

        $this->applySearchFilter($search);
        $this->db->order_by('barang.id', 'DESC');
        $this->db->limit($limit, $offset);

        return $this->db->get('barang')->result_array();
    }

    // // Hitung total data nama barang (dengan search yang sama) untuk pagination
    // public function count_barang($search = '')
    // {
    //     $this->applySearchFilter($search);
    //     return $this->db->count_all_results('barang');
    // }

    // Hitung total data nama barang (dengan search yang sama) untuk pagination
    public function count_barang($search = '')
    {
        // JOIN tetap diperlukan di sini karena filter search menyentuh kolom kategori_barang.nama_kategori
        $this->db->join('kategori_barang', 'kategori_barang.id = barang.id_kategori', 'left');
        $this->applySearchFilter($search);
        return $this->db->count_all_results('barang');
    }

    // Mengambil semua barang (id, kode, nama, kategori) untuk dijadikan sumber
    // pilihan "barang" di form Purchase Order -- supaya baris detail PO
    // BENAR-BENAR merujuk ke baris yang ada di tabel barang (via id), bukan teks bebas.
    public function get_all_for_select()
    {
        $this->selectWithKategori();
        $this->db->order_by('barang.nama', 'ASC');
        return $this->db->get('barang')->result_array();
    }

    public function insert_nama_barang($data)
    {
        return $this->db->insert('barang', $data);
    }

    // // Mengambil satu data nama barang berdasarkan id (untuk isi form edit)
    // public function get_by_id($id)
    // {
    //     return $this->db->get_where('barang', ['id' => $id])->row_array();
    // }

    // Mengambil satu data nama barang berdasarkan id (untuk isi form edit)
    public function get_by_id($id)
    {
        $this->selectWithKategori();
        $this->db->where('barang.id', $id);
        return $this->db->get('barang')->row_array();
    }

    // Update data nama barang
    public function update_nama_barang($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('barang', $data);
    }

    // Hapus data nama barang
    public function delete_nama_barang($id)
    {
        return $this->db->delete('barang', ['id' => $id]);
    }
}