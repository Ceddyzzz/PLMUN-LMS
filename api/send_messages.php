<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
  echo json_encode([
    'success' => false,
    'error' => 'Not authenticated',
    'debug' => 'User not logged in'
  ]);
  exit();
}

// Check if db_connect.php exists
if (!file_exists('../includes/db_connect.php')) {
  echo json_encode([
    'success' => false,
    'error' => 'Database connection file not found',
    'debug' => 'Check if includes/db_connect.php exists'
  ]);
  exit();
}

include '../includes/db_connect.php';

// Check database connection
if ($conn->connect_error) {
  echo json_encode([
    'success' => false,
    'error' => 'Database connection failed',
    'debug' => $conn->connect_error
  ]);
  exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['user_id']) || !isset($input['message'])) {
  echo json_encode([
    'success' => false,
    'error' => 'Invalid request',
    'debug' => 'Missing user_id or message in request'
  ]);
  exit();
}

$current_user_email = $_SESSION['user'];
$receiver_id = (int)$input['user_id'];
$message = trim($input['message']);

// Validate message not empty
if (empty($message)) {
  echo json_encode([
    'success' => false,
    'error' => 'Message cannot be empty'
  ]);
  exit();
}

// Get current user ID
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
if (!$stmt) {
  echo json_encode([
    'success' => false,
    'error' => 'Database query preparation failed',
    'debug' => $conn->error
  ]);
  exit();
}

$stmt->bind_param("s", $current_user_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  echo json_encode([
    'success' => false,
    'error' => 'User not found in database',
    'debug' => 'Email: ' . $current_user_email
  ]);
  exit();
}

$current_user = $result->fetch_assoc();
$sender_id = $current_user['id'];
$stmt->close();

// Check if messages table exists
$table_check = $conn->query("SHOW TABLES LIKE 'messages'");
if ($table_check->num_rows === 0) {
  echo json_encode([
    'success' => false,
    'error' => 'Messages table does not exist',
    'debug' => 'Run the database update script first'
  ]);
  exit();
}

// Insert message
$sql = "INSERT INTO messages (sender_id, receiver_id, message, created_at) 
        VALUES (?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode([
    'success' => false,
    'error' => 'Failed to prepare insert statement',
    'debug' => $conn->error
  ]);
  exit();
}

$stmt->bind_param("iis", $sender_id, $receiver_id, $message);

if ($stmt->execute()) {
  echo json_encode([
    'success' => true,
    'message_id' => $stmt->insert_id,
    'timestamp' => date('h:i A'),
    'debug' => 'Message sent successfully'
  ]);
} else {
  echo json_encode([
    'success' => false,
    'error' => 'Failed to send message',
    'debug' => $stmt->error
  ]);
}

$stmt->close();
$conn->close();
?>
