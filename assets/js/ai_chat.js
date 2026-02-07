// assets/js/ai_chat.js
document.addEventListener('DOMContentLoaded', function() {
    const chatWidget = document.getElementById('ai-chat-widget');
    const messageInput = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');
    const chatMessages = document.getElementById('chat-messages');
    const typingIndicator = document.getElementById('typing-indicator');
    const openChatBtn = document.getElementById('open-chat');
    const closeChatBtn = document.getElementById('close-chat');
    const clearChatBtn = document.getElementById('clear-chat');

    if (!chatWidget || !messageInput || !sendButton || !chatMessages || !typingIndicator) {
        return;
    }

    const handlerUrl = new URL('ai_handler.php', window.location.href).toString();

    function openChat() {
        chatWidget.style.display = 'flex';
        if (openChatBtn) openChatBtn.style.display = 'none';
        setTimeout(() => messageInput.focus(), 50);

        try {
            const key = `ai_help_shown_${getCurrentPageName() || 'default'}`;
            if (!sessionStorage.getItem(key)) {
                sessionStorage.setItem(key, '1');
                sendMessageWithText('How do I use this page?');
            }
        } catch (e) {
            // ignore
        }
    }

    function closeChat() {
        chatWidget.style.display = 'none';
        if (openChatBtn) openChatBtn.style.display = '';
    }

    function getCurrentPageName() {
        try {
            const path = window.location.pathname || '';
            const file = path.split('/').pop() || '';
            return file;
        } catch (e) {
            return '';
        }
    }

    async function sendMessage() {
        const message = messageInput.value.trim();
        if (!message) return;

        await sendMessageWithText(message);
    }

    async function sendMessageWithText(message) {
        const msg = String(message ?? '').trim();
        if (!msg) return;

        // 1. Ipakita ang message ng user sa UI
        addMessage(msg, 'user');
        messageInput.value = '';
        typingIndicator.style.display = 'flex';

        try {
            // 2. I-send ang data sa PHP handler
            const response = await fetch(handlerUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: msg, page: getCurrentPageName() })
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
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        contentDiv.textContent = String(content ?? '');
        div.appendChild(contentDiv);
        chatMessages.appendChild(div);
        
        // Auto-scroll pababa
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function clearChat() {
        chatMessages.textContent = '';
    }

    // Event Listeners
    sendButton.addEventListener('click', sendMessage);

    messageInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    openChatBtn?.addEventListener('click', openChat);
    closeChatBtn?.addEventListener('click', closeChat);
    clearChatBtn?.addEventListener('click', clearChat);

    if (chatWidget.style.display === 'none') {
        if (openChatBtn) openChatBtn.style.display = '';
    }
});