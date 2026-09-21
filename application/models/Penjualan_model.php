<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Penjualan_model extends CI_Model {

    public function get_all_barang_stok()
    {
        // Mengambil barang jadi yang memiliki saldo stok > 0
        $this->db->select('sb.*, b.kode_barang, b.nama, b.harga_satuan, l.zone_name');
        $this->db->from('stok_barang sb');
        $this->db->join('barang b', 'b.id = sb.id_barang');
        $this->db->join('locations l', 'l.id = sb.id_location');
        $this->db->where('sb.stok >', 0);
        return $this->db->get()->result_array();
    }

    // Daftar customer untuk dropdown di form penjualan (key: id, nama)
    public function get_list_customer()
    {
        return $this->db->select('id, nama')
                        ->order_by('nama', 'ASC')
                        ->get('customer')
                        ->result_array();
    }

    public function get_all_customer($id){
    
        $this->selectWithCustomer();
        $this->db->where('penjualan.id', $id);
        return $this->db->get('penjualan')->row_array();
    
    }
     private function selectWithCustomer()
    {
        $this->db->select('penjualan.*, customer.nama');
        $this->db->join('customer', 'customer.id = penjualan.id_customer', 'left');
    }

    public function simpan_penjualan($data_header, $items)
    {
        $this->db->trans_begin();

        // 1. Insert Header Penjualan
        $this->db->insert('penjualan', $data_header);
        $id_penjualan = $this->db->insert_id();

        foreach ($items as $item) {
            // 2. Ambil stok saat ini dan kunci barisnya (FOR UPDATE), supaya dua transaksi
            //    yang berjalan bersamaan tidak saling menimpa saldo stok
            $row_stok = $this->db->query(
                'SELECT stok FROM stok_barang WHERE id_barang = ? AND id_location = ? FOR UPDATE',
                [$item['id_barang'], $item['id_location']]
            )->row();

            $stok_sebelum = $row_stok ? (int) $row_stok->stok : 0;
            $stok_sesudah = $stok_sebelum - $item['qty'];

            // Tolak jika stok tidak cukup (stok tidak boleh minus)
            if ($stok_sesudah < 0) {
                $this->db->trans_rollback();
                return FALSE;
            }

            // 3. Insert Detail Penjualan
            $this->db->insert('penjualan_detail', [
                'id_penjualan' => $id_penjualan,
                'id_barang'    => $item['id_barang'],
                'id_location'  => $item['id_location'],
                'qty'          => $item['qty'],
                'harga_satuan' => $item['harga_satuan'],
                'subtotal'     => $item['qty'] * $item['harga_satuan']
            ]);

            // 4. Update Saldo di stok_barang
            $this->db->where('id_barang', $item['id_barang']);
            $this->db->where('id_location', $item['id_location']);
            $this->db->update('stok_barang', ['stok' => $stok_sesudah]);

            // 5. Catat Log ke stok_riwayat
            $this->db->insert('stok_riwayat', [
                'id_barang'       => $item['id_barang'],
                'id_location'     => $item['id_location'],
                'jenis_transaksi' => 'PENJUALAN_LANGSUNG',
                'id_referensi'    => $id_penjualan,
                'qty_masuk'       => 0,
                'qty_keluar'      => $item['qty'],
                'stok_sebelum'    => $stok_sebelum,
                'stok_sesudah'    => $stok_sesudah,
                'keterangan'      => 'Penjualan Langsung Faktur: ' . $data_header['no_faktur'],
                'created_by'      => $data_header['created_by'],
                'created_at'      => date('Y-m-d H:i:s')
            ]);
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return FALSE;
        }

        $this->db->trans_commit();
        return TRUE;
    }
}