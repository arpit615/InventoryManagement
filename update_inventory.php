<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "inventory"; // <-- your DB name

$botToken = "8142634174:AAGhd5FpMqHTPKFkKQGQbOAgblvgT82ynMk";
$chatId = "1479753251";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (isset($_GET['item']) && isset($_GET['left']) && isset($_GET['latitude']) && isset($_GET['longitude'])) {
    $item = $conn->real_escape_string($_GET['item']);
    $left = (int) $_GET['left'];
    $latitude = (float) $_GET['latitude'];  // Get latitude
    $longitude = (float) $_GET['longitude'];  // Get longitude
    
   
    date_default_timezone_set('Asia/Kolkata');
$time = date('Y-m-d H:i:s');


    // Update main inventory_data table
    $check = "SELECT * FROM inventory_data WHERE item_name='$item'";
    $result = $conn->query($check);

    if ($result->num_rows > 0) {
        // Update inventory_data with latitude and longitude
        $update = "UPDATE inventory_data SET stock_left=$left, latitude=$latitude, longitude=$longitude WHERE item_name='$item'";
        $conn->query($update);
        echo "Success";
    } 
    else 
    {
        // Insert into inventory_data with latitude and longitude
        $insert_main = "INSERT INTO inventory_data (item_name, stock_left, latitude, longitude) 
                        VALUES ('$item', $left, $latitude, $longitude)";
        $conn->query($insert_main);
        echo "Success";
    }

    // Insert into inventory_log table with latitude and longitude
    $insert_log = "INSERT INTO inventory_log (item_name, stock_left, timestamp, latitude, longitude) 
                   VALUES ('$item', $left, '$time', $latitude, $longitude)";
    $conn->query($insert_log);

    echo "Inventory updated & logged";

    // Telegram alert if low stock
    if ($left < 3) 
    {
        $message = urlencode("⚠️ Low stock: $item has only $left left.");
        $url = "https://api.telegram.org/bot$botToken/sendMessage?chat_id=$chatId&text=$message";
        file_get_contents($url);
    }

} else {
    echo "Missing parameters";
}

$conn->close();
?>
