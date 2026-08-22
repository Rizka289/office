<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Controller
 * Simpan file ini di: application/core/MY_Controller.php
 *
 * Semua controller yang butuh multi-bahasa (Login, Dashboard, dll)
 * tinggal extends MY_Controller ini, bukan CI_Controller lagi.
 */
class MY_Controller extends CI_Controller
{
    // kode bahasa => nama folder di application/language/
    protected $available_languages = [
        'id' => 'indonesia',
        'en' => 'english',
        'zh' => 'chinese',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->_set_language();
    }

    private function _set_language()
    {
        // urutan prioritas: session -> default
        $lang_code = $this->session->userdata('lang');

        if (!$lang_code || !array_key_exists($lang_code, $this->available_languages)) {
            $lang_code = 'id'; // default bahasa Indonesia
        }

        $folder = $this->available_languages[$lang_code];

        // load file bahasa app_lang.php dari folder yang sesuai
        $this->lang->load('app', $folder);

        // simpan kode bahasa aktif supaya bisa dipakai di view
        // (misalnya untuk kasih tanda bahasa mana yang sedang aktif)
        $this->config->set_item('current_lang', $lang_code);
    }

    /**
     * Ganti bahasa aktif, lalu redirect balik ke halaman asal.
     * Route contoh: site_url('login/lang/en')
     */
    public function lang($lang_code = 'id')
    {
        if (array_key_exists($lang_code, $this->available_languages)) {
            $this->session->set_userdata('lang', $lang_code);
        }

        $referer = $this->input->server('HTTP_REFERER');
        redirect($referer ? $referer : base_url());
    }

    /**
     * Pastikan user sudah login DAN role-nya sesuai yang diizinkan.
     * Kalau belum login -> lempar ke halaman login.
     * Kalau login tapi role tidak cocok -> tampilkan halaman akses ditolak.
     *
     * Contoh pemakaian di controller:
     *   $this->requireRole('super_admin');
     *   $this->requireRole(['super_admin', 'supervisor']); // izinkan lebih dari satu role
     */
    protected function requireRole($allowed_roles)
    {
        if (!$this->session->userdata('logged_in')) {
            redirect('login');
            return;
        }

        $allowed_roles = is_array($allowed_roles) ? $allowed_roles : [$allowed_roles];
        $user_role     = $this->session->userdata('role');

        if (!in_array($user_role, $allowed_roles, TRUE)) {
            show_error(lang('app_access_denied') ?: 'Anda tidak memiliki akses ke halaman ini.', 403, 'Akses Ditolak');
        }
    }
}