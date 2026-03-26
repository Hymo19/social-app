<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'user_id', 'title', 'content', 'image',
        'visibility', 'mood',
        'shared_post_id', 'shared_comment',
    ];

    public function user()      { return $this->belongsTo(User::class); }
    public function likes()     { return $this->hasMany(Like::class); }
    public function comments()  { return $this->hasMany(Comment::class)->whereNull('parent_id'); }
    public function reactions() { return $this->hasMany(PostReaction::class); }

    // Post original embarqué (republication)
    public function sharedPost() {
        return $this->belongsTo(Post::class, 'shared_post_id')
                    ->with(['user', 'likes', 'reactions']);
    }

    // Posts qui ont republié ce post
    public function reposts() {
        return $this->hasMany(Post::class, 'shared_post_id');
    }

    public function isRepost(): bool {
        return !is_null($this->shared_post_id);
    }

    public function isLikedBy(User $user): bool {
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function getMoodBadge(): ?array {
        $moods = [
            'joie'    => ['emoji' => '😊', 'label' => 'en joie',            'color' => '#fbbf24'],
            'colere'  => ['emoji' => '😡', 'label' => 'en colère',          'color' => '#ef4444'],
            'danse'   => ['emoji' => '💃', 'label' => 'en train de danser', 'color' => '#a78bfa'],
            'furieux' => ['emoji' => '🤬', 'label' => 'furieux(se)',        'color' => '#dc2626'],
            'triste'  => ['emoji' => '😢', 'label' => 'triste',             'color' => '#60a5fa'],
            'musique' => ['emoji' => '🎵', 'label' => 'en mode musique',    'color' => '#34d399'],
        ];
        return $this->mood ? ($moods[$this->mood] ?? null) : null;
    }
}