<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Security
 * Kalau token CSRF tidak cocok / kedaluwarsa (mis. halaman lama dari tombol Back,
 * cookie baru dihapus, atau form dibiarkan terbuka terlalu lama), jangan tampilkan
 * "The action you have requested is not allowed." -- kembalikan user ke halaman
 * form-nya lagi (GET) supaya dapat token baru dan bisa langsung coba lagi.
 */
class MY_Security extends CI_Security
{
    public function csrf_show_error()
    {
        $config   =& get_config();
        $base_url = rtrim((string) ($config['base_url'] ?? ''), '/');
        $referer  = isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : '';

        // Hanya kembali ke halaman di situs sendiri; selain itu ke halaman login
        $host_sendiri = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : '';
        $host_referer = strtolower((string) parse_url($referer, PHP_URL_HOST) . (parse_url($referer, PHP_URL_PORT) ? ':' . parse_url($referer, PHP_URL_PORT) : ''));
        if ($referer !== '' && $host_sendiri !== '' && $host_referer === $host_sendiri) {
            $tujuan = $referer;
        } else {
            $index  = isset($config['index_page']) && $config['index_page'] !== '' ? '/' . $config['index_page'] : '';
            $tujuan = $base_url . $index . '/login';
        }

        $tujuan = rtrim(preg_replace('/([?&])csrf_expired=1(&|$)/', '$1', $tujuan), '?&');
        $tujuan .= (strpos($tujuan, '?') === false ? '?' : '&') . 'csrf_expired=1';

        header('Location: ' . $tujuan, true, 303);
        exit;
    }
}
