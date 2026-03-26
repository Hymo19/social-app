<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Like;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    // Like si pas encore liké, Unlike si déjà liké
    public function toggle(Post $post)
    {
        $existing = Like::where('user_id', Auth::id())
                        ->where('post_id', $post->id)
                        ->first();

        if ($existing) {
            $existing->delete(); // Unlike
        } else {
            Like::create([
                'user_id' => Auth::id(),
                'post_id' => $post->id,
            ]);
        }

        return back();
    }
}