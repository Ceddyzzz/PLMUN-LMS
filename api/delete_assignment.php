<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: ../login.php");
  exit();
}

include '../includes/db_connect.php';

// Get current user info
$current_user_email = $_SESSION['user'];
$stmt = $conn->prepare("SELECT id, email, name, role FROM users WHERE email = ?");
$stmt->bind_param("s", $current_user_email);
$stmt->execute();
$result = $stmt->get_result();
$current_user = $result->fetch_assoc();
$user_role = $current_user['role'];
$stmt->close();

// Only teachers can delete assignments
if ($user_role !== 'teacher') {
  header("Location: ../assignment.php");
  exit();
}

// Get assignment ID
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($assignment_id === 0) {
  header("Location: ../assignment.php");
  exit();
}

// Verify the assignment belongs to this teacher
$stmt = $conn->prepare("SELECT * FROM assignments WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $assignment_id, $current_user['id']);
$stmt->execute();
$result = $stmt->get_result();
$assignment = $result->fetch_assoc();
$stmt->close();

if (!$assignment) {
  // Assignment doesn't exist or doesn't belong to this teacher
  header("Location: ../assignment.php?error=unauthorized");
  exit();
}

// Delete related submissions first (to maintain referential integrity)
$stmt = $conn->prepare("DELETE FROM assignment_submissions WHERE assignment_id = ?");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$stmt->close();

// Delete the assignment
$stmt = $conn->prepare("DELETE FROM assignments WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $assignment_id, $current_user['id']);

if ($stmt->execute()) {
  $stmt->close();
  $conn->close();
  header("Location: ../assignment.php?deleted=1");
  exit();
} else {
  $stmt->close();
  $conn->close();
  header("Location: ../assignment.php?error=delete_failed");
  exit();
}
?>
