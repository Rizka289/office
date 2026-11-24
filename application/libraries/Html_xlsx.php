<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Html_xlsx
 * ---------------------------------------------------------------------------
 * Mengubah HTML tabel (hasil view cetak penawaran mode Excel) menjadi file
 * .xlsx ASLI, lengkap dengan foto yang tertanam di sel. Tanpa PhpSpreadsheet,
 * hanya butuh ekstensi PHP "zip" dan "dom".
 *
 * Yang dibaca dari HTML:
 * - Semua <table> tingkat atas, berurutan, ditumpuk ke bawah dalam 1 sheet.
 * - <col width="px"> di tabel pertama  -> lebar kolom.
 * - colspan / rowspan                  -> merge cell.
 * - atribut x:num="1234.5"             -> sel angka (bisa dijumlah).
 * - <br>                               -> pindah baris dalam sel.
 * - <img src="penawaran_files/NAMA">   -> foto dari $foto_list (nama => data).
 * - class: money, luas, num, tval, tlabel, judul, tengah, deskripsi-cell,
 *   gambar-cell; <th>; <tr class="grand">; <b>/<strong> sebagai isi utama.
 *
 * Pemakaian:
 *   $this->load->library('html_xlsx');
 *   $biner = $this->html_xlsx->buat($html, $foto_list, 'Penawaran');
 */
class Html_xlsx
{
    // Naikkan kalau file ini diubah; dicek oleh Penawaran::excel()
    const VERSI = 5;

    // Index style (urutan sama dengan <cellXfs> di _styles())
    const S_DEFAULT  = 0;
    const S_TH       = 1;
    const S_TEKS_BRD = 2;
    const S_NUM_BRD  = 3;
    const S_RP_BRD   = 4;
    const S_LUAS_BRD = 5;
    const S_JUDUL    = 6;
    const S_TENGAH   = 7;
    const S_TLABEL   = 8;
    const S_TVAL     = 9;
    const S_TLABEL_B = 10;
    const S_TVAL_B   = 11;
    const S_BOLD     = 12;
    const S_GARIS_ATAS = 13;
    const S_TEKS     = 14;
    const S_TEKS_WRAP = 15;
    const S_TQTY      = 16;
    const S_GARIS_BAWAH = 17;

    const EMU_PX = 9525; // 1 px = 9525 EMU
    // Tinggi minimal semua baris (pt), cukup untuk 1 baris font 11 + huruf Mandarin
    const TINGGI_MIN = 17;

    private $sel      = [];  // [row][col] => ['v'=>, 'num'=>bool, 's'=>style]
    private $merge    = [];  // "A1:B2"
    private $gambar   = [];  // ['row','col','w','h','nama']
    private $lebar    = [];  // col index => px
    private $tinggi   = [];  // row index => pt (minimal)
    private $span_img = [];  // row awal => [jumlah baris, tinggi px]

    public function buat($html, $foto_list = [], $nama_sheet = 'Sheet1')
    {
        if (!class_exists('ZipArchive') || !class_exists('DOMDocument')) {
            show_error('Export Excel butuh ekstensi PHP "zip" dan "dom".');
        }
        $this->sel = $this->merge = $this->gambar = $this->lebar = $this->tinggi = $this->span_img = [];

        $foto = [];
        foreach ($foto_list as $f) {
            $foto[$f['nama']] = $f;
        }

        $this->_baca_html($html);
        $this->_hitung_tinggi_baris();

        return $this->_tulis_zip($foto, $nama_sheet);
    }

    // ------------------------------------------------------------------ HTML

