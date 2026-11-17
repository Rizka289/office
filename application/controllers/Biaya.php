<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BIAYA OPERASIONAL / PENGELUARAN KANTOR (menu Keuangan)
 *
 * Uang keluar yang TIDAK lewat PO supplier: listrik, gaji, ATK, bensin, sewa, dll.
 * Dicatat langsung oleh finance (tanpa approval). Foto nota / bukti boleh dilampirkan (opsional).
 * Foto disimpan di uploads/biaya/.
 */
class Biaya extends MY_Controller
{
    /**
     * HAK AKSES. Ubah di sini saja kalau pembagian tugas berubah.
     *   lihat : buka menu biaya & rekap
     *   catat : tambah / ubah / hapus pengeluaran & kelola kategori
     *   export: unduh Excel / CSV & cetak PDF (sesuai filter yang sedang tampil)
     */
    private $izin = array(
        'lihat'  => array('super_admin', 'atasan', 'finance'),
        'catat'  => array('super_admin', 'finance'),
        'export' => array('super_admin', 'atasan', 'finance'),
    );

    private $metode_list = array('Tunai', 'Transfer', 'Kartu Debit', 'Kartu Kredit', 'E-Wallet');
    private $folder_foto = 'uploads/biaya/';

    public function __construct()
    {
        parent::__construct();
        $this->requireRole($this->izin['lihat']);
        $this->load->model('Biaya_model');
        $this->load->helper(array('url', 'form'));
        $this->load->library('session');
    }

    // MENU: daftar pengeluaran + ringkasan per kategori
    public function index()
    {
        $f = $this->_filter_get();

        $data['title']        = translate('i18n_biaya_operasional');
        $data['f']            = $f;
        $data['list']         = $this->Biaya_model->get_list($f);
        $data['per_kategori'] = $this->Biaya_model->get_per_kategori($f);
        $data['kategori']     = $this->Biaya_model->get_kategori(false);
        $data['kategori_form'] = $this->Biaya_model->get_kategori(true);
        $data['metode_list']  = $this->metode_list;
        $data['active_menu']  = 'biaya';
        $this->_view('biaya/index', $data);
    }

    // POST: tambah ($id = 0) atau ubah pengeluaran
    public function simpan($id = 0)
    {
        $this->_wajib_post();
        $this->_wajib_izin('catat');
        $kembali = $this->_kembali();
        $id      = (int) $id;
        $lama    = null;
        if ($id) {
            $lama = $this->Biaya_model->get($id);
            if (!$lama) {
                show_404();
            }
        }

        $tanggal     = $this->_tgl($this->input->post('tanggal'));
        $id_kategori = (int) $this->input->post('id_kategori');
        $keterangan  = mb_substr(trim((string) $this->input->post('keterangan', true)), 0, 255);
        $nominal     = round((float) $this->input->post('nominal'), 2); // input type=number
        $metode      = (string) $this->input->post('metode');
        $dibayar_ke  = mb_substr(trim((string) $this->input->post('dibayar_ke', true)), 0, 100);

        $kat = $this->Biaya_model->get_kategori_by_id($id_kategori);
        $err = array();
        if ($tanggal === '') {
            $err[] = 'tanggal';
        } elseif ($tanggal > date('Y-m-d')) {
            $this->_flash(false, translate('i18n_tanggal_pengeluaran_tidak_boleh_di_masa'));
            redirect($kembali);
        }
        if (!$kat || (!$kat->aktif && (!$lama || (int) $lama->id_kategori !== $id_kategori))) {
            $err[] = 'kategori';
        }
        if ($keterangan === '') {
            $err[] = 'keterangan';
        }
        if ($nominal <= 0) {
            $err[] = 'nominal';
        }
        if (!in_array($metode, $this->metode_list, true)) {
            $err[] = 'metode';
        }
        $ada_file = !empty($_FILES['foto']['name']);   // foto nota opsional
        if ($err) {
            $this->_flash(false, (translate('i18n_lengkapi_dulu') . ' ') . implode(', ', $err) . '.');
            redirect($kembali);
        }

        $d = array(
            'tanggal'     => $tanggal,
            'id_kategori' => $id_kategori,
            'keterangan'  => $keterangan,
            'dibayar_ke'  => $dibayar_ke !== '' ? $dibayar_ke : null,
            'nominal'     => $nominal,
            'metode'      => $metode,
        );

        if ($ada_file) {
            list($ok, $hasil) = $this->_upload_foto('foto');
            if (!$ok) {
                $this->_flash(false, $hasil);
                redirect($kembali);
            }
            $d['foto'] = $hasil;
        }

        list($ok, $pesan) = $this->Biaya_model->simpan($id, $d, $this->_id_user());
        if ($ok && $ada_file && $lama && $lama->foto) {
            $this->_hapus_file($lama->foto);          // foto lama diganti
        } elseif (!$ok && $ada_file) {
            $this->_hapus_file($d['foto']);           // simpan gagal -> buang file baru
        }
        $this->_flash($ok, $pesan);
        redirect($kembali);
    }

