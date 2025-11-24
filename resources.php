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
  <title>Learning Resources | PLMUN LMS</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
  
<?php include 'includes/header.php'; ?>

  <main class="p-6 max-w-7xl mx-auto">
    <h2 class="text-3xl font-bold mb-6">📚 Learning Resources</h2>
    
    <!-- Category Navigation -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <div class="flex flex-wrap gap-4">
        <a href="e-books.php" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
          📖 E-Books Library
        </a>
        <a href="#videos" class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-semibold">
          🎥 Video Tutorials
        </a>
        <a href="#documents" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
          📄 Course Materials
        </a>
        <a href="#links" class="px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 font-semibold">
          🔗 External Links
        </a>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
      <!-- E-Books Section -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-2xl font-bold">📖 E-Books</h3>
          <a href="e-books.php" class="text-blue-600 hover:underline text-sm">View All →</a>
        </div>
        <ul class="space-y-3">
          <li class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded">
            <span class="text-2xl">📘</span>
            <div>
              <a href="#" class="text-blue-600 font-semibold hover:underline">Introduction to Programming</a>
              <p class="text-sm text-gray-600">Dr. John Smith • 420 pages • PDF</p>
            </div>
          </li>
          <li class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded">
            <span class="text-2xl">📗</span>
            <div>
              <a href="#" class="text-blue-600 font-semibold hover:underline">Database Systems</a>
              <p class="text-sm text-gray-600">Prof. Maria Garcia • 580 pages • PDF</p>
            </div>
          </li>
          <li class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded">
            <span class="text-2xl">📕</span>
            <div>
              <a href="#" class="text-blue-600 font-semibold hover:underline">Web Development Essentials</a>
              <p class="text-sm text-gray-600">Sarah Johnson • 520 pages • PDF</p>
            </div>
          </li>
        </ul>
      </div>

      <!-- Video Tutorials Section -->
      <div id="videos" class="bg-white rounded-lg shadow p-6">
        <h3 class="text-2xl font-bold mb-4">🎥 Video Tutorials</h3>
        <ul class="space-y-3">
          <li class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded">
            <span class="text-2xl">▶️</span>
            <div>
              <a href="#" class="text-blue-600 font-semibold hover:underline">HTML & CSS Crash Course</a>
              <p class="text-sm text-gray-600">Duration: 2h 15m • Web Development</p>
            </div>
          </li>
          <li class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded">
            <span class="text-2xl">▶️</span>
            <div>
              <a href="#" class="text-blue-600 font-semibold hover:underline">JavaScript Fundamentals</a>
              <p class="text-sm text-gray-600">Duration: 3h 45m • Programming</p>
            </div>
          </li>
          <li class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded">
            <span class="text-2xl">▶️</span>
            <div>
              <a href="#" class="text-blue-600 font-semibold hover:underline">Database Design Tutorial</a>
              <p class="text-sm text-gray-600">Duration: 1h 30m • Database Systems</p>
            </div>
          </li>
          <li class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded">
            <span class="text-2xl">▶️</span>
            <div>
              <a href="#" class="text-blue-600 font-semibold hover:underline">Data Structures Explained</a>
              <p class="text-sm text-gray-600">Duration: 2h 00m • Algorithms</p>
            </div>
          </li>
        </ul>
      </div>

      <!-- Course Materials Section -->
      <div id="documents" class="bg-white rounded-lg shadow p-6">
        <h3 class="text-2xl font-bold mb-4">📄 Course Materials</h3>
        <ul class="space-y-3">
          <li class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded">
            <span class="text-2xl">📄</span>
            <div>
              <a href="#" class="text-blue-600 font-semibold hover:underline">CS101 - Lecture Notes.pdf</a>
              <p class="text-sm text-gray-600">Introduction to Programming • 2.3 MB</p>
            </div>
          </li>
          <li class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded">
            <span class="text-2xl">📊</span>
            <div>
              <a href="#" class="text-blue-600 font-semibold hover:underline">Database Design - Slides.pptx</a>
              <p class="text-sm text-gray-600">Database Systems • 4.1 MB</p>
            </div>
          </li>
          <li class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded">
            <span class="text-2xl">📝</span>
            <div>
              <a href="#" class="text-blue-600 font-semibold hover:underline">Web Dev - Project Guidelines.docx</a>
              <p class="text-sm text-gray-600">Web Development • 850 KB</p>
            </div>
          </li>
          <li class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded">
            <span class="text-2xl">📊</span>
            <div>
              <a href="#" class="text-blue-600 font-semibold hover:underline">Algorithm Analysis - Cheat Sheet.pdf</a>
              <p class="text-sm text-gray-600">Data Structures • 1.2 MB</p>
            </div>
          </li>
        </ul>
      </div>

      <!-- External Resources Section -->
      <div id="links" class="bg-white rounded-lg shadow p-6">
        <h3 class="text-2xl font-bold mb-4">🔗 Useful Links</h3>
        <ul class="space-y-3">
          <li class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded">
            <span class="text-2xl">🌐</span>
            <div>
              <a href="https://developer.mozilla.org" target="_blank" class="text-blue-600 font-semibold hover:underline">MDN Web Docs</a>
              <p class="text-sm text-gray-600">Complete web development reference</p>
            </div>
          </li>
          <li class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded">
            <span class="text-2xl">🌐</span>
            <div>
              <a href="https://stackoverflow.com" target="_blank" class="text-blue-600 font-semibold hover:underline">Stack Overflow</a>
              <p class="text-sm text-gray-600">Programming Q&A community</p>
            </div>
          </li>
          <li class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded">
            <span class="text-2xl">🌐</span>
            <div>
              <a href="https://github.com" target="_blank" class="text-blue-600 font-semibold hover:underline">GitHub</a>
              <p class="text-sm text-gray-600">Code repository and collaboration</p>
            </div>
          </li>
          <li class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded">
            <span class="text-2xl">🌐</span>
            <div>
              <a href="https://w3schools.com" target="_blank" class="text-blue-600 font-semibold hover:underline">W3Schools</a>
              <p class="text-sm text-gray-600">Web development tutorials</p>
            </div>
          </li>
        </ul>
      </div>

    </div>

    <!-- Popular Downloads Section -->
    <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow-lg p-8 mt-6 text-white">
      <h3 class="text-2xl font-bold mb-4">⭐ Most Popular Resources This Week</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white bg-opacity-20 backdrop-blur rounded-lg p-4">
          <div class="text-3xl mb-2">📘</div>
          <h4 class="font-bold mb-1">Introduction to Python</h4>
          <p class="text-sm opacity-90">Downloaded 234 times</p>
        </div>
        <div class="bg-white bg-opacity-20 backdrop-blur rounded-lg p-4">
          <div class="text-3xl mb-2">🎥</div>
          <h4 class="font-bold mb-1">React.js Tutorial Series</h4>
          <p class="text-sm opacity-90">Watched by 189 students</p>
        </div>
        <div class="bg-white bg-opacity-20 backdrop-blur rounded-lg p-4">
          <div class="text-3xl mb-2">📄</div>
          <h4 class="font-bold mb-1">Algorithm Cheat Sheet</h4>
          <p class="text-sm opacity-90">Downloaded 156 times</p>
        </div>
      </div>
    </div>

  </main>
</body>
</html>
