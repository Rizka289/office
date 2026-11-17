<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PEMASUKAN LAIN & MODAL (uang masuk yang BUKAN dari pembayaran customer)
 *
 * Contoh: setoran modal / investasi pemilik, pinjaman bank, bunga bank,
 * penjualan aset (mobil/mesin bekas), klaim asuransi, dll.
 *
 * Tabel: kas_masuk_kategori, kas_masuk  (lihat database/kas_masuk.sql)
 * No. bukti otomatis: KM/YYYY/MM/0001 (urut per bulan)
 *
 * Setiap kategori punya JENIS supaya laporan bisa membedakan:
 *   modal      -> setoran modal / investasi (bukan laba, tapi menambah kas)
 *   pinjaman   -> pinjaman yang nanti harus dikembalikan
 *   pendapatan -> pendapatan lain-lain di luar penjualan (bunga, jual aset, dll)
 */
class Kas_masuk_model extends CI_Model
{
    public $jenis_valid = array('modal', 'pinjaman', 'pendapatan');

    public function tabel_siap()
    {
        return $this->db->table_exists('kas_masuk') && $this->db->table_exists('kas_masuk_kategori');
    }

    // ================= KATEGORI =================

    public function get_kategori($hanya_aktif = false)
    {
        $this->db->select('k.*, (SELECT COUNT(*) FROM kas_masuk m WHERE m.id_kategori = k.id) AS dipakai', false)
            ->from('kas_masuk_kategori k');
        if ($hanya_aktif) {
            $this->db->where('k.aktif', 1);
        }
        return $this->db->order_by('FIELD(k.jenis, \'modal\', \'pinjaman\', \'pendapatan\')', '', false)
            ->order_by('k.nama', 'ASC')->get()->result();
    }

    public function get_kategori_by_id($id)
    {
        return $this->db->get_where('kas_masuk_kategori', array('id' => (int) $id))->row();
    }

    public function nama_kategori_dipakai($nama, $kecuali_id = 0)
    {
        return $this->db->where('nama', $nama)->where('id <>', (int) $kecuali_id)
            ->count_all_results('kas_masuk_kategori') > 0;
    }

    public function simpan_kategori($id, $nama, $jenis)
    {
        $d = array('nama' => $nama, 'jenis' => $jenis);
        if ($id) {
            return $this->db->where('id', (int) $id)->update('kas_masuk_kategori', $d);
        }
        $d['aktif'] = 1;
        return $this->db->insert('kas_masuk_kategori', $d);
    }

    public function set_aktif_kategori($id, $aktif)
    {
        return $this->db->where('id', (int) $id)->update('kas_masuk_kategori', array('aktif' => $aktif ? 1 : 0));
    }

    // Hapus hanya kalau belum pernah dipakai. Return: array(ok, pesan)
    public function hapus_kategori($id)
    {
        $k = $this->get_kategori_by_id($id);
        if (!$k) {
            return array(false, translate('i18n_kategori_tidak_ditemukan'));
        }
        if ($this->db->where('id_kategori', (int) $id)->count_all_results('kas_masuk') > 0) {
            return array(false, translate('i18n_kategori') . $k->nama . translate('i18n_q_sudah_dipakai_transaksi'));
        }
        $this->db->where('id', (int) $id)->delete('kas_masuk_kategori');
        return array(true, translate('i18n_kategori') . $k->nama . translate('i18n_q_dihapus'));
    }

    // ================= TRANSAKSI =================

    // $f: dari, sampai, id_kategori, jenis, q
    public function get_list($f)
    {
        $this->db->select('m.*, k.nama AS nama_kategori, k.jenis, COALESCE(NULLIF(u.nama, \'\'), u.username) AS nama_user', false)
            ->from('kas_masuk m')
            ->join('kas_masuk_kategori k', 'k.id = m.id_kategori', 'left')
            ->join('users u', 'u.id = m.id_user', 'left');
        $this->_filter($f);
        return $this->db->order_by('m.tanggal', 'DESC')->order_by('m.id', 'DESC')->get()->result();
    }

