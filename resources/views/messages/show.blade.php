@extends('layouts.app')

@section('title', 'Conversation avec ' . $user->name)

@section('content')
<style>
    .message-bubble { transition: all 0.15s ease; }
    .msg-react-btn {
        opacity: 0; transform: scale(0.7); transition: all 0.15s ease; pointer-events: none;
    }
    .message-bubble:hover .msg-react-btn {
        opacity: 1; transform: scale(1); pointer-events: all;
    }
    .reaction-picker {
        display: none; position: absolute; z-index: 30; background: white;
        border: 1px solid #e5e7eb; border-radius: 999px; padding: 5px 8px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.13); white-space: nowrap;
        bottom: calc(100% + 6px); animation: pickerIn 0.15s ease;
    }
    @keyframes pickerIn {
        from { opacity:0; transform: scale(0.85) translateY(6px); }
        to   { opacity:1; transform: scale(1) translateY(0); }
    }
    .reaction-picker.open { display: flex; align-items: center; gap: 2px; }
    .reaction-badge {
        background: white; border: 1px solid #e5e7eb; border-radius: 999px;
        padding: 2px 7px; font-size: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.07);
        cursor: pointer; transition: transform 0.15s; display: inline-flex; align-items: center; gap: 2px;
    }
    .reaction-badge:hover { transform: scale(1.15); }
    .msg-sticker {
        font-size: 3.5rem; line-height: 1; display: inline-block;
        animation: stickerPop 0.4s cubic-bezier(0.175,0.885,0.32,1.275);
        cursor: default; user-select: none;
    }
    @keyframes stickerPop {
        0%   { transform: scale(0); opacity: 0; }
        60%  { transform: scale(1.3); opacity: 1; }
        100% { transform: scale(1); }
    }
    .sticker-item { transition: transform 0.15s ease; cursor: pointer; }
    .sticker-item:hover { transform: scale(1.3); }
    .modal-overlay { backdrop-filter: blur(4px); }
    .slide-in { animation: slideIn 0.25s ease; }
    @keyframes slideIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
</style>

@php
    $iBlockedThem = \Illuminate\Support\Facades\DB::table('blocked_users')
        ->where('user_id', auth()->id())->where('blocked_user_id', $user->id)->exists();
    $theyBlockedMe = \Illuminate\Support\Facades\DB::table('blocked_users')
        ->where('user_id', $user->id)->where('blocked_user_id', auth()->id())->exists();
    $isBlocked  = $iBlockedThem;
    $isArchived = \Illuminate\Support\Facades\DB::table('archived_conversations')
        ->where('user_id', auth()->id())->where('contact_id', $user->id)->exists();
    $friends = auth()->user()->friends()->where('id', '!=', $user->id)->get();
    $statusColors = ['online'=>'bg-green-400','away'=>'bg-yellow-400','busy'=>'bg-red-400','offline'=>'bg-gray-400'];
@endphp

