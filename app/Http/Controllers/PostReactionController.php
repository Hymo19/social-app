<?php
namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostReaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostReactionController extends Controller
{
    public function toggle(Post $post, Request $request)
    {
        $request->validate(['reaction' => 'required|string']);
        $user     = Auth::user();
        $existing = PostReaction::where('user_id', $user->id)
                                ->where('post_id', $post->id)->first();

        if ($existing) {
            if ($existing->reaction === $request->reaction) {
                $existing->delete();
                $myReaction = null;
            } else {
                $existing->update(['reaction' => $request->reaction]);
                $myReaction = $request->reaction;
            }
        } else {
            PostReaction::create([
                'user_id'  => $user->id,
                'post_id'  => $post->id,
                'reaction' => $request->reaction,
            ]);
            $myReaction = $request->reaction;
        }

        $reactions = PostReaction::where('post_id', $post->id)
            ->get()->groupBy('reaction')->map->count();

        return response()->json([
            'success'     => true,
            'reactions'   => $reactions,
            'my_reaction' => $myReaction,
            'total'       => PostReaction::where('post_id', $post->id)->count(),
        ]);
    }
}