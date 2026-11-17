<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Penulis file Excel (.xlsx) sederhana TANPA library tambahan.
 * Hanya butuh ekstensi PHP "zip" (ZipArchive) yang umumnya sudah aktif.
 *
 * Pemakaian:
 *   $this->load->library('xlsx_writer');
 *   $x = $this->xlsx_writer;
 *   $x->add_sheet('Rincian', $rows, array('widths' => array(5, 12, 30)));
 *   $x->download('laporan.xlsx');
 *
 * $rows = array baris; tiap baris = array sel. Sel bisa:
 *   - string / angka biasa
 *   - array('v' => nilai, 's' => gaya)   gaya: 'judul', 'header', 'bold', 'uang', 'uang_bold', 'persen', 'muted'
 *   - null  (sel kosong)
 * Opsi sheet: widths (lebar kolom), freeze (nomor baris yang dibekukan di atas), autofilter ('A4:H4')
 */
class Xlsx_writer
{
    private $sheets = array();

    // Indeks gaya di styles.xml
    private $gaya = array(
        ''          => 0,
        'judul'     => 1,
        'header'    => 2,
        'bold'      => 3,
        'uang'      => 4,
        'uang_bold' => 5,
        'persen'    => 6,
        'muted'     => 7,
    );

    public static function tersedia()
    {
        return class_exists('ZipArchive');
    }

    public function add_sheet($nama, $rows, $opsi = array())
    {
        $nama = trim(preg_replace('/[\[\]\:\*\?\/\\\\]/', ' ', (string) $nama));
        $nama = mb_substr($nama !== '' ? $nama : 'Sheet' . (count($this->sheets) + 1), 0, 31);
        $this->sheets[] = array('nama' => $nama, 'rows' => $rows, 'opsi' => $opsi);
    }

