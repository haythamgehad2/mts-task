<?php

require 'ExcelImporter.php';

// Path to SQLite database file
$dbPath = 'mts.db';

// Instantiate ExcelImporter with SQLite connection
$excelImporter = new ExcelImporter($dbPath);

// Replace 'data.xlsx' with the path to your test Excel file
$excelImporter->importFromExcel('../data.xlsx');

// Close the database connection
$excelImporter->closeConnection();

echo "Data imported successfully.";
