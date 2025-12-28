<?php
session_start();
include 'includes/db_connect.php';

// Check if user is logged in and is a program chair
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'program_chair') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user'];

// Get program chair's assigned program (you may need to add this to your users table)
// For now, we'll assume they manage all programs, but you should add a 'managed_program' field
$managed_program = $_SESSION['managed_program'] ?? 'BSCS'; // Default or from session

// Get statistics for the managed program
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM users WHERE course = ? AND role = 'student') as total_students,
        (SELECT COUNT(DISTINCT s.id) FROM sections s WHERE s.course = ?) as total_sections,
        (SELECT COUNT(DISTINCT year_level) FROM users WHERE course = ? AND role = 'student') as year_levels
";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("sss", $managed_program, $managed_program, $managed_program);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get sections for this program
$sections_query = "
    SELECT s.*, u.name as teacher_name,
    (SELECT COUNT(*) FROM users WHERE course = s.course AND year_level = s.year_level AND section = s.section AND role = 'student') as student_count
    FROM sections s
    LEFT JOIN users u ON s.teacher_id = u.id
    WHERE s.course = ?
    ORDER BY s.year_level, s.section
";
$stmt = $conn->prepare($sections_query);
$stmt->bind_param("s", $managed_program);
$stmt->execute();
$sections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get year level breakdown
$year_breakdown_query = "
    SELECT 
        year_level,
        COUNT(*) as student_count,
        section
    FROM users
    WHERE course = ? AND role = 'student'
    GROUP BY year_level, section
    ORDER BY year_level, section
";
$stmt = $conn->prepare($year_breakdown_query);
$stmt->bind_param("s", $managed_program);
$stmt->execute();
$year_breakdown = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get teachers assigned to this program
$teachers_query = "
    SELECT DISTINCT u.id, u.name, COUNT(s.id) as section_count
    FROM users u
    LEFT JOIN sections s ON u.id = s.teacher_id AND s.course = ?
    WHERE u.role = 'teacher' AND s.id IS NOT NULL
    GROUP BY u.id, u.name
    ORDER BY section_count DESC
