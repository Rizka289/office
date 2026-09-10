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
            // FIX: kolom supplier bernama `nama`, bukan `nama_supplier`
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

    // Mengambil data PO + item detailnya untuk AJAX form tambah.
    // FIX: sebelumnya select s.nama_supplier (kolom itu tidak ada),
    // dan tidak mengirim id_po_detail / id_barang padahal keduanya
    // wajib (FK) saat menyimpan penerimaan_detail.

    // FIX BUG UTAMA: method sebelumnya TIDAK menerima parameter $no_po,
    // sehingga nilai yang dikirim controller diabaikan. Selain itu
    // `$this->db->where('po.no_po')` tanpa nilai pembanding menghasilkan
    // kondisi mentah "WHERE po.no_po" (hanya cek kolom tidak kosong/0),
    // BUKAN "WHERE po.no_po = <input user>". Akibatnya pencarian PO
    // selalu mengembalikan baris pertama yang "kebetulan cocok",
    // bukan PO yang benar-benar dicari user.
    public function get_po_by_no($no_po)
    {
        // FIX: nama kolom status di tabel purchase_order adalah `status_qc`
        // (enum: 'menunggu', 'lolos', 'ditolak') — bukan `status`. Sebelumnya
        // ini menyebabkan SQL error "Unknown column 'po.status'" (500).
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
            pod.satuan    AS satuan
        ');
        $this->db->from('po_detail pod');
        $this->db->join('barang b', 'b.id = pod.id_barang', 'left');
        $this->db->where('pod.id_po', $po->id);
        $items = $this->db->get()->result();

        return [
            'id'       => $po->id,
            'status'   => $po->status_qc,
            'supplier' => $po->supplier,
            'items'    => $items,
        ];
    }
    // FIX: nama kolom status di tabel purchase_order adalah `status_qc`
    // (enum: 'menunggu', 'lolos', 'ditolak'), bukan `status`/`supplier_nama`.
    // Sekarang hanya PO berstatus 'menunggu' yang muncul di daftar/autocomplete.
    public function get_all_active_po()
    {
        $this->db->select('po.no_po, s.nama AS supplier_nama, po.status_qc');
        $this->db->from('purchase_order po');
        $this->db->join('supplier s', 's.id = po.id_supplier', 'left');
        $this->db->where('po.status_qc', 'menunggu');
        $this->db->order_by('po.no_po', 'DESC');
        return $this->db->get()->result();
    }

    // Dipakai simpan_penerimaan (aksi final) untuk memindahkan status_qc PO
    // dari 'menunggu' menjadi 'lolos' setelah barangnya benar-benar diterima,
    // supaya PO tsb tidak muncul lagi di pencarian/daftar PO "menunggu".
    public function update_status_po($id_po, $status_qc)
    {
        $this->db->where('id', $id_po);
        return $this->db->update('purchase_order', ['status_qc' => $status_qc]);
    }
    // Daftar lokasi aktif untuk dropdown "Lokasi Penempatan" per item
    public function get_locations()
    {
        $this->db->select('id, location_code, zone_name, location_type');
        $this->db->from('locations');
        $this->db->where('is_active', 1);
        $this->db->order_by('zone_name', 'ASC');
        $this->db->order_by('location_code', 'ASC');
        return $this->db->get()->result();
    }

    // FIX BUG UTAMA: sebelumnya kode (controller & view) hardcode "id_location = 2"
    // sebagai default. Jika baris id 2 di tabel `locations` sudah dihapus / tidak
    // aktif / memang tidak pernah ada, maka SETIAP insert ke `penerimaan_detail`
    // akan gagal karena melanggar foreign key `id_location` -> `locations.id`.
    // Karena error DB tidak pernah dicatat (lihat simpan_penerimaan), kegagalan ini
    // terlihat seolah-olah "form disubmit tapi tidak ada yang tersimpan".
    // Fungsi ini mengambil lokasi aktif PERTAMA yang benar-benar ada di DB,
    // dipakai sebagai default pengganti angka hardcode.
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

    // Cek apakah sebuah id_location benar-benar ada & aktif di DB.
    // Dipakai controller untuk validasi sebelum insert, supaya tidak
    // mengandalkan angka hardcode yang bisa saja sudah tidak valid.
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

    // FIX: controller sebelumnya fallback ke id_user = 1 (hardcode) kalau
    // session kosong. Kalau id 1 tidak ada di tabel `users` (FK
    // `penerimaan_barang.id_user` -> `users.id`), insert HEADER akan gagal
    // duluan -> seluruh transaksi rollback -> tidak ada satupun data yang
    // tersimpan, baik draft maupun final. Fungsi ini dipakai controller untuk
    // memastikan id_user yang dipakai benar-benar ada sebelum insert.
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

    // Daftar barang master, dipakai untuk baris "Tambah Barang Diluar PO"
    // Modifikasi pada file Penerimaan_barang_model.php
    public function get_all_barang()
    {
        $this->db->select('id, kode_barang, nama AS nama_barang'); // Tambahkan alias nama_barang
        $this->db->from('barang');
        $this->db->order_by('nama', 'ASC');
        return $this->db->get()->result();
    }

    // Menyimpan header + detail penerimaan dalam satu transaksi.
    // Catatan: key pada $header dan setiap elemen $items HARUS persis
    // sama dengan nama kolom di tabel penerimaan_barang /
    // penerimaan_detail (lihat cara controller menyusunnya).
    public function simpan_penerimaan($header, $items)
    {
        $this->last_error = null;

        // FIX: tanpa trans_strict(FALSE), jika ada query lain yang gagal
        // sebelumnya di request yang sama, CI bisa memaksa transaksi ini
        // ikut gagal/rollback walau query di sini sendiri sukses.
        $this->db->trans_strict(FALSE);
        $this->db->trans_begin();

        // 1. Insert ke tabel `penerimaan_barang`
        $this->db->insert('penerimaan_barang', $header);

        // FIX: sebelumnya kegagalan insert (mis. kolom salah, constraint,
        // tipe data tidak cocok) tidak pernah dicatat -> proses gagal secara
        // "senyap" dan sangat sulit didiagnosis. Sekarang error DB ditangkap
        // dan dicatat ke log aplikasi.
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

        // 2. Insert ke `penerimaan_detail` & tabel `photos`
        foreach ($items as $item) {
            $foto_info = isset($item['foto_info']) ? $item['foto_info'] : null;
            unset($item['foto_info']); // Hapus key temp agar tidak dimasukkan ke penerimaan_detail

            $item['id_penerimaan'] = $id_penerimaan;

            // Insert detail penerimaan
            $this->db->insert('penerimaan_detail', $item);

            $db_error = $this->db->error();
            if (!empty($db_error['code'])) {
                // Contoh kasus paling umum: id_location tidak ada di tabel
                // `locations` (FK violation) karena mengandalkan nilai
                // default yang sudah tidak valid.
                $this->last_error = 'Insert detail gagal (id_barang=' . ($item['id_barang'] ?? '-') . '): ' . $db_error['message'];
                log_message('error', 'simpan_penerimaan - ' . $this->last_error);
                $this->db->trans_rollback();
                return FALSE;
            }

            $id_penerimaan_detail = $this->db->insert_id();

            // Insert foto ke tabel polymorphic `photos` jika ada foto
            if ($foto_info && $id_penerimaan_detail) {
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
                    $this->last_error = 'Insert foto gagal (detail id=' . $id_penerimaan_detail . '): ' . $db_error['message'];
                    log_message('error', 'simpan_penerimaan - ' . $this->last_error);
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

    // Mengambil header + detail item satu penerimaan, dipakai untuk
    // halaman detail maupun halaman cetak.
    // FIX: sebelumnya select kolom yang tidak ada sama sekali
    // (qty_terima, kondisi, keterangan, kode_barang, nama_barang,
    // no_surat_jalan, diterima_oleh, gudang) -> selalu error/kosong.
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
        p.file_name AS foto_kondisi,
        p.file_path AS foto_path,
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
        // Join ke tabel photos berdasar imageable_type dan imageable_id
        $this->db->join('photos p', "p.imageable_id = pd.id AND p.imageable_type = 'penerimaan_detail'", 'left');
        $this->db->where('pd.id_penerimaan', $id);
        $items = $this->db->get()->result();

        return [
            'header' => $header,
            'items'  => $items,
        ];
    }
}