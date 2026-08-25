<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class SimpleXlsxWriter
{
    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, scalar|null>>  $rows
     */
    public function write(string $title, array $headers, array $rows): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi ZipArchive belum tersedia di server.');
        }

        $path = tempnam(sys_get_temp_dir(), 'bbh-xlsx-');
        if ($path === false) {
            throw new RuntimeException('Gagal: File sementara XLSX gagal dibuat.');
        }

        $xlsxPath = $path.'.xlsx';
        @rename($path, $xlsxPath);

        $zip = new ZipArchive;
        if ($zip->open($xlsxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal: File XLSX gagal dibuat.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook($title));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml($headers, $rows));
        $zip->close();

        return $xlsxPath;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, scalar|null>>  $rows
     */
    private function sheetXml(array $headers, array $rows): string
    {
        $sheetRows = [];
        $sheetRows[] = $this->rowXml(1, $headers, true);

        foreach (array_values($rows) as $index => $row) {
            $sheetRows[] = $this->rowXml($index + 2, $row, false);
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="18"/>'
            .'<sheetData>'.implode('', $sheetRows).'</sheetData>'
            .'</worksheet>';
    }

    /**
     * @param  array<int, scalar|null>  $values
     */
    private function rowXml(int $rowNumber, array $values, bool $header): string
    {
        $cells = [];
        foreach (array_values($values) as $index => $value) {
            $ref = $this->columnName($index + 1).$rowNumber;
            $style = $header ? ' s="1"' : '';
            $cells[] = '<c r="'.$ref.'" t="inlineStr"'.$style.'><is><t>'.$this->escape((string) ($value ?? '')).'</t></is></c>';
        }

        return '<row r="'.$rowNumber.'">'.implode('', $cells).'</row>';
    }

    private function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)).$name;
            $number = intdiv($number, 26);
        }

        return $name;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbook(string $title): string
    {
        $safeTitle = mb_substr(preg_replace('/[\\\\\\/\\?\\*\\[\\]:]+/', ' ', $title) ?: 'Laporan', 0, 31);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$this->escape($safeTitle).'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            .'</styleSheet>';
    }
}
