@extends('layouts.app')
@section('title', 'Republier')
@section('content')

<div class="max-w-xl mx-auto mt-8 px-4">
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

    <form method="POST" action="{{ route('posts.repost', $post) }}" class="p-5 space-y-4">
        @csrf

        {{-- Zone avis --}}
        <div class="flex gap-3">
            @if(auth()->user()->avatar)
                <img src="{{ Storage::url(auth()->user()->avatar) }}" class="w-10 h-10 rounded-full object-cover flex-shrink-0"/>
            @else
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-lg flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->name,0,1)) }}
                </div>
            @endif
            <textarea name="shared_comment" rows="3" autofocus
                placeholder="Dites quelque chose..."
                class="flex-1 text-sm text-gray-800 placeholder-gray-400 focus:outline-none resize-none bg-transparent border-0 leading-relaxed pt-1"
            >{{ old('shared_comment') }}</textarea>
        </div>

        {{-- Post racine affiché proprement --}}
        @php
            // Remonter au post racine
            $root = $post;
            while ($root->shared_post_id && $root->sharedPost) {
                $root = $root->sharedPost;
            }
        @endphp

        <div class="border border-gray-200 rounded-xl overflow-hidden">
            <div class="flex items-center gap-3 px-4 pt-3 pb-2">
                @if($root->user->avatar)
                    <img src="{{ Storage::url($root->user->avatar) }}" class="w-8 h-8 rounded-full object-cover flex-shrink-0"/>
                @else
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm flex-shrink-0">
                        {{ strtoupper(substr($root->user->name,0,1)) }}
                    </div>
                @endif
                <div>
                    <p class="font-semibold text-sm text-gray-800">{{ $root->user->name }}</p>
                    <p class="text-xs text-gray-400">{{ $root->created_at->diffForHumans() }}</p>
                </div>
            </div>
            @if($root->title || $root->content)
            <div class="px-4 pb-3">
                @if($root->title)<p class="font-bold text-gray-800 text-sm mb-1">{{ $root->title }}</p>@endif
                @if($root->content)<p class="text-gray-600 text-sm leading-relaxed">{{ Str::limit($root->content, 200) }}</p>@endif
            </div>
            @endif
            @if($root->image)
                <img src="{{ Storage::url($root->image) }}" class="w-full max-h-72 object-cover"/>
            @endif
        </div>

        {{-- Boutons --}}
        <div class="flex items-center gap-3 pt-1 border-t border-gray-100">
            <select name="visibility" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none bg-gray-50">
                <option value="public">🌍 Public</option>
                <option value="friends">👥 Amis</option>
                <option value="private">🔒 Privé</option>
            </select>
            <a href="{{ url()->previous() }}" class="flex-1 text-center bg-gray-100 text-gray-700 py-2.5 rounded-xl font-semibold hover:bg-gray-200 transition text-sm">
                Annuler
            </a>
            <button type="submit" class="flex-1 bg-green-600 text-white py-2.5 rounded-xl font-semibold hover:bg-green-700 transition flex items-center justify-center gap-2 text-sm">
                <i class="fa-solid fa-retweet"></i>
                Republier
            </button>
        </div>
    </form>
</div>
</div>
@endsection