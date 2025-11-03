<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
  echo json_encode(['error' => 'Not authenticated']);
  exit();
}

include '../includes/db_connect.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id']) || !isset($input['typing'])) {
  echo json_encode(['error' => 'Invalid request']);
  exit();
}

$current_user_email = $_SESSION['user'];
$typing_to_id = (int)$input['user_id'];
$is_typing = (bool)$input['typing'];

// Get current user ID
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $current_user_email);
$stmt->execute();
$result = $stmt->get_result();
$current_user = $result->fetch_assoc();
$user_id = $current_user['id'];
$stmt->close();

// Update or insert typing status
$sql = "INSERT INTO typing_status (user_id, typing_to, is_typing, updated_at) 
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
        is_typing = VALUES(is_typing),
        updated_at = NOW()";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $typing_to_id, $is_typing);

if ($stmt->execute()) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['error' => 'Failed to update typing status']);
}

$stmt->close();
$conn->close();
?>
