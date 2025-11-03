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
    .chat-container {
      height: calc(100vh - 250px);
    }
    .messages-area {
      height: calc(100% - 80px);
      overflow-y: auto;
    }
    .online-indicator {
      width: 10px;
      height: 10px;
      background: #10b981;
      border-radius: 50%;
      display: inline-block;
      animation: pulse 2s infinite;
    }
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }
    .typing-indicator {
      display: none;
    }
    .typing-indicator.active {
      display: block;
    }
  </style>
</head>
<body class="bg-gray-100">
  
<?php include 'includes/header.php'; ?>

  <main class="p-6 max-w-7xl mx-auto">
    <h2 class="text-3xl font-bold mb-6">💬 Messages</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 chat-container">
      
      <!-- Contacts Sidebar -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-blue-600 text-white p-4">
          <h3 class="font-bold text-lg">Conversations</h3>
        </div>
        
        <div id="contactsList" class="overflow-y-auto" style="height: calc(100% - 60px);">
          <!-- Contacts will load here via AJAX -->
          <div class="p-4 text-center text-gray-500">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-2">Loading...</p>
          </div>
        </div>
      </div>

      <!-- Chat Area -->
      <div class="md:col-span-2 bg-white rounded-lg shadow flex flex-col">
        <!-- Chat Header -->
        <div id="chatHeader" class="bg-blue-600 text-white p-4 rounded-t-lg flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="bg-blue-800 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold">
              ?
            </div>
            <div>
              <h3 class="font-bold">Select a conversation</h3>
              <p class="text-sm text-blue-200">Click a contact to start chatting</p>
            </div>
          </div>
        </div>

        <!-- Messages Area -->
        <div id="messagesArea" class="messages-area p-4 space-y-4 bg-gray-50">
          <div class="text-center text-gray-500 mt-20">
            <div class="text-6xl mb-4">💬</div>
            <p>Select a conversation to start messaging</p>
          </div>
        </div>

        <!-- Typing Indicator -->
        <div id="typingIndicator" class="typing-indicator px-4 py-2 text-sm text-gray-600 italic">
          <span id="typingUser">Someone</span> is typing...
        </div>

        <!-- Message Input -->
        <div class="p-4 border-t bg-white rounded-b-lg">
          <form id="messageForm" class="flex space-x-2">
            <input 
              type="text" 
              id="messageInput"
              placeholder="Type your message..." 
              class="flex-1 p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              disabled
            >
            <button 
              type="submit"
              id="sendButton"
              class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-semibold"
              disabled
            >
              Send
            </button>
          </form>
        </div>
      </div>

    </div>
  </main>

  <script>
    let currentChat = null;
    let lastMessageId = 0;
    let typingTimeout = null;

    // Load contacts on page load
    document.addEventListener('DOMContentLoaded', function() {
      loadContacts();
      // Refresh contacts every 5 seconds
      setInterval(loadContacts, 5000);
    });

    // Load contacts list
    function loadContacts() {
      fetch('api/get_contacts.php')
        .then(response => response.json())
        .then(data => {
          const contactsList = document.getElementById('contactsList');
          
          if (data.contacts && data.contacts.length > 0) {
            contactsList.innerHTML = data.contacts.map(contact => `
              <div class="p-4 border-b hover:bg-gray-50 cursor-pointer contact-item ${currentChat === contact.id ? 'bg-blue-50 border-l-4 border-blue-600' : ''}" 
                   onclick="openChat(${contact.id}, '${contact.name}', '${contact.initials}')">
                <div class="flex items-center space-x-3">
                  <div class="relative">
                    <div class="bg-${contact.color}-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold">
                      ${contact.initials}
                    </div>
                    ${contact.online ? '<span class="online-indicator absolute bottom-0 right-0"></span>' : ''}
                  </div>
                  <div class="flex-1">
                    <h4 class="font-semibold">${contact.name}</h4>
                    <p class="text-sm text-gray-600 truncate">${contact.lastMessage || 'No messages yet'}</p>
                  </div>
                  ${contact.unread > 0 ? `<span class="bg-blue-600 text-white text-xs rounded-full px-2 py-1">${contact.unread}</span>` : ''}
                  <span class="text-xs text-gray-500">${contact.time || ''}</span>
                </div>
              </div>
            `).join('');
          } else {
            contactsList.innerHTML = '<div class="p-4 text-center text-gray-500">No contacts found</div>';
          }
        })
        .catch(error => {
          console.error('Error loading contacts:', error);
        });
    }

    // Open chat with a contact
    function openChat(userId, userName, userInitials) {
      currentChat = userId;
      lastMessageId = 0;
      
      // Update chat header
      document.getElementById('chatHeader').innerHTML = `
        <div class="flex items-center space-x-3">
          <div class="bg-blue-800 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold">
            ${userInitials}
          </div>
          <div>
            <h3 class="font-bold">${userName}</h3>
            <p class="text-sm text-blue-200">
              <span class="online-indicator"></span> Online
            </p>
          </div>
        </div>
      `;

      // Enable input
      document.getElementById('messageInput').disabled = false;
      document.getElementById('sendButton').disabled = false;
      document.getElementById('messageInput').focus();

      // Load messages
      loadMessages();

      // Start polling for new messages every 2 seconds
      if (window.messageInterval) {
        clearInterval(window.messageInterval);
      }
      window.messageInterval = setInterval(loadMessages, 2000);
    }

    // Load messages for current chat
    function loadMessages() {
      if (!currentChat) return;

      fetch(`api/get_messages.php?user_id=${currentChat}&last_id=${lastMessageId}`)
        .then(response => response.json())
        .then(data => {
          const messagesArea = document.getElementById('messagesArea');
          
          if (data.messages && data.messages.length > 0) {
            // If first load, replace all
            if (lastMessageId === 0) {
              messagesArea.innerHTML = '';
            }

            // Append new messages
            data.messages.forEach(msg => {
              const messageDiv = document.createElement('div');
              messageDiv.className = `flex items-${msg.sent_by_me ? 'end justify-end' : 'start'} space-x-2`;
              
              if (msg.sent_by_me) {
                messageDiv.innerHTML = `
                  <div class="bg-blue-600 text-white rounded-lg p-3 shadow max-w-md">
                    <p>${escapeHtml(msg.message)}</p>
                    <span class="text-xs text-blue-200 mt-1 block">${msg.time}</span>
                  </div>
                `;
              } else {
                messageDiv.innerHTML = `
                  <div class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold flex-shrink-0">
                    ${msg.sender_initials}
                  </div>
                  <div class="bg-white rounded-lg p-3 shadow max-w-md">
                    <p class="text-gray-800">${escapeHtml(msg.message)}</p>
                    <span class="text-xs text-gray-500 mt-1 block">${msg.time}</span>
                  </div>
                `;
              }
              
              messagesArea.appendChild(messageDiv);
              lastMessageId = Math.max(lastMessageId, msg.id);
            });

            // Scroll to bottom
            messagesArea.scrollTop = messagesArea.scrollHeight;
          }
        })
        .catch(error => {
          console.error('Error loading messages:', error);
        });
    }

    // Send message
    document.getElementById('messageForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const messageInput = document.getElementById('messageInput');
      const message = messageInput.value.trim();
      
      if (!message || !currentChat) return;

      // Send via AJAX
      fetch('api/send_message.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          user_id: currentChat,
          message: message
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          messageInput.value = '';
          loadMessages(); // Reload messages
        }
      })
      .catch(error => {
        console.error('Error sending message:', error);
      });
    });

    // Typing indicator
    document.getElementById('messageInput').addEventListener('input', function() {
      if (!currentChat) return;

      // Send typing status
      fetch('api/typing_status.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          user_id: currentChat,
          typing: true
        })
      });

      // Clear previous timeout
      clearTimeout(typingTimeout);
      
      // Stop typing after 2 seconds
      typingTimeout = setTimeout(() => {
        fetch('api/typing_status.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            user_id: currentChat,
            typing: false
          })
        });
      }, 2000);
    });

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
      if (window.messageInterval) {
        clearInterval(window.messageInterval);
      }
    });
  </script>
</body>
</html>