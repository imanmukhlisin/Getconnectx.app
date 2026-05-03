<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationParticipant extends Model
{
    protected $table = 'conversation_participants';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'last_read_message_id',
    ];

    public $incrementing = false;

    protected $primaryKey = null;
}
