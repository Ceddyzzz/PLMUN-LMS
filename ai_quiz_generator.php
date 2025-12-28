<?php
// ai_quiz_generator.php - AI Quiz Generation Page
require_once 'config/config.php';

// Check if user is logged in and is a teacher
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user = getCurrentUser();
$user_id = $user['id'] ?? 0;
$user_role = $user['role'] ?? 'student';

if ($user_role !== 'teacher') {
    header("Location: quiz.php");
    exit();
}

// Fetch teacher's sections
$teacherSections = [];
$tableCheck = $conn->query("SHOW TABLES LIKE 'sections'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $columnCheck = $conn->query("SHOW COLUMNS FROM sections LIKE 'teacher_id'");
    
    if ($columnCheck && $columnCheck->num_rows > 0) {
        $sql = "SELECT s.id, s.section_name, 
                       COUNT(DISTINCT ss.student_id) as student_count
                FROM sections s
                LEFT JOIN section_students ss ON s.id = ss.section_id
                WHERE s.teacher_id = ?
                GROUP BY s.id
                ORDER BY s.section_name";
    } else {
        $sql = "SELECT s.id, s.section_name, 
                       COUNT(DISTINCT ss.student_id) as student_count
                FROM sections s
                LEFT JOIN section_students ss ON s.id = ss.section_id
                GROUP BY s.id
                ORDER BY s.section_name";
    }
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (strpos($sql, '?') !== false) {
            $stmt->bind_param("i", $user_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $teacherSections[] = $row;
        }
        $stmt->close();
    }
}

