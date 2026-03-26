@extends('layouts.app')

@section('title', $user->name)

@section('content')
<div class="max-w-4xl mx-auto mt-6 space-y-5">

    {{-- ─── Carte Profil ───────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow">

        {{-- Bannière / Cover Photo --}}
        <div class="h-56 relative">
            @if($user->cover_photo)
                <img src="{{ Storage::url($user->cover_photo) }}"
                     class="w-full h-full object-cover rounded-t-2xl"/>
            @else
                <div class="w-full h-full bg-gradient-to-r from-blue-500 to-blue-700 rounded-t-2xl"></div>
            @endif

            {{-- Bouton changer cover --}}
            @if(auth()->id() === $user->id)
                <div class="absolute bottom-3 right-3">
                    <button onclick="document.getElementById('cover-input').click()"
                        class="bg-black bg-opacity-50 text-white px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-opacity-70 transition">
                        <i class="fa-solid fa-camera mr-1"></i>Modifier la couverture
                    </button>
                </div>
                <form method="POST" action="{{ route('profile.cover', $user) }}"
                      enctype="multipart/form-data" id="cover-form">
                    @csrf @method('PUT')
                    <input type="file" id="cover-input" name="cover_photo"
                           accept="image/*" class="hidden"
                           onchange="document.getElementById('cover-form').submit()"/>
                </form>
            @endif

            {{-- Avatar positionné sur la bannière --}}
            <div class="absolute -bottom-14 left-6">
                @if($user->avatar)
                    <img src="{{ Storage::url($user->avatar) }}"
                         class="w-28 h-28 rounded-full object-cover border-4 border-white shadow-lg"/>
                @else
                    <div class="w-28 h-28 rounded-full bg-blue-100 border-4 border-white shadow-lg flex items-center justify-center text-blue-600 text-4xl font-bold">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Infos principales --}}
        <div class="px-6 pb-6 pt-16">
            <div class="flex items-center justify-between mb-4">

                <div></div>

                {{-- Boutons actions --}}
                <div class="flex gap-2">
                    @if(auth()->id() === $user->id)
                        <a href="{{ route('profile.edit', $user) }}"
                           class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                            <i class="fa-solid fa-pen mr-1"></i>Modifier le profil
                        </a>
                    @else
                        @if(auth()->user()->isFriendWith($user))
                            <form method="POST" action="{{ route('friends.remove', $user) }}">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-200 transition">
                                    <i class="fa-solid fa-user-minus mr-1"></i>Retirer de mes amis
                                </button>
                            </form>
                            <a href="{{ route('messages.show', $user) }}"
                               class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-green-700 transition">
                                <i class="fa-solid fa-message mr-1"></i>Message
                            </a>
                        @elseif(auth()->user()->hasPendingRequestWith($user))
                            <button disabled
                                class="bg-yellow-50 text-yellow-600 px-4 py-2 rounded-lg text-sm font-semibold cursor-not-allowed">
                                <i class="fa-solid fa-clock mr-1"></i>Demande envoyée
                            </button>
                        @else
                            <form method="POST" action="{{ route('friends.send', $user) }}">
                                @csrf
                                <button type="submit"
                                    class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                                    <i class="fa-solid fa-user-plus mr-1"></i>Ajouter en ami
                                </button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('follow.toggle', $user) }}">
                            @csrf
                            <button type="submit"
                                class="px-4 py-2 rounded-lg text-sm font-semibold transition
                                {{ $isFollowing ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-green-600 text-white hover:bg-green-700' }}">
                                @if($isFollowing)
                                    <i class="fa-solid fa-user-minus mr-1"></i>Ne plus suivre
                                @else
                                    <i class="fa-solid fa-rss mr-1"></i>Suivre
                                @endif
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Nom + Statut --}}
            <div class="mb-3">
                <h1 class="text-2xl font-bold text-gray-800">{{ $user->name }}</h1>

                @php
                    $statusColors = ['online' => 'bg-green-400', 'away' => 'bg-yellow-400', 'busy' => 'bg-red-400', 'offline' => 'bg-gray-400'];
                    $statusLabels = ['online' => 'En ligne', 'away' => 'Absent', 'busy' => 'Occupé', 'offline' => 'Hors ligne'];
                    $color = $statusColors[$user->status] ?? 'bg-gray-400';
                    $label = $statusLabels[$user->status] ?? 'Hors ligne';
                @endphp

                <div class="flex items-center gap-2 mt-1">
                    <span class="w-2.5 h-2.5 rounded-full {{ $color }}"></span>
                    <span class="text-sm text-gray-500">{{ $label }}</span>
                    @if($user->status_text)
                        <span class="text-sm text-gray-400">· {{ $user->status_text }}</span>
                    @endif
                </div>

                @if(!$user->isOnline() && $user->last_seen_at)
                    <p class="text-xs text-gray-400 mt-1">
                        <i class="fa-regular fa-clock mr-1"></i>Vu {{ $user->lastSeenHuman() }}
                    </p>
                @endif
            </div>

            {{-- Bio --}}
            @if($user->bio)
                <p class="text-gray-600 mb-3">{{ $user->bio }}</p>
            @endif

            {{-- Infos supplémentaires --}}
            <div class="flex flex-wrap gap-4 text-sm text-gray-500 mb-4">
                @if($user->location)
                    <span><i class="fa-solid fa-location-dot mr-1 text-blue-400"></i>{{ $user->location }}</span>
                @endif
                @if($user->birth_date)
                    <span><i class="fa-solid fa-cake-candles mr-1 text-blue-400"></i>{{ $user->birth_date->format('d/m/Y') }}</span>
                @endif
                <span><i class="fa-regular fa-calendar mr-1 text-blue-400"></i>Membre depuis {{ $user->created_at->format('M Y') }}</span>
            </div>

            {{-- Stats --}}
            <div class="flex gap-6 border-t pt-4 text-center">
                <div>
                    <p class="font-bold text-gray-800 text-lg">{{ $posts->count() }}</p>
                    <p class="text-xs text-gray-500">Posts</p>
                </div>
                <div>
                    <p class="font-bold text-gray-800 text-lg">{{ $followers }}</p>
                    <p class="text-xs text-gray-500">Abonnés</p>
                </div>
                <div>
                    <p class="font-bold text-gray-800 text-lg">{{ $following }}</p>
                    <p class="text-xs text-gray-500">Abonnements</p>
                </div>
                <div>
                    <p class="font-bold text-gray-800 text-lg">{{ $friends->count() }}</p>
                    <p class="text-xs text-gray-500">Amis</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Section Amis ────────────────────────────────────── --}}
    @if($friends->count() > 0)
    <div class="bg-white rounded-2xl shadow p-5">

        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-800">
                <i class="fa-solid fa-user-group mr-2 text-blue-600"></i>
                Amis <span class="text-gray-400 font-normal text-sm">({{ $friends->count() }})</span>
            </h2>
            @if(auth()->id() === $user->id)
                <a href="{{ route('friends.index') }}"
                   class="text-sm text-blue-500 hover:text-blue-700 transition">
                    Voir tous <i class="fa-solid fa-arrow-right ml-1"></i>
                </a>
            @endif
        </div>

        @php
            $colors = ['online' => 'bg-green-400', 'away' => 'bg-yellow-400', 'busy' => 'bg-red-400', 'offline' => 'bg-gray-300'];
        @endphp
        <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
            @foreach($friends->take(10) as $friend)
            <a href="{{ route('profile.show', $friend) }}"
               class="flex flex-col items-center gap-1 group">

                @if($friend->avatar)
                    <div class="relative">
                        <img src="{{ Storage::url($friend->avatar) }}"
                             class="w-16 h-16 rounded-xl object-cover border-2 border-gray-100 group-hover:border-blue-300 transition"/>
                        <span class="absolute bottom-1 right-1 w-3 h-3 rounded-full border-2 border-white {{ $colors[$friend->status] ?? 'bg-gray-300' }}"></span>
                    </div>
                @else
                    <div class="relative">
                        <div class="w-16 h-16 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 text-2xl font-bold border-2 border-gray-100 group-hover:border-blue-300 transition">
                            {{ strtoupper(substr($friend->name, 0, 1)) }}
                        </div>
                        <span class="absolute bottom-1 right-1 w-3 h-3 rounded-full border-2 border-white {{ $colors[$friend->status] ?? 'bg-gray-300' }}"></span>
                    </div>
                @endif

                <p class="text-xs text-gray-700 font-medium text-center group-hover:text-blue-600 transition truncate w-full">
                    {{ Str::limit($friend->name, 10) }}
                </p>

            </a>
            @endforeach
        </div>

        @if($friends->count() > 10)
            <div class="mt-4 text-center">
                <a href="{{ route('friends.index') }}"
                   class="text-sm text-blue-500 hover:text-blue-700 transition">
                    Voir les {{ $friends->count() - 10 }} autres amis
                </a>
            </div>
        @endif

    </div>
    @endif

    {{-- ─── Posts de l'utilisateur ─────────────────────────── --}}
    <div class="space-y-4">
        <h2 class="text-lg font-bold text-gray-800">
            <i class="fa-solid fa-newspaper mr-2 text-blue-600"></i>Posts de {{ $user->name }}
        </h2>

        @forelse($posts as $post)
        <div class="bg-white rounded-2xl shadow p-5">

            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="font-bold text-gray-800">{{ $post->title }}</h3>
                    <p class="text-xs text-gray-400">
                        <i class="fa-regular fa-clock mr-1"></i>{{ $post->created_at->diffForHumans() }}
                        @if($post->visibility === 'public')
                            · <i class="fa-solid fa-earth-americas"></i>
                        @elseif($post->visibility === 'friends')
                            · <i class="fa-solid fa-user-group"></i>
                        @else
                            · <i class="fa-solid fa-lock"></i>
                        @endif
                    </p>
                </div>

                @can('update', $post)
                <div class="flex gap-2">
                    <a href="{{ route('posts.edit', $post) }}"
                       class="text-blue-400 hover:text-blue-600 transition text-sm">
                        <i class="fa-solid fa-pen"></i>
                    </a>
                    <form method="POST" action="{{ route('posts.destroy', $post) }}">
                        @csrf @method('DELETE')
                        <button onclick="return confirm('Supprimer ?')"
                            class="text-red-400 hover:text-red-600 transition text-sm">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </div>
                @endcan
            </div>

            <p class="text-gray-600 mb-3">{{ Str::limit($post->content, 150) }}</p>

            @if($post->image)
                <img src="{{ Storage::url($post->image) }}"
                     class="w-full rounded-xl mb-3 max-h-60 object-cover"/>
            @endif

            <div class="flex items-center justify-between border-t pt-3">
                <div class="flex gap-4 text-sm text-gray-400">
                    <span><i class="fa-regular fa-thumbs-up mr-1"></i>{{ $post->likes->count() }}</span>
                    <span><i class="fa-regular fa-comment mr-1"></i>{{ $post->comments->count() }}</span>
                </div>
                <a href="{{ route('posts.show', $post) }}"
                   class="text-sm text-blue-500 hover:text-blue-700 font-medium transition">
                    Voir le post <i class="fa-solid fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
        @empty
            <div class="bg-white rounded-2xl shadow p-10 text-center text-gray-400">
                <i class="fa-solid fa-newspaper text-5xl mb-3"></i>
                <p>Aucun post pour l'instant</p>
            </div>
        @endforelse
    </div>

</div>
@endsection