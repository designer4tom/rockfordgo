<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\JcTable;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Turns a header row + row iterable into a downloadable file. Shared by every
 * admin list page that offers CSV/Excel/Word export, so the four formats stay
 * visually consistent instead of each controller rolling its own writer.
 */
class TableExport
{
    public static function csv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            // BOM so Excel on Windows doesn't mangle non-ASCII (names, etc.)
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public static function xlsx(string $filename, string $title, array $headers, iterable $rows): StreamedResponse
    {
        $sheet = new Spreadsheet();
        $active = $sheet->getActiveSheet();
        $active->setTitle(substr(preg_replace('/[\[\]:*?\/\\\\]/', '', $title), 0, 31) ?: 'Sheet1');

        $colCount = count($headers);
        $col = 1;
        foreach ($headers as $h) {
            $active->setCellValue([$col++, 1], $h);
        }
        $lastCol = Coordinate::stringFromColumnIndex($colCount);
        $active->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
        $active->getStyle("A1:{$lastCol}1")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2FF');
        $active->getStyle("A1:{$lastCol}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $r = 2;
        foreach ($rows as $row) {
            $col = 1;
            foreach ($row as $val) {
                $active->setCellValue([$col++, $r], $val);
            }
            $r++;
        }

        foreach (range(1, $colCount) as $c) {
            $active->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }
        $active->freezePane('A2');

        return response()->streamDownload(function () use ($sheet) {
            (new XlsxWriter($sheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public static function docx(string $filename, string $title, array $subtitle, array $headers, iterable $rows): StreamedResponse
    {
        $doc = new PhpWord();
        $section = $doc->addSection(['orientation' => 'landscape', 'marginLeft' => 600, 'marginRight' => 600]);

        $section->addText(htmlspecialchars($title), ['bold' => true, 'size' => 18, 'color' => '312E81']);
        foreach ($subtitle as $line) {
            $section->addText(htmlspecialchars($line), ['size' => 9, 'color' => '6B7280']);
        }
        $section->addTextBreak(1);

        $table = $section->addTable([
            'borderSize' => 4,
            'borderColor' => 'E5E7EB',
            'cellMarginTop' => 80,
            'cellMarginBottom' => 80,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);

        $table->addRow(null, ['tblHeader' => true]);
        foreach ($headers as $h) {
            $table->addCell(null, ['bgColor' => '4F46E5'])
                ->addText(htmlspecialchars((string) $h), ['bold' => true, 'color' => 'FFFFFF', 'size' => 9]);
        }

        foreach ($rows as $row) {
            $table->addRow();
            foreach ($row as $val) {
                $table->addCell(null)->addText(htmlspecialchars((string) $val), ['size' => 9]);
            }
        }

        $footer = $section->addFooter();
        $footer->addPreserveText(
            'ReadyRide Admin Panel · Generated {DATE \@ "d MMM yyyy, h:mm a"} · Page {PAGE} of {NUMPAGES}',
            ['size' => 8, 'color' => '9CA3AF'],
            ['alignment' => JcTable::CENTER]
        );

        return response()->streamDownload(function () use ($doc) {
            \PhpOffice\PhpWord\IOFactory::createWriter($doc, 'Word2007')->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }
}
