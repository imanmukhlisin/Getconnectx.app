<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'content',
        'type',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * The conversation this message belongs to.
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * The user who sent this message.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
