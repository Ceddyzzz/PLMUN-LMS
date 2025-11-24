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

// Only teachers can grade
if ($user['role'] !== 'teacher') {
  die("Access denied: Only teachers can grade assignments");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $submission_id = (int)$_POST['submission_id'];
  $assignment_id = (int)$_POST['assignment_id'];
  $grade = (int)$_POST['grade'];
  $feedback = trim($_POST['feedback'] ?? '');
  $teacher_id = $user['id'];
  
  // Verify this submission belongs to this teacher's assignment
  $verify_stmt = $conn->prepare("SELECT s.id 
                                  FROM assignment_submissions s
                                  JOIN assignments a ON s.assignment_id = a.id
                                  WHERE s.id = ? AND a.id = ? AND a.teacher_id = ?");
  $verify_stmt->bind_param("iii", $submission_id, $assignment_id, $teacher_id);
  $verify_stmt->execute();
  $verify_result = $verify_stmt->get_result();
  
  if ($verify_result->num_rows === 0) {
    die("Access denied: You don't have permission to grade this submission");
  }
  $verify_stmt->close();
  
  // Update submission with grade
  $stmt = $conn->prepare("UPDATE assignment_submissions 
                          SET grade = ?, feedback = ?, graded_at = NOW() 
                          WHERE id = ?");
  $stmt->bind_param("isi", $grade, $feedback, $submission_id);
  
  if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: ../view_submissions.php?id=" . $assignment_id . "&success=graded");
    exit();
  } else {
    die("Error grading submission: " . $stmt->error);
  }
}

$conn->close();
header("Location: ../assignment.php");
exit();
?>
