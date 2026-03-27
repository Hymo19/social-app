@extends('layouts.app')
@section('title', 'Feed')
@section('content')
{{-- amélioration de la page posts --}}
{{-- modification accidentelle sur master --}}
<style>
.post-truncated{display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;}
.reaction-popup-cm{display:none;position:absolute;bottom:calc(100% + 6px);left:0;background:white;border:1px solid #e5e7eb;border-radius:999px;padding:5px 10px;box-shadow:0 8px 24px rgba(0,0,0,0.13);white-space:nowrap;z-index:60;}
.reaction-popup-cm.open{display:flex;align-items:center;gap:4px;}
.post-reaction-picker{display:none;position:absolute;bottom:calc(100% + 6px);left:0;background:white;border-radius:999px;padding:4px 8px;box-shadow:0 4px 20px rgba(0,0,0,0.15);border:1px solid #e5e7eb;white-space:nowrap;z-index:50;}
.post-reaction-picker.open{display:flex;align-items:center;gap:2px;}
.post-reaction-emoji{font-size:1.3rem;cursor:pointer;transition:transform 0.12s;border:none;background:none;padding:3px 4px;border-radius:50%;line-height:1;}
.post-reaction-emoji:hover{transform:scale(1.35) translateY(-3px);}
.mood-badge{display:inline-flex;align-items:center;gap:3px;font-size:0.7rem;font-weight:600;padding:2px 8px;border-radius:999px;margin-left:6px;}
@keyframes modalPop{from{opacity:0;transform:translate(-50%,-50%) scale(0.93)}to{opacity:1;transform:translate(-50%,-50%) scale(1)}}
.repost-banner{background:#f0fdf4;border-bottom:1px solid #bbf7d0;padding:8px 20px;display:flex;align-items:center;gap:8px;font-size:0.8rem;color:#166534;}
.repost-banner a{font-weight:700;color:#15803d;text-decoration:none;}
.repost-banner a:hover{text-decoration:underline;}
.shared-banner{background:#eff6ff;border-bottom:1px solid #bfdbfe;padding:8px 20px;display:flex;align-items:center;gap:8px;font-size:0.8rem;color:#1e40af;}
.shared-banner a{font-weight:700;color:#1d4ed8;text-decoration:none;}
.shared-banner a:hover{text-decoration:underline;}
.chain-inner-banner{background:#f0fdf4;border-bottom:1px solid #bbf7d0;padding:5px 12px;display:flex;align-items:center;gap:6px;font-size:0.72rem;color:#166534;}
</style>

{{-- STORIES --}}
<div class="w-full bg-white shadow-sm border-b border-gray-100 px-6 py-4 mb-6">
    <div class="flex gap-4 overflow-x-auto pb-1" style="scrollbar-width:none;">
        <a href="{{ route('stories.create') }}" class="flex-shrink-0 flex flex-col items-center gap-2 group" style="width:120px;">
            <div class="relative w-full rounded-2xl overflow-hidden shadow-md group-hover:shadow-xl transition-all duration-300 group-hover:-translate-y-1" style="height:200px;background:linear-gradient(135deg,#dbeafe,#ede9fe);">
                @if(auth()->user()->avatar)
                    <img src="{{ Storage::url(auth()->user()->avatar) }}" class="absolute inset-0 w-full h-full object-cover opacity-80"/>
                @else
                    <div class="absolute inset-0 flex items-center justify-center"><i class="fa-solid fa-user text-5xl text-blue-200"></i></div>
                @endif
                <div class="absolute inset-0" style="background:linear-gradient(to top,rgba(0,0,0,0.5) 0%,transparent 60%)"></div>
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center border-4 border-white shadow-lg"><i class="fa-solid fa-plus text-white text-sm"></i></div>
            </div>
            <span class="text-xs font-bold text-blue-600">{{ $myStories > 0 ? 'Ma story' : 'Créer' }}</span>
        </a>
        @foreach($storyUsersData as $sd)
        @php $storyUser=$sd['user'];$allViewed=$sd['allViewed'];$firstStory=$sd['firstStory']; @endphp
        @if($firstStory)
        <button onclick="openStoryViewer({{ $storyUser->id }})" class="flex-shrink-0 flex flex-col items-center gap-2 group" style="width:120px;">
            <div class="relative w-full rounded-2xl overflow-hidden shadow-md group-hover:shadow-xl transition-all duration-300 group-hover:-translate-y-1" style="height:200px;">
                @if($firstStory->type==='text')
                    <div class="absolute inset-0 flex items-center justify-center p-3" style="background-color:{{ $firstStory->bg_color }}"><p class="text-sm font-bold text-center break-words" style="color:{{ $firstStory->text_color }}">{{ Str::limit($firstStory->text_content,50) }}</p></div>
                @elseif($firstStory->type==='video')
                    <video src="{{ Storage::url($firstStory->media) }}" class="absolute inset-0 w-full h-full object-cover"></video>
                @else
                    <img src="{{ Storage::url($firstStory->media) }}" class="absolute inset-0 w-full h-full object-cover"/>
                @endif
                <div class="absolute inset-0" style="background:linear-gradient(to top,rgba(0,0,0,0.6) 0%,transparent 55%)"></div>
                <div class="absolute top-3 left-3">
                    <div class="p-0.5 rounded-full {{ $allViewed ? 'bg-gray-400' : 'bg-gradient-to-tr from-blue-500 to-indigo-500' }} shadow-lg">
                        <div class="w-9 h-9 rounded-full overflow-hidden border-2 border-white">
                            @if($storyUser->avatar)
                                <img src="{{ Storage::url($storyUser->avatar) }}" class="w-full h-full object-cover"/>
                            @else
                                <div class="w-full h-full bg-blue-100 flex items-center justify-center text-blue-600 text-sm font-bold">{{ strtoupper(substr($storyUser->name,0,1)) }}</div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 right-0 px-2.5 pb-3">
                    <p class="text-white text-xs font-semibold truncate drop-shadow-md">{{ $storyUser->name }}</p>
                    @if(!$allViewed)<p class="text-blue-300 text-xs">Nouveau</p>@endif
                </div>
                @if(!$allViewed)<div class="absolute inset-0 rounded-2xl ring-2 ring-blue-500 ring-offset-1 pointer-events-none"></div>@endif
            </div>
            <span class="text-xs font-semibold {{ $allViewed ? 'text-gray-500' : 'text-blue-600' }} truncate w-full text-center">{{ $storyUser->name }}</span>
        </button>
        @endif
        @endforeach
    </div>
</div>

{{-- STORY VIEWER --}}
<div id="story-viewer" class="fixed inset-0 z-[9998] hidden" style="background:rgba(0,0,0,0.95);backdrop-filter:blur(10px);">
    <div class="flex items-center justify-center w-full h-full gap-6 px-4">
        <button onclick="prevUserStories()" id="sv-prev-user" class="hidden md:flex w-14 h-14 bg-white/10 hover:bg-white/25 rounded-full items-center justify-center text-white transition"><i class="fa-solid fa-chevron-left text-xl"></i></button>
        <div class="relative flex flex-col rounded-3xl overflow-hidden shadow-2xl flex-shrink-0" style="width:min(100vw,420px);height:min(100vh,750px);background:#000;">
            <div id="story-progress-bars" class="absolute top-0 left-0 right-0 z-30 flex gap-1 px-4 pt-4"></div>
            <div class="absolute top-8 left-0 right-0 z-30 flex items-center justify-between px-4">
                <div class="flex items-center gap-3">
                    <div id="sv-avatar" class="w-11 h-11 rounded-full overflow-hidden border-2 border-white shadow-lg flex-shrink-0"></div>
                    <div><p id="sv-name" class="text-white font-bold text-sm"></p><p id="sv-time" class="text-gray-300 text-xs"></p></div>
                </div>
                <div class="flex items-center gap-2">
                    <button id="sv-music-toggle" onclick="toggleStoryMusic()" class="hidden w-9 h-9 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition"><i id="sv-music-icon" class="fa-solid fa-volume-xmark text-white text-sm"></i></button>
                    <button id="sv-delete-btn" onclick="deleteCurrentStory()" class="hidden w-9 h-9 bg-red-500/80 hover:bg-red-600 rounded-full flex items-center justify-center transition"><i class="fa-solid fa-trash text-white text-xs"></i></button>
                    <button onclick="closeStoryViewer()" class="w-9 h-9 bg-white/15 hover:bg-white/30 rounded-full flex items-center justify-center transition"><i class="fa-solid fa-xmark text-white text-lg"></i></button>
                </div>
            </div>
            <div id="sv-content" class="absolute inset-0 flex items-center justify-center"></div>
            <button onclick="prevStory()" class="absolute left-0 top-0 w-1/3 h-full z-20"></button>
            <button onclick="nextStory()" class="absolute right-0 top-0 w-1/3 h-full z-20"></button>
            <div id="sv-music-bar" class="hidden absolute bottom-[120px] left-4 right-4 z-30 rounded-2xl px-4 py-3 flex items-center gap-3 border border-white/10" style="background:rgba(0,0,0,0.6);backdrop-filter:blur(12px);">
                <div class="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-music text-white text-sm animate-pulse"></i></div>
                <div class="flex-1 min-w-0"><p id="sv-music-name" class="text-white text-xs font-bold truncate"></p><p class="text-gray-400 text-xs">En écoute</p></div>
                <audio id="sv-audio" loop></audio>
            </div>
            <div id="sv-reaction-bar" class="absolute bottom-0 left-0 right-0 z-30 px-3 py-3" style="background:linear-gradient(to top,rgba(0,0,0,0.8) 0%,transparent 100%);">
                <div class="flex gap-2 mb-2 justify-center">
                    @foreach(['❤️','😮','😂','😢','🔥','👏'] as $emoji)
                    <button onclick="sendStoryReaction('{{ $emoji }}')" class="text-2xl hover:scale-125 transition-transform active:scale-150" style="filter:drop-shadow(0 1px 3px rgba(0,0,0,0.5))">{{ $emoji }}</button>
                    @endforeach
                </div>
                <div class="flex items-center gap-2">
                    @if(auth()->user()->avatar)
                        <img src="{{ Storage::url(auth()->user()->avatar) }}" class="w-8 h-8 rounded-full object-cover flex-shrink-0 border-2 border-white/50"/>
                    @else
                        <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0 border-2 border-white/50">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
                    @endif
                    <div class="flex-1 flex items-center gap-2 rounded-full px-3 py-2 border border-white/30" style="background:rgba(255,255,255,0.15);backdrop-filter:blur(8px);">
                        <input type="text" id="sv-comment-input" placeholder="Répondre à cette story..." autocomplete="off" onclick="pauseStoryTimer()" onblur="resumeStoryTimer()" class="flex-1 bg-transparent text-white text-sm placeholder-white/60 focus:outline-none"/>
                        <button onclick="sendStoryComment()" class="w-7 h-7 bg-blue-600 hover:bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0 transition"><i class="fa-solid fa-paper-plane text-white text-xs"></i></button>
                    </div>
                </div>
            </div>
            <div id="sv-toast" class="hidden absolute top-20 left-1/2 -translate-x-1/2 z-40 bg-white/20 backdrop-blur-sm text-white text-xs font-semibold px-4 py-2 rounded-full border border-white/20 whitespace-nowrap">Réaction envoyée ✓</div>
        </div>
        <button onclick="nextUserStories()" id="sv-next-user" class="hidden md:flex w-14 h-14 bg-white/10 hover:bg-white/25 rounded-full items-center justify-center text-white transition"><i class="fa-solid fa-chevron-right text-xl"></i></button>
    </div>
</div>

{{-- FEED --}}
<div class="max-w-7xl mx-auto px-4">
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Sidebar --}}
    <div class="hidden lg:block">
        <div class="bg-white rounded-2xl shadow p-5 sticky top-24">
            <div class="flex items-center gap-3 mb-4">
                @if(auth()->user()->avatar)
                    <img src="{{ Storage::url(auth()->user()->avatar) }}" class="w-14 h-14 rounded-full object-cover border-2 border-blue-400"/>
                @else
                    <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xl font-bold">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
                @endif
                <div>
                    <p class="font-semibold text-gray-800">{{ auth()->user()->name }}</p>
                    @php $sc=['online'=>'bg-green-400','away'=>'bg-yellow-400','busy'=>'bg-red-400','offline'=>'bg-gray-400'];$sl=['online'=>'En ligne','away'=>'Absent','busy'=>'Occupé','offline'=>'Hors ligne']; @endphp
                    <span class="flex items-center gap-1 text-sm text-gray-500"><span class="w-2 h-2 rounded-full {{ $sc[auth()->user()->status] ?? 'bg-gray-400' }}"></span>{{ $sl[auth()->user()->status] ?? 'Hors ligne' }}</span>
                </div>
            </div>
            @if(auth()->user()->bio)<p class="text-sm text-gray-500 mb-4">{{ auth()->user()->bio }}</p>@endif
            <div class="flex justify-around text-center border-t pt-4 mb-4">
                <div><p class="font-bold text-gray-800">{{ auth()->user()->posts()->count() }}</p><p class="text-xs text-gray-500">Posts</p></div>
                <div><p class="font-bold text-gray-800">{{ auth()->user()->followers()->count() }}</p><p class="text-xs text-gray-500">Abonnés</p></div>
                <div><p class="font-bold text-gray-800">{{ auth()->user()->friends()->count() }}</p><p class="text-xs text-gray-500">Amis</p></div>
            </div>
            <a href="{{ route('profile.show', auth()->user()) }}" class="block text-center bg-blue-50 text-blue-600 rounded-lg py-2 text-sm font-semibold hover:bg-blue-100 transition mb-2"><i class="fa-solid fa-user mr-1"></i>Voir mon profil</a>
            <a href="{{ route('posts.create') }}" class="block text-center bg-blue-600 text-white rounded-lg py-2 text-sm font-semibold hover:bg-blue-700 transition"><i class="fa-solid fa-plus mr-1"></i>Nouveau post</a>
            <form method="POST" action="{{ route('status.update') }}" class="mt-4 space-y-2">
                @csrf
                <select name="status" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="online" {{ auth()->user()->status==='online'?'selected':'' }}>🟢 En ligne</option>
                    <option value="away" {{ auth()->user()->status==='away'?'selected':'' }}>🟡 Absent</option>
                    <option value="busy" {{ auth()->user()->status==='busy'?'selected':'' }}>🔴 Occupé</option>
                    <option value="offline" {{ auth()->user()->status==='offline'?'selected':'' }}>⚫ Hors ligne</option>
                </select>
                <input type="text" name="status_text" value="{{ auth()->user()->status_text }}" placeholder="Votre statut..." class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
                <button type="submit" class="w-full bg-gray-100 text-gray-700 rounded-lg py-2 text-sm font-semibold hover:bg-gray-200 transition">Mettre à jour</button>
            </form>
        </div>
    </div>

    {{-- Posts --}}
    <div class="lg:col-span-2 space-y-4">

        @if($posts->isEmpty())
        <div class="bg-white rounded-2xl shadow p-10 text-center text-gray-400">
            <i class="fa-solid fa-newspaper text-5xl mb-3 block text-gray-200"></i>
            <p class="text-lg font-medium text-gray-500">Aucun post pour l'instant</p>
            <a href="{{ route('posts.create') }}" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-full text-sm font-semibold hover:bg-blue-700 transition mt-3"><i class="fa-solid fa-plus mr-1"></i>Créer un post</a>
        </div>
        @else
        @foreach($posts as $item)
        @php
            $type     = is_array($item) ? ($item['type'] ?? 'post') : 'post';
            $isPost   = $type === 'post';
            $isRepost = $type === 'repost';
            $isShare  = $type === 'share';

            $post      = null;
            $statsPost = null;
            $repostObj = null;
            $share     = null;

            if ($isShare) {
                $share     = $item['data'];
                $post      = $share->post ?? null;
                $statsPost = $post;
            } elseif ($isRepost) {
                $repostObj = $item['data'];
                $post      = $repostObj->sharedPost ?? null;
                $statsPost = $post;
                $depth = 0;
                while ($statsPost && $statsPost->shared_post_id && $depth < 10) {
                    $statsPost = $statsPost->sharedPost ?? $statsPost;
                    $depth++;
                }
            } else {
                $post      = is_array($item) ? ($item['data'] ?? null) : $item;
                $statsPost = $post;
            }
        @endphp
        @if($post && $statsPost)
        @php
            $actionPost     = $isRepost ? $repostObj : $post;
            $moodBadge      = $actionPost->getMoodBadge();
            $reactionGroups = $actionPost->reactions->groupBy('reaction')->map->count();
            $myReaction     = $actionPost->reactions->where('user_id', auth()->id())->first()?->reaction;
            $allReactions   = $reactionGroups->toArray();
            $allReactions['👍'] = ($allReactions['👍'] ?? 0) + $actionPost->likes->count();
            if (($allReactions['👍'] ?? 0) === 0) unset($allReactions['👍']);
            $totalReactions = array_sum($allReactions);
            $jsIndex        = $loop->index;
            $repostCount    = $actionPost->reposts()->count();
            $rootRepostCount = $statsPost->reposts()->count();
            $shareCount     = \App\Models\Share::where('post_id', $actionPost->id)->where('status','accepted')->count();
        @endphp

        <div class="bg-white rounded-2xl shadow-sm overflow-hidden hover:shadow-md transition-shadow {{ $isShare ? 'border border-blue-200' : ($isRepost ? 'border border-green-100' : 'border border-gray-100') }}"
             id="post-card-{{ $statsPost->id }}">

            {{-- BANNIÈRE REPOST --}}
            @if($isRepost && $repostObj)
            <div class="repost-banner">
                <i class="fa-solid fa-retweet flex-shrink-0"></i>
                @if($repostObj->user->avatar)
                    <img src="{{ Storage::url($repostObj->user->avatar) }}" class="w-5 h-5 rounded-full object-cover flex-shrink-0"/>
                @endif
                <span>
                    <a href="{{ route('profile.show', $repostObj->user) }}" onclick="event.stopPropagation()">{{ $repostObj->user->name }}</a>
                    a republié
                </span>
                <span class="ml-auto flex-shrink-0 text-xs" style="color:#9ca3af">{{ $repostObj->created_at->diffForHumans() }}</span>
            </div>
            @endif

            {{-- BANNIÈRE PARTAGE --}}
            @if($isShare && $share)
            <div class="shared-banner">
                <i class="fa-solid fa-share-nodes flex-shrink-0"></i>
                @if($share->sender->avatar)
                    <img src="{{ Storage::url($share->sender->avatar) }}" class="w-5 h-5 rounded-full object-cover flex-shrink-0"/>
                @endif
                <span>
                    <a href="{{ route('profile.show', $share->sender) }}" onclick="event.stopPropagation()">{{ $share->sender->name }}</a>
                    a partagé la publication de
                    <a href="{{ route('profile.show', $post->user) }}" onclick="event.stopPropagation()">{{ $post->user->name }}</a>
                    @if($share->message) · <span style="font-weight:400;color:#6b83b8">"{{ $share->message }}"</span>@endif
                </span>
                <span class="ml-auto flex-shrink-0 text-xs" style="color:#9ca3af">{{ $share->created_at->diffForHumans() }}</span>
            </div>
            @endif

            {{-- HEADER --}}
            <div class="flex items-center justify-between px-5 pt-4 pb-2">
                <a href="{{ route('profile.show', $isRepost ? $repostObj->user : $post->user) }}" onclick="event.stopPropagation()" class="flex items-center gap-3 group">
                    @php $headerUser = $isRepost ? $repostObj->user : $post->user; @endphp
                    @if($headerUser->avatar)
                        <img src="{{ Storage::url($headerUser->avatar) }}" class="w-11 h-11 rounded-full object-cover border-2 border-gray-100 group-hover:border-blue-300 transition"/>
                    @else
                        <div class="w-11 h-11 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-lg">{{ strtoupper(substr($headerUser->name,0,1)) }}</div>
                    @endif
                    <div>
                        <div class="flex items-center flex-wrap gap-1">
                            <p class="font-semibold text-gray-800 group-hover:text-blue-600 transition text-sm">{{ $headerUser->name }}</p>
                            @if(!$isRepost && $moodBadge)
                                <span class="mood-badge" style="background:{{ $moodBadge['color'] }}20;color:{{ $moodBadge['color'] }}">{{ $moodBadge['emoji'] }} {{ $moodBadge['label'] }}</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 flex items-center gap-1">
                            {{ $isRepost ? $repostObj->created_at->diffForHumans() : $post->created_at->diffForHumans() }}
                            @if(!$isRepost)
                                @if($post->visibility==='public') · <i class="fa-solid fa-earth-americas"></i>
                                @elseif($post->visibility==='friends') · <i class="fa-solid fa-user-group"></i>
                                @else · <i class="fa-solid fa-lock"></i>
                                @endif
                            @endif
                        </p>
                    </div>
                </a>
                @if($isPost)
                @can('update', $post)
                <div class="flex gap-2" onclick="event.stopPropagation()">
                    <a href="{{ route('posts.edit', $post) }}" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-blue-50 flex items-center justify-center text-gray-500 hover:text-blue-600 transition"><i class="fa-solid fa-pen text-xs"></i></a>
                    <form method="POST" action="{{ route('posts.destroy', $post) }}" class="inline">
                        @csrf @method('DELETE')
                        <button onclick="event.stopPropagation(); return confirm('Supprimer ce post ?')" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-red-50 flex items-center justify-center text-gray-500 hover:text-red-500 transition"><i class="fa-solid fa-trash text-xs"></i></button>
                    </form>
                </div>
                @endcan
                @endif
            </div>

            {{-- MON AVIS --}}
            @if($isRepost && $repostObj && $repostObj->content)
            <div class="px-5 pb-3 cursor-pointer" onclick="window.location='{{ route('posts.show', $repostObj) }}'">
                <p class="text-gray-900 text-sm leading-relaxed">{{ $repostObj->content }}</p>
            </div>
            @endif

            {{-- POST REPUBLIÉ --}}
            @if($isRepost)
            @php
                $sharedRef  = $repostObj->sharedPost ?? null;
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
            @if($sharedRef)
            <div class="mx-4 mb-3 border border-gray-200 rounded-xl overflow-hidden bg-white"
                 onclick="event.stopPropagation(); window.location='{{ route('posts.show', $sharedRef) }}'">
                <div class="flex items-center gap-2 px-4 pt-3 pb-2">
                    @if($sharedRef->user->avatar)
                        <img src="{{ Storage::url($sharedRef->user->avatar) }}" class="w-8 h-8 rounded-full object-cover flex-shrink-0" onclick="event.stopPropagation(); window.location='{{ route('profile.show', $sharedRef->user) }}'"/>
                    @else
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm flex-shrink-0">{{ strtoupper(substr($sharedRef->user->name,0,1)) }}</div>
                    @endif
                    <div>
                        <p class="font-semibold text-sm text-gray-800 hover:underline cursor-pointer" onclick="event.stopPropagation(); window.location='{{ route('profile.show', $sharedRef->user) }}'">{{ $sharedRef->user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $sharedRef->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @if($sharedRef->content)
                <div class="px-4 pb-2">
                    <p class="text-gray-700 text-sm leading-relaxed">{{ $sharedRef->content }}</p>
                </div>
                @endif
                @if($sharedRoot)
                <div class="mx-3 mb-3 border border-gray-200 rounded-xl overflow-hidden bg-gray-50 hover:bg-gray-100 transition cursor-pointer"
                     onclick="event.stopPropagation(); window.location='{{ route('posts.show', $sharedRoot) }}'">
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
                @if($sharedRef->title)
                <div class="px-4 pb-2">
                    <p class="font-bold text-gray-800 text-sm">{{ $sharedRef->title }}</p>
                </div>
                @endif
                @if($sharedRef->image)
                    <img src="{{ Storage::url($sharedRef->image) }}" class="w-full max-h-72 object-cover"/>
                @endif
                @endif
            </div>
            @endif

            @else
            {{-- CONTENU NORMAL --}}
            @if($post->title || $post->content)
            <div class="px-5 pb-3">
                @if($post->title)
                    <h2 class="font-bold text-gray-800 mb-1">{{ $post->title }}</h2>
                @endif
                @if($post->content)
                    <p class="text-gray-600 text-sm leading-relaxed {{ strlen($post->content)>200?'post-truncated':'' }}" id="post-content-{{ $post->id }}">{{ $post->content }}</p>
                    @if(strlen($post->content)>200)
                        <button onclick="event.stopPropagation(); toggleContent({{ $post->id }},this)" class="text-xs text-blue-500 font-semibold mt-1 hover:underline">Voir plus</button>
                    @endif
                @endif
            </div>
            @endif
            @if($post->image)
            <div class="overflow-hidden bg-gray-100" onclick="event.stopPropagation(); {{ $isPost ? 'openImageModal('.$jsIndex.')' : '' }}">
                <img src="{{ Storage::url($post->image) }}" class="w-full object-cover {{ $isPost ? 'hover:opacity-95 cursor-zoom-in' : '' }} transition-opacity duration-200" style="max-height:450px;object-fit:cover;"/>
            </div>
            @endif
            @endif

            {{-- COMPTEURS --}}
            @php $totalRepostCount = $statsPost->reposts()->count(); @endphp
            <div class="px-5 py-2.5 flex items-center justify-between border-b border-gray-100">
                @if($totalReactions > 0)
                <button type="button"
                    data-action="who-reacted"
                    data-post-id="{{ $actionPost->id }}"
                    class="flex items-center gap-1.5 hover:bg-gray-50 rounded-lg px-2 py-1 transition"
                    id="reaction-badges-{{ $actionPost->id }}">
                    <div class="flex">
                        @foreach(array_slice($allReactions,0,3,true) as $emoji=>$count)
                        <span style="width:20px;height:20px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:0.7rem;border:2px solid white;background:#f3f4f6;margin-left:-3px">{{ $emoji }}</span>
                        @endforeach
                    </div>
                    <span class="text-sm font-medium text-gray-600">{{ $totalReactions }}</span>
                </button>
                @else
                <div id="reaction-badges-{{ $actionPost->id }}"></div>
                @endif

                <div class="flex items-center gap-3">
                    @if($actionPost->comments->count() > 0)
                    <a href="{{ route('posts.show', $actionPost) }}"
                       class="text-sm text-gray-500 hover:underline"
                       id="comment-count-{{ $actionPost->id }}"
                       onclick="event.stopPropagation()">
                        {{ $actionPost->comments->count() }} commentaire(s)
                    </a>
                    @else
                    <span class="text-sm text-gray-400" id="comment-count-{{ $actionPost->id }}">0 commentaire</span>
                    @endif

                    @if($repostCount > 0)
                    <button type="button"
                            data-action="who-reposted"
                            data-post-id="{{ $actionPost->id }}"
                            class="flex items-center gap-1 text-sm font-semibold text-green-600 hover:bg-green-50 rounded-lg px-2 py-1 transition"
                            id="repost-count-{{ $actionPost->id }}">
                        <i class="fa-solid fa-retweet"></i> {{ $repostCount }}
                    </button>
                    @endif

                    @if($isRepost && $totalRepostCount > $repostCount)
                    <span class="text-xs text-gray-400" id="total-repost-count-{{ $statsPost->id }}">
                        <i class="fa-solid fa-retweet text-gray-300"></i> {{ $totalRepostCount }} total
                    </span>
                    @endif

                    @if($shareCount > 0)
                    <button type="button"
                            data-action="who-shared"
                            data-post-id="{{ $actionPost->id }}"
                            class="flex items-center gap-1 text-sm font-medium text-blue-500 hover:bg-blue-50 rounded-lg px-2 py-1 transition"
                            id="share-count-{{ $actionPost->id }}">
                        <i class="fa-solid fa-share-nodes"></i> {{ $shareCount }}
                    </button>
                    @endif
                </div>
            </div>

            {{-- ACTIONS --}}
            <div class="px-5 py-1 flex items-center gap-1" onclick="event.stopPropagation()">
                <div class="relative flex-1" id="post-react-wrap-{{ $actionPost->id }}" onmouseleave="scheduleHidePostPicker({{ $actionPost->id }})">
                    <div class="post-reaction-picker" id="post-react-picker-{{ $actionPost->id }}" onmouseenter="cancelHidePostPicker({{ $actionPost->id }})">
                        @foreach(['👍'=>'J\'aime','❤️'=>'J\'adore','😂'=>'Haha','😮'=>'Wow','😢'=>'Triste','😡'=>'Grrr'] as $emoji=>$label)
                        <button class="post-reaction-emoji" onclick="togglePostReaction({{ $actionPost->id }},'{{ $emoji }}')" title="{{ $label }}">{{ $emoji }}</button>
                        @endforeach
                    </div>
                    <button onmouseenter="showPostPicker({{ $actionPost->id }})"
                            onclick="togglePostReaction({{ $actionPost->id }},'👍')"
                            id="post-react-btn-{{ $actionPost->id }}"
                            class="w-full flex items-center justify-center gap-2 py-2 rounded-xl text-sm font-semibold transition {{ $myReaction?'bg-blue-50':'text-gray-500 hover:bg-gray-100' }}"
                            style="{{ $myReaction ? 'color:'.(['👍'=>'#2563eb','❤️'=>'#ef4444','😂'=>'#fbbf24','😮'=>'#f59e0b','😢'=>'#60a5fa','😡'=>'#ef4444'][$myReaction]??'#2563eb') : '' }}">
                        @if($myReaction)
                            <span style="font-size:1.1rem">{{ $myReaction }}</span>
                            <span>{{ ['👍'=>'J\'aime','❤️'=>'J\'adore','😂'=>'Haha','😮'=>'Wow','😢'=>'Triste','😡'=>'Grrr'][$myReaction]??'Réaction' }}</span>
                        @else
                            <i class="fa-regular fa-thumbs-up text-base"></i>J'aime
                        @endif
                    </button>
                </div>

                @if($isPost)
                    <button onclick="openPostCard({{ $jsIndex }}); setTimeout(()=>document.getElementById('card-comment-input').focus(),300)" class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-sm font-semibold text-gray-500 hover:bg-gray-100 transition"><i class="fa-regular fa-comment text-base"></i>Commenter</button>
                @else
                    <a href="{{ route('posts.show', $actionPost) }}" onclick="event.stopPropagation()" class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-sm font-semibold text-gray-500 hover:bg-gray-100 transition"><i class="fa-regular fa-comment text-base"></i>Commenter</a>
                @endif

                <a href="{{ route('posts.repost.show', $actionPost) }}" onclick="event.stopPropagation()" class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-sm font-semibold text-gray-500 hover:bg-green-50 hover:text-green-600 transition"><i class="fa-solid fa-retweet text-base"></i>Republier</a>
                <a href="{{ route('shares.create', $actionPost) }}" onclick="event.stopPropagation()" class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-sm font-semibold text-gray-500 hover:bg-blue-50 hover:text-blue-600 transition"><i class="fa-solid fa-share-nodes text-base"></i>Partager</a>
            </div>

            {{-- Commentaire rapide --}}
            <div class="px-5 pb-4 flex items-center gap-2" onclick="event.stopPropagation()">
                @if(auth()->user()->avatar)
                    <img src="{{ Storage::url(auth()->user()->avatar) }}" class="w-8 h-8 rounded-full object-cover flex-shrink-0"/>
                @else
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-bold flex-shrink-0">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
                @endif
                <form method="POST" action="{{ route('comments.store', $actionPost) }}" class="flex-1 flex gap-2">
                    @csrf
                    <input type="text" name="content" placeholder="Écrire un commentaire..." onclick="event.stopPropagation()" class="flex-1 bg-gray-100 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"/>
                    <button type="submit" class="w-9 h-9 bg-blue-600 text-white rounded-full flex items-center justify-center hover:bg-blue-700 transition flex-shrink-0"><i class="fa-solid fa-paper-plane text-xs"></i></button>
                </form>
            </div>
        </div>
        @endif
        @endforeach
        @endif

        <div class="mt-4">{{ $posts->links() }}</div>
    </div>
</div>
</div>

{{-- MODALS --}}
<div id="modal-overlay" class="fixed inset-0 z-[9990] hidden" style="background:rgba(15,23,42,0.65);backdrop-filter:blur(6px);" onclick="closeAllModals()"></div>

<div id="post-card-modal" class="fixed z-[9995] hidden" style="top:50%;left:50%;transform:translate(-50%,-50%);width:min(96vw,680px);max-height:90vh;border-radius:18px;overflow:hidden;box-shadow:0 32px 80px rgba(0,0,0,0.45);display:none;flex-direction:column;background:#fff;">
    <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 flex-shrink-0">
        <div class="flex items-center gap-3"><div id="card-avatar" class="w-10 h-10 rounded-full overflow-hidden bg-blue-100 flex items-center justify-center text-blue-600 font-bold border border-gray-200 flex-shrink-0"></div><div><p id="card-username" class="font-semibold text-gray-800 text-sm"></p><p id="card-time" class="text-xs text-gray-400"></p></div></div>
        <div class="flex items-center gap-2"><a id="card-post-link" href="#" class="text-xs text-blue-500 hover:underline flex items-center gap-1"><i class="fa-solid fa-arrow-up-right-from-square"></i> Voir</a><button onclick="closeAllModals()" class="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center text-gray-600 transition ml-1"><i class="fa-solid fa-xmark"></i></button></div>
    </div>
    <div class="overflow-y-auto flex-1">
        <div class="px-5 py-3"><p id="card-title" class="font-bold text-gray-800 mb-1"></p><p id="card-content" class="text-sm text-gray-600 leading-relaxed"></p></div>
        <div id="card-img-wrap" class="hidden bg-black"><img id="card-img" src="" alt="" class="w-full object-contain cursor-zoom-in" style="max-height:400px;" onclick="switchToImageModal()"/></div>
        <div id="card-counts" class="px-5 py-2 flex items-center justify-between text-xs text-gray-400 border-t border-gray-100"></div>
        <div id="card-actions" class="px-5 py-1 flex gap-1 border-b border-gray-100"></div>
        <div class="px-5 pt-3 pb-1"><p class="text-sm font-bold text-gray-700">Commentaires</p></div>
        <div id="card-comments-list" class="px-5 pb-3 space-y-4"></div>
    </div>
    <div class="px-4 py-3 border-t border-gray-100 flex-shrink-0 bg-white">
        <div class="flex gap-2 items-center">
            @if(auth()->user()->avatar)
                <img src="{{ Storage::url(auth()->user()->avatar) }}" class="w-9 h-9 rounded-full object-cover flex-shrink-0 border border-gray-200"/>
            @else
                <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-bold flex-shrink-0">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
            @endif
            <form id="card-comment-form" class="flex-1 flex gap-2" onsubmit="submitCardComment(event)">
                <input type="hidden" id="card-post-id" value=""/>
                <input type="text" id="card-comment-input" name="content" placeholder="Écrire un commentaire..." autocomplete="off" class="flex-1 bg-gray-100 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"/>
                <button type="submit" class="w-9 h-9 bg-blue-600 text-white rounded-full flex items-center justify-center hover:bg-blue-700 transition flex-shrink-0"><i class="fa-solid fa-paper-plane text-xs"></i></button>
            </form>
        </div>
    </div>
</div>

<div id="image-modal" class="fixed z-[9996] hidden" style="top:50%;left:50%;transform:translate(-50%,-50%);width:min(95vw,1100px);height:min(92vh,700px);border-radius:20px;overflow:hidden;box-shadow:0 32px 80px rgba(0,0,0,0.5);">
    <button onclick="closeImageModal()" class="absolute top-3 right-3 z-30 w-9 h-9 bg-black/50 hover:bg-black/70 rounded-full flex items-center justify-center text-white transition"><i class="fa-solid fa-xmark"></i></button>
    <button onclick="navigatePost(-1)" id="img-modal-prev" class="absolute left-3 top-1/2 -translate-y-1/2 z-30 w-10 h-10 bg-black/40 hover:bg-black/60 rounded-full flex items-center justify-center text-white transition hidden"><i class="fa-solid fa-chevron-left"></i></button>
    <button onclick="navigatePost(1)" id="img-modal-next" class="absolute z-30 w-10 h-10 bg-black/40 hover:bg-black/60 rounded-full flex items-center justify-center text-white transition hidden" style="right:403px;top:50%;transform:translateY(-50%)"><i class="fa-solid fa-chevron-right"></i></button>
    <div class="flex h-full">
        <div class="flex-1 flex items-center justify-center bg-black min-w-0"><img id="img-modal-img" src="" class="max-h-full max-w-full object-contain"/></div>
        <div class="w-[390px] flex-shrink-0 bg-white flex flex-col h-full">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 flex-shrink-0"><div class="flex items-center gap-3"><div id="img-modal-avatar" class="w-10 h-10 rounded-full overflow-hidden bg-blue-100 flex items-center justify-center text-blue-600 font-bold border border-gray-200 flex-shrink-0"></div><div><p id="img-modal-username" class="font-semibold text-gray-800 text-sm"></p><p id="img-modal-time" class="text-xs text-gray-400"></p></div></div><a id="img-modal-link" href="#" class="text-xs text-blue-500 hover:underline flex items-center gap-1 flex-shrink-0"><i class="fa-solid fa-arrow-up-right-from-square"></i> Voir</a></div>
            <div class="px-4 py-3 border-b border-gray-100 flex-shrink-0 max-h-32 overflow-y-auto"><p id="img-modal-title" class="font-bold text-gray-800 mb-1 text-sm"></p><p id="img-modal-content" class="text-sm text-gray-600 leading-relaxed"></p></div>
            <div id="img-modal-counts" class="px-4 py-2 border-b border-gray-100 flex items-center justify-between flex-shrink-0 text-xs text-gray-400"></div>
            <div id="img-modal-actions" class="px-4 py-1 border-b border-gray-100 flex-shrink-0 flex gap-1"></div>
            <div id="img-modal-comments" class="flex-1 overflow-y-auto px-4 py-3 space-y-4"></div>
            <div class="px-4 py-3 border-t border-gray-100 flex-shrink-0">
                <div class="flex gap-2 items-center">
                    @if(auth()->user()->avatar)
                        <img src="{{ Storage::url(auth()->user()->avatar) }}" class="w-8 h-8 rounded-full object-cover flex-shrink-0 border border-gray-200"/>
                    @else
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-bold flex-shrink-0">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
                    @endif
                    <form id="img-modal-comment-form" class="flex-1 flex gap-2" onsubmit="submitImgComment(event)">
                        <input type="hidden" id="img-modal-post-id" value=""/>
                        <input type="text" id="img-modal-comment-input" placeholder="Écrire un commentaire..." autocomplete="off" class="flex-1 bg-gray-100 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"/>
                        <button type="submit" class="w-9 h-9 bg-blue-600 text-white rounded-full flex items-center justify-center hover:bg-blue-700 transition flex-shrink-0"><i class="fa-solid fa-paper-plane text-xs"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="feed-toast" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-[99999] bg-gray-800 text-white px-5 py-3 rounded-full text-sm font-medium shadow-xl flex items-center gap-2">
    <i class="fa-solid fa-circle-check text-green-400"></i><span id="feed-toast-msg"></span>
</div>

{{-- MODAL LISTE (réactions / reposts / partages) — CORRECTION: pas de display:flex dans le style inline --}}
<div id="list-modal-overlay" class="fixed inset-0 z-[9997] hidden" style="background:rgba(15,23,42,0.6);backdrop-filter:blur(4px);" onclick="closeListModal()"></div>
<div id="list-modal" class="fixed z-[9998] bg-white rounded-2xl shadow-2xl overflow-hidden" style="top:50%;left:50%;transform:translate(-50%,-50%);width:min(95vw,420px);max-height:80vh;flex-direction:column;display:none;">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
        <h3 class="font-bold text-gray-800 text-base" id="list-modal-title">Liste</h3>
        <button onclick="closeListModal()" class="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center text-gray-600 transition">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <div id="list-modal-tabs" class="flex gap-1 px-4 pt-3 pb-2 border-b border-gray-100 overflow-x-auto flex-shrink-0 hidden"></div>
    <div id="list-modal-body" class="overflow-y-auto flex-1 px-4 py-3">
        <div class="flex items-center justify-center py-8 text-gray-300">
            <i class="fa-solid fa-spinner fa-spin text-2xl"></i>
        </div>
    </div>
</div>

<script>
const CSRF=document.querySelector('meta[name="csrf-token"]')?.content;
try {
const allPosts=@json($postsData??[]);
window.__allPosts = allPosts;
} catch(e) { console.error('allPosts JSON error:', e); window.__allPosts = []; }
const allPosts = window.__allPosts;
let currentPostIndex=0;
const reactionTimersCm={};
const postPickerTimers={};
const REACTION_LABELS={'👍':'J\'aime','❤️':'J\'adore','😂':'Haha','😮':'Wow','😢':'Triste','😡':'Grrr'};
const REACTION_COLORS={'👍':'#2563eb','❤️':'#ef4444','😂':'#fbbf24','😮':'#f59e0b','😢':'#60a5fa','😡':'#ef4444'};

function showFeedToast(msg,d=3000){const t=document.getElementById('feed-toast');document.getElementById('feed-toast-msg').textContent=msg;t.classList.remove('hidden');setTimeout(()=>t.classList.add('hidden'),d);}

// ── MODAL LISTE ─────────────────────────────────────────────
// CORRECTION : on utilise style.display au lieu de classList hidden/remove
function closeListModal(){
    document.getElementById('list-modal').style.display='none';
    document.getElementById('list-modal-overlay').classList.add('hidden');
    document.body.style.overflow='';
}
function openListModal(title){
    document.getElementById('list-modal-title').textContent=title;
    document.getElementById('list-modal-tabs').classList.add('hidden');
    document.getElementById('list-modal-tabs').innerHTML='';
    document.getElementById('list-modal-body').innerHTML='<div class="flex items-center justify-center py-8 text-gray-300"><i class="fa-solid fa-spinner fa-spin text-2xl"></i></div>';
    document.getElementById('list-modal').style.display='flex';
    document.getElementById('list-modal-overlay').classList.remove('hidden');
    document.body.style.overflow='hidden';
}

function renderUserRow(u){
    const av=u.avatar?`<img src="${u.avatar}" class="w-10 h-10 rounded-full object-cover flex-shrink-0"/>`:`<div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold flex-shrink-0">${u.name.charAt(0).toUpperCase()}</div>`;
    return`<a href="${u.url}" class="flex items-center gap-3 py-2.5 hover:bg-gray-50 rounded-xl px-2 transition">${av}<div><p class="font-semibold text-sm text-gray-800">${u.name}</p>${u.extra?`<p class="text-xs text-gray-400">${u.extra}</p>`:''}</div></a>`;
}

function openWhoReacted(postId){
    console.log('openWhoReacted postId=', postId);
    openListModal('Réactions');
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '/posts/'+postId+'/reactions-list', true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-CSRF-TOKEN', CSRF);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onload = function() {
        console.log('XHR status=', xhr.status, 'response=', xhr.responseText.substring(0,100));
        if(xhr.status !== 200){ document.getElementById('list-modal-body').innerHTML='<p class="text-center text-red-400 py-8 text-sm">Erreur HTTP '+xhr.status+'</p>'; return; }
        try {
            var data = JSON.parse(xhr.responseText);
            if(!Array.isArray(data)||!data.length){
                document.getElementById('list-modal-body').innerHTML='<p class="text-center text-gray-400 py-8 text-sm">Aucune réaction</p>';return;
            }
            var groups={};
            data.forEach(function(r){if(!groups[r.reaction])groups[r.reaction]=[];groups[r.reaction].push(r);});
            var tabs=document.getElementById('list-modal-tabs');
            tabs.classList.remove('hidden');
            tabs.innerHTML='<button data-tab="all" class="list-tab px-3 py-1.5 rounded-full text-xs font-semibold bg-blue-600 text-white flex-shrink-0">Tout '+data.length+'</button>'
                +Object.entries(groups).map(function(e){return '<button data-tab="'+e[0]+'" class="list-tab px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 flex-shrink-0">'+e[0]+' '+e[1].length+'</button>';}).join('');
            document.getElementById('list-modal-body').innerHTML=data.map(function(r){return '<div class="list-row" data-tab="'+r.reaction+'">'+renderUserRow({name:r.user_name,avatar:r.avatar,url:r.profile_url,extra:r.reaction+' · '+r.created_at})+'</div>';}).join('');
        } catch(e){ document.getElementById('list-modal-body').innerHTML='<p class="text-center text-red-400 py-8 text-sm">Erreur parse: '+e.message+'</p>'; }
    };
    xhr.onerror = function(){ console.error('XHR error'); document.getElementById('list-modal-body').innerHTML='<p class="text-center text-red-400 py-8 text-sm">Erreur réseau</p>'; };
    xhr.send();
}

function openWhoReposted(postId){
    openListModal('Republications');
    fetch(`/posts/${postId}/reposters-list`,{headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}})
    .then(r=>{if(!r.ok)throw new Error('HTTP '+r.status);return r.json();})
    .then(data=>{
        if(!Array.isArray(data)||!data.length){
            document.getElementById('list-modal-body').innerHTML='<p class="text-center text-gray-400 py-8 text-sm">Aucune republication pour l\'instant</p>';return;
        }
        document.getElementById('list-modal-body').innerHTML=data.map(r=>renderUserRow({name:r.user_name,avatar:r.avatar,url:r.profile_url,extra:'🔁 · '+r.created_at+(r.comment?` · "${r.comment}"`:'')})).join('');
    }).catch(e=>{console.error('repostersList error:',e);document.getElementById('list-modal-body').innerHTML=`<p class="text-center text-red-400 py-8 text-sm">Erreur: ${e.message}</p>`;});
}

function openWhoShared(postId){
    openListModal('Partages');
    fetch(`/posts/${postId}/sharers-list`,{headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}})
    .then(r=>{if(!r.ok)throw new Error('HTTP '+r.status);return r.json();})
    .then(data=>{
        if(!Array.isArray(data)||!data.length){
            document.getElementById('list-modal-body').innerHTML='<p class="text-center text-gray-400 py-8 text-sm">Aucun partage pour l\'instant</p>';return;
        }
        document.getElementById('list-modal-body').innerHTML=data.map(r=>renderUserRow({name:r.sender_name,avatar:r.avatar,url:r.profile_url,extra:'🔗 · '+r.created_at})).join('');
    }).catch(e=>{console.error('sharersList error:',e);document.getElementById('list-modal-body').innerHTML=`<p class="text-center text-red-400 py-8 text-sm">Erreur: ${e.message}</p>`;});
}

function filterListByTab(tab,btn){
    document.querySelectorAll('.list-tab').forEach(b=>{
        b.classList.remove('bg-blue-600','text-white');
        b.classList.add('bg-gray-100','text-gray-700','hover:bg-gray-200');
    });
    btn.classList.remove('bg-gray-100','text-gray-700','hover:bg-gray-200');
    btn.classList.add('bg-blue-600','text-white');
    document.querySelectorAll('.list-row').forEach(row=>{
        row.style.display=(tab==='all'||row.dataset.tab===tab)?'block':'none';
    });
}

// ── Gestionnaire global pour les boutons compteurs ──────────
document.addEventListener('click', function(e) {
    const btn = e.target.closest('[data-action]');
    if (btn) {
        e.stopPropagation();
        e.preventDefault();
        const action = btn.dataset.action;
        const postId = parseInt(btn.dataset.postId);
        if (action === 'who-reacted')  openWhoReacted(postId);
        if (action === 'who-reposted') openWhoReposted(postId);
        if (action === 'who-shared')   openWhoShared(postId);
        return;
    }
    const tab = e.target.closest('.list-tab[data-tab]');
    if (tab) {
        e.stopPropagation();
        filterListByTab(tab.dataset.tab, tab);
        return;
    }
}, true);

function buildReactionBlock(r,l){const a=Object.assign({},r||{});a['👍']=(a['👍']||0)+(l||0);if(!a['👍'])delete a['👍'];const t=Object.values(a).reduce((x,y)=>x+y,0);if(t===0)return'<span style="color:#9ca3af">Aucune réaction</span>';const b=Object.keys(a).slice(0,3).map((e,i)=>`<span style="width:18px;height:18px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:0.65rem;border:2px solid white;background:#f3f4f6;margin-left:${i===0?'0':'-3px'}">${e}</span>`).join('');return`<div style="display:flex;align-items:center;gap:5px;"><div style="display:flex;">${b}</div><span style="color:#6b7280;font-size:0.75rem">${t}</span></div>`;}
function showPostPicker(id){cancelHidePostPicker(id);document.getElementById(`post-react-picker-${id}`)?.classList.add('open');}
function scheduleHidePostPicker(id){postPickerTimers[id]=setTimeout(()=>document.getElementById(`post-react-picker-${id}`)?.classList.remove('open'),400);}
function cancelHidePostPicker(id){clearTimeout(postPickerTimers[id]);}
function togglePostReaction(postId,emoji){
    document.getElementById(`post-react-picker-${postId}`)?.classList.remove('open');
    document.getElementById(`post-react-picker-modal-${postId}`)?.classList.remove('open');
    document.getElementById(`post-react-picker-img-${postId}`)?.classList.remove('open');
    const idx=allPosts.findIndex(p=>p.id===postId);if(idx===-1)return;
    fetch(allPosts[idx].reaction_url,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:JSON.stringify({reaction:emoji})})
    .then(r=>r.json()).then(data=>{
        if(!data.success)return;
        allPosts[idx].my_reaction=data.my_reaction;allPosts[idx].reactions=data.reactions||{};
        [`post-react-btn-${postId}`,`post-react-btn-modal-${postId}`,`post-react-btn-img-${postId}`].forEach(bid=>{
            const btn=document.getElementById(bid);if(!btn)return;
            if(data.my_reaction){btn.style.color=REACTION_COLORS[data.my_reaction]||'#2563eb';btn.className=btn.className.replace('text-gray-500 hover:bg-gray-100','')+' bg-blue-50';btn.innerHTML=`<span style="font-size:1.1rem">${data.my_reaction}</span><span>${REACTION_LABELS[data.my_reaction]||''}</span>`;}
            else{btn.style.color='';btn.className=btn.className.replace('bg-blue-50','')+' text-gray-500 hover:bg-gray-100';btn.innerHTML=`<i class="fa-regular fa-thumbs-up text-base"></i>J'aime`;}
        });
        const bd=document.getElementById(`reaction-badges-${postId}`);
        if(bd){
            const a=Object.assign({},data.reactions||{});
            if(emoji==='👍'){
                if(data.my_reaction==='👍') allPosts[idx].like_count=(allPosts[idx].like_count||0)+1;
                else allPosts[idx].like_count=Math.max(0,(allPosts[idx].like_count||0)-1);
            }
            a['👍']=(a['👍']||0)+(allPosts[idx].like_count||0);
            if(!a['👍'])delete a['👍'];
            const t=Object.values(a).reduce((x,y)=>x+y,0);
            if(t>0){
                const b=Object.keys(a).slice(0,3).map((e,i)=>`<span style="width:18px;height:18px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:0.65rem;border:2px solid white;background:#f3f4f6;margin-left:${i===0?'0':'-3px'}">${e}</span>`).join('');
                bd.innerHTML=`<div style="display:flex;">${b}</div><span style="color:#6b7280;font-size:0.75rem">${t}</span>`;
            } else bd.innerHTML='';
        }
    }).catch(e=>console.error(e));
}
function openPostCard(index){
    currentPostIndex=index;const p=allPosts[index];if(!p)return;
    document.body.style.overflow='hidden';document.getElementById('modal-overlay').classList.remove('hidden');
    const m=document.getElementById('post-card-modal');m.style.display='flex';m.classList.remove('hidden');m.style.animation='none';m.offsetHeight;m.style.animation='modalPop 0.25s cubic-bezier(0.34,1.56,0.64,1) forwards';
    const av=document.getElementById('card-avatar');av.innerHTML=p.avatar?`<img src="${p.avatar}" class="w-full h-full object-cover rounded-full"/>`:`<span>${p.user_name.charAt(0).toUpperCase()}</span>`;
    document.getElementById('card-post-id').value=p.id;document.getElementById('card-username').textContent=p.user_name;document.getElementById('card-time').textContent=p.created_at;document.getElementById('card-title').textContent=p.title;document.getElementById('card-content').textContent=p.content;document.getElementById('card-post-link').href=p.url;
    const iw=document.getElementById('card-img-wrap');if(p.image){document.getElementById('card-img').src=p.image;iw.classList.remove('hidden');}else iw.classList.add('hidden');
    renderCardMeta(p);loadComments(p.id,'card-comments-list','card-post-id');
}
function renderCardMeta(p){
    document.getElementById('card-counts').innerHTML=`<div style="display:flex;align-items:center;gap:4px">${buildReactionBlock(p.reactions,p.like_count)}</div><span>${p.comment_count} commentaire(s)</span>`;
    const myR=p.my_reaction,color=myR?(REACTION_COLORS[myR]||'#2563eb'):'';
    const pb=['👍','❤️','😂','😮','😢','😡'].map(e=>`<button class="post-reaction-emoji" onclick="togglePostReaction(${p.id},'${e}')" title="${REACTION_LABELS[e]}">${e}</button>`).join('');
    document.getElementById('card-actions').innerHTML=`<div class="relative flex-1" onmouseleave="scheduleHidePostPicker('modal-${p.id}')"><div class="post-reaction-picker" id="post-react-picker-modal-${p.id}" onmouseenter="cancelHidePostPicker('modal-${p.id}')">${pb}</div><button onmouseenter="showPostPicker('modal-${p.id}')" onclick="togglePostReaction(${p.id},'👍')" id="post-react-btn-modal-${p.id}" class="w-full flex items-center justify-center gap-2 py-2 rounded-xl text-sm font-semibold transition ${myR?'bg-blue-50':'text-gray-500 hover:bg-gray-100'}" ${myR?`style="color:${color}"`:''}>
    ${myR?`<span style="font-size:1.1rem">${myR}</span><span>${REACTION_LABELS[myR]||''}</span>`:`<i class="fa-regular fa-thumbs-up"></i> J'aime`}</button></div>
    <button onclick="document.getElementById('card-comment-input').focus()" class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-sm font-semibold text-gray-500 hover:bg-gray-100 transition"><i class="fa-regular fa-comment"></i> Commenter</button>
    <a href="/posts/${p.id}/repost" class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-sm font-semibold text-gray-500 hover:bg-green-50 hover:text-green-600 transition"><i class="fa-solid fa-retweet"></i> Republier</a>
    <a href="/posts/${p.id}/share" class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-sm font-semibold text-gray-500 hover:bg-blue-50 hover:text-blue-600 transition"><i class="fa-solid fa-share-nodes"></i> Partager</a>`;
}
function switchToImageModal(){document.getElementById('post-card-modal').style.display='none';document.getElementById('post-card-modal').classList.add('hidden');openImageModal(currentPostIndex);}
function openImageModal(index){
    currentPostIndex=index;const p=allPosts[index];if(!p||!p.image)return;
    document.body.style.overflow='hidden';document.getElementById('modal-overlay').classList.remove('hidden');
    const m=document.getElementById('image-modal');m.classList.remove('hidden');m.style.animation='none';m.offsetHeight;m.style.animation='modalPop 0.25s cubic-bezier(0.34,1.56,0.64,1) forwards';
    document.getElementById('img-modal-img').src=p.image;document.getElementById('img-modal-post-id').value=p.id;document.getElementById('img-modal-username').textContent=p.user_name;document.getElementById('img-modal-time').textContent=p.created_at;document.getElementById('img-modal-title').textContent=p.title;document.getElementById('img-modal-content').textContent=p.content;document.getElementById('img-modal-link').href=p.url;
    const av=document.getElementById('img-modal-avatar');av.innerHTML=p.avatar?`<img src="${p.avatar}" class="w-full h-full object-cover rounded-full"/>`:`<span>${p.user_name.charAt(0).toUpperCase()}</span>`;
    const pwi=allPosts.map((x,i)=>({x,i})).filter(v=>v.x.image);const pi=pwi.findIndex(v=>v.i===index);
    document.getElementById('img-modal-prev').classList.toggle('hidden',pi<=0);document.getElementById('img-modal-next').classList.toggle('hidden',pi>=pwi.length-1);
    renderImgMeta(p);loadComments(p.id,'img-modal-comments','img-modal-post-id');
}
function renderImgMeta(p){
    document.getElementById('img-modal-counts').innerHTML=`<div style="display:flex;align-items:center;gap:4px">${buildReactionBlock(p.reactions,p.like_count)}</div><span>${p.comment_count} commentaire(s)</span>`;
    const myR=p.my_reaction,color=myR?(REACTION_COLORS[myR]||'#2563eb'):'';
    const pb=['👍','❤️','😂','😮','😢','😡'].map(e=>`<button class="post-reaction-emoji" onclick="togglePostReaction(${p.id},'${e}')" title="${REACTION_LABELS[e]}">${e}</button>`).join('');
    document.getElementById('img-modal-actions').innerHTML=`<div class="relative flex-1" onmouseleave="scheduleHidePostPicker('img-${p.id}')"><div class="post-reaction-picker" id="post-react-picker-img-${p.id}" onmouseenter="cancelHidePostPicker('img-${p.id}')">${pb}</div><button onmouseenter="showPostPicker('img-${p.id}')" onclick="togglePostReaction(${p.id},'👍')" id="post-react-btn-img-${p.id}" class="w-full flex items-center justify-center gap-2 py-2 rounded-xl text-sm font-semibold transition ${myR?'bg-blue-50':'text-gray-500 hover:bg-gray-100'}" ${myR?`style="color:${color}"`:''}>
    ${myR?`<span style="font-size:1.1rem">${myR}</span><span>${REACTION_LABELS[myR]||''}</span>`:`<i class="fa-regular fa-thumbs-up"></i> J'aime`}</button></div>
    <button onclick="document.getElementById('img-modal-comment-input').focus()" class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-sm font-semibold text-gray-500 hover:bg-gray-100 transition"><i class="fa-regular fa-comment"></i> Commenter</button>
    <a href="/posts/${p.id}/repost" class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-sm font-semibold text-gray-500 hover:bg-green-50 hover:text-green-600 transition"><i class="fa-solid fa-retweet"></i> Republier</a>
    <a href="/posts/${p.id}/share" class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-sm font-semibold text-gray-500 hover:bg-blue-50 hover:text-blue-600 transition"><i class="fa-solid fa-share-nodes"></i> Partager</a>`;
}
function navigatePost(dir){const pwi=allPosts.map((p,i)=>({p,i})).filter(x=>x.p.image);const pi=pwi.findIndex(x=>x.i===currentPostIndex);const n=pwi[pi+dir];if(n)openImageModal(n.i);}
function closeImageModal(){document.getElementById('image-modal').classList.add('hidden');document.getElementById('modal-overlay').classList.add('hidden');document.body.style.overflow='';}
function closeAllModals(){document.getElementById('post-card-modal').classList.add('hidden');document.getElementById('post-card-modal').style.display='none';document.getElementById('image-modal').classList.add('hidden');document.getElementById('modal-overlay').classList.add('hidden');document.body.style.overflow='';}
function loadComments(postId,cid,pfid){const c=document.getElementById(cid);if(!c)return;c.innerHTML=`<div class="flex items-center justify-center py-8 text-gray-300"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Chargement...</div>`;fetch(`/posts/${postId}/comments-json`,{headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}}).then(r=>{if(!r.ok)throw new Error();return r.json();}).then(data=>{if(!data.comments||!data.comments.length){c.innerHTML=`<div class="text-center text-gray-300 py-8"><i class="fa-regular fa-comment text-5xl mb-3 block"></i><p class="text-sm">Sois le premier à commenter !</p></div>`;return;}c.innerHTML=data.comments.map(x=>buildCommentHTML(x,pfid)).join('');}).catch(()=>{c.innerHTML=`<div class="text-center text-red-300 py-4 text-sm">Erreur chargement</div>`;});}
function submitCardComment(e){e.preventDefault();const i=document.getElementById('card-comment-input');const pid=document.getElementById('card-post-id').value;const ct=i.value.trim();if(!ct||!pid)return;fetch(`/posts/${pid}/comments`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:JSON.stringify({content:ct})}).then(r=>r.json()).then(()=>{i.value='';allPosts[currentPostIndex].comment_count++;renderCardMeta(allPosts[currentPostIndex]);loadComments(pid,'card-comments-list','card-post-id');const cc=document.getElementById(`comment-count-${pid}`);if(cc)cc.textContent=`${allPosts[currentPostIndex].comment_count} commentaire(s)`;});}
function submitImgComment(e){e.preventDefault();const i=document.getElementById('img-modal-comment-input');const pid=document.getElementById('img-modal-post-id').value;const ct=i.value.trim();if(!ct||!pid)return;fetch(`/posts/${pid}/comments`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:JSON.stringify({content:ct})}).then(r=>r.json()).then(()=>{i.value='';allPosts[currentPostIndex].comment_count++;renderImgMeta(allPosts[currentPostIndex]);loadComments(pid,'img-modal-comments','img-modal-post-id');});}
function buildCommentHTML(c,pf){const av=c.avatar?`<img src="${c.avatar}" class="w-8 h-8 rounded-full object-cover"/>`:`<div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-bold">${c.user_name.charAt(0).toUpperCase()}</div>`;const bg=c.reactions&&Object.keys(c.reactions).length>0?`<div class="flex gap-1 mt-1 flex-wrap">${Object.entries(c.reactions).map(([e,n])=>`<span onclick="sendReactionCm(${c.id},'${e}')" class="bg-white border border-gray-200 rounded-full px-2 py-0.5 text-xs shadow-sm cursor-pointer hover:scale-110 transition inline-flex items-center gap-1">${e}${n>1?` <span class="text-gray-500">${n}</span>`:''}</span>`).join('')}</div>`:'';const rp=c.replies&&c.replies.length>0?`<button onclick="toggleRepliesCm(${c.id},this)" class="text-xs text-blue-500 font-semibold mt-2 flex items-center gap-1"><i class="fa-solid fa-chevron-down text-xs"></i> Voir ${c.replies.length} réponse(s)</button><div id="replies-cm-${c.id}" class="hidden mt-2 border-l-2 border-blue-100 pl-3 space-y-3">${c.replies.map(r=>buildReplyHTML(r)).join('')}</div>`:'';
return`<div id="comment-cm-${c.id}" class="flex gap-2"><div class="flex-shrink-0 mt-1">${av}</div><div class="flex-1 min-w-0"><div class="bg-gray-100 rounded-2xl px-3 py-2 inline-block max-w-full"><p class="font-semibold text-xs text-gray-800">${c.user_name}</p><p class="text-sm text-gray-700 mt-0.5 break-words">${c.content}</p></div>${bg}<div class="flex items-center gap-3 mt-1 ml-1 flex-wrap"><span class="text-xs text-gray-400">${c.created_at}</span><div class="relative inline-block"><button onmouseenter="showReactionCm(${c.id})" onmouseleave="hideReactionCm(${c.id})" id="rcm-btn-${c.id}" class="text-xs font-bold transition ${c.my_reaction?'text-blue-600':'text-gray-500 hover:text-blue-600'}">${c.my_reaction?c.my_reaction+' Réagi':'👍 Réagir'}</button><div id="rcm-popup-${c.id}" class="reaction-popup-cm" onmouseenter="keepReactionCm(${c.id})" onmouseleave="hideReactionCm(${c.id})">${['👍','❤️','😂','😮','😢','😡'].map(e=>`<button onclick="sendReactionCm(${c.id},'${e}')" class="text-xl hover:scale-125 transition-transform" style="padding:2px 3px">${e}</button>`).join('')}</div></div><button onclick="focusReplyInputCm(${c.id},'${c.user_name.replace(/'/g,"\\'")}','${pf}')" class="text-xs font-bold text-gray-500 hover:text-blue-600 transition">Répondre</button></div><div id="reply-form-cm-${c.id}" class="hidden mt-2"><form onsubmit="submitReplyCm(event,${c.id},'${pf}')" class="flex gap-2"><input type="text" id="reply-input-cm-${c.id}" autocomplete="off" class="flex-1 bg-gray-100 rounded-full px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"/><button type="submit" class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center hover:bg-blue-700 flex-shrink-0"><i class="fa-solid fa-paper-plane text-xs"></i></button></form></div>${rp}</div></div>`;}
function buildReplyHTML(r){const av=r.avatar?`<img src="${r.avatar}" class="w-7 h-7 rounded-full object-cover"/>`:`<div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-bold">${r.user_name.charAt(0).toUpperCase()}</div>`;return`<div class="flex gap-2"><div class="flex-shrink-0 mt-1">${av}</div><div class="flex-1 min-w-0"><div class="bg-gray-100 rounded-2xl px-3 py-2 inline-block max-w-full"><p class="font-semibold text-xs text-gray-800">${r.user_name}</p><p class="text-sm text-gray-700 mt-0.5 break-words">${r.content}</p></div><div class="flex items-center gap-3 mt-1 ml-1"><span class="text-xs text-gray-400">${r.created_at}</span></div></div></div>`;}
function showReactionCm(id){clearTimeout(reactionTimersCm[id]);document.getElementById(`rcm-popup-${id}`)?.classList.add('open');}
function keepReactionCm(id){clearTimeout(reactionTimersCm[id]);}
function hideReactionCm(id){reactionTimersCm[id]=setTimeout(()=>document.getElementById(`rcm-popup-${id}`)?.classList.remove('open'),300);}
function sendReactionCm(cid,r){document.getElementById(`rcm-popup-${cid}`)?.classList.remove('open');fetch(`/comments/${cid}/react`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:JSON.stringify({reaction:r})}).then(x=>x.json()).then(d=>{const b=document.getElementById(`rcm-btn-${cid}`);if(b){b.textContent=d.user_reaction?d.user_reaction+' Réagi':'👍 Réagir';b.className=`text-xs font-bold transition ${d.user_reaction?'text-blue-600':'text-gray-500 hover:text-blue-600'}`;}});}
function toggleRepliesCm(id,btn){const l=document.getElementById(`replies-cm-${id}`);l.classList.toggle('hidden');btn.innerHTML=l.classList.contains('hidden')?`<i class="fa-solid fa-chevron-down text-xs"></i> Voir les réponses`:`<i class="fa-solid fa-chevron-up text-xs"></i> Masquer`;}
function focusReplyInputCm(id,name,pf){const f=document.getElementById(`reply-form-cm-${id}`);const i=document.getElementById(`reply-input-cm-${id}`);f.classList.remove('hidden');i.value=`@${name} `;i.placeholder=`Répondre à ${name}...`;i.focus();}
function submitReplyCm(e,pid,pf){e.preventDefault();const i=document.getElementById(`reply-input-cm-${pid}`);const ct=i.value.trim();if(!ct)return;const postId=document.getElementById(pf).value;fetch(`/posts/${postId}/comments`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:JSON.stringify({content:ct,parent_id:pid})}).then(r=>r.json()).then(()=>{i.value='';document.getElementById(`reply-form-cm-${pid}`).classList.add('hidden');loadComments(postId,pf==='card-post-id'?'card-comments-list':'img-modal-comments',pf);});}
function toggleContent(id,btn){const el=document.getElementById(`post-content-${id}`);el.classList.toggle('post-truncated');btn.textContent=el.classList.contains('post-truncated')?'Voir plus':'Voir moins';}
document.addEventListener('keydown',e=>{const io=!document.getElementById('image-modal').classList.contains('hidden');if(e.key==='Escape'){closeAllModals();closeListModal();}if(io&&e.key==='ArrowLeft')navigatePost(-1);if(io&&e.key==='ArrowRight')navigatePost(1);});

