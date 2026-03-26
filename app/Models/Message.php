<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\MessageReaction;

class Message extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'image',        // ← manquait
        'is_read', 'forwarded_from_id'
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    // ← ces deux méthodes manquaient !
    public function hasImage(): bool
    {
        return !is_null($this->image);
    }

    public function hasContent(): bool
    {
        return !is_null($this->content) && $this->content !== '';
    }

public function reactions()
{
    return $this->hasMany(MessageReaction::class);
}

public function forwardedFrom()
{
    return $this->belongsTo(Message::class, 'forwarded_from_id');
}


}