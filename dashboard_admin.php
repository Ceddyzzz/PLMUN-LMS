<?php
// Session already started in dashboard.php
include 'includes/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['user'];

// Get comprehensive system statistics
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'student') as total_students,
        (SELECT COUNT(*) FROM users WHERE role = 'teacher') as total_teachers,
        (SELECT COUNT(*) FROM users WHERE role = 'dean') as total_deans,
        (SELECT COUNT(*) FROM users WHERE role = 'program_chair') as total_program_chairs,
        (SELECT COUNT(*) FROM users WHERE role = 'admin') as total_admins,
        (SELECT COUNT(*) FROM sections) as total_sections,
        (SELECT COUNT(DISTINCT course) FROM sections) as total_programs
";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Calculate total users
$total_users = $stats['total_students'] + $stats['total_teachers'] + 
               $stats['total_deans'] + $stats['total_program_chairs'] + 
               $stats['total_admins'];

// Get user distribution by role
$role_distribution = [
    'Students' => $stats['total_students'],
    'Teachers' => $stats['total_teachers'],
    'Deans' => $stats['total_deans'],
    'Program Chairs' => $stats['total_program_chairs'],
    'Admins' => $stats['total_admins']
];

// Get recent user registrations
$recent_users_query = "
    SELECT id, name, email, role, created_at 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 10
";
$recent_users = $conn->query($recent_users_query)->fetch_all(MYSQLI_ASSOC);

// Get program statistics
$program_stats_query = "
    SELECT 
        s.course,
        COUNT(DISTINCT s.id) as section_count,
        COUNT(DISTINCT u.id) as student_count,
        COUNT(DISTINCT s.teacher_id) as teacher_count
    FROM sections s
    LEFT JOIN users u ON (s.course = u.course AND s.year_level = u.year_level AND s.section = u.section AND u.role = 'student')
    GROUP BY s.course
    ORDER BY s.course
";
$program_stats = $conn->query($program_stats_query)->fetch_all(MYSQLI_ASSOC);

// Get system activity (sections created recently)
$recent_activity_query = "
    SELECT s.section_code, s.course, s.created_at, u.name as teacher_name
    FROM sections s
    LEFT JOIN users u ON s.teacher_id = u.id
    ORDER BY s.created_at DESC
    LIMIT 5
";
$recent_activity = $conn->query($recent_activity_query)->fetch_all(MYSQLI_ASSOC);

// Get users without sections (teachers with no assignments)
$unassigned_teachers_query = "
    SELECT u.id, u.name, u.email
    FROM users u
    WHERE u.role = 'teacher' 
    AND u.id NOT IN (SELECT DISTINCT teacher_id FROM sections WHERE teacher_id IS NOT NULL)
    LIMIT 5
