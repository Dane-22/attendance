<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Assistant - JAJR Company</title>
    <link rel="stylesheet" href="../assets/css/ai_chat.css">
</head>
<body>
    <div id="ai-chat-widget" class="ai-chat-widget">
        <div class="chat-header">
            <span>JAJR AI Assistant</span>
            <button id="close-chat">&times;</button>
        </div>
        <div class="chat-messages" id="chat-messages">
            <div class="message ai-message">
                <div class="message-content">Hello! I'm your JAJR AI Assistant. How can I help you today?</div>
            </div>
        </div>
        <div class="typing-indicator" id="typing-indicator" style="display: none;">
            <div class="typing-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <span>AI is typing...</span>
        </div>
        <div class="chat-input">
            <input type="text" id="message-input" placeholder="Type your message...">
            <button id="send-button">Send</button>
        </div>
    </div>

    <script src="../assets/js/ai_chat.js"></script>
</body>
</html>