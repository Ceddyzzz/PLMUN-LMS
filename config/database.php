<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'plmun lms');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS `plmun lms` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

// Select the database
$conn->select_db("`plmun lms`");

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to test database connection
function testDatabaseConnection() {
    global $conn;
    
    if ($conn->ping()) {
        return "Database connected successfully!";
    } else {
        return "Database connection failed: " . $conn->error;
    }
}
?>
