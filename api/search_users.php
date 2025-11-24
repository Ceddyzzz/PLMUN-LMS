<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
  echo json_encode(['error' => 'Not authenticated']);
  exit();
}

include '../includes/db_connect.php';

$email = isset($_GET['email']) ? trim($_GET['email']) : '';

if (strlen($email) < 3) {
  echo json_encode(['users' => []]);
  exit();
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();
$current_user_id = $current_user['id'];
$stmt->close();

// Search users
$search_term = "%{$email}%";
$sql = "SELECT id, email, name FROM users 
        WHERE email LIKE ? AND id != ? 
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $search_term, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
  $name = $row['name'] ?: explode('@', $row['email'])[0];
  $nameParts = explode(' ', ucwords(str_replace(['.', '_'], ' ', $name)));
  
  $initials = count($nameParts) >= 2 
    ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1))
    : strtoupper(substr($name, 0, 2));

  // Check friendship status
  $check_friend = $conn->prepare("SELECT 1 FROM friendships 
                                   WHERE (user1_id = ? AND user2_id = ?) 
                                      OR (user1_id = ? AND user2_id = ?)");
  $check_friend->bind_param("iiii", $current_user_id, $row['id'], $row['id'], $current_user_id);
  $check_friend->execute();
  $is_friend = $check_friend->get_result()->num_rows > 0;
  $check_friend->close();

  // Check if request pending
  $check_request = $conn->prepare("SELECT 1 FROM friend_requests 
                                    WHERE ((sender_id = ? AND receiver_id = ?) 
                                       OR (sender_id = ? AND receiver_id = ?))
                                      AND status = 'pending'");
  $check_request->bind_param("iiii", $current_user_id, $row['id'], $row['id'], $current_user_id);
  $check_request->execute();
  $is_pending = $check_request->get_result()->num_rows > 0;
  $check_request->close();

  $status = $is_friend ? 'friends' : ($is_pending ? 'pending' : 'none');

  $users[] = [
    'id' => $row['id'],
    'name' => ucwords(str_replace(['.', '_'], ' ', $name)),
    'email' => $row['email'],
    'initials' => $initials,
    'status' => $status
  ];
}

echo json_encode(['success' => true, 'users' => $users]);

$stmt->close();
$conn->close();
?>
