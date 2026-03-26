<?php
namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\PhotoReaction;
use App\Models\Post;
use App\Models\Share;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(User $user)
    {
        $authUser = Auth::user();

        // 1. Posts normaux de l'utilisateur (sans shared_post_id)
        $normalPosts = $user->posts()
            ->with(['likes', 'comments', 'reactions', 'user'])
            ->whereNull('shared_post_id')
            ->latest()
            ->get()
            ->map(fn($p) => ['type' => 'post', 'data' => $p, 'sort_date' => $p->created_at]);

        // 2. Reposts faits par l'utilisateur (posts avec shared_post_id)
        $reposts = $user->posts()
            ->with(['likes', 'comments', 'reactions', 'user', 'sharedPost.user', 'sharedPost.likes', 'sharedPost.comments', 'sharedPost.reactions'])
            ->whereNotNull('shared_post_id')
            ->latest()
            ->get()
            ->map(fn($p) => ['type' => 'repost', 'data' => $p, 'sort_date' => $p->created_at]);

        // 3. Partages acceptés sur le profil de l'utilisateur
        //    (destination = self_profile OU sender_id = recipient_id)
        $profileShares = Share::with(['post.user', 'post.likes', 'post.comments', 'post.reactions', 'sender'])
            ->where('recipient_id', $user->id)
            ->where('status', 'accepted')
            ->where(function($q) use ($user) {
                $q->where('destination', 'self_profile')
                  ->orWhere(function($q2) use ($user) {
                      $q2->where('sender_id', $user->id)
                         ->where('recipient_id', $user->id);
                  });
            })
            ->where('sender_id', '!=', $user->id) // exclure les auto-partages (reposts gérés ci-dessus)
            ->latest()
            ->get()
            ->map(fn($s) => ['type' => 'share', 'data' => $s, 'sort_date' => $s->created_at]);

        // Fusionner et trier par date décroissante
        $allItems = $normalPosts
            ->concat($reposts)
            ->concat($profileShares)
            ->sortByDesc('sort_date')
            ->values();

        $followers = $user->followers()->count();
        $following = $user->following()->count();
        $friends   = $user->friends()->get();

        $isFollowing = $authUser->following()->where('following_id', $user->id)->exists();

        $profileReactions = PhotoReaction::where('profile_user_id', $user->id)
            ->where('photo_type', 'profile')
            ->get()->groupBy('reaction')->map->count();

        $coverReactions = PhotoReaction::where('profile_user_id', $user->id)
            ->where('photo_type', 'cover')
            ->get()->groupBy('reaction')->map->count();

        $myProfileReaction = PhotoReaction::where('user_id', $authUser->id)
            ->where('profile_user_id', $user->id)
            ->where('photo_type', 'profile')
            ->first()?->reaction;

        $myCoverReaction = PhotoReaction::where('user_id', $authUser->id)
            ->where('profile_user_id', $user->id)
            ->where('photo_type', 'cover')
            ->first()?->reaction;

        // Garder $posts pour compatibilité (stats)
        $posts = $user->posts()->whereNull('shared_post_id')->get();

        return view('profile.show', compact(
            'user', 'posts', 'allItems', 'followers', 'following', 'friends',
            'isFollowing',
            'profileReactions', 'coverReactions',
            'myProfileReaction', 'myCoverReaction'
        ));
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $request->validate([
            'name'          => 'required|max:255',
            'bio'           => 'nullable|max:500',
            'location'      => 'nullable|max:255',
            'birth_date'    => 'nullable|date',
            'avatar'        => 'nullable|image|max:2048',
            'story_privacy' => 'nullable|in:public,friends',
        ]);

        $data = $request->only(['name', 'bio', 'location', 'birth_date', 'story_privacy']);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) Storage::disk('public')->delete($user->avatar);
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');

            Activity::create([
                'user_id' => $user->id,
                'type'    => 'profile_photo',
                'data'    => ['avatar' => $data['avatar']],
            ]);
        }

        $user->update($data);
        return redirect()->route('profile.show', $user)->with('success', 'Profil mis à jour !');
    }

    public function updateCover(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $request->validate(['cover' => 'required|image|max:4096']);

        if ($user->cover_photo) Storage::disk('public')->delete($user->cover_photo);
        $path = $request->file('cover')->store('covers', 'public');
        $user->update(['cover_photo' => $path]);

        Activity::create([
            'user_id' => $user->id,
            'type'    => 'cover_photo',
            'data'    => ['cover_photo' => $path],
        ]);

        return redirect()->route('profile.show', $user)->with('success', 'Photo de couverture mise à jour !');
    }

    public function updateStatus(Request $request)
    {
        $request->validate(['status' => 'required|in:online,away,busy,offline']);
        Auth::user()->update([
            'status'       => $request->status,
            'last_seen_at' => now(),
        ]);
        return response()->json(['success' => true]);
    }

    public function users()
    {
        $users = User::where('id', '!=', Auth::id())->latest()->get();
        return view('profile.users', compact('users'));
    }
}