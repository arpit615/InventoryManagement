<?php
// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "inventory"; // Change to your DB name

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$tableName = "inventory_log"; // Change this if you want to export another table

// Fetch data
$sql = "SELECT * FROM $tableName ";
$result = $conn->query($sql);

// Set headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $tableName . '_backup_' . date('Y-m-d') . '.csv');

// Open output stream
$output = fopen('php://output', 'w');

if ($result->num_rows > 0) {
    // Fetch column names
    $fields = $result->fetch_fields();
    $headers = array();
    foreach ($fields as $field) {
        $headers[] = $field->name;
    }
    fputcsv($output, $headers);

    // Fetch rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
} else {
    // No data fallback
    fputcsv($output, array("No data found in table `$tableName`"));
}

fclose($output);
$conn->close();
exit;
?>
