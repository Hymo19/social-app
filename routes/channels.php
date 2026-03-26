<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Group;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('group.{groupId}', function ($user, $groupId) {
    $group = Group::find($groupId);
    if ($group && $group->isMember($user->id)) {
        return [
            'id'     => $user->id,
            'name'   => $user->name,
            'avatar' => $user->avatar
                ? \Illuminate\Support\Facades\Storage::url($user->avatar)
                : null,
        ];
    }
    return false;
});