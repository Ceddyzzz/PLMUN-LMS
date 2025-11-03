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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PLMUN LMS - Quiz</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
 <header class="bg-blue-900 text-white p-4">
  <div class="max-w-7xl mx-auto flex justify-between items-center">
    <h1 class="text-xl font-bold">PLMUN LMS</h1>
    <ul class="flex space-x-6">
        <li><a href="/PLMUN%20LMS/dashboard.php" class="hover:text-yellow-300">Dashboard</a></li>
        <li><a href="/PLMUN%20LMS/announcement.php" class="hover:text-yellow-300">Announcements</a></li>
        <li><a href="/PLMUN%20LMS/chat_realtime.php" class="hover:text-yellow-300">Chat</a></li>
        <li><a href="/PLMUN%20LMS/assignment.php" class="hover:text-yellow-300">Assignments</a></li>
        <li><a href="/PLMUN%20LMS/calendar.php" class="hover:text-yellow-300">Calendar</a></li>
        <li><a href="/PLMUN%20LMS/e-books.php" class="hover:text-yellow-300">E-Books</a></li>
        <li><a href="/PLMUN%20LMS/quiz.php" class="hover:text-yellow-300">Quiz</a></li>
        <li><a href="/PLMUN%20LMS/logout.php" class="hover:text-yellow-300">Logout</a></li>
    </ul>
  </div>
