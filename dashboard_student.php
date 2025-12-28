<?php
// DO NOT start session here - it's already started in dashboard.php or header.php
// session_start(); // REMOVE THIS LINE

include 'includes/db_connect.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user'];

// Get student info - FIXED with error handling
$student_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($student_query);

// Check if prepare() succeeded
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get enrolled sections - FIXED with error handling
$sections_query = "
    SELECT s.*, u.name as teacher_name
    FROM sections s
    LEFT JOIN users u ON s.teacher_id = u.id
    WHERE s.course = ? AND s.year_level = ? AND s.section = ?
";
$stmt = $conn->prepare($sections_query);

if (!$stmt) {
    die("Sections query prepare failed: " . $conn->error);
}

$course = $student['course'] ?? '';
$year_level = $student['year_level'] ?? '';
$section = $student['section'] ?? '';

$stmt->bind_param("sis", $course, $year_level, $section);
$stmt->execute();
$sections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get upcoming assignments - FIXED with error handling
$assignments = [];
if (!empty($course) && !empty($year_level) && !empty($section)) {
    $assignments_query = "
        SELECT a.*, s.section_code 
        FROM assignments a
        LEFT JOIN sections s ON a.section_id = s.id
        WHERE a.due_date >= CURDATE()
        AND s.course = ? AND s.year_level = ? AND s.section = ?
        ORDER BY a.due_date ASC
        LIMIT 5
    ";
    
    $stmt = $conn->prepare($assignments_query);
    
    if ($stmt) {
        $stmt->bind_param("sis", $course, $year_level, $section);
        $stmt->execute();
        $assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// Get recent announcements - FIXED with error handling
$announcements = [];
if (!empty($course)) {
    $announcements_query = "
        SELECT * FROM announcements 
        WHERE target_type = 'university' OR (target_type = 'program' AND program = ?)
        ORDER BY created_at DESC 
        LIMIT 5
    ";
    
    $stmt = $conn->prepare($announcements_query);
    
    if ($stmt) {
        $stmt->bind_param("s", $course);
        $stmt->execute();
        $announcements_result = $stmt->get_result();
        $announcements = $announcements_result ? $announcements_result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
    }
}

// Calculate stats
$total_sections = count($sections);
$total_assignments = count($assignments);

// Get current grades - FIXED with error handling
$average_grade = '--';
$grades_query = "
    SELECT AVG(grade) as average_grade 
    FROM grades 
    WHERE student_id = ?
";
$stmt = $conn->prepare($grades_query);

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $grades_result = $stmt->get_result();
    $grade_data = $grades_result->fetch_assoc();
    $average_grade = $grade_data['average_grade'] ? number_format($grade_data['average_grade'], 2) : '--';
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard | PLMUN LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Chatbase Chatbot Button Styles (Keeping Chatbase) */
        #chatbaseButton {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #10B981, #3B82F6);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 18px 28px;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        #chatbaseButton:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 30px rgba(16, 185, 129, 0.4);
        }
        
        #chatbaseButton i {
            font-size: 1.2rem;
        }
        
        #chatbaseButton.loading {
            background: #6b7280;
        }
        
        #chatbaseButton.error {
            background: #ef4444;
        }
        
        /* Dashboard Enhancements */
        .hover-card {
            transition: all 0.3s ease;
        }
        
        .hover-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            #chatbaseButton {
                bottom: 20px;
                right: 20px;
                padding: 15px 20px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-2xl shadow-xl p-8 mb-8 text-white hover-card">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2">Welcome back, <?php echo htmlspecialchars($user_name); ?>! 👋</h1>
                    <?php if (!empty($student)): ?>
                        <p class="text-blue-100 text-lg">
                            <i class="fas fa-graduation-cap mr-2"></i>
                            <?php echo htmlspecialchars($student['course'] ?? 'Not Assigned'); ?> - 
                            Year <?php echo $student['year_level'] ?? 'N/A'; ?> - 
                            Section <?php echo htmlspecialchars($student['section'] ?? 'N/A'); ?>
                        </p>
                        <p class="text-blue-200 text-sm mt-1">
                            <i class="fas fa-id-card mr-1"></i>Student ID: <?php echo htmlspecialchars($student['student_id'] ?? $user_id); ?>
                        </p>
                    <?php else: ?>
                        <p class="text-blue-100 text-lg">
                            <i class="fas fa-user mr-2"></i>Student Profile Loading...
                        </p>
                    <?php endif; ?>
                </div>
                <div class="text-right">
                    <div class="text-6xl mb-2">🎓</div>
                    <p class="text-sm text-blue-200">
                        <i class="fas fa-calendar-day mr-1"></i><?php echo date('l, F d, Y'); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6 hover-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">My Sections</p>
                        <p class="text-3xl font-bold text-blue-600"><?php echo $total_sections; ?></p>
                        <p class="text-xs text-gray-500 mt-1">Enrolled courses</p>
                    </div>
                    <div class="bg-blue-100 p-4 rounded-full">
                        <i class="fas fa-book text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 hover-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Pending Assignments</p>
                        <p class="text-3xl font-bold text-orange-600"><?php echo $total_assignments; ?></p>
                        <p class="text-xs text-gray-500 mt-1">To be submitted</p>
                    </div>
                    <div class="bg-orange-100 p-4 rounded-full">
                        <i class="fas fa-tasks text-orange-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 hover-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Average Grade</p>
                        <p class="text-3xl font-bold text-purple-600"><?php echo $average_grade; ?></p>
                        <p class="text-xs text-gray-500 mt-1">Current standing</p>
                    </div>
                    <div class="bg-purple-100 p-4 rounded-full">
                        <i class="fas fa-chart-line text-purple-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 hover-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Announcements</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo count($announcements); ?></p>
                        <p class="text-xs text-gray-500 mt-1">Latest updates</p>
                    </div>
                    <div class="bg-green-100 p-4 rounded-full">
                        <i class="fas fa-bullhorn text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Upcoming Assignments -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b flex items-center justify-between">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-clipboard-list text-orange-500 mr-2"></i>Upcoming Assignments
                        </h2>
                        <a href="assignment.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            View All <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    <div class="p-6">
                        <?php if (empty($assignments)): ?>
                            <div class="text-center py-12">
                                <div class="text-green-400 text-6xl mb-4">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-700 mb-2">All Caught Up!</h3>
                                <p class="text-gray-500">No pending assignments. Great work! 🎉</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($assignments as $assignment): 
                                    $due_date = strtotime($assignment['due_date']);
                                    $days_left = ceil(($due_date - time()) / (60 * 60 * 24));
                                    $urgency_color = $days_left <= 1 ? 'bg-red-100 text-red-800' : 
                                                   ($days_left <= 3 ? 'bg-yellow-100 text-yellow-800' : 
                                                   'bg-green-100 text-green-800');
                                ?>
                                    <div class="border border-gray-200 rounded-lg p-5 hover:shadow-md transition-all duration-300 hover:border-blue-300">
                                        <div class="flex items-start justify-between mb-3">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3 mb-2">
                                                    <span class="px-3 py-1 text-xs rounded-full <?php echo $urgency_color; ?> font-medium">
                                                        <?php echo $days_left; ?> day<?php echo $days_left != 1 ? 's' : ''; ?> left
                                                    </span>
                                                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                                        <?php echo htmlspecialchars($assignment['section_code']); ?>
                                                    </span>
                                                </div>
                                                <h4 class="font-semibold text-gray-800 text-lg mb-1">
                                                    <?php echo htmlspecialchars($assignment['title']); ?>
                                                </h4>
                                                <?php if (!empty($assignment['description'])): ?>
                                                    <p class="text-gray-600 text-sm mb-2">
                                                        <?php echo substr(htmlspecialchars($assignment['description']), 0, 100); ?>...
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-4">
                                                <span class="text-sm text-gray-500">
                                                    <i class="fas fa-calendar-alt mr-1"></i>
                                                    Due: <?php echo date('M d, Y', $due_date); ?>
                                                </span>
                                                <?php if (isset($assignment['points'])): ?>
                                                    <span class="text-sm text-gray-500">
                                                        <i class="fas fa-star mr-1"></i>
                                                        <?php echo $assignment['points']; ?> points
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <a href="assignment.php?id=<?php echo $assignment['id']; ?>" 
                                               class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                                View Details <i class="fas fa-external-link-alt ml-1"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Announcements -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b flex items-center justify-between">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-bullhorn text-red-500 mr-2"></i>Latest Announcements
                        </h2>
                        <a href="announcement.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            See All <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    <div class="p-6">
                        <?php if (empty($announcements)): ?>
                            <div class="text-center py-8">
                                <div class="text-gray-300 text-5xl mb-3">
                                    <i class="fas fa-bell-slash"></i>
                                </div>
                                <p class="text-gray-500">No announcements at the moment</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($announcements as $announcement): 
                                    $badge_colors = [
                                        'urgent' => 'bg-red-100 text-red-800',
                                        'event' => 'bg-blue-100 text-blue-800',
                                        'academic' => 'bg-green-100 text-green-800',
                                        'info' => 'bg-purple-100 text-purple-800'
                                    ];
                                    $badge_color = $badge_colors[$announcement['category']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                    <div class="border-l-4 border-blue-500 pl-4 py-3 hover:bg-blue-50 transition rounded-r">
                                        <div class="flex items-start justify-between mb-2">
                                            <div>
                                                <span class="px-2 py-1 text-xs rounded-full <?php echo $badge_color; ?> font-medium mr-2">
                                                    <?php echo ucfirst($announcement['category']); ?>
                                                </span>
                                                <?php if ($announcement['target_type'] === 'program'): ?>
                                                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                                        <?php echo htmlspecialchars($announcement['program']); ?> Only
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="text-xs text-gray-500">
                                                <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?>
                                            </span>
                                        </div>
                                        <h4 class="font-semibold text-gray-800 text-sm mb-1">
                                            <?php echo htmlspecialchars($announcement['title']); ?>
                                        </h4>
                                        <p class="text-gray-600 text-xs">
                                            <?php echo substr(htmlspecialchars($announcement['content']), 0, 120); ?>...
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-8">
                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-bolt text-yellow-500 mr-2"></i>Quick Actions
                        </h2>
                    </div>
                    <div class="p-6 space-y-3">
                        <a href="assignment.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-3 rounded-lg transition flex items-center justify-center font-medium">
                            <i class="fas fa-tasks mr-2"></i>View Assignments
                        </a>
                        <a href="e-books.php" class="block w-full bg-green-600 hover:bg-green-700 text-white text-center py-3 rounded-lg transition flex items-center justify-center font-medium">
                            <i class="fas fa-book mr-2"></i>Browse E-Books
                        </a>
                        <a href="quiz.php" class="block w-full bg-purple-600 hover:bg-purple-700 text-white text-center py-3 rounded-lg transition flex items-center justify-center font-medium">
                            <i class="fas fa-question-circle mr-2"></i>Take Quiz
                        </a>
                        <a href="calendar.php" class="block w-full bg-pink-600 hover:bg-pink-700 text-white text-center py-3 rounded-lg transition flex items-center justify-center font-medium">
                            <i class="fas fa-calendar-alt mr-2"></i>View Calendar
                        </a>
                    </div>
                </div>

                <!-- Academic Profile -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl shadow-lg p-6 border border-blue-200">
                    <h3 class="font-bold text-gray-800 mb-4 text-lg">
                        <i class="fas fa-user-graduate text-blue-600 mr-2"></i>Academic Profile
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-blue-200">
                            <span class="text-gray-700 font-medium">Program:</span>
                            <span class="font-bold text-blue-800"><?php echo htmlspecialchars($student['course'] ?? 'Not Assigned'); ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-blue-200">
                            <span class="text-gray-700 font-medium">Year Level:</span>
                            <span class="font-bold text-blue-800">Year <?php echo $student['year_level'] ?? 'N/A'; ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-blue-200">
                            <span class="text-gray-700 font-medium">Section:</span>
                            <span class="font-bold text-blue-800"><?php echo htmlspecialchars($student['section'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-gray-700 font-medium">Status:</span>
                            <span class="px-3 py-1 bg-green-200 text-green-800 text-sm rounded-full font-semibold">
                                <i class="fas fa-check-circle mr-1"></i>Active
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chatbase AI Script - Official Clean Embed -->
    <script>
        (function() {
            const chatbotId = 'o5GQsfIkaTL9N3YOLBdU7';
            
            // 1. Load the official Chatbase widget script
            const script = document.createElement('script');
            script.src = 'https://www.chatbase.co/embed.min.js';
            script.setAttribute('chatbotId', chatbotId);
            script.setAttribute('domain', 'www.chatbase.co');
            script.defer = true;
            document.head.appendChild(script);
            
            // 2. Hide your custom button once the official widget loads
            setTimeout(() => {
                const myButton = document.getElementById('chatbaseButton');
                if (myButton && window.chatbase) {
                    // The official Chatbase widget is now loaded and visible
                    myButton.style.display = 'none'; // Hide your custom button
                    console.log('Official Chatbase widget loaded.');
                }
            }, 2000); // Check after 2 seconds
        })();
    </script>

    <!-- Bootstrap JS (if needed) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
