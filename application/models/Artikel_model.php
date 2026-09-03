<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Artikel_model extends CI_Model
{
    protected $table = 'artikel';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Terapkan filter pencarian & status
     */
    private function applyFilters($search = '', $is_active = null)
    {
        if (!empty($search)) {
            $this->db->group_start()
                ->like('title', $search)
                ->or_like('title_en', $search)
                ->or_like('title_zh', $search)
                ->or_like('description', $search)
                ->or_like('description_en', $search)
                ->or_like('description_zh', $search)
                ->group_end();
        }

        if ($is_active !== null && $is_active !== '') {
            $this->db->where('is_active', (int) $is_active);
        }
    }

    /**
     * Ambil data artikel dengan pagination, search, dan filter status
     *
     * @param string $search Keyword pencarian
     * @param int $limit Jumlah data per halaman
     * @param int $offset Offset pagination
     * @param string|null $is_active Filter status aktif (1, 0, atau null untuk semua)
     * @return array
     */
    public function get_artikel_paginated($search = '', $limit = 5, $offset = 0, $is_active = null)
    {
        $this->applyFilters($search, $is_active);
        $this->db->order_by('id', 'DESC');
        $this->db->limit($limit, $offset);

        return $this->db->get($this->table)->result_array();
    }

    /**
     * Hitung total data artikel untuk pagination
     *
     * @param string $search
     * @param string|null $is_active
     * @return int
     */
    public function count_artikel($search = '', $is_active = null)
    {
        $this->applyFilters($search, $is_active);
        return $this->db->count_all_results($this->table);
    }

    /**
     * Ambil satu artikel berdasarkan ID
     *
     * @param int $id
     * @return array|null
     */
    public function get_by_id($id)
    {
        return $this->db->get_where($this->table, ['id' => $id])->row_array();
    }

    /**
     * Insert artikel baru
     *
     * @param array $data
     * @return bool
     */
    public function insert_artikel($data)
    {
        return $this->db->insert($this->table, $data);
    }

    /**
     * Update data artikel berdasarkan ID
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update_artikel($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Hapus artikel berdasarkan ID
     *
     * @param int $id
     * @return bool
     */
    public function delete_artikel($id)
    {
        return $this->db->delete($this->table, ['id' => $id]);
    }

    /**
     * Toggle status aktif / nonaktif
     *
     * @param int $id
     * @return bool
     */
    public function toggle_status($id)
    {
        $item = $this->get_by_id($id);
        if (!$item) {
            return false;
        }

        $newStatus = $item['is_active'] == 1 ? 0 : 1;
        return $this->update_artikel($id, ['is_active' => $newStatus]);
    }
}
