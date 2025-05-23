<?php
$servername = "localhost";
$username = "root";
$password = "Wolfver_05";
$dbname = "iot_street_light_system";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT latitude, longitude FROM gps_data ORDER BY timestamp DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  $row = $result->fetch_assoc();
  echo json_encode($row);
} else {
  echo json_encode(["latitude" => 0, "longitude" => 0]);
}

$conn->close();
?>
