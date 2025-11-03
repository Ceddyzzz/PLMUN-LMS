<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !isset($_GET['user_id'])) {
  echo json_encode(['error' => 'Invalid request']);
  exit();
}

include '../includes/db_connect.php';

$current_user_email = $_SESSION['user'];
$other_user_id = (int)$_GET['user_id'];
$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

// Get current user ID
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $current_user_email);
$stmt->execute();
$result = $stmt->get_result();
$current_user = $result->fetch_assoc();
$current_user_id = $current_user['id'];
$stmt->close();

// Get messages between two users
$sql = "SELECT 
          m.id,
          m.message,
          m.sender_id,
          m.created_at,
          DATE_FORMAT(m.created_at, '%h:%i %p') as formatted_time,
          u.email as sender_email,
          u.name as sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.id > ?
          AND ((m.sender_id = ? AND m.receiver_id = ?)
               OR (m.sender_id = ? AND m.receiver_id = ?))
        ORDER BY m.created_at ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiii", $last_id, $current_user_id, $other_user_id, $other_user_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];

while ($row = $result->fetch_assoc()) {
  $sender_name = $row['sender_name'] ?: explode('@', $row['sender_email'])[0];
  $nameParts = explode(' ', ucwords(str_replace(['.', '_'], ' ', $sender_name)));
  $initials = '';
  
  if (count($nameParts) >= 2) {
    $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
  } else {
    $initials = strtoupper(substr($sender_name, 0, 2));
  }

  $messages[] = [
    'id' => (int)$row['id'],
    'message' => $row['message'],
    'sent_by_me' => $row['sender_id'] == $current_user_id,
    'sender_initials' => $initials,
    'time' => $row['formatted_time']
  ];
}

// Mark messages as read
if (!empty($messages)) {
  $update_sql = "UPDATE messages 
                 SET is_read = 1 
                 WHERE sender_id = ? 
                   AND receiver_id = ? 
                   AND is_read = 0";
  $update_stmt = $conn->prepare($update_sql);
  $update_stmt->bind_param("ii", $other_user_id, $current_user_id);
  $update_stmt->execute();
  $update_stmt->close();
}

echo json_encode(['messages' => $messages]);

$stmt->close();
$conn->close();
?>
