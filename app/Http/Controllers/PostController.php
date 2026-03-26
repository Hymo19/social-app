<?php
namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Share;
use App\Models\Story;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $followingIds = $user->following()->pluck('following_id');
        $friendIds    = $user->friends()->pluck('id');
        $ids          = $followingIds->merge($friendIds)->push($user->id)->unique()->values();

        // 1. Posts normaux
        $normalPosts = Post::whereIn('user_id', $ids)
            ->whereNull('shared_post_id')
            ->where(function ($query) use ($user, $friendIds) {
                $query->where('visibility', 'public')
                      ->orWhere('user_id', $user->id)
                      ->orWhere(function ($q) use ($friendIds) {
                          $q->where('visibility', 'friends')->whereIn('user_id', $friendIds);
                      });
            })
            ->with(['user', 'likes', 'comments', 'reactions'])
            ->latest()->get();

        // 2. Reposts — charge la chaîne sur 3 niveaux
        $reposts = Post::whereIn('user_id', $ids)
            ->whereNotNull('shared_post_id')
            ->with([
                'user', 'likes', 'comments', 'reactions',
                'sharedPost.user',
                'sharedPost.likes',
                'sharedPost.comments',
                'sharedPost.reactions',
                'sharedPost.sharedPost.user',
                'sharedPost.sharedPost.likes',
                'sharedPost.sharedPost.reactions',
                'sharedPost.sharedPost.sharedPost.user',
                'sharedPost.sharedPost.sharedPost.likes',
            ])
            ->latest()->get();

        // 3. Partages reçus acceptés sur mon fil
        $myAcceptedShares = Share::where('status', 'accepted')
            ->where('destination', 'self_feed')
            ->where('recipient_type', User::class)
            ->where('recipient_id', $user->id)
            ->whereColumn('sender_id', '!=', 'recipient_id')
            ->with(['post.user','post.likes','post.comments','post.reactions','post.sharedPost.user','post.sharedPost.sharedPost.user','sender'])
            ->latest()->get();

        // 4. Partages de mes amis sur leur propre fil
        $friendsOwnShares = Share::where('status', 'accepted')
            ->where('destination', 'self_feed')
            ->whereIn('sender_id', $friendIds)
            ->where('recipient_type', User::class)
            ->whereColumn('sender_id', 'recipient_id')
            ->with(['post.user','post.likes','post.comments','post.reactions','post.sharedPost.user','post.sharedPost.sharedPost.user','sender'])
            ->latest()->get();

        $feedItems = collect();
        foreach ($normalPosts as $post) {
            $feedItems->push(['type' => 'post', 'data' => $post, 'date' => $post->created_at]);
        }
        foreach ($reposts as $repost) {
            if ($repost->sharedPost) {
                $feedItems->push(['type' => 'repost', 'data' => $repost, 'date' => $repost->created_at]);
            }
        }
        foreach ($myAcceptedShares->merge($friendsOwnShares) as $share) {
            if ($share->post) {
                $feedItems->push(['type' => 'share', 'data' => $share, 'date' => $share->accepted_at ?? $share->created_at]);
            }
        }

        $feedItems = $feedItems->sortByDesc('date')->values();

        $page    = request()->get('page', 1);
        $perPage = 10;
        $posts   = new \Illuminate\Pagination\LengthAwarePaginator(
            $feedItems->forPage($page, $perPage),
            $feedItems->count(), $perPage, $page,
            ['path' => route('feed')]
        );

        // Pré-calculer les compteurs de reposts en UNE SEULE requête pour tous les posts
        // au lieu d'appeler countAllReposts() pour chaque post (évite les N+1)
        $allPostIds = $feedItems->map(fn($item) => $item['data']->id ?? null)->filter()->unique()->values();
        $repostCounts = Post::whereIn('shared_post_id', $allPostIds)
            ->selectRaw('shared_post_id, count(*) as cnt')
            ->groupBy('shared_post_id')
            ->pluck('cnt', 'shared_post_id');

        $postsData = $feedItems
            ->filter(fn($item) => in_array($item['type'], ['post', 'repost']))
            ->map(function ($item) use ($repostCounts) {
                $p        = $item['data'];
                $isRepost = $item['type'] === 'repost';
                $actionPost = $p;

                $reactionGroups = $actionPost->reactions->groupBy('reaction')->map->count();
                $myReaction     = $actionPost->reactions->where('user_id', Auth::id())->first()?->reaction;

                $sharedPost       = $isRepost ? $p->sharedPost : null;
                $sharedPostParent = $sharedPost?->sharedPost;

                return [
                    'id'             => $p->id,
                    'type'           => $item['type'],
                    'title'          => $p->title ?? '',
                    'content'        => $p->content ?? '',
                    'image'          => $p->image ? Storage::url($p->image) : null,
                    'user_id'        => $p->user_id,
                    'user_name'      => $p->user->name,
                    'avatar'         => $p->user->avatar ? Storage::url($p->user->avatar) : null,
                    'created_at'     => $p->created_at->diffForHumans(),
                    'url'            => route('posts.show', $p),
                    'like_count'     => $actionPost->likes->count(),
                    'comment_count'  => $actionPost->comments->count(),
                    'is_liked'       => $actionPost->likes->contains('user_id', Auth::id()),
                    'like_url'       => route('likes.toggle', $actionPost),
                    'reactions'      => $reactionGroups,
                    'my_reaction'    => $myReaction,
                    'reaction_url'   => route('posts.react', $actionPost),
                    'mood_badge'     => $actionPost->getMoodBadge(),
                    // Compteur direct uniquement (pas récursif au chargement)
                    'repost_count'   => $repostCounts->get($p->id, 0),
                    'is_repost'      => $isRepost,
                    'repost_comment' => $isRepost ? ($p->content ?? '') : null,
                    'repost_user'    => $isRepost ? $p->user->name : null,
                    'repost_avatar'  => $isRepost && $p->user->avatar ? Storage::url($p->user->avatar) : null,
                    'shared_post'    => $sharedPost ? [
                        'id'          => $sharedPost->id,
                        'title'       => $sharedPost->title ?? '',
                        'content'     => $sharedPost->content ?? '',
                        'image'       => $sharedPost->image ? Storage::url($sharedPost->image) : null,
                        'user_name'   => $sharedPost->user->name ?? '',
                        'avatar'      => $sharedPost->user->avatar ? Storage::url($sharedPost->user->avatar) : null,
                        'created_at'  => $sharedPost->created_at->diffForHumans(),
                        'url'         => route('posts.show', $sharedPost),
                        'is_repost'   => (bool) $sharedPost->shared_post_id,
                        'parent_post' => $sharedPostParent ? [
                            'id'         => $sharedPostParent->id,
                            'title'      => $sharedPostParent->title ?? '',
                            'content'    => $sharedPostParent->content ?? '',
                            'image'      => $sharedPostParent->image ? Storage::url($sharedPostParent->image) : null,
                            'user_name'  => $sharedPostParent->user->name ?? '',
                            'avatar'     => $sharedPostParent->user->avatar ? Storage::url($sharedPostParent->user->avatar) : null,
                            'created_at' => $sharedPostParent->created_at->diffForHumans(),
                            'url'        => route('posts.show', $sharedPostParent),
                        ] : null,
                    ] : null,
                ];
            })->values();

        $myStories = Story::where('user_id', $user->id)->active()->count();
        $storyUsers = Story::active()->with('user')->get()
            ->groupBy('user_id')
            ->map(fn($stories) => $stories->first()->user)
            ->filter(function ($storyUser) use ($user) {
                if ($storyUser->id === $user->id) return true;
                if (($storyUser->story_privacy ?? 'public') === 'friends') return $user->isFriendWith($storyUser);
                return true;
            })->values();

        $storyUsersData = $storyUsers->map(function ($storyUser) use ($user) {
            $userStories = Story::where('user_id', $storyUser->id)->active()->with('user')->get();
            $allViewed   = $userStories->every(fn($s) => $s->isViewedBy($user->id));
            return ['user' => $storyUser, 'allViewed' => $allViewed, 'firstStory' => $userStories->first()];
        });

        return view('posts.index', compact('posts', 'postsData', 'myStories', 'storyUsers', 'storyUsersData'));
    }

    public function create() { return view('posts.create'); }

    public function store(Request $request)
    {
        $request->validate([
            'title'      => 'required|max:255',
            'content'    => 'required',
            'image'      => 'nullable|image|max:2048',
            'visibility' => 'required|in:public,friends,private',
            'mood'       => 'nullable|string|in:joie,colere,danse,furieux,triste,musique',
        ]);
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('posts', 'public');
        }
        Post::create([
            'user_id'    => Auth::id(),
            'title'      => $request->title,
            'content'    => $request->content,
            'image'      => $imagePath,
            'visibility' => $request->visibility,
            'mood'       => $request->mood,
        ]);
        return redirect()->route('feed')->with('success', 'Post publié !');
    }

    public function show(Post $post)
    {
        $post->load([
            'user', 'likes', 'reactions',
            'sharedPost.user',
            'sharedPost.sharedPost.user',
            'comments' => function ($q) {
                $q->whereNull('parent_id')
                  ->with(['user', 'reactions.user', 'replies' => function ($r) {
                      $r->with(['user', 'reactions.user'])->oldest();
                  }])->oldest();
            }
        ]);
        $mentionUsers = $post->comments->flatMap(function ($c) {
            return collect([$c->user])->merge($c->replies->pluck('user'));
        })->unique('id')->keyBy('name');
        return view('posts.show', compact('post', 'mentionUsers'));
    }

    public function edit(Post $post)
    {
        $this->authorize('update', $post);
        return view('posts.edit', compact('post'));
    }

    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);
        $request->validate([
            'title'      => 'required|max:255',
            'content'    => 'required',
            'image'      => 'nullable|image|max:2048',
            'visibility' => 'required|in:public,friends,private',
        ]);
        $imagePath = $post->image;
        if ($request->hasFile('image')) {
            if ($post->image) Storage::disk('public')->delete($post->image);
            $imagePath = $request->file('image')->store('posts', 'public');
        }
        $post->update([
            'title'      => $request->title,
            'content'    => $request->content,
            'image'      => $imagePath,
            'visibility' => $request->visibility,
        ]);
        return redirect()->route('posts.show', $post)->with('success', 'Post modifié !');
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);
        if ($post->image) Storage::disk('public')->delete($post->image);
        $post->delete();
        return redirect()->route('feed')->with('success', 'Post supprimé !');
    }

    // ── Liste des personnes qui ont réagi ─────────────────────
    public function reactionsList(Post $post)
    {
        $reactions = $post->reactions()->with('user')->latest()->get()
            ->map(fn($r) => [
                'user_name'   => $r->user->name,
                'avatar'      => $r->user->avatar ? Storage::url($r->user->avatar) : null,
                'profile_url' => route('profile.show', $r->user),
                'reaction'    => $r->reaction,
                'created_at'  => $r->created_at->diffForHumans(),
            ]);
        $likes = $post->likes()->with('user')->latest()->get()
            ->map(fn($l) => [
                'user_name'   => $l->user->name,
                'avatar'      => $l->user->avatar ? Storage::url($l->user->avatar) : null,
                'profile_url' => route('profile.show', $l->user),
                'reaction'    => '👍',
                'created_at'  => $l->created_at->diffForHumans(),
            ]);
        return response()->json($reactions->merge($likes)->values());
    }

    // ── Liste des personnes qui ont republié (directs + toute la chaîne) ──
    public function repostersList(Post $post)
    {
        // BFS pour récupérer tous les reposts de la chaîne en quelques requêtes
        $allIds   = collect([$post->id]);
        $toCheck  = collect([$post->id]);
        $depth    = 0;
        while ($toCheck->isNotEmpty() && $depth < 6) {
            $children = Post::whereIn('shared_post_id', $toCheck)->pluck('id');
            $toCheck  = $children->diff($allIds);
            $allIds   = $allIds->merge($children);
            $depth++;
        }

        $reposts = Post::whereIn('id', $allIds->diff([$post->id]))
            ->with('user')->latest()->get()
            ->map(fn($r) => [
                'user_name'   => $r->user->name,
                'avatar'      => $r->user->avatar ? Storage::url($r->user->avatar) : null,
                'profile_url' => route('profile.show', $r->user),
                'comment'     => $r->content,
                'created_at'  => $r->created_at->diffForHumans(),
            ]);
        return response()->json($reposts);
    }

    // ── Liste des personnes qui ont partagé ───────────────────
    public function sharersList(Post $post)
    {
        $shares = Share::where('post_id', $post->id)
            ->where('status', 'accepted')
            ->with('sender')->latest()->get()
            ->map(fn($s) => [
                'sender_name' => $s->sender->name,
                'avatar'      => $s->sender->avatar ? Storage::url($s->sender->avatar) : null,
                'profile_url' => route('profile.show', $s->sender),
                'created_at'  => $s->created_at->diffForHumans(),
            ]);
        return response()->json($shares);
    }
}