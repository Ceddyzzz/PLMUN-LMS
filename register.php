<?php
session_start();
include 'includes/db_connect.php';

// ✅ If already logged in, go to dashboard
if (isset($_SESSION['user'])) {
  header("Location: /PLMUN%20LMS/dashboard.php");
  exit();
}

// ✅ Fetch available sections for dropdown
$sections_query = "SELECT * FROM sections ORDER BY course, year_level, section";
$sections_result = $conn->query($sections_query);
$sections = [];
while ($row = $sections_result->fetch_assoc()) {
  $sections[] = $row;
}

if (isset($_POST['register'])) {
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];
  $role = $_POST['role'] ?? 'student';
  
  // ✅ Section-related fields
  $section_id = null;
  $course = null;
  $year_level = null;
  $section = null;
  
  if ($role === 'student') {
    $section_id = $_POST['section_id'] ?? null;
    if (!$section_id) {
      $error = "Please select your section/block.";
    } else {
      // Get section details
      $stmt = $conn->prepare("SELECT * FROM sections WHERE id = ?");
      $stmt->bind_param("i", $section_id);
      $stmt->execute();
      $section_result = $stmt->get_result();
      if ($section_result->num_rows > 0) {
        $section_data = $section_result->fetch_assoc();
        $course = $section_data['course'];
        $year_level = $section_data['year_level'];
        $section = $section_data['section'];
      }
      $stmt->close();
    }
  }

  // ✅ Prevent registration of dean and program_chair roles
  if (in_array($role, ['dean', 'program_chair'])) {
    $error = "Dean and Program Chair accounts cannot be created through registration. Please contact the administrator.";
  } 
  // Validate institutional email format
  elseif (!preg_match("/^[a-zA-Z0-9._%+-]+@plmun\.edu\.ph$/", $email)) {
    $error = "Please use your institutional email (e.g., name@plmun.edu.ph)";
  } else {
    // Extract the username part before @plmun.edu.ph
    $emailParts = explode('@', $email);
    $username = $emailParts[0];
    
    // Validate email format based on role
    if ($role === 'student') {
      // Student must have course code (e.g., cedric.bsit, juan.bscs)
      $validCourses = ['bsit', 'bscs', 'bsa', 'bsba', 'bsed', 'beed', 'bsn', 'bscrim'];
      $hasValidCourse = false;
      
      foreach ($validCourses as $courseCode) {
        if (stripos($username, '.' . $courseCode) !== false) {
          $hasValidCourse = true;
          break;
        }
      }
      
      if (!$hasValidCourse) {
        $error = "Student email must include your course (e.g., cedric.bsit@plmun.edu.ph, juan.bscs@plmun.edu.ph)";
      }
    } elseif ($role === 'teacher') {
      // Teacher must have prof, faculty, or teacher designation
      $validDesignations = ['.prof', '.faculty', '.teacher'];
      $hasValidDesignation = false;
      
      foreach ($validDesignations as $designation) {
        if (stripos($username, $designation) !== false) {
          $hasValidDesignation = true;
          break;
        }
      }
      
      if (!$hasValidDesignation) {
        $error = "Teacher email must include .prof, .faculty, or .teacher (e.g., cedric.prof@plmun.edu.ph)";
      }
    }
  }
  
  // Continue with registration if no errors
  if (!isset($error)) {
    if ($password !== $confirm_password) {
      $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
      $error = "Password must be at least 8 characters long.";
    } else {
      // Check if email already exists
      $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result->num_rows > 0) {
        $error = "Email already registered.";
      } else {
        // Hash password and insert new user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // ✅ Insert with section information for students
        if ($role === 'student') {
          $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, course, year_level, section) VALUES (?, ?, ?, ?, ?, ?, ?)");
          $stmt->bind_param("sssssis", $name, $email, $hashed_password, $role, $course, $year_level, $section);
        } else {
          $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
          $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
        }
        
        if ($stmt->execute()) {
          $success = "Registration successful! You can now log in.";
        } else {
          $error = "Registration failed. Please try again.";
        }
      }
      
      $stmt->close();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register | PLMUN LMS</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <style>
    body {
      background: url('background/campus.jpg') no-repeat center center fixed;
      background-size: cover;
      overflow-y: auto;
    }
  </style>
