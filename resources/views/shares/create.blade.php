@extends('layouts.app')
@section('title', 'Partager un post')
@section('content')

<div class="max-w-xl mx-auto mt-6 px-4">

    {{-- Aperçu du post --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-4">
        <div class="flex items-center gap-3 mb-3">
            @if($post->user->avatar)
                <img src="{{ Storage::url($post->user->avatar) }}" class="w-9 h-9 rounded-full object-cover"/>
            @else
                <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">
                    {{ strtoupper(substr($post->user->name,0,1)) }}
                </div>
            @endif
            <div>
                <p class="font-semibold text-gray-800 text-sm">{{ $post->user->name }}</p>
                <p class="text-xs text-gray-400">{{ $post->created_at->diffForHumans() }}</p>
            </div>
        </div>
        @if($post->title)<p class="font-bold text-gray-800 text-sm mb-1">{{ $post->title }}</p>@endif
        @if($post->content)<p class="text-gray-600 text-sm">{{ Str::limit($post->content, 120) }}</p>@endif
        @if($post->image)
            <img src="{{ Storage::url($post->image) }}" class="w-full rounded-xl mt-2 max-h-40 object-cover"/>
        @endif
    </div>

    {{-- Formulaire partage --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-share-nodes text-blue-600 text-lg"></i>
            </div>
            <div>
                <h1 class="text-base font-bold text-gray-800">Envoyer à des amis ou groupes</h1>
                <p class="text-xs text-gray-400">Choisissez les destinataires</p>
            </div>
        </div>

        <form method="POST" action="{{ route('shares.store', $post) }}" class="p-5 space-y-5">
            @csrf

            <textarea name="message" rows="2" placeholder="Ajouter un message... (optionnel)"
                      class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 resize-none">{{ old('message') }}</textarea>

            {{-- Amis --}}
            @if($friends->count() > 0)
            <div>
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest flex items-center gap-2">
                        <i class="fa-solid fa-user-group text-blue-400"></i>Amis ({{ $friends->count() }})
                    </p>
                    <button type="button" onclick="selectAll('friends-list')" class="text-xs text-blue-500 hover:underline">Tout sélectionner</button>
                </div>
                <div class="relative mb-2">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" placeholder="Rechercher..."
                           oninput="filterList(this.value, 'friends-list')"
                           class="w-full pl-8 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none"/>
                </div>
                <div class="space-y-1 max-h-52 overflow-y-auto" id="friends-list">
                    @foreach($friends as $friend)
                    <label class="recipient-item flex items-center gap-3 p-3 rounded-xl hover:bg-blue-50 cursor-pointer border-2 border-transparent has-[:checked]:border-blue-400 has-[:checked]:bg-blue-50 transition"
                           data-name="{{ strtolower($friend->name) }}">
                        <input type="checkbox" name="recipients[]" value="user_{{ $friend->id }}"
                               class="w-4 h-4 accent-blue-600 flex-shrink-0" onchange="updateCount()"/>
                        @if($friend->avatar)
                            <img src="{{ Storage::url($friend->avatar) }}" class="w-9 h-9 rounded-full object-cover flex-shrink-0"/>
                        @else
                            <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm flex-shrink-0">
                                {{ strtoupper(substr($friend->name,0,1)) }}
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $friend->name }}</p>
                            <p class="text-xs {{ $friend->isOnline() ? 'text-green-500' : 'text-gray-400' }}">
                                {{ $friend->isOnline() ? '🟢 En ligne' : '⚫ Hors ligne' }}
                            </p>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Groupes --}}
            @if($groups->count() > 0)
            <div>
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest flex items-center gap-2">
                        <i class="fa-solid fa-people-group text-purple-400"></i>Groupes ({{ $groups->count() }})
                    </p>
                    <button type="button" onclick="selectAll('groups-list')" class="text-xs text-purple-500 hover:underline">Tout sélectionner</button>
                </div>
                <div class="space-y-1 max-h-40 overflow-y-auto" id="groups-list">
                    @foreach($groups as $group)
                    <label class="recipient-item flex items-center gap-3 p-3 rounded-xl hover:bg-purple-50 cursor-pointer border-2 border-transparent has-[:checked]:border-purple-400 has-[:checked]:bg-purple-50 transition"
                           data-name="{{ strtolower($group->name) }}">
                        <input type="checkbox" name="recipients[]" value="group_{{ $group->id }}"
                               class="w-4 h-4 accent-purple-600 flex-shrink-0" onchange="updateCount()"/>
                        <div class="w-9 h-9 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 flex-shrink-0">
                            <i class="fa-solid fa-people-group text-sm"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $group->name }}</p>
                            <p class="text-xs text-purple-400">{{ $group->members()->count() }} membre(s)</p>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            @if($friends->count() === 0 && $groups->count() === 0)
            <div class="text-center py-8 text-gray-400">
                <i class="fa-solid fa-user-group text-4xl mb-3 block text-gray-200"></i>
                <p class="text-sm">Aucun ami ni groupe disponible</p>
            </div>
            @endif

            <div id="count-bar" class="hidden bg-blue-50 border border-blue-200 rounded-xl px-4 py-2.5 flex items-center gap-2 text-sm text-blue-700 font-medium">
                <i class="fa-solid fa-circle-check text-blue-500"></i>
                <span id="count-num">0</span> destinataire(s) sélectionné(s)
            </div>

            <div class="flex gap-3 pt-1">
                <a href="{{ url()->previous() }}"
                   class="flex-1 text-center bg-gray-100 text-gray-700 py-2.5 rounded-xl font-semibold hover:bg-gray-200 transition text-sm">
                    Annuler
                </a>
                <button type="submit" id="send-btn" disabled
                        class="flex-1 bg-blue-600 text-white py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2 text-sm">
                    <i class="fa-solid fa-paper-plane"></i>Envoyer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function filterList(q, listId) {
    q = q.toLowerCase();
    document.querySelectorAll(`#${listId} .recipient-item`).forEach(item => {
        item.style.display = item.dataset.name.includes(q) ? '' : 'none';
    });
}
function selectAll(listId) {
    document.querySelectorAll(`#${listId} input[type="checkbox"]`).forEach(cb => cb.checked = true);
    updateCount();
}
function updateCount() {
    const n = document.querySelectorAll('input[name="recipients[]"]:checked').length;
    document.getElementById('count-num').textContent = n;
    document.getElementById('count-bar').classList.toggle('hidden', n === 0);
    document.getElementById('send-btn').disabled = n === 0;
}
</script>
@endsection