@extends('layouts.app')

@section('title', 'Inscription')

@section('content')
<div class="flex items-center justify-center py-10">
    <div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-md">

        {{-- Logo --}}
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-blue-600">
                <i class="fa-solid fa-globe mr-2"></i>SocialApp
            </h1>
            <p class="text-gray-500 mt-1">Crée ton compte gratuitement</p>
        </div>

        {{-- Formulaire --}}
        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            {{-- Nom --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fa-solid fa-user mr-1"></i>Nom complet
                </label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="John Doe"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 @error('name') border-red-400 @enderror"
                />
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

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
                    placeholder="Minimum 6 caractères"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 @error('password') border-red-400 @enderror"
                />
                @error('password')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirmation --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fa-solid fa-lock mr-1"></i>Confirme le mot de passe
                </label>
                <input
                    type="password"
                    name="password_confirmation"
                    placeholder="••••••••"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                />
            </div>

            {{-- Bouton --}}
            <button type="submit"
                class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition mt-2">
                <i class="fa-solid fa-user-plus mr-2"></i>Créer mon compte
            </button>

        </form>

        {{-- Lien connexion --}}
        <p class="text-center text-sm text-gray-500 mt-6">
            Déjà un compte ?
            <a href="{{ route('login') }}" class="text-blue-600 font-semibold hover:underline">
                Se connecter
            </a>
        </p>

    </div>
</div>
@endsection