@extends('layouts.app')

@section('title', 'Messages')

@section('content')
<style>
    .msg-tab { transition: all 0.2s ease; }
    .msg-tab.active { background: #2563eb; color: white; }
    .msg-tab:not(.active) { color: #6b7280; }
    .msg-tab:not(.active):hover { background: #f3f4f6; color: #1d4ed8; }
    .conversation-item { transition: all 0.2s ease; }
    .conversation-item:hover { background: #eff6ff; }
    .reminder-badge { animation: pulse-badge 2s infinite; }
    @keyframes pulse-badge {
        0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); }
        50%       { box-shadow: 0 0 0 6px rgba(239,68,68,0); }
    }
    .friend-suggestion {
        border-radius: 12px;
        padding: 10px 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        font-size: 0.8rem;
    }
    .friend-suggestion.no-request { background:#eff6ff; border:1px solid #bfdbfe; }
    .friend-suggestion.they-asked  { background:#f0fdf4; border:1px solid #bbf7d0; }
    .friend-suggestion.i-asked     { background:#f9fafb; border:1px solid #e5e7eb; }
</style>

@php
    $unreadCount   = $conversations->sum(fn($c) => $c->unreadCount ?? 0);
    $archivedCount = $conversations->filter(fn($c) => $c->isArchived ?? false)->count();
    $normalCount   = $conversations->filter(fn($c) => !($c->isArchived ?? false))->count();
@endphp

<div class="flex gap-6 mt-2" style="min-height: calc(100vh - 100px);">

    {{-- ══ SIDEBAR ══ --}}
    <aside class="w-72 flex-shrink-0 flex flex-col gap-4">

        {{-- Mon statut --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Mon statut</p>
            <div class="flex items-center gap-3">
                @if(auth()->user()->avatar)
                    <img src="{{ Storage::url(auth()->user()->avatar) }}"
                         class="w-11 h-11 rounded-full object-cover border-2 border-blue-200"/>
                @else
                    <div class="w-11 h-11 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-lg">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                @endif
                <div class="flex-1">
                    <p class="font-semibold text-gray-800 text-sm">{{ auth()->user()->name }}</p>
                    <form method="POST" action="{{ route('status.update') }}" id="status-form">
                        @csrf
                        <select name="status" onchange="document.getElementById('status-form').submit()"
                                class="text-xs border-0 bg-transparent text-gray-500 focus:outline-none cursor-pointer mt-0.5 -ml-0.5">
                            @php $s = auth()->user()->status ?? 'online'; @endphp
                            <option value="online"  {{ $s==='online'  ? 'selected' : '' }}>🟢 En ligne</option>
                            <option value="away"    {{ $s==='away'    ? 'selected' : '' }}>🟡 Absent</option>
                            <option value="busy"    {{ $s==='busy'    ? 'selected' : '' }}>🔴 Occupé</option>
                            <option value="offline" {{ $s==='offline' ? 'selected' : '' }}>⚫ Invisible</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>

        {{-- Filtres --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Boîte de réception</p>
            <div class="flex flex-col gap-1">
                <button onclick="setFilter('all')" id="tab-all"
                        class="msg-tab active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium w-full text-left">
                    <i class="fa-solid fa-inbox w-4 text-center"></i>
                    <span>Tous les messages</span>
                    @if($normalCount > 0)
                    <span class="ml-auto bg-blue-100 text-blue-600 text-xs font-bold px-2 py-0.5 rounded-full">{{ $normalCount }}</span>
                    @endif
                </button>
                <button onclick="setFilter('unread')" id="tab-unread"
                        class="msg-tab flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium w-full text-left">
                    <i class="fa-solid fa-envelope w-4 text-center"></i>
                    <span>Non lus</span>
                    @if($unreadCount > 0)
                    <span class="ml-auto bg-red-100 text-red-600 text-xs font-bold px-2 py-0.5 rounded-full reminder-badge">{{ $unreadCount }}</span>
                    @endif
                </button>
                <button onclick="setFilter('archived')" id="tab-archived"
                        class="msg-tab flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium w-full text-left">
                    <i class="fa-solid fa-box-archive w-4 text-center"></i>
                    <span>Archivés</span>
                    @if($archivedCount > 0)
                    <span class="ml-auto bg-orange-100 text-orange-600 text-xs font-bold px-2 py-0.5 rounded-full">{{ $archivedCount }}</span>
                    @endif
                </button>
                <button onclick="setFilter('deleted')" id="tab-deleted"
                        class="msg-tab flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium w-full text-left">
                    <i class="fa-solid fa-trash w-4 text-center"></i>
                    <span>Supprimés</span>
                </button>
            </div>
        </div>

        {{-- Rappel non lus --}}
        @if($unreadCount > 0)
        <div class="bg-gradient-to-br from-red-50 to-orange-50 border border-red-200 rounded-2xl p-4">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-bell text-red-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-red-700">
                        {{ $unreadCount }} message{{ $unreadCount > 1 ? 's' : '' }} non lu{{ $unreadCount > 1 ? 's' : '' }}
                    </p>
                    <p class="text-xs text-red-500 mt-0.5">Tu as des messages en attente</p>
                    <button onclick="setFilter('unread')"
                            class="mt-2 text-xs bg-red-500 text-white px-3 py-1 rounded-full hover:bg-red-600 transition font-medium">
                        Voir maintenant →
                    </button>
                </div>
            </div>
        </div>
        @endif

        {{-- Notifications push --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Notifications</p>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-bell text-blue-500 text-sm"></i>
                    <div>
                        <p class="text-sm font-medium text-gray-700">Notifications push</p>
                        <p class="text-xs text-gray-400" id="notif-status-text">Cliquer pour activer</p>
                    </div>
                </div>
                <button id="notif-toggle-btn" onclick="toggleNotifications()"
                        class="relative w-11 h-6 bg-gray-200 rounded-full focus:outline-none">
                    <span id="notif-knob" class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-300"></span>
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-2 leading-relaxed">
                <i class="fa-solid fa-shield-halved mr-1 text-green-400"></i>
                Reçois une alerte instantanée dès qu'un message arrive.
            </p>
        </div>

        {{-- Amis en ligne --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">
                <i class="fa-solid fa-circle text-green-400 text-xs mr-1"></i>Amis en ligne
            </p>
            @php
                $onlineFriends = auth()->user()->friends()
                    ->where('id', '!=', auth()->id())
                    ->where(function($q) {
                        $q->where('status', 'online')->orWhere('last_seen_at', '>=', now()->subMinutes(2));
                    })->get();
            @endphp
            @forelse($onlineFriends->take(6) as $friend)
            <a href="{{ route('messages.show', $friend) }}"
               class="flex items-center gap-2 py-1.5 hover:bg-gray-50 rounded-lg px-1 transition">
                <div class="relative flex-shrink-0">
                    @if($friend->avatar)
                        <img src="{{ Storage::url($friend->avatar) }}" class="w-8 h-8 rounded-full object-cover"/>
                    @else
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-bold">
                            {{ strtoupper(substr($friend->name, 0, 1)) }}
                        </div>
                    @endif
                    <span class="absolute bottom-0 right-0 w-2 h-2 bg-green-400 rounded-full border border-white"></span>
                </div>
                <p class="text-sm text-gray-700 font-medium">{{ Str::limit($friend->name, 16) }}</p>
            </a>
            @empty
            <p class="text-xs text-gray-400 text-center py-3">
                <i class="fa-solid fa-moon block text-xl mb-1"></i>Aucun ami en ligne
            </p>
            @endforelse
        </div>

    </aside>

    {{-- ══ CONTENU PRINCIPAL ══ --}}
    <div class="flex-1 flex flex-col gap-4">

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-message text-blue-600"></i>Messages
                </h1>
                <a href="{{ route('users.index') }}"
                   class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-blue-700 transition">
                    <i class="fa-solid fa-pen-to-square"></i>Nouveau message
                </a>
            </div>
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" id="search-msg-input"
                       placeholder="Rechercher une conversation..."
                       oninput="filterConversations(this.value)"
                       class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 transition"/>
                <span id="search-clear"
                      class="hidden absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-gray-400 hover:text-gray-600"
                      onclick="clearSearch()">
                    <i class="fa-solid fa-xmark"></i>
                </span>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex-1">
            <div class="px-5 py-3 border-b bg-gray-50 flex items-center justify-between">
                <p class="text-sm font-semibold text-gray-600" id="filter-label">
                    <i class="fa-solid fa-inbox mr-1 text-blue-500"></i> Tous les messages
                </p>
                <span class="text-xs text-gray-400" id="conv-count">{{ $normalCount }} conversation(s)</span>
            </div>
            <div id="conversations-list">
                <div class="py-10 text-center text-gray-300">
                    <i class="fa-solid fa-spinner fa-spin text-2xl"></i>
                </div>
            </div>
            <div id="no-results" class="hidden py-10 text-center text-gray-400">
                <i class="fa-solid fa-face-sad-tear text-3xl mb-2 block text-gray-300"></i>
                <p class="text-sm">Aucune conversation trouvée</p>
            </div>
        </div>
    </div>
</div>

{{-- Toast --}}
<div id="inbox-toast" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-gray-800 text-white px-5 py-3 rounded-full text-sm font-medium shadow-xl flex items-center gap-2">
    <i class="fa-solid fa-circle-check text-green-400"></i>
    <span id="inbox-toast-msg"></span>
</div>

<script>
const CSRF_TOKEN    = document.querySelector('meta[name="csrf-token"]')?.content;
let currentFilter   = 'all';
let lastUnreadCount = {{ $unreadCount }};

const filterLabels = {
    all:      '<i class="fa-solid fa-inbox mr-1 text-blue-500"></i> Tous les messages',
    unread:   '<i class="fa-solid fa-envelope mr-1 text-blue-500"></i> Messages non lus',
    archived: '<i class="fa-solid fa-box-archive mr-1 text-orange-500"></i> Messages archivés',
    deleted:  '<i class="fa-solid fa-trash mr-1 text-gray-500"></i> Messages supprimés',
};

function setFilter(filter) {
    currentFilter = filter;
    document.querySelectorAll('.msg-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + filter)?.classList.add('active');
    document.getElementById('filter-label').innerHTML = filterLabels[filter];
    const items = document.querySelectorAll('.conversation-item');
    let visible = 0;
    items.forEach(item => {
        let show = false;
        if (filter === 'all')      show = item.dataset.archived !== '1';
        if (filter === 'unread')   show = item.dataset.unread === '1' && item.dataset.archived !== '1';
        if (filter === 'archived') show = item.dataset.archived === '1';
        if (filter === 'deleted')  show = false;
        item.style.display = show ? 'flex' : 'none';
        if (show) visible++;
    });
    document.getElementById('conv-count').textContent = `${visible} conversation(s)`;
    const noResults = document.getElementById('no-results');
    if (visible === 0) {
        noResults.classList.remove('hidden');
        const msgs = {
            archived: '<i class="fa-solid fa-box-archive text-3xl mb-2 block text-gray-300"></i><p class="text-sm">Aucune conversation archivée</p>',
            deleted:  '<i class="fa-solid fa-trash text-3xl mb-2 block text-gray-300"></i><p class="text-sm">Aucun message supprimé</p>',
            unread:   '<i class="fa-solid fa-envelope-open text-3xl mb-2 block text-gray-300"></i><p class="text-sm">Tous les messages sont lus !</p>',
        };
        noResults.innerHTML = msgs[filter] || '<i class="fa-solid fa-face-sad-tear text-3xl mb-2 block text-gray-300"></i><p class="text-sm">Aucune conversation</p>';
    } else {
        noResults.classList.add('hidden');
    }
}

function filterConversations(query) {
    const q = query.toLowerCase().trim();
    document.getElementById('search-clear').classList.toggle('hidden', q === '');
    const items = document.querySelectorAll('.conversation-item');
    let visible = 0;
    items.forEach(item => {
        const match = (item.dataset.name || '').includes(q);
        item.style.display = match ? 'flex' : 'none';
        if (match) visible++;
    });
    document.getElementById('conv-count').textContent = `${visible} résultat(s)`;
    const noResults = document.getElementById('no-results');
    noResults.classList.toggle('hidden', visible > 0);
    if (visible === 0) {
        noResults.innerHTML = `<i class="fa-solid fa-face-sad-tear text-3xl mb-2 block text-gray-300"></i>
            <p class="text-sm">Aucun résultat pour "<strong>${query}</strong>"</p>`;
    }
}

function clearSearch() {
    document.getElementById('search-msg-input').value = '';
    filterConversations('');
    setFilter(currentFilter);
}

// ── Bloc suggestion d'ami ─────────────────────────────────
function buildFriendSuggestion(c) {
    if (!c.is_story_public || c.is_friend) return '';

    const avatarHtml = c.avatar
        ? `<img src="${c.avatar}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0"/>`
        : `<div style="width:32px;height:32px;border-radius:50%;background:#2563eb;color:white;display:flex;align-items:center;justify-content:center;font-size:0.85rem;font-weight:700;flex-shrink:0">${c.name.charAt(0).toUpperCase()}</div>`;

    // CAS 1 : Ils t'ont envoyé une demande
    if (c.they_requested && c.pending_request) {
        return `
        <div class="friend-suggestion they-asked" onclick="event.preventDefault();event.stopPropagation()">
            <div style="display:flex;align-items:center;gap:8px;min-width:0;flex:1">
                ${avatarHtml}
                <div style="min-width:0">
                    <p style="font-weight:600;color:#166534;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${c.name}</p>
                    <p style="font-size:0.7rem;color:#16a34a">t'a ajouté(e) en ami(e) !</p>
                </div>
            </div>
            <div style="display:flex;gap:6px;flex-shrink:0">
                <button onclick="acceptFriendRequest(${c.pending_request.id}, this)"
                        style="background:#16a34a;color:white;font-size:0.75rem;font-weight:600;padding:6px 12px;border-radius:8px;border:none;cursor:pointer;display:flex;align-items:center;gap:4px;"
                        onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
                    <i class="fa-solid fa-check" style="font-size:0.7rem"></i> Accepter
                </button>
                <button onclick="declineFriendRequest(${c.pending_request.id}, this)"
                        style="background:#f3f4f6;color:#6b7280;font-size:0.75rem;padding:6px 10px;border-radius:8px;border:1px solid #e5e7eb;cursor:pointer;"
                        onmouseover="this.style.background='#fee2e2';this.style.color='#ef4444'" onmouseout="this.style.background='#f3f4f6';this.style.color='#6b7280'">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>`;
    }

    // CAS 2 : J'ai déjà envoyé une demande
    if (c.pending_request && c.pending_request.direction === 'sent') {
        return `
        <div class="friend-suggestion i-asked" onclick="event.preventDefault();event.stopPropagation()">
            <i class="fa-solid fa-clock" style="color:#d1d5db;flex-shrink:0"></i>
            <p style="color:#9ca3af;font-size:0.75rem">Demande envoyée à <strong>${c.name}</strong> — en attente</p>
        </div>`;
    }

    // CAS 3 : Aucune demande → bouton Ajouter
    return `
    <div class="friend-suggestion no-request" id="suggestion-${c.id}" onclick="event.preventDefault();event.stopPropagation()">
        <div style="display:flex;align-items:center;gap:8px;min-width:0;flex:1">
            ${avatarHtml}
            <div style="min-width:0">
                <p style="font-weight:600;color:#1e3a5f;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${c.name}</p>
                <p style="font-size:0.7rem;color:#64748b">Vous n'êtes pas encore amis</p>
            </div>
        </div>
        <button onclick="sendFriendRequest(${c.id}, this)"
                style="background:#2563eb;color:white;font-size:0.75rem;font-weight:600;padding:6px 12px;border-radius:8px;border:none;cursor:pointer;white-space:nowrap;flex-shrink:0;display:flex;align-items:center;gap:4px;"
                onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
            <i class="fa-solid fa-user-plus" style="font-size:0.7rem"></i> Ajouter
        </button>
    </div>`;
}

// ── Rendu liste conversations ─────────────────────────────
function renderConversations(conversations) {
    const list = document.getElementById('conversations-list');
    if (!conversations.length) {
        list.innerHTML = `
            <div class="py-16 text-center text-gray-400">
                <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-message text-4xl text-blue-200"></i>
                </div>
                <p class="text-lg font-semibold text-gray-500">Aucune conversation</p>
                <a href="/users" class="inline-flex items-center gap-2 bg-blue-600 text-white px-6 py-2.5 rounded-full text-sm font-semibold hover:bg-blue-700 transition mt-4">
                    <i class="fa-solid fa-users"></i>Voir les utilisateurs
                </a>
            </div>`;
        return;
    }

    const dots = { online:'bg-green-400', away:'bg-yellow-400', busy:'bg-red-400', offline:'bg-gray-300' };

    list.innerHTML = conversations.map(c => {
        const isUnread   = c.unread_count > 0;
        const isArchived = c.is_archived;
        const avatarHtml = c.avatar
            ? `<img src="${c.avatar}" class="w-14 h-14 rounded-full object-cover border-2 ${isUnread ? 'border-blue-400' : 'border-gray-200'}"/>`
            : `<div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center text-blue-600 text-2xl font-bold">${c.name.charAt(0).toUpperCase()}</div>`;
        const dot = dots[c.status] || 'bg-gray-300';
        const lastMsg = c.last_message
            ? `<p class="text-sm truncate ${isUnread ? 'text-gray-700 font-medium' : 'text-gray-400'}">
                ${c.last_message_mine ? '<span class="text-blue-400 font-medium">Toi : </span>' : ''}
                ${c.last_message_is_image ? '<i class="fa-solid fa-image mr-1"></i>Image' : c.last_message}
               </p>`
            : `<p class="text-sm text-gray-400 italic">Aucun message</p>`;
        const badge = isUnread
            ? `<span class="bg-blue-600 text-white text-xs font-bold min-w-[22px] h-5 px-1.5 rounded-full flex items-center justify-center reminder-badge">${c.unread_count}</span>`
            : `<i class="fa-solid fa-check-double text-blue-300 text-xs"></i>`;
        const suggestion = buildFriendSuggestion(c);

        return `
        <div class="conversation-item border-b last:border-0 ${isUnread ? 'bg-blue-50/50' : ''}"
             data-name="${c.name.toLowerCase()}"
             data-unread="${isUnread ? '1' : '0'}"
             data-archived="${isArchived ? '1' : '0'}"
             style="display:flex;flex-direction:column;${isArchived ? 'display:none!important;' : ''}">
            <a href="/messages/${c.id}" class="flex items-center gap-4 px-5 py-4 w-full">
                <div class="relative flex-shrink-0">
                    ${avatarHtml}
                    <span class="absolute bottom-0.5 right-0.5 w-3.5 h-3.5 rounded-full border-2 border-white ${dot}"></span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-0.5">
                        <p class="font-semibold ${isUnread ? 'text-blue-700' : 'text-gray-800'}">
                            ${c.name}
                            ${isArchived ? '<span class="ml-1 text-xs bg-orange-100 text-orange-600 px-1.5 py-0.5 rounded-full">Archivé</span>' : ''}
                        </p>
                        <span class="text-xs text-gray-400 flex-shrink-0 ml-2">${c.last_message_time ?? ''}</span>
                    </div>
                    ${lastMsg}
                </div>
                <div class="flex flex-col items-end gap-2 flex-shrink-0">${badge}</div>
            </a>
            ${suggestion ? `<div class="px-5 pb-3">${suggestion}</div>` : ''}
        </div>`;
    }).join('');

    setFilter(currentFilter);
}

// ── Actions demandes d'ami ────────────────────────────────
function sendFriendRequest(userId, btn) {
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    }
    fetch('/friend-requests', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ receiver_id: userId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success || data.message === 'Demande déjà existante') {
            const suggestionDiv = btn?.closest('.friend-suggestion');
            if (suggestionDiv) {
                suggestionDiv.className = 'friend-suggestion i-asked';
                suggestionDiv.innerHTML = `
                    <i class="fa-solid fa-check-circle" style="color:#22c55e;flex-shrink:0"></i>
                    <p style="color:#16a34a;font-size:0.75rem;font-weight:600">
                        Demande envoyée — en attente de confirmation
                    </p>`;
            }
            showInboxToast(data.success ? '✅ Demande d\'ami envoyée !' : 'ℹ️ Demande déjà envoyée');
        } else {
            showInboxToast('⚠️ ' + (data.message || 'Erreur'));
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-user-plus" style="font-size:0.7rem"></i> Ajouter';
            }
        }
    })
    .catch(() => {
        showInboxToast('⚠️ Erreur réseau');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-user-plus" style="font-size:0.7rem"></i> Ajouter';
        }
    });
}

function acceptFriendRequest(requestId, btn) {
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>'; }
    fetch(`/friend-requests/${requestId}/accept`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showInboxToast('🎉 Vous êtes maintenant amis !');
            const suggestionDiv = btn?.closest('.friend-suggestion');
            if (suggestionDiv) {
                suggestionDiv.className = 'friend-suggestion i-asked';
                suggestionDiv.innerHTML = `
                    <i class="fa-solid fa-user-check" style="color:#22c55e;flex-shrink:0"></i>
                    <p style="color:#16a34a;font-size:0.75rem;font-weight:600">Vous êtes maintenant amis ! 🎉</p>`;
            }
            setTimeout(refreshInbox, 1500);
        } else {
            showInboxToast('⚠️ ' + (data.error || 'Erreur'));
        }
    })
    .catch(() => showInboxToast('⚠️ Erreur réseau'));
}

function declineFriendRequest(requestId, btn) {
    if (btn) btn.disabled = true;
    fetch(`/friend-requests/${requestId}/decline`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showInboxToast('Demande refusée');
            const suggestionDiv = btn?.closest('.friend-suggestion');
            if (suggestionDiv) suggestionDiv.remove();
            setTimeout(refreshInbox, 500);
        } else {
            showInboxToast('⚠️ ' + (data.error || 'Erreur'));
        }
    })
    .catch(() => showInboxToast('⚠️ Erreur réseau'));
}

// ── Refresh inbox ─────────────────────────────────────────
function refreshInbox() {
    fetch('/messages/inbox-json', {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN
        }
    })
    .then(r => r.json())
    .then(data => {
        const newUnread = data.unread_total;
        if (notifEnabled && Notification.permission === 'granted' && newUnread > lastUnreadCount) {
            const diff      = newUnread - lastUnreadCount;
            const newSender = data.conversations.find(c => c.unread_count > 0);
            new Notification('SocialApp — Nouveau message 💬', {
                body: newSender
                    ? `${newSender.name} t'a envoyé ${diff} nouveau(x) message(s)`
                    : `Tu as ${diff} nouveau(x) message(s) non lu(s)`,
                icon: newSender?.avatar || '/favicon.ico',
                tag: 'new-message', renotify: true,
            });
        }
        lastUnreadCount = newUnread;
        renderConversations(data.conversations);
    })
    .catch(() => {});
}

// ── Notifications Push ────────────────────────────────────
let notifEnabled = localStorage.getItem('pushNotif') === 'true';

function applyNotifState() {
    const btn  = document.getElementById('notif-toggle-btn');
    const knob = document.getElementById('notif-knob');
    const txt  = document.getElementById('notif-status-text');
    if (notifEnabled) {
        btn.style.background = '#2563eb'; knob.style.transform = 'translateX(20px)';
        txt.textContent = 'Activées ✓'; txt.className = 'text-xs text-green-500';
    } else {
        btn.style.background = '#e5e7eb'; knob.style.transform = 'translateX(0)';
        txt.textContent = 'Cliquer pour activer'; txt.className = 'text-xs text-gray-400';
    }
}

function toggleNotifications() {
    if (!('Notification' in window)) { alert("Navigateur non supporté."); return; }
    if (!notifEnabled) {
        Notification.requestPermission().then(perm => {
            if (perm === 'granted') {
                notifEnabled = true;
                localStorage.setItem('pushNotif', 'true');
                applyNotifState();
                new Notification('SocialApp 🔔', { body: 'Notifications activées !', icon: '/favicon.ico' });
            } else {
                alert("Notifications refusées. Change ça dans les paramètres du navigateur.");
            }
        });
    } else {
        notifEnabled = false;
        localStorage.setItem('pushNotif', 'false');
        applyNotifState();
    }
}

if ('Notification' in window && Notification.permission === 'granted') {
    notifEnabled = true;
    localStorage.setItem('pushNotif', 'true');
}
applyNotifState();

function showInboxToast(msg) {
    const toast = document.getElementById('inbox-toast');
    document.getElementById('inbox-toast-msg').textContent = msg;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}

setInterval(refreshInbox, 3000);
refreshInbox();
</script>
@endsection