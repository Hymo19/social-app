<?php
namespace App\Http\Controllers;

use App\Models\Story;
use App\Models\StoryView;
use App\Models\User;
use App\Models\Message;
use App\Models\FriendRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StoryController extends Controller
{
    public function create()
    {
        return view('stories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'type'         => 'required|in:image,video,text',
            'media'        => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,mov,webm|max:51200',
            'music'        => 'nullable|file|mimes:mp3,ogg,wav,m4a|max:10240',
            'music_name'   => 'nullable|string|max:100',
            'text_content' => 'nullable|string|max:300',
            'bg_color'     => 'nullable|string|max:20',
            'text_color'   => 'nullable|string|max:20',
        ]);

        $mediaPath = null;
        $musicPath = null;

        if ($request->hasFile('media')) {
            $mediaPath = $request->file('media')->store('stories', 'public');
        }
        if ($request->hasFile('music')) {
            $musicPath = $request->file('music')->store('stories/music', 'public');
        }

        Story::create([
            'user_id'      => Auth::id(),
            'type'         => $request->type,
            'media'        => $mediaPath,
            'music'        => $musicPath,
            'music_name'   => $request->music_name,
            'text_content' => $request->text_content,
            'bg_color'     => $request->bg_color ?? '#1877f2',
            'text_color'   => $request->text_color ?? '#ffffff',
            'expires_at'   => now()->addHours(24),
        ]);

        return redirect()->route('feed')->with('success', 'Story publiée !');
    }

    public function view(Story $story)
    {
        StoryView::firstOrCreate([
            'story_id' => $story->id,
            'user_id'  => Auth::id(),
        ]);
        return response()->json(['ok' => true]);
    }

    public function destroy(Story $story)
    {
        if ($story->user_id !== Auth::id()) abort(403);
        if ($story->media) Storage::disk('public')->delete($story->media);
        if ($story->music) Storage::disk('public')->delete($story->music);
        $story->delete();
        return back()->with('success', 'Story supprimée.');
    }

    public function userStories(User $user)
{
    $authUser = Auth::user();

    // ✅ Si le compte est en mode "amis uniquement" et qu'on n'est pas ami → liste vide
    if (($user->story_privacy ?? 'public') === 'friends'
        && $authUser->id !== $user->id
        && !$authUser->isFriendWith($user))
    {
        return response()->json([]);
    }

    $stories = Story::where('user_id', $user->id)
        ->active()
        ->with('user')
        ->oldest()
        ->get()
        ->map(function ($s) {
            return [
                'id'           => $s->id,
                'type'         => $s->type,
                'media_url'    => $s->media  ? Storage::url($s->media)  : null,
                'music_url'    => $s->music  ? Storage::url($s->music)  : null,
                'music_name'   => $s->music_name ?? ($s->music ? pathinfo($s->music, PATHINFO_FILENAME) : null),
                'text_content' => $s->text_content,
                'bg_color'     => $s->bg_color  ?? '#1877f2',
                'text_color'   => $s->text_color ?? '#ffffff',
                'created_at'   => $s->created_at->diffForHumans(),
                'viewed'       => $s->isViewedBy(Auth::id()),
                'is_mine'      => $s->user_id === Auth::id(),
                'views_count'  => $s->views()->count(),
                'user'         => [
                    'id'         => $s->user->id,
                    'name'       => $s->user->name,
                    'avatar_url' => $s->user->avatar ? Storage::url($s->user->avatar) : null,
                ],
            ];
        });

    return response()->json($stories);
}

    public function react(Story $story, Request $request)
    {
        $request->validate(['content' => 'required|string|max:500']);

        $sender   = Auth::user();
        $receiver = $story->user;

        if ($sender->id === $receiver->id) {
            return response()->json(['ok' => true]);
        }

        $isFriend = $sender->friends()->where('id', $receiver->id)->exists();

        $pendingRequest = FriendRequest::where(function ($q) use ($sender, $receiver) {
            $q->where('sender_id', $sender->id)->where('receiver_id', $receiver->id);
        })->orWhere(function ($q) use ($sender, $receiver) {
            $q->where('sender_id', $receiver->id)->where('receiver_id', $sender->id);
        })->first();

        $prefix = (!$isFriend) ? '📖 [story_public] ' : '📖 ';

        Message::create([
            'sender_id'   => $sender->id,
            'receiver_id' => $receiver->id,
            'content'     => $prefix . $request->content,
        ]);

        return response()->json([
            'ok'              => true,
            'is_friend'       => $isFriend,
            'pending_request' => $pendingRequest ? true : false,
        ]);
    }
}