<?php
// Include main config
require_once 'config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get user info
$user = getCurrentUser();
$user_id = $user['id'] ?? 0;
$user_email = $user['email'] ?? '';
$user_name = $user['name'] ?? 'User';
$user_role = $user['role'] ?? 'student';

if ($user_id == 0) {
    header("Location: login.php");
    exit();
}

// Initialize database tables
initializeDatabaseTables($conn);

// Use environment variable or define fallback
define('OPENAI_API_KEY', $_ENV['OPENAI_API_KEY'] ?? 'your-api-key-here');

// Fetch teacher's sections if they're a teacher
$teacherSections = [];
if ($user_role === 'teacher') {
    // First, create a sample section if none exists
    $checkSections = $conn->query("SELECT COUNT(*) as count FROM sections WHERE teacher_id = $user_id");
    $sectionCount = $checkSections->fetch_assoc()['count'];
    
    if ($sectionCount == 0) {
        // Create a default section
        $conn->query("INSERT INTO sections (section_name, teacher_id) VALUES ('Default Section', $user_id)");
    }
    
    $sql = "SELECT s.id, s.section_name, 
                   COUNT(DISTINCT ss.student_id) as student_count
            FROM sections s
            LEFT JOIN section_students ss ON s.id = ss.section_id
            WHERE s.teacher_id = ?
            GROUP BY s.id
            ORDER BY s.section_name";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $teacherSections[] = $row;
        }
        $stmt->close();
    }
}

