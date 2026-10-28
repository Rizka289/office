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

    /**
     * $data['mode'] :
     *   'tambah' => qty DITAMBAHKAN ke stok saat ini (stok awal / barang masuk)
     *   'opname' => qty adalah JUMLAH FISIK sebenarnya; stok sistem disamakan,
     *               selisihnya dicatat sebagai masuk/keluar
     */
    public function proses_stok_opname($data)
    {
        $mode = isset($data['mode']) ? $data['mode'] : 'tambah';
        $qty  = (int) $data['qty_fisik'];

        $this->db->trans_begin();

        // 1. Stok sistem saat ini.
        //    Pakai SUM semua baris barang+lokasi (grid stok juga menjumlah),
        //    supaya kalau ada lebih dari 1 baris, stok_sebelum tidak salah hitung.
        $rows = $this->db->order_by('id', 'ASC')->get_where('stok_barang', [
            'id_barang'   => $data['id_barang'],
            'id_location' => $data['id_location']
        ])->result();

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
                'stok'        => $stok_sesudah
            ]);
        }

        // 4. Catat ke stok_riwayat
        $ket_default = ($mode === 'opname') ? 'Penyesuaian Stok Opname' : 'Input Stok Awal';

        $data_riwayat = [
            'id_barang'       => $data['id_barang'],
            'id_location'     => $data['id_location'],
            'jenis_transaksi' => 'STOK_OPNAME', // ENUM yang sudah ada
            'id_referensi'    => NULL,
            'ukuran'          => isset($data['ukuran']) ? $data['ukuran'] : NULL,
            'qty_masuk'       => $qty_masuk,
            'qty_keluar'      => $qty_keluar,
            'stok_sebelum'    => $stok_sebelum,
            'stok_sesudah'    => $stok_sesudah,
            'keterangan'      => !empty($data['keterangan']) ? $data['keterangan'] : $ket_default,
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