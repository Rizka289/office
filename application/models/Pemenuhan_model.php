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
            return array(false, 'Sumber hanya bisa diatur untuk SO berstatus Dikonfirmasi / Produksi.');
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
            return $this->_gagal('Gagal menyimpan sumber item.');
        }
        $this->db->trans_commit();

        $pesan = $ubah . ' item diperbarui.';
        if ($tolak) {
            $pesan .= ' Sumber tidak diubah karena sudah ada SPK/PO/pengiriman: ' . implode('; ', $tolak) . '.';
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
                return array(array(), 0, 'Nama biaya tambahan wajib diisi (mis. Biaya kirim).');
            }
            if ($per === 'm2') {
                if (!$this->_biaya_po_per_m2_siap()) {
                    return array(array(), 0, 'Hitung biaya per m² belum aktif. Jalankan database/pm_po_biaya_luas.sql dulu.');
                }
                if ($luas <= 0 || $harga <= 0) {
                    return array(array(), 0, 'Luas dan harga per m² untuk "' . $nama . '" wajib diisi.');
                }
            } elseif ($nominal <= 0) {
                return array(array(), 0, 'Nominal "' . $nama . '" harus lebih dari 0.');
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
            return array(false, 'SO harus berstatus Dikonfirmasi / Produksi untuk membuat PO.');
        }
        $jenis = $hdr['jenis'];
        if (!in_array($jenis, array('stok', 'dropship'), true)) {
            return $this->_gagal('Jenis PO tidak valid.');
        }
        $sup = $this->db->get_where('supplier', array('id' => (int) $hdr['id_supplier']))->row();
        if (!$sup) {
            return $this->_gagal('Pilih supplier terlebih dahulu.');
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
                    return $this->_gagal('Ada item yang bukan milik SO ini.');
                }
                $it = $peta[$id_d];
                if ((string) $it->sumber !== $perlu) {
                    return $this->_gagal('Item "' . $it->p_nama . '" sumbernya bukan "' . $perlu . '".');
                }
                if ($qty > $it->p_sisa_proses + $this->eps) {
                    return $this->_gagal('Qty "' . $it->p_nama . '" melebihi sisa yang belum dipesan ('
                        . (float) $it->p_sisa_proses . ').');
                }
                $it->p_sisa_proses -= $qty;
                if ($nama === '') {
                    $nama = $it->p_nama;
                }
            } else {
                if ($jenis === 'dropship') {
                    return $this->_gagal('PO dropship hanya boleh berisi item dari SO.');
                }
                if ($nama === '') {
                    return $this->_gagal('Nama barang pada baris tambahan wajib diisi.');
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
                return $this->_gagal('Harga beli "' . $nama . '" belum diisi.');
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
            return $this->_gagal('Isi qty minimal satu barang.');
        }

        list($biaya_rows, $total_biaya, $err_biaya) = $this->_saring_biaya_po($biaya);
        if ($err_biaya !== '') {
            return $this->_gagal($err_biaya);
        }
        if ($biaya_rows && !$this->ada_tabel_biaya_po()) {
            return $this->_gagal('Tabel biaya tambahan PO belum ada. Jalankan database/pm_po_biaya.sql dulu.');
        }
        $total_barang = round($total, 2);
        $dpp          = round($total_barang + $total_biaya, 2);

        // PPN 11% dari (subtotal barang + biaya tambahan), dibulatkan ke rupiah (sama dengan penawaran)
        $pakai_ppn = !empty($hdr['pakai_ppn']);
        if ($pakai_ppn && !$this->ada_kolom_ppn_po()) {
            return $this->_gagal('Kolom PPN di PO belum ada. Jalankan database/pm_po_ppn.sql dulu.');
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
            'id_so'        => (int) $id_so,
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
            return $this->_gagal('Gagal menyimpan PO.');
        }
        $id_po = (int) $this->db->insert_id();
        foreach ($rows as $r) {
            $r['id_po'] = $id_po;
            if (!$this->db->insert('pm_po_detail', $r)) {
                return $this->_gagal('Gagal menyimpan detail PO.');
            }
        }
        foreach ($biaya_rows as $b) {
            $b['id_po'] = $id_po;
            if (!$this->db->insert('pm_po_biaya', $b)) {
                return $this->_gagal('Gagal menyimpan biaya tambahan PO.');
            }
        }
        $this->sinkron_status_so($id_so);
        if ($this->db->trans_status() === false) {
            return $this->_gagal('Gagal menyimpan PO.');
        }
        $this->db->trans_commit();
        return array(true, 'PO ' . $no['no'] . ' dibuat (draft), menunggu persetujuan atasan.', $id_po);
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
            return array(false, 'PO tidak ditemukan.');
        }
        $this->db->trans_begin();
        if (!$this->_kunci_so($po->id_so)) {
            $this->db->trans_rollback();
            return array(false, 'SO sudah selesai / batal, PO tidak bisa diubah.');
        }
        $po     = $this->db->query('SELECT * FROM pm_po WHERE id = ? FOR UPDATE', array((int) $id_po))->row();
        $alasan = mb_substr(trim((string) $alasan), 0, 255);
        $set    = array();

        if ($aksi === 'setujui') {
            $dari  = array('draft');
            $ke    = 'approved';
            $set   = array('approved_by' => $id_user, 'approved_at' => date('Y-m-d H:i:s'));
            $pesan = 'PO ' . $po->no_po . ' disetujui. Purchasing bisa mengirim PO ke supplier.';
        } elseif ($aksi === 'tolak') {
            $dari = array('draft');
            $ke   = 'batal';
            if ($alasan === '') {
                $this->db->trans_rollback();
                return array(false, 'Isi alasan penolakan PO.');
            }
            $set   = array('approved_by' => $id_user, 'approved_at' => date('Y-m-d H:i:s'), 'alasan_batal' => 'Ditolak atasan: ' . $alasan);
            $pesan = 'PO ' . $po->no_po . ' ditolak. Qty item kembali bisa dibuatkan PO baru.';
        } elseif ($aksi === 'kirim') {
            $dari  = array('approved');
            $ke    = 'dipesan';
            $pesan = 'PO ' . $po->no_po . ' ditandai sudah dipesan ke supplier.';
        } elseif ($aksi === 'batal') {
            $dari = array('draft', 'approved', 'dipesan');
            $ke   = 'batal';
            $r    = $this->db->select_sum('qty_diterima', 't')->where('id_po', $po->id)->get('pm_po_detail')->row();
            if ($r && (float) $r->t > 0) {
                $this->db->trans_rollback();
                return array(false, 'PO sudah ada penerimaan barang, tidak bisa dibatalkan.');
            }
            if ((float) $po->total_bayar > 0) {
                $this->db->trans_rollback();
                return array(false, 'PO sudah ada pembayaran ke supplier. Hapus / selesaikan pembayarannya dulu (finance).');
            }
            if ($alasan !== '') {
                $set['alasan_batal'] = $alasan;
            }
            $pesan = 'PO ' . $po->no_po . ' dibatalkan.';
        } else {
            $this->db->trans_rollback();
            return array(false, 'Aksi tidak dikenal.');
        }
        if (!in_array($po->status, $dari, true)) {
            $this->db->trans_rollback();
            $label = $po->status === 'draft' ? 'belum disetujui atasan' : 'berstatus "' . $po->status . '"';
            return array(false, 'PO ' . $po->no_po . ' ' . $label . ', tidak bisa di-' . $aksi . '.');
        }
        $this->db->where('id', $po->id)->update('pm_po', array_merge(array('status' => $ke), $set));
        $this->sinkron_status_so($po->id_so);
        if ($this->db->trans_status() === false) {
            return $this->_gagal('Gagal mengubah status PO.');
        }
        $this->db->trans_commit();
        return array(true, $pesan);
    }

    // Terima barang PO.
    //  - PO stok     : barang masuk gudang -> siap dibuatkan surat jalan
    //  - PO dropship : konfirmasi barang sudah diterima customer -> otomatis tercatat terkirim
    // $hdr: tanggal, no_sj_supplier, ekspedisi, penerima, catatan
    // $qty_map: id_po_detail => qty
    public function terima_po($id_po, $hdr, $qty_map, $id_user)
    {
        return $this->_aman('_proses_terima', array($id_po, $hdr, $qty_map, $id_user));
    }

    private function _proses_terima($id_po, $hdr, $qty_map, $id_user)
    {
        $po = $this->db->get_where('pm_po', array('id' => (int) $id_po))->row();
        if (!$po) {
            return array(false, 'PO tidak ditemukan.');
        }
        $this->db->trans_begin();
        if (!$this->_kunci_so($po->id_so)) {
            $this->db->trans_rollback();
            return array(false, 'SO sudah selesai / batal.');
        }
        $po = $this->db->query('SELECT * FROM pm_po WHERE id = ? FOR UPDATE', array((int) $id_po))->row();
        if (!in_array($po->status, array('dipesan', 'sebagian'), true)) {
            $this->db->trans_rollback();
            return array(false, 'Penerimaan hanya untuk PO berstatus Dipesan / Diterima sebagian.');
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
                return $this->_gagal('Qty "' . $d->nama_barang . '" melebihi sisa (' . $sisa . ').');
            }
            $terima[] = array($d, $q);
        }
        if (!$terima) {
            return $this->_gagal('Isi qty yang diterima minimal satu barang.');
        }

        $this->db->insert('pm_po_terima', array(
            'id_po'          => $po->id,
            'tanggal'        => $hdr['tanggal'],
            'no_sj_supplier' => $hdr['no_sj_supplier'],
            'catatan'        => $hdr['catatan'],
            'id_user'        => $id_user,
        ));
        $id_trm = (int) $this->db->insert_id();
        foreach ($terima as $t) {
            list($d, $q) = $t;
            $this->db->insert('pm_po_terima_detail', array('id_penerimaan' => $id_trm, 'id_po_detail' => $d->id, 'qty' => $q));
            $this->db->query('UPDATE pm_po_detail SET qty_diterima = qty_diterima + ? WHERE id = ?', array($q, $d->id));
        }

        $pesan = 'Penerimaan PO ' . $po->no_po . ' dicatat.';

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
            foreach ($terima as $t) {
                list($d, $q) = $t;
                if (!$d->id_so_detail) {
                    continue;
                }
                $this->db->insert('pm_sj_detail', array('id_sj' => $id_sj, 'id_so_detail' => $d->id_so_detail, 'qty' => $q));
                $this->db->query('UPDATE sales_order_detail SET qty_terkirim = qty_terkirim + ? WHERE id = ?', array($q, $d->id_so_detail));
            }
            $pesan = 'Dropship PO ' . $po->no_po . ' dikonfirmasi diterima customer (' . $no['no'] . ').';
        }

        $r = $this->db->query('SELECT COALESCE(SUM(qty - qty_diterima), 0) AS sisa FROM pm_po_detail WHERE id_po = ?', array($po->id))->row();
        $this->db->where('id', $po->id)->update('pm_po', array(
            'status' => ((float) $r->sisa <= $this->eps) ? 'selesai' : 'sebagian',
        ));
        $this->sinkron_status_so($po->id_so);

        if ($this->db->trans_status() === false) {
            return $this->_gagal('Gagal menyimpan penerimaan barang.');
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
            return array(false, 'SO harus berstatus Dikonfirmasi / Produksi untuk membuat SPK.');
        }
        $rows = array();
        foreach ($this->get_items($id_so) as $it) {
            $q = isset($qty_map[(int) $it->id]) ? (float) $qty_map[(int) $it->id] : 0;
            if ($q <= 0) {
                continue;
            }
            if ($it->sumber !== 'produksi') {
                return $this->_gagal('Item "' . $it->p_nama . '" bukan item produksi.');
            }
            if ($q > $it->p_sisa_proses + $this->eps) {
                return $this->_gagal('Qty "' . $it->p_nama . '" melebihi sisa yang belum di-SPK (' . (float) $it->p_sisa_proses . ').');
            }
            $rows[] = array('id_so_detail' => (int) $it->id, 'qty' => $q);
        }
        if (!$rows) {
            return $this->_gagal('Isi qty produksi minimal satu item.');
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
            return $this->_gagal('Gagal menyimpan SPK.');
        }
        $id_spk = (int) $this->db->insert_id();
        foreach ($rows as $r) {
            $r['id_spk'] = $id_spk;
            if (!$this->db->insert('pm_spk_detail', $r)) {
                return $this->_gagal('Gagal menyimpan detail SPK.');
            }
        }
        $this->sinkron_status_so($id_so);
        if ($this->db->trans_status() === false) {
            return $this->_gagal('Gagal menyimpan SPK.');
        }
        $this->db->trans_commit();
        return array(true, 'SPK ' . $no['no'] . ' dibuat, masuk antrian produksi.', $id_spk);
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
            return array(false, 'SPK tidak ditemukan.');
        }
        $aturan = array(
            'mulai'   => array(array('antri'), 'proses', array('tgl_mulai' => date('Y-m-d')), 'dimulai'),
            'selesai' => array(array('antri', 'proses'), 'selesai', array('tgl_selesai' => date('Y-m-d')), 'selesai, barang siap dikirim'),
            'batal'   => array(array('antri', 'proses'), 'batal', array(), 'dibatalkan'),
        );
        if (!isset($aturan[$aksi])) {
            return array(false, 'Aksi tidak dikenal.');
        }
        list($dari, $ke, $extra, $kata) = $aturan[$aksi];

        $this->db->trans_begin();
        if (!$this->_kunci_so($spk->id_so)) {
            $this->db->trans_rollback();
            return array(false, 'SO sudah selesai / batal, SPK tidak bisa diubah.');
        }
        $spk = $this->db->query('SELECT * FROM pm_spk WHERE id = ? FOR UPDATE', array((int) $id_spk))->row();
        if (!in_array($spk->status, $dari, true)) {
            $this->db->trans_rollback();
            return array(false, 'SPK berstatus "' . $spk->status . '" tidak bisa di-' . $aksi . '.');
        }
        if ($aksi === 'selesai' && !$spk->tgl_mulai) {
            $extra['tgl_mulai'] = date('Y-m-d');
        }
        $this->db->where('id', $spk->id)->update('pm_spk', array_merge(array('status' => $ke), $extra));
        $this->sinkron_status_so($spk->id_so);
        if ($this->db->trans_status() === false) {
            return $this->_gagal('Gagal mengubah status SPK.');
        }
        $this->db->trans_commit();
        return array(true, 'SPK ' . $spk->no_spk . ' ' . $kata . '.');
    }

    // ================= 3. SURAT JALAN (dari gudang) =================

    // $hdr: tanggal, alamat_kirim, ekspedisi, no_kendaraan, catatan; $qty_map: id_so_detail => qty
    // Return: array(ok, pesan, id_sj)
    public function simpan_sj($id_so, $hdr, $qty_map, $id_user)
    {
        return $this->_aman('_proses_sj', array($id_so, $hdr, $qty_map, $id_user));
    }

    private function _proses_sj($id_so, $hdr, $qty_map, $id_user)
    {
        $this->db->trans_begin();
        if (!$this->_kunci_so($id_so)) {
            $this->db->trans_rollback();
            return array(false, 'SO harus berstatus Dikonfirmasi / Produksi untuk membuat surat jalan.');
        }
        $rows = array();
        foreach ($this->get_items($id_so) as $it) {
            $q = isset($qty_map[(int) $it->id]) ? (float) $qty_map[(int) $it->id] : 0;
            if ($q <= 0) {
                continue;
            }
            if (!in_array($it->sumber, array('produksi', 'beli', 'stok'), true)) {
                return $this->_gagal('Item "' . $it->p_nama . '" tidak dikirim dari gudang.');
            }
            if ($it->sumber === 'stok' && abs($q - round($q)) > $this->eps) {
                return $this->_gagal('Qty kirim "' . $it->p_nama . '" dari stok harus bilangan bulat.');
            }
            if ($q > $it->p_siap_kirim + $this->eps) {
                return $this->_gagal('Qty kirim "' . $it->p_nama . '" melebihi barang siap di gudang (' . (float) $it->p_siap_kirim . ').');
            }
            $rows[] = array('id_so_detail' => (int) $it->id, 'qty' => $q, '_stok' => $it->sumber === 'stok', '_nama' => $it->p_nama);
        }
        if (!$rows) {
            return $this->_gagal('Isi qty kirim minimal satu item.');
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
            return $this->_gagal('Gagal menyimpan surat jalan.');
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
        $this->sinkron_status_so($id_so);
        if ($this->db->trans_status() === false) {
            return $this->_gagal('Gagal menyimpan surat jalan.');
        }
        $this->db->trans_commit();
        return array(true, 'Surat jalan ' . $no['no'] . ' dibuat.', $id_sj);
    }

    public function batal_sj($id_sj)
    {
        return $this->_aman('_proses_batal_sj', array($id_sj));
    }

    private function _proses_batal_sj($id_sj)
    {
        $sj = $this->db->get_where('pm_sj', array('id' => (int) $id_sj))->row();
        if (!$sj) {
            return array(false, 'Surat jalan tidak ditemukan.');
        }
        if ($sj->jenis === 'dropship') {
            return array(false, 'Surat jalan dropship berasal dari penerimaan PO, tidak bisa dibatalkan di sini.');
        }
        $this->db->trans_begin();
        if (!$this->_kunci_so($sj->id_so)) {
            $this->db->trans_rollback();
            return array(false, 'SO sudah selesai / batal, surat jalan tidak bisa dibatalkan.');
        }
        $sj = $this->db->query('SELECT * FROM pm_sj WHERE id = ? FOR UPDATE', array((int) $id_sj))->row();
        if ($sj->status !== 'terkirim') {
            $this->db->trans_rollback();
            return array(false, 'Surat jalan sudah dibatalkan.');
        }
        $cek_konsinyasi = $this->db->field_exists('qty_laku', 'sales_order_detail');
        foreach ($this->db->get_where('pm_sj_detail', array('id_sj' => $sj->id))->result() as $d) {
            // Konsinyasi: barang yang sudah dilaporkan laku / ditarik tidak bisa "batal dikirim"
            if ($cek_konsinyasi) {
                $sod = $this->db->select('qty_terkirim, qty_laku, qty_retur')->get_where('sales_order_detail', array('id' => (int) $d->id_so_detail))->row();
                if ($sod && (float) $sod->qty_terkirim - (float) $d->qty + $this->eps < (float) $sod->qty_laku + (float) $sod->qty_retur) {
                    return $this->_gagal('Surat jalan tidak bisa dibatalkan: sebagian barangnya sudah dilaporkan laku / ditarik (konsinyasi). Batalkan dokumen konsinyasinya dulu.');
                }
            }
            $this->db->query('UPDATE sales_order_detail SET qty_terkirim = GREATEST(0, qty_terkirim - ?) WHERE id = ?', array((float) $d->qty, $d->id_so_detail));
        }
        // kembalikan stok yang keluar lewat SJ ini
        $this->_kembalikan_stok_sj($sj);
        $this->db->where('id', $sj->id)->update('pm_sj', array('status' => 'batal'));
        $this->sinkron_status_so($sj->id_so);
        if ($this->db->trans_status() === false) {
            return $this->_gagal('Gagal membatalkan surat jalan.');
        }
        $this->db->trans_commit();
        return array(true, 'Surat jalan ' . $sj->no_sj . ' dibatalkan, qty kembali ke "siap kirim". Barang dari stok dikembalikan ke stok gudang.');
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
            return array(false, 'Item SO tidak ditemukan.');
        }
        $this->db->trans_begin();
        if (!$this->_kunci_so($d->id_so)) {
            $this->db->trans_rollback();
            return array(false, 'SO harus berstatus Dikonfirmasi / Produksi.');
        }
        $it = null;
        foreach ($this->get_items($d->id_so) as $x) {
            if ((int) $x->id === (int) $d->id) { $it = $x; }
        }
        if (!$it || $it->sumber !== 'stok') {
            $this->db->trans_rollback();
            return array(false, 'Sumber item ini bukan "Ambil dari stok". Atur sumbernya dulu.');
        }

        $total = 0;
        $baris = array();
        foreach ($pilihan as $kunci => $qty) {
            $qty = (float) $qty;
            if ($qty <= 0) {
                continue;
            }
            if (abs($qty - round($qty)) > $this->eps) {
                return $this->_gagal('Qty dari stok harus bilangan bulat.');
            }
            if (substr_count((string) $kunci, '|') < 3) {
                return $this->_gagal('Data stok tidak valid.');
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
                return $this->_gagal('Stok tidak cukup (tersedia ' . max(0, $tersedia) . ', diminta ' . $qty . '). Mungkin baru dipakai pesanan lain, muat ulang halaman.');
            }
            $total  += $qty;
            $baris[] = array('id_barang' => (int) $b, 'id_location' => (int) $l, 'opsi' => $o, 'ukuran' => $u, 'qty' => $qty);
        }
        if (!$baris) {
            return $this->_gagal('Isi qty minimal pada satu stok.');
        }
        if ($total > $it->p_sisa_proses + $this->eps) {
            return $this->_gagal('Total qty (' . $total . ') melebihi qty item yang belum dipenuhi (' . (float) $it->p_sisa_proses . ').');
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
            return $this->_gagal('Gagal menyimpan pengambilan stok.');
        }
        $this->db->trans_commit();
        return array(true, $total . ' qty dipesan dari stok untuk "' . $it->p_nama . '". Barang siap dibuatkan surat jalan.');
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
            return array(false, 'Data tidak ditemukan.', 0);
        }
        $this->db->trans_begin();
        if (!$this->_kunci_so($a->id_so)) {
            $this->db->trans_rollback();
            return array(false, 'SO sudah selesai / batal.', (int) $a->id_so);
        }
        $a = $this->db->query('SELECT * FROM pm_alokasi_stok WHERE id = ? FOR UPDATE', array((int) $id_alokasi))->row();
        if ($a->status !== 'aktif') {
            $this->db->trans_rollback();
            return array(false, 'Stok ini sudah dilepas.', (int) $a->id_so);
        }
        if ((float) $a->qty_keluar > $this->eps) {
            $this->db->trans_rollback();
            return array(false, 'Sebagian barang dari stok ini sudah dikirim. Batalkan surat jalannya dulu.', (int) $a->id_so);
        }
        $this->db->where('id', $a->id)->update('pm_alokasi_stok', array('status' => 'batal'));
        $this->sinkron_status_so($a->id_so);
        if ($this->db->trans_status() === false) {
            $r = $this->_gagal('Gagal melepas stok.');
            return array($r[0], $r[1], (int) $a->id_so);
        }
        $this->db->trans_commit();
        return array(true, 'Stok dilepas dan kembali tersedia untuk pesanan lain.', (int) $a->id_so);
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
        return $sisa > 0 ? 'stok yang dipesan tidak cukup untuk qty kirim.' : '';
    }

    // Kembalikan stok dari SJ yang dibatalkan
    private function _kembalikan_stok_sj($sj)
    {
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
            return 'stok fisik di gudang tinggal ' . $sebelum . ', tidak cukup. Cek stok opname.';
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
            return array(false, 'PO tidak ditemukan.');
        }
        if (!in_array($po->status, $this->status_boleh_bayar, true)) {
            $this->db->trans_rollback();
            return array(false, $po->status === 'draft'
                ? 'PO belum disetujui atasan, belum boleh dibayar.'
                : 'PO berstatus "' . $po->status . '" tidak bisa dibayar.');
        }
        if (!in_array($d['jenis'], $this->jenis_bayar_valid, true)) {
            $this->db->trans_rollback();
            return array(false, 'Jenis pembayaran tidak valid.');
        }
        if (!in_array($d['metode'], $this->metode_bayar_valid, true)) {
            $this->db->trans_rollback();
            return array(false, 'Metode pembayaran tidak valid.');
        }
        $nominal = round((float) $d['nominal'], 2);
        $sisa    = round((float) $po->total - (float) $po->total_bayar, 2);
        if ($nominal <= 0) {
            $this->db->trans_rollback();
            return array(false, 'Nominal pembayaran harus lebih dari 0.');
        }
        if ($nominal > $sisa + 0.005) {
            $this->db->trans_rollback();
            return array(false, 'Nominal melebihi sisa hutang ke supplier (Rp ' . number_format($sisa, 0, ',', '.') . ').');
        }
        // pelunasan harus menutup sisa; DP tidak boleh langsung melunasi semua
        if ($d['jenis'] === 'pelunasan' && abs($nominal - $sisa) > 0.005) {
            $this->db->trans_rollback();
            return array(false, 'Pelunasan harus sama dengan sisa hutang (Rp ' . number_format($sisa, 0, ',', '.') . '). Pakai jenis "Termin" untuk bayar sebagian.');
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
            return $this->_gagal('Gagal menyimpan pembayaran.');
        }
        $this->_sinkron_bayar_po($po->id);
        if ($this->db->trans_status() === false) {
            return $this->_gagal('Gagal menyimpan pembayaran.');
        }
        $this->db->trans_commit();
        return array(true, 'Pembayaran ' . strtoupper($d['jenis']) . ' Rp ' . number_format($nominal, 0, ',', '.') . ' untuk PO ' . $po->no_po . ' dicatat.');
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
            return array(false, 'Data pembayaran tidak ditemukan.', '');
        }
        $this->db->trans_begin();
        $this->db->query('SELECT id FROM pm_po WHERE id = ? FOR UPDATE', array((int) $b->id_po));
        $this->db->where('id', $b->id)->delete('pm_po_pembayaran');
        $this->_sinkron_bayar_po($b->id_po);
        if ($this->db->trans_status() === false) {
            $r = $this->_gagal('Gagal menghapus pembayaran.');
            return array($r[0], $r[1], '');
        }
        $this->db->trans_commit();
        return array(true, 'Pembayaran Rp ' . number_format((float) $b->nominal, 0, ',', '.') . ' dihapus.', (string) $b->bukti);
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
