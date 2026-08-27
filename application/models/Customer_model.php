<?php defined('BASEPATH') or exit('No direct script access allowed');

class Customer_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    // Mengambil semua data dari tabel 'user'
    public function get_all_customer()
    {
        $query = $this->db->get('customer');
        return $query->result_array();
    }

    public function get_by_user($nama)
    {
        $this->db->where('nama', $nama);
        $query = $this->db->get('customer');
        return $query->row();
    }

    public function get_by_id($id)
    {
        return $this->db->get_where('customer', ['id' => $id])->row();
    }
    // Simpan data user baru
    public function insert_customer($data)
    {
        return $this->db->insert('customer', $data);
    }
    public function update_customer($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('customer', $data);
    }

    public function delete_customer($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete('customer');
    }
}
