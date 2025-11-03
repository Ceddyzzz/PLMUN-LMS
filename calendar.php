<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Calendar | PLMUN LMS</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <style>
    .calendar-day {
      min-height: 100px;
    }
    .event-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      display: inline-block;
    }
  </style>
</head>
<body class="bg-gray-100">
  
<header class="bg-blue-900 text-white p-4">
  <div class="max-w-7xl mx-auto flex justify-between items-center">
    <h1 class="text-xl font-bold">PLMUN LMS</h1>
    <ul class="flex space-x-6">
        <li><a href="/PLMUN%20LMS/dashboard.php" class="hover:text-yellow-300">Dashboard</a></li>
        <li><a href="/PLMUN%20LMS/announcement.php" class="hover:text-yellow-300">Announcements</a></li>
        <li><a href="/PLMUN%20LMS/chat_realtime.php" class="hover:text-yellow-300">Chat</a></li>
        <li><a href="/PLMUN%20LMS/assignment.php" class="hover:text-yellow-300">Assignments</a></li>
        <li><a href="/PLMUN%20LMS/calendar.php" class="hover:text-yellow-300">Calendar</a></li>
        <li><a href="/PLMUN%20LMS/e-books.php" class="hover:text-yellow-300">E-Books</a></li>
        <li><a href="/PLMUN%20LMS/quiz.php" class="hover:text-yellow-300">Quiz</a></li>
        <li><a href="/PLMUN%20LMS/logout.php" class="hover:text-yellow-300">Logout</a></li>
    </ul>
  </div>
