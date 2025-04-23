/**
 * EDMS-App Chat JavaScript file
 * Handles all chat functionality
 */

let currentUser = null;
let currentChat = null;
let chats = [];
let messages = [];
let users = [];
let messagePollingInterval = null;
let typingTimeout = null;
let lastTypingStatus = false;

// Initialize chat page
function initChat() {
    if (!checkAuth()) return;
    
    currentUser = JSON.parse(localStorage.getItem('edms_user'));
    
    // Update user info in the sidebar
    if (currentUser) {
        $('.user-name').text(currentUser.username);
        if (currentUser.status) {
            $('.user-status').text(currentUser.status);
        }
        if (currentUser.profile_pic) {
            $('.user-profile-pic').attr('src', currentUser.profile_pic);
        } else {
            // Create initials avatar
            const initial = currentUser.username.charAt(0).toUpperCase();
            $('.user-profile-pic').replaceWith(`
                <div class="profile-pic bg-primary d-flex align-items-center justify-content-center text-white">
                    <span style="font-size: 20px;">${initial}</span>
                </div>
            `);
        }
    }
    
    // Load chats
    loadChats();
    
    // Set up polling for new messages
    startMessagePolling();
    
    // Set up event listeners
    $('#message-form').on('submit', sendMessage);
    $('#new-chat-form').on('submit', createNewChat);
    $('#new-group-form').on('submit', createNewGroup);
    $('#message-input').on('input', handleTyping);
    $('#attachment-input').on('change', handleAttachment);
    
    // Mobile navigation
    $('.back-to-chats').on('click', function() {
        $('.chat-sidebar').addClass('show');
        $('.chat-content').addClass('d-none d-md-flex');
    });
    
    // Show/hide new chat modal
    $('#new-chat-btn').on('click', function() {
        loadUsersForNewChat();
    });
}

// Load all chats for the current user
function loadChats() {
    $.ajax({
        url: 'api/chats.php',
        type: 'GET',
        data: { action: 'list' },
        dataType: 'json',
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('edms_token') },
        success: function(response) {
            if (response.success) {
                chats = response.chats;
                renderChatList();
                
                // If URL has chat parameter, load that chat
                const urlParams = new URLSearchParams(window.location.search);
                const chatId = urlParams.get('chat');
                if (chatId) {
                    const chat = chats.find(c => c.chat_id === chatId);
                    if (chat) {
                        openChat(chat);
                    }
                } else if (chats.length > 0) {
                    // Otherwise load the first chat
                    openChat(chats[0]);
                }
            } else {
                showAlert(response.message || 'Failed to load chats', 'danger');
            }
        },
        error: function() {
            showAlert('Server error. Please try again later.', 'danger');
        }
    });
}

