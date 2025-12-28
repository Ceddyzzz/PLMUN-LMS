<?php
session_start();
include 'includes/db_connect.php';

// ✅ Check if user is logged in and has proper role
if (!isset($_SESSION['user']) || !in_array($_SESSION['role'], ['teacher', 'dean', 'program_chair', 'admin'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// ✅ Get all sections for this teacher
if ($role === 'teacher') {
  // Teacher sees only their sections - FIXED: removed ts.assigned_at
  $sections_query = "
    SELECT s.*
    FROM sections s
    WHERE s.teacher_id = ?
    ORDER BY s.year_level, s.course, s.section
  ";
  $stmt = $conn->prepare($sections_query);
  if (!$stmt) {
    die("Database error: " . $conn->error);
  }
  $stmt->bind_param("i", $user_id);
} else {
  // Dean/Program Chair/Admin sees all sections
  $sections_query = "
    SELECT s.*, u.name as teacher_name 
    FROM sections s
    LEFT JOIN users u ON s.teacher_id = u.id
    ORDER BY s.year_level, s.course, s.section
  ";
  $stmt = $conn->prepare($sections_query);
  if (!$stmt) {
    die("Database error: " . $conn->error);
  }
}

$stmt->execute();
$sections_result = $stmt->get_result();
$sections = [];
while ($row = $sections_result->fetch_assoc()) {
  $sections[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Sections | PLMUN LMS</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
  <?php include 'includes/header.php'; ?>
  
  <div class="container mx-auto px-4 py-8 max-w-7xl">
    
    <!-- Header with Create Button -->
    <div class="mb-8">
      <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800">My Sections</h1>
          <p class="text-gray-600 mt-1">Manage your class sections and student groups</p>
        </div>
        <div class="mt-4 md:mt-0">
          <a href="create_section.php" 
             class="inline-flex items-center bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition duration-300">
            <i class="fas fa-plus-circle mr-2"></i> Create New Section
          </a>
          <span class="ml-4 text-gray-600 text-sm hidden md:inline-block">
            Format: <code class="font-mono bg-gray-100 px-2 py-1 rounded border">1-BSCS-A</code>
          </span>
        </div>
      </div>
      
      <!-- Info Card -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
          <div class="bg-blue-100 text-blue-800 p-2 rounded-lg mr-3">
            <i class="fas fa-info-circle"></i>
          </div>
          <div>
            <p class="text-blue-800 font-medium">CITCS Section Format: <code class="font-mono">Year-Program-Section</code></p>
            <p class="text-blue-600 text-sm mt-1">
              Examples: <span class="font-mono">1-BSCS-A</span> (1st Year Computer Science, Section A), 
              <span class="font-mono">4-BSIT-C</span> (4th Year IT, Section C),
              <span class="font-mono">2-ACT-B</span> (2nd Year Computer Tech, Section B)
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Sections Count -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-lg font-bold text-gray-800">📚 Section Summary</h2>
          <p class="text-gray-600 text-sm mt-1">Total: <span class="font-bold text-blue-600"><?php echo count($sections); ?></span> sections</p>
        </div>
        <div class="text-sm text-gray-500">
          <i class="far fa-clock mr-1"></i> Last updated: <?php echo date('M d, Y h:i A'); ?>
        </div>
      </div>
    </div>

    <!-- Sections Grid/Table -->
    <?php if (empty($sections)): ?>
      <!-- Empty State -->
      <div class="bg-white rounded-xl shadow-lg p-8 text-center">
        <div class="text-6xl mb-6 text-gray-300">
          <i class="fas fa-chalkboard-teacher"></i>
        </div>
        <h3 class="text-2xl font-bold text-gray-700 mb-3">No Sections Yet</h3>
        <p class="text-gray-600 mb-6 max-w-md mx-auto">
          You haven't created any sections yet. Create your first section to start managing students and assignments.
        </p>
        <a href="create_section.php" 
           class="inline-flex items-center bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-3 px-8 rounded-lg shadow-lg transition duration-300 text-lg">
          <i class="fas fa-plus-circle mr-2"></i> Create Your First Section
        </a>
        <div class="mt-8 text-sm text-gray-500">
          <p><i class="fas fa-lightbulb mr-2"></i> Tip: Sections follow the format: <code class="font-mono bg-gray-100 px-2 py-1 rounded">Year-Program-Section</code></p>
        </div>
      </div>
    <?php else: ?>
      <!-- Sections Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($sections as $section): 
          // Determine badge color based on program
          $program_colors = [
            'BSCS' => 'bg-blue-100 text-blue-800 border-blue-200',
            'BSIT' => 'bg-green-100 text-green-800 border-green-200',
            'ACT' => 'bg-purple-100 text-purple-800 border-purple-200'
          ];
          $program_color = $program_colors[$section['course']] ?? 'bg-gray-100 text-gray-800 border-gray-200';
          
          // Determine year color
          $year_colors = ['bg-red-100 text-red-800', 'bg-orange-100 text-orange-800', 'bg-yellow-100 text-yellow-800', 'bg-indigo-100 text-indigo-800'];
          $year_color = $year_colors[$section['year_level'] - 1] ?? 'bg-gray-100 text-gray-800';
        ?>
        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-shadow duration-300">
          <!-- Section Header -->
          <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-5 border-b border-blue-200">
            <div class="flex justify-between items-start">
              <div>
                <div class="font-mono text-2xl font-bold text-gray-800 mb-1"><?php echo htmlspecialchars($section['section_code']); ?></div>
                <div class="flex flex-wrap gap-2 mt-2">
                  <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $program_color; ?> border">
                    <?php echo htmlspecialchars($section['course']); ?>
                  </span>
                  <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $year_color; ?>">
                    Year <?php echo $section['year_level']; ?>
                  </span>
                  <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                    Section <?php echo htmlspecialchars($section['section']); ?>
                  </span>
                </div>
              </div>
              <div class="bg-white rounded-lg p-2 shadow-sm">
                <div class="text-center">
                  <div class="text-2xl font-bold text-blue-600">
                    <?php 
                    // Get student count for this section
                    $count_query = "SELECT COUNT(*) as student_count FROM users WHERE course = ? AND year_level = ? AND section = ? AND role = 'student'";
                    $count_stmt = $conn->prepare($count_query);
                    $count_stmt->bind_param("sis", $section['course'], $section['year_level'], $section['section']);
                    $count_stmt->execute();
                    $count_result = $count_stmt->get_result();
                    $student_count = $count_result->fetch_assoc()['student_count'] ?? 0;
                    $count_stmt->close();
                    echo $student_count;
                    ?>
                  </div>
                  <div class="text-xs text-gray-600">Students</div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Section Details -->
          <div class="p-5">
            <div class="space-y-3">
              <div class="flex items-center text-gray-600">
                <i class="fas fa-user-tie w-5 mr-3 text-blue-500"></i>
                <div>
                  <div class="text-sm">Teacher</div>
                  <div class="font-medium">
                    <?php 
                    if ($role === 'teacher') {
                      echo "You";
                    } else {
                      echo htmlspecialchars($section['teacher_name'] ?? 'Not assigned');
                    }
                    ?>
                  </div>
                </div>
              </div>
              
              <div class="flex items-center text-gray-600">
                <i class="far fa-calendar w-5 mr-3 text-green-500"></i>
                <div>
                  <div class="text-sm">Created</div>
                  <div class="font-medium">
                    <?php echo date('M d, Y', strtotime($section['created_at'])); ?>
                  </div>
                </div>
              </div>
              
              <!-- REMOVED assigned_at section since column doesn't exist -->
            </div>
          </div>
          
 <!-- Action Buttons -->
<div class="bg-gray-50 p-4 border-t border-gray-200">
  <div class="flex justify-between">
    <a href="teacher_section_management.php?section_id=<?php echo $section['id']; ?>" 
       class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
      <i class="fas fa-cog mr-2"></i> Manage
    </a>
    
    <a href="view_section_students.php?section_id=<?php echo $section['id']; ?>" 
       class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
      <i class="fas fa-users mr-2"></i> View Students
    </a>
    
    <a href="#" 
       onclick="return confirm('Are you sure you want to delete this section?')" 
       class="inline-flex items-center bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
      <i class="fas fa-trash mr-2"></i> Delete
    </a>
  </div>
</div>
        </div>
        <?php endforeach; ?>
      </div>
      
      <!-- Summary Stats -->
      <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4">
        <?php
        // Calculate stats
        $program_counts = [];
        $year_counts = [];
        foreach ($sections as $section) {
            $program = $section['course'];
            $year = $section['year_level'];
            
            if (!isset($program_counts[$program])) $program_counts[$program] = 0;
            if (!isset($year_counts[$year])) $year_counts[$year] = 0;
            
            $program_counts[$program]++;
            $year_counts[$year]++;
        }
        
        // Display stats
        $stat_colors = ['bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-orange-500'];
        $stat_index = 0;
        
        // Total sections
        ?>
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-3xl font-bold text-blue-600"><?php echo count($sections); ?></div>
          <div class="text-gray-600 text-sm mt-1">Total Sections</div>
        </div>
        
        <?php foreach ($program_counts as $program => $count): ?>
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-3xl font-bold <?php echo $stat_colors[$stat_index % count($stat_colors)]; ?> text-white p-2 rounded-full inline-block"><?php echo $count; ?></div>
          <div class="text-gray-600 text-sm mt-2"><?php echo $program; ?> Sections</div>
        </div>
        <?php $stat_index++; endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  
  <script>
    // Simple confirmation for delete
    document.addEventListener('DOMContentLoaded', function() {
      const deleteButtons = document.querySelectorAll('a[onclick*="confirm"]');
      deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
          if (!confirm('Are you sure you want to delete this section? This action cannot be undone.')) {
            e.preventDefault();
          }
        });
      });
    });
  </script>
</body>
</html>
