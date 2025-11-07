<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}

include 'includes/db_connect.php';

// Get filter and page from URL
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 5; // Announcements per page
$offset = ($page - 1) * $per_page;

// Build SQL query based on filter
$where_clause = "";
$valid_filters = ['urgent', 'event', 'academic', 'info', 'reminder'];

if ($filter !== 'all' && in_array($filter, $valid_filters)) {
  $where_clause = "WHERE category = '$filter'";
}

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM announcements $where_clause";
$count_result = $conn->query($count_sql);
$total_announcements = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_announcements / $per_page);

// Get announcements for current page
$sql = "SELECT a.*, u.name as author_name, u.email as author_email 
        FROM announcements a
        LEFT JOIN users u ON a.author_id = u.id
        $where_clause
        ORDER BY a.created_at DESC
        LIMIT $per_page OFFSET $offset";

$result = $conn->query($sql);
$announcements = [];

while ($row = $result->fetch_assoc()) {
  $announcements[] = $row;
}

// Get count for each category
$urgent_count = $conn->query("SELECT COUNT(*) as count FROM announcements WHERE category='urgent'")->fetch_assoc()['count'];
$event_count = $conn->query("SELECT COUNT(*) as count FROM announcements WHERE category='event'")->fetch_assoc()['count'];
$academic_count = $conn->query("SELECT COUNT(*) as count FROM announcements WHERE category='academic'")->fetch_assoc()['count'];
$info_count = $conn->query("SELECT COUNT(*) as count FROM announcements WHERE category='info'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Announcements | PLMUN LMS</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
  <?php include 'includes/header.php'; ?>

  <main class="p-6 max-w-7xl mx-auto">
    <h2 class="text-3xl font-bold mb-6">📢 Announcements</h2>
    
    <!-- Filter Tabs -->
    <div class="flex space-x-4 mb-6 overflow-x-auto pb-2">
      <a href="?filter=all" 
         class="px-4 py-2 rounded-lg whitespace-nowrap transition <?php echo $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
        All (<?php echo $total_announcements; ?>)
      </a>
      <a href="?filter=urgent" 
         class="px-4 py-2 rounded-lg whitespace-nowrap transition <?php echo $filter === 'urgent' ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
        Urgent (<?php echo $urgent_count; ?>)
      </a>
      <a href="?filter=event" 
         class="px-4 py-2 rounded-lg whitespace-nowrap transition <?php echo $filter === 'event' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
        Events (<?php echo $event_count; ?>)
      </a>
      <a href="?filter=academic" 
         class="px-4 py-2 rounded-lg whitespace-nowrap transition <?php echo $filter === 'academic' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
        Academic (<?php echo $academic_count; ?>)
      </a>
      <a href="?filter=info" 
         class="px-4 py-2 rounded-lg whitespace-nowrap transition <?php echo $filter === 'info' ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
        Info (<?php echo $info_count; ?>)
      </a>
      <a href="?filter=reminder" 
         class="px-4 py-2 rounded-lg whitespace-nowrap transition <?php echo $filter === 'reminder' ? 'bg-yellow-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
        Reminder
      </a>
    </div>

    <!-- Announcements List -->
    <?php if (count($announcements) > 0): ?>
      <div class="space-y-6">
        <?php foreach ($announcements as $announcement): 
          // Determine badge color based on category
          $badge_colors = [
            'urgent' => 'bg-red-500',
            'event' => 'bg-blue-500',
            'academic' => 'bg-green-500',
            'info' => 'bg-purple-500',
            'reminder' => 'bg-yellow-500'
          ];
          $border_colors = [
            'urgent' => 'border-red-500',
            'event' => 'border-blue-500',
            'academic' => 'border-green-500',
            'info' => 'border-purple-500',
            'reminder' => 'border-yellow-500'
          ];
          $bg_colors = [
            'urgent' => 'bg-red-50',
            'event' => 'bg-white',
            'academic' => 'bg-white',
            'info' => 'bg-white',
            'reminder' => 'bg-white'
          ];
          
          $category = $announcement['category'];
          $badge_color = $badge_colors[$category] ?? 'bg-gray-500';
          $border_color = $border_colors[$category] ?? 'border-gray-500';
          $bg_color = $bg_colors[$category] ?? 'bg-white';
          
          $author_name = $announcement['author_name'] ?: ($announcement['author_email'] ? explode('@', $announcement['author_email'])[0] : 'Unknown');
          $formatted_date = date('F j, Y', strtotime($announcement['created_at']));
        ?>
        
        <div class="<?php echo $bg_color; ?> border-l-4 <?php echo $border_color; ?> p-6 rounded-lg shadow hover:shadow-lg transition">
          <div class="flex justify-between items-start mb-2">
            <span class="<?php echo $badge_color; ?> text-white px-3 py-1 rounded-full text-xs font-bold uppercase">
              <?php echo htmlspecialchars($category); ?>
            </span>
            <span class="text-gray-600 text-sm"><?php echo $formatted_date; ?></span>
          </div>
          <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($announcement['title']); ?></h3>
          <p class="text-gray-700 mb-2">
            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
          </p>
          <p class="text-sm text-gray-600">Posted by: <?php echo htmlspecialchars($author_name); ?></p>
        </div>
        
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
      <div class="flex justify-center mt-8 space-x-2">
        <!-- Previous Button -->
        <?php if ($page > 1): ?>
          <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>" 
             class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
            ← Previous
          </a>
        <?php endif; ?>

        <!-- Page Numbers -->
        <?php
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        for ($i = $start_page; $i <= $end_page; $i++): 
        ?>
          <a href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>" 
             class="px-4 py-2 rounded transition <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
            <?php echo $i; ?>
          </a>
        <?php endfor; ?>

        <!-- Next Button -->
        <?php if ($page < $total_pages): ?>
          <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>" 
             class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
            Next →
          </a>
        <?php endif; ?>
      </div>

      <!-- Page Info -->
      <div class="text-center mt-4 text-gray-600">
        Showing page <?php echo $page; ?> of <?php echo $total_pages; ?> 
        (<?php echo $total_announcements; ?> total announcements)
      </div>
      <?php endif; ?>

    <?php else: ?>
      <!-- No Announcements Found -->
      <div class="bg-white rounded-lg shadow p-12 text-center">
        <div class="text-6xl mb-4">📭</div>
        <h3 class="text-2xl font-bold mb-2">No Announcements Found</h3>
        <p class="text-gray-600">
          <?php if ($filter !== 'all'): ?>
            There are no <strong><?php echo htmlspecialchars($filter); ?></strong> announcements at the moment.
            <br>
            <a href="?filter=all" class="text-blue-600 hover:underline">View all announcements</a>
          <?php else: ?>
            Check back later for updates.
          <?php endif; ?>
        </p>
      </div>
    <?php endif; ?>

  </main>

  <script>
    // Smooth scroll to top when changing pages
    window.addEventListener('load', function() {
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.has('page') || urlParams.has('filter')) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });
  </script>
</body>
</html>
