<?php

namespace Tests\Unit\Exports;

use App\Support\Exports\CsvExportResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvExportResponseTest extends TestCase
{
    use RefreshDatabase;
    public function test_it_streams_excel_compatible_utf8_csv_and_neutralizes_formula_cells(): void
    {
        $response = CsvExportResponse::stream(
            'daftar-tamu.csv',
            ['Nama', 'Jumlah', 'Catatan'],
            [
                ['Ayu', 2, '=HYPERLINK("https://example.test")'],
                ['Bima', 0, " \t+SUM(1,1)"],
                ['Citra', -25, 'Catatan biasa'],
                ['Dewi', null, '@formula'],
            ],
        );

        ob_start();
        $response->sendContent();
        $content = (string) ob_get_clean();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename=daftar-tamu.csv', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString("'=HYPERLINK", $content);
        $this->assertStringContainsString("' \t+SUM", $content);
        $this->assertStringContainsString("'@formula", $content);
        $this->assertStringContainsString(',-25,', $content);
        $this->assertStringContainsString('Catatan biasa', $content);
    }

    public function test_it_normalizes_unsafe_download_filename_characters(): void
    {
        $response = CsvExportResponse::stream("../daftar\r\ntamu", ['Nama'], []);
        $disposition = (string) $response->headers->get('Content-Disposition');

        $this->assertStringNotContainsString('..', $disposition);
        $this->assertStringNotContainsString("\r", $disposition);
        $this->assertStringNotContainsString("\n", $disposition);
        $this->assertStringContainsString('.csv', $disposition);
    }
}
