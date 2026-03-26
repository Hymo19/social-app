@extends('layouts.app')

@section('title', 'Demandes d\'amis')

@section('content')
<div class="max-w-4xl mx-auto mt-6">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fa-solid fa-bell mr-2 text-blue-600"></i>Demandes d'amis reçues
        </h1>
        <a href="{{ route('friends.index') }}"
           class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-200 transition">
            <i class="fa-solid fa-arrow-left mr-1"></i>Mes amis
        </a>
    </div>

    {{-- Liste --}}
    @forelse($requests as $request)
    <div class="bg-white rounded-2xl shadow p-4 flex items-center justify-between mb-3">

        {{-- Infos expéditeur --}}
        <a href="{{ route('profile.show', $request->sender) }}" class="flex items-center gap-4">
            @if($request->sender->avatar)
                <img src="{{ Storage::url($request->sender->avatar) }}"
                     class="w-14 h-14 rounded-full object-cover border-2 border-blue-200"/>
            @else
                <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-2xl font-bold">
                    {{ strtoupper(substr($request->sender->name, 0, 1)) }}
                </div>
            @endif

            <div>
                <p class="font-semibold text-gray-800 hover:text-blue-600 transition">
                    {{ $request->sender->name }}
                </p>
                <p class="text-xs text-gray-400">
                    <i class="fa-regular fa-clock mr-1"></i>{{ $request->created_at->diffForHumans() }}
                </p>
                @if($request->sender->bio)
                    <p class="text-sm text-gray-500 mt-1">{{ Str::limit($request->sender->bio, 60) }}</p>
                @endif
            </div>
        </a>

        {{-- Boutons Accepter / Refuser --}}
        <div class="flex gap-2">
            <form method="POST" action="{{ route('friends.accept', $request) }}">
                @csrf
                <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                    <i class="fa-solid fa-check mr-1"></i>Accepter
                </button>
            </form>

            <form method="POST" action="{{ route('friends.decline', $request) }}">
                @csrf
                <button type="submit"
                    class="bg-red-50 text-red-500 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-red-100 transition">
                    <i class="fa-solid fa-xmark mr-1"></i>Refuser
                </button>
            </form>
        </div>

    </div>
    @empty
        <div class="bg-white rounded-2xl shadow p-10 text-center text-gray-400">
            <i class="fa-solid fa-bell text-5xl mb-3"></i>
            <p class="text-lg font-medium">Aucune demande d'amis</p>
            <p class="text-sm mt-1">Tu n'as pas de nouvelles demandes d'amis.</p>
        </div>
    @endforelse

</div>
@endsection