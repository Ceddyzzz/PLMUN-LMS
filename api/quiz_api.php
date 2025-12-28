<?php
// api/quiz_api.php
require_once '../config/config.php';

// Ensure we always return JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit();
}

$user = getCurrentUser();
$user_id = $user['id'] ?? 0;
$user_role = $user['role'] ?? 'student';

// Get action from request (Handling both GET and POST)
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// For POST requests with JSON data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST)) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if ($data) {
        $_POST = $data;
        if (isset($data['action'])) $action = $data['action'];
    }
}

// Initialize database tables if they don't exist
initializeDatabaseTables($conn);

// Main action router
switch ($action) {
    case 'get_quizzes':
        getQuizzes($conn, $user_id, $user_role);
        break;
    case 'generate_ai_quiz':
        generateAIQuiz($conn, $user_id, $user_role);
        break;
    case 'check_ai_quota':
        checkAIQuota($conn, $user_id);
        break;
    case 'get_quiz':
        getQuiz($conn, $user_id);
        break;
    case 'submit_result':
        submitQuizResult($conn, $user_id);
        break;
    case 'delete_quiz':
        deleteQuiz($conn, $user_id, $user_role);
        break;
    case 'create_manual_quiz':
        createManualQuiz($conn, $user_id, $user_role);
        break;
    case 'create_section':
        createSection($conn, $user_id, $user_role);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action: ' . $action]);
}

// --- HELPER FUNCTIONS ---

function checkAIQuota($conn, $user_id) {
    try {
        // Ensure table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'ai_generation_log'");
        if (!$tableCheck || $tableCheck->num_rows == 0) {
            // Table doesn't exist, return default quota
            echo json_encode([
                'success' => true,
                'quota' => [
                    'used' => 0,
                    'remaining' => 10,
                    'limit' => 10
                ]
            ]);
            return;
        }
        
        $today = date('Y-m-d');
        $sql = "SELECT COUNT(*) as used_today FROM ai_generation_log 
                WHERE user_id = ? AND DATE(created_at) = ? 
                AND action = 'generate_quiz'";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) { throw new Exception("Table might be missing"); }
        
        $stmt->bind_param("is", $user_id, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $used = $row['used_today'] ?? 0;
        $limit = 10;
        $remaining = max(0, $limit - $used);

        echo json_encode([
            'success' => true,
            'quota' => [
                'used' => $used,
                'remaining' => $remaining,
                'limit' => $limit
            ]
        ]);
    } catch (Exception $e) {
        // FALLBACK: If database fails, return a default quota so buttons still work
        echo json_encode([
            'success' => true,
            'quota' => ['used' => 0, 'remaining' => 10, 'limit' => 10],
            'note' => 'Using fallback quota'
        ]);
    }
}

function getQuizzes($conn, $user_id, $user_role) {
    try {
        $section_id = $_GET['section_id'] ?? 'all';
        $quizzes = [];

        if ($user_role === 'teacher') {
            $sql = "SELECT q.*, 
                    GROUP_CONCAT(DISTINCT s.section_name) as section_names,
                    COUNT(DISTINCT qq.id) as question_count 
                    FROM quizzes q 
                    LEFT JOIN quiz_sections qs ON q.id = qs.quiz_id
                    LEFT JOIN sections s ON qs.section_id = s.id
                    LEFT JOIN quiz_questions qq ON q.id = qq.quiz_id 
                    WHERE q.teacher_id = ? 
                    GROUP BY q.id 
                    ORDER BY q.created_at DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
        } else {
            // For students: get quizzes assigned to their sections
            $sql = "SELECT DISTINCT q.*, 
                    COUNT(DISTINCT qq.id) as question_count,
                    r.score, r.percentage, r.attempt_status
                    FROM quizzes q 
                    JOIN quiz_sections qs ON q.id = qs.quiz_id
                    JOIN quiz_questions qq ON q.id = qq.quiz_id
                    LEFT JOIN quiz_results r ON q.id = r.quiz_id AND r.student_id = ?
                    WHERE qs.section_id IN (
                        SELECT section_id FROM section_students WHERE student_id = ?
                    )
                    AND q.is_published = 1 
                    GROUP BY q.id
                    ORDER BY q.created_at DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $user_id, $user_id);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            // Format for easier use in JavaScript
            $row['duration'] = (int)($row['duration'] ?? 45);
            $row['question_count'] = (int)($row['question_count'] ?? 0);
            $quizzes[] = $row;
        }

        echo json_encode(['success' => true, 'quizzes' => $quizzes]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage(), 'quizzes' => []]);
    }
}

