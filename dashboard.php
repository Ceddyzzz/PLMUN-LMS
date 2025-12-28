<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// ========== EMERGENCY FIX: Auto-assign role if missing ==========
if (!isset($_SESSION['role']) || empty(trim($_SESSION['role'] ?? ''))) {
    $email = $_SESSION['user'];
    $username = explode('@', $email)[0];
    
    // Determine role from email pattern
    if (stripos($username, '.dean') !== false) {
        $_SESSION['role'] = 'dean';
    } 
    elseif (stripos($username, '.prof') !== false || stripos($username, '.faculty') !== false || stripos($username, '.teacher') !== false) {
        $_SESSION['role'] = 'teacher';
    }
    elseif (stripos($username, '.chair') !== false || stripos($username, '.pc') !== false) {
        $_SESSION['role'] = 'program_chair';
    }
    elseif (stripos($username, '.admin') !== false) {
        $_SESSION['role'] = 'admin';
    }
    else {
        $_SESSION['role'] = 'student'; // default for any course codes
    }
    
    error_log("Auto-assigned role '{$_SESSION['role']}' to user: $email");
}
// ========== END EMERGENCY FIX ==========

// Get and clean the role
$role = strtolower(trim($_SESSION['role'] ?? ''));

// If role is still empty, show error
if (empty($role)) {
    die("Error: Could not determine user role. Please contact administrator.");
}

// Route to appropriate dashboard based on role
switch ($role) {
    case 'student':
        if (file_exists('dashboard_student.php')) {
            include 'dashboard_student.php';
        } else {
            die("Student dashboard not found. Please create dashboard_student.php");
        }
        break;
    
    case 'teacher':
        if (file_exists('dashboard_teacher.php')) {
            include 'dashboard_teacher.php';
        } else {
            die("Teacher dashboard not found. Please create dashboard_teacher.php");
        }
        break;
    
    case 'dean':
        if (file_exists('dashboard_dean.php')) {
            include 'dashboard_dean.php';
        } else {
            die("Dean dashboard not found. Please create dashboard_dean.php");
        }
        break;
    
    case 'program_chair':
    case 'programchair': // Handle both formats
        if (file_exists('dashboard_programchair.php')) {
            include 'dashboard_programchair.php';
        } else {
            die("Program Chair dashboard not found. Please create dashboard_programchair.php");
        }
        break;
    
    case 'admin':
        if (file_exists('dashboard_admin.php')) {
            include 'dashboard_admin.php';
        } else {
            die("Admin dashboard not found. Please create dashboard_admin.php");
        }
        break;
    
    default:
        // Show clear error instead of falling back to generic dashboard
        echo "<!DOCTYPE html>
<html lang='en'>
<head>
  <meta charset='UTF-8'>
  <title>Dashboard Error | PLMUN LMS</title>
  <link href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' rel='stylesheet'>
</head>
<body class='bg-gray-100'>";
        include 'includes/header.php';
        echo "
  <main class='p-6 max-w-7xl mx-auto'>
    <h2 class='text-3xl font-bold mb-6'>Dashboard Error</h2>
    <div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4'>
      <p class='font-bold'>Invalid User Role</p>
      <p>Your account has an unrecognized role: <strong>" . htmlspecialchars($role) . "</strong></p>
      <p class='text-sm mt-2'>Email: " . htmlspecialchars($_SESSION['user'] ?? 'Unknown') . "</p>
      <p class='mt-4'>Please contact your administrator to fix your account permissions.</p>
    </div>
    
    <div class='bg-yellow-50 p-4 rounded-lg mt-6'>
      <p class='font-semibold'>Available Roles:</p>
      <ul class='list-disc ml-5 mt-2 text-sm'>
        <li>student</li>
        <li>teacher</li>
        <li>dean</li>
        <li>program_chair</li>
        <li>admin</li>
      </ul>
    </div>
  </main>
</body>
</html>";
        exit();
}
?>
