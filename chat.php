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
  <title>Chat | PLMUN LMS</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <style>
    .chat-container { height: calc(100vh - 250px); }
    .messages-area { height: calc(100% - 80px); overflow-y: auto; scroll-behavior: smooth; }
    .online-indicator { width: 10px; height: 10px; background: #10b981; border-radius: 50%; display: inline-block; animation: pulse 2s infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    .contact-item.active { background-color: #dbeafe; border-left: 4px solid #2563eb; }
    .tab-button { padding: 8px 12px; border-radius: 0.5rem; transition: all 0.2s; }
    .tab-button.active { background-color: #2563eb; color: white; }
    .badge { position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; font-size: 0.75rem; padding: 2px 6px; border-radius: 9999px; font-weight: bold; }
  </style>
</head>
<body class="bg-gray-100">
  
<?php include 'includes/header.php'; ?>

  <main class="p-6 max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-3xl font-bold">💬 Messages</h2>
      <button onclick="showAddFriendModal()" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 font-semibold shadow-lg">
        ➕ Add Friend
      </button>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 chat-container">
      
      <!-- Contacts Sidebar -->
      <div class="bg-white rounded-lg shadow overflow-hidden flex flex-col">
        <div class="bg-blue-600 text-white p-4">
          <h3 class="font-bold text-lg mb-3">Conversations</h3>
          
          <div class="relative mb-3">
            <input type="text" id="searchContact" placeholder="Search by email..." 
                   class="w-full p-2 pr-8 rounded text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
            <svg class="absolute right-2 top-2.5 w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
          </div>

          <div class="flex space-x-2">
            <button onclick="switchTab('friends')" id="friendsTab" class="tab-button flex-1 active">Friends</button>
            <button onclick="switchTab('requests')" id="requestsTab" class="tab-button flex-1 relative">
              Requests <span id="requestBadge" class="badge hidden">0</span>
            </button>
          </div>
        </div>
        
        <div id="friendsList" class="overflow-y-auto flex-1">
          <div class="p-4 text-center text-gray-500">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-2"></div>
            <p class="text-sm">Loading...</p>
          </div>
        </div>

        <div id="requestsList" class="overflow-y-auto flex-1 hidden">
          <div class="p-4 text-center text-gray-500">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-2"></div>
            <p class="text-sm">Loading...</p>
          </div>
        </div>
      </div>

      <!-- Chat Area -->
      <div class="md:col-span-2 bg-white rounded-lg shadow flex flex-col">
        <div id="chatHeader" class="bg-blue-600 text-white p-4 rounded-t-lg flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="bg-blue-800 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold">?</div>
            <div>
              <h3 class="font-bold">Select a conversation</h3>
              <p class="text-sm text-blue-200">Click a contact to start chatting</p>
            </div>
          </div>
        </div>

        <div id="messagesArea" class="messages-area p-4 space-y-4 bg-gray-50">
          <div class="text-center text-gray-500 mt-20">
            <div class="text-6xl mb-4">💬</div>
            <p class="text-lg font-semibold mb-2">Start a Conversation</p>
            <p class="text-sm">Add friends to start messaging!</p>
          </div>
        </div>

        <div class="p-4 border-t bg-white rounded-b-lg">
          <form id="messageForm" class="flex space-x-2">
            <input type="text" id="messageInput" placeholder="Add a friend first..." 
                   class="flex-1 p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" disabled>
            <button type="submit" id="sendButton" 
                    class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-semibold disabled:bg-gray-400 disabled:cursor-not-allowed" disabled>
              Send
            </button>
          </form>
        </div>
      </div>

    </div>
  </main>

  <!-- Add Friend Modal -->
  <div id="addFriendModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
      <h3 class="text-2xl font-bold mb-4">➕ Add Friend</h3>
      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Search by Email</label>
        <input type="email" id="friendSearchInput" placeholder="Enter institutional email..." 
               class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" oninput="searchUsers(this.value)">
      </div>
      <div id="searchResults" class="mb-4 max-h-60 overflow-y-auto">
        <p class="text-sm text-gray-500 text-center py-4">Type an email to search...</p>
      </div>
      <button onclick="hideAddFriendModal()" class="w-full bg-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-400 font-semibold">Close</button>
    </div>
  </div>

  <!-- Chat Menu -->
  <div id="chatMenu" class="hidden fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50" onclick="hideChatMenu()">
    <div class="bg-white rounded-lg shadow-lg w-64" onclick="event.stopPropagation()">
      <button onclick="deleteConversation()" class="w-full text-left px-4 py-3 hover:bg-gray-100 flex items-center space-x-2 text-red-600">
        <span>🗑️</span><span>Delete Conversation</span>
      </button>
      <button onclick="removeFriend()" class="w-full text-left px-4 py-3 hover:bg-gray-100 flex items-center space-x-2 text-red-600">
        <span>❌</span><span>Remove Friend</span>
      </button>
      <button onclick="hideChatMenu()" class="w-full text-left px-4 py-3 hover:bg-gray-100 flex items-center space-x-2">
        <span>✖️</span><span>Cancel</span>
      </button>
    </div>
  </div>

  <script>
    let currentChat = null, currentChatName = '', lastMessageId = 0, allFriends = [];
    let messageInterval = null;

    document.addEventListener('DOMContentLoaded', function() {
      loadFriends();
      loadFriendRequests();
      setInterval(loadFriends, 10000);
      setInterval(loadFriendRequests, 15000);
      document.getElementById('searchContact').addEventListener('input', e => filterContacts(e.target.value));
    });

    function switchTab(tab) {
      document.getElementById('friendsTab').classList.toggle('active', tab === 'friends');
      document.getElementById('requestsTab').classList.toggle('active', tab === 'requests');
      document.getElementById('friendsList').classList.toggle('hidden', tab !== 'friends');
      document.getElementById('requestsList').classList.toggle('hidden', tab !== 'requests');
    }

    function loadFriends() {
      fetch('api/get_friends.php')
        .then(r => r.json())
        .then(data => { if (data.friends) { allFriends = data.friends; displayFriends(allFriends); } });
    }

    function displayFriends(friends) {
      const list = document.getElementById('friendsList');
      if (friends.length === 0) {
        list.innerHTML = '<div class="p-4 text-center text-gray-500"><p class="text-sm">No friends yet</p><p class="text-xs mt-2">Click "Add Friend"!</p></div>';
        return;
      }
      list.innerHTML = friends.map(f => `
        <div class="p-4 border-b hover:bg-gray-50 cursor-pointer contact-item ${currentChat === f.id ? 'active' : ''}" 
             onclick="openChat(${f.id}, '${f.name}', '${f.initials}', '${f.email}')">
          <div class="flex items-center space-x-3">
            <div class="relative">
              <div class="bg-${f.color}-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold">${f.initials}</div>
              ${f.online ? '<span class="online-indicator absolute bottom-0 right-0"></span>' : ''}
            </div>
            <div class="flex-1 min-w-0">
              <h4 class="font-semibold truncate">${f.name}</h4>
              <p class="text-xs text-gray-500 truncate">${f.email}</p>
            </div>
            ${f.unread > 0 ? `<span class="bg-blue-600 text-white text-xs rounded-full px-2 py-1">${f.unread}</span>` : ''}
          </div>
        </div>
      `).join('');
    }

    function loadFriendRequests() {
      fetch('api/get_friend_requests.php')
        .then(r => r.json())
        .then(data => {
          if (data.requests) {
            displayRequests(data.requests);
            const badge = document.getElementById('requestBadge');
            if (data.requests.length > 0) { badge.textContent = data.requests.length; badge.classList.remove('hidden'); }
            else { badge.classList.add('hidden'); }
          }
        });
    }

    function displayRequests(requests) {
      const list = document.getElementById('requestsList');
      if (requests.length === 0) {
        list.innerHTML = '<div class="p-4 text-center text-gray-500"><p class="text-sm">No requests</p></div>';
        return;
      }
      list.innerHTML = requests.map(r => `
        <div class="p-4 border-b">
          <div class="flex items-center space-x-3 mb-3">
            <div class="bg-purple-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold">${r.initials}</div>
            <div class="flex-1"><h4 class="font-semibold">${r.name}</h4><p class="text-xs text-gray-500">${r.email}</p></div>
          </div>
          <div class="flex space-x-2">
            <button onclick="acceptRequest(${r.id})" class="flex-1 bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700">✓ Accept</button>
            <button onclick="rejectRequest(${r.id})" class="flex-1 bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700">✗ Decline</button>
          </div>
        </div>
      `).join('');
    }

    function filterContacts(term) {
      term = term.toLowerCase().trim();
      displayFriends(term === '' ? allFriends : allFriends.filter(f => f.email.toLowerCase().includes(term) || f.name.toLowerCase().includes(term)));
    }

    function showAddFriendModal() {
      document.getElementById('addFriendModal').classList.remove('hidden');
      document.getElementById('friendSearchInput').value = '';
      document.getElementById('searchResults').innerHTML = '<p class="text-sm text-gray-500 text-center py-4">Type an email...</p>';
    }

    function hideAddFriendModal() { document.getElementById('addFriendModal').classList.add('hidden'); }

    let searchTimeout;
    function searchUsers(email) {
      clearTimeout(searchTimeout);
      if (email.trim().length < 3) {
        document.getElementById('searchResults').innerHTML = '<p class="text-sm text-gray-500 text-center py-4">Type at least 3 characters...</p>';
        return;
      }
      searchTimeout = setTimeout(() => {
        fetch('api/search_users.php?email=' + encodeURIComponent(email))
          .then(r => r.json())
          .then(data => {
            const results = document.getElementById('searchResults');
            if (data.users && data.users.length > 0) {
              results.innerHTML = data.users.map(u => `
                <div class="p-3 border rounded mb-2 flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <div class="bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold text-sm">${u.initials}</div>
                    <div><p class="font-semibold text-sm">${u.name}</p><p class="text-xs text-gray-500">${u.email}</p></div>
                  </div>
                  ${u.status === 'friends' ? '<span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">✓ Friends</span>' :
                    u.status === 'pending' ? '<span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">⏳ Pending</span>' :
                    `<button onclick="sendFriendRequest(${u.id})" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">+ Add</button>`}
                </div>
              `).join('');
            } else {
              results.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">No users found</p>';
            }
          });
      }, 500);
    }

    function sendFriendRequest(userId) {
      fetch('api/send_friend_request.php', {
        method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({user_id: userId})
      }).then(r => r.json()).then(data => { if (data.success) { alert('Friend request sent!'); hideAddFriendModal(); } });
    }

    function acceptRequest(requestId) {
      fetch('api/accept_friend_request.php', {
        method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({request_id: requestId})
      }).then(r => r.json()).then(data => { if (data.success) { loadFriendRequests(); loadFriends(); } });
    }

    function rejectRequest(requestId) {
      if (confirm('Decline this request?')) {
        fetch('api/reject_friend_request.php', {
          method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({request_id: requestId})
        }).then(r => r.json()).then(() => loadFriendRequests());
      }
    }

    function showChatMenu() { document.getElementById('chatMenu').classList.remove('hidden'); }
    function hideChatMenu() { document.getElementById('chatMenu').classList.add('hidden'); }

    function deleteConversation() {
      if (!currentChat) return;
      if (confirm('Delete all messages? Cannot be undone.')) {
        fetch('api/delete_conversation.php', {
          method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({user_id: currentChat})
        }).then(r => r.json()).then(data => {
          if (data.success) { alert('Deleted'); hideChatMenu(); loadFriends(); currentChat = null; }
        });
      }
    }

    function removeFriend() {
      if (!currentChat) return;
      if (confirm('Remove ' + currentChatName + '?')) {
        fetch('api/remove_friend.php', {
          method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({user_id: currentChat})
        }).then(r => r.json()).then(data => { if (data.success) { alert('Removed'); hideChatMenu(); loadFriends(); currentChat = null; } });
      }
    }

    function openChat(userId, userName, userInitials, userEmail) {
      currentChat = userId; currentChatName = userName; lastMessageId = 0;
      document.getElementById('chatHeader').innerHTML = `
        <div class="flex items-center space-x-3">
          <div class="bg-blue-800 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold">${userInitials}</div>
          <div><h3 class="font-bold">${userName}</h3><p class="text-sm text-blue-200">${userEmail}</p></div>
        </div>
        <button onclick="showChatMenu()" class="hover:bg-blue-700 p-2 rounded">⋮</button>
      `;
      document.getElementById('messageInput').disabled = false;
      document.getElementById('messageInput').placeholder = `Message ${userName}...`;
      document.getElementById('sendButton').disabled = false;
      loadMessages();
      if (messageInterval) clearInterval(messageInterval);
      messageInterval = setInterval(loadMessages, 2000);
    }

    function loadMessages() {
      if (!currentChat) return;
      fetch(`api/get_messages.php?user_id=${currentChat}&last_id=${lastMessageId}`)
        .then(r => r.json())
        .then(data => {
          const area = document.getElementById('messagesArea');
          if (data.messages && data.messages.length > 0) {
            if (lastMessageId === 0) area.innerHTML = '';
            data.messages.forEach(m => {
              const div = document.createElement('div');
              div.className = `flex items-${m.sent_by_me ? 'end justify-end' : 'start'} space-x-2`;
              div.innerHTML = m.sent_by_me ? 
                `<div class="bg-blue-600 text-white rounded-lg p-3 shadow max-w-md break-words"><p>${m.message}</p><span class="text-xs text-blue-200 mt-1 block">${m.time}</span></div>` :
                `<div class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">${m.sender_initials}</div><div class="bg-white border rounded-lg p-3 shadow max-w-md break-words"><p class="text-gray-800">${m.message}</p><span class="text-xs text-gray-500 mt-1 block">${m.time}</span></div>`;
              area.appendChild(div);
              lastMessageId = Math.max(lastMessageId, m.id);
            });
            area.scrollTop = area.scrollHeight;
          } else if (lastMessageId === 0) {
            area.innerHTML = '<div class="text-center text-gray-500 mt-20"><div class="text-6xl mb-4">👋</div><p>No messages yet</p></div>';
          }
        });
    }

    document.getElementById('messageForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const input = document.getElementById('messageInput');
      const msg = input.value.trim();
      if (!msg || !currentChat) return;
      input.disabled = true;
      fetch('api/send_message.php', {
        method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({user_id: currentChat, message: msg})
      }).then(r => r.json()).then(data => {
        if (data.success) { input.value = ''; input.disabled = false; input.focus(); loadMessages(); loadFriends(); }
      });
    });
  </script>
</body>
</html>
