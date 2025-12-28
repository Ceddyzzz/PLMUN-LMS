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
$section_id = $_GET['section_id'] ?? null;

if (!$section_id) {
  header("Location: sections.php"); // FIXED: Changed from teachers_section.php
  exit();
}

// ✅ Get section details
$section_query = "SELECT * FROM sections WHERE id = ?";
$stmt = $conn->prepare($section_query);
$stmt->bind_param("i", $section_id);
$stmt->execute();
$section_result = $stmt->get_result();

if ($section_result->num_rows === 0) {
  header("Location: sections.php"); // FIXED: Changed from teachers_section.php
  exit();
}

$section = $section_result->fetch_assoc();
$stmt->close();

// ✅ Verify teacher has access to this section (unless dean/program_chair/admin)
if ($role === 'teacher') {
  // FIXED: Check teacher_id directly in sections table
  if ($section['teacher_id'] != $user_id) {
    header("Location: sections.php");
    exit();
  }
}

// ✅ Get all students in this section
$students_query = "
  SELECT id, name, email, course, year_level, section 
  FROM users 
  WHERE role = 'student' 
    AND course = ? 
    AND year_level = ? 
    AND section = ?
  ORDER BY name ASC
";

// Debug: Show the query and parameters
error_log("Students Query: " . $students_query);
error_log("Params: course=" . $section['course'] . ", year=" . $section['year_level'] . ", section=" . $section['section']);

$stmt = $conn->prepare($students_query);

if (!$stmt) {
    // Log the error and show user-friendly message
    error_log("SQL Prepare Error: " . $conn->error);
    die("Database error. Please contact administrator. Error: " . htmlspecialchars($conn->error));
}

if (!$stmt->bind_param("sis", $section['course'], $section['year_level'], $section['section'])) {
    error_log("Bind Param Error: " . $stmt->error);
    die("Database parameter error.");
}

if (!$stmt->execute()) {
    error_log("Execute Error: " . $stmt->error);
    die("Failed to load student data.");
}

$students_result = $stmt->get_result();
$students = [];
while ($row = $students_result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($section['section_code']); ?> Students | PLMUN LMS</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
  <?php include 'includes/header.php'; ?>
  
  <div class="container mx-auto px-4 py-8 max-w-6xl">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow-lg p-6 mb-6 text-white">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">
            <i class="fas fa-users mr-2"></i>
            <?php echo htmlspecialchars($section['section_code']); ?>
          </h1>
          <p class="mt-2">
            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm">
              <?php echo htmlspecialchars($section['course']); ?>
            </span>
            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm ml-2">
              Year <?php echo $section['year_level']; ?>
            </span>
            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm ml-2">
              Section <?php echo htmlspecialchars($section['section']); ?>
            </span>
          </p>
        </div>
        <div class="space-x-2">
          <a href="teacher_section_management.php?section_id=<?php echo $section_id; ?>" 
             class="bg-white text-blue-600 px-4 py-2 rounded-lg hover:bg-gray-100 transition font-semibold">
            <i class="fas fa-cog mr-2"></i>Manage Section
          </a>
          <a href="sections.php" 
             class="bg-white bg-opacity-20 text-white px-4 py-2 rounded-lg hover:bg-opacity-30 transition font-semibold">
            <i class="fas fa-arrow-left mr-2"></i>Back
          </a>
        </div>
      </div>
    </div>

    <!-- Student Count -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-bold text-gray-800">
            <i class="fas fa-user-graduate text-blue-600 mr-2"></i>Class Roster
          </h2>
          <p class="text-gray-600 mt-1">Total Students: <strong class="text-blue-600"><?php echo count($students); ?></strong></p>
        </div>
        <div class="space-x-2">
          <button onclick="window.print()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
            <i class="fas fa-print mr-2"></i>Print List
          </button>
          <button onclick="exportToCSV()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
            <i class="fas fa-file-csv mr-2"></i>Export CSV
          </button>
        </div>
      </div>
    </div>

    <!-- Students Table -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
      <?php if (empty($students)): ?>
        <div class="p-8 text-center">
          <div class="text-6xl mb-4 text-gray-300">
            <i class="fas fa-inbox"></i>
          </div>
          <h3 class="text-xl font-bold text-gray-700 mb-2">No Students Yet</h3>
          <p class="text-gray-600">This section doesn't have any enrolled students at the moment.</p>
          <p class="text-sm text-gray-500 mt-2">Students will appear here once they are assigned to this section.</p>
        </div>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="w-full" id="studentsTable">
            <thead class="bg-gray-50 border-b-2 border-gray-200">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">#</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Student Name</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Section</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <?php 
              $counter = 1;
              foreach ($students as $student): 
              ?>
                <tr class="hover:bg-gray-50 transition">
                  <td class="px-6 py-4 text-sm text-gray-600"><?php echo $counter++; ?></td>
                  <td class="px-6 py-4">
                    <div class="flex items-center">
                      <div class="h-10 w-10 flex-shrink-0">
                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                          <span class="text-blue-600 font-semibold">
                            <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                          </span>
                        </div>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-semibold text-gray-900">
                          <?php echo htmlspecialchars($student['name']); ?>
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600">
                    <?php echo htmlspecialchars($student['email']); ?>
                  </td>
                  <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                      <?php echo htmlspecialchars($section['section_code']); ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm">
                    <a href="student_profile.php?id=<?php echo $student['id']; ?>" 
                       class="text-blue-600 hover:text-blue-800 font-semibold">
                      <i class="fas fa-eye mr-1"></i>View Profile
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
      <div class="bg-white rounded-lg shadow p-4 text-center">
        <div class="text-3xl font-bold text-blue-600"><?php echo count($students); ?></div>
        <div class="text-gray-600 text-sm mt-1">Total Enrolled</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4 text-center">
        <div class="text-3xl font-bold text-green-600">
          <?php echo count($students); ?>
        </div>
        <div class="text-gray-600 text-sm mt-1">Active Students</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4 text-center">
        <div class="text-3xl font-bold text-purple-600"><?php echo htmlspecialchars($section['course']); ?></div>
        <div class="text-gray-600 text-sm mt-1">Program</div>
      </div>
    </div>
  </div>

  <script>
    // Export to CSV function
    function exportToCSV() {
      const table = document.getElementById('studentsTable');
      if (!table) {
        alert('No students to export');
        return;
      }
      
      const rows = table.querySelectorAll('tr');
      let csv = [];
      
      for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length - 1; j++) { // Exclude last column (Actions)
          let text = cols[j].innerText.replace(/\s+/g, ' ').trim();
          row.push('"' + text + '"');
        }
        csv.push(row.join(','));
      }
      
      const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
      const downloadLink = document.createElement('a');
      downloadLink.download = '<?php echo $section['section_code']; ?>_students.csv';
      downloadLink.href = window.URL.createObjectURL(csvFile);
      downloadLink.style.display = 'none';
      document.body.appendChild(downloadLink);
      downloadLink.click();
      document.body.removeChild(downloadLink);
    }
  </script>

  <style>
    @media print {
      body * {
        visibility: hidden;
      }
      .container, .container * {
        visibility: visible;
      }
      .container {
        position: absolute;
        left: 0;
        top: 0;
      }
      button {
        display: none !important;
      }
    }
  </style>
</body>
</html>
