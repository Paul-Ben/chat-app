<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Models\ChatParticipant;
use App\Models\User;
use App\Events\MessageSent;
use App\Events\TypingEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ChatController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $users = User::all();

        // $chats = $user->chats()
        //     ->with([
        //         'participants.user' => function ($query) {
        //             $query->select('id', 'name', 'profile_pic', 'status');
        //         },
        //         'latestMessage.sender:id,name,profile_pic',
        //         'admin:id,name,profile_pic',
        //         'creator:id,name'
        //     ])
            // ->withCount([
            //     'unreadMessages' => function ($query) use ($user) {
            //         $query->where('sender_id', '!=', $user->id)
            //             ->whereDoesntHave('readReceipts', function ($q) use ($user) {
            //                 $q->where('user_id', $user->id);
            //             });
            //     },
            //     'participants as total_participants'
            // ])
            // ->addSelect([
            //     'last_message_time' => Message::select('created_at')
            //         ->whereColumn('chat_id', 'chats.id')
            //         ->latest()
            //         ->limit(1)
            // ])
            // ->orderByDesc('last_message_time')
            // ->get();

        // Add additional computed attributes
        // $chats->each(function ($chat) use ($user) {
        //     $chat->is_group = $chat->is_group; // Explicitly include in response
        //     $chat->other_participant = $chat->is_group
        //         ? null
        //         : $chat->participants->where('user_id', '!=', $user->id)->first();
        // });
        return view('chats.index', 
        // [
        //     'chats' => $chats,
        //     'users' => $users
        //     // 'unread_count' => $user->unreadMessages()->count()
        // ]
    );
    }
    // public function index()
    // {
    //     $user = Auth::user();

    //     $chats = $user->chats()
    //         ->with([
    //             'participants.user',
    //             'latestMessage.sender',
    //             'admin'
    //         ])
    //         ->withCount('unreadMessages')
    //         ->orderByDesc(function($query) {
    //             $query->select('created_at')
    //                 ->from('messages')
    //                 ->whereColumn('chat_id', 'chats.id')
    //                 ->latest()
    //                 ->limit(1);
    //         })
    //         ->get();

    //     return view('chats.index', compact('chats'));
    // }

    public function myDetails()
    {
        $details = Auth::user();

        return response()->json([
            'success' => true,
            'user' => $details
        ], 200);
    }

    public function loadChatUsers()
    {
        $users = User::all();

        return response()->json([
            'success' => true,
            'users' => $users
        ], 200);
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
    // create chat
    public function create(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $userId = $request->input('user_id');
        $currentUserId = Auth::id();

        if ($userId === $currentUserId) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create chat with yourself'
            ], 400);
        }

        return DB::transaction(function () use ($userId, $currentUserId) {
            // Check if chat already exists between these users
            $existingChat = $this->findExistingChat($currentUserId, $userId);

            if ($existingChat) {
                return response()->json([
                    'success' => true,
                    'message' => 'Chat already exists',
                    'chat' => $this->formatChatResponse($existingChat)
                ]);
            }

            // Create new chat
            $chat = Chat::create([
                'is_group' => false,
                'created_by' => $currentUserId,
            ]);

            // Get users
            $currentUser = User::findOrFail($currentUserId);
            $otherUser = User::findOrFail($userId);

            // Add participants
            ChatParticipant::create([
                'chat_id' => $chat->id,
                'user_id' => $currentUserId,
                'role' => 'creator',
            ]);

            ChatParticipant::create([
                'chat_id' => $chat->id,
                'user_id' => $userId,
                'role' => 'member',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Chat created successfully',
                'chat' => $this->formatChatResponse($chat->load(['participants.user']))
            ]);
        });
    }

    private function findExistingChat($user1, $user2)
    {
        return Chat::where('is_group', false)
            ->whereHas('participants', function($query) use ($user1) {
                $query->where('user_id', $user1);
            })
            ->whereHas('participants', function($query) use ($user2) {
                $query->where('user_id', $user2);
            })
            ->with(['participants.user'])
            ->first();

    }

    private function formatChatResponse(Chat $chat)
    {
        return [
            'chat_id' => $chat->id,
            'is_group' => $chat->is_group,
            'name' => $chat->name,
            'avatar' => $chat->avatar,
            'created_at' => $chat->created_at->toDateTimeString(),
            'participants' => $chat->participants->map(function($participant) {
                return [
                    'user_id' => $participant->user_id,
                    'name' => $participant->user->name,
                    'profile_pic' => $participant->user->profile_photo_path,
                    'role' => $participant->role,
                    'status' => 'online', 
                    'unread_count' => 0, 
                ];
            })->toArray(),
            'last_message' => null 
        ];
    }

    public function listChats(Request $request)
    {
        // Get authenticated user
        $user = Auth::user();
        
        // Load chats where user is a participant
        $chats = Chat::whereHas('participants', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->with(['participants.user' => function($query) {
            $query->select('id', 'name', 'profile_pic', 'status');
        }])
        ->orderByDesc('updated_at')
        ->get();

        log::error();
        
        $formattedChats = $chats->map(function($chat) {
            return [
                'chat_id' => $chat->id,
                'is_group' => $chat->is_group,
                'participants' => $chat->participants->map(function($participant) {
                    return [
                        'user_id' => $participant->user_id,
                        'name' => $participant->user->name,
                        'profile_pic' => $participant->user->profile_pic,
                        'status' => $participant->user->status,
                        'role' => $participant->role,
                    ];
                })->toArray(),
                'created_at' => $chat->created_at->toDateTimeString(),
                'updated_at' => $chat->updated_at->toDateTimeString(),
                'last_message' => null, 
            ];
        });
        
        return response()->json([
            'success' => true,
            'chats' => $formattedChats
        ]);
    }

     /**
     * List messages for a specific chat.
     */
    public function listMessages(Request $request, $chatId)
    {
        $user = auth()->user();
        
        if (empty($chatId)) {
            return response()->json(['success' => false, 'message' => 'Chat ID is required'], 400);
        }

        $chat = Chat::find($chatId);
        if (!$chat) {
            return response()->json(['success' => false, 'message' => 'Chat not found'], 404);
        }

        $isParticipant = $chat->participants->contains('user_id', $user->id);
        if (!$isParticipant) {
            return response()->json(['success' => false, 'message' => 'You are not a participant in this chat'], 403);
        }

        $messages = $chat->messages()->orderByDesc('created_at')->get();

        return response()->json([
            'success' => true,
            'messages' => $messages
        ]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'chat_id' => 'required|exists:chats,id',
            'content' => 'nullable|string',
            'media' => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,mov'
        ]);

        $chat = Chat::with('participants')->where('id', $request->chat_id)->first();

        $user = auth()->user();
        $isParticipant = $chat->participants->contains('id', $user->id);

        if (!$isParticipant) {
            return response()->json(['success' => false, 'message' => 'You are not a participant in this chat'], 403);
        }

        $media_url = null;
        if ($request->hasFile('media')) {
            $media_path = $request->file('media')->store('chat_media', 'public');
            $media_url = Storage::url($media_path);
        }

        // Create message
        $message = Message::create([
            // 'message_id' => (string) Str::uuid(),
            'chat_id' => $chat->id,
            'sender_id' => $user->id,
            'content' => $request->content,
            'media_url' => $media_url,
            'delivered_at' =>  Carbon::now()->format('Y-m-d H:i:s')
        ]);

        return response()->json([
            'success' => true,
            'message' => $message
        ], 201);
    }

}
