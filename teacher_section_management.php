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
  header("Location: sections.php");
  exit();
}

// ✅ Get section details - UPDATED for new structure
$section_query = "
    SELECT s.*, 
           u.name as adviser_name,
           (SELECT COUNT(*) FROM users WHERE role='student' AND course=s.course AND year_level=s.year_level AND section=s.section) as student_count
    FROM sections s
    LEFT JOIN users u ON s.adviser_id = u.id
    WHERE s.id = ?
";
$stmt = $conn->prepare($section_query);
$stmt->bind_param("i", $section_id);
$stmt->execute();
$section_result = $stmt->get_result();

if ($section_result->num_rows === 0) {
  header("Location: sections.php");
  exit();
}

$section = $section_result->fetch_assoc();
$stmt->close();

// ✅ Verify teacher has access to this section
if ($role === 'teacher') {
  // Check if teacher is adviser OR teaches subjects in this section
  $access_check_query = "
      SELECT 1 
      FROM sections s
      LEFT JOIN teacher_subjects ts ON s.id = ts.section_id
      WHERE s.id = ? 
        AND (s.adviser_id = ? OR ts.teacher_id = ?)
      LIMIT 1
  ";
  $access_stmt = $conn->prepare($access_check_query);
  $access_stmt->bind_param("iii", $section_id, $user_id, $user_id);
  $access_stmt->execute();
  $access_result = $access_stmt->get_result();
  
  if ($access_result->num_rows === 0) {
    header("Location: sections.php");
    exit();
  }
  $access_stmt->close();
}

// ✅ Get teacher's subjects for this section (if any)
$teacher_subjects_query = "
    SELECT ts.*, sub.subject_code, sub.subject_name, sub.units
    FROM teacher_subjects ts
    JOIN subjects sub ON ts.subject_id = sub.id
    WHERE ts.section_id = ? AND ts.teacher_id = ?
    ORDER BY sub.subject_code
";
$subjects_stmt = $conn->prepare($teacher_subjects_query);
$subjects_stmt->bind_param("ii", $section_id, $user_id);
$subjects_stmt->execute();
$teacher_subjects_result = $subjects_stmt->get_result();
$teacher_subjects = [];
while ($row = $teacher_subjects_result->fetch_assoc()) {
    $teacher_subjects[] = $row;
}
$subjects_stmt->close();

// ✅ Get all students in this section
$students_query = "
  SELECT * FROM users 
  WHERE role = 'student' 
    AND course = ? 
    AND year_level = ? 
    AND section = ?
  ORDER BY name ASC
";
$stmt = $conn->prepare($students_query);
$stmt->bind_param("sis", $section['course'], $section['year_level'], $section['section']);
$stmt->execute();
$students_result = $stmt->get_result();
$students = [];
while ($row = $students_result->fetch_assoc()) {
  $students[] = $row;
}
$stmt->close();

// ✅ Get all subjects taught in this section (for all teachers)
$all_subjects_query = "
    SELECT DISTINCT 
        sub.id,
        sub.subject_code,
        sub.subject_name,
        sub.units,
        u.name as teacher_name,
        ts.schedule,
        ts.room
    FROM teacher_subjects ts
    JOIN subjects sub ON ts.subject_id = sub.id
    JOIN users u ON ts.teacher_id = u.id
    WHERE ts.section_id = ?
    ORDER BY sub.subject_code