    // POST: hapus pengeluaran + fotonya
    public function hapus($id = 0)
    {
        $this->_wajib_post();
        $this->_wajib_izin('catat');
        $b = $this->Biaya_model->get((int) $id);
        if (!$b) {
            show_404();
        }
        if ($this->Biaya_model->hapus($b->id)) {
            if ($b->foto) {
                $this->_hapus_file($b->foto);
            }
            $this->_flash(true, (translate('i18n_pengeluaran') . ' ') . $b->no_bukti . (' ' . translate('i18n_dihapus')));
        } else {
            $this->_flash(false, translate('i18n_gagal_menghapus_pengeluaran'));
        }
        redirect($this->_kembali());
    }

    // Kelola kategori biaya
    public function kategori()
    {
        $data['title']       = translate('i18n_kategori_biaya');
        $data['kategori']    = $this->Biaya_model->get_kategori(false);
        $data['active_menu'] = 'biaya';
        $this->_view('biaya/kategori', $data);
    }

    // POST: tambah / ganti nama kategori
    public function kategori_simpan($id = 0)
    {
        $this->_wajib_post();
        $this->_wajib_izin('catat');
        $id   = (int) $id;
        $nama = mb_substr(trim(preg_replace('/\s+/', ' ', (string) $this->input->post('nama', true))), 0, 100);
        if ($nama === '') {
            $this->_flash(false, translate('i18n_nama_kategori_wajib_diisi'));
        } elseif ($id && !$this->Biaya_model->get_kategori_by_id($id)) {
            show_404();
        } elseif ($this->Biaya_model->nama_kategori_dipakai($nama, $id)) {
            $this->_flash(false, translate('i18n_kategori') . $nama . translate('i18n_q_sudah_ada'));
        } else {
            $ok = $this->Biaya_model->simpan_kategori($id, $nama);
            $this->_flash($ok, $ok ? translate('i18n_kategori') . $nama . translate('i18n_q_disimpan') : translate('i18n_gagal_menyimpan_kategori'));
        }
        redirect('biaya/kategori');
    }

    // POST: aktif / nonaktif kategori
    public function kategori_aktif($id = 0)
    {
        $this->_wajib_post();
        $this->_wajib_izin('catat');
        $k = $this->Biaya_model->get_kategori_by_id((int) $id);
        if (!$k) {
            show_404();
        }
        $this->Biaya_model->set_aktif_kategori($k->id, !$k->aktif);
        $this->_flash(true, translate('i18n_kategori') . $k->nama . '" ' . ($k->aktif ? translate('i18n_dinonaktifkan') : translate('i18n_diaktifkan_kembali')));
        redirect('biaya/kategori');
    }

    // POST: hapus kategori (hanya yang belum dipakai)
    public function kategori_hapus($id = 0)
    {
        $this->_wajib_post();
        $this->_wajib_izin('catat');
        list($ok, $pesan) = $this->Biaya_model->hapus_kategori((int) $id);
        $this->_flash($ok, $pesan);
        redirect('biaya/kategori');
    }

