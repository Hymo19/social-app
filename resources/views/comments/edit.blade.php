@extends('layouts.app')

@section('title', 'Modifier le commentaire')

@section('content')
<div class="max-w-2xl mx-auto mt-6">
    <div class="bg-white rounded-2xl shadow p-6">

        {{-- Header --}}
        <h2 class="text-xl font-bold text-gray-800 mb-6">
            <i class="fa-solid fa-pen-to-square mr-2 text-blue-600"></i>Modifier le commentaire
        </h2>

        <form method="POST" action="{{ route('comments.update', $comment) }}" class="space-y-4">
            @csrf
            @method('PUT')

            {{-- Post associé --}}
            <div class="bg-gray-50 rounded-xl p-4 mb-2">
                <p class="text-xs text-gray-400 mb-1">
                    <i class="fa-solid fa-file-lines mr-1"></i>Commentaire sur le post :
                </p>
                <p class="text-sm font-semibold text-gray-700">{{ $comment->post->title }}</p>
            </div>

            {{-- Contenu --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fa-solid fa-comment mr-1"></i>Commentaire
                </label>
                <textarea
                    name="content"
                    rows="4"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 @error('content') border-red-400 @enderror"
                >{{ old('content', $comment->content) }}</textarea>
                @error('content')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Boutons --}}
            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                    <i class="fa-solid fa-floppy-disk mr-2"></i>Enregistrer
                </button>
                <a href="{{ route('posts.show', $comment->post_id) }}"
                    class="flex-1 text-center bg-gray-100 text-gray-700 py-2 rounded-lg font-semibold hover:bg-gray-200 transition">
                    <i class="fa-solid fa-xmark mr-2"></i>Annuler
                </a>
            </div>

        </form>
    </div>
</div>
@endsection