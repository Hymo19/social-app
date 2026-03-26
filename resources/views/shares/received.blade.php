@extends('layouts.app')
@section('title', 'Partages reçus')
@section('content')
<div class="max-w-2xl mx-auto mt-6 space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fa-solid fa-inbox text-blue-600"></i>Partages reçus
        </h1>
        <a href="{{ route('shares.sent') }}" class="text-sm text-blue-500 hover:underline">
            Voir mes partages envoyés →
        </a>
    </div>

    {{-- ═══ EN ATTENTE ════════════════════════════════════════ --}}
    @if($pending->count() > 0)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 bg-yellow-50 border-b border-yellow-200 flex items-center gap-2">
            <i class="fa-solid fa-clock text-yellow-600"></i>
            <p class="text-sm font-semibold text-yellow-700">
                {{ $pending->count() }} partage(s) en attente de ton approbation
            </p>
        </div>

        <div class="divide-y divide-gray-100">
            @foreach($pending as $share)
            <div class="p-5">
                {{-- Expéditeur --}}
                <div class="flex items-center gap-3 mb-3">
                    @if($share->sender->avatar)
                        <img src="{{ Storage::url($share->sender->avatar) }}" class="w-10 h-10 rounded-full object-cover"/>
                    @else
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                            {{ strtoupper(substr($share->sender->name,0,1)) }}
                        </div>
                    @endif
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-800">
                            <a href="{{ route('profile.show', $share->sender) }}" class="hover:text-blue-600">{{ $share->sender->name }}</a>
                            <span class="font-normal text-gray-500"> veut partager un post avec toi</span>
                        </p>
                        <p class="text-xs text-gray-400">{{ $share->created_at->diffForHumans() }}</p>
                    </div>
                </div>

                {{-- Message --}}
                @if($share->message)
                <div class="bg-blue-50 rounded-xl px-3 py-2 mb-3 text-sm text-blue-800 italic">
                    "{{ $share->message }}"
                </div>
                @endif

                {{-- Aperçu du post --}}
                <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 mb-4">
                    <div class="flex items-center gap-2 mb-2">
                        @if($share->post->user->avatar)
                            <img src="{{ Storage::url($share->post->user->avatar) }}" class="w-8 h-8 rounded-full object-cover"/>
                        @else
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs">
                                {{ strtoupper(substr($share->post->user->name,0,1)) }}
                            </div>
                        @endif
                        <p class="text-xs font-semibold text-gray-700">{{ $share->post->user->name }}</p>
                        <span class="text-xs text-gray-400">· {{ $share->post->created_at->diffForHumans() }}</span>
                    </div>
                    @if($share->post->title)<p class="font-semibold text-gray-800 text-sm mb-1">{{ $share->post->title }}</p>@endif
                    @if($share->post->content)<p class="text-gray-600 text-sm">{{ Str::limit($share->post->content, 120) }}</p>@endif
                    @if($share->post->image)
                        <img src="{{ Storage::url($share->post->image) }}" class="w-full rounded-lg mt-2 max-h-40 object-cover"/>
                    @endif
                    <a href="{{ route('posts.show', $share->post) }}" class="text-xs text-blue-500 hover:underline mt-2 inline-block">
                        <i class="fa-solid fa-arrow-up-right-from-square mr-1"></i>Voir le post complet
                    </a>
                </div>

                {{-- ══ CHOIX DE DESTINATION (comme Facebook) ══ --}}
                <div class="mb-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-2">
                        Où voulez-vous afficher ce post ?
                    </p>
                    <div class="grid grid-cols-2 gap-2" id="dest-choice-{{ $share->id }}">

                        {{-- Fil d'actualité --}}
                        <label class="dest-option flex flex-col items-center gap-2 p-3 rounded-xl border-2 border-gray-200 cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition"
                               data-share="{{ $share->id }}" data-dest="self_feed">
                            <input type="radio" name="destination_{{ $share->id }}" value="self_feed" class="hidden"/>
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-newspaper text-blue-600 text-lg"></i>
                            </div>
                            <div class="text-center">
                                <p class="font-semibold text-gray-800 text-xs">Fil d'actualité</p>
                                <p class="text-xs text-gray-400">Visible par vos amis</p>
                            </div>
                        </label>

                        {{-- Mon profil --}}
                        <label class="dest-option flex flex-col items-center gap-2 p-3 rounded-xl border-2 border-gray-200 cursor-pointer hover:border-green-400 hover:bg-green-50 transition"
                               data-share="{{ $share->id }}" data-dest="self_profile">
                            <input type="radio" name="destination_{{ $share->id }}" value="self_profile" class="hidden"/>
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center overflow-hidden">
                                @if(auth()->user()->avatar)
                                    <img src="{{ Storage::url(auth()->user()->avatar) }}" class="w-full h-full object-cover"/>
                                @else
                                    <i class="fa-solid fa-user text-green-600 text-lg"></i>
                                @endif
                            </div>
                            <div class="text-center">
                                <p class="font-semibold text-gray-800 text-xs">Mon profil</p>
                                <p class="text-xs text-gray-400">Sur votre page</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Boutons Accept / Refuser --}}
                <div class="flex gap-3">
                    {{-- Accepter avec destination --}}
                    <form method="POST" action="{{ route('shares.accept', $share) }}" class="flex-1" id="accept-form-{{ $share->id }}">
                        @csrf
                        <input type="hidden" name="destination" id="dest-input-{{ $share->id }}" value="self_feed"/>
                        <button type="submit" id="accept-btn-{{ $share->id }}"
                                class="w-full bg-blue-600 text-white py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition flex items-center justify-center gap-2 text-sm opacity-60 cursor-not-allowed"
                                disabled>
                            <i class="fa-solid fa-check"></i>
                            <span id="accept-label-{{ $share->id }}">Choisir une destination d'abord</span>
                        </button>
                    </form>

                    {{-- Refuser --}}
                    <form method="POST" action="{{ route('shares.decline', $share) }}" class="flex-1">
                        @csrf
                        <button type="submit"
                                class="w-full bg-gray-100 text-gray-700 py-2.5 rounded-xl font-semibold hover:bg-red-50 hover:text-red-600 transition flex items-center justify-center gap-2 text-sm">
                            <i class="fa-solid fa-xmark"></i>Refuser
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-10 text-center text-gray-400">
        <i class="fa-solid fa-inbox text-5xl mb-3 block text-gray-200"></i>
        <p class="text-lg font-medium text-gray-500">Aucun partage en attente</p>
    </div>
    @endif

    {{-- ═══ ACCEPTÉS ══════════════════════════════════════════ --}}
    @if($accepted->count() > 0)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-600 flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-green-500"></i>
                Partages acceptés ({{ $accepted->count() }})
            </p>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($accepted as $share)
            <div class="p-4 flex items-center gap-3">
                <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0
                    {{ $share->destination === 'self_profile' ? 'bg-green-100' : 'bg-blue-100' }}">
                    @if($share->destination === 'self_profile')
                        <i class="fa-solid fa-user text-green-500 text-sm"></i>
                    @else
                        <i class="fa-solid fa-newspaper text-blue-500 text-sm"></i>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-700">
                        <span class="font-semibold">{{ $share->sender->name }}</span>
                        a partagé un post avec toi
                    </p>
                    <p class="text-xs text-gray-400 flex items-center gap-1">
                        Accepté {{ $share->accepted_at->diffForHumans() }}
                        @if($share->destination === 'self_profile')
                            · <i class="fa-solid fa-user text-xs text-green-500"></i> <span class="text-green-600">Mon profil</span>
                        @else
                            · <i class="fa-solid fa-newspaper text-xs text-blue-500"></i> <span class="text-blue-600">Fil d'actualité</span>
                        @endif
                    </p>
                </div>
                <a href="{{ route('posts.show', $share->post) }}"
                   class="text-xs text-blue-500 hover:underline flex-shrink-0">
                    Voir →
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

