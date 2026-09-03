<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Stichoza\GoogleTranslate\GoogleTranslate;

/**
 * Translate_service
 * Service untuk menangani auto-translation menggunakan library stichoza/google-translate-php
 */
class Translate_service
{
    /**
     * Pemetaan kode bahasa standar ke kode Google Translate
     * @var array
     */
    protected $langMap = [
        'id'    => 'id',
        'en'    => 'en',
        'zh'    => 'zh-CN',
        'zh-CN' => 'zh-CN',
    ];

    public function __construct()
    {
        // Pastikan autoloader vendor composer termuat
        if (!class_exists('Stichoza\GoogleTranslate\GoogleTranslate')) {
            if (file_exists(FCPATH . 'vendor/autoload.php')) {
                require_once FCPATH . 'vendor/autoload.php';
            }
        }
    }

    /**
     * Menerjemahkan satu teks string dari source language ke target language
     *
     * @param string $text Teks yang akan diterjemahkan
     * @param string $targetLang Kode bahasa tujuan ('en', 'zh', 'zh-CN', 'id', dll)
     * @param string $sourceLang Kode bahasa asal (default 'id')
     * @return string Hasil terjemahan (atau teks asli jika gagal/kosong)
     */
    public function translate($text, $targetLang = 'en', $sourceLang = 'id')
    {
        if ($text === null || trim($text) === '') {
            return '';
        }

        $source = isset($this->langMap[$sourceLang]) ? $this->langMap[$sourceLang] : $sourceLang;
        $target = isset($this->langMap[$targetLang]) ? $this->langMap[$targetLang] : $targetLang;

        if ($source === $target) {
            return $text;
        }

        try {
            $tr = new GoogleTranslate();
            if (method_exists($tr, 'setClient')) {
                $tr->setClient('webapp');
            }
            $tr->setSource($source);
            $tr->setTarget($target);
            $result = $tr->translate($text);

            return $result !== null ? $result : $text;
        } catch (\Exception $e) {
            log_message('error', 'Translate_service Error [' . $text . '] to [' . $target . ']: ' . $e->getMessage());
            // Fallback kembali ke teks awal jika koneksi/API terkendala
            return $text;
        }
    }

    /**
     * Menerjemahkan paket konten Artikel (Title & Description) ke English dan Mandarin (Simplified)
     *
     * @param string $title
     * @param string $description
     * @param string $sourceLang
     * @return array [title_en, title_zh, description_en, description_zh]
     */
    public function translateArticle($title, $description, $sourceLang = 'id')
    {
        $title_en       = $this->translate($title, 'en', $sourceLang);
        $title_zh       = $this->translate($title, 'zh-CN', $sourceLang);

        $description_en = $this->translate($description, 'en', $sourceLang);
        $description_zh = $this->translate($description, 'zh-CN', $sourceLang);

        return [
            'title_en'       => $title_en,
            'title_zh'       => $title_zh,
            'description_en' => $description_en,
            'description_zh' => $description_zh,
        ];
    }
}
