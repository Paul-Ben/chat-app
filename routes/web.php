<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
   
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
     // Chat routes
     Route::get('/chat-users', [ChatController::class, 'loadChatUsers'])->name('chats.users');
     Route::get('/chats', [ChatController::class, 'index'])->name('chats.index');
     Route::get('/chats/{chat}', [ChatController::class, 'show'])->name('chats.show');
     
     // Message routes
     Route::post('/chats/{chat}/messages', [ChatController::class, 'storeMessage'])->name('messages.store');
     
     // Group management
     Route::post('/chats/{chat}/participants', [ChatController::class, 'addParticipant'])->name('chats.participants.add');
     
     // Real-time features
     Route::post('/chats/{chat}/typing', [ChatController::class, 'typing'])->name('chats.typing');
});

require __DIR__.'/auth.php';
