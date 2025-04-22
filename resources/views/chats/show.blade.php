@extends('layouts.app')

@section('title', $chat->name)

@section('content')
    <div class="chat-container">
        <div class="chat-header">
            <div class="chat-info">
                <h2>{{ $chat->name }}</h2>
                @if ($chat->is_group)
                    <div class="group-info">
                        <span class="participant-count">{{ $chat->participants->count() }} members</span>
                        <span class="admin-badge">Admin: {{ $chat->admin->name }}</span>
                    </div>
                @else
                    <div class="user-status">
                        <span class="status-indicator {{ $otherUser->status }}"></span>
                        <span>{{ ucfirst($otherUser->status) }}</span>
                    </div>
                @endif
            </div>

            @if ($chat->is_group && auth()->user()->can('admin', $chat))
                <div class="group-actions">
                    <button id="addParticipantBtn">Add Member</button>
                    <div class="dropdown-menu" id="groupActionsMenu">
                        <a href="#" class="dropdown-item">Change Group Name</a>
                        <a href="#" class="dropdown-item">Change Admin</a>
                        <a href="#" class="dropdown-item">Leave Group</a>
                    </div>
                </div>
            @endif
        </div>

        <div class="messages-container" id="messagesContainer">
            @foreach ($messages as $message)
                @include('partials.message', ['message' => $message])
            @endforeach
        </div>

        <div class="message-input-container">
            <form id="messageForm" enctype="multipart/form-data">
                @csrf
                <div class="input-group">
                    <input type="text" name="content" id="messageInput" placeholder="Type a message..."
                        autocomplete="off">
                    <label for="mediaUpload" class="file-upload-label">
                        <i class="fas fa-paperclip"></i>
                        <input type="file" id="mediaUpload" name="media"
                            accept="image/*,video/*,audio/*,application/pdf">
                    </label>
                    <button type="submit" id="sendButton">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <div class="typing-indicator" id="typingIndicator"></div>
            </form>
        </div>
    </div>

    <!-- Add Participant Modal -->
    @if ($chat->is_group)
        <div class="modal" id="addParticipantModal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h3>Add Participant</h3>
                <form id="addParticipantForm">
                    @csrf
                    <div class="form-group">
                        <label for="userSearch">Search Users</label>
                        <input type="text" id="userSearch" placeholder="Enter name or email">
                        <div id="userSearchResults"></div>
                    </div>
                    <button type="submit" class="btn-primary">Add to Group</button>
                </form>
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            const chatId = "{{ $chat->id }}";
            const currentUserId = "{{ auth()->id() }}";
            const isGroupAdmin = {{ $chat->is_group && auth()->user()->can('admin', $chat) ? 'true' : 'false' }};
            const pusherKey = "{{ config('broadcasting.connections.pusher.key') }}";
            const pusherCluster = "{{ config('broadcasting.connections.pusher.options.cluster') }}";
        </script>
        <script src="{{ asset('js/chat.js') }}"></script>
    @endpush
@endsection