// Render the list of chats in the sidebar
function renderChatList() {
    const chatListContainer = $('.chat-list');
    chatListContainer.empty();
    
    if (chats.length === 0) {
        chatListContainer.html('<p class="text-center text-muted p-3">No conversations yet.</p>');
        return;
    }
    
    // Sort chats by most recent message
    chats.sort((a, b) => {
        const aLastMsg = a.last_message ? new Date(a.last_message.timestamp) : new Date(0);
        const bLastMsg = b.last_message ? new Date(b.last_message.timestamp) : new Date(0);
        return bLastMsg - aLastMsg;
    });
    
    chats.forEach(chat => {
        // Determine chat name and image
        let chatName, chatImage;
        
        if (chat.is_group) {
            chatName = chat.group_name;
            chatImage = `<div class="small-profile-pic bg-primary d-flex align-items-center justify-content-center text-white">
                <span style="font-size: 16px;">G</span>
            </div>`;
        } else {
            // Find the other participant
            const otherParticipant = chat.participants.find(p => p.user_id !== currentUser.user_id);
            if (otherParticipant) {
                chatName = otherParticipant.username;
                if (otherParticipant.profile_pic) {
                    chatImage = `<img src="${otherParticipant.profile_pic}" alt="${otherParticipant.username}" class="small-profile-pic">`;
                } else {
                    const initial = otherParticipant.username.charAt(0).toUpperCase();
                    chatImage = `<div class="small-profile-pic bg-secondary d-flex align-items-center justify-content-center text-white">
                        <span style="font-size: 16px;">${initial}</span>
                    </div>`;
                }
            } else {
                chatName = 'Unknown User';
                chatImage = `<div class="small-profile-pic bg-secondary d-flex align-items-center justify-content-center text-white">
                    <span style="font-size: 16px;">?</span>
                </div>`;
            }
        }
        
        // Determine last message preview
        let lastMessagePreview = 'No messages yet';
        let lastMessageTime = '';
        let unreadCount = chat.unread_count || 0;
        
        if (chat.last_message) {
            if (chat.last_message.media_url) {
                lastMessagePreview = '📷 Image';
            } else {
                lastMessagePreview = chat.last_message.content;
                if (lastMessagePreview.length > 30) {
                    lastMessagePreview = lastMessagePreview.substring(0, 27) + '...';
                }
            }
            
            // Format timestamp
            const msgDate = new Date(chat.last_message.timestamp);
            const today = new Date();
            if (msgDate.toDateString() === today.toDateString()) {
                lastMessageTime = msgDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            } else {
                lastMessageTime = msgDate.toLocaleDateString([], { month: 'short', day: 'numeric' });
            }
        }
        
        const isActive = currentChat && currentChat.chat_id === chat.chat_id;
        
        const chatItem = $(`
            <div class="chat-list-item p-3 ${isActive ? 'active' : ''}" data-chat-id="${chat.chat_id}">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        ${chatImage}
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-truncate">${chatName}</h6>
                            <small class="text-muted ms-2">${lastMessageTime}</small>
                        </div>
                        <p class="mb-0 text-muted text-truncate small">${lastMessagePreview}</p>
                    </div>
                    ${unreadCount > 0 ? `<div class="ms-2 unread-badge">${unreadCount}</div>` : ''}
                </div>
            </div>
        `);
        
        chatItem.on('click', function() {
            openChat(chat);
        });
        
        chatListContainer.append(chatItem);
    });
}

// Open a chat and load its messages
function openChat(chat) {
    currentChat = chat;
    
    // Update UI to show selected chat
    $('.chat-list-item').removeClass('active');
    $(`.chat-list-item[data-chat-id="${chat.chat_id}"]`).addClass('active');
    
    // On mobile, hide sidebar and show chat
    $('.chat-sidebar').removeClass('show');
    $('.chat-content').removeClass('d-none');
    
    // Update chat header
    let chatName, chatStatus, chatImage;
    
    if (chat.is_group) {
        chatName = chat.group_name;
        chatStatus = `${chat.participants.length} members`;
        chatImage = `<div class="profile-pic bg-primary d-flex align-items-center justify-content-center text-white">
            <span style="font-size: 20px;">G</span>
        </div>`;
    } else {
        // Find the other participant
        const otherParticipant = chat.participants.find(p => p.user_id !== currentUser.user_id);
        if (otherParticipant) {
            chatName = otherParticipant.username;
            chatStatus = otherParticipant.status || 'Online';
            if (otherParticipant.profile_pic) {
                chatImage = `<img src="${otherParticipant.profile_pic}" alt="${otherParticipant.username}" class="profile-pic">`;
            } else {
                const initial = otherParticipant.username.charAt(0).toUpperCase();
                chatImage = `<div class="profile-pic bg-secondary d-flex align-items-center justify-content-center text-white">
                    <span style="font-size: 20px;">${initial}</span>
                </div>`;
            }
        } else {
            chatName = 'Unknown User';
            chatStatus = '';
            chatImage = `<div class="profile-pic bg-secondary d-flex align-items-center justify-content-center text-white">
                <span style="font-size: 20px;">?</span>
            </div>`;
        }
    }
    
    $('.chat-header .chat-name').text(chatName);
    $('.chat-header .chat-status').text(chatStatus);
    $('.chat-header .chat-pic').html(chatImage);
    
    // Empty message container
    $('.messages-container').empty();
    
    // Load messages
    loadMessages(chat.chat_id);
    
    // Mark chat as read
    markChatAsRead(chat.chat_id);
    
    // Update URL
    const url = new URL(window.location.href);
    url.searchParams.set('chat', chat.chat_id);
    window.history.pushState({}, '', url);
}

