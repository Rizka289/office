<?php defined('BASEPATH') or exit('No direct script access allowed');

class Kategori_barang_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    // Mengambil semua data dari tabel 'kategori barang'
    public function get_all_kat_barang()
    {
        $query = $this->db->get('kategori_barang');
        return $query->result_array();
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
    
    public function insert_kategori_barang($data)
    {
        return $this->db->insert('kategori_barang', $data);
    }

    // Mengambil satu data kategori barang berdasarkan id (untuk isi form edit)
    public function get_by_id($id)
    {
        return $this->db->get_where('kategori_barang', ['id' => $id])->row_array();
    }

    // Update data kategori barang
    public function update_kategori_barang($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('kategori_barang', $data);
    }

    // Hapus data kategori barang
    public function delete_kategori_barang($id)
    {
        return $this->db->delete('kategori_barang', ['id' => $id]);
    }
}
