<?php
// include/ai_chat_widget.php
?>
<!-- AI Chat Widget -->
<div id="ai-chat-widget" class="ai-chat-widget" style="display: none;">
    <div class="chat-header">
        <span>
            JAJR AI Assistant
        </span>
        <div class="header-controls">
            <button id="clear-chat" title="Clear conversation">🗑️</button>
            <button id="close-chat" title="Close chat">×</button>
        </div>
    </div>
    
    <div class="chat-messages" id="chat-messages">
        <!-- Messages will be inserted here by JavaScript -->
    </div>
    
    <div class="typing-indicator" id="typing-indicator" style="display: none;">
        <div class="typing-dots">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <span>JAJR AI is thinking...</span>
    </div>
    
    <div class="chat-input">
        <textarea 
            id="message-input" 
            placeholder="Type your message here... (Press Enter to send, Shift+Enter for new line)" 
            rows="1"
            maxlength="500"
        ></textarea>
        <button id="send-button">Send</button>
    </div>
</div>

<!-- Floating Chat Toggle Button -->
<button id="open-chat" title="Open AI Assistant">
    🤖
</button>

<!-- Include Styles and Scripts -->
<link rel="stylesheet" href="../assets/css/ai_chat.css">
<script src="../assets/js/ai_chat.js"></script>