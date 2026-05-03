<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    /**
     * Users participating in this conversation.
     */
    public function participants()
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
                    ->withPivot('last_read_message_id')
                    ->withTimestamps();
    }

    /**
     * Pivot rows for this conversation's participants (for per-user reads).
     */
    public function participantPivot()
    {
        return $this->hasMany(\App\Models\ConversationParticipant::class);
    }

    /**
     * Messages in this conversation.
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * The last message sent in this conversation.
     */
    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latest('created_at');
    }
}
