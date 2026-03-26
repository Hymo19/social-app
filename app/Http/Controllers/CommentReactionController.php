<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentReaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentReactionController extends Controller
{
    public function toggle(Request $request, Comment $comment)
    {
        $request->validate(['reaction' => 'required|string']);

        $existing = CommentReaction::where('user_id', Auth::id())
                                   ->where('comment_id', $comment->id)
                                   ->first();

        if ($existing) {
            if ($existing->reaction === $request->reaction) {
                $existing->delete();
                $userReaction = null;
            } else {
                $existing->update(['reaction' => $request->reaction]);
                $userReaction = $request->reaction;
            }
        } else {
            CommentReaction::create([
                'user_id'    => Auth::id(),
                'comment_id' => $comment->id,
                'reaction'   => $request->reaction,
            ]);
            $userReaction = $request->reaction;
        }

        $comment->load('reactions.user');
        $counts = $comment->getReactionCounts();

        $names = $comment->reactions
                         ->groupBy('reaction')
                         ->map(fn($group) => $group->pluck('user.name')->toArray());

        return response()->json([
            'counts'        => $counts,
            'total'         => $comment->reactions->count(),
            'user_reaction' => $userReaction,
            'names'         => $names,
        ]);
    }
}