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
      scroll-behavior: smooth;
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
      padding: 8px 16px;
      font-style: italic;
      color: #6b7280;
      font-size: 0.875rem;
    }
    .typing-indicator.active {
      display: block;
    }
    .typing-dots span {
      display: inline-block;
      width: 6px;
      height: 6px;
      background: #6b7280;
      border-radius: 50%;
      margin: 0 2px;
      animation: bounce 1.4s infinite ease-in-out;
    }
    .typing-dots span:nth-child(1) { animation-delay: -0.32s; }
    .typing-dots span:nth-child(2) { animation-delay: -0.16s; }
    @keyframes bounce {
      0%, 80%, 100% { transform: scale(0); }
      40% { transform: scale(1); }
    }
    .contact-item.active {
      background-color: #dbeafe;
      border-left: 4px solid #2563eb;
    }
  </style>
</head>
<body class="bg-gray-100">
  
<?php include 'includes/header.php'; ?>

  <main class="p-6 max-w-7xl mx-auto">
    <h2 class="text-3xl font-bold mb-6">💬 Messages</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 chat-container">
      
      <!-- Contacts Sidebar -->
      <div class="bg-white rounded-lg shadow overflow-hidden flex flex-col">
        <div class="bg-blue-600 text-white p-4">
          <h3 class="font-bold text-lg mb-3">Conversations</h3>
          
          <!-- Search Box -->
          <div class="relative">
            <input 
              type="text" 
              id="searchContact" 
              placeholder="Search by email..." 
              class="w-full p-2 pr-8 rounded text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
            >
            <svg class="absolute right-2 top-2.5 w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
          </div>
        </div>
        
        <div id="contactsList" class="overflow-y-auto flex-1">
          <!-- Contacts will load here via AJAX -->
          <div class="p-4 text-center text-gray-500">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-2"></div>
            <p class="text-sm">Loading contacts...</p>
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
            <p class="text-lg font-semibold mb-2">Start a Conversation</p>
            <p class="text-sm">Select someone from your contacts to begin messaging</p>
          </div>
        </div>

        <!-- Typing Indicator -->
        <div id="typingIndicator" class="typing-indicator">
          <span id="typingUser">Someone</span> is typing
          <span class="typing-dots">
            <span></span>
            <span></span>
            <span></span>
          </span>
        </div>

        <!-- Message Input -->
        <div class="p-4 border-t bg-white rounded-b-lg">
          <form id="messageForm" class="flex space-x-2">
            <input 
              type="text" 
              id="messageInput"
              placeholder="Select a contact to start messaging..." 
              class="flex-1 p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              disabled
            >
            <button 
              type="submit"
              id="sendButton"
              class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-semibold disabled:bg-gray-400 disabled:cursor-not-allowed transition"
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
    let allContacts = []; // Store all contacts for search
    let messageInterval = null;
    let contactsInterval = null;

    // Load contacts on page load
    document.addEventListener('DOMContentLoaded', function() {
      loadContacts();
      // Refresh contacts every 10 seconds
      contactsInterval = setInterval(loadContacts, 10000);
      
      // Search functionality
      document.getElementById('searchContact').addEventListener('input', function(e) {
        filterContacts(e.target.value);
      });
    });

    // Load contacts list
    function loadContacts() {
      fetch('api/get_contacts.php')
        .then(response => response.json())
        .then(data => {
          if (data.contacts && data.contacts.length > 0) {
            allContacts = data.contacts;
            displayContacts(allContacts);
          } else {
            document.getElementById('contactsList').innerHTML = 
              '<div class="p-4 text-center text-gray-500"><p class="text-sm">No contacts found</p><p class="text-xs mt-2">Other users will appear here</p></div>';
          }
        })
        .catch(error => {
          console.error('Error loading contacts:', error);
          document.getElementById('contactsList').innerHTML = 
            '<div class="p-4 text-center text-red-500"><p class="text-sm">Failed to load contacts</p><p class="text-xs mt-1">Check your connection</p></div>';
        });
    }

    // Display contacts
    function displayContacts(contacts) {
      const contactsList = document.getElementById('contactsList');
      
      if (contacts.length === 0) {
        contactsList.innerHTML = 
          '<div class="p-4 text-center text-gray-500"><p class="text-sm">No matches found</p></div>';
        return;
      }

      contactsList.innerHTML = contacts.map(contact => `
        <div class="p-4 border-b hover:bg-gray-50 cursor-pointer contact-item transition ${currentChat === contact.id ? 'active' : ''}" 
             onclick="openChat(${contact.id}, '${escapeHtml(contact.name)}', '${contact.initials}', '${escapeHtml(contact.email)}')"
             data-email="${contact.email.toLowerCase()}">
          <div class="flex items-center space-x-3">
            <div class="relative">
              <div class="bg-${contact.color}-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold">
                ${contact.initials}
              </div>
              ${contact.online ? '<span class="online-indicator absolute bottom-0 right-0"></span>' : ''}
            </div>
            <div class="flex-1 min-w-0">
              <h4 class="font-semibold truncate">${escapeHtml(contact.name)}</h4>
              <p class="text-xs text-gray-500 truncate">${escapeHtml(contact.email)}</p>
              ${contact.lastMessage ? `<p class="text-sm text-gray-600 truncate mt-1">${escapeHtml(contact.lastMessage)}</p>` : '<p class="text-sm text-gray-400 italic">No messages yet</p>'}
            </div>
            <div class="flex flex-col items-end space-y-1">
              ${contact.unread > 0 ? `<span class="bg-blue-600 text-white text-xs rounded-full px-2 py-1">${contact.unread}</span>` : ''}
              ${contact.time ? `<span class="text-xs text-gray-500">${contact.time}</span>` : ''}
            </div>
          </div>
        </div>
      `).join('');
    }

    // Filter contacts by search
    function filterContacts(searchTerm) {
      searchTerm = searchTerm.toLowerCase().trim();
      
      if (searchTerm === '') {
        displayContacts(allContacts);
        return;
      }

      const filtered = allContacts.filter(contact => 
        contact.email.toLowerCase().includes(searchTerm) ||
        contact.name.toLowerCase().includes(searchTerm)
      );

      displayContacts(filtered);
    }

    // Open chat with a contact
    function openChat(userId, userName, userInitials, userEmail) {
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
            <p class="text-sm text-blue-200">${userEmail}</p>
          </div>
        </div>
      `;

      // Clear messages area and show loading
      document.getElementById('messagesArea').innerHTML = `
        <div class="text-center text-gray-500 mt-20">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p>Loading conversation...</p>
        </div>
      `;

      // Enable input
      const messageInput = document.getElementById('messageInput');
      const sendButton = document.getElementById('sendButton');
      messageInput.disabled = false;
      messageInput.placeholder = `Message ${userName}...`;
      sendButton.disabled = false;
      messageInput.focus();

      // Update active contact
      document.querySelectorAll('.contact-item').forEach(item => {
        item.classList.remove('active');
      });
      event.currentTarget.classList.add('active');

      // Load messages
      loadMessages();

      // Start polling for new messages every 2 seconds
      if (messageInterval) {
        clearInterval(messageInterval);
      }
      messageInterval = setInterval(loadMessages, 2000);
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
              messageDiv.className = `flex items-${msg.sent_by_me ? 'end justify-end' : 'start'} space-x-2 message-item`;
              
              if (msg.sent_by_me) {
                messageDiv.innerHTML = `
                  <div class="bg-blue-600 text-white rounded-lg p-3 shadow max-w-md break-words">
                    <p>${escapeHtml(msg.message)}</p>
                    <span class="text-xs text-blue-200 mt-1 block">${msg.time}</span>
                  </div>
                `;
              } else {
                messageDiv.innerHTML = `
                  <div class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold flex-shrink-0">
                    ${msg.sender_initials}
                  </div>
                  <div class="bg-white border rounded-lg p-3 shadow max-w-md break-words">
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
          } else if (lastMessageId === 0) {
            // First load with no messages
            messagesArea.innerHTML = `
              <div class="text-center text-gray-500 mt-20">
                <div class="text-6xl mb-4">👋</div>
                <p class="text-lg font-semibold mb-2">No messages yet</p>
                <p class="text-sm">Send a message to start the conversation</p>
              </div>
            `;
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

      // Disable input while sending
      messageInput.disabled = true;
      document.getElementById('sendButton').disabled = true;

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
          messageInput.disabled = false;
          document.getElementById('sendButton').disabled = false;
          messageInput.focus();
          loadMessages(); // Reload messages immediately
          loadContacts(); // Update contact list
        } else {
          alert('Failed to send message. Please try again.');
          messageInput.disabled = false;
          document.getElementById('sendButton').disabled = false;
        }
      })
      .catch(error => {
        console.error('Error sending message:', error);
        alert('Error sending message. Please check your connection.');
        messageInput.disabled = false;
        document.getElementById('sendButton').disabled = false;
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
      if (messageInterval) {
        clearInterval(messageInterval);
      }
      if (contactsInterval) {
        clearInterval(contactsInterval);
      }
    });

    // Send message on Enter key (Shift+Enter for new line)
    document.getElementById('messageInput').addEventListener('keypress', function(e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        document.getElementById('messageForm').dispatchEvent(new Event('submit'));
      }
    });
  </script>
</body>
</html>
