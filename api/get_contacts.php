<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
  echo json_encode(['error' => 'Not authenticated']);
  exit();
}

include '../includes/db_connect.php';

$current_user = $_SESSION['user'];

// Get all users except current user with their last messages
$sql = "SELECT 
          u.id,
          u.email,
          u.name,
          COALESCE(
            (SELECT m.message 
             FROM messages m 
             WHERE (m.sender_id = u.id AND m.receiver_id = cu.id) 
                OR (m.sender_id = cu.id AND m.receiver_id = u.id)
             ORDER BY m.created_at DESC 
             LIMIT 1),
            ''
          ) as last_message,
          COALESCE(
            (SELECT DATE_FORMAT(m.created_at, '%h:%i %p')
             FROM messages m 
             WHERE (m.sender_id = u.id AND m.receiver_id = cu.id) 
                OR (m.sender_id = cu.id AND m.receiver_id = u.id)
             ORDER BY m.created_at DESC 
             LIMIT 1),
            ''
          ) as last_message_time,
          COALESCE(
            (SELECT COUNT(*) 
             FROM messages m 
             WHERE m.sender_id = u.id 
               AND m.receiver_id = cu.id 
               AND m.is_read = 0),
            0
          ) as unread_count,
          u.is_online
        FROM users u
        CROSS JOIN users cu
        WHERE cu.email = ? AND u.email != ?
        ORDER BY last_message_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $current_user, $current_user);
$stmt->execute();
$result = $stmt->get_result();

$contacts = [];
$colors = ['blue', 'green', 'purple', 'orange', 'red', 'indigo', 'pink', 'teal'];

while ($row = $result->fetch_assoc()) {
  // Get initials from email or name
  $name = $row['name'] ?: explode('@', $row['email'])[0];
  $nameParts = explode(' ', ucwords(str_replace(['.', '_'], ' ', $name)));
  $initials = '';
  
  if (count($nameParts) >= 2) {
    $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
  } else {
    $initials = strtoupper(substr($name, 0, 2));
  }

  $contacts[] = [
    'id' => $row['id'],
    'name' => ucwords(str_replace(['.', '_'], ' ', $name)),
    'email' => $row['email'],
    'initials' => $initials,
    'color' => $colors[array_rand($colors)],
    'lastMessage' => $row['last_message'] ? substr($row['last_message'], 0, 50) : '',
    'time' => $row['last_message_time'],
    'unread' => (int)$row['unread_count'],
    'online' => (bool)$row['is_online']
  ];
}

echo json_encode(['contacts' => $contacts]);

$stmt->close();
$conn->close();
?>