function getQuiz($conn, $user_id) {
    $quiz_id = $_GET['quiz_id'] ?? 0;
    try {
        $stmt = $conn->prepare("SELECT * FROM quizzes WHERE id = ?");
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $quiz = $stmt->get_result()->fetch_assoc();

        if (!$quiz) {
            echo json_encode(['success' => false, 'error' => 'Quiz not found']);
            return;
        }

        $stmt2 = $conn->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY question_order");
        $stmt2->bind_param("i", $quiz_id);
        $stmt2->execute();
        $res = $stmt2->get_result();
        
        $questions = [];
        while ($row = $res->fetch_assoc()) {
            $questions[] = [
                'id' => $row['id'],
                'question' => $row['question'],
                'options' => [
                    $row['option_a'] ?? 'Option A',
                    $row['option_b'] ?? 'Option B', 
                    $row['option_c'] ?? 'Option C',
                    $row['option_d'] ?? 'Option D'
                ],
                'correct_answer' => $row['correct_answer'] ?? 'a',
                'explanation' => $row['explanation'] ?? ''
            ];
        }
        $quiz['questions'] = $questions;
        echo json_encode(['success' => true, 'quiz' => $quiz]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function createManualQuiz($conn, $user_id, $user_role) {
    if ($user_role !== 'teacher') {
        echo json_encode(['success' => false, 'error' => 'Only teachers can create quizzes']);
        return;
    }
    
    $quiz_title = $_POST['quiz_title'] ?? '';
    $quiz_subject = $_POST['quiz_subject'] ?? '';
    $quiz_description = $_POST['quiz_description'] ?? '';
    $quiz_duration = (int)($_POST['quiz_duration'] ?? 45);
    $sections = $_POST['sections'] ?? [];
    
    if (empty($quiz_title) || empty($quiz_subject) || empty($sections)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        return;
    }
    
    try {
        $conn->begin_transaction();
        
        // Insert quiz
        $stmt = $conn->prepare("INSERT INTO quizzes (title, subject, description, duration, teacher_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssii", $quiz_title, $quiz_subject, $quiz_description, $quiz_duration, $user_id);
        $stmt->execute();
        
        $quiz_id = $stmt->insert_id;
        
        // Assign to sections
        foreach ($sections as $section_id) {
            $stmt2 = $conn->prepare("INSERT INTO quiz_sections (quiz_id, section_id) VALUES (?, ?)");
            $stmt2->bind_param("ii", $quiz_id, $section_id);
            $stmt2->execute();
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'quiz_id' => $quiz_id,
            'quiz_title' => $quiz_title
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function generateAIQuiz($conn, $user_id, $user_role) {
    if ($user_role !== 'teacher') {
        echo json_encode(['success' => false, 'error' => 'Only teachers can generate AI quizzes']);
        return;
    }
    
    // Check quota first
    $quota_result = checkAIQuotaInternal($conn, $user_id);
    if ($quota_result['remaining'] <= 0) {
        echo json_encode(['success' => false, 'error' => 'AI generation quota exceeded for today']);
        return;
    }
    
    $subject = $_POST['subject'] ?? '';
    $prompt = $_POST['prompt'] ?? '';
    $num_questions = (int)($_POST['num_questions'] ?? 10);
    $difficulty = $_POST['difficulty'] ?? 'medium';
    $duration = (int)($_POST['duration'] ?? 45);
    $sections = $_POST['sections'] ?? [];
    
    if (empty($subject) || empty($prompt) || empty($sections)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        return;
    }
    
    try {
        // In a real implementation, you would call OpenAI API here
        // For now, we'll create a sample quiz
        
        $conn->begin_transaction();
        
        // Create quiz
        $title = "AI Quiz: " . $subject . " (" . ucfirst($difficulty) . ")";
        $description = "AI-generated quiz based on: " . substr($prompt, 0, 200) . "...";
        
        $stmt = $conn->prepare("INSERT INTO quizzes (title, subject, description, duration, teacher_id, is_ai_generated, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
        $stmt->bind_param("sssii", $title, $subject, $description, $duration, $user_id);
        $stmt->execute();
        
        $quiz_id = $stmt->insert_id;
        
        // Add sample questions (in real implementation, these would come from OpenAI)
        $sample_questions = [
            [
                'question' => "What is the main purpose of " . $subject . "?",
                'options' => [
                    "To solve complex problems",
                    "To analyze data structures", 
                    "To implement algorithms",
                    "All of the above"
                ],
                'correct' => 'd',
                'explanation' => $subject . " encompasses multiple aspects including problem-solving, data analysis, and algorithm implementation."
            ],
            // Add more sample questions based on the subject
        ];
        
        foreach ($sample_questions as $index => $question) {
            $stmt2 = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question, option_a, option_b, option_c, option_d, correct_answer, explanation, question_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param("isssssssi", $quiz_id, $question['question'], 
                $question['options'][0], $question['options'][1], 
                $question['options'][2], $question['options'][3],
                $question['correct'], $question['explanation'], $index + 1);
            $stmt2->execute();
        }
        
        // Assign to sections
        foreach ($sections as $section_id) {
            $stmt3 = $conn->prepare("INSERT INTO quiz_sections (quiz_id, section_id) VALUES (?, ?)");
            $stmt3->bind_param("ii", $quiz_id, $section_id);
            $stmt3->execute();
        }
        
        // Log AI generation
        $stmt4 = $conn->prepare("INSERT INTO ai_generation_log (user_id, action, details, created_at) VALUES (?, 'generate_quiz', ?, NOW())");
        $details = json_encode(['subject' => $subject, 'num_questions' => $num_questions]);
        $stmt4->bind_param("is", $user_id, $details);
        $stmt4->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'quiz' => [
                'id' => $quiz_id,
                'title' => $title,
                'question_count' => count($sample_questions)
            ]
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function submitQuizResult($conn, $user_id) {
    $quiz_id = (int)($_POST['quiz_id'] ?? 0);
    $score = (int)($_POST['score'] ?? 0);
    $total = (int)($_POST['total'] ?? 1);
    $percentage = (float)($_POST['percentage'] ?? 0);
    $answers = $_POST['answers'] ?? [];
    
    if ($quiz_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid quiz']);
        return;
    }
    
    try {
        $conn->begin_transaction();
        
        // Check if already attempted
        $check_stmt = $conn->prepare("SELECT id FROM quiz_results WHERE quiz_id = ? AND student_id = ?");
        $check_stmt->bind_param("ii", $quiz_id, $user_id);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();
        
        if ($existing) {
            // Update existing result
            $stmt = $conn->prepare("UPDATE quiz_results SET score = ?, total_questions = ?, percentage = ?, submitted_at = NOW(), attempt_status = 'completed' WHERE id = ?");
            $stmt->bind_param("iidi", $score, $total, $percentage, $existing['id']);
        } else {
            // Insert new result
            $stmt = $conn->prepare("INSERT INTO quiz_results (quiz_id, student_id, score, total_questions, percentage, submitted_at, attempt_status) VALUES (?, ?, ?, ?, ?, NOW(), 'completed')");
            $stmt->bind_param("iiiid", $quiz_id, $user_id, $score, $total, $percentage);
        }
        
        $stmt->execute();
        
        // Save individual answers
        $result_id = $existing ? $existing['id'] : $stmt->insert_id;
        
        foreach ($answers as $question_idx => $answer_idx) {
            $question_number = $question_idx + 1;
            $is_correct = 0; // You would determine this based on the correct answer
            
            $answer_stmt = $conn->prepare("INSERT INTO student_answers (result_id, question_number, selected_option, is_correct) VALUES (?, ?, ?, ?)");
            $answer_stmt->bind_param("iiii", $result_id, $question_number, $answer_idx, $is_correct);
            $answer_stmt->execute();
        }
        
        $conn->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function deleteQuiz($conn, $user_id, $user_role) {
    if ($user_role !== 'teacher') {
        echo json_encode(['success' => false, 'error' => 'Only teachers can delete quizzes']);
        return;
    }
    
    $quiz_id = (int)($_POST['quiz_id'] ?? 0);
    
    try {
        // Verify ownership
        $check_stmt = $conn->prepare("SELECT id FROM quizzes WHERE id = ? AND teacher_id = ?");
        $check_stmt->bind_param("ii", $quiz_id, $user_id);
        $check_stmt->execute();
        $quiz = $check_stmt->get_result()->fetch_assoc();
        
        if (!$quiz) {
            echo json_encode(['success' => false, 'error' => 'Quiz not found or unauthorized']);
            return;
        }
        
        $conn->begin_transaction();
        
        // Delete related records
        $conn->query("DELETE FROM quiz_sections WHERE quiz_id = $quiz_id");
        $conn->query("DELETE FROM quiz_questions WHERE quiz_id = $quiz_id");
        $conn->query("DELETE FROM quiz_results WHERE quiz_id = $quiz_id");
        
        // Delete the quiz
        $delete_stmt = $conn->prepare("DELETE FROM quizzes WHERE id = ?");
        $delete_stmt->bind_param("i", $quiz_id);
        $delete_stmt->execute();
        
        $conn->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function createSection($conn, $user_id, $user_role) {
    if ($user_role !== 'teacher') {
        echo json_encode(['success' => false, 'error' => 'Only teachers can create sections']);
        return;
    }
    
    $section_name = $_POST['section_name'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($section_name)) {
        echo json_encode(['success' => false, 'error' => 'Section name is required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO sections (section_name, description, teacher_id, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("ssi", $section_name, $description, $user_id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'section_id' => $stmt->insert_id]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// Internal helper function for quota check
function checkAIQuotaInternal($conn, $user_id) {
    $today = date('Y-m-d');
    $sql = "SELECT COUNT(*) as used_today FROM ai_generation_log 
            WHERE user_id = ? AND DATE(created_at) = ? 
            AND action = 'generate_quiz'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $used = $row['used_today'] ?? 0;
    $limit = 10;
    $remaining = max(0, $limit - $used);
    
    return ['used' => $used, 'remaining' => $remaining, 'limit' => $limit];
}
?>
