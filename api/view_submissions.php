<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: ../login.php");
  exit();
}

include '../includes/db_connect.php';

// Get current user info
$current_user_email = $_SESSION['user'];
$stmt = $conn->prepare("SELECT id, email, name, role FROM users WHERE email = ?");
$stmt->bind_param("s", $current_user_email);
$stmt->execute();
$result = $stmt->get_result();
$current_user = $result->fetch_assoc();
$user_role = $current_user['role'];
$stmt->close();

// Only teachers can view submissions
if ($user_role !== 'teacher') {
  header("Location: ../assignment.php");
  exit();
}

// Get assignment ID
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($assignment_id === 0) {
  header("Location: ../assignment.php");
  exit();
}

// Get assignment details
$stmt = $conn->prepare("SELECT * FROM assignments WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $assignment_id, $current_user['id']);
$stmt->execute();
$result = $stmt->get_result();
$assignment = $result->fetch_assoc();
$stmt->close();

if (!$assignment) {
  header("Location: ../assignment.php");
  exit();
}

// Get all submissions for this assignment
$stmt = $conn->prepare("
  SELECT s.*, u.name as student_name, u.email as student_email 
  FROM assignment_submissions s
  LEFT JOIN users u ON s.student_id = u.id
  WHERE s.assignment_id = ?
  ORDER BY s.submitted_at DESC
");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$result = $stmt->get_result();
$submissions = [];
while ($row = $result->fetch_assoc()) {
  $submissions[] = $row;
}
$stmt->close();

// Handle grading submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_submission'])) {
  $submission_id = intval($_POST['submission_id']);
  $grade = floatval($_POST['grade']);
  $feedback = $_POST['feedback'];
  
  $stmt = $conn->prepare("UPDATE assignment_submissions SET grade = ?, feedback = ? WHERE id = ? AND assignment_id = ?");
  $stmt->bind_param("dsii", $grade, $feedback, $submission_id, $assignment_id);
  
  if ($stmt->execute()) {
    $success_message = "Grade submitted successfully!";
    // Refresh submissions
    header("Location: view_submissions.php?id=" . $assignment_id . "&success=1");
    exit();
  }
  $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Submissions | PLMUN LMS</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
  
<header class="bg-blue-900 text-white p-4">
  <div class="max-w-7xl mx-auto flex justify-between items-center">
    <h1 class="text-xl font-bold">PLMUN LMS</h1>
    <ul class="flex space-x-6">
      <li><a href="/PLMUN%20LMS/dashboard.php" class="hover:text-yellow-300">Dashboard</a></li>
      <li><a href="/PLMUN%20LMS/announcement.php" class="hover:text-yellow-300">Announcements</a></li>
      <li><a href="/PLMUN%20LMS/chat.php" class="hover:text-yellow-300">Chat</a></li>
      <li><a href="/PLMUN%20LMS/assignment.php" class="hover:text-yellow-300">Assignments</a></li>
      <li><a href="/PLMUN%20LMS/calendar.php" class="hover:text-yellow-300">Calendar</a></li>
      <li><a href="/PLMUN%20LMS/e-books.php" class="hover:text-yellow-300">E-Books</a></li>
      <li><a href="/PLMUN%20LMS/quiz.php" class="hover:text-yellow-300">Quiz</a></li>
      <li><a href="/PLMUN%20LMS/logout.php" class="hover:text-yellow-300">Logout</a></li>
    </ul>
  </div>
</header>

  <main class="p-6 max-w-7xl mx-auto">
    <!-- Back Button -->
    <div class="mb-6">
      <a href="../assignment.php" class="text-blue-600 hover:text-blue-800 font-semibold">
        ← Back to Assignments
      </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
        ✅ Grade submitted successfully!
      </div>
    <?php endif; ?>

    <!-- Assignment Info -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <h2 class="text-3xl font-bold mb-4">📋 <?php echo htmlspecialchars($assignment['title']); ?></h2>
      <div class="grid grid-cols-3 gap-4 text-gray-700">
        <div>
          <span class="font-semibold">Course:</span> <?php echo htmlspecialchars($assignment['course']); ?>
        </div>
        <div>
          <span class="font-semibold">Points:</span> <?php echo $assignment['points']; ?>
        </div>
        <div>
          <span class="font-semibold">Due:</span> <?php echo date('M j, Y g:i A', strtotime($assignment['due_date'])); ?>
        </div>
      </div>
      <div class="mt-4">
        <span class="font-semibold">Total Submissions:</span> 
        <span class="bg-purple-500 text-white px-3 py-1 rounded-full text-sm font-bold ml-2">
          <?php echo count($submissions); ?>
        </span>
      </div>
    </div>

    <!-- Submissions List -->
    <h3 class="text-2xl font-bold mb-4">Student Submissions</h3>
    
    <?php if (count($submissions) > 0): ?>
      <div class="space-y-4">
        <?php foreach ($submissions as $submission): 
          $student_name = $submission['student_name'] ?: explode('@', $submission['student_email'])[0];
          $is_graded = $submission['grade'] !== null;
        ?>
        
        <div class="bg-white rounded-lg shadow p-6 border-l-4 <?php echo $is_graded ? 'border-green-500' : 'border-yellow-500'; ?>">
          <div class="flex justify-between items-start mb-4">
            <div class="flex-1">
              <div class="flex items-center space-x-3 mb-2">
                <h4 class="text-xl font-bold"><?php echo htmlspecialchars($student_name); ?></h4>
                <?php if ($is_graded): ?>
                  <span class="bg-green-500 text-white px-3 py-1 rounded-full text-xs font-bold">GRADED</span>
                <?php else: ?>
                  <span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-xs font-bold">PENDING</span>
                <?php endif; ?>
              </div>
              
              <p class="text-sm text-gray-600 mb-3">
                📧 <?php echo htmlspecialchars($submission['student_email']); ?> • 
                📅 Submitted: <?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?>
              </p>
              
              <?php if ($submission['submission_text']): ?>
                <div class="bg-gray-50 p-4 rounded-lg mb-3">
                  <p class="font-semibold mb-2">Submission:</p>
                  <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?></p>
                </div>
              <?php endif; ?>
              
              <?php if ($submission['file_path']): ?>
                <div class="mb-3">
                  <a href="../<?php echo htmlspecialchars($submission['file_path']); ?>" 
                     target="_blank"
                     class="text-blue-600 hover:text-blue-800 font-semibold">
                    📎 Download Submitted File
                  </a>
                </div>
              <?php endif; ?>
              
              <?php if ($is_graded): ?>
                <div class="bg-green-50 p-4 rounded-lg">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-2xl font-bold text-green-700">
                      Grade: <?php echo $submission['grade']; ?>/<?php echo $assignment['points']; ?>
                    </span>
                    <span class="text-green-700 font-semibold">
                      <?php echo round(($submission['grade'] / $assignment['points']) * 100, 1); ?>%
                    </span>
                  </div>
                  <?php if ($submission['feedback']): ?>
                    <p class="text-sm text-gray-700">
                      <strong>Feedback:</strong> <?php echo htmlspecialchars($submission['feedback']); ?>
                    </p>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
          
          <div class="pt-4 border-t">
            <button onclick="showGradeModal(<?php echo $submission['id']; ?>, '<?php echo htmlspecialchars($student_name); ?>', <?php echo $submission['grade'] ?? 0; ?>, '<?php echo htmlspecialchars($submission['feedback'] ?? ''); ?>')" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-semibold">
              <?php echo $is_graded ? '✏️ Edit Grade' : '📊 Grade Submission'; ?>
            </button>
          </div>
        </div>
        
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="bg-white rounded-lg shadow p-12 text-center">
        <div class="text-6xl mb-4">📭</div>
        <h3 class="text-2xl font-bold mb-2">No Submissions Yet</h3>
        <p class="text-gray-600">Students haven't submitted their work for this assignment yet.</p>
      </div>
    <?php endif; ?>

  </main>

  <!-- Grade Modal -->
  <div id="gradeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-8 max-w-2xl w-full mx-4">
      <h3 class="text-2xl font-bold mb-4">📊 Grade Submission</h3>
      <form method="POST">
        <input type="hidden" name="grade_submission" value="1">
        <input type="hidden" name="submission_id" id="submission_id">
        
        <div class="mb-4">
          <p class="text-gray-700 font-semibold mb-2">Student: <span id="student_name"></span></p>
        </div>
        
        <div class="mb-4">
          <label class="block text-sm font-semibold mb-2">Grade (out of <?php echo $assignment['points']; ?> points)</label>
          <input type="number" name="grade" id="grade_input" min="0" max="<?php echo $assignment['points']; ?>" step="0.5" required 
                 class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div class="mb-4">
          <label class="block text-sm font-semibold mb-2">Feedback (optional)</label>
          <textarea name="feedback" id="feedback_input" rows="4" 
                    class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Provide feedback to the student..."></textarea>
        </div>
        
        <div class="flex space-x-3">
          <button type="submit" 
                  class="flex-1 bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 font-semibold">
            Submit Grade
          </button>
          <button type="button" onclick="hideGradeModal()" 
                  class="px-6 bg-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-400 font-semibold">
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function showGradeModal(submissionId, studentName, currentGrade, currentFeedback) {
      document.getElementById('submission_id').value = submissionId;
      document.getElementById('student_name').textContent = studentName;
      document.getElementById('grade_input').value = currentGrade || '';
      document.getElementById('feedback_input').value = currentFeedback || '';
      document.getElementById('gradeModal').classList.remove('hidden');
    }
    
    function hideGradeModal() {
      document.getElementById('gradeModal').classList.add('hidden');
    }
    
    // Close modal when clicking outside
    document.getElementById('gradeModal').addEventListener('click', function(e) {
      if (e.target === this) {
        hideGradeModal();
      }
    });
  </script>
</body>
</html>
