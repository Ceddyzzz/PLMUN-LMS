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

// Get request details
$stmt = $conn->prepare("SELECT sender_id, receiver_id FROM friend_requests 
                        WHERE id = ? AND receiver_id = ? AND status = 'pending'");
$stmt->bind_param("ii", $request_id, $current_user_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$request) {
  echo json_encode(['success' => false, 'error' => 'Request not found']);
  exit();
}

// Create friendship (always store smaller ID first)
$user1 = min($request['sender_id'], $request['receiver_id']);
$user2 = max($request['sender_id'], $request['receiver_id']);

$stmt = $conn->prepare("INSERT IGNORE INTO friendships (user1_id, user2_id) VALUES (?, ?)");
$stmt->bind_param("ii", $user1, $user2);
$stmt->execute();
$stmt->close();

// Update request status
$stmt = $conn->prepare("UPDATE friend_requests SET status = 'accepted' WHERE id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);

$conn->close();
?>
