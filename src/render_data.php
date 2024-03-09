<?php

// Path to SQLite database file
$dbPath = 'mts.db';

// Connect to the SQLite database
$connection = new SQLite3($dbPath);
if (!$connection) {
    die("Connection failed.");
}

// Fetch data from the database
$query = "SELECT * FROM invoice 
          JOIN customer ON invoice.customer_id = customer.customer_id
          JOIN invoice_product ON invoice.invoice_id = invoice_product.invoice_id
          JOIN product ON invoice_product.product_id = product.product_id";
$result = $connection->query($query);

// Create an array to hold the data
$data = array();
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $data[] = $row;
}

// Close the database connection
$connection->close();

// Set the appropriate content type header based on the requested format
if (isset($_GET['format']) && $_GET['format'] === 'xml') {
    header('Content-Type: application/xml');
    // Create XML output
    $xml = new SimpleXMLElement('<data/>');
    foreach ($data as $item) {
        $entry = $xml->addChild('entry');
        foreach ($item as $key => $value) {
            $entry->addChild($key, htmlspecialchars($value));
        }
    }
    // Output the XML
    echo $xml->asXML();
} else {
    header('Content-Type: application/json');
    // Output the JSON
    echo json_encode($data, JSON_PRETTY_PRINT);
}
