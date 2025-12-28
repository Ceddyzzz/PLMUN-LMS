<?php
// Remove session_start() if included from dashboard.php
include 'includes/db_connect.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? $_SESSION['user'];

// Get teacher's sections - SINGLE LINE QUERY to avoid issues
$sections_query = "SELECT DISTINCT s.* FROM sections s WHERE s.adviser_id = ? OR s.id IN (SELECT section_id FROM teacher_subjects WHERE teacher_id = ?) ORDER BY s.year_level, s.course, s.section";

$sections_stmt = $conn->prepare($sections_query);
if ($sections_stmt === false) {
    die("SQL Error: " . $conn->error);
}

$sections_stmt->bind_param("ii", $user_id, $user_id);
$execute_result = $sections_stmt->execute();

if ($execute_result === false) {
    die("Execute Error: " . $sections_stmt->error);
}

$sections_result = $sections_stmt->get_result();
$sections = $sections_result->fetch_all(MYSQLI_ASSOC);
$total_sections = count($sections);

// Get teacher's subjects (teaching assignments)
$subjects_query = "SELECT ts.id as assignment_id, sub.subject_code, sub.subject_name, sub.units, s.section_code, s.course, s.year_level, s.section, ts.schedule, ts.room FROM teacher_subjects ts JOIN subjects sub ON ts.subject_id = sub.id JOIN sections s ON ts.section_id = s.id WHERE ts.teacher_id = ? ORDER BY s.year_level, s.course, sub.subject_code";
$subjects_stmt = $conn->prepare($subjects_query);
$subjects_stmt->bind_param("i", $user_id);
$subjects_stmt->execute();
$subjects_result = $subjects_stmt->get_result();
$teacher_subjects = $subjects_result->fetch_all(MYSQLI_ASSOC);
$total_subjects = count($teacher_subjects);

// SIMPLIFIED: Get total students across all teacher's sections
$total_students = 0;
if ($total_sections > 0) {
    // Get unique courses from teacher's sections
    $courses = [];
    foreach ($sections as $section) {
        $courses[] = $section['course'];
    }
    $unique_courses = array_unique($courses);
    
    // Count students in those courses (simple approach)
    if (!empty($unique_courses)) {
        // Create SQL-safe course list
        $course_list = "'" . implode("','", array_map([$conn, 'real_escape_string'], $unique_courses)) . "'";
        
        $student_count_query = "SELECT COUNT(*) as student_count FROM users WHERE role = 'student' AND course IN ($course_list)";
        
        $result = $conn->query($student_count_query);
        if ($result) {
            $row = $result->fetch_assoc();
            $total_students = $row['student_count'] ?? 0;
        }
    }
}

// Get total programs (distinct courses) from sections
$total_programs = 0;
$programs = [];
if ($total_sections > 0) {
    $programs = array_unique(array_column($sections, 'course'));
    $total_programs = count($programs);
}

// FIXED: Get teacher's quizzes with proper error handling
$teacher_quizzes = [];
$quiz_error = null;

try {
    // First, check if quizzes table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'quizzes'");
    
    if ($table_check && $table_check->num_rows > 0) {
        // Table exists, fetch quizzes
        $quiz_query = "
            SELECT q.*, s.section_code, sub.subject_name
            FROM quizzes q
            LEFT JOIN sections s ON q.section_id = s.id
            LEFT JOIN subjects sub ON q.subject_id = sub.id
            WHERE q.teacher_id = ?
            ORDER BY q.created_at DESC
        ";
        
        $quiz_stmt = $conn->prepare($quiz_query);
        
        if ($quiz_stmt === false) {
            throw new Exception("Quiz query preparation failed: " . $conn->error);
        }
        
        $quiz_stmt->bind_param("i", $user_id);
        
        if (!$quiz_stmt->execute()) {
            throw new Exception("Quiz query execution failed: " . $quiz_stmt->error);
        }
        
        $quiz_result = $quiz_stmt->get_result();
        
        if ($quiz_result === false) {
            throw new Exception("Failed to get quiz result: " . $quiz_stmt->error);
        }
        
        if (method_exists($quiz_result, 'fetch_all')) {
            $teacher_quizzes = $quiz_result->fetch_all(MYSQLI_ASSOC);
        } else {
            $teacher_quizzes = [];
            while ($row = $quiz_result->fetch_assoc()) {
                $teacher_quizzes[] = $row;
            }
        }
        
        $quiz_stmt->close();
    } else {
        // Table doesn't exist, show appropriate message
        $teacher_quizzes = []; // Empty array, not an error
    }
} catch (Exception $e) {
    // Log error but don't crash the page
    error_log("Quiz loading error: " . $e->getMessage());
    $teacher_quizzes = []; // Return empty array on error
    $quiz_error = "No quizzes available yet"; // User-friendly message
}

