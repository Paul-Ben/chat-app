<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Models\ChatParticipant;
use App\Events\MessageSent;
use App\Events\TypingEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $chats = $user->chats()
            ->with([
                'participants.user',
                'latestMessage.sender',
                'admin'
            ])
            ->withCount('unreadMessages')
            ->orderByDesc(function($query) {
                $query->select('created_at')
                    ->from('messages')
                    ->whereColumn('chat_id', 'chats.id')
                    ->latest()
                    ->limit(1);
            })
            ->get();

        return view('chats.index', compact('chats'));
    }

    public function show(Chat $chat)
    {
        $this->authorize('view', $chat);
        
        $chat->load([
            'participants.user',
            'admin',
            'creator'
        ]);

        $messages = $chat->messages()
            ->with(['sender', 'readReceipts.user'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark messages as read
        $this->markMessagesAsRead($chat);

        return view('chats.show', compact('chat', 'messages'));
    }

    public function storeMessage(Request $request, Chat $chat)
    {
        $this->authorize('participate', $chat);
        
        $request->validate([
            'content' => 'required_without:media|string|max:2000',
            'media' => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,mp3,doc,pdf|max:10240'
        ]);

        DB::transaction(function () use ($request, $chat) {
            $message = $chat->messages()->create([
                'sender_id' => auth()->id(),
                'content' => $request->content,
                'media_url' => $request->hasFile('media') ? 
                    $request->file('media')->store('chat_media', 'public') : null,
                'media_type' => $request->hasFile('media') ? 
                    $request->file('media')->getMimeType() : null
            ]);

            // Mark as delivered to all participants
            $chat->participants()
                ->where('user_id', '!=', auth()->id())
                ->update(['last_read_at' => null]);

            broadcast(new MessageSent($message, $chat->id))->toOthers();

            return $message;
        });
    }

    public function typing(Request $request, Chat $chat)
    {
        $this->authorize('view', $chat);
        
        $request->validate([
            'is_typing' => 'required|boolean'
        ]);

        broadcast(new TypingEvent(
            auth()->id(),
            $chat->id,
            $request->is_typing,
            auth()->user()->name
        ))->toOthers();

        return response()->json(['status' => 'success']);
    }

    public function addParticipant(Request $request, Chat $chat)
    {
        $this->authorize('admin', $chat);
        
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $participant = $chat->participants()->create([
            'user_id' => $request->user_id,
            'role' => 'member'
        ]);

        // Create system message
        $message = $chat->messages()->create([
            'sender_id' => auth()->id(),
            'is_system_message' => true,
            'system_message_type' => 'user_added',
            'system_message_metadata' => [
                'user_id' => $request->user_id,
                'action_by' => auth()->id()
            ]
        ]);

        broadcast(new MessageSent($message, $chat->id))->toOthers();

        return response()->json($participant);
    }

    protected function markMessagesAsRead(Chat $chat)
    {
        $chat->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', auth()->id())
            ->update(['read_at' => now()]);

        $chat->participants()
            ->where('user_id', auth()->id())
            ->update(['last_read_at' => now()]);
    }
}