// Fetch student's sections if they're a student
$studentSections = [];
if ($user_role === 'student') {
    $sql = "SELECT s.id, s.section_name
            FROM sections s
            INNER JOIN section_students ss ON s.id = ss.section_id
            WHERE ss.student_id = ?
            ORDER BY s.section_name";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $studentSections[] = $row;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLMUN LMS - Quiz System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .chat-message { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .quiz-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .quiz-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-container {
            background: white;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            margin: 20px;
        }
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        .modal-body {
            padding: 20px;
        }
        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="bg-white text-purple-700 p-2 rounded-lg">
                        <i class="fas fa-graduation-cap text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">PLMUN LMS</h1>
                        <p class="text-sm opacity-90">AI-Powered Learning Management System</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="font-semibold"><?php echo htmlspecialchars($user_name); ?></p>
                        <p class="text-sm opacity-90 capitalize"><?php echo $user_role; ?></p>
                    </div>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
            
            <!-- Navigation - FIXED: Removed unnecessary JavaScript handlers -->
            <nav class="mt-4">
                <ul class="flex space-x-6 overflow-x-auto pb-2">
                    <li><a href="dashboard.php" class="flex items-center space-x-2 hover:text-yellow-300 py-2"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                    <li><a href="announcement.php" class="flex items-center space-x-2 hover:text-yellow-300 py-2"><i class="fas fa-bullhorn"></i><span>Announcements</span></a></li>
                    <li><a href="chat.php" class="flex items-center space-x-2 hover:text-yellow-300 py-2"><i class="fas fa-comments"></i><span>Chat</span></a></li>
                    <li><a href="assignment.php" class="flex items-center space-x-2 hover:text-yellow-300 py-2"><i class="fas fa-tasks"></i><span>Assignments</span></a></li>
                    <li><a href="calendar.php" class="flex items-center space-x-2 hover:text-yellow-300 py-2"><i class="fas fa-calendar"></i><span>Calendar</span></a></li>
                    <li><a href="e-books.php" class="flex items-center space-x-2 hover:text-yellow-300 py-2"><i class="fas fa-book"></i><span>E-Books</span></a></li>
                    <li class="flex items-center space-x-2 bg-white text-purple-700 px-4 py-2 rounded-lg font-bold">
                        <i class="fas fa-question-circle"></i>
                        <span>Quiz System</span>
                    </li>
                    <li><a href="grades.php" class="flex items-center space-x-2 hover:text-yellow-300 py-2"><i class="fas fa-chart-line"></i><span>Grades</span></a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-4 md:p-6">
        <div class="grid lg:grid-cols-3 gap-6">
            
            <!-- Left Section - Quiz Content -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Teacher Dashboard -->
                <div id="teacherView" <?php echo $user_role !== 'teacher' ? 'class="hidden"' : ''; ?>>
                    <!-- Header with Stats -->
                    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                            <div>
                                <h2 class="text-3xl font-bold text-gray-800">📊 Quiz Dashboard</h2>
                                <p class="text-gray-600 mt-2">Create, manage, and track quizzes with AI assistance</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <?php if (!empty($teacherSections)): ?>
                                <button onclick="showSectionManagement()" 
                                        class="bg-gray-800 text-white px-5 py-3 rounded-lg hover:bg-gray-900 font-semibold transition flex items-center gap-2">
                                    <i class="fas fa-users"></i>
                                    <span>Manage Sections</span>
                                </button>
                                <?php endif; ?>
                                <button onclick="showAIQuizModal()" 
                                        class="gradient-bg text-white px-6 py-3 rounded-lg hover:opacity-90 font-semibold transition flex items-center gap-2 pulse">
                                    <i class="fas fa-robot"></i>
                                    <span>✨ Generate AI Quiz</span>
                                </button>
                                <button onclick="showCreateQuizModal()" 
                                        class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-semibold transition flex items-center gap-2">
                                    <i class="fas fa-plus"></i>
                                    <span>Manual Quiz</span>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Quick Stats -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-blue-700"><?php echo getQuizCount($conn, $user_id); ?></div>
                                <div class="text-sm text-blue-600">Total Quizzes</div>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-green-700"><?php echo count($teacherSections); ?></div>
                                <div class="text-sm text-green-600">Sections</div>
                            </div>
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-purple-700">
                                    <?php echo array_sum(array_column($teacherSections, 'student_count')); ?>
                                </div>
                                <div class="text-sm text-purple-600">Total Students</div>
                            </div>
                            <div class="bg-yellow-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-yellow-700">0</div>
                                <div class="text-sm text-yellow-600">Submissions Today</div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Filter -->
                    <?php if (!empty($teacherSections)): ?>
                    <div class="bg-white rounded-xl shadow p-4 mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-3">Filter by Section:</label>
                        <div class="flex flex-wrap gap-2">
                            <button onclick="filterBySection('all')" 
                                    class="filter-section-btn px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition"
                                    data-section="all">
                                <i class="fas fa-layer-group"></i> All Sections
                            </button>
                            <?php foreach ($teacherSections as $section): ?>
                            <button onclick="filterBySection(<?php echo $section['id']; ?>)" 
                                    class="filter-section-btn px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition flex items-center gap-2"
                                    data-section="<?php echo $section['id']; ?>">
                                <i class="fas fa-user-graduate"></i>
                                <span><?php echo htmlspecialchars($section['section_name']); ?></span>
                                <span class="bg-gray-300 text-gray-700 text-xs px-2 py-1 rounded-full">
                                    <?php echo $section['student_count']; ?>
                                </span>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Quizzes List -->
                    <div id="quizzesList" class="space-y-4">
                        <div class="bg-white rounded-xl shadow-lg p-8 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
                            </div>
                            <p class="text-gray-500 mb-2">Loading quizzes...</p>
                            <p class="text-sm text-gray-400">Please wait while we fetch your quizzes</p>
                        </div>
                    </div>
                </div>

                <!-- Student Dashboard -->
                <div id="studentView" <?php echo $user_role !== 'student' ? 'class="hidden"' : ''; ?>>
                    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                        <h2 class="text-3xl font-bold text-gray-800 mb-2">📚 Available Quizzes</h2>
                        <p class="text-gray-600">Test your knowledge with interactive quizzes</p>
                        
                        <!-- Student's Sections -->
                        <?php if (!empty($studentSections)): ?>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="text-gray-700 font-medium">Your Sections:</span>
                            <?php foreach ($studentSections as $section): ?>
                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium flex items-center gap-1">
                                <i class="fas fa-user-graduate"></i>
                                <?php echo htmlspecialchars($section['section_name']); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Available Quizzes -->
                    <div id="studentQuizzesList" class="space-y-4">
                        <div class="bg-white rounded-xl shadow-lg p-8 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
                            </div>
                            <p class="text-gray-500 mb-2">Loading available quizzes...</p>
                            <p class="text-sm text-gray-400">Checking for new assignments</p>
                        </div>
                    </div>
                </div>

                <!-- Quiz Taking Interface -->
                <div id="quizTakingView" class="hidden">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <button onclick="exitQuiz()" class="text-blue-600 hover:text-blue-800 font-medium mb-4 flex items-center gap-2">
                            <i class="fas fa-arrow-left"></i>
                            Back to Quizzes
                        </button>
                        <div id="quizContent"></div>
                    </div>
                </div>

                <!-- Quiz Results View -->
                <div id="quizResultsView" class="hidden">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <button onclick="exitResults()" class="text-blue-600 hover:text-blue-800 font-medium mb-4 flex items-center gap-2">
                            <i class="fas fa-arrow-left"></i>
                            Back to Quizzes
                        </button>
                        <div id="resultsContent"></div>
                    </div>
                </div>
            </div>

            <!-- Right Section - AI Assistant -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-lg h-[calc(100vh-8rem)] flex flex-col sticky top-6 overflow-hidden">
                    <!-- Chat Header -->
                    <div class="gradient-bg text-white p-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-white text-purple-600 w-10 h-10 rounded-full flex items-center justify-center">
                                <i class="fas fa-robot text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold">AI Quiz Assistant</h3>
                                <p class="text-xs opacity-90">Powered by OpenAI GPT</p>
                            </div>
                        </div>
                        <div class="mt-2 text-xs flex items-center gap-2">
                            <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                            <span>Online • Ready to help</span>
                        </div>
                    </div>

                    <!-- Chat Messages -->
                    <div id="chatMessages" class="flex-1 overflow-y-auto p-4 space-y-4">
                        <div class="chat-message bg-blue-50 rounded-xl p-4 border border-blue-100">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-robot"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-700">
                                        <strong class="text-blue-600">AI Assistant:</strong> Hello! I'm your AI Quiz Assistant. I can help you:
                                        <br><br>
                                        <?php if ($user_role === 'teacher'): ?>
                                        <strong>For Teachers:</strong><br>
                                        • <span class="text-blue-600">"Create a quiz about Web Development"</span><br>
                                        • <span class="text-blue-600">"Generate 15 questions on Database Systems"</span><br>
                                        • <span class="text-blue-600">"Make a quiz with mixed difficulty levels"</span><br>
                                        • <span class="text-blue-600">"Create exam for midterms"</span><br>
                                        <?php if (!empty($teacherSections)): ?>
                                        • <span class="text-blue-600">"Quiz for <?php echo htmlspecialchars($teacherSections[0]['section_name'] ?? 'my class'); ?>"</span><br>
                                        <?php endif; ?>
                                        <?php else: ?>
                                        <strong>For Students:</strong><br>
                                        • <span class="text-blue-600">"Help me study for upcoming quiz"</span><br>
                                        • <span class="text-blue-600">"Explain database normalization"</span><br>
                                        • <span class="text-blue-600">"Quiz me on programming basics"</span><br>
                                        • <span class="text-blue-600">"Create practice questions"</span><br>
                                        <?php endif; ?>
                                        <br>
                                        <em>What would you like me to help you with today?</em>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chat Input -->
                    <div class="p-4 border-t">
                        <div id="chatTyping" class="hidden mb-2 px-3">
                            <div class="flex items-center gap-2 text-gray-500 text-sm">
                                <div class="flex space-x-1">
                                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                                </div>
                                <span>AI is thinking...</span>
                            </div>
                        </div>
                        
                        <div class="flex space-x-2">
                            <input
                                type="text"
                                id="chatInput"
                                placeholder="Ask me to create a quiz or help with studying..."
                                class="flex-1 border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                onkeypress="handleChatKeyPress(event)"
                            />
                            <button
                                onclick="sendMessage()"
                                class="gradient-bg text-white px-5 py-3 rounded-xl hover:opacity-90 font-semibold transition"
                            >
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        
                        <div class="mt-2 flex flex-wrap gap-1 justify-center">
                            <button onclick="quickPrompt('Create a quiz about PHP programming')" class="text-xs bg-gray-100 hover:bg-gray-200 px-2 py-1 rounded">
                                PHP Quiz
                            </button>
                            <button onclick="quickPrompt('Generate 10 questions on database management')" class="text-xs bg-gray-100 hover:bg-gray-200 px-2 py-1 rounded">
                                Database
                            </button>
                            <button onclick="quickPrompt('Make a web development quiz with HTML, CSS, JS')" class="text-xs bg-gray-100 hover:bg-gray-200 px-2 py-1 rounded">
                                Web Dev
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- AI Quiz Generation Modal -->
    <div id="aiQuizModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header gradient-bg text-white">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="bg-white text-purple-600 w-12 h-12 rounded-full flex items-center justify-center">
                            <i class="fas fa-robot text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold">AI Quiz Generator</h3>
                            <p class="text-sm opacity-90">Let AI create your perfect quiz in seconds</p>
                        </div>
                    </div>
                    <button onclick="closeAIQuizModal()" class="text-white hover:text-gray-200 text-3xl">&times;</button>
                </div>
            </div>
            
            <div class="modal-body">
                <div class="space-y-6">
                    <!-- Subject -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-book text-blue-500"></i> Subject/Topic *
                        </label>
                        <input type="text" id="aiSubject" 
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., Web Development, Database Systems, Calculus, Physics">
                        <p class="text-xs text-gray-500 mt-1">What subject will this quiz cover?</p>
                    </div>
                    
                    <!-- AI Prompt -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-lightbulb text-yellow-500"></i> Quiz Instructions *
                        </label>
                        <textarea id="aiPrompt" 
                                  class="w-full border border-gray-300 rounded-lg px-4 py-3 h-40 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Describe exactly what you want in the quiz. Be specific!