    // Total per jenis (modal / pinjaman / pendapatan) pada periode filter
    public function get_per_jenis($f)
    {
        $this->db->select('k.jenis, COUNT(m.id) AS jumlah, SUM(m.nominal) AS total', false)
            ->from('kas_masuk m')
            ->join('kas_masuk_kategori k', 'k.id = m.id_kategori', 'left');
        $this->_filter($f);
        $hasil = array_fill_keys($this->jenis_valid, 0.0);
        foreach ($this->db->group_by('k.jenis')->get()->result() as $r) {
            if (isset($hasil[$r->jenis])) {
                $hasil[$r->jenis] = (float) $r->total;
            }
        }
        return $hasil;
    }

    // Total per kategori pada periode filter
    public function get_per_kategori($f)
    {
        $this->db->select('k.id, k.nama, k.jenis, COUNT(m.id) AS jumlah, SUM(m.nominal) AS total', false)
            ->from('kas_masuk m')
            ->join('kas_masuk_kategori k', 'k.id = m.id_kategori', 'left');
        $this->_filter($f);
        return $this->db->group_by('k.id, k.nama, k.jenis')->order_by('total', 'DESC')->get()->result();
    }

    private function _filter($f)
    {
        if (!empty($f['dari'])) {
            $this->db->where('m.tanggal >=', $f['dari']);
        }
        if (!empty($f['sampai'])) {
            $this->db->where('m.tanggal <=', $f['sampai']);
        }
        if (!empty($f['id_kategori'])) {
            $this->db->where('m.id_kategori', (int) $f['id_kategori']);
        }
        if (!empty($f['jenis']) && in_array($f['jenis'], $this->jenis_valid, true)) {
            $this->db->where('k.jenis', $f['jenis']);
        }
        if (!empty($f['q'])) {
            $this->db->group_start()
                ->like('m.no_bukti', $f['q'])
                ->or_like('m.keterangan', $f['q'])
                ->or_like('m.sumber', $f['q'])
                ->group_end();
        }
    }

    public function get($id)
    {
        return $this->db->get_where('kas_masuk', array('id' => (int) $id))->row();
    }

    // Return: array(ok, pesan)
    public function simpan($id, $d, $id_user)
    {
        if ($id) {
            $d['updated_at'] = date('Y-m-d H:i:s');
            $ok = $this->db->where('id', (int) $id)->update('kas_masuk', $d);
            return array($ok, $ok ? translate('i18n_km_diperbarui') : translate('i18n_km_gagal_simpan'));
        }

        // No. bukti urut per bulan; dikunci supaya tidak dobel saat dua orang simpan bersamaan
        $this->db->trans_begin();
        $prefix = 'KM/' . date('Y/m', strtotime($d['tanggal'])) . '/';
        $last = $this->db->query(
            'SELECT no_bukti FROM kas_masuk WHERE no_bukti LIKE ? ORDER BY no_bukti DESC LIMIT 1 FOR UPDATE',
            array($prefix . '%')
        )->row();
        $urut = $last ? ((int) substr($last->no_bukti, strlen($prefix))) + 1 : 1;

        $d['no_bukti'] = $prefix . str_pad($urut, 4, '0', STR_PAD_LEFT);
        $d['id_user']  = $id_user;
        $this->db->insert('kas_masuk', $d);
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_km_gagal_simpan'));
        }
        $this->db->trans_commit();
        return array(true, (translate('i18n_km_pemasukan') . ' ') . $d['no_bukti'] . ' Rp ' . number_format($d['nominal'], 0, ',', '.') . (' ' . translate('i18n_dicatat')));
    }

    public function hapus($id)
    {
        return $this->db->where('id', (int) $id)->delete('kas_masuk');
    }
}
