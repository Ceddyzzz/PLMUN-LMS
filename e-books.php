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
  <style>
    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }
    
    .chat-bubble {
      animation: slideUp 0.3s ease-out;
    }
    
    .chat-message {
      word-wrap: break-word;
      overflow-wrap: break-word;
      white-space: pre-wrap;
    }
    
    .pdf-viewer {
      height: 80vh;
    }
    
    .typing-indicator {
      animation: pulse 1.5s ease-in-out infinite;
    }
    
    .book-card {
      transition: all 0.3s ease;
    }
    
    .book-card:hover {
      transform: translateY(-4px);
    }
    
    .ai-suggestion {
      animation: fadeIn 0.5s ease-out;
    }
  </style>
</head>
<body class="bg-gray-100">
  <?php include 'includes/header.php'; ?>

  <main class="p-6 max-w-7xl mx-auto">
    <!-- Header with AI Stats -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-3xl font-bold">📖 Digital Library</h2>
        <p class="text-gray-600 text-sm mt-1">AI-Powered Book Discovery</p>
      </div>
      <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-lg shadow-lg">
        <div class="text-xs opacity-90">AI Assistant Active</div>
        <div class="text-xl font-bold">🤖 Ready to Help</div>
      </div>
    </div>

    <!-- AI Smart Suggestions Bar -->
    <div id="aiSuggestions" class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-lg shadow p-4 mb-6 border border-purple-200">
      <div class="flex items-start">
        <div class="text-2xl mr-3">💡</div>
        <div class="flex-1">
          <h3 class="font-bold text-gray-800 mb-2">AI Recommendations for You</h3>
          <div id="suggestionsList" class="flex flex-wrap gap-2">
            <!-- AI suggestions will be populated here -->
          </div>
        </div>
      </div>
    </div>
    
    <!-- Smart Search with AI -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
      <div class="flex items-center mb-4">
        <span class="text-2xl mr-3">🔍</span>
        <h3 class="text-lg font-bold">Smart Search</h3>
        <span class="ml-2 bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded-full">AI-Powered</span>
      </div>
      <div class="flex flex-col md:flex-row gap-4">
        <div class="flex-1 relative">
          <input 
            type="text" 
            id="searchInput"
            placeholder="Ask anything... e.g., 'Books for beginners in Python' or 'Advanced database concepts'" 
            class="w-full p-3 pr-10 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
          >
          <div class="absolute right-3 top-3 text-gray-400">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
          </div>
        </div>
        <select id="categoryFilter" class="p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
          <option value="">All Categories</option>
          <option value="Programming">Programming</option>
          <option value="Web Development">Web Development</option>
          <option value="Database">Database</option>
          <option value="Networks">Networks</option>
          <option value="Software Engineering">Software Engineering</option>
          <option value="Mobile Dev">Mobile Dev</option>
          <option value="Cloud">Cloud</option>
        </select>
        <button onclick="searchBooks()" class="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-3 rounded-lg hover:from-purple-700 hover:to-blue-700 font-semibold shadow-lg">
          Search
        </button>
      </div>
    </div>

    <!-- Quick Category Filters -->
    <div class="flex space-x-3 mb-6 overflow-x-auto pb-2">
      <button onclick="filterCategory('')" class="category-tab px-4 py-2 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg whitespace-nowrap shadow">All Books</button>
      <button onclick="filterCategory('Programming')" class="category-tab px-4 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-50 whitespace-nowrap shadow">💻 Programming</button>
      <button onclick="filterCategory('Web Development')" class="category-tab px-4 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-50 whitespace-nowrap shadow">🌐 Web Dev</button>
      <button onclick="filterCategory('Database')" class="category-tab px-4 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-50 whitespace-nowrap shadow">💾 Database</button>
      <button onclick="filterCategory('Networks')" class="category-tab px-4 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-50 whitespace-nowrap shadow">🔗 Networks</button>
      <button onclick="filterCategory('Cloud')" class="category-tab px-4 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-50 whitespace-nowrap shadow">☁️ Cloud</button>
    </div>

    <!-- AI Reading Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4 flex items-center">
        <div class="bg-blue-100 text-blue-600 p-3 rounded-lg mr-4">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
          </svg>
        </div>
        <div>
          <p class="text-gray-600 text-sm">Total Books</p>
          <p class="text-2xl font-bold" id="totalBooks">0</p>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow p-4 flex items-center">
        <div class="bg-green-100 text-green-600 p-3 rounded-lg mr-4">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <div>
          <p class="text-gray-600 text-sm">Categories</p>
          <p class="text-2xl font-bold" id="totalCategories">0</p>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow p-4 flex items-center">
        <div class="bg-purple-100 text-purple-600 p-3 rounded-lg mr-4">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
          </svg>
        </div>
        <div>
          <p class="text-gray-600 text-sm">AI Suggestions</p>
          <p class="text-2xl font-bold" id="aiSuggestionsCount">0</p>
        </div>
      </div>
    </div>

    <!-- Books Grid -->
    <div id="booksGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <!-- Books will be rendered here -->
    </div>

    <!-- No Results -->
    <div id="noResults" class="hidden text-center py-12">
      <div class="text-6xl mb-4">🔍</div>
      <h3 class="text-2xl font-bold text-gray-700 mb-2">No books found</h3>
      <p class="text-gray-600 mb-4">Try adjusting your search or ask our AI assistant for help</p>
      <button onclick="toggleChat()" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 font-semibold">
        Ask AI Assistant
      </button>
    </div>
  </main>

  <!-- PDF Viewer Modal -->
  <div id="pdfModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg w-full max-w-6xl pdf-viewer flex flex-col shadow-2xl">
      <div class="flex items-center justify-between p-4 border-b bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-t-lg">
        <div>
          <h3 id="pdfTitle" class="text-xl font-bold"></h3>
          <p id="pdfAuthor" class="text-sm opacity-90"></p>
        </div>
        <button onclick="closePdfViewer()" class="text-white hover:text-gray-200 text-3xl font-bold">×</button>
      </div>
      <div class="flex-1 overflow-auto p-4">
        <iframe id="pdfFrame" class="w-full h-full border-0 rounded" src=""></iframe>
      </div>
      <div class="p-4 border-t flex gap-2">
        <button onclick="downloadPdf()" class="flex-1 bg-gradient-to-r from-green-600 to-green-700 text-white py-3 rounded-lg hover:from-green-700 hover:to-green-800 font-semibold shadow-lg">
          📥 Download PDF
        </button>
        <button onclick="addToFavorites()" class="px-6 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 font-semibold">
          ⭐ Favorite
        </button>
        <button onclick="closePdfViewer()" class="px-6 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold">
          Close
        </button>
      </div>
    </div>
  </div>

  <!-- AI Chatbot -->
  <div id="chatbot" class="fixed bottom-6 right-6 z-50">
    <!-- Chat Button -->
    <button id="chatButton" onclick="toggleChat()" class="bg-gradient-to-r from-purple-600 to-blue-600 text-white p-4 rounded-full shadow-2xl hover:shadow-3xl transition-all transform hover:scale-110">
      <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
      </svg>
      <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center" id="chatNotification" style="display: none;">!</span>
    </button>

    <!-- Chat Window -->
    <div id="chatWindow" class="hidden bg-white rounded-2xl shadow-2xl w-96 flex flex-col mb-4" style="height: 550px;">
      <!-- Chat Header -->
      <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white p-4 rounded-t-2xl flex items-center justify-between flex-shrink-0">
        <div class="flex items-center">
          <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-3 text-2xl">
            🤖
          </div>
          <div>
            <h3 class="font-bold text-lg">AI Library Assistant</h3>
            <p class="text-xs opacity-90">Powered by Advanced AI</p>
          </div>
        </div>
        <button onclick="toggleChat()" class="text-white hover:text-gray-200 flex-shrink-0">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <!-- Chat Messages -->
      <div id="chatMessages" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50 min-h-0">
        <div class="chat-bubble flex items-start">
          <div class="bg-gradient-to-r from-purple-100 to-blue-100 text-gray-800 p-3 rounded-lg max-w-[85%] border border-purple-200">
            <p class="text-sm">👋 Hello! I'm your AI Library Assistant. I can help you:

