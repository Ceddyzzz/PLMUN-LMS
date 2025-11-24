<?php
include 'includes/db_connect.php';
session_start();

if (isset($_SESSION['user'])) {
  header("Location: dashboard.php");
  exit();
}

if (isset($_POST['register'])) {
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];

  // Validate institutional email
  if (!preg_match("/^[a-zA-Z0-9._%+-]+@plmun\.edu\.ph$/", $email)) {
    $error = "Please use your institutional email (@plmun.edu.ph)";
  } elseif (strlen($password) < 8) {
    $error = "Password must be at least 8 characters long.";
  } elseif ($password !== $confirm_password) {
    $error = "Passwords do not match!";
  } else {
    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
      $error = "This email is already registered.";
    } else {
      // AUTO-DETECT ROLE based on email pattern
      $role = 'student'; // Default
      
      // Check if email starts with teacher/faculty prefixes
      if (preg_match("/^(prof\.|teacher\.|faculty\.)/", $email) ||
          preg_match("/(teacher|faculty)@plmun\.edu\.ph$/", $email)) {
        $role = 'teacher';
        $detected_role = "Teacher/Faculty";
      } else {
        $detected_role = "Student";
      }
      
      // Hash password
      $hashed = password_hash($password, PASSWORD_DEFAULT);
      
      // Insert user with auto-detected role
      $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
      $stmt->bind_param("sss", $email, $hashed, $role);
      
      if ($stmt->execute()) {
        $_SESSION['user'] = $email;
        $_SESSION['user_id'] = $stmt->insert_id;
        $_SESSION['role'] = $role;
        
        // Redirect based on role
        if ($role === 'teacher') {
          header("Location: dashboard.php?welcome=teacher");
        } else {
          header("Location: dashboard.php?welcome=student");
        }
        exit();
      } else {
        $error = "Registration failed. Please try again.";
      }
    }
    $stmt->close();
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up | PLMUN LMS</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
  </style>
</head>
<body class="flex items-center justify-center min-h-screen py-8">
  <div class="bg-white p-8 rounded-lg shadow-lg w-96">
    <div class="text-center mb-6">
      <span class="text-5xl"></span>
      <h1 class="text-2xl font-bold text-blue-700 mt-2">Create Account</h1>
      <p class="text-gray-600 text-sm mt-1">Join PLMUN Learning Management System</p>
    </div>
    
    <?php if(isset($error)): ?>
      <div class="bg-red-50 border border-red-200 text-red-800 p-3 rounded mb-4 text-sm">
        ⚠️ <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>
    
    <form method="POST" action="">
      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Institutional Email</label>
        <input 
          type="email" 
          name="email" 
          placeholder="yourname@plmun.edu.ph" 
          class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          id="emailInput"
          oninput="detectRole(this.value)"
          required
        >
        <div id="roleDetection" class="mt-2 text-sm"></div>
      </div>
      
      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Password</label>
        <input 
          type="password" 
          name="password" 
          placeholder="••••••••" 
          class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
          required
        >
        <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
      </div>
      
      <div class="mb-6">
        <label class="block text-sm font-semibold mb-2">Confirm Password</label>
        <input 
          type="password" 
          name="confirm_password" 
          placeholder="••••••••" 
          class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
          required
        >
      </div>
      
      <button 
        type="submit" 
        name="register" 
        class="bg-blue-600 w-full py-3 text-white rounded-lg hover:bg-blue-700 font-semibold transition"
      >
        Create Account
      </button>
    </form>

    <p class="text-sm text-center mt-6 text-gray-600">
      Already have an account?
      <a href="login.php" class="text-blue-600 hover:underline font-semibold">Login here</a>
    </p>
    
    <div class="mt-6 p-4 bg-blue-50 rounded-lg">
      <p class="text-xs text-gray-700 font-semibold mb-2">Email Guidelines:</p>
      <ul class="text-xs text-gray-600 space-y-1">
        <li> <strong>Teachers:</strong> prof.name@plmun.edu.ph</li>
        <li> <strong>Faculty:</strong> faculty.name@plmun.edu.ph</li>
        <li> <strong>Students:</strong> firstname.lastname@plmun.edu.ph</li>
      </ul>
    </div>
  </div>

  <script>
    function detectRole(email) {
      const detection = document.getElementById('roleDetection');
      
      if (email.length < 3) {
        detection.innerHTML = '';
        return;
      }

      // Check for teacher/faculty patterns
      if (email.match(/^(prof\.|teacher\.|faculty\.)/i) || 
          email.match(/(teacher|faculty)@plmun\.edu\.ph$/i)) {
        detection.innerHTML = `
          <div class="bg-purple-50 border border-purple-200 px-3 py-2 rounded flex items-center space-x-2">
            <span>👨‍🏫</span>
            <span class="text-purple-800 font-semibold">Detected as: Teacher/Faculty</span>
          </div>
        `;
      } else if (email.includes('@plmun.edu.ph')) {
        detection.innerHTML = `
          <div class="bg-blue-50 border border-blue-200 px-3 py-2 rounded flex items-center space-x-2">
            <span>👨‍🎓</span>
            <span class="text-blue-800 font-semibold">Detected as: Student</span>
          </div>
        `;
      } else if (email.includes('@')) {
        detection.innerHTML = `
          <div class="bg-red-50 border border-red-200 px-3 py-2 rounded">
            <span class="text-red-800 text-xs">⚠️ Must use @plmun.edu.ph email</span>
          </div>
        `;
      } else {
        detection.innerHTML = '';
      }
    }
  </script>
</body>
</html>
