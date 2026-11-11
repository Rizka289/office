<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BIAYA OPERASIONAL (pengeluaran kantor di luar PO supplier)
 *
 * Tabel: biaya_kategori, biaya_operasional  (lihat database/biaya_operasional.sql)
 * No. bukti otomatis: BO/YYYY/MM/0001 (urut per bulan)
 */
class Biaya_model extends CI_Model
{
    // ================= KATEGORI =================

    // $hanya_aktif = true untuk pilihan di form
    public function get_kategori($hanya_aktif = false)
    {
        $this->db->select('k.*, (SELECT COUNT(*) FROM biaya_operasional b WHERE b.id_kategori = k.id) AS dipakai', false)
            ->from('biaya_kategori k');
        if ($hanya_aktif) {
            $this->db->where('k.aktif', 1);
        }
        return $this->db->order_by('k.nama', 'ASC')->get()->result();
    }

    public function get_kategori_by_id($id)
    {
        return $this->db->get_where('biaya_kategori', array('id' => (int) $id))->row();
    }

    public function nama_kategori_dipakai($nama, $kecuali_id = 0)
    {
        return $this->db->where('nama', $nama)->where('id <>', (int) $kecuali_id)
            ->count_all_results('biaya_kategori') > 0;
    }

    public function simpan_kategori($id, $nama)
    {
        if ($id) {
            return $this->db->where('id', (int) $id)->update('biaya_kategori', array('nama' => $nama));
        }
        return $this->db->insert('biaya_kategori', array('nama' => $nama, 'aktif' => 1));
    }

    public function set_aktif_kategori($id, $aktif)
    {
        return $this->db->where('id', (int) $id)->update('biaya_kategori', array('aktif' => $aktif ? 1 : 0));
    }

    // Hapus hanya kalau belum pernah dipakai. Return: array(ok, pesan)
    public function hapus_kategori($id)
    {
        $k = $this->get_kategori_by_id($id);
        if (!$k) {
            return array(false, 'Kategori tidak ditemukan.');
        }
        if ($this->db->where('id_kategori', (int) $id)->count_all_results('biaya_operasional') > 0) {
            return array(false, 'Kategori "' . $k->nama . '" sudah dipakai transaksi. Nonaktifkan saja supaya tidak muncul di form.');
        }
        $this->db->where('id', (int) $id)->delete('biaya_kategori');
        return array(true, 'Kategori "' . $k->nama . '" dihapus.');
    }

    // ================= TRANSAKSI =================

    // $f: dari, sampai, id_kategori, q
    public function get_list($f)
    {
        $this->db->select('b.*, k.nama AS nama_kategori, COALESCE(NULLIF(u.nama, \'\'), u.username) AS nama_user', false)
            ->from('biaya_operasional b')
            ->join('biaya_kategori k', 'k.id = b.id_kategori', 'left')
            ->join('users u', 'u.id = b.id_user', 'left');
        $this->_filter($f);
        return $this->db->order_by('b.tanggal', 'DESC')->order_by('b.id', 'DESC')->get()->result();
    }

    // Total per kategori pada periode filter (untuk ringkasan)
    public function get_per_kategori($f)
    {
        $this->db->select('k.id, k.nama, COUNT(b.id) AS jumlah, SUM(b.nominal) AS total', false)
            ->from('biaya_operasional b')
            ->join('biaya_kategori k', 'k.id = b.id_kategori', 'left');
        $this->_filter($f);
        return $this->db->group_by('k.id, k.nama')->order_by('total', 'DESC')->get()->result();
    }

    private function _filter($f)
    {
        if (!empty($f['dari'])) {
            $this->db->where('b.tanggal >=', $f['dari']);
        }
        if (!empty($f['sampai'])) {
            $this->db->where('b.tanggal <=', $f['sampai']);
        }
        if (!empty($f['id_kategori'])) {
            $this->db->where('b.id_kategori', (int) $f['id_kategori']);
        }
        if (!empty($f['q'])) {
            $this->db->group_start()
                ->like('b.no_bukti', $f['q'])
                ->or_like('b.keterangan', $f['q'])
                ->or_like('b.dibayar_ke', $f['q'])
                ->group_end();
        }
    }

