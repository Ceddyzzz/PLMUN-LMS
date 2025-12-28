<?php
session_start();
include 'includes/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in and has proper role
if (!isset($_SESSION['user']) || !in_array($_SESSION['role'], ['teacher', 'dean', 'program_chair', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get section_id from GET parameter
$section_id = $_GET['section_id'] ?? null;

if (!$section_id) {
    echo json_encode(['success' => false, 'message' => 'Section ID is required']);
    exit();
}

try {
    // First, get the section details
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
    if ($_SESSION['role'] === 'teacher' && $section['teacher_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'You are not assigned to this section']);
        exit();
    }
    
    // Get students for this section
    // Students are identified by matching course, year_level, and section
    $students_query = "
        SELECT 
            u.id,
            u.student_id,
            u.name,
            u.email,
            u.course,
            u.year_level,
            u.section,
            COALESCE(g.grade, '') as grade
        FROM users u
        LEFT JOIN grades g ON u.id = g.student_id AND g.section_id = ?
        WHERE u.role = 'student' 
        AND u.course = ? 
        AND u.year_level = ? 
        AND u.section = ?
        ORDER BY u.name ASC
    ";
    
    $students_stmt = $conn->prepare($students_query);
    $students_stmt->bind_param("isis", $section_id, $section['course'], $section['year_level'], $section['section']);
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();
    
    $students = [];
    while ($row = $students_result->fetch_assoc()) {
        $students[] = $row;
    }
    $students_stmt->close();
    
    echo json_encode([
        'success' => true,
        'students' => $students,
        'section' => $section
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
