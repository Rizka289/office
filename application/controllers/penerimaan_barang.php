<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penerimaan_barang extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['super_admin', 'staff_purchasing', 'staff_gudang']);
        $this->load->model('Penerimaan_barang_model');
        $this->load->model('Purchase_order_model');
        $this->load->helper('url');
        $this->load->library('form_validation');
    }


    // Halaman Index (Menampilkan List Data)
    public function index()
    {
        $data['page_title']    = 'Daftar Penerimaan Barang';
        $data['page_subtitle'] = 'Riwayat dan status penerimaan barang dari PO';
        $data['active_menu']   = 'penerimaan_barang';

        $filter = [
            'q'      => $this->input->get('q', TRUE),
            'dari'   => $this->input->get('dari', TRUE),
            'sampai' => $this->input->get('sampai', TRUE),
            'status' => $this->input->get('status', TRUE),
        ];

        $data['filter_q']      = $filter['q'];
        $data['filter_dari']   = $filter['dari'];
        $data['filter_sampai'] = $filter['sampai'];
        $data['filter_status'] = $filter['status'];

        $penerimaan_list = $this->Penerimaan_barang_model->get_all_penerimaan($filter);


        // Status hanya 2 (setelah migrasi enum): draft / selesai
        $total_draft   = 0;
        $total_selesai = 0;

        foreach ($penerimaan_list as $row) {
            // Normalisasi string status dari database
            $status_db = strtolower(trim($row->status ?? ''));
            if ($status_db === 'selesai') {
                $total_selesai++;
            } else {
                $total_draft++;
            }
        }

        $data['penerimaan_list'] = $penerimaan_list;
        $data['total_bulan_ini'] = count($penerimaan_list);
        $data['total_draft']     = $total_draft;
        $data['total_selesai']   = $total_selesai;

        $this->load->view('templates/header', $data);
        $this->load->view('staff_gudang/penerimaan_barang_view', $data);
        $this->load->view('templates/footer', $data);
    }

    // Halaman Form Penerimaan Baru
    public function tambah()
    {
        $data['page_title']    = 'Penerimaan Barang Baru';
        $data['page_subtitle'] = 'Input barang masuk dari PO';
        $data['active_menu']   = 'penerimaan_barang';
        $data['no_penerimaan'] = $this->Penerimaan_barang_model->generate_no_penerimaan();
        // Dipakai form untuk dropdown lokasi penempatan & pemilihan barang di luar PO
        $data['locations']   = $this->Penerimaan_barang_model->get_locations();
        // FIX: sebelumnya baris ini memanggil get_po_by_no() TANPA argumen
        // dan hasilnya tidak pernah dipakai di view manapun. Setelah model
        // diperbaiki (parameter $no_po wajib), pemanggilan tanpa argumen ini
        // akan fatal error — jadi dihapus karena memang tidak diperlukan
        // (pencarian PO dilakukan via AJAX ke get_po_items()).
        // FIX: $barang_list sebelumnya tidak pernah dikirim ke view, padahal
        // dipakai JS (BARANG_ALL) untuk dropdown "Tambah Barang Diluar PO".
        $data['barang_list'] = $this->Penerimaan_barang_model->get_all_barang();
        // FIX: dikirim ke view supaya dropdown "Lokasi Simpan" pre-select
        // lokasi yang BENAR-BENAR ada di DB, bukan angka hardcode (id=2)
        // yang bisa saja sudah tidak valid.
        $data['default_location_id'] = $this->Penerimaan_barang_model->get_default_location_id();

        $this->load->view('templates/header', $data);
        $this->load->view('staff_gudang/add_penerimaan_barang_view', $data);
        $this->load->view('templates/footer', $data);
    }

    // Proses Simpan Penerimaan Barang (Draft / Final)
    // Proses Simpan Penerimaan Barang (Draft / Final)
    // Proses Simpan Penerimaan Barang (Draft / Final)
    public function simpan()
    {
        if ($this->input->method() !== 'post') {
            redirect('penerimaan_barang/tambah');
            return;
        }

        $no_po = $this->input->post('no_po', TRUE);

        // Validasi PO benar-benar ada
        $po_data = $this->Penerimaan_barang_model->get_po_by_no($no_po);

        if (!$po_data) {
            $this->session->set_flashdata('error', 'No. PO "' . html_escape($no_po) . '" tidak ditemukan.');
            redirect('penerimaan_barang/tambah');
            return;
        }

        $id_po = is_array($po_data) ? $po_data['id'] : $po_data->id;

        // FIX: endpoint AJAX get_po_items() menolak PO yang status_qc-nya
        // bukan 'menunggu', tapi proses simpan() sebelumnya TIDAK melakukan
        // pengecekan yang sama. Akibatnya PO yang sudah pernah diproses bisa
        // "diterima" lagi dan membuat data tidak konsisten. Sekarang dicek ulang
        // di server, jangan hanya percaya validasi sisi client/AJAX.
        $status_po = is_array($po_data) ? ($po_data['status'] ?? '') : ($po_data->status ?? '');
        $status_po = strtolower(trim((string) $status_po));
        if (!empty($status_po) && $status_po !== 'menunggu') {
            $this->session->set_flashdata('error', 'PO "' . html_escape($no_po) . '" tidak bisa diproses karena status QC-nya sudah "' . html_escape($status_po) . '" (harus "menunggu").');
            redirect('penerimaan_barang/tambah');
            return;
        }

        $aksi   = $this->input->post('aksi', TRUE);
        $status = ($aksi === 'final') ? 'selesai' : 'draft';

        $no_penerimaan = $this->input->post('no_penerimaan', TRUE);
        if (empty($no_penerimaan)) {
            $no_penerimaan = $this->Penerimaan_barang_model->generate_no_penerimaan();
        }

        // Header disesuaikan PERSIS dengan kolom tabel `penerimaan_barang`
        $id_user_session = $this->session->userdata('user_id');

        // FIX: sebelumnya langsung fallback ke angka hardcode 1 kalau session
        // kosong, tanpa pernah memastikan user id itu (baik dari session
        // maupun hardcode) benar-benar ada di tabel `users`. Kalau tidak ada,
        // insert ke `penerimaan_barang` gagal karena FK id_user, dan karena
        // ini terjadi di awal (insert header), SELURUH data batal tersimpan
        // -- baik untuk aksi draft maupun final.
        $id_user = $id_user_session ?: 1;

        if (!$this->Penerimaan_barang_model->user_exists($id_user)) {
            log_message('error', 'Penerimaan_barang::simpan() - id_user tidak valid/tidak ditemukan: ' . var_export($id_user, TRUE) . '. Cek session key "user_id" & pastikan user tsb ada di tabel users.');
            $this->session->set_flashdata('error', 'Sesi user tidak valid (user tidak ditemukan di database). Silakan logout lalu login kembali sebelum menyimpan penerimaan.');
            redirect('penerimaan_barang/tambah');
            return;
        }

        $header = [
            'no_penerimaan'        => $no_penerimaan,
            'id_po'                => $id_po,
            'tanggal_terima'       => $this->input->post('tanggal_terima', TRUE) ?: date('Y-m-d H:i:s'),
            'surat_jalan_supplier' => $this->input->post('no_surat_jalan', TRUE),
            'keterangan'           => $this->input->post('catatan', TRUE),
            'status'               => $status,
            'id_user'              => $id_user,
        ];

        $id_po_detail_arr = $this->input->post('id_po_detail')    ?: [];
        $id_barang_arr    = $this->input->post('id_barang')       ?: [];
        $qty_arr          = $this->input->post('qty_diterima')    ?: [];
        $satuan_arr       = $this->input->post('satuan')          ?: [];
        $kondisi_arr      = $this->input->post('kondisi')         ?: [];
        $ket_item_arr     = $this->input->post('keterangan_item') ?: [];
        $lokasi_arr       = $this->input->post('id_location')     ?: [];

        if (empty($id_barang_arr) || !is_array($id_barang_arr)) {
            $this->session->set_flashdata('error', 'Minimal harus ada 1 item barang yang diterima.');
            redirect('penerimaan_barang/tambah');
            return;
        }

        $foto_files  = isset($_FILES['foto_kondisi']) ? $_FILES['foto_kondisi'] : null;
        $errors_foto = [];
        $items       = [];

        // FIX BUG UTAMA (penyebab data tidak pernah tersimpan):
        // Sebelumnya baris tanpa lokasi dipilih otomatis diisi id_location = 2
        // (angka hardcode). Kalau id 2 di tabel `locations` sudah tidak ada /
        // tidak aktif, insert ke `penerimaan_detail` akan GAGAL karena
        // melanggar foreign key, dan seluruh transaksi di-rollback — sehingga
        // baik draft maupun final tidak pernah benar-benar tersimpan.
        // Sekarang default diambil secara dinamis dari lokasi aktif yang
        // benar-benar ada di DB.
        $default_location_id = $this->Penerimaan_barang_model->get_default_location_id();

        foreach ($id_barang_arr as $i => $id_barang) {
            $id_barang = (int) $id_barang;
            $qty       = isset($qty_arr[$i]) ? (float) $qty_arr[$i] : 0;

            if (!$id_barang || $qty <= 0) {
                continue;
            }

            $kondisi   = isset($kondisi_arr[$i]) && $kondisi_arr[$i] !== '' ? $kondisi_arr[$i] : 'baik';
            $foto_info = null;

            if ($kondisi === 'rusak') {
                $ada_file = $foto_files
                    && isset($foto_files['error'][$i])
                    && $foto_files['error'][$i] === UPLOAD_ERR_OK;

                if (!$ada_file) {
                    $errors_foto[] = 'Baris ke-' . ($i + 1) . ': foto wajib diupload untuk barang berkondisi "Rusak".';
                    continue;
                }

                $foto_info = $this->_upload_foto_kondisi($foto_files, $i);

                if ($foto_info === FALSE) {
                    $errors_foto[] = 'Baris ke-' . ($i + 1) . ': gagal upload foto (format harus jpg/jpeg/png, maks 2MB).';
                    continue;
                }
            }

            // Tentukan id_location: pakai pilihan user, atau fallback ke
            // default yang benar-benar ada di DB (bukan angka hardcode lagi).
            $id_location = !empty($lokasi_arr[$i]) ? (int) $lokasi_arr[$i] : $default_location_id;

            // FIX: validasi id_location benar-benar ada & aktif sebelum insert.
            // Ini mencegah insert gagal senyap karena FK violation, dan
            // memberi pesan yang jelas ke user alih-alih "gagal menyimpan"
            // tanpa keterangan.
            if (empty($id_location) || !$this->Penerimaan_barang_model->location_exists($id_location)) {
                $errors_foto[] = 'Baris ke-' . ($i + 1) . ': lokasi penyimpanan wajib dipilih dan harus valid.';
                continue;
            }

            $items[] = [
                'id_po_detail' => !empty($id_po_detail_arr[$i]) ? (int) $id_po_detail_arr[$i] : NULL,
                'id_barang'    => $id_barang,
                'qty_diterima' => $qty,
                'satuan'       => isset($satuan_arr[$i]) ? $satuan_arr[$i] : NULL,
                'kondisi'      => $kondisi,
                'keterangan'   => isset($ket_item_arr[$i]) && $ket_item_arr[$i] !== '' ? $ket_item_arr[$i] : NULL,
                'id_location'  => $id_location,
                'foto_info'    => $foto_info,
            ];
        }

        if (!empty($errors_foto)) {
            $this->session->set_flashdata('error', implode('<br>', $errors_foto));
            redirect('penerimaan_barang/tambah');
            return;
        }

        if (empty($items)) {
            $this->session->set_flashdata('error', 'Minimal harus ada 1 item barang yang valid (barang, qty > 0, dan lokasi valid).');
            redirect('penerimaan_barang/tambah');
            return;
        }

        // Simpan ke DB via Model
        $id_penerimaan = $this->Penerimaan_barang_model->simpan_penerimaan($header, $items);

        if ($id_penerimaan) {
            if ($status === 'selesai') {
                $this->Penerimaan_barang_model->update_status_po($id_po, 'lolos');
            }

            $this->session->set_flashdata('success', 'Penerimaan barang berhasil disimpan.');
            redirect('penerimaan_barang/detail/' . $id_penerimaan);
        } else {
            // Hapus file fisik jika insert DB gagal
            foreach ($items as $item) {
                if (!empty($item['foto_info']['file_path']) && file_exists(FCPATH . $item['foto_info']['file_path'])) {
                    unlink(FCPATH . $item['foto_info']['file_path']);
                }
            }

            // FIX: sebelumnya pesan error selalu generik ("periksa koneksi
            // DB/log error") padahal error sebenarnya tidak pernah dicatat
            // di mana pun. Sekarang ambil detail error dari model (yang sudah
            // dicatat ke log aplikasi) supaya mudah didiagnosis.
            $detail_error = $this->Penerimaan_barang_model->get_last_error();
            $pesan_user   = 'Gagal menyimpan data penerimaan barang.';
            if ($detail_error) {
                log_message('error', 'Penerimaan_barang::simpan() - ' . $detail_error);
                // Tampilkan detail hanya di environment non-production supaya
                // memudahkan debugging tanpa membocorkan detail DB ke user akhir.
                if (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') {
                    $pesan_user .= ' Detail: ' . html_escape($detail_error);
                }
            }

            $this->session->set_flashdata('error', $pesan_user);
            redirect('penerimaan_barang/tambah');
        }
    }

    private function _upload_foto_kondisi(array $files, int $index)
    {
        $allowed_ext = ['jpg', 'jpeg', 'png'];
        $max_size    = 2 * 1024 * 1024; // 2MB

        $name     = $files['name'][$index] ?? '';
        $tmp_name = $files['tmp_name'][$index] ?? '';
        $size     = $files['size'][$index] ?? 0;
        $type     = $files['type'][$index] ?? '';

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (!$tmp_name || !in_array($ext, $allowed_ext, TRUE) || $size <= 0 || $size > $max_size) {
            return FALSE;
        }

        $relative_path = 'uploads/penerimaan_barang/';
        $upload_dir    = FCPATH . $relative_path;

        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, TRUE) && !is_dir($upload_dir)) {
            return FALSE;
        }

        $new_name = 'rcv_' . date('YmdHis') . '_' . uniqid() . '.' . $ext;

        if (!move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
            return FALSE;
        }

        return [
            'file_name' => $new_name,
            'file_path' => $relative_path . $new_name,
            'file_size' => $size,
            'mime_type' => $type ?: 'image/' . $ext
        ];
    }

    // Halaman Detail Penerimaan Barang
    public function detail($id = null)
    {
        $id = (int) $id;

        if (!$id) {
            show_404();
            return;
        }

        $detail = $this->Penerimaan_barang_model->get_penerimaan_detail($id);

        if (!$detail) {
            show_404();
            return;
        }

        $data['page_title']    = 'Detail Penerimaan Barang';
        $data['page_subtitle'] = 'RCV-' . $detail['header']->no_penerimaan;
        $data['active_menu']   = 'penerimaan_barang';
        $data['penerimaan']    = $detail['header'];
        $data['items']         = $detail['items'];

        $this->load->view('templates/header', $data);
        $this->load->view('staff_gudang/penerimaan_barang_detail_view', $data);
        $this->load->view('templates/footer', $data);
    }

    // Halaman Cetak (tanpa header/footer aplikasi, siap window.print())
    // FIX: method ini sebelumnya sama sekali belum ada, padahal sudah
    // dipakai sebagai link "Cetak" di halaman list & detail.
    public function cetak($id = null)
    {
        $id = (int) $id;

        if (!$id) {
            show_404();
            return;
        }

        $detail = $this->Penerimaan_barang_model->get_penerimaan_detail($id);

        if (!$detail) {
            show_404();
            return;
        }

        $data['penerimaan'] = $detail['header'];
        $data['items']      = $detail['items'];

        // Sengaja TIDAK memuat templates/header & templates/footer supaya
        // hasil cetak bersih dari sidebar/topbar aplikasi.
        $this->load->view('staff_gudang/penerimaan_barang_cetak_view', $data);
    }

    // Endpoint AJAX untuk Fetch Item PO ke JavaScript
    // Hapus salah satu method get_po_items() yang ganda
    // Pastikan hanya tersisa SATU method seperti ini:
    public function get_po_list()
    {
        header('Content-Type: application/json');

        // Ambil semua daftar PO yang belum selesai/diterima dari model
        $list_po = $this->Penerimaan_barang_model->get_all_active_po();

        echo json_encode($list_po);
    }
    public function get_po_items()
    {
        // Matikan output buffer/warning agar JSON tidak rusak
        error_reporting(0);
        header('Content-Type: application/json');

        $no_po = $this->input->get('no_po', TRUE);

        if (empty($no_po)) {
            echo json_encode(['status' => false, 'message' => 'Nomor PO tidak boleh kosong.']);
            return;
        }

        $po_data = $this->Penerimaan_barang_model->get_po_by_no($no_po);

        if ($po_data) {
            // Konversi ke Array jika return dari model berupa Object
            if (is_object($po_data)) {
                $po_data = (array) $po_data;
            }

            // Ambil status_qc atau status (toleran terhadap nama kolom)
            $status_raw = $po_data['status_qc'] ?? $po_data['status'] ?? '';
            $status_po  = strtolower(trim((string) $status_raw));

            // Jika status_qc berisi 'menunggu' ATAU jika status_qc kosong/tidak tercek, izinkan lewat
            if (!empty($status_po) && $status_po !== 'menunggu') {
                echo json_encode([
                    'status'  => false,
                    'message' => 'PO tidak dapat diproses karena status QC adalah "' . $status_raw . '" (harus "menunggu").'
                ]);
                return;
            }

            $supplier = $po_data['supplier_nama'] ?? $po_data['supplier'] ?? $po_data['nama_supplier'] ?? '';
            $items    = $po_data['items'] ?? [];

            // Jika items dikirim dalam bentuk stdClass, konversi ke array
            if (is_object($items)) {
                $items = (array) $items;
            }

            echo json_encode([
                'status'   => true,
                'supplier' => $supplier,
                'items'    => $items
            ]);
        } else {
            echo json_encode(['status' => false, 'message' => 'PO "' . htmlspecialchars($no_po) . '" tidak ditemukan di database.']);
        }
    }
}