    public function get($id)
    {
        return $this->db->get_where('biaya_operasional', array('id' => (int) $id))->row();
    }

    // Return: array(ok, pesan)
    public function simpan($id, $d, $id_user)
    {
        if ($id) {
            $d['updated_at'] = date('Y-m-d H:i:s');
            $ok = $this->db->where('id', (int) $id)->update('biaya_operasional', $d);
            return array($ok, $ok ? 'Pengeluaran diperbarui.' : 'Gagal memperbarui pengeluaran.');
        }

        // No. bukti urut per bulan; dikunci supaya tidak dobel saat dua orang simpan bersamaan
        $this->db->trans_begin();
        $prefix = 'BO/' . date('Y/m', strtotime($d['tanggal'])) . '/';
        $last = $this->db->query(
            'SELECT no_bukti FROM biaya_operasional WHERE no_bukti LIKE ? ORDER BY no_bukti DESC LIMIT 1 FOR UPDATE',
            array($prefix . '%')
        )->row();
        $urut = $last ? ((int) substr($last->no_bukti, strlen($prefix))) + 1 : 1;

        $d['no_bukti'] = $prefix . str_pad($urut, 4, '0', STR_PAD_LEFT);
        $d['id_user']  = $id_user;
        $this->db->insert('biaya_operasional', $d);
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array(false, 'Gagal menyimpan pengeluaran.');
        }
        $this->db->trans_commit();
        return array(true, 'Pengeluaran ' . $d['no_bukti'] . ' Rp ' . number_format($d['nominal'], 0, ',', '.') . ' dicatat.');
    }

    public function hapus($id)
    {
        return $this->db->where('id', (int) $id)->delete('biaya_operasional');
    }

    // ================= REKAP =================

    // Pivot kategori x bulan untuk satu tahun: array(id_kategori => array(nama, bulan[1..12], total))
    public function get_rekap_tahun($tahun)
    {
        $rows = $this->db->query(
            'SELECT k.id, k.nama, MONTH(b.tanggal) AS bln, SUM(b.nominal) AS total
               FROM biaya_operasional b
               LEFT JOIN biaya_kategori k ON k.id = b.id_kategori
              WHERE b.tanggal BETWEEN ? AND ?
              GROUP BY k.id, k.nama, MONTH(b.tanggal)
              ORDER BY k.nama',
            array($tahun . '-01-01', $tahun . '-12-31')
        )->result();

        $hasil = array();
        foreach ($rows as $r) {
            $k = (int) $r->id;
            if (!isset($hasil[$k])) {
                $hasil[$k] = array('nama' => $r->nama ?: '(tanpa kategori)', 'bulan' => array_fill(1, 12, 0), 'total' => 0);
            }
            $hasil[$k]['bulan'][(int) $r->bln] += (float) $r->total;
            $hasil[$k]['total']                += (float) $r->total;
        }
        return $hasil;
    }

    // Uang masuk (pembayaran customer) per bulan, untuk perbandingan kas masuk vs keluar
    public function get_kas_masuk_tahun($tahun)
    {
        $bulan = array_fill(1, 12, 0);
        if (!$this->db->table_exists('sales_order_pembayaran')) {
            return $bulan;
        }
        $rows = $this->db->query(
            'SELECT MONTH(tanggal) AS bln, SUM(nominal) AS total FROM sales_order_pembayaran
              WHERE tanggal BETWEEN ? AND ? GROUP BY MONTH(tanggal)',
            array($tahun . '-01-01', $tahun . '-12-31')
        )->result();
        foreach ($rows as $r) {
            $bulan[(int) $r->bln] = (float) $r->total;
        }
        return $bulan;
    }
}
