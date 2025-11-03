<?php
include 'includes/db_connect.php';
session_start();

// ✅ If already logged in, redirect to dashboard
if (isset($_SESSION['user'])) {
  header("Location: dashboard.php");
  exit();
}

if (isset($_POST['register'])) {
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];

  // ✅ Validate institutional email
  if (!preg_match("/^[a-zA-Z0-9._%+-]+@plmun\.edu\.ph$/", $email)) {
    $error = "Please use your institutional email (e.g., name@plmun.edu.ph)";
  } elseif (strlen($password) < 8) {
    $error = "Password must be at least 8 characters long.";
  } elseif ($password !== $confirm_password) {
    $error = "Passwords do not match!";
  } else {
    // ✅ FIXED: Use prepared statement to check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
      $error = "This email is already registered.";
    } else {
      // ✅ FIXED: Use prepared statement to insert new user
      $hashed = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
      $stmt->bind_param("ss", $email, $hashed);
      
      if ($stmt->execute()) {
        $_SESSION['user'] = $email;
        $_SESSION['user_id'] = $stmt->insert_id;
        header("Location: dashboard.php");
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
      background: url('background/campus.jpg') no-repeat center center fixed;
      background-size: cover;
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
    
    <?php if(isset($error)) echo "<p class='text-red-500 text-center mb-3 bg-red-50 p-3 rounded text-sm'>$error</p>"; ?>
    
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
        <p class="text-xs text-gray-500 mt-1">Use your official PLMUN email address</p>
      </div>
      
      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-semibold mb-2">Password</label>
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
        <label class="block text-gray-700 text-sm font-semibold mb-2">Confirm Password</label>
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
    
    <p class="text-xs text-center mt-4 text-gray-500">
      By signing up, you agree to our Terms of Service and Privacy Policy
    </p>
  </div>
</body>
</html>