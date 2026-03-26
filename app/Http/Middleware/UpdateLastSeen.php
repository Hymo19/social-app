<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UpdateLastSeen
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            // Met à jour à chaque requête sans condition de délai
            $user->update([
                'last_seen_at' => now(),
                'status'       => 'online',
            ]);
        }

        return $next($request);
    }
}