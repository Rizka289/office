<?php defined('BASEPATH') or exit('No direct script access allowed');

class Supplier_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    // Mengambil semua data dari tabel 'supplier'
    public function get_all_supplier()
    {
        $query = $this->db->get('supplier');
        return $query->result_array();
    }
    // Terapkan filter search ke query builder (dipakai bareng oleh get & count)
    private function applySearchFilter($search)
    {
        if (!empty($search)) {
            $this->db->group_start()
                ->like('nama', $search)
                ->or_like('kontak', $search)
                ->or_like('deskripsi', $search)
                ->or_like('alamat', $search)
                ->group_end();
        }
    }
    // Mengambil data supplier dengan pagination & search (untuk grid)
    public function get_supplier_paginated($search = '', $limit = 5, $offset = 0)
    {
        $this->applySearchFilter($search);
        $this->db->order_by('id', 'DESC');
        $this->db->limit($limit, $offset);

        return $this->db->get('supplier')->result_array();
    }

    // Hitung total data supplier (dengan search yang sama) untuk pagination
    public function count_supplier($search = '')
    {
        $this->applySearchFilter($search);
        return $this->db->count_all_results('supplier');
    }

    public function get_by_user($nama)
    {
        $this->db->where('nama', $nama);
        $query = $this->db->get('supplier');
        return $query->row();
    }

    public function get_by_id($id)
    {
        return $this->db->get_where('supplier', ['id' => $id])->row();
    }
    // Simpan data supplier baru
    public function insert_supplier($data)
    {
        return $this->db->insert('supplier', $data);
    }
    public function update_supplier($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('supplier', $data);
    }

    // Hapus supplier. Mengembalikan array ['success' => bool, 'error' => ['code' => .., 'message' => ..]]
    public function delete_supplier($id)
    {
        // Matikan sementara db_debug supaya error DB (mis. FK constraint)
        // tidak memunculkan halaman error 500 dari CodeIgniter
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;

        $this->db->where('id', $id);
        $ok    = $this->db->delete('supplier');
        $error = $this->db->error(); // ['code' => ..., 'message' => ...]

        $this->db->db_debug = $debug;

        return ['success' => (bool) $ok, 'error' => $error];
    }
}