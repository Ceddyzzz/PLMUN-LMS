<?php
session_start();
include 'includes/db_connect.php';

// Check if user is logged in and has proper role
if (!isset($_SESSION['user']) || !in_array($_SESSION['role'], ['teacher', 'dean', 'program_chair', 'admin'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$search = $_GET['search'] ?? '';
$program_filter = $_GET['program'] ?? '';
$year_filter = $_GET['year'] ?? '';

// Get all sections for this user
if ($role === 'teacher') {
  // SIMPLIFIED QUERY - removed assigned_at
  $sections_query = "
    SELECT s.*
    FROM sections s
    WHERE s.teacher_id = ?
  ";
  $params = [$user_id];
  $types = "i";
} else {
  // For admins/deans - show all sections
  $sections_query = "
    SELECT s.*, u.name as teacher_name
    FROM sections s
    LEFT JOIN users u ON s.teacher_id = u.id
    WHERE 1=1
  ";
  $params = [];
  $types = "";
}

// Add search filter
if (!empty($search)) {
  $sections_query .= " AND (s.section_code LIKE ? OR s.course LIKE ?)";
  $search_term = "%{$search}%";
  $params[] = $search_term;
  $params[] = $search_term;
  $types .= "ss";
}

// Add program filter
if (!empty($program_filter)) {
  $sections_query .= " AND s.course = ?";
  $params[] = $program_filter;
  $types .= "s";
}

// Add year filter
if (!empty($year_filter)) {
  $sections_query .= " AND s.year_level = ?";
  $params[] = $year_filter;
  $types .= "i";
}

// Order by
$sections_query .= " ORDER BY s.year_level, s.course, s.section";

// Prepare and execute
$stmt = $conn->prepare($sections_query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error . "<br>Query: " . $sections_query);
}

if (!empty($params)) {
    if (!$stmt->bind_param($types, ...$params)) {
        die("Bind param failed: " . $stmt->error);
    }
}

if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$sections_result = $stmt->get_result();
$sections = [];
while ($row = $sections_result->fetch_assoc()) {
  $sections[] = $row;
}
$stmt->close();

// Get stats for cards - SIMPLIFIED
$stats_query = "
  SELECT 
    COUNT(*) as total_sections,
    (SELECT COUNT(*) FROM users WHERE role = 'student') as total_students,
    COUNT(DISTINCT course) as total_programs
  FROM sections
  " . ($role === 'teacher' ? " WHERE teacher_id = ?" : "");
  
$stats_stmt = $conn->prepare($stats_query);
if (!$stats_stmt) {
    die("Stats prepare failed: " . $conn->error);
}

if ($role === 'teacher') {
  if (!$stats_stmt->bind_param("i", $user_id)) {
      die("Stats bind param failed: " . $stats_stmt->error);
  }
}

if (!$stats_stmt->execute()) {
    die("Stats execute failed: " . $stats_stmt->error);
}

$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();

// Get student count for each section
foreach ($sections as &$section) {
  $count_query = "SELECT COUNT(*) as student_count FROM users 
                  WHERE course = ? AND year_level = ? AND section = ? AND role = 'student'";
  $count_stmt = $conn->prepare($count_query);
  $count_stmt->bind_param("sis", $section['course'], $section['year_level'], $section['section']);
  $count_stmt->execute();
  $count_result = $count_stmt->get_result();
  $student_count = $count_result->fetch_assoc()['student_count'] ?? 0;
  $section['student_count'] = $student_count;
  $count_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Sections | PLMUN LMS</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .section-code {
      font-family: 'Courier New', monospace;
    }
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.5);
    }
    .modal.active {
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .modal-content {
      background-color: #fefefe;
      border-radius: 8px;
      max-width: 90%;
      max-height: 90vh;
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }
    .modal-body {
      overflow-y: auto;
      max-height: calc(90vh - 200px);
    }
  </style>
</head>
<body class="bg-gray-100">
  <?php 
  $header_path = 'includes/header.php';
  if (file_exists($header_path)) {
    include $header_path;
  }
  ?>
  
  <div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Page Header -->
    <div class="mb-8">
      <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-chalkboard-teacher text-blue-500 mr-3"></i>Manage Sections
          </h1>
          <p class="text-gray-600 mt-1">Create and manage student sections for CITCS programs</p>
        </div>
        <div class="mt-4 md:mt-0">
          <a href="create_section.php" 
             class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg shadow transition">
            <i class="fas fa-plus-circle mr-2"></i> Create New Section
          </a>
        </div>
      </div>
      
      <!-- Info Banner -->
      <div class="bg-blue-100 border border-blue-300 rounded-lg p-4 mb-6">
        <div class="flex items-start">
          <div class="bg-blue-500 text-white p-2 rounded-lg mr-3">
            <i class="fas fa-info-circle"></i>
          </div>
          <div>
            <h3 class="font-bold text-blue-800">Section Format</h3>
            <p class="text-blue-700 text-sm mt-1">
              <span class="font-mono font-bold">Year-Program-Section</span> | 
              Example: <span class="font-mono">1-BSCS-A</span> (1st Year Computer Science, Section A)
            </p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow p-6 text-center">
        <div class="text-3xl font-bold text-blue-600"><?php echo $stats['total_sections'] ?? 0; ?></div>
        <div class="text-gray-600">Total Sections</div>
      </div>
      
      <div class="bg-white rounded-lg shadow p-6 text-center">
        <div class="text-3xl font-bold text-green-600"><?php echo $stats['total_students'] ?? 0; ?></div>
        <div class="text-gray-600">Total Students</div>
      </div>
      
      <div class="bg-white rounded-lg shadow p-6 text-center">
        <div class="text-3xl font-bold text-purple-600"><?php echo $stats['total_programs'] ?? 0; ?></div>
        <div class="text-gray-600">Programs</div>
      </div>
    </div>
    
    <!-- Search Box -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <form method="GET" action="" class="flex space-x-4">
        <div class="flex-grow">
          <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                 placeholder="Search sections by code or program..." 
                 class="w-full border border-gray-300 rounded-lg px-4 py-2">
        </div>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium">
          Search
        </button>
        <?php if (!empty($search)): ?>
        <a href="sections.php" class="bg-gray-200 text-gray-800 px-6 py-2 rounded-lg font-medium">
          Clear
        </a>
        <?php endif; ?>
      </form>
    </div>
    
    <!-- Sections List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-bold text-gray-800">
          <?php echo $role === 'teacher' ? 'My Sections' : 'All Sections'; ?>
          <span class="text-sm font-normal text-gray-600 ml-2">(<?php echo count($sections); ?> found)</span>
        </h2>
      </div>
      
      <?php if (empty($sections)): ?>
        <div class="p-8 text-center">
          <div class="text-6xl mb-4 text-gray-300">
            <i class="fas fa-chalkboard"></i>
          </div>
          <h3 class="text-xl font-bold text-gray-700 mb-3">
            <?php echo !empty($search) ? 'No sections found' : 'No sections yet'; ?>
          </h3>
          <p class="text-gray-600 mb-6">
            <?php echo !empty($search) ? 'Try a different search term' : 'Create your first section to get started'; ?>
          </p>
          <a href="create_section.php" 
             class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg">
            <i class="fas fa-plus-circle mr-2"></i> Create New Section
          </a>
        </div>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Section Code</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Program</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Year</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Section</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Students</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Created</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <?php foreach ($sections as $section): 
                $program_bg = [
                  'BSCS' => 'bg-blue-100 text-blue-800',
                  'BSIT' => 'bg-green-100 text-green-800',
                  'ACT' => 'bg-purple-100 text-purple-800'
                ];
                $bg_class = $program_bg[$section['course']] ?? 'bg-gray-100 text-gray-800';
              ?>
              <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                  <div class="font-bold text-gray-800 section-code"><?php echo htmlspecialchars($section['section_code']); ?></div>
                </td>
                <td class="px-6 py-4">
                  <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $bg_class; ?>">
                    <?php echo htmlspecialchars($section['course']); ?>
                  </span>
                </td>
                <td class="px-6 py-4">
                  <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                    Year <?php echo $section['year_level']; ?>
                  </span>
                </td>
                <td class="px-6 py-4 font-medium">
                  <?php echo htmlspecialchars($section['section']); ?>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center">
                    <i class="fas fa-user-graduate text-gray-400 mr-2"></i>
                    <span class="font-medium"><?php echo $section['student_count']; ?></span>
                  </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                  <?php echo date('M d, Y', strtotime($section['created_at'])); ?>
                </td>
                <td class="px-6 py-4">
                  <div class="flex space-x-2">
                    <button onclick="openGradeModal(<?php echo htmlspecialchars(json_encode($section)); ?>)" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-sm font-medium">
                      <i class="fas fa-cog mr-1"></i> Manage
                    </button>
                    <a href="view_sections_students.php?section_id=<?php echo $section['id']; ?>" 
                       class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-sm font-medium">
                      <i class="fas fa-users mr-1"></i> Students
                    </a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        
        <!-- Quick Summary -->
        <div class="px-6 py-4 border-t bg-gray-50">
          <div class="text-sm text-gray-600">
            Showing <?php echo count($sections); ?> section<?php echo count($sections) !== 1 ? 's' : ''; ?>
            <?php if ($role === 'teacher'): ?>
              • Assigned to you
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
    
    <!-- Quick Tips -->
    <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-6">
      <h3 class="font-bold text-yellow-800 mb-3 flex items-center">
        <i class="fas fa-lightbulb text-yellow-500 mr-2"></i> Quick Tips
      </h3>
      <ul class="text-yellow-700 space-y-2 text-sm">
        <li><i class="fas fa-check-circle mr-2"></i> Sections follow the format: <code class="font-mono bg-white/50 px-2 py-1 rounded">Year-Program-Section</code></li>
        <li><i class="fas fa-check-circle mr-2"></i> Example: <code class="font-mono">1-BSCS-A</code> = 1st Year Computer Science, Section A</li>
        <li><i class="fas fa-check-circle mr-2"></i> Each section can have multiple students enrolled</li>
        <li><i class="fas fa-check-circle mr-2"></i> Click "Manage" to view students and input their grades</li>
      </ul>
    </div>
  </div>
  
  <!-- Grade Management Modal -->
  <div id="gradeModal" class="modal">
    <div class="modal-content w-full max-w-5xl">
      <!-- Modal Header -->
      <div class="bg-blue-600 text-white px-6 py-4 flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-bold">Manage Student Grades</h2>
          <p class="text-blue-100 text-sm mt-1" id="modalSectionInfo"></p>
        </div>
        <button onclick="closeGradeModal()" class="text-white hover:text-gray-200 text-3xl font-bold">&times;</button>
      </div>
      
      <!-- Modal Body -->
      <div class="modal-body p-6">
        <div id="modalStudentList">
          <div class="text-center py-8">
            <i class="fas fa-spinner fa-spin text-4xl text-blue-500"></i>
            <p class="mt-4 text-gray-600">Loading students...</p>
          </div>
        </div>
      </div>
      
      <!-- Modal Footer -->
      <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
        <button onclick="closeGradeModal()" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-100 transition-colors">
          Cancel
        </button>
        <button onclick="saveGrades()" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
          <i class="fas fa-save mr-2"></i>Save Grades
        </button>
      </div>
    </div>
  </div>
  
  <script>
    let currentSection = null;
    let studentGrades = {};
    
    function openGradeModal(section) {
      currentSection = section;
      const modal = document.getElementById('gradeModal');
      const sectionInfo = document.getElementById('modalSectionInfo');
      
      sectionInfo.textContent = `${section.section_code} - ${section.course} (Year ${section.year_level})`;
      modal.classList.add('active');
      
      // Load students for this section
      loadStudents(section);
    }
    
    function closeGradeModal() {
      const modal = document.getElementById('gradeModal');
      modal.classList.remove('active');
      currentSection = null;
      studentGrades = {};
    }
    
    function loadStudents(section) {
      const studentList = document.getElementById('modalStudentList');
      
      // Make AJAX request to get students
      fetch('get_section_students.php?section_id=' + section.id)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            displayStudents(data.students);
          } else {
            studentList.innerHTML = `
              <div class="text-center py-8">
                <i class="fas fa-exclamation-circle text-4xl text-red-500"></i>
                <p class="mt-4 text-gray-600">${data.message || 'Error loading students'}</p>
              </div>
            `;
          }
        })
        .catch(error => {
          console.error('Error:', error);
          studentList.innerHTML = `
            <div class="text-center py-8">
              <i class="fas fa-exclamation-circle text-4xl text-red-500"></i>
              <p class="mt-4 text-gray-600">Error loading students. Please try again.</p>
            </div>
          `;
        });
    }
    
    function displayStudents(students) {
      const studentList = document.getElementById('modalStudentList');
      
      if (!students || students.length === 0) {
        studentList.innerHTML = `
          <div class="text-center py-8">
            <i class="fas fa-user-slash text-4xl text-gray-400"></i>
            <p class="mt-4 text-gray-600">No students enrolled in this section yet.</p>
          </div>
        `;
        return;
      }
      
      // Initialize student grades
      students.forEach(student => {
        studentGrades[student.id] = student.grade || '';
      });
      
      let html = `
        <div class="overflow-x-auto">
          <table class="w-full border-collapse">
            <thead>
              <tr class="bg-gray-100">
                <th class="border border-gray-300 px-4 py-3 text-left font-semibold text-gray-700">Student ID</th>
                <th class="border border-gray-300 px-4 py-3 text-left font-semibold text-gray-700">Student Name</th>
                <th class="border border-gray-300 px-4 py-3 text-left font-semibold text-gray-700">Email</th>
                <th class="border border-gray-300 px-4 py-3 text-left font-semibold text-gray-700">Current Grade</th>
                <th class="border border-gray-300 px-4 py-3 text-left font-semibold text-gray-700">New Grade</th>
              </tr>
            </thead>
            <tbody>
      `;
      
      students.forEach((student, index) => {
        const rowClass = index % 2 === 0 ? 'bg-white' : 'bg-gray-50';
        const gradeClass = student.grade ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600';
        
        html += `
          <tr class="${rowClass}">
            <td class="border border-gray-300 px-4 py-3">${student.student_id || student.id}</td>
            <td class="border border-gray-300 px-4 py-3 font-medium">${student.name}</td>
            <td class="border border-gray-300 px-4 py-3 text-sm text-gray-600">${student.email || 'N/A'}</td>
            <td class="border border-gray-300 px-4 py-3">
              <span class="px-3 py-1 rounded ${gradeClass}">
                ${student.grade || 'No grade yet'}
              </span>
            </td>
            <td class="border border-gray-300 px-4 py-3">
              <input 
                type="text" 
                id="grade_${student.id}"
                value="${student.grade || ''}"
                placeholder="Enter grade (e.g., 95, A, B+)"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                onchange="updateGrade(${student.id}, this.value)"
              />
            </td>
          </tr>
        `;
      });
      
      html += `
            </tbody>
          </table>
        </div>
      `;
      
      studentList.innerHTML = html;
    }
    
    function updateGrade(studentId, grade) {
      studentGrades[studentId] = grade;
    }
    
    function saveGrades() {
      if (!currentSection) {
        alert('No section selected');
        return;
      }
      
      // Prepare data to send
      const data = {
        section_id: currentSection.id,
        grades: studentGrades
      };
      
      // Show loading state
      const saveButton = event.target;
      const originalText = saveButton.innerHTML;
      saveButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
      saveButton.disabled = true;
      
      // Make AJAX request to save grades
      fetch('save_grades.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Grades saved successfully!');
          closeGradeModal();
        } else {
          alert('Error saving grades: ' + (data.message || 'Unknown error'));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error saving grades. Please try again.');
      })
      .finally(() => {
        saveButton.innerHTML = originalText;
        saveButton.disabled = false;
      });
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('gradeModal');
      if (event.target === modal) {
        closeGradeModal();
      }
    }
  </script>
</body>
</html>
