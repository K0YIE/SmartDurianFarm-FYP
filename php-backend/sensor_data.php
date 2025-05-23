<?php
// Database connection details
$servername = "localhost";      // Replace with your server details
$username = "root";             // Replace with your MySQL username
$password = "Wolfver_05";    // Replace with your MySQL password
$dbname = "iot_street_light_system";      // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get data from POST request
$latitude = $_POST['latitude'];
$longitude = $_POST['longitude'];
$mq135 = $_POST['mq135'];
$temp = $_POST['temp'];
$lux = $_POST['lux'];

// Insert GPS data into gps_data table
$sql_gps = "INSERT INTO gps_data (latitude, longitude) VALUES ('$latitude', '$longitude')";
if ($conn->query($sql_gps) === TRUE) {
    echo "GPS data saved successfully.\n";
} else {
    echo "Error: " . $sql_gps . "<br>" . $conn->error;
}

// Insert sensor readings into sensor_readings table
$sql_sensor = "INSERT INTO sensor_readings (mq135_value, temperature, light_level) 
               VALUES ('$mq135', '$temp', '$lux')";
if ($conn->query($sql_sensor) === TRUE) {
    echo "Sensor readings saved successfully.\n";
} else {
    echo "Error: " . $sql_sensor . "<br>" . $conn->error;
}

// Close connection
$conn->close();
?>
