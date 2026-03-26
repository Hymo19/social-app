<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    // Modifier un post : seulement le propriétaire
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    // Supprimer un post : seulement le propriétaire
    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}