@extends('layouts.app')

@section('title', 'Mes Amis')

@section('content')
<div class="max-w-4xl mx-auto mt-6">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fa-solid fa-user-group mr-2 text-blue-600"></i>Mes Amis
        </h1>
        <a href="{{ route('friends.requests') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
            <i class="fa-solid fa-bell mr-1"></i>Demandes reçues
        </a>
    </div>

    {{-- Liste --}}
    @forelse($friends as $friend)
    <div class="bg-white rounded-2xl shadow p-4 flex items-center justify-between mb-3">

        <a href="{{ route('profile.show', $friend) }}" class="flex items-center gap-4">
            {{-- Avatar --}}
            @if($friend->avatar)
                <img src="{{ Storage::url($friend->avatar) }}"
                     class="w-14 h-14 rounded-full object-cover border-2 border-blue-200"/>
            @else
                <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-2xl font-bold">
                    {{ strtoupper(substr($friend->name, 0, 1)) }}
                </div>
            @endif

            {{-- Infos --}}
            <div>
                <p class="font-semibold text-gray-800 hover:text-blue-600 transition">
                    {{ $friend->name }}
                </p>

                {{-- Statut --}}
                @php
                    $colors = ['online' => 'bg-green-400', 'away' => 'bg-yellow-400', 'busy' => 'bg-red-400', 'offline' => 'bg-gray-400'];
                    $labels = ['online' => 'En ligne', 'away' => 'Absent', 'busy' => 'Occupé', 'offline' => 'Hors ligne'];
                @endphp
                <span class="flex items-center gap-1 text-sm text-gray-500">
                    <span class="w-2 h-2 rounded-full {{ $colors[$friend->status] ?? 'bg-gray-400' }}"></span>
                    {{ $labels[$friend->status] ?? 'Hors ligne' }}
                    @if($friend->status_text)
                        · {{ $friend->status_text }}
                    @endif
                </span>

                @if($friend->location)
                    <p class="text-xs text-gray-400">
                        <i class="fa-solid fa-location-dot mr-1"></i>{{ $friend->location }}
                    </p>
                @endif
            </div>
        </a>

        {{-- Bouton supprimer --}}
        <form method="POST" action="{{ route('friends.remove', $friend) }}">
            @csrf @method('DELETE')
            <button onclick="return confirm('Supprimer cet ami ?')"
                class="bg-red-50 text-red-500 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-red-100 transition">
                <i class="fa-solid fa-user-minus mr-1"></i>Retirer
            </button>

 {{-- Message --}}
    <a href="{{ route('messages.show', $friend) }}"
       class="bg-blue-600 text-white px-3 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
        <i class="fa-solid fa-message mr-1"></i>Message
    </a>


        </form>

    </div>
    @empty
        <div class="bg-white rounded-2xl shadow p-10 text-center text-gray-400">
            <i class="fa-solid fa-user-group text-5xl mb-3"></i>
            <p class="text-lg font-medium">Aucun ami pour l'instant</p>
            <p class="text-sm mt-1">Explore des profils et envoie des demandes d'amis !</p>
        </div>
    @endforelse

</div>
@endsection