";
$all_subjects_stmt = $conn->prepare($all_subjects_query);
$all_subjects_stmt->bind_param("i", $section_id);
$all_subjects_stmt->execute();
$all_subjects_result = $all_subjects_stmt->get_result();
$all_subjects = [];
while ($row = $all_subjects_result->fetch_assoc()) {
    $all_subjects[] = $row;
}
$all_subjects_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($section['section_code']); ?> Management | PLMUN LMS</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
  <?php include 'includes/header.php'; ?>
  
  <div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow-lg p-6 mb-6 text-white">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">
            <i class="fas fa-chalkboard-teacher mr-2"></i>
            <?php echo htmlspecialchars($section['section_code']); ?>
          </h1>
          <div class="flex flex-wrap gap-2 mt-2">
            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm">
              <?php echo htmlspecialchars($section['course']); ?>
            </span>
            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm">
              Year <?php echo $section['year_level']; ?>
            </span>
            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm">
              Section <?php echo htmlspecialchars($section['section']); ?>
            </span>
            <?php if ($section['adviser_name']): ?>
            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm">
              Adviser: <?php echo htmlspecialchars($section['adviser_name']); ?>
            </span>
            <?php endif; ?>
          </div>
        </div>
        <a href="sections.php" class="bg-white text-blue-600 px-4 py-2 rounded-lg hover:bg-gray-100 transition font-semibold">
          <i class="fas fa-arrow-left mr-2"></i>Back to Sections
        </a>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4 text-center">
        <div class="text-3xl font-bold text-blue-600"><?php echo $section['student_count']; ?></div>
        <div class="text-gray-600 text-sm mt-1">Total Students</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4 text-center">
        <div class="text-3xl font-bold text-green-600"><?php echo count($teacher_subjects); ?></div>
        <div class="text-gray-600 text-sm mt-1">Your Subjects</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4 text-center">
        <div class="text-3xl font-bold text-purple-600"><?php echo count($all_subjects); ?></div>
        <div class="text-gray-600 text-sm mt-1">Total Subjects</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4 text-center">
        <div class="text-3xl font-bold text-orange-600"><?php echo htmlspecialchars($section['course']); ?></div>
        <div class="text-gray-600 text-sm mt-1">Program</div>
      </div>
    </div>

    <!-- Management Options -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
      <a href="view_sections_students.php?section_id=<?php echo $section_id; ?>" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition text-center">
        <div class="text-3xl text-blue-600 mb-2">
          <i class="fas fa-users"></i>
        </div>
        <h3 class="font-bold text-gray-800">Students</h3>
        <p class="text-gray-600 text-sm mt-1">Manage students</p>
      </a>

      <?php if (!empty($teacher_subjects)): ?>
      <a href="section_assignments.php?section_id=<?php echo $section_id; ?>" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition text-center">
        <div class="text-3xl text-green-600 mb-2">
          <i class="fas fa-tasks"></i>
        </div>
        <h3 class="font-bold text-gray-800">Assignments</h3>
        <p class="text-gray-600 text-sm mt-1">Create assignments</p>
      </a>
      <?php endif; ?>

      <a href="section_announcements.php?section_id=<?php echo $section_id; ?>" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition text-center">
        <div class="text-3xl text-purple-600 mb-2">
          <i class="fas fa-bullhorn"></i>
        </div>
        <h3 class="font-bold text-gray-800">Announcements</h3>
        <p class="text-gray-600 text-sm mt-1">Post updates</p>
      </a>

      <?php if (!empty($teacher_subjects)): ?>
      <a href="section_grades.php?section_id=<?php echo $section_id; ?>" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition text-center">
        <div class="text-3xl text-yellow-600 mb-2">
          <i class="fas fa-graduation-cap"></i>
        </div>
        <h3 class="font-bold text-gray-800">Grades</h3>
        <p class="text-gray-600 text-sm mt-1">Manage grades</p>
      </a>

      <a href="section_attendance.php?section_id=<?php echo $section_id; ?>" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition text-center">
        <div class="text-3xl text-red-600 mb-2">
          <i class="fas fa-clipboard-check"></i>
        </div>
        <h3 class="font-bold text-gray-800">Attendance</h3>
        <p class="text-gray-600 text-sm mt-1">Take attendance</p>
      </a>
      <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Your Subjects in This Section -->
      <div class="bg-white rounded-lg shadow-lg">
        <div class="px-6 py-4 bg-gray-50 border-b">
          <h2 class="text-xl font-bold text-gray-800">
            <i class="fas fa-book-open text-green-600 mr-2"></i>Your Subjects in This Section
          </h2>
        </div>
        <div class="p-6">
          <?php if (empty($teacher_subjects)): ?>
            <div class="text-center py-8">
              <div class="text-5xl text-gray-300 mb-4">
                <i class="fas fa-book"></i>
              </div>
              <h3 class="text-lg font-bold text-gray-700 mb-2">No Subjects Assigned</h3>
              <p class="text-gray-600 mb-4">You are not teaching any subjects in this section.</p>
              <?php if ($role === 'dean' || $role === 'admin'): ?>
                <a href="manage_subjects.php" class="text-blue-600 hover:text-blue-800 font-semibold">
                  <i class="fas fa-plus-circle mr-1"></i>Assign Subjects
                </a>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="space-y-4">
              <?php foreach ($teacher_subjects as $subject): ?>
                <div class="border rounded-lg p-4 hover:bg-green-50 transition">
                  <div class="flex justify-between items-start mb-2">
                    <div>
                      <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($subject['subject_name']); ?></h3>
                      <p class="text-sm text-gray-600"><?php echo htmlspecialchars($subject['subject_code']); ?> • <?php echo $subject['units']; ?> units</p>
                    </div>
                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">
                      Your Subject
                    </span>
                  </div>
                  <?php if ($subject['schedule'] || $subject['room']): ?>
                  <div class="grid grid-cols-2 gap-2 text-sm text-gray-600">
                    <?php if ($subject['schedule']): ?>
                    <div>
                      <i class="fas fa-clock mr-1"></i>
                      <?php echo htmlspecialchars($subject['schedule']); ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($subject['room']): ?>
                    <div>
                      <i class="fas fa-door-open mr-1"></i>
                      <?php echo htmlspecialchars($subject['room']); ?>
                    </div>
                    <?php endif; ?>
                  </div>
                  <?php endif; ?>
                  <div class="mt-3 flex space-x-3">
                    <a href="manage_assignments.php?assignment_id=<?php echo $subject['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                      <i class="fas fa-tasks mr-1"></i>Assignments
                    </a>
                    <a href="manage_grades.php?assignment_id=<?php echo $subject['id']; ?>" class="text-purple-600 hover:text-purple-800 text-sm font-semibold">
                      <i class="fas fa-graduation-cap mr-1"></i>Grades
                    </a>
                    <a href="subject_attendance.php?assignment_id=<?php echo $subject['id']; ?>" class="text-orange-600 hover:text-orange-800 text-sm font-semibold">
                      <i class="fas fa-clipboard-check mr-1"></i>Attendance
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- All Subjects in This Section -->
      <div class="bg-white rounded-lg shadow-lg">
        <div class="px-6 py-4 bg-gray-50 border-b">
          <h2 class="text-xl font-bold text-gray-800">
            <i class="fas fa-book text-blue-600 mr-2"></i>All Subjects in This Section
          </h2>
        </div>
        <div class="p-6">
          <?php if (empty($all_subjects)): ?>
            <div class="text-center py-8">
              <div class="text-5xl text-gray-300 mb-4">
                <i class="fas fa-book"></i>
              </div>
              <h3 class="text-lg font-bold text-gray-700 mb-2">No Subjects</h3>
              <p class="text-gray-600">No subjects are being taught in this section.</p>
            </div>
          <?php else: ?>
            <div class="space-y-4">
              <?php foreach ($all_subjects as $subject): 
                $is_your_subject = false;
                foreach ($teacher_subjects as $ts) {
                  if ($ts['subject_id'] == $subject['id']) {
                    $is_your_subject = true;
                    break;
                  }
                }
              ?>
                <div class="border rounded-lg p-4 hover:bg-blue-50 transition <?php echo $is_your_subject ? 'border-l-4 border-green-500' : ''; ?>">
                  <div class="flex justify-between items-start mb-2">
                    <div>
                      <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($subject['subject_name']); ?></h3>
                      <p class="text-sm text-gray-600"><?php echo htmlspecialchars($subject['subject_code']); ?> • <?php echo $subject['units']; ?> units</p>
                    </div>
                    <?php if ($is_your_subject): ?>
                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">
                      You teach this
                    </span>
                    <?php endif; ?>
                  </div>
                  <div class="text-sm text-gray-600">
                    <i class="fas fa-chalkboard-teacher mr-1"></i>
                    Teacher: <?php echo htmlspecialchars($subject['teacher_name']); ?>
                  </div>
                  <?php if ($subject['schedule'] || $subject['room']): ?>
                  <div class="grid grid-cols-2 gap-2 text-sm text-gray-600 mt-2">
                    <?php if ($subject['schedule']): ?>
                    <div>
                      <i class="fas fa-clock mr-1"></i>
                      <?php echo htmlspecialchars($subject['schedule']); ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($subject['room']): ?>
                    <div>
                      <i class="fas fa-door-open mr-1"></i>
                      <?php echo htmlspecialchars($subject['room']); ?>
                    </div>
                    <?php endif; ?>
                  </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Student Quick View -->
    <div class="bg-white rounded-lg shadow-lg mt-6">
      <div class="px-6 py-4 bg-gray-50 border-b flex justify-between items-center">
        <h2 class="text-xl font-bold text-gray-800">
          <i class="fas fa-user-graduate text-blue-600 mr-2"></i>Students (<?php echo count($students); ?>)
        </h2>
        <a href="view_sections_students.php?section_id=<?php echo $section_id; ?>" class="text-blue-600 hover:text-blue-800 font-semibold">
          <i class="fas fa-list mr-1"></i>View All
        </a>
      </div>
      <div class="p-6">
        <?php if (empty($students)): ?>
          <div class="text-center py-8">
            <div class="text-6xl mb-4 text-gray-300">
              <i class="fas fa-inbox"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Students Yet</h3>
            <p class="text-gray-600">This section doesn't have any enrolled students at the moment.</p>
          </div>
        <?php else: ?>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach (array_slice($students, 0, 6) as $student): ?>
              <div class="border rounded-lg p-4 hover:bg-gray-50 transition">
                <div class="flex items-center mb-3">
                  <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                    <span class="text-blue-600 font-bold text-lg">
                      <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                    </span>
                  </div>
                  <div>
                    <h4 class="font-bold text-gray-800"><?php echo htmlspecialchars($student['name']); ?></h4>
                    <p class="text-sm text-gray-600 truncate"><?php echo htmlspecialchars($student['email']); ?></p>
                  </div>
                </div>
                <div class="flex space-x-2">
                  <a href="student_profile.php?id=<?php echo $student['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm">
                    <i class="fas fa-eye mr-1"></i>Profile
                  </a>
                  <a href="student_grades.php?student_id=<?php echo $student['id']; ?>" class="text-green-600 hover:text-green-800 text-sm">
                    <i class="fas fa-chart-line mr-1"></i>Grades
                  </a>
                  <a href="student_attendance.php?student_id=<?php echo $student['id']; ?>" class="text-orange-600 hover:text-orange-800 text-sm">
                    <i class="fas fa-calendar-check mr-1"></i>Attendance
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>