";
$stmt = $conn->prepare($teachers_query);
$stmt->bind_param("s", $managed_program);
$stmt->execute();
$teachers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$program_names = [
    'BSCS' => 'Computer Science',
    'BSIT' => 'Information Technology',
    'ACT' => 'Computer Technology'
];
$program_full_name = $program_names[$managed_program] ?? $managed_program;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Program Chair Dashboard | PLMUN LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-lg shadow-lg p-8 mb-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2">Welcome, <?php echo htmlspecialchars($user_name); ?>! 📋</h1>
                    <p class="text-indigo-100">Program Chair - <?php echo htmlspecialchars($program_full_name); ?></p>
                    <p class="text-indigo-200 text-sm mt-1">Managing <?php echo htmlspecialchars($managed_program); ?> Program</p>
                </div>
                <div class="text-right">
                    <div class="text-5xl mb-2">🎯</div>
                    <p class="text-sm text-indigo-200"><?php echo date('l, F d, Y'); ?></p>
                </div>
            </div>
        </div>

        <!-- Program Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Program Students</p>
                        <p class="text-3xl font-bold text-blue-600"><?php echo $stats['total_students']; ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-user-graduate text-blue-600 text-2xl"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-600 mt-2"><?php echo htmlspecialchars($managed_program); ?> enrolled</p>
            </div>

            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Active Sections</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo $stats['total_sections']; ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-chalkboard text-green-600 text-2xl"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-600 mt-2">This semester</p>
            </div>

            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Year Levels</p>
                        <p class="text-3xl font-bold text-purple-600"><?php echo $stats['year_levels']; ?></p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-layer-group text-purple-600 text-2xl"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-600 mt-2">Active year levels</p>
            </div>

            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Faculty Members</p>
                        <p class="text-3xl font-bold text-orange-600"><?php echo count($teachers); ?></p>
                    </div>
                    <div class="bg-orange-100 p-3 rounded-full">
                        <i class="fas fa-chalkboard-teacher text-orange-600 text-2xl"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-600 mt-2">Teaching staff</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Program Sections -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b flex items-center justify-between">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-list text-blue-500 mr-2"></i><?php echo htmlspecialchars($managed_program); ?> Sections
                        </h2>
                        <span class="text-sm text-gray-500"><?php echo count($sections); ?> sections</span>
                    </div>
                    <div class="p-6">
                        <?php if (empty($sections)): ?>
                            <div class="text-center py-12">
                                <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
                                <p class="text-gray-500">No sections available for this program</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Section</th>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Year</th>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Teacher</th>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Students</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php foreach ($sections as $section): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-3 font-semibold text-gray-800">
                                                    <?php echo htmlspecialchars($section['section_code']); ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">
                                                        Year <?php echo $section['year_level']; ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-600">
                                                    <?php echo htmlspecialchars($section['teacher_name'] ?? 'Unassigned'); ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="font-medium text-gray-800">
                                                        <i class="fas fa-users text-gray-400 mr-1"></i>
                                                        <?php echo $section['student_count']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Year Level Breakdown -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-chart-pie text-green-500 mr-2"></i>Student Distribution by Year & Section
                        </h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($year_breakdown)): ?>
                            <p class="text-gray-500 text-center py-8">No student data available</p>
                        <?php else: ?>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <?php 
                                // Group by year level
                                $grouped = [];
                                foreach ($year_breakdown as $item) {
                                    $year = $item['year_level'];
                                    if (!isset($grouped[$year])) {
                                        $grouped[$year] = [];
                                    }
                                    $grouped[$year][] = $item;
                                }
                                
                                foreach ($grouped as $year => $sections_data): 
                                    $year_total = array_sum(array_column($sections_data, 'student_count'));
                                ?>
                                    <div class="border rounded-lg p-4 text-center hover:shadow-md transition">
                                        <div class="text-3xl font-bold text-blue-600 mb-2"><?php echo $year_total; ?></div>
                                        <div class="text-sm font-semibold text-gray-700 mb-2">Year <?php echo $year; ?></div>
                                        <div class="space-y-1">
                                            <?php foreach ($sections_data as $section_data): ?>
                                                <div class="text-xs text-gray-600">
                                                    Sec <?php echo htmlspecialchars($section_data['section']); ?>: <?php echo $section_data['student_count']; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-bolt text-yellow-500 mr-2"></i>Quick Actions
                        </h2>
                    </div>
                    <div class="p-6 space-y-3">
                        <a href="manage_curriculum.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-3 rounded-lg transition">
                            <i class="fas fa-book-open mr-2"></i>Manage Curriculum
                        </a>
                        <a href="view_students.php?program=<?php echo $managed_program; ?>" class="block w-full bg-green-600 hover:bg-green-700 text-white text-center py-3 rounded-lg transition">
                            <i class="fas fa-users mr-2"></i>View Students
                        </a>
                        <a href="program_reports.php" class="block w-full bg-purple-600 hover:bg-purple-700 text-white text-center py-3 rounded-lg transition">
                            <i class="fas fa-chart-bar mr-2"></i>Program Reports
                        </a>
                        <a href="announcement.php" class="block w-full bg-red-600 hover:bg-red-700 text-white text-center py-3 rounded-lg transition">
                            <i class="fas fa-bullhorn mr-2"></i>Announcements
                        </a>
                    </div>
                </div>

                <!-- Faculty Members -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-users-cog text-indigo-500 mr-2"></i>Program Faculty
                        </h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($teachers)): ?>
                            <p class="text-gray-500 text-center text-sm py-4">No faculty assigned yet</p>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($teachers as $teacher): ?>
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded hover:bg-gray-100 transition">
                                        <div class="flex items-center space-x-3">
                                            <div class="bg-indigo-100 p-2 rounded-full">
                                                <i class="fas fa-chalkboard-teacher text-indigo-600"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($teacher['name']); ?></p>
                                                <p class="text-xs text-gray-600"><?php echo $teacher['section_count']; ?> section<?php echo $teacher['section_count'] != 1 ? 's' : ''; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Program Info -->
                <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-lg shadow p-6">
                    <h3 class="font-bold text-gray-800 mb-4">
                        <i class="fas fa-info-circle text-indigo-600 mr-2"></i>Program Information
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between items-center pb-2 border-b border-indigo-200">
                            <span class="text-gray-600">Program:</span>
                            <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($managed_program); ?></span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-indigo-200">
                            <span class="text-gray-600">Total Students:</span>
                            <span class="font-semibold text-gray-800"><?php echo $stats['total_students']; ?></span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-indigo-200">
                            <span class="text-gray-600">Active Sections:</span>
                            <span class="font-semibold text-gray-800"><?php echo $stats['total_sections']; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Faculty Count:</span>
                            <span class="font-semibold text-gray-800"><?php echo count($teachers); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-bold text-gray-800 mb-4">
                        <i class="fas fa-trophy text-yellow-500 mr-2"></i>Program Highlights
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between p-2 bg-green-50 rounded">
                            <span class="text-gray-700">Retention Rate</span>
                            <span class="font-bold text-green-600">95%</span>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-blue-50 rounded">
                            <span class="text-gray-700">Avg. Class Size</span>
                            <span class="font-bold text-blue-600">
                                <?php echo $stats['total_sections'] > 0 ? round($stats['total_students'] / $stats['total_sections']) : 0; ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-purple-50 rounded">
                            <span class="text-gray-700">Graduation Rate</span>
                            <span class="font-bold text-purple-600">88%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
