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
  <title>Announcements | PLMUN LMS</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
  <?php include 'includes/header.php'; ?>

  <main class="p-6 max-w-7xl mx-auto">
    <h2 class="text-3xl font-bold mb-6">📢 Announcements</h2>
    
    <!-- Filter Tabs -->
    <div class="flex space-x-4 mb-6">
      <button class="px-4 py-2 bg-blue-600 text-white rounded-lg">All</button>
      <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Urgent</button>
      <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Events</button>
      <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Academic</button>
    </div>

    <!-- Announcements List -->
    <div class="space-y-6">
      <!-- Urgent Announcement -->
      <div class="bg-red-50 border-l-4 border-red-500 p-6 rounded-lg shadow">
        <div class="flex justify-between items-start mb-2">
          <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-semibold">URGENT</span>
          <span class="text-gray-600 text-sm">October 22, 2025</span>
        </div>
        <h3 class="text-xl font-bold mb-2">Midterm Exam Schedule Change</h3>
        <p class="text-gray-700 mb-2">
          Important update: The Software Engineering midterm exam has been rescheduled from October 26 to October 29, 2025. 
          Same time (9:00 AM) and venue (Room 301).
        </p>
        <p class="text-sm text-gray-600">Posted by: Prof. Maria Santos</p>
      </div>

      <!-- Regular Announcement -->
      <div class="bg-white border-l-4 border-blue-500 p-6 rounded-lg shadow">
        <div class="flex justify-between items-start mb-2">
          <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-sm font-semibold">EVENT</span>
          <span class="text-gray-600 text-sm">October 21, 2025</span>
        </div>
        <h3 class="text-xl font-bold mb-2">Tech Talk: AI in Modern Development</h3>
        <p class="text-gray-700 mb-2">
          Join us for an exciting guest lecture on Artificial Intelligence applications in software development. 
          Date: October 30, 2025 at 3:00 PM in the Innovation Hub. All CS students are welcome!
        </p>
        <p class="text-sm text-gray-600">Posted by: Computer Science Department</p>
      </div>

      <!-- Info Announcement -->
      <div class="bg-white border-l-4 border-green-500 p-6 rounded-lg shadow">
        <div class="flex justify-between items-start mb-2">
          <span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm font-semibold">ACADEMIC</span>
          <span class="text-gray-600 text-sm">October 20, 2025</span>
        </div>
        <h3 class="text-xl font-bold mb-2">New E-Books Added to Library</h3>
        <p class="text-gray-700 mb-2">
          We've added 15 new programming and web development e-books to the digital library. 
          Check the E-Books section to browse the latest additions including titles on React, Node.js, and Python.
        </p>
        <p class="text-sm text-gray-600">Posted by: Library Services</p>
      </div>

      <!-- General Announcement -->
      <div class="bg-white border-l-4 border-purple-500 p-6 rounded-lg shadow">
        <div class="flex justify-between items-start mb-2">
          <span class="bg-purple-500 text-white px-3 py-1 rounded-full text-sm font-semibold">INFO</span>
          <span class="text-gray-600 text-sm">October 19, 2025</span>
        </div>
        <h3 class="text-xl font-bold mb-2">Enrollment Period for Next Semester</h3>
        <p class="text-gray-700 mb-2">
          Enrollment for the second semester will open on November 15, 2025. Please consult with your academic advisor 
          to plan your course schedule. Early enrollment is encouraged to secure your preferred subjects.
        </p>
        <p class="text-sm text-gray-600">Posted by: Registrar's Office</p>
      </div>

      <!-- Reminder -->
      <div class="bg-white border-l-4 border-yellow-500 p-6 rounded-lg shadow">
        <div class="flex justify-between items-start mb-2">
          <span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-sm font-semibold">REMINDER</span>
          <span class="text-gray-600 text-sm">October 18, 2025</span>
        </div>
        <h3 class="text-xl font-bold mb-2">Laboratory Equipment Care Guidelines</h3>
        <p class="text-gray-700 mb-2">
          Reminder to all students: Please handle all laboratory equipment with care. Report any damages or malfunctions 
          immediately to the lab coordinator. Proper equipment usage ensures everyone can learn effectively.
        </p>
        <p class="text-sm text-gray-600">Posted by: Laboratory Department</p>
      </div>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center mt-8 space-x-2">
      <button class="px-4 py-2 bg-blue-600 text-white rounded">1</button>
      <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">2</button>
      <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">3</button>
      <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Next →</button>
    </div>
  </main>
</body>
</html>
