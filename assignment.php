<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}

include 'includes/db_connect.php';

// Get current user info and role
$current_user_email = $_SESSION['user'];
$stmt = $conn->prepare("SELECT id, email, name, role FROM users WHERE email = ?");
$stmt->bind_param("s", $current_user_email);
$stmt->execute();
$result = $stmt->get_result();
$current_user = $result->fetch_assoc();
$user_id = $current_user['id'];
$user_role = $current_user['role']; // 'teacher' or 'student'
$stmt->close();

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build query based on role
if ($user_role === 'teacher') {
  // Teachers see assignments they created
  $where_clause = "WHERE a.teacher_id = $user_id";
  if ($filter === 'pending') {
    $where_clause .= " AND a.due_date >= NOW()";
  } elseif ($filter === 'graded') {
    $where_clause .= " AND EXISTS (SELECT 1 FROM assignment_submissions s WHERE s.assignment_id = a.id AND s.grade IS NOT NULL)";
  }
} else {
  // Students see all assignments
  $where_clause = "";
  if ($filter === 'pending') {
    $where_clause = "WHERE NOT EXISTS (SELECT 1 FROM assignment_submissions s WHERE s.assignment_id = a.id AND s.student_id = $user_id)";
  } elseif ($filter === 'submitted') {
    $where_clause = "WHERE EXISTS (SELECT 1 FROM assignment_submissions s WHERE s.assignment_id = a.id AND s.student_id = $user_id AND s.grade IS NULL)";
  } elseif ($filter === 'graded') {
    $where_clause = "WHERE EXISTS (SELECT 1 FROM assignment_submissions s WHERE s.assignment_id = a.id AND s.student_id = $user_id AND s.grade IS NOT NULL)";
  }
}

// Get assignments
$sql = "SELECT a.*, u.name as teacher_name, u.email as teacher_email,
        (SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id = a.id) as submission_count
        FROM assignments a
        LEFT JOIN users u ON a.teacher_id = u.id
        $where_clause
        ORDER BY a.due_date DESC";

$result = $conn->query($sql);
$assignments = [];

