<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    // Modifier un profil : seulement le propriétaire
    public function update(User $user, User $model): bool
    {
        return $user->id === $model->id;
    }

    // Supprimer un compte : seulement le propriétaire
    public function delete(User $user, User $model): bool
    {
        return $user->id === $model->id;
    }
}