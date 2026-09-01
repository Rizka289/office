<?php defined('BASEPATH') or exit('No direct script access allowed');

class Locations_model extends CI_Model
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
                ->like('location_code', $search)
                ->or_like('location_type', $search)
                ->or_like('zone_name', $search)
                ->group_end();
        }
    }

    // Mengambil data kategori locations dengan pagination & search (untuk grid)
    public function get_location_paginated($search = '', $limit = 5, $offset = 0)
    {
        $this->applySearchFilter($search);
        $this->db->order_by('id', 'DESC');
        $this->db->limit($limit, $offset);

        return $this->db->get('locations')->result_array();
    }

    // Hitung total data locations (dengan search yang sama) untuk pagination
    public function count_location($search = '')
    {
        $this->applySearchFilter($search);
        return $this->db->count_all_results('locations');
    }

    // Mengambil semua data dari tabel 'locations'
    public function get_all_location()
    {
        $query = $this->db->get('locations');
        return $query->result_array();
    }

    public function get_by_location($zone)
    {
        $this->db->where('zone_name', $zone);
        $query = $this->db->get('locations');
        return $query->row();
    }

    public function get_by_id($id)
    {
        return $this->db->get_where('locations', ['id' => $id])->row();
    }

    // Simpan data location baru
    public function insert_location($data)
    {
        return $this->db->insert('locations', $data);
    }

    public function update_location($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('locations', $data);
    }

    public function delete_location($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete('locations');
    }
}