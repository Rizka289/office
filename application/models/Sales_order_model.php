<?php defined('BASEPATH') or exit('No direct script access allowed');

class Sales_order_model extends CI_Model
{
    public function generate_no($tanggal)
    {
        $tahun = (int) date('Y', strtotime($tanggal));
        $bulan = date('m', strtotime($tanggal));

        $row = $this->db->select_max('no_urut', 'maks')
            ->where('tahun', $tahun)
            ->get('sales_order')->row();

        $urut = ((int) $row->maks) + 1;
        return array(
            'no_urut' => $urut,
            'tahun'   => $tahun,
            'no_so'   => sprintf('%02d/SO-OPJI/%s/%d', $urut, $bulan, $tahun),
        );
    }

    public function get_penawaran_siap()
    {
        return $this->db
            ->select('p.id, p.no_surat, p.tanggal, p.grand_total, c.nama AS nama_customer')
            ->from('penawaran p')
            ->join('customer c', 'c.id = p.id_customer', 'left')
            ->where('p.status', 'approved')
            ->where('p.id NOT IN (SELECT id_penawaran FROM sales_order)', null, false)
            ->order_by('p.tanggal', 'DESC')
            ->get()->result();
    }

    // Tabel 1 + 2 + 3: header, item, aksesoris (aksesoris ditempel ke tiap item)
    public function get_penawaran_lengkap($id)
    {
        $header = $this->db
            ->select('p.*, c.nama AS nama_customer, c.alamat AS alamat_customer')
            ->from('penawaran p')
            ->join('customer c', 'c.id = p.id_customer', 'left')
            ->where('p.id', (int) $id)
            ->get()->row();
        if (!$header) {
            return null;
        }

        $items = $this->db->where('id_penawaran', (int) $id)
            ->order_by('id', 'ASC')->get('penawaran_detail')->result();

        $this->_tempel_aksesoris($items, 'penawaran_detail_aksesoris', 'id_penawaran_detail');
        return array('header' => $header, 'items' => $items);
    }

    // Untuk halaman detail / cetak SO
    public function get_so_lengkap($id)
    {
        $header = $this->db
            ->select('so.*, c.nama AS nama_customer, c.alamat AS alamat_customer')
            ->from('sales_order so')
            ->join('customer c', 'c.id = so.id_customer', 'left')
            ->where('so.id', (int) $id)
            ->get()->row();
        if (!$header) {
            return null;
        }

        $items = $this->db->where('id_so', (int) $id)
            ->order_by('id', 'ASC')->get('sales_order_detail')->result();

        $this->_tempel_aksesoris($items, 'sales_order_detail_aksesoris', 'id_so_detail');
        return array('header' => $header, 'items' => $items);
    }

    // satu query untuk semua aksesoris, lalu dibagi ke item masing-masing
    private function _tempel_aksesoris(&$items, $tabel, $kolom_fk)
    {
        $ids = array();
        foreach ($items as $it) {
            $it->aksesoris = array();
            $ids[] = (int) $it->id;
        }
        if (!$ids) {
            return;
        }
        $aks = $this->db->where_in($kolom_fk, $ids)->order_by('id', 'ASC')->get($tabel)->result();
        $peta = array();
        foreach ($items as $it) {
            $peta[(int) $it->id] = $it;
        }
        foreach ($aks as $a) {
            $peta[(int) $a->$kolom_fk]->aksesoris[] = $a;
        }
    }

