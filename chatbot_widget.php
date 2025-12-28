<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    // Don't show chatbot for non-students
    return;
}
?>
<!-- Chatbot Widget for Students -->
<div id="chatbot-widget">
    <style>
        #chatbot-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 350px;
            max-height: 500px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 1000;
            display: none;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }
        
        #chatbot-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        #chatbot-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        #chatbot-close {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 20px;
            padding: 0;
        }
        
        #chatbot-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            max-height: 350px;
            background: #f9fafb;
        }
        
        .message {
            margin-bottom: 15px;
            max-width: 80%;
        }
        
        .user-message {
            margin-left: auto;
            background: #3b82f6;
            color: white;
            padding: 10px 15px;
            border-radius: 15px 15px 5px 15px;
        }
        
        .bot-message {
            background: white;
            padding: 10px 15px;
            border-radius: 15px 15px 15px 5px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            white-space: pre-line;
        }
        
        #chatbot-input-area {
            padding: 15px;
            border-top: 1px solid #e5e7eb;
            background: white;
        }
        
        #chatbot-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #d1d5db;
            border-radius: 25px;
            outline: none;
            font-size: 14px;
        }
        
        #chatbot-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        #chatbot-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border-radius: 50%;
            color: white;
            border: none;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            z-index: 999;
        }
        
        .typing-indicator {
            display: inline-flex;
            align-items: center;
            padding: 10px 15px;
            background: #f3f4f6;
            border-radius: 15px 15px 15px 5px;
        }
        
        .typing-dot {
            width: 6px;
            height: 6px;
            background: #9ca3af;
            border-radius: 50%;
            margin: 0 2px;
            animation: typing 1.4s infinite ease-in-out;
        }
        
        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }
        
        @keyframes typing {
            0%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-5px); }
        }
        
        .quick-replies {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        
        .quick-reply {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .quick-reply:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        .chat-timestamp {
            font-size: 11px;
            color: #9ca3af;
            margin-top: 4px;
        }
        
        .user-timestamp { text-align: right; }
        .bot-timestamp { text-align: left; }
        
        .student-specific {
            background: #e0f2fe;
            border-left: 3px solid #0ea5e9;
            padding: 8px;
            margin-top: 5px;
            border-radius: 0 5px 5px 0;
            font-size: 12px;
        }
    </style>
    
    <!-- Chatbot Toggle Button -->
    <button id="chatbot-toggle">
        <i class="fas fa-graduation-cap"></i>
    </button>
    
    <!-- Chatbot Container -->
    <div id="chatbot-container">
        <div id="chatbot-header">
            <h3><i class="fas fa-robot mr-2"></i>Student Assistant</h3>
            <button id="chatbot-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div id="chatbot-messages">
            <div class="message bot-message">
                Hello, <?php echo htmlspecialchars($_SESSION['user']); ?>! 👋 
                I'm your PLMUN LMS Student Assistant.
                
                <div class="student-specific">
                    <strong>Student Features:</strong><br>
                    • Assignment help<br>
                    • Grade inquiries<br>
                    • Schedule info<br>
                    • E-book access
                </div>
                
                <div class="quick-replies" id="initial-quick-replies">
                    <div class="quick-reply" data-message="How do I submit assignments?">Submit Assignment</div>
                    <div class="quick-reply" data-message="Where are my grades?">Check Grades</div>
                    <div class="quick-reply" data-message="Show my schedule">View Schedule</div>
                    <div class="quick-reply" data-message="Access e-books">E-Books Help</div>
                </div>
            </div>
        </div>
        
        <div id="chatbot-input-area">
            <input type="text" 
                   id="chatbot-input" 
                   placeholder="Ask about assignments, grades, schedule..." 
                   autocomplete="off">
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('chatbot-toggle');
        const container = document.getElementById('chatbot-container');
        const closeBtn = document.getElementById('chatbot-close');
        const input = document.getElementById('chatbot-input');
        const messagesContainer = document.getElementById('chatbot-messages');
        
        let sessionId = 'student_chat_' + Date.now();
        let isLoading = false;
        
        // Toggle chatbot
        toggleBtn.addEventListener('click', () => {
            container.style.display = container.style.display === 'flex' ? 'none' : 'flex';
            if (container.style.display === 'flex') {
                loadChatHistory();
                input.focus();
            }
        });
        
        // Close chatbot
        closeBtn.addEventListener('click', () => {
            container.style.display = 'none';
        });
        
        // Quick replies
        document.querySelectorAll('.quick-reply').forEach(reply => {
            reply.addEventListener('click', () => {
                const message = reply.getAttribute('data-message');
                sendMessage(message);
            });
        });
        
        // Send message on Enter
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && input.value.trim() && !isLoading) {
                sendMessage(input.value.trim());
                input.value = '';
            }
        });
        
        // Function to send message
        async function sendMessage(message) {
            if (!message || isLoading) return;
            
            // Add user message to UI
            addMessage(message, 'user');
            
            // Show typing indicator
            showTypingIndicator();
            isLoading = true;
            
            try {
                const formData = new FormData();
                formData.append('action', 'chat');
                formData.append('message', message);
                formData.append('session_id', sessionId);
                
                const response = await fetch('chatbot_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                // Remove typing indicator
                removeTypingIndicator();
                
                if (data.error) {
                    addMessage('Sorry, there was an error. Please try again.', 'bot');
                } else {
                    addMessage(data.response, 'bot');
                    sessionId = data.session_id;
                }
            } catch (error) {
                removeTypingIndicator();
                addMessage('Network error. Please check your connection.', 'bot');
            }
            
            isLoading = false;
        }
        
        // Function to add message to UI
        function addMessage(text, sender) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${sender}-message`;
            
            const timestamp = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            messageDiv.innerHTML = `
                ${text}
                <div class="chat-timestamp ${sender}-timestamp">${timestamp}</div>
            `;
            
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Function to show typing indicator
        function showTypingIndicator() {
            const typingDiv = document.createElement('div');
            typingDiv.className = 'message bot-message typing-indicator';
            typingDiv.id = 'typing-indicator';
            typingDiv.innerHTML = `
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            `;
            messagesContainer.appendChild(typingDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Function to remove typing indicator
        function removeTypingIndicator() {
            const indicator = document.getElementById('typing-indicator');
            if (indicator) {
                indicator.remove();
            }
        }
        
        // Function to load chat history
        async function loadChatHistory() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_history');
                formData.append('session_id', sessionId);
                
                const response = await fetch('chatbot_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.history && data.history.length > 0) {
                    // Clear initial message
                    messagesContainer.innerHTML = '';
                    
                    // Add all messages
                    data.history.forEach(msg => {
                        const sender = msg.is_bot ? 'bot' : 'user';
                        const timestamp = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        
                        const messageDiv = document.createElement('div');
                        messageDiv.className = `message ${sender}-message`;
                        messageDiv.innerHTML = `
                            ${msg.message}
                            <div class="chat-timestamp ${sender}-timestamp">${timestamp}</div>
                        `;
                        
                        messagesContainer.appendChild(messageDiv);
                    });
                    
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            } catch (error) {
                console.error('Error loading chat history:', error);
            }
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === '/') {
                e.preventDefault();
                container.style.display = container.style.display === 'flex' ? 'none' : 'flex';
                if (container.style.display === 'flex') {
                    input.focus();
                }
            }
            
            if (e.key === 'Escape' && container.style.display === 'flex') {
                container.style.display = 'none';
            }
        });
    });
    </script>
</div>
