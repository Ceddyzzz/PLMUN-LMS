<?php
session_start();
include '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$data = json_decode(file_get_contents('php://input'), true);
$section_id = $data['section_id'] ?? 0;

if (!$section_id) {
    echo json_encode(['success' => false, 'message' => 'Section ID required']);
    exit();
}

// Check permissions
if ($role === 'teacher') {
    // Teachers can only delete their own sections
    $check_sql = "SELECT s.id FROM sections s 
                  JOIN teacher_sections ts ON s.id = ts.section_id 
                  WHERE s.id = ? AND ts.teacher_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $section_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'You can only delete your own sections']);
        $check_stmt->close();
        exit();
    }
    $check_stmt->close();
}

// Delete section
$delete_sql = "DELETE FROM sections WHERE id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("i", $section_id);

if ($delete_stmt->execute()) {
    // Also delete from teacher_sections
    $conn->query("DELETE FROM teacher_sections WHERE section_id = $section_id");
    
    echo json_encode(['success' => true, 'message' => 'Section deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete section']);
}

$delete_stmt->close();
$conn->close();
?>
