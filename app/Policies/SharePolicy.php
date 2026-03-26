<?php
namespace App\Policies;

use App\Models\Share;
use App\Models\User;

class SharePolicy
{
    public function update(User $user, Share $share): bool
    {
        return $share->recipient_type === User::class
            && $share->recipient_id === $user->id;
    }
}