while ($row = $result->fetch_assoc()) {
  // For students, check submission status
  if ($user_role === 'student') {
    $sub_stmt = $conn->prepare("SELECT * FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
    $sub_stmt->bind_param("ii", $row['id'], $user_id);
    $sub_stmt->execute();
    $sub_result = $sub_stmt->get_result();
    $row['submission'] = $sub_result->fetch_assoc();
    $sub_stmt->close();
  }
  $assignments[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Assignments | PLMUN LMS</title>
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
      <li><a href="/PLMUN%20LMS/ebooks.php" class="hover:text-yellow-300">E-Books</a></li>
      <li><a href="/PLMUN%20LMS/quiz.php" class="hover:text-yellow-300">Quiz</a></li>
      <li><a href="/PLMUN%20LMS/logout.php" class="hover:text-yellow-300">Logout</a></li>
    </ul>
  </div>
</header>

  <main class="p-6 max-w-7xl mx-auto">
    <!-- Header with role indicator -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h2 class="text-3xl font-bold">📂 Assignments</h2>
        <p class="text-gray-600 mt-1">
          <?php if ($user_role === 'teacher'): ?>
            <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm font-semibold">👨‍🏫 Teacher View</span>
          <?php else: ?>
            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold">👨‍🎓 Student View</span>
          <?php endif; ?>
        </p>
      </div>
      
      <?php if ($user_role === 'teacher'): ?>
        <button onclick="showCreateModal()" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 font-semibold shadow-lg">
          ➕ Create New Assignment
        </button>
      <?php endif; ?>
    </div>
    
    <!-- Filter Tabs -->
    <div class="flex space-x-4 mb-6">
      <a href="?filter=all" 
         class="px-4 py-2 rounded-lg transition <?php echo $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
        All
      </a>
      
      <?php if ($user_role === 'teacher'): ?>
        <a href="?filter=pending" 
           class="px-4 py-2 rounded-lg transition <?php echo $filter === 'pending' ? 'bg-yellow-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
          Active
        </a>
        <a href="?filter=graded" 
           class="px-4 py-2 rounded-lg transition <?php echo $filter === 'graded' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
          Graded
        </a>
      <?php else: ?>
        <a href="?filter=pending" 
           class="px-4 py-2 rounded-lg transition <?php echo $filter === 'pending' ? 'bg-yellow-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
          Pending
        </a>
        <a href="?filter=submitted" 
           class="px-4 py-2 rounded-lg transition <?php echo $filter === 'submitted' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
          Submitted
        </a>
        <a href="?filter=graded" 
           class="px-4 py-2 rounded-lg transition <?php echo $filter === 'graded' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
          Graded
        </a>
      <?php endif; ?>
    </div>

    <!-- Assignments List -->
    <?php if (count($assignments) > 0): ?>
      <div class="space-y-6">
        <?php foreach ($assignments as $assignment): 
          $teacher_name = $assignment['teacher_name'] ?: explode('@', $assignment['teacher_email'])[0];
          $due_date = new DateTime($assignment['due_date']);
          $now = new DateTime();
          $is_overdue = $due_date < $now;
          
          // Determine status for students
          $status = 'pending';
          $status_color = 'yellow';
          if ($user_role === 'student' && isset($assignment['submission'])) {
            if ($assignment['submission']['grade'] !== null) {
              $status = 'graded';
              $status_color = 'green';
            } else {
              $status = 'submitted';
              $status_color = 'blue';
            }
          } elseif ($is_overdue && $user_role === 'student') {
            $status = 'overdue';
            $status_color = 'red';
          }
        ?>
        
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-<?php echo $status_color; ?>-500 hover:shadow-lg transition">
          <div class="flex justify-between items-start mb-4">
            <div class="flex-1">
              <div class="flex items-center space-x-3 mb-2">
                <?php if ($user_role === 'student'): ?>
                  <?php if ($status === 'overdue'): ?>
                    <span class="bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold">OVERDUE</span>
                  <?php elseif ($status === 'graded'): ?>
                    <span class="bg-green-500 text-white px-3 py-1 rounded-full text-xs font-bold">GRADED</span>
                  <?php elseif ($status === 'submitted'): ?>
                    <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-xs font-bold">SUBMITTED</span>
                  <?php else: ?>
                    <span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-xs font-bold">PENDING</span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="bg-purple-500 text-white px-3 py-1 rounded-full text-xs font-bold">
                    <?php echo $assignment['submission_count']; ?> SUBMISSIONS
                  </span>
                <?php endif; ?>
                
                <span class="text-sm text-gray-600"><?php echo htmlspecialchars($assignment['course']); ?></span>
              </div>
              
              <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($assignment['title']); ?></h3>
              <p class="text-gray-700 mb-3"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
              
              <div class="flex items-center space-x-4 text-sm text-gray-600">
                <span>📊 Points: <?php echo $assignment['points']; ?></span>
                <span>👨‍🏫 <?php echo htmlspecialchars($teacher_name); ?></span>
                <span class="<?php echo $is_overdue ? 'text-red-600 font-semibold' : ''; ?>">
                  📅 Due: <?php echo $due_date->format('M j, Y g:i A'); ?>
                </span>
              </div>
              
              <?php if ($user_role === 'student' && $status === 'graded'): ?>
                <div class="mt-4 p-4 bg-green-50 rounded-lg">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-2xl font-bold text-green-700">
                      <?php echo $assignment['submission']['grade']; ?>/<?php echo $assignment['points']; ?>
                    </span>
                    <span class="text-green-700 font-semibold">
                      <?php echo round(($assignment['submission']['grade'] / $assignment['points']) * 100, 1); ?>%
                    </span>
                  </div>
                  <?php if ($assignment['submission']['feedback']): ?>
                    <p class="text-sm text-gray-700">
                      <strong>Feedback:</strong> <?php echo htmlspecialchars($assignment['submission']['feedback']); ?>
                    </p>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              
              <?php if ($user_role === 'student' && $status === 'submitted'): ?>
                <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                  <p class="text-sm text-gray-700">
                    <strong>Submitted:</strong> <?php echo date('M j, Y g:i A', strtotime($assignment['submission']['submitted_at'])); ?>
                  </p>
                  <p class="text-sm text-blue-600 mt-1">⏰ Awaiting grading...</p>
                </div>
              <?php endif; ?>
            </div>
          </div>
          
          <div class="flex space-x-2 pt-4 border-t">
            <?php if ($user_role === 'teacher'): ?>
              <!-- Teacher Actions -->
              <button onclick="viewSubmissions(<?php echo $assignment['id']; ?>)" 
                      class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-semibold">
                📋 View Submissions (<?php echo $assignment['submission_count']; ?>)
              </button>
              <button onclick="editAssignment(<?php echo $assignment['id']; ?>)" 
                      class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 text-sm font-semibold">
                ✏️ Edit
              </button>
              <button onclick="deleteAssignment(<?php echo $assignment['id']; ?>)" 
                      class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-sm font-semibold">
                🗑️ Delete
              </button>
            <?php else: ?>
              <!-- Student Actions -->
              <button onclick="viewAssignment(<?php echo $assignment['id']; ?>)" 
                      class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-semibold">
                👁️ View Details
              </button>
              
              <?php if ($status === 'pending' || $status === 'overdue'): ?>
                <button onclick="submitAssignment(<?php echo $assignment['id']; ?>)" 
                        class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm font-semibold">
                  📤 Submit Assignment
                </button>
              <?php endif; ?>
              
              <button onclick="addComment(<?php echo $assignment['id']; ?>)" 
                      class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 text-sm font-semibold">
                💬 Comment
              </button>
            <?php endif; ?>
          </div>
        </div>
        
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="bg-white rounded-lg shadow p-12 text-center">
        <div class="text-6xl mb-4">📭</div>
        <h3 class="text-2xl font-bold mb-2">No Assignments Found</h3>
        <p class="text-gray-600">
          <?php if ($user_role === 'teacher'): ?>
            You haven't created any assignments yet. Click "Create New Assignment" to get started!
          <?php else: ?>
            No assignments available at the moment. Check back later!
          <?php endif; ?>
        </p>
      </div>
    <?php endif; ?>

  </main>

  <!-- Create Assignment Modal (Teacher Only) -->
  <?php if ($user_role === 'teacher'): ?>
  <div id="createModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-8 max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
      <h3 class="text-2xl font-bold mb-4">➕ Create New Assignment</h3>
      <form action="api/create_assignment.php" method="POST">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-semibold mb-2">Title</label>
            <input type="text" name="title" required 
                   class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
          
          <div>
            <label class="block text-sm font-semibold mb-2">Description</label>
            <textarea name="description" rows="4" required 
                      class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-semibold mb-2">Course</label>
              <input type="text" name="course" required 
                     class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
              <label class="block text-sm font-semibold mb-2">Points</label>
              <input type="number" name="points" value="100" required 
                     class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-semibold mb-2">Due Date & Time</label>
            <input type="datetime-local" name="due_date" required 
                   class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
        </div>
        
        <div class="flex space-x-3 mt-6">
          <button type="submit" 
                  class="flex-1 bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 font-semibold">
            Create Assignment
          </button>
          <button type="button" onclick="hideCreateModal()" 
                  class="px-6 bg-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-400 font-semibold">
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <script>
    function showCreateModal() {
      document.getElementById('createModal').classList.remove('hidden');
    }
    
    function hideCreateModal() {
      document.getElementById('createModal').classList.add('hidden');
    }
    
    function viewSubmissions(assignmentId) {
      window.location.href = 'view_submissions.php?id=' + assignmentId;
    }
    
    function editAssignment(assignmentId) {
      alert('Edit assignment #' + assignmentId + ' - Feature coming soon!');
    }
    
    function deleteAssignment(assignmentId) {
      if (confirm('Are you sure you want to delete this assignment? This cannot be undone.')) {
        window.location.href = 'delete_assignment.php?id=' + assignmentId;
      }
    }
    
    function viewAssignment(assignmentId) {
      window.location.href = 'view_assignment.php?id=' + assignmentId;
    }
    
    function submitAssignment(assignmentId) {
      window.location.href = 'submit_assignment.php?id=' + assignmentId;
    }
    
    function addComment(assignmentId) {
      const comment = prompt('Enter your comment or question:');
      if (comment) {
        alert('Comment feature coming soon! Your comment: ' + comment);
      }
    }
    
    // Close modal when clicking outside
    document.getElementById('createModal')?.addEventListener('click', function(e) {
      if (e.target === this) {
        hideCreateModal();
      }
    });
  </script>
</body>
</html>