    private function _baca_html($html)
    {
        // "x:num" diganti "data-xnum" supaya tidak hilang dibaca parser HTML
        $html = str_replace(' x:num="', ' data-xnum="', $html);

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        $baris = 0;
        $pertama = true;
        foreach ($dom->getElementsByTagName('table') as $tabel) {
            // hanya tabel tingkat atas (bukan tabel di dalam sel)
            $p = $tabel->parentNode;
            $bersarang = false;
            while ($p) {
                if ($p->nodeName === 'td' || $p->nodeName === 'th') { $bersarang = true; break; }
                $p = $p->parentNode;
            }
            if ($bersarang) continue;

            if ($pertama) {
                $c = 0;
                foreach ($tabel->getElementsByTagName('col') as $col) {
                    $this->lebar[$c++] = max(20, (int) $col->getAttribute('width'));
                }
                $pertama = false;
            }
            $kelas_tabel = ' ' . $tabel->getAttribute('class') . ' ';
            $berbingkai  = strpos($kelas_tabel, ' item-table ') !== false;
            $baris = $this->_baca_tabel($tabel, $baris, $berbingkai);
        }
    }

    private function _baca_tabel(DOMElement $tabel, $baris, $berbingkai)
    {
        $terisi = []; // [row][col] => true (karena rowspan/colspan)

        foreach ($this->_anak_tr($tabel) as $tr) {
            $kelas_tr = ' ' . $tr->getAttribute('class') . ' ';
            $grand    = strpos($kelas_tr, ' grand ') !== false;
            if (preg_match('/height:\s*([\d.]+)pt/', $tr->getAttribute('style'), $m)) {
                $this->tinggi[$baris] = max($this->tinggi[$baris] ?? 0, (float) $m[1]);
            }

            $kol = 0;
            foreach ($tr->childNodes as $sel) {
                if (!($sel instanceof DOMElement) || !in_array($sel->nodeName, ['td', 'th'], true)) continue;

                while (!empty($terisi[$baris][$kol])) $kol++;

                $cs = max(1, (int) $sel->getAttribute('colspan'));
                $rs = max(1, (int) $sel->getAttribute('rowspan'));
                for ($r = 0; $r < $rs; $r++) {
                    for ($c = 0; $c < $cs; $c++) {
                        $terisi[$baris + $r][$kol + $c] = true;
                    }
                }

                $gaya = $this->_gaya($sel, $berbingkai, $grand);
                $xnum = $sel->getAttribute('data-xnum');
                if ($xnum !== '' && is_numeric($xnum)) {
                    $this->sel[$baris][$kol] = ['v' => $xnum, 'num' => true, 's' => $gaya, 'cs' => $cs];
                } else {
                    $teks = $this->_teks($sel);
                    if (preg_match('/height:\s*([\d.]+)px/', $sel->getAttribute('style'), $m)) {
                        $this->tinggi[$baris] = max($this->tinggi[$baris] ?? 0, (float) $m[1] * 0.75);
                    }
                    // Bilangan bulat polos (lebar, tinggi, qty) -> angka, kecuali sel teks
                    // (mis. no. telepon "0812..." tetap teks supaya angka 0 di depan tidak hilang)
                    $angka = preg_match('/^(0|[1-9]\d{0,14})$/', $teks) && strpos(' ' . $sel->getAttribute('class') . ' ', ' teks ') === false;
                    $this->sel[$baris][$kol] = ['v' => $teks, 'num' => (bool) $angka, 's' => $gaya, 'cs' => $cs];
                }

                // isi sel lain di area merge dengan gaya yang sama (supaya garis tabel lengkap)
                if ($cs > 1 || $rs > 1) {
                    for ($r = 0; $r < $rs; $r++) {
                        for ($c = 0; $c < $cs; $c++) {
                            if ($r === 0 && $c === 0) continue;
                            $this->sel[$baris + $r][$kol + $c] = ['v' => '', 'num' => false, 's' => $gaya];
                        }
                    }
                    $this->merge[] = $this->_ref($baris, $kol) . ':' . $this->_ref($baris + $rs - 1, $kol + $cs - 1);
                }

                // foto
                foreach ($sel->getElementsByTagName('img') as $img) {
                    $src = $img->getAttribute('src');
                    $this->gambar[] = [
                        'row'  => $baris,
                        'col'  => $kol,
                        'cs'   => $cs,
                        'rs'   => $rs,
                        'w'    => max(1, (int) $img->getAttribute('width')),
                        'h'    => max(1, (int) $img->getAttribute('height')),
                        'nama' => basename($src),
                    ];
                    break; // 1 foto per sel
                }

                $kol += $cs;
            }
            $baris++;
        }
        // baris yang hanya terisi rowspan dari atas
        return max($baris, $terisi ? max(array_keys($terisi)) + 1 : $baris);
    }

