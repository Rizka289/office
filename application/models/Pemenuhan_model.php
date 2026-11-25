<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pemenuhan Sales Order (setelah SO dikonfirmasi)
 *
 *   sumber item SO  | dokumen         | "siap"                     | kirim
 *   ----------------+-----------------+----------------------------+-------------------------------
 *   produksi        | SPK produksi    | SPK selesai                | Surat Jalan (gudang)
 *   beli            | PO jenis stok   | barang PO diterima gudang  | Surat Jalan (gudang)
 *   dropship        | PO jenis dropsh.| -                          | konfirmasi PO diterima customer
 *                   |                 |                            | -> Surat Jalan dropship otomatis
 *
 * Status SO diatur otomatis oleh sinkron_status_so():
 *   confirmed -> produksi (ada SPK/PO/SJ) -> terkirim (semua qty terkirim) -> selesai (terkirim + lunas)
 */
class Pemenuhan_model extends CI_Model
{
    public $sumber_valid = array('produksi', 'beli', 'dropship', 'stok');

    // Tarif PPN untuk PO ke supplier (persen)
    const TARIF_PPN_PO = 11;

    // Kolom PPN di pm_po sudah ada? (database/pm_po_ppn.sql)
    public function ada_kolom_ppn_po()
    {
        static $ada = null;
        if ($ada === null) {
            $ada = $this->db->field_exists('ppn_persen', 'pm_po') && $this->db->field_exists('ppn_nominal', 'pm_po');
        }
        return $ada;
    }

    // Status SO yang alokasi stoknya masih "memegang" stok
    private $so_aktif_stok = array('draft', 'confirmed', 'produksi', 'terkirim');
    private $eps = 0.0001;

    // ================= DATA PERUSAHAAN (kop surat semua dokumen cetak) =================
    public function perusahaan()
    {
        return array(
            'nama'     => 'PT OUPAI PINTU JENDELA INDONESIA',
            'tagline'  => 'Manufaktur Pintu & Jendela',
            'alamat'   => 'Jl. Prof. Dr. Hamka No.45, Tambakaji, 50185, Kec. Ngaliyan Kota Semarang, Jawa Tengah, Indonesia',
            'telp'     => '085819570958',
            'email'    => 'ptoupaipintujendelaindonesia@gmail.com',
            'logo_file' => FCPATH . 'uploads/logo/Logo.jpeg',
            'logo_url' => base_url('uploads/logo/Logo.jpeg'),
        );
    }

    // ================= UTIL =================

    // Nomor dokumen: 01/PO-OPJI/11/2026, 01/SPK-OPJI/11/2026, 01/SJ-OPJI/11/2026
    public function nomor($tabel, $kode, $tanggal, $kunci = false)
    {
        $tahun = (int) date('Y', strtotime($tanggal));
        $bulan = date('m', strtotime($tanggal));
        $sql   = 'SELECT COALESCE(MAX(no_urut), 0) AS maks FROM ' . $tabel . ' WHERE tahun = ?' . ($kunci ? ' FOR UPDATE' : '');
        $q     = $this->db->query($sql, array($tahun));
        $row   = $q ? $q->row() : null;
        $urut  = ($row ? (int) $row->maks : 0) + 1;
        return array(
            'no_urut' => $urut,
            'tahun'   => $tahun,
            'no'      => sprintf('%02d/%s-OPJI/%s/%d', $urut, $kode, $bulan, $tahun),
        );
    }

    // Ukuran tampil (sama dengan detail SO / invoice): rolling door sesuai rumus ukuran
    // dari penawaran: l200_t600 (default) = +200 x +600 mm, t500 = lebar tetap, tinggi +500 mm
    public function ukuran($row)
    {
        $l     = (int) ($row->lebar_mm ?? 0);
        $t     = (int) ($row->tinggi_mm ?? 0);
        $rumus = (string) ($row->rumus ?? '');
        $is_rd = preg_match('/^ro{1,2}l{1,2}ing_(door_)?(tahan_api|baja|otomatis)$/', $rumus)
            || ($rumus === '' && preg_match('/ro{1,2}l{1,2}ing\s*door/i', (string) ($row->jenis_item ?? '')));
        if ($is_rd) {
            if (($row->rumus_ukuran ?? '') === 't500') {
                $t += 500;
            } else {
                $l += 200;
                $t += 600;
            }
        }
        return array($l, $t);
    }

    // Nama item tanpa ukuran (ukuran sudah ada kolom sendiri di PO)
    public function nama_item_polos($it)
    {
        $bag = array(trim((string) ($it->jenis_item ?? 'Item')));
        if (trim((string) ($it->warna ?? '')) !== '' && trim((string) $it->warna) !== '-') {
            $bag[] = trim($it->warna);
        }
        return implode(' - ', $bag);
    }

    public function nama_item($it)
    {
        $bag = array(trim((string) ($it->jenis_item ?? 'Item')));
        if (trim((string) ($it->warna ?? '')) !== '' && trim((string) $it->warna) !== '-') {
            $bag[] = trim($it->warna);
        }
        list($l, $t) = $this->ukuran($it);
        if ($l > 0 && $t > 0) {
            $bag[] = number_format($l, 0, ',', '.') . ' x ' . number_format($t, 0, ',', '.') . ' mm';
        }
        return implode(' - ', $bag);
    }

    // Jalankan proses dengan db_debug mati: error DB jadi pesan, bukan halaman error
    private function _aman($metode, $args)
    {
        $lama               = $this->db->db_debug;
        $this->db->db_debug = false;
        try {
            return $this->$metode(...$args);
        } finally {
            $this->db->db_debug = $lama;
        }
    }

    private function _gagal($pesan)
    {
        $e = $this->db->error();
        $this->db->trans_rollback();
        if (!empty($e['code'])) {
            log_message('error', 'Pemenuhan_model: ' . $pesan . ' | DB ' . $e['code'] . ': ' . $e['message']);
            $pesan .= ' (DB ' . $e['code'] . ': ' . $e['message'] . ')';
        }
        return array(false, $pesan);
    }

    // Kunci baris SO (di dalam transaksi) supaya proses untuk satu SO berjalan bergantian.
    private function _kunci_so($id_so)
    {
        $q  = $this->db->query('SELECT * FROM sales_order WHERE id = ? FOR UPDATE', array((int) $id_so));
        $so = $q ? $q->row() : null;
        if (!$so || !in_array($so->status, array('confirmed', 'produksi', 'terkirim'), true)) {
            return null;
        }
        return $so;
    }

    // ================= ITEM SO + PROGRES =================

    public function get_items($id_so)
    {
        $items = $this->db->select('d.*, s.nama AS nama_supplier')
            ->from('sales_order_detail d')
            ->join('supplier s', 's.id = d.id_supplier', 'left')
            ->where('d.id_so', (int) $id_so)
            ->order_by('d.id', 'ASC')
            ->get()->result();
        $this->_hitung_progres((int) $id_so, $items);
        return $items;
    }

    // Menambah properti ke tiap item:
    //  p_proses      = qty yang sudah dibuatkan SPK / PO
    //  p_siap        = qty selesai produksi / diterima dari supplier
    //  p_sisa_proses = qty yang belum dibuatkan SPK / PO
    //  p_siap_kirim  = qty di gudang yang siap dibuatkan surat jalan
    //  p_terkunci    = sumber tidak boleh diubah lagi
    private function _hitung_progres($id_so, &$items)
    {
        $spk = array();
        $q   = $this->db->query(
            "SELECT pd.id_so_detail, SUM(pd.qty) AS proses,
                    SUM(CASE WHEN p.status = 'selesai' THEN pd.qty ELSE 0 END) AS siap
               FROM pm_spk_detail pd
               JOIN pm_spk p ON p.id = pd.id_spk
              WHERE p.id_so = ? AND p.status <> 'batal'
              GROUP BY pd.id_so_detail",
            array($id_so)
        );
        foreach ($q ? $q->result() : array() as $r) {
            $spk[(int) $r->id_so_detail] = $r;
        }

        $po = array();
        $q  = $this->db->query(
            "SELECT d.id_so_detail, SUM(d.qty) AS proses, SUM(d.qty_diterima) AS siap
               FROM pm_po_detail d
               JOIN pm_po p ON p.id = d.id_po
              WHERE p.id_so = ? AND p.status <> 'batal' AND d.id_so_detail IS NOT NULL
              GROUP BY d.id_so_detail",
            array($id_so)
        );
        foreach ($q ? $q->result() : array() as $r) {
            $po[(int) $r->id_so_detail] = $r;
        }

        // alokasi stok aktif per item (sumber = stok)
        $alo = array();
        $q   = $this->db->query(
            "SELECT id_so_detail, SUM(qty) AS proses, SUM(qty) AS siap
               FROM pm_alokasi_stok
              WHERE id_so = ? AND status = 'aktif'
              GROUP BY id_so_detail",
            array($id_so)
        );
        foreach ($q ? $q->result() : array() as $r) {
            $alo[(int) $r->id_so_detail] = $r;
        }

        foreach ($items as $it) {
            $id     = (int) $it->id;
            $sumber = (string) $it->sumber;
            if ($sumber === 'produksi') {
                $src = $spk[$id] ?? null;
            } elseif ($sumber === 'stok') {
                $src = $alo[$id] ?? null;   // stok yang dipilih langsung dianggap siap kirim
            } else {
                $src = $po[$id] ?? null;
            }

            $it->qty_terkirim  = (float) ($it->qty_terkirim ?? 0);
            $it->p_proses      = $src ? (float) $src->proses : 0.0;
            $it->p_siap        = $src ? (float) $src->siap : 0.0;
            $it->p_sisa_proses = $sumber !== '' ? max(0, (float) $it->qty - $it->p_proses) : 0.0;
            $it->p_siap_kirim  = in_array($sumber, array('produksi', 'beli', 'stok'), true)
                ? max(0, $it->p_siap - $it->qty_terkirim) : 0.0;
            $it->p_terkunci    = isset($spk[$id]) || isset($po[$id]) || isset($alo[$id]) || $it->qty_terkirim > 0;
            $it->p_nama        = $this->nama_item($it);
        }
    }

    // ================= 1. SUMBER ITEM =================

    // $input = array(id_so_detail => array('sumber' => ..., 'id_supplier' => ...))
    public function simpan_sumber($id_so, $input)
    {
        return $this->_aman('_proses_sumber', array($id_so, $input));
    }

    private function _proses_sumber($id_so, $input)
    {
        $this->db->trans_begin();
        if (!$this->_kunci_so($id_so)) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_sumber_hanya_bisa_diatur_untuk_so'));
        }
        $sup_valid = array();
        foreach ($this->db->select('id')->get('supplier')->result() as $s) {
            $sup_valid[(int) $s->id] = true;
        }

        $ubah  = 0;
        $tolak = array();
        foreach ($this->get_items($id_so) as $it) {
            $id = (int) $it->id;
            if (!array_key_exists($id, $input)) {
                continue;
            }
            $sumber = (string) $input[$id]['sumber'];
            $sup    = (int) $input[$id]['id_supplier'];
            if ($sumber !== '' && !in_array($sumber, $this->sumber_valid, true)) {
                continue;
            }
            if ($sumber === '' || $sumber === 'produksi' || $sumber === 'stok' || !isset($sup_valid[$sup])) {
                $sup = 0;
            }
            if ($sumber === (string) $it->sumber && $sup === (int) $it->id_supplier) {
                continue;
            }
            if ($sumber !== (string) $it->sumber && $it->p_terkunci) {
                $tolak[] = $it->p_nama;
                continue;
            }
            $this->db->where('id', $id)->update('sales_order_detail', array(
                'sumber'      => $sumber === '' ? null : $sumber,
                'id_supplier' => $sup > 0 ? $sup : null,
            ));
            $ubah++;
        }
        if ($this->db->trans_status() === false) {
            return $this->_gagal(translate('i18n_gagal_menyimpan_sumber_item'));
        }
        $this->db->trans_commit();

