{{-- @extends('layouts.chat')

@section('title', 'Chats')

@section('styles')
<style>
    :root {
        --primary-green: #128C7E;
        --secondary-green: #25D366;
        --light-green: #DCF8C6;
        --chat-bg: #F0F2F5;
        --chat-header: #F0F2F5;
        --chat-border: #E9EDEF;
        --text-primary: #111B21;
        --text-secondary: #667781;
        --unread-badge: #25D366;
    }

    body {
        font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
        background-color: var(--chat-bg);
        margin: 0;
        padding: 0;
        color: var(--text-primary);
    }

    .chat-container {
        display: flex;
        height: 100vh;
        max-width: 1200px;
        margin: 0 auto;
        box-shadow: 0 1px 1px rgba(0,0,0,0.08);
    }

    .sidebar {
        width: 30%;
        min-width: 300px;
        border-right: 1px solid var(--chat-border);
        background-color: white;
        display: flex;
        flex-direction: column;
    }

    .header {
        padding: 10px 16px;
        background-color: var(--chat-header);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .profile-img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    .search-container {
        padding: 8px 12px;
        background-color: var(--chat-header);
    }

    .search-input {
        width: 100%;
        padding: 8px 12px;
        border-radius: 8px;
        border: none;
        background-color: white;
        font-size: 14px;
    }

    .chat-list {
        flex: 1;
        overflow-y: auto;
    }

    .chat-item {
        display: flex;
        padding: 12px;
        border-bottom: 1px solid var(--chat-border);
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .chat-item:hover {
        background-color: #F5F6F6;
    }

    .chat-item.active {
        background-color: #F0F2F5;
    }

    .chat-avatar {
        position: relative;
        margin-right: 12px;
    }

    .chat-avatar-img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
    }

    .online-status {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: var(--unread-badge);
        border: 2px solid white;
    }

    .chat-info {
        flex: 1;
        min-width: 0;
    }

    .chat-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 4px;
    }

    .chat-name {
        font-weight: 500;
        font-size: 16px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .chat-time {
        font-size: 12px;
        color: var(--text-secondary);
    }

    .chat-preview {
        display: flex;
        align-items: center;
    }

    .chat-message {
        font-size: 14px;
        color: var(--text-secondary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        flex: 1;
    }

    .unread-count {
        background-color: var(--unread-badge);
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-left: 6px;
    }

    .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        background-color: #E5DDD5;
        background-image: url('https://web.whatsapp.com/img/bg-chat-tile-light_a4be512e7195b6b733d9110b408f075d.png');
        background-repeat: repeat;
    }

    .empty-chat {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: var(--text-secondary);
    }

    .empty-chat-icon {
        font-size: 120px;
        margin-bottom: 20px;
        color: #E9EDEF;
    }

    .empty-chat-text {
        font-size: 24px;
        margin-bottom: 10px;
    }

    .empty-chat-subtext {
        font-size: 14px;
        text-align: center;
        max-width: 400px;
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
        }
        .main-content {
            display: none;
        }
        .main-content.active {
            display: flex;
        }
    }
</style>
@endsection

@section('content')
<div class="chat-container">
    <!-- Left sidebar with chat list -->
    <div class="sidebar">
        <!-- Header -->
        <div class="header">
            <div class="user-profile">
                <img src="{{ Auth::user()->profile_pic ? asset('storage/'.Auth::user()->profile_pic) : asset('images/default-profile.png') }}" 
                     alt="Profile" class="profile-img">
            </div>
            <div class="header-actions">
                <i class="fas fa-users"></i>
                <i class="fas fa-comment-dots"></i>
                <i class="fas fa-ellipsis-v"></i>
            </div>
        </div>

        <!-- Search -->
        <div class="search-container">
            <input type="text" placeholder="Search or start new chat" class="search-input">
        </div>

        <!-- Chat list -->
        <div class="chat-list">
            @foreach($chats as $chat)
                @php
                    $otherParticipant = $chat->is_group ? null : $chat->other_participant;
                    $lastMessage = $chat->latestMessage;
                    $unreadCount = $chat->unread_messages_count;
                @endphp

                <a href="{{ route('chats.show', $chat->id) }}" 
                   class="chat-item {{ request()->route('chat') && request()->route('chat')->id == $chat->id ? 'active' : '' }}">
                    <div class="chat-avatar">
                        @if($chat->is_group)
                            <img src="{{ $chat->avatar ? asset('storage/'.$chat->avatar) : asset('images/group-default.png') }}" 
                                 alt="{{ $chat->name }}" class="chat-avatar-img">
                        @else
                            <img src="{{ $otherParticipant->profile_pic ? asset('storage/'.$otherParticipant->profile_pic) : asset('images/default-profile.png') }}" 
                                 alt="{{ $otherParticipant->name }}" class="chat-avatar-img">
                            @if($otherParticipant->status == 'online')
                                <span class="online-status"></span>
                            @endif
                        @endif
                    </div>
                    <div class="chat-info">
                        <div class="chat-header">
                            <div class="chat-name">
                                {{ $chat->is_group ? $chat->name : $otherParticipant->name }}
                            </div>
                            <div class="chat-time">
                                @if($lastMessage)
                                    {{ $lastMessage->created_at->format('h:i A') }}
                                @endif
                            </div>
                        </div>
                        <div class="chat-preview">
                            <div class="chat-message">
                                @if($lastMessage)
                                    @if($lastMessage->sender_id == Auth::id())
                                        You: 
                                    @elseif($chat->is_group)
                                        {{ $lastMessage->sender->name }}: 
                                    @endif
                                    {{ Str::limit($lastMessage->content, 30) }}
                                @else
                                    No messages yet
                                @endif
                            </div>
                            @if($unreadCount > 0)
                                <div class="unread-count">{{ $unreadCount }}</div>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Right content area -->
    <div class="main-content {{ !request()->route('chat') ? 'active' : '' }}">
        <div class="empty-chat">
            <div class="empty-chat-icon">
                <i class="fas fa-comment-dots"></i>
            </div>
            <div class="empty-chat-text">
                WhatsApp Web
            </div>
            <div class="empty-chat-subtext">
                Send and receive messages without keeping your phone online.
                Use WhatsApp on up to 4 linked devices and 1 phone at the same time.
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile view toggle
        const chatItems = document.querySelectorAll('.chat-item');
        const mainContent = document.querySelector('.main-content');
        
        chatItems.forEach(item => {
            item.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    const href = this.getAttribute('href');
                    
                    // Hide all chat items and show main content
                    document.querySelector('.sidebar').style.display = 'none';
                    mainContent.style.display = 'flex';
                    mainContent.innerHTML = '<div class="loading">Loading chat...</div>';
                    
                    // Load the chat
                    fetch(href)
                        .then(response => response.text())
                        .then(html => {
                            mainContent.innerHTML = html;
                        });
                }
            });
        });
        
        // Search functionality
        const searchInput = document.querySelector('.search-input');
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const chatItems = document.querySelectorAll('.chat-item');
            
            chatItems.forEach(item => {
                const chatName = item.querySelector('.chat-name').textContent.toLowerCase();
                const lastMessage = item.querySelector('.chat-message').textContent.toLowerCase();
                
                if (chatName.includes(searchTerm) || lastMessage.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
</script>
@endsection --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EDMS-App | Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>
<body>
    <div class="container-fluid p-0 vh-100">
        <div class="row g-0 h-100">
            <!-- Chat Sidebar -->
            <div class="col-md-4 col-lg-3 chat-sidebar d-none d-md-block">
                <div class="sidebar-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="me-2">
                            <img src="https://via.placeholder.com/50" alt="User" class="user-profile-pic profile-pic">
                        </div>
                        <div>
                            <h6 class="mb-0 user-name">Loading...</h6>
                            <small class="text-muted user-status">Online</small>
                        </div>
                    </div>
                    <div>
                        <div class="dropdown">
                            <button class="btn btn-light rounded-circle" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.html"><i class="bi bi-person me-2"></i> Profile</a></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#newChatModal"><i class="bi bi-chat me-2"></i> New Chat</a></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#newGroupModal"><i class="bi bi-people me-2"></i> New Group</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" id="logout-btn"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="sidebar-search p-2">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-0 bg-light" placeholder="Search chats">
                    </div>
                </div>
                <div class="chat-list">
                    <!-- Chat list will be populated by JavaScript -->
                    <div class="text-center text-muted p-3">Loading chats...</div>
                </div>
            </div>

            <!-- Chat Content -->
            <div class="col-md-8 col-lg-9 chat-content">
                <!-- Mobile header with back button -->
                <div class="chat-header d-flex align-items-center">
                    <button class="btn back-to-chats d-md-none me-2">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <div class="d-flex align-items-center">
                        <div class="me-3 chat-pic">
                            <div class="profile-pic bg-secondary d-flex align-items-center justify-content-center text-white">
                                <span style="font-size: 20px;">?</span>
                            </div>
                        </div>
                        <div>
                            <h6 class="mb-0 chat-name">Select a chat</h6>
                            <small class="text-muted chat-status">No chat selected</small>
                        </div>
                    </div>
                </div>

                <!-- Messages Area -->
                <div class="messages-container">
                    <div class="text-center text-muted my-5">Select a chat to start messaging</div>
                </div>

                <!-- Typing indicator -->
                <div class="typing-indicator px-3 py-1 small" style="display:none;"></div>

                <!-- Message Input -->
                <div class="message-input-container p-3 border-top">
                    <form id="message-form">
                        <div class="input-group">
                            <label for="attachment-input" class="attachment-btn d-flex align-items-center justify-content-center px-2">
                                <i class="bi bi-paperclip fs-5"></i>
                            </label>
                            <input type="file" id="attachment-input" class="d-none" accept="image/*">
                            <input type="text" id="message-input" class="form-control" placeholder="Type a message" autocomplete="off">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i>
                            </button>
                        </div>
                    </form>
                    <div class="attachment-preview mt-2" style="display:none;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Chat Modal -->
    <div class="modal fade" id="newChatModal" tabindex="-1" aria-labelledby="newChatModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newChatModalLabel">New Chat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="new-chat-form">
                        <div class="mb-3">
                            <label for="new-chat-user" class="form-label">Select a user</label>
                            <select class="form-select" id="new-chat-user" required>
                                <option value="">Loading users...</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Start Chat</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- New Group Modal -->
    <div class="modal fade" id="newGroupModal" tabindex="-1" aria-labelledby="newGroupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newGroupModalLabel">Create Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="new-group-form">
                        <div class="mb-3">
                            <label for="group-name" class="form-label">Group Name</label>
                            <input type="text" class="form-control" id="group-name" required>
                        </div>
                        <div class="mb-3">
                            <label for="group-users" class="form-label">Select Participants</label>
                            <select class="form-select" id="group-users" multiple required style="height: 150px;">
                                <option value="">Loading users...</option>
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple users</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Create Group</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="alert-container" class="position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 1050;"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <script>
        $(document).ready(function () {
           console.log('jQuery is ready');
       
           $('#newChatModal').on('shown.bs.modal', function () {
               console.log('Modal is shown');
               loadUsersForNewChat();
           });

           function loadUsersForNewChat() {
    $.ajax({
        url: 'http://127.0.0.1:8000/chat-users',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('users:', response.users);
            if (response.success) {
                const users = response.users;
                const currentUser = { user_id: 1 }; // example, replace with your current user logic

                const newChatSelect = $('#new-chat-user');
                newChatSelect.empty();
                newChatSelect.append('<option value="">Select a user</option>');

                const groupUsersSelect = $('#group-users');
                groupUsersSelect.empty();

                users.forEach(user => {
                    // !== currentUser.user_id
                    if (user.id ) {
                        newChatSelect.append(`<option value="${user.id}">${user.name}</option>`);
                        groupUsersSelect.append(`<option value="${user.id}">${user.name}</option>`);
                    }
                });
            } else {
                alert(response.message || 'Failed to load users');
            }
        },
        error: function() {
            alert('Server error. Please try again later.');
        }
    });
}
       }); 
   </script>
</body>
</html>