    private function _anak_tr(DOMElement $tabel)
    {
        $hasil = [];
        foreach ($tabel->childNodes as $n) {
            if (!($n instanceof DOMElement)) continue;
            if ($n->nodeName === 'tr') {
                $hasil[] = $n;
            } elseif (in_array($n->nodeName, ['thead', 'tbody', 'tfoot'], true)) {
                foreach ($n->childNodes as $tr) {
                    if ($tr instanceof DOMElement && $tr->nodeName === 'tr') $hasil[] = $tr;
                }
            }
        }
        return $hasil;
    }

    // Teks sel: <br> jadi baris baru, spasi berlebih dirapikan
    private function _teks(DOMNode $n)
    {
        $out = '';
        foreach ($n->childNodes as $c) {
            if ($c instanceof DOMText) {
                $out .= preg_replace('/\s+/u', ' ', $c->nodeValue);
            } elseif ($c instanceof DOMElement) {
                if ($c->nodeName === 'br') {
                    $out .= "\n";
                } elseif (in_array($c->nodeName, ['img', 'style', 'script'], true)) {
                    continue;
                } elseif (in_array($c->nodeName, ['div', 'p', 'tr'], true)) {
                    $out .= "\n" . $this->_teks($c) . "\n";
                } else {
                    $out .= $this->_teks($c);
                }
            }
        }
        $baris = array_map(function ($b) { return trim($b, " \t\xC2\xA0"); }, explode("\n", $out));
        return trim(implode("\n", $baris), "\n");
    }

    private function _gaya(DOMElement $sel, $berbingkai, $grand)
    {
        $k = ' ' . $sel->getAttribute('class') . ' ';
        $ada = function ($c) use ($k) { return strpos($k, ' ' . $c . ' ') !== false; };

        if ($sel->nodeName === 'th') return self::S_TH;
        if ($ada('judul'))  return self::S_JUDUL;
        if ($ada('tval'))   return $grand ? self::S_TVAL_B : self::S_TVAL;
        if ($ada('tlabel')) return $grand ? self::S_TLABEL_B : self::S_TLABEL;
        if ($ada('tqty'))   return self::S_TQTY;
        if ($berbingkai) {
            if ($ada('money')) return self::S_RP_BRD;
            if ($ada('luas'))  return self::S_LUAS_BRD;
            if ($ada('num') || $ada('gambar-cell')) return self::S_NUM_BRD;
            return self::S_TEKS_BRD;
        }
        if (strpos($sel->getAttribute('style'), 'border-top') !== false) return self::S_GARIS_ATAS;
        if (strpos($sel->getAttribute('style'), 'border-bottom') !== false) return self::S_GARIS_BAWAH;
        if ($ada('tengah')) return self::S_TENGAH;
        if ($ada('wrap'))   return self::S_TEKS_WRAP;
        // seluruh isi sel tebal (<b> / <strong>)
        $b = $sel->getElementsByTagName('b')->length + $sel->getElementsByTagName('strong')->length;
        if ($b > 0 && trim($sel->textContent) !== '' && $sel->firstChild && in_array($sel->firstChild->nodeName, ['b', 'strong'], true)) {
            return self::S_BOLD;
        }
        return self::S_TEKS;
    }

    // ------------------------------------------------------------ TINGGI BARIS

