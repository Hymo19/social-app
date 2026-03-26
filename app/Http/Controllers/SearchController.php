<?php
namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Post;
use App\Models\Share;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShareController extends Controller
{
    // ── Page PARTAGER (amis + groupes uniquement) ──
    public function create(Post $post)
    {
        $user    = Auth::user();
        $friends = $user->friends()->get();
        $groups  = method_exists($user, 'groups') ? $user->groups()->get() : collect();
        return view('shares.create', compact('post', 'friends', 'groups'));
    }

    // ── Page REPUBLIER (style Twitter) ──
    public function showRepost(Post $post)
    {
        $user = Auth::user();

        // Charger la chaîne pour l'affichage dans la vue
        $post->load(['user', 'sharedPost.user', 'sharedPost.sharedPost.user']);

        // Vérifier qu'on ne republie pas son propre post (remonter au post racine)
        $root = $this->getRootPost($post);
        if ($root->user_id === $user->id) {
            return redirect()->back()->with('error', 'Vous ne pouvez pas republier votre propre post.');
        }

        $alreadyReposted = Post::where('user_id', $user->id)
            ->where('shared_post_id', $post->id)
            ->exists();

        return view('shares.repost', compact('post', 'alreadyReposted'));
    }

    // ── Republier sur son propre feed ──
    public function repost(Request $request, Post $post)
    {
        $request->validate([
            'shared_comment' => 'nullable|string|max:500',
            'visibility'     => 'nullable|in:public,friends,private',
        ]);

        $user = Auth::user();
        $originalPost = $this->getRootPost($post);

        if ($originalPost->user_id === $user->id) {
            return back()->with('error', 'Vous ne pouvez pas republier votre propre post.');
        }

        // shared_post_id pointe vers le POST QU'ON REPUBLIE
        // (peut être un repost lui-même → conserve la chaîne)
        Post::create([
            'user_id'        => $user->id,
            'title'          => '',
            'content'        => $request->shared_comment ?? '',
            'image'          => null,
            'visibility'     => $request->visibility ?? 'public',
            'shared_post_id' => $post->id,
            'shared_comment' => $request->shared_comment,
        ]);

        return redirect()->route('feed')
            ->with('success', "✅ Post republié sur votre fil d'actualité !");
    }

    // ── Remonter au post racine (sans chaîne infinie) ──
    private function getRootPost(Post $post, int $depth = 0): Post
    {
        if ($depth > 10 || !$post->shared_post_id) return $post;
        $parent = Post::find($post->shared_post_id);
        if (!$parent) return $post;
        return $this->getRootPost($parent, $depth + 1);
    }

    // ── Envoyer le partage à des amis / groupes ──
    public function store(Request $request, Post $post)
    {
        $request->validate([
            'recipients'   => 'required|array|min:1',
            'recipients.*' => 'string',
            'message'      => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $sent = 0;

        foreach ($request->recipients as $recipientKey) {
            if (str_starts_with($recipientKey, 'user_')) {
                [, $id] = explode('_', $recipientKey, 2);
                $recipient = User::find($id);
                if (!$recipient) continue;

                $exists = Share::where('post_id', $post->id)
                    ->where('sender_id', $user->id)
                    ->where('recipient_type', User::class)
                    ->where('recipient_id', $id)
                    ->where('status', 'pending')
                    ->exists();

                if (!$exists) {
                    Share::create([
                        'post_id'        => $post->id,
                        'sender_id'      => $user->id,
                        'recipient_type' => User::class,
                        'recipient_id'   => $id,
                        'message'        => $request->message,
                        'status'         => 'pending',
                        'destination'    => 'friend',
                    ]);
                    $sent++;
                }
            } elseif (str_starts_with($recipientKey, 'group_')) {
                [, $id] = explode('_', $recipientKey, 2);
                $group = Group::find($id);
                if (!$group) continue;

                Share::create([
                    'post_id'        => $post->id,
                    'sender_id'      => $user->id,
                    'recipient_type' => Group::class,
                    'recipient_id'   => $id,
                    'message'        => $request->message,
                    'status'         => 'accepted',
                    'destination'    => 'group',
                    'accepted_at'    => now(),
                ]);
                $sent++;
            }
        }

        if ($sent > 0) {
            return redirect()->route('feed')->with('success', "✅ Post partagé avec $sent ami(s)/groupe(s) !");
        }
        return back()->with('error', 'Aucun partage envoyé (déjà en attente ?).');
    }

    public function received()
    {
        $user = Auth::user();
        $pending = Share::where('recipient_type', User::class)->where('recipient_id', $user->id)
            ->where('status', 'pending')->with(['post.user', 'sender'])->latest()->get();
        $accepted = Share::where('recipient_type', User::class)->where('recipient_id', $user->id)
            ->where('status', 'accepted')->with(['post.user', 'sender'])->latest()->take(20)->get();
        return view('shares.received', compact('pending', 'accepted'));
    }

    public function accept(Share $share, Request $request)
    {
        $this->authorize('update', $share);
        $destination = $request->input('destination', 'self_feed');
        if (!in_array($destination, ['self_feed', 'self_profile'])) $destination = 'self_feed';
        $share->update(['status' => 'accepted', 'destination' => $destination, 'accepted_at' => now()]);
        $msg = $destination === 'self_profile' ? '✅ Post ajouté à votre profil !' : "✅ Post ajouté à votre fil d'actualité !";
        return back()->with('success', $msg);
    }

    public function decline(Share $share)
    {
        $this->authorize('update', $share);
        $share->update(['status' => 'declined']);
        return back()->with('success', 'Partage refusé.');
    }

    public function sent()
    {
        $user = Auth::user();
        $shares = Share::where('sender_id', $user->id)->with(['post', 'recipient'])->latest()->paginate(20);
        return view('shares.sent', compact('shares'));
    }
}