Example: 'Create a comprehensive quiz about HTML and CSS covering:
- Selectors and specificity
- Box model and positioning
- Responsive design techniques
- CSS Grid and Flexbox
Include practical coding questions and real-world scenarios.'"></textarea>
                        <p class="text-xs text-gray-500 mt-1">Detailed prompts create better quizzes! Mention topics, difficulty, question types.</p>
                    </div>
                    
                    <!-- Configuration -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-question-circle text-green-500"></i> Questions
                            </label>
                            <select id="aiNumQuestions" class="w-full border border-gray-300 rounded-lg px-3 py-3">
                                <option value="5">5 Questions (Quick Quiz)</option>
                                <option value="10" selected>10 Questions (Standard)</option>
                                <option value="15">15 Questions (Comprehensive)</option>
                                <option value="20">20 Questions (Exam)</option>
                                <option value="25">25 Questions (Final Exam)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-chart-line text-red-500"></i> Difficulty
                            </label>
                            <select id="aiDifficulty" class="w-full border border-gray-300 rounded-lg px-3 py-3">
                                <option value="easy">Easy (Beginner)</option>
                                <option value="medium" selected>Medium (Intermediate)</option>
                                <option value="hard">Hard (Advanced)</option>
                                <option value="mixed">Mixed Levels</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-clock text-purple-500"></i> Duration
                            </label>
                            <select id="aiDuration" class="w-full border border-gray-300 rounded-lg px-3 py-3">
                                <option value="15">15 minutes</option>
                                <option value="30">30 minutes</option>
                                <option value="45" selected>45 minutes</option>
                                <option value="60">60 minutes</option>
                                <option value="90">90 minutes</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Sections -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-users text-indigo-500"></i> Assign to Sections *
                        </label>
                        <div class="border border-gray-300 rounded-lg p-4">
                            <div class="mb-3">
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="checkbox" id="aiSelectAllSections" onchange="toggleAISections()" 
                                           class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                                    <span class="font-medium text-gray-700">Select All Sections</span>
                                </label>
                            </div>
                            <div id="aiSectionCheckboxes" class="space-y-2 max-h-40 overflow-y-auto">
                                <?php foreach ($teacherSections as $section): ?>
                                <label class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded cursor-pointer">
                                    <input type="checkbox" name="ai_section" value="<?php echo $section['id']; ?>" 
                                           class="ai-section-checkbox w-4 h-4 text-blue-600 rounded">
                                    <div class="flex-1">
                                        <span class="font-medium"><?php echo htmlspecialchars($section['section_name']); ?></span>
                                        <span class="text-xs text-gray-500 ml-2">
                                            <?php echo $section['student_count']; ?> students
                                        </span>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Generation Status -->
                    <div id="aiGenerationStatus" class="hidden">
                        <div class="bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-xl p-6">
                            <div class="flex items-center justify-center space-x-4">
                                <div class="relative">
                                    <div class="w-16 h-16 border-4 border-blue-200 rounded-full"></div>
                                    <div class="w-16 h-16 border-4 border-blue-600 border-t-transparent rounded-full animate-spin absolute top-0 left-0"></div>
                                </div>
                                <div>
                                    <h4 class="font-bold text-lg text-blue-700">Generating Quiz with AI...</h4>
                                    <p class="text-blue-600 mt-2">This typically takes 10-30 seconds depending on complexity.</p>
                                    <div class="mt-4 flex items-center space-x-2 text-sm text-blue-600">
                                        <i class="fas fa-cog fa-spin"></i>
                                        <span>Processing your request</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <div class="flex space-x-3">
                    <button onclick="generateAIQuiz()" 
                            class="flex-1 bg-gradient-to-r from-purple-600 to-blue-600 text-white py-3 rounded-lg hover:opacity-90 font-semibold transition flex items-center justify-center gap-3">
                        <i class="fas fa-robot"></i>
                        <span>✨ Generate Quiz with AI</span>
                    </button>
                    <button onclick="closeAIQuizModal()" 
                            class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg hover:bg-gray-300 font-semibold transition">
                        Cancel
                    </button>
                </div>
                <p class="text-xs text-gray-500 text-center mt-3">
                    <i class="fas fa-info-circle"></i> AI-generated quizzes include explanations and are ready to use immediately.
                </p>
            </div>
        </div>
    </div>

    <!-- Create Quiz Modal (Manual) -->
    <div id="createQuizModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header bg-blue-600 text-white">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="bg-white text-blue-600 w-12 h-12 rounded-full flex items-center justify-center">
                            <i class="fas fa-plus text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold">Create Manual Quiz</h3>
                            <p class="text-sm opacity-90">Build your quiz step by step</p>
                        </div>
                    </div>
                    <button onclick="closeCreateQuizModal()" class="text-white hover:text-gray-200 text-3xl">&times;</button>
                </div>
            </div>
            
            <div class="modal-body">
                <div class="space-y-6">
                    <!-- Quiz Title -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-heading text-blue-500"></i> Quiz Title *
                        </label>
                        <input type="text" id="quizTitle" 
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., Midterm Exam - Database Systems">
                    </div>
                    
                    <!-- Subject -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-book text-green-500"></i> Subject *
                        </label>
                        <input type="text" id="quizSubject" 
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., Database Management Systems">
                    </div>
                    
                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-align-left text-yellow-500"></i> Description
                        </label>
                        <textarea id="quizDescription" 
                                  class="w-full border border-gray-300 rounded-lg px-4 py-3 h-32 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Describe the quiz objectives and topics covered..."></textarea>
                    </div>
                    
                    <!-- Duration -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-clock text-red-500"></i> Duration (minutes) *
                        </label>
                        <select id="quizDuration" class="w-full border border-gray-300 rounded-lg px-3 py-3">
                            <option value="30">30 minutes</option>
                            <option value="45" selected>45 minutes</option>
                            <option value="60">60 minutes</option>
                            <option value="90">90 minutes</option>
                            <option value="120">120 minutes</option>
                        </select>
                    </div>
                    
                    <!-- Sections -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-users text-purple-500"></i> Assign to Sections *
                        </label>
                        <div class="border border-gray-300 rounded-lg p-4">
                            <div class="mb-3">
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="checkbox" id="selectAllSections" onchange="toggleAllSections()" 
                                           class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                                    <span class="font-medium text-gray-700">Select All Sections</span>
                                </label>
                            </div>
                            <div id="sectionCheckboxes" class="space-y-2 max-h-40 overflow-y-auto">
                                <?php foreach ($teacherSections as $section): ?>
                                <label class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded cursor-pointer">
                                    <input type="checkbox" name="section" value="<?php echo $section['id']; ?>" 
                                           class="section-checkbox w-4 h-4 text-blue-600 rounded">
                                    <div class="flex-1">
                                        <span class="font-medium"><?php echo htmlspecialchars($section['section_name']); ?></span>
                                        <span class="text-xs text-gray-500 ml-2">
                                            <?php echo $section['student_count']; ?> students
                                        </span>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <div class="flex space-x-3">
                    <button onclick="createManualQuiz()" 
                            class="flex-1 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 font-semibold transition flex items-center justify-center gap-3">
                        <i class="fas fa-save"></i>
                        <span>Create Quiz & Add Questions</span>
                    </button>
                    <button onclick="closeCreateQuizModal()" 
                            class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg hover:bg-gray-300 font-semibold transition">
                        Cancel
                    </button>
                </div>
                <p class="text-xs text-gray-500 text-center mt-3">
                    <i class="fas fa-info-circle"></i> You'll be able to add questions after creating the quiz.
                </p>
            </div>
        </div>
    </div>

    <!-- Section Management Modal -->
    <div id="sectionManagementModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header bg-gray-800 text-white">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="bg-white text-gray-800 w-12 h-12 rounded-full flex items-center justify-center">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold">Manage Sections</h3>
                            <p class="text-sm opacity-90">Create and manage your class sections</p>
                        </div>
                    </div>
                    <button onclick="closeSectionManagement()" class="text-white hover:text-gray-200 text-3xl">&times;</button>
                </div>
            </div>
            
            <div class="modal-body">
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Create Section Form -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">Create New Section</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Section Name *</label>
                                <input type="text" id="newSectionName" 
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea id="newSectionDescription" 
                                          class="w-full border border-gray-300 rounded-lg px-4 py-2 h-24"></textarea>
                            </div>
                            <button onclick="createNewSection()" 
                                    class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                                Create Section
                            </button>
                        </div>
                    </div>
                    
                    <!-- Existing Sections -->
                    <div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">Your Sections</h4>
                        <div id="sectionsList" class="space-y-3 max-h-80 overflow-y-auto">
                            <?php foreach ($teacherSections as $section): ?>
                            <div class="bg-white border rounded-lg p-3">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h5 class="font-medium text-gray-800"><?php echo htmlspecialchars($section['section_name']); ?></h5>
                                        <p class="text-sm text-gray-500"><?php echo $section['student_count']; ?> students</p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button onclick="editSection(<?php echo $section['id']; ?>)" 
                                                class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteSection(<?php echo $section['id']; ?>)" 
                                                class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        const currentRole = '<?php echo $user_role; ?>';
        const teacherSections = <?php echo json_encode($teacherSections); ?>;
        const studentSections = <?php echo json_encode($studentSections); ?>;
        const userId = <?php echo $user_id; ?>;
        const userName = '<?php echo addslashes($user_name); ?>';
        
        let currentQuiz = null;
        let studentAnswers = {};
        let currentSectionFilter = 'all';
        let quizTimer = null;
        let timeLeft = 0;

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Quiz page loaded');
            loadQuizzes();
            
            // Check for AI quota
            if (currentRole === 'teacher') {
                checkAIQuota();
            }
        });

        // Modal Functions
        function showAIQuizModal() {
            console.log('Showing AI Quiz Modal');
            if (teacherSections.length === 0) {
                showNotification('warning', 'Please create sections first before generating quizzes.');
                showSectionManagement();
                return;
            }
            
            document.getElementById('aiQuizModal').style.display = 'flex';
        }

        function closeAIQuizModal() {
            console.log('Closing AI Quiz Modal');
            document.getElementById('aiQuizModal').style.display = 'none';
            document.getElementById('aiSubject').value = '';
            document.getElementById('aiPrompt').value = '';
        }

        function showCreateQuizModal() {
            console.log('Showing Create Quiz Modal');
            if (teacherSections.length === 0) {
                showNotification('warning', 'Please create sections first before creating quizzes.');
                showSectionManagement();
                return;
            }
            
            document.getElementById('createQuizModal').style.display = 'flex';
        }

        function closeCreateQuizModal() {
            console.log('Closing Create Quiz Modal');
            document.getElementById('createQuizModal').style.display = 'none';
            document.getElementById('quizTitle').value = '';
            document.getElementById('quizSubject').value = '';
            document.getElementById('quizDescription').value = '';
        }

        function showSectionManagement() {
            console.log('Showing Section Management Modal');
            document.getElementById('sectionManagementModal').style.display = 'flex';
        }

        function closeSectionManagement() {
            console.log('Closing Section Management Modal');
            document.getElementById('sectionManagementModal').style.display = 'none';
        }

        // Section checkbox functions
        function toggleAISections() {
            const selectAll = document.getElementById('aiSelectAllSections').checked;
            document.querySelectorAll('.ai-section-checkbox').forEach(checkbox => {
                checkbox.checked = selectAll;
            });
        }

        function toggleAllSections() {
            const selectAll = document.getElementById('selectAllSections').checked;
            document.querySelectorAll('.section-checkbox').forEach(checkbox => {
                checkbox.checked = selectAll;
            });
        }

        // Check AI generation quota
        async function checkAIQuota() {
            try {
                const response = await fetch(`api/quiz_api.php?action=check_ai_quota&user_id=${userId}`);
                const data = await response.json();
                console.log('AI Quota:', data);
            } catch (error) {
                console.error('Error checking quota:', error);
            }
        }

        // Load quizzes
        async function loadQuizzes() {
            console.log('Loading quizzes...');
            try {
                let url = `api/quiz_api.php?action=get_quizzes&role=${currentRole}&user_id=${userId}`;
                if (currentSectionFilter !== 'all') {
                    url += `&section_id=${currentSectionFilter}`;
                }
                
                console.log('Fetching from:', url);
                const response = await fetch(url);
                const data = await response.json();
                console.log('Quizzes loaded:', data);
                
                if (data.success) {
                    if (currentRole === 'teacher') {
                        displayTeacherQuizzes(data.quizzes);
                    } else {
                        displayStudentQuizzes(data.quizzes);
                    }
                } else {
                    showNotification('error', data.error || 'Error loading quizzes');
                }
            } catch (error) {
                console.error('Error loading quizzes:', error);
                showNotification('error', 'Network error loading quizzes');
            }
        }

        // Display teacher quizzes
        function displayTeacherQuizzes(quizzes) {
            const container = document.getElementById('quizzesList');
            console.log('Displaying teacher quizzes:', quizzes);
            
            if (!quizzes || quizzes.length === 0) {
                container.innerHTML = `
                    <div class="bg-white rounded-xl shadow-lg p-8 text-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-question-circle text-gray-400 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">No Quizzes Yet</h3>
                        <p class="text-gray-500 mb-4">Create your first quiz to get started</p>
                        <div class="flex gap-2 justify-center">
                            <button onclick="showAIQuizModal()" class="gradient-bg text-white px-6 py-2 rounded-lg hover:opacity-90">
                                Create with AI
                            </button>
                            <button onclick="showCreateQuizModal()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                                Manual Quiz
                            </button>
                        </div>
                    </div>
                `;
                return;
            }

            container.innerHTML = quizzes.map(quiz => `
                <div class="quiz-card bg-white rounded-xl shadow-lg p-6 border-l-4 ${quiz.is_ai_generated ? 'border-purple-500' : 'border-blue-500'}">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <h3 class="text-xl font-bold text-gray-800">${quiz.title}</h3>
                                ${quiz.is_ai_generated ? 
                                    '<span class="bg-purple-100 text-purple-800 text-xs font-bold px-2 py-1 rounded-full flex items-center gap-1"><i class="fas fa-robot"></i> AI</span>' : 
                                    '<span class="bg-blue-100 text-blue-800 text-xs font-bold px-2 py-1 rounded-full"><i class="fas fa-hand"></i> Manual</span>'
                                }
                            </div>
                            <p class="text-gray-600 mb-2">${quiz.subject}</p>
                            <div class="flex flex-wrap gap-3 text-sm text-gray-500">
                                <span class="flex items-center gap-1">
                                    <i class="fas fa-clock"></i> ${quiz.duration} min
                                </span>
                                <span class="flex items-center gap-1">
                                    <i class="fas fa-question-circle"></i> ${quiz.question_count || '?'} questions
                                </span>
                                <span class="flex items-center gap-1">
                                    <i class="fas fa-calendar"></i> ${formatDate(quiz.created_at)}
                                </span>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="viewQuizResults(${quiz.id})" class="text-blue-600 hover:text-blue-800 font-medium">
                                <i class="fas fa-chart-bar"></i> Results
                            </button>
                            <button onclick="editQuiz(${quiz.id})" class="text-green-600 hover:text-green-800 font-medium">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button onclick="deleteQuiz(${quiz.id})" class="text-red-600 hover:text-red-800 font-medium">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-between items-center pt-4 border-t">
                        <div class="text-sm text-gray-500">
                            Assigned to: <span class="font-medium">${quiz.section_names || 'No sections'}</span>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="previewQuiz(${quiz.id})" class="text-sm bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded">
                                Preview
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Display student quizzes
        function displayStudentQuizzes(quizzes) {
            const container = document.getElementById('studentQuizzesList');
            console.log('Displaying student quizzes:', quizzes);
            
            if (!quizzes || quizzes.length === 0) {
                container.innerHTML = `
                    <div class="bg-white rounded-xl shadow-lg p-8 text-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-check-circle text-gray-400 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">No Quizzes Assigned</h3>
                        <p class="text-gray-500">You don't have any quizzes at the moment.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = quizzes.map(quiz => {
                const isAttempted = quiz.attempt_status === 'completed';
                
                return `
                    <div class="quiz-card bg-white rounded-xl shadow-lg p-6 border-l-4 ${isAttempted ? 'border-green-500' : 'border-blue-500'}">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <h3 class="text-xl font-bold text-gray-800">${quiz.title}</h3>
                                    ${isAttempted ? 
                                        '<span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded-full"><i class="fas fa-check"></i> Completed</span>' : 
                                        '<span class="bg-blue-100 text-blue-800 text-xs font-bold px-2 py-1 rounded-full pulse"><i class="fas fa-clock"></i> Available</span>'
                                    }
                                </div>
                                <p class="text-gray-600 mb-2">${quiz.subject}</p>
                                <div class="flex flex-wrap gap-3 text-sm text-gray-500 mb-4">
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-clock"></i> ${quiz.duration} minutes
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-question-circle"></i> ${quiz.question_count} questions
                                    </span>
                                </div>
                                
                                ${isAttempted ? `
                                    <div class="bg-gray-50 p-3 rounded-lg">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span class="font-medium">Your Score: </span>
                                                <span class="text-lg font-bold ${quiz.percentage >= 60 ? 'text-green-600' : 'text-red-600'}">
                                                    ${quiz.percentage}%
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                ` : `
                                    <button onclick="startQuiz(${quiz.id})" class="w-full gradient-bg text-white py-3 rounded-lg hover:opacity-90 font-bold text-lg flex items-center justify-center gap-2">
                                        <i class="fas fa-play-circle"></i>
                                        Start Quiz Now
                                    </button>
                                `}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Generate AI Quiz
        async function generateAIQuiz() {
            console.log('Generating AI Quiz...');
            const subject = document.getElementById('aiSubject').value.trim();
            const prompt = document.getElementById('aiPrompt').value.trim();
            const numQuestions = parseInt(document.getElementById('aiNumQuestions').value);
            const difficulty = document.getElementById('aiDifficulty').value;
            const duration = parseInt(document.getElementById('aiDuration').value);
            
            const selectedSections = Array.from(document.querySelectorAll('.ai-section-checkbox:checked'))
                .map(cb => parseInt(cb.value));
            
            // Validation
            if (!subject) {
                showNotification('error', 'Please enter a subject/topic');
                document.getElementById('aiSubject').focus();
                return;
            }
            
            if (!prompt || prompt.length < 20) {
                showNotification('error', 'Please provide detailed instructions (at least 20 characters)');
                document.getElementById('aiPrompt').focus();
                return;
            }
            
            if (selectedSections.length === 0) {
                showNotification('error', 'Please select at least one section');
                return;
            }
            
            // Show loading state
            const statusDiv = document.getElementById('aiGenerationStatus');
            statusDiv.classList.remove('hidden');
            
            try {
                const response = await fetch('api/quiz_api.php?action=generate_ai_quiz', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        subject,
                        prompt,
                        num_questions: numQuestions,
                        difficulty,
                        duration,
                        sections: selectedSections,
                        teacher_id: userId
                    })
                });
                
                const data = await response.json();
                console.log('AI Quiz Response:', data);
                
                if (data.success) {
                    closeAIQuizModal();
                    loadQuizzes();
                    
                    // Show success notification
                    showNotification('success', 
                        `✅ AI Quiz Generated Successfully!<br>
                        <strong>${data.quiz.title}</strong> with ${data.quiz.question_count} questions`,
                        5000
                    );
                } else {
                    showNotification('error', data.error || 'Failed to generate quiz');
                }
            } catch (error) {
                console.error('Error generating AI quiz:', error);
                showNotification('error', 'Network error. Please check your connection.');
            } finally {
                statusDiv.classList.add('hidden');
            }
        }

        // Create Manual Quiz
        async function createManualQuiz() {
            console.log('Creating manual quiz...');
            const title = document.getElementById('quizTitle').value.trim();
            const subject = document.getElementById('quizSubject').value.trim();
            const description = document.getElementById('quizDescription').value.trim();
            const duration = parseInt(document.getElementById('quizDuration').value);
            
            const selectedSections = Array.from(document.querySelectorAll('.section-checkbox:checked'))
                .map(cb => parseInt(cb.value));
            
            // Validation
            if (!title) {
                showNotification('error', 'Please enter quiz title');
                document.getElementById('quizTitle').focus();
                return;
            }
            
            if (!subject) {
                showNotification('error', 'Please enter subject');
                document.getElementById('quizSubject').focus();
                return;
            }
            
            if (selectedSections.length === 0) {
                showNotification('error', 'Please select at least one section');
                return;
            }
            
            try {
                const response = await fetch('api/quiz_api.php?action=create_manual_quiz', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        quiz_title: title,
                        quiz_subject: subject,
                        quiz_description: description,
                        quiz_duration: duration,
                        sections: selectedSections,
                        teacher_id: userId
                    })
                });
                
                const data = await response.json();
                console.log('Manual Quiz Response:', data);
                
                if (data.success) {
                    closeCreateQuizModal();
                    loadQuizzes();
                    
                    // Show success notification with option to add questions
                    showNotification('success', 
                        `✅ Quiz Created Successfully!<br>
                        <strong>${data.quiz_title}</strong><br>
                        <button onclick="addQuestionsToQuiz(${data.quiz_id})" class="mt-2 text-sm bg-blue-600 text-white px-3 py-1 rounded">
                            Add Questions Now
                        </button>`,
                        6000
                    );
                } else {
                    showNotification('error', data.error || 'Failed to create quiz');
                }
            } catch (error) {
                console.error('Error creating manual quiz:', error);
                showNotification('error', 'Network error. Please check your connection.');
            }
        }

        // Quiz Functions
        async function startQuiz(quizId) {
            console.log('Starting quiz:', quizId);
            try {
                const response = await fetch(`api/quiz_api.php?action=get_quiz&quiz_id=${quizId}`);
                const data = await response.json();
                console.log('Quiz data:', data);
                
                if (data.quiz) {
                    currentQuiz = data.quiz;
                    timeLeft = currentQuiz.duration * 60;
                    
                    // Hide student view, show quiz view
                    document.getElementById('studentView').classList.add('hidden');
                    document.getElementById('quizTakingView').classList.remove('hidden');
                    
                    // Display quiz
                    displayQuizQuestions();
                    
                    // Start timer
                    startQuizTimer();
                } else {
                    showNotification('error', data.error || 'Error loading quiz');
                }
            } catch (error) {
                console.error('Error loading quiz:', error);
                showNotification('error', 'Error loading quiz');
            }
        }

        function displayQuizQuestions() {
            const content = document.getElementById('quizContent');
            studentAnswers = {};
            
            content.innerHTML = `
                <div class="mb-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800 mb-2">${currentQuiz.title}</h2>
                            <p class="text-gray-600">${currentQuiz.subject}</p>
                            <p class="text-gray-500 text-sm mt-1">${currentQuiz.description || ''}</p>
                        </div>
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="text-center">
                                <div class="text-sm text-gray-600">Time Remaining</div>
                                <div id="quizTimer" class="text-2xl font-bold text-blue-700">${formatTime(timeLeft)}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    ${currentQuiz.questions.map((q, idx) => `
                        <div class="bg-white border rounded-xl p-6">
                            <p class="font-semibold text-lg mb-4">${idx + 1}. ${q.question}</p>
                            <div class="space-y-3">
                                ${q.options.map((opt, optIdx) => `
                                    <label class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-blue-50 cursor-pointer">
                                        <input type="radio" 
                                               name="question_${idx}" 
                                               value="${optIdx}"
                                               onchange="studentAnswers[${idx}] = ${optIdx}"
                                               class="w-5 h-5 text-blue-600">
                                        <span>${opt}</span>
                                    </label>
                                `).join('')}
                            </div>
                        </div>
                    `).join('')}
                </div>

                <button onclick="submitQuiz()" class="mt-6 w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 font-bold text-lg">
                    Submit Quiz
                </button>
            `;
        }

        function startQuizTimer() {
            if (quizTimer) clearInterval(quizTimer);
            
            quizTimer = setInterval(() => {
                timeLeft--;
                if (document.getElementById('quizTimer')) {
                    document.getElementById('quizTimer').textContent = formatTime(timeLeft);
                }
                
                if (timeLeft <= 0) {
                    clearInterval(quizTimer);
                    showNotification('warning', 'Time is up! Submitting your quiz...');
                    submitQuiz();
                }
            }, 1000);
        }

        async function submitQuiz() {
            if (quizTimer) clearInterval(quizTimer);
            
            if (Object.keys(studentAnswers).length < currentQuiz.questions.length) {
                if (!confirm('You have not answered all questions. Submit anyway?')) return;
            }

            let score = 0;
            currentQuiz.questions.forEach((q, idx) => {
                // Convert correct_answer from char (a,b,c,d) to index (0,1,2,3)
                const correctIndex = q.correct_answer.charCodeAt(0) - 97; // 'a' -> 0, 'b' -> 1, etc.
                if (studentAnswers[idx] === correctIndex) {
                    score++;
                }
            });

            const percentage = Math.round((score / currentQuiz.questions.length) * 100);
            
            try {
                const response = await fetch('api/quiz_api.php?action=submit_result', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        quiz_id: currentQuiz.id,
                        score: score,
                        total: currentQuiz.questions.length,
                        percentage: percentage,
                        answers: studentAnswers
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    showNotification('success', 
                        `Quiz submitted successfully!<br>
                        Score: ${score}/${currentQuiz.questions.length} (${percentage}%)`
                    );
                    exitQuiz();
                    loadQuizzes();
                } else {
                    showNotification('error', 'Error submitting quiz');
                }
            } catch (error) {
                showNotification('error', 'Error submitting quiz');
            }
        }

        function exitQuiz() {
            document.getElementById('quizTakingView').classList.add('hidden');
            document.getElementById('studentView').classList.remove('hidden');
            currentQuiz = null;
            studentAnswers = {};
            if (quizTimer) clearInterval(quizTimer);
        }

        function exitResults() {
            document.getElementById('quizResultsView').classList.add('hidden');
            document.getElementById('studentView').classList.remove('hidden');
        }

        // Chat Functions
        function handleChatKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }

        function quickPrompt(prompt) {
            document.getElementById('chatInput').value = prompt;
            sendMessage();
        }

        async function sendMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            
            if (!message) return;

            // Add user message
            addChatMessage('user', message);
            input.value = '';
            
            // Show typing indicator
            document.getElementById('chatTyping').classList.remove('hidden');
            
            try {
                // Check if it's a quiz generation request
                const quizKeywords = ['quiz', 'exam', 'test', 'questions', 'generate'];
                const isQuizRequest = quizKeywords.some(keyword => 
                    message.toLowerCase().includes(keyword)
                );
                
                if (currentRole === 'teacher' && isQuizRequest) {
                    // Suggest AI quiz generation
                    setTimeout(() => {
                        addChatMessage('assistant', 
                            `I can help you create a quiz! Would you like me to:
                            
                            1. **Generate with AI**: Create a quiz automatically based on your topic
                            2. **Manual Creation**: Use the manual quiz creator
                            
                            Tell me what subject you want the quiz to be about, or click the "Generate AI Quiz" button above.`
                        );
                        document.getElementById('chatTyping').classList.add('hidden');
                    }, 1000);
                } else {
                    // Regular chat response
                    setTimeout(() => {
                        let response = '';
                        
                        if (currentRole === 'teacher') {
                            response = `I'm here to help you create and manage quizzes. 
                            
                            **For AI Quiz Generation:**
                            • Use the "✨ Generate AI Quiz" button above
                            • Or tell me: "Create a quiz about [topic]"
                            
                            **Manual Quiz Creation:**
                            • Use the "Manual Quiz" button for full control
                            
                            How can I assist you today?`;
                        } else {
                            response = `I can help you with your studies! 
                            
                            **For Quizzes:**
                            • Start available quizzes from the list
                            • Ask me about quiz topics to study
                            
                            What subject are you studying?`;
                        }
                        
                        addChatMessage('assistant', response);
                        document.getElementById('chatTyping').classList.add('hidden');
                    }, 1000);
                }
            } catch (error) {
                console.error('Chat error:', error);
                addChatMessage('assistant', 'Sorry, I encountered an error. Please try again.');
                document.getElementById('chatTyping').classList.add('hidden');
            }
        }

        function addChatMessage(sender, message) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `chat-message ${sender === 'user' ? 'bg-gray-100' : 'bg-blue-50'} rounded-xl p-4 border ${sender === 'user' ? 'border-gray-200' : 'border-blue-100'}`;
            
            messageDiv.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 ${sender === 'user' ? 'bg-gray-200' : 'bg-blue-100'} ${sender === 'user' ? 'text-gray-600' : 'text-blue-600'} rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas ${sender === 'user' ? 'fa-user' : 'fa-robot'}"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm ${sender === 'user' ? 'text-gray-700' : 'text-gray-700'}">
                            <strong class="${sender === 'user' ? 'text-gray-800' : 'text-blue-600'}">
                                ${sender === 'user' ? userName : 'AI Assistant'}:
                            </strong> 
                            ${message.replace(/\n/g, '<br>')}
                        </p>
                    </div>
                </div>
            `;
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Other functions
        async function deleteQuiz(quizId) {
            if (!confirm('Are you sure you want to delete this quiz? This action cannot be undone.')) return;
            
            try {
                const response = await fetch('api/quiz_api.php?action=delete_quiz', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ quiz_id: quizId })
                });
                
                const data = await response.json();
                if (data.success) {
                    showNotification('success', 'Quiz deleted successfully!');
                    loadQuizzes();
                } else {
                    showNotification('error', data.error || 'Error deleting quiz');
                }
            } catch (error) {
                showNotification('error', 'Error deleting quiz');
            }
        }

        async function createNewSection() {
            const name = document.getElementById('newSectionName').value.trim();
            const description = document.getElementById('newSectionDescription').value.trim();
            
            if (!name) {
                showNotification('error', 'Please enter section name');
                return;
            }
            
            try {
                const response = await fetch('api/quiz_api.php?action=create_section', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        section_name: name,
                        description: description,
                        teacher_id: userId
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    showNotification('success', 'Section created successfully!');
                    // Reload the page to show new section
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('error', data.error || 'Error creating section');
                }
            } catch (error) {
                showNotification('error', 'Error creating section');
            }
        }

        // Utility Functions
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        }

        function formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        function showNotification(type, message, duration = 3000) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 max-w-md transform translate-x-full transition-transform duration-300`;
            
            // Set colors based on type
            const colors = {
                success: 'bg-green-100 border-green-400 text-green-700',
                error: 'bg-red-100 border-red-400 text-red-700',
                warning: 'bg-yellow-100 border-yellow-400 text-yellow-700',
                info: 'bg-blue-100 border-blue-400 text-blue-700'
            };
            
            notification.className += ` ${colors[type] || colors.info}`;
            notification.innerHTML = `
                <div class="flex items-center gap-3">
                    <div class="text-xl">
                        ${type === 'success' ? '✅' : 
                          type === 'error' ? '❌' : 
                          type === 'warning' ? '⚠️' : 'ℹ️'}
                    </div>
                    <div class="flex-1">
                        <div class="font-semibold">${type.charAt(0).toUpperCase() + type.slice(1)}</div>
                        <div class="text-sm">${message}</div>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="text-lg hover:opacity-70">
                        &times;
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 10);
            
            // Auto-remove
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, duration);
        }

        // Filter by section
        function filterBySection(sectionId) {
            currentSectionFilter = sectionId;
            
            // Update button styles
            document.querySelectorAll('.filter-section-btn').forEach(btn => {
                if (btn.dataset.section === sectionId.toString()) {
                    btn.classList.add('bg-blue-600', 'text-white');
                    btn.classList.remove('bg-gray-100', 'hover:bg-gray-200');
                } else {
                    btn.classList.remove('bg-blue-600', 'text-white');
                    btn.classList.add('bg-gray-100', 'hover:bg-gray-200');
                }
            });
            
            loadQuizzes();
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal-overlay');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Stub functions for future implementation
        function editQuiz(quizId) {
            showNotification('info', 'Edit quiz feature coming soon!');
        }

        function previewQuiz(quizId) {
            showNotification('info', 'Preview feature coming soon!');
        }

        function viewQuizResults(quizId) {
            showNotification('info', 'View results feature coming soon!');
        }

        function editSection(sectionId) {
            showNotification('info', 'Edit section feature coming soon!');
        }

        function deleteSection(sectionId) {
            if (confirm('Delete this section? This will remove all students from the section.')) {
                showNotification('info', 'Delete section feature coming soon!');
            }
        }

        function addQuestionsToQuiz(quizId) {
            showNotification('info', 'Add questions feature coming soon! You can add questions from the quiz edit page.');
        }
    </script>
</body>
</html>

<?php
// Helper function to get quiz count
function getQuizCount($conn, $teacher_id) {
    // Check if quizzes table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'quizzes'");
    if (!$tableCheck || $tableCheck->num_rows == 0) {
        return 0;
    }
    
    $sql = "SELECT COUNT(*) as count FROM quizzes WHERE teacher_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] ?? 0;
}
?>
