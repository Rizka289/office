<?php defined('BASEPATH') or exit('No direct script access allowed');

class Barang_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    // Join standar ke kategori_barang, dipakai bareng oleh get_barang_paginated & get_by_id
    // supaya nama kategori ikut terbawa (untuk ditampilkan), bukan cuma id_kategori.
    private function selectWithKategori()
    {
        $this->db->select('barang.*, kategori_barang.nama_kategori');
        $this->db->join('kategori_barang', 'kategori_barang.id = barang.id_kategori', 'left');
    }

    // Mengambil semua data dari tabel 'nama_barang'
    public function get_all_barang()
    {
        $query = $this->db->get('barang');
        return $query->result_array();
    }


    // Terapkan filter search ke query builder (dipakai bareng oleh get & count)
    // Search sekarang juga menjangkau nama_kategori (hasil join), bukan cuma kode & nama barang.
    private function applySearchFilter($search)
    {
        if (!empty($search)) {
            $this->db->group_start()
                ->like('barang.kode_barang', $search)
                ->or_like('barang.nama', $search)
                ->or_like('kategori_barang.nama_kategori', $search)
                ->group_end();
        }
    }

    // Mengambil data nama barang dengan pagination & search (untuk grid)
    public function get_barang_paginated($search = '', $limit = 5, $offset = 0)
    {
        // Tambahkan baris ini agar menyertakan JOIN dan SELECT nama_kategori
        $this->selectWithKategori();

        $this->applySearchFilter($search);
        $this->db->order_by('barang.id', 'DESC');
        $this->db->limit($limit, $offset);

        return $this->db->get('barang')->result_array();
    }


    public function count_barang($search = '')
    {
        // JOIN tetap diperlukan di sini karena filter search menyentuh kolom kategori_barang.nama_kategori
        $this->db->join('kategori_barang', 'kategori_barang.id = barang.id_kategori', 'left');
        $this->applySearchFilter($search);
        return $this->db->count_all_results('barang');
    }

    public function get_all_for_select()
    {
        $this->selectWithKategori();
        $this->db->order_by('barang.nama', 'ASC');
        return $this->db->get('barang')->result_array();
    }

    // Menyimpan error DB terakhir supaya controller bisa menampilkan penyebab sebenarnya
    public $last_error = array('code' => 0, 'message' => '');

    public function insert_nama_barang($data)
    {
        // Matikan db_debug sementara: kalau tidak, CI3 akan menampilkan halaman error HTML
        // (bukan JSON) sehingga AJAX hanya menerima "parsererror" tanpa alasan yang jelas.
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;

        $ok = $this->db->insert('barang', $data);

        if (!$ok) {
            $this->last_error = $this->db->error();
            log_message('error', 'Insert barang gagal [' . $this->last_error['code'] . ']: ' . $this->last_error['message']);
        }

        $this->db->db_debug = $debug;
        return $ok;
    }

    // Cek apakah kode barang sudah dipakai (opsional: abaikan id tertentu saat edit)
    public function kode_exists($kode, $exclude_id = null)
    {
        $this->db->where('kode_barang', $kode);
        if (!empty($exclude_id)) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->count_all_results('barang') > 0;
    }



    // Mengambil satu data nama barang berdasarkan id (untuk isi form edit)
    public function get_by_id($id)
    {
        $this->selectWithKategori();
        $this->db->where('barang.id', $id);
        return $this->db->get('barang')->row_array();
    }

    // Update data nama barang
    public function update_nama_barang($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update('barang', $data);

        // Mengecek apakah query berhasil dijalankan (bukan sekadar terpengaruh barisnya)
        return $this->db->error()['code'] === 0;
    }

    // Hapus data nama barang
    public function delete_nama_barang($id)
    {
        return $this->db->delete('barang', ['id' => $id]);
    }
}