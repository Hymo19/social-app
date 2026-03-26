@extends('layouts.app')
@section('title', 'Nouveau post')
@section('content')
<div class="max-w-2xl mx-auto mt-6">
    <div class="bg-white rounded-2xl shadow p-6">
        <h1 class="text-xl font-bold text-gray-800 mb-5">
            <i class="fa-solid fa-pen-to-square mr-2 text-blue-600"></i>Nouvelle publication
        </h1>
        <form method="POST" action="{{ route('posts.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="space-y-4">

                <div>
                    <label class="text-sm font-semibold text-gray-600 mb-1 block">Titre *</label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
                           placeholder="Titre de votre post..."/>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-600 mb-1 block">Contenu *</label>
                    <textarea name="content" rows="5" required
                              class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 resize-none"
                              placeholder="Quoi de neuf ?">{{ old('content') }}</textarea>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-600 mb-1 block">
                        <i class="fa-solid fa-image mr-1 text-green-500"></i>Photo (optionnel)
                    </label>
                    <input type="file" name="image" accept="image/*"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none"/>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-600 mb-1 block">
                        <i class="fa-solid fa-eye mr-1 text-blue-500"></i>Visibilité
                    </label>
                    <select name="visibility"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <option value="public"  {{ old('visibility')=='public'  ? 'selected':'' }}>🌍 Public</option>
                        <option value="friends" {{ old('visibility')=='friends' ? 'selected':'' }}>👥 Amis uniquement</option>
                        <option value="private" {{ old('visibility')=='private' ? 'selected':'' }}>🔒 Privé</option>
                    </select>
                </div>

                {{-- ✅ Humeur optionnelle --}}
                <div>
                    <label class="text-sm font-semibold text-gray-600 mb-2 block">
                        <i class="fa-solid fa-face-smile mr-1 text-yellow-500"></i>Humeur (optionnel)
                    </label>
                    <input type="hidden" name="mood" id="mood-input" value="{{ old('mood') }}"/>
                    <div class="grid grid-cols-6 gap-2">
                        @foreach([
                            ['key'=>'joie',    'emoji'=>'😊', 'label'=>'Joie',    'color'=>'#fbbf24'],
                            ['key'=>'colere',  'emoji'=>'😡', 'label'=>'Colère',  'color'=>'#ef4444'],
                            ['key'=>'danse',   'emoji'=>'💃', 'label'=>'Danse',   'color'=>'#a78bfa'],
                            ['key'=>'furieux', 'emoji'=>'🤬', 'label'=>'Furieux', 'color'=>'#dc2626'],
                            ['key'=>'triste',  'emoji'=>'😢', 'label'=>'Triste',  'color'=>'#60a5fa'],
                            ['key'=>'musique', 'emoji'=>'🎵', 'label'=>'Musique', 'color'=>'#34d399'],
                        ] as $m)
                        <button type="button"
                                onclick="selectMood('{{ $m['key'] }}')"
                                id="mood-btn-{{ $m['key'] }}"
                                class="flex flex-col items-center p-2 rounded-xl border-2 border-gray-100 hover:border-blue-300 transition text-xs font-medium
                                       {{ old('mood') === $m['key'] ? 'border-blue-500 bg-blue-50' : '' }}">
                            <span class="text-2xl">{{ $m['emoji'] }}</span>
                            <span class="text-gray-500 mt-0.5" style="font-size:0.65rem">{{ $m['label'] }}</span>
                        </button>
                        @endforeach
                    </div>
                    <div id="mood-selected" class="hidden mt-2">
                        <span class="text-xs text-gray-500">Humeur sélectionnée :
                            <span id="mood-selected-label" class="font-semibold text-blue-600"></span>
                            <button type="button" onclick="clearMood()" class="ml-1 text-red-400 hover:text-red-600">✕ Effacer</button>
                        </span>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="flex-1 bg-blue-600 text-white py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition">
                        <i class="fa-solid fa-paper-plane mr-1"></i>Publier
                    </button>
                    <a href="{{ route('feed') }}"
                       class="flex-1 text-center bg-gray-100 text-gray-700 py-2.5 rounded-xl font-semibold hover:bg-gray-200 transition">
                        Annuler
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const MOODS = {
    joie:    {emoji:'😊', label:'en joie'},
    colere:  {emoji:'😡', label:'en colère'},
    danse:   {emoji:'💃', label:'en train de danser'},
    furieux: {emoji:'🤬', label:'furieux(se)'},
    triste:  {emoji:'😢', label:'triste'},
    musique: {emoji:'🎵', label:'en mode musique'},
};

function selectMood(key) {
    // Reset tous les boutons
    document.querySelectorAll('[id^="mood-btn-"]').forEach(btn => {
        btn.classList.remove('border-blue-500', 'bg-blue-50');
        btn.classList.add('border-gray-100');
    });
    // Activer le sélectionné
    const btn = document.getElementById(`mood-btn-${key}`);
    btn.classList.add('border-blue-500', 'bg-blue-50');
    btn.classList.remove('border-gray-100');
    // Mettre à jour le champ caché
    document.getElementById('mood-input').value = key;
    // Afficher le label
    const m = MOODS[key];
    document.getElementById('mood-selected').classList.remove('hidden');
    document.getElementById('mood-selected-label').textContent = `${m.emoji} ${m.label}`;
}

function clearMood() {
    document.querySelectorAll('[id^="mood-btn-"]').forEach(btn => {
        btn.classList.remove('border-blue-500', 'bg-blue-50');
        btn.classList.add('border-gray-100');
    });
    document.getElementById('mood-input').value = '';
    document.getElementById('mood-selected').classList.add('hidden');
}

// Initialiser si old('mood') existe
@if(old('mood'))
selectMood('{{ old('mood') }}');
@endif
</script>
@endsection