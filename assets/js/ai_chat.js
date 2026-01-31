// assets/js/ai_chat.js
document.addEventListener('DOMContentLoaded', function() {
    const chatWidget = document.getElementById('ai-chat-widget');
    const messageInput = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');
    const chatMessages = document.getElementById('chat-messages');
    const typingIndicator = document.getElementById('typing-indicator');

    async function sendMessage() {
        const message = messageInput.value.trim();
        if (!message) return;

        // 1. Ipakita ang message ng user sa UI
        addMessage(message, 'user');
        messageInput.value = '';
        typingIndicator.style.display = 'flex';

        try {
            // 2. I-send ang data sa PHP handler
            const response = await fetch('../employee/ai_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message })
            });

            // 3. I-parse ang JSON response
            const data = await response.json();
            typingIndicator.style.display = 'none';

            if (data.error) {
                // Ipakita ang debug info kung meron
                const errorMsg = data.debug ? `${data.error}: ${data.debug}` : data.error;
                addMessage('System Error: ' + errorMsg, 'ai');
            } else {
                addMessage(data.response, 'ai');
            }
        } catch (error) {
            typingIndicator.style.display = 'none';
            addMessage('Error: Cannot reach the server. Check your connection.', 'ai');
            console.error('Fetch Error:', error);
        }
    }

    function addMessage(content, sender) {
        const div = document.createElement('div');
        div.className = `message ${sender}-message`;
        div.innerHTML = `<div class="message-content">${content}</div>`;
        chatMessages.appendChild(div);
        
        // Auto-scroll pababa
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Event Listeners
    sendButton.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });
});