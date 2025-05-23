<?php
$servername = "localhost";
$username = "root";
$password = "Wolfver_05";
$dbname = "iot_street_light_system";  // Replace with your actual database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// SQL query to get the latest sensor data
$sql = "SELECT mq135_value, temperature, light_level FROM sensor_readings ORDER BY timestamp DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  // Output the latest data as an associative array
  $row = $result->fetch_assoc();
  echo json_encode($row);
} else {
  // If no data is found, send default values
  echo json_encode(["mq135_value" => 0, "temperature" => 0, "light_level" => 0]);
}

$conn->close();
?>