    // Rekap tahunan: kategori x bulan + kas masuk vs keluar
    public function rekap()
    {
        $tahun = (int) $this->input->get('tahun');
        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = (int) date('Y');
        }
        $data['title']       = (translate('i18n_rekap_biaya') . ' ') . $tahun;
        $data['tahun']       = $tahun;
        $data['rekap']       = $this->Biaya_model->get_rekap_tahun($tahun);
        // Uang masuk = pembayaran customer + pemasukan lain & modal
        $data['kas_customer'] = $this->Biaya_model->get_kas_masuk_tahun($tahun);
        $data['kas_lain']     = $this->Biaya_model->get_kas_masuk_lain_tahun($tahun);
        $data['kas_masuk']    = array();
        foreach ($data['kas_customer'] as $b => $n) {
            $data['kas_masuk'][$b] = $n + $data['kas_lain'][$b];
        }
        $data['active_menu'] = 'biaya_rekap';
        $this->_view('biaya/rekap', $data);
    }

    // ================= EXPORT =================
    // Semua export memakai filter yang sama dengan halaman daftar (?dari=&sampai=&kategori=&q=)

    // Unduh Excel (.xlsx): sheet "Rincian" + "Per Kategori"
    public function export_excel()
    {
        $this->_wajib_izin('export');
        $this->load->library('xlsx_writer');
        if (!Xlsx_writer::tersedia()) {
            // Server tanpa ekstensi zip: pakai CSV saja (tetap bisa dibuka di Excel)
            $this->_flash(false, translate('i18n_bx_excel_tidak_tersedia'));
            redirect('biaya/export_csv?' . $this->_qs());
        }
        $e = $this->_data_export();
        $t = function ($k) { return strip_tags(translate($k)); };

        // ---- Sheet 1: Rincian ----
        $head  = $this->_kolom_export();
        $jml_k = count($head);
        $rows  = array(
            array(array('v' => $t('i18n_biaya_operasional'), 's' => 'judul')),
            array(array('v' => $e['periode'], 's' => 'muted')),
            array(array('v' => $e['info_filter'], 's' => 'muted')),
            array(),
            array_map(function ($h) { return array('v' => $h, 's' => 'header'); }, $head),
        );
        $baris_header = count($rows);
        foreach ($e['list'] as $i => $b) {
            $r = $this->_baris_export($i + 1, $b);
            $r[7] = array('v' => (float) $b->nominal, 's' => 'uang');
            $rows[] = $r;
        }
        $total_row = array_fill(0, $jml_k, array('v' => null, 's' => 'bold'));
        $total_row[0] = array('v' => $t('total') . ' (' . count($e['list']) . ' ' . $t('i18n_bx_transaksi') . ')', 's' => 'bold');
        $total_row[7] = array('v' => $e['total'], 's' => 'uang_bold');
        $rows[] = $total_row;
        $rows[] = array();
        $rows[] = array(array('v' => $e['dicetak'], 's' => 'muted'));

        $this->xlsx_writer->add_sheet($t('i18n_bx_rincian'), $rows, array(
            'widths'     => array(5, 12, 20, 20, 40, 24, 14, 16, 18, 50),
            'freeze'     => $baris_header,
            'autofilter' => 'A' . $baris_header . ':' . chr(64 + $jml_k) . ($baris_header + count($e['list'])),
            'merge'      => array('A' . (count($rows) - 2) . ':G' . (count($rows) - 2)),
        ));

        // ---- Sheet 2: Per Kategori ----
        $rows2 = array(
            array(array('v' => $t('i18n_per_kategori'), 's' => 'judul')),
            array(array('v' => $e['periode'], 's' => 'muted')),
            array(),
            array(
                array('v' => $t('i18n_kategori_2'), 's' => 'header'),
                array('v' => $t('i18n_jumlah_transaksi'), 's' => 'header'),
                array('v' => $t('total'), 's' => 'header'),
                array('v' => $t('i18n_bx_persen'), 's' => 'header'),
            ),
        );
        foreach ($e['per_kategori'] as $k) {
            $rows2[] = array(
                $k->nama ?: '-',
                (int) $k->jumlah,
                array('v' => (float) $k->total, 's' => 'uang'),
                array('v' => $e['total'] > 0 ? (float) $k->total / $e['total'] : 0, 's' => 'persen'),
            );
        }
        $rows2[] = array(
            array('v' => $t('total'), 's' => 'bold'),
            array('v' => count($e['list']), 's' => 'bold'),
            array('v' => $e['total'], 's' => 'uang_bold'),
            array('v' => null, 's' => 'bold'),
        );
        $this->xlsx_writer->add_sheet($t('i18n_per_kategori'), $rows2, array('widths' => array(32, 16, 20, 12), 'freeze' => 4));

        $this->xlsx_writer->download($e['nama_file'] . '.xlsx');
    }

    // Unduh CSV (pemisah ";" supaya langsung rapi di Excel versi Indonesia)
    public function export_csv()
    {
        $this->_wajib_izin('export');
        $e = $this->_data_export();
        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $e['nama_file'] . '.csv"');
        header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");   // BOM: huruf Indonesia/Mandarin tampil benar di Excel
        fputcsv($out, $this->_kolom_export(), ';');
        foreach ($e['list'] as $i => $b) {
            $r = array_map(array($this, '_csv_aman'), $this->_baris_export($i + 1, $b));
            $r[7] = number_format((float) $b->nominal, 2, '.', '');
            fputcsv($out, $r, ';');
        }
        fputcsv($out, array(strip_tags(translate('total')), '', '', '', '', '', '', number_format($e['total'], 2, '.', '')), ';');
        fclose($out);
        exit;
    }

    // Halaman cetak A4 dengan kop perusahaan (Ctrl+P -> Simpan sebagai PDF)
    public function cetak()
    {
        $this->_wajib_izin('export');
        $this->load->model('Pemenuhan_model');   // untuk kop perusahaan ($perusahaan) di views/pemenuhan/_cetak.php
        $data = $this->_data_export();
        $data['nama_user'] = $this->_nama_user();
        $this->load->view('biaya/cetak', $data);
    }

    private function _data_export()
    {
        $f     = $this->_filter_get();
        $list  = $this->Biaya_model->get_list($f);
        $total = 0.0;
        foreach ($list as $b) {
            $total += (float) $b->nominal;
        }
        $nama_kat = '';
        if ($f['id_kategori']) {
            foreach ($this->Biaya_model->get_kategori(false) as $k) {
                if ((int) $k->id === (int) $f['id_kategori']) {
                    $nama_kat = $k->nama;
                }
            }
        }
        $info = array(strip_tags(translate('i18n_kategori_2')) . ': ' . ($nama_kat !== '' ? $nama_kat : strip_tags(translate('i18n_semua_kategori'))));
        if ($f['q'] !== '') {
            $info[] = strip_tags(translate('cari')) . ': "' . $f['q'] . '"';
        }
        return array(
            'f'            => $f,
            'list'         => $list,
            'per_kategori' => $this->Biaya_model->get_per_kategori($f),
            'total'        => $total,
            'nama_kategori' => $nama_kat,
            'periode'      => strip_tags(translate('i18n_bx_periode')) . ': ' . date('d/m/Y', strtotime($f['dari'])) . ' - ' . date('d/m/Y', strtotime($f['sampai'])),
            'info_filter'  => implode('   |   ', $info),
            'dicetak'      => strip_tags(translate('i18n_bx_diekspor')) . ' ' . date('d/m/Y H:i') . ' - ' . $this->_nama_user(),
            'nama_file'    => 'biaya_operasional_' . $f['dari'] . '_sd_' . $f['sampai'],
        );
    }

    private function _kolom_export()
    {
        $t = function ($k) { return strip_tags(translate($k)); };
        return array('No', $t('tanggal'), $t('i18n_bx_no_bukti'), $t('i18n_kategori_2'), $t('deskripsi'),
            $t('i18n_dibayar_ke'), $t('i18n_metode'), $t('i18n_nominal_2'), $t('i18n_bx_dicatat_oleh'), $t('i18n_bx_foto_nota'));
    }

    private function _baris_export($no, $b)
    {
        $metode = $b->metode ? strip_tags(translate('i18n_metode_' . strtolower(str_replace(array(' ', '-'), '_', $b->metode)))) : '';
        return array(
            $no,
            date('d/m/Y', strtotime($b->tanggal)),
            (string) $b->no_bukti,
            (string) ($b->nama_kategori ?: '-'),
            (string) $b->keterangan,
            (string) $b->dibayar_ke,
            $metode,
            (float) $b->nominal,
            (string) ($b->nama_user ?? ''),
            $b->foto ? base_url($b->foto) : '',
        );
    }

    // Cegah "CSV injection": teks yang diawali = + - @ dianggap rumus oleh Excel
    private function _csv_aman($v)
    {
        if (is_string($v) && $v !== '' && strpos('=+-@', $v[0]) !== false) {
            return "'" . $v;
        }
        return $v;
    }

    // Query string filter aktif (untuk redirect antar export)
    private function _qs()
    {
        $f = $this->_filter_get();
        return http_build_query(array_filter(array(
            'dari' => $f['dari'], 'sampai' => $f['sampai'], 'kategori' => $f['id_kategori'] ?: '', 'q' => $f['q'],
        ), 'strlen'));
    }

    private function _nama_user()
    {
        foreach (array('nama', 'username') as $k) {
            $v = trim((string) $this->session->userdata($k));
            if ($v !== '') {
                return $v;
            }
        }
        return '-';
    }

    // ================= HELPER =================

    private function _view($view, $data)
    {
        $data['page_title'] = $data['title'];
        $data['izin']       = array();
        foreach (array_keys($this->izin) as $k) {
            $data['izin'][$k] = $this->_boleh($k);
        }
        $this->load->view('templates/header', $data);
        $this->load->view($view, $data);
        $this->load->view('templates/footer', $data);
    }

    private function _filter_get()
    {
        $dari   = $this->_tgl($this->input->get('dari')) ?: date('Y-m-01');
        $sampai = $this->_tgl($this->input->get('sampai')) ?: date('Y-m-d');
        if ($dari > $sampai) {
            list($dari, $sampai) = array($sampai, $dari);
        }
        return array(
            'dari'        => $dari,
            'sampai'      => $sampai,
            'id_kategori' => (int) $this->input->get('kategori'),
            'q'           => mb_substr(trim((string) $this->input->get('q', true)), 0, 60),
        );
    }

    // Upload foto nota (jpg/png/webp/pdf, maks 5MB). Return: array(ok, path relatif | pesan error)
    private function _upload_foto($field)
    {
        $dir = FCPATH . $this->folder_foto;
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return array(false, (translate('i18n_folder') . ' ') . $this->folder_foto . (' ' . translate('i18n_tidak_bisa_dibuat_buat_manual_beri')));
        }
        $this->load->library('upload');
        $this->upload->initialize(array(
            'upload_path'   => $dir,
            'allowed_types' => 'jpg|jpeg|png|webp|pdf',
            'max_size'      => 5120,
            'file_name'     => 'biaya_' . date('YmdHis') . '_' . mt_rand(100, 999),
            'overwrite'     => false,
        ), true);
        if (!$this->upload->do_upload($field)) {
            return array(false, (translate('i18n_upload_foto_gagal') . ' ') . strip_tags($this->upload->display_errors('', '')) . (' ' . translate('i18n_jpg_png_webp_pdf_maks_5mb')));
        }
        return array(true, $this->folder_foto . $this->upload->data('file_name'));
    }

    // Hapus file hanya kalau memang ada di folder foto biaya
    private function _hapus_file($rel)
    {
        if (strpos($rel, $this->folder_foto) === 0 && strpos($rel, '..') === false && is_file(FCPATH . $rel)) {
            @unlink(FCPATH . $rel);
        }
    }

    private function _boleh($kunci)
    {
        return isset($this->izin[$kunci]) && in_array((string) $this->session->userdata('role'), $this->izin[$kunci], true);
    }

    private function _wajib_izin($kunci)
    {
        if (!$this->_boleh($kunci)) {
            $this->_flash(false, translate('i18n_anda_tidak_punya_hak_akses_untuk_aksi'));
            redirect('biaya');
        }
    }

    // Tujuan redirect setelah aksi (hanya halaman biaya + query filter)
    private function _kembali()
    {
        $k = trim((string) $this->input->post('kembali'));
        return preg_match('#^biaya(\?[a-z_=&%0-9A-Z.+-]*)?$#', $k) ? $k : 'biaya';
    }

    private function _flash($ok, $pesan)
    {
        $this->session->set_flashdata($ok ? 'success' : 'error', $pesan);
    }

    private function _tgl($v)
    {
        $v = trim((string) $v);
        return ($v !== '' && strtotime($v)) ? date('Y-m-d', strtotime($v)) : '';
    }

    private function _wajib_post()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
    }

    // Sesuaikan dengan key session login di aplikasi Anda
    private function _id_user()
    {
        foreach (array('user_id', 'id_user', 'id') as $k) {
            $v = $this->session->userdata($k);
            if ($v) {
                return (int) $v;
            }
        }
        return null;
    }
}
