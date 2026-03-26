<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PhotoReaction extends Model {
    protected $fillable = ['user_id', 'profile_user_id', 'photo_type', 'reaction'];
    public function user()        { return $this->belongsTo(User::class); }
    public function profileUser() { return $this->belongsTo(User::class, 'profile_user_id'); }
}