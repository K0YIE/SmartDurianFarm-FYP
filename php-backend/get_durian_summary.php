<?php

// Database credentials
$servername = "localhost";
$username = "Username";
$password = "Password"; 
$dbname = "Database name";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT durian_count, timestamp FROM durian_count ORDER BY id DESC LIMIT 7";
$result = $conn->query($sql);

$data = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            "durian_count" => (int)$row["durian_count"],
            "timestamp"    => $row["timestamp"]
        ];
    }
}

// Return data in chronological order (oldest first)
echo json_encode(array_reverse($data));

$conn->close();
?>