<div class="max-w-4xl mx-auto mt-2 flex flex-col" style="height: calc(100vh - 90px);">

    {{-- Header --}}
    <div class="bg-white rounded-t-2xl shadow-sm border border-gray-200 px-5 py-3 flex items-center justify-between flex-shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('messages.index') }}" class="text-gray-400 hover:text-blue-600 transition mr-1">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div class="relative">
                @if($user->avatar)
                    <img src="{{ Storage::url($user->avatar) }}" class="w-11 h-11 rounded-full object-cover border-2 border-blue-200"/>
                @else
                    <div class="w-11 h-11 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xl font-bold">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
                <span class="absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white {{ $statusColors[$user->status] ?? 'bg-gray-400' }}"></span>
            </div>
            <div>
                <p class="font-bold text-gray-800">{{ $user->name }}</p>
                <p class="text-xs {{ $user->status === 'online' ? 'text-green-500' : 'text-gray-400' }}">
                    @if($user->status === 'online') 🟢 En ligne
                    @elseif($user->status === 'away') 🟡 Absent
                    @elseif($user->status === 'busy') 🔴 Occupé
                    @else ⚫ Hors ligne @endif
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('profile.show', $user) }}"
               class="flex items-center gap-1.5 text-xs text-gray-500 bg-gray-100 hover:bg-blue-50 hover:text-blue-600 px-3 py-1.5 rounded-full transition">
                <i class="fa-solid fa-user"></i> Profil
            </a>
            <button onclick="toggleArchive()" id="archive-btn"
                    class="flex items-center gap-1.5 text-xs {{ $isArchived ? 'text-orange-600 bg-orange-50' : 'text-gray-500 bg-gray-100' }} hover:bg-orange-50 hover:text-orange-600 px-3 py-1.5 rounded-full transition">
                <i class="fa-solid fa-box-archive"></i>
                <span id="archive-label">{{ $isArchived ? 'Désarchiver' : 'Archiver' }}</span>
            </button>
            <button onclick="openBlockModal()" id="block-btn"
                    class="flex items-center gap-1.5 text-xs {{ $isBlocked ? 'text-red-600 bg-red-50' : 'text-gray-500 bg-gray-100' }} hover:bg-red-50 hover:text-red-600 px-3 py-1.5 rounded-full transition">
                <i class="fa-solid fa-ban"></i>
                <span id="block-label">{{ $isBlocked ? 'Débloquer' : 'Bloquer' }}</span>
            </button>
            <button onclick="openReportModal()"
                    class="flex items-center gap-1.5 text-xs text-gray-500 bg-gray-100 hover:bg-yellow-50 hover:text-yellow-600 px-3 py-1.5 rounded-full transition">
                <i class="fa-solid fa-flag"></i> Signaler
            </button>
        </div>
    </div>

    {{-- Zone messages --}}
    <div id="messages-container"
         class="flex-1 overflow-y-auto bg-gray-50 px-5 py-4 space-y-3 border-x border-gray-200">
    </div>

    {{-- Zone de saisie --}}
    @if($theyBlockedMe)
    <div class="bg-gray-100 border border-gray-200 rounded-b-2xl px-5 py-4 text-center text-gray-500 text-sm flex-shrink-0">
        <i class="fa-solid fa-lock mr-2 text-gray-400"></i>Tu ne peux pas répondre à cette conversation.
    </div>
    @elseif($iBlockedThem)
    <div class="bg-red-50 border border-red-200 rounded-b-2xl px-5 py-4 text-center text-red-600 text-sm flex-shrink-0">
        <i class="fa-solid fa-ban mr-2"></i>Tu as bloqué {{ $user->name }}.
        <button onclick="openBlockModal()" class="underline font-medium hover:text-red-800 ml-1">Débloquer</button>
    </div>
    @else
    <div class="bg-white border border-t-0 border-gray-200 rounded-b-2xl px-4 py-3 flex-shrink-0">
        <div id="image-preview" class="hidden mb-2 relative inline-block">
            <img id="preview-img" class="h-20 rounded-xl border border-gray-200"/>
            <button onclick="clearImage()" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full text-xs flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="sticker-panel" class="hidden mb-3 bg-gray-50 rounded-2xl border border-gray-200 p-3 slide-in">
            <div class="flex gap-2 mb-2 overflow-x-auto pb-1 flex-wrap">
                @foreach(['😊','😂','😍','😎','🥳','😢','😡','🤔','👍','👎','❤️','🔥','💯','🎉','✨','🙏','💪','😴','🤣','😅','🥰','😏','🤯','😱','🤝','👋','🫶','💀','😇','🤩'] as $s)
                <span class="sticker-item text-2xl" onclick="sendSticker('{{ $s }}')">{{ $s }}</span>
                @endforeach
            </div>
            <p class="text-xs text-gray-400 text-center">Clique sur un sticker pour l'envoyer</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="toggleStickers()"
                    class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-yellow-50 text-gray-400 hover:text-yellow-500 transition text-xl flex-shrink-0">😊</button>
            <label class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-blue-50 text-gray-400 hover:text-blue-500 transition cursor-pointer flex-shrink-0">
                <i class="fa-solid fa-image"></i>
                <input type="file" id="image-input" accept="image/*" class="hidden" onchange="previewImage(this)"/>
            </label>
            <input type="text" id="msg-input"
                   placeholder="Écrire un message..."
                   class="flex-1 bg-gray-100 border-0 rounded-full px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"/>
            <button onclick="sendMessage()"
                    class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center hover:bg-blue-700 transition flex-shrink-0">
                <i class="fa-solid fa-paper-plane text-sm"></i>
            </button>
        </div>
    </div>
    @endif
