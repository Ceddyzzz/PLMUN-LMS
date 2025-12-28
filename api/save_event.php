<?php
session_start();

if (!isset($_SESSION['user'])) {
    die("Not logged in");
}

// Correct include path
include "../includes/db_connect.php";

// Session contains only the user ID (integer)
$userId = $_SESSION['user'];

$event_date = $_POST['event_date'];
$title = $_POST['title'];
$description = $_POST['description'];
$color = $_POST['color'];

$sql = "INSERT INTO events (user_id, event_date, title, description, color)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("issss", $userId, $event_date, $title, $description, $color);
$stmt->execute();

header("Location: ../calendar.php");
exit;
?>
