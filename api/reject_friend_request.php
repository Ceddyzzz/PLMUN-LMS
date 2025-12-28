<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
  echo json_encode(['error' => 'Not authenticated']);
  exit();
}

include '../includes/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$request_id = (int)$input['request_id'];

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();
$current_user_id = $current_user['id'];
$stmt->close();

// Update request status
$stmt = $conn->prepare("UPDATE friend_requests 
                        SET status = 'rejected' 
                        WHERE id = ? AND receiver_id = ?");
$stmt->bind_param("ii", $request_id, $current_user_id);

if ($stmt->execute()) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false]);
}

$stmt->close();
$conn->close();
?>