</div>

{{-- Modal Bloquer --}}
<div id="block-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay bg-black/40">
    <div class="bg-white rounded-2xl shadow-2xl w-96 p-6 slide-in">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                <i class="fa-solid fa-ban text-red-500 text-xl"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-800" id="block-modal-title">Bloquer {{ $user->name }}</h3>
                <p class="text-sm text-gray-500" id="block-modal-desc">Cette personne ne pourra plus t'envoyer de messages.</p>
            </div>
        </div>
        <div class="flex gap-3 mt-4">
            <button onclick="closeModal('block-modal')" class="flex-1 bg-gray-100 text-gray-700 py-2.5 rounded-xl font-semibold hover:bg-gray-200 transition">Annuler</button>
            <button onclick="confirmBlock()" id="confirm-block-btn" class="flex-1 bg-red-500 text-white py-2.5 rounded-xl font-semibold hover:bg-red-600 transition">
                <i class="fa-solid fa-ban mr-1"></i>Bloquer
            </button>
        </div>
    </div>
</div>

{{-- Modal Signaler --}}
<div id="report-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay bg-black/40">
    <div class="bg-white rounded-2xl shadow-2xl w-[440px] p-6 slide-in">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fa-solid fa-flag text-yellow-500 text-xl"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-800">Signaler {{ $user->name }}</h3>
                <p class="text-sm text-gray-500">Ce signalement sera examiné par notre équipe.</p>
            </div>
        </div>
        <div class="space-y-3">
            <div>
                <label class="text-sm font-medium text-gray-700 mb-1 block">Raison <span class="text-red-500">*</span></label>
                <select id="report-reason" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-300">
                    <option value="">-- Choisir une raison --</option>
                    <option value="spam">Spam ou publicité</option>
                    <option value="harassment">Harcèlement ou intimidation</option>
                    <option value="hate">Contenu haineux</option>
                    <option value="violence">Violence ou menaces</option>
                    <option value="fake">Faux compte</option>
                    <option value="inappropriate">Contenu inapproprié</option>
                    <option value="other">Autre</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 mb-1 block">Description (optionnel)</label>
                <textarea id="report-desc" rows="3" placeholder="Décris le problème..."
                          class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-300 resize-none"></textarea>
            </div>
        </div>
        <div class="flex gap-3 mt-5">
            <button onclick="closeModal('report-modal')" class="flex-1 bg-gray-100 text-gray-700 py-2.5 rounded-xl font-semibold hover:bg-gray-200 transition">Annuler</button>
            <button onclick="submitReport()" class="flex-1 bg-yellow-500 text-white py-2.5 rounded-xl font-semibold hover:bg-yellow-600 transition">
                <i class="fa-solid fa-flag mr-1"></i>Signaler
            </button>
        </div>
    </div>
</div>

{{-- Modal Transférer --}}
<div id="forward-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay bg-black/40">
    <div class="bg-white rounded-2xl shadow-2xl w-[420px] p-6 slide-in">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fa-solid fa-share text-blue-500 text-xl"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-800">Transférer le message</h3>
                <p class="text-sm text-gray-500 truncate max-w-[260px]" id="forward-preview"></p>
            </div>
        </div>
        <input type="hidden" id="forward-msg-id"/>
        <div class="space-y-2 max-h-64 overflow-y-auto">
            @forelse($friends as $friend)
            <label class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 cursor-pointer border border-transparent hover:border-blue-200 transition">
                <input type="checkbox" name="forward_to" value="{{ $friend->id }}" class="w-4 h-4 accent-blue-600 rounded"/>
                <div class="flex items-center gap-2">
                    @if($friend->avatar)
                        <img src="{{ Storage::url($friend->avatar) }}" class="w-9 h-9 rounded-full object-cover"/>
                    @else
                        <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">
                            {{ strtoupper(substr($friend->name, 0, 1)) }}
                        </div>
                    @endif
                    <p class="text-sm font-medium text-gray-700">{{ $friend->name }}</p>
                </div>
            </label>
            @empty
            <p class="text-center text-gray-400 text-sm py-4">Aucun ami disponible</p>
            @endforelse
        </div>
        <div class="flex gap-3 mt-5">
            <button onclick="closeModal('forward-modal')" class="flex-1 bg-gray-100 text-gray-700 py-2.5 rounded-xl font-semibold hover:bg-gray-200 transition">Annuler</button>
            <button onclick="submitForward()" class="flex-1 bg-blue-600 text-white py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition">
                <i class="fa-solid fa-share mr-1"></i>Transférer
            </button>
        </div>
    </div>
