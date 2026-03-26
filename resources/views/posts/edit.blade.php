@extends('layouts.app')

@section('title', 'Modifier le post')

@section('content')
<div class="max-w-2xl mx-auto mt-6">
    <div class="bg-white rounded-2xl shadow p-6">

        {{-- Header --}}
        <h2 class="text-xl font-bold text-gray-800 mb-6">
            <i class="fa-solid fa-pen-to-square mr-2 text-blue-600"></i>Modifier le post
        </h2>

        <form method="POST" action="{{ route('posts.update', $post) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            {{-- Titre --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fa-solid fa-heading mr-1"></i>Titre
                </label>
                <input
                    type="text"
                    name="title"
                    value="{{ old('title', $post->title) }}"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 @error('title') border-red-400 @enderror"
                />
                @error('title')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Contenu --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fa-solid fa-align-left mr-1"></i>Contenu
                </label>
                <textarea
                    name="content"
                    rows="5"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 @error('content') border-red-400 @enderror"
                >{{ old('content', $post->content) }}</textarea>
                @error('content')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Image actuelle --}}
            @if($post->image)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fa-solid fa-image mr-1"></i>Image actuelle
                </label>
                <img src="{{ Storage::url($post->image) }}"
                     class="w-full max-h-48 object-cover rounded-lg mb-2"/>
                <p class="text-xs text-gray-400">Uploade une nouvelle image pour la remplacer</p>
            </div>
            @endif

            {{-- Nouvelle image --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fa-solid fa-upload mr-1"></i>Nouvelle image (optionnel)
                </label>
                <input
                    type="file"
                    name="image"
                    accept="image/*"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                />
                @error('image')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Visibilité --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fa-solid fa-eye mr-1"></i>Visibilité
                </label>
                <select name="visibility"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="public"  {{ old('visibility', $post->visibility) === 'public'  ? 'selected' : '' }}>🌍 Public</option>
                    <option value="friends" {{ old('visibility', $post->visibility) === 'friends' ? 'selected' : '' }}>👥 Amis seulement</option>
                    <option value="private" {{ old('visibility', $post->visibility) === 'private' ? 'selected' : '' }}>🔒 Privé</option>
                </select>
            </div>

            {{-- Boutons --}}
            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                    <i class="fa-solid fa-floppy-disk mr-2"></i>Enregistrer
                </button>
                <a href="{{ route('posts.show', $post) }}"
                    class="flex-1 text-center bg-gray-100 text-gray-700 py-2 rounded-lg font-semibold hover:bg-gray-200 transition">
                    <i class="fa-solid fa-xmark mr-2"></i>Annuler
                </a>
            </div>

        </form>
    </div>
</div>
@endsection