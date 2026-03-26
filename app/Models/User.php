<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name', 'email', 'password', 'avatar', 'cover_photo',
        'bio', 'location', 'birth_date', 'status', 'status_text',
        'last_seen_at', 'story_privacy', 'mood',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'last_seen_at'      => 'datetime',
            'birth_date'        => 'date',
        ];
    }

    public function isOnline(): bool {
        return $this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(5));
    }

    public function lastSeenHuman(): string {
        return $this->last_seen_at ? $this->last_seen_at->diffForHumans() : 'Jamais connecté';
    }

    public function hasPublicStories(): bool {
        return ($this->story_privacy ?? 'public') === 'public';
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

    // ─── Relations ───────────────────────────────────────────────

    public function posts()     { return $this->hasMany(Post::class)->latest(); }
    public function comments()  { return $this->hasMany(Comment::class); }
    public function following() { return $this->hasMany(Follow::class, 'follower_id'); }
    public function followers() { return $this->hasMany(Follow::class, 'following_id'); }
    public function activities(){ return $this->hasMany(Activity::class)->latest(); }
    public function postReactions() { return $this->hasMany(PostReaction::class); }

    public function isFollowing(User $user): bool {
        return $this->following()->where('following_id', $user->id)->exists();
    }

    // ─── Amis ────────────────────────────────────────────────────

    public function sentFriendRequests()     { return $this->hasMany(FriendRequest::class, 'sender_id'); }
    public function receivedFriendRequests() { return $this->hasMany(FriendRequest::class, 'receiver_id'); }

    public function friends() {
        $sentIds     = $this->sentFriendRequests()->where('status', 'accepted')->pluck('receiver_id');
        $receivedIds = $this->receivedFriendRequests()->where('status', 'accepted')->pluck('sender_id');
        return User::whereIn('id', $sentIds->merge($receivedIds));
    }

    public function isFriendWith(User $user): bool {
        return FriendRequest::where(function ($q) use ($user) {
            $q->where('sender_id', $this->id)->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($user) {
            $q->where('sender_id', $user->id)->where('receiver_id', $this->id);
        })->where('status', 'accepted')->exists();
    }

    public function hasPendingRequestWith(User $user): bool {
        return FriendRequest::where(function ($q) use ($user) {
            $q->where('sender_id', $this->id)->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($user) {
            $q->where('sender_id', $user->id)->where('receiver_id', $this->id);
        })->where('status', 'pending')->exists();
    }

    // ─── Messages ────────────────────────────────────────────────

    public function sentMessages()     { return $this->hasMany(Message::class, 'sender_id'); }
    public function receivedMessages() { return $this->hasMany(Message::class, 'receiver_id'); }

    public function unreadMessagesCount(): int {
        return $this->receivedMessages()->where('is_read', false)->count();
    }

    // ─── Groupes ─────────────────────────────────────────────────

    public function groups() {
        return $this->belongsToMany(Group::class, 'group_members', 'user_id', 'group_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    public function isGroupAdmin(Group $group): bool {
        return $this->groups()
                    ->where('group_id', $group->id)
                    ->wherePivot('role', 'admin')
                    ->exists();
    }
}