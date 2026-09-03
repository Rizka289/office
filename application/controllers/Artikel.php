<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Controller Artikel
 * Master Data & CRUD Artikel dengan Auto-Translate Service (stichoza/google-translate-php)
 */
class Artikel extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRole('super_admin'); // Hanya super admin yang boleh akses
        $this->load->model('Artikel_model');
        $this->load->library('translate_service');
        $this->load->helper(['url', 'form']);
    }

    /**
     * Halaman Utama CRUD Artikel
     */
    public function index()
    {
        $data['title']       = 'Manajemen Data Artikel';
        $data['page_title']  = function_exists('translate') ? translate('menu_artikel') : 'Master Data Artikel';
        $data['active_menu'] = 'artikel';

        $this->load->view('templates/header', $data);
        $this->load->view('super_admin/artikel_grid_view', $data);
        $this->load->view('templates/footer', $data);
    }

    /**
     * Endpoint AJAX: List data artikel dengan pagination, search, dan filter bahasa
     */
    public function list_data()
    {
        $search    = $this->input->get('search', true);
        $lang      = $this->input->get('lang', true) ?: 'id'; // id (default), en, zh
        $is_active = $this->input->get('is_active', true);
        $page      = (int) $this->input->get('page', true);

        if ($page < 1) {
            $page = 1;
        }

        $perPage = 5;
        $offset  = ($page - 1) * $perPage;

        $total = $this->Artikel_model->count_artikel($search, $is_active);
        $rows  = $this->Artikel_model->get_artikel_paginated($search, $perPage, $offset, $is_active);

        // Format data sesuai dengan bahasa yang dipilih untuk preview list
        $formattedData = [];
        foreach ($rows as $item) {
            // Tentukan display title & description berdasarkan filter bahasa
            $displayTitle = $item['title'];
            $displayDesc  = $item['description'];

            if ($lang === 'en') {
                $displayTitle = !empty($item['title_en']) ? $item['title_en'] : $item['title'] . ' (No EN translation)';
                $displayDesc  = !empty($item['description_en']) ? $item['description_en'] : $item['description'];
            } elseif ($lang === 'zh' || $lang === 'zh-CN') {
                $displayTitle = !empty($item['title_zh']) ? $item['title_zh'] : $item['title'] . ' (No ZH translation)';
                $displayDesc  = !empty($item['description_zh']) ? $item['description_zh'] : $item['description'];
            }

            $formattedData[] = [
                'id'             => $item['id'],
                'title'          => $item['title'],
                'title_en'       => $item['title_en'],
                'title_zh'       => $item['title_zh'],
                'description'    => $item['description'],
                'description_en' => $item['description_en'],
                'description_zh' => $item['description_zh'],
                'display_title'  => $displayTitle,
                'display_desc'   => $displayDesc,
                'is_active'      => (int) $item['is_active'],
                'created_at'     => $item['created_at'],
                'updated_at'     => $item['updated_at'],
            ];
        }

        $this->jsonResponse([
            'status'       => true,
            'data'         => $formattedData,
            'selected_lang'=> $lang,
            'total'        => (int) $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'total_pages'  => (int) ceil($total / $perPage),
        ]);
    }

    /**
     * Endpoint AJAX: Simpan Artikel Baru
     * Form input hanya menerima bahasa default (title, description, is_active),
     * lalu otomatis diterjemahkan ke bahasa Inggris dan Mandarin sebelum disimpan.
     */
    public function simpan()
    {
        $title       = trim((string) $this->input->post('title', true));
        $description = trim((string) $this->input->post('description', true));
        $isActive    = $this->input->post('is_active') !== null ? (int) $this->input->post('is_active') : 1;

        if (empty($title) || empty($description)) {
            $this->jsonResponse(['status' => false, 'message' => 'Judul dan deskripsi artikel wajib diisi!']);
            return;
        }

        // Proses Auto-Translation via Translate_service
        $translated = $this->translate_service->translateArticle($title, $description, 'id');

        $data = [
            'title'          => $title,
            'title_en'       => $translated['title_en'],
            'title_zh'       => $translated['title_zh'],
            'description'    => $description,
            'description_en' => $translated['description_en'],
            'description_zh' => $translated['description_zh'],
            'is_active'      => $isActive,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        $simpan = $this->Artikel_model->insert_artikel($data);

        $this->jsonResponse(
            $simpan
                ? [
                    'status'  => true,
                    'message' => 'Artikel berhasil disimpan dan otomatis diterjemahkan!',
                    'data'    => $data,
                ]
                : ['status' => false, 'message' => 'Gagal menyimpan artikel ke database.']
        );
    }

    /**
     * Endpoint AJAX: Ambil data artikel by ID (untuk form edit atau modal detail)
     */
    public function get_by_id($id)
    {
        $id  = (int) $id;
        $row = $this->Artikel_model->get_by_id($id);

        if ($row) {
            $this->jsonResponse(['status' => true, 'data' => $row]);
        } else {
            $this->jsonResponse(['status' => false, 'message' => 'Artikel tidak ditemukan.']);
        }
    }

    /**
     * Endpoint AJAX: Update Data Artikel
     * Form edit hanya input bahasa default (title, description, is_active),
     * lalu otomatis di-update dan di-retranslate ke EN & ZH.
     */
    public function update()
    {
        $id          = (int) $this->input->post('id', true);
        $title       = trim((string) $this->input->post('title', true));
        $description = trim((string) $this->input->post('description', true));
        $isActive    = $this->input->post('is_active') !== null ? (int) $this->input->post('is_active') : 1;

        if (empty($id) || empty($title) || empty($description)) {
            $this->jsonResponse(['status' => false, 'message' => 'ID, Judul, dan deskripsi wajib diisi!']);
            return;
        }

        $existing = $this->Artikel_model->get_by_id($id);
        if (!$existing) {
            $this->jsonResponse(['status' => false, 'message' => 'Data artikel tidak ditemukan!']);
            return;
        }

        // Lakukan translate ulang jika judul atau deskripsi berubah
        $translated = $this->translate_service->translateArticle($title, $description, 'id');

        $data = [
            'title'          => $title,
            'title_en'       => $translated['title_en'],
            'title_zh'       => $translated['title_zh'],
            'description'    => $description,
            'description_en' => $translated['description_en'],
            'description_zh' => $translated['description_zh'],
            'is_active'      => $isActive,
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        $update = $this->Artikel_model->update_artikel($id, $data);

        $this->jsonResponse(
            $update
                ? [
                    'status'  => true,
                    'message' => 'Artikel berhasil diperbarui dan terjemahan diperbarui!',
                ]
                : ['status' => false, 'message' => 'Gagal memperbarui artikel.']
        );
    }

    /**
     * Endpoint AJAX: Hapus Artikel
     */
    public function delete($id)
    {
        $id    = (int) $id;
        $hapus = $this->Artikel_model->delete_artikel($id);

        $this->jsonResponse(
            $hapus
                ? ['status' => true, 'message' => 'Artikel berhasil dihapus.']
                : ['status' => false, 'message' => 'Gagal menghapus artikel.']
        );
    }

    /**
     * Endpoint AJAX: Toggle status aktif (1 <-> 0)
     */
    public function toggle_status($id)
    {
        $id = (int) $id;
        $ok = $this->Artikel_model->toggle_status($id);

        $this->jsonResponse(
            $ok
                ? ['status' => true, 'message' => 'Status artikel berhasil diubah.']
                : ['status' => false, 'message' => 'Gagal mengubah status artikel.']
        );
    }

    /**
     * Helper response JSON dengan penyisipan CSRF Hash terbaru
     */
    private function jsonResponse($payload)
    {
        $payload['csrf_hash'] = $this->security->get_csrf_hash();
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }
}