$total_quizzes = count($teacher_quizzes);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard | PLMUN LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .quiz-status-published {
            background-color: #D1FAE5;
            color: #065F46;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .quiz-status-draft {
            background-color: #FEF3C7;
            color: #92400E;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #6B7280;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #D1D5DB;
        }
        /* Fix for Create Quiz button hover */
        .quick-action-quiz {
            position: relative;
            background-color: #8B4513 !important; /* Brown color */
            color: white !important;
        }
        .quick-action-quiz:hover {
            background-color: #A0522D !important; /* Darker brown on hover */
        }
        /* Add a subtle indicator for current page if needed */
        .quick-action-quiz.current {
            box-shadow: 0 0 0 3px rgba(139, 69, 19, 0.3);
        }
        /* Fix for stat cards to ensure consistent styling */
        .stat-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        .stat-icon {
            padding: 0.75rem;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow-lg p-8 mb-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2">Welcome, <?php echo htmlspecialchars($user_name); ?>! 👨‍🏫</h1>
                    <p class="text-blue-100">Ready to inspire minds today?</p>
                </div>
                <div class="text-right">
                    <div class="text-5xl mb-2">📚</div>
                    <p class="text-sm text-blue-200"><?php echo date('l, F d, Y'); ?></p>
                </div>
            </div>
        </div>

        <!-- Stats Cards - FIXED with consistent styling -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Sections Card -->
            <div class="stat-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="stat-icon bg-blue-100">
                        <i class="fas fa-users text-blue-600 text-2xl"></i>
                    </div>
                    <span class="text-xs text-gray-500">Handled</span>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $total_sections; ?></p>
                <p class="text-sm text-gray-600 mt-1">Total Sections</p>
            </div>

            <!-- Programs Card -->
            <div class="stat-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="stat-icon bg-green-100">
                        <i class="fas fa-graduation-cap text-green-600 text-2xl"></i>
                    </div>
                    <span class="text-xs text-gray-500">Programs</span>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $total_programs; ?></p>
                <p class="text-sm text-gray-600 mt-1">Programs Handled</p>
                <div class="mt-3">
                    <?php if ($total_programs > 0): ?>
                        <div class="text-sm text-gray-700">
                            <i class="fas fa-check-circle text-green-500 mr-1"></i>
                            <?php echo implode(', ', $programs); ?>
                        </div>
                    <?php else: ?>
                        <span class="text-gray-500 text-sm">No programs</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Students Card -->
            <div class="stat-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="stat-icon bg-purple-100">
                        <i class="fas fa-user-graduate text-purple-600 text-2xl"></i>
                    </div>
                    <span class="text-xs text-gray-500">Students</span>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $total_students; ?></p>
                <p class="text-sm text-gray-600 mt-1">Total Students</p>
            </div>

            <!-- Quizzes Card (FIXED - now has proper background) -->
            <div class="stat-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="stat-icon bg-pink-100">
                        <i class="fas fa-question-circle text-pink-600 text-2xl"></i>
                    </div>
                    <span class="text-xs text-gray-500">Quizzes</span>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $total_quizzes; ?></p>
                <p class="text-sm text-gray-600 mt-1">Total Quizzes</p>
                <?php if ($quiz_error): ?>
                    <div class="mt-3 text-xs text-orange-600">
                        <i class="fas fa-info-circle mr-1"></i> <?php echo $quiz_error; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- My Sections -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-users text-blue-500 mr-2"></i>My Sections
                            <span class="text-sm font-normal text-gray-600 ml-2">
                                (<?php echo $total_sections; ?> section<?php echo $total_sections != 1 ? 's' : ''; ?>)
                            </span>
                        </h2>
                    </div>
                </div>
                <div class="p-6">
                    <?php if (empty($sections)): ?>
                        <p class="text-gray-500 text-center py-4">No sections assigned yet.</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($sections as $section): ?>
                                <?php
                                // Check if teacher is adviser for this section
                                $is_adviser = ($section['adviser_id'] == $user_id);
                                
                                // Check if teacher teaches subjects in this section
                                $teaches_query = "SELECT COUNT(*) as count FROM teacher_subjects WHERE teacher_id = ? AND section_id = ?";
                                $teaches_stmt = $conn->prepare($teaches_query);
                                $teaches_stmt->bind_param("ii", $user_id, $section['id']);
                                $teaches_stmt->execute();
                                $teaches_result = $teaches_stmt->get_result();
                                $teaches_count = $teaches_result->fetch_assoc()['count'] ?? 0;
                                $teaches_stmt->close();
                                ?>
                                
                                <div class="border rounded-lg p-4 hover:bg-blue-50 transition">
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="font-bold text-lg text-gray-800">
                                            <?php echo htmlspecialchars($section['section_code']); ?>
                                            <?php if ($is_adviser): ?>
                                                <span class="text-xs text-blue-600 ml-2">★</span>
                                            <?php endif; ?>
                                        </h3>
                                        <div class="flex items-center space-x-2">
                                            <!-- Show role badges -->
                                            <?php if ($is_adviser): ?>
                                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">
                                                    <i class="fas fa-user-tie mr-1"></i>Adviser
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($teaches_count > 0): ?>
                                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">
                                                    <i class="fas fa-chalkboard-teacher mr-1"></i>Teacher
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="text-sm text-gray-600 mb-3">
                                        <span class="px-2 py-1 bg-gray-100 rounded">
                                            <?php echo htmlspecialchars($section['course']); ?> • Year <?php echo $section['year_level']; ?> • Section <?php echo htmlspecialchars($section['section']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="flex justify-between items-center">
                                        <div class="text-sm text-gray-600">
                                            <i class="fas fa-user-friends mr-1"></i>
                                            <?php 
                                            // Simple student count for this section
                                            $count_query = "SELECT COUNT(*) as count FROM users WHERE role='student' AND course=? AND year_level=? AND section=?";
                                            $count_stmt = $conn->prepare($count_query);
                                            $count_stmt->bind_param("sis", $section['course'], $section['year_level'], $section['section']);
                                            $count_stmt->execute();
                                            $count_result = $count_stmt->get_result();
                                            $student_count = $count_result->fetch_assoc()['count'] ?? 0;
                                            echo $student_count . " student" . ($student_count != 1 ? 's' : '');
                                            $count_stmt->close();
                                            ?>
                                        </div>
                                        <div>
                                            <a href="teacher_section_management.php?section_id=<?php echo $section['id']; ?>" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm font-semibold transition">
                                                <i class="fas fa-cog mr-1"></i>Manage
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- My Quizzes (NEW SECTION) -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-question-circle text-orange-500 mr-2"></i>My Quizzes
                            <span class="text-sm font-normal text-gray-600 ml-2">
                                (<?php echo $total_quizzes; ?> quiz<?php echo $total_quizzes != 1 ? 'zes' : ''; ?>)
                            </span>
                        </h2>
                        <a href="quiz.php" class="text-orange-600 hover:text-orange-800 text-sm font-semibold">
                            <i class="fas fa-plus-circle mr-1"></i>Create Quiz
                        </a>
                    </div>
                </div>
                <div class="p-6">
                    <?php if (empty($teacher_quizzes)): ?>
                        <div class="empty-state">
                            <i class="fas fa-question-circle"></i>
                            <h3 class="text-lg font-medium text-gray-700 mb-2">No Quizzes Yet</h3>
                            <p class="text-gray-500 mb-4">You haven't created any quizzes yet.</p>
                            <a href="create_quiz.php" class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($teacher_quizzes as $quiz): ?>
                                <?php
                                $status_class = $quiz['is_published'] ? 'quiz-status-published' : 'quiz-status-draft';
                                $status_text = $quiz['is_published'] ? 'Published' : 'Draft';
                                $due_date = !empty($quiz['due_date']) ? date('M d, Y', strtotime($quiz['due_date'])) : 'No due date';
                                ?>
                                
                                <div class="border rounded-lg p-4 hover:bg-orange-50 transition">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex-1">
                                            <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($quiz['title']); ?></h3>
                                            <?php if (!empty($quiz['description'])): ?>
                                                <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($quiz['description'], 0, 100)); ?>...</p>
                                            <?php endif; ?>
                                        </div>
                                        <span class="<?php echo $status_class; ?> ml-2"><?php echo $status_text; ?></span>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4 text-sm text-gray-600 mt-3">
                                        <div>
                                            <i class="fas fa-book mr-1"></i>
                                            <?php echo htmlspecialchars($quiz['subject_name'] ?? 'General'); ?>
                                        </div>
                                        <div>
                                            <i class="fas fa-users mr-1"></i>
                                            <?php echo htmlspecialchars($quiz['section_code'] ?? 'All Sections'); ?>
                                        </div>
                                        <div>
                                            <i class="fas fa-star mr-1"></i>
                                            <?php echo $quiz['total_points'] ?? 100; ?> points
                                        </div>
                                        <div>
                                            <i class="fas fa-clock mr-1"></i>
                                            <?php echo $quiz['time_limit'] ?? 60; ?> mins
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4 flex justify-between items-center">
                                        <div class="text-sm text-gray-500">
                                            <i class="fas fa-calendar mr-1"></i>
                                            Created: <?php echo date('M d, Y', strtotime($quiz['created_at'])); ?>
                                        </div>
                                        <div class="flex space-x-2">
                                            <a href="edit_quiz.php?id=<?php echo $quiz['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </a>
                                            <a href="view_quiz_results.php?id=<?php echo $quiz['id']; ?>" class="text-green-600 hover:text-green-800 text-sm font-semibold">
                                                <i class="fas fa-chart-bar mr-1"></i>Results
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8 bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-bolt text-yellow-500 mr-2"></i>Quick Actions
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <a href="create_section.php" class="bg-blue-600 hover:bg-blue-700 text-white p-4 rounded-lg text-center transition">
                        <i class="fas fa-plus-circle text-2xl mb-2 block"></i>
                        <span class="font-semibold">Create Section</span>
                    </a>
                    <a href="grade_students.php" class="bg-green-600 hover:bg-green-700 text-white p-4 rounded-lg text-center transition">
                        <i class="fas fa-graduation-cap text-2xl mb-2 block"></i>
                        <span class="font-semibold">Grade Students</span>
                    </a>
                    <a href="assignment.php" class="bg-purple-600 hover:bg-purple-700 text-white p-4 rounded-lg text-center transition">
                        <i class="fas fa-tasks text-2xl mb-2 block"></i>
                        <span class="font-semibold">Assignments</span>
                    </a>
                    <a href="quiz.php" class="quick-action-quiz p-4 rounded-lg text-center transition">
                        <i class="fas fa-question-circle text-2xl mb-2 block"></i>
                        <span class="font-semibold">Create Quiz</span>
                    </a>
                    <a href="announcement.php" class="bg-pink-600 hover:bg-pink-700 text-white p-4 rounded-lg text-center transition">
                        <i class="fas fa-bullhorn text-2xl mb-2 block"></i>
                        <span class="font-semibold">Announcements</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    // 1. CHATBASE WIDGET
    (function() {
        const chatbotId = 'o5GQsfIkaTL9N3YOLBdU7';
        const script = document.createElement('script');
        script.src = 'https://www.chatbase.co/embed.min.js';
        script.setAttribute('chatbotId', chatbotId);
        script.setAttribute('domain', 'www.chatbase.co');
        script.defer = true;
        document.head.appendChild(script);
    })();

    // 2. QUIZ REDIRECT - SIMPLIFIED VERSION
    document.addEventListener('DOMContentLoaded', function() {
        // Only target the quick action button, not the header link
        const quickActionQuizBtn = document.querySelector('.quick-action-quiz');
        
        if (quickActionQuizBtn) {
            quickActionQuizBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Simple redirect without quota check
                window.location.href = 'quiz.php';
            });
        }
        
        // For header navigation - let it work normally
        const headerQuizLink = document.querySelector('nav a[href*="quiz.php"]');
        if (headerQuizLink) {
            // Remove any existing event listeners by cloning
            const newLink = headerQuizLink.cloneNode(true);
            headerQuizLink.parentNode.replaceChild(newLink, headerQuizLink);
        }
    });
</script>

</body>
</html>
