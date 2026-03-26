<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CommentController extends Controller
{
    public function store(Request $request, Post $post)
{
    $request->validate([
        'content'   => 'required|max:500',
        'parent_id' => 'nullable|exists:comments,id',
    ]);

    $comment = Comment::create([
        'user_id'   => Auth::id(),
        'post_id'   => $post->id,
        'content'   => $request->content,
        'parent_id' => $request->parent_id,
    ]);

    $comment->load('user');

    if ($request->expectsJson()) {
        return response()->json([
            'id'         => $comment->id,
            'user_name'  => $comment->user->name,
            'avatar'     => $comment->user->avatar
                                ? Storage::url($comment->user->avatar)
                                : null,
            'content'    => $comment->content,
            'created_at' => $comment->created_at->diffForHumans(),
            'reactions'  => [],
            'my_reaction'=> null,
            'replies'    => [],
            'parent_id'  => $comment->parent_id,
        ]);
    }

    return back()->with('success', 'Commentaire ajouté !');
}

    public function edit(Comment $comment)
    {
        $this->authorize('update', $comment);
        return view('comments.edit', compact('comment'));
    }

    public function update(Request $request, Comment $comment)
    {
        $this->authorize('update', $comment);

        $request->validate([
            'content' => 'required|max:500',
        ]);

        $comment->update(['content' => $request->content]);

        return redirect()->route('posts.show', $comment->post_id)
                         ->with('success', 'Commentaire modifié !');
    }

    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);
        $comment->delete();

        return back()->with('success', 'Commentaire supprimé !');
    }
}