<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
  echo json_encode(['error' => 'Not authenticated', 'friends' => []]);
  exit();
}

include '../includes/db_connect.php';

$current_user_email = $_SESSION['user'];

// Get current user ID
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $current_user_email);
$stmt->execute();
$result = $stmt->get_result();
$current_user = $result->fetch_assoc();
$current_user_id = $current_user['id'];
$stmt->close();

// Get friends (users with accepted friendship)
$sql = "SELECT DISTINCT
          u.id,
          u.email,
          u.name,
          u.is_online,
          (SELECT m.message 
           FROM messages m 
           WHERE (m.sender_id = u.id AND m.receiver_id = ?) 
              OR (m.sender_id = ? AND m.receiver_id = u.id)
           ORDER BY m.created_at DESC 
           LIMIT 1) as last_message,
          (SELECT DATE_FORMAT(m.created_at, '%h:%i %p')
           FROM messages m 
           WHERE (m.sender_id = u.id AND m.receiver_id = ?) 
              OR (m.sender_id = ? AND m.receiver_id = u.id)
           ORDER BY m.created_at DESC 
           LIMIT 1) as last_message_time,
          (SELECT COUNT(*) 
           FROM messages m 
           WHERE m.sender_id = u.id 
             AND m.receiver_id = ? 
             AND m.is_read = 0) as unread_count
        FROM users u
        INNER JOIN friendships f ON (
          (f.user1_id = ? AND f.user2_id = u.id) OR
          (f.user2_id = ? AND f.user1_id = u.id)
        )
        WHERE u.id != ?
        ORDER BY last_message_time IS NULL, last_message_time DESC, u.name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiiiiii", 
  $current_user_id, $current_user_id, 
  $current_user_id, $current_user_id,
  $current_user_id,
  $current_user_id, $current_user_id,
  $current_user_id
);
$stmt->execute();
$result = $stmt->get_result();

$friends = [];
$colors = ['blue', 'green', 'purple', 'orange', 'red', 'indigo', 'pink', 'teal'];

while ($row = $result->fetch_assoc()) {
  $name = $row['name'] ?: explode('@', $row['email'])[0];
  $nameParts = explode(' ', ucwords(str_replace(['.', '_'], ' ', $name)));
  
  if (count($nameParts) >= 2) {
    $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
  } else {
    $initials = strtoupper(substr($name, 0, 2));
  }

  $friends[] = [
    'id' => $row['id'],
    'name' => ucwords(str_replace(['.', '_'], ' ', $name)),
    'email' => $row['email'],
    'initials' => $initials,
    'color' => $colors[array_rand($colors)],
    'lastMessage' => $row['last_message'] ? substr($row['last_message'], 0, 50) : '',
    'time' => $row['last_message_time'] ?? '',
    'unread' => (int)$row['unread_count'],
    'online' => (bool)$row['is_online']
  ];
}

echo json_encode(['success' => true, 'friends' => $friends]);

$stmt->close();
$conn->close();
?>
