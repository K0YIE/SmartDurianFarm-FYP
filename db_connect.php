<?php
// Database credentials
$servername = "localhost";    // Change this if your database is hosted somewhere else
$username = "root";             // Your MySQL username
$password = "root";             // Your MySQL password
$dbname = "fyp";                // The name of your database

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>