";
$unassigned_teachers = $conn->query($unassigned_teachers_query)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | PLMUN LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-red-600 to-red-800 rounded-lg shadow-lg p-8 mb-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2">System Administrator Panel 🔐</h1>
                    <p class="text-red-100">Welcome, <?php echo htmlspecialchars($user_name); ?>!</p>
                    <p class="text-red-200 text-sm mt-1">Full system access and management capabilities</p>
                </div>
                <div class="text-right">
                    <div class="text-5xl mb-2">⚙️</div>
                    <p class="text-sm text-red-200"><?php echo date('l, F d, Y'); ?></p>
                </div>
            </div>
        </div>

        <!-- System Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-users text-blue-600 text-2xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($total_users); ?></p>
                <p class="text-sm text-gray-600 mt-1">Total Users</p>
                <div class="mt-3 flex items-center text-blue-600 text-xs">
                    <i class="fas fa-chart-line mr-1"></i>
                    <span>All roles</span>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-user-graduate text-green-600 text-2xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['total_students']); ?></p>
                <p class="text-sm text-gray-600 mt-1">Students</p>
                <div class="mt-3 flex items-center text-green-600 text-xs">
                    <i class="fas fa-user-check mr-1"></i>
                    <span>Enrolled</span>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-chalkboard-teacher text-purple-600 text-2xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_teachers']; ?></p>
                <p class="text-sm text-gray-600 mt-1">Teachers</p>
                <div class="mt-3 flex items-center text-purple-600 text-xs">
                    <i class="fas fa-user-tie mr-1"></i>
                    <span>Faculty</span>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-orange-100 p-3 rounded-full">
                        <i class="fas fa-book text-orange-600 text-2xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_sections']; ?></p>
                <p class="text-sm text-gray-600 mt-1">Sections</p>
                <div class="mt-3 flex items-center text-orange-600 text-xs">
                    <i class="fas fa-layer-group mr-1"></i>
                    <span>Active</span>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-indigo-100 p-3 rounded-full">
                        <i class="fas fa-user-shield text-indigo-600 text-2xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_deans'] + $stats['total_program_chairs']; ?></p>
                <p class="text-sm text-gray-600 mt-1">Administrators</p>
                <div class="mt-3 flex items-center text-indigo-600 text-xs">
                    <i class="fas fa-shield-alt mr-1"></i>
                    <span>Dean & Chairs</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Quick Admin Actions -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-rocket text-red-500 mr-2"></i>Quick Admin Actions
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <a href="admin_create_account.php" class="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg hover:shadow-md transition border border-blue-200">
                                <div class="bg-blue-500 p-3 rounded-full mb-3">
                                    <i class="fas fa-user-plus text-white text-2xl"></i>
                                </div>
                                <span class="text-sm font-semibold text-gray-800 text-center">Create Admin Account</span>
                            </a>

                            <a href="manage_users.php" class="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-lg hover:shadow-md transition border border-green-200">
                                <div class="bg-green-500 p-3 rounded-full mb-3">
                                    <i class="fas fa-users-cog text-white text-2xl"></i>
                                </div>
                                <span class="text-sm font-semibold text-gray-800 text-center">Manage Users</span>
                            </a>

                            <a href="sections.php" class="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg hover:shadow-md transition border border-purple-200">
                                <div class="bg-purple-500 p-3 rounded-full mb-3">
                                    <i class="fas fa-chalkboard text-white text-2xl"></i>
                                </div>
                                <span class="text-sm font-semibold text-gray-800 text-center">Manage Sections</span>
                            </a>

                            <a href="system_settings.php" class="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg hover:shadow-md transition border border-orange-200">
                                <div class="bg-orange-500 p-3 rounded-full mb-3">
                                    <i class="fas fa-cog text-white text-2xl"></i>
                                </div>
                                <span class="text-sm font-semibold text-gray-800 text-center">System Settings</span>
                            </a>

                            <a href="reports.php" class="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-red-50 to-red-100 rounded-lg hover:shadow-md transition border border-red-200">
                                <div class="bg-red-500 p-3 rounded-full mb-3">
                                    <i class="fas fa-chart-bar text-white text-2xl"></i>
                                </div>
                                <span class="text-sm font-semibold text-gray-800 text-center">Reports</span>
                            </a>

                            <a href="backup.php" class="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-lg hover:shadow-md transition border border-indigo-200">
                                <div class="bg-indigo-500 p-3 rounded-full mb-3">
                                    <i class="fas fa-database text-white text-2xl"></i>
                                </div>
                                <span class="text-sm font-semibold text-gray-800 text-center">Backup System</span>
                            </a>

                            <a href="activity_logs.php" class="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg hover:shadow-md transition border border-yellow-200">
                                <div class="bg-yellow-500 p-3 rounded-full mb-3">
                                    <i class="fas fa-history text-white text-2xl"></i>
                                </div>
                                <span class="text-sm font-semibold text-gray-800 text-center">Activity Logs</span>
                            </a>

                            <a href="announcement.php" class="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg hover:shadow-md transition border border-pink-200">
                                <div class="bg-pink-500 p-3 rounded-full mb-3">
                                    <i class="fas fa-bullhorn text-white text-2xl"></i>
                                </div>
                                <span class="text-sm font-semibold text-gray-800 text-center">Announcements</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Program Statistics -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-graduation-cap text-blue-500 mr-2"></i>Program Statistics
                        </h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($program_stats)): ?>
                            <p class="text-gray-500 text-center py-8">No program data available</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php 
                                $program_colors = [
                                    'BSCS' => ['bg' => 'bg-blue-500', 'light' => 'bg-blue-50', 'border' => 'border-blue-200'],
                                    'BSIT' => ['bg' => 'bg-green-500', 'light' => 'bg-green-50', 'border' => 'border-green-200'],
                                    'ACT' => ['bg' => 'bg-purple-500', 'light' => 'bg-purple-50', 'border' => 'border-purple-200']
                                ];
                                
                                foreach ($program_stats as $program): 
                                    $colors = $program_colors[$program['course']] ?? ['bg' => 'bg-gray-500', 'light' => 'bg-gray-50', 'border' => 'border-gray-200'];
                                ?>
                                    <div class="border rounded-lg p-4 <?php echo $colors['light']; ?> <?php echo $colors['border']; ?>">
                                        <div class="flex items-center justify-between mb-3">
                                            <h3 class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($program['course']); ?></h3>
                                            <div class="flex space-x-2">
                                                <span class="px-3 py-1 bg-white rounded-full text-xs font-semibold shadow-sm">
                                                    <i class="fas fa-users text-gray-600"></i> <?php echo $program['student_count']; ?>
                                                </span>
                                                <span class="px-3 py-1 bg-white rounded-full text-xs font-semibold shadow-sm">
                                                    <i class="fas fa-chalkboard text-gray-600"></i> <?php echo $program['section_count']; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-3">
                                            <div class="bg-white rounded p-2 text-center">
                                                <p class="text-xl font-bold text-gray-800"><?php echo $program['student_count']; ?></p>
                                                <p class="text-xs text-gray-600">Students</p>
                                            </div>
                                            <div class="bg-white rounded p-2 text-center">
                                                <p class="text-xl font-bold text-gray-800"><?php echo $program['section_count']; ?></p>
                                                <p class="text-xs text-gray-600">Sections</p>
                                            </div>
                                            <div class="bg-white rounded p-2 text-center">
                                                <p class="text-xl font-bold text-gray-800"><?php echo $program['teacher_count']; ?></p>
                                                <p class="text-xs text-gray-600">Teachers</p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-clock text-green-500 mr-2"></i>Recent System Activity
                        </h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($recent_activity)): ?>
                            <p class="text-gray-500 text-center py-8">No recent activity</p>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($recent_activity as $activity): ?>
                                    <div class="flex items-start space-x-3 pb-3 border-b last:border-b-0">
                                        <div class="bg-green-100 p-2 rounded-full">
                                            <i class="fas fa-plus text-green-600"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm text-gray-800">
                                                Section <span class="font-semibold"><?php echo htmlspecialchars($activity['section_code']); ?></span> created
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <?php echo htmlspecialchars($activity['teacher_name'] ?? 'No teacher assigned'); ?> • 
                                                <?php echo date('M d, Y g:i A', strtotime($activity['created_at'])); ?>
                                            </p>
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
                <!-- User Role Distribution -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-chart-pie text-purple-500 mr-2"></i>User Distribution
                        </h2>
                    </div>
                    <div class="p-6">
                        <canvas id="userDistributionChart" height="200"></canvas>
                        <div class="mt-4 space-y-2">
                            <?php foreach ($role_distribution as $role => $count): 
                                if ($count == 0) continue;
                                $percentage = $total_users > 0 ? round(($count / $total_users) * 100, 1) : 0;
                            ?>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600"><?php echo $role; ?></span>
                                    <span class="font-semibold text-gray-800"><?php echo $count; ?> (<?php echo $percentage; ?>%)</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent User Registrations -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b flex items-center justify-between">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-user-plus text-blue-500 mr-2"></i>Recent Users
                        </h2>
                        <a href="manage_users.php" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                    </div>
                    <div class="p-6">
                        <?php if (empty($recent_users)): ?>
                            <p class="text-gray-500 text-center text-sm py-4">No users yet</p>
                        <?php else: ?>
                            <div class="space-y-3 max-h-96 overflow-y-auto">
                                <?php foreach (array_slice($recent_users, 0, 5) as $user): 
                                    $role_colors = [
                                        'student' => 'bg-blue-100 text-blue-800',
                                        'teacher' => 'bg-green-100 text-green-800',
                                        'dean' => 'bg-purple-100 text-purple-800',
                                        'program_chair' => 'bg-indigo-100 text-indigo-800',
                                        'admin' => 'bg-red-100 text-red-800'
                                    ];
                                    $color = $role_colors[$user['role']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded hover:bg-gray-100 transition">
                                        <div class="flex-1">
                                            <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($user['name']); ?></p>
                                            <p class="text-xs text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                                            <p class="text-xs text-gray-500 mt-1"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                                        </div>
                                        <span class="px-2 py-1 <?php echo $color; ?> text-xs rounded-full font-semibold">
                                            <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- System Alerts -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>System Alerts
                        </h2>
                    </div>
                    <div class="p-6 space-y-3">
                        <?php if (!empty($unassigned_teachers)): ?>
                            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-3 rounded">
                                <p class="text-sm font-semibold text-yellow-800">Unassigned Teachers</p>
                                <p class="text-xs text-yellow-700 mt-1"><?php echo count($unassigned_teachers); ?> teacher(s) have no sections assigned</p>
                                <a href="manage_users.php?filter=unassigned_teachers" class="text-xs text-yellow-600 hover:text-yellow-800 mt-2 inline-block">View teachers →</a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="bg-green-50 border-l-4 border-green-500 p-3 rounded">
                            <p class="text-sm font-semibold text-green-800">System Status</p>
                            <p class="text-xs text-green-700 mt-1">All systems operational</p>
                        </div>

                        <div class="bg-blue-50 border-l-4 border-blue-500 p-3 rounded">
                            <p class="text-sm font-semibold text-blue-800">Database Health</p>
                            <p class="text-xs text-blue-700 mt-1">Last backup: <?php echo date('M d, Y g:i A'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Summary -->
                <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg shadow p-6">
                    <h3 class="font-bold text-gray-800 mb-4">
                        <i class="fas fa-info-circle text-red-600 mr-2"></i>System Summary
                    </h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between items-center pb-2 border-b border-red-200">
                            <span class="text-gray-600">Total Users:</span>
                            <span class="font-semibold text-gray-800"><?php echo number_format($total_users); ?></span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-red-200">
                            <span class="text-gray-600">Active Sections:</span>
                            <span class="font-semibold text-gray-800"><?php echo $stats['total_sections']; ?></span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-red-200">
                            <span class="text-gray-600">Programs:</span>
                            <span class="font-semibold text-gray-800"><?php echo $stats['total_programs']; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">System Version:</span>
                            <span class="font-semibold text-gray-800">v1.0.0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // User Distribution Chart
        const ctx = document.getElementById('userDistributionChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($role_distribution)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($role_distribution)); ?>,
                    backgroundColor: [
                        '#3B82F6', // Blue - Students
                        '#10B981', // Green - Teachers
                        '#8B5CF6', // Purple - Deans
                        '#6366F1', // Indigo - Program Chairs
                        '#EF4444'  // Red - Admins
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