let svStories=[],svIndex=0,svTimer=null,svDuration=5000,svCurrentUserId=null,svPaused=false,svTimeLeft=5000,svTimerStart=null;
let svAllUsers=@json($storyUsers->pluck('id')->values());
async function openStoryViewer(uid){svCurrentUserId=uid;const r=await fetch(`/stories/${uid}/json`);svStories=await r.json();if(!svStories.length)return;svIndex=0;document.getElementById('story-viewer').classList.remove('hidden');document.body.style.overflow='hidden';showStory(0);}
function closeStoryViewer(){clearTimeout(svTimer);stopMusic();document.getElementById('story-viewer').classList.add('hidden');document.body.style.overflow='';const i=document.getElementById('sv-comment-input');if(i)i.value='';svPaused=false;}
function showStory(index){clearTimeout(svTimer);svPaused=false;const s=svStories[index];if(!s){closeStoryViewer();return;}svDuration=5000;svTimeLeft=5000;svTimerStart=Date.now();fetch(`/stories/${s.id}/view`,{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}});const bars=document.getElementById('story-progress-bars');bars.innerHTML=svStories.map((x,i)=>`<div class="flex-1 rounded-full overflow-hidden" style="height:3px;background:rgba(255,255,255,0.3)"><div id="pb-${i}" class="h-full rounded-full bg-white" style="width:${i<index?'100%':'0%'};${i===index?`transition:width ${svDuration}ms linear`:''}"></div></div>`).join('');requestAnimationFrame(()=>requestAnimationFrame(()=>{const b=document.getElementById(`pb-${index}`);if(b)b.style.width='100%';}));const av=document.getElementById('sv-avatar');av.innerHTML=s.user?.avatar_url?`<img src="${s.user.avatar_url}" class="w-full h-full object-cover"/>`:`<div class="w-full h-full bg-blue-600 flex items-center justify-center text-white font-bold text-lg">${(s.user?.name??'?')[0].toUpperCase()}</div>`;document.getElementById('sv-name').textContent=s.is_mine?'Votre story':(s.user?.name??'');document.getElementById('sv-time').textContent=s.created_at;const del=document.getElementById('sv-delete-btn');s.is_mine?del.classList.remove('hidden'):del.classList.add('hidden');del.dataset.storyId=s.id;const rb=document.getElementById('sv-reaction-bar');s.is_mine?rb.classList.add('hidden'):rb.classList.remove('hidden');const c=document.getElementById('sv-content');if(s.type==='text'){c.innerHTML=`<div class="w-full h-full flex items-center justify-center px-10" style="background:${s.bg_color}"><p class="text-4xl font-black text-center break-words" style="color:${s.text_color};text-shadow:0 2px 8px rgba(0,0,0,0.3)">${s.text_content}</p></div>`;}else if(s.type==='video'){c.innerHTML=`<video src="${s.media_url}" class="w-full h-full object-contain" autoplay muted playsinline></video>`;const v=c.querySelector('video');v.onloadedmetadata=()=>{svDuration=v.duration*1000;svTimeLeft=svDuration;svTimerStart=Date.now();clearTimeout(svTimer);svTimer=setTimeout(nextStory,svDuration);const b=document.getElementById(`pb-${index}`);if(b){b.style.transition=`width ${svDuration}ms linear`;b.style.width='100%';}};stopMusic();startStoryMusic(s);return;}else{c.innerHTML=`<img src="${s.media_url}" class="w-full h-full object-contain"/>`;}stopMusic();startStoryMusic(s);svTimer=setTimeout(nextStory,svDuration);}
function startStoryMusic(s){const mb=document.getElementById('sv-music-bar'),mt=document.getElementById('sv-music-toggle'),mi=document.getElementById('sv-music-icon'),a=document.getElementById('sv-audio');if(!s.music_url){mb.classList.add('hidden');mt.classList.add('hidden');a.src='';return;}a.src=s.music_url;a.loop=true;mb.classList.remove('hidden');mt.classList.remove('hidden');document.getElementById('sv-music-name').textContent=s.music_name||'Musique';mi.className='fa-solid fa-volume-xmark text-white text-sm';a.play().then(()=>{mi.className='fa-solid fa-volume-high text-white text-sm';}).catch(()=>{const v=document.getElementById('story-viewer');const u=()=>{a.play().then(()=>{mi.className='fa-solid fa-volume-high text-white text-sm';}).catch(()=>{});v.removeEventListener('click',u);};v.addEventListener('click',u);});}
function toggleStoryMusic(){const a=document.getElementById('sv-audio'),i=document.getElementById('sv-music-icon');if(!a.src)return;if(a.paused){a.play().then(()=>{i.className='fa-solid fa-volume-high text-white text-sm';}).catch(()=>{});}else{a.pause();i.className='fa-solid fa-volume-xmark text-white text-sm';}}
function stopMusic(){const a=document.getElementById('sv-audio');a.pause();a.src='';document.getElementById('sv-music-bar')?.classList.add('hidden');document.getElementById('sv-music-toggle')?.classList.add('hidden');}
function pauseStoryTimer(){if(svPaused)return;svPaused=true;svTimeLeft=svDuration-(Date.now()-svTimerStart);clearTimeout(svTimer);const b=document.getElementById(`pb-${svIndex}`);if(b){const w=window.getComputedStyle(b).width;b.style.transition='none';b.style.width=w;}}
function resumeStoryTimer(){if(!svPaused)return;svPaused=false;svTimerStart=Date.now();const b=document.getElementById(`pb-${svIndex}`);if(b){b.style.transition=`width ${svTimeLeft}ms linear`;b.style.width='100%';}svTimer=setTimeout(nextStory,svTimeLeft);}
function sendStoryReaction(emoji){const s=svStories[svIndex];if(!s||s.is_mine)return;pauseStoryTimer();fetch(`/stories/${s.id}/react`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'},body:JSON.stringify({content:emoji})}).then(r=>r.json()).then(()=>{showStoryToast(`${emoji} envoyé !`);resumeStoryTimer();}).catch(()=>{showStoryToast('Erreur');resumeStoryTimer();});}
function sendStoryComment(){const i=document.getElementById('sv-comment-input'),ct=i.value.trim(),s=svStories[svIndex];if(!ct||!s||s.is_mine)return;fetch(`/stories/${s.id}/react`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'},body:JSON.stringify({content:ct})}).then(r=>r.json()).then(()=>{i.value='';resumeStoryTimer();showStoryToast('Message envoyé ✓');}).catch(()=>showStoryToast('Erreur'));}
function showStoryToast(msg){const t=document.getElementById('sv-toast');if(!t)return;t.textContent=msg;t.classList.remove('hidden');setTimeout(()=>t.classList.add('hidden'),2500);}
function nextStory(){svIndex<svStories.length-1?showStory(++svIndex):nextUserStories();}
function prevStory(){svIndex>0?showStory(--svIndex):prevUserStories();}
function nextUserStories(){const i=svAllUsers.indexOf(svCurrentUserId);i!==-1&&i<svAllUsers.length-1?openStoryViewer(svAllUsers[i+1]):closeStoryViewer();}
function prevUserStories(){const i=svAllUsers.indexOf(svCurrentUserId);if(i>0)openStoryViewer(svAllUsers[i-1]);}
async function deleteCurrentStory(){const id=document.getElementById('sv-delete-btn').dataset.storyId;if(!confirm('Supprimer cette story ?'))return;await fetch(`/stories/${id}`,{method:'DELETE',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}});svStories=svStories.filter(s=>s.id!=id);if(!svStories.length){closeStoryViewer();location.reload();return;}svIndex=Math.min(svIndex,svStories.length-1);showStory(svIndex);}
document.addEventListener('keydown',e=>{if(document.getElementById('story-viewer').classList.contains('hidden'))return;if(e.key==='Escape')closeStoryViewer();if(e.key==='ArrowRight')nextStory();if(e.key==='ArrowLeft')prevStory();});
</script>
@endsection