<?php
// config.php - Main configuration
session_start();

// Database configuration
require_once 'database.php';

// OpenAI Configuration - GET YOUR NEW API KEY FROM https://platform.openai.com/api-keys
// ⚠️ REPLACE THIS KEY - the one below is exposed!
define('OPENAI_API_KEY', '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');

// Site Configuration
define('SITE_NAME', 'PLMUN LMS');
define('AI_QUIZ_LIMIT_DAILY', 10);
define('SITE_URL', 'http://localhost/PLMUN LMS');

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user']);
}

function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

// Database table initialization
function initializeDatabaseTables($conn) {
    echo "<!-- Initializing database tables... -->\n";
    
    // First, let's fix the database name issue - use backticks for table names
    $conn->query("CREATE DATABASE IF NOT EXISTS `plmun lms` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db("`plmun lms`");
    
    // Create users table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(100) UNIQUE NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `role` ENUM('student', 'teacher', 'admin') DEFAULT 'student',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Check if users table has data
    $result = $conn->query("SELECT COUNT(*) as count FROM `users`");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        // Insert default users
        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
        $conn->query("INSERT INTO `users` (`email`, `password`, `name`, `role`) VALUES 
            ('admin@plmun.edu.ph', '$hashed_password', 'Admin User', 'admin')");
        
        $hashed_password = password_hash('teacher123', PASSWORD_DEFAULT);
        $conn->query("INSERT INTO `users` (`email`, `password`, `name`, `role`) VALUES 
            ('teacher@plmun.edu.ph', '$hashed_password', 'Sample Teacher', 'teacher')");
        
        $hashed_password = password_hash('student123', PASSWORD_DEFAULT);
        $conn->query("INSERT INTO `users` (`email`, `password`, `name`, `role`) VALUES 
            ('student@plmun.edu.ph', '$hashed_password', 'Sample Student', 'student')");
    }
    
    // Create sections table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS `sections` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `section_name` VARCHAR(100) NOT NULL,
        `description` TEXT,
        `teacher_id` INT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Create section_students table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS `section_students` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `section_id` INT NOT NULL,
        `student_id` INT NOT NULL,
        `enrolled_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `unique_enrollment` (`section_id`, `student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Create quizzes table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS `quizzes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `subject` VARCHAR(100) NOT NULL,
        `description` TEXT,
        `duration` INT NOT NULL,
        `teacher_id` INT,
        `is_ai_generated` BOOLEAN DEFAULT FALSE,
        `ai_prompt` TEXT,
        `ai_model` VARCHAR(50),
        `passing_score` INT DEFAULT 60,
        `is_published` BOOLEAN DEFAULT TRUE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Create quiz_questions table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS `quiz_questions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `quiz_id` INT NOT NULL,
        `question` TEXT NOT NULL,
        `option_a` TEXT NOT NULL,
        `option_b` TEXT NOT NULL,
        `option_c` TEXT NOT NULL,
        `option_d` TEXT NOT NULL,
        `correct_answer` CHAR(1) NOT NULL,
        `explanation` TEXT,
        `points` INT DEFAULT 1,
        `question_order` INT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Create quiz_sections table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS `quiz_sections` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `quiz_id` INT NOT NULL,
        `section_id` INT NOT NULL,
        `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Create quiz_attempts table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS `quiz_attempts` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `quiz_id` INT NOT NULL,
        `student_id` INT NOT NULL,
        `score` INT,
        `total` INT,
        `percentage` DECIMAL(5,2),
        `answers` TEXT,
        `completed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Create ai_generation_log table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS `ai_generation_log` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `action` VARCHAR(50) NOT NULL,
        `prompt` TEXT,
        `response` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_user_date` (`user_id`, DATE(`created_at`))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    echo "<!-- Database tables initialized successfully -->\n";
}
?>
