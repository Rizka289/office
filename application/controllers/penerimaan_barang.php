<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penerimaan_barang extends MY_Controller
{
    // Batas jumlah foto yang boleh diupload untuk SATU baris barang rusak.
    const MAX_FOTO_PER_ITEM = 5;

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
        $data['page_title']    = translate('penerimaan_barang') ;
        $data['page_subtitle'] = translate('des_index_pb');
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
        $this->load->view('pembelian/penerimaan_barang_index', $data);
        $this->load->view('templates/footer', $data);
    }

    // Halaman Form Penerimaan Baru
    public function tambah()
    {
        $data['page_title']    = 'Penerimaan Barang Baru';
        $data['page_subtitle'] = 'Input barang masuk dari PO';
        $data['active_menu']   = 'penerimaan_barang';
        $data['no_penerimaan'] = $this->Penerimaan_barang_model->generate_no_penerimaan();

        $data['locations']   = $this->Penerimaan_barang_model->get_locations();

        $data['barang_list'] = $this->Penerimaan_barang_model->get_all_barang();

        $data['default_location_id'] = $this->Penerimaan_barang_model->get_default_location_id();

        $this->load->view('templates/header', $data);
        $this->load->view('pembelian/add_penerimaan_barang', $data);
        $this->load->view('templates/footer', $data);
    }


    public function simpan()
    {
        if ($this->input->method() !== 'post') {
            redirect('penerimaan_barang/tambah');
            return;
        }


        $content_length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);

        if (empty($_POST) && $content_length > 0) {
            $post_max = $this->_bytes_ini(ini_get('post_max_size'));
            $pesan    = 'Data gagal dikirim karena ukuran total upload terlalu besar ('
                . round($content_length / 1048576, 2) . ' MB) melebihi batas server ('
                . round($post_max / 1048576, 2) . ' MB). Kurangi jumlah/ukuran foto, '
                . 'atau naikkan post_max_size & upload_max_filesize di php.ini lalu restart Apache.';

            log_message('error', 'Penerimaan_barang::simpan() - ' . $pesan);
            $this->session->set_flashdata('error', $pesan);
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


        $id_user_session = $this->session->userdata('user_id');

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


        $default_location_id = $this->Penerimaan_barang_model->get_default_location_id();


        $rows_valid = [];

        foreach ($id_barang_arr as $i => $id_barang) {
            $id_barang = (int) $id_barang;
            $qty       = isset($qty_arr[$i]) ? (float) $qty_arr[$i] : 0;

            if (!$id_barang || $qty <= 0) {
                continue;
            }


            $id_po_detail_row = !empty($id_po_detail_arr[$i]) ? (int) $id_po_detail_arr[$i] : null;

            if ($id_po_detail_row) {
                $info_sisa = $this->Penerimaan_barang_model->get_sisa_qty_po_detail($id_po_detail_row);

                if ($info_sisa && $qty > $info_sisa['sisa']) {
                    $errors_foto[] = 'Baris ke-' . ($i + 1) . ': qty diterima (' . $qty . ') melebihi sisa PO yang belum diterima (sisa: ' . $info_sisa['sisa'] . ', sudah diterima sebelumnya: ' . $info_sisa['sudah_diterima'] . ' dari total pesanan ' . $info_sisa['qty_pesan'] . ').';
                    continue;
                }
            }

            $kondisi     = isset($kondisi_arr[$i]) && $kondisi_arr[$i] !== '' ? $kondisi_arr[$i] : 'baik';
            $wajib_foto  = ($kondisi === 'rusak');

            if ($wajib_foto) {
                $jml_file = $this->_hitung_file_terkirim($foto_files, $i);

                if ($jml_file < 1) {
                    $errors_foto[] = 'Baris ke-' . ($i + 1) . ': minimal 1 foto wajib diupload untuk barang berkondisi "Rusak".';
                    continue;
                }

                if ($jml_file > self::MAX_FOTO_PER_ITEM) {
                    $errors_foto[] = 'Baris ke-' . ($i + 1) . ': maksimal ' . self::MAX_FOTO_PER_ITEM . ' foto per barang (dikirim ' . $jml_file . ').';
                    continue;
                }
            }


            $id_location = !empty($lokasi_arr[$i]) ? (int) $lokasi_arr[$i] : $default_location_id;

            if (empty($id_location) || !$this->Penerimaan_barang_model->location_exists($id_location)) {
                $errors_foto[] = 'Baris ke-' . ($i + 1) . ': lokasi penyimpanan wajib dipilih dan harus valid.';
                continue;
            }

            $rows_valid[] = [
                'index'        => $i,
                'wajib_foto'   => $wajib_foto,
                'id_po_detail' => !empty($id_po_detail_arr[$i]) ? (int) $id_po_detail_arr[$i] : NULL,
                'id_barang'    => $id_barang,
                'qty_diterima' => $qty,
                'satuan'       => isset($satuan_arr[$i]) ? $satuan_arr[$i] : NULL,
                'kondisi'      => $kondisi,
                'keterangan'   => isset($ket_item_arr[$i]) && $ket_item_arr[$i] !== '' ? $ket_item_arr[$i] : NULL,
                'id_location'  => $id_location,
            ];
        }

        if (!empty($errors_foto)) {
            $this->session->set_flashdata('error', implode('<br>', $errors_foto));
            redirect('penerimaan_barang/tambah');
            return;
        }


        foreach ($rows_valid as $row) {
            $fotos = [];

            if ($row['wajib_foto']) {
                $fotos = $this->_upload_foto_kondisi($foto_files, $row['index']);

                if ($fotos === FALSE) {
                    $errors_foto[] = 'Baris ke-' . ($row['index'] + 1) . ': gagal upload foto (format harus jpg/jpeg/png, maks 2MB per file).';

                    continue;
                }
            }

            unset($row['index'], $row['wajib_foto']);
            $row['fotos'] = $fotos;
            $items[] = $row;
        }

        if (!empty($errors_foto)) {

            $this->_hapus_file_terupload($items);
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


            $this->session->set_flashdata('success', 'Penerimaan barang berhasil disimpan.');
            redirect('penerimaan_barang/detail/' . $id_penerimaan);
        } else {
            // Hapus file fisik jika insert DB gagal
            $this->_hapus_file_terupload($items);


            $detail_error = $this->Penerimaan_barang_model->get_last_error();
            $pesan_user   = 'Gagal menyimpan data penerimaan barang.';
            if ($detail_error) {
                log_message('error', 'Penerimaan_barang::simpan() - ' . $detail_error);

                $pesan_user .= ' Detail: ' . html_escape($detail_error);
            }

            $this->session->set_flashdata('error', $pesan_user);
            redirect('penerimaan_barang/tambah');
        }
    }

    private function _bytes_ini($val)
    {
        $val  = trim((string) $val);
        $unit = strtolower(substr($val, -1));
        $num  = (int) $val;

        switch ($unit) {
            case 'g':
                $num *= 1024;
            case 'm':
                $num *= 1024;
            case 'k':
                $num *= 1024;
        }

        return $num;
    }


    // GANTI METHOD _files_baris DENGAN IMPLEMENTASI BERIKUT:
    private function _files_baris(array $files = null, int $index = null)
    {
        if (empty($files) || !isset($files['name'][$index])) {
            return [];
        }

        $names     = $files['name'][$index];
        $tmp_names = $files['tmp_name'][$index] ?? [];
        $sizes     = $files['size'][$index] ?? [];
        $types     = $files['type'][$index] ?? [];
        $errors    = $files['error'][$index] ?? [];

        // Jika hanya 1 file dikirim bukan sebagai array sub-level
        if (!is_array($names)) {
            return [[
                'name'     => $names,
                'tmp_name' => is_array($tmp_names) ? ($tmp_names[0] ?? '') : $tmp_names,
                'size'     => is_array($sizes) ? ($sizes[0] ?? 0) : $sizes,
                'type'     => is_array($types) ? ($types[0] ?? '') : $types,
                'error'    => is_array($errors) ? ($errors[0] ?? UPLOAD_ERR_NO_FILE) : $errors,
            ]];
        }

        $out = [];
        foreach ($names as $k => $name) {
            if (empty($name)) continue;

            $out[] = [
                'name'     => $name,
                'tmp_name' => $tmp_names[$k] ?? '',
                'size'     => $sizes[$k] ?? 0,
                'type'     => $types[$k] ?? '',
                'error'    => $errors[$k] ?? UPLOAD_ERR_NO_FILE,
            ];
        }

        return $out;
    }

    // GANTI METHOD _hitung_file_terkirim DENGAN IMPLEMENTASI BERIKUT:
    private function _hitung_file_terkirim($files, int $index)
    {
        $jml = 0;
        $baris_files = $this->_files_baris($files, $index);

        foreach ($baris_files as $f) {
            if (isset($f['error']) && $f['error'] === UPLOAD_ERR_OK && !empty($f['tmp_name'])) {
                $jml++;
            }
        }

        return $jml;
    }
   
    private function _hapus_file_terupload(array $items)
    {
        foreach ($items as $item) {
            $fotos = $item['fotos'] ?? [];

            foreach ($fotos as $foto) {
                if (!empty($foto['file_path']) && file_exists(FCPATH . $foto['file_path'])) {
                    unlink(FCPATH . $foto['file_path']);
                }
            }
        }
    }

    private function _upload_foto_kondisi($files, int $index)
    {
        $allowed_ext = ['jpg', 'jpeg', 'png'];
        $max_size    = 2 * 1024 * 1024; // 2MB per file

        $relative_path = 'uploads/penerimaan_barang/';
        $upload_dir    = FCPATH . $relative_path;

        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, TRUE) && !is_dir($upload_dir)) {
            return FALSE;
        }

        $hasil = [];

        foreach ($this->_files_baris($files, $index) as $f) {
            if ($f['error'] !== UPLOAD_ERR_OK || empty($f['tmp_name'])) {
                continue; // slot kosong: diabaikan, bukan error
            }

            $ext  = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            $size = (int) $f['size'];

            if (!in_array($ext, $allowed_ext, TRUE) || $size <= 0 || $size > $max_size) {
                $this->_hapus_file_terupload([['fotos' => $hasil]]);
                return FALSE;
            }

            $new_name = 'rcv_' . date('YmdHis') . '_' . uniqid('', TRUE) . '.' . $ext;

            if (!move_uploaded_file($f['tmp_name'], $upload_dir . $new_name)) {
                $this->_hapus_file_terupload([['fotos' => $hasil]]);
                return FALSE;
            }

            $hasil[] = [
                'file_name' => $new_name,
                'file_path' => $relative_path . $new_name,
                'file_size' => $size,
                'mime_type' => $f['type'] ?: 'image/' . $ext,
            ];
        }

        return $hasil;
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
        $this->load->view('pembelian/penerimaan_barang_detail', $data);
        $this->load->view('templates/footer', $data);
    }


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


        $this->load->view('pembelian/penerimaan_barang_cetak', $data);
    }


    public function get_po_list()
    {
        header('Content-Type: application/json');


        $list_po = $this->Penerimaan_barang_model->get_all_active_po();

        echo json_encode($list_po);
    }
    public function get_po_items()
    {

        error_reporting(0);
        header('Content-Type: application/json');

        $no_po = $this->input->get('no_po', TRUE);

        if (empty($no_po)) {
            echo json_encode(['status' => false, 'message' => 'Nomor PO tidak boleh kosong.']);
            return;
        }

        $po_data = $this->Penerimaan_barang_model->get_po_by_no($no_po);

        if ($po_data) {
            if (is_object($po_data)) {
                $po_data = (array) $po_data;
            }

            $status_raw = $po_data['status_qc'] ?? $po_data['status'] ?? '';
            $status_po  = strtolower(trim((string) $status_raw));

            if (!empty($status_po) && $status_po !== 'menunggu') {
                echo json_encode([
                    'status'  => false,
                    'message' => 'PO tidak dapat diproses karena status QC adalah "' . $status_raw . '" (harus "menunggu").'
                ]);
                return;
            }

            $supplier = $po_data['supplier_nama'] ?? $po_data['supplier'] ?? $po_data['nama_supplier'] ?? '';
            $items    = $po_data['items'] ?? [];

            if (is_object($items)) {
                $items = (array) $items;
            }


            foreach ($items as $it) {
                if (is_object($it)) {
                    $qty_pesan = isset($it->qty_pesan) ? (float) $it->qty_pesan : 0;
                    $sudah     = isset($it->qty_diterima_sebelumnya) ? (float) $it->qty_diterima_sebelumnya : 0;
                    $it->sisa  = $qty_pesan - $sudah;

                    $it->qty_rusak_sebelumnya = isset($it->qty_rusak_sebelumnya) ? (float) $it->qty_rusak_sebelumnya : 0;
                }
            }
            unset($it);

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
