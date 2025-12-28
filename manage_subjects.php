<?php
session_start();
include 'includes/db_connect.php';

// Only admin/dean can manage subjects
if (!isset($_SESSION['user']) || !in_array($_SESSION['role'], ['dean', 'admin'])) {
    header("Location: login.php");
    exit();
}

// Handle subject assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_subject'])) {
    $teacher_id = $_POST['teacher_id'];
    $subject_id = $_POST['subject_id'];
    $section_id = $_POST['section_id'];
    $schedule = $_POST['schedule'];
    $room = $_POST['room'];
    
    // Check if assignment already exists
    $check_sql = "SELECT id FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ? AND section_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("iii", $teacher_id, $subject_id, $section_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $error = "This subject is already assigned to this teacher for this section.";
    } else {
        // Assign subject
        $sql = "INSERT INTO teacher_subjects (teacher_id, subject_id, section_id, schedule, room) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiss", $teacher_id, $subject_id, $section_id, $schedule, $room);
        
        if ($stmt->execute()) {
            $success = "Subject assigned successfully!";
        } else {
            $error = "Failed to assign subject: " . $stmt->error;
        }
    }
}

// Get all teachers
$teachers = $conn->query("SELECT id, name, email FROM users WHERE role = 'teacher' ORDER BY name");

// Get all subjects
$subjects = $conn->query("SELECT id, subject_code, subject_name, course, year_level FROM subjects ORDER BY course, year_level, subject_code");

// Get all sections
$sections = $conn->query("SELECT id, section_code, course, year_level FROM sections ORDER BY year_level, course, section_code");

// Get current assignments
$assignments = $conn->query("
    SELECT ts.*, u.name as teacher_name, s.section_code, sub.subject_name, sub.subject_code
    FROM teacher_subjects ts
    JOIN users u ON ts.teacher_id = u.id
    JOIN sections s ON ts.section_id = s.id
    JOIN subjects sub ON ts.subject_id = sub.id
    ORDER BY u.name, s.section_code
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Subjects | PLMUN LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Manage Teacher Subjects</h1>
        
        <!-- Assignment Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Assign Subject to Teacher</h2>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Teacher</label>
                        <select name="teacher_id" class="w-full p-3 border rounded-lg" required>
                            <option value="">Select Teacher</option>
                            <?php while ($teacher = $teachers->fetch_assoc()): ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                    <?php echo htmlspecialchars($teacher['name']); ?> (<?php echo htmlspecialchars($teacher['email']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Subject</label>
                        <select name="subject_id" class="w-full p-3 border rounded-lg" required>
                            <option value="">Select Subject</option>
                            <?php while ($subject = $subjects->fetch_assoc()): ?>
                                <option value="<?php echo $subject['id']; ?>">
                                    <?php echo htmlspecialchars($subject['subject_code']); ?> - <?php echo htmlspecialchars($subject['subject_name']); ?>
                                    (<?php echo htmlspecialchars($subject['course']); ?> Year <?php echo $subject['year_level']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Section</label>
                        <select name="section_id" class="w-full p-3 border rounded-lg" required>
                            <option value="">Select Section</option>
                            <?php while ($section = $sections->fetch_assoc()): ?>
                                <option value="<?php echo $section['id']; ?>">
                                    <?php echo htmlspecialchars($section['section_code']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Schedule</label>
                        <input type="text" name="schedule" placeholder="e.g., MWF 8:00-9:00" class="w-full p-3 border rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Room</label>
                        <input type="text" name="room" placeholder="e.g., Room 101" class="w-full p-3 border rounded-lg">
                    </div>
                </div>
                
                <button type="submit" name="assign_subject" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg">
                    <i class="fas fa-plus-circle mr-2"></i> Assign Subject
                </button>
            </form>
        </div>
        
        <!-- Current Assignments -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Current Subject Assignments</h2>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Teacher</th>
                            <th class="px-4 py-3 text-left">Subject</th>
                            <th class="px-4 py-3 text-left">Section</th>
                            <th class="px-4 py-3 text-left">Schedule</th>
                            <th class="px-4 py-3 text-left">Room</th>
                            <th class="px-4 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php while ($assignment = $assignments->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3"><?php echo htmlspecialchars($assignment['teacher_name']); ?></td>
                            <td class="px-4 py-3">
                                <?php echo htmlspecialchars($assignment['subject_code']); ?><br>
                                <small class="text-gray-600"><?php echo htmlspecialchars($assignment['subject_name']); ?></small>
                            </td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($assignment['section_code']); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($assignment['schedule']); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($assignment['room']); ?></td>
                            <td class="px-4 py-3">
                                <a href="edit_assignment.php?id=<?php echo $assignment['id']; ?>" class="text-blue-600 hover:text-blue-800 mr-3">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="delete_assignment.php?id=<?php echo $assignment['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Remove this assignment?')">
                                    <i class="fas fa-trash"></i> Remove
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
