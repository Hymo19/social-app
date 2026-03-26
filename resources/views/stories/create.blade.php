@extends('layouts.app')
@section('title', 'Créer une story')
@section('content')

<div class="max-w-2xl mx-auto mt-8">
    <div class="bg-white rounded-2xl shadow p-6">
        <h1 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <i class="fa-solid fa-circle-plus text-blue-600"></i>
            Créer une story
        </h1>

        {{-- Tabs type --}}
        <div class="flex gap-3 mb-6">
            <button onclick="setType('image')" id="tab-image"
                    class="tab-btn flex-1 py-2.5 rounded-xl text-sm font-semibold border-2 border-blue-600 bg-blue-600 text-white transition">
                <i class="fa-solid fa-image mr-1"></i> Photo
            </button>
            <button onclick="setType('video')" id="tab-video"
                    class="tab-btn flex-1 py-2.5 rounded-xl text-sm font-semibold border-2 border-gray-200 text-gray-600 hover:border-blue-400 transition">
                <i class="fa-solid fa-video mr-1"></i> Vidéo
            </button>
            <button onclick="setType('text')" id="tab-text"
                    class="tab-btn flex-1 py-2.5 rounded-xl text-sm font-semibold border-2 border-gray-200 text-gray-600 hover:border-blue-400 transition">
                <i class="fa-solid fa-font mr-1"></i> Texte
            </button>
        </div>

        <form method="POST" action="{{ route('stories.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="type" id="story-type" value="image"/>

            {{-- Section IMAGE --}}
            <div id="section-image" class="space-y-4">
                <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center cursor-pointer hover:border-blue-400 transition"
                     onclick="document.getElementById('media-image').click()">
                    <div id="image-preview" class="hidden mb-3">
                        <img id="img-preview-el" class="max-h-64 mx-auto rounded-xl object-cover"/>
                    </div>
                    <div id="image-placeholder">
                        <i class="fa-solid fa-cloud-arrow-up text-4xl text-gray-300 mb-2 block"></i>
                        <p class="text-sm text-gray-500">Clique pour ajouter une photo</p>
                        <p class="text-xs text-gray-400 mt-1">JPG, PNG, GIF — max 50MB</p>
                    </div>
                    <input type="file" id="media-image" name="media" accept="image/*" class="hidden"
                           onchange="previewImage(this)"/>
                </div>
            </div>

            {{-- Section VIDEO --}}
            <div id="section-video" class="space-y-4 hidden">
                <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center cursor-pointer hover:border-blue-400 transition"
                     onclick="document.getElementById('media-video').click()">
                    <div id="video-preview" class="hidden mb-3">
                        <video id="vid-preview-el" class="max-h-64 mx-auto rounded-xl" controls></video>
                    </div>
                    <div id="video-placeholder">
                        <i class="fa-solid fa-film text-4xl text-gray-300 mb-2 block"></i>
                        <p class="text-sm text-gray-500">Clique pour ajouter une vidéo</p>
                        <p class="text-xs text-gray-400 mt-1">MP4, MOV, WEBM — max 50MB</p>
                    </div>
                    <input type="file" id="media-video" name="media" accept="video/*" class="hidden"
                           onchange="previewVideo(this)"/>
                </div>
            </div>

            {{-- Section TEXTE --}}
            <div id="section-text" class="hidden">
                {{-- Preview en temps réel --}}
                <div id="text-preview-box"
                     class="w-full h-64 rounded-2xl flex items-center justify-center mb-4 transition-all"
                     style="background-color: #1877f2;">
                    <p id="text-preview-content"
                       class="text-white text-2xl font-bold text-center px-6 break-words max-w-full"
                       style="color: #ffffff;">
                        Votre texte ici...
                    </p>
                </div>

                <textarea name="text_content" id="text-content-input"
                          placeholder="Écrivez votre texte..."
                          maxlength="300"
                          oninput="updateTextPreview(this.value)"
                          class="w-full bg-gray-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 resize-none h-24 mb-4"></textarea>

                {{-- Couleurs de fond --}}
                <p class="text-xs font-semibold text-gray-500 mb-2">Couleur de fond</p>
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach(['#1877f2','#e74c3c','#2ecc71','#9b59b6','#f39c12','#1abc9c','#e91e63','#000000','#ff6b35','#2c3e50'] as $color)
                    <button type="button" onclick="setBgColor('{{ $color }}')"
                            class="w-8 h-8 rounded-full border-2 border-white shadow hover:scale-110 transition-transform"
                            style="background-color: {{ $color }}"></button>
                    @endforeach
                </div>
                <input type="hidden" name="bg_color" id="bg-color-input" value="#1877f2"/>

                {{-- Couleurs de texte --}}
                <p class="text-xs font-semibold text-gray-500 mb-2">Couleur du texte</p>
                <div class="flex gap-2 mb-4">
                    @foreach(['#ffffff','#000000','#f1c40f','#e74c3c','#2ecc71'] as $color)
                    <button type="button" onclick="setTextColor('{{ $color }}')"
                            class="w-8 h-8 rounded-full border-2 border-gray-300 shadow hover:scale-110 transition-transform"
                            style="background-color: {{ $color }}"></button>
                    @endforeach
                </div>
                <input type="hidden" name="text_color" id="text-color-input" value="#ffffff"/>
            </div>

            {{-- Musique (commune à tous les types) --}}
            <div class="mt-5 border-t pt-4">
                <p class="text-sm font-semibold text-gray-600 mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-music text-blue-500"></i> Ajouter de la musique (optionnel)
                </p>
                <div class="flex gap-3">
                    <div class="flex-1">
                        <input type="text" name="music_name"
                               placeholder="Nom du morceau (ex: Drake - God's Plan)"
                               class="w-full bg-gray-100 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 mb-2"/>
                        <label class="flex items-center gap-2 bg-gray-100 rounded-xl px-4 py-2.5 cursor-pointer hover:bg-gray-200 transition">
                            <i class="fa-solid fa-file-audio text-blue-500"></i>
                            <span id="music-label" class="text-sm text-gray-500">Choisir un fichier audio (MP3)</span>
                            <input type="file" name="music" accept="audio/*" class="hidden"
                                   onchange="document.getElementById('music-label').textContent = this.files[0].name"/>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Bouton publier --}}
            <div class="mt-6 flex gap-3">
                <a href="{{ route('feed') }}"
                   class="flex-1 text-center py-3 rounded-xl border-2 border-gray-200 text-gray-600 font-semibold hover:bg-gray-50 transition">
                    Annuler
                </a>
                <button type="submit"
                        class="flex-1 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">
                    <i class="fa-solid fa-paper-plane mr-2"></i> Publier la story
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentType = 'image';

