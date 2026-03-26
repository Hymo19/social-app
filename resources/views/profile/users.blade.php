@extends('layouts.app')

@section('title', 'Découvrir des utilisateurs')

@section('content')
<div class="max-w-4xl mx-auto mt-6">

    {{-- Header --}}
    <h1 class="text-2xl font-bold text-gray-800 mb-6">
        <i class="fa-solid fa-users mr-2 text-blue-600"></i>Découvrir des utilisateurs
    </h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @forelse($users as $user)
        <div class="bg-white rounded-2xl shadow p-4 flex items-center justify-between">

            {{-- Infos --}}
            <a href="{{ route('profile.show', $user) }}" class="flex items-center gap-4">
                @if($user->avatar)
                    <img src="{{ Storage::url($user->avatar) }}"
                         class="w-14 h-14 rounded-full object-cover border-2 border-blue-200"/>
                @else
                    <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-2xl font-bold">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <p class="font-semibold text-gray-800 hover:text-blue-600 transition">
                        {{ $user->name }}
                    </p>

                    {{-- Statut --}}
                    @php
                        $colors = ['online' => 'bg-green-400', 'away' => 'bg-yellow-400', 'busy' => 'bg-red-400', 'offline' => 'bg-gray-400'];
                        $labels = ['online' => 'En ligne', 'away' => 'Absent', 'busy' => 'Occupé', 'offline' => 'Hors ligne'];
                    @endphp
                    <span class="flex items-center gap-1 text-sm text-gray-500">
                        <span class="w-2 h-2 rounded-full {{ $colors[$user->status] ?? 'bg-gray-400' }}"></span>
                        {{ $labels[$user->status] ?? 'Hors ligne' }}
                    </span>

                    @if($user->bio)
                        <p class="text-xs text-gray-400 mt-1">{{ Str::limit($user->bio, 40) }}</p>
                    @endif
                </div>
            </a>

            {{-- Bouton action --}}
            <div>
                @if(auth()->user()->isFriendWith($user))
                    <span class="bg-green-50 text-green-600 px-3 py-2 rounded-lg text-sm font-semibold">
                        <i class="fa-solid fa-user-check mr-1"></i>Ami
                    </span>
                @elseif(auth()->user()->hasPendingRequestWith($user))
                    <span class="bg-yellow-50 text-yellow-600 px-3 py-2 rounded-lg text-sm font-semibold">
                        <i class="fa-solid fa-clock mr-1"></i>En attente
                    </span>
                @else
                    <form method="POST" action="{{ route('friends.send', $user) }}">
                        @csrf
                        <button type="submit"
                            class="bg-blue-600 text-white px-3 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                            <i class="fa-solid fa-user-plus mr-1"></i>Ajouter
                        </button>
                    </form>
                @endif
            </div>

        </div>
        @empty
            <div class="col-span-2 bg-white rounded-2xl shadow p-10 text-center text-gray-400">
                <i class="fa-solid fa-users text-5xl mb-3"></i>
                <p class="text-lg font-medium">Aucun autre utilisateur pour l'instant</p>
            </div>
        @endforelse
    </div>

</div>
@endsection