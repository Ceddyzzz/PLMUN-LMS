<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

include "includes/db_connect.php";  // Your database connection

// Get current month/year
$month = isset($_GET['month']) ? $_GET['month'] : date("n");
$year  = isset($_GET['year']) ? $_GET['year'] : date("Y");

// Philippine Holidays
$philHolidays = [
    "01-01" => ["New Year's Day", "#ef4444"],
    "04-09" => ["Araw ng Kagitingan", "#f97316"],
    "05-01" => ["Labor Day", "#10b981"],
    "06-12" => ["Independence Day", "#3b82f6"],
    "11-01" => ["All Saints Day", "#8b5cf6"],
    "12-25" => ["Christmas Day", "#ef4444"],
    "12-30" => ["Rizal Day", "#3b82f6"]
];

// Fetch user events
$sql = "SELECT * FROM events WHERE user_id = ?";
$stmt = $conn->prepare($sql);

$userId = $_SESSION['user'];

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$userEvents = $result->fetch_all(MYSQLI_ASSOC);

// Group events by date
$eventsByDate = [];
foreach ($userEvents as $event) {
    $eventsByDate[$event['event_date']][] = $event;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Calendar | PLMUN LMS</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
<header class="bg-blue-900 text-white p-4">
  <div class="max-w-7xl mx-auto flex justify-between items-center">
    <h1 class="text-xl font-bold">PLMUN LMS</h1>
    <nav>
      <ul class="flex space-x-6">
      <li><a href="/PLMUN%20LMS/dashboard.php" class="hover:text-yellow-300">Dashboard</a></li>
      <li><a href="/PLMUN%20LMS/announcement.php" class="hover:text-yellow-300">Announcements</a></li>
      <li><a href="/PLMUN%20LMS/chat.php" class="hover:text-yellow-300">Chat</a></li>
      <li><a href="/PLMUN%20LMS/calendar.php" class="hover:text-yellow-300">Calendar</a></li>
      <li><a href="/PLMUN%20LMS/e-books.php" class="hover:text-yellow-300">E-Books</a></li>
      <li><a href="/PLMUN%20LMS/sections.php" class="hover:text-yellow-300 transition">Assign Subjects</a></li>
      <li><a href="/PLMUN%20LMS/sections.php" class="hover:text-yellow-300 transition">Sections</a></li>
      <li><a href="/PLMUN%20LMS/logout.php" class="hover:text-yellow-300">Logout</a></li>
      </ul>
    </nav>
  </div>
</header>

<main class="max-w-5xl mx-auto p-6">
  <div class="flex justify-between items-center">
    <a href="?month=<?= $month-1 ?>&year=<?= $year ?>" class="px-3 py-2 bg-white shadow rounded">← Prev</a>
    <h2 class="text-2xl font-bold">
      <?= date("F Y", strtotime("$year-$month-01")) ?>
    </h2>
    <a href="?month=<?= $month+1 ?>&year=<?= $year ?>" class="px-3 py-2 bg-white shadow rounded">Next →</a>
  </div>

  <div class="grid grid-cols-7 text-center font-bold mt-6">
    <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
  </div>

  <div class="grid grid-cols-7 gap-2 mt-2">
    <?php
    $firstDay = date("w", strtotime("$year-$month-01"));
    $daysInMonth = date("t", strtotime("$year-$month-01"));

    // blank days
    for ($i=0; $i<$firstDay; $i++) echo "<div></div>";

    // days
    for ($day = 1; $day <= $daysInMonth; $day++):
        $dateStr = "$year-$month-" . str_pad($day, 2, "0", STR_PAD_LEFT);
        $md = date("m-d", strtotime($dateStr));
    ?>
      <div class="bg-white p-2 h-32 border shadow rounded relative hover:bg-blue-50 cursor-pointer"
           onclick="openModal('<?= $dateStr ?>')">

        <p class="font-semibold text-sm"><?= $day ?></p>

        <!-- Philippine holiday -->
        <?php if (isset($philHolidays[$md])): ?>
          <div class="text-xs bg-red-100 text-red-700 px-1 rounded">
            <?= $philHolidays[$md][0] ?>
          </div>
        <?php endif; ?>

        <!-- User events -->
        <?php if (isset($eventsByDate[$dateStr])): ?>
          <?php foreach ($eventsByDate[$dateStr] as $e): ?>
            <div class="text-xs mt-1 px-1 rounded" style="background: <?= $e['color'] ?>20; color: <?= $e['color'] ?>">
              <?= htmlspecialchars($e['title']) ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    <?php endfor; ?>
  </div>
</main>

<!-- Add Event Modal -->
<div id="eventModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center">
  <form action="api/save_event.php" method="POST" class="bg-white p-6 rounded shadow w-96">
    <h3 class="text-xl font-bold mb-4">Add Event</h3>

    <input type="hidden" name="event_date" id="event_date">

    <label class="block font-medium">Title</label>
    <input type="text" name="title" class="w-full border p-2 rounded mb-3" required>

    <label class="block font-medium">Description</label>
    <textarea name="description" class="w-full border p-2 rounded mb-3"></textarea>

    <label class="block font-medium">Color</label>
    <input type="color" name="color" class="mb-3">

    <div class="flex justify-end space-x-2">
      <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
      <button class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
    </div>
  </form>
</div>

<script>
function openModal(date) {
    document.getElementById("event_date").value = date;
    document.getElementById("eventModal").classList.remove("hidden");
}

function closeModal() {
    document.getElementById("eventModal").classList.add("hidden");
}
</script>

</body>
</html>
