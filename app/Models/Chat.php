<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $guarded = [] ;
    public function users()
    {
        return $this->belongsToMany(User::class, 'chat_participants')
            ->withPivot('is_admin', 'is_creator')
            ->withTimestamps();
    }
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
    public function participants()
    {
        return $this->hasMany(ChatParticipant::class);
    }
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
