@extends('layouts.app')
@section('title', 'Partages envoyés')
@section('content')
<div class="max-w-2xl mx-auto mt-6 space-y-4">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fa-solid fa-paper-plane text-blue-600"></i>Partages envoyés
        </h1>
        <a href="{{ route('shares.received') }}" class="text-sm text-blue-500 hover:underline">
            ← Partages reçus
        </a>
    </div>

    @forelse($shares as $share)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center gap-3 mb-3">
            @php
                $statusColors = ['pending'=>'bg-yellow-100 text-yellow-700','accepted'=>'bg-green-100 text-green-700','declined'=>'bg-red-100 text-red-600'];
                $statusLabels = ['pending'=>'⏳ En attente','accepted'=>'✅ Accepté','declined'=>'❌ Refusé'];
            @endphp
            <span class="text-xs font-semibold px-3 py-1 rounded-full {{ $statusColors[$share->status] }}">
                {{ $statusLabels[$share->status] }}
            </span>
            <span class="text-xs text-gray-400 ml-auto">{{ $share->created_at->diffForHumans() }}</span>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex-1">
                <p class="text-sm text-gray-700">
                    Partagé avec
                    @if($share->recipient instanceof \App\Models\User)
                        <a href="{{ route('profile.show', $share->recipient) }}" class="font-semibold text-blue-600 hover:underline">
                            {{ $share->recipient->name }}
                        </a>
                    @else
                        <span class="font-semibold text-purple-600">{{ $share->recipient->name ?? 'Groupe' }}</span>
                    @endif
                </p>
                @if($share->message)
                <p class="text-xs text-gray-400 italic mt-0.5">"{{ $share->message }}"</p>
                @endif
            </div>
            <a href="{{ route('posts.show', $share->post) }}"
               class="text-xs text-blue-500 hover:underline flex-shrink-0">
                Voir le post →
            </a>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-10 text-center text-gray-400">
        <i class="fa-solid fa-paper-plane text-5xl mb-3 block text-gray-200"></i>
        <p class="text-lg font-medium text-gray-500">Aucun partage envoyé</p>
    </div>
    @endforelse

    <div class="mt-4">{{ $shares->links() }}</div>
</div>
@endsection