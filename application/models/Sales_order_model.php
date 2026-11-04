<?php defined('BASEPATH') or exit('No direct script access allowed');

class Sales_order_model extends CI_Model
{
    // $kunci = true hanya dipakai saat simpan (di dalam transaksi) supaya
    // dua orang yang simpan bersamaan tidak mendapat nomor yang sama.
    public function generate_no($tanggal, $kunci = false)
    {
        $tahun = (int) date('Y', strtotime($tanggal));
        $bulan = date('m', strtotime($tanggal));

        $sql = 'SELECT COALESCE(MAX(no_urut), 0) AS maks FROM sales_order WHERE tahun = ?'
            . ($kunci ? ' FOR UPDATE' : '');
        $q   = $this->db->query($sql, array($tahun));
        $row = $q ? $q->row() : null;

        $urut = ($row ? (int) $row->maks : 0) + 1;
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
            ->where("p.id NOT IN (SELECT id_penawaran FROM sales_order WHERE status <> 'batal')", null, false)
            ->order_by('p.tanggal', 'DESC')
            ->get()->result();
    }

    // Penawaran: header + item + aksesoris + kaca
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

        $this->_tempel_anak($items, 'penawaran_detail_aksesoris', 'id_penawaran_detail', 'aksesoris');
        $this->_tempel_anak($items, 'penawaran_detail_kaca', 'id_penawaran_detail', 'kaca');
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

