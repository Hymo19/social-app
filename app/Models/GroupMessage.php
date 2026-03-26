<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GroupMessage extends Model
{
    protected $fillable = ['group_id', 'user_id', 'content', 'image', 'type'];

    public function group() { return $this->belongsTo(Group::class); }
    public function user()  { return $this->belongsTo(User::class); }

    public function getImageUrlAttribute()
    {
        return $this->image ? Storage::url($this->image) : null;
    }

public function hiddenBy()
    {
        return $this->belongsToMany(User::class, 'group_message_hides');
    }

    public function isHiddenBy($userId)
    {
        return $this->hiddenBy()->where('user_id', $userId)->exists();
    }



}