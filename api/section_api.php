<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$database = "lms_db";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['role'] ?? 'student';

// Define valid programs - ACT will also have 4 years for consistency
$valid_programs = ['BSCS', 'BSIT', 'ACT'];

switch ($action) {
    case 'get_section_options':
        // Return options for frontend dropdowns
        echo json_encode([
            'years' => [1, 2, 3, 4], // All programs have 1-4 years
            'programs' => $valid_programs,
            'sections' => range('A', 'Z') // Sections A to Z for each year
        ]);
        break;
        
    case 'create_section':
        if ($user_role !== 'teacher') {
            echo json_encode(['error' => 'Only teachers can create sections']);
            break;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $year = $data['year'] ?? '';
        $program = $data['program'] ?? '';
        $section_letter = $data['section_letter'] ?? '';
        
        // Validate inputs
        if (empty($year) || empty($program) || empty($section_letter)) {
            echo json_encode(['error' => 'All fields are required: Year, Program, and Section']);
            break;
        }
        
        // Validate year (1-4 for ALL programs)
        if (!is_numeric($year) || $year < 1 || $year > 4) {
            echo json_encode(['error' => "Year must be between 1 and 4"]);
            break;
        }
        
        // Validate program
        if (!in_array($program, $valid_programs)) {
            echo json_encode(['error' => 'Invalid program selected']);
            break;
        }
        
        // Validate section letter (A-Z)
        if (!preg_match('/^[A-Z]$/', $section_letter)) {
            echo json_encode(['error' => 'Section must be a single letter A-Z']);
            break;
        }
        
        // Generate section name in format: 1-BSCS-A
        $section_name = "{$year}-{$program}-{$section_letter}";
        
        // Check if section already exists
        $check_sql = "SELECT id FROM sections WHERE section_name = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $section_name);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            echo json_encode(['error' => 'Section already exists']);
            $check_stmt->close();
            break;
        }
        $check_stmt->close();
        
        // Prepare the insert statement
        $sql = "INSERT INTO sections (section_name, program, year_level, section_letter, created_at";
        $values = "VALUES (?, ?, ?, ?, NOW()";
        
        // Check if teacher_id column exists and add it if present
        $check = $conn->query("SHOW COLUMNS FROM sections LIKE 'teacher_id'");
        if ($check->num_rows > 0) {
            $sql .= ", teacher_id";
            $values .= ", ?";
        }
        
        $sql .= ") " . $values . ")";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            if ($check->num_rows > 0) {
                $stmt->bind_param("ssisi", $section_name, $program, $year, $section_letter, $user_id);
            } else {
                $stmt->bind_param("ssis", $section_name, $program, $year, $section_letter);
            }
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true, 
                    'section_id' => $conn->insert_id,
                    'section_name' => $section_name
                ]);
            } else {
                echo json_encode(['error' => 'Failed to create section: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['error' => 'Failed to prepare statement: ' . $conn->error]);
        }
        break;
        
    case 'get_sections':
        // Get all sections, optionally filtered
        $program_filter = $_GET['program'] ?? '';
        $year_filter = $_GET['year'] ?? '';
        $show_all = $_GET['show_all'] ?? false;
        
        // Build query based on user role
        if ($user_role === 'teacher' && !$show_all) {
            // Teachers see only their sections if teacher_id column exists
            $check = $conn->query("SHOW COLUMNS FROM sections LIKE 'teacher_id'");
            if ($check->num_rows > 0) {
                $sql = "SELECT * FROM sections WHERE teacher_id = ?";
                $params = [$user_id];
                $types = "i";
            } else {
                $sql = "SELECT * FROM sections WHERE 1=1";
                $params = [];
                $types = "";
            }
        } else {
            // Admins or when show_all=true
            $sql = "SELECT * FROM sections WHERE 1=1";
            $params = [];
            $types = "";
        }
        
        // Add filters
        if ($program_filter) {
            $sql .= " AND program = ?";
            $params[] = $program_filter;
            $types .= "s";
        }
        
        if ($year_filter) {
            $sql .= " AND year_level = ?";
            $params[] = $year_filter;
            $types .= "i";
        }
        
        $sql .= " ORDER BY year_level, program, section_letter";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $sections = [];
            
            while ($row = $result->fetch_assoc()) {
                $sections[] = $row;
            }
            
            echo json_encode(['success' => true, 'sections' => $sections]);
            $stmt->close();
        } else {
            echo json_encode(['error' => 'Failed to fetch sections']);
        }
        break;
        
    case 'get_section_stats':
        // Get statistics about sections
        $sql = "SELECT 
                program,
                year_level,
                COUNT(*) as section_count,
                GROUP_CONCAT(section_letter ORDER BY section_letter) as sections
                FROM sections 
                GROUP BY program, year_level
                ORDER BY program, year_level";
        
        $result = $conn->query($sql);
        $stats = [];
        
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
        
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;
        
    case 'delete_section':
        if ($user_role !== 'teacher' && $user_role !== 'admin') {
            echo json_encode(['error' => 'Only teachers and admins can delete sections']);
            break;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $section_id = $data['section_id'] ?? 0;
        
        // Check if teacher owns this section (if not admin)
        if ($user_role === 'teacher') {
            $check_sql = "SELECT id FROM sections WHERE id = ?";
            $check = $conn->query("SHOW COLUMNS FROM sections LIKE 'teacher_id'");
            if ($check->num_rows > 0) {
                $check_sql .= " AND teacher_id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("ii", $section_id, $user_id);
            } else {
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("i", $section_id);
            }
            
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                echo json_encode(['error' => 'You can only delete your own sections']);
                $check_stmt->close();
                break;
            }
            $check_stmt->close();
        }
        
        $sql = "DELETE FROM sections WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $section_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Failed to delete section']);
            }
            $stmt->close();
        } else {
            echo json_encode(['error' => 'Failed to prepare statement']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}

$conn->close();
?>
