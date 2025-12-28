<?php
session_start();
include 'includes/db_connect.php';

header('Content-Type: application/json');

// Check if student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = 'student';
$user_name = $_SESSION['user'];
$action = $_POST['action'] ?? 'chat';

// Get student info for personalized responses
$student_query = "SELECT course, year_level, section FROM users WHERE id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Initialize conversation
$session_id = $_POST['session_id'] ?? 'student_' . $user_id . '_' . time();

// Get or create conversation
$conversation_stmt = $conn->prepare("
    SELECT id FROM chatbot_conversations 
    WHERE user_id = ? AND session_id = ?
");
$conversation_stmt->bind_param("is", $user_id, $session_id);
$conversation_stmt->execute();
$conversation_result = $conversation_stmt->get_result();

if ($conversation_result->num_rows === 0) {
    $create_stmt = $conn->prepare("
        INSERT INTO chatbot_conversations (user_id, user_role, session_id) 
        VALUES (?, ?, ?)
    ");
    $create_stmt->bind_param("iss", $user_id, $user_role, $session_id);
    $create_stmt->execute();
    $conversation_id = $create_stmt->insert_id;
} else {
    $row = $conversation_result->fetch_assoc();
    $conversation_id = $row['id'];
}

// Handle different actions
switch ($action) {
    case 'chat':
        $message = trim($_POST['message']);
        
        if (empty($message)) {
            echo json_encode(['error' => 'Message is required']);
            exit();
        }
        
        // Save user message
        $save_msg = $conn->prepare("
            INSERT INTO chatbot_messages (conversation_id, message, is_bot) 
            VALUES (?, ?, FALSE)
        ");
        $save_msg->bind_param("is", $conversation_id, $message);
        $save_msg->execute();
        
        // Get bot response
        $response = getStudentBotResponse($conn, $message, $student_info, $user_name);
        
        // Save bot response
        $save_bot = $conn->prepare("
            INSERT INTO chatbot_messages (conversation_id, message, is_bot) 
            VALUES (?, ?, TRUE)
        ");
        $save_bot->bind_param("is", $conversation_id, $response);
        $save_bot->execute();
        
        echo json_encode([
            'response' => $response,
            'session_id' => $session_id
        ]);
        break;
        
    case 'get_history':
        $history_stmt = $conn->prepare("
            SELECT m.message, m.is_bot, m.created_at 
            FROM chatbot_messages m
            JOIN chatbot_conversations c ON m.conversation_id = c.id
            WHERE c.user_id = ? AND c.session_id = ?
            ORDER BY m.created_at ASC
        ");
        $history_stmt->bind_param("is", $user_id, $session_id);
        $history_stmt->execute();
        $history = $history_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode(['history' => $history]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}

// Function to get bot response for students
function getStudentBotResponse($conn, $message, $student_info, $user_name) {
    $message_lower = strtolower($message);
    
    // Check for greetings
    if (preg_match('/(hello|hi|hey|greetings)/', $message_lower)) {
        $time = date('H');
        $greeting = ($time < 12) ? 'Good morning' : (($time < 18) ? 'Good afternoon' : 'Good evening');
        
        return "$greeting, $user_name! I'm your PLMUN LMS assistant. How can I help you today?";
    }
    
    // Check for specific student queries
    if (strpos($message_lower, 'assignment') !== false) {
        return "For assignments, go to your dashboard → Upcoming Assignments. You can submit files, check deadlines, and view grades there.";
    }
    
    if (strpos($message_lower, 'grade') !== false) {
        return "Your grades are visible in the Grades section. Contact your teacher if you need clarification on any marks.";
    }
    
    if (strpos($message_lower, 'section') !== false) {
        $course = $student_info['course'] ?? 'your course';
        $year = $student_info['year_level'] ?? 'your year';
        $section = $student_info['section'] ?? 'your section';
        return "You're enrolled in $course, Year $year, Section $section. You can view all your sections in the dashboard.";
    }
    
    if (strpos($message_lower, 'schedule') !== false) {
        return "Your class schedule is available in the Calendar section. You can also view it in your dashboard.";
    }
    
    if (strpos($message_lower, 'e-book') !== false || strpos($message_lower, 'book') !== false) {
        return "E-books are available in the E-Books section. You can browse by subject or search for specific titles.";
    }
    
    if (strpos($message_lower, 'quiz') !== false || strpos($message_lower, 'exam') !== false) {
        return "Quizzes and exams are taken in the Quiz section. Make sure you have a stable internet connection before starting.";
    }
    
    if (strpos($message_lower, 'help') !== false) {
        return getStudentHelpMessage($student_info);
    }
    
    // Check knowledge base
    $knowledge_stmt = $conn->prepare("
        SELECT answer FROM chatbot_knowledge 
        WHERE question LIKE ? 
        AND (user_roles IS NULL OR JSON_CONTAINS(user_roles, ?))
        ORDER BY priority DESC
        LIMIT 1
    ");
    $search_term = "%" . $message . "%";
    $json_role = json_encode(['student']);
    $knowledge_stmt->bind_param("ss", $search_term, $json_role);
    $knowledge_stmt->execute();
    $knowledge_result = $knowledge_stmt->get_result();
    
    if ($knowledge_result->num_rows > 0) {
        return $knowledge_result->fetch_assoc()['answer'];
    }
    
    // Default response
    return "I understand you're asking about: \"$message\". As a student, I can help with assignments, grades, schedules, e-books, and general LMS navigation. Try asking more specifically!";
}

// Function to get student help message
function getStudentHelpMessage($student_info) {
    $course = $student_info['course'] ?? 'your course';
    
    return "👋 **STUDENT HELP GUIDE**\n\nAs a **$course** student, I can help you with:\n\n📚 **Academic:**\n• Assignment submissions & deadlines\n• Grade inquiries\n• Course materials & e-books\n• Quiz/exam information\n• Class schedule\n\n🎯 **Navigation:**\n• How to submit assignments\n• Where to find grades\n• How to access e-books\n• Calendar & schedule viewing\n• Announcement checking\n\n💡 **Quick Tips:**\n• Use 'My Assignments' for pending work\n• Check announcements regularly\n• Submit assignments before deadlines\n• Contact teachers for course-specific questions\n\nTry asking:\n- 'How do I submit an assignment?'\n- 'Where can I see my grades?'\n- 'What's my class schedule?'\n- 'How to access e-books?'";
}
?>
