<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $guarded = [];
    
    public function users()
    {
        return $this->belongsToMany(User::class, 'chat_participants')
            ->withPivot('is_admin', 'is_creator')
            ->withTimestamps();
    }
    // public function users()
    // {
    //     return $this->belongsToMany(User::class, 'chat_participants');
    // }
    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
    public function participants()
    {
        return $this->hasMany(ChatParticipant::class);
    }
    // public function participants()
    // {
    //     return $this->belongsToMany(User::class, 'chat_participants', 'chat_id', 'user_id');
    // }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