</div>

{{-- Toast --}}
<div id="toast" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-gray-800 text-white px-5 py-3 rounded-full text-sm font-medium shadow-xl flex items-center gap-2 slide-in">
    <i class="fa-solid fa-circle-check text-green-400"></i>
    <span id="toast-msg"></span>
</div>

<script>
const CSRF      = document.querySelector('meta[name="csrf-token"]')?.content;
const FRIEND_ID = {{ $user->id }};
const AUTH_ID   = {{ auth()->id() }};
let isBlocked   = {{ $isBlocked ? 'true' : 'false' }};
let isArchived  = {{ $isArchived ? 'true' : 'false' }};
let pendingImage = null;

const container = document.getElementById('messages-container');

function scrollBottom(smooth = false) {
    container?.scrollTo({ top: container.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.reaction-picker') && !e.target.closest('.msg-react-btn')) {
        document.querySelectorAll('.reaction-picker.open').forEach(p => p.classList.remove('open'));
    }
});

function togglePicker(msgId, event) {
    event.stopPropagation();
    const picker = document.getElementById('picker-' + msgId);
    if (!picker) return;
    const wasOpen = picker.classList.contains('open');
    document.querySelectorAll('.reaction-picker.open').forEach(p => p.classList.remove('open'));
    if (!wasOpen) picker.classList.add('open');
}

function isSticker(content) {
    if (!content) return false;
    const emojiRegex = /^(\p{Emoji_Presentation}|\p{Extended_Pictographic})\s*$/u;
    return emojiRegex.test(content.trim());
}

function reactionsHash(messages) {
    return messages.map(m => {
        const r = (m.reactions || []).map(x => x.reaction + x.user_id).sort().join('|');
        return m.id + ':' + r;
    }).join(';');
}

let lastHash = '';

function loadMessages(forceScroll = false) {
    fetch(`/messages/${FRIEND_ID}/json`, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
    })
    .then(r => r.json())
    .then(messages => {
        if (!messages.length) return;
        const currentHash = reactionsHash(messages);
        if (currentHash === lastHash && !forceScroll) return;
        const atBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 80;
        lastHash = currentHash;
        container.innerHTML = messages.map(msg => buildMessageHTML(msg)).join('');
        if (atBottom || forceScroll) scrollBottom(forceScroll);
    });
}