• 🔍 Find books instantly
• 💡 Get smart recommendations
• 📚 Suggest learning paths
• ❓ Answer book-related questions

Try asking: "What's a good book for learning Python?"</p>
          </div>
        </div>
      </div>

      <!-- Typing Indicator -->
      <div id="typingIndicator" class="hidden px-4 pb-2">
        <div class="bg-gray-200 rounded-lg p-3 inline-flex items-center">
          <div class="flex space-x-1">
            <div class="w-2 h-2 bg-gray-500 rounded-full typing-indicator"></div>
            <div class="w-2 h-2 bg-gray-500 rounded-full typing-indicator" style="animation-delay: 0.2s"></div>
            <div class="w-2 h-2 bg-gray-500 rounded-full typing-indicator" style="animation-delay: 0.4s"></div>
          </div>
          <span class="ml-2 text-xs text-gray-600">AI is thinking...</span>
        </div>
      </div>

      <!-- Chat Input -->
      <div class="p-4 border-t bg-white rounded-b-2xl flex-shrink-0">
        <div class="flex gap-2 mb-2">
          <input 
            type="text" 
            id="chatInput"
            placeholder="Ask me anything about books..."
            class="flex-1 p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm"
            onkeypress="if(event.key === 'Enter') sendMessage()"
          >
          <button onclick="sendMessage()" class="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-4 py-2 rounded-lg hover:from-purple-700 hover:to-blue-700 flex-shrink-0 shadow-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
            </svg>
          </button>
        </div>
        <div class="flex flex-wrap gap-2">
          <button onclick="quickMessage('Recommend a programming book')" class="text-xs bg-purple-50 hover:bg-purple-100 text-purple-700 px-3 py-1.5 rounded-full border border-purple-200">
            💻 Programming
          </button>
          <button onclick="quickMessage('Show database books')" class="text-xs bg-blue-50 hover:bg-blue-100 text-blue-700 px-3 py-1.5 rounded-full border border-blue-200">
            💾 Database
          </button>
          <button onclick="quickMessage('Learning path for beginners')" class="text-xs bg-green-50 hover:bg-green-100 text-green-700 px-3 py-1.5 rounded-full border border-green-200">
            🎯 Learning Path
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="includes/ebooks-ai.js"></script>
</body>
</html>
