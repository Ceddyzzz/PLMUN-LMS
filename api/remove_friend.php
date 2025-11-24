<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
  echo json_encode(['error' => 'Not authenticated']);
  exit();
}

include '../includes/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$other_user_id = (int)$input['user_id'];

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();
$current_user_id = $current_user['id'];
$stmt->close();

// Delete friendship
$stmt = $conn->prepare("DELETE FROM friendships 
                        WHERE (user1_id = ? AND user2_id = ?) 
                           OR (user1_id = ? AND user2_id = ?)");
$stmt->bind_param("iiii", $current_user_id, $other_user_id, $other_user_id, $current_user_id);

if ($stmt->execute()) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false]);
}

$stmt->close();
$conn->close();
?>
