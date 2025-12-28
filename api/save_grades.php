<?php
session_start();
include 'includes/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in and has proper role
if (!isset($_SESSION['user']) || !in_array($_SESSION['role'], ['teacher', 'dean', 'program_chair', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['section_id']) || !isset($data['grades'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
    exit();
}

$section_id = $data['section_id'];
$grades = $data['grades'];
$teacher_id = $_SESSION['user_id'];

try {
    // Verify section exists and user has access
    $section_query = "SELECT * FROM sections WHERE id = ?";
    $section_stmt = $conn->prepare($section_query);
    $section_stmt->bind_param("i", $section_id);
    $section_stmt->execute();
    $section_result = $section_stmt->get_result();
    
    if ($section_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Section not found']);
        exit();
    }
    
    $section = $section_result->fetch_assoc();
    $section_stmt->close();
    
    // Check if this teacher is assigned to this section (if user is a teacher)
    if ($_SESSION['role'] === 'teacher' && $section['teacher_id'] != $teacher_id) {
        echo json_encode(['success' => false, 'message' => 'You are not assigned to this section']);
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($grades as $student_id => $grade) {
        // Skip empty grades
        if (empty(trim($grade))) {
            continue;
        }
        
        // Check if grade record exists
        $check_query = "SELECT id FROM grades WHERE student_id = ? AND section_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ii", $student_id, $section_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Update existing grade
            $update_query = "UPDATE grades SET grade = ?, updated_at = NOW() WHERE student_id = ? AND section_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("sii", $grade, $student_id, $section_id);
            
            if ($update_stmt->execute()) {
                $success_count++;
            } else {
                $error_count++;
            }
            $update_stmt->close();
        } else {
            // Insert new grade
            $insert_query = "INSERT INTO grades (student_id, section_id, teacher_id, grade, created_at, updated_at) 
                           VALUES (?, ?, ?, ?, NOW(), NOW())";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("iiis", $student_id, $section_id, $teacher_id, $grade);
            
            if ($insert_stmt->execute()) {
                $success_count++;
            } else {
                $error_count++;
            }
            $insert_stmt->close();
        }
        
        $check_stmt->close();
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully saved $success_count grade(s)",
        'success_count' => $success_count,
        'error_count' => $error_count
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
