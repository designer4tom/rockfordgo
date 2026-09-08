<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

/**
 * Reads an uploaded CSV or Excel file into an array of associative rows,
 * keyed by a normalised version of the header row (lowercase, spaces ->
 * underscores) so "Phone Number" and "phone_number" both map to the same key.
 */
class TableImport
{
    /** @return array<int, array<string, string>> */
    public static function read(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        return match (true) {
            $ext === 'csv' => self::readCsv($file->getRealPath()),
            in_array($ext, ['xlsx', 'xls'], true) => self::readSpreadsheet($file->getRealPath()),
            default => throw new RuntimeException('Unsupported file type: .' . $ext),
        };
    }

    private static function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException('Could not open the uploaded file.');
        }

        // Skip a UTF-8 BOM if present (Excel adds one on Windows).
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return [];
        }
        $keys = array_map([self::class, 'normalizeHeader'], $header);

        $rows = [];
        while (($line = fgetcsv($handle)) !== false) {
            if (count($line) === 1 && ($line[0] === null || trim((string) $line[0]) === '')) {
                continue; // blank line
            }
            $rows[] = array_combine($keys, array_pad($line, count($keys), null));
        }
        fclose($handle);

        return $rows;
    }

    private static function readSpreadsheet(string $path): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $data = $sheet->toArray(null, true, true, false);

        if (empty($data)) {
            return [];
        }

        $keys = array_map([self::class, 'normalizeHeader'], array_shift($data));

        $rows = [];
        foreach ($data as $line) {
            if (implode('', array_map('strval', $line)) === '') {
                continue; // blank row
            }
            $rows[] = array_combine($keys, array_pad($line, count($keys), null));
        }

        return $rows;
    }

    private static function normalizeHeader(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? $value;

        return trim($value, '_');
    }
}
