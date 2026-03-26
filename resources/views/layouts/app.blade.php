<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SocialApp — @yield('title', 'Accueil')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <style>
        #friends-sidebar {
            transition: transform 0.3s ease;
            transform: translateY(calc(100% - 44px));
        }
        #friends-sidebar:hover {
            transform: translateY(0);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

    {{-- ─── Navbar ─────────────────────────────────────────── --}}
    @auth
    <nav class="bg-blue-600 text-white shadow-md fixed top-0 w-full z-50">
    <div class="px-6 py-3 flex items-center gap-6">

        <a href="{{ route('feed') }}" class="text-xl font-bold tracking-wide flex-shrink-0">
            <i class="fa-solid fa-globe mr-2"></i>SocialApp
        </a>

        <div class="relative flex-shrink-0" id="search-container">
            <form action="{{ route('search') }}" method="GET" autocomplete="off">
                <div class="flex items-center bg-blue-500 rounded-full px-3 py-1.5">
                    <i class="fa-solid fa-magnifying-glass text-blue-200 text-sm mr-2"></i>
                    <input type="text" name="q" id="search-input"
                           value="{{ request('q') }}"
                           placeholder="Rechercher..."
                           class="bg-transparent text-white placeholder-blue-200 text-sm focus:outline-none w-44"
                           oninput="fetchSuggestions(this.value)"
                           autocomplete="off"/>
                </div>
            </form>
            <div id="search-dropdown"
                 class="hidden absolute top-10 left-0 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden z-50">
                <div id="search-loader" class="hidden px-4 py-3 text-sm text-gray-400 text-center">
                    <i class="fa-solid fa-spinner animate-spin mr-1"></i>Recherche...
                </div>
                <div id="search-results"></div>
                <div id="search-footer" class="hidden border-t px-4 py-2">
                    <a id="search-full-link" href="#"
                       class="text-sm text-blue-500 hover:text-blue-700 font-medium">
                        <i class="fa-solid fa-magnifying-glass mr-1"></i>Voir tous les résultats
                    </a>
                </div>
            </div>
        </div>

        <div class="flex-1 flex items-center justify-center gap-8 text-sm font-medium">
            <a href="{{ route('feed') }}" class="hover:text-blue-200 transition">
                <i class="fa-solid fa-house mr-1"></i>Fil d'actualité
            </a>
            <a href="{{ route('posts.create') }}" class="hover:text-blue-200 transition">
                <i class="fa-solid fa-plus mr-1"></i>Publier
            </a>
            <a href="{{ route('profile.show', auth()->user()) }}" class="hover:text-blue-200 transition">
                <i class="fa-solid fa-user mr-1"></i>Mon Profil
            </a>

            <a href="{{ route('friends.index') }}" class="hover:text-blue-200 transition relative">
                <i class="fa-solid fa-user-group mr-1"></i>Amis
                @php
                    $pendingCount = \App\Models\FriendRequest::where('receiver_id', auth()->id())
                        ->where('status', 'pending')->count();
                @endphp
                @if($pendingCount > 0)
                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold w-4 h-4 rounded-full flex items-center justify-center">
                        {{ $pendingCount }}
                    </span>
                @endif
            </a>

            <a href="{{ route('groups.index') }}"
               class="flex items-center gap-1.5 text-white hover:bg-blue-700 px-3 py-2 rounded-lg transition font-medium text-sm">
                <i class="fa-solid fa-people-group"></i>Groupes
            </a>

            <a href="{{ route('messages.index') }}"
               id="messages-nav-link"
               class="hover:text-blue-200 transition relative">
                <i class="fa-solid fa-message mr-1"></i>Messages
                @if(auth()->user()->unreadMessagesCount() > 0)
                    <span id="messages-badge" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold w-4 h-4 rounded-full flex items-center justify-center">
                        {{ auth()->user()->unreadMessagesCount() }}
                    </span>
                @endif
            </a>

<a href="{{ route('shares.received') }}" class="hover:text-blue-200 transition relative">
    <i class="fa-solid fa-share-nodes mr-1"></i>Partages
    @php $pendingShares = \App\Models\Share::where('recipient_type', \App\Models\User::class)->where('recipient_id', auth()->id())->where('status','pending')->count(); @endphp
    @if($pendingShares > 0)
        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold w-4 h-4 rounded-full flex items-center justify-center">
            {{ $pendingShares }}
        </span>
    @endif
