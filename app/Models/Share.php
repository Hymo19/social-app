<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Share extends Model
{
    protected $fillable = [
        'post_id', 'sender_id', 'recipient_type', 'recipient_id',
        'message', 'status', 'destination', 'accepted_at'
    ];

    protected $casts = ['accepted_at' => 'datetime'];

    public function post()      { return $this->belongsTo(Post::class); }
    public function sender()    { return $this->belongsTo(User::class, 'sender_id'); }
    public function recipient() { return $this->morphTo(); }

    public function isPending()  { return $this->status === 'pending'; }
    public function isAccepted() { return $this->status === 'accepted'; }
}