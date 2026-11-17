<?php defined('BASEPATH') or exit('No direct script access allowed');

// Fungsi translation: ambil teks dari tabel `interfaces` sesuai bahasa aktif (id / en / zh).
// Seluruh tabel dibaca SEKALI per request lalu disimpan di static cache,
// jadi ratusan pemanggilan translate() di satu halaman tetap hanya 1 query.
// Kalau key belum ada di tabel, yang tampil adalah key-nya sendiri.
function translate($key)
{
    static $cache = array();

    /** @var Controller */
    $CI = &get_instance();
    $lang = $CI->session->userdata('site_lang');
    if (!in_array($lang, array('id', 'en', 'zh'), true)) {
        $lang = 'id'; // default bahasa Indonesia (sama dengan halaman login)
    }

    if (!isset($cache[$lang])) {
        $cache[$lang] = array();
        $query = $CI->db->select('`key`, `' . $lang . '`', false)->get('interfaces');
        foreach ($query->result_array() as $row) {
            $cache[$lang][$row['key']] = $row[$lang];
        }
        log_message('debug', 'Translation cache loaded for language: ' . $lang);
    }

    $text = isset($cache[$lang][$key]) ? $cache[$lang][$key] : null;
    if (($text === null || $text === '') && $lang !== 'id') {
        // fallback ke bahasa Indonesia kalau terjemahan kosong
        $id = translate_fallback_id($key);
        return $id !== null ? $id : $key;
    }
    return $text !== null && $text !== '' ? $text : $key;
}

// Ambil teks bahasa Indonesia (dipakai sebagai cadangan kalau kolom en/zh kosong)
function translate_fallback_id($key)
{
    static $id_cache = null;
    if ($id_cache === null) {
        $CI = &get_instance();
        $id_cache = array();
        foreach ($CI->db->select('`key`, `id`', false)->get('interfaces')->result_array() as $row) {
            $id_cache[$row['key']] = $row['id'];
        }
    }
    return isset($id_cache[$key]) && $id_cache[$key] !== '' ? $id_cache[$key] : null;
}

// Escape teks supaya aman ditaruh di dalam string JavaScript ('...' atau "...")
// maupun di atribut HTML seperti onclick="...". Tanda kutip, <, >, & diubah ke \uXXXX.
function js_esc($text)
{
    $json = json_encode((string) $text, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    return substr($json, 1, -1);
}

// translate() untuk dipakai di dalam string JavaScript, contoh:
//   alert("...") di view: tulis tj("message_sukses") di dalam tag echo PHP
function tj($key)
{
    return js_esc(translate($key));
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
