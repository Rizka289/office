<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Barang extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRole('super_admin');
        $this->load->model('Barang_model');
        $this->load->model('Kategori_barang_model');
        $this->load->helper('url');
        $this->load->library('upload');
    }

    public function index()
    {
        $data['title']       = translate('list_barang');
        $data['page_title']  = translate('list_barang');
        $data['active_menu'] = 'barang';

        $data['kategoriList'] = $this->Kategori_barang_model->get_kat_barang_paginated('', 1000, 0);

        $this->load->view('templates/header', $data);
        $this->load->view('master_produk/barang_index', $data);
        $this->load->view('templates/footer', $data);
    }

    private function is_blank($value)
    {
        return $value === null || trim((string) $value) === '';
    }

    private function respond(array $payload)
    {
        $payload['csrf_hash'] = $this->security->get_csrf_hash();
        $this->jsonResponse($payload);
    }

    private function _upload_config()
    {
        $config['upload_path']   = './uploads/barang/';
        $config['allowed_types'] = 'jpg|jpeg|png|webp';

        // Ubah dari 2048 jadi 40960 (artinya 40 MB = 40 x 1024 KB)
        $config['max_size']      = 40960;

        $config['encrypt_name']  = TRUE;

        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0777, true);
        }

        return $config;
    }

    public function list_data()
    {
        $search  = $this->input->get('search', true);
        $page    = (int) $this->input->get('page', true);
        if ($page < 1) $page = 1;

        $perPage = 5;
        $offset  = ($page - 1) * $perPage;

        $total   = $this->Barang_model->count_barang($search);
        $data    = $this->Barang_model->get_barang_paginated($search, $perPage, $offset);

        foreach ($data as &$item) {
            if (!empty($item['gambar']) && file_exists(FCPATH . 'uploads/barang/' . $item['gambar'])) {
                $item['gambar_url'] = base_url('uploads/barang/' . $item['gambar']);
            } else {
                $item['gambar_url'] = base_url('assets/images/no-image.png');
            }
        }

        $this->respond([
            'status'       => true,
            'data'         => $data,
            'total'        => (int) $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'total_pages'  => (int) ceil($total / $perPage),
        ]);
    }

    private function parse_harga_decimal($input_harga)
    {
        if ($input_harga === null || $input_harga === '') return 0;
        $clean = preg_replace('/[^0-9.,]/', '', trim($input_harga));
        if ($clean === '') return 0;

        $hasDot   = strpos($clean, '.') !== false;
        $hasComma = strpos($clean, ',') !== false;

        if ($hasDot && $hasComma) {
            if (strrpos($clean, ',') > strrpos($clean, '.')) {
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
                $clean = str_replace(',', '', $clean);
            }
        } elseif ($hasComma) {
            $clean = (substr_count($clean, ',') > 1) ? str_replace(',', '', $clean) : str_replace(',', '.', $clean);
        } elseif ($hasDot) {
            if (substr_count($clean, '.') > 1) $clean = str_replace('.', '', $clean);
        }

        return (float) $clean;
    }

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

    public function simpan()
    {
        $kode        = trim((string) $this->input->post('kode', true));
        $nama        = trim((string) $this->input->post('nama', true));
        $warna        = trim((string) $this->input->post('warna', true));
        $idkategori  = $this->input->post('id_kategori', true);
        $jenis       = $this->input->post('jenis', true);
        $satuan      = $this->input->post('satuan', true);
        $dimensi     = trim((string) $this->input->post('dimensi', true));
        $harga_raw   = $this->input->post('harga_satuan', true);
        $stokMinimum = $this->input->post('stok_minimum', true);

        if (
            $this->is_blank($kode) || $this->is_blank($nama) || empty($warna) ||empty($idkategori) || $this->is_blank($jenis)
            || $this->is_blank($satuan) || $this->is_blank($dimensi) || $this->is_blank($harga_raw)
            || $this->is_blank($stokMinimum)
        ) {
            $this->respond(['status' => false, 'message' => 'Semua field wajib diisi!']);
            return;
        }

        $harga_clean = $this->parse_harga_decimal($harga_raw);

        if (!$this->Kategori_barang_model->get_by_id($idkategori)) {
            $this->respond(['status' => false, 'message' => 'Kategori tidak valid!']);
            return;
        }

        if ($this->Barang_model->kode_exists($kode)) {
            $this->respond(['status' => false, 'message' => 'Kode barang sudah dipakai, gunakan kode lain.']);
            return;
        }

        // Upload Foto
        $gambar_nama = null;
        if (!empty($_FILES['gambar']['name'])) {
            $this->upload->initialize($this->_upload_config());
            if ($this->upload->do_upload('gambar')) {
                $gambar_nama = $this->upload->data('file_name');
            } else {
                $this->respond(['status' => false, 'message' => $this->upload->display_errors('', '')]);
                return;
            }
        }

        $is_produced = $this->input->post('is_produced', true);

        $data = array(
            'kode_barang'  => $kode,
            'nama'         => $nama,
            'warna'         => $warna,
            'id_kategori'  => $idkategori,
            'jenis_barang' => $jenis,
            'satuan'       => $satuan,
            'dimensi'      => $dimensi,
            'is_produced'  => ($is_produced !== null) ? (int)$is_produced : 0,
            'harga_satuan' => $harga_clean,
            'stok_minimum' => $stokMinimum,
            'gambar'       => $gambar_nama,
            'created_at'   => date('Y-m-d H:i:s'),
        );

        $simpan = $this->Barang_model->insert_nama_barang($data);

        $this->respond(
            $simpan
                ? ['status' => true, 'message' => 'Data berhasil disimpan']
                : ['status' => false, 'message' => $this->db_error_message('Gagal menyimpan data')]
        );
    }

    public function get_by_id($id)
    {
        $row = $this->Barang_model->get_by_id($id);

        if ($row) {
            if (!empty($row['gambar']) && file_exists(FCPATH . 'uploads/barang/' . $row['gambar'])) {
                $row['gambar_url'] = base_url('uploads/barang/' . $row['gambar']);
            } else {
                $row['gambar_url'] = base_url('assets/images/no-image.png');
            }
            $this->respond(['status' => true, 'data' => $row]);
        } else {
            $this->respond(['status' => false, 'message' => 'Data tidak ditemukan']);
        }
    }

    public function update()
    {
        $id          = $this->input->post('id', true);
        $kode        = $this->input->post('kode', true);
        $nama        = $this->input->post('nama', true);
        $warna        = $this->input->post('warna', true);
        $idkategori  = $this->input->post('id_kategori', true);
        $jenis       = $this->input->post('jenis', true);
        $harga_raw   = $this->input->post('harga_satuan', true);
        $satuan      = $this->input->post('satuan', true);
        $dimensi     = $this->input->post('dimensi', true);
        $stokMinimum = $this->input->post('stok_minimum', true);

        if (
            empty($id) || $this->is_blank($kode) || $this->is_blank($nama) || empty($warna)|| empty($idkategori) || $this->is_blank($jenis)
            || $this->is_blank($satuan) || $this->is_blank($dimensi) || $this->is_blank($harga_raw)
            || $this->is_blank($stokMinimum)
        ) {
            $this->respond(['status' => false, 'message' => 'Semua field wajib diisi!']);
            return;
        }

        $harga_clean = $this->parse_harga_decimal($harga_raw);

        if (!$this->Kategori_barang_model->get_by_id($idkategori)) {
            $this->respond(['status' => false, 'message' => 'Kategori tidak valid!']);
            return;
        }

        $existing = $this->Barang_model->get_by_id($id);
        $is_produced = $this->input->post('is_produced', true);

        $data = array(
            'kode_barang'  => $kode,
            'nama'         => $nama,
            'warna'         => $warna,
            'id_kategori'  => $idkategori,
            'jenis_barang' => $jenis,
            'satuan'       => $satuan,
            'dimensi'      => $dimensi,
            'is_produced'  => ($is_produced !== null) ? (int)$is_produced : 0,
            'harga_satuan' => $harga_clean,
            'stok_minimum' => $stokMinimum,
        );

        if (!empty($_FILES['gambar']['name'])) {
            $this->upload->initialize($this->_upload_config());
            if ($this->upload->do_upload('gambar')) {
                if (!empty($existing['gambar']) && file_exists(FCPATH . 'uploads/barang/' . $existing['gambar'])) {
                    @unlink(FCPATH . 'uploads/barang/' . $existing['gambar']);
                }
                $data['gambar'] = $this->upload->data('file_name');
            } else {
                $this->respond(['status' => false, 'message' => $this->upload->display_errors('', '')]);
                return;
            }
        }

        $update = $this->Barang_model->update_nama_barang($id, $data);

        $this->respond(
            $update
                ? ['status' => true, 'message' => 'Data berhasil diperbarui']
                : ['status' => false, 'message' => 'Gagal memperbarui data']
        );
    }

    public function delete($id)
    {
        $existing = $this->Barang_model->get_by_id($id);
        if ($existing && !empty($existing['gambar']) && file_exists(FCPATH . 'uploads/barang/' . $existing['gambar'])) {
            @unlink(FCPATH . 'uploads/barang/' . $existing['gambar']);
        }

        $hapus = $this->Barang_model->delete_nama_barang($id);

        $this->respond(
            $hapus
                ? ['status' => true, 'message' => 'Data berhasil dihapus']
                : ['status' => false, 'message' => 'Gagal menghapus data']
        );
    }
}
