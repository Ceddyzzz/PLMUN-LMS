<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'] ?? '';
?>

<header class="bg-blue-900 text-white p-4">
  <div class="max-w-7xl mx-auto flex justify-between items-center">
    <div class="flex items-center space-x-2">
      <span class="text-2xl">🎓</span>
      <h1 class="text-xl font-bold">PLMUN LMS</h1>
      <?php if ($role): ?>
        <span class="text-xs bg-purple-600 px-2 py-1 rounded ml-2"><?php echo $role; ?></span>
      <?php endif; ?>
    </div>
    
    <nav>
      <ul class="flex space-x-6">
        <li><a href="/PLMUN%20LMS/dashboard.php" class="hover:text-yellow-300">Dashboard</a></li>
        <li><a href="/PLMUN%20LMS/announcement.php" class="hover:text-yellow-300">Announcements</a></li>
        <li><a href="/PLMUN%20LMS/chat.php" class="hover:text-yellow-300">Chat</a></li>
        <li><a href="/PLMUN%20LMS/calendar.php" class="hover:text-yellow-300">Calendar</a></li>
        <li><a href="/PLMUN%20LMS/e-books.php" class="hover:text-yellow-300">E-Books</a></li>
        
        <?php if ($role === 'dean'): ?>
          <li><a href="/PLMUN%20LMS/assign_subjects.php" class="hover:text-yellow-300">Assign Subjects</a></li>
        <?php endif; ?>
        
        <?php if ($role === 'teacher'): ?>
          <li><a href="/PLMUN%20LMS/sections.php" class="hover:text-yellow-300">Sections</a></li>
        <?php endif; ?>
        
        <?php if ($role === 'student' || $role === 'teacher'): ?>
          <li><a href="/PLMUN%20LMS/assignment.php" class="hover:text-yellow-300">Assignments</a></li>
          <li><a href="/PLMUN%20LMS/quiz.php" class="hover:text-yellow-300">Quiz</a></li>
        <?php endif; ?>
        
        <li><a href="/PLMUN%20LMS/logout.php" class="hover:text-yellow-300">Logout</a></li>
      </ul>
    </nav>
  </div>
</header>
