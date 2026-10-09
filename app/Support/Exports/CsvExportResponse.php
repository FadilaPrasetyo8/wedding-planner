<?php

namespace App\Support\Exports;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class CsvExportResponse
{
    /**
     * @param list<string> $headers
     * @param iterable<array<int, mixed>> $rows
     */
    public static function stream(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        $safeFilename = self::filename($filename);

        return response()->streamDownload(static function () use ($headers, $rows): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, array_map(self::cell(...), $headers));

            foreach ($rows as $row) {
                fputcsv($output, array_map(self::cell(...), array_values($row)));
            }

            fclose($output);
        }, $safeFilename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    private static function filename(string $filename): string
    {
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename) ?: 'export.csv';
        $filename = trim($filename, '.-');

        if ($filename === '') {
            $filename = 'export';
        }

        return str_ends_with(strtolower($filename), '.csv') ? $filename : $filename.'.csv';
    }

    private static function cell(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return preg_match('/^\s*[=+\-@]/u', $value) === 1 ? "'".$value : $value;
    }
}