    private function _lebar_px($c)
    {
        return $this->lebar[$c] ?? 64;
    }

    // Excel tidak otomatis menyesuaikan tinggi baris untuk teks wrap / merge,
    // jadi tingginya diperkirakan dari jumlah baris teks dan ukuran foto.
    private function _hitung_tinggi_baris()
    {
        $merge_baris = []; // sel yang di-merge ke bawah tidak dipakai untuk tinggi
        foreach ($this->merge as $m) {
            list($a, $b) = explode(':', $m);
            $ra = $this->_baris_dari_ref($a);
            $rb = $this->_baris_dari_ref($b);
            if ($rb > $ra) {
                $merge_baris[$ra . ':' . $this->_kol_dari_ref($a)] = true;
            }
        }

        foreach ($this->sel as $r => $kolom) {
            foreach ($kolom as $c => $s) {
                if ($s['num'] || $s['v'] === '' || isset($merge_baris[$r . ':' . $c])) continue;
                if (!in_array($s['s'], [self::S_TEKS_BRD, self::S_TH, self::S_NUM_BRD, self::S_TEKS_WRAP], true)) continue;
                $px = 0;
                for ($i = $c; $i < $c + ($s['cs'] ?? 1); $i++) $px += $this->_lebar_px($i);
                // perkiraan longgar (huruf Mandarin dihitung 2) supaya teks tidak terpotong
                $lebar_char = max(5, (int) floor(($px - 10) / 6.9));
                $jml = 0;
                foreach (explode("\n", $s['v']) as $b) {
                    $jml += max(1, (int) ceil(mb_strwidth($b, 'UTF-8') / $lebar_char));
                }
                $this->tinggi[$r] = max($this->tinggi[$r] ?? 0, $jml * 15.5 + 6);
            }
        }

        // Foto: pastikan total tinggi baris yang ditempati cukup
        foreach ($this->gambar as $g) {
            $butuh = $g['h'] * 0.75 + 8; // px -> pt + padding
            $ada = 0;
            for ($r = $g['row']; $r < $g['row'] + $g['rs']; $r++) {
                $ada += max(self::TINGGI_MIN, $this->tinggi[$r] ?? 0);
            }
            if ($ada < $butuh) {
                $akhir = $g['row'] + $g['rs'] - 1;
                $this->tinggi[$akhir] = max(self::TINGGI_MIN, $this->tinggi[$akhir] ?? 0) + ($butuh - $ada);
            }
        }
    }

    // ---------------------------------------------------------------- XLSX