// Load messages for a chat
function loadMessages(chatId) {
    $.ajax({
        url: 'api/messages.php',
        type: 'GET',
        data: { 
            action: 'list',
            chat_id: chatId
        },
        dataType: 'json',
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('edms_token') },
        success: function(response) {
            if (response.success) {
                messages = response.messages;
                renderMessages();
                scrollToBottom();
            } else {
                showAlert(response.message || 'Failed to load messages', 'danger');
            }
        },
        error: function() {
            showAlert('Server error. Please try again later.', 'danger');
        }
    });
}

// Render messages in the chat window
function renderMessages() {
    const messagesContainer = $('.messages-container');
    
    if (messages.length === 0) {
        messagesContainer.html('<div class="text-center text-muted my-5">No messages yet. Say hello!</div>');
        return;
    }
    
    // Group messages by date
    const groupedMessages = {};
    messages.forEach(message => {
        const msgDate = new Date(message.timestamp);
        const dateKey = msgDate.toDateString();
        if (!groupedMessages[dateKey]) {
            groupedMessages[dateKey] = [];
        }
        groupedMessages[dateKey].push(message);
    });
    
    // Clear container
    messagesContainer.empty();
    
    // Add message groups
    Object.keys(groupedMessages).forEach(dateKey => {
        const dateMessages = groupedMessages[dateKey];
        
        // Add date separator
        const today = new Date().toDateString();
        const yesterday = new Date(Date.now() - 86400000).toDateString();
        let dateLabel;
        
        if (dateKey === today) {
            dateLabel = 'Today';
        } else if (dateKey === yesterday) {
            dateLabel = 'Yesterday';
        } else {
            dateLabel = new Date(dateKey).toLocaleDateString();
        }
        
        messagesContainer.append(`
            <div class="date-separator text-center my-3">
                <span class="badge bg-secondary">${dateLabel}</span>
            </div>
        `);
        
        // Add messages
        dateMessages.forEach(message => {
            const isOutgoing = message.sender_id === currentUser.user_id;
            const messageClass = isOutgoing ? 'outgoing' : 'incoming';
            
            // Format timestamp
            const msgTime = new Date(message.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            // Message status icon
            let statusIcon = '';
            if (isOutgoing) {
                if (message.status === 'read') {
                    statusIcon = '<i class="bi bi-check-all text-primary"></i>';
                } else if (message.status === 'delivered') {
                    statusIcon = '<i class="bi bi-check-all"></i>';
                } else {
                    statusIcon = '<i class="bi bi-check"></i>';
                }
            }
            
            // Message content
            let messageContent = '';
            if (message.media_url) {
                messageContent = `
                    <div class="message-content">
                        <div class="message-text">${message.content || ''}</div>
                        <img src="${message.media_url}" class="message-image img-fluid mt-2" alt="Shared image">
                    </div>
                `;
            } else {
                messageContent = `
                    <div class="message-content">
                        <div class="message-text">${message.content}</div>
                    </div>
                `;
            }
            
            // Add sender name for group chats
            let senderName = '';
            if (currentChat.is_group && !isOutgoing) {
                const sender = currentChat.participants.find(p => p.user_id === message.sender_id);
                if (sender) {
                    senderName = `<div class="message-sender small text-primary mb-1">${sender.username}</div>`;
                }
            }
            
            const messageElement = $(`
                <div class="message ${messageClass}" data-message-id="${message.message_id}">
                    ${senderName}
                    ${messageContent}
                    <div class="message-time">
                        ${msgTime} ${statusIcon}
                    </div>
                </div>
            `);
            
            messagesContainer.append(messageElement);
        });
    });
}

// Send a new message
function sendMessage(event) {
    event.preventDefault();
    
    if (!currentChat) {
        showAlert('Please select a chat first', 'warning');
        return;
    }
    
    const messageInput = $('#message-input');
    const content = messageInput.val().trim();
    const attachmentInput = $('#attachment-input')[0];
    
    if (!content && !attachmentInput.files.length) {
        return; // Don't send empty messages
    }
    
    // Clear input
    messageInput.val('');
    
    // Create formData for possible file upload
    const formData = new FormData();
    formData.append('action', 'send');
    formData.append('chat_id', currentChat.chat_id);
    formData.append('content', content);
    
    if (attachmentInput.files.length) {
        formData.append('media', attachmentInput.files[0]);
        // Clear file input
        attachmentInput.value = '';
        $('.attachment-preview').empty().hide();
    }
    
    // Optimistically add message to UI
    const tempId = 'temp-' + Date.now();
    const tempMessage = {
        message_id: tempId,
        chat_id: currentChat.chat_id,
        sender_id: currentUser.user_id,
        content: content,
        timestamp: new Date().toISOString(),
        status: 'sent'
    };
    
    if (attachmentInput.files.length) {
        // Create a temporary URL for the image
        const file = attachmentInput.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            tempMessage.media_url = e.target.result;
            messages.push(tempMessage);
            renderMessages();
            scrollToBottom();
        }
        reader.readAsDataURL(file);
    } else {
        messages.push(tempMessage);
        renderMessages();
        scrollToBottom();
    }
    
    // Send message to server
    $.ajax({
        url: 'api/messages.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('edms_token') },
        success: function(response) {
            if (response.success) {
                // Replace temp message with real one
                const index = messages.findIndex(m => m.message_id === tempId);
                if (index !== -1) {
                    messages[index] = response.message;
                }
                renderMessages();
            } else {
                showAlert(response.message || 'Failed to send message', 'danger');
                // Remove temp message
                messages = messages.filter(m => m.message_id !== tempId);
                renderMessages();
            }
        },
        error: function() {
            showAlert('Server error. Please try again later.', 'danger');
            // Remove temp message
            messages = messages.filter(m => m.message_id !== tempId);
            renderMessages();
        }
    });
}

