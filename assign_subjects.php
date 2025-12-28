<?php
session_start();
include 'includes/db_connect.php';

// Check if user is logged in and is a dean
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'dean') {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['user'];

// Helper function to safely fetch all rows (compatible with and without mysqlnd)
function fetchAllRows($result) {
    $rows = [];
    if ($result && method_exists($result, 'fetch_all')) {
        try {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            // Fallback to manual fetching
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
    } elseif ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

// Get all subjects from CITCS data structure - FIXED
$subjects_query = "
    SELECT DISTINCT 
        subject_code,
        subject_name
    FROM subjects 
    WHERE department = 'CITCS'
    ORDER BY subject_code
";
$subjects_result = $conn->query($subjects_query);
if (!$subjects_result) {
    die("Error in subjects query: " . $conn->error);
}
$subjects = fetchAllRows($subjects_result);

// Get all teachers - FIXED
$teachers_query = "
    SELECT 
        u.id,
        u.name,
        u.email,
        u.department,
        COUNT(ts.id) as assignment_count
    FROM users u
    LEFT JOIN teacher_subjects ts ON u.id = ts.teacher_id
    WHERE u.role = 'teacher'
    GROUP BY u.id, u.name, u.email, u.department
    ORDER BY u.name ASC
";
$teachers_result = $conn->query($teachers_query);
if (!$teachers_result) {
    die("Error in teachers query: " . $conn->error);
}
$teachers = fetchAllRows($teachers_result);

// Get all programs (courses) from sections - FIXED
$programs_query = "SELECT DISTINCT course FROM sections WHERE course IS NOT NULL ORDER BY course";
$programs_result = $conn->query($programs_query);
if (!$programs_result) {
    die("Error in programs query: " . $conn->error);
}
$programs = fetchAllRows($programs_result);

// Get all current assignments - FIXED
$assignments_query = "
    SELECT 
        ts.id,
        ts.teacher_id,
        ts.subject_id,
        s.subject_code,
        s.subject_name,
        s.department,
        sec.course,
        sec.year_level,
        sec.section,
        sec.schedule_day,
        sec.start_time,
        sec.end_time,
        sec.room,
        sec.max_students,
        sec.current_enrollment,
        sec.status,
        u.name as teacher_name,
        u.email as teacher_email
    FROM teacher_subjects ts
    INNER JOIN subjects s ON ts.subject_id = s.id
    INNER JOIN users u ON ts.teacher_id = u.id
    LEFT JOIN sections sec ON ts.id = sec.teacher_subject_id
    WHERE s.department = 'CITCS'
    ORDER BY ts.created_at DESC
";
$assignments_result = $conn->query($assignments_query);
if (!$assignments_result) {
    die("Error in assignments query: " . $conn->error);
}
$assignments = fetchAllRows($assignments_result);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'assign') {
            $teacher_id = $_POST['teacher_id'];
            $course = $_POST['course'];
            $year_level = $_POST['year_level'];
            $section = $_POST['section'];
            $schedule_day = $_POST['schedule_day'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $room = $_POST['room'];
            $max_students = $_POST['max_students'] ?? 45;
            $status = $_POST['status'] ?? 'OPEN';
            $subject_code_display = $_POST['subject_code_display'];
            $subject_name = $_POST['subject_name'];
            
            // First, check if subject exists, if not insert it
            $subject_check = "SELECT id FROM subjects WHERE subject_code = ? AND department = 'CITCS'";
            $check_stmt = $conn->prepare($subject_check);
            $check_stmt->bind_param("s", $subject_code_display);
            $check_stmt->execute();
            $subject_result = $check_stmt->get_result();
            
            if ($subject_result->num_rows == 0) {
                // Insert new subject
                $insert_subject = "INSERT INTO subjects (subject_code, subject_name, department) VALUES (?, ?, 'CITCS')";
                $sub_stmt = $conn->prepare($insert_subject);
                $sub_stmt->bind_param("ss", $subject_code_display, $subject_name);
                if ($sub_stmt->execute()) {
                    $subject_id = $conn->insert_id;
                } else {
                    $_SESSION['error_message'] = "Error creating subject: " . $conn->error;
                    $sub_stmt->close();
                    $check_stmt->close();
                    header("Location: assign_subjects.php");
                    exit();
                }
                $sub_stmt->close();
            } else {
                $subject_row = $subject_result->fetch_assoc();
                $subject_id = $subject_row['id'];
            }
            $check_stmt->close();
            
            // Check if this teacher is already assigned this subject
            $check_assignment = "SELECT id FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ?";
            $check_assign_stmt = $conn->prepare($check_assignment);
            $check_assign_stmt->bind_param("ii", $teacher_id, $subject_id);
            $check_assign_stmt->execute();
            $assign_result = $check_assign_stmt->get_result();
            
            if ($assign_result->num_rows > 0) {
                $_SESSION['error_message'] = "This teacher is already assigned this subject!";
                $check_assign_stmt->close();
                header("Location: assign_subjects.php");
                exit();
            }
            $check_assign_stmt->close();
            
            // Insert into teacher_subjects
            $insert_ts_query = "INSERT INTO teacher_subjects (teacher_id, subject_id, created_at) VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($insert_ts_query);
            $stmt->bind_param("ii", $teacher_id, $subject_id);
            
            if ($stmt->execute()) {
                $teacher_subject_id = $conn->insert_id;
                
                // Create section information
                $insert_section = "
                    INSERT INTO sections 
                    (teacher_subject_id, course, year_level, section, schedule_day, start_time, end_time, room, max_students, current_enrollment, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, NOW())
                ";
                $sec_stmt = $conn->prepare($insert_section);
                $sec_stmt->bind_param("isssssssis", $teacher_subject_id, $course, $year_level, $section, $schedule_day, $start_time, $end_time, $room, $max_students, $status);
                
                if ($sec_stmt->execute()) {
                    $_SESSION['success_message'] = "Subject successfully assigned to teacher!";
                } else {
                    // Rollback teacher_subjects if section creation fails
                    $rollback = "DELETE FROM teacher_subjects WHERE id = ?";
                    $rollback_stmt = $conn->prepare($rollback);
                    $rollback_stmt->bind_param("i", $teacher_subject_id);
                    $rollback_stmt->execute();
                    $rollback_stmt->close();
                    
                    $_SESSION['error_message'] = "Error creating section: " . $conn->error;
                }
                $sec_stmt->close();
            } else {
                $_SESSION['error_message'] = "Error assigning subject: " . $conn->error;
            }
            $stmt->close();
            
            header("Location: assign_subjects.php");
            exit();
            
        } elseif ($_POST['action'] === 'remove') {
            $assignment_id = $_POST['assignment_id'];
            
            // First, delete the section
            $delete_section = "DELETE FROM sections WHERE teacher_subject_id = ?";
            $sec_stmt = $conn->prepare($delete_section);
            $sec_stmt->bind_param("i", $assignment_id);
            $sec_stmt->execute();
            $sec_stmt->close();
            
            // Then delete the teacher_subject assignment
            $delete_query = "DELETE FROM teacher_subjects WHERE id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $assignment_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Assignment removed successfully!";
            } else {
                $_SESSION['error_message'] = "Error removing assignment: " . $conn->error;
            }
            $stmt->close();
            
            header("Location: assign_subjects.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Subjects to Teachers | PLMUN LMS - CITCS</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .list-item {
            transition: all 0.3s ease;
        }
        .list-item:hover {
            transform: translateX(5px);
        }
        .list-item.selected {
            background: #e0e7ff;
            border-left: 4px solid #6366f1;
        }
        .status-open {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-closed {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .program-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .program-badge.act { background: #a78bfa; color: white; }
        .program-badge.bscs { background: #3b82f6; color: white; }
        .program-badge.bsit { background: #10b981; color: white; }
        .program-badge.gened { background: #f59e0b; color: white; }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-lg p-6 mb-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2">
                        <i class="fas fa-book mr-2"></i>Assign Subjects to Teachers
                    </h1>
                    <p class="text-blue-100">College of Information Technology and Computer Studies (CITCS)</p>
                    <p class="text-sm text-blue-200 mt-1">Welcome, <?php echo htmlspecialchars($user_name); ?> (Dean)</p>
                </div>
                <a href="dashboard_dean.php" class="bg-white text-blue-600 px-6 py-2 rounded-lg font-semibold hover:bg-blue-50 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-2xl mr-3"></i>
                <p><?php echo $_SESSION['success_message']; ?></p>
            </div>
        </div>
        <?php unset($_SESSION['success_message']); endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
                <p><?php echo $_SESSION['error_message']; ?></p>
            </div>
        </div>
        <?php unset($_SESSION['error_message']); endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Subjects List -->
            <div class="bg-white rounded-lg shadow-lg">
                <div class="px-6 py-4 border-b bg-gradient-to-r from-blue-50 to-indigo-50">
                    <h2 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-book text-blue-600 mr-2"></i>CITCS Subjects
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">Select a subject to assign</p>
                    <p class="text-xs text-gray-500">Total: <?php echo count($subjects); ?> subjects</p>
                </div>
                <div class="p-6">
                    <input type="text" id="subjectSearch" class="w-full px-4 py-2 border border-gray-300 rounded-lg mb-4 focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Search subjects...">
                    <div class="max-h-96 overflow-y-auto" id="subjectsList">
                        <?php if (empty($subjects)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-book-open text-4xl mb-3"></i>
                            <p>No subjects found</p>
                            <p class="text-xs mt-2">Add subjects in database first</p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($subjects as $subject): ?>
                            <div class="list-item subject-item bg-gray-50 p-4 mb-3 rounded-lg cursor-pointer border-l-4 border-transparent hover:bg-gray-100" 
                                 data-code="<?php echo htmlspecialchars($subject['subject_code']); ?>"
                                 data-name="<?php echo htmlspecialchars($subject['subject_name']); ?>">
                                <div class="font-bold text-indigo-600 text-sm"><?php echo htmlspecialchars($subject['subject_code']); ?></div>
                                <div class="text-gray-700 text-sm mt-1"><?php echo htmlspecialchars($subject['subject_name']); ?></div>
                                <div class="text-xs text-gray-500 mt-2">
                                    <i class="fas fa-building mr-1"></i>CITCS
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Teachers List -->
            <div class="bg-white rounded-lg shadow-lg">
                <div class="px-6 py-4 border-b bg-gradient-to-r from-green-50 to-emerald-50">
                    <h2 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-users text-green-600 mr-2"></i>Available Teachers
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">Select a teacher to assign</p>
                    <p class="text-xs text-gray-500">Total: <?php echo count($teachers); ?> teachers</p>
                </div>
                <div class="p-6">
                    <input type="text" id="teacherSearch" class="w-full px-4 py-2 border border-gray-300 rounded-lg mb-4 focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="Search teachers...">
                    <div class="max-h-96 overflow-y-auto" id="teachersList">
                        <?php if (empty($teachers)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-user-times text-4xl mb-3"></i>
                            <p>No teachers found</p>
                            <p class="text-xs mt-2">Add teachers in system first</p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($teachers as $teacher): ?>
                            <div class="list-item teacher-item bg-gray-50 p-4 mb-3 rounded-lg cursor-pointer border-l-4 border-transparent hover:bg-gray-100"
                                 data-id="<?php echo $teacher['id']; ?>"
                                 data-name="<?php echo htmlspecialchars($teacher['name']); ?>"
                                 data-email="<?php echo htmlspecialchars($teacher['email']); ?>"
                                 data-department="<?php echo htmlspecialchars($teacher['department'] ?? 'CITCS'); ?>">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-bold text-gray-800 text-sm"><?php echo htmlspecialchars($teacher['name']); ?></div>
                                        <div class="text-gray-500 text-xs mt-1"><?php echo htmlspecialchars($teacher['email']); ?></div>
                                    </div>
                                    <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs font-semibold">
                                        <?php echo $teacher['assignment_count']; ?> assigned
                                    </span>
                                </div>
                                <?php if (!empty($teacher['department'])): ?>
                                <div class="text-gray-600 text-xs mt-2">
                                    <i class="fas fa-building mr-1"></i><?php echo htmlspecialchars($teacher['department']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Programs/Courses -->
            <div class="bg-white rounded-lg shadow-lg">
                <div class="px-6 py-4 border-b bg-gradient-to-r from-purple-50 to-pink-50">
                    <h2 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-graduation-cap text-purple-600 mr-2"></i>CITCS Programs
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">Available programs</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-3">
                        <!-- ACT Program -->
                        <div class="border rounded-lg p-4 bg-gradient-to-r from-purple-50 to-indigo-50 hover:shadow-md transition">
                            <div class="flex items-center mb-2">
                                <div class="bg-purple-100 p-2 rounded-lg mr-3">
                                    <i class="fas fa-laptop-code text-purple-600"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800">ACT</h3>
                                    <p class="text-xs text-gray-600">Associate in Computer Technology</p>
                                </div>
                            </div>
                            <div class="text-sm text-gray-700">
                                <div class="flex items-center mb-1">
                                    <i class="fas fa-users text-xs mr-2"></i>
                                    <span>Year Levels: 1-2</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-clock text-xs mr-2"></i>
                                    <span>2-year program</span>
                                </div>
                            </div>
                        </div>

                        <!-- BSCS Program -->
                        <div class="border rounded-lg p-4 bg-gradient-to-r from-blue-50 to-cyan-50 hover:shadow-md transition">
                            <div class="flex items-center mb-2">
                                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                    <i class="fas fa-code text-blue-600"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800">BSCS</h3>
                                    <p class="text-xs text-gray-600">BS Computer Science</p>
                                </div>
                            </div>
                            <div class="text-sm text-gray-700">
                                <div class="flex items-center mb-1">
                                    <i class="fas fa-users text-xs mr-2"></i>
                                    <span>Year Levels: 1-4</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-clock text-xs mr-2"></i>
                                    <span>4-year program</span>
                                </div>
                            </div>
                        </div>

                        <!-- BSIT Program -->
                        <div class="border rounded-lg p-4 bg-gradient-to-r from-green-50 to-emerald-50 hover:shadow-md transition">
                            <div class="flex items-center mb-2">
                                <div class="bg-green-100 p-2 rounded-lg mr-3">
                                    <i class="fas fa-server text-green-600"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800">BSIT</h3>
                                    <p class="text-xs text-gray-600">BS Information Technology</p>
                                </div>
                            </div>
                            <div class="text-sm text-gray-700">
                                <div class="flex items-center mb-1">
                                    <i class="fas fa-users text-xs mr-2"></i>
                                    <span>Year Levels: 1-4</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-clock text-xs mr-2"></i>
                                    <span>4-year program</span>
                                </div>
                            </div>
                        </div>

                        <!-- Assignment Guide -->
                        <div class="border rounded-lg p-4 bg-gradient-to-r from-gray-50 to-slate-50 col-span-2 hover:shadow-md transition">
                            <h4 class="font-bold text-gray-800 mb-2 flex items-center">
                                <i class="fas fa-info-circle text-blue-500 mr-2"></i>Assignment Instructions
                            </h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li class="flex items-center">
                                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                    <span>1. Select a subject from the left panel</span>
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                    <span>2. Choose a teacher from the middle panel</span>
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                    <span>3. Fill in program, section, and schedule details</span>
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                    <span>4. Click "Assign Subject to Teacher"</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignment Form -->
        <div class="bg-white rounded-lg shadow-lg mb-8">
            <div class="px-6 py-4 border-b bg-gradient-to-r from-indigo-50 to-blue-50">
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-plus-circle text-indigo-600 mr-2"></i>Create Subject Assignment
                </h2>
                <p class="text-sm text-gray-600 mt-1">Fill in all required details to assign subject to teacher</p>
                <div class="mt-2 text-xs text-gray-500">
                    <span id="formStatus">Select a subject and teacher first</span>
                </div>
            </div>
            <div class="p-6">
                <form method="POST" id="assignmentForm">
                    <input type="hidden" name="action" value="assign">
                    <input type="hidden" name="subject_id" id="selectedSubjectId">
                    <input type="hidden" name="subject_code_display" id="subjectCodeDisplay">
                    <input type="hidden" name="subject_name" id="subjectNameDisplay">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Selected Subject & Teacher Display -->
                        <div class="md:col-span-2 lg:col-span-3">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                                            <i class="fas fa-book text-blue-500 mr-1"></i>Selected Subject
                                        </label>
                                        <div id="selectedSubjectDisplay" class="text-gray-600 bg-white p-3 rounded border">
                                            No subject selected. Click a subject from the left panel.
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                                            <i class="fas fa-user text-green-500 mr-1"></i>Selected Teacher
                                        </label>
                                        <div id="selectedTeacherDisplay" class="text-gray-600 bg-white p-3 rounded border">
                                            No teacher selected. Click a teacher from the middle panel.
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="teacher_id" id="selectedTeacherId">
                            </div>
                        </div>

                        <!-- Course/Program -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-graduation-cap text-purple-500 mr-1"></i>Program *
                            </label>
                            <select name="course" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">Select Program</option>
                                <option value="ACT">ACT - Associate in Computer Technology</option>
                                <option value="BSCS">BSCS - BS Computer Science</option>
                                <option value="BSIT">BSIT - BS Information Technology</option>
                            </select>
                        </div>

                        <!-- Year Level -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-calendar-alt text-blue-500 mr-1"></i>Year Level *
                            </label>
                            <select name="year_level" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">Select Year</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>

                        <!-- Section -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-users text-green-500 mr-1"></i>Section *
                            </label>
                            <select name="section" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">Select Section</option>
                                <option value="A">Section A</option>
                                <option value="B">Section B</option>
                                <option value="C">Section C</option>
                                <option value="D">Section D</option>
                                <option value="E">Section E</option>
                                <option value="F">Section F</option>
                                <option value="G">Section G</option>
                                <option value="H">Section H</option>
                                <option value="I">Section I</option>
                                <option value="J">Section J</option>
                                <option value="K">Section K</option>
                                <option value="L">Section L</option>
                                <option value="M">Section M</option>
                            </select>
                        </div>

                        <!-- Schedule Day -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-calendar-day text-red-500 mr-1"></i>Schedule Day *
                            </label>
                            <select name="schedule_day" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">Select Day</option>
                                <option value="M">Monday</option>
                                <option value="T">Tuesday</option>
                                <option value="W">Wednesday</option>
                                <option value="TH">Thursday</option>
                                <option value="F">Friday</option>
                                <option value="S">Saturday</option>
                                <option value="MTH">Monday & Thursday</option>
                                <option value="TF">Tuesday & Friday</option>
                                <option value="WS">Wednesday & Saturday</option>
                                <option value="MWF">Monday, Wednesday, Friday</option>
                            </select>
                        </div>

                        <!-- Start Time -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-clock text-yellow-500 mr-1"></i>Start Time *
                            </label>
                            <input type="time" name="start_time" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   value="08:00">
                        </div>

                        <!-- End Time -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-clock text-yellow-500 mr-1"></i>End Time *
                            </label>
                            <input type="time" name="end_time" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   value="10:00">
                        </div>

                        <!-- Room -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-door-closed text-gray-500 mr-1"></i>Room
                            </label>
                            <select name="room" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">Select Room</option>
                                <option value="CITCS-101">CITCS Room 101</option>
                                <option value="CITCS-102">CITCS Room 102</option>
                                <option value="CITCS-103">CITCS Room 103</option>
                                <option value="LAB-1">Computer Lab 1</option>
                                <option value="LAB-2">Computer Lab 2</option>
                                <option value="LAB-3">Computer Lab 3</option>
                                <option value="LECTURE-1">Lecture Hall 1</option>
                                <option value="LECTURE-2">Lecture Hall 2</option>
                                <option value="ONLINE">Online Class</option>
                            </select>
                        </div>

                        <!-- Max Students -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-user-friends text-indigo-500 mr-1"></i>Maximum Students
                            </label>
                            <input type="number" name="max_students" value="45" min="1" max="100"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-toggle-on text-green-500 mr-1"></i>Status
                            </label>
                            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="OPEN">OPEN - Available for enrollment</option>
                                <option value="CLOSED">CLOSED - Not available</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" id="assignBtn" disabled
                                class="w-full bg-gradient-to-r from-indigo-600 to-blue-600 text-white py-3 rounded-lg font-semibold hover:from-indigo-700 hover:to-blue-700 transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-plus-circle mr-2"></i>Assign Subject to Teacher
                        </button>
                        <p class="text-xs text-gray-500 text-center mt-2">
                            All fields marked with * are required. Subject and teacher must be selected first.
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <!-- Current Assignments Table -->
        <div class="bg-white rounded-lg shadow-lg">
            <div class="px-6 py-4 border-b bg-gradient-to-r from-gray-50 to-slate-50">
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-list text-gray-600 mr-2"></i>Current Assignments - CITCS
                </h2>
                <p class="text-sm text-gray-600 mt-1">All assigned subjects in College of Information Technology and Computer Studies</p>
                <p class="text-xs text-gray-500">Total: <?php echo count($assignments); ?> assignments</p>
            </div>
            <div class="overflow-x-auto">
                <?php if (empty($assignments)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-3"></i>
                    <p class="text-lg font-semibold mb-2">No Assignments Yet</p>
                    <p>Create your first assignment using the form above.</p>
                </div>
                <?php else: ?>
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Subject Details</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Teacher</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Section</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Schedule</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Capacity</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($assignments as $assignment): 
                            $program_class = strtolower($assignment['course'] ?? 'gened');
                        ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="font-bold text-indigo-600 text-sm"><?php echo htmlspecialchars($assignment['subject_code']); ?></div>
                                <div class="text-gray-600 text-xs"><?php echo htmlspecialchars($assignment['subject_name']); ?></div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-building mr-1"></i><?php echo htmlspecialchars($assignment['department'] ?? 'CITCS'); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($assignment['teacher_name']); ?></div>
                                <div class="text-gray-500 text-xs"><?php echo htmlspecialchars($assignment['teacher_email']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <span class="program-badge <?php echo $program_class; ?> mr-2">
                                        <?php echo htmlspecialchars($assignment['course'] ?? 'GENED'); ?>
                                    </span>
                                    <span class="text-sm font-medium">
                                        <?php echo htmlspecialchars($assignment['year_level'] ?? ''); ?><?php echo htmlspecialchars($assignment['section'] ?? ''); ?>
                                    </span>
                                </div>
                                <?php if (!empty($assignment['room'])): ?>
                                <div class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-door-open mr-1"></i><?php echo htmlspecialchars($assignment['room']); ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?php if (!empty($assignment['schedule_day'])): ?>
                                    <div class="font-medium"><?php echo htmlspecialchars($assignment['schedule_day']); ?></div>
                                    <?php if (!empty($assignment['start_time'])): ?>
                                    <div class="text-xs text-gray-500">
                                        <?php echo date('g:i A', strtotime($assignment['start_time'])); ?> - 
                                        <?php echo date('g:i A', strtotime($assignment['end_time'])); ?>
                                    </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-gray-400 italic">Not scheduled</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php 
                                $current = $assignment['current_enrollment'] ?? 0;
                                $max = $assignment['max_students'] ?? 45;
                                $percentage = $max > 0 ? round(($current / $max) * 100) : 0;
                                $color = $percentage >= 90 ? 'bg-red-500' : ($percentage >= 70 ? 'bg-yellow-500' : 'bg-green-500');
                                ?>
                                <div class="font-semibold"><?php echo $current; ?>/<?php echo $max; ?></div>
                                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                    <div class="<?php echo $color; ?> h-2 rounded-full" style="width: <?php echo min($percentage, 100); ?>%"></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1"><?php echo $percentage; ?>% full</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php $status = $assignment['status'] ?? 'OPEN'; ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $status == 'OPEN' ? 'status-open' : 'status-closed'; ?>">
                                    <?php echo htmlspecialchars($status); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <form method="POST" onsubmit="return confirm('Are you sure you want to remove this assignment?\n\nThis will delete the section and unassign the teacher from this subject.');" style="display: inline;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded text-sm font-semibold transition flex items-center mx-auto">
                                        <i class="fas fa-trash-alt mr-2"></i>Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        let selectedSubject = null;
        let selectedTeacher = null;

        // Subject selection
        document.querySelectorAll('.subject-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.subject-item').forEach(el => {
                    el.classList.remove('selected');
                    el.style.borderLeftColor = 'transparent';
                });
                this.classList.add('selected');
                this.style.borderLeftColor = '#6366f1';
                
                selectedSubject = {
                    code: this.dataset.code,
                    name: this.dataset.name
                };
                
                document.getElementById('selectedSubjectDisplay').innerHTML = 
                    `<div class="font-bold text-indigo-600">${selectedSubject.code}</div>
                     <div class="text-gray-700 text-sm">${selectedSubject.name}</div>`;
                document.getElementById('subjectCodeDisplay').value = selectedSubject.code;
                document.getElementById('subjectNameDisplay').value = selectedSubject.name;
                document.getElementById('formStatus').textContent = 'Subject selected. Now choose a teacher.';
                document.getElementById('formStatus').className = 'text-blue-600';
                checkFormReady();
                updateSubjectCode();
            });
        });

        // Teacher selection
        document.querySelectorAll('.teacher-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.teacher-item').forEach(el => {
                    el.classList.remove('selected');
                    el.style.borderLeftColor = 'transparent';
                });
                this.classList.add('selected');
                this.style.borderLeftColor = '#6366f1';
                
                selectedTeacher = {
                    id: this.dataset.id,
                    name: this.dataset.name,
                    email: this.dataset.email,
                    department: this.dataset.department
                };
                
                document.getElementById('selectedTeacherDisplay').innerHTML = 
                    `<div class="font-bold text-gray-800">${selectedTeacher.name}</div>
                     <div class="text-gray-500 text-sm">${selectedTeacher.email}</div>
                     <div class="text-gray-600 text-xs mt-1">
                        <i class="fas fa-building mr-1"></i>${selectedTeacher.department}
                     </div>`;
                document.getElementById('selectedTeacherId').value = selectedTeacher.id;
                
                if (selectedSubject) {
                    document.getElementById('formStatus').textContent = 'Ready to assign! Fill in the remaining details.';
                    document.getElementById('formStatus').className = 'text-green-600';
                } else {
                    document.getElementById('formStatus').textContent = 'Teacher selected. Now choose a subject.';
                    document.getElementById('formStatus').className = 'text-blue-600';
                }
                
                checkFormReady();
            });
        });

        // Search subjects
        document.getElementById('subjectSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            let visibleCount = 0;
            
            document.querySelectorAll('.subject-item').forEach(item => {
                const code = item.dataset.code.toLowerCase();
                const name = item.dataset.name.toLowerCase();
                if (code.includes(searchTerm) || name.includes(searchTerm)) {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Show message if no results
            const container = document.getElementById('subjectsList');
            if (visibleCount === 0 && searchTerm.length > 0) {
                if (!container.querySelector('.no-results')) {
                    const msg = document.createElement('div');
                    msg.className = 'no-results text-center py-4 text-gray-500';
                    msg.innerHTML = `<i class="fas fa-search mb-2"></i><p>No subjects match "${searchTerm}"</p>`;
                    container.appendChild(msg);
                }
            } else {
                const msg = container.querySelector('.no-results');
                if (msg) msg.remove();
            }
        });

        // Search teachers
        document.getElementById('teacherSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            let visibleCount = 0;
            
            document.querySelectorAll('.teacher-item').forEach(item => {
                const name = item.dataset.name.toLowerCase();
                const email = item.dataset.email.toLowerCase();
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Show message if no results
            const container = document.getElementById('teachersList');
            if (visibleCount === 0 && searchTerm.length > 0) {
                if (!container.querySelector('.no-results')) {
                    const msg = document.createElement('div');
                    msg.className = 'no-results text-center py-4 text-gray-500';
                    msg.innerHTML = `<i class="fas fa-search mb-2"></i><p>No teachers match "${searchTerm}"</p>`;
                    container.appendChild(msg);
                }
            } else {
                const msg = container.querySelector('.no-results');
                if (msg) msg.remove();
            }
        });

        // Check if form is ready
        function checkFormReady() {
            const assignBtn = document.getElementById('assignBtn');
            const requiredFields = document.querySelectorAll('[required]');
            let allFilled = true;
            
            // Check if subject and teacher are selected
            if (!selectedSubject || !selectedTeacher) {
                allFilled = false;
            } else {
                // Check all required form fields
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        allFilled = false;
                    }
                });
            }
            
            assignBtn.disabled = !allFilled;
        }

        // Auto-update form status when fields change
        document.querySelectorAll('select, input').forEach(field => {
            field.addEventListener('change', checkFormReady);
            if (field.type === 'text' || field.type === 'time' || field.type === 'number') {
                field.addEventListener('input', checkFormReady);
            }
        });

        // Auto-generate subject code based on selections
        function updateSubjectCode() {
            if (selectedSubject) {
                const courseSelect = document.querySelector('select[name="course"]');
                const yearSelect = document.querySelector('select[name="year_level"]');
                const sectionSelect = document.querySelector('select[name="section"]');
                
                let newCode = selectedSubject.code;
                
                if (courseSelect && courseSelect.value && yearSelect && yearSelect.value && sectionSelect && sectionSelect.value) {
                    const course = courseSelect.value;
                    const year = yearSelect.value;
                    const section = sectionSelect.value;
                    
                    // Generate subject code like: GENED03-ACT1A
                    const baseCode = selectedSubject.code.split('-')[0];
                    newCode = `${baseCode}-${course}${year}${section}`;
                }
                
                document.getElementById('subjectCodeDisplay').value = newCode;
                document.getElementById('selectedSubjectDisplay').innerHTML = 
                    `<div class="font-bold text-indigo-600">${newCode}</div>
                     <div class="text-gray-700 text-sm">${selectedSubject.name}</div>`;
            }
        }
        
        // Initialize form state
        checkFormReady();
        
        // Add auto-update for subject code when program/year/section change
        const courseSelect = document.querySelector('select[name="course"]');
        const yearSelect = document.querySelector('select[name="year_level"]');
        const sectionSelect = document.querySelector('select[name="section"]');
        
        if (courseSelect) courseSelect.addEventListener('change', function() {
            updateSubjectCode();
            checkFormReady();
        });
        if (yearSelect) yearSelect.addEventListener('change', function() {
            updateSubjectCode();
            checkFormReady();
        });
        if (sectionSelect) sectionSelect.addEventListener('change', function() {
            updateSubjectCode();
            checkFormReady();
        });

        // Form submission validation
        document.getElementById('assignmentForm').addEventListener('submit', function(e) {
            if (!selectedSubject || !selectedTeacher) {
                e.preventDefault();
                alert('Please select both a subject and a teacher before submitting.');
                return false;
            }
            
            const startTime = document.querySelector('input[name="start_time"]').value;
            const endTime = document.querySelector('input[name="end_time"]').value;
            
            if (startTime && endTime) {
                const start = new Date('2000-01-01T' + startTime);
                const end = new Date('2000-01-01T' + endTime);
                
                if (end <= start) {
                    e.preventDefault();
                    alert('End time must be after start time.');
                    return false;
                }
                
                // Check if duration is reasonable (not too long)
                const duration = (end - start) / (1000 * 60 * 60); // in hours
                if (duration > 6) {
                    if (!confirm(`Class duration is ${duration} hours. Is this correct?`)) {
                        e.preventDefault();
                        return false;
                    }
                }
            }
            
            // Show loading state
            const submitBtn = document.getElementById('assignBtn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Assigning...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>
