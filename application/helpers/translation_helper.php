<?php defined('BASEPATH') or exit('No direct script access allowed');

// Fungsi translation dengan session caching untuk optimasi performa
// Mengurangi query database dengan menyimpan semua translation di session
function translate($key)
{
    /** @var Controller */
    $CI = &get_instance();
    $lang = $CI->session->userdata('site_lang') ?? 'en';

    // Cek apakah data translation sudah ada di session
    $session_key = 'translation_cache_' . $lang;
    // $translation_cache = $CI->session->userdata($session_key);
    $translation_cache = null;
    // Jika belum ada di session, load semua data translation
    if (!$translation_cache) {
        $translation_cache = [];
        $query = $CI->db->get('interfaces');

        foreach ($query->result() as $row) {
            if (isset($row->$lang)) {
                $translation_cache[$row->key] = $row->$lang;
            }
        }

        // Simpan ke session untuk penggunaan selanjutnya
        $CI->session->set_userdata($session_key, $translation_cache);

        log_message('debug', 'Translation cache loaded to session for language: ' . $lang);
    }

    // Return translation dari session cache
    return isset($translation_cache[$key]) ? $translation_cache[$key] : $key;
}
function lang($key){
    return translate($key);
}
// Fungsi untuk clear translation cache (berguna saat ada update data translation)
function clear_translation_cache($lang = null)
{
    /** @var Controller */
    $CI = &get_instance();

    if ($lang) {
        $CI->session->unset_userdata('translation_cache_' . $lang);
    } else {
        // Clear semua language cache
        $languages = ['en', 'id', 'zh']; // sesuaikan dengan bahasa yang tersedia
        foreach ($languages as $language) {
            $CI->session->unset_userdata('translation_cache_' . $language);
        }
    }
}

function make_site_lang_cookie($lang)
{
    /** @var Controller */
    $CI = &get_instance();
    $CI->session->set_userdata('site_lang', $lang);
    setcookie('site_lang', $lang, time() + (86400 * 30), "/");
}

function get_current_lang($default = null)
{
    /** @var Controller */
    $CI = &get_instance();
    
    $lang = $CI->session->userdata('site_lang');
    if (is_null($lang)){

        $lang = get_cookie('site_lang_s');
    }
    
    return is_null($lang) ? $default : $lang;
}

function make_list_lang_cookie()
{
    /** @var Controller */
    $ci = &get_instance();
    // Pastikan helper URL tersedia untuk base_url() dan redirect()
    $ci->load->helper('url');

    if (!isset($_COOKIE['list_lang'])) {
        // Pastikan model tersedia
        $ci->load->model('Language_model');
        $language = $ci->Language_model->get_all_language(['language_flag' => 1], null, null, null, 'language_id', 'asc');
        if (!empty($language)) {
            $json_data = json_encode($language);
            // Set cookie daftar bahasa yang tersedia selama 30 hari
            setcookie('list_lang', $json_data, time() + (86400 * 30), "/");
            // Samakan perilaku dengan implementasi sebelumnya
            redirect(base_url());
        }
    }
}

//

// Fungsi baru untuk memaksa refresh cookie list_lang tanpa redirect
function reload_list_lang_cookie()
{
    /** @var Controller */
    $ci = &get_instance();
    // Muat model bahasa
    $ci->load->model('Language_model');
    // Ambil ulang daftar bahasa aktif
    $language = $ci->Language_model->get_all_language(['language_flag' => 1], null, null, null, 'language_id', 'asc');
    if (!empty($language)) {
        $json_data = json_encode($language);
        // Tulis ulang cookie selama 30 hari
        setcookie('list_lang', $json_data, time() + (86400 * 30), "/");
    } else {
        // Jika data kosong, hapus cookie
        setcookie('list_lang', '', time() - 3600, "/");
    }
}
