<?php
session_start();

if (!isset($_SESSION['user'])) {
  header("Location: ../login.php");
  exit();
}

include '../includes/db_connect.php';

// Get current user and check if teacher
$stmt = $conn->prepare("SELECT id, role FROM users WHERE email = ?");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Only teachers can create assignments
if ($user['role'] !== 'teacher') {
  die("Access denied: Only teachers can create assignments");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title']);
  $description = trim($_POST['description']);
  $course = trim($_POST['course']);
  $points = (int)$_POST['points'];
  $due_date = $_POST['due_date'];
  $teacher_id = $user['id'];
  
  // Validate inputs
  if (empty($title) || empty($description) || empty($course) || empty($due_date)) {
    die("All fields are required");
  }
  
  // Insert assignment
  $stmt = $conn->prepare("INSERT INTO assignments (title, description, course, teacher_id, points, due_date) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("ssssis", $title, $description, $course, $teacher_id, $points, $due_date);
  
  if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: ../assignment.php?success=created");
    exit();
  } else {
    die("Error creating assignment: " . $conn->error);
  }
}

$conn->close();
header("Location: ../assignment.php");
exit();
?>
