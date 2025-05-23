<?php
// Database connection
$servername = "localhost";
$username = "root"; // MySQL username
$password = "root"; // MySQL password
$dbname = "fyp"; // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the latest sensor data
$sql = "SELECT * FROM sensor_readings ORDER BY timestamp DESC LIMIT 1"; // Fetch the latest reading
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Output data for the latest row
    $sensor_data = $result->fetch_assoc(); // Fetch the latest reading as an associative array
} else {
    $sensor_data = [];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sensor Data Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin: 20px; }
        .gauge-container { display: flex; justify-content: space-around; margin-bottom: 30px; }
        .gauge { width: 250px; height: 200px; }
        .btn-refresh { margin: 10px 0; padding: 10px 20px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        .btn-refresh:hover { background-color: #45a049; }
    </style>
</head>
<body>

    <h1>Sensor Data Dashboard</h1>
    <button class="btn-refresh" onclick="loadSensorData()">Refresh Data</button>

    <div class="gauge-container">
        <div id="mq135-gauge" class="gauge"></div>
        <div id="temperature-gauge" class="gauge"></div>
        <div id="light-level-gauge" class="gauge"></div>
    </div>

    <!-- Include JustGage and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/justgage@1.3.0/raphael-2.1.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/justgage@1.3.0/justgage.min.js"></script>

    <script>
        // Initialize the gauges with the sensor data
        let mq135_value = <?php echo isset($sensor_data['mq135_value']) ? $sensor_data['mq135_value'] : 0; ?>;
        let temperature = <?php echo isset($sensor_data['temperature']) ? $sensor_data['temperature'] : 0; ?>;
        let light_level = <?php echo isset($sensor_data['light_level']) ? $sensor_data['light_level'] : 0; ?>;

        var mq135Gauge = new JustGage({
            id: "mq135-gauge",
            value: mq135_value,
            min: 0,
            max: 1000,
            title: "MQ135 Value",
            label: "ppm",
            levelColors: ["#ff0000", "#ffcc00", "#00ff00"]
        });

        var temperatureGauge = new JustGage({
            id: "temperature-gauge",
            value: temperature,
            min: -40,
            max: 100,
            title: "Temperature",
            label: "°C",
            levelColors: ["#00ccff", "#ffff00", "#ff0000"]
        });

        var lightLevelGauge = new JustGage({
            id: "light-level-gauge",
            value: light_level,
            min: 0,
            max: 1023,
            title: "Light Level",
            label: "LDR",
            levelColors: ["#ff0000", "#ffcc00", "#00ff00"]
        });

        // Function to refresh the data using AJAX
        function loadSensorData() {
            const xhr = new XMLHttpRequest();
            xhr.open("GET", "fetch_data.php", true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    let newData = JSON.parse(xhr.responseText);
                    mq135Gauge.refresh(newData.mq135_value);
                    temperatureGauge.refresh(newData.temperature);
                    lightLevelGauge.refresh(newData.light_level);
                }
            };
            xhr.send();
        }

        // Auto-refresh every 10 seconds
        setInterval(loadSensorData, 10000);
    </script>

</body>
</html>
