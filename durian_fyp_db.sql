-- Create the database (if not exists)
CREATE DATABASE IF NOT EXISTS fyp;
USE fyp;

-- Table 1: project1 (for vibration detection)
CREATE TABLE IF NOT EXISTS project1 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    region1 VARCHAR(20),
    time1 DATETIME,
    region2 VARCHAR(20),
    time2 DATETIME
);

-- Table 2: gps (for location tracking)
CREATE TABLE IF NOT EXISTS gps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    latitude DOUBLE NOT NULL,
    longitude DOUBLE NOT NULL,
    timestamp DATETIME NOT NULL
);

-- Table 3: durian_count (for counting fruits)
CREATE TABLE IF NOT EXISTS durian_count (
    id INT AUTO_INCREMENT PRIMARY KEY,
    durian_count INT NOT NULL,
    timestamp DATETIME NOT NULL
);
