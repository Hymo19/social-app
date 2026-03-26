<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommentReactionController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostReactionController;
use App\Http\Controllers\MoodController;
use App\Http\Controllers\PhotoReactionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ShareController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', fn() => redirect()->route('login'));
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ─── Feed ─────────────────────────────────────────────
    Route::get('/feed', [PostController::class, 'index'])->name('feed');

    // ─── Posts ────────────────────────────────────────────
    Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::post('/posts/{post}/react', [PostReactionController::class, 'toggle'])->name('posts.react');
    Route::post('/posts/{post}/like', [LikeController::class, 'toggle'])->name('likes.toggle');

    // ─── Reposts & Partages ───────────────────────────────
    Route::get('/posts/{post}/repost', [ShareController::class, 'showRepost'])->name('posts.repost.show');
    Route::post('/posts/{post}/repost', [ShareController::class, 'repost'])->name('posts.repost');
    Route::get('/posts/{post}/share', [ShareController::class, 'create'])->name('shares.create');
    Route::post('/posts/{post}/share', [ShareController::class, 'store'])->name('shares.store');
    Route::get('/shares/received', [ShareController::class, 'received'])->name('shares.received');
    Route::get('/shares/sent', [ShareController::class, 'sent'])->name('shares.sent');
    Route::post('/shares/{share}/accept', [ShareController::class, 'accept'])->name('shares.accept');
    Route::post('/shares/{share}/decline', [ShareController::class, 'decline'])->name('shares.decline');

    // ─── Listes (réactions, reposts, partages) ────────────
    Route::get('/posts/{post}/reactions-list', [PostController::class, 'reactionsList'])->name('posts.reactions-list');
    Route::get('/posts/{post}/reposters-list', [PostController::class, 'repostersList'])->name('posts.reposters-list');
    Route::get('/posts/{post}/sharers-list', [PostController::class, 'sharersList'])->name('posts.sharers-list');

    // ─── Modal data & commentaires JSON ──────────────────
    Route::get('/posts/{post}/modal-data', function (\App\Models\Post $post) {
        return response()->json([
            'title'      => $post->title,
            'content'    => $post->content,
            'user_name'  => $post->user->name,
            'avatar'     => $post->user->avatar ? Storage::url($post->user->avatar) : null,
            'created_at' => $post->created_at->diffForHumans(),
        ]);
    });

    Route::get('/posts/{post}/comments-json', function (\App\Models\Post $post) {
        $comments = $post->comments()
            ->whereNull('parent_id')
            ->with(['user', 'reactions.user', 'replies.user', 'replies.reactions.user'])
            ->latest()->get()
            ->map(function ($c) {
                $counts = [];
                foreach ($c->reactions->groupBy('reaction') as $emoji => $grp) {
                    $counts[$emoji] = $grp->count();
                }
                $myR = $c->reactions->where('user_id', auth()->id())->first();
                return [
                    'id'          => $c->id,
                    'user_name'   => $c->user->name,
                    'avatar'      => $c->user->avatar ? Storage::url($c->user->avatar) : null,
                    'content'     => $c->content,
                    'created_at'  => $c->created_at->diffForHumans(),
                    'reactions'   => $counts,
                    'my_reaction' => $myR?->reaction,
                    'replies'     => $c->replies->map(function ($r) {
                        $myRr = $r->reactions->where('user_id', auth()->id())->first();
                        return [
                            'id'          => $r->id,
                            'user_name'   => $r->user->name,
                            'avatar'      => $r->user->avatar ? Storage::url($r->user->avatar) : null,
                            'content'     => $r->content,
                            'created_at'  => $r->created_at->diffForHumans(),
                            'my_reaction' => $myRr?->reaction,
                        ];
                    })->values(),
                ];
            });
        return response()->json(['comments' => $comments]);
    });

    // ─── Page show (APRÈS toutes les routes fixes /posts/*) ─
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');

    // ─── Commentaires ─────────────────────────────────────
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::get('/comments/{comment}/edit', [CommentController::class, 'edit'])->name('comments.edit');
    Route::put('/comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('/comments/{comment}/react', [CommentReactionController::class, 'toggle'])->name('comments.react');

    // ─── Profil ───────────────────────────────────────────
    Route::get('/profile/{user}', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/{user}/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/{user}', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/{user}/cover', [ProfileController::class, 'updateCover'])->name('profile.cover');
    Route::post('/status', [ProfileController::class, 'updateStatus'])->name('status.update');
    Route::post('/profile/{user}/react-photo', [PhotoReactionController::class, 'toggle'])->name('profile.react-photo');

    // ─── Follow ───────────────────────────────────────────
    Route::post('/follow/{user}', [FollowController::class, 'toggle'])->name('follow.toggle');

    // ─── Amis ─────────────────────────────────────────────
    Route::get('/friends', [FriendController::class, 'index'])->name('friends.index');
    Route::get('/friends/requests', [FriendController::class, 'requests'])->name('friends.requests');
    Route::post('/friends/send/{user}', [FriendController::class, 'send'])->name('friends.send');
    Route::post('/friends/accept/{friendRequest}', [FriendController::class, 'accept'])->name('friends.accept');
    Route::post('/friends/decline/{friendRequest}', [FriendController::class, 'decline'])->name('friends.decline');
    Route::delete('/friends/remove/{user}', [FriendController::class, 'remove'])->name('friends.remove');

    Route::post('/friend-requests', function (\Illuminate\Http\Request $request) {
        $request->validate(['receiver_id' => 'required|exists:users,id']);
        $sender     = auth()->user();
        $receiverId = $request->receiver_id;
        $already = \App\Models\FriendRequest::where(function ($q) use ($sender, $receiverId) {
            $q->where('sender_id', $sender->id)->where('receiver_id', $receiverId);
        })->orWhere(function ($q) use ($sender, $receiverId) {
            $q->where('sender_id', $receiverId)->where('receiver_id', $sender->id);
        })->exists();
        if ($already) return response()->json(['message' => 'Demande déjà existante'], 409);
        $fr = \App\Models\FriendRequest::create([
            'sender_id'   => $sender->id,
            'receiver_id' => $receiverId,
            'status'      => 'pending',
        ]);
        return response()->json(['success' => true, 'id' => $fr->id]);
    })->name('friend-requests.send');
    Route::post('/friend-requests/{friendRequest}/accept', [FriendController::class, 'accept'])->name('friend-requests.accept');
    Route::post('/friend-requests/{friendRequest}/decline', [FriendController::class, 'decline'])->name('friend-requests.decline');

    // ─── Utilisateurs ─────────────────────────────────────
    Route::get('/users', [ProfileController::class, 'users'])->name('users.index');

    // ─── Recherche ────────────────────────────────────────
    Route::get('/search', [SearchController::class, 'search'])->name('search');
    Route::get('/search/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');

    // ─── Messages ─────────────────────────────────────────
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/inbox-json', [MessageController::class, 'inboxJson'])->name('messages.inbox-json');
    Route::get('/messages/unread-count', [MessageController::class, 'unreadCount'])->name('messages.unread-count');
    Route::get('/messages/{user}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{user}', [MessageController::class, 'send'])->name('messages.send');
    Route::get('/messages/{user}/json', [MessageController::class, 'json'])->name('messages.json');
    Route::post('/messages/{user}/read', [MessageController::class, 'markAsRead'])->name('messages.read');
    Route::post('/messages/{user}/archive', [MessageController::class, 'archiveConversation'])->name('messages.archive');
    Route::post('/messages/{user}/block', [MessageController::class, 'blockUser'])->name('messages.block');
    Route::post('/messages/{user}/report', [MessageController::class, 'reportUser'])->name('messages.report');
    Route::post('/messages/{message}/react', [MessageController::class, 'reactToMessage'])->name('messages.react');
    Route::post('/messages/{message}/forward', [MessageController::class, 'forwardMessage'])->name('messages.forward');
    Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');

    // ─── Stories ──────────────────────────────────────────
    Route::get('/stories/create', [StoryController::class, 'create'])->name('stories.create');
    Route::post('/stories', [StoryController::class, 'store'])->name('stories.store');
    Route::post('/stories/{story}/view', [StoryController::class, 'view'])->name('stories.view');
    Route::post('/stories/{story}/react', [StoryController::class, 'react'])->name('stories.react');
    Route::delete('/stories/{story}', [StoryController::class, 'destroy'])->name('stories.destroy');
    Route::get('/stories/{user}/json', [StoryController::class, 'userStories'])->name('stories.user');

    // ─── Groupes ──────────────────────────────────────────
    Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
    Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
    Route::get('/groups/{group}', [GroupController::class, 'show'])->name('groups.show');
    Route::put('/groups/{group}', [GroupController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');
    Route::post('/groups/{group}/messages', [GroupController::class, 'sendMessage'])->name('groups.messages.send');
    Route::post('/groups/{group}/messages/{message}/hide', [GroupController::class, 'hideMessage'])->name('groups.messages.hide');
    Route::delete('/groups/{group}/messages/{message}', [GroupController::class, 'deleteMessage'])->name('groups.messages.delete');
    Route::post('/groups/{group}/members', [GroupController::class, 'addMember'])->name('groups.members.add');
    Route::delete('/groups/{group}/members/{user}', [GroupController::class, 'removeMember'])->name('groups.members.remove');
    Route::put('/groups/{group}/members/{user}/role', [GroupController::class, 'changeRole'])->name('groups.members.role');
    Route::post('/groups/{group}/call-signal', [GroupController::class, 'callSignal'])->name('groups.call.signal');

    // ─── Amis en ligne ────────────────────────────────────
    Route::get('/online-friends', function () {
        try {
            $user    = Auth::user();
            $friends = $user->friends()->get()
                ->filter(fn($f) => $f->id !== $user->id && $f->isOnline())
                ->map(fn($f) => [
                    'id'     => $f->id,
                    'name'   => $f->name,
                    'avatar' => $f->avatar ? Storage::url($f->avatar) : null,
                ])->values();
            return response()->json($friends);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    })->name('online-friends');

    // ─── Humeur ───────────────────────────────────────────
    Route::post('/mood', [MoodController::class, 'update'])->name('mood.update');
    Route::get('/mood/list', [MoodController::class, 'moods'])->name('mood.list');
});