// Create a new one-on-one chat
function createNewChat(event) {
    event.preventDefault();
    
    const userId = $('#new-chat-user').val();
    if (!userId) {
        showAlert('Please select a user', 'warning');
        return;
    }
    
    $.ajax({
        url: 'api/chats.php',
        type: 'POST',
        data: {
            action: 'create',
            user_id: userId
        },
        dataType: 'json',
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('edms_token') },
        success: function(response) {
            if (response.success) {
                $('#newChatModal').modal('hide');
                
                // Check if this is an existing chat or new one
                const existingChatIndex = chats.findIndex(c => c.chat_id === response.chat.chat_id);
                if (existingChatIndex !== -1) {
                    chats[existingChatIndex] = response.chat;
                } else {
                    chats.push(response.chat);
                }
                
                renderChatList();
                openChat(response.chat);
            } else {
                showAlert(response.message || 'Failed to create chat', 'danger');
            }
        },
        error: function() {
            showAlert('Server error. Please try again later.', 'danger');
        }
    });
}

// Create a new group chat
function createNewGroup(event) {
    event.preventDefault();
    
    const groupName = $('#group-name').val().trim();
    const selectedUsers = $('#group-users').val();
    
    if (!groupName) {
        showAlert('Please enter a group name', 'warning');
        return;
    }
    
    if (!selectedUsers || selectedUsers.length === 0) {
        showAlert('Please select at least one user', 'warning');
        return;
    }
    
    $.ajax({
        url: 'api/chats.php',
        type: 'POST',
        data: {
            action: 'create_group',
            group_name: groupName,
            user_ids: selectedUsers
        },
        dataType: 'json',
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('edms_token') },
        success: function(response) {
            if (response.success) {
                $('#newGroupModal').modal('hide');
                chats.push(response.chat);
                renderChatList();
                openChat(response.chat);
            } else {
                showAlert(response.message || 'Failed to create group', 'danger');
            }
        },
        error: function() {
            showAlert('Server error. Please try again later.', 'danger');
        }
    });
}

