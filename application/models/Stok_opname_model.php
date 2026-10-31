<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stok_opname_model extends CI_Model {

    public function get_all_barang()
    {
        return $this->db->get('barang')->result_array();
    }

    public function get_all_location()
    {
        return $this->db->get('locations')->result_array();
    }

    /**
     * Kunci stok = id_barang + id_location + opsi + ukuran.
     * $data['mode'] :
     *   'tambah' => qty DITAMBAHKAN ke stok saat ini (stok awal / barang masuk)
     *   'opname' => qty adalah JUMLAH FISIK sebenarnya; stok sistem disamakan,
     *               selisihnya dicatat sebagai masuk/keluar
     */
    public function proses_stok_opname($data)
    {
        $mode   = isset($data['mode']) ? $data['mode'] : 'tambah';
        $qty    = (int) $data['qty_fisik'];
        $opsi   = (string) $data['opsi'];
        $ukuran = isset($data['ukuran']) ? (string) $data['ukuran'] : '';   // '' = tanpa ukuran

        $this->db->trans_begin();

        // 1. Stok sistem saat ini untuk barang + lokasi + opsi + UKURAN yang sama.
        //    FOR UPDATE: kunci baris supaya 2 user yang simpan bersamaan
        //    tidak membaca stok_sebelum yang sama.
        $rows = $this->db->query(
            'SELECT id, stok FROM stok_barang
             WHERE id_barang = ? AND id_location = ? AND opsi = ? AND ukuran = ?
             ORDER BY id ASC FOR UPDATE',
            [$data['id_barang'], $data['id_location'], $opsi, $ukuran]
        )->result();

        $stok_sebelum = 0;
        foreach ($rows as $r) {
            $stok_sebelum += (int) $r->stok;
        }

        // 2. Tentukan stok sesudah sesuai mode
        if ($mode === 'opname') {
            $stok_sesudah = $qty;                  // set sama dengan jumlah fisik
        } else {
            $stok_sesudah = $stok_sebelum + $qty;  // tambah ke stok yang ada
        }

        $selisih    = $stok_sesudah - $stok_sebelum;
        $qty_masuk  = $selisih > 0 ? $selisih : 0;
        $qty_keluar = $selisih < 0 ? abs($selisih) : 0;

        // 3. Update / Insert ke stok_barang
        if (!empty($rows)) {
            // selisih diterapkan ke baris pertama; total semua baris jadi = stok_sesudah
            $this->db->set('stok', 'stok + (' . (int) $selisih . ')', false);
            $this->db->where('id', $rows[0]->id);
            $this->db->update('stok_barang');
        } else {
            $this->db->insert('stok_barang', [
                'id_barang'   => $data['id_barang'],
                'id_location' => $data['id_location'],
                'opsi'        => $opsi,
                'ukuran'      => $ukuran,
                'stok'        => $stok_sesudah
            ]);
        }

        // 4. Catat ke stok_riwayat
        $ket_default = ($mode === 'opname') ? 'Penyesuaian Stok Opname' : 'Input Stok Awal';

        $this->db->insert('stok_riwayat', [
            'id_barang'       => $data['id_barang'],
            'id_location'     => $data['id_location'],
            'jenis_transaksi' => 'STOK_OPNAME',
            'id_referensi'    => NULL,
            'ukuran'          => $ukuran,
            'opsi'            => $opsi,
            'qty_masuk'       => $qty_masuk,
            'qty_keluar'      => $qty_keluar,
            'stok_sebelum'    => $stok_sebelum,
            'stok_sesudah'    => $stok_sesudah,
            'keterangan'      => !empty($data['keterangan']) ? $data['keterangan'] : $ket_default,
            'created_by'      => $data['created_by'],
            'created_at'      => date('Y-m-d H:i:s')
        ]);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return FALSE;
        }

        $this->db->trans_commit();
        return TRUE;
    }
}