<?php defined('BASEPATH') or exit('No direct script access allowed');

class User_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    // Terapkan filter search ke query builder (dipakai bareng oleh get & count)
    private function applySearchFilter($search)
    {
        if (!empty($search)) {
            $this->db->group_start()
                ->like('nama', $search)
                ->or_like('username', $search)
                ->group_end();
        }
    }
    // Mengambil data kategori barang dengan pagination & search (untuk grid)
    public function get_users_paginated($search = '', $limit = 5, $offset = 0)
    {
        $this->applySearchFilter($search);
        $this->db->order_by('id', 'DESC');
        $this->db->limit($limit, $offset);

        return $this->db->get('users')->result_array();
    }

    // Hitung total data kategori barang (dengan search yang sama) untuk pagination
    public function count_users($search = '')
    {
        $this->applySearchFilter($search);
        return $this->db->count_all_results('users');
    }

    // Mengambil semua data dari tabel 'user'
    public function get_all_users()
    {
        $query = $this->db->get('users');
        return $query->result_array();
    }

    public function get_by_username($username)
    {
        $this->db->where('username', $username);
        $query = $this->db->get('users');
        return $query->row();
    }

    public function get_by_id($id)
    {
        return $this->db->get_where('users', ['id' => $id])->row();
    }
    // Simpan data user baru
    public function insert_user($data)
    {
        return $this->db->insert('users', $data);
    }
    public function update_user($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('users', $data);
    }

    public function delete_user($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete('users');
    }
}
