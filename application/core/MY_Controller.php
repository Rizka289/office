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
    // Role yang dikenal sistem (samakan dengan User::$role_valid)
    const ROLE_VALID = ['super_admin', 'atasan', 'finance', 'staff_purchasing', 'staff_gudang'];

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

        // Role di database tidak dikenal sistem (mis. 'purchasing' bukan 'staff_purchasing'):
        // jangan biarkan user terjebak di halaman Akses Ditolak -> logout & kembali ke login.
        if (!in_array($user_role, self::ROLE_VALID, TRUE)) {
            $this->session->unset_userdata(['user_id', 'username', 'nama', 'role', 'foto', 'logged_in']);
            $this->session->set_flashdata('error', 'Role akun "' . html_escape((string) $user_role) . '" tidak dikenali sistem. Hubungi admin untuk memperbaiki role user.');
            redirect('login');
            return;
        }

        if (!in_array($user_role, $allowed_roles, TRUE)) {
            // translate() mengembalikan key-nya sendiri kalau terjemahan tidak ada
            $pesan = lang('app_access_denied');
            if (!$pesan || $pesan === 'app_access_denied') {
                $pesan = 'Anda tidak memiliki akses ke halaman ini.';
            }
            $pesan .= '<br><br>'
                . '<a href="' . site_url('dashboard') . '" style="margin-right:12px">&larr; Kembali ke Dashboard</a>'
                . '<a href="' . site_url('login/logout') . '">Keluar / Ganti Akun</a>';
            show_error($pesan, 403, 'Akses Ditolak');
        }
    }

    /**
     * Kirim response dalam format JSON.
     * Dipakai oleh endpoint-endpoint AJAX di semua controller anak.
     *
     * Contoh pemakaian:
     *   $this->jsonResponse(['status' => true, 'data' => $data]);
     *   $this->jsonResponse(['status' => false, 'message' => 'Gagal'], 400);
     */
    protected function jsonResponse($data, $statusCode = 200)
    {
        $this->output
            ->set_status_header($statusCode)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data));
    }
}