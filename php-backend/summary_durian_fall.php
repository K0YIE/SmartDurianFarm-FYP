<?php
$host = "localhost";
$user = "root";
$pass = "root";
$db   = "fyp";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(["durian_count" => 0, "timestamp" => null]);
    exit;
}

$sql = "SELECT durian_count, timestamp FROM durian_count ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        "durian_count" => (int)$row["durian_count"],
        "timestamp"    => $row["timestamp"]
    ]);
} else {
    echo json_encode(["durian_count" => 0, "timestamp" => null]);
}

$conn->close();
?>