function buildMessageHTML(msg) {
    const isMine    = msg.sender_id === AUTH_ID;
    const reactions = msg.reactions || [];

    const isStoryPublic  = msg.is_story_public;
    const cleanContent   = msg.content ? msg.content.replace('📖 [story_public] ', '📖 ') : '';
    const displayContent = isStoryPublic ? cleanContent : (msg.content || '');

    const groups = {};
    reactions.forEach(r => { groups[r.reaction] = (groups[r.reaction] || 0) + 1; });

    const badges = Object.entries(groups).map(([emoji, count]) =>
        `<span class="reaction-badge" onclick="reactToMessage(${msg.id},'${emoji}')">
            ${emoji}${count > 1 ? ' <span style="color:#6b7280;font-size:0.7rem">' + count + '</span>' : ''}
        </span>`
    ).join('');

    const pickerEmojis = ['❤️','😂','😮','😢','😡','👍','🔥','🎉'];
    const pickerBtns   = pickerEmojis.map(e =>
        `<button onclick="reactToMessage(${msg.id},'${e}');event.stopPropagation();"
                 style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;font-size:1.1rem;border:none;background:none;cursor:pointer;transition:transform 0.1s"
                 onmouseover="this.style.transform='scale(1.3)'" onmouseout="this.style.transform='scale(1)'">${e}</button>`
    ).join('');

    const pickerActions = `
        <div style="width:1px;height:20px;background:#e5e7eb;margin:0 4px"></div>
        <button onclick="openForwardModal(${msg.id},'${(displayContent||'Image').substring(0,30).replace(/'/g,"\\'")}');event.stopPropagation();"
                style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;border:none;background:none;cursor:pointer;color:#9ca3af"
                onmouseover="this.style.color='#2563eb'" onmouseout="this.style.color='#9ca3af'" title="Transférer">
            <i class="fa-solid fa-share" style="font-size:0.75rem"></i>
        </button>
        ${msg.sender_id === AUTH_ID ? `
        <button onclick="deleteMessage(${msg.id});event.stopPropagation();"
                style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;border:none;background:none;cursor:pointer;color:#9ca3af"
                onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#9ca3af'" title="Supprimer">
            <i class="fa-solid fa-trash" style="font-size:0.75rem"></i>
        </button>` : ''}`;

    const reactBtn = `
        <button class="msg-react-btn"
                onclick="togglePicker(${msg.id}, event)"
                style="position:absolute;
                       ${isMine ? 'right:calc(100% + 6px)' : 'left:calc(100% + 6px)'};
                       top:50%;transform:translateY(-50%) scale(0.7);
                       width:28px;height:28px;border-radius:50%;
                       background:#f3f4f6;border:1px solid #d1d5db;
                       display:flex;align-items:center;justify-content:center;
                       cursor:pointer;padding:0;
                       box-shadow:0 1px 3px rgba(0,0,0,0.08);
                       color:#6b7280;font-size:0.85rem;line-height:1;">
            <span style="font-size:0.85rem;filter:grayscale(1);opacity:0.6">😶</span>
            <span style="font-size:0.6rem;font-weight:700;margin-left:1px">+</span>
        </button>`;

    const picker = `
        <div id="picker-${msg.id}" class="reaction-picker" style="${isMine ? 'right:0' : 'left:0'}">
            ${pickerBtns}${pickerActions}
        </div>`;

    const isStickerOnly = isSticker(displayContent) && !msg.image;
    let msgContent = '';
    if (isStickerOnly) {
        msgContent = `<span class="msg-sticker">${displayContent}</span>`;
    } else {
        if (displayContent) msgContent += `<p style="font-size:0.875rem;line-height:1.5">${displayContent}</p>`;
        if (msg.image)      msgContent += `<img src="/storage/${msg.image}" style="max-width:100%;border-radius:12px;margin-top:4px;cursor:pointer" onclick="window.open('/storage/${msg.image}','_blank')"/>`;
    }

    const timeHtml = isStickerOnly ? '' : `
        <p style="font-size:0.7rem;margin-top:4px;text-align:right;${isMine ? 'color:rgba(191,219,254,0.9)' : 'color:#9ca3af'}">
            ${new Date(msg.created_at).toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'})}
            ${isMine ? (msg.is_read
                ? '<i class="fa-solid fa-check-double" style="margin-left:3px;color:#93c5fd"></i>'
                : '<i class="fa-solid fa-check" style="margin-left:3px;opacity:0.6"></i>') : ''}
        </p>`;

    const bubbleStyle = isStickerOnly ? '' :
        isMine
        ? 'background:#2563eb;color:white;border-radius:18px 18px 4px 18px;padding:10px 14px'
        : 'background:white;color:#1f2937;border-radius:18px 18px 18px 4px;padding:10px 14px;box-shadow:0 1px 3px rgba(0,0,0,0.07);border:1px solid #f3f4f6';

    // ── Bloc suggestion d'ami ─────────────────────────────
    let friendSuggestion = '';
    if (isStoryPublic && !msg.is_friend) {
        if (!msg.pending_request) {
            // CAS 1 : Aucune demande → bouton Ajouter
            const avatarHtml = msg.sender_avatar
                ? `<img src="${msg.sender_avatar}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0"/>`
                : `<div style="width:32px;height:32px;border-radius:50%;background:#2563eb;color:white;display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;flex-shrink:0">${msg.sender_name ? msg.sender_name[0].toUpperCase() : '?'}</div>`;

            friendSuggestion = `
                <div style="margin-top:6px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:14px;padding:10px 12px;display:flex;align-items:center;justify-content:space-between;gap:10px;">
                    <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                        ${avatarHtml}
                        <div style="min-width:0;">
                            <p style="font-size:0.8rem;font-weight:600;color:#1e3a5f">${msg.sender_name ?? ''}</p>
                            <p style="font-size:0.7rem;color:#64748b">Vous n'êtes pas encore amis</p>
                        </div>
                    </div>
                    <button onclick="sendFriendRequest(${FRIEND_ID})"
                            id="fr-btn-${FRIEND_ID}"
                            style="background:#2563eb;color:white;font-size:0.75rem;font-weight:600;padding:6px 12px;border-radius:8px;border:none;cursor:pointer;white-space:nowrap;flex-shrink:0;display:flex;align-items:center;gap:4px;"
                            onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
                        <i class="fa-solid fa-user-plus" style="font-size:0.7rem"></i> Ajouter
                    </button>
                </div>`;

        } else if (msg.pending_request.direction === 'received') {
            // CAS 2 : Ils t'ont envoyé une demande → bouton Accepter
            friendSuggestion = `
                <div style="margin-top:6px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:14px;padding:10px 12px;display:flex;align-items:center;justify-content:space-between;gap:10px;">
                    <p style="font-size:0.75rem;color:#166534;font-weight:600">
                        <i class="fa-solid fa-user-plus mr-1"></i>t'a ajouté(e) en ami(e) !
                    </p>
                    <div style="display:flex;gap:6px">
                        <button onclick="acceptFriendRequest(${msg.pending_request.id})"
                                style="background:#16a34a;color:white;font-size:0.75rem;font-weight:600;padding:6px 12px;border-radius:8px;border:none;cursor:pointer;">
                            <i class="fa-solid fa-check"></i> Accepter
                        </button>
                        <button onclick="declineFriendRequest(${msg.pending_request.id})"
                                style="background:#f3f4f6;color:#6b7280;font-size:0.75rem;padding:6px 10px;border-radius:8px;border:1px solid #e5e7eb;cursor:pointer;">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>`;

        } else if (msg.pending_request.direction === 'sent') {
            // CAS 3 : J'ai déjà envoyé → en attente
            friendSuggestion = `
                <div style="margin-top:6px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:14px;padding:8px 12px;font-size:0.7rem;color:#9ca3af;display:flex;align-items:center;gap:6px;">
                    <i class="fa-solid fa-clock" style="color:#d1d5db"></i> Demande d'ami en attente
                </div>`;
        }
    }

    return `
    <div class="flex ${isMine ? 'justify-end' : 'justify-start'} message-bubble group" data-msg-id="${msg.id}">
        <div style="position:relative;max-width:65%">
            ${msg.forwarded_from_id ? `<p style="font-size:0.7rem;color:#9ca3af;margin-bottom:4px;text-align:${isMine?'right':'left'}"><i class="fa-solid fa-share" style="margin-right:3px"></i>Transféré</p>` : ''}
            <div style="${bubbleStyle}">${msgContent}${timeHtml}</div>
            ${badges ? `<div style="display:flex;gap:4px;margin-top:4px;flex-wrap:wrap;justify-content:${isMine?'flex-end':'flex-start'}">${badges}</div>` : ''}
            ${friendSuggestion}
            ${reactBtn}
            ${picker}
        </div>
    </div>`;
}

