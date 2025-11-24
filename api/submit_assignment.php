<?php
// Process form submission FIRST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assignment_id'])) {
  session_start();
  
  if (!isset($_SESSION['user'])) {
    die("Not authenticated");
  }
  
  include 'includes/db_connect.php';
  
  // Get current user
  $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
  $stmt->bind_param("s", $_SESSION['user']);
  $stmt->execute();
  $result = $stmt->get_result();
  $current_user = $result->fetch_assoc();
  $user_id = $current_user['id'];
  $stmt->close();
  
  // Get form data
  $assignment_id = (int)$_POST['assignment_id'];
  $text_response = trim($_POST['text_response'] ?? '');
  $notes = trim($_POST['notes'] ?? '');
  
  // Handle file upload
  $file_path = null;
  if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/submissions/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
      mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($_FILES['submission_file']['name'], PATHINFO_EXTENSION);
    $file_name = 'submission_' . $user_id . '_' . $assignment_id . '_' . time() . '.' . $file_extension;
    $target_path = $upload_dir . $file_name;
    
    // Validate file size (10MB max)
    if ($_FILES['submission_file']['size'] > 10 * 1024 * 1024) {
      header("Location: submit_assignment.php?id=" . $assignment_id . "&error=filesize");
      exit();
    }
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $target_path)) {
      $file_path = $target_path;
    }
  }
  
  // Validate: must have either text or file
  if (empty($text_response) && empty($file_path)) {
    header("Location: submit_assignment.php?id=" . $assignment_id . "&error=empty");
    exit();
  }
  
  // Insert submission into database
  $stmt = $conn->prepare("INSERT INTO assignment_submissions 
                          (assignment_id, student_id, text_response, file_path, notes, submitted_at) 
                          VALUES (?, ?, ?, ?, ?, NOW())");
  $stmt->bind_param("iisss", $assignment_id, $user_id, $text_response, $file_path, $notes);
  
  if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    
    // Redirect to assignments page with success message
    header("Location: assignment.php?success=submitted");
    exit();
  } else {
    $stmt->close();
    $conn->close();
    
    header("Location: submit_assignment.php?id=" . $assignment_id . "&error=database");
    exit();
  }
}

// If not POST request, continue to display the form below
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}

include 'includes/db_connect.php';

// Get assignment ID
$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get current user
$stmt = $conn->prepare("SELECT id, email, name, role FROM users WHERE email = ?");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$result = $stmt->get_result();
$current_user = $result->fetch_assoc();
$user_id = $current_user['id'];
$stmt->close();

// Only students can submit
if ($current_user['role'] !== 'student') {
  die("Only students can submit assignments");
}

