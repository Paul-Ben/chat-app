@extends('layouts.chat')

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
@endsection