// Load users for new chat creation
function loadUsersForNewChat() {
    $.ajax({
        url: 'api/users.php',
        type: 'GET',
        data: { action: 'list' },
        dataType: 'json',
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('edms_token') },
        success: function(response) {
            if (response.success) {
                users = response.users;
                
                // Populate new chat select
                const newChatSelect = $('#new-chat-user');
                newChatSelect.empty();
                newChatSelect.append('<option value="">Select a user</option>');
                
                // Populate group chat multiselect
                const groupUsersSelect = $('#group-users');
                groupUsersSelect.empty();
                
                users.forEach(user => {
                    if (user.user_id !== currentUser.user_id) {
                        newChatSelect.append(`<option value="${user.user_id}">${user.username}</option>`);
                        groupUsersSelect.append(`<option value="${user.user_id}">${user.username}</option>`);
                    }
                });
            } else {
                showAlert(response.message || 'Failed to load users', 'danger');
            }
        },
        error: function() {
            showAlert('Server error. Please try again later.', 'danger');
        }
    });
}

// Mark a chat as read
function markChatAsRead(chatId) {
    $.ajax({
        url: 'api/messages.php',
        type: 'POST',
        data: {
            action: 'mark_read',
            chat_id: chatId
        },
        dataType: 'json',
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('edms_token') },
        success: function(response) {
            if (response.success) {
                // Update chat in the list
                const chatIndex = chats.findIndex(c => c.chat_id === chatId);
                if (chatIndex !== -1) {
                    chats[chatIndex].unread_count = 0;
                }
                renderChatList();
            }
        }
    });
}

// Start polling for new messages
function startMessagePolling() {
    // Clear any existing interval
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
    }
    
    // Poll every 3 seconds
    messagePollingInterval = setInterval(pollForUpdates, 3000);
}

// Poll for new messages and updates
function pollForUpdates() {
    if (!currentUser) return;
    
    $.ajax({
        url: 'api/chats.php',
        type: 'GET',
        data: { 
            action: 'updates',
            last_update: lastUpdateTimestamp || 0
        },
        dataType: 'json',
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('edms_token') },
        success: function(response) {
            if (response.success) {
                // Update last update timestamp
                lastUpdateTimestamp = response.timestamp;
                
                // Handle chat updates
                if (response.chat_updates) {
                    handleChatUpdates(response.chat_updates);
                }
                
                // Handle new messages for current chat
                if (currentChat && response.message_updates && 
                    response.message_updates[currentChat.chat_id]) {
                    handleMessageUpdates(response.message_updates[currentChat.chat_id]);
                }
                
                // Handle typing indicators
                if (response.typing_updates) {
                    handleTypingUpdates(response.typing_updates);
                }
            }
        }
    });
}

// Handle chat list updates
function handleChatUpdates(chatUpdates) {
    let chatListChanged = false;
    
    chatUpdates.forEach(updatedChat => {
        const chatIndex = chats.findIndex(c => c.chat_id === updatedChat.chat_id);
        
        if (chatIndex !== -1) {
            // Update existing chat
            chats[chatIndex] = { ...chats[chatIndex], ...updatedChat };
        } else {
            // New chat
            chats.push(updatedChat);
        }
        
        chatListChanged = true;
    });
    
    if (chatListChanged) {
        renderChatList();
    }
}

