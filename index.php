<?php
session_start();
// If already logged in, redirect to dashboard
if (isset($_SESSION['user'])) {
  header("Location: dashboard.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PLMUN LMS - Learning Management System</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
  </style>
</head>
<body class="min-h-screen">
  
  <!-- Navigation -->
  <nav class="bg-white bg-opacity-10 backdrop-blur-lg border-b border-white border-opacity-20">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
      <div class="flex items-center space-x-2">
        <span class="text-3xl"></span>
        <h1 class="text-2xl font-bold text-white">PLMUN LMS</h1>
      </div>
      <div class="space-x-4">
        <a href="login.php" class="text-white hover:text-yellow-300 font-semibold">Login</a>
        <a href="register.php" class="bg-white text-blue-700 px-6 py-2 rounded-lg hover:bg-yellow-300 font-semibold">Sign Up</a>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="max-w-7xl mx-auto px-6 py-20 text-center">
    <h2 class="text-5xl md:text-6xl font-bold text-white mb-6">
      Welcome to PLMUN<br>Learning Management System
    </h2>
    <p class="text-xl text-white text-opacity-90 mb-8 max-w-3xl mx-auto">
      Your gateway to seamless online education. Access courses, submit assignments, 
      take quizzes, and connect with teachers and classmates all in one place.
    </p>
    <div class="space-x-4">
      <a href="register.php" class="bg-white text-blue-700 px-8 py-4 rounded-lg hover:bg-yellow-300 font-bold text-lg inline-block">
        Get Started Free
      </a>
      <a href="login.php" class="bg-transparent border-2 border-white text-white px-8 py-4 rounded-lg hover:bg-white hover:text-blue-700 font-bold text-lg inline-block">
        Login Now
      </a>
    </div>
  </section>

  <!-- Features Section -->
  <section class="max-w-7xl mx-auto px-6 py-16">
    <h3 class="text-3xl font-bold text-white text-center mb-12">Everything You Need to Succeed</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <!-- Feature 1 -->
      <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-lg p-8 border border-white border-opacity-20 hover:bg-opacity-20 transition-all">
        <div class="text-5xl mb-4">📚</div>
        <h4 class="text-2xl font-bold text-white mb-3">Digital Library</h4>
        <p class="text-white text-opacity-80">
          Access hundreds of e-books and learning resources anytime, anywhere. Download materials for offline study.
        </p>
      </div>

      <!-- Feature 2 -->
      <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-lg p-8 border border-white border-opacity-20 hover:bg-opacity-20 transition-all">
        <div class="text-5xl mb-4">✍️</div>
        <h4 class="text-2xl font-bold text-white mb-3">Smart Assignments</h4>
        <p class="text-white text-opacity-80">
          Submit assignments online, track deadlines, and receive instant feedback from your instructors.
        </p>
      </div>

      <!-- Feature 3 -->
      <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-lg p-8 border border-white border-opacity-20 hover:bg-opacity-20 transition-all">
        <div class="text-5xl mb-4">🎯</div>
        <h4 class="text-2xl font-bold text-white mb-3">Interactive Quizzes</h4>
        <p class="text-white text-opacity-80">
          Test your knowledge with interactive quizzes and get immediate results to track your progress.
        </p>
      </div>

      <!-- Feature 4 -->
      <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-lg p-8 border border-white border-opacity-20 hover:bg-opacity-20 transition-all">
        <div class="text-5xl mb-4">💬</div>
        <h4 class="text-2xl font-bold text-white mb-3">Real-time Chat</h4>
        <p class="text-white text-opacity-80">
          Connect with teachers and classmates instantly. Collaborate on projects and get help when you need it.
        </p>
      </div>

      <!-- Feature 5 -->
      <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-lg p-8 border border-white border-opacity-20 hover:bg-opacity-20 transition-all">
        <div class="text-5xl mb-4">📅</div>
        <h4 class="text-2xl font-bold text-white mb-3">Smart Calendar</h4>
        <p class="text-white text-opacity-80">
          Never miss a deadline with our integrated calendar. Track classes, assignments, and important events.
        </p>
      </div>

      <!-- Feature 6 -->
      <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-lg p-8 border border-white border-opacity-20 hover:bg-opacity-20 transition-all">
        <div class="text-5xl mb-4">📢</div>
        <h4 class="text-2xl font-bold text-white mb-3">Announcements</h4>
        <p class="text-white text-opacity-80">
          Stay updated with important announcements from your teachers and the administration.
        </p>
      </div>
    </div>
  </section>

  <!-- Stats Section -->
  <section class="max-w-7xl mx-auto px-6 py-16">
    <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-lg p-12 border border-white border-opacity-20">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center">
        <div>
          <div class="text-4xl font-bold text-white mb-2">10</div>
          <div class="text-white text-opacity-80">Active Students</div>
        </div>
        <div>
          <div class="text-4xl font-bold text-white mb-2">2</div>
          <div class="text-white text-opacity-80">Expert Teachers</div>
        </div>
        <div>
          <div class="text-4xl font-bold text-white mb-2">5+</div>
          <div class="text-white text-opacity-80">Courses Available</div>
        </div>
        <div>
          <div class="text-4xl font-bold text-white mb-2">98%</div>
          <div class="text-white text-opacity-80">Student Satisfaction</div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="max-w-7xl mx-auto px-6 py-20 text-center">
    <h3 class="text-4xl font-bold text-white mb-6">Ready to Start Learning?</h3>
    <p class="text-xl text-white text-opacity-90 mb-8">
      Join thousands of students already using PLMUN LMS for their education journey.
    </p>
    <a href="register.php" class="bg-white text-blue-700 px-10 py-4 rounded-lg hover:bg-yellow-300 font-bold text-lg inline-block">
      Create Your Account
    </a>
  </section>

  <!-- Footer -->
  <footer class="bg-white bg-opacity-10 backdrop-blur-lg border-t border-white border-opacity-20 mt-16">
    <div class="max-w-7xl mx-auto px-6 py-8 text-center text-white text-opacity-80">
      <p>&copy; 2025 PLMUN Learning Management System. All rights reserved.</p>
      <p class="mt-2">Pamantasan ng Lungsod ng Muntinlupa</p>
    </div>
  </footer>

</body>
</html>
