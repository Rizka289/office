<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stok_riwayat_model extends CI_Model {

    public function get_riwayat($id_barang = NULL, $id_location = NULL)
    {
        $this->db->select('sr.*, b.nama, b.kode_barang, l.zone_name, u.username, l.location_code');
        $this->db->from('stok_riwayat sr');
        // JOIN ke tabel master barang, lokasi, dan user (sesuaikan nama tabel/kolom master kamu)
        $this->db->join('barang b', 'b.id = sr.id_barang', 'left');
        $this->db->join('locations l', 'l.id = sr.id_location', 'left');
        $this->db->join('users u', 'u.id = sr.created_by', 'left');

        if (!empty($id_barang)) {
            $this->db->where('sr.id_barang', $id_barang);
        }
        if (!empty($id_location)) {
            $this->db->where('sr.id_location', $id_location);
        }

        $this->db->order_by('sr.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_all_barang()
    {
        return $this->db->get('barang')->result_array(); // Sesuaikan nama tabel master barang
    }

    public function get_all_location()
    {
        return $this->db->get('locations')->result_array(); // Sesuaikan nama tabel master lokasi
    }
}