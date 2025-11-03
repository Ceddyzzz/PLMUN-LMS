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
  <title>E-Books | PLMUN LMS</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
  <?php include 'includes/header.php'; ?>

  <main class="p-6 max-w-7xl mx-auto">
    <h2 class="text-3xl font-bold mb-6">📖 Digital Library</h2>
    
    <!-- Search and Filter -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <div class="flex flex-col md:flex-row gap-4">
        <input 
          type="text" 
          placeholder="Search e-books by title, author, or subject..." 
          class="flex-1 p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
        <select class="p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option>All Categories</option>
          <option>Programming</option>
          <option>Web Development</option>
          <option>Database</option>
          <option>Networks</option>
          <option>Software Engineering</option>
        </select>
        <button class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-semibold">
          Search
        </button>
      </div>
    </div>

    <!-- Category Tabs -->
    <div class="flex space-x-4 mb-6 overflow-x-auto pb-2">
      <button class="px-4 py-2 bg-blue-600 text-white rounded-lg whitespace-nowrap">All Books</button>
      <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 whitespace-nowrap">Programming</button>
      <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 whitespace-nowrap">Web Development</button>
      <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 whitespace-nowrap">Database</button>
      <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 whitespace-nowrap">Networks</button>
      <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 whitespace-nowrap">Recently Added</button>
    </div>

    <!-- E-Books Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      
      <!-- E-Book Card 1 -->
      <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
        <div class="bg-gradient-to-br from-blue-500 to-blue-700 h-48 rounded-t-lg flex items-center justify-center">
          <div class="text-white text-center p-6">
            <div class="text-6xl mb-2">📘</div>
            <h3 class="font-bold text-lg">Introduction to Programming</h3>
          </div>
        </div>
        <div class="p-6">
          <div class="flex items-center justify-between mb-3">
            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-semibold">Programming</span>
            <span class="text-sm text-gray-600">420 pages</span>
          </div>
          <p class="text-gray-700 text-sm mb-4">
            Comprehensive guide covering fundamental programming concepts, algorithms, and problem-solving techniques.
          </p>
          <p class="text-xs text-gray-600 mb-4">
            <strong>Author:</strong> Dr. John Smith<br>
            <strong>Added:</strong> Sept 15, 2025
          </p>
          <div class="flex gap-2">
            <button class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 font-semibold">
              📥 Download
            </button>
            <button class="px-4 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
              👁️
            </button>
          </div>
        </div>
      </div>

      <!-- E-Book Card 2 -->
      <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
        <div class="bg-gradient-to-br from-green-500 to-green-700 h-48 rounded-t-lg flex items-center justify-center">
          <div class="text-white text-center p-6">
            <div class="text-6xl mb-2">📗</div>
            <h3 class="font-bold text-lg">Database Systems</h3>
          </div>
        </div>
        <div class="p-6">
          <div class="flex items-center justify-between mb-3">
            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-semibold">Database</span>
            <span class="text-sm text-gray-600">580 pages</span>
          </div>
          <p class="text-gray-700 text-sm mb-4">
            Complete coverage of relational databases, SQL, normalization, and modern database management concepts.
          </p>
          <p class="text-xs text-gray-600 mb-4">
            <strong>Author:</strong> Prof. Maria Garcia<br>
            <strong>Added:</strong> Sept 20, 2025
          </p>
          <div class="flex gap-2">
            <button class="flex-1 bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 font-semibold">
              📥 Download
            </button>
            <button class="px-4 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
              👁️
            </button>
          </div>
        </div>
      </div>

      <!-- E-Book Card 3 -->
      <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
        <div class="bg-gradient-to-br from-purple-500 to-purple-700 h-48 rounded-t-lg flex items-center justify-center">
          <div class="text-white text-center p-6">
            <div class="text-6xl mb-2">📕</div>
            <h3 class="font-bold text-lg">Web Development</h3>
          </div>
        </div>
        <div class="p-6">
          <div class="flex items-center justify-between mb-3">
            <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-xs font-semibold">Web Dev</span>
            <span class="text-sm text-gray-600">520 pages</span>
          </div>
          <p class="text-gray-700 text-sm mb-4">
            Modern web development with HTML5, CSS3, JavaScript, and responsive design principles.
          </p>
          <p class="text-xs text-gray-600 mb-4">
            <strong>Author:</strong> Sarah Johnson<br>
            <strong>Added:</strong> Oct 1, 2025
          </p>
          <div class="flex gap-2">
            <button class="flex-1 bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 font-semibold">
              📥 Download
            </button>
            <button class="px-4 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
              👁️
            </button>
          </div>
        </div>
      </div>

      <!-- E-Book Card 4 -->
      <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
        <div class="bg-gradient-to-br from-orange-500 to-orange-700 h-48 rounded-t-lg flex items-center justify-center">
          <div class="text-white text-center p-6">
            <div class="text-6xl mb-2">📙</div>
            <h3 class="font-bold text-lg">Data Structures & Algorithms</h3>
          </div>
        </div>
        <div class="p-6">
          <div class="flex items-center justify-between mb-3">
            <span class="bg-orange-100 text-orange-800 px-3 py-1 rounded-full text-xs font-semibold">Programming</span>
            <span class="text-sm text-gray-600">650 pages</span>
          </div>
          <p class="text-gray-700 text-sm mb-4">
            In-depth exploration of data structures, algorithm design, complexity analysis, and optimization techniques.
          </p>
          <p class="text-xs text-gray-600 mb-4">
            <strong>Author:</strong> Dr. Robert Chen<br>
            <strong>Added:</strong> Aug 28, 2025
          </p>
          <div class="flex gap-2">
            <button class="flex-1 bg-orange-600 text-white py-2 rounded-lg hover:bg-orange-700 font-semibold">
              📥 Download
            </button>
            <button class="px-4 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
              👁️
            </button>
          </div>
        </div>
      </div>

      <!-- E-Book Card 5 -->
      <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
        <div class="bg-gradient-to-br from-red-500 to-red-700 h-48 rounded-t-lg flex items-center justify-center">
          <div class="text-white text-center p-6">
            <div class="text-6xl mb-2">📕</div>
            <h3 class="font-bold text-lg">Computer Networks</h3>
          </div>
        </div>
        <div class="p-6">
          <div class="flex items-center justify-between mb-3">
            <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-semibold">Networks</span>
            <span class="text-sm text-gray-600">490 pages</span>
          </div>
          <p class="text-gray-700 text-sm mb-4">
            Comprehensive guide to networking fundamentals, protocols, security, and network architecture.
          </p>
          <p class="text-xs text-gray-600 mb-4">
            <strong>Author:</strong> Prof. Lisa Anderson<br>
            <strong>Added:</strong> Sept 10, 2025
          </p>
          <div class="flex gap-2">
            <button class="flex-1 bg-red-600 text-white py-2 rounded-lg hover:bg-red-700 font-semibold">
              📥 Download
            </button>
            <button class="px-4 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
              👁️
            </button>
          </div>
        </div>
      </div>

      <!-- E-Book Card 6 -->
      <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-700 h-48 rounded-t-lg flex items-center justify-center">
          <div class="text-white text-center p-6">
            <div class="text-6xl mb-2">📘</div>
            <h3 class="font-bold text-lg">Software Engineering</h3>
          </div>
        </div>
        <div class="p-6">
          <div class="flex items-center justify-between mb-3">
            <span class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-xs font-semibold">Software Eng</span>
            <span class="text-sm text-gray-600">720 pages</span>
          </div>
          <p class="text-gray-700 text-sm mb-4">
            Software development lifecycle, design patterns, testing methodologies, and project management principles.
          </p>
          <p class="text-xs text-gray-600 mb-4">
            <strong>Author:</strong> Michael Brown<br>
            <strong>Added:</strong> Sept 5, 2025
          </p>
          <div class="flex gap-2">
            <button class="flex-1 bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 font-semibold">
              📥 Download
            </button>
            <button class="px-4 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
              👁️
            </button>
          </div>
        </div>
      </div>

      <!-- E-Book Card 7 -->
      <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
        <div class="bg-gradient-to-br from-pink-500 to-pink-700 h-48 rounded-t-lg flex items-center justify-center">
          <div class="text-white text-center p-6">
            <div class="text-6xl mb-2">📗</div>
            <h3 class="font-bold text-lg">Mobile App Development</h3>
          </div>
        </div>
        <div class="p-6">
          <div class="flex items-center justify-between mb-3">
            <span class="bg-pink-100 text-pink-800 px-3 py-1 rounded-full text-xs font-semibold">Mobile Dev</span>
            <span class="text-sm text-gray-600">380 pages</span>
          </div>
          <p class="text-gray-700 text-sm mb-4">
            Build native and cross-platform mobile applications for iOS and Android with modern frameworks.
          </p>
          <p class="text-xs text-gray-600 mb-4">
            <strong>Author:</strong> Emma Wilson<br>
            <strong>Added:</strong> Oct 10, 2025
          </p>
          <div class="flex gap-2">
            <button class="flex-1 bg-pink-600 text-white py-2 rounded-lg hover:bg-pink-700 font-semibold">
              📥 Download
            </button>
            <button class="px-4 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
              👁️
            </button>
          </div>
        </div>
      </div>

      <!-- E-Book Card 8 -->
      <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
        <div class="bg-gradient-to-br from-teal-500 to-teal-700 h-48 rounded-t-lg flex items-center justify-center">
          <div class="text-white text-center p-6">
            <div class="text-6xl mb-2">📙</div>
            <h3 class="font-bold text-lg">Python Programming</h3>
          </div>
        </div>
        <div class="p-6">
          <div class="flex items-center justify-between mb-3">
            <span class="bg-teal-100 text-teal-800 px-3 py-1 rounded-full text-xs font-semibold">Programming</span>
            <span class="text-sm text-gray-600">540 pages</span>
          </div>
          <p class="text-gray-700 text-sm mb-4">
            Master Python from basics to advanced topics including OOP, data structures, and popular libraries.
          </p>
          <p class="text-xs text-gray-600 mb-4">
            <strong>Author:</strong> Dr. Kevin Lee<br>
            <strong>Added:</strong> Oct 15, 2025
          </p>
          <div class="flex gap-2">
            <button class="flex-1 bg-teal-600 text-white py-2 rounded-lg hover:bg-teal-700 font-semibold">
              📥 Download
            </button>
            <button class="px-4 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
              👁️
            </button>
          </div>
        </div>
      </div>

      <!-- E-Book Card 9 -->
      <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-700 h-48 rounded-t-lg flex items-center justify-center">
          <div class="text-white text-center p-6">
            <div class="text-6xl mb-2">📕</div>
            <h3 class="font-bold text-lg">Cloud Computing</h3>
          </div>
        </div>
        <div class="p-6">
          <div class="flex items-center justify-between mb-3">
            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-semibold">Cloud</span>
            <span class="text-sm text-gray-600">450 pages</span>
          </div>
          <p class="text-gray-700 text-sm mb-4">
            Learn cloud platforms, services, deployment models, and building scalable cloud-native applications.
          </p>
          <p class="text-xs text-gray-600 mb-4">
            <strong>Author:</strong> Rachel Martinez<br>
            <strong>Added:</strong> Oct 18, 2025
          </p>
          <div class="flex gap-2">
            <button class="flex-1 bg-yellow-600 text-white py-2 rounded-lg hover:bg-yellow-700 font-semibold">
              📥 Download
            </button>
            <button class="px-4 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
              👁️
            </button>
          </div>
        </div>
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