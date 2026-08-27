<?php defined('BASEPATH') or exit('No direct script access allowed');

class Supplier_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    // Mengambil semua data dari tabel 'user'
    public function get_all_supplier()
    {
        $query = $this->db->get('supplier');
        return $query->result_array();
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
    // Simpan data user baru
    public function insert_supplier($data)
    {
        return $this->db->insert('supplier', $data);
    }
    public function update_supplier($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('supplier', $data);
    }

    public function delete_supplier($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete('supplier');
    }
}
