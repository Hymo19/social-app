<?php
namespace App\Models;

use App\Models\StoryView;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Story extends Model
{
    protected $fillable = [
        'user_id', 'type', 'media', 'music', 'music_name',
        'text_content', 'bg_color', 'text_color', 'expires_at'
    ];

    protected $casts = ['expires_at' => 'datetime'];

    public function user()    { return $this->belongsTo(User::class); }
    public function views()   { return $this->hasMany(StoryView::class); }

    public function isViewedBy($userId)
    {
        return $this->views()->where('user_id', $userId)->exists();
    }

    public function getMediaUrlAttribute()
    {
        return $this->media ? Storage::url($this->media) : null;
    }

    public function getMusicUrlAttribute()
    {
        return $this->music ? Storage::url($this->music) : null;
    }

    // Scope stories actives (moins de 24h)
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }
}