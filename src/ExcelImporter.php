<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelImporter
{
    private $connection;

    public function __construct($dbPath)
    {
        $this->connection = new SQLite3($dbPath);
        if (!$this->connection) {
            die("Connection failed.");
        }
        $this->createTables(); // Ensure tables are created
    }

    private function createTables()
    {
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS customer (
                customer_id INTEGER PRIMARY KEY,
                name TEXT UNIQUE,
                address TEXT
            );
        ");
        
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS invoice (
                invoice_id INTEGER PRIMARY KEY,
                customer_id INTEGER,
                invoice_number TEXT UNIQUE,
                invoice_date TEXT,
                FOREIGN KEY (customer_id) REFERENCES customer(customer_id)
            );
        ");
        
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS product (
                product_id INTEGER PRIMARY KEY,
                title TEXT,
                price REAL
            );
        ");
        
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS invoice_product (
                invoice_id INTEGER,
                product_id INTEGER,
                quantity INTEGER,
                total REAL,
                customer_id INTEGER,
                PRIMARY KEY (invoice_id, product_id),
                FOREIGN KEY (invoice_id) REFERENCES invoice(invoice_id),
                FOREIGN KEY (product_id) REFERENCES product(product_id),
                FOREIGN KEY (customer_id) REFERENCES customer(customer_id)
            );
        ");
    }

    public function importFromExcel($excelFile)
    {
        $reader = IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($excelFile);
        $worksheet = $spreadsheet->getActiveSheet();

        foreach ($worksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }

            // Check if invoice already exists
            $invoiceId = $this->getInvoiceId($rowData[0]);

            // If invoice doesn't exist, insert invoice
            if (!$invoiceId) {
                $customerId = $this->getCustomerId($rowData[2]);
                if (!$customerId) {
                    $customerId = $this->insertCustomer($rowData[2], $rowData[3]);
                }

                $invoiceId = $this->insertInvoice($customerId, $rowData[0], $rowData[1]);
            }

            // Insert Product
            $productId = $this->insertProduct($rowData[4], $rowData[6]);

            // Insert Invoice Product
            $this->insertInvoiceProduct($invoiceId, $productId, $rowData[5], $rowData[7], $customerId);
        }
    }

    private function getInvoiceId($invoiceNumber)
    {
        $query = "SELECT invoice_id FROM invoice WHERE invoice_number = :invoice_number";
        $stmt = $this->connection->prepare($query);
        $stmt->bindValue(':invoice_number', $invoiceNumber);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return ($row) ? $row['invoice_id'] : null;
    }

    private function getCustomerId($customerName)
    {
        $query = "SELECT customer_id FROM customer WHERE name = :name";
        $stmt = $this->connection->prepare($query);
        $stmt->bindValue(':name', $customerName);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return ($row) ? $row['customer_id'] : null;
    }

    private function insertCustomer($name, $address)
    {
        $query = "INSERT INTO customer (name, address) VALUES (:name, :address)";
        $stmt = $this->connection->prepare($query);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':address', $address);
        $stmt->execute();
        return $this->connection->lastInsertRowID();
    }

    private function insertInvoice($customerId, $invoiceNumber, $invoiceDate)
    {
        $query = "INSERT INTO invoice (customer_id, invoice_number, invoice_date) VALUES (:customer_id, :invoice_number, :invoice_date)";
        $stmt = $this->connection->prepare($query);
        $stmt->bindValue(':customer_id', $customerId);
        $stmt->bindValue(':invoice_number', $invoiceNumber);
        $stmt->bindValue(':invoice_date', date("Y-m-d", strtotime($invoiceDate)));
        $stmt->execute();
        return $this->connection->lastInsertRowID();
    }

    private function insertProduct($title, $price)
    {
        $query = "INSERT INTO product (title, price) VALUES (:title, :price)";
        $stmt = $this->connection->prepare($query);
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':price', $price);
        $stmt->execute();
        return $this->connection->lastInsertRowID();
    }

    private function insertInvoiceProduct($invoiceId, $productId, $quantity, $total, $customerId)
    {
        $query = "INSERT INTO invoice_product (invoice_id, product_id, quantity, total, customer_id) VALUES (:invoice_id, :product_id, :quantity, :total, :customer_id)";
        $stmt = $this->connection->prepare($query);
        $stmt->bindValue(':invoice_id', $invoiceId);
        $stmt->bindValue(':product_id', $productId);
        $stmt->bindValue(':quantity', $quantity);
        $stmt->bindValue(':total', $total);
        $stmt->bindValue(':customer_id', $customerId);
        $stmt->execute();
    }

    public function closeConnection()
    {
        $this->connection->close();
    }
}
