// ============================================
// BOOKS DATABASE
// ============================================
const books = [
    {
      id: 1,
      title: "Introduction to Programming",
      author: "Dr. John Smith",
      category: "Programming",
      pages: 420,
      description: "Comprehensive guide covering fundamental programming concepts, algorithms, and problem-solving techniques.",
      added: "Sept 15, 2025",
      color: "blue",
      emoji: "📘",
      level: "beginner",
      tags: ["basics", "algorithms", "fundamentals"],
      pdfUrl: "https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf"
    },
    {
      id: 2,
      title: "Database Systems",
      author: "Prof. Maria Garcia",
      category: "Database",
      pages: 580,
      description: "Complete coverage of relational databases, SQL, normalization, and modern database management concepts.",
      added: "Sept 20, 2025",
      color: "green",
      emoji: "📗",
      level: "intermediate",
      tags: ["sql", "database", "relational"],
      pdfUrl: "https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf"
    },
    {
      id: 3,
      title: "Web Development",
      author: "Sarah Johnson",
      category: "Web Development",
      pages: 520,
      description: "Modern web development with HTML5, CSS3, JavaScript, and responsive design principles.",
      added: "Oct 1, 2025",
      color: "purple",
      emoji: "📕",
      level: "beginner",
      tags: ["html", "css", "javascript", "responsive"],
      pdfUrl: "https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf"
    },
    {
      id: 4,
      title: "Data Structures & Algorithms",
      author: "Dr. Robert Chen",
      category: "Programming",
      pages: 650,
      description: "In-depth exploration of data structures, algorithm design, complexity analysis, and optimization techniques.",
      added: "Aug 28, 2025",
      color: "orange",
      emoji: "📙",
      level: "advanced",
      tags: ["algorithms", "data-structures", "optimization"],
      pdfUrl: "https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf"
    },
    {
      id: 5,
      title: "Computer Networks",
      author: "Prof. Lisa Anderson",
      category: "Networks",
      pages: 490,
      description: "Comprehensive guide to networking fundamentals, protocols, security, and network architecture.",
      added: "Sept 10, 2025",
      color: "red",
      emoji: "📕",
      level: "intermediate",
      tags: ["networking", "protocols", "security"],
      pdfUrl: "https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf"
    },
    {
      id: 6,
      title: "Software Engineering",
      author: "Michael Brown",
      category: "Software Engineering",
      pages: 720,
      description: "Software development lifecycle, design patterns, testing methodologies, and project management principles.",
      added: "Sept 5, 2025",
      color: "indigo",
      emoji: "📘",
      level: "intermediate",
      tags: ["software", "design-patterns", "testing"],
      pdfUrl: "https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf"
    },
    {
      id: 7,
      title: "Mobile App Development",
      author: "Emma Wilson",
      category: "Mobile Dev",
      pages: 380,
      description: "Build native and cross-platform mobile applications for iOS and Android with modern frameworks.",
      added: "Oct 10, 2025",
      color: "pink",
      emoji: "📗",
      level: "intermediate",
      tags: ["mobile", "ios", "android"],
      pdfUrl: "https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf"
    },
    {
      id: 8,
      title: "Python Programming",
      author: "Dr. Kevin Lee",
      category: "Programming",
      pages: 540,
      description: "Master Python from basics to advanced topics including OOP, data structures, and popular libraries.",
      added: "Oct 15, 2025",
      color: "teal",
      emoji: "📙",
      level: "beginner",
      tags: ["python", "oop", "libraries"],
      pdfUrl: "https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf"
    },
    {
      id: 9,
      title: "Cloud Computing",
      author: "Rachel Martinez",
      category: "Cloud",
      pages: 450,
      description: "Learn cloud platforms, services, deployment models, and building scalable cloud-native applications.",
      added: "Oct 18, 2025",
      color: "yellow",
      emoji: "📕",
      level: "advanced",
      tags: ["cloud", "aws", "azure", "deployment"],
      pdfUrl: "https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf"
    }
  ];
  
  // ============================================
  // COLOR CONFIGURATION
  // ============================================
  const colorClasses = {
    blue: { 
      gradient: 'from-blue-500 to-blue-700', 
      badge: 'bg-blue-100 text-blue-800', 
      button: 'bg-blue-600 hover:bg-blue-700' 
    },
    green: { 
      gradient: 'from-green-500 to-green-700', 
      badge: 'bg-green-100 text-green-800', 
      button: 'bg-green-600 hover:bg-green-700' 
    },
    purple: { 
      gradient: 'from-purple-500 to-purple-700', 
      badge: 'bg-purple-100 text-purple-800', 
      button: 'bg-purple-600 hover:bg-purple-700' 
    },
    orange: { 
      gradient: 'from-orange-500 to-orange-700', 
      badge: 'bg-orange-100 text-orange-800', 
      button: 'bg-orange-600 hover:bg-orange-700' 
    },
    red: { 
      gradient: 'from-red-500 to-red-700', 
      badge: 'bg-red-100 text-red-800', 
      button: 'bg-red-600 hover:bg-red-700' 
    },
    indigo: { 
      gradient: 'from-indigo-500 to-indigo-700', 
      badge: 'bg-indigo-100 text-indigo-800', 
      button: 'bg-indigo-600 hover:bg-indigo-700' 
    },
    pink: { 
      gradient: 'from-pink-500 to-pink-700', 
      badge: 'bg-pink-100 text-pink-800', 
      button: 'bg-pink-600 hover:bg-pink-700' 
    },
    teal: { 
      gradient: 'from-teal-500 to-teal-700', 
      badge: 'bg-teal-100 text-teal-800', 
      button: 'bg-teal-600 hover:bg-teal-700' 
    },
    yellow: { 
      gradient: 'from-yellow-500 to-yellow-700', 
      badge: 'bg-yellow-100 text-yellow-800', 
      button: 'bg-yellow-600 hover:bg-yellow-700' 
    }
  };
  
  // ============================================
  // STATE MANAGEMENT
  // ============================================
  let currentBook = null;
  let userReadingHistory = [];
  let favorites = JSON.parse(localStorage.getItem('favorites') || '[]');
  
  // ============================================
  // AI RECOMMENDATION ENGINE
  // ============================================
  class AIRecommendationEngine {
    static generateSmartSuggestions() {
      const suggestions = [];
      
      // Beginner-friendly books
      const beginnerBooks = books.filter(b => b.level === 'beginner');
      if (beginnerBooks.length > 0) {
        const book = beginnerBooks[Math.floor(Math.random() * beginnerBooks.length)];
        suggestions.push({
          text: `Start with "${book.title}" - Perfect for beginners!`,
          action: () => this.highlightBook(book.id),
          icon: '🎯'
        });
      }
      
      // Popular category
      const programmingBooks = books.filter(b => b.category === 'Programming');
      if (programmingBooks.length > 0) {
        suggestions.push({
          text: `${programmingBooks.length} Programming books available`,
          action: () => filterCategory('Programming'),
          icon: '💻'
        });
      }
      
      // Latest additions
      const sortedBooks = [...books].sort((a, b) => new Date(b.added) - new Date(a.added));
      if (sortedBooks.length > 0) {
        const latest = sortedBooks[0];
        suggestions.push({
          text: `New: "${latest.title}"`,
          action: () => this.highlightBook(latest.id),
          icon: '✨'
        });
      }
      
      // Quick reads
      const quickReads = books.filter(b => b.pages < 400);
      if (quickReads.length > 0) {
        const book = quickReads[Math.floor(Math.random() * quickReads.length)];
        suggestions.push({
          text: `Quick read: "${book.title}" (${book.pages} pages)`,
          action: () => this.highlightBook(book.id),
          icon: '⚡'
        });
      }
      
      return suggestions;
    }
    
    static highlightBook(bookId) {
      const book = books.find(b => b.id === bookId);
      if (book) {
        document.getElementById('searchInput').value = book.title;
        searchBooks();
        
        // Scroll to the book
        setTimeout(() => {
          const bookElement = document.querySelector(`[data-book-id="${bookId}"]`);
          if (bookElement) {
            bookElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            bookElement.classList.add('ring-4', 'ring-purple-500');
            setTimeout(() => {
              bookElement.classList.remove('ring-4', 'ring-purple-500');
            }, 2000);
          }
        }, 500);
      }
    }
    
    static getSmartResponse(message) {
      const lowerMessage = message.toLowerCase();
      
      // Learning path recommendations
      if (lowerMessage.includes('learning path') || lowerMessage.includes('where to start') || lowerMessage.includes('beginner')) {
        const beginnerBooks = books.filter(b => b.level === 'beginner');
        const path = beginnerBooks.slice(0, 3);
        return `🎯 Recommended Learning Path:
  
  1️⃣ Start: ${path[0]?.title || 'Introduction to Programming'}
  2️⃣ Then: ${path[1]?.title || 'Web Development'}
  3️⃣ Next: ${path[2]?.title || 'Python Programming'}
  
  This progression will build a solid foundation! Would you like details on any of these books?`;
      }
      
      // Book comparison
      if (lowerMessage.includes('compare') || lowerMessage.includes('difference between')) {
        return `🔍 I can help you compare books! Tell me which books you'd like to compare, or ask me about:
  
  • Python Programming vs Introduction to Programming
  • Database Systems vs Cloud Computing
  • Any specific books you're curious about
  
  What would you like to compare?`;
      }
      
      // Study recommendations
      if (lowerMessage.includes('exam') || lowerMessage.includes('study') || lowerMessage.includes('prepare')) {
        return `📚 Study Recommendations:
  
  For comprehensive exam prep:
  • Data Structures & Algorithms - Core concepts
  • Database Systems - For database courses
  • Software Engineering - Best practices
  
  Pro tip: Start with the book that matches your exam topic, then use related books for additional context!`;
      }
      
      // Career-focused recommendations
      if (lowerMessage.includes('career') || lowerMessage.includes('job') || lowerMessage.includes('professional')) {
        return `💼 Career-Focused Book Recommendations:
  
  🌐 Web Developer: Web Development + Python Programming
  💾 Database Admin: Database Systems + Cloud Computing
  🔧 Software Engineer: Software Engineering + Data Structures
  📱 Mobile Developer: Mobile App Development + Programming Basics
  
  Which career path interests you?`;
      }
      
      // Time-based recommendations
      if (lowerMessage.includes('quick') || lowerMessage.includes('short') || lowerMessage.includes('fast')) {
        const quickBooks = books.filter(b => b.pages < 400).slice(0, 3);
        return `⚡ Quick Reads (Under 400 pages):
  
  ${quickBooks.map((b, i) => `${i+1}. ${b.title} - ${b.pages} pages`).join('\n')}
  
  These are perfect for busy schedules!`;
      }
      
      // Advanced topics
      if (lowerMessage.includes('advanced') || lowerMessage.includes('expert') || lowerMessage.includes('deep dive')) {
        const advancedBooks = books.filter(b => b.level === 'advanced');
        return `🚀 Advanced Topics:
  
  ${advancedBooks.map(b => `• ${b.title} - ${b.description.slice(0, 60)}...`).join('\n')}
  
  Ready to level up your skills?`;
      }
      
      return null; // Will use default responses if no smart match
    }
  }
  
  // ============================================
  // RENDER FUNCTIONS
  // ============================================
  function renderBooks(booksToRender) {
    const grid = document.getElementById('booksGrid');
    const noResults = document.getElementById('noResults');
    
    if (booksToRender.length === 0) {
      grid.innerHTML = '';
      noResults.classList.remove('hidden');
      return;
    }
  
    noResults.classList.add('hidden');
    grid.innerHTML = booksToRender.map(book => {
      const colors = colorClasses[book.color];
      const isFavorite = favorites.includes(book.id);
      
      return `
        <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-all book-card" data-book-id="${book.id}">
          <div class="bg-gradient-to-br ${colors.gradient} h-40 rounded-t-lg flex items-center justify-center relative">
            <button 
              onclick="toggleFavorite(${book.id}, event)" 
              class="absolute top-3 right-3 text-white hover:scale-110 transition-transform"
            >
              ${isFavorite ? '⭐' : '☆'}
            </button>
            <div class="text-white text-center p-4">
              <div class="text-5xl mb-2">${book.emoji}</div>
              <h3 class="font-bold text-base">${book.title}</h3>
            </div>
          </div>
          <div class="p-4">
            <div class="flex items-center justify-between mb-2">
              <span class="${colors.badge} px-2 py-1 rounded-full text-xs font-semibold">${book.category}</span>
              <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded-full text-xs">${book.level}</span>
            </div>
            <p class="text-gray-700 text-xs mb-2 line-clamp-2">${book.description}</p>
            <p class="text-xs text-gray-600 mb-3">
              <strong>👤</strong> ${book.author}<br>
              <strong>📄</strong> ${book.pages} pages
            </p>
            <div class="flex gap-2">
              <button 
                onclick="viewPdf(${book.id})" 
                class="flex-1 ${colors.button} text-white py-2 rounded-lg font-semibold text-sm transition-all"
              >
                👁️ Preview
              </button>
              <button 
                onclick="downloadBook(${book.id})" 
                class="px-4 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold text-sm"
              >
                📥
              </button>
            </div>
          </div>
        </div>
      `;
    }).join('');
  }
  
  function renderAISuggestions() {
    const suggestions = AIRecommendationEngine.generateSmartSuggestions();
    const container = document.getElementById('suggestionsList');
    
    container.innerHTML = suggestions.map(s => `
      <button 
        onclick='${s.action.toString().replace(/^.*{|}$/g, '')}' 
        class="ai-suggestion bg-white hover:bg-purple-50 border border-purple-200 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 transition-all hover:shadow-md"
      >
        ${s.icon} ${s.text}
      </button>
    `).join('');
    
    document.getElementById('aiSuggestionsCount').textContent = suggestions.length;
  }
  
  function updateStats() {
    const categories = [...new Set(books.map(b => b.category))];
    document.getElementById('totalBooks').textContent = books.length;
    document.getElementById('totalCategories').textContent = categories.length;
  }
  
  // ============================================
  // SEARCH & FILTER
  // ============================================
  function searchBooks() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const category = document.getElementById('categoryFilter').value;
    
    const filtered = books.filter(book => {
      const matchesSearch = 
        book.title.toLowerCase().includes(searchTerm) ||
        book.author.toLowerCase().includes(searchTerm) ||
        book.description.toLowerCase().includes(searchTerm) ||
        book.tags.some(tag => tag.includes(searchTerm));
      
      const matchesCategory = !category || book.category === category;
      return matchesSearch && matchesCategory;
    });
    
    renderBooks(filtered);
  }
  
  function filterCategory(category) {
    document.getElementById('categoryFilter').value = category;
    document.getElementById('searchInput').value = '';
    
    // Update tab styles
    document.querySelectorAll('.category-tab').forEach(tab => {
      tab.classList.remove('bg-gradient-to-r', 'from-purple-600', 'to-blue-600', 'text-white', 'shadow');
      tab.classList.add('bg-white', 'text-gray-700');
    });
    
    event.target.classList.remove('bg-white', 'text-gray-700');
    event.target.classList.add('bg-gradient-to-r', 'from-purple-600', 'to-blue-600', 'text-white', 'shadow');
    
    searchBooks();
  }
  
  // ============================================
  // PDF VIEWER
  // ============================================
  function viewPdf(bookId) {
    const book = books.find(b => b.id === bookId);
    if (book) {
      currentBook = book;
      document.getElementById('pdfTitle').textContent = book.title;
      document.getElementById('pdfAuthor').textContent = `by ${book.author}`;
      document.getElementById('pdfFrame').src = book.pdfUrl;
      document.getElementById('pdfModal').classList.remove('hidden');
      
      // Add to reading history
      if (!userReadingHistory.includes(bookId)) {
        userReadingHistory.push(bookId);
      }
    }
  }
  
  function closePdfViewer() {
    document.getElementById('pdfModal').classList.add('hidden');
    document.getElementById('pdfFrame').src = '';
  }
  
  function downloadPdf() {
    if (currentBook) {
      window.open(currentBook.pdfUrl, '_blank');
      addChatMessage(`📥 Opening "${currentBook.title}" for download.`, 'bot');
    }
  }
  
  function downloadBook(bookId) {
    const book = books.find(b => b.id === bookId);
    if (book) {
      window.open(book.pdfUrl, '_blank');
      addChatMessage(`📥 Downloading "${book.title}". Check your downloads folder!`, 'bot');
      
      if (document.getElementById('chatWindow').classList.contains('hidden')) {
        showChatNotification();
      }
    }
  }
  
  // ============================================
  // FAVORITES
  // ============================================
  function toggleFavorite(bookId, event) {
    event.stopPropagation();
    
    const index = favorites.indexOf(bookId);
    if (index > -1) {
      favorites.splice(index, 1);
    } else {
      favorites.push(bookId);
    }
    
    localStorage.setItem('favorites', JSON.stringify(favorites));
    renderBooks(books); // Re-render to update star icons
  }
  
  function addToFavorites() {
    if (currentBook && !favorites.includes(currentBook.id)) {
      favorites.push(currentBook.id);
      localStorage.setItem('favorites', JSON.stringify(favorites));
      addChatMessage(`⭐ Added "${currentBook.title}" to your favorites!`, 'bot');
    }
  }
  
  // ============================================
  // CHATBOT
  // ============================================
  function toggleChat() {
    const chatWindow = document.getElementById('chatWindow');
    const chatNotification = document.getElementById('chatNotification');
    
    chatWindow.classList.toggle('hidden');
    chatNotification.style.display = 'none';
    
    if (!chatWindow.classList.contains('hidden')) {
      document.getElementById('chatInput').focus();
    }
  }
  
  function showChatNotification() {
    const chatNotification = document.getElementById('chatNotification');
    chatNotification.style.display = 'flex';
    
    setTimeout(() => {
      chatNotification.style.display = 'none';
    }, 3000);
  }
  
  function sendMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    addChatMessage(message, 'user');
    input.value = '';
    
    // Show typing indicator
    showTypingIndicator();
    
    setTimeout(() => {
      hideTypingIndicator();
      const response = getBotResponse(message);
      addChatMessage(response, 'bot');
    }, 1000);
  }
  
  function quickMessage(message) {
    document.getElementById('chatInput').value = message;
    sendMessage();
  }
  
  function showTypingIndicator() {
    document.getElementById('typingIndicator').classList.remove('hidden');
    const messagesDiv = document.getElementById('chatMessages');
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
  }
  
  function hideTypingIndicator() {
    document.getElementById('typingIndicator').classList.add('hidden');
  }
  
  function addChatMessage(message, sender) {
    const messagesDiv = document.getElementById('chatMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chat-bubble flex items-start ' + (sender === 'user' ? 'justify-end' : '');
    
    const formattedMessage = escapeHtml(message).replace(/\n/g, '<br>');
    
    if (sender === 'user') {
      messageDiv.innerHTML = `
        <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white p-3 rounded-lg chat-message shadow-md" style="max-width: 85%;">
          <p class="text-sm">${formattedMessage}</p>
        </div>
      `;
    } else {
      messageDiv.innerHTML = `
        <div class="bg-gradient-to-r from-purple-50 to-blue-50 text-gray-800 p-3 rounded-lg chat-message border border-purple-200 shadow-sm" style="max-width: 85%;">
          <p class="text-sm">${formattedMessage}</p>
        </div>
      `;
    }
    
    messagesDiv.appendChild(messageDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
  }
  
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
  
  function getBotResponse(message) {
    const lowerMessage = message.toLowerCase();
    
    // Try smart AI response first
    const smartResponse = AIRecommendationEngine.getSmartResponse(message);
    if (smartResponse) {
      return smartResponse;
    }
    
    // Book search by title
    const bookMatch = books.find(book => 
      lowerMessage.includes(book.title.toLowerCase())
    );
    
    if (bookMatch) {
      document.getElementById('searchInput').value = bookMatch.title;
      searchBooks();
      return `📚 Found "${bookMatch.title}"!
  
  📖 ${bookMatch.description}
  
  👤 Author: ${bookMatch.author}
  📄 Pages: ${bookMatch.pages}
  🎯 Level: ${bookMatch.level}
  
  I've shown it in the results above. Click Preview to read or Download to save it!`;
    }
    
    // Category searches
    const categoryMap = {
      'programming': 'Programming',
      'database': 'Database',
      'web development': 'Web Development',
      'web dev': 'Web Development',
      'network': 'Networks',
      'mobile': 'Mobile Dev',
      'cloud': 'Cloud'
    };
    
    for (const [key, category] of Object.entries(categoryMap)) {
      if (lowerMessage.includes(key)) {
        const categoryBooks = books.filter(b => b.category === category);
        filterCategory(category);
        return `📚 Found ${categoryBooks.length} ${category} books:
  
  ${categoryBooks.slice(0, 3).map(b => `• ${b.title} (${b.level})`).join('\n')}
  
  ${categoryBooks.length > 3 ? `...and ${categoryBooks.length - 3} more!` : ''}
  
  Check them out above! 👆`;
      }
    }
    
    // Help commands
    if (lowerMessage.includes('help') || lowerMessage.includes('what can you')) {
      return `🤖 I'm your AI Library Assistant! I can help you:
  
  🔍 **Find Books**: "Show me Python books"
  💡 **Recommend**: "Suggest a beginner book"
  🎯 **Learning Paths**: "Where should I start?"
  📊 **Compare**: "Compare two books"
  ⭐ **Favorites**: Just click the star on any book!
  
  Try asking: "What's a good book for learning web development?"`;
    }
    
    // Default response
    return `🤔 I'd love to help you find the perfect book!
  
  Try asking me:
  • "Show me programming books"
  • "Recommend a book for beginners"
  • "What's good for learning Python?"
  • "Quick reads under 400 pages"
  
  What are you interested in learning?`;
  }
  
  // ============================================
  // INITIALIZATION
  // ============================================
  document.addEventListener('DOMContentLoaded', () => {
    renderBooks(books);
    renderAISuggestions();
    updateStats();
    
    // Real-time search
    document.getElementById('searchInput').addEventListener('input', searchBooks);
    
    // Welcome message
    console.log('🤖 AI Library Assistant initialized!');
  });
