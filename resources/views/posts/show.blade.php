@extends('layouts.app')
@section('title', $post->title ?: 'Post')
@section('content')

@php
function renderMentions($content, $mentionUsers) {
    return preg_replace_callback('/@([\w\s\-\.]+?)(\s|$)/', function($matches) use ($mentionUsers) {
        $name = trim($matches[1]);
        if (isset($mentionUsers[$name])) {
            $user = $mentionUsers[$name];
            $url = route('profile.show', $user);
            return '<a href="'.$url.'" class="text-blue-600 font-semibold hover:underline">'.$name.'</a>'.$matches[2];
        }
        return $matches[0];
    }, $content);
}

$reactionGroups = $post->reactions->groupBy('reaction')->map->count();
$myReaction     = $post->reactions->where('user_id', auth()->id())->first()?->reaction;
$moodBadge      = $post->getMoodBadge();
$likeCount      = $post->likes->count();
$thumbsCount    = $likeCount + ($reactionGroups['👍'] ?? 0);
$otherReactions = $reactionGroups->except('👍');
$totalReactions = $reactionGroups->sum() + $likeCount;
$reactionDetails = $post->reactions->groupBy('reaction')->map(fn($group) =>
    $group->map(fn($r) => $r->user->name)->values()
);
$likers = $post->likes()->with('user')->get()->pluck('user.name');

// Post imbriqué si c'est un repost
$sharedRef  = $post->sharedPost ?? null;
$sharedRoot = null;
if ($sharedRef && $sharedRef->shared_post_id) {
    $tmp = $sharedRef->sharedPost;
    $d = 0;
    while ($tmp && $tmp->shared_post_id && $tmp->sharedPost && $d < 5) {
        $tmp = $tmp->sharedPost;
        $d++;
    }
    $sharedRoot = $tmp;
}
@endphp

