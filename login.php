<?php
session_start();
include 'includes/db_connect.php';

// ✅ If already logged in, go to appropriate dashboard based on role
if (isset($_SESSION['user'])) {
  if (isset($_SESSION['role']) && $_SESSION['role'] === 'dean') {
    header("Location: dashboard_dean.php");
  } else {
    header("Location: dashboard.php");
  }
  exit();
}

if (isset($_POST['login'])) {
  $email = trim($_POST['email']);
  $password = $_POST['password'];

  // Optional: check institutional email format
  if (!preg_match("/^[a-zA-Z0-9._%+-]+@plmun\.edu\.ph$/", $email)) {
    $error = "Please use your institutional email (e.g., name@plmun.edu.ph)";
  } else {
    // ✅ FIXED: Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      $row = $result->fetch_assoc();

      // ========== CRITICAL FIX: Auto-assign missing roles ==========
      if (empty($row['role']) || $row['role'] === NULL) {
          $username = explode('@', $email)[0];
          
          // Determine role from email pattern
          if (stripos($username, '.dean') !== false) {
              $assigned_role = 'dean';
          } 
          elseif (stripos($username, '.prof') !== false || stripos($username, '.faculty') !== false || stripos($username, '.teacher') !== false) {
              $assigned_role = 'teacher';
          }
          elseif (stripos($username, '.chair') !== false || stripos($username, '.pc') !== false) {
              $assigned_role = 'program_chair';
          }
          elseif (stripos($username, '.admin') !== false) {
              $assigned_role = 'admin';
          }
          else {
              // Default to student for any course codes
              $assigned_role = 'student';
          }
          
          // Update database with correct role
          $update_stmt = $conn->prepare("UPDATE users SET role = ? WHERE email = ?");
          $update_stmt->bind_param("ss", $assigned_role, $email);
          $update_stmt->execute();
          
          // Update the row with new role
          $row['role'] = $assigned_role;
          
          error_log("Auto-assigned role '$assigned_role' to user: $email");
      }
      // ========== END CRITICAL FIX ==========

      // Verify password (matches hashed one from registration)
      if (password_verify($password, $row['password'])) {
        // ✅ Extract username from email for role validation
        $emailParts = explode('@', $email);
        $username = $emailParts[0];
        
        // ✅ Validate email format matches the role in database
        $role = trim($row['role']);
        $emailValid = true;
        
        if ($role === 'dean') {
          // Dean must have .dean in email
          if (stripos($username, '.dean') === false) {
            $emailValid = false;
            $error = "Dean accounts must use email format: name.dean@plmun.edu.ph";
          }
        } elseif ($role === 'program_chair') {
          // Program Chair must have .chair or .pc in email
          if (stripos($username, '.chair') === false && stripos($username, '.pc') === false) {
            $emailValid = false;
            $error = "Program Chair accounts must use email format: name.chair@plmun.edu.ph";
          }
        } elseif ($role === 'teacher') {
          // Teacher validation
          $validDesignations = ['.prof', '.faculty', '.teacher'];
          $hasValidDesignation = false;
          foreach ($validDesignations as $designation) {
            if (stripos($username, $designation) !== false) {
              $hasValidDesignation = true;
              break;
            }
          }
          if (!$hasValidDesignation) {
            $emailValid = false;
            $error = "Teacher accounts must use email format with .prof, .faculty, or .teacher";
          }
        } elseif ($role === 'student') {
          // Student validation
          $validCourses = ['bsit', 'bscs', 'bsa', 'bsba', 'bsed', 'beed', 'bsn', 'bscrim'];
          $hasValidCourse = false;
          foreach ($validCourses as $course) {
            if (stripos($username, '.' . $course) !== false) {
              $hasValidCourse = true;
              break;
            }
          }
          if (!$hasValidCourse) {
            $emailValid = false;
            $error = "Student accounts must include course code in email (e.g., name.bsit@plmun.edu.ph)";
          }
        }
        
        // ✅ Only allow login if email format is valid for the role
        if ($emailValid) {
          $_SESSION['user'] = $email;
          $_SESSION['user_id'] = $row['id'];
          $_SESSION['role'] = $role;
          $_SESSION['user_name'] = $row['name'] ?? 'User';
          
          // ✅ Always redirect to dashboard.php (the router)
          header("Location: dashboard.php");
          exit();
        }
      } else {
        $error = "Incorrect password.";
      }
    } else {
      $error = "Email not found.";
    }
    
    $stmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login | PLMUN LMS</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <style>
    body {
      background: url('background/campus.jpg') no-repeat center center fixed;
      background-size: cover;
    }
  </style>
</head>
<body class="flex items-center justify-center h-screen">
  <div class="bg-white p-8 rounded-lg shadow-lg w-96">
    <div class="text-center mb-6">
      <span class="text-5xl">🎓</span>
      <h1 class="text-2xl font-bold text-blue-700 mt-2">PLMUN LMS</h1>
      <p class="text-gray-600 text-sm mt-1">Learning Management System</p>
    </div>
    
    <?php if(isset($error)) echo "<p class='text-red-500 text-center mb-3 bg-red-50 p-3 rounded'>$error</p>"; ?>
    
    <form method="POST" action="">
      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-semibold mb-2">Institutional Email</label>
        <input 
          type="email" 
          name="email" 
          placeholder="yourname@plmun.edu.ph" 
          class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
          required
        >
        <p class="text-xs text-gray-500 mt-1">
          ℹ️ Use your role-specific email format
        </p>
      </div>
      
      <div class="mb-6">
        <label class="block text-gray-700 text-sm font-semibold mb-2">Password</label>
        <div class="relative">
          <input 
            type="password" 
            id="password"
            name="password" 
            placeholder="••••••••••" 
            class="w-full p-3 pr-12 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
            required
          >
          <button 
            type="button" 
            id="togglePassword"
            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none"
          >
            <!-- Eye icon (hidden state) -->
            <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <!-- Eye-off icon (visible state) - hidden by default -->
            <svg id="eyeOffIcon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
            </svg>
          </button>
        </div>
      </div>
      
      <button 
        type="submit" 
        name="login" 
        class="bg-blue-600 w-full py-3 text-white rounded-lg hover:bg-blue-700 font-semibold transition"
      >
        Login
      </button>
    </form>

    <p class="text-sm text-center mt-6 text-gray-600">
      Don't have an account?
      <a href="register.php" class="text-blue-600 hover:underline font-semibold">Sign up</a>
    </p>
    
    <div class="mt-4 p-3 bg-blue-50 rounded-lg">
      <p class="text-xs text-blue-800 font-semibold mb-1">📧 Email Format Guide:</p>
      <ul class="text-xs text-blue-700 space-y-1">
        <li>• <strong>Student:</strong> name.bsit@plmun.edu.ph</li>
        <li>• <strong>Teacher:</strong> name.prof@plmun.edu.ph</li>
        <li>• <strong>Dean:</strong> name.dean@plmun.edu.ph</li>
        <li>• <strong>Program Chair:</strong> name.chair@plmun.edu.ph</li>
      </ul>
    </div>
    
    <p class="text-xs text-center mt-4 text-gray-500">
      <a href="#" class="hover:underline">Forgot password?</a>
    </p>
  </div>

  <script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    const eyeOffIcon = document.getElementById('eyeOffIcon');

    togglePassword.addEventListener('click', function() {
      // Toggle password visibility
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
      
      // Toggle eye icons
      eyeIcon.classList.toggle('hidden');
      eyeOffIcon.classList.toggle('hidden');
    });
  </script>
</body>
</html>