    private function _tulis_zip(array $foto, $nama_sheet)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);

        // foto yang benar-benar dipakai
        $media = [];
        foreach ($this->gambar as $i => $g) {
            if (!isset($foto[$g['nama']])) { unset($this->gambar[$i]); continue; }
            $media[$g['nama']] = $foto[$g['nama']];
        }
        $ada_gambar = !empty($this->gambar);

        $zip->addFromString('[Content_Types].xml', $this->_content_types($media, $ada_gambar));
        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $this->_x(mb_substr($nama_sheet, 0, 31)) . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/styles.xml', $this->_styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->_sheet($ada_gambar));

        if ($ada_gambar) {
            $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/>'
                . '</Relationships>');
            list($drawing, $rels) = $this->_drawing(array_keys($media));
            $zip->addFromString('xl/drawings/drawing1.xml', $drawing);
            $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', $rels);
            foreach ($media as $nama => $f) {
                $zip->addFromString('xl/media/' . $nama, $f['data']);
            }
        }

        $zip->close();
        $isi = file_get_contents($tmp);
        @unlink($tmp);
        return $isi;
    }

    private function _content_types(array $media, $ada_gambar)
    {
        $ext = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];
        $def = '';
        $sudah = [];
        foreach (array_keys($media) as $nama) {
            $e = strtolower(pathinfo($nama, PATHINFO_EXTENSION));
            if (isset($ext[$e]) && !isset($sudah[$e])) {
                $def .= '<Default Extension="' . $e . '" ContentType="' . $ext[$e] . '"/>';
                $sudah[$e] = true;
            }
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>' . $def
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . ($ada_gambar ? '<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>' : '')
            . '</Types>';
    }

    private function _styles()
    {
        // numFmt 164 = Rupiah, 165 = luas 2 desimal
        $xf = function ($font, $fill, $border, $numfmt, $h, $v, $wrap) {
            return '<xf numFmtId="' . $numfmt . '" fontId="' . $font . '" fillId="' . $fill . '" borderId="' . $border . '" xfId="0"'
                . ($numfmt ? ' applyNumberFormat="1"' : '') . ' applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
                . '<alignment' . ($h ? ' horizontal="' . $h . '"' : '') . ' vertical="' . $v . '"' . ($wrap ? ' wrapText="1"' : '') . '/></xf>';
        };
        $xfs = [
            self::S_DEFAULT    => $xf(0, 0, 0, 0, '', 'top', false),
            self::S_TH         => $xf(1, 2, 1, 0, 'center', 'center', true),
            self::S_TEKS_BRD   => $xf(0, 0, 1, 0, 'left', 'top', true),
            self::S_NUM_BRD    => $xf(0, 0, 1, 0, 'center', 'center', true),
            self::S_RP_BRD     => $xf(0, 0, 1, 164, 'right', 'center', false),
            self::S_LUAS_BRD   => $xf(0, 0, 1, 165, 'center', 'center', false),
            self::S_JUDUL      => $xf(2, 0, 0, 0, 'center', 'center', false),
            self::S_TENGAH     => $xf(0, 0, 0, 0, 'center', 'top', false),
            self::S_TLABEL     => $xf(0, 0, 0, 0, 'right', 'center', false),
            self::S_TVAL       => $xf(3, 0, 0, 164, 'right', 'center', false),
            self::S_TLABEL_B   => $xf(4, 0, 2, 0, 'right', 'center', false),
            self::S_TVAL_B     => $xf(4, 0, 2, 164, 'right', 'center', false),
            self::S_BOLD       => $xf(3, 0, 0, 0, 'left', 'top', false),
            self::S_GARIS_ATAS => $xf(0, 0, 3, 0, 'center', 'top', false),
            self::S_TEKS       => $xf(0, 0, 0, 0, 'left', 'top', false),
            self::S_TEKS_WRAP  => $xf(0, 0, 0, 0, 'left', 'top', true),
            self::S_TQTY       => $xf(3, 0, 0, 0, 'right', 'center', false),
            self::S_GARIS_BAWAH => $xf(0, 0, 4, 0, '', 'top', false),
        ];
        ksort($xfs);

        $tipis = '<left style="thin"><color rgb="FF9AA5A0"/></left><right style="thin"><color rgb="FF9AA5A0"/></right>'
            . '<top style="thin"><color rgb="FF9AA5A0"/></top><bottom style="thin"><color rgb="FF9AA5A0"/></bottom><diagonal/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="2"><numFmt numFmtId="164" formatCode="&quot;Rp &quot;#,##0"/><numFmt numFmtId="165" formatCode="0.00"/></numFmts>'
            . '<fonts count="5">'
            . '<font><sz val="11"/><color rgb="FF222222"/><name val="Arial"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF14512F"/><name val="Arial"/></font>'
            . '<font><b/><sz val="16"/><color rgb="FF14512F"/><name val="Arial"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF222222"/><name val="Arial"/></font>'
            . '<font><b/><sz val="12"/><color rgb="FF14512F"/><name val="Arial"/></font>'
            . '</fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFEAF5EE"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="5">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border>' . $tipis . '</border>'
            . '<border><left/><right/><top style="medium"><color rgb="FF1F7A45"/></top><bottom/><diagonal/></border>'
            . '<border><left/><right/><top style="thin"><color rgb="FF333333"/></top><bottom/><diagonal/></border>'
            . '<border><left/><right/><top/><bottom style="double"><color rgb="FF1F7A45"/></bottom><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . count($xfs) . '">' . implode('', $xfs) . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function _sheet($ada_gambar)
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            . '<sheetViews><sheetView workbookViewId="0" showGridLines="0"/></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="' . self::TINGGI_MIN . '" customHeight="1"/>';

        if ($this->lebar) {
            $x .= '<cols>';
            foreach ($this->lebar as $c => $px) {
                $w = round(($px - 5) / 7, 2); // px -> lebar karakter Excel
                $x .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . $w . '" customWidth="1"/>';
            }
            $x .= '</cols>';
        }

        $x .= '<sheetData>';
        $semua_baris = array_unique(array_merge(array_keys($this->sel), array_keys($this->tinggi)));
        sort($semua_baris);
        foreach ($semua_baris as $r) {
            // Tinggi SELALU ditulis: kalau dibiarkan otomatis, tiap aplikasi (Excel/WPS/LibreOffice)
            // menghitung sendiri dan posisi foto di bawahnya bisa bergeser.
            $ht = max(self::TINGGI_MIN, (float) ($this->tinggi[$r] ?? 0));
            $x .= '<row r="' . ($r + 1) . '" ht="' . round($ht, 1) . '" customHeight="1">';
            $kolom = $this->sel[$r] ?? [];
            ksort($kolom);
            foreach ($kolom as $c => $s) {
                $ref = $this->_ref($r, $c);
                if ($s['num']) {
                    $x .= '<c r="' . $ref . '" s="' . $s['s'] . '"><v>' . $s['v'] . '</v></c>';
                } elseif ($s['v'] !== '') {
                    $x .= '<c r="' . $ref . '" s="' . $s['s'] . '" t="inlineStr"><is><t xml:space="preserve">' . $this->_x($s['v']) . '</t></is></c>';
                } else {
                    $x .= '<c r="' . $ref . '" s="' . $s['s'] . '"/>';
                }
            }
            $x .= '</row>';
        }
        $x .= '</sheetData>';

        if ($this->merge) {
            $x .= '<mergeCells count="' . count($this->merge) . '">';
            foreach ($this->merge as $m) $x .= '<mergeCell ref="' . $m . '"/>';
            $x .= '</mergeCells>';
        }
        $x .= '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>'
            . '<pageSetup paperSize="9" orientation="portrait" fitToWidth="1" fitToHeight="0"/>';
        if ($ada_gambar) {
            $x .= '<drawing r:id="rId1"/>';
        }
        return $x . '</worksheet>';
    }

    private function _drawing(array $nama_media)
    {
        $rid = [];
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach (array_values($nama_media) as $i => $nama) {
            $rid[$nama] = 'rId' . ($i + 1);
            $rels .= '<Relationship Id="rId' . ($i + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/' . $this->_x($nama) . '"/>';
        }
        $rels .= '</Relationships>';

        $d = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $id = 2;
        foreach ($this->gambar as $g) {
            // Foto dikunci ke sel di DUA ujung (twoCellAnchor): pojok kiri-atas & kanan-bawah.
            // Jadi foto selalu tetap di barisnya walaupun Excel menggambar tinggi baris
            // sedikit berbeda (tidak "turun" ke baris lain).
            $lebar_sel = 0;
            for ($c = $g['col']; $c < $g['col'] + $g['cs']; $c++) $lebar_sel += $this->_lebar_px($c);
            $tinggi_sel = 0;
            for ($r = $g['row']; $r < $g['row'] + $g['rs']; $r++) $tinggi_sel += $this->_tinggi_px($r);

            // jaga foto tetap di dalam area sel (sisakan 3 px tiap sisi)
            $skala = min(1, ($lebar_sel - 6) / $g['w'], ($tinggi_sel - 6) / $g['h']);
            $w = max(1, (int) floor($g['w'] * $skala));
            $h = max(1, (int) floor($g['h'] * $skala));

            $x1 = (int) (($lebar_sel - $w) / 2); // tengah horizontal
            $y1 = 3;                              // rata atas
            list($c1, $cx1) = $this->_posisi($x1, $g['col'], $g['cs'], 'kolom');
            list($c2, $cx2) = $this->_posisi($x1 + $w, $g['col'], $g['cs'], 'kolom');
            list($r1, $ry1) = $this->_posisi($y1, $g['row'], $g['rs'], 'baris');
            list($r2, $ry2) = $this->_posisi($y1 + $h, $g['row'], $g['rs'], 'baris');

            $cx = $w * self::EMU_PX;
            $cy = $h * self::EMU_PX;
            $d .= '<xdr:twoCellAnchor editAs="oneCell">'
                . '<xdr:from><xdr:col>' . $c1 . '</xdr:col><xdr:colOff>' . ($cx1 * self::EMU_PX) . '</xdr:colOff>'
                . '<xdr:row>' . $r1 . '</xdr:row><xdr:rowOff>' . ($ry1 * self::EMU_PX) . '</xdr:rowOff></xdr:from>'
                . '<xdr:to><xdr:col>' . $c2 . '</xdr:col><xdr:colOff>' . ($cx2 * self::EMU_PX) . '</xdr:colOff>'
                . '<xdr:row>' . $r2 . '</xdr:row><xdr:rowOff>' . ($ry2 * self::EMU_PX) . '</xdr:rowOff></xdr:to>'
                . '<xdr:pic><xdr:nvPicPr><xdr:cNvPr id="' . $id . '" name="Foto ' . ($id - 1) . '"/>'
                . '<xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr>'
                . '<xdr:blipFill><a:blip r:embed="' . $rid[$g['nama']] . '"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill>'
                . '<xdr:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>'
                . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr></xdr:pic><xdr:clientData/></xdr:twoCellAnchor>';
            $id++;
        }
        $d .= '</xdr:wsDr>';
        return [$d, $rels];
    }

    // Tinggi baris dalam px (ht disimpan dalam pt)
    private function _tinggi_px($r)
    {
        $pt = $this->tinggi[$r] ?? self::TINGGI_MIN;
        return (int) floor(max(self::TINGGI_MIN, $pt) / 0.75);
    }

    // Ubah jarak px dari awal sel ke (index kolom/baris, offset px), tetap di dalam span
    private function _posisi($px, $awal, $span, $jenis)
    {
        $i = $awal;
        $akhir = $awal + $span - 1;
        while ($i < $akhir) {
            $ukuran = $jenis === 'kolom' ? $this->_lebar_px($i) : $this->_tinggi_px($i);
            if ($px < $ukuran) break;
            $px -= $ukuran;
            $i++;
        }
        $maks = ($jenis === 'kolom' ? $this->_lebar_px($i) : $this->_tinggi_px($i)) - 1;
        return [$i, max(0, min((int) $px, $maks))];
    }

    // ----------------------------------------------------------------- util

    private function _ref($r, $c)
    {
        $huruf = '';
        $c++;
        while ($c > 0) {
            $m = ($c - 1) % 26;
            $huruf = chr(65 + $m) . $huruf;
            $c = (int) (($c - $m) / 26);
        }
        return $huruf . ($r + 1);
    }

    private function _baris_dari_ref($ref)
    {
        return (int) preg_replace('/[A-Z]+/', '', $ref) - 1;
    }

    private function _kol_dari_ref($ref)
    {
        $h = preg_replace('/\d+/', '', $ref);
        $n = 0;
        for ($i = 0; $i < strlen($h); $i++) $n = $n * 26 + (ord($h[$i]) - 64);
        return $n - 1;
    }

    private function _x($s)
    {
        // buang karakter kontrol yang tidak sah di XML
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) $s);
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
