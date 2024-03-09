<?php

use PHPUnit\Framework\TestCase;

require 'src/ExcelImporter.php';

class ExcelImporterTest extends TestCase
{
    private $excelImporter;

    protected function setUp(): void
    {
        $this->excelImporter = new ExcelImporter('test_database.db');
    }

    protected function tearDown(): void
    {
        $this->excelImporter->closeConnection();
    }

    public function testImportFromExcel()
    {
        
        $excelFile = 'data.xlsx';

        // Call the importFromExcel method
        $this->excelImporter->importFromExcel($excelFile);
        
    }
}