<style>
.reaction-popup-post{display:none;position:absolute;bottom:calc(100% + 8px);left:50%;transform:translateX(-50%);background:white;border-radius:999px;padding:6px 10px;box-shadow:0 8px 24px rgba(0,0,0,0.15);border:1px solid #e5e7eb;white-space:nowrap;z-index:50;}
.reaction-popup-post.open{display:flex;align-items:center;gap:4px;}
.reaction-emoji-post{font-size:1.6rem;cursor:pointer;transition:transform 0.15s;border:none;background:none;padding:4px;}
.reaction-emoji-post:hover{transform:scale(1.4);}
.reaction-popup-cm{display:none;position:absolute;bottom:calc(100% + 6px);left:0;background:white;border:1px solid #e5e7eb;border-radius:999px;padding:5px 10px;box-shadow:0 8px 24px rgba(0,0,0,0.13);white-space:nowrap;z-index:60;}
.reaction-popup-cm.open{display:flex;align-items:center;gap:4px;}
.mood-badge{display:inline-flex;align-items:center;gap:3px;font-size:0.7rem;font-weight:600;padding:2px 8px;border-radius:999px;margin-left:6px;}
.reactions-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;}
.reactions-modal-overlay.open{display:flex;}
</style>

<div class="max-w-2xl mx-auto mt-6 space-y-4">

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 pt-5 pb-3">
            <div class="flex items-center gap-3">
                @if($post->user->avatar)
                    <img src="{{ Storage::url($post->user->avatar) }}" class="w-12 h-12 rounded-full object-cover border-2 border-blue-100"/>
                @else
                    <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xl font-bold">
                        {{ strtoupper(substr($post->user->name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <div class="flex items-center flex-wrap gap-1">
                        <a href="{{ route('profile.show', $post->user) }}" class="font-semibold text-gray-800 hover:text-blue-600 transition">
                            {{ $post->user->name }}
                        </a>
                        @if($moodBadge)
                            <span class="mood-badge" style="background:{{ $moodBadge['color'] }}20;color:{{ $moodBadge['color'] }}">
                                {{ $moodBadge['emoji'] }} {{ $moodBadge['label'] }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-400 flex items-center gap-1">
                        {{ $post->created_at->diffForHumans() }} ·
                        @if($post->visibility==='public') <i class="fa-solid fa-earth-americas"></i>
                        @elseif($post->visibility==='friends') <i class="fa-solid fa-user-group"></i>
                        @else <i class="fa-solid fa-lock"></i> @endif
                    </p>
                </div>
            </div>
            @can('update', $post)
            <div class="flex gap-2">
                <a href="{{ route('posts.edit', $post) }}" class="w-9 h-9 rounded-full bg-gray-100 hover:bg-blue-50 flex items-center justify-center text-gray-500 hover:text-blue-600 transition">
                    <i class="fa-solid fa-pen text-sm"></i>
                </a>
                <form method="POST" action="{{ route('posts.destroy', $post) }}" class="inline">
                    @csrf @method('DELETE')
                    <button onclick="return confirm('Supprimer ce post ?')" class="w-9 h-9 rounded-full bg-gray-100 hover:bg-red-50 flex items-center justify-center text-gray-500 hover:text-red-500 transition">
                        <i class="fa-solid fa-trash text-sm"></i>
                    </button>
                </form>
            </div>
            @endcan
        </div>

        {{-- Contenu du post --}}
        @if($post->title || $post->content)
        <div class="px-5 pb-3">
            @if($post->title)<h1 class="text-xl font-bold text-gray-800 mb-2">{{ $post->title }}</h1>@endif
            @if($post->content)<p class="text-gray-700 leading-relaxed">{{ $post->content }}</p>@endif
        </div>
        @endif

        {{-- Image directe (post simple) --}}
        @if($post->image && !$post->shared_post_id)
        <div class="bg-gray-100">
            <img src="{{ Storage::url($post->image) }}" class="w-full object-contain max-h-[500px] cursor-pointer"
                 onclick="window.open('{{ Storage::url($post->image) }}','_blank')"/>
        </div>
        @endif

        {{-- POST IMBRIQUÉ si c'est un repost --}}
        @if($sharedRef)
        <div class="mx-4 mb-3 border border-gray-200 rounded-xl overflow-hidden bg-white">

            {{-- Header du post republié --}}
            <div class="flex items-center gap-2 px-4 pt-3 pb-2">
                @if($sharedRef->user->avatar)
                    <img src="{{ Storage::url($sharedRef->user->avatar) }}" class="w-8 h-8 rounded-full object-cover flex-shrink-0"/>
                @else
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm flex-shrink-0">{{ strtoupper(substr($sharedRef->user->name,0,1)) }}</div>
                @endif
                <div>
                    <a href="{{ route('profile.show', $sharedRef->user) }}" class="font-semibold text-sm text-gray-800 hover:text-blue-600">{{ $sharedRef->user->name }}</a>
                    <p class="text-xs text-gray-400">{{ $sharedRef->created_at->diffForHumans() }}</p>
                </div>
            </div>

            {{-- Avis/contenu du post republié --}}
            @if($sharedRef->content)
            <div class="px-4 pb-2">
                <p class="text-gray-700 text-sm leading-relaxed">{{ $sharedRef->content }}</p>
            </div>
            @endif
            @if($sharedRef->title)
            <div class="px-4 pb-2">
                <p class="font-bold text-gray-800 text-sm">{{ $sharedRef->title }}</p>
            </div>
            @endif

            {{-- Si le post republié est lui-même un repost → afficher le post original --}}
            @if($sharedRoot)
            <div class="mx-3 mb-3 border border-gray-200 rounded-xl overflow-hidden bg-gray-50">
                <div class="flex items-center gap-2 px-3 pt-2 pb-1">
                    @if($sharedRoot->user->avatar)
                        <img src="{{ Storage::url($sharedRoot->user->avatar) }}" class="w-6 h-6 rounded-full object-cover flex-shrink-0"/>
                    @else
                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs flex-shrink-0">{{ strtoupper(substr($sharedRoot->user->name,0,1)) }}</div>
                    @endif
                    <div>
                        <p class="font-semibold text-xs text-gray-800">{{ $sharedRoot->user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $sharedRoot->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @if($sharedRoot->title || $sharedRoot->content)
                <div class="px-3 pb-2">
                    @if($sharedRoot->title)<p class="font-bold text-gray-800 text-sm mb-1">{{ $sharedRoot->title }}</p>@endif
                    @if($sharedRoot->content)<p class="text-gray-600 text-sm leading-relaxed">{{ $sharedRoot->content }}</p>@endif
                </div>
                @endif
                @if($sharedRoot->image)
                    <img src="{{ Storage::url($sharedRoot->image) }}" class="w-full max-h-64 object-cover"/>
                @endif
            </div>
            @else
            {{-- Post simple : image directe --}}
            @if($sharedRef->image)
                <img src="{{ Storage::url($sharedRef->image) }}" class="w-full max-h-64 object-cover"/>
            @endif
            @endif
        </div>
        @endif

        {{-- Compteurs réactions --}}
        @if($totalReactions > 0)
        <div class="px-5 py-3 flex items-center justify-between border-b border-gray-100">
            <button onclick="openReactionsModal()" class="flex items-center gap-1.5 hover:underline group">
                <div class="flex -space-x-1">
                    @if($thumbsCount > 0)
                    <span class="w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center text-xs border-2 border-white z-10">👍</span>
                    @endif
                    @foreach($otherReactions->take(2) as $emoji => $count)
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs border-2 border-white" style="background:#f3f4f6">{{ $emoji }}</span>
                    @endforeach
                </div>
                <span class="text-sm text-gray-500 group-hover:text-blue-600 transition">
                    @if($myReaction || $post->isLikedBy(auth()->user()))
                        Toi @if($totalReactions > 1) et {{ $totalReactions - 1 }} autre(s) @endif
                    @else
                        {{ $totalReactions }} réaction(s)
                    @endif
                </span>
            </button>
            <span class="text-sm text-gray-400">{{ $post->comments->count() }} commentaire(s)</span>
        </div>
        @endif

        {{-- Boutons d'action --}}
        <div class="px-5 py-1 flex items-center gap-1">
            {{-- J'aime --}}
            <div class="relative flex-1" id="react-wrap-show" onmouseleave="scheduleHideShowPicker()">
                <div class="reaction-popup-post" id="react-picker-show" onmouseenter="cancelHideShowPicker()">
                    @foreach(['👍'=>'J\'aime','❤️'=>'J\'adore','😂'=>'Haha','😮'=>'Wow','😢'=>'Triste','😡'=>'Grrr'] as $emoji => $label)
                    <button class="reaction-emoji-post" onclick="sendReactionShow('{{ $emoji }}')" title="{{ $label }}">{{ $emoji }}</button>
                    @endforeach
                </div>
                <button onmouseenter="showShowPicker()" onclick="sendReactionShow('👍')" id="react-btn-show"
                        class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-semibold transition {{ $myReaction ? 'bg-blue-50' : 'text-gray-500 hover:bg-gray-100' }}"
                        @if($myReaction) style="color:{{ ['👍'=>'#2563eb','❤️'=>'#ef4444','😂'=>'#fbbf24','😮'=>'#f59e0b','😢'=>'#60a5fa','😡'=>'#ef4444'][$myReaction] ?? '#2563eb' }}" @endif>
                    @if($myReaction)
                        <span style="font-size:1.2rem">{{ $myReaction }}</span>
                        <span>{{ ['👍'=>'J\'aime','❤️'=>'J\'adore','😂'=>'Haha','😮'=>'Wow','😢'=>'Triste','😡'=>'Grrr'][$myReaction] ?? '' }}</span>
                    @elseif($post->isLikedBy(auth()->user()))
                        <i class="fa-solid fa-thumbs-up text-blue-600"></i><span class="text-blue-600">J'aime</span>
                    @else
                        <i class="fa-regular fa-thumbs-up"></i>J'aime
                    @endif
                </button>
            </div>

            {{-- Commenter --}}
            <button onclick="document.getElementById('main-comment-input').focus()"
                    class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-semibold text-gray-500 hover:bg-gray-100 transition">
                <i class="fa-regular fa-comment"></i>Commenter
            </button>

            {{-- Republier --}}
            <a href="{{ route('posts.repost.show', $post) }}"
               class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-semibold text-gray-500 hover:bg-green-50 hover:text-green-600 transition">
                <i class="fa-solid fa-retweet"></i>Republier
            </a>

            {{-- Partager --}}
            <a href="{{ route('shares.create', $post) }}"
               class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-semibold text-gray-500 hover:bg-blue-50 hover:text-blue-600 transition">
                <i class="fa-solid fa-share-nodes"></i>Partager
            </a>
        </div>

        <div id="reactions-display-show" class="px-5 pb-2 flex flex-wrap gap-2"></div>
    </div>

    {{-- Commentaires --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <h2 class="text-base font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-comments text-blue-600"></i>
            Commentaires ({{ $post->comments->count() }})
        </h2>

        <div class="flex gap-3 mb-5">
            @if(auth()->user()->avatar)
                <img src="{{ Storage::url(auth()->user()->avatar) }}" class="w-10 h-10 rounded-full object-cover border border-gray-200 flex-shrink-0"/>
            @else
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold flex-shrink-0">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            @endif
            <form method="POST" action="{{ route('comments.store', $post) }}" class="flex-1">
                @csrf
                <div class="flex gap-2">
                    <input type="text" name="content" id="main-comment-input"
                           placeholder="Écrire un commentaire..." autocomplete="off"
                           class="flex-1 bg-gray-100 rounded-full px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"/>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-full text-sm font-semibold hover:bg-blue-700 transition flex-shrink-0">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
            </form>
        </div>

        <div class="space-y-5">
            @forelse($post->comments as $comment)
            @php $myReactionC = $comment->getReactionBy(auth()->id()); @endphp
            <div class="flex gap-3" id="comment-{{ $comment->id }}">
                @if($comment->user->avatar)
                    <img src="{{ Storage::url($comment->user->avatar) }}" class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0 mt-1"/>
                @else
                    <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm flex-shrink-0 mt-1">{{ strtoupper(substr($comment->user->name, 0, 1)) }}</div>
                @endif
                <div class="flex-1">
                    <div class="bg-gray-100 rounded-2xl px-4 py-2.5 inline-block max-w-full">
                        <a href="{{ route('profile.show', $comment->user) }}" class="font-semibold text-sm text-gray-800 hover:text-blue-600 transition">{{ $comment->user->name }}</a>
                        <p class="text-sm text-gray-700 mt-0.5">{!! renderMentions($comment->content, $mentionUsers) !!}</p>
                    </div>
                    <div class="flex flex-wrap gap-1 mt-1 ml-1" id="reaction-counts-{{ $comment->id }}">
                        @foreach($comment->getReactionCounts() as $emoji => $count)
                        <div class="relative group">
                            <span class="bg-white border border-gray-200 rounded-full px-2 py-0.5 text-xs flex items-center gap-1 shadow-sm cursor-pointer hover:bg-gray-50">{{ $emoji }} {{ $count }}</span>
                            <div class="hidden group-hover:flex flex-col absolute bottom-8 left-0 bg-gray-900 text-white text-xs rounded-xl px-3 py-2 z-50 shadow-xl min-w-max">
                                <p class="font-bold mb-1">{{ $emoji }} {{ $count }} personne(s)</p>
                                @foreach($comment->reactions->where('reaction', $emoji) as $r)
                                    <p class="text-gray-300">{{ $r->user->name }}</p>
                                @endforeach
                                <div class="absolute -bottom-1 left-3 w-2 h-2 bg-gray-900 rotate-45"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="flex items-center gap-3 mt-1.5 ml-1 flex-wrap">
                        <span class="text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                        <div class="relative inline-block" id="reaction-wrapper-{{ $comment->id }}">
                            <button type="button" onmouseenter="showReactions({{ $comment->id }})"
                                    class="text-xs font-bold transition {{ $myReactionC ? 'text-blue-600' : 'text-gray-500 hover:text-blue-600' }}">
                                {{ $myReactionC ? $myReactionC->reaction . ' Réagi' : '👍 Réagir' }}
                            </button>
                            <div id="reaction-popup-{{ $comment->id }}"
                                 class="hidden absolute bottom-7 left-0 bg-white rounded-full shadow-xl border border-gray-100 px-3 py-2 flex gap-2 z-50 whitespace-nowrap"
                                 onmouseenter="keepReactions({{ $comment->id }})" onmouseleave="hideReactions({{ $comment->id }})">
                                @foreach(['👍','❤️','😂','😮','😢','😡'] as $reaction)
                                    <button type="button" onclick="sendReaction({{ $comment->id }}, '{{ $reaction }}')" class="text-2xl hover:scale-125 transition-transform cursor-pointer">{{ $reaction }}</button>
                                @endforeach
                            </div>
                        </div>
                        <button onclick="toggleReplyForm({{ $comment->id }}, '{{ $comment->user->name }}')" class="text-xs font-bold text-gray-500 hover:text-blue-600 transition">Répondre</button>
                        @can('update', $comment)<a href="{{ route('comments.edit', $comment) }}" class="text-xs text-blue-400 hover:text-blue-600 transition">Modifier</a>@endcan
                        @can('delete', $comment)
                        <form method="POST" action="{{ route('comments.destroy', $comment) }}" class="inline">
                            @csrf @method('DELETE')
                            <button onclick="return confirm('Supprimer ?')" class="text-xs text-red-400 hover:text-red-600 transition">Supprimer</button>
                        </form>
                        @endcan
                    </div>
                    <div id="reply-form-{{ $comment->id }}" class="hidden mt-3">
                        <div class="flex gap-2 items-center">
                            @if(auth()->user()->avatar)
                                <img src="{{ Storage::url(auth()->user()->avatar) }}" class="w-8 h-8 rounded-full object-cover border border-gray-200 flex-shrink-0"/>
                            @else
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs flex-shrink-0">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                            @endif
                            <form method="POST" action="{{ route('comments.store', $post) }}" class="flex-1 flex gap-2">
                                @csrf
                                <input type="hidden" name="parent_id" value="{{ $comment->id }}"/>
                                <input type="text" name="content" id="reply-input-{{ $comment->id }}" autocomplete="off"
                                       class="flex-1 bg-gray-100 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"/>
                                <button type="submit" class="bg-blue-600 text-white px-3 py-2 rounded-full text-xs font-semibold hover:bg-blue-700 transition flex-shrink-0"><i class="fa-solid fa-paper-plane"></i></button>
                            </form>
                        </div>
                    </div>
                    @if($comment->replies->count() > 0)
                    <div class="mt-3 ml-1">
                        <button onclick="toggleReplies({{ $comment->id }}, {{ $comment->replies->count() }})" id="toggle-replies-btn-{{ $comment->id }}"
                                class="text-xs text-blue-500 font-semibold hover:text-blue-700 transition flex items-center gap-1 mb-2">
                            <i class="fa-solid fa-chevron-down text-xs"></i> Voir les {{ $comment->replies->count() }} réponse(s)
                        </button>
                        <div id="replies-list-{{ $comment->id }}" class="hidden border-l-2 border-blue-100 pl-4 space-y-3">
                            @foreach($comment->replies as $index => $reply)
                            @php $myReplyReaction = $reply->getReactionBy(auth()->id()); @endphp
                            <div class="flex gap-2 {{ $index >= 2 ? 'hidden extra-reply-' . $comment->id : '' }}" id="reply-{{ $reply->id }}">
                                @if($reply->user->avatar)
                                    <img src="{{ Storage::url($reply->user->avatar) }}" class="w-8 h-8 rounded-full object-cover border border-gray-200 flex-shrink-0 mt-1"/>
                                @else
                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs flex-shrink-0 mt-1">{{ strtoupper(substr($reply->user->name, 0, 1)) }}</div>
                                @endif
                                <div class="flex-1">
                                    <div class="bg-gray-100 rounded-2xl px-4 py-2.5 inline-block max-w-full">
                                        <a href="{{ route('profile.show', $reply->user) }}" class="font-semibold text-sm text-gray-800 hover:text-blue-600 transition">{{ $reply->user->name }}</a>
                                        <p class="text-sm text-gray-700 mt-0.5">{!! renderMentions($reply->content, $mentionUsers) !!}</p>
                                    </div>
                                    <div class="flex flex-wrap gap-1 mt-1 ml-1" id="reaction-counts-{{ $reply->id }}">
                                        @foreach($reply->getReactionCounts() as $emoji => $count)
                                        <div class="relative group">
                                            <span class="bg-white border border-gray-200 rounded-full px-2 py-0.5 text-xs flex items-center gap-1 shadow-sm cursor-pointer hover:bg-gray-50">{{ $emoji }} {{ $count }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                    <div class="flex items-center gap-3 mt-1 ml-1 flex-wrap">
                                        <span class="text-xs text-gray-400">{{ $reply->created_at->diffForHumans() }}</span>
                                        <div class="relative inline-block" id="reaction-wrapper-{{ $reply->id }}">
                                            <button type="button" onmouseenter="showReactions({{ $reply->id }})"
                                                    class="text-xs font-bold transition {{ $myReplyReaction ? 'text-blue-600' : 'text-gray-500 hover:text-blue-600' }}">
                                                {{ $myReplyReaction ? $myReplyReaction->reaction . ' Réagi' : '👍 Réagir' }}
                                            </button>
                                            <div id="reaction-popup-{{ $reply->id }}"
                                                 class="hidden absolute bottom-7 left-0 bg-white rounded-full shadow-xl border border-gray-100 px-3 py-2 flex gap-2 z-50 whitespace-nowrap"
                                                 onmouseenter="keepReactions({{ $reply->id }})" onmouseleave="hideReactions({{ $reply->id }})">
                                                @foreach(['👍','❤️','😂','😮','😢','😡'] as $reaction)
                                                    <button type="button" onclick="sendReaction({{ $reply->id }}, '{{ $reaction }}')" class="text-2xl hover:scale-125 transition-transform cursor-pointer">{{ $reaction }}</button>
                                                @endforeach
                                            </div>
                                        </div>
                                        <button onclick="toggleReplyForm({{ $comment->id }}, '{{ $reply->user->name }}')" class="text-xs font-bold text-gray-500 hover:text-blue-600 transition">Répondre</button>
                                        @can('delete', $reply)
                                        <form method="POST" action="{{ route('comments.destroy', $reply) }}" class="inline">
                                            @csrf @method('DELETE')
                                            <button onclick="return confirm('Supprimer ?')" class="text-xs text-red-400 hover:text-red-600 transition">Supprimer</button>
                                        </form>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            @if($comment->replies->count() > 2)
                            <button onclick="showAllReplies({{ $comment->id }})" id="show-more-{{ $comment->id }}"
                                    class="text-xs text-blue-500 font-semibold hover:text-blue-700 transition flex items-center gap-1 mt-1">
                                <i class="fa-solid fa-rotate-left text-xs"></i> Voir {{ $comment->replies->count() - 2 }} réponse(s) de plus
                            </button>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="text-center text-gray-300 py-8">
                <i class="fa-regular fa-comment text-5xl mb-3 block"></i>
                <p class="text-sm text-gray-400">Sois le premier à commenter !</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Modal réactions --}}
<div class="reactions-modal-overlay" id="reactions-modal" onclick="if(event.target===this)closeReactionsModal()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800 text-lg">Réactions</h3>
            <button onclick="closeReactionsModal()" class="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center text-gray-600 transition"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="flex gap-1 px-4 pt-3 overflow-x-auto border-b border-gray-100" id="reaction-tabs">
            <button onclick="filterReaction('all')" id="tab-all" class="tab-btn px-3 py-1.5 rounded-full text-sm font-semibold transition bg-blue-600 text-white flex-shrink-0">Tout <span class="ml-1 opacity-80">{{ $totalReactions }}</span></button>
            @if($thumbsCount > 0)
            <button onclick="filterReaction('👍')" id="tab-👍" class="tab-btn px-3 py-1.5 rounded-full text-sm font-semibold transition bg-gray-100 text-gray-700 hover:bg-gray-200 flex-shrink-0">👍 <span class="ml-1 opacity-70">{{ $thumbsCount }}</span></button>
            @endif
            @foreach($otherReactions as $emoji => $count)
            <button onclick="filterReaction('{{ $emoji }}')" id="tab-{{ $emoji }}" class="tab-btn px-3 py-1.5 rounded-full text-sm font-semibold transition bg-gray-100 text-gray-700 hover:bg-gray-200 flex-shrink-0">{{ $emoji }} <span class="ml-1 opacity-70">{{ $count }}</span></button>
            @endforeach
        </div>
        <div class="max-h-80 overflow-y-auto px-4 py-3 space-y-3" id="reactions-list">
            @foreach($post->likes()->with('user')->get() as $like)
            <div class="reaction-row flex items-center gap-3" data-reaction="👍">
                <div class="relative flex-shrink-0">
                    @if($like->user->avatar)<img src="{{ Storage::url($like->user->avatar) }}" class="w-10 h-10 rounded-full object-cover border border-gray-200"/>
                    @else<div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">{{ strtoupper(substr($like->user->name,0,1)) }}</div>@endif
                    <span class="absolute -bottom-0.5 -right-0.5 w-5 h-5 bg-white rounded-full flex items-center justify-center text-xs border border-gray-200">👍</span>
                </div>
                <a href="{{ route('profile.show', $like->user) }}" class="font-semibold text-sm text-gray-800 hover:text-blue-600">{{ $like->user->name }}</a>
            </div>
            @endforeach
            @foreach($post->reactions()->with('user')->get() as $reaction)
            <div class="reaction-row flex items-center gap-3" data-reaction="{{ $reaction->reaction }}">
                <div class="relative flex-shrink-0">
                    @if($reaction->user->avatar)<img src="{{ Storage::url($reaction->user->avatar) }}" class="w-10 h-10 rounded-full object-cover border border-gray-200"/>
                    @else<div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">{{ strtoupper(substr($reaction->user->name,0,1)) }}</div>@endif
                    <span class="absolute -bottom-0.5 -right-0.5 w-5 h-5 bg-white rounded-full flex items-center justify-center text-xs border border-gray-200">{{ $reaction->reaction }}</span>
                </div>
                <div>
                    <a href="{{ route('profile.show', $reaction->user) }}" class="font-semibold text-sm text-gray-800 hover:text-blue-600">{{ $reaction->user->name }}</a>
                    <p class="text-xs text-gray-400">{{ $reaction->created_at->diffForHumans() }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<script>
const POST_CSRF    = document.querySelector('meta[name="csrf-token"]')?.content;
const REACTION_URL = '{{ route("posts.react", $post) }}';
const REACTION_LABELS_SHOW = {'👍':'J\'aime','❤️':'J\'adore','😂':'Haha','😮':'Wow','😢':'Triste','😡':'Grrr'};
const REACTION_COLORS_SHOW = {'👍':'#2563eb','❤️':'#ef4444','😂':'#fbbf24','😮':'#f59e0b','😢':'#60a5fa','😡':'#ef4444'};
let reactionTimers = {};
let showPickerTimer = null;

function showShowPicker(){clearTimeout(showPickerTimer);document.getElementById('react-picker-show')?.classList.add('open');}
function scheduleHideShowPicker(){showPickerTimer=setTimeout(()=>document.getElementById('react-picker-show')?.classList.remove('open'),400);}
function cancelHideShowPicker(){clearTimeout(showPickerTimer);}

function sendReactionShow(emoji){
    document.getElementById('react-picker-show')?.classList.remove('open');
    fetch(REACTION_URL,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':POST_CSRF,'Accept':'application/json'},body:JSON.stringify({reaction:emoji})})
    .then(r=>r.json()).then(data=>{if(!data.success)return;setTimeout(()=>location.reload(),300);});
}
function openReactionsModal(){document.getElementById('reactions-modal').classList.add('open');document.body.style.overflow='hidden';}
function closeReactionsModal(){document.getElementById('reactions-modal').classList.remove('open');document.body.style.overflow='';}
function filterReaction(type){
    document.querySelectorAll('.tab-btn').forEach(btn=>{btn.className=btn.className.replace('bg-blue-600 text-white','bg-gray-100 text-gray-700 hover:bg-gray-200');});
    const t=document.getElementById(`tab-${type}`);if(t)t.className=t.className.replace('bg-gray-100 text-gray-700 hover:bg-gray-200','bg-blue-600 text-white');
    document.querySelectorAll('.reaction-row').forEach(row=>{row.style.display=type==='all'?'flex':row.dataset.reaction===type?'flex':'none';});
}
function toggleReplyForm(commentId,userName){const f=document.getElementById(`reply-form-${commentId}`);const i=document.getElementById(`reply-input-${commentId}`);f.classList.remove('hidden');i.value=`@${userName} `;i.focus();}
function toggleReplies(commentId,total){const l=document.getElementById(`replies-list-${commentId}`);const b=document.getElementById(`toggle-replies-btn-${commentId}`);l.classList.toggle('hidden');b.innerHTML=l.classList.contains('hidden')?`<i class="fa-solid fa-chevron-down text-xs"></i> Voir les ${total} réponse(s)`:`<i class="fa-solid fa-chevron-up text-xs"></i> Masquer les réponses`;}
function showAllReplies(commentId){document.querySelectorAll(`.extra-reply-${commentId}`).forEach(el=>el.classList.remove('hidden'));document.getElementById(`show-more-${commentId}`)?.remove();}
function showReactions(id){clearTimeout(reactionTimers[id]);document.getElementById(`reaction-popup-${id}`)?.classList.remove('hidden');}
function keepReactions(id){clearTimeout(reactionTimers[id]);}
function hideReactions(id){reactionTimers[id]=setTimeout(()=>document.getElementById(`reaction-popup-${id}`)?.classList.add('hidden'),300);}
function sendReaction(commentId,reaction){
    fetch(`/comments/${commentId}/react`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':POST_CSRF,'Accept':'application/json'},body:JSON.stringify({reaction})})
    .then(res=>res.json()).then(data=>{
        document.getElementById(`reaction-popup-${commentId}`)?.classList.add('hidden');
        const btn=document.querySelector(`#reaction-wrapper-${commentId} button`);
        if(btn){btn.textContent=data.user_reaction?data.user_reaction+' Réagi':'👍 Réagir';btn.className=`text-xs font-bold transition ${data.user_reaction?'text-blue-600':'text-gray-500 hover:text-blue-600'}`;}
        const cd=document.getElementById(`reaction-counts-${commentId}`);
        if(cd){cd.innerHTML='';Object.entries(data.counts).forEach(([e,c])=>{cd.innerHTML+=`<span class="bg-white border border-gray-200 rounded-full px-2 py-0.5 text-xs">${e} ${c}</span>`;});}
    });
}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeReactionsModal();});
</script>
@endsection