function setType(type) {
    currentType = type;
    document.getElementById('story-type').value = type;
    ['image','video','text'].forEach(t => {
        document.getElementById(`section-${t}`).classList.toggle('hidden', t !== type);
        const tab = document.getElementById(`tab-${t}`);
        if (t === type) {
            tab.classList.add('bg-blue-600', 'text-white', 'border-blue-600');
            tab.classList.remove('text-gray-600', 'border-gray-200');
        } else {
            tab.classList.remove('bg-blue-600', 'text-white', 'border-blue-600');
            tab.classList.add('text-gray-600', 'border-gray-200');
        }
    });
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('img-preview-el').src = e.target.result;
            document.getElementById('image-preview').classList.remove('hidden');
            document.getElementById('image-placeholder').classList.add('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewVideo(input) {
    if (input.files && input.files[0]) {
        const url = URL.createObjectURL(input.files[0]);
        document.getElementById('vid-preview-el').src = url;
        document.getElementById('video-preview').classList.remove('hidden');
        document.getElementById('video-placeholder').classList.add('hidden');
    }
}

function updateTextPreview(val) {
    const el = document.getElementById('text-preview-content');
    el.textContent = val || 'Votre texte ici...';
}

function setBgColor(color) {
    document.getElementById('text-preview-box').style.backgroundColor = color;
    document.getElementById('bg-color-input').value = color;
}

function setTextColor(color) {
    document.getElementById('text-preview-content').style.color = color;
    document.getElementById('text-color-input').value = color;
}
</script>
@endsection