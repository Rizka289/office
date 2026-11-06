<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tabel supplier: id, nama, kontak, alamat, deskripsi (+ created_at / aktif bila ada)
 */
class Supplier_model extends CI_Model
{
    private $table = 'supplier';

    // ---------- Untuk modul lain (Proses Pesanan / PO) ----------

    public function get_all()
    {
        return $this->db->order_by('nama', 'ASC')->get($this->table)->result();
    }

    // Untuk dropdown supplier: hanya yang aktif (kalau kolom aktif ada)
    public function get_aktif()
    {
        if ($this->db->field_exists('aktif', $this->table)) {
            $this->db->where('aktif', 1);
        }
        return $this->db->order_by('nama', 'ASC')->get($this->table)->result();
    }

    public function get($id)
    {
        return $this->get_by_id($id);
    }

    // ---------- Halaman master supplier ----------

    private function _filter($search)
    {
        if ($search !== '') {
            $this->db->group_start()
                ->like('nama', $search)
                ->or_like('kontak', $search)
                ->or_like('alamat', $search)
                ->or_like('deskripsi', $search)
                ->group_end();
        }
        // supplier nonaktif (sudah dipakai lalu "dihapus") tidak ditampilkan
        if ($this->db->field_exists('aktif', $this->table)) {
            $this->db->where('aktif', 1);
        }
    }

    public function get_supplier_paginated($search = '', $limit = 5, $offset = 0)
    {
        $this->db->select('id, nama, kontak, alamat, deskripsi');
        $this->_filter((string) $search);
        return $this->db->order_by('nama', 'ASC')->limit((int) $limit, (int) $offset)->get($this->table)->result();
    }

    public function count_supplier($search = '')
    {
        $this->_filter((string) $search);
        return $this->db->count_all_results($this->table);
    }

    public function get_by_id($id)
    {
        return $this->db->select('id, nama, kontak, alamat, deskripsi')
            ->get_where($this->table, array('id' => (int) $id))->row();
    }

    // Cek nama kembar (abaikan id sendiri saat edit)
    public function nama_dipakai($nama, $kecuali_id = 0)
    {
        $this->db->where('nama', $nama);
        if ((int) $kecuali_id > 0) {
            $this->db->where('id <>', (int) $kecuali_id);
        }
        if ($this->db->field_exists('aktif', $this->table)) {
            $this->db->where('aktif', 1);
        }
        return $this->db->count_all_results($this->table) > 0;
    }

    public function insert_supplier($data)
    {
        return $this->db->insert($this->table, $this->_saring($data));
    }

    public function update_supplier($id, $data)
    {
        return $this->db->where('id', (int) $id)->update($this->table, $this->_saring($data));
    }

    // Hanya kolom yang benar-benar ada di tabel
    private function _saring($data)
    {
        return array_intersect_key($data, array_flip($this->db->list_fields($this->table)));
    }

    // Sudah dipakai di PO / item SO?
    public function dipakai($id)
    {
        $pakai = 0;
        if ($this->db->table_exists('pm_po')) {
            $pakai += $this->db->where('id_supplier', (int) $id)->count_all_results('pm_po');
        }
        if ($this->db->field_exists('id_supplier', 'sales_order_detail')) {
            $pakai += $this->db->where('id_supplier', (int) $id)->count_all_results('sales_order_detail');
        }
        return $pakai > 0;
    }

    // Supplier yang sudah dipakai tidak dihapus (PO lama tetap butuh datanya), hanya dinonaktifkan
    public function hapus($id)
    {
        if (!$this->get_by_id($id)) {
            return array(false, 'Data tidak ditemukan!');
        }
        if ($this->dipakai($id)) {
            if ($this->db->field_exists('aktif', $this->table)) {
                $this->db->where('id', (int) $id)->update($this->table, array('aktif' => 0));
                return array(true, 'Supplier sudah dipakai di PO / SO, jadi dinonaktifkan (tidak dihapus).');
            }
            return array(false, 'Supplier sudah dipakai di PO / SO, tidak bisa dihapus.');
        }
        if (!$this->db->where('id', (int) $id)->delete($this->table)) {
            $err = $this->db->error();
            log_message('error', 'Hapus supplier gagal: ' . $err['message']);
            return array(false, 'Gagal menghapus data dari database.');
        }
        return array(true, 'Supplier dihapus.');
    }
}
