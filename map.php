<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map</title>
    <link rel="stylesheet" href="style.css">
    
    <!-- Include Leaflet.js for mapping -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    
    <style>
        #map { height: 400px; width: 100%; }
    </style>
</head>
<body>
    <h1>Map Location</h1>
    <div id="map"></div>
    
    <script>
        // Initialize the map
        var map = L.map('map').setView([3.0500, 101.5064], 13);  // Coordinates for KKTPJ

        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Add a marker for KKTPJ
        var marker = L.marker([3.0500, 101.5064]).addTo(map);
        marker.bindPopup("<b>KKTPJ</b><br>Click to view dashboard.").openPopup();

        // Add click event on marker to redirect to dashboard
        marker.on('click', function() {
            window.location.href = "dashboard.php"; // Redirect to the dashboard page
        });
    </script>
</body>
</html>