// Handle message updates for current chat
function handleMessageUpdates(messageUpdates) {
    if (!messageUpdates || messageUpdates.length === 0) return;
    
    let hasNewMessages = false;
    
    messageUpdates.forEach(message => {
        // Check if this is a new message or an update to existing one
        const messageIndex = messages.findIndex(m => m.message_id === message.message_id);
        
        if (messageIndex !== -1) {
            // Update existing message
            messages[messageIndex] = { ...messages[messageIndex], ...message };
        } else {
            // New message
            messages.push(message);
            hasNewMessages = true;
        }
    });
    
    // If we have updates, re-render messages
    renderMessages();
    
    // If we have new messages, scroll to bottom
    if (hasNewMessages) {
        scrollToBottom();
    }
    
    // If this is the current chat, mark as read
    if (currentChat) {
        markChatAsRead(currentChat.chat_id);
    }
}

// Handle typing indicators
function handleTypingUpdates(typingUpdates) {
    if (!currentChat || !typingUpdates[currentChat.chat_id]) {
        $('.typing-indicator').hide();
        return;
    }
    
    const typingUsers = typingUpdates[currentChat.chat_id].filter(id => id !== currentUser.user_id);
    
    if (typingUsers.length === 0) {
        $('.typing-indicator').hide();
    } else {
        // Find user names
        const names = typingUsers.map(id => {
            const participant = currentChat.participants.find(p => p.user_id === id);
            return participant ? participant.username : 'Someone';
        });
        
        let typingText;
        if (names.length === 1) {
            typingText = `${names[0]} is typing...`;
        } else if (names.length === 2) {
            typingText = `${names[0]} and ${names[1]} are typing...`;
        } else {
            typingText = `Several people are typing...`;
        }
        
        $('.typing-indicator').text(typingText).show();
    }
}

// Handle typing indicator
function handleTyping() {
    if (!currentChat) return;
    
    // Send typing status to server
    if (!lastTypingStatus) {
        $.ajax({
            url: 'api/messages.php',
            type: 'POST',
            data: {
                action: 'typing',
                chat_id: currentChat.chat_id,
                is_typing: true
            },
            dataType: 'json',
            headers: { 'Authorization': 'Bearer ' + localStorage.getItem('edms_token') }
        });
        
        lastTypingStatus = true;
    }
    
    // Clear existing timeout
    if (typingTimeout) {
        clearTimeout(typingTimeout);
    }
    
    // Set timeout to clear typing status
    typingTimeout = setTimeout(() => {
        $.ajax({
            url: 'api/messages.php',
            type: 'POST',
            data: {
                action: 'typing',
                chat_id: currentChat.chat_id,
                is_typing: false
            },
            dataType: 'json',
            headers: { 'Authorization': 'Bearer ' + localStorage.getItem('edms_token') }
        });
        
        lastTypingStatus = false;
    }, 2000);
}

// Handle image attachment selection
function handleAttachment() {
    const file = this.files[0];
    if (!file) {
        $('.attachment-preview').empty().hide();
        return;
    }
    
    // Check file type
    if (!file.type.match('image.*')) {
        showAlert('Only image files are supported', 'warning');
        this.value = '';
        return;
    }
    
    // Check file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
        showAlert('File is too large. Maximum size is 5MB', 'warning');
        this.value = '';
        return;
    }
    
    // Show preview
    const reader = new FileReader();
    reader.onload = function(e) {
        $('.attachment-preview').html(`
            <div class="position-relative">
                <img src="${e.target.result}" class="img-thumbnail" style="max-height: 100px">
                <button type="button" class="btn-close position-absolute top-0 end-0" id="clear-attachment"></button>
            </div>
        `).show();
        
        $('#clear-attachment').on('click', function() {
            $('#attachment-input').val('');
            $('.attachment-preview').empty().hide();
        });
    }
    reader.readAsDataURL(file);
}

// Scroll to bottom of chat
function scrollToBottom() {
    const messagesContainer = $('.messages-container');
    messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
}

// Initialize when document is ready
$(document).ready(function() {
    // Only initialize if we're on the chat page
    if (window.location.pathname.endsWith('chat.html')) {
        initChat();
    }
});

// Track last update timestamp for polling
let lastUpdateTimestamp = 0;
