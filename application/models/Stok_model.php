<?php defined('BASEPATH') or exit('No direct script access allowed');

// Lokasi file: application/models/Stok_model.php

class Stok_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    // Subquery: keterangan riwayat TERAKHIR untuk kombinasi barang + lokasi + opsi + ukuran.
    // "<=>" = pembanding NULL-safe (NULL <=> NULL bernilai true).
    private function _keterangan_sql()
    {
        return "(SELECT sr.keterangan
                 FROM stok_riwayat sr
                 WHERE sr.id_barang = sb.id_barang
                   AND sr.id_location = sb.id_location
                   AND sr.opsi <=> sb.opsi
                   AND sr.ukuran <=> sb.ukuran
                   AND sr.keterangan IS NOT NULL
                   AND sr.keterangan <> ''
                 ORDER BY sr.id DESC
                 LIMIT 1)";
    }

    // Query dasar: satu baris = satu kombinasi barang + lokasi + opsi + ukuran
    // (sama dengan kunci stok di Stok_opname_model)
    private function _build_stok_query($search = null)
    {
        $ket_sql = $this->_keterangan_sql();

        $this->db->select("
            sb.id_barang,
            sb.id_location,
            sb.opsi,
            sb.ukuran,
            barang.kode_barang,
            barang.nama AS nama_barang,
            barang.warna,
            location.zone_name AS nama_lokasi,
            location.location_code AS kode_lokasi,
            SUM(sb.stok) AS total_stok,
            $ket_sql AS keterangan
        ", false);
        $this->db->from('stok_barang sb');
        $this->db->join('barang barang', 'barang.id = sb.id_barang', 'left');
        $this->db->join('locations location', 'location.id = sb.id_location', 'left');

        // Semua kolom non-agregat wajib masuk GROUP BY (aman untuk ONLY_FULL_GROUP_BY)
        $this->db->group_by(
            'sb.id_barang, sb.id_location, sb.opsi, sb.ukuran, barang.kode_barang, barang.nama, barang.warna, ' .
            'location.zone_name, location.location_code'
        );

        if (!empty($search)) {
            $kw = $this->db->escape_like_str($search);

            $this->db->group_start();
            $this->db->like('barang.nama', $search);
            $this->db->or_like('barang.kode_barang', $search);
            $this->db->or_like('barang.warna', $search);
            $this->db->or_like('sb.opsi', $search);
            $this->db->or_like('sb.ukuran', $search);
            $this->db->or_like('location.zone_name', $search);
            $this->db->or_like('location.location_code', $search);
            $this->db->or_where("$ket_sql LIKE '%{$kw}%'", null, false);
            $this->db->group_end();
        }

        $this->db->order_by('barang.nama', 'ASC');
        $this->db->order_by('sb.opsi', 'ASC');
        $this->db->order_by('sb.ukuran', 'ASC');
        $this->db->order_by('location.location_code', 'ASC');
    }

    // Hitung total grup (barang + lokasi + opsi + ukuran) untuk pagination
    public function count_stok($search = null)
    {
        $this->_build_stok_query($search);
        $sql = $this->db->get_compiled_select(); // juga mereset query builder

        $row = $this->db->query("SELECT COUNT(*) AS total FROM ($sql) AS t")->row();
        return $row ? (int) $row->total : 0;
    }

    // Ambil data paginated
    public function get_stok_paginated($search = null, $limit = 5, $offset = 0)
    {
        $this->_build_stok_query($search);
        $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }
}