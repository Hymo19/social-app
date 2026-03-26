<?php
namespace App\Http\Controllers;

use App\Events\GroupCallSignal;
use App\Events\GroupMessageSent;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    public function index()
    {
        $groups = Group::whereHas('members', fn($q) => $q->where('user_id', Auth::id()))
            ->with(['creator', 'members.user'])
            ->latest()
            ->get();

        return view('groups.index', compact('groups'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:300',
            'avatar'      => 'nullable|image|max:4096',
            'members'     => 'nullable|array',
            'members.*'   => 'exists:users,id',
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('groups', 'public');
        }

        $group = Group::create([
            'creator_id'  => Auth::id(),
            'name'        => $request->name,
            'description' => $request->description,
            'avatar'      => $avatarPath,
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id'  => Auth::id(),
            'role'     => 'admin',
        ]);

        foreach (($request->members ?? []) as $userId) {
            if ($userId != Auth::id()) {
                GroupMember::create([
                    'group_id' => $group->id,
                    'user_id'  => $userId,
                    'role'     => 'member',
                ]);
            }
        }

        return redirect()->route('groups.show', $group)->with('success', 'Groupe créé !');
    }

   public function show(Group $group)
{
    abort_unless($group->isMember(Auth::id()), 403);

    $messages = GroupMessage::where('group_id', $group->id)
        ->whereNotIn('id', function($q) {
            $q->select('group_message_id')
              ->from('group_message_hides')
              ->where('user_id', Auth::id());
        })
        ->with('user')
        ->oldest()
        ->get();

    $members  = $group->users()->get();
    $allUsers = User::whereNotIn('id', $members->pluck('id'))->get();

    return view('groups.show', compact('group', 'messages', 'members', 'allUsers'));
}
    public function sendMessage(Request $request, Group $group)
    {
        abort_unless($group->isMember(Auth::id()), 403);

        $request->validate([
            'content' => 'nullable|string|max:2000',
            'image'   => 'nullable|image|max:10240',
            'type'    => 'nullable|string',
        ]);

        $imagePath = null;
        $type = $request->type ?? 'text';

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('group-messages', 'public');
            $type = 'image';
        }

        $msg = GroupMessage::create([
            'group_id' => $group->id,
            'user_id'  => Auth::id(),
            'content'  => $request->content,
            'image'    => $imagePath,
            'type'     => $type,
        ]);

        broadcast(new GroupMessageSent($msg))->toOthers();

        return response()->json([
            'id'         => $msg->id,
            'content'    => $msg->content,
            'image_url'  => $msg->image_url,
            'type'       => $msg->type,
            'created_at' => $msg->created_at->diffForHumans(),
            'user' => [
                'id'         => Auth::user()->id,
                'name'       => Auth::user()->name,
                'avatar_url' => Auth::user()->avatar
                    ? Storage::url(Auth::user()->avatar)
                    : null,
            ],
        ]);
    }

    public function addMember(Request $request, Group $group)
    {
        abort_unless($group->isAdmin(Auth::id()), 403);
        $request->validate(['user_id' => 'required|exists:users,id']);

        GroupMember::firstOrCreate([
            'group_id' => $group->id,
            'user_id'  => $request->user_id,
        ], ['role' => 'member']);

        return back()->with('success', 'Membre ajouté !');
    }

    public function removeMember(Group $group, User $user)
    {
        abort_unless($group->isAdmin(Auth::id()) || $user->id === Auth::id(), 403);

        GroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->delete();

        return back()->with('success', 'Membre retiré.');
    }

    public function changeRole(Request $request, Group $group, User $user)
    {
        abort_unless($group->isAdmin(Auth::id()), 403);
        $request->validate(['role' => 'required|in:admin,member']);

        GroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->update(['role' => $request->role]);

        return back()->with('success', 'Rôle mis à jour.');
    }

    public function update(Request $request, Group $group)
    {
        abort_unless($group->isAdmin(Auth::id()), 403);
        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:300',
            'avatar'      => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('avatar')) {
            if ($group->avatar) Storage::disk('public')->delete($group->avatar);
            $group->avatar = $request->file('avatar')->store('groups', 'public');
        }

        $group->update([
            'name'        => $request->name,
            'description' => $request->description,
        ]);

        return back()->with('success', 'Groupe mis à jour !');
    }

    public function destroy(Group $group)
    {
        abort_unless($group->isAdmin(Auth::id()), 403);

        if ($group->avatar) Storage::disk('public')->delete($group->avatar);
        $group->delete();

        return redirect()->route('groups.index')->with('success', 'Groupe supprimé.');
    }

    public function callSignal(Request $request, Group $group)
    {
        abort_unless($group->isMember(Auth::id()), 403);
        $request->validate(['signal' => 'required|string', 'data' => 'nullable']);

        broadcast(new GroupCallSignal(
            groupId:      $group->id,
            fromUserId:   Auth::id(),
            fromUserName: Auth::user()->name,
            signal:       $request->signal,
            data:         $request->data,
            toUserId:     $request->to_user_id,
        ));

        return response()->json(['ok' => true]);
    }


public function hideMessage(Group $group, GroupMessage $message)
{
    abort_unless($group->isMember(Auth::id()), 403);

    DB::table('group_message_hides')->insertOrIgnore([
        'group_message_id' => $message->id,
        'user_id'          => Auth::id(),
        'created_at'       => now(),
        'updated_at'       => now(),
    ]);

    return response()->json(['ok' => true]);
}

public function deleteMessage(Group $group, GroupMessage $message)
{
    abort_unless(
        $message->user_id === Auth::id() || $group->isAdmin(Auth::id()),
        403
    );

    if ($message->image) {
        Storage::disk('public')->delete($message->image);
    }

    $message->delete();

    return response()->json(['ok' => true]);
}



}