    // Kirim ke browser sebagai unduhan
    public function download($nama_file)
    {
        $tmp = $this->simpan_tmp();
        $nama_file = preg_replace('/[^A-Za-z0-9._-]+/', '_', $nama_file);
        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nama_file . '"');
        header('Content-Length: ' . filesize($tmp));
        header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    // Tulis ke file sementara, return path-nya
    public function simpan_tmp()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            show_error('Gagal membuat file Excel.');
        }
        $n = count($this->sheets);
        $zip->addFromString('[Content_Types].xml', $this->_content_types($n));
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', $this->_workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->_workbook_rels($n));
        $zip->addFromString('xl/styles.xml', $this->_styles());
        foreach ($this->sheets as $i => $s) {
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $this->_sheet($s));
        }
        $zip->close();
        return $tmp;
    }

    // ================= XML =================

    private function _content_types($n)
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        for ($i = 1; $i <= $n; $i++) {
            $x .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return $x . '</Types>';
    }

    private function _workbook()
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        foreach ($this->sheets as $i => $s) {
            $x .= '<sheet name="' . $this->_esc($s['nama']) . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
        }
        $x .= '</sheets>';
        // nama tersembunyi untuk autofilter
        $def = '';
        foreach ($this->sheets as $i => $s) {
            if (!empty($s['opsi']['autofilter'])) {
                $ref = preg_replace('/([A-Z]+)(\d+)/', '\$$1\$$2', $s['opsi']['autofilter']);
                $def .= '<definedName name="_xlnm._FilterDatabase" localSheetId="' . $i . '" hidden="1">\''
                    . $this->_esc(str_replace("'", "''", $s['nama'])) . '\'!' . $ref . '</definedName>';
            }
        }
        if ($def !== '') {
            $x .= '<definedNames>' . $def . '</definedNames>';
        }
        return $x . '</workbook>';
    }

    private function _workbook_rels($n)
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        for ($i = 1; $i <= $n; $i++) {
            $x .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }
        $x .= '<Relationship Id="rId' . ($n + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        return $x . '</Relationships>';
    }

    private function _styles()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="2"><numFmt numFmtId="164" formatCode="&quot;Rp&quot;\ #,##0;[Red]\-&quot;Rp&quot;\ #,##0"/><numFmt numFmtId="165" formatCode="0.0%"/></numFmts>'
            . '<fonts count="5">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="14"/><color rgb="FF1F3B57"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><i/><sz val="10"/><color rgb="FF666666"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="4">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1F3B57"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFEEF2F5"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFC9D1D9"/></left><right style="thin"><color rgb="FFC9D1D9"/></right><top style="thin"><color rgb="FFC9D1D9"/></top><bottom style="thin"><color rgb="FFC9D1D9"/></bottom><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="8">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'                                                   // 0 biasa
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                                     // 1 judul
            . '<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' // 2 header
            . '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'      // 3 bold (baris total)
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'                          // 4 uang
            . '<xf numFmtId="164" fontId="3" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"/>' // 5 uang bold
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'                          // 6 persen
            . '<xf numFmtId="0" fontId="4" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                                     // 7 muted
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function _sheet($s)
    {
        $o = $s['opsi'];
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>';   // cetak: muat 1 halaman lebar
        if (!empty($o['freeze'])) {
            $r = (int) $o['freeze'] + 1;
            $x .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="' . (int) $o['freeze'] . '" topLeftCell="A' . $r
                . '" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A' . $r . '" sqref="A' . $r . '"/></sheetView></sheetViews>';
        }
        if (!empty($o['widths'])) {
            $x .= '<cols>';
            foreach (array_values($o['widths']) as $i => $w) {
                $x .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . (float) $w . '" customWidth="1"/>';
            }
            $x .= '</cols>';
        }
        $x .= '<sheetData>';
        foreach (array_values($s['rows']) as $ri => $row) {
            $rn = $ri + 1;
            $x .= '<row r="' . $rn . '">';
            foreach (array_values((array) $row) as $ci => $cell) {
                $x .= $this->_cell($this->_kol($ci) . $rn, $cell);
            }
            $x .= '</row>';
        }
        $x .= '</sheetData>';
        if (!empty($o['autofilter'])) {
            $x .= '<autoFilter ref="' . $this->_esc($o['autofilter']) . '"/>';
        }
        if (!empty($o['merge'])) {
            $x .= '<mergeCells count="' . count($o['merge']) . '">';
            foreach ($o['merge'] as $m) {
                $x .= '<mergeCell ref="' . $this->_esc($m) . '"/>';
            }
            $x .= '</mergeCells>';
        }
        $x .= '<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.3" footer="0.3"/>'
            . '<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>';
        return $x . '</worksheet>';
    }

    private function _cell($ref, $cell)
    {
        $gaya = '';
        $v    = $cell;
        if (is_array($cell)) {
            $v    = isset($cell['v']) ? $cell['v'] : null;
            $gaya = isset($cell['s']) ? $cell['s'] : '';
        }
        $s  = isset($this->gaya[$gaya]) ? $this->gaya[$gaya] : 0;
        $sa = $s ? ' s="' . $s . '"' : '';
        if ($v === null || $v === '') {
            return $s ? '<c r="' . $ref . '"' . $sa . '/>' : '';
        }
        if (is_int($v) || is_float($v)) {
            return '<c r="' . $ref . '"' . $sa . '><v>' . (is_finite($v) ? $v + 0 : 0) . '</v></c>';
        }
        return '<c r="' . $ref . '"' . $sa . ' t="inlineStr"><is><t xml:space="preserve">' . $this->_esc($v) . '</t></is></c>';
    }

    // 0 -> A, 25 -> Z, 26 -> AA
    private function _kol($i)
    {
        $s = '';
        for ($i++; $i > 0; $i = (int) (($i - 1) / 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }
        return $s;
    }

    private function _esc($s)
    {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) $s);   // karakter kontrol tidak sah di XML
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
