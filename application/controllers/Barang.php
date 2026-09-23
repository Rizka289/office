<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Lokasi file: application/controllers/super_admin/Barang.php
// URL akses: domain.com/super_admin/barang

class Barang extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRole('super_admin'); // hanya super admin yang boleh akses seluruh method di sini
        $this->load->model('Barang_model');
        $this->load->model('Kategori_barang_model'); // 1. Load model kategori
        $this->load->helper('url');
    }

    public function index()
    {
        $data['title'] = translate('list_barang');
        $data['page_title']     =  translate('list_barang');
        $data['active_menu']   = 'barang';


        // 2. Ambil data kategori list dari model untuk dikirim ke view
        $data['kategoriList'] = $this->Kategori_barang_model->get_kat_barang_paginated('', 1000, 0);

        $this->load->view('templates/header', $data);
        $this->load->view('master_produk/barang_index', $data);
        $this->load->view('templates/footer', $data);
    }

    // Cek "kosong" yang benar: hanya null / string kosong / spasi saja.
    // JANGAN pakai empty(): di PHP empty("0") bernilai TRUE, sehingga isian "0" dianggap kosong.
    private function is_blank($value)
    {
        return $value === null || trim((string) $value) === '';
    }

    // Wrapper jsonResponse: selalu sertakan token CSRF terbaru supaya form di halaman
    // tidak memakai token lama (penyebab HTTP 403 pada request POST berikutnya).
    private function respond(array $payload)
    {
        $payload['csrf_hash'] = $this->security->get_csrf_hash();
        $this->jsonResponse($payload);
    }

    // Endpoint AJAX: ambil data nama barang dengan pagination (max 5/halaman) & search
    public function list_data()
    {
        $search = $this->input->get('search', true);
        $page   = (int) $this->input->get('page', true);
        if ($page < 1) {
            $page = 1;
        }

        $perPage = 5;
        $offset  = ($page - 1) * $perPage;

        $total = $this->Barang_model->count_barang($search);
        $data  = $this->Barang_model->get_barang_paginated($search, $perPage, $offset);

        $this->respond([
            'status'       => true,
            'data'         => $data,
            'total'        => (int) $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'total_pages'  => (int) ceil($total / $perPage),
        ]);
    }
    // Helper: bersihkan input harga menjadi float yang aman untuk SQL.
    // Aturan: bila ada titik DAN koma, pemisah yang paling akhir = desimal.
    // Bila hanya satu jenis pemisah: muncul >1 kali = pemisah ribuan, muncul 1 kali = desimal.
    private function parse_harga_decimal($input_harga)
    {
        if ($input_harga === null || $input_harga === '') return 0;

        $clean = preg_replace('/[^0-9.,]/', '', trim($input_harga));
        if ($clean === '') return 0;

        $hasDot   = strpos($clean, '.') !== false;
        $hasComma = strpos($clean, ',') !== false;

        if ($hasDot && $hasComma) {
            if (strrpos($clean, ',') > strrpos($clean, '.')) {
                // Format Indonesia: 15.000,50
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
                // Format US: 15,000.50
                $clean = str_replace(',', '', $clean);
            }
        } elseif ($hasComma) {
            // 15000,50 (desimal) atau 1,000,000 (ribuan)
            $clean = (substr_count($clean, ',') > 1)
                ? str_replace(',', '', $clean)
                : str_replace(',', '.', $clean);
        } elseif ($hasDot) {
            // 15000.50 (desimal) atau 1.000.000 (ribuan)
            if (substr_count($clean, '.') > 1) {
                $clean = str_replace('.', '', $clean);
            }
        }

        return (float) $clean;
    }

    // Susun pesan gagal dari error DB (detail hanya tampil di mode development)
    private function db_error_message($default)
    {
        $err = $this->Barang_model->last_error;

        if (isset($err['code']) && (int) $err['code'] === 1062) {
            return 'Kode barang sudah dipakai, gunakan kode lain.';
        }
        if (ENVIRONMENT === 'development' && !empty($err['message'])) {
            return $default . ' [DB ' . $err['code'] . ': ' . $err['message'] . ']';
        }
        return $default;
    }


    // Method simpan data via AJAX
    public function simpan()
    {
        // Ambil data dari input POST
        $kode        = trim((string) $this->input->post('kode', true));
        $nama        = trim((string) $this->input->post('nama', true));
        $idkategori  = $this->input->post('id_kategori', true);
        $jenis       = $this->input->post('jenis', true);
        $satuan      = $this->input->post('satuan', true);
        $dimensi     = trim((string) $this->input->post('dimensi', true));
        $harga_raw   = $this->input->post('harga_satuan', true);
        $stokMinimum = $this->input->post('stok_minimum', true);

        // 1. Validasi field yang wajib diisi (Gunakan $harga_raw di sini)
        if (
            $this->is_blank($kode) || $this->is_blank($nama) || empty($idkategori) || $this->is_blank($jenis)
            || $this->is_blank($satuan) || $this->is_blank($dimensi) || $this->is_blank($harga_raw)
            || $this->is_blank($stokMinimum)
        ) {
            $this->respond(['status' => false, 'message' => 'Semua field wajib diisi!']);
            return;
        }

        // 2. Parsel & konversi harga menjadi nilai desimal yang aman untuk SQL
        $harga_clean = $this->parse_harga_decimal($harga_raw);

        // 3. Pastikan id_kategori yang dikirim benar-benar ada di tabel kategori_barang
        if (!$this->Kategori_barang_model->get_by_id($idkategori)) {
            $this->respond(['status' => false, 'message' => 'Kategori tidak valid!']);
            return;
        }

        // 3b. Kode barang tidak boleh kembar
        if ($this->Barang_model->kode_exists($kode)) {
            $this->respond(['status' => false, 'message' => 'Kode barang sudah dipakai, gunakan kode lain.']);
            return;
        }
        $is_produced = $this->input->post('is_produced', true);

        // 4. Susun array data untuk dikirim ke model
        $data = array(
            'kode_barang'  => $kode,
            'nama'         => $nama,
            'id_kategori'  => $idkategori,
            'jenis_barang' => $jenis,
            'satuan'       => $satuan,
            'dimensi'      => $dimensi,
            'is_produced'  => ($is_produced !== null) ? (int)$is_produced : 0,
            'harga_satuan' => $harga_clean,
            'stok_minimum' => $stokMinimum,
            'created_at'   => date('Y-m-d H:i:s'),
        );

        $simpan = $this->Barang_model->insert_nama_barang($data);

        $this->respond(
            $simpan
                ? ['status' => true, 'message' => 'Data berhasil disimpan']
                : ['status' => false, 'message' => $this->db_error_message('Gagal menyimpan data')]
        );
    }
    // Ambil data nama barang by id (untuk mengisi form edit)
    public function get_by_id($id)
    {
        $row = $this->Barang_model->get_by_id($id);

        if ($row) {
            $this->respond(['status' => true, 'data' => $row]);
        } else {
            $this->respond(['status' => false, 'message' => 'Data tidak ditemukan']);
        }
    }

    // Method update data via AJAX
    public function update()
    {
        $id          = $this->input->post('id', true);
        $kode        = $this->input->post('kode', true);
        $nama        = $this->input->post('nama', true);
        $idkategori  = $this->input->post('id_kategori', true);
        $jenis       = $this->input->post('jenis', true);
        $harga_raw   = $this->input->post('harga_satuan', true);
        $satuan      = $this->input->post('satuan', true);
        $dimensi     = $this->input->post('dimensi', true);
        $stokMinimum = $this->input->post('stok_minimum', true);

        if (
            empty($id) || $this->is_blank($kode) || $this->is_blank($nama) || empty($idkategori) || $this->is_blank($jenis)
            || $this->is_blank($satuan) || $this->is_blank($dimensi) || $this->is_blank($harga_raw)
            || $this->is_blank($stokMinimum)
        ) {
            $this->respond(['status' => false, 'message' => 'Semua field wajib diisi!']);
            return;
        }

        // Konversi nilai harga yang diinput
        $harga_clean = $this->parse_harga_decimal($harga_raw);

        if (!$this->Kategori_barang_model->get_by_id($idkategori)) {
            $this->respond(['status' => false, 'message' => 'Kategori tidak valid!']);
            return;
        }
        $is_produced = $this->input->post('is_produced', true);
        $data = array(
            'kode_barang'  => $kode,
            'nama'         => $nama,
            'id_kategori'  => $idkategori,
            'jenis_barang' => $jenis,
            'satuan'       => $satuan,
            'dimensi'      => $dimensi,
            'is_produced'  => ($is_produced !== null) ? (int)$is_produced : 0,
            'harga_satuan' => $harga_clean,
            'stok_minimum' => $stokMinimum,
        );

        $update = $this->Barang_model->update_nama_barang($id, $data);

        // $update bernilai true walau tidak ada data yang berubah
        $this->respond(
            $update
                ? ['status' => true, 'message' => 'Data berhasil diperbarui']
                : ['status' => false, 'message' => 'Gagal memperbarui data']
        );
    }

    // Method hapus data via AJAX
    public function delete($id)
    {
        $hapus = $this->Barang_model->delete_nama_barang($id);

        $this->respond(
            $hapus
                ? ['status' => true, 'message' => 'Data berhasil dihapus']
                : ['status' => false, 'message' => 'Gagal menghapus data']
        );
    }
}
