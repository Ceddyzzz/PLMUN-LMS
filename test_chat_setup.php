<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Chat System Diagnostic | PLMUN LMS</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
  <div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">🔍 Chat System Diagnostic Tool</h1>
    
    <div class="space-y-4">
      
      <!-- Check 1: Session -->
      <div class="bg-white p-4 rounded-lg shadow">
        <h3 class="font-bold text-lg mb-2">1. Session Check</h3>
        <?php if (isset($_SESSION['user'])): ?>
          <p class="text-green-600">✅ You are logged in as: <strong><?php echo htmlspecialchars($_SESSION['user']); ?></strong></p>
        <?php else: ?>
          <p class="text-red-600">❌ You are NOT logged in!</p>
          <p class="text-sm mt-2">
            <a href="login.php" class="text-blue-600 underline">Click here to login</a>
          </p>
        <?php endif; ?>
      </div>

      <!-- Check 2: Database Connection -->
      <div class="bg-white p-4 rounded-lg shadow">
        <h3 class="font-bold text-lg mb-2">2. Database Connection</h3>
        <?php
        if (file_exists('includes/db_connect.php')) {
          echo '<p class="text-green-600">✅ db_connect.php file exists</p>';
          include 'includes/db_connect.php';
          
          if ($conn->connect_error) {
            echo '<p class="text-red-600">❌ Database connection failed: ' . $conn->connect_error . '</p>';
          } else {
            echo '<p class="text-green-600">✅ Database connected successfully</p>';
          }
        } else {
          echo '<p class="text-red-600">❌ db_connect.php file NOT FOUND in includes/ folder</p>';
        }
        ?>
      </div>

      <!-- Check 3: API Files -->
      <div class="bg-white p-4 rounded-lg shadow">
        <h3 class="font-bold text-lg mb-2">3. API Files Check</h3>
        <?php
        $api_files = [
          'get_contacts.php',
          'get_messages.php',
          'send_message.php',
          'typing_status.php'
        ];
        
        $all_exist = true;
        foreach ($api_files as $file) {
          $path = "api/$file";
          if (file_exists($path)) {
            echo "<p class='text-green-600'>✅ $file exists</p>";
          } else {
            echo "<p class='text-red-600'>❌ $file NOT FOUND in api/ folder</p>";
            $all_exist = false;
          }
        }
        
        if (!$all_exist) {
          echo '<p class="text-orange-600 mt-2 text-sm">⚠️ Create the missing files in the api/ folder</p>';
        }
        ?>
      </div>

      <!-- Check 4: Database Tables -->
      <div class="bg-white p-4 rounded-lg shadow">
        <h3 class="font-bold text-lg mb-2">4. Database Tables Check</h3>
        <?php
        if (isset($conn) && !$conn->connect_error) {
          $required_tables = ['users', 'messages', 'notifications', 'typing_status'];
          
          foreach ($required_tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
              echo "<p class='text-green-600'>✅ Table '$table' exists</p>";
              
              // Show row count
              $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
              $count = $count_result->fetch_assoc()['count'];
              echo "<p class='text-sm text-gray-600 ml-6'>→ Contains $count rows</p>";
            } else {
              echo "<p class='text-red-600'>❌ Table '$table' NOT FOUND</p>";
            }
          }
        } else {
          echo '<p class="text-gray-500">⚠️ Cannot check tables - database not connected</p>';
        }
        ?>
      </div>

      <!-- Check 5: Test API Endpoints -->
      <div class="bg-white p-4 rounded-lg shadow">
        <h3 class="font-bold text-lg mb-2">5. Test API Endpoints</h3>
        <p class="text-sm text-gray-600 mb-3">Click the buttons below to test each API endpoint:</p>
        
        <div class="space-y-2">
          <button onclick="testAPI('get_contacts')" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full text-left">
            Test get_contacts.php
          </button>
          <div id="result-get_contacts" class="ml-4 text-sm"></div>
          
          <button onclick="testAPI('get_messages')" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 w-full text-left">
            Test get_messages.php (requires user_id=1)
          </button>
          <div id="result-get_messages" class="ml-4 text-sm"></div>
        </div>
      </div>

      <!-- Check 6: Users in Database -->
      <div class="bg-white p-4 rounded-lg shadow">
        <h3 class="font-bold text-lg mb-2">6. Users in Database</h3>
        <?php
        if (isset($conn) && !$conn->connect_error) {
          $users_result = $conn->query("SELECT id, email, name FROM users LIMIT 10");
          
          if ($users_result && $users_result->num_rows > 0) {
            echo "<p class='text-green-600 mb-2'>✅ Found " . $users_result->num_rows . " users:</p>";
            echo "<ul class='list-disc ml-6 text-sm'>";
            while ($user = $users_result->fetch_assoc()) {
              echo "<li>ID: {$user['id']} - {$user['email']} " . ($user['name'] ? "({$user['name']})" : "") . "</li>";
            }
            echo "</ul>";
          } else {
            echo "<p class='text-red-600'>❌ No users found in database</p>";
            echo "<p class='text-sm mt-2'>⚠️ You need to register at least 2 users to test chat</p>";
          }
        }
        ?>
      </div>

      <!-- Summary -->
      <div class="bg-blue-50 border-l-4 border-blue-600 p-4 rounded">
        <h3 class="font-bold text-lg mb-2">📋 Next Steps:</h3>
        <ol class="list-decimal ml-6 space-y-1 text-sm">
          <li>Make sure all checks above show ✅ green checkmarks</li>
          <li>If any API files are missing, create them from the artifacts</li>
          <li>If database tables are missing, run the SQL update script</li>
          <li>If no users exist, register at least 2 users to test chat</li>
          <li>After everything is ✅, go back to <a href="chat.php" class="text-blue-600 underline">chat.php</a></li>
        </ol>
      </div>

    </div>
  </div>

  <script>
    function testAPI(endpoint) {
      const resultDiv = document.getElementById('result-' + endpoint);
      resultDiv.innerHTML = '<p class="text-gray-500">Testing...</p>';
      
      let url = 'api/' + endpoint + '.php';
      if (endpoint === 'get_messages') {
        url += '?user_id=1&last_id=0';
      }
      
      fetch(url)
        .then(response => response.json())
        .then(data => {
          if (data.error) {
            resultDiv.innerHTML = '<p class="text-red-600">❌ Error: ' + data.error + '</p>';
          } else {
            resultDiv.innerHTML = '<p class="text-green-600">✅ Success! Response:</p><pre class="bg-gray-100 p-2 rounded mt-1 text-xs overflow-auto">' + JSON.stringify(data, null, 2) + '</pre>';
          }
        })
        .catch(error => {
          resultDiv.innerHTML = '<p class="text-red-600">❌ Failed to connect: ' + error + '</p>';
        });
    }
  </script>
</body>
</html>