</header>

  <main class="p-6 max-w-7xl mx-auto">
    <h2 class="text-3xl font-bold mb-6">🎯 Quizzes & Assessments</h2>

    <!-- Filter Tabs -->
    <div class="flex space-x-4 mb-6">
      <button class="px-4 py-2 bg-blue-600 text-white rounded-lg">All Quizzes</button>
      <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Available (3)</button>
      <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Completed (2)</button>
      <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Upcoming (2)</button>
    </div>

    <!-- Quizzes List -->
    <div class="space-y-6">

      <!-- Available Quiz (Urgent) -->
      <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-red-500">
        <div class="flex justify-between items-start mb-4">
          <div>
            <span class="bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold">AVAILABLE NOW</span>
            <h3 class="text-2xl font-bold mt-2">Web Development Fundamentals</h3>
            <p class="text-gray-600 text-sm">Prof. Luis Garcia • Web Development</p>
          </div>
          <div class="text-right">
            <p class="text-sm text-gray-600">Opens: Oct 22, 2025</p>
            <p class="text-sm text-red-600 font-semibold">Closes: Oct 25, 2025 10:00 AM</p>
          </div>
        </div>

        <div class="bg-red-50 p-4 rounded-lg mb-4">
          <p class="text-sm text-gray-700 mb-2">
            <strong>⏰ Time Remaining:</strong> 2 days, 15 hours
          </p>
          <p class="text-sm text-gray-700">
            <strong>Duration:</strong> 45 minutes | <strong>Questions:</strong> 20 | <strong>Points:</strong> 50
          </p>
        </div>

        <p class="text-gray-700 mb-4">
          This quiz covers HTML5, CSS3, responsive design principles, and basic JavaScript concepts. 
          Make sure you've reviewed all lecture materials before starting.
        </p>

        <div class="flex items-center justify-between pt-4 border-t">
          <div class="flex items-center space-x-4 text-sm">
            <span class="text-gray-600">📝 Multiple Choice & True/False</span>
            <span class="text-gray-600">🔄 1 Attempt Only</span>
          </div>
          <button class="bg-red-600 text-white px-8 py-3 rounded-lg hover:bg-red-700 font-bold">
            Start Quiz →
          </button>
        </div>
      </div>

      <!-- Available Quiz -->
      <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
        <div class="flex justify-between items-start mb-4">
          <div>
            <span class="bg-green-500 text-white px-3 py-1 rounded-full text-xs font-bold">AVAILABLE</span>
            <h3 class="text-2xl font-bold mt-2">Database Systems - SQL Basics</h3>
            <p class="text-gray-600 text-sm">Prof. Maria Santos • Database Management</p>
          </div>
          <div class="text-right">
            <p class="text-sm text-gray-600">Opens: Oct 20, 2025</p>
            <p class="text-sm text-gray-600">Closes: Oct 27, 2025 11:59 PM</p>
          </div>
        </div>

        <div class="bg-green-50 p-4 rounded-lg mb-4">
          <p class="text-sm text-gray-700 mb-2">
            <strong>Duration:</strong> 60 minutes | <strong>Questions:</strong> 25 | <strong>Points:</strong> 75
          </p>
        </div>

        <p class="text-gray-700 mb-4">
          Test your knowledge of SQL queries, joins, subqueries, and database normalization. 
          Includes both theoretical questions and practical SQL writing tasks.
        </p>

        <div class="flex items-center justify-between pt-4 border-t">
          <div class="flex items-center space-x-4 text-sm">
            <span class="text-gray-600">📝 Mixed Format</span>
            <span class="text-gray-600">🔄 2 Attempts Allowed</span>
          </div>
          <button class="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700 font-bold">
            Start Quiz →
          </button>
        </div>
      </div>

      <!-- Completed Quiz -->
      <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
        <div class="flex justify-between items-start mb-4">
          <div>
            <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-xs font-bold">COMPLETED</span>
            <h3 class="text-2xl font-bold mt-2">Data Structures - Sorting Algorithms</h3>
            <p class="text-gray-600 text-sm">Prof. Anna Cruz • Data Structures</p>
          </div>
          <div class="text-right">
            <p class="text-sm text-gray-600">Completed: Oct 18, 2025</p>
            <p class="text-sm text-gray-600">Submitted: 2:45 PM</p>
          </div>
        </div>

        <div class="bg-blue-50 p-4 rounded-lg mb-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-3xl font-bold text-blue-700 mb-1">42/50</p>
              <p class="text-sm text-gray-600">84% - Very Good</p>
            </div>
            <div class="text-right">
              <p class="text-sm text-gray-700"><strong>Time Taken:</strong> 32 minutes</p>
              <p class="text-sm text-gray-700"><strong>Attempt:</strong> 1 of 2</p>
            </div>
          </div>
        </div>

        <p class="text-gray-700 mb-4">
          <strong>Feedback:</strong> Excellent understanding of sorting algorithms! Minor improvements needed 
          in analyzing time complexity for edge cases.
        </p>

        <div class="flex items-center justify-between pt-4 border-t">
          <span class="text-sm text-gray-600">🏆 Grade: B+</span>
          <button class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-semibold">
            View Results
          </button>
        </div>
      </div>

      <!-- Completed Quiz with High Score -->
      <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
        <div class="flex justify-between items-start mb-4">
          <div>
            <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-xs font-bold">COMPLETED</span>
            <h3 class="text-2xl font-bold mt-2">Software Engineering - UML Diagrams</h3>
            <p class="text-gray-600 text-sm">Prof. Robert Mendoza • Software Engineering</p>
          </div>
          <div class="text-right">
            <p class="text-sm text-gray-600">Completed: Oct 15, 2025</p>
            <p class="text-sm text-gray-600">Submitted: 3:22 PM</p>
          </div>
        </div>

        <div class="bg-blue-50 p-4 rounded-lg mb-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-3xl font-bold text-blue-700 mb-1">48/50</p>
              <p class="text-sm text-gray-600">96% - Excellent!</p>
            </div>
            <div class="text-right">
              <p class="text-sm text-gray-700"><strong>Time Taken:</strong> 38 minutes</p>
              <p class="text-sm text-gray-700"><strong>Attempt:</strong> 1 of 1</p>
            </div>
          </div>
        </div>

        <p class="text-gray-700 mb-4">
          <strong>Feedback:</strong> Outstanding work! You demonstrated excellent understanding of UML notation 
          and diagram relationships. Keep up the great work!
        </p>

        <div class="flex items-center justify-between pt-4 border-t">
          <span class="text-sm text-gray-600">🏆 Grade: A</span>
          <button class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-semibold">
            View Results
          </button>
        </div>
      </div>

      <!-- Upcoming Quiz -->
      <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
        <div class="flex justify-between items-start mb-4">
          <div>
            <span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-xs font-bold">UPCOMING</span>
            <h3 class="text-2xl font-bold mt-2">Computer Networks - TCP/IP Protocol</h3>
            <p class="text-gray-600 text-sm">Prof. Elena Rodriguez • Computer Networks</p>
          </div>
          <div class="text-right">
            <p class="text-sm text-gray-600">Opens: Oct 28, 2025 9:00 AM</p>
            <p class="text-sm text-gray-600">Closes: Oct 30, 2025 11:59 PM</p>
          </div>
        </div>

        <div class="bg-yellow-50 p-4 rounded-lg mb-4">
          <p class="text-sm text-gray-700 mb-2">
            <strong>Opens in:</strong> 6 days
          </p>
          <p class="text-sm text-gray-700">
            <strong>Duration:</strong> 50 minutes | <strong>Questions:</strong> 30 | <strong>Points:</strong> 60
          </p>
        </div>

        <p class="text-gray-700 mb-4">
          Comprehensive quiz on TCP/IP protocol suite, network layers, addressing, and routing. 
          Review chapters 4-6 and lab exercises before the quiz opens.
        </p>

        <div class="flex items-center justify-between pt-4 border-t">
          <div class="flex items-center space-x-4 text-sm">
            <span class="text-gray-600">📝 Multiple Choice</span>
            <span class="text-gray-600">🔄 1 Attempt Only</span>
          </div>
          <button class="bg-gray-400 text-white px-8 py-3 rounded-lg cursor-not-allowed" disabled>
            Not Yet Available
          </button>
        </div>
      </div>

      <!-- Upcoming Quiz -->
      <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
        <div class="flex justify-between items-start mb-4">
          <div>
            <span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-xs font-bold">UPCOMING</span>
            <h3 class="text-2xl font-bold mt-2">Mobile Development - UI/UX Principles</h3>
            <p class="text-gray-600 text-sm">Prof. Mark Santos • Mobile App Development</p>
          </div>
          <div class="text-right">
            <p class="text-sm text-gray-600">Opens: Nov 2, 2025 10:00 AM</p>
            <p class="text-sm text-gray-600">Closes: Nov 4, 2025 11:59 PM</p>
          </div>
        </div>

        <div class="bg-yellow-50 p-4 rounded-lg mb-4">
          <p class="text-sm text-gray-700 mb-2">
            <strong>Opens in:</strong> 11 days
          </p>
          <p class="text-sm text-gray-700">
            <strong>Duration:</strong> 40 minutes | <strong>Questions:</strong> 20 | <strong>Points:</strong> 40
          </p>
        </div>

        <p class="text-gray-700 mb-4">
          Test your knowledge of mobile UI/UX design principles, Material Design guidelines, 
          and iOS Human Interface Guidelines. Focus on practical application scenarios.
        </p>

        <div class="flex items-center justify-between pt-4 border-t">
          <div class="flex items-center space-x-4 text-sm">
            <span class="text-gray-600">📝 Mixed Format</span>
            <span class="text-gray-600">🔄 2 Attempts Allowed</span>
          </div>
          <button class="bg-gray-400 text-white px-8 py-3 rounded-lg cursor-not-allowed" disabled>
            Not Yet Available
          </button>
        </div>
      </div>

    </div>
  </main>
  
</body>
</html>