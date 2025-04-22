document.addEventListener('DOMContentLoaded', function() {
    // Initialize Pusher/Echo
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: pusherCluster,
        encrypted: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        }
    });

    // DOM Elements
    const messagesContainer = document.getElementById('messagesContainer');
    const messageInput = document.getElementById('messageInput');
    const messageForm = document.getElementById('messageForm');
    const typingIndicator = document.getElementById('typingIndicator');
    const mediaUpload = document.getElementById('mediaUpload');
    let isTyping = false;
    let lastTypingTime;
    
    // Scroll to bottom initially
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    // Real-time Listeners
    window.Echo.private(`chat.${chatId}`)
        .listen('MessageSent', (e) => {
            appendMessage(e.message);
        })
        .listenForWhisper('typing', (e) => {
            if (e.userId != currentUserId) {
                typingIndicator.textContent = e.isTyping ? `${e.userName} is typing...` : '';
            }
        });
    
    // Message Form Submission
    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        sendMessage();
    });
    
    // Typing Indicators
    messageInput.addEventListener('input', function() {
        updateTypingStatus();
    });
    
    // Media Upload Preview
    mediaUpload.addEventListener('change', function() {
        if (this.files.length > 0) {
            sendMessage();
        }
    });
    
    // Group Admin Functionality
    if (isGroupAdmin) {
        const addParticipantBtn = document.getElementById('addParticipantBtn');
        const addParticipantModal = document.getElementById('addParticipantModal');
        const closeModal = document.querySelector('.close-modal');
        
        addParticipantBtn.addEventListener('click', function() {
            addParticipantModal.style.display = 'block';
        });
        
        closeModal.addEventListener('click', function() {
            addParticipantModal.style.display = 'none';
        });
        
        document.getElementById('addParticipantForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const userId = document.querySelector('#userSearchResults .selected')?.dataset.userId;
            
            if (userId) {
                fetch(`/chats/${chatId}/participants`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ user_id: userId })
                })
                .then(response => response.json())
                .then(data => {
                    addParticipantModal.style.display = 'none';
                    showToast('User added successfully');
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error adding user', 'error');
                });
            }
        });
    }
    
    // Functions
    function sendMessage() {
        const formData = new FormData(messageForm);
        const content = messageInput.value.trim();
        
        if (content === '' && !formData.has('media')) return;
        
        fetch(`/chats/${chatId}/messages`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            messageInput.value = '';
            mediaUpload.value = '';
            resetTyping();
        })
        .catch(error => console.error('Error:', error));
    }
    
    function appendMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${message.sender_id == currentUserId ? 'sent' : 'received'}`;
        
        let messageContent = '';
        if (message.is_system_message) {
            messageContent = formatSystemMessage(message);
        } else if (message.media_url) {
            messageContent = formatMediaMessage(message);
        } else {
            messageContent = `
                <div class="message-content">${message.content}</div>
                <div class="message-meta">
                    <span class="message-time">${formatTime(message.created_at)}</span>
                    ${message.sender_id == currentUserId ? messageStatusIcon(message) : ''}
                </div>
            `;
        }
        
        messageDiv.innerHTML = messageContent;
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    function formatSystemMessage(message) {
        const meta = message.system_message_metadata;
        let text = '';
        
        switch(message.system_message_type) {
            case 'user_added':
                text = `<strong>${meta.action_by_name}</strong> added <strong>${meta.user_name}</strong>`;
                break;
            case 'admin_changed':
                text = `<strong>${meta.admin_name}</strong> is now group admin`;
                break;
            default:
                text = 'System message';
        }
        
        return `<div class="system-message">${text}</div>`;
    }
    
    function formatMediaMessage(message) {
        let mediaElement = '';
        const mediaUrl = `/storage/${message.media_url}`;
        
        if (message.media_type.startsWith('image/')) {
            mediaElement = `<img src="${mediaUrl}" class="media-preview" alt="Image">`;
        } else if (message.media_type.startsWith('video/')) {
            mediaElement = `
                <video controls class="media-preview">
                    <source src="${mediaUrl}" type="${message.media_type}">
                </video>
            `;
        } else {
            mediaElement = `<a href="${mediaUrl}" target="_blank" class="file-download">Download file</a>`;
        }
        
        return `
            <div class="media-container">
                ${mediaElement}
                ${message.content ? `<div class="media-caption">${message.content}</div>` : ''}
                <div class="message-meta">
                    <span class="message-time">${formatTime(message.created_at)}</span>
                    ${message.sender_id == currentUserId ? messageStatusIcon(message) : ''}
                </div>
            </div>
        `;
    }
    
    function messageStatusIcon(message) {
        if (message.read_at) {
            return '<span class="read-receipt">✓✓</span>';
        } else if (message.delivered_at) {
            return '<span class="read-receipt">✓</span>';
        }
        return '';
    }
    
    function updateTypingStatus() {
        if (!isTyping) {
            isTyping = true;
            window.Echo.private(`chat.${chatId}`)
                .whisper('typing', {
                    userId: currentUserId,
                    userName: "{{ auth()->user()->name }}",
                    isTyping: true
                });
        }
        
        lastTypingTime = new Date().getTime();
        
        setTimeout(() => {
            const timeNow = new Date().getTime();
            const timeDiff = timeNow - lastTypingTime;
            
            if (timeDiff >= 2000 && isTyping) {
                resetTyping();
            }
        }, 2000);
    }
    
    function resetTyping() {
        isTyping = false;
        window.Echo.private(`chat.${chatId}`)
            .whisper('typing', {
                userId: currentUserId,
                userName: "{{ auth()->user()->name }}",
                isTyping: false
            });
    }
    
    function formatTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
});