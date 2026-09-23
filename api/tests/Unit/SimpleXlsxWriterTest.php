<?php

namespace Tests\Unit;

use App\Services\SimpleXlsxWriter;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class SimpleXlsxWriterTest extends TestCase
{
    public function test_writer_streams_generator_rows_into_a_valid_worksheet(): void
    {
        $consumed = 0;
        $rows = (function () use (&$consumed) {
            for ($number = 1; $number <= 1200; $number++) {
                $consumed++;
                yield [$number, 'Kambing & '.$number];
            }
        })();

        $path = (new SimpleXlsxWriter)->write('Data Kambing', ['Nomor', 'Nama'], $rows);

        try {
            $this->assertSame(1200, $consumed);
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path) === true);
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();
            $this->assertIsString($xml);
            $this->assertStringContainsString('r="A1201"', $xml);
            $this->assertStringContainsString('Kambing &amp; 1200', $xml);
            $this->assertNotFalse(simplexml_load_string($xml));
        } finally {
            @unlink($path);
        }
    }
}
