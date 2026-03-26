<?php

namespace App\Http\Controllers;

use App\Models\FriendRequest;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    // ── Liste des conversations ───────────────────────────
    public function index()
    {
        $authUser = User::findOrFail(Auth::id());

        $archivedIds = DB::table('archived_conversations')
            ->where('user_id', $authUser->id)
            ->pluck('contact_id')->toArray();

        $conversations = User::whereIn('id', function ($query) use ($authUser) {
            $query->select('sender_id')->from('messages')->where('receiver_id', $authUser->id)
                  ->union(DB::table('messages')->select('receiver_id')->where('sender_id', $authUser->id));
        })->get()->map(function ($user) use ($authUser, $archivedIds) {
            $user->lastMessage = Message::where(function ($q) use ($authUser, $user) {
                $q->where('sender_id', $authUser->id)->where('receiver_id', $user->id);
            })->orWhere(function ($q) use ($authUser, $user) {
                $q->where('sender_id', $user->id)->where('receiver_id', $authUser->id);
            })->latest()->first();

            $user->unreadCount = Message::where('sender_id', $user->id)
                ->where('receiver_id', $authUser->id)
                ->where('is_read', false)->count();

            $user->isArchived = in_array($user->id, $archivedIds);
            return $user;
        })->sortByDesc(fn($u) => $u->lastMessage?->created_at);

        return view('messages.index', compact('conversations'));
    }

    // ── Conversation avec un utilisateur ─────────────────
    public function show(User $user)
    {
        $authUser = Auth::user();

        Message::where('sender_id', $user->id)
               ->where('receiver_id', $authUser->id)
               ->where('is_read', false)
               ->update(['is_read' => true]);

        $messages = Message::with('reactions')
            ->where(function ($q) use ($authUser, $user) {
                $q->where('sender_id', $authUser->id)->where('receiver_id', $user->id);
            })->orWhere(function ($q) use ($authUser, $user) {
                $q->where('sender_id', $user->id)->where('receiver_id', $authUser->id);
            })->oldest()->get();

        $friends = $authUser->friends()->where('id', '!=', $user->id)->get();

        return view('messages.show', compact('user', 'messages', 'friends'));
    }

    // ── Envoie un message ─────────────────────────────────
    public function send(Request $request, User $user)
    {
        $request->validate([
            'content' => 'nullable|max:1000',
            'image'   => 'nullable|image|max:4096',
        ]);

        if (!$request->filled('content') && !$request->hasFile('image')) {
            return response()->json(['error' => 'Message vide'], 422);
        }

        $isBlockedByReceiver = DB::table('blocked_users')
            ->where('user_id', $user->id)->where('blocked_user_id', Auth::id())->exists();
        if ($isBlockedByReceiver) {
            return response()->json(['error' => 'Vous ne pouvez pas envoyer de message à cette personne.'], 403);
        }

        $iBlockedThem = DB::table('blocked_users')
            ->where('user_id', Auth::id())->where('blocked_user_id', $user->id)->exists();
        if ($iBlockedThem) {
            return response()->json(['error' => 'Vous avez bloqué cette personne.'], 403);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('messages', 'public');
        }

        $message = Message::create([
            'sender_id'   => Auth::id(),
            'receiver_id' => $user->id,
            'content'     => $request->content,
            'image'       => $imagePath,
            'is_read'     => false,
        ]);

        return $request->expectsJson() ? response()->json($message) : back();
    }

    // ── Supprime un message ───────────────────────────────
    public function destroy(Message $message)
    {
        if ($message->sender_id === Auth::id()) {
            $message->delete();
        }
        return back()->with('success', 'Message supprimé.');
    }

    // ── JSON messages (pour messages/show) ───────────────
    public function json(User $user)
    {
        $authUser = Auth::user();

        $messages = Message::where(function ($q) use ($authUser, $user) {
                $q->where('sender_id', $authUser->id)->where('receiver_id', $user->id);
            })->orWhere(function ($q) use ($authUser, $user) {
                $q->where('sender_id', $user->id)->where('receiver_id', $authUser->id);
            })
            ->with(['reactions', 'sender'])
            ->oldest()
            ->get()
            ->map(function ($m) use ($authUser, $user) {

                // ✅ Bidirectionnel : détecte story_public peu importe qui a envoyé
                $isStoryPublic = str_starts_with($m->content ?? '', '📖 [story_public]');

                $isFriend       = false;
                $pendingRequest = false;

                if ($isStoryPublic) {
                    $isFriend = $authUser->friends()->where('id', $user->id)->exists();

                    if (!$isFriend) {
                        // Est-ce que le contact a envoyé une demande à moi ?
                        $theirRequest = FriendRequest::where('sender_id', $user->id)
                            ->where('receiver_id', $authUser->id)
                            ->where('status', 'pending')->first();

                        // Est-ce que moi j'ai envoyé une demande au contact ?
                        $myRequest = FriendRequest::where('sender_id', $authUser->id)
                            ->where('receiver_id', $user->id)
                            ->where('status', 'pending')->first();

                        if ($theirRequest) {
                            $pendingRequest = ['id' => $theirRequest->id, 'direction' => 'received'];
                        } elseif ($myRequest) {
                            $pendingRequest = ['id' => $myRequest->id, 'direction' => 'sent'];
                        }
                    }
                }

                return [
                    'id'                => $m->id,
                    'sender_id'         => $m->sender_id,
                    'content'           => $m->content,
                    'image'             => $m->image,
                    'is_read'           => $m->is_read,
                    'created_at'        => $m->created_at,
                    'forwarded_from_id' => $m->forwarded_from_id ?? null,
                    'reactions'         => $m->reactions->map(fn($r) => [
                        'reaction' => $r->reaction,
                        'user_id'  => $r->user_id,
                    ]),
                    'is_story_public'   => $isStoryPublic,
                    'is_friend'         => $isFriend,
                    'pending_request'   => $pendingRequest,
                    'sender_name'       => $m->sender->name ?? null,
                    'sender_id_contact' => $user->id,
                    'sender_avatar'     => $m->sender?->avatar
                                              ? Storage::url($m->sender->avatar)
                                              : null,
                ];
            });

        Message::where('sender_id', $user->id)
            ->where('receiver_id', $authUser->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    // ── Marque comme lu ───────────────────────────────────
    public function markAsRead(User $user)
    {
        Message::where('sender_id', $user->id)
               ->where('receiver_id', Auth::id())
               ->where('is_read', false)
               ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    // ── Unread count ──────────────────────────────────────
    public function unreadCount()
    {
        $authUser = User::findOrFail(Auth::id());
        $last     = $authUser->receivedMessages()->where('is_read', false)->latest()->first();

        return response()->json([
            'count'   => $authUser->unreadMessagesCount(),
            'last_id' => $last?->id,
        ]);
    }

    // ── Inbox JSON (pour messages/index) ─────────────────
    public function inboxJson()
    {
        $authUser = User::findOrFail(Auth::id());

        $conversations = Message::where('sender_id', $authUser->id)
            ->orWhere('receiver_id', $authUser->id)
            ->with(['sender', 'receiver'])
            ->latest()
            ->get()
            ->groupBy(function ($m) use ($authUser) {
                return $m->sender_id === $authUser->id ? $m->receiver_id : $m->sender_id;
            })
            ->map(function ($messages) use ($authUser) {
                $last      = $messages->first();
                $contactId = $last->sender_id === $authUser->id ? $last->receiver_id : $last->sender_id;
                $contact   = $last->sender_id === $authUser->id ? $last->receiver : $last->sender;
                $unread    = $messages->where('receiver_id', $authUser->id)->where('is_read', false)->count();

                $isArchived = DB::table('archived_conversations')
                    ->where('user_id', $authUser->id)->where('contact_id', $contactId)->exists();

                // ✅ Bidirectionnel : détecte story_public peu importe qui a envoyé
                // Vérifie dans TOUS les messages de la conversation, pas que le dernier
                $hasStoryPublic = $messages->contains(function ($m) {
                    return str_starts_with($m->content ?? '', '📖 [story_public]');
                });

                $isFriend       = false;
                $pendingRequest = null;
                $theyRequested  = false;

                if ($hasStoryPublic) {
                    $isFriend = $authUser->friends()->where('id', $contactId)->exists();

                    if (!$isFriend) {
                        // Eux → moi
                        $theirRequest = FriendRequest::where('sender_id', $contactId)
                            ->where('receiver_id', $authUser->id)
                            ->where('status', 'pending')->first();

                        // Moi → eux
                        $myRequest = FriendRequest::where('sender_id', $authUser->id)
                            ->where('receiver_id', $contactId)
                            ->where('status', 'pending')->first();

                        if ($theirRequest) {
                            $pendingRequest = ['id' => $theirRequest->id, 'direction' => 'received'];
                            $theyRequested  = true;
                        } elseif ($myRequest) {
                            $pendingRequest = ['id' => $myRequest->id, 'direction' => 'sent'];
                        }
                    }
                }

                return [
                    'id'                    => $contactId,
                    'name'                  => $contact->name ?? 'Inconnu',
                    'avatar'                => $contact->avatar ? Storage::url($contact->avatar) : null,
                    'status'                => $contact->status ?? 'offline',
                    'last_message'          => $last->content
                                                ? Str::limit(str_replace('📖 [story_public] ', '📖 ', $last->content), 55)
                                                : null,
                    'last_message_mine'     => $last->sender_id === $authUser->id,
                    'last_message_is_image' => !$last->content && $last->image,
                    'last_message_time'     => $last->created_at->diffForHumans(),
                    'unread_count'          => $unread,
                    'is_archived'           => $isArchived,
                    // ✅ is_story_public = true pour les DEUX côtés
                    'is_story_public'       => $hasStoryPublic && !$isFriend,
                    'is_friend'             => $isFriend,
                    'pending_request'       => $pendingRequest,
                    'they_requested'        => $theyRequested,
                    'sender_id'             => $last->sender_id,
                ];
            })
            ->values();

        return response()->json([
            'conversations' => $conversations,
            'unread_total'  => $conversations->sum('unread_count'),
        ]);
    }

    // ── Archive / Désarchive ──────────────────────────────
    public function archiveConversation(User $user)
    {
        $authId = Auth::id();
        $exists = DB::table('archived_conversations')
            ->where('user_id', $authId)->where('contact_id', $user->id)->exists();

        if ($exists) {
            DB::table('archived_conversations')
                ->where('user_id', $authId)->where('contact_id', $user->id)->delete();
            return response()->json(['status' => 'unarchived']);
        }

        DB::table('archived_conversations')->insert([
            'user_id' => $authId, 'contact_id' => $user->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return response()->json(['status' => 'archived']);
    }

    // ── Bloquer / Débloquer ───────────────────────────────
    public function blockUser(User $user)
    {
        $authId = Auth::id();
        $exists = DB::table('blocked_users')
            ->where('user_id', $authId)->where('blocked_user_id', $user->id)->exists();

        if ($exists) {
            DB::table('blocked_users')
                ->where('user_id', $authId)->where('blocked_user_id', $user->id)->delete();
            return response()->json(['status' => 'unblocked']);
        }

        DB::table('blocked_users')->insert([
            'user_id' => $authId, 'blocked_user_id' => $user->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return response()->json(['status' => 'blocked']);
    }

    // ── Signaler ──────────────────────────────────────────
    public function reportUser(Request $request, User $user)
    {
        $request->validate([
            'reason'      => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $alreadyReported = DB::table('user_reports')
            ->where('reporter_id', Auth::id())->where('reported_id', $user->id)->exists();

        if (!$alreadyReported) {
            DB::table('user_reports')->insert([
                'reporter_id' => Auth::id(), 'reported_id' => $user->id,
                'reason'      => $request->reason, 'description' => $request->description,
                'created_at'  => now(), 'updated_at' => now(),
            ]);
        }

        $totalReports = DB::table('user_reports')
            ->where('reported_id', $user->id)->distinct('reporter_id')->count();

        if ($totalReports >= 4) {
            $alreadyBlocked = DB::table('blocked_users')
                ->where('user_id', Auth::id())->where('blocked_user_id', $user->id)->exists();
            if (!$alreadyBlocked) {
                DB::table('blocked_users')->insert([
                    'user_id' => Auth::id(), 'blocked_user_id' => $user->id,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            return response()->json(['success' => true, 'auto_blocked' => true]);
        }

        return response()->json(['success' => true, 'auto_blocked' => false]);
    }

    // ── Réaction à un message ─────────────────────────────
    public function reactToMessage(Request $request, Message $message)
    {
        $request->validate(['reaction' => 'required|string|max:10']);
        $authId   = Auth::id();
        $existing = MessageReaction::where('message_id', $message->id)->where('user_id', $authId)->first();

        if ($existing) {
            if ($existing->reaction === $request->reaction) {
                $existing->delete();
                return response()->json(['status' => 'removed']);
            }
            $existing->update(['reaction' => $request->reaction]);
            return response()->json(['status' => 'updated']);
        }

        MessageReaction::create([
            'message_id' => $message->id,
            'user_id'    => $authId,
            'reaction'   => $request->reaction,
        ]);
        return response()->json(['status' => 'added']);
    }

    // ── Transférer un message ─────────────────────────────
    public function forwardMessage(Request $request, Message $message)
    {
        $request->validate(['to_user_id' => 'required|exists:users,id']);

        Message::create([
            'sender_id'         => Auth::id(),
            'receiver_id'       => $request->to_user_id,
            'content'           => $message->content,
            'image'             => $message->image,
            'is_read'           => false,
            'forwarded_from_id' => $message->id,
        ]);

        return response()->json(['success' => true]);
    }
}