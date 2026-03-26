<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Group extends Model
{
    protected $fillable = ['creator_id', 'name', 'description', 'avatar'];

    public function creator()  { return $this->belongsTo(User::class, 'creator_id'); }
    public function members()  { return $this->hasMany(GroupMember::class); }
    public function users()    { return $this->belongsToMany(User::class, 'group_members')->withPivot('role')->withTimestamps(); }
    public function messages() { return $this->hasMany(GroupMessage::class)->latest(); }

    public function isMember($userId)
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    public function isAdmin($userId)
    {
        return $this->members()->where('user_id', $userId)->where('role', 'admin')->exists();
    }

    public function getAvatarUrlAttribute()
    {
        return $this->avatar ? Storage::url($this->avatar) : null;
    }
}