        $pesan = $ubah . (' ' . translate('i18n_item_diperbarui'));
        if ($tolak) {
            $pesan .= (' ' . translate('i18n_sumber_tidak_diubah_karena_sudah_ada_spk') . ' ') . implode('; ', $tolak) . '.';
        }
        return array(true, $pesan);
    }

    // ================= 2a. PURCHASE ORDER =================

    // $hdr: tanggal, jenis (stok|dropship), id_supplier, tgl_butuh, alamat_kirim, catatan
    // $lines: array of (id_so_detail, nama_barang, spesifikasi, qty, satuan, harga)
    // $biaya: array of (nama, nominal, keterangan) -> biaya tambahan PO (ongkir, gosok, packing, dll)
    // Return: array(ok, pesan, id_po)
    public function simpan_po($id_so, $hdr, $lines, $id_user, $biaya = array())
    {
        return $this->_aman('_proses_po', array($id_so, $hdr, $lines, $id_user, $biaya));
    }

    // Biaya tambahan PO (tabel pm_po_biaya, lihat database/pm_po_biaya.sql)
    public function ada_tabel_biaya_po()
    {
        static $ada = null;
        if ($ada === null) {
            $ada = $this->db->table_exists('pm_po_biaya');
        }
        return $ada;
    }

    // Kolom cara hitung biaya (hitung_per, luas_m2, harga) sudah ada? (database/pm_po_biaya_luas.sql)
    private function _biaya_po_per_m2_siap()
    {
        static $ada = null;
        if ($ada === null) {
            $ada = $this->ada_tabel_biaya_po()
                && $this->db->field_exists('hitung_per', 'pm_po_biaya')
                && $this->db->field_exists('luas_m2', 'pm_po_biaya')
                && $this->db->field_exists('harga', 'pm_po_biaya');
        }
        return $ada;
    }

    // Saring & validasi baris biaya dari form. Return array(rows, total, pesan_error)
    // Cara hitung per baris:
    //   borongan : total = nominal yang diketik
    //   m2       : total = luas (m²) x harga per m²  (mis. biaya gosok) - dihitung ulang di server
    private function _saring_biaya_po($biaya)
    {
        $rows  = array();
        $total = 0;
        foreach ((array) $biaya as $b) {
            $nama  = mb_substr(trim((string) ($b['nama'] ?? '')), 0, 100);
            $ket   = mb_substr(trim((string) ($b['keterangan'] ?? '')), 0, 255);
            $per   = (($b['hitung'] ?? '') === 'm2') ? 'm2' : 'borongan';
            $luas  = round(max(0, (float) ($b['luas'] ?? 0)), 2);
            $harga = round(max(0, (float) ($b['harga'] ?? 0)), 2);
            $nominal = $per === 'm2' ? round($luas * $harga, 2) : round((float) ($b['nominal'] ?? 0), 2);

            if ($nama === '' && $nominal == 0 && $luas == 0 && $harga == 0) {
                continue; // baris kosong
            }
            if ($nama === '') {
                return array(array(), 0, translate('i18n_nama_biaya_tambahan_wajib_diisi_mis'));
            }
            if ($per === 'm2') {
                if (!$this->_biaya_po_per_m2_siap()) {
                    return array(array(), 0, translate('i18n_hitung_biaya_per_m2_belum_aktif_jalankan'));
                }
                if ($luas <= 0 || $harga <= 0) {
                    return array(array(), 0, translate('i18n_luas_dan_harga_per_m2_untuk') . $nama . translate('i18n_q_wajib_diisi'));
                }
            } elseif ($nominal <= 0) {
                return array(array(), 0, translate('i18n_nominal') . $nama . translate('i18n_q_harus_lebih_dari_0'));
            }

            $row = array('nama' => $nama, 'nominal' => $nominal, 'keterangan' => $ket !== '' ? $ket : null);
            if ($this->_biaya_po_per_m2_siap()) {
                $row['hitung_per'] = $per;
                $row['luas_m2']    = $per === 'm2' ? $luas : null;
                $row['harga']      = $per === 'm2' ? $harga : null;
            }
            $rows[] = $row;
            $total += $nominal;
        }
        return array($rows, round($total, 2), '');
    }

    private function _proses_po($id_so, $hdr, $lines, $id_user, $biaya = array())
    {
        $this->db->trans_begin();
        if (!$this->_kunci_so($id_so)) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_so_harus_berstatus_dikonfirmasi_produksi'));
        }
        $jenis = $hdr['jenis'];
        if (!in_array($jenis, array('stok', 'dropship'), true)) {
            return $this->_gagal(translate('i18n_jenis_po_tidak_valid'));
        }
        $sup = $this->db->get_where('supplier', array('id' => (int) $hdr['id_supplier']))->row();
        if (!$sup) {
            return $this->_gagal(translate('i18n_pilih_supplier_terlebih_dahulu'));
        }

        $peta = array();
        foreach ($this->get_items($id_so) as $it) {
            $peta[(int) $it->id] = $it;
        }
        $perlu = $jenis === 'dropship' ? 'dropship' : 'beli';
        $rows  = array();
        $total = 0;

        foreach ($lines as $l) {
            $qty = (float) $l['qty'];
            if ($qty <= 0) {
                continue;
            }
            $harga = max(0, (float) $l['harga']);
            $id_d  = (int) $l['id_so_detail'];
            $nama  = trim((string) $l['nama_barang']);

            if ($id_d > 0) {
                if (!isset($peta[$id_d])) {
                    return $this->_gagal(translate('i18n_ada_item_yang_bukan_milik_so_ini'));
                }
                $it = $peta[$id_d];
                if ((string) $it->sumber !== $perlu) {
                    return $this->_gagal(translate('i18n_item_2') . $it->p_nama . translate('i18n_q_sumbernya_bukan') . $perlu . '".');
                }
                if ($qty > $it->p_sisa_proses + $this->eps) {
                    return $this->_gagal(translate('i18n_qty') . $it->p_nama . translate('i18n_q_melebihi_sisa_belum_dipesan')
                        . (float) $it->p_sisa_proses . ').');
                }
                $it->p_sisa_proses -= $qty;
                if ($nama === '') {
                    $nama = $it->p_nama;
                }
            } else {
                if ($jenis === 'dropship') {
                    return $this->_gagal(translate('i18n_po_dropship_hanya_boleh_berisi_item_dari'));
                }
                if ($nama === '') {
                    return $this->_gagal(translate('i18n_nama_barang_pada_baris_tambahan_wajib'));
                }
            }
            // Sama seperti penawaran:
            //   Luas (m2)   = Lebar x Tinggi / 1.000.000   (per unit)
            //   Total Harga = Luas x Qty x Harga/m2
            //   Lebar/tinggi kosong -> Total Harga = Qty x Harga
            $lebar  = max(0, (int) ($l['lebar_mm'] ?? 0));
            $tinggi = max(0, (int) ($l['tinggi_mm'] ?? 0));
            // Luas per unit dibulatkan 2 angka di belakang koma (sama dengan penawaran: 1,649 -> 1,65)
            $luas   = round($lebar * $tinggi / 1000000, 2);
            $per    = $luas > 0 ? 'm2' : 'unit';
            if ($harga <= 0) {
                return $this->_gagal(translate('i18n_harga_beli') . $nama . translate('i18n_q_belum_diisi'));
            }
            $sub    = round(($per === 'm2' ? $luas * $qty : $qty) * $harga, 2);
            $rows[] = array(
                'id_so_detail' => $id_d > 0 ? $id_d : null,
                'nama_barang'  => mb_substr($nama, 0, 255),
                'lebar_mm'     => $lebar,
                'tinggi_mm'    => $tinggi,
                'luas_m2'      => $luas,
                'spesifikasi'  => trim((string) $l['spesifikasi']),
                'qty'          => $qty,
                'satuan'       => 'unit',
                'harga'        => $harga,
                'hitung_per'   => $per,
                'total'        => $sub,
            );
            $total += $sub;
        }
        if (!$rows) {
            return $this->_gagal(translate('i18n_isi_qty_minimal_satu_barang'));
        }

        $hasil = $this->_insert_po((int) $id_so, $jenis, $hdr, $sup, $rows, $total, $biaya, $id_user);
        if (!$hasil[0]) {
            return $hasil;
        }
        $this->sinkron_status_so($id_so);
        if ($this->db->trans_status() === false) {
            return $this->_gagal(translate('i18n_gagal_menyimpan_po'));
        }
        $this->db->trans_commit();
        return $hasil;
    }

    // Simpan header + detail + biaya PO (dipakai PO dari SO dan PO bahan/stok tanpa SO).
    // Dipanggil di dalam transaksi. $id_so = 0 -> PO tanpa SO (id_so NULL).
    // Return array(ok, pesan, id_po)
    private function _insert_po($id_so, $jenis, $hdr, $sup, $rows, $total, $biaya, $id_user)
    {
        list($biaya_rows, $total_biaya, $err_biaya) = $this->_saring_biaya_po($biaya);
        if ($err_biaya !== '') {
            return $this->_gagal($err_biaya);
        }
        if ($biaya_rows && !$this->ada_tabel_biaya_po()) {
            return $this->_gagal(translate('i18n_tabel_biaya_tambahan_po_belum_ada'));
        }
        $total_barang = round($total, 2);
        $dpp          = round($total_barang + $total_biaya, 2);

        // PPN 11% dari (subtotal barang + biaya tambahan), dibulatkan ke rupiah (sama dengan penawaran)
        $pakai_ppn = !empty($hdr['pakai_ppn']);
        if ($pakai_ppn && !$this->ada_kolom_ppn_po()) {
            return $this->_gagal(translate('i18n_kolom_ppn_di_po_belum_ada_jalankan'));
        }
        $ppn_persen  = $pakai_ppn ? self::TARIF_PPN_PO : 0;
        $ppn_nominal = $pakai_ppn ? round($dpp * $ppn_persen / 100) : 0;
        $total       = round($dpp + $ppn_nominal, 2);

        $kolom_po = array();
        if ($this->db->field_exists('subtotal_barang', 'pm_po')) {
            $kolom_po['subtotal_barang'] = $total_barang;
        }
        if ($this->db->field_exists('total_biaya', 'pm_po')) {
            $kolom_po['total_biaya'] = $total_biaya;
        }
        if ($this->ada_kolom_ppn_po()) {
            $kolom_po['ppn_persen']  = $ppn_persen;
            $kolom_po['ppn_nominal'] = $ppn_nominal;
        }

        $no = $this->nomor('pm_po', 'PO', $hdr['tanggal'], true);
        $ok = $this->db->insert('pm_po', $kolom_po + array(
            'no_po'        => $no['no'],
            'no_urut'      => $no['no_urut'],
            'tahun'        => $no['tahun'],
            'id_so'        => $id_so > 0 ? (int) $id_so : null,
            'id_supplier'  => (int) $sup->id,
            'jenis'        => $jenis,
            'tanggal'      => $hdr['tanggal'],
            'tgl_butuh'    => $hdr['tgl_butuh'] !== '' ? $hdr['tgl_butuh'] : null,
            'alamat_kirim' => $hdr['alamat_kirim'],
            'catatan'      => $hdr['catatan'],
            'total'        => $total,
            'status'       => 'draft',
            'id_user'      => $id_user,
        ));
        if (!$ok) {
            return $this->_gagal(translate('i18n_gagal_menyimpan_po'));
        }
        $id_po = (int) $this->db->insert_id();
        foreach ($rows as $r) {
            $r['id_po'] = $id_po;
            if (!$this->db->insert('pm_po_detail', $r)) {
                return $this->_gagal(translate('i18n_gagal_menyimpan_detail_po'));
            }
        }
        foreach ($biaya_rows as $b) {
            $b['id_po'] = $id_po;
            if (!$this->db->insert('pm_po_biaya', $b)) {
                return $this->_gagal(translate('i18n_gagal_menyimpan_biaya_tambahan_po'));
            }
        }
        return array(true, 'PO ' . $no['no'] . (' ' . translate('i18n_dibuat_draft_menunggu_persetujuan_atasan')), $id_po);
    }

    // ================= 2a'. PO BAHAN / STOK (TANPA SALES ORDER) =================
    //
    // Untuk pembelian di luar penawaran/SO, mis. aluminium, kaca, aksesoris untuk produksi sendiri.
    // Alur sama dengan PO biasa: draft -> disetujui atasan -> dipesan -> diterima gudang -> dibayar.
    // Baris yang dipilih dari master barang otomatis MENAMBAH stok_barang saat diterima
    // (kartu stok: PENERIMAAN_SUPPLIER), jadi bisa dipakai oleh SPK produksi.
    //
    // Butuh perubahan tabel: database/pm_po_tanpa_so.sql

    // Struktur database untuk PO tanpa SO sudah siap?
    public function po_bebas_siap()
    {
        static $ada = null;
        if ($ada === null) {
            $q   = $this->db->query(
                "SELECT IS_NULLABLE FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pm_po' AND COLUMN_NAME = 'id_so'"
            );
            $row = $q ? $q->row() : null;
            $ada = $row && strtoupper($row->IS_NULLABLE) === 'YES'
                && $this->db->field_exists('id_barang', 'pm_po_detail')
                && $this->db->field_exists('id_location', 'pm_po_terima');
        }
        return $ada;
    }

    // Master barang untuk dipilih di PO bahan
    public function get_barang_beli()
    {
        return $this->db->select('id, kode_barang, nama, warna, satuan, jenis_barang')
            ->order_by('nama', 'ASC')->get('barang')->result();
    }

    public function get_lokasi()
    {
        return $this->db->select('id, location_code, zone_name')
            ->order_by('location_code', 'ASC')->get('locations')->result();
    }

    // $hdr: tanggal, id_supplier, tgl_butuh, alamat_kirim, catatan, pakai_ppn
    // $lines: array of (id_barang, nama_barang, spesifikasi, qty, satuan, harga)
    // Return: array(ok, pesan, id_po)
    public function simpan_po_bebas($hdr, $lines, $id_user, $biaya = array())
    {
        return $this->_aman('_proses_po_bebas', array($hdr, $lines, $id_user, $biaya));
    }

    private function _proses_po_bebas($hdr, $lines, $id_user, $biaya = array())
    {
        if (!$this->po_bebas_siap()) {
            return array(false, 'Fitur PO bahan belum aktif. Jalankan database/pm_po_tanpa_so.sql di database dulu.');
        }
        $this->db->trans_begin();
        $sup = $this->db->get_where('supplier', array('id' => (int) $hdr['id_supplier']))->row();
        if (!$sup) {
            return $this->_gagal(translate('i18n_pilih_supplier_terlebih_dahulu'));
        }

        $barang = array();
        foreach ($this->get_barang_beli() as $b) {
            $barang[(int) $b->id] = $b;
        }

        $rows  = array();
        $total = 0;
        foreach ($lines as $l) {
            $qty = round((float) $l['qty'], 2);
            if ($qty <= 0) {
                continue;
            }
            $id_b   = (int) ($l['id_barang'] ?? 0);
            $nama   = trim((string) $l['nama_barang']);
            $satuan = mb_substr(trim((string) ($l['satuan'] ?? '')), 0, 30);
            if ($id_b > 0) {
                if (!isset($barang[$id_b])) {
                    return $this->_gagal('Barang yang dipilih tidak ditemukan di master barang.');
                }
                $b = $barang[$id_b];
                if ($nama === '') {
                    $nama = trim($b->nama . ($b->warna && $b->warna !== '-' ? ' - ' . $b->warna : ''));
                }
                if ($satuan === '') {
                    $satuan = (string) $b->satuan;
                }
                // stok_barang disimpan bilangan bulat
                if (abs($qty - round($qty)) > $this->eps) {
                    return $this->_gagal('Qty ' . $nama . ' harus bilangan bulat karena masuk ke stok gudang.');
                }
            }
            if ($nama === '') {
                return $this->_gagal(translate('i18n_nama_barang_pada_baris_tambahan_wajib'));
            }
            $harga = max(0, (float) $l['harga']);
            if ($harga <= 0) {
                return $this->_gagal(translate('i18n_harga_beli') . $nama . translate('i18n_q_belum_diisi'));
            }
            $sub    = round($qty * $harga, 2);
            $rows[] = array(
                'id_so_detail' => null,
                'id_barang'    => $id_b > 0 ? $id_b : null,
                'nama_barang'  => mb_substr($nama, 0, 255),
                'lebar_mm'     => 0,
                'tinggi_mm'    => 0,
                'luas_m2'      => 0,
                'spesifikasi'  => trim((string) $l['spesifikasi']),
                'qty'          => $qty,
                'satuan'       => $satuan !== '' ? $satuan : 'unit',
                'harga'        => $harga,
                'hitung_per'   => 'unit',
                'total'        => $sub,
            );
            $total += $sub;
        }
        if (!$rows) {
            return $this->_gagal(translate('i18n_isi_qty_minimal_satu_barang'));
        }

        $hasil = $this->_insert_po(0, 'stok', $hdr, $sup, $rows, $total, $biaya, $id_user);
        if (!$hasil[0]) {
            return $hasil;
        }
        if ($this->db->trans_status() === false) {
            return $this->_gagal(translate('i18n_gagal_menyimpan_po'));
        }
        $this->db->trans_commit();
        return $hasil;
    }

    // PO terkait SO: kunci SO (harus masih berjalan). PO tanpa SO: selalu boleh.
    private function _kunci_so_po($id_so)
    {
        if ((int) $id_so <= 0) {
            return true;
        }
        return (bool) $this->_kunci_so($id_so);
    }

    // Tambah stok_barang dari penerimaan PO + catat kartu stok (PENERIMAAN_SUPPLIER).
    // Return '' jika ok, atau pesan error.
    private function _stok_masuk_po($id_barang, $id_location, $qty, $id_trm, $no_po, $nama_supplier, $id_user)
    {
        $jenis_b = $this->db->select('jenis_barang')->get_where('barang', array('id' => (int) $id_barang))->row();
        $opsi    = ($jenis_b && $jenis_b->jenis_barang === 'aksesoris') ? 'aksesoris' : 'bahan';
        $ukuran  = '';
        $rows    = $this->db->query(
            'SELECT id, stok FROM stok_barang WHERE id_barang = ? AND id_location = ? AND opsi = ? AND ukuran = ? ORDER BY id ASC FOR UPDATE',
            array((int) $id_barang, (int) $id_location, $opsi, $ukuran)
        )->result();
        $sebelum = 0;
        foreach ($rows as $r) {
            $sebelum += (int) $r->stok;
        }
        $sesudah = $sebelum + (int) $qty;
        if ($rows) {
            $ok = $this->db->set('stok', 'stok + ' . (int) $qty, false)->where('id', $rows[0]->id)->update('stok_barang');
        } else {
            $ok = $this->db->insert('stok_barang', array(
                'id_barang' => (int) $id_barang, 'id_location' => (int) $id_location,
                'opsi' => $opsi, 'ukuran' => $ukuran, 'stok' => $sesudah,
            ));
        }
        if (!$ok) {
            return 'Gagal menambah stok gudang.';
        }
        $ok = $this->db->insert('stok_riwayat', array(
            'id_barang'       => (int) $id_barang,
            'id_location'     => (int) $id_location,
            'jenis_transaksi' => 'PENERIMAAN_SUPPLIER',
            'id_referensi'    => (int) $id_trm,
            'ukuran'          => $ukuran,
            'opsi'            => $opsi,
            'qty_masuk'       => (int) $qty,
            'qty_keluar'      => 0,
            'stok_sebelum'    => $sebelum,
            'stok_sesudah'    => $sesudah,
            'keterangan'      => 'Penerimaan PO ' . $no_po . ($nama_supplier ? ' dari ' . $nama_supplier : ''),
            'created_by'      => $id_user,
            'created_at'      => date('Y-m-d H:i:s'),
        ));
        return $ok ? '' : 'Gagal mencatat kartu stok.';
    }

    // $aksi:
    //   setujui : draft -> approved            (atasan)
    //   tolak   : draft -> batal + alasan      (atasan)
    //   kirim   : approved -> dipesan          (purchasing)
    //   batal   : draft/approved/dipesan -> batal (belum ada penerimaan & belum ada pembayaran)
    public function aksi_po($id_po, $aksi, $id_user = null, $alasan = '')
    {
        return $this->_aman('_proses_aksi_po', array($id_po, $aksi, $id_user, $alasan));
    }

    private function _proses_aksi_po($id_po, $aksi, $id_user, $alasan)
    {
        $po = $this->db->get_where('pm_po', array('id' => (int) $id_po))->row();
        if (!$po) {
            return array(false, translate('i18n_po_tidak_ditemukan'));
        }
        $this->db->trans_begin();
        if (!$this->_kunci_so_po($po->id_so)) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_so_sudah_selesai_batal_po_tidak_bisa'));
        }
        $po     = $this->db->query('SELECT * FROM pm_po WHERE id = ? FOR UPDATE', array((int) $id_po))->row();
        $alasan = mb_substr(trim((string) $alasan), 0, 255);
        $set    = array();

        if ($aksi === 'setujui') {
            $dari  = array('draft');
            $ke    = 'approved';
            $set   = array('approved_by' => $id_user, 'approved_at' => date('Y-m-d H:i:s'));
            $pesan = 'PO ' . $po->no_po . (' ' . translate('i18n_disetujui_purchasing_bisa_mengirim_po_ke'));
        } elseif ($aksi === 'tolak') {
            $dari = array('draft');
            $ke   = 'batal';
            if ($alasan === '') {
                $this->db->trans_rollback();
                return array(false, translate('i18n_isi_alasan_penolakan_po'));
            }
            $set   = array('approved_by' => $id_user, 'approved_at' => date('Y-m-d H:i:s'), 'alasan_batal' => 'Ditolak atasan: ' . $alasan);
            $pesan = 'PO ' . $po->no_po . (' ' . translate('i18n_ditolak_qty_item_kembali_bisa_dibuatkan'));
        } elseif ($aksi === 'kirim') {
            $dari  = array('approved');
            $ke    = 'dipesan';
            $pesan = 'PO ' . $po->no_po . (' ' . translate('i18n_ditandai_sudah_dipesan_ke_supplier'));
        } elseif ($aksi === 'batal') {
            $dari = array('draft', 'approved', 'dipesan');
            $ke   = 'batal';
            $r    = $this->db->select_sum('qty_diterima', 't')->where('id_po', $po->id)->get('pm_po_detail')->row();
            if ($r && (float) $r->t > 0) {
                $this->db->trans_rollback();
                return array(false, translate('i18n_po_sudah_ada_penerimaan_barang_tidak'));
            }
            if ((float) $po->total_bayar > 0) {
                $this->db->trans_rollback();
                return array(false, translate('i18n_po_sudah_ada_pembayaran_ke_supplier'));
            }
            if ($alasan !== '') {
                $set['alasan_batal'] = $alasan;
            }
            $pesan = 'PO ' . $po->no_po . (' ' . translate('i18n_dibatalkan'));
        } else {
            $this->db->trans_rollback();
            return array(false, translate('i18n_aksi_tidak_dikenal'));
        }
        if (!in_array($po->status, $dari, true)) {
            $this->db->trans_rollback();
            $label = $po->status === 'draft' ? 'belum disetujui atasan' : translate('i18n_berstatus') . $po->status . '"';
            return array(false, 'PO ' . $po->no_po . ' ' . $label . translate('i18n_tidak_bisa_di') . $aksi . '.');
        }
        $this->db->where('id', $po->id)->update('pm_po', array_merge(array('status' => $ke), $set));
        $this->sinkron_status_so($po->id_so);
        if ($this->db->trans_status() === false) {
            return $this->_gagal(translate('i18n_gagal_mengubah_status_po'));
        }
        $this->db->trans_commit();
        return array(true, $pesan);
    }

    // Terima barang PO.
    //  - PO stok     : barang masuk gudang -> siap dibuatkan surat jalan
    //  - PO dropship : konfirmasi barang sudah diterima customer -> otomatis tercatat terkirim
    // $hdr: tanggal, no_sj_supplier, ekspedisi, penerima, catatan
    // $qty_map: id_po_detail => qty
    // $aks: aksesoris paket dari gudang untuk barang dropship (lihat _aksesoris_keluar)
    public function terima_po($id_po, $hdr, $qty_map, $id_user, $aks = array())
    {
        return $this->_aman('_proses_terima', array($id_po, $hdr, $qty_map, $id_user, $aks));
    }

    private function _proses_terima($id_po, $hdr, $qty_map, $id_user, $aks = array())
    {
        $po = $this->db->get_where('pm_po', array('id' => (int) $id_po))->row();
        if (!$po) {
            return array(false, translate('i18n_po_tidak_ditemukan'));
        }
        $this->db->trans_begin();
        if (!$this->_kunci_so_po($po->id_so)) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_so_sudah_selesai_batal'));
        }
        $po = $this->db->query('SELECT * FROM pm_po WHERE id = ? FOR UPDATE', array((int) $id_po))->row();
        if (!in_array($po->status, array('dipesan', 'sebagian'), true)) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_penerimaan_hanya_untuk_po_berstatus'));
        }

        $det    = $this->db->query('SELECT * FROM pm_po_detail WHERE id_po = ? FOR UPDATE', array($po->id))->result();
        $terima = array();
        foreach ($det as $d) {
            $q = isset($qty_map[(int) $d->id]) ? (float) $qty_map[(int) $d->id] : 0;
            if ($q <= 0) {
                continue;
            }
            $sisa = (float) $d->qty - (float) $d->qty_diterima;
            if ($q > $sisa + $this->eps) {
                return $this->_gagal(translate('i18n_qty') . $d->nama_barang . translate('i18n_q_melebihi_sisa') . $sisa . ').');
            }
            $terima[] = array($d, $q);
        }
        if (!$terima) {
            return $this->_gagal(translate('i18n_isi_qty_yang_diterima_minimal_satu'));
        }

        // Baris yang terhubung ke master barang (PO bahan) -> masuk stok gudang, wajib pilih lokasi
        $masuk_stok  = $po->jenis === 'stok';
        $id_location = (int) ($hdr['id_location'] ?? 0);
        $ada_barang  = false;
        foreach ($terima as $t) {
            if ($masuk_stok && !empty($t[0]->id_barang)) {
                $ada_barang = true;
                if (abs($t[1] - round($t[1])) > $this->eps) {
                    return $this->_gagal('Qty diterima ' . $t[0]->nama_barang . ' harus bilangan bulat (masuk stok gudang).');
                }
            }
        }
        if ($ada_barang) {
            if ($id_location <= 0 || !$this->db->where('id', $id_location)->count_all_results('locations')) {
                return $this->_gagal('Pilih lokasi gudang tempat barang disimpan.');
            }
        }

        $trm = array(
            'id_po'          => $po->id,
            'tanggal'        => $hdr['tanggal'],
            'no_sj_supplier' => $hdr['no_sj_supplier'],
            'catatan'        => $hdr['catatan'],
            'id_user'        => $id_user,
        );
        if ($ada_barang) {
            $trm['id_location'] = $id_location;
        }
        $this->db->insert('pm_po_terima', $trm);
        $id_trm = (int) $this->db->insert_id();
        $nama_sup = $this->db->select('nama')->get_where('supplier', array('id' => (int) $po->id_supplier))->row();
        foreach ($terima as $t) {
            list($d, $q) = $t;
            $this->db->insert('pm_po_terima_detail', array('id_penerimaan' => $id_trm, 'id_po_detail' => $d->id, 'qty' => $q));
            $this->db->query('UPDATE pm_po_detail SET qty_diterima = qty_diterima + ? WHERE id = ?', array($q, $d->id));
            if ($masuk_stok && !empty($d->id_barang)) {
                $err = $this->_stok_masuk_po($d->id_barang, $id_location, (int) round($q), $id_trm, $po->no_po, $nama_sup ? $nama_sup->nama : '', $id_user);
                if ($err !== '') {
                    return $this->_gagal($err);
                }
            }
        }

        $pesan = (translate('i18n_penerimaan_po') . ' ') . $po->no_po . (' ' . translate('i18n_dicatat'));
        if ($ada_barang) {
            $pesan .= ' Stok gudang sudah bertambah (lihat Riwayat Stok).';
        }

        // Dropship: barang sudah sampai di customer -> buat surat jalan dropship + tambah qty_terkirim
        if ($po->jenis === 'dropship') {
            $no = $this->nomor('pm_sj', 'SJ', $hdr['tanggal'], true);
            $this->db->insert('pm_sj', array(
                'no_sj'        => $no['no'],
                'no_urut'      => $no['no_urut'],
                'tahun'        => $no['tahun'],
                'id_so'        => $po->id_so,
                'jenis'        => 'dropship',
                'id_po'        => $po->id,
                'tanggal'      => $hdr['tanggal'],
                'alamat_kirim' => $po->alamat_kirim,
                'ekspedisi'    => $hdr['ekspedisi'],
                'no_resi'      => $hdr['no_sj_supplier'],
                'penerima'     => $hdr['penerima'],
                'catatan'      => trim('Dropship oleh supplier (PO ' . $po->no_po . '). ' . $hdr['catatan']),
                'status'       => 'terkirim',
                'id_user'      => $id_user,
            ));
            $id_sj = (int) $this->db->insert_id();
            $ids_detail = array();
            foreach ($terima as $t) {
                list($d, $q) = $t;
                if (!$d->id_so_detail) {
                    continue;
                }
                $ids_detail[] = (int) $d->id_so_detail;
                $this->db->insert('pm_sj_detail', array('id_sj' => $id_sj, 'id_so_detail' => $d->id_so_detail, 'qty' => $q));
                $this->db->query('UPDATE sales_order_detail SET qty_terkirim = qty_terkirim + ? WHERE id = ?', array($q, $d->id_so_detail));
            }
            // aksesoris paket (mis. handle) dari gudang kita ikut dikirim untuk barang dropship
            $err = $this->_aksesoris_keluar($id_sj, $no['no'], $po->id_so, $ids_detail, $aks, $id_user);
            if ($err !== '') {
                return $this->_gagal($err);
            }
            $pesan = (translate('i18n_dropship_po') . ' ') . $po->no_po . (' ' . translate('i18n_dikonfirmasi_diterima_customer')) . $no['no'] . ').';
        }

        $r = $this->db->query('SELECT COALESCE(SUM(qty - qty_diterima), 0) AS sisa FROM pm_po_detail WHERE id_po = ?', array($po->id))->row();
        $this->db->where('id', $po->id)->update('pm_po', array(
            'status' => ((float) $r->sisa <= $this->eps) ? 'selesai' : 'sebagian',
        ));
        $this->sinkron_status_so($po->id_so);

        if ($this->db->trans_status() === false) {
            return $this->_gagal(translate('i18n_gagal_menyimpan_penerimaan_barang'));
        }
        $this->db->trans_commit();
        return array(true, $pesan);
    }

    // ================= 2b. SPK PRODUKSI =================

    // $hdr: tanggal, target_selesai, catatan; $qty_map: id_so_detail => qty
    // Return: array(ok, pesan, id_spk)
    public function simpan_spk($id_so, $hdr, $qty_map, $id_user)
    {
        return $this->_aman('_proses_spk', array($id_so, $hdr, $qty_map, $id_user));
    }

    private function _proses_spk($id_so, $hdr, $qty_map, $id_user)
    {
        $this->db->trans_begin();
        if (!$this->_kunci_so($id_so)) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_so_harus_berstatus_dikonfirmasi_produksi_2'));
        }
        $rows = array();
        foreach ($this->get_items($id_so) as $it) {
            $q = isset($qty_map[(int) $it->id]) ? (float) $qty_map[(int) $it->id] : 0;
            if ($q <= 0) {
                continue;
            }
            if ($it->sumber !== 'produksi') {
                return $this->_gagal(translate('i18n_item_2') . $it->p_nama . translate('i18n_q_bukan_item_produksi'));
            }
            if ($q > $it->p_sisa_proses + $this->eps) {
                return $this->_gagal(translate('i18n_qty') . $it->p_nama . translate('i18n_q_melebihi_sisa_belum_spk') . (float) $it->p_sisa_proses . ').');
            }
            $rows[] = array('id_so_detail' => (int) $it->id, 'qty' => $q);
        }
        if (!$rows) {
            return $this->_gagal(translate('i18n_isi_qty_produksi_minimal_satu_item'));
        }

        $no = $this->nomor('pm_spk', 'SPK', $hdr['tanggal'], true);
        $ok = $this->db->insert('pm_spk', array(
            'no_spk'         => $no['no'],
            'no_urut'        => $no['no_urut'],
            'tahun'          => $no['tahun'],
            'id_so'          => (int) $id_so,
            'tanggal'        => $hdr['tanggal'],
            'target_selesai' => $hdr['target_selesai'] !== '' ? $hdr['target_selesai'] : null,
            'catatan'        => $hdr['catatan'],
            'status'         => 'antri',
            'id_user'        => $id_user,
        ));
        if (!$ok) {
            return $this->_gagal(translate('i18n_gagal_menyimpan_spk'));
        }
        $id_spk = (int) $this->db->insert_id();
        foreach ($rows as $r) {
            $r['id_spk'] = $id_spk;
            if (!$this->db->insert('pm_spk_detail', $r)) {
                return $this->_gagal(translate('i18n_gagal_menyimpan_detail_spk'));
            }
        }
        $this->sinkron_status_so($id_so);
        if ($this->db->trans_status() === false) {
            return $this->_gagal(translate('i18n_gagal_menyimpan_spk'));
        }
        $this->db->trans_commit();
        return array(true, 'SPK ' . $no['no'] . (' ' . translate('i18n_dibuat_masuk_antrian_produksi')), $id_spk);
    }

    // $aksi: mulai (antri -> proses) | selesai (antri/proses -> selesai) | batal (antri/proses -> batal)
    public function aksi_spk($id_spk, $aksi)
    {
        return $this->_aman('_proses_aksi_spk', array($id_spk, $aksi));
    }

    private function _proses_aksi_spk($id_spk, $aksi)
    {
        $spk = $this->db->get_where('pm_spk', array('id' => (int) $id_spk))->row();
        if (!$spk) {
            return array(false, translate('i18n_spk_tidak_ditemukan'));
        }
        $aturan = array(
            'mulai'   => array(array('antri'), 'proses', array('tgl_mulai' => date('Y-m-d')), 'dimulai'),
            'selesai' => array(array('antri', 'proses'), 'selesai', array('tgl_selesai' => date('Y-m-d')), translate('i18n_selesai_barang_siap_dikirim')),
            'batal'   => array(array('antri', 'proses'), 'batal', array(), 'dibatalkan'),
        );
        if (!isset($aturan[$aksi])) {
            return array(false, translate('i18n_aksi_tidak_dikenal'));
        }
        list($dari, $ke, $extra, $kata) = $aturan[$aksi];

        $this->db->trans_begin();
        if (!$this->_kunci_so($spk->id_so)) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_so_sudah_selesai_batal_spk_tidak_bisa'));
        }
        $spk = $this->db->query('SELECT * FROM pm_spk WHERE id = ? FOR UPDATE', array((int) $id_spk))->row();
        if (!in_array($spk->status, $dari, true)) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_spk_berstatus') . $spk->status . translate('i18n_q_tidak_bisa_di') . $aksi . '.');
        }
        if ($aksi === 'selesai' && !$spk->tgl_mulai) {
            $extra['tgl_mulai'] = date('Y-m-d');
        }
        $this->db->where('id', $spk->id)->update('pm_spk', array_merge(array('status' => $ke), $extra));
        $this->sinkron_status_so($spk->id_so);
        if ($this->db->trans_status() === false) {
            return $this->_gagal(translate('i18n_gagal_mengubah_status_spk'));
        }
        $this->db->trans_commit();
        return array(true, 'SPK ' . $spk->no_spk . ' ' . $kata . '.');
    }

    // ================= 3. SURAT JALAN (dari gudang) =================

    // $hdr: tanggal, alamat_kirim, ekspedisi, no_kendaraan, catatan; $qty_map: id_so_detail => qty
    // Return: array(ok, pesan, id_sj)
    // $aks: aksesoris paket yang ikut dikirim (lihat _aksesoris_keluar)
    public function simpan_sj($id_so, $hdr, $qty_map, $id_user, $aks = array())
    {
        return $this->_aman('_proses_sj', array($id_so, $hdr, $qty_map, $id_user, $aks));
    }

    private function _proses_sj($id_so, $hdr, $qty_map, $id_user, $aks = array())
    {
        $this->db->trans_begin();
        if (!$this->_kunci_so($id_so)) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_so_harus_berstatus_dikonfirmasi_produksi_3'));
        }
        $rows = array();
        foreach ($this->get_items($id_so) as $it) {
            $q = isset($qty_map[(int) $it->id]) ? (float) $qty_map[(int) $it->id] : 0;
            if ($q <= 0) {
                continue;
            }
            if (!in_array($it->sumber, array('produksi', 'beli', 'stok'), true)) {
                return $this->_gagal(translate('i18n_item_2') . $it->p_nama . translate('i18n_q_tidak_dikirim_dari_gudang'));
            }
            if ($it->sumber === 'stok' && abs($q - round($q)) > $this->eps) {
                return $this->_gagal(translate('i18n_qty_kirim') . $it->p_nama . translate('i18n_q_dari_stok_harus_bulat'));
            }
            if ($q > $it->p_siap_kirim + $this->eps) {
                return $this->_gagal(translate('i18n_qty_kirim') . $it->p_nama . translate('i18n_q_melebihi_barang_siap') . (float) $it->p_siap_kirim . ').');
            }
            $rows[] = array('id_so_detail' => (int) $it->id, 'qty' => $q, '_stok' => $it->sumber === 'stok', '_nama' => $it->p_nama);
        }
        if (!$rows) {
            return $this->_gagal(translate('i18n_isi_qty_kirim_minimal_satu_item'));
        }

        $no = $this->nomor('pm_sj', 'SJ', $hdr['tanggal'], true);
        $ok = $this->db->insert('pm_sj', array(
            'no_sj'        => $no['no'],
            'no_urut'      => $no['no_urut'],
            'tahun'        => $no['tahun'],
            'id_so'        => (int) $id_so,
            'jenis'        => 'gudang',
            'tanggal'      => $hdr['tanggal'],
            'alamat_kirim' => $hdr['alamat_kirim'],
            'ekspedisi'    => $hdr['ekspedisi'],
            'no_kendaraan' => $hdr['no_kendaraan'],
            'catatan'      => $hdr['catatan'],
            'status'       => 'terkirim',
            'id_user'      => $id_user,
        ));
        if (!$ok) {
            return $this->_gagal(translate('i18n_gagal_menyimpan_surat_jalan'));
        }
        $id_sj = (int) $this->db->insert_id();
        foreach ($rows as $r) {
            $stok = $r['_stok'];
            $nama = $r['_nama'];
            unset($r['_stok'], $r['_nama']);
            $r['id_sj'] = $id_sj;
            $this->db->insert('pm_sj_detail', $r);
            $this->db->query('UPDATE sales_order_detail SET qty_terkirim = qty_terkirim + ? WHERE id = ?', array($r['qty'], $r['id_so_detail']));
            // sumber stok: potong stok_barang + catat Kartu Stok
            if ($stok) {
                $err = $this->_keluarkan_stok($id_sj, $no['no'], $r['id_so_detail'], (int) round($r['qty']), $id_user);
                if ($err !== '') {
                    return $this->_gagal('"' . $nama . '": ' . $err);
                }
            }
        }
        $err = $this->_aksesoris_keluar($id_sj, $no['no'], $id_so, array_column($rows, 'id_so_detail'), $aks, $id_user);
        if ($err !== '') {
            return $this->_gagal($err);
        }
        $this->sinkron_status_so($id_so);
        if ($this->db->trans_status() === false) {
            return $this->_gagal(translate('i18n_gagal_menyimpan_surat_jalan'));
        }
        $this->db->trans_commit();
        return array(true, (translate('i18n_surat_jalan_2') . ' ') . $no['no'] . (' ' . translate('i18n_dibuat')), $id_sj);
    }

    public function batal_sj($id_sj)
    {
        return $this->_aman('_proses_batal_sj', array($id_sj));
    }

    private function _proses_batal_sj($id_sj)
    {
        $sj = $this->db->get_where('pm_sj', array('id' => (int) $id_sj))->row();
        if (!$sj) {
            return array(false, translate('i18n_surat_jalan_tidak_ditemukan'));
        }
        if ($sj->jenis === 'dropship') {
            return array(false, translate('i18n_surat_jalan_dropship_berasal_dari'));
        }
        $this->db->trans_begin();
        if (!$this->_kunci_so($sj->id_so)) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_so_sudah_selesai_batal_surat_jalan_tidak'));
        }
        $sj = $this->db->query('SELECT * FROM pm_sj WHERE id = ? FOR UPDATE', array((int) $id_sj))->row();
        if ($sj->status !== 'terkirim') {
            $this->db->trans_rollback();
            return array(false, translate('i18n_surat_jalan_sudah_dibatalkan'));
        }
        $cek_konsinyasi = $this->db->field_exists('qty_laku', 'sales_order_detail');
        foreach ($this->db->get_where('pm_sj_detail', array('id_sj' => $sj->id))->result() as $d) {
            // Konsinyasi: barang yang sudah dilaporkan laku / ditarik tidak bisa "batal dikirim"
            if ($cek_konsinyasi) {
                $sod = $this->db->select('qty_terkirim, qty_laku, qty_retur')->get_where('sales_order_detail', array('id' => (int) $d->id_so_detail))->row();
                if ($sod && (float) $sod->qty_terkirim - (float) $d->qty + $this->eps < (float) $sod->qty_laku + (float) $sod->qty_retur) {
                    return $this->_gagal(translate('i18n_surat_jalan_tidak_bisa_dibatalkan'));
                }
            }
            $this->db->query('UPDATE sales_order_detail SET qty_terkirim = GREATEST(0, qty_terkirim - ?) WHERE id = ?', array((float) $d->qty, $d->id_so_detail));
        }
        // kembalikan stok yang keluar lewat SJ ini
        $this->_kembalikan_stok_sj($sj);
        $this->db->where('id', $sj->id)->update('pm_sj', array('status' => 'batal'));
        $this->sinkron_status_so($sj->id_so);
        if ($this->db->trans_status() === false) {
            return $this->_gagal(translate('i18n_gagal_membatalkan_surat_jalan'));
        }
        $this->db->trans_commit();
        return array(true, (translate('i18n_surat_jalan_2') . ' ') . $sj->no_sj . (' ' . translate('i18n_dibatalkan_qty_kembali_ke_siap_kirim')));
    }

    // ================= 3b. AMBIL DARI STOK =================

    // Daftar stok (barang + lokasi + opsi + ukuran) dengan qty tersedia = stok - dialokasikan (belum keluar)
    // $id_barang > 0: hanya barang itu. $cari: nama/kode barang, ukuran, lokasi.
    public function get_stok_tersedia($id_barang = 0, $cari = '')
    {
        $this->db->select("sb.id_barang, sb.id_location, sb.opsi, sb.ukuran, SUM(sb.stok) AS stok,
                b.kode_barang, b.nama AS nama_barang, b.warna, l.zone_name AS nama_lokasi, l.location_code AS kode_lokasi", false)
            ->from('stok_barang sb')
            ->join('barang b', 'b.id = sb.id_barang', 'left')
            ->join('locations l', 'l.id = sb.id_location', 'left')
            ->group_by('sb.id_barang, sb.id_location, sb.opsi, sb.ukuran, b.kode_barang, b.nama, b.warna, l.zone_name, l.location_code')
            ->having('SUM(sb.stok) >', 0);
        if ((int) $id_barang > 0) {
            $this->db->where('sb.id_barang', (int) $id_barang);
        }
        if ($cari !== '') {
            $this->db->group_start()
                ->like('b.nama', $cari)->or_like('b.kode_barang', $cari)->or_like('b.warna', $cari)
                ->or_like('sb.ukuran', $cari)->or_like('sb.opsi', $cari)
                ->or_like('l.zone_name', $cari)->or_like('l.location_code', $cari)
                ->group_end();
        }
        $rows = $this->db->order_by('b.nama', 'ASC')->order_by('sb.opsi', 'ASC')->order_by('sb.ukuran', 'ASC')
            ->limit(200)->get()->result();

        $dipesan = $this->_stok_dipesan();
        foreach ($rows as $r) {
            $k            = $this->_kunci_stok($r->id_barang, $r->id_location, $r->opsi, $r->ukuran);
            $r->kunci     = $k;
            $r->dipesan   = (float) ($dipesan[$k] ?? 0);
            $r->tersedia  = max(0, (float) $r->stok - $r->dipesan);
        }
        return $rows;
    }

    private function _kunci_stok($id_barang, $id_location, $opsi, $ukuran)
    {
        return (int) $id_barang . '|' . (int) $id_location . '|' . (string) $opsi . '|' . (string) $ukuran;
    }

    // Qty stok yang sudah dipesan SO lain dan belum dikirim: array(kunci => qty)
    private function _stok_dipesan($kunci = null)
    {
        $this->db->select('a.id_barang, a.id_location, a.opsi, a.ukuran, SUM(a.qty - a.qty_keluar) AS q', false)
            ->from('pm_alokasi_stok a')
            ->join('sales_order so', 'so.id = a.id_so')
            ->where('a.status', 'aktif')
            ->where_in('so.status', $this->so_aktif_stok)
            ->group_by('a.id_barang, a.id_location, a.opsi, a.ukuran');
        if ($kunci !== null) {
            list($b, $l, $o, $u) = explode('|', $kunci, 4);
            $this->db->where(array('a.id_barang' => (int) $b, 'a.id_location' => (int) $l, 'a.opsi' => $o, 'a.ukuran' => $u));
        }
        $hasil = array();
        $q     = $this->db->get();
        foreach ($q ? $q->result() : array() as $r) {
            $hasil[$this->_kunci_stok($r->id_barang, $r->id_location, $r->opsi, $r->ukuran)] = (float) $r->q;
        }
        return $hasil;
    }

    // Alokasi aktif untuk 1 item SO (dengan nama barang & lokasi)
    public function get_alokasi($id_so_detail)
    {
        return $this->db->select('a.*, b.kode_barang, b.nama AS nama_barang, l.zone_name AS nama_lokasi, l.location_code AS kode_lokasi, u.nama AS nama_user')
            ->from('pm_alokasi_stok a')
            ->join('barang b', 'b.id = a.id_barang', 'left')
            ->join('locations l', 'l.id = a.id_location', 'left')
            ->join('users u', 'u.id = a.id_user', 'left')
            ->where('a.id_so_detail', (int) $id_so_detail)
            ->where('a.status', 'aktif')
            ->order_by('a.id', 'ASC')->get()->result();
    }

    // $pilihan: array(kunci_stok => qty). Return array(ok, pesan)
    public function alokasi_stok($id_so_detail, $pilihan, $id_user)
    {
        return $this->_aman('_proses_alokasi', array($id_so_detail, $pilihan, $id_user));
    }

    private function _proses_alokasi($id_so_detail, $pilihan, $id_user)
    {
        $d = $this->db->get_where('sales_order_detail', array('id' => (int) $id_so_detail))->row();
        if (!$d) {
            return array(false, translate('i18n_item_so_tidak_ditemukan'));
        }
        $this->db->trans_begin();
        if (!$this->_kunci_so($d->id_so)) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_so_harus_berstatus_dikonfirmasi_produksi_4'));
        }
        $it = null;
        foreach ($this->get_items($d->id_so) as $x) {
            if ((int) $x->id === (int) $d->id) { $it = $x; }
        }
        if (!$it || $it->sumber !== 'stok') {
            $this->db->trans_rollback();
            return array(false, translate('i18n_sumber_item_ini_bukan_ambil_dari_stok'));
        }

        $total = 0;
        $baris = array();
        foreach ($pilihan as $kunci => $qty) {
            $qty = (float) $qty;
            if ($qty <= 0) {
                continue;
            }
            if (abs($qty - round($qty)) > $this->eps) {
                return $this->_gagal(translate('i18n_qty_dari_stok_harus_bilangan_bulat'));
            }
            if (substr_count((string) $kunci, '|') < 3) {
                return $this->_gagal(translate('i18n_data_stok_tidak_valid'));
            }
            list($b, $l, $o, $u) = explode('|', (string) $kunci, 4);
            // kunci baris stok supaya tidak dipakai dua pesanan bersamaan
            $r = $this->db->query(
                'SELECT COALESCE(SUM(stok), 0) AS stok FROM stok_barang WHERE id_barang = ? AND id_location = ? AND opsi = ? AND ukuran = ? FOR UPDATE',
                array((int) $b, (int) $l, $o, $u)
            )->row();
            $dipesan  = $this->_stok_dipesan($kunci);
            $tersedia = (float) $r->stok - (float) ($dipesan[$kunci] ?? 0);
            if ($qty > $tersedia + $this->eps) {
                return $this->_gagal((translate('i18n_stok_tidak_cukup_tersedia') . ' ') . max(0, $tersedia) . (translate('i18n_diminta') . ' ') . $qty . translate('i18n_mungkin_baru_dipakai_pesanan_lain_muat'));
            }
            $total  += $qty;
            $baris[] = array('id_barang' => (int) $b, 'id_location' => (int) $l, 'opsi' => $o, 'ukuran' => $u, 'qty' => $qty);
        }
        if (!$baris) {
            return $this->_gagal(translate('i18n_isi_qty_minimal_pada_satu_stok'));
        }
        if ($total > $it->p_sisa_proses + $this->eps) {
            return $this->_gagal(translate('i18n_total_qty') . $total . translate('i18n_melebihi_qty_item_yang_belum_dipenuhi') . (float) $it->p_sisa_proses . ').');
        }
        foreach ($baris as $x) {
            $this->db->insert('pm_alokasi_stok', $x + array(
                'id_so'        => (int) $d->id_so,
                'id_so_detail' => (int) $d->id,
                'status'       => 'aktif',
                'id_user'      => $id_user,
            ));
        }
        $this->sinkron_status_so($d->id_so);
        if ($this->db->trans_status() === false) {
            return $this->_gagal(translate('i18n_gagal_menyimpan_pengambilan_stok'));
        }
        $this->db->trans_commit();
        return array(true, $total . (' ' . translate('i18n_qty_dipesan_dari_stok_untuk')) . $it->p_nama . translate('i18n_q_barang_siap_dibuatkan_sj'));
    }

    // Lepas alokasi yang belum dikirim. Return array(ok, pesan, id_so)
    public function lepas_alokasi($id_alokasi)
    {
        return $this->_aman('_proses_lepas', array($id_alokasi));
    }

    private function _proses_lepas($id_alokasi)
    {
        $a = $this->db->get_where('pm_alokasi_stok', array('id' => (int) $id_alokasi))->row();
        if (!$a) {
            return array(false, translate('i18n_data_tidak_ditemukan_3'), 0);
        }
        $this->db->trans_begin();
        if (!$this->_kunci_so($a->id_so)) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_so_sudah_selesai_batal'), (int) $a->id_so);
        }
        $a = $this->db->query('SELECT * FROM pm_alokasi_stok WHERE id = ? FOR UPDATE', array((int) $id_alokasi))->row();
        if ($a->status !== 'aktif') {
            $this->db->trans_rollback();
            return array(false, translate('i18n_stok_ini_sudah_dilepas'), (int) $a->id_so);
        }
        if ((float) $a->qty_keluar > $this->eps) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_sebagian_barang_dari_stok_ini_sudah'), (int) $a->id_so);
        }
        $this->db->where('id', $a->id)->update('pm_alokasi_stok', array('status' => 'batal'));
        $this->sinkron_status_so($a->id_so);
        if ($this->db->trans_status() === false) {
            $r = $this->_gagal(translate('i18n_gagal_melepas_stok'));
            return array($r[0], $r[1], (int) $a->id_so);
        }
        $this->db->trans_commit();
        return array(true, translate('i18n_stok_dilepas_dan_kembali_tersedia_untuk'), (int) $a->id_so);
    }

    // Potong stok untuk 1 item SO saat surat jalan dibuat (FIFO dari alokasi). Return '' jika ok, atau pesan error.
    private function _keluarkan_stok($id_sj, $no_sj, $id_so_detail, $qty, $id_user)
    {
        $alo = $this->db->query(
            "SELECT * FROM pm_alokasi_stok WHERE id_so_detail = ? AND status = 'aktif' AND qty > qty_keluar ORDER BY id ASC FOR UPDATE",
            array((int) $id_so_detail)
        )->result();
        $sisa = $qty;
        foreach ($alo as $a) {
            if ($sisa <= 0) {
                break;
            }
            $ambil = (int) min($sisa, round((float) $a->qty - (float) $a->qty_keluar));
            if ($ambil <= 0) {
                continue;
            }
            $err = $this->_mutasi_stok($a, -$ambil, 'PENGIRIMAN_SO', $id_sj, 'Pengiriman ' . $no_sj, $id_user);
            if ($err !== '') {
                return $err;
            }
            $this->db->query('UPDATE pm_alokasi_stok SET qty_keluar = qty_keluar + ? WHERE id = ?', array($ambil, $a->id));
            $this->db->insert('pm_sj_stok', array('id_sj' => (int) $id_sj, 'id_alokasi' => (int) $a->id, 'qty' => $ambil));
            $sisa -= $ambil;
        }
        return $sisa > 0 ? translate('i18n_stok_yang_dipesan_tidak_cukup_untuk_qty') : '';
    }

    // ================= 3c. AKSESORIS PAKET (mis. handle pintu) =================
    //
    // Aksesoris item SO (sales_order_detail_aksesoris) ikut dikirim bersama barangnya.
    // Saat SJ gudang dibuat / dropship dikonfirmasi diterima, admin memilih stok gudang
    // untuk tiap aksesoris -> stok_barang berkurang (Kartu Stok: PENGIRIMAN_SO).
    // Dicatat di pm_sj_aksesoris (database/pm_sj_aksesoris.sql). Batal SJ -> stok kembali.

    // Handle bawaan dipecah per bukaan pintu. id aksesoris virtual = -(id_so_detail + offset)
    const AKS_BUKAAN = array('bukaan_kanan' => 100000000, 'bukaan_kiri' => 200000000);

    public function ada_tabel_sj_aksesoris()
    {
        static $ada = null;
        if ($ada === null) {
            $ada = $this->db->table_exists('pm_sj_aksesoris') && $this->db->table_exists('sales_order_detail_aksesoris')
                && $this->db->field_exists('pakai_handle', 'kategori_barang');
        }
        return $ada;
    }

    // Aksesoris per item SO + qty yang belum dikirim: array(id_so_detail => array(obj aksesoris))
    // obj: id, nama, qty (total untuk seluruh qty item), terkirim, sisa, qty_item, id_barang_default
    //
    // Sumber aksesoris yang dipotong stoknya:
    //  a. Aksesoris di penawaran/SO -> hanya untuk barang di kategori bertanda "pakai handle"
    //     (pintu kamar / kamar mandi; rolling door dll tidak).
    //  b. Handle bawaan dari master barang pintu (barang.id_handle x qty_handle per pintu)
    //     -> selalu ditambahkan, kecuali handle itu sudah diisi sebagai aksesoris di penawaran.
    //     id-nya negatif (= -id_so_detail) karena tidak ada di sales_order_detail_aksesoris.
    public function get_aksesoris_so($ids_so_detail)
    {
        $ids = array_values(array_filter(array_map('intval', (array) $ids_so_detail)));
        if (!$ids || !$this->ada_tabel_sj_aksesoris()) {
            return array();
        }
        $ada_bawaan = $this->db->field_exists('id_handle', 'barang');
        $this->db->select('d.id, d.qty, k.pakai_handle')
            ->from('sales_order_detail d')
            ->join('barang b', 'b.id = d.id_barang', 'left')
            ->join('kategori_barang k', 'k.id = b.id_kategori', 'left');
        if ($ada_bawaan) {
            $this->db->select('b.id_handle, b.qty_handle, h.nama AS nama_handle, h.warna AS warna_handle')
                ->join('barang h', 'h.id = b.id_handle', 'left');
        }
        $item = array();
        foreach ($this->db->where_in('d.id', $ids)->get()->result() as $d) {
            $item[(int) $d->id] = $d;
        }

        // Alokasi stok pintu per bukaan (kanan/kiri). Urutan sama dengan pemotongan stok di SJ (id ASC),
        // supaya qty handle per bukaan mengikuti pintu yang benar-benar keluar.
        $bukaan_item = array();   // id_so_detail => opsi => array(total)
        $seg_item    = array();   // id_so_detail => list array(opsi, sisa) urut FIFO (untuk qty otomatis di form)
        foreach ($this->db->select('id_so_detail, opsi, qty, qty_keluar')->where_in('id_so_detail', $ids)
            ->where('status', 'aktif')->order_by('id', 'ASC')->get('pm_alokasi_stok')->result() as $a) {
            $idd = (int) $a->id_so_detail;
            if (!isset(self::AKS_BUKAAN[$a->opsi])) {
                continue;
            }
            $bukaan_item[$idd][$a->opsi]['total'] = ($bukaan_item[$idd][$a->opsi]['total'] ?? 0) + (float) $a->qty;
            $sisa = (float) $a->qty - (float) $a->qty_keluar;
            if ($sisa > 0) {
                $seg_item[$idd][] = array($a->opsi, $sisa);
            }
        }
        foreach ($bukaan_item as $idd => $x) {
            $seg_item[$idd] = $seg_item[$idd] ?? array();
        }

        $daftar = array();
        $ids_kat = array();
        foreach ($item as $id => $d) {
            if ((int) $d->pakai_handle === 1) {
                $ids_kat[] = $id;
            }
        }
        if ($ids_kat) {
            foreach ($this->db->select('id, id_so_detail, nama, qty')->where_in('id_so_detail', $ids_kat)
                ->order_by('id', 'ASC')->get('sales_order_detail_aksesoris')->result() as $r) {
                if (trim((string) $r->nama) === '' || (float) $r->qty <= 0) {
                    continue;
                }
                $r->id_barang_default = 0;
                $daftar[(int) $r->id_so_detail][] = $r;
            }
        }
        foreach ($item as $id => $d) {
            if (!$ada_bawaan || (int) $d->id_handle <= 0 || $d->nama_handle === null) {
                continue;
            }
            $nama_h = trim($d->nama_handle . ($d->warna_handle && $d->warna_handle !== '-' ? ' - ' . $d->warna_handle : ''));
            $sudah  = false;
            foreach ($daftar[$id] ?? array() as $r) {
                $x = mb_strtolower(trim($r->nama));
                $y = mb_strtolower(trim($d->nama_handle));
                if ($x !== '' && (mb_strpos($x, $y) !== false || mb_strpos($y, $x) !== false || mb_strpos($x, 'handle') !== false)) {
                    $sudah = true;
                }
            }
            if ($sudah) {
                continue;
            }
            $per = (float) $d->qty_handle > 0 ? (float) $d->qty_handle : 1;
            // Pintu diambil dari stok dengan bukaan kanan / kiri -> handle dipecah sesuai bukaannya
            $bukaan = $bukaan_item[$id] ?? array();
            if ($bukaan) {
                foreach ($bukaan as $opsi => $b) {
                    $daftar[$id][] = (object) array(
                        'id' => -($id + self::AKS_BUKAAN[$opsi]), 'id_so_detail' => $id,
                        'nama' => $nama_h . ' (' . str_replace('_', ' ', $opsi) . ')',
                        'qty' => round($per * $b['total'], 2), 'id_barang_default' => (int) $d->id_handle,
                        'opsi' => $opsi, 'per' => $per, 'seg' => $seg_item[$id],
                    );
                }
                continue;
            }
            $daftar[$id][] = (object) array(
                'id' => -$id, 'id_so_detail' => $id, 'nama' => $nama_h,
                'qty' => round($per * (float) $d->qty, 2), 'id_barang_default' => (int) $d->id_handle,
            );
        }
        if (!$daftar) {
            return array();
        }

        $terkirim = array();
        $q = $this->db->select('sa.id_aksesoris, SUM(sa.qty) AS q', false)
            ->from('pm_sj_aksesoris sa')->join('pm_sj sj', 'sj.id = sa.id_sj')
            ->where('sj.status <>', 'batal')->where_in('sa.id_so_detail', array_keys($daftar))
            ->group_by('sa.id_aksesoris')->get();
        foreach ($q ? $q->result() : array() as $r) {
            $terkirim[(int) $r->id_aksesoris] = (float) $r->q;
        }
        foreach ($daftar as $id => $list) {
            foreach ($list as $r) {
                $r->qty_item = (float) $item[$id]->qty;
                $r->terkirim = $terkirim[(int) $r->id] ?? 0;
                $r->sisa     = max(0, (float) $r->qty - $r->terkirim);
            }
        }
        return $daftar;
    }

    // Pilihan stok gudang untuk aksesoris (stok fisik > 0), barang/opsi aksesoris di atas
    // $prioritas: id barang handle bawaan -> stoknya selalu ikut (daftar umum dibatasi 200 baris)
    public function get_stok_aksesoris($prioritas = array())
    {
        $rows = array();
        $ada  = array();
        foreach (array_unique(array_filter(array_map('intval', (array) $prioritas))) as $idb) {
            foreach ($this->get_stok_tersedia($idb) as $r) {
                $rows[] = $r;
                $ada[$r->kunci] = true;
            }
        }
        foreach ($this->get_stok_tersedia() as $r) {
            if (!isset($ada[$r->kunci])) {
                $rows[] = $r;
            }
        }
        usort($rows, function ($a, $b) {
            $x = ($a->opsi === 'aksesoris') ? 0 : 1;
            $y = ($b->opsi === 'aksesoris') ? 0 : 1;
            return $x !== $y ? $x - $y : strcasecmp((string) $a->nama_barang, (string) $b->nama_barang);
        });
        return $rows;
    }

    // Simpan aksesoris yang ikut dikirim di SJ + potong stok. Dipanggil di dalam transaksi.
    // $aks: array(id_aksesoris => array('qty' => .., 'kunci' => 'id_barang|id_location|opsi|ukuran' atau ''))
    // $ids_detail: id_so_detail yang ikut di SJ ini. Return '' jika ok, atau pesan error.
    private function _aksesoris_keluar($id_sj, $no_sj, $id_so, $ids_detail, $aks, $id_user)
    {
        if (!$this->ada_tabel_sj_aksesoris() || !$ids_detail) {
            return '';
        }
        $daftar = $this->get_aksesoris_so($ids_detail);
        foreach ($daftar as $id_detail => $list) {
            foreach ($list as $a) {
                $in    = $aks[(int) $a->id] ?? array('qty' => 0, 'kunci' => '');
                $qty   = round(max(0, (float) $in['qty']), 2);
                $kunci = trim((string) ($in['kunci'] ?? ''));
                if ($qty <= 0) {
                    // tetap dicatat (qty 0) supaya cetak SJ tahu aksesoris ini memang tidak ikut dikirim
                    $this->db->insert('pm_sj_aksesoris', array(
                        'id_sj' => (int) $id_sj, 'id_so_detail' => (int) $id_detail, 'id_aksesoris' => (int) $a->id,
                        'nama' => mb_substr((string) $a->nama, 0, 255), 'qty' => 0,
                    ));
                    continue;
                }
                if ($qty > $a->sisa + $this->eps) {
                    return 'Aksesoris "' . $a->nama . '": qty melebihi sisa yang belum dikirim (' . (float) $a->sisa . ').';
                }
                $row = array(
                    'id_sj' => (int) $id_sj, 'id_so_detail' => (int) $id_detail, 'id_aksesoris' => (int) $a->id,
                    'nama' => mb_substr((string) $a->nama, 0, 255), 'qty' => $qty,
                    'id_barang' => null, 'id_location' => null, 'opsi' => '', 'ukuran' => '',
                );
                if ($kunci !== '') {
                    $p = explode('|', $kunci, 4);
                    if (count($p) !== 4 || (int) $p[0] <= 0 || (int) $p[1] <= 0) {
                        return 'Aksesoris "' . $a->nama . '": pilihan stok tidak valid.';
                    }
                    if (abs($qty - round($qty)) > $this->eps) {
                        return 'Aksesoris "' . $a->nama . '": qty harus bilangan bulat karena dipotong dari stok.';
                    }
                    // jangan ambil stok yang sudah dipesan SO lain
                    $fisik = $this->db->query(
                        'SELECT COALESCE(SUM(stok), 0) AS s FROM stok_barang WHERE id_barang = ? AND id_location = ? AND opsi = ? AND ukuran = ? FOR UPDATE',
                        array((int) $p[0], (int) $p[1], $p[2], $p[3])
                    )->row();
                    $dipesan  = $this->_stok_dipesan($this->_kunci_stok($p[0], $p[1], $p[2], $p[3]));
                    $tersedia = (float) $fisik->s - (float) array_sum($dipesan);
                    if ($qty > $tersedia + $this->eps) {
                        return 'Aksesoris "' . $a->nama . '": stok tersedia di lokasi itu tinggal ' . max(0, $tersedia) . '.';
                    }
                    $src = (object) array('id_barang' => (int) $p[0], 'id_location' => (int) $p[1], 'opsi' => $p[2], 'ukuran' => $p[3], 'id_so' => (int) $id_so);
                    $err = $this->_mutasi_stok($src, -(int) round($qty), 'PENGIRIMAN_SO', $id_sj, 'Aksesoris ' . $a->nama . ' - ' . $no_sj, $id_user);
                    if ($err !== '') {
                        return 'Aksesoris "' . $a->nama . '": ' . $err;
                    }
                    $row['id_barang']   = (int) $p[0];
                    $row['id_location'] = (int) $p[1];
                    $row['opsi']        = $p[2];
                    $row['ukuran']      = $p[3];
                }
                if (!$this->db->insert('pm_sj_aksesoris', $row)) {
                    return 'Gagal menyimpan aksesoris surat jalan.';
                }
            }
        }
        return '';
    }

    // Aksesoris yang tercatat di satu SJ (untuk cetak). null = SJ lama / fitur belum aktif.
    public function get_sj_aksesoris($id_sj)
    {
        if (!$this->ada_tabel_sj_aksesoris()) {
            return null;
        }
        $rows = $this->db->where('id_sj', (int) $id_sj)->order_by('id', 'ASC')->get('pm_sj_aksesoris')->result();
        return $rows ?: null;
    }

    // Kembalikan stok dari SJ yang dibatalkan
    private function _kembalikan_stok_sj($sj)
    {
        if ($this->ada_tabel_sj_aksesoris()) {
            foreach ($this->db->get_where('pm_sj_aksesoris', array('id_sj' => (int) $sj->id))->result() as $x) {
                if ((int) $x->id_barang > 0 && (float) $x->qty > 0) {
                    $src = (object) array('id_barang' => (int) $x->id_barang, 'id_location' => (int) $x->id_location,
                        'opsi' => (string) $x->opsi, 'ukuran' => (string) $x->ukuran, 'id_so' => (int) $sj->id_so);
                    $this->_mutasi_stok($src, (int) round((float) $x->qty), 'BATAL_PENGIRIMAN_SO', $sj->id, 'Batal aksesoris ' . $x->nama . ' - ' . $sj->no_sj, null);
                }
            }
        }
        if (!$this->db->table_exists('pm_sj_stok')) {
            return;
        }
        foreach ($this->db->get_where('pm_sj_stok', array('id_sj' => (int) $sj->id))->result() as $x) {
            $a = $this->db->query('SELECT * FROM pm_alokasi_stok WHERE id = ? FOR UPDATE', array((int) $x->id_alokasi))->row();
            if (!$a) {
                continue;
            }
            $this->_mutasi_stok($a, (int) round((float) $x->qty), 'BATAL_PENGIRIMAN_SO', $sj->id, 'Batal ' . $sj->no_sj, null);
            $this->db->query('UPDATE pm_alokasi_stok SET qty_keluar = GREATEST(0, qty_keluar - ?) WHERE id = ?', array((float) $x->qty, $a->id));
        }
        $this->db->where('id_sj', (int) $sj->id)->delete('pm_sj_stok');
    }

    // Ubah stok_barang (kombinasi barang+lokasi+opsi+ukuran) sebesar $delta dan catat di stok_riwayat.
    private function _mutasi_stok($a, $delta, $jenis, $id_ref, $ket, $id_user)
    {
        $rows = $this->db->query(
            'SELECT id, stok FROM stok_barang WHERE id_barang = ? AND id_location = ? AND opsi = ? AND ukuran = ? ORDER BY id ASC FOR UPDATE',
            array((int) $a->id_barang, (int) $a->id_location, (string) $a->opsi, (string) $a->ukuran)
        )->result();
        $sebelum = 0;
        foreach ($rows as $r) {
            $sebelum += (int) $r->stok;
        }
        $sesudah = $sebelum + (int) $delta;
        if ($sesudah < 0) {
            return (translate('i18n_stok_fisik_di_gudang_tinggal') . ' ') . $sebelum . translate('i18n_tidak_cukup_cek_stok_opname');
        }
        if ($rows) {
            // sama seperti stok opname: selisih diterapkan ke baris pertama
            $this->db->set('stok', 'stok + (' . (int) $delta . ')', false)->where('id', $rows[0]->id)->update('stok_barang');
        } else {
            $this->db->insert('stok_barang', array(
                'id_barang' => (int) $a->id_barang, 'id_location' => (int) $a->id_location,
                'opsi' => (string) $a->opsi, 'ukuran' => (string) $a->ukuran, 'stok' => $sesudah,
            ));
        }
        $so = $this->db->select('no_so')->get_where('sales_order', array('id' => (int) $a->id_so))->row();
        $this->db->insert('stok_riwayat', array(
            'id_barang'       => (int) $a->id_barang,
            'id_location'     => (int) $a->id_location,
            'jenis_transaksi' => $jenis,
            'id_referensi'    => (int) $id_ref,
            'ukuran'          => (string) $a->ukuran,
            'opsi'            => (string) $a->opsi,
            'qty_masuk'       => $delta > 0 ? (int) $delta : 0,
            'qty_keluar'      => $delta < 0 ? (int) abs($delta) : 0,
            'stok_sebelum'    => $sebelum,
            'stok_sesudah'    => $sesudah,
            'keterangan'      => $ket . ($so ? ' (' . $so->no_so . ')' : ''),
            'created_by'      => $id_user,
            'created_at'      => date('Y-m-d H:i:s'),
        ));
        return '';
    }

    // ================= STATUS SO OTOMATIS =================

    public function sinkron_status_so($id_so)
    {
        $so = $this->db->get_where('sales_order', array('id' => (int) $id_so))->row();
        if (!$so || !in_array($so->status, array('confirmed', 'produksi', 'terkirim', 'selesai'), true)) {
            return;
        }
        // Konsinyasi (titip jual): tagihan = nilai yang sudah laku, dan baru selesai
        // kalau semua barang sudah laku / ditarik kembali.
        $konsinyasi = ($so->jenis_penjualan ?? 'biasa') === 'konsinyasi';
        $t = $this->db->query(
            'SELECT COALESCE(SUM(qty), 0) AS q, COALESCE(SUM(qty_terkirim), 0) AS k'
            . ($konsinyasi ? ', COALESCE(SUM(qty_laku + qty_retur), 0) AS tuntas' : '')
            . ' FROM sales_order_detail WHERE id_so = ?',
            array($so->id)
        )->row();

        if ((float) $t->q > 0 && (float) $t->k + $this->eps >= (float) $t->q) {
            $b       = $this->db->select_sum('nominal', 't')->where('id_so', $so->id)->get('sales_order_pembayaran')->row();
            $tagihan = $konsinyasi ? (float) $so->nilai_laku : (float) $so->grand_total;
            $lunas   = (float) ($b ? $b->t : 0) + 0.005 >= $tagihan;
            $tuntas  = !$konsinyasi || (float) $t->tuntas + $this->eps >= (float) $t->q;
            $baru    = ($lunas && $tuntas) ? 'selesai' : 'terkirim';
        } else {
            $ada = $this->db->where('id_so', $so->id)->where('status <>', 'batal')->count_all_results('pm_spk')
                + $this->db->where('id_so', $so->id)->where('status <>', 'batal')->count_all_results('pm_po')
                + $this->db->where('id_so', $so->id)->where('status <>', 'batal')->count_all_results('pm_sj')
                + ($this->db->table_exists('pm_alokasi_stok')
                    ? $this->db->where('id_so', $so->id)->where('status', 'aktif')->count_all_results('pm_alokasi_stok') : 0);
            $baru = $ada > 0 ? 'produksi' : 'confirmed';
        }
        if ($baru !== $so->status) {
            $this->db->where('id', $so->id)->update('sales_order', array('status' => $baru));
        }
    }

    // ================= DAFTAR & DETAIL =================

    public function get_so_aktif()
    {
        return $this->db->query(
            "SELECT so.id, so.no_so, so.tanggal, so.tgl_kirim, so.status, c.nama AS nama_customer,
                    (SELECT COALESCE(SUM(d.qty), 0) FROM sales_order_detail d WHERE d.id_so = so.id) AS total_qty,
                    (SELECT COALESCE(SUM(d.qty_terkirim), 0) FROM sales_order_detail d WHERE d.id_so = so.id) AS total_kirim,
                    (SELECT COUNT(*) FROM sales_order_detail d WHERE d.id_so = so.id AND d.sumber IS NULL) AS belum_sumber,
                    (SELECT COUNT(*) FROM pm_spk p WHERE p.id_so = so.id AND p.status IN ('antri','proses')) AS spk_jalan,
                    (SELECT COUNT(*) FROM pm_po p WHERE p.id_so = so.id AND p.status IN ('draft','approved','dipesan','sebagian')) AS po_jalan
               FROM sales_order so
               LEFT JOIN customer c ON c.id = so.id_customer
              WHERE so.status IN ('confirmed','produksi','terkirim')
              ORDER BY so.tgl_kirim IS NULL, so.tgl_kirim ASC, so.id ASC"
        )->result();
    }

    public function get_po_list($id_so = 0)
    {
        $this->db->select('po.*, s.nama AS nama_supplier, so.no_so, c.nama AS nama_customer')
            ->from('pm_po po')
            ->join('supplier s', 's.id = po.id_supplier', 'left')
            ->join('sales_order so', 'so.id = po.id_so', 'left')
            ->join('customer c', 'c.id = so.id_customer', 'left');
        if ($id_so) {
            $this->db->where('po.id_so', (int) $id_so);
        } else {
            $this->db->limit(300);
        }
        return $this->db->order_by('po.id', 'DESC')->get()->result();
    }

    // ---------- Menu Purchase Order ----------

    // Jumlah PO per status: array(status => jumlah)
    public function hitung_po_per_status()
    {
        $hasil = array();
        foreach ($this->db->select('status, COUNT(*) AS n', false)->group_by('status')->get('pm_po')->result() as $r) {
            $hasil[$r->status] = (int) $r->n;
        }
        return $hasil;
    }

    // $status: array status (kosong = semua); $q: cari no PO / supplier / no SO / customer
    public function cari_po($status = array(), $q = '')
    {
        $this->db->select('po.*, s.nama AS nama_supplier, so.no_so, c.nama AS nama_customer, u.nama AS nama_pembuat')
            ->from('pm_po po')
            ->join('supplier s', 's.id = po.id_supplier', 'left')
            ->join('sales_order so', 'so.id = po.id_so', 'left')
            ->join('customer c', 'c.id = so.id_customer', 'left')
            ->join('users u', 'u.id = po.id_user', 'left');
        if ($status) {
            $this->db->where_in('po.status', $status);
        }
        if ($q !== '') {
            $this->db->group_start()
                ->like('po.no_po', $q)->or_like('s.nama', $q)
                ->or_like('so.no_so', $q)->or_like('c.nama', $q)
                ->group_end();
        }
        return $this->db->order_by('po.id', 'DESC')->limit(300)->get()->result();
    }

    // PO yang barangnya sedang ditunggu (sudah dipesan, belum diterima semua)
    public function get_po_penerimaan()
    {
        return $this->db->select('po.*, s.nama AS nama_supplier, so.no_so, c.nama AS nama_customer,
                (SELECT COALESCE(SUM(d.qty), 0) FROM pm_po_detail d WHERE d.id_po = po.id) AS total_qty,
                (SELECT COALESCE(SUM(d.qty_diterima), 0) FROM pm_po_detail d WHERE d.id_po = po.id) AS total_terima', false)
            ->from('pm_po po')
            ->join('supplier s', 's.id = po.id_supplier', 'left')
            ->join('sales_order so', 'so.id = po.id_so', 'left')
            ->join('customer c', 'c.id = so.id_customer', 'left')
            ->where_in('po.status', array('dipesan', 'sebagian'))
            ->order_by('po.tgl_butuh IS NULL', '', false)
            ->order_by('po.tgl_butuh', 'ASC')
            ->order_by('po.id', 'ASC')
            ->get()->result();
    }

    // SO berjalan yang punya barang siap dikirim dari gudang (untuk menu Surat Jalan)
    public function get_so_siap_kirim()
    {
        $hasil = array();
        foreach ($this->get_so_aktif() as $so) {
            $siap = 0;
            foreach ($this->get_items($so->id) as $it) {
                $siap += max(0, (float) $it->p_siap_kirim);
            }
            if ($siap > $this->eps) {
                $so->qty_siap = $siap;
                $hasil[]      = $so;
            }
        }
        return $hasil;
    }

    public function get_spk_list($id_so = 0)
    {
        $this->db->select('p.*, so.no_so, c.nama AS nama_customer,
                (SELECT COALESCE(SUM(pd.qty), 0) FROM pm_spk_detail pd WHERE pd.id_spk = p.id) AS total_qty', false)
            ->from('pm_spk p')
            ->join('sales_order so', 'so.id = p.id_so', 'left')
            ->join('customer c', 'c.id = so.id_customer', 'left');
        if ($id_so) {
            $this->db->where('p.id_so', (int) $id_so);
        } else {
            $this->db->limit(300);
        }
        return $this->db->order_by('p.id', 'DESC')->get()->result();
    }

    public function get_sj_list($id_so = 0)
    {
        $this->db->select('sj.*, so.no_so, c.nama AS nama_customer, po.no_po, s.nama AS nama_supplier,
                (SELECT COALESCE(SUM(sd.qty), 0) FROM pm_sj_detail sd WHERE sd.id_sj = sj.id) AS total_qty', false)
            ->from('pm_sj sj')
            ->join('sales_order so', 'so.id = sj.id_so', 'left')
            ->join('customer c', 'c.id = so.id_customer', 'left')
            ->join('pm_po po', 'po.id = sj.id_po', 'left')
            ->join('supplier s', 's.id = po.id_supplier', 'left');
        if ($id_so) {
            $this->db->where('sj.id_so', (int) $id_so);
        } else {
            $this->db->limit(300);
        }
        return $this->db->order_by('sj.id', 'DESC')->get()->result();
    }

    public function get_po($id)
    {
        $h = $this->db->select('po.*, so.no_so, so.no_po_customer, so.status AS status_so, c.nama AS nama_customer,
                ub.nama AS nama_pembuat, ua.nama AS nama_approver', false)
            ->from('pm_po po')
            ->join('sales_order so', 'so.id = po.id_so', 'left')
            ->join('customer c', 'c.id = so.id_customer', 'left')
            ->join('users ub', 'ub.id = po.id_user', 'left')
            ->join('users ua', 'ua.id = po.approved_by', 'left')
            ->where('po.id', (int) $id)->get()->row();
        if (!$h) {
            return null;
        }
        $h->supplier = $this->db->get_where('supplier', array('id' => $h->id_supplier))->row();
        $items       = $this->db->where('id_po', $h->id)->order_by('id', 'ASC')->get('pm_po_detail')->result();
        $terima      = $this->db->where('id_po', $h->id)->order_by('id', 'ASC')->get('pm_po_terima')->result();
        $bayar       = $this->db->select('b.*, u.nama AS nama_user')
            ->from('pm_po_pembayaran b')
            ->join('users u', 'u.id = b.id_user', 'left')
            ->where('b.id_po', $h->id)
            ->order_by('b.tanggal', 'ASC')->order_by('b.id', 'ASC')
            ->get()->result();
        $biaya = $this->ada_tabel_biaya_po()
            ? $this->db->where('id_po', $h->id)->order_by('id', 'ASC')->get('pm_po_biaya')->result()
            : array();
        return array('header' => $h, 'items' => $items, 'biaya' => $biaya, 'penerimaan' => $terima, 'pembayaran' => $bayar);
    }

    // ================= 2c. PEMBAYARAN KE SUPPLIER (finance) =================

    public $jenis_bayar_valid  = array('dp', 'termin', 'pelunasan');
    public $metode_bayar_valid = array('transfer', 'tunai');

    // Status PO yang boleh dibayar: sudah disetujui atasan dan tidak batal
    public $status_boleh_bayar = array('approved', 'dipesan', 'sebagian', 'selesai');

    // $d: tanggal, jenis, nominal, metode, rekening_asal, bukti, keterangan
    public function simpan_pembayaran_po($id_po, $d, $id_user)
    {
        return $this->_aman('_proses_bayar_po', array($id_po, $d, $id_user));
    }

    private function _proses_bayar_po($id_po, $d, $id_user)
    {
        $this->db->trans_begin();
        $q  = $this->db->query('SELECT * FROM pm_po WHERE id = ? FOR UPDATE', array((int) $id_po));
        $po = $q ? $q->row() : null;
        if (!$po) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_po_tidak_ditemukan'));
        }
        if (!in_array($po->status, $this->status_boleh_bayar, true)) {
            $this->db->trans_rollback();
            return array(false, $po->status === 'draft'
                ? 'PO belum disetujui atasan, belum boleh dibayar.'
                : translate('i18n_po_berstatus') . $po->status . translate('i18n_q_tidak_bisa_dibayar'));
        }
        if (!in_array($d['jenis'], $this->jenis_bayar_valid, true)) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_jenis_pembayaran_tidak_valid'));
        }
        if (!in_array($d['metode'], $this->metode_bayar_valid, true)) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_metode_pembayaran_tidak_valid'));
        }
        $nominal = round((float) $d['nominal'], 2);
        $sisa    = round((float) $po->total - (float) $po->total_bayar, 2);
        if ($nominal <= 0) {
            $this->db->trans_rollback();
            return array(false, translate('i18n_nominal_pembayaran_harus_lebih_dari_0'));
        }
        if ($nominal > $sisa + 0.005) {
            $this->db->trans_rollback();
            return array(false, (translate('i18n_nominal_melebihi_sisa_hutang_ke_supplier') . ' ') . number_format($sisa, 0, ',', '.') . ').');
        }
        // pelunasan harus menutup sisa; DP tidak boleh langsung melunasi semua
        if ($d['jenis'] === 'pelunasan' && abs($nominal - $sisa) > 0.005) {
            $this->db->trans_rollback();
            return array(false, (translate('i18n_pelunasan_harus_sama_dengan_sisa_hutang') . ' ') . number_format($sisa, 0, ',', '.') . translate('i18n_pakai_jenis_termin_untuk_bayar_sebagian'));
        }

        $ok = $this->db->insert('pm_po_pembayaran', array(
            'id_po'         => $po->id,
            'tanggal'       => $d['tanggal'],
            'jenis'         => $d['jenis'],
            'nominal'       => $nominal,
            'metode'        => $d['metode'],
            'rekening_asal' => $d['rekening_asal'] !== '' ? $d['rekening_asal'] : null,
            'bukti'         => $d['bukti'] !== '' ? $d['bukti'] : null,
            'keterangan'    => $d['keterangan'],
            'id_user'       => $id_user,
        ));
        if (!$ok) {
            return $this->_gagal(translate('i18n_gagal_menyimpan_pembayaran'));
        }
        $this->_sinkron_bayar_po($po->id);
        if ($this->db->trans_status() === false) {
            return $this->_gagal(translate('i18n_gagal_menyimpan_pembayaran'));
        }
        $this->db->trans_commit();
        return array(true, (translate('pembayaran') . ' ') . strtoupper($d['jenis']) . ' Rp ' . number_format($nominal, 0, ',', '.') . (' ' . translate('i18n_untuk_po') . ' ') . $po->no_po . (' ' . translate('i18n_dicatat')));
    }

    // Hapus pembayaran yang salah input. Return array(ok, pesan, path_bukti_untuk_dihapus)
    public function hapus_pembayaran_po($id_bayar)
    {
        return $this->_aman('_proses_hapus_bayar', array($id_bayar));
    }

    private function _proses_hapus_bayar($id_bayar)
    {
        $b = $this->db->get_where('pm_po_pembayaran', array('id' => (int) $id_bayar))->row();
        if (!$b) {
            return array(false, translate('i18n_data_pembayaran_tidak_ditemukan'), '');
        }
        $this->db->trans_begin();
        $this->db->query('SELECT id FROM pm_po WHERE id = ? FOR UPDATE', array((int) $b->id_po));
        $this->db->where('id', $b->id)->delete('pm_po_pembayaran');
        $this->_sinkron_bayar_po($b->id_po);
        if ($this->db->trans_status() === false) {
            $r = $this->_gagal(translate('i18n_gagal_menghapus_pembayaran'));
            return array($r[0], $r[1], '');
        }
        $this->db->trans_commit();
        return array(true, (translate('i18n_pembayaran_rp') . ' ') . number_format((float) $b->nominal, 0, ',', '.') . (' ' . translate('i18n_dihapus')), (string) $b->bukti);
    }

    // Hitung ulang total_bayar & status_bayar (belum | dp | lunas) di pm_po
    private function _sinkron_bayar_po($id_po)
    {
        $po = $this->db->select('id, total')->get_where('pm_po', array('id' => (int) $id_po))->row();
        if (!$po) {
            return;
        }
        $r     = $this->db->select_sum('nominal', 't')->where('id_po', $po->id)->get('pm_po_pembayaran')->row();
        $bayar = round((float) ($r ? $r->t : 0), 2);
        if ($bayar <= 0) {
            $status = 'belum';
        } elseif ($bayar + 0.005 >= (float) $po->total) {
            $status = 'lunas';
        } else {
            $status = 'dp';
        }
        $this->db->where('id', $po->id)->update('pm_po', array('total_bayar' => $bayar, 'status_bayar' => $status));
    }

    // Daftar PO untuk finance: sudah disetujui & belum lunas
    public function get_po_hutang()
    {
        return $this->db->select('po.*, s.nama AS nama_supplier, so.no_so, c.nama AS nama_customer')
            ->from('pm_po po')
            ->join('supplier s', 's.id = po.id_supplier', 'left')
            ->join('sales_order so', 'so.id = po.id_so', 'left')
            ->join('customer c', 'c.id = so.id_customer', 'left')
            ->where_in('po.status', $this->status_boleh_bayar)
            ->where('po.status_bayar <>', 'lunas')
            ->order_by('po.tanggal', 'ASC')
            ->get()->result();
    }

    // Daftar PO menunggu persetujuan atasan
    public function get_po_menunggu_approval()
    {
        return $this->db->select('po.*, s.nama AS nama_supplier, so.no_so, c.nama AS nama_customer, u.nama AS nama_pembuat')
            ->from('pm_po po')
            ->join('supplier s', 's.id = po.id_supplier', 'left')
            ->join('sales_order so', 'so.id = po.id_so', 'left')
            ->join('customer c', 'c.id = so.id_customer', 'left')
            ->join('users u', 'u.id = po.id_user', 'left')
            ->where('po.status', 'draft')
            ->order_by('po.id', 'ASC')
            ->get()->result();
    }

    public function get_spk($id)
    {
        $h = $this->db->select('p.*, so.no_so, so.no_po_customer, so.tgl_kirim, c.nama AS nama_customer')
            ->from('pm_spk p')
            ->join('sales_order so', 'so.id = p.id_so', 'left')
            ->join('customer c', 'c.id = so.id_customer', 'left')
            ->where('p.id', (int) $id)->get()->row();
        if (!$h) {
            return null;
        }
        $det = $this->db->where('id_spk', $h->id)->order_by('id', 'ASC')->get('pm_spk_detail')->result();
        return array('header' => $h, 'detail' => $det);
    }

    public function get_sj($id)
    {
        $h = $this->db->select('sj.*, so.no_so, so.no_po_customer, c.nama AS nama_customer, c.alamat AS alamat_customer,
                po.no_po, s.nama AS nama_supplier'
                . ($this->db->field_exists('jenis_penjualan', 'sales_order') ? ', so.jenis_penjualan' : ''))
            ->from('pm_sj sj')
            ->join('sales_order so', 'so.id = sj.id_so', 'left')
            ->join('customer c', 'c.id = so.id_customer', 'left')
            ->join('pm_po po', 'po.id = sj.id_po', 'left')
            ->join('supplier s', 's.id = po.id_supplier', 'left')
            ->where('sj.id', (int) $id)->get()->row();
        if (!$h) {
            return null;
        }
        $items = $this->db->select('d.*, sd.qty AS qty_kirim')
            ->from('pm_sj_detail sd')
            ->join('sales_order_detail d', 'd.id = sd.id_so_detail', 'left')
            ->where('sd.id_sj', $h->id)->order_by('sd.id', 'ASC')->get()->result();
        foreach ($items as $it) {
            $it->p_nama = $this->nama_item($it);
        }
        return array('header' => $h, 'items' => $items);
    }
}
