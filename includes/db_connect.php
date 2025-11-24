<?php
$servername = "localhost";
$username = "root";  // or your MySQL username
$password = "";      // or your MySQL password
$dbname = "plmun lms";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}
?>