</a>


            <a href="{{ route('users.index') }}" class="hover:text-blue-200 transition">
                <i class="fa-solid fa-users mr-1"></i>Utilisateurs
            </a>
        </div>

        <div class="flex items-center gap-4 text-sm flex-shrink-0">
            @php
                $statusColors  = ['online'=>'bg-green-400','away'=>'bg-yellow-400','busy'=>'bg-red-400','offline'=>'bg-gray-400'];
                $currentStatus = auth()->user()->isOnline() ? 'online' : auth()->user()->status;
                $color         = $statusColors[$currentStatus] ?? 'bg-green-400';
            @endphp
            <span class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full {{ $color }} animate-pulse inline-block"></span>
                <span class="truncate max-w-[120px]">{{ auth()->user()->name }}</span>
            </span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="bg-white text-blue-600 px-3 py-1.5 rounded-full font-semibold hover:bg-blue-50 transition whitespace-nowrap">
                    <i class="fa-solid fa-right-from-bracket mr-1"></i>Déconnexion
                </button>
            </form>
        </div>

    </div>
    </nav>
    @endauth

    {{-- ─── Contenu principal ──────────────────────────────── --}}
    <main class="max-w-5xl mx-auto px-4 {{ auth()->check() ? 'pt-20' : '' }} pb-10">
        @if(session('success'))
            <div class="mt-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg">
                <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error') || $errors->any())
            <div class="mt-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded-lg">
                <i class="fa-solid fa-circle-exclamation mr-2"></i>
                @if(session('error'))
                    {{ session('error') }}
                @else
                    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                @endif
            </div>
        @endif
        @yield('content')
    </main>

    {{-- ─── Barre amis en ligne ────────────────────────────── --}}
    @auth
    <div class="fixed bottom-0 right-4 z-50 flex items-end gap-2">

        <div id="chat-windows" class="flex items-end gap-2"></div>

        <div id="friends-sidebar" class="w-64 bg-white rounded-t-2xl shadow-2xl border border-gray-200">
            <div class="flex items-center justify-between px-4 py-3 bg-blue-600 text-white rounded-t-2xl cursor-default select-none"
                 style="height:44px;">
                <span class="font-semibold text-sm flex items-center gap-1.5">
                    <i class="fa-solid fa-circle text-green-400 text-xs"></i>
                    <span id="online-count-label">Amis en ligne (0)</span>
                </span>
                <i class="fa-solid fa-chevron-up text-xs opacity-60"></i>
            </div>
            <div id="friends-list-panel" class="max-h-80 overflow-y-auto">
                <div class="px-4 py-6 text-center text-gray-400 text-sm">
                    <i class="fa-solid fa-spinner animate-spin block text-xl mb-1"></i>
                    Chargement…
                </div>
            </div>
        </div>
    </div>

    <template id="chat-template">
        <div class="chat-window w-72 bg-white rounded-t-2xl shadow-2xl border border-gray-200 flex flex-col"
             style="height:380px;" data-friend-id="">
            <div class="flex items-center justify-between px-3 py-2 bg-blue-600 text-white rounded-t-2xl flex-shrink-0">
                <div class="flex items-center gap-2">
                    <div class="chat-avatar w-8 h-8 rounded-full bg-blue-300 flex items-center justify-center text-white font-bold text-sm overflow-hidden"></div>
                    <div>
                        <p class="chat-name text-sm font-semibold"></p>
                        <p class="text-xs text-blue-200">En ligne</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a class="chat-profile-link text-white hover:text-blue-200 transition text-xs">
                        <i class="fa-solid fa-user"></i>
                    </a>
                    <button class="close-chat text-white hover:text-red-300 transition">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
            <div class="chat-messages flex-1 overflow-y-auto p-3 space-y-2 bg-gray-50">
                <div class="text-center text-gray-400 text-xs py-4">Chargement...</div>
            </div>
            <div class="flex items-center gap-2 p-2 border-t bg-white flex-shrink-0">
                <input type="text"
                       class="chat-input flex-1 border border-gray-200 rounded-full px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
                       placeholder="Écrire..."/>
                <button class="chat-send bg-blue-600 text-white w-8 h-8 rounded-full flex items-center justify-center hover:bg-blue-700 transition flex-shrink-0">
                    <i class="fa-solid fa-paper-plane text-xs"></i>
                </button>
            </div>
        </div>
    </template>

    <script>
    // ✅ Noms uniques pour éviter conflits avec les vues enfants
    const APP_CSRF    = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    const APP_AUTH_ID = {{ auth()->id() }};
    let openChats     = {};

    const sidebar = document.getElementById('friends-sidebar');

    // ─── Sidebar hover ────────────────────────────────────
    sidebar.addEventListener('mouseenter', () => {
        sidebar.style.transform = 'translateY(0)';
    });
    sidebar.addEventListener('mouseleave', () => {
        if (Object.keys(openChats).length === 0) {
            sidebar.style.transform = 'translateY(calc(100% - 44px))';
        }
    });

    function updateSidebarState() {
        sidebar.style.transform = Object.keys(openChats).length > 0
            ? 'translateY(0)'
            : 'translateY(calc(100% - 44px))';
    }

    // ─── Clic dans le vide → ferme tout ──────────────────
    document.addEventListener('click', function(e) {
        const chatWindowsDiv  = document.getElementById('chat-windows');
        const searchContainer = document.getElementById('search-container');

        if (chatWindowsDiv && !chatWindowsDiv.contains(e.target) && !sidebar.contains(e.target)) {
            Object.keys(openChats).forEach(friendId => {
                const win = openChats[friendId];
                win.style.transition = 'opacity 0.25s ease';
                win.style.opacity    = '0';
                setTimeout(() => { win.remove(); delete openChats[friendId]; }, 250);
            });
            setTimeout(() => {
                sidebar.style.transition = 'transform 0.3s ease';
                sidebar.style.transform  = 'translateY(calc(100% - 44px))';
            }, 200);
        }

        if (searchContainer && !searchContainer.contains(e.target)) {
            document.getElementById('search-dropdown').classList.add('hidden');
        }
    });

    // ─── Amis en ligne ────────────────────────────────────
    function refreshOnlineFriends() {
        fetch('/online-friends', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': APP_CSRF }
        })
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(friends => {
            const panel = document.getElementById('friends-list-panel');
            const label = document.getElementById('online-count-label');

            if (!Array.isArray(friends)) {
                label.textContent = 'Amis en ligne (0)';
                panel.innerHTML   = noFriendsHtml();
                return;
            }

            label.textContent = `Amis en ligne (${friends.length})`;

            if (friends.length === 0) {
                panel.innerHTML = noFriendsHtml();
                return;
            }

            panel.innerHTML = friends.map(friend => {
                const avatar = friend.avatar
                    ? `<img src="${friend.avatar}" class="w-9 h-9 rounded-full object-cover border border-gray-200"/>`
                    : `<div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-sm font-bold">${friend.name.charAt(0).toUpperCase()}</div>`;
                return `
                    <div class="flex items-center justify-between px-3 py-2 hover:bg-gray-50 cursor-pointer transition"
                         onclick="openChat(${friend.id}, '${friend.name.replace(/'/g,"\\'")}', '${friend.avatar ?? ''}')">
                        <div class="flex items-center gap-2">
                            <div class="relative flex-shrink-0">
                                ${avatar}
                                <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-400 rounded-full border-2 border-white"></span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800">${friend.name.substring(0, 18)}</p>
                                <p class="text-xs text-green-500">En ligne</p>
                            </div>
                        </div>
                        <i class="fa-solid fa-message text-blue-400 text-sm"></i>
                    </div>`;
            }).join('');
        })
        .catch(err => {
            console.error('online-friends error:', err);
            document.getElementById('online-count-label').textContent = 'Amis en ligne (0)';
            document.getElementById('friends-list-panel').innerHTML   = noFriendsHtml();
        });
    }

    function noFriendsHtml() {
        return `<div class="px-4 py-6 text-center text-gray-400 text-sm">
            <i class="fa-solid fa-moon mb-1 block text-xl"></i>
            Aucun ami en ligne
        </div>`;
    }

    // ─── Badge messages ───────────────────────────────────
    function updateMessageBadge() {
        fetch('/messages/unread-count', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': APP_CSRF }
        })
        .then(res => res.json())
        .then(data => {
            let badge = document.getElementById('messages-badge');
            if (data.count > 0) {
                if (!badge) {
                    const link  = document.getElementById('messages-nav-link');
                    badge       = document.createElement('span');
                    badge.id    = 'messages-badge';
                    badge.className = 'absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold w-4 h-4 rounded-full flex items-center justify-center';
                    link.appendChild(badge);
                }
                badge.textContent = data.count;
            } else {
                if (badge) badge.remove();
            }
        })
        .catch(err => console.error('badge error:', err));
    }

    // ─── Ouvre/Ferme un chat ──────────────────────────────
    function openChat(friendId, friendName, friendAvatar) {
        event.stopPropagation();

        if (openChats[friendId]) {
            openChats[friendId].remove();
            delete openChats[friendId];
            updateSidebarState();
            return;
        }

        const template   = document.getElementById('chat-template');
        const clone      = template.content.cloneNode(true);
        const chatWindow = clone.querySelector('.chat-window');

        chatWindow.dataset.friendId = friendId;
        chatWindow.querySelector('.chat-name').textContent = friendName;

        const avatarDiv = chatWindow.querySelector('.chat-avatar');
        if (friendAvatar) {
            avatarDiv.innerHTML = `<img src="${friendAvatar}" class="w-full h-full object-cover"/>`;
        } else {
            avatarDiv.textContent = friendName.charAt(0).toUpperCase();
        }

        chatWindow.querySelector('.chat-profile-link').href = `/profile/${friendId}`;
        chatWindow.querySelector('.close-chat').addEventListener('click', function(e) {
            e.stopPropagation();
            chatWindow.remove();
            delete openChats[friendId];
            updateSidebarState();
        });

        const input   = chatWindow.querySelector('.chat-input');
        const sendBtn = chatWindow.querySelector('.chat-send');

        function sendMsg() {
            const content = input.value.trim();
            if (!content) return;
            fetch(`/messages/${friendId}`, {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': APP_CSRF,
                    'Accept':       'application/json'
                },
                body: JSON.stringify({ content })
            }).then(() => { input.value = ''; loadChatMessages(friendId, chatWindow); });
        }

        sendBtn.addEventListener('click',    e => { e.stopPropagation(); sendMsg(); });
        input.addEventListener('keydown',    e => { if (e.key === 'Enter') sendMsg(); });
        input.addEventListener('click',      e => e.stopPropagation());
        chatWindow.addEventListener('click', e => e.stopPropagation());

        document.getElementById('chat-windows').appendChild(clone);
        openChats[friendId] = chatWindow;

        sidebar.style.transform = 'translateY(0)';
        loadChatMessages(friendId, chatWindow);
        setTimeout(() => input.focus(), 100);
    }

    // ─── Charge messages mini-chat ────────────────────────
    function loadChatMessages(friendId, chatWindow) {
        fetch(`/messages/${friendId}/read`, {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': APP_CSRF, 'Accept': 'application/json' }
        }).then(() => updateMessageBadge());

        fetch(`/messages/${friendId}/json`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': APP_CSRF }
        })
        .then(res => res.json())
        .then(messages => {
            const container = chatWindow.querySelector('.chat-messages');
            if (!Array.isArray(messages) || !messages.length) {
                container.innerHTML = '<div class="text-center text-gray-400 text-xs py-4">Commence la conversation !</div>';
                return;
            }
            container.innerHTML = messages.map(msg => {
                const isMine  = msg.sender_id === APP_AUTH_ID;
                const content = (msg.content ?? '').replace('📖 [story_public] ', '📖 ');
                return `<div class="flex ${isMine ? 'justify-end' : 'justify-start'}">
                    <div class="max-w-[80%] px-3 py-1.5 rounded-2xl text-sm
                        ${isMine ? 'bg-blue-600 text-white rounded-br-sm' : 'bg-white text-gray-800 rounded-bl-sm shadow-sm border'}">
                        ${content}
                        ${msg.image ? `<img src="/storage/${msg.image}" class="max-w-full rounded-lg mt-1 cursor-pointer" onclick="window.open('/storage/${msg.image}','_blank')"/>` : ''}
                    </div>
                </div>`;
            }).join('');
            container.scrollTop = container.scrollHeight;
        })
        .catch(err => console.error('loadChatMessages error:', err));
    }

    // ─── Intervals ───────────────────────────────────────
    setInterval(function() {
        Object.keys(openChats).forEach(fid => loadChatMessages(fid, openChats[fid]));
        if (Object.keys(openChats).length === 0) updateMessageBadge();
    }, 3000);

    setInterval(refreshOnlineFriends, 30000);

    // ─── ✅ Appels immédiats ──────────────────────────────
    refreshOnlineFriends();
    updateMessageBadge();

    // ─── Recherche ────────────────────────────────────────
    let searchTimeout = null;

    document.getElementById('search-input').addEventListener('focus', function() {
        if (this.value.length >= 2) {
            document.getElementById('search-dropdown').classList.remove('hidden');
        }
    });

    function fetchSuggestions(query) {
        clearTimeout(searchTimeout);
        if (query.length < 2) {
            document.getElementById('search-results').innerHTML = '';
            document.getElementById('search-footer').classList.add('hidden');
            document.getElementById('search-dropdown').classList.add('hidden');
            return;
        }
        document.getElementById('search-loader').classList.remove('hidden');
        document.getElementById('search-dropdown').classList.remove('hidden');

        searchTimeout = setTimeout(() => {
            fetch(`/search/suggestions?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('search-loader').classList.add('hidden');
                    renderSuggestions(data, query);
                })
                .catch(err => console.error('search error:', err));
        }, 300);
    }

    function renderSuggestions(data, query) {
        const container = document.getElementById('search-results');
        const footer    = document.getElementById('search-footer');
        const fullLink  = document.getElementById('search-full-link');
        let html = '';

        if (data.users && data.users.length > 0) {
            html += `<div class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Utilisateurs</div>`;
            data.users.forEach(user => {
                const statusColor = { online:'#4ade80', away:'#facc15', busy:'#f87171', offline:'#9ca3af' }[user.status] || '#9ca3af';
                const avatar = user.avatar
                    ? `<img src="${user.avatar}" class="w-9 h-9 rounded-full object-cover"/>`
                    : `<div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">${user.name.charAt(0).toUpperCase()}</div>`;
                html += `
                    <a href="${user.url}"
                       onmousedown="event.preventDefault(); window.location='${user.url}';"
                       class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition cursor-pointer">
                        <div class="relative flex-shrink-0">
                            ${avatar}
                            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full border-2 border-white"
                                  style="background:${statusColor}"></span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">${user.name}</p>
                            <p class="text-xs text-gray-400">${user.status === 'online' ? '🟢 En ligne' : '⚫ Hors ligne'}</p>
                        </div>
                    </a>`;
            });
        }

        if (data.posts && data.posts.length > 0) {
            html += `<div class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Posts</div>`;
            data.posts.forEach(post => {
                html += `
                    <a href="${post.url}"
                       onmousedown="event.preventDefault(); window.location='${post.url}';"
                       class="flex items-start gap-3 px-4 py-2.5 hover:bg-gray-50 transition cursor-pointer">
                        <i class="fa-solid fa-newspaper text-blue-400 mt-0.5 flex-shrink-0"></i>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">${post.title}</p>
                            <p class="text-xs text-gray-400">${post.content} · <span class="text-blue-400">${post.author}</span></p>
                        </div>
                    </a>`;
            });
        }

        if ((!data.users || data.users.length === 0) && (!data.posts || data.posts.length === 0)) {
            html = `<div class="px-4 py-6 text-center text-gray-400 text-sm">
                <i class="fa-solid fa-face-sad-tear block text-2xl mb-1"></i>Aucun résultat
            </div>`;
        }

        container.innerHTML = html;
        fullLink.href = `/search?q=${encodeURIComponent(query)}`;
        fullLink.setAttribute('onmousedown', `event.preventDefault(); window.location='/search?q=${encodeURIComponent(query)}';`);
        footer.classList.remove('hidden');
    }
    </script>
    @endauth

</body>
</html>