setInterval(() => loadMessages(false), 2000);
loadMessages(true);

// ── Envoyer message ───────────────────────────────────────
function sendMessage() {
    const input   = document.getElementById('msg-input');
    const content = input?.value.trim();
    if (!content && !pendingImage) return;

    const formData = new FormData();
    if (content)      formData.append('content', content);
    if (pendingImage) formData.append('image', pendingImage);
    formData.append('_token', CSRF);
    if (input) input.value = '';
    clearImage();

    fetch(`/messages/${FRIEND_ID}`, {
        method: 'POST', body: formData,
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) { showToast('⚠️ ' + data.error); return; }
        lastHash = '';
        loadMessages(true);
    });
}

document.getElementById('msg-input')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') sendMessage();
});

// ── Sticker ───────────────────────────────────────────────
function sendSticker(emoji) {
    fetch(`/messages/${FRIEND_ID}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ content: emoji })
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) { showToast('⚠️ ' + data.error); return; }
        document.getElementById('sticker-panel').classList.add('hidden');
        lastHash = '';
        loadMessages(true);
    });
}

function toggleStickers() {
    document.getElementById('sticker-panel').classList.toggle('hidden');
}

// ── Réactions messages ────────────────────────────────────
function reactToMessage(msgId, emoji) {
    document.querySelectorAll('.reaction-picker.open').forEach(p => p.classList.remove('open'));
    fetch(`/messages/${msgId}/react`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ reaction: emoji })
    })
    .then(() => { lastHash = ''; loadMessages(false); });
}

// ── Supprimer message ─────────────────────────────────────
function deleteMessage(msgId) {
    if (!confirm('Supprimer ce message ?')) return;
    fetch(`/messages/${msgId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    }).then(() => { lastHash = ''; loadMessages(false); });
}