</header>

  <main class="p-6 max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-3xl font-bold">📅 Calendar</h2>
      <div class="flex items-center space-x-4">
        <button class="px-4 py-2 bg-white rounded-lg shadow hover:bg-gray-50">← Previous</button>
        <span class="text-xl font-semibold">October 2025</span>
        <button class="px-4 py-2 bg-white rounded-lg shadow hover:bg-gray-50">Next →</button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- Calendar Grid -->
      <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
        <!-- Calendar Header -->
        <div class="grid grid-cols-7 gap-2 mb-2">
          <div class="text-center font-bold text-gray-600 py-2">Sun</div>
          <div class="text-center font-bold text-gray-600 py-2">Mon</div>
          <div class="text-center font-bold text-gray-600 py-2">Tue</div>
          <div class="text-center font-bold text-gray-600 py-2">Wed</div>
          <div class="text-center font-bold text-gray-600 py-2">Thu</div>
          <div class="text-center font-bold text-gray-600 py-2">Fri</div>
          <div class="text-center font-bold text-gray-600 py-2">Sat</div>
        </div>

        <!-- Calendar Days -->
        <div class="grid grid-cols-7 gap-2">
          <!-- Previous month days -->
          <div class="calendar-day border rounded p-2 bg-gray-50 text-gray-400">
            <span class="text-sm">29</span>
          </div>
          <div class="calendar-day border rounded p-2 bg-gray-50 text-gray-400">
            <span class="text-sm">30</span>
          </div>

          <!-- Current month days -->
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">1</span>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">2</span>
            <div class="mt-1">
              <div class="text-xs bg-blue-100 text-blue-800 px-1 py-0.5 rounded mb-1">Lab 2PM</div>
            </div>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">3</span>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">4</span>
            <div class="mt-1">
              <div class="text-xs bg-green-100 text-green-800 px-1 py-0.5 rounded mb-1">Quiz 10AM</div>
            </div>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">5</span>
          </div>

          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">6</span>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">7</span>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">8</span>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">9</span>
            <div class="mt-1">
              <div class="text-xs bg-blue-100 text-blue-800 px-1 py-0.5 rounded mb-1">Lab 2PM</div>
            </div>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">10</span>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">11</span>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">12</span>
          </div>

          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">13</span>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">14</span>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">15</span>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">16</span>
            <div class="mt-1">
              <div class="text-xs bg-blue-100 text-blue-800 px-1 py-0.5 rounded mb-1">Lab 2PM</div>
            </div>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">17</span>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">18</span>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">19</span>
          </div>

          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">20</span>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">21</span>
          </div>
          <!-- Today -->
          <div class="calendar-day border-2 border-blue-600 bg-blue-50 rounded p-2">
            <span class="text-sm font-bold text-blue-600">22</span>
            <div class="mt-1">
              <div class="text-xs bg-purple-100 text-purple-800 px-1 py-0.5 rounded mb-1">Event 3PM</div>
            </div>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">23</span>
            <div class="mt-1">
              <div class="text-xs bg-blue-100 text-blue-800 px-1 py-0.5 rounded mb-1">Lab 2PM</div>
            </div>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">24</span>
            <div class="mt-1">
              <div class="text-xs bg-red-100 text-red-800 px-1 py-0.5 rounded mb-1">Due 11PM</div>
            </div>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">25</span>
            <div class="mt-1">
              <div class="text-xs bg-green-100 text-green-800 px-1 py-0.5 rounded mb-1">Quiz 10AM</div>
            </div>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">26</span>
          </div>

          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">27</span>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">28</span>
            <div class="mt-1">
              <div class="text-xs bg-red-100 text-red-800 px-1 py-0.5 rounded mb-1">Due 11PM</div>
            </div>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">29</span>
            <div class="mt-1">
              <div class="text-xs bg-orange-100 text-orange-800 px-1 py-0.5 rounded mb-1">Exam 9AM</div>
            </div>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">30</span>
            <div class="mt-1">
              <div class="text-xs bg-blue-100 text-blue-800 px-1 py-0.5 rounded mb-1">Lab 2PM</div>
              <div class="text-xs bg-purple-100 text-purple-800 px-1 py-0.5 rounded">Event 3PM</div>
            </div>
          </div>
          <div class="calendar-day border rounded p-2 hover:bg-blue-50 cursor-pointer">
            <span class="text-sm font-semibold">31</span>
          </div>

          <!-- Next month days -->
          <div class="calendar-day border rounded p-2 bg-gray-50 text-gray-400">
            <span class="text-sm">1</span>
          </div>
          <div class="calendar-day border rounded p-2 bg-gray-50 text-gray-400">
            <span class="text-sm">2</span>
          </div>
        </div>

        <!-- Legend -->
        <div class="mt-6 pt-4 border-t flex flex-wrap gap-4 text-sm">
          <div class="flex items-center space-x-2">
            <span class="event-dot bg-blue-500"></span>
            <span>Lab Session</span>
          </div>
          <div class="flex items-center space-x-2">
            <span class="event-dot bg-red-500"></span>
            <span>Assignment Due</span>
          </div>
          <div class="flex items-center space-x-2">
            <span class="event-dot bg-green-500"></span>
            <span>Quiz</span>
          </div>
          <div class="flex items-center space-x-2">
            <span class="event-dot bg-orange-500"></span>
            <span>Exam</span>
          </div>
          <div class="flex items-center space-x-2">
            <span class="event-dot bg-purple-500"></span>
            <span>Event</span>
          </div>
        </div>
      </div>

      <!-- Upcoming Events Sidebar -->
      <div class="space-y-6">
        <!-- Today's Events -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-xl font-bold mb-4">Today's Schedule</h3>
          <div class="space-y-3">
            <div class="border-l-4 border-purple-500 pl-3 py-2">
              <p class="font-semibold text-sm">AI Guest Lecture</p>
              <p class="text-xs text-gray-600">3:00 PM - 5:00 PM</p>
              <p class="text-xs text-gray-600">Innovation Hub</p>
            </div>
          </div>
        </div>

        <!-- Upcoming Events -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-xl font-bold mb-4">Upcoming Events</h3>
          <div class="space-y-4">
            <div class="border-l-4 border-red-500 pl-3 py-2">
              <p class="font-semibold text-sm">Database Project Due</p>
              <p class="text-xs text-gray-600">Oct 24, 11:59 PM</p>
              <span class="text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded">2 days left</span>
            </div>

            <div class="border-l-4 border-green-500 pl-3 py-2">
              <p class="font-semibold text-sm">Web Dev Quiz</p>
              <p class="text-xs text-gray-600">Oct 25, 10:00 AM</p>
              <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded">3 days</span>
            </div>

            <div class="border-l-4 border-red-500 pl-3 py-2">
              <p class="font-semibold text-sm">Portfolio Assignment Due</p>
              <p class="text-xs text-gray-600">Oct 28, 11:59 PM</p>
              <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded">6 days</span>
            </div>

            <div class="border-l-4 border-orange-500 pl-3 py-2">
              <p class="font-semibold text-sm">Midterm Exam - Software Eng</p>
              <p class="text-xs text-gray-600">Oct 29, 9:00 AM</p>
              <span class="text-xs bg-orange-100 text-orange-800 px-2 py-0.5 rounded">7 days</span>
            </div>

            <div class="border-l-4 border-purple-500 pl-3 py-2">
              <p class="font-semibold text-sm">Tech Talk: AI Development</p>
              <p class="text-xs text-gray-600">Oct 30, 3:00 PM</p>
              <span class="text-xs bg-purple-100 text-purple-800 px-2 py-0.5 rounded">8 days</span>
            </div>
          </div>
        </div>

        <!-- Weekly Class Schedule -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-xl font-bold mb-4">Regular Schedule</h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between py-2 border-b">
              <span class="font-semibold">Monday</span>
              <span class="text-gray-600">9AM - 5PM</span>
            </div>
            <div class="flex justify-between py-2 border-b">
              <span class="font-semibold">Wednesday</span>
              <span class="text-gray-600">9AM - 5PM</span>
            </div>
            <div class="flex justify-between py-2 border-b">
              <span class="font-semibold">Friday</span>
              <span class="text-gray-600">10AM - 4PM</span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</body>
</html>