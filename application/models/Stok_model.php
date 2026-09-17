<?php defined('BASEPATH') or exit('No direct script access allowed');

class Stok_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    // Query Dasar untuk konsistensi data
    private function _build_stok_query($search = null)
    {
        $this->db->select('
            sb.id_barang,
            sb.id_location,
            barang.nama AS nama_barang,
            location.zone_name AS nama_lokasi,
            location.location_code AS kode_lokasi,
            SUM(sb.stok) AS total_stok
        ');
        $this->db->from('stok_barang sb');
        $this->db->join('barang barang', 'barang.id = sb.id_barang', 'left');
        $this->db->join('locations location', 'location.id = sb.id_location', 'left');
        $this->db->group_by('sb.id_barang, sb.id_location');

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('barang.nama', $search);
            $this->db->or_like('location.zone_name', $search);
            $this->db->group_end();
        }
    }

    // Hitung total baris grup untuk pagination
    public function count_stok($search = null)
    {
        $this->_build_stok_query($search);
        $query = $this->db->get();
        return $query->num_rows(); // Menghitung total grup hasil GROUP BY
    }

    // Ambil data paginated
    public function get_stok_paginated($search = null, $limit = 5, $offset = 0)
    {
        $this->_build_stok_query($search);
        $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }
}