<script>
// Gestion du choix de destination
document.querySelectorAll('.dest-option').forEach(label => {
    label.addEventListener('click', function () {
        const shareId = this.dataset.share;
        const dest    = this.dataset.dest;

        // Désélectionner toutes les options du même share
        document.querySelectorAll(`.dest-option[data-share="${shareId}"]`).forEach(l => {
            l.classList.remove('border-blue-500', 'bg-blue-50', 'border-green-500', 'bg-green-50');
            l.classList.add('border-gray-200');
        });

        // Sélectionner celle cliquée
        if (dest === 'self_feed') {
            this.classList.remove('border-gray-200');
            this.classList.add('border-blue-500', 'bg-blue-50');
        } else {
            this.classList.remove('border-gray-200');
            this.classList.add('border-green-500', 'bg-green-50');
        }

        // Mettre à jour le champ caché
        document.getElementById(`dest-input-${shareId}`).value = dest;

        // Activer le bouton accepter
        const btn   = document.getElementById(`accept-btn-${shareId}`);
        const label2 = document.getElementById(`accept-label-${shareId}`);
        btn.disabled = false;
        btn.classList.remove('opacity-60', 'cursor-not-allowed');
        btn.classList.add('hover:bg-blue-700');

        if (dest === 'self_feed') {
            label2.textContent = "Accepter → Fil d'actualité";
        } else {
            label2.textContent = "Accepter → Mon profil";
        }
    });
});
</script>
@endsection