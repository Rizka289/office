<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stok_opname_model extends CI_Model {

    public function get_all_barang()
    {
        return $this->db->get('barang')->result_array(); // Sesuaikan nama tabel barang
    }

    public function get_all_location()
    {
        return $this->db->get('locations')->result_array(); // Sesuaikan nama tabel lokasi
    }

    public function proses_stok_opname($data)
    {
        $this->db->trans_begin();

        // 1. Ambil Stok Sistem saat ini
        $row_stok = $this->db->get_where('stok_barang', [
            'id_barang'   => $data['id_barang'],
            'id_location' => $data['id_location']
        ])->row();

        $stok_sebelum = $row_stok ? (int) $row_stok->stok : 0;
        $stok_sesudah = $data['qty_fisik'];
        $selisih      = $stok_sesudah - $stok_sebelum;

        $qty_masuk  = $selisih > 0 ? $selisih : 0;
        $qty_keluar = $selisih < 0 ? abs($selisih) : 0;

        // 2. Update / Insert ke stok_barang
        if ($row_stok) {
            $this->db->where('id', $row_stok->id);
            $this->db->update('stok_barang', ['stok' => $stok_sesudah]);
        } else {
            $this->db->insert('stok_barang', [
                'id_barang'   => $data['id_barang'],
                'id_location' => $data['id_location'],
                'stok'        => $stok_sesudah
            ]);
        }

        // 3. Catat ke stok_riwayat
        $data_riwayat = [
            'id_barang'       => $data['id_barang'],
            'id_location'     => $data['id_location'],
            'jenis_transaksi' => 'STOK_OPNAME', // Memakai ENUM STOK_OPNAME
            'id_referensi'    => NULL,
            'qty_masuk'       => $qty_masuk,
            'qty_keluar'      => $qty_keluar,
            'stok_sebelum'    => $stok_sebelum,
            'stok_sesudah'    => $stok_sesudah,
            'keterangan'      => !empty($data['keterangan']) ? $data['keterangan'] : 'Penyesuaian Stok Opname',
            'created_by'      => $data['created_by'],
            'created_at'      => date('Y-m-d H:i:s')
        ];

        $this->db->insert('stok_riwayat', $data_riwayat);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return FALSE;
        }

        $this->db->trans_commit();
        return TRUE;
    }
}