</head>
<body class="flex items-center justify-center min-h-screen py-8">
  <div class="bg-white p-6 rounded-lg shadow-lg w-96 my-8 max-h-screen overflow-y-auto">
    <div class="text-center mb-4">
      <span class="text-4xl">🎓</span>
      <h1 class="text-xl font-bold text-blue-700 mt-1">PLMUN LMS</h1>
      <p class="text-gray-600 text-xs">Create your account</p>
    </div>
    
    <?php if(isset($error)) echo "<p class='text-red-500 text-center mb-2 bg-red-50 p-2 rounded text-xs'>" . htmlspecialchars($error) . "</p>"; ?>
    <?php if(isset($success)) echo "<p class='text-green-500 text-center mb-2 bg-green-50 p-2 rounded text-xs'>" . htmlspecialchars($success) . "</p>"; ?>
    
    <form method="POST" action="" id="registerForm">
      <div class="mb-3">
        <label class="block text-gray-700 text-xs font-semibold mb-1">Full Name</label>
        <input 
          type="text" 
          name="name" 
          placeholder="Juan Dela Cruz" 
          class="w-full p-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
          required
        >
      </div>

      <div class="mb-3">
        <label class="block text-gray-700 text-xs font-semibold mb-1">Role</label>
        <select 
          name="role" 
          id="roleSelect"
          class="w-full p-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          required
          onchange="updateEmailPlaceholder()"
        >
          <option value="student">Student</option>
          <option value="teacher">Teacher</option>
        </select>
        <p class="text-xs text-gray-500 mt-1">
          ℹ️ Dean and Program Chair accounts are created by administrators only
        </p>
      </div>

      <!-- ✅ Section Selection (Only for Students) -->
      <div class="mb-3" id="sectionContainer">
        <label class="block text-gray-700 text-xs font-semibold mb-1">Section/Block *</label>
        <select 
          name="section_id" 
          id="sectionSelect"
          class="w-full p-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">-- Select Your Section --</option>
          <?php 
          $current_course = '';
          foreach ($sections as $sec) {
            if ($current_course !== $sec['course']) {
              if ($current_course !== '') echo '</optgroup>';
              echo '<optgroup label="' . htmlspecialchars($sec['course']) . '">';
              $current_course = $sec['course'];
            }
            echo '<option value="' . $sec['id'] . '">' . htmlspecialchars($sec['section_code']) . '</option>';
          }
          if ($current_course !== '') echo '</optgroup>';
          ?>
        </select>
        <p class="text-xs text-gray-500 mt-1">
          Select your course, year, and section (e.g., BSIT-1A)
        </p>
      </div>

      <div class="mb-3">
        <label class="block text-gray-700 text-xs font-semibold mb-1">Institutional Email</label>
        <input 
          type="email" 
          name="email" 
          id="emailInput"
          placeholder="cedric.bsit@plmun.edu.ph" 
          class="w-full p-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
          required
        >
        <p class="text-xs text-gray-500 mt-1" id="emailHint">
          Format: yourname.bsit@plmun.edu.ph
        </p>
      </div>
      
      <div class="mb-3">
        <label class="block text-gray-700 text-xs font-semibold mb-1">Password</label>
        <div class="relative">
          <input 
            type="password" 
            id="password"
            name="password" 
            placeholder="••••••••••" 
            class="w-full p-2 pr-10 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
            required
          >
          <button 
            type="button" 
            id="togglePassword"
            class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none"
          >
            <svg id="eyeIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <svg id="eyeOffIcon" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
            </svg>
          </button>
        </div>
      </div>
      
      <div class="mb-4">
        <label class="block text-gray-700 text-xs font-semibold mb-1">Confirm Password</label>
        <div class="relative">
          <input 
            type="password" 
            id="confirmPassword"
            name="confirm_password" 
            placeholder="••••••••••" 
            class="w-full p-2 pr-10 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
            required
          >
          <button 
            type="button" 
            id="toggleConfirmPassword"
            class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none"
          >
            <svg id="eyeIcon2" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <svg id="eyeOffIcon2" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
            </svg>
          </button>
        </div>
      </div>
      
      <button 
        type="submit" 
        name="register" 
        class="bg-blue-600 w-full py-2.5 text-white text-sm rounded-lg hover:bg-blue-700 font-semibold transition"
      >
        Create Account
      </button>
    </form>

    <p class="text-xs text-center mt-4 text-gray-600">
      Already have an account?
      <a href="login.php" class="text-blue-600 hover:underline font-semibold">Login</a>
    </p>
  </div>

  <script>
    // Update email placeholder and hint based on role
    function updateEmailPlaceholder() {
      const role = document.getElementById('roleSelect').value;
      const emailInput = document.getElementById('emailInput');
      const emailHint = document.getElementById('emailHint');
      const sectionContainer = document.getElementById('sectionContainer');
      const sectionSelect = document.getElementById('sectionSelect');
      
      if (role === 'student') {
        emailInput.placeholder = 'cedric.bsit@plmun.edu.ph';
        emailHint.textContent = 'Format: yourname.course@plmun.edu.ph (e.g., .bsit, .bscs, .bsa)';
        sectionContainer.style.display = 'block';
        sectionSelect.required = true;
      } else {
        emailInput.placeholder = 'cedric.prof@plmun.edu.ph';
        emailHint.textContent = 'Format: yourname.prof@plmun.edu.ph (or .faculty or .teacher)';
        sectionContainer.style.display = 'none';
        sectionSelect.required = false;
      }
    }

    // Initialize on page load
    updateEmailPlaceholder();

    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    const eyeOffIcon = document.getElementById('eyeOffIcon');

    togglePassword.addEventListener('click', function() {
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
      eyeIcon.classList.toggle('hidden');
      eyeOffIcon.classList.toggle('hidden');
    });

    // Toggle confirm password visibility
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const eyeIcon2 = document.getElementById('eyeIcon2');
    const eyeOffIcon2 = document.getElementById('eyeOffIcon2');

    toggleConfirmPassword.addEventListener('click', function() {
      const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      confirmPasswordInput.setAttribute('type', type);
      eyeIcon2.classList.toggle('hidden');
      eyeOffIcon2.classList.toggle('hidden');
    });
  </script>
</body>
</html>
