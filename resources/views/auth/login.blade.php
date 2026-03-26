@extends('layouts.app')

@section('title', 'Connexion')

@section('content')
<div class="min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-md">

        {{-- Logo --}}
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-blue-600">
                <i class="fa-solid fa-globe mr-2"></i>SocialApp
            </h1>
            <p class="text-gray-500 mt-1">Connecte-toi à ton compte</p>
        </div>

        {{-- Formulaire --}}
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            {{-- Email --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fa-solid fa-envelope mr-1"></i>Email
                </label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="exemple@email.com"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 @error('email') border-red-400 @enderror"
                />
                @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Mot de passe --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fa-solid fa-lock mr-1"></i>Mot de passe
                </label>
                <input
                    type="password"
                    name="password"
                    placeholder="••••••••"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                />
            </div>

            {{-- Se souvenir de moi --}}
            <div class="flex items-center gap-2">
                <input type="checkbox" name="remember" id="remember" class="rounded">
                <label for="remember" class="text-sm text-gray-600">Se souvenir de moi</label>
            </div>

            {{-- Bouton --}}
            <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                <i class="fa-solid fa-right-to-bracket mr-2"></i>Se connecter
            </button>
        </form>

        {{-- Lien inscription --}}
        <p class="text-center text-sm text-gray-500 mt-6">
            Pas encore de compte ?
            <a href="{{ route('register') }}" class="text-blue-600 font-semibold hover:underline">
                S'inscrire
            </a>
        </p>

    </div>
</div>
@endsection