// ── Image preview ─────────────────────────────────────────
function previewImage(input) {
    const file = input.files[0];
    if (!file) return;
    pendingImage = file;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('preview-img').src = e.target.result;
        document.getElementById('image-preview').classList.remove('hidden');
    };
    reader.readAsDataURL(file);
}

function clearImage() {
    pendingImage = null;
    document.getElementById('image-preview')?.classList.add('hidden');
    const inp = document.getElementById('image-input');
    if (inp) inp.value = '';
}

// ── Demande d'ami ─────────────────────────────────────────
function sendFriendRequest(userId) {
    const btn = document.getElementById(`fr-btn-${userId}`);
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>'; }

    fetch('/friend-requests', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ receiver_id: userId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success || data.message === 'Demande déjà existante') {
            // Remplace tous les blocs suggestion par "en attente"
            document.querySelectorAll('[id^="fr-btn-"]').forEach(b => {
                const parent = b.closest('[style*="background:#eff6ff"]') || b.parentElement?.parentElement;
                if (parent) {
                    parent.style.background = '#f9fafb';
                    parent.style.border = '1px solid #e5e7eb';
                    parent.innerHTML = `
                        <i class="fa-solid fa-check-circle" style="color:#22c55e;flex-shrink:0"></i>
                        <p style="color:#16a34a;font-size:0.75rem;font-weight:600;margin:0">
                            Demande envoyée — en attente de confirmation
                        </p>`;
                }
            });
            showToast(data.success ? '✅ Demande d\'ami envoyée !' : 'ℹ️ Demande déjà envoyée');
            setTimeout(() => { lastHash = ''; loadMessages(false); }, 500);
        } else {
            showToast('⚠️ ' + (data.message || 'Erreur'));
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-user-plus" style="font-size:0.7rem"></i> Ajouter';
            }
        }
    })
    .catch(() => showToast('⚠️ Erreur réseau'));
}

function acceptFriendRequest(requestId) {
    fetch(`/friend-requests/${requestId}/accept`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('🎉 Vous êtes maintenant amis !');
            setTimeout(() => { lastHash = ''; loadMessages(false); }, 500);
        } else {
            showToast('⚠️ ' + (data.error || 'Erreur'));
        }
    })
    .catch(() => showToast('⚠️ Erreur réseau'));
}

function declineFriendRequest(requestId) {
    fetch(`/friend-requests/${requestId}/decline`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Demande refusée');
            setTimeout(() => { lastHash = ''; loadMessages(false); }, 500);
        } else {
            showToast('⚠️ ' + (data.error || 'Erreur'));
        }
    })
    .catch(() => showToast('⚠️ Erreur réseau'));
}