// Handle AI quiz generation form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_ai_quiz'])) {
    $subject = $_POST['subject'] ?? '';
    $prompt = $_POST['prompt'] ?? '';
    $num_questions = intval($_POST['num_questions'] ?? 10);
    $difficulty = $_POST['difficulty'] ?? 'medium';
    $duration = intval($_POST['duration'] ?? 45);
    $sections = $_POST['sections'] ?? [];
    
    // Validate inputs
    if (empty($subject) || empty($prompt) || empty($sections)) {
        $error_message = "Please fill all required fields and select at least one section.";
    } else {
        // Call the API to generate AI quiz
        $api_url = 'api/quiz_api.php?action=generate_ai_quiz';
        
        $post_data = [
            'subject' => $subject,
            'prompt' => $prompt,
            'num_questions' => $num_questions,
            'difficulty' => $difficulty,
            'duration' => $duration,
            'sections' => $sections,
            'teacher_id' => $user_id
        ];
        
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $result = json_decode($response, true);
            if ($result['success']) {
                // Redirect to edit quiz page
                header("Location: edit_quiz.php?quiz_id=" . $result['quiz']['id'] . "&ai_generated=1");
                exit();
            } else {
                $error_message = $result['error'] ?? 'Failed to generate quiz.';
            }
        } else {
            $error_message = 'API request failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Quiz Generator - PLMUN LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
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
                        <p class="font-semibold"><?php echo htmlspecialchars($user['name'] ?? 'Teacher'); ?></p>
                        <p class="text-sm opacity-90">Teacher</p>
                    </div>
                    <a href="quiz.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-arrow-left"></i> Back to Quizzes
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto p-4 md:p-6">
        <!-- Success/Error Messages -->
        <?php if (isset($error_message)): ?>
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
            <span class="block sm:inline"><?php echo $error_message; ?></span>
            <button onclick="this.parentElement.remove()" class="absolute top-0 right-0 px-4 py-3">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="gradient-bg text-white p-6">
                <div class="flex items-center gap-4">
                    <div class="bg-white text-purple-600 w-16 h-16 rounded-full flex items-center justify-center">
                        <i class="fas fa-robot text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold">✨ AI Quiz Generator</h1>
                        <p class="text-lg opacity-90">Create intelligent quizzes instantly with AI assistance</p>
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-sm">
                    <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                    <span>AI Assistant Ready • Powered by OpenAI GPT</span>
                </div>
            </div>
            
            <!-- AI Generation Form -->
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="generate_ai_quiz" value="1">
                
                <div class="space-y-6">
                    <!-- Subject -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-book text-blue-500"></i> Subject/Topic *
                        </label>
                        <input type="text" name="subject" 
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., Web Development, Database Systems, Calculus, Physics"
                               required
                               value="<?php echo $_POST['subject'] ?? ''; ?>">
                        <p class="text-xs text-gray-500 mt-1">What subject will this quiz cover?</p>
                    </div>
                    
                    <!-- AI Prompt -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-lightbulb text-yellow-500"></i> Quiz Instructions *
                        </label>
                        <textarea name="prompt" rows="6"
                                  class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Describe exactly what you want in the quiz. Be specific!

Example: 'Create a comprehensive quiz about HTML and CSS covering:
- Selectors and specificity
- Box model and positioning
- Responsive design techniques
- CSS Grid and Flexbox
Include practical coding questions and real-world scenarios.'"
                                  required><?php echo $_POST['prompt'] ?? ''; ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Detailed prompts create better quizzes! Mention topics, difficulty, question types.</p>
                    </div>
                    
                    <!-- Configuration -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-question-circle text-green-500"></i> Questions
                            </label>
                            <select name="num_questions" class="w-full border border-gray-300 rounded-lg px-3 py-3">
                                <option value="5" <?php echo ($_POST['num_questions'] ?? 10) == 5 ? 'selected' : ''; ?>>5 Questions (Quick Quiz)</option>
                                <option value="10" <?php echo ($_POST['num_questions'] ?? 10) == 10 ? 'selected' : ''; ?>>10 Questions (Standard)</option>
                                <option value="15" <?php echo ($_POST['num_questions'] ?? 10) == 15 ? 'selected' : ''; ?>>15 Questions (Comprehensive)</option>
                                <option value="20" <?php echo ($_POST['num_questions'] ?? 10) == 20 ? 'selected' : ''; ?>>20 Questions (Exam)</option>
                                <option value="25" <?php echo ($_POST['num_questions'] ?? 10) == 25 ? 'selected' : ''; ?>>25 Questions (Final Exam)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-chart-line text-red-500"></i> Difficulty
                            </label>
                            <select name="difficulty" class="w-full border border-gray-300 rounded-lg px-3 py-3">
                                <option value="easy" <?php echo ($_POST['difficulty'] ?? 'medium') == 'easy' ? 'selected' : ''; ?>>Easy (Beginner)</option>
                                <option value="medium" <?php echo ($_POST['difficulty'] ?? 'medium') == 'medium' ? 'selected' : ''; ?>>Medium (Intermediate)</option>
                                <option value="hard" <?php echo ($_POST['difficulty'] ?? 'medium') == 'hard' ? 'selected' : ''; ?>>Hard (Advanced)</option>
                                <option value="mixed" <?php echo ($_POST['difficulty'] ?? 'medium') == 'mixed' ? 'selected' : ''; ?>>Mixed Levels</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-clock text-purple-500"></i> Duration
                            </label>
                            <select name="duration" class="w-full border border-gray-300 rounded-lg px-3 py-3">
                                <option value="15" <?php echo ($_POST['duration'] ?? 45) == 15 ? 'selected' : ''; ?>>15 minutes</option>
                                <option value="30" <?php echo ($_POST['duration'] ?? 45) == 30 ? 'selected' : ''; ?>>30 minutes</option>
                                <option value="45" <?php echo ($_POST['duration'] ?? 45) == 45 ? 'selected' : ''; ?>>45 minutes</option>
                                <option value="60" <?php echo ($_POST['duration'] ?? 45) == 60 ? 'selected' : ''; ?>>60 minutes</option>
                                <option value="90" <?php echo ($_POST['duration'] ?? 45) == 90 ? 'selected' : ''; ?>>90 minutes</option>
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
                                    <input type="checkbox" id="selectAllSections" 
                                           class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                                    <span class="font-medium text-gray-700">Select All Sections</span>
                                </label>
                            </div>
                            <div class="space-y-2 max-h-40 overflow-y-auto">
                                <?php if (!empty($teacherSections)): ?>
                                    <?php foreach ($teacherSections as $section): ?>
                                    <label class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded cursor-pointer">
                                        <input type="checkbox" name="sections[]" value="<?php echo $section['id']; ?>" 
                                               class="section-checkbox w-4 h-4 text-blue-600 rounded"
                                               <?php echo (isset($_POST['sections']) && in_array($section['id'], $_POST['sections'])) ? 'checked' : ''; ?>>
                                        <div class="flex-1">
                                            <span class="font-medium"><?php echo htmlspecialchars($section['section_name']); ?></span>
                                            <span class="text-xs text-gray-500 ml-2">
                                                <?php echo $section['student_count']; ?> students
                                            </span>
                                        </div>
                                    </label>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-gray-500 text-center py-4">No sections available. Please create sections first.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- AI Tips -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <h4 class="font-bold text-blue-800 mb-2 flex items-center gap-2">
                            <i class="fas fa-lightbulb"></i> AI Generation Tips
                        </h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li>• Be specific about topics and concepts to cover</li>
                            <li>• Mention the desired difficulty level</li>
                            <li>• Include real-world scenarios for better questions</li>
                            <li>• Specify if you want coding questions or theory-based</li>
                            <li>• AI generates questions with explanations automatically</li>
                        </ul>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="border-t pt-6">
                        <div class="flex space-x-3">
                            <button type="submit" 
                                    class="flex-1 bg-gradient-to-r from-purple-600 to-blue-600 text-white py-4 rounded-lg hover:opacity-90 font-semibold transition flex items-center justify-center gap-3 pulse">
                                <i class="fas fa-robot text-xl"></i>
                                <span class="text-lg">✨ Generate Quiz with AI</span>
                            </button>
                            <a href="quiz.php" 
                               class="flex-1 bg-gray-200 text-gray-700 py-4 rounded-lg hover:bg-gray-300 font-semibold transition flex items-center justify-center">
                                <i class="fas fa-times"></i>
                                <span class="ml-2">Cancel</span>
                            </a>
                        </div>
                        <p class="text-xs text-gray-500 text-center mt-3">
                            <i class="fas fa-info-circle"></i> AI-generated quizzes include explanations and are ready to use immediately. Generation takes 10-30 seconds.
                        </p>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Sample Prompts -->
        <div class="mt-6 bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">💡 Sample Prompts for Inspiration</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-700 mb-2">Web Development Quiz</h4>
                    <p class="text-sm text-gray-600">"Create a quiz about frontend web development covering HTML5, CSS3, and JavaScript basics. Include questions about semantic HTML, CSS flexbox, DOM manipulation, and event handling. Mix theory with practical coding scenarios."</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-700 mb-2">Database Systems</h4>
                    <p class="text-sm text-gray-600">"Generate a quiz on relational database concepts including SQL queries, normalization, indexing, and transactions. Include practical SQL problems and theoretical questions about ACID properties and database design principles."</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-700 mb-2">Programming Fundamentals</h4>
                    <p class="text-sm text-gray-600">"Make a quiz about programming basics covering variables, data types, control structures, functions, and object-oriented programming concepts. Include code snippets and ask students to predict output or fix errors."</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-700 mb-2">Network Security</h4>
                    <p class="text-sm text-gray-600">"Create a comprehensive quiz on network security topics including encryption algorithms, firewall types, VPN protocols, and common attacks like XSS and SQL injection. Include real-world security scenarios."</p>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Select All Sections checkbox
            const selectAll = document.getElementById('selectAllSections');
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    document.querySelectorAll('.section-checkbox').forEach(cb => {
                        cb.checked = this.checked;
                    });
                });
            }
            
            // Form validation
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const sections = document.querySelectorAll('.section-checkbox:checked');
                    if (sections.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one section.');
                        return false;
                    }
                    
                    // Show loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating with AI...';
                        submitBtn.disabled = true;
                    }
                });
            }
        });
    </script>
</body>
</html>
