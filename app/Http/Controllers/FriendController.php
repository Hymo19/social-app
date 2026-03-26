<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\FriendRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FriendController extends Controller
{
    // ── Envoie une demande d'ami (via formulaire) ─────────
    public function send(User $user)
    {
        $authUser = Auth::user();

        if ($authUser->id === $user->id) {
            return back()->withErrors(['error' => 'Vous ne pouvez pas vous ajouter vous-même.']);
        }

        $exists = FriendRequest::where(function ($q) use ($authUser, $user) {
            $q->where('sender_id', $authUser->id)->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($authUser, $user) {
            $q->where('sender_id', $user->id)->where('receiver_id', $authUser->id);
        })->exists();

        if (!$exists) {
            FriendRequest::create([
                'sender_id'   => $authUser->id,
                'receiver_id' => $user->id,
                'status'      => 'pending',
            ]);
        }

        return back()->with('success', 'Demande d\'ami envoyée !');
    }

    // ── Accepte une demande ───────────────────────────────
    public function accept(FriendRequest $friendRequest)
    {
        if ($friendRequest->receiver_id !== Auth::id()) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Non autorisé'], 403);
            }
            return back()->withErrors(['error' => 'Non autorisé']);
        }

        $friendRequest->update(['status' => 'accepted']);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Demande acceptée !');
    }

    // ── Refuse une demande ────────────────────────────────
    public function decline(FriendRequest $friendRequest)
    {
        if ($friendRequest->receiver_id !== Auth::id()) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Non autorisé'], 403);
            }
            return back()->withErrors(['error' => 'Non autorisé']);
        }

        $friendRequest->update(['status' => 'declined']);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Demande refusée.');
    }

    // ── Supprime un ami ───────────────────────────────────
    public function remove(User $user)
    {
        $authUser = Auth::user();

        FriendRequest::where(function ($q) use ($authUser, $user) {
            $q->where('sender_id', $authUser->id)->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($authUser, $user) {
            $q->where('sender_id', $user->id)->where('receiver_id', $authUser->id);
        })->delete();

        return back()->with('success', 'Ami supprimé.');
    }

    // ── Demandes reçues ───────────────────────────────────
    public function requests()
    {
        $authUser = Auth::user();

        $requests = FriendRequest::where('receiver_id', $authUser->id)
            ->where('status', 'pending')
            ->with('sender')
            ->latest()
            ->get();

        return view('friends.requests', compact('requests'));
    }

    // ── Liste des amis ────────────────────────────────────
    public function index()
    {
        $authUser = Auth::user();
        $friends  = $authUser->friends()->get();
        return view('friends.index', compact('friends'));
    }
}