// ── Archive ───────────────────────────────────────────────
function toggleArchive() {
    fetch(`/messages/${FRIEND_ID}/archive`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        isArchived = data.status === 'archived';
        document.getElementById('archive-label').textContent = isArchived ? 'Désarchiver' : 'Archiver';
        const btn = document.getElementById('archive-btn');
        btn.className = `flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-full transition
            ${isArchived ? 'text-orange-600 bg-orange-50' : 'text-gray-500 bg-gray-100'} hover:bg-orange-50 hover:text-orange-600`;
        showToast(isArchived ? 'Conversation archivée' : 'Conversation désarchivée');
    });
}

// ── Bloquer ───────────────────────────────────────────────
function openBlockModal() {
    const title = document.getElementById('block-modal-title');
    const desc  = document.getElementById('block-modal-desc');
    const btn   = document.getElementById('confirm-block-btn');
    if (isBlocked) {
        title.textContent = 'Débloquer {{ $user->name }}';
        desc.textContent  = "Cette personne pourra à nouveau t'envoyer des messages.";
        btn.innerHTML     = '<i class="fa-solid fa-unlock mr-1"></i>Débloquer';
        btn.className     = 'flex-1 bg-green-500 text-white py-2.5 rounded-xl font-semibold hover:bg-green-600 transition';
    } else {
        title.textContent = 'Bloquer {{ $user->name }}';
        desc.textContent  = "Cette personne ne pourra plus t'envoyer de messages.";
        btn.innerHTML     = '<i class="fa-solid fa-ban mr-1"></i>Bloquer';
        btn.className     = 'flex-1 bg-red-500 text-white py-2.5 rounded-xl font-semibold hover:bg-red-600 transition';
    }
    document.getElementById('block-modal').classList.remove('hidden');
}

function confirmBlock() {
    fetch(`/messages/${FRIEND_ID}/block`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        isBlocked = data.status === 'blocked';
        document.getElementById('block-label').textContent = isBlocked ? 'Débloquer' : 'Bloquer';
        const btn = document.getElementById('block-btn');
        btn.className = `flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-full transition
            ${isBlocked ? 'text-red-600 bg-red-50' : 'text-gray-500 bg-gray-100'} hover:bg-red-50 hover:text-red-600`;
        closeModal('block-modal');
        showToast(isBlocked ? '{{ $user->name }} est bloqué' : '{{ $user->name }} est débloqué');
        setTimeout(() => location.reload(), 1500);
    });
}

// ── Signaler ──────────────────────────────────────────────
function openReportModal() {
    document.getElementById('report-modal').classList.remove('hidden');
}

function submitReport() {
    const reason = document.getElementById('report-reason').value;
    const desc   = document.getElementById('report-desc').value;
    if (!reason) { alert('Choisis une raison.'); return; }
    fetch(`/messages/${FRIEND_ID}/report`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ reason, description: desc })
    })
    .then(r => r.json())
    .then(data => {
        closeModal('report-modal');
        if (data.auto_blocked) {
            showToast('Utilisateur signalé et automatiquement bloqué !');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('Signalement envoyé, merci !');
        }
    });
}

// ── Transférer ────────────────────────────────────────────
function openForwardModal(msgId, preview) {
    document.getElementById('forward-msg-id').value = msgId;
    document.getElementById('forward-preview').textContent = preview;
    document.querySelectorAll('input[name="forward_to"]').forEach(c => c.checked = false);
    document.getElementById('forward-modal').classList.remove('hidden');
}

function submitForward() {
    const msgId    = document.getElementById('forward-msg-id').value;
    const selected = [...document.querySelectorAll('input[name="forward_to"]:checked')].map(c => c.value);
    if (!selected.length) { alert('Sélectionne au moins un ami.'); return; }
    Promise.all(selected.map(userId =>
        fetch(`/messages/${msgId}/forward`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ to_user_id: userId })
        })
    )).then(() => {
        closeModal('forward-modal');
        showToast('📤 Message transféré à ' + selected.length + ' personne(s) !');
    });
}

// ── Utilitaires ───────────────────────────────────────────
function closeModal(id) {
    document.getElementById(id)?.classList.add('hidden');
}

document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', function(e) {
        if (e.target === this) this.classList.add('hidden');
    });
});

function showToast(msg) {
    const toast = document.getElementById('toast');
    document.getElementById('toast-msg').textContent = msg;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}
</script>
@endsection