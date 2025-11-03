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
      <li><a href="/PLMUN%20LMS/chat_realtime.php" class="hover:text-yellow-300">Chat</a></li>
      <li><a href="/PLMUN%20LMS/assignment.php" class="hover:text-yellow-300">Assignments</a></li>
      <li><a href="/PLMUN%20LMS/calendar.php" class="hover:text-yellow-300">Calendar</a></li>
      <li><a href="/PLMUN%20LMS/ebooks.php" class="hover:text-yellow-300">E-Books</a></li>
      <li><a href="/PLMUN%20LMS/quiz.php" class="hover:text-yellow-300">Quiz</a></li>
      <li><a href="/PLMUN%20LMS/logout.php" class="hover:text-yellow-300">Logout</a></li>
    </ul>
  </div>
</header>

  <main class="p-6 max-w-7xl mx-auto">
    <h2 class="text-3xl font-bold mb-6">📂 Assignments</h2>
    
    <!-- Filter Tabs -->
    <div class="flex space-x-4 mb-6">
      <button class="px-4 py-2 bg-blue-600 text-white rounded-lg">All (7)</button>
      <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Pending (3)</button>
      <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Submitted (2)</button>
      <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Graded (2)</button>
    </div>

    <!-- Assignments List -->
    <div class="space-y-6">
      
      <!-- Urgent Assignment (Due Soon) -->
      <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-red-500">
        <div class="flex justify-between items-start mb-3">
          <div>
            <span class="bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold">DUE IN 2 DAYS</span>
            <h3 class="text-xl font-bold mt-2">Database Management - Final Project</h3>
            <p class="text-gray-600 text-sm">Prof. Maria Santos • Database Management System</p>
          </div>
          <span class="text-gray-500 text-sm">Due: Oct 24, 2025 11:59 PM</span>
        </div>
        
        <p class="text-gray-700 mb-4">
          Design and implement a complete database system for a library management system. Include ER diagrams, 
          normalization steps, and SQL queries. Submit both documentation and working code.
        </p>
        
        <div class="flex items-center justify-between pt-4 border-t">
          <div class="flex items-center space-x-4 text-sm text-gray-600">
            <span>📊 Points: 100</span>
            <span>📎 Attachments: 2 files</span>
          </div>
          <button class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-semibold">
            Submit Assignment
          </button>
        </div>
      </div>

      <!-- Pending Assignment -->
      <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
        <div class="flex justify-between items-start mb-3">
          <div>
            <span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-xs font-bold">PENDING</span>
            <h3 class="text-xl font-bold mt-2">Web Development - Responsive Portfolio</h3>
            <p class="text-gray-600 text-sm">Prof. Luis Garcia • Web Development</p>
          </div>
          <span class="text-gray-500 text-sm">Due: Oct 28, 2025 11:59 PM</span>
        </div>
        
        <p class="text-gray-700 mb-4">
          Create a fully responsive personal portfolio website using HTML, CSS, and JavaScript. 
          Must include: home, about, projects, and contact sections. Mobile-first design required.
        </p>
        
        <div class="flex items-center justify-between pt-4 border-t">
          <div class="flex items-center space-x-4 text-sm text-gray-600">
            <span>📊 Points: 80</span>
            <span>📎 Instructions.pdf</span>
          </div>
          <button class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-semibold">
            Submit Assignment
          </button>
        </div>
      </div>

      <!-- Submitted Assignment -->
      <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
        <div class="flex justify-between items-start mb-3">
          <div>
            <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-xs font-bold">SUBMITTED</span>
            <h3 class="text-xl font-bold mt-2">Data Structures - Sorting Algorithms</h3>
            <p class="text-gray-600 text-sm">Prof. Anna Cruz • Data Structures & Algorithms</p>
          </div>
          <span class="text-gray-500 text-sm">Submitted: Oct 18, 2025</span>
        </div>
        
        <p class="text-gray-700 mb-4">
          Implement and analyze 5 different sorting algorithms (Bubble, Selection, Insertion, Merge, Quick). 
          Include time complexity analysis and performance comparison.
        </p>
        
        <div class="bg-blue-50 p-3 rounded mb-4">
          <p class="text-sm text-gray-700">
            <strong>Your submission:</strong> sorting_algorithms.zip (2.3 MB)
          </p>
        </div>
        
        <div class="flex items-center justify-between pt-4 border-t">
          <div class="flex items-center space-x-4 text-sm text-gray-600">
            <span>📊 Points: 100</span>
            <span>⏰ Awaiting Grade</span>
          </div>
          <button class="bg-gray-300 text-gray-600 px-6 py-2 rounded-lg cursor-not-allowed" disabled>
            Already Submitted
          </button>
        </div>
      </div>

      <!-- Graded Assignment -->
      <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
        <div class="flex justify-between items-start mb-3">
          <div>
            <span class="bg-green-500 text-white px-3 py-1 rounded-full text-xs font-bold">GRADED</span>
            <h3 class="text-xl font-bold mt-2">Software Engineering - UML Diagrams</h3>
            <p class="text-gray-600 text-sm">Prof. Robert Mendoza • Software Engineering</p>
          </div>
          <span class="text-gray-500 text-sm">Graded: Oct 15, 2025</span>
        </div>
        
        <p class="text-gray-700 mb-4">
          Create complete UML diagrams for an online shopping system including class diagrams, 
          use case diagrams, and sequence diagrams.
        </p>
        
        <div class="bg-green-50 p-4 rounded mb-4">
          <div class="flex items-center justify-between mb-2">
            <span class="text-2xl font-bold text-green-700">95/100</span>
            <span class="text-green-700 font-semibold">Excellent Work!</span>
          </div>
          <p class="text-sm text-gray-700">
            <strong>Feedback:</strong> Very comprehensive diagrams. Great attention to detail. 
            Minor improvement needed in sequence diagram timing.
          </p>
        </div>
        
        <div class="flex items-center justify-between pt-4 border-t">
          <div class="flex items-center space-x-4 text-sm text-gray-600">
            <span>📊 Points: 100</span>
            <span>✅ Grade: A</span>
          </div>
          <button class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300">
            View Details
          </button>
        </div>
      </div>

      <!-- Another Pending Assignment -->
      <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
        <div class="flex justify-between items-start mb-3">
          <div>
            <span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-xs font-bold">PENDING</span>
            <h3 class="text-xl font-bold mt-2">Computer Networks - Network Simulation</h3>
            <p class="text-gray-600 text-sm">Prof. Elena Rodriguez • Computer Networks</p>
          </div>
          <span class="text-gray-500 text-sm">Due: Nov 5, 2025 11:59 PM</span>
        </div>
        
        <p class="text-gray-700 mb-4">
          Use Packet Tracer to simulate a small office network with routers, switches, and 10 workstations. 
          Configure IP addressing, routing protocols, and test connectivity.
        </p>
        
        <div class="flex items-center justify-between pt-4 border-t">
          <div class="flex items-center space-x-4 text-sm text-gray-600">
            <span>📊 Points: 90</span>
            <span>📎 Network_Requirements.pdf</span>
          </div>
          <button class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-semibold">
            Submit Assignment
          </button>
        </div>
      </div>

      <!-- Graded Assignment with Lower Score -->
      <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
        <div class="flex justify-between items-start mb-3">
          <div>
            <span class="bg-green-500 text-white px-3 py-1 rounded-full text-xs font-bold">GRADED</span>
            <h3 class="text-xl font-bold mt-2">Mobile App Development - UI Prototype</h3>
            <p class="text-gray-600 text-sm">Prof. Mark Santos • Mobile Development</p>
          </div>
          <span class="text-gray-500 text-sm">Graded: Oct 10, 2025</span>
        </div>
        
        <p class="text-gray-700 mb-4">
          Design a complete UI prototype for a mobile fitness tracking app using Figma. 
          Include at least 8 screens with proper navigation flow.
        </p>
        
        <div class="bg-green-50 p-4 rounded mb-4">
          <div class="flex items-center justify-between mb-2">
            <span class="text-2xl font-bold text-green-700">78/100</span>
            <span class="text-green-700 font-semibold">Good Work</span>
          </div>
          <p class="text-sm text-gray-700">
            <strong>Feedback:</strong> Good design overall, but some inconsistencies in button styles. 
            Navigation flow needs improvement. Please review Material Design guidelines.
          </p>
        </div>
        
        <div class="flex items-center justify-between pt-4 border-t">
          <div class="flex items-center space-x-4 text-sm text-gray-600">
            <span>📊 Points: 100</span>
            <span>✅ Grade: B+</span>
          </div>
          <button class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300">
            View Details
          </button>
        </div>
      </div>

    </div>
  </main>
</body>
</html>
