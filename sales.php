<?php
// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "inventory"; // change this

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch data from inventory_data table
$sql = "SELECT * FROM inventory_log";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Inventory Data</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        table {
            border-collapse: collapse;
            margin: 30px auto;
            width: 90%;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px 15px;
            text-align: center;
        }
        th {
            background-color: #007bff;
            color: white;
            text-transform: uppercase;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .button-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 30px;
}

.btn {
    padding: 10px 25px;
    background-color: #28a745;
    color: white;
    text-decoration: none;
    font-size: 16px;
    border-radius: 8px;
    transition: 0.3s;
    text-align: center;
    white-space: nowrap;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.btn:hover {
    background-color: #218838;
}

        .map-link {
            color: #007bff;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <h2>Inventory Data Table</h2>

    <table>
        <tr>
            <?php
            // Print headers
            if ($result->num_rows > 0) {
                $fields = $result->fetch_fields();
                foreach ($fields as $field) {
                    echo "<th>" . htmlspecialchars($field->name) . "</th>";
                }
                echo "<th>Map</th></tr>"; // Extra column for Map link

                // Print rows
                $result->data_seek(0);
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    foreach ($row as $key => $value) {
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }

                    // Add Map link (assuming 'latitude' and 'longitude' exist)
                    if (isset($row['latitude']) && isset($row['longitude'])) {
                        $lat = $row['latitude'];
                        $lon = $row['longitude'];
                        $mapURL = "https://www.google.com/maps?q=$lat,$lon";
                        echo "<td><a class='map-link' href='$mapURL' target='_blank'>View</a></td>";
                    } else {
                        echo "<td>N/A</td>";
                    }

                    echo "</tr>";
                }
            } else {
                echo "<td colspan='100%'>No data found.</td>";
            }
            ?>
    </table>

    <div class="button-wrapper">
    <a href="backup.php" class="btn">Generate Backup</a>
    </div>

    <div class="button-wrapper">
    <a href="index.php" class="btn">Go Back</a>
    </div>

</body>
</html>

<?php
$conn->close();
?>