// Get assignment details
$stmt = $conn->prepare("SELECT a.*, u.name as teacher_name, u.email as teacher_email 
                        FROM assignments a 
                        LEFT JOIN users u ON a.teacher_id = u.id 
                        WHERE a.id = ?");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$result = $stmt->get_result();
$assignment = $result->fetch_assoc();
$stmt->close();

if (!$assignment) {
  die("Assignment not found");
}

// Check if already submitted
$stmt = $conn->prepare("SELECT * FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
$stmt->bind_param("ii", $assignment_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$existing_submission = $result->fetch_assoc();
$stmt->close();

$due_date = new DateTime($assignment['due_date']);
$now = new DateTime();
$is_overdue = $due_date < $now;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Submit Assignment | PLMUN LMS</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
  <?php include 'includes/header.php'; ?>

  <main class="p-6 max-w-4xl mx-auto">
    <div class="mb-6">
      <a href="assignment.php" class="text-blue-600 hover:underline">← Back to Assignments</a>
    </div>

    <?php if (isset($_GET['error'])): ?>
      <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <p class="text-red-800 font-semibold">
          <?php 
            if ($_GET['error'] === 'filesize') echo '⚠️ File is too large! Maximum size is 10MB.';
            elseif ($_GET['error'] === 'empty') echo '⚠️ Please provide either a text response or upload a file.';
            elseif ($_GET['error'] === 'database') echo '⚠️ Database error. Please try again.';
            else echo '⚠️ An error occurred. Please try again.';
          ?>
        </p>
      </div>
    <?php endif; ?>

    <!-- Assignment Details -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <h2 class="text-3xl font-bold mb-4">📤 Submit Assignment</h2>
      
      <div class="border-l-4 border-blue-500 pl-4 mb-6">
        <h3 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($assignment['title']); ?></h3>
        <p class="text-gray-700 mb-3"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
        
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <span class="font-semibold">Course:</span> 
            <?php echo htmlspecialchars($assignment['course']); ?>
          </div>
          <div>
            <span class="font-semibold">Teacher:</span> 
            <?php echo htmlspecialchars($assignment['teacher_name'] ?: $assignment['teacher_email']); ?>
          </div>
          <div>
            <span class="font-semibold">Points:</span> 
            <?php echo $assignment['points']; ?>
          </div>
          <div class="<?php echo $is_overdue ? 'text-red-600 font-bold' : ''; ?>">
            <span class="font-semibold">Due:</span> 
            <?php echo $due_date->format('M j, Y g:i A'); ?>
            <?php if ($is_overdue): ?>
              <span class="ml-2 bg-red-100 text-red-800 px-2 py-1 rounded text-xs">OVERDUE</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?php if ($existing_submission): ?>
        <!-- Already Submitted -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
          <h4 class="text-lg font-bold mb-3">✅ Already Submitted</h4>
          <p class="text-sm text-gray-700 mb-3">
            <strong>Submitted on:</strong> <?php echo date('M j, Y g:i A', strtotime($existing_submission['submitted_at'])); ?>
          </p>
          
          <?php if ($existing_submission['file_path']): ?>
            <p class="text-sm text-gray-700 mb-3">
              <strong>File:</strong> 
              <a href="<?php echo htmlspecialchars($existing_submission['file_path']); ?>" 
                 class="text-blue-600 hover:underline" target="_blank">
                📎 View Submission
              </a>
            </p>
          <?php endif; ?>

          <?php if ($existing_submission['grade'] !== null): ?>
            <!-- Graded -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mt-4">
              <h5 class="font-bold text-green-800 mb-2">🎉 Graded</h5>
              <p class="text-2xl font-bold text-green-700 mb-2">
                <?php echo $existing_submission['grade']; ?> / <?php echo $assignment['points']; ?>
                <span class="text-lg">
                  (<?php echo round(($existing_submission['grade'] / $assignment['points']) * 100, 1); ?>%)
                </span>
              </p>
              <?php if ($existing_submission['feedback']): ?>
                <p class="text-sm text-gray-700">
                  <strong>Feedback:</strong> <?php echo nl2br(htmlspecialchars($existing_submission['feedback'])); ?>
                </p>
              <?php endif; ?>
              <p class="text-xs text-gray-600 mt-2">
                Graded on: <?php echo date('M j, Y g:i A', strtotime($existing_submission['graded_at'])); ?>
              </p>
            </div>
          <?php else: ?>
            <!-- Awaiting Grade -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-4">
              <p class="text-yellow-800">⏰ Awaiting grading from teacher</p>
            </div>
          <?php endif; ?>

          <div class="mt-4 p-4 bg-gray-50 rounded">
            <p class="text-sm text-gray-600">
              💡 <strong>Note:</strong> You have already submitted this assignment. 
              If you need to resubmit, please contact your teacher.
            </p>
          </div>
        </div>

      <?php else: ?>
        <!-- Submission Form -->
        <div class="bg-gray-50 rounded-lg p-6">
          <h4 class="text-lg font-bold mb-4">📝 Your Submission</h4>

          <?php if ($is_overdue): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
              <p class="text-red-800 font-semibold">⚠️ Warning: This assignment is overdue!</p>
              <p class="text-sm text-red-700 mt-1">
                You can still submit, but it may be marked as late by your teacher.
              </p>
            </div>
          <?php endif; ?>

          <form action="submit_assignment.php" method="POST" enctype="multipart/form-data" id="submissionForm">
            <input type="hidden" name="assignment_id" value="<?php echo $assignment_id; ?>">

            <!-- Text Response -->
            <div class="mb-6">
              <label class="block text-sm font-semibold mb-2">
                Written Response (Optional)
              </label>
              <textarea name="text_response" rows="6" 
                        class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Type your answer or explanation here..."></textarea>
              <p class="text-xs text-gray-600 mt-1">You can provide a text response or upload files, or both.</p>
            </div>

            <!-- File Upload -->
            <div class="mb-6">
              <label class="block text-sm font-semibold mb-2">
                Upload File (Optional)
              </label>
              <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition">
                <input type="file" name="submission_file" id="fileInput" 
                       accept=".pdf,.doc,.docx,.txt,.zip,.rar" 
                       class="hidden" onchange="showFileName(this)">
                <label for="fileInput" class="cursor-pointer">
                  <div class="text-5xl mb-2">📎</div>
                  <p class="text-gray-700 font-semibold">Click to upload file</p>
                  <p class="text-sm text-gray-500 mt-1">PDF, DOC, DOCX, TXT, ZIP (Max 10MB)</p>
                </label>
                <p id="fileName" class="text-sm text-blue-600 font-semibold mt-2"></p>
              </div>
            </div>

            <!-- Submission Notes -->
            <div class="mb-6">
              <label class="block text-sm font-semibold mb-2">
                Additional Notes (Optional)
              </label>
              <textarea name="notes" rows="3" 
                        class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Any additional comments for your teacher..."></textarea>
            </div>

            <!-- Submit Button -->
            <div class="flex space-x-3">
              <button type="submit" 
                      class="flex-1 bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 font-semibold text-lg">
                ✅ Submit Assignment
              </button>
              <a href="assignment.php" 
                 class="px-6 bg-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-400 font-semibold flex items-center justify-center">
                Cancel
              </a>
            </div>

            <p class="text-xs text-gray-600 mt-3 text-center">
              ⚠️ Make sure to review your submission before clicking Submit. 
              You cannot edit after submission.
            </p>
          </form>
        </div>
      <?php endif; ?>
    </div>

  </main>

  <script>
    function showFileName(input) {
      const fileName = input.files[0]?.name;
      const fileNameDisplay = document.getElementById('fileName');
      
      if (fileName) {
        fileNameDisplay.textContent = '✅ Selected: ' + fileName;
      } else {
        fileNameDisplay.textContent = '';
      }
    }

    // Form validation
    document.getElementById('submissionForm')?.addEventListener('submit', function(e) {
      const textResponse = document.querySelector('[name="text_response"]').value.trim();
      const fileInput = document.querySelector('[name="submission_file"]');
      const hasFile = fileInput.files.length > 0;

      if (!textResponse && !hasFile) {
        e.preventDefault();
        alert('Please provide either a written response or upload a file before submitting.');
        return false;
      }

      // Confirm submission
      if (!confirm('Are you sure you want to submit? You cannot edit after submission.')) {
        e.preventDefault();
        return false;
      }
    });
  </script>
</body>
</html>