    public function get_all_sales_order($filter = array())
    {
        $this->db->select('so.*, c.nama AS nama_customer, (SELECT COUNT(*) FROM sales_order_detail sod WHERE sod.id_so = so.id) AS total_item')
            ->from('sales_order so')
            ->join('customer c', 'c.id = so.id_customer', 'left');
        if (!empty($filter['q'])) {
            $this->db->group_start()
                ->like('so.no_so', $filter['q'])
                ->or_like('so.no_po_customer', $filter['q'])
                ->or_like('c.nama', $filter['q'])
                ->group_end();
        }
        if (!empty($filter['dari'])) {
            $this->db->where('so.tanggal >=', $filter['dari']);
        }
        if (!empty($filter['sampai'])) {
            $this->db->where('so.tanggal <=', $filter['sampai']);
        }
        return $this->db->order_by('so.tanggal', 'DESC')->order_by('so.id', 'DESC')->get()->result();
    }

    // Salin penawaran (3 tabel) -> sales_order (3 tabel) dalam satu transaksi
    public function simpan_dari_penawaran($id_penawaran, $input, $id_user)
    {
        $this->db->trans_begin();

        $p = $this->db->query('SELECT * FROM penawaran WHERE id = ? FOR UPDATE', array((int) $id_penawaran))->row();
        if (!$p || $p->status !== 'approved') {
            $this->db->trans_rollback();
            return array(false, 'Penawaran belum di-approve atau tidak ditemukan.');
        }
        if ($this->db->where('id_penawaran', $p->id)->count_all_results('sales_order') > 0) {
            $this->db->trans_rollback();
            return array(false, 'Penawaran ini sudah dibuatkan Sales Order.');
        }

        $no = $this->generate_no($input['tanggal']);

        // ---- Tabel 1: header ----
        $this->db->insert('sales_order', array(
            'no_so'          => $no['no_so'],
            'no_urut'        => $no['no_urut'],
            'tahun'          => $no['tahun'],
            'id_penawaran'   => $p->id,
            'id_customer'    => $p->id_customer,
            'tanggal'        => $input['tanggal'],
            'no_po_customer' => $input['no_po_customer'],
            'tgl_kirim'      => $input['tgl_kirim'] !== '' ? $input['tgl_kirim'] : null,
            'alamat_kirim'   => $input['alamat_kirim'],
            'termin'         => $input['termin'],
            'dp_persen'      => $input['dp_persen'],
            'subtotal'       => $p->subtotal,
            'ppn_persen'     => $p->ppn_persen,
            'ppn_nominal'    => $p->ppn_nominal,
            'pph_persen'     => $p->pph_persen,
            'pph_nominal'    => $p->pph_nominal,
            'biaya_pasang'   => $p->biaya_pasang,
            'biaya_ongkir'   => $p->biaya_ongkir,
            'grand_total'    => $p->grand_total,
            'catatan'        => $input['catatan'],
            'status'         => 'draft',
            'id_user'        => $id_user,
        ));
        $id_so = $this->db->insert_id();

        // ---- Tabel 2 + 3: item, lalu aksesoris per item ----
        // Kolom penawaran_detail & sales_order_detail namanya sama, jadi cukup
        // buang id & id_penawaran lalu isi id_so. Dilooping karena id baru tiap item
        // dibutuhkan untuk menyambungkan aksesoris.
        $items = $this->db->where('id_penawaran', $p->id)->order_by('id', 'ASC')->get('penawaran_detail')->result_array();

        foreach ($items as $row) {
            $id_lama = (int) $row['id'];
            unset($row['id'], $row['id_penawaran']);
            $row['id_so'] = $id_so;
            $this->db->insert('sales_order_detail', $row);
            $id_baru = $this->db->insert_id();

            $aks = $this->db->where('id_penawaran_detail', $id_lama)->get('penawaran_detail_aksesoris')->result_array();
            foreach ($aks as $a) {
                $this->db->insert('sales_order_detail_aksesoris', array(
                    'id_so_detail' => $id_baru,
                    'nama'         => $a['nama'],
                    'qty'          => $a['qty'],
                    'harga'        => $a['harga'],
                    'total_harga'  => $a['total_harga'],
                ));
            }
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array(false, 'Gagal menyimpan Sales Order.');
        }
        $this->db->trans_commit();
        return array(true, $no['no_so']);
    }
}