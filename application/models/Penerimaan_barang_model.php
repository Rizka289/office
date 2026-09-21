<?php defined('BASEPATH') or exit('No direct script access allowed');

class Penerimaan_barang_model extends CI_Model
{
    // Menyimpan pesan error DB terakhir (untuk debugging & ditampilkan ke user/log)
    protected $last_error = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_last_error()
    {
        return $this->last_error;
    }

    // Mengambil semua daftar penerimaan beserta relasinya
    public function get_all_penerimaan($filter = [])
    {
        $this->db->select('
            pb.id,
            pb.no_penerimaan,
            pb.tanggal_terima,
            pb.status AS status_penerimaan,
            po.no_po,
            s.nama AS supplier,
            u.nama AS diterima_oleh,
            COUNT(pd.id) AS total_item,
            pb.keterangan
        ');
        $this->db->from('penerimaan_barang pb');
        $this->db->join('purchase_order po', 'po.id = pb.id_po', 'left');
        $this->db->join('supplier s', 's.id = po.id_supplier', 'left');
        $this->db->join('users u', 'u.id = pb.id_user', 'left');
        $this->db->join('penerimaan_detail pd', 'pd.id_penerimaan = pb.id', 'left');

        if (!empty($filter['q'])) {
            $this->db->group_start();
            $this->db->like('pb.no_penerimaan', $filter['q']);
            $this->db->or_like('po.no_po', $filter['q']);
            $this->db->or_like('s.nama', $filter['q']);
            $this->db->group_end();
        }

        if (!empty($filter['dari'])) {
            $this->db->where('DATE(pb.tanggal_terima) >=', $filter['dari']);
        }

        if (!empty($filter['sampai'])) {
            $this->db->where('DATE(pb.tanggal_terima) <=', $filter['sampai']);
        }

        if (!empty($filter['status'])) {
            $this->db->where('pb.status', $filter['status']);
        }

        $this->db->group_by('pb.id');
        $this->db->order_by('pb.tanggal_terima', 'DESC');

        return $this->db->get()->result();
    }

    // Membuat nomor penerimaan berurutan per bulan, format: YYYY/MM/0001
    public function generate_no_penerimaan()
    {
        $prefix = date('Y/m');

        $this->db->select('no_penerimaan');
        $this->db->from('penerimaan_barang');
        $this->db->like('no_penerimaan', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $last = $this->db->get()->row();

        $urut = 1;
        if ($last) {
            $parts = explode('/', $last->no_penerimaan);
            $urut  = ((int) end($parts)) + 1;
        }

        return $prefix . '/' . sprintf('%04d', $urut);
    }


    public function get_po_by_no($no_po)
    {

        $this->db->select('po.id, po.no_po, po.status_qc, s.nama AS supplier');
        $this->db->from('purchase_order po');
        $this->db->join('supplier s', 's.id = po.id_supplier', 'left');
        $this->db->where('po.no_po', $no_po);
        $po = $this->db->get()->row();

        if (!$po) {
            return NULL;
        }

        $this->db->select('
            pod.id        AS id_po_detail,
            pod.id_barang AS id_barang,
            b.kode_barang AS kode,
            b.nama AS nama,
            pod.satuan    AS satuan,
            pod.qty       AS qty_pesan,
            COALESCE(SUM(CASE WHEN pdet.kondisi = "baik" THEN pdet.qty_diterima ELSE 0 END), 0) AS qty_diterima_sebelumnya,
            COALESCE(SUM(CASE WHEN pdet.kondisi <> "baik" THEN pdet.qty_diterima ELSE 0 END), 0) AS qty_rusak_sebelumnya
        ');
        $this->db->from('po_detail pod');
        $this->db->join('barang b', 'b.id = pod.id_barang', 'left');
        $this->db->join('penerimaan_detail pdet', 'pdet.id_po_detail = pod.id', 'left');
        $this->db->where('pod.id_po', $po->id);
        $this->db->group_by('pod.id');
        $items = $this->db->get()->result();

        return [
            'id'       => $po->id,
            'status'   => $po->status_qc,
            'supplier' => $po->supplier,
            'items'    => $items,
        ];
    }

    public function get_all_active_po()
    {
        $this->db->select('po.no_po, s.nama AS supplier_nama, po.status_qc');
        $this->db->from('purchase_order po');
        $this->db->join('supplier s', 's.id = po.id_supplier', 'left');
        $this->db->where('po.status_qc', 'menunggu');
        $this->db->order_by('po.no_po', 'DESC');
        return $this->db->get()->result();
    }
    public function update_status_po($id_po, $status_qc)
    {
        $this->db->where('id', $id_po);
        return $this->db->update('purchase_order', ['status_qc' => $status_qc]);
    }
    public function get_locations()
    {
        $this->db->select('id, location_code, zone_name, location_type');
        $this->db->from('locations');
        $this->db->where('is_active', 1);
        $this->db->order_by('zone_name', 'ASC');
        $this->db->order_by('location_code', 'ASC');
        return $this->db->get()->result();
    }

    public function get_default_location_id()
    {
        $this->db->select('id');
        $this->db->from('locations');
        $this->db->where('is_active', 1);
        $this->db->order_by('id', 'ASC');
        $this->db->limit(1);
        $row = $this->db->get()->row();

        return $row ? (int) $row->id : null;
    }


    public function location_exists($id_location)
    {
        if (empty($id_location)) {
            return false;
        }

        $this->db->select('id');
        $this->db->from('locations');
        $this->db->where('id', $id_location);
        $this->db->where('is_active', 1);
        return (bool) $this->db->get()->row();
    }


    public function user_exists($id_user)
    {
        if (empty($id_user)) {
            return false;
        }

        $this->db->select('id');
        $this->db->from('users');
        $this->db->where('id', $id_user);
        return (bool) $this->db->get()->row();
    }


    public function get_all_barang()
    {
        $this->db->select('id, kode_barang, nama AS nama_barang'); // Tambahkan alias nama_barang
        $this->db->from('barang');
        $this->db->order_by('nama', 'ASC');
        return $this->db->get()->result();
    }

    public function get_sisa_qty_po_detail($id_po_detail)
    {

        $this->db->select('
            pod.qty AS qty_pesan,
            COALESCE(SUM(CASE WHEN pd.kondisi = "baik" THEN pd.qty_diterima ELSE 0 END), 0) AS qty_diterima_sebelumnya,
            COALESCE(SUM(CASE WHEN pd.kondisi <> "baik" THEN pd.qty_diterima ELSE 0 END), 0) AS qty_rusak_sebelumnya
        ');
        $this->db->from('po_detail pod');
        $this->db->join('penerimaan_detail pd', 'pd.id_po_detail = pod.id', 'left');
        $this->db->where('pod.id', $id_po_detail);
        $this->db->group_by('pod.id');
        $row = $this->db->get()->row();

        if (!$row) {
            return null;
        }

        $qty_pesan      = (float) $row->qty_pesan;
        $sudah_diterima = (float) $row->qty_diterima_sebelumnya;
        $sudah_rusak    = (float) $row->qty_rusak_sebelumnya;

        return [
            'qty_pesan'      => $qty_pesan,
            'sudah_diterima' => $sudah_diterima,
            'sudah_rusak'    => $sudah_rusak,
            'sisa'           => $qty_pesan - $sudah_diterima,
        ];
    }

    public function upsert_stok_barang($id_barang, $id_location, $qty)
    {
        $sql = "INSERT INTO stok_barang (id_barang, id_location, stok, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    stok = stok + VALUES(stok),
                    updated_at = NOW()";

        $result = $this->db->query($sql, [$id_barang, $id_location, $qty]);

        $db_error = $this->db->error();
        if (!empty($db_error['code'])) {
            $this->last_error = 'Update stok_barang gagal (id_barang=' . $id_barang . ', id_location=' . $id_location . '): ' . $db_error['message'];
            log_message('error', 'upsert_stok_barang - ' . $this->last_error);
            return FALSE;
        }

        return $result !== FALSE;
    }


    public function get_po_fulfillment_status($id_po)
    {
        $this->db->select('
            pod.id AS id_po_detail,
            pod.qty AS qty_pesan,
            COALESCE(SUM(CASE WHEN pd.kondisi = "baik" THEN pd.qty_diterima ELSE 0 END), 0) AS qty_diterima_baik,
            COALESCE(SUM(CASE WHEN pd.kondisi <> "baik" THEN pd.qty_diterima ELSE 0 END), 0) AS qty_diterima_rusak
        ');
        $this->db->from('po_detail pod');
        $this->db->join('penerimaan_detail pd', 'pd.id_po_detail = pod.id', 'left');
        $this->db->where('pod.id_po', $id_po);
        $this->db->group_by('pod.id');
        $rows = $this->db->get()->result();

        if (empty($rows)) {
            return 'menunggu';
        }

        $ada_yang_diterima = false;
        $semua_terpenuhi   = true;

        foreach ($rows as $row) {
            if ($row->qty_diterima_baik > 0 || $row->qty_diterima_rusak > 0) {
                $ada_yang_diterima = true;
            }

            if ($row->qty_diterima_baik < $row->qty_pesan) {
                $semua_terpenuhi = false;
            }
        }

        if ($semua_terpenuhi) {
            return 'selesai';
        }

        return $ada_yang_diterima ? 'partial' : 'menunggu';
    }

    public function insert_stok_riwayat($data_riwayat)
    {
        $data = [
            'id_barang'       => $data_riwayat['id_barang'],
            'id_location'     => $data_riwayat['id_location'],
            'jenis_transaksi' => 'PENERIMAAN_SUPPLIER', // Sesuai enum di SQL
            'id_referensi'    => $data_riwayat['id_referensi'], // ID Header Penerimaan (penerimaan_barang.id)
            'qty_masuk'       => $data_riwayat['qty'],
            'qty_keluar'      => 0,
            'stok_sebelum'    => $data_riwayat['stok_sebelum'],
            'stok_sesudah'    => $data_riwayat['stok_sesudah'],
            'keterangan'      => $data_riwayat['keterangan'] ?? 'Penerimaan Barang Supplier',
            'created_by'      => $data_riwayat['created_by'],
            'created_at'      => date('Y-m-d H:i:s')
        ];

        $result = $this->db->insert('stok_riwayat', $data);

        if (!$result) {
            $db_error = $this->db->error();
            $this->last_error = 'Insert stok_riwayat gagal: ' . $db_error['message'];
            log_message('error', 'insert_stok_riwayat - ' . $this->last_error);
            return FALSE;
        }

        return TRUE;
    }

    public function sync_status_qc_po($id_po)
    {
        if (empty($id_po)) {
            return TRUE; // item di luar PO: tidak ada yang perlu disinkronkan
        }

        $pemenuhan = $this->get_po_fulfillment_status($id_po);
        $status_qc = ($pemenuhan === 'selesai') ? 'lolos' : 'menunggu';

        $this->db->where('id', $id_po);
        $this->db->update('purchase_order', ['status_qc' => $status_qc]);

        $db_error = $this->db->error();
        if (!empty($db_error['code'])) {
            $this->last_error = 'Update status_qc PO gagal (id_po=' . $id_po . '): ' . $db_error['message'];
            log_message('error', 'sync_status_qc_po - ' . $this->last_error);
            return FALSE;
        }

        return TRUE;
    }


    public function simpan_penerimaan($header, $items)
    {
        $this->last_error = null;


        $this->db->trans_strict(FALSE);
        $this->db->trans_begin();

        // 1. Insert ke tabel `penerimaan_barang`
        $this->db->insert('penerimaan_barang', $header);


        $db_error = $this->db->error();
        if (!empty($db_error['code'])) {
            $this->last_error = 'Insert header gagal: ' . $db_error['message'];
            log_message('error', 'simpan_penerimaan - insert header: ' . $db_error['message']);
            $this->db->trans_rollback();
            return FALSE;
        }

        $id_penerimaan = $this->db->insert_id();

        if (!$id_penerimaan) {
            $this->last_error = 'Insert header tidak menghasilkan ID (insert_id = 0).';
            log_message('error', 'simpan_penerimaan - ' . $this->last_error);
            $this->db->trans_rollback();
            return FALSE;
        }

        foreach ($items as $item) {

            $fotos = [];
            if (!empty($item['fotos']) && is_array($item['fotos'])) {
                $fotos = $item['fotos'];
            } elseif (!empty($item['foto_info'])) {
                $fotos = [$item['foto_info']];
            }
            unset($item['fotos'], $item['foto_info']); // key temp, bukan kolom tabel

            $item['id_penerimaan'] = $id_penerimaan;

            $this->db->insert('penerimaan_detail', $item);

            $db_error = $this->db->error();
            if (!empty($db_error['code'])) {
                $this->last_error = 'Insert detail gagal (id_barang=' . ($item['id_barang'] ?? '-') . '): ' . $db_error['message'];
                log_message('error', 'simpan_penerimaan - ' . $this->last_error);
                $this->db->trans_rollback();
                return FALSE;
            }

            $id_penerimaan_detail = $this->db->insert_id();

            if (!empty($fotos) && $id_penerimaan_detail) {
                foreach ($fotos as $foto_info) {
                    if (empty($foto_info['file_name'])) {
                        continue;
                    }

                    $data_photo = [
                        'file_name'      => $foto_info['file_name'],
                        'file_path'      => $foto_info['file_path'],
                        'file_size'      => $foto_info['file_size'],
                        'mime_type'      => $foto_info['mime_type'],
                        'imageable_type' => 'penerimaan_detail',
                        'imageable_id'   => $id_penerimaan_detail,
                        'uploaded_by'    => $header['id_user'] ?? null,
                        'created_at'     => date('Y-m-d H:i:s')
                    ];

                    $this->db->insert('photos', $data_photo);

                    $db_error = $this->db->error();
                    if (!empty($db_error['code'])) {
                        $this->last_error = 'Insert foto gagal (detail id=' . $id_penerimaan_detail
                            . ', payload=' . json_encode($data_photo) . '): ' . $db_error['message'];
                        log_message('error', 'simpan_penerimaan - ' . $this->last_error);
                        $this->db->trans_rollback();
                        return FALSE;
                    }
                }
            }
        }


        $status_final = isset($header['status']) && strtolower(trim((string) $header['status'])) === 'selesai';

        if ($status_final) {
            foreach ($items as $item) {
                $kondisi_item = strtolower(trim((string) ($item['kondisi'] ?? 'baik')));

                // Hanya barang berkondisi baik yang masuk ke stok utama & riwayat stok
                if ($kondisi_item !== 'baik') {
                    continue;
                }

                // A. AMBIL STOK AWAL (stok_sebelum)
                $stok_sebelum = 0;
                $row_stok = $this->db->get_where('stok_barang', [
                    'id_barang'   => $item['id_barang'],
                    'id_location' => $item['id_location']
                ])->row();

                if ($row_stok) {
                    $stok_sebelum = (int) $row_stok->stok;
                }

                $qty_masuk    = (int) $item['qty_diterima'];
                $stok_sesudah = $stok_sebelum + $qty_masuk;

                // B. UPDATE STOK UTAMA (stok_barang)
                $ok_stok = $this->upsert_stok_barang(
                    $item['id_barang'],
                    $item['id_location'],
                    $qty_masuk
                );

                if (!$ok_stok) {
                    $this->db->trans_rollback();
                    return FALSE;
                }

                // C. INSERT LOG KE RIWAYAT STOK (stok_riwayat)
                $ok_riwayat = $this->insert_stok_riwayat([
                    'id_barang'    => $item['id_barang'],
                    'id_location'  => $item['id_location'],
                    'id_referensi' => $id_penerimaan, // ID dari $this->db->insert_id() penerimaan_barang
                    'qty'          => $qty_masuk,
                    'stok_sebelum' => $stok_sebelum,
                    'stok_sesudah' => $stok_sesudah,
                    'keterangan'   => 'Penerimaan No: ' . $header['no_penerimaan'] . ' (SJ: ' . ($header['surat_jalan_supplier'] ?? '-') . ')',
                    'created_by'   => $header['id_user']
                ]);

                if (!$ok_riwayat) {
                    $this->db->trans_rollback();
                    return FALSE;
                }
            }

            if (!empty($header['id_po'])) {
                if (!$this->sync_status_qc_po($header['id_po'])) {
                    $this->db->trans_rollback();
                    return FALSE;
                }
            }
        }
        if ($this->db->trans_status() === FALSE) {
            $this->last_error = $this->last_error ?: 'Transaksi gagal tanpa pesan error spesifik.';
            $this->db->trans_rollback();
            return FALSE;
        }

        $this->db->trans_commit();
        return $id_penerimaan;
    }


    public function get_penerimaan_detail($id)
    {
        $this->db->select('
        pb.id,
        pb.no_penerimaan,
        pb.tanggal_terima,
        pb.surat_jalan_supplier,
        pb.id_user,
        pb.keterangan,
        pb.status,
        po.no_po,
        s.nama AS supplier,
        u.nama AS dibuat_oleh
    ');
        $this->db->from('penerimaan_barang pb');
        $this->db->join('purchase_order po', 'po.id = pb.id_po', 'left');
        $this->db->join('supplier s', 's.id = po.id_supplier', 'left');
        $this->db->join('users u', 'u.id = pb.id_user', 'left');
        $this->db->where('pb.id', $id);
        $header = $this->db->get()->row();

        if (!$header) {
            return NULL;
        }

        $this->db->select('
        pd.id,
        pd.id_po_detail,
        pd.id_barang,
        pd.qty_diterima,
        pd.kondisi,
        pd.keterangan,
        b.kode_barang,
        b.nama,
        COALESCE(pod.satuan, pd.satuan, "-") AS satuan,
        l.location_code,
        l.zone_name
    ');
        $this->db->from('penerimaan_detail pd');
        $this->db->join('barang b', 'b.id = pd.id_barang', 'left');
        $this->db->join('po_detail pod', 'pod.id = pd.id_po_detail', 'left');
        $this->db->join('locations l', 'l.id = pd.id_location', 'left');
        $this->db->where('pd.id_penerimaan', $id);
        $items = $this->db->get()->result();

        if (!empty($items)) {
            $detail_ids = array_map(function ($it) {
                return $it->id;
            }, $items);

            $this->db->select('id, imageable_id, file_name, file_path, mime_type');
            $this->db->from('photos');
            $this->db->where('imageable_type', 'penerimaan_detail');
            $this->db->where_in('imageable_id', $detail_ids);
            $this->db->order_by('id', 'ASC');
            $photos = $this->db->get()->result();

            $map = [];
            foreach ($photos as $p) {
                $map[$p->imageable_id][] = $p;
            }

            foreach ($items as $it) {
                $it->fotos = isset($map[$it->id]) ? $map[$it->id] : [];
                // Kompatibilitas view lama yang masih memakai 1 foto saja.
                $it->foto_kondisi = !empty($it->fotos) ? $it->fotos[0]->file_name : null;
                $it->foto_path    = !empty($it->fotos) ? $it->fotos[0]->file_path : null;
            }
        }

        return [
            'header' => $header,
            'items'  => $items,
        ];
    }
}
