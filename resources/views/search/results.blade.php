@extends('layouts.app')

@section('title', 'Recherche : ' . $query)

@section('content')
<div class="max-w-4xl mx-auto mt-6 space-y-6">

    {{-- Header --}}
    <div class="bg-white rounded-2xl shadow p-5">
        <h1 class="text-xl font-bold text-gray-800 mb-1">
            <i class="fa-solid fa-magnifying-glass mr-2 text-blue-600"></i>
            Résultats pour "<span class="text-blue-600">{{ $query }}</span>"
        </h1>
        <p class="text-sm text-gray-400">
            {{ $users->count() }} utilisateur(s) · {{ $posts->count() }} post(s)
        </p>
    </div>

    {{-- Onglets --}}
    <div class="flex gap-2" id="search-tabs">
        <button onclick="showTab('all')"
            class="tab-btn active px-4 py-2 rounded-full text-sm font-semibold bg-blue-600 text-white transition"
            data-tab="all">
            Tout
        </button>
        <button onclick="showTab('users')"
            class="tab-btn px-4 py-2 rounded-full text-sm font-semibold bg-white text-gray-600 hover:bg-gray-100 transition"
            data-tab="users">
            <i class="fa-solid fa-user mr-1"></i>Utilisateurs ({{ $users->count() }})
        </button>
        <button onclick="showTab('posts')"
            class="tab-btn px-4 py-2 rounded-full text-sm font-semibold bg-white text-gray-600 hover:bg-gray-100 transition"
            data-tab="posts">
            <i class="fa-solid fa-newspaper mr-1"></i>Posts ({{ $posts->count() }})
        </button>
    </div>

    {{-- ─── Section Utilisateurs ─────────────────────────── --}}
    <div id="section-users">
        @if($users->count() > 0)
        <div class="bg-white rounded-2xl shadow p-5">
            <h2 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fa-solid fa-users mr-2 text-blue-600"></i>Utilisateurs
            </h2>
            <div class="space-y-3">
                @foreach($users as $user)
                @php
                    $statusColors = ['online' => 'bg-green-400', 'away' => 'bg-yellow-400', 'busy' => 'bg-red-400', 'offline' => 'bg-gray-400'];
                    $statusLabels = ['online' => 'En ligne', 'away' => 'Absent', 'busy' => 'Occupé', 'offline' => 'Hors ligne'];
                @endphp
                <a href="{{ route('profile.show', $user) }}"
                   class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 transition group">
                    <div class="flex items-center gap-3">
                        <div class="relative flex-shrink-0">
                            @if($user->avatar)
                                <img src="{{ Storage::url($user->avatar) }}"
                                     class="w-12 h-12 rounded-full object-cover border-2 border-gray-100"/>
                            @else
                                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xl font-bold">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                            @endif
                            <span class="absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white {{ $statusColors[$user->status] ?? 'bg-gray-400' }}"></span>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800 group-hover:text-blue-600 transition">{{ $user->name }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $statusLabels[$user->status] ?? 'Hors ligne' }}
                                @if($user->location) · <i class="fa-solid fa-location-dot"></i> {{ $user->location }} @endif
                            </p>
                            @if($user->bio)
                                <p class="text-xs text-gray-500 mt-0.5">{{ Str::limit($user->bio, 60) }}</p>
                            @endif
                        </div>
                    </div>
                    <i class="fa-solid fa-arrow-right text-gray-300 group-hover:text-blue-400 transition"></i>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- ─── Section Posts ────────────────────────────────── --}}
    <div id="section-posts">
        @if($posts->count() > 0)
        <div class="space-y-4">
            <h2 class="text-lg font-bold text-gray-800">
                <i class="fa-solid fa-newspaper mr-2 text-blue-600"></i>Posts
            </h2>
            @foreach($posts as $post)
            <div class="bg-white rounded-2xl shadow p-5">
                <div class="flex items-center gap-3 mb-3">
                    @if($post->user->avatar)
                        <img src="{{ Storage::url($post->user->avatar) }}"
                             class="w-10 h-10 rounded-full object-cover border border-gray-200"/>
                    @else
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                            {{ strtoupper(substr($post->user->name, 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <a href="{{ route('profile.show', $post->user) }}"
                           class="font-semibold text-gray-800 hover:text-blue-600 transition text-sm">
                            {{ $post->user->name }}
                        </a>
                        <p class="text-xs text-gray-400">{{ $post->created_at->diffForHumans() }}</p>
                    </div>
                </div>

                <h3 class="font-bold text-gray-800 mb-1">{{ $post->title }}</h3>
                <p class="text-gray-600 text-sm mb-3">{{ Str::limit($post->content, 150) }}</p>

                @if($post->image)
                    <img src="{{ Storage::url($post->image) }}"
                         class="w-full rounded-xl mb-3 max-h-48 object-cover"/>
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
            @endforeach
        </div>
        @endif
    </div>

    {{-- Aucun résultat --}}
    @if($users->count() === 0 && $posts->count() === 0)
    <div class="bg-white rounded-2xl shadow p-10 text-center text-gray-400">
        <i class="fa-solid fa-magnifying-glass text-5xl mb-3"></i>
        <p class="text-lg font-medium">Aucun résultat pour "{{ $query }}"</p>
        <p class="text-sm mt-1">Essaie un autre mot-clé</p>
    </div>
    @endif

</div>

<script>
    function showTab(tab) {
        // Boutons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('bg-blue-600', 'text-white');
            btn.classList.add('bg-white', 'text-gray-600');
        });
        document.querySelector(`[data-tab="${tab}"]`).classList.add('bg-blue-600', 'text-white');
        document.querySelector(`[data-tab="${tab}"]`).classList.remove('bg-white', 'text-gray-600');

        // Sections
        const showUsers = tab === 'all' || tab === 'users';
        const showPosts = tab === 'all' || tab === 'posts';
        document.getElementById('section-users').style.display = showUsers ? 'block' : 'none';
        document.getElementById('section-posts').style.display = showPosts ? 'block' : 'none';
    }
</script>
@endsection