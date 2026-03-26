<?php
namespace App\Http\Controllers;

use App\Models\PhotoReaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhotoReactionController extends Controller
{
    public function toggle(User $user, Request $request)
    {
        $request->validate([
            'reaction'   => 'required|string',
            'photo_type' => 'required|in:profile,cover',
        ]);

        $authId   = Auth::id();
        $existing = PhotoReaction::where('user_id', $authId)
                                 ->where('profile_user_id', $user->id)
                                 ->where('photo_type', $request->photo_type)
                                 ->first();

        if ($existing) {
            if ($existing->reaction === $request->reaction) {
                $existing->delete();
                $myReaction = null;
            } else {
                $existing->update(['reaction' => $request->reaction]);
                $myReaction = $request->reaction;
            }
        } else {
            PhotoReaction::create([
                'user_id'         => $authId,
                'profile_user_id' => $user->id,
                'photo_type'      => $request->photo_type,
                'reaction'        => $request->reaction,
            ]);
            $myReaction = $request->reaction;
        }

        $reactions = PhotoReaction::where('profile_user_id', $user->id)
            ->where('photo_type', $request->photo_type)
            ->get()
            ->groupBy('reaction')
            ->map->count();

        return response()->json([
            'success'     => true,
            'reactions'   => $reactions,
            'my_reaction' => $myReaction,
        ]);
    }
}