        $this->_tempel_anak($items, 'sales_order_detail_aksesoris', 'id_so_detail', 'aksesoris');
        $this->_tempel_anak($items, 'sales_order_detail_kaca', 'id_so_detail', 'kaca');
        return array('header' => $header, 'items' => $items);
    }

    // Satu query untuk semua baris anak (aksesoris / kaca), lalu dibagi ke tiap item.
    // $prop = nama properti di item: 'aksesoris' atau 'kaca'
    // Kalau tabel anak belum ada, halaman tetap jalan (anak dikosongkan).
    private function _tempel_anak(&$items, $tabel, $kolom_fk, $prop)
    {
        $ids  = array();
        $peta = array();
        foreach ($items as $it) {
            $it->$prop = array();
            $ids[]     = (int) $it->id;
            $peta[(int) $it->id] = $it;
        }
        if (!$ids) {
            return;
        }
        if (!$this->db->table_exists($tabel)) {
            log_message('error', 'Sales_order_model: tabel ' . $tabel . ' belum ada.');
            return;
        }
        $rows = $this->db->where_in($kolom_fk, $ids)->order_by('id', 'ASC')->get($tabel)->result();
        foreach ($rows as $r) {
            $k = (int) $r->$kolom_fk;
            if (isset($peta[$k])) {
                $peta[$k]->{$prop}[] = $r;
            }
        }
    }

    public function get_all_sales_order($filter = array())
    {
        $this->db->select(
            "so.*, c.nama AS nama_customer,
            (SELECT COUNT(*) FROM sales_order_detail sod WHERE sod.id_so = so.id) AS total_item,
            (SELECT COALESCE(SUM(sp.nominal), 0) FROM sales_order_pembayaran sp
                WHERE sp.id_so = so.id AND sp.jenis = 'dp') AS total_dp,
            (SELECT COALESCE(SUM(sp.nominal), 0) FROM sales_order_pembayaran sp
                WHERE sp.id_so = so.id) AS total_diterima,
            (so.grand_total - (SELECT COALESCE(SUM(sp.nominal), 0) FROM sales_order_pembayaran sp
                WHERE sp.id_so = so.id)) AS sisa_bayar",
            false
        )
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

    // ------------------------------------------------------------------
    // Salin penawaran (4 tabel) -> sales_order (4 tabel) dalam satu transaksi.
    //
    // PERBAIKAN:
    // - Kolom disaring memakai list_fields() (sama seperti Penawaran_model::_insert_items)
    //   sehingga kolom baru di penawaran (foto, keterangan, dll) yang belum ada
    //   di tabel SO tidak membuat error "Unknown column".
    // - db_debug dimatikan selama proses supaya error DB tidak menampilkan
    //   halaman error + transaksi menggantung; pesan error dikembalikan ke user & log.
    // - biaya_pasang / biaya_ongkir NULL di penawaran diubah jadi 0.
    // - foto aksesoris ikut tersalin (jika kolomnya ada di tabel SO).
    // ------------------------------------------------------------------
    public function simpan_dari_penawaran($id_penawaran, $input, $id_user)
    {
        $debug_lama         = $this->db->db_debug;
        $this->db->db_debug = false;
        $hasil              = $this->_proses_simpan($id_penawaran, $input, $id_user);
        $this->db->db_debug = $debug_lama;
        return $hasil;
    }

    // Rollback + catat error DB ke log, kembalikan array(false, pesan)
    private function _gagal($pesan)
    {
        $e = $this->db->error();
        $this->db->trans_rollback();
        if (!empty($e['code'])) {
            log_message('error', 'Sales_order_model: ' . $pesan . ' | DB ' . $e['code'] . ': ' . $e['message']);
            $pesan .= ' (DB ' . $e['code'] . ': ' . $e['message'] . ')';
        }
        return array(false, $pesan);
    }

    // Ambil hanya key yang kolomnya ada di $tabel; yang tidak ada dicatat di log
    private function _saring_kolom($tabel, $data)
    {
        $kolom  = $this->db->list_fields($tabel);
        $hilang = array_diff(array_keys($data), $kolom);
        if ($hilang) {
            log_message('error', $tabel . ': kolom tidak ada, dilewati -> ' . implode(', ', $hilang));
        }
        return array_intersect_key($data, array_flip($kolom));
    }

    private function _proses_simpan($id_penawaran, $input, $id_user)
    {
        $this->db->trans_begin();

        $q = $this->db->query('SELECT * FROM penawaran WHERE id = ? FOR UPDATE', array((int) $id_penawaran));
        $p = $q ? $q->row() : null;
        if (!$p || $p->status !== 'approved') {
            $this->db->trans_rollback();
            return array(false, 'Penawaran belum di-approve atau tidak ditemukan.');
        }
        if ($this->db->where('id_penawaran', $p->id)->where('status !=', 'batal')->count_all_results('sales_order') > 0) {
            $this->db->trans_rollback();
            return array(false, 'Penawaran ini sudah dibuatkan Sales Order.');
        }

        $no = $this->generate_no($input['tanggal'], true);

        // ---- Tabel 1: header ----
        $header = $this->_saring_kolom('sales_order', array(
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
            'subtotal'       => (float) $p->subtotal,
            'ppn_persen'     => (float) $p->ppn_persen,
            'ppn_nominal'    => (float) $p->ppn_nominal,
            'pph_persen'     => (float) $p->pph_persen,
            'pph_nominal'    => (float) $p->pph_nominal,
            'biaya_pasang'   => (float) $p->biaya_pasang,   // NULL -> 0
            'biaya_ongkir'   => (float) $p->biaya_ongkir,   // NULL -> 0
            'grand_total'    => (float) $p->grand_total,
            'catatan'        => $input['catatan'],
            'status'         => 'draft',
            'id_user'        => $id_user,
        ));
        if (!$this->db->insert('sales_order', $header)) {
            return $this->_gagal('Gagal menyimpan header Sales Order.');
        }
        $id_so = $this->db->insert_id();

        // ---- Tabel 2: item; Tabel 3: aksesoris; Tabel 4: kaca ----
        $items = $this->db->where('id_penawaran', $p->id)->order_by('id', 'ASC')
            ->get('penawaran_detail')->result_array();

        $ada_aks  = $this->db->table_exists('sales_order_detail_aksesoris');
        $ada_kaca = $this->db->table_exists('sales_order_detail_kaca');

        foreach ($items as $row) {
            $id_lama = (int) $row['id'];
            unset($row['id'], $row['id_penawaran']);
            $row['id_so'] = $id_so;
            $row = $this->_saring_kolom('sales_order_detail', $row);

            if (!$this->db->insert('sales_order_detail', $row)) {
                return $this->_gagal('Gagal menyimpan item Sales Order.');
            }
            $id_baru = $this->db->insert_id();

            // aksesoris (salin semua kolom yang ada, termasuk foto)
            $aks = $this->db->where('id_penawaran_detail', $id_lama)
                ->get('penawaran_detail_aksesoris')->result_array();
            if ($aks && !$ada_aks) {
                return $this->_gagal('Tabel sales_order_detail_aksesoris belum dibuat.');
            }
            foreach ($aks as $a) {
                unset($a['id'], $a['id_penawaran_detail']);
                $a['id_so_detail'] = $id_baru;
                $a = $this->_saring_kolom('sales_order_detail_aksesoris', $a);
                if (!$this->db->insert('sales_order_detail_aksesoris', $a)) {
                    return $this->_gagal('Gagal menyimpan aksesoris Sales Order.');
                }
            }

            // kaca
            $kaca = $this->db->where('id_penawaran_detail', $id_lama)
                ->get('penawaran_detail_kaca')->result_array();
            if ($kaca && !$ada_kaca) {
                return $this->_gagal('Tabel sales_order_detail_kaca belum dibuat.');
            }
            foreach ($kaca as $k) {
                unset($k['id'], $k['id_penawaran_detail']);
                $k['id_so_detail'] = $id_baru;
                $k = $this->_saring_kolom('sales_order_detail_kaca', $k);
                if (!$this->db->insert('sales_order_detail_kaca', $k)) {
                    return $this->_gagal('Gagal menyimpan detail kaca Sales Order.');
                }
            }
        }

        if ($this->db->trans_status() === false) {
            return $this->_gagal('Gagal menyimpan Sales Order.');
        }
        $this->db->trans_commit();
        return array(true, $no['no_so']);
    }

    // Ubah status dengan syarat status lama (atomik, aman dari klik ganda)
    public function ubah_status($id, $dari, $ke)
    {
        $this->db->where('id', (int) $id)->where_in('status', $dari)->update('sales_order', array('status' => $ke));
        return $this->db->affected_rows() > 0;
    }

    public function batalkan($id)
    {
        // qty_terkirim hanya dicek kalau kolomnya ada (hindari error Unknown column)
        if ($this->db->field_exists('qty_terkirim', 'sales_order_detail')) {
            $kirim = $this->db->select_sum('qty_terkirim', 'total')->where('id_so', (int) $id)
                ->get('sales_order_detail')->row();
            if ($kirim && (float) $kirim->total > 0) {
                return array(false, 'SO tidak bisa dibatalkan karena sudah ada barang yang terkirim.');
            }
        }
        if (!$this->ubah_status($id, array('draft', 'confirmed'), 'batal')) {
            return array(false, 'SO dengan status ini tidak bisa dibatalkan.');
        }
        return array(true, 'Sales Order dibatalkan. Penawarannya bisa dibuatkan SO baru.');
    }

    // ================= PEMBAYARAN (DP / pelunasan) =================

    public function get_pembayaran($id_so)
    {
        return $this->db->where('id_so', (int) $id_so)
            ->order_by('tanggal', 'ASC')->order_by('id', 'ASC')
            ->get('sales_order_pembayaran')->result();
    }

    public function total_dibayar($id_so)
    {
        $r = $this->db->select_sum('nominal', 'total')->where('id_so', (int) $id_so)
            ->get('sales_order_pembayaran')->row();
        return $r ? (float) $r->total : 0.0;
    }

    // $d: tanggal, jenis, nominal, metode, no_referensi, catatan
    public function simpan_pembayaran($id_so, $d, $id_user)
    {
        $this->db->trans_begin();
        $so = $this->db->query('SELECT id, status, grand_total FROM sales_order WHERE id = ? FOR UPDATE', array((int) $id_so))->row();
        if (!$so || in_array($so->status, array('batal', 'selesai'), true)) {
            $this->db->trans_rollback();
            return array(false, 'SO tidak ditemukan atau sudah batal / selesai.');
        }
        $sisa = (float) $so->grand_total - $this->total_dibayar($id_so);
        if ($d['nominal'] > $sisa + 0.005) {
            $this->db->trans_rollback();
            return array(false, 'Nominal melebihi sisa tagihan (Rp ' . number_format($sisa, 0, ',', '.') . ').');
        }
        $this->db->insert('sales_order_pembayaran', array(
            'id_so'        => (int) $id_so,
            'tanggal'      => $d['tanggal'],
            'jenis'        => $d['jenis'],
            'nominal'      => $d['nominal'],
            'metode'       => $d['metode'],
            'no_referensi' => $d['no_referensi'],
            'catatan'      => $d['catatan'],
            'id_user'      => $id_user,
        ));
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array(false, 'Gagal menyimpan pembayaran.');
        }
        $this->db->trans_commit();
        return array(true, 'Pembayaran Rp ' . number_format($d['nominal'], 0, ',', '.') . ' dicatat.');
    }

    // Return: array(ok, pesan, id_so)
    public function hapus_pembayaran($id_bayar)
    {
        $b = $this->db->where('id', (int) $id_bayar)->get('sales_order_pembayaran')->row();
        if (!$b) {
            return array(false, 'Data pembayaran tidak ditemukan.', 0);
        }
        $this->db->trans_begin();
        $so = $this->db->query('SELECT id, status, grand_total, dp_persen FROM sales_order WHERE id = ? FOR UPDATE', array((int) $b->id_so))->row();
        if (!$so) {
            $this->db->trans_rollback();
            return array(false, 'SO tidak ditemukan.', 0);
        }
        if (in_array($so->status, array('produksi', 'terkirim', 'selesai', 'batal'), true)) {
            $this->db->trans_rollback();
            return array(false, 'Pembayaran tidak bisa dihapus karena SO sudah berstatus ' . $so->status . '.', (int) $so->id);
        }
        if ($so->status === 'confirmed') {
            $dp_nilai = round((float) $so->grand_total * (float) $so->dp_persen / 100);
            if ($this->total_dibayar($so->id) - (float) $b->nominal + 0.005 < $dp_nilai) {
                $this->db->trans_rollback();
                return array(false, 'Tidak bisa dihapus: DP SO yang sudah dikonfirmasi akan jadi kurang.', (int) $so->id);
            }
        }
        $this->db->where('id', (int) $id_bayar)->delete('sales_order_pembayaran');
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array(false, 'Gagal menghapus pembayaran.', (int) $so->id);
        }
        $this->db->trans_commit();
        return array(true, 'Pembayaran dihapus.', (int) $so->id);
    }

    // Draft -> Dikonfirmasi, hanya jika DP sudah tercatat sesuai termin
    public function konfirmasi($id)
    {
        $this->db->trans_begin();
        $so = $this->db->query('SELECT id, no_so, status, grand_total, dp_persen FROM sales_order WHERE id = ? FOR UPDATE', array((int) $id))->row();
        if (!$so || $so->status !== 'draft') {
            $this->db->trans_rollback();
            return array(false, 'Hanya SO berstatus Draft yang bisa dikonfirmasi.');
        }
        $dp_nilai = round((float) $so->grand_total * (float) $so->dp_persen / 100);
        $dibayar  = $this->total_dibayar($so->id);
        if ($dibayar + 0.005 < $dp_nilai) {
            $this->db->trans_rollback();
            return array(false, 'DP belum cukup. Dibutuhkan Rp ' . number_format($dp_nilai, 0, ',', '.')
                . ', tercatat Rp ' . number_format($dibayar, 0, ',', '.') . '.');
        }
        $this->db->where('id', $so->id)->update('sales_order', array('status' => 'confirmed'));
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array(false, 'Gagal mengonfirmasi SO.');
        }
        $this->db->trans_commit();
        return array(true, 'SO ' . $so->no_so . ' dikonfirmasi. Siap diproses ke produksi.');
    }
}