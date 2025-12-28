<?php
session_start();
include 'includes/db_connect.php';

// Check if user is logged in and has proper role
if (!isset($_SESSION['user']) || !in_array($_SESSION['role'], ['teacher', 'dean', 'program_chair', 'admin'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course = $_POST['course'] ?? '';
    $year_level = $_POST['year_level'] ?? '';
    $section = $_POST['section'] ?? '';
    
    // Validate inputs
    if (empty($course) || empty($year_level) || empty($section)) {
        $error = 'All fields are required.';
    } elseif (!in_array($course, ['BSCS', 'BSIT', 'ACT'])) {
        $error = 'Invalid program selected.';
    } elseif ($year_level < 1 || $year_level > 4) {
        $error = 'Year level must be between 1 and 4.';
    } elseif (!preg_match('/^[A-Z]$/', $section)) {
        $error = 'Section must be a single letter A-Z.';
    } else {
        // Generate section code in format: 1-BSCS-A
        $section_code = "{$year_level}-{$course}-{$section}";
        
        // Check if section already exists
        $check_sql = "SELECT id FROM sections WHERE section_code = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $section_code);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Section {$section_code} already exists.";
        } else {
            // Insert new section - FIXED: using adviser_id column instead of teacher_id
            $sql = "INSERT INTO sections (section_code, course, year_level, section, adviser_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            
            // Check if prepare succeeded
            if ($stmt === false) {
                $error = "Database error: " . $conn->error;
                error_log("SQL Error: " . $conn->error);
            } else {
                // Bind parameters - FIXED: using adviser_id instead of teacher_id
                $stmt->bind_param("ssisi", $section_code, $course, $year_level, $section, $user_id);
                
                if ($stmt->execute()) {
                    $section_id = $conn->insert_id;
                    
                    // Also add to teacher_sections table if it exists
                    $table_check = $conn->query("SHOW TABLES LIKE 'teacher_sections'");
                    if ($table_check && $table_check->num_rows > 0) {
                        $assign_sql = "INSERT INTO teacher_sections (teacher_id, section_id, assigned_at) VALUES (?, ?, NOW())";
                        $assign_stmt = $conn->prepare($assign_sql);
                        if ($assign_stmt) {
                            $assign_stmt->bind_param("ii", $user_id, $section_id);
                            $assign_stmt->execute();
                            $assign_stmt->close();
                        }
                    }
                    
                    $success = "Section {$section_code} created successfully!";
                    $_POST = []; // Clear form
                } else {
                    $error = "Failed to create section: " . $stmt->error;
                }
                $stmt->close();
            }
        }
        $check_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create New Section | PLMUN LMS</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
  <div class="container mx-auto px-4 py-8 max-w-4xl">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow-lg p-6 mb-6 text-white">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">Create New Section</h1>
          <p class="mt-2 opacity-90">CITCS - Add new student sections</p>
        </div>
        <a href="sections.php" class="bg-white text-blue-600 px-4 py-2 rounded-lg hover:bg-gray-100 transition font-semibold">
          ← Back to Sections
        </a>
      </div>
    </div>
    
    <!-- Alert Messages -->
    <?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6" role="alert">
      <div class="flex">
        <div class="py-1"><i class="fas fa-exclamation-circle mr-3"></i></div>
        <div>
          <strong class="font-bold">Error!</strong>
          <span class="block sm:inline"> <?php echo htmlspecialchars($error); ?></span>
        </div>
      </div>
    </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6" role="alert">
      <div class="flex">
        <div class="py-1"><i class="fas fa-check-circle mr-3"></i></div>
        <div>
          <strong class="font-bold">Success!</strong>
          <span class="block sm:inline"> <?php echo htmlspecialchars($success); ?></span>
        </div>
      </div>
    </div>
    <?php endif; ?>
    
    <!-- Form -->
    <div class="bg-white rounded-lg shadow-lg p-6">
      <form method="POST" action="" class="space-y-6">
        <!-- Program Selection -->
        <div>
          <label class="block text-gray-700 text-sm font-bold mb-2" for="course">
            Program <span class="text-red-500">*</span>
          </label>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="relative">
              <input class="sr-only peer" type="radio" name="course" id="course_bscs" value="BSCS" 
                     <?php echo (($_POST['course'] ?? '') === 'BSCS') ? 'checked' : ''; ?> required>
              <label for="course_bscs" class="block p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all">
                <div class="text-center">
                  <div class="text-lg font-semibold text-gray-800">BSCS</div>
                  <div class="text-sm text-gray-600 mt-1">Computer Science</div>
                </div>
              </label>
            </div>
            
            <div class="relative">
              <input class="sr-only peer" type="radio" name="course" id="course_bsit" value="BSIT"
                     <?php echo (($_POST['course'] ?? '') === 'BSIT') ? 'checked' : ''; ?>>
              <label for="course_bsit" class="block p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all">
                <div class="text-center">
                  <div class="text-lg font-semibold text-gray-800">BSIT</div>
                  <div class="text-sm text-gray-600 mt-1">Information Technology</div>
                </div>
              </label>
            </div>
            
            <div class="relative">
              <input class="sr-only peer" type="radio" name="course" id="course_act" value="ACT"
                     <?php echo (($_POST['course'] ?? '') === 'ACT') ? 'checked' : ''; ?>>
              <label for="course_act" class="block p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all">
                <div class="text-center">
                  <div class="text-lg font-semibold text-gray-800">ACT</div>
                  <div class="text-sm text-gray-600 mt-1">Computer Technology</div>
                </div>
              </label>
            </div>
          </div>
        </div>
        
        <!-- Year Level -->
        <div>
          <label class="block text-gray-700 text-sm font-bold mb-2" for="year_level">
            Year Level <span class="text-red-500">*</span>
          </label>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <?php for ($i = 1; $i <= 4; $i++): ?>
            <div class="relative">
              <input class="sr-only peer" type="radio" name="year_level" id="year_<?php echo $i; ?>" value="<?php echo $i; ?>"
                     <?php echo (($_POST['year_level'] ?? '') == $i) ? 'checked' : ''; ?> required>
              <label for="year_<?php echo $i; ?>" class="block p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all text-center">
                <span class="text-lg font-semibold">
                  <?php 
                  $suffixes = ['st', 'nd', 'rd', 'th'];
                  echo $i . $suffixes[$i-1] . ' Year';
                  ?>
                </span>
              </label>
            </div>
            <?php endfor; ?>
          </div>
        </div>
        
        <!-- Section Letter -->
        <div>
          <label class="block text-gray-700 text-sm font-bold mb-2" for="section">
            Section Letter <span class="text-red-500">*</span>
          </label>
          <div class="grid grid-cols-6 md:grid-cols-8 lg:grid-cols-13 gap-2">
            <?php 
            // Generate A-Z options
            for ($i = 65; $i <= 90; $i++):
              $letter = chr($i);
            ?>
            <div class="relative">
              <input class="sr-only peer" type="radio" name="section" id="section_<?php echo $letter; ?>" value="<?php echo $letter; ?>"
                     <?php echo (($_POST['section'] ?? '') === $letter) ? 'checked' : ''; ?> required>
              <label for="section_<?php echo $letter; ?>" class="block p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all text-center">
                <span class="text-lg font-semibold"><?php echo $letter; ?></span>
              </label>
            </div>
            <?php endfor; ?>
          </div>
        </div>
        
        <!-- Preview -->
        <div class="bg-gray-50 p-4 rounded-lg">
          <h3 class="font-bold text-gray-700 mb-2">Section Preview</h3>
          <div id="sectionPreview" class="text-2xl font-bold text-blue-600">
            <?php 
            if (isset($_POST['course']) && isset($_POST['year_level']) && isset($_POST['section'])) {
                echo htmlspecialchars("{$_POST['year_level']}-{$_POST['course']}-{$_POST['section']}");
            } else {
                echo "Select options to preview";
            }
            ?>
          </div>
          <p class="text-gray-600 text-sm mt-1">This is how the section will appear in the system.</p>
        </div>
        
        <!-- Submit Button -->
        <div class="pt-4">
          <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300">
            <i class="fas fa-plus-circle mr-2"></i> Create Section
          </button>
        </div>
      </form>
    </div>
    
    <!-- Info Box -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
      <h3 class="font-bold text-blue-800 mb-2"><i class="fas fa-info-circle mr-2"></i>Section Format</h3>
      <p class="text-blue-700">Format: <code class="font-mono font-bold">Year-Program-Section</code></p>
      <ul class="text-blue-600 text-sm mt-2 space-y-1">
        <li><span class="font-mono">1-BSCS-A</span> = 1st Year Computer Science, Section A</li>
        <li><span class="font-mono">4-BSIT-C</span> = 4th Year Information Technology, Section C</li>
        <li><span class="font-mono">2-ACT-B</span> = 2nd Year Computer Technology, Section B</li>
      </ul>
    </div>
  </div>
  
  <script>
    // Update preview in real-time
    document.addEventListener('DOMContentLoaded', function() {
      const programInputs = document.querySelectorAll('input[name="course"]');
      const yearInputs = document.querySelectorAll('input[name="year_level"]');
      const sectionInputs = document.querySelectorAll('input[name="section"]');
      const preview = document.getElementById('sectionPreview');
      
      function updatePreview() {
        const program = document.querySelector('input[name="course"]:checked');
        const year = document.querySelector('input[name="year_level"]:checked');
        const section = document.querySelector('input[name="section"]:checked');
        
        if (program && year && section) {
          preview.textContent = `${year.value}-${program.value}-${section.value}`;
          preview.classList.remove('text-gray-400');
          preview.classList.add('text-blue-600');
        } else {
          preview.textContent = 'Select options to preview';
          preview.classList.remove('text-blue-600');
          preview.classList.add('text-gray-400');
        }
      }
      
      programInputs.forEach(input => input.addEventListener('change', updatePreview));
      yearInputs.forEach(input => input.addEventListener('change', updatePreview));
      sectionInputs.forEach(input => input.addEventListener('change', updatePreview));
      updatePreview();
    });
  </script>
</body>
</html>
