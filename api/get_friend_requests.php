<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
  echo json_encode(['error' => 'Not authenticated', 'requests' => []]);
  exit();
}

include '../includes/db_connect.php';

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();
$current_user_id = $current_user['id'];
$stmt->close();

// Get pending friend requests sent to current user
$sql = "SELECT fr.id, u.id as user_id, u.email, u.name
        FROM friend_requests fr
        JOIN users u ON fr.sender_id = u.id
        WHERE fr.receiver_id = ? AND fr.status = 'pending'
        ORDER BY fr.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
  $name = $row['name'] ?: explode('@', $row['email'])[0];
  $nameParts = explode(' ', ucwords(str_replace(['.', '_'], ' ', $name)));
  
  $initials = count($nameParts) >= 2 
    ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1))
    : strtoupper(substr($name, 0, 2));

  $requests[] = [
    'id' => $row['id'],
    'user_id' => $row['user_id'],
    'name' => ucwords(str_replace(['.', '_'], ' ', $name)),
    'email' => $row['email'],
    'initials' => $initials
  ];
}

echo json_encode(['success' => true, 'requests' => $requests]);

$stmt->close();
$conn->close();
?>
