<?php
session_start();
include 'includes/db_connect.php';

// ✅ If already logged in, go to dashboard
if (isset($_SESSION['user'])) {
  header("Location: /PLMUN%20LMS/dashboard.php");
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

      // Verify password (matches hashed one from registration)
      if (password_verify($password, $row['password'])) {
        $_SESSION['user'] = $email;
        $_SESSION['user_id'] = $row['id']; // Store user ID for future use
        header("Location: /PLMUN%20LMS/dashboard.php");
        exit();
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
      <span class="text-5xl"></span>
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
      </div>
      
      <div class="mb-6">
        <label class="block text-gray-700 text-sm font-semibold mb-2">Password</label>
        <input 
          type="password" 
          name="password" 
          placeholder="••••••••" 
          class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
          required
        >
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
    
    <p class="text-xs text-center mt-4 text-gray-500">
      <a href="#" class="hover:underline">Forgot password?</a>
    </p>
  </div>
</body>
</html>
