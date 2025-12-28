<?php
session_start();
include 'includes/db_connect.php';

// ✅ Only admin can access this page
// You can add admin role check here if you have admin accounts
// For now, this is a protected page that should only be accessible to authorized personnel

if (isset($_POST['create_account'])) {
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];
  $role = $_POST['role'];
  $department = trim($_POST['department'] ?? '');

  // ✅ Only allow dean and program_chair roles
  if (!in_array($role, ['dean', 'program_chair'])) {
    $error = "This form is only for creating Dean and Program Chair accounts.";
  }
  // Validate institutional email format
  elseif (!preg_match("/^[a-zA-Z0-9._%+-]+@plmun\.edu\.ph$/", $email)) {
    $error = "Please use institutional email format (e.g., name@plmun.edu.ph)";
  } else {
    // Extract the username part before @plmun.edu.ph
    $emailParts = explode('@', $email);
    $username = $emailParts[0];
    
    // ✅ Validate email format based on role
    if ($role === 'dean') {
      // Dean must have .dean in email
      if (stripos($username, '.dean') === false) {
        $error = "Dean email must include .dean (e.g., john.dean@plmun.edu.ph)";
      }
    } elseif ($role === 'program_chair') {
      // Program Chair must have .chair or .pc in email
      if (stripos($username, '.chair') === false && stripos($username, '.pc') === false) {
        $error = "Program Chair email must include .chair or .pc (e.g., jane.chair@plmun.edu.ph)";
      }
    }
  }
  
  // Continue with account creation if no errors
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
        
        // If your users table has a department column, include it
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, department) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $hashed_password, $role, $department);
        
        if ($stmt->execute()) {
          $success = "Account created successfully! Credentials can now be shared with the user.";
          // Clear form
          $name = $email = $department = '';
        } else {
          $error = "Account creation failed. Please try again.";
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
  <title>Admin - Create Account | PLMUN LMS</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
    }
  </style>
</head>
<body class="flex items-center justify-center min-h-screen py-8">
  <div class="bg-white p-8 rounded-lg shadow-2xl w-full max-w-md">
    <div class="text-center mb-6">
      <h1 class="text-2xl font-bold text-purple-700 mt-2">Admin Panel</h1>
      <p class="text-gray-600 text-sm mt-1">Create Dean & Program Chair Accounts</p>
    </div>
    
    <?php if(isset($error)) echo "<p class='text-red-500 text-center mb-4 bg-red-50 p-3 rounded'>$error</p>"; ?>
    <?php if(isset($success)) echo "<p class='text-green-500 text-center mb-4 bg-green-50 p-3 rounded'>$success</p>"; ?>
    
    <form method="POST" action="">
      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-semibold mb-2">Full Name *</label>
        <input 
          type="text" 
          name="name" 
          value="<?php echo htmlspecialchars($name ?? ''); ?>"
          placeholder="Dr. John Smith" 
          class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" 
          required
        >
      </div>

      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-semibold mb-2">Role *</label>
        <select 
          name="role" 
          id="roleSelect"
          class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
          required
          onchange="updateEmailPlaceholder()"
        >
          <option value="">-- Select Role --</option>
          <option value="dean">Dean</option>
          <option value="program_chair">Program Chair</option>
        </select>
      </div>

      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-semibold mb-2">Department/College</label>
        <input 
          type="text" 
          name="department" 
          value="<?php echo htmlspecialchars($department ?? ''); ?>"
          placeholder="College of Computer Studies" 
          class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
        >
        <p class="text-xs text-gray-500 mt-1">Optional: For organizational purposes</p>
      </div>

      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-semibold mb-2">Institutional Email *</label>
        <input 
          type="email" 
          name="email" 
          id="emailInput"
          value="<?php echo htmlspecialchars($email ?? ''); ?>"
          placeholder="john.dean@plmun.edu.ph" 
          class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" 
          required
        >
        <p class="text-xs text-gray-500 mt-1" id="emailHint">
          Select a role to see email format requirements
        </p>
      </div>
      
      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-semibold mb-2">Password *</label>
        <div class="relative">
          <input 
            type="password" 
            id="password"
            name="password" 
            placeholder="••••••••••" 
            class="w-full p-3 pr-12 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" 
            required
          >
          <button 
            type="button" 
            id="togglePassword"
            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none"
          >
            <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <svg id="eyeOffIcon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
            </svg>
          </button>
        </div>
        <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
      </div>
      
      <div class="mb-6">
        <label class="block text-gray-700 text-sm font-semibold mb-2">Confirm Password *</label>
        <div class="relative">
          <input 
            type="password" 
            id="confirmPassword"
            name="confirm_password" 
            placeholder="••••••••••" 
            class="w-full p-3 pr-12 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" 
            required
          >
          <button 
            type="button" 
            id="toggleConfirmPassword"
            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none"
          >
            <svg id="eyeIcon2" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <svg id="eyeOffIcon2" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
            </svg>
          </button>
        </div>
      </div>

      <div class="bg-purple-50 border-l-4 border-purple-500 p-3 mb-4">
        <p class="text-xs text-purple-800">
          <strong>⚠️ Security Notice:</strong> These credentials will be used by high-level personnel. Ensure the password is strong and communicated securely.
        </p>
      </div>
      
      <button 
        type="submit" 
        name="create_account" 
        class="bg-purple-600 w-full py-3 text-white rounded-lg hover:bg-purple-700 font-semibold transition"
      >
        Create Account
      </button>
    </form>

    <p class="text-xs text-center mt-6 text-gray-600">
      <a href="dashboard.php" class="text-purple-600 hover:underline font-semibold">← Back to Dashboard</a>
    </p>
  </div>

  <script>
    // Update email placeholder and hint based on role
    function updateEmailPlaceholder() {
      const role = document.getElementById('roleSelect').value;
      const emailInput = document.getElementById('emailInput');
      const emailHint = document.getElementById('emailHint');
      
      if (role === 'dean') {
        emailInput.placeholder = 'john.dean@plmun.edu.ph';
        emailHint.innerHTML = '<strong>Format:</strong> name.dean@plmun.edu.ph';
        emailHint.classList.add('text-purple-600');
      } else if (role === 'program_chair') {
        emailInput.placeholder = 'jane.chair@plmun.edu.ph';
        emailHint.innerHTML = '<strong>Format:</strong> name.chair@plmun.edu.ph (or name.pc@plmun.edu.ph)';
        emailHint.classList.add('text-purple-600');
      } else {
        emailInput.placeholder = 'Select a role first';
        emailHint.textContent = 'Select a role to see email format requirements';
        emailHint.classList.remove('text-purple-600');
      }
    }

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
