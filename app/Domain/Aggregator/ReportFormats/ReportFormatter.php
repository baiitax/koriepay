<?php

namespace App\Domain\Aggregator\ReportFormats;

use Barryvdh\DomPDF\Facade\Pdf;
use RuntimeException;

/**
 * Report artifact writers (Stage H §62).
 *
 * Every writer returns REAL file bytes of the advertised format:
 *   - CSV  → RFC 4180 text;
 *   - XLSX → a valid OOXML spreadsheet (ZIP of the minimal part set, written
 *            with ZipArchive — no legacy PHPExcel dependency);
 *   - PDF  → rendered through Dompdf.
 * Tests assert magic bytes (PK\x03\x04 for xlsx, %PDF- for pdf).
 */
class ReportFormatter
{
    /**
     * @param  array<string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function format(string $format, string $title, array $headers, array $rows): string
    {
        return match ($format) {
            'csv' => $this->csv($headers, $rows),
            'xlsx' => $this->xlsx($title, $headers, $rows),
            'pdf' => $this->pdf($title, $headers, $rows),
            default => throw new RuntimeException("Unsupported report format [{$format}]."),
        };
    }

    /** @param  array<string>  $headers  @param  array<int, array<int, mixed>>  $rows */
    protected function csv(array $headers, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn ($v) => $this->cell($v), $row));
        }

        rewind($handle);
        $out = stream_get_contents($handle);
        fclose($handle);

        return $out === false ? '' : $out;
    }

    /**
     * Minimal but valid .xlsx: [Content_Types].xml, rels, workbook + sheet
     * with inline strings and a plain number format. Excel/LibreOffice open
     * it; the ZIP magic bytes prove it is a real xlsx, not a renamed CSV.
     *
     * @param  array<string>  $headers  @param  array<int, array<int, mixed>>  $rows
     */
    protected function xlsx(string $title, array $headers, array $rows): string
    {
        if (! class_exists(\ZipArchive::class)) {
            throw new RuntimeException('The Zip extension is required to write XLSX reports.');
        }

        $esc = fn (string $s) => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $sheetRows = [];
        $sheetRows[] = $this->xlsxRow(1, $headers, $esc);
        foreach (array_values($rows) as $i => $row) {
            $sheetRows[] = $this->xlsxRow($i + 2, $row, $esc);
        }

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="15"/>'
            .'<sheetData>'.implode('', $sheetRows).'</sheetData>'
            .'</worksheet>';

        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$esc(mb_substr($title, 0, 31)).'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';

        $zip = new \ZipArchive();
        $path = tempnam(sys_get_temp_dir(), 'rpt') ?: throw new RuntimeException('Could not create temp xlsx.');
        if ($zip->open($path, \ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not open xlsx archive.');
        }

        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>');
        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>');
        $zip->addFromString('xl/workbook.xml', $workbookXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>');
        $zip->addFromString('xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="2"><fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        $bytes = (string) file_get_contents($path);
        unlink($path);

        return $bytes;
    }

    /** @param  array<int, mixed>  $row */
    protected function xlsxRow(int $rowNum, array $row, callable $esc): string
    {
        $cells = '';
        foreach (array_values($row) as $col => $value) {
            $ref = $this->colLetter($col).$rowNum;
            $cell = $this->cell($value);
            if (is_numeric($cell) && $cell !== '') {
                $cells .= '<c r="'.$ref.'" t="n"><v>'.$cell.'</v></c>';
            } else {
                $cells .= '<c r="'.$ref.'" t="inlineStr"><is><t xml:space="preserve">'.$esc($cell).'</t></is></c>';
            }
        }

        return '<row r="'.$rowNum.'">'.$cells.'</row>';
    }

    protected function colLetter(int $index): string
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr(65 + ($index % 26)).$letter;
            $index = (int) ($index / 26) - 1;
        }

        return $letter ?: 'A';
    }

    /** @param  array<string>  $headers  @param  array<int, array<int, mixed>>  $rows */
    protected function pdf(string $title, array $headers, array $rows): string
    {
        $head = '';
        foreach ($headers as $h) {
            $head .= '<th style="text-align:left;padding:6px 10px;border-bottom:2px solid #0f766e;color:#0f766e;font-size:11px">'.e($h).'</th>';
        }

        $body = '';
        foreach ($rows as $row) {
            $body .= '<tr>';
            foreach ($row as $value) {
                $body .= '<td style="padding:6px 10px;border-bottom:1px solid #e2e8f0;font-size:10px">'.e($this->cell($value)).'</td>';
            }
            $body .= '</tr>';
        }

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:DejaVu Sans, sans-serif;">'
            .'<h2 style="color:#0f172a;font-size:16px">'.e($title).'</h2>'
            .'<p style="color:#64748b;font-size:10px">Generated '.now()->toDateTimeString().' · KoriePay Aggregator Console</p>'
            .'<table style="border-collapse:collapse;width:100%">'.$head.'</table>'
            .'<table style="border-collapse:collapse;width:100%">'.$body.'</table>'
            .'</body></html>';

        return Pdf::loadHTML($html)->setPaper('a4', 'landscape')->output();
    }

    /** Normalize a value for a report cell. */
    protected function cell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}
