<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Follow;
use Illuminate\Support\Facades\Auth;

class FollowController extends Controller
{
    // Suit ou ne suit plus un utilisateur
    public function toggle(User $user)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        // On ne peut pas se suivre soi-même
        if ($authUser->id === $user->id) {
            return back()->withErrors(['error' => 'Vous ne pouvez pas vous suivre vous-même.']);
        }

        $existing = Follow::where('follower_id', $authUser->id)
                          ->where('following_id', $user->id)
                          ->first();

        if ($existing) {
            $existing->delete(); // Unfollow
        } else {
            Follow::create([
                'follower_id'  => $authUser->id,
                'following_id' => $user->id,
            ]);
        }

        return back();
    }
}