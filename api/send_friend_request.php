<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
  echo json_encode(['error' => 'Not authenticated']);
  exit();
}

include '../includes/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$receiver_id = (int)$input['user_id'];

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();
$sender_id = $current_user['id'];
$stmt->close();

// Insert friend request
$stmt = $conn->prepare("INSERT INTO friend_requests (sender_id, receiver_id, status) 
                        VALUES (?, ?, 'pending')
                        ON DUPLICATE KEY UPDATE status = 'pending', updated_at = NOW()");
$stmt->bind_param("ii", $sender_id, $receiver_id);

if ($stmt->execute()) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'error' => 'Failed to send request']);
}

$stmt->close();
$conn->close();
?>
