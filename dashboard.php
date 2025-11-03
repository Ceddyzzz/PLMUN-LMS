<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard | PLMUN LMS</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
  <?php include 'includes/header.php'; ?>
  
  <main class="p-6 max-w-7xl mx-auto">
    <h2 class="text-3xl font-bold mb-6">Welcome back, <?php echo htmlspecialchars($_SESSION['user']); ?> 👋</h2>
    
    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white p-6 rounded-lg shadow">
        <div class="text-blue-600 text-3xl mb-2"></div>
        <h3 class="text-xl font-bold">5 Courses</h3>
        <p class="text-gray-600">Active this semester</p>
      </div>
      
      <div class="bg-white p-6 rounded-lg shadow">
        <div class="text-green-600 text-3xl mb-2"></div>
        <h3 class="text-xl font-bold">3 Pending</h3>
        <p class="text-gray-600">Assignments due</p>
      </div>
      
      <div class="bg-white p-6 rounded-lg shadow">
        <div class="text-purple-600 text-3xl mb-2"></div>
        <h3 class="text-xl font-bold">2 Quizzes</h3>
        <p class="text-gray-600">Scheduled this week</p>
      </div>
      
      <div class="bg-white p-6 rounded-lg shadow">
        <div class="text-orange-600 text-3xl mb-2"></div>
        <h3 class="text-xl font-bold">8 Messages</h3>
        <p class="text-gray-600">Unread notifications</p>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
      <h3 class="text-2xl font-bold mb-4"> Recent Activity</h3>
      <div class="space-y-4">
        <div class="border-l-4 border-blue-500 pl-4 py-2">
          <p class="font-semibold">New assignment posted in Database Management</p>
          <p class="text-sm text-gray-600">Due: October 28, 2025</p>
        </div>
        <div class="border-l-4 border-green-500 pl-4 py-2">
          <p class="font-semibold">Quiz scheduled: Web Development Fundamentals</p>
          <p class="text-sm text-gray-600">October 25, 2025 at 10:00 AM</p>
        </div>
        <div class="border-l-4 border-purple-500 pl-4 py-2">
          <p class="font-semibold">Grade posted for Programming Assignment #2</p>
          <p class="text-sm text-gray-600">Score: 95/100</p>
        </div>
      </div>
    </div>

    <!-- Quick Links -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-xl font-bold mb-4"> Quick Actions</h3>
        <div class="space-y-2">
          <a href="assignment.php" class="block p-3 bg-blue-50 rounded hover:bg-blue-100">Submit Assignment</a>
          <a href="quiz.php" class="block p-3 bg-green-50 rounded hover:bg-green-100">Take Quiz</a>
          <a href="e-books.php" class="block p-3 bg-purple-50 rounded hover:bg-purple-100">Browse E-Books</a>
          <a href="chat.php" class="block p-3 bg-orange-50 rounded hover:bg-orange-100">Message Teacher</a>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-xl font-bold mb-4">📅 Upcoming Events</h3>
        <ul class="space-y-3 text-gray-700">
          <li><strong>Today</strong> - </li>
          <li><strong>Tomorrow</strong> - </li>
          <li><strong>Friday</strong> - </li>
        </ul>
      </div>
    </div>
  </main>
</body>
</html>