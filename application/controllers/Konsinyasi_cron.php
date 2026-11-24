<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tarik otomatis barang konsinyasi yang lewat masa titip.
 * HANYA lewat command line (cron / Task Scheduler), jalankan sekali sehari:
 *
 *   Linux  : 5 0 * * * php /var/www/office/index.php konsinyasi_cron
 *   Windows: C:\xampp\php\php.exe C:\xampp\htdocs\office\index.php konsinyasi_cron
 *
 * Tanpa cron pun penarikan tetap jalan setiap kali menu Konsinyasi dibuka.
 */
class Konsinyasi_cron extends CI_Controller
{
    public function index()
    {
        if (!is_cli()) {
            show_404();
        }
        $this->load->model('Konsinyasi_model');
        if (!$this->Konsinyasi_model->siap()) {
            echo "Migrasi database konsinyasi belum dijalankan.\n";
            return;
        }
        $r = $this->Konsinyasi_model->auto_tarik(0, null);
        echo date('Y-m-d H:i:s') . " tarik otomatis: {$r['qty']} unit dari {$r['so']} SO\n";
        foreach ($r['manual'] as $m) {
            echo "  PERLU TARIK MANUAL: {$m['no_so']} - {$m['nama']} ({$m['qty']} unit, belum ada master barang)\n";
        }
        foreach ($r['error'] as $e) {
            echo "  GAGAL: {$e}\n";
        }
    }
}
