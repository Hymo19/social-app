<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'status'       => 'online',       // ← online dès l'inscription
            'last_seen_at' => now(),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;
        session(['auth_token' => $token]);

        Auth::login($user);

        return redirect()->route('feed')->with('success', 'Compte créé avec succès !');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email ou mot de passe incorrect.']);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;
        session(['auth_token' => $token]);

        $user->update([
            'status'       => 'online',       // ← online dès la connexion
            'last_seen_at' => now(),
        ]);

        return redirect()->route('feed');
    }

    public function logout(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $user->update([
            'status'       => 'offline',      // ← offline à la déconnexion
            'last_seen_at' => now(),
        ]);
        $user->tokens()->delete();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}