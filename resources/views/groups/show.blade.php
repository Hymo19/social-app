@extends('layouts.app')
@section('title', $group->name)
@section('content')

<div class="max-w-6xl mx-auto mt-4 px-4">
<div class="grid grid-cols-1 lg:grid-cols-4 gap-5" style="height: calc(100vh - 100px);">

    {{-- ── Sidebar membres ──────────────────────────────── --}}
    <div class="hidden lg:flex flex-col bg-white rounded-2xl shadow overflow-hidden">

        <div class="p-4 border-b">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-12 h-12 rounded-xl overflow-hidden flex-shrink-0 bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center">
                    @if($group->avatar)
                        <img src="{{ $group->avatar_url }}" class="w-full h-full object-cover"/>
                    @else
                        <i class="fa-solid fa-people-group text-white"></i>
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="font-bold text-gray-800 truncate">{{ $group->name }}</p>
                    <p class="text-xs text-gray-400">{{ $members->count() }} membres</p>
                </div>
            </div>
            @if($group->description)
                <p class="text-xs text-gray-500 mb-3">{{ $group->description }}</p>
            @endif
            <div class="flex gap-2">
                <button onclick="startCall('audio')"
                        class="flex-1 bg-green-500 hover:bg-green-600 text-white rounded-xl py-2 text-xs font-semibold transition flex items-center justify-center gap-1">
                    <i class="fa-solid fa-phone"></i> Audio
                </button>
                <button onclick="startCall('video')"
                        class="flex-1 bg-blue-500 hover:bg-blue-600 text-white rounded-xl py-2 text-xs font-semibold transition flex items-center justify-center gap-1">
                    <i class="fa-solid fa-video"></i> Vidéo
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-3 space-y-1">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">
                Membres ({{ $members->count() }})
            </p>
            @foreach($members as $member)
            <div class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-gray-50 group">
                <div class="relative flex-shrink-0">
                    @if($member->avatar)
                        <img src="{{ Storage::url($member->avatar) }}" class="w-9 h-9 rounded-full object-cover"/>
                    @else
                        <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-sm font-bold">
                            {{ strtoupper(substr($member->name, 0, 1)) }}
                        </div>
                    @endif
                    @if($member->status === 'online')
                        <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-400 rounded-full border-2 border-white"></div>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-700 truncate">{{ $member->name }}</p>
                    <p class="text-xs {{ $member->pivot->role === 'admin' ? 'text-blue-500 font-semibold' : 'text-gray-400' }}">
                        {{ $member->pivot->role === 'admin' ? '👑 Admin' : 'Membre' }}
                    </p>
                </div>
                @if($group->isAdmin(auth()->id()) && $member->id !== auth()->id())
                <div class="hidden group-hover:flex items-center gap-1">
                    <form method="POST" action="{{ route('groups.members.remove', [$group, $member]) }}">
                        @csrf @method('DELETE')
                        <button title="Retirer"
                                class="w-6 h-6 bg-red-100 text-red-500 rounded-full flex items-center justify-center hover:bg-red-200 transition text-xs">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </form>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        @if($group->isAdmin(auth()->id()))
        <div class="p-3 border-t">
            <form method="POST" action="{{ route('groups.members.add', $group) }}" class="flex gap-2">
                @csrf
                <select name="user_id"
                        class="flex-1 border rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-300">
                    <option value="">Ajouter un membre...</option>
                    @foreach($allUsers as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                <button type="submit"
                        class="bg-blue-600 text-white px-3 py-2 rounded-xl text-sm hover:bg-blue-700 transition">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </form>
        </div>
        @endif
    </div>

    {{-- ── Chat principal ────────────────────────────────── --}}
    <div class="lg:col-span-3 flex flex-col bg-white rounded-2xl shadow overflow-hidden">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-3 border-b bg-white flex-shrink-0">
            <div class="flex items-center gap-3">
                <a href="{{ route('groups.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div class="w-10 h-10 rounded-xl overflow-hidden bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center flex-shrink-0">
                    @if($group->avatar)
                        <img src="{{ $group->avatar_url }}" class="w-full h-full object-cover"/>
                    @else
                        <i class="fa-solid fa-people-group text-white text-sm"></i>
                    @endif
                </div>
                <div>
                    <p class="font-bold text-gray-800">{{ $group->name }}</p>
                    <p class="text-xs text-gray-400">{{ $members->count() }} membres</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="startCall('audio')"
                        class="w-9 h-9 bg-green-50 hover:bg-green-100 text-green-600 rounded-xl flex items-center justify-center transition"
                        title="Appel audio">
                    <i class="fa-solid fa-phone text-sm"></i>
                </button>
                <button onclick="startCall('video')"
                        class="w-9 h-9 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center transition"
                        title="Appel vidéo">
                    <i class="fa-solid fa-video text-sm"></i>
                </button>
                @if($group->isAdmin(auth()->id()))
                <button onclick="document.getElementById('modal-settings').classList.remove('hidden')"
                        class="w-9 h-9 bg-gray-50 hover:bg-gray-100 text-gray-600 rounded-xl flex items-center justify-center transition">
                    <i class="fa-solid fa-gear text-sm"></i>
                </button>
                @endif
            </div>
        </div>

        {{-- Messages --}}
        <div id="messages-container" class="flex-1 overflow-y-auto p-5 space-y-4 bg-gray-50">
            @foreach($messages as $msg)
            <div class="flex gap-3 group {{ $msg->user_id === auth()->id() ? 'flex-row-reverse' : '' }}"
                 id="msg-{{ $msg->id }}">

                {{-- Avatar --}}
                @if($msg->user_id !== auth()->id())
                    @if($msg->user->avatar)
                        <img src="{{ Storage::url($msg->user->avatar) }}"
                             class="w-8 h-8 rounded-full object-cover flex-shrink-0 mt-1"/>
                    @else
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-bold flex-shrink-0 mt-1">
                            {{ strtoupper(substr($msg->user->name, 0, 1)) }}
                        </div>
                    @endif
                @endif

                <div class="flex flex-col {{ $msg->user_id === auth()->id() ? 'items-end' : 'items-start' }} max-w-xs lg:max-w-md">
                    @if($msg->user_id !== auth()->id())
                        <p class="text-xs text-gray-400 mb-1 ml-1">{{ $msg->user->name }}</p>
                    @endif

                    <div class="relative">
                        {{-- Contenu --}}
                        @if($msg->type === 'call_started')
                            <div class="bg-green-50 border border-green-200 rounded-2xl px-4 py-2.5 text-sm text-green-700 flex items-center gap-2">
                                <i class="fa-solid fa-phone text-green-500"></i> {{ $msg->content }}
                            </div>
                        @elseif($msg->type === 'call_ended')
                            <div class="bg-gray-100 border border-gray-200 rounded-2xl px-4 py-2.5 text-sm text-gray-500 flex items-center gap-2">
                                <i class="fa-solid fa-phone-slash text-gray-400"></i> {{ $msg->content }}
                            </div>
                        @elseif($msg->image)
                            <img src="{{ $msg->image_url }}"
                                 class="max-w-xs rounded-2xl shadow-sm cursor-pointer hover:opacity-90 transition"
                                 onclick="this.requestFullscreen()"/>
                        @else
                            <div class="px-4 py-2.5 rounded-2xl text-sm
                                {{ $msg->user_id === auth()->id()
                                    ? 'bg-blue-600 text-white rounded-tr-sm'
                                    : 'bg-white text-gray-800 shadow-sm rounded-tl-sm' }}">
                                {{ $msg->content }}
                            </div>
                        @endif

                        {{-- Boutons selon le rôle --}}
                        @if($msg->user_id === auth()->id() || $group->isAdmin(auth()->id()))
                            {{-- Auteur ou Admin → 2 boutons --}}
                            <div class="absolute -top-2 {{ $msg->user_id === auth()->id() ? '-left-16' : '-right-16' }}
                                        opacity-0 group-hover:opacity-100 transition flex gap-1">
                                <button onclick="deleteMessage({{ $msg->id }})"
                                        class="w-6 h-6 bg-gray-200 hover:bg-red-100 hover:text-red-500
                                               rounded-full flex items-center justify-center text-gray-400 text-xs transition"
                                        title="Supprimer pour tout le monde">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <button onclick="hideMessage({{ $msg->id }})"
                                        class="w-6 h-6 bg-gray-200 hover:bg-orange-100 hover:text-orange-500
                                               rounded-full flex items-center justify-center text-gray-400 text-xs transition"
                                        title="Supprimer pour moi uniquement">
                                    <i class="fa-solid fa-eye-slash"></i>
                                </button>
                            </div>
                        @else
                            {{-- Simple membre → 1 bouton --}}
                            <button onclick="hideMessage({{ $msg->id }})"
                                    class="absolute -top-2 -right-8
                                           opacity-0 group-hover:opacity-100 transition
                                           w-6 h-6 bg-gray-200 hover:bg-orange-100 hover:text-orange-500
                                           rounded-full flex items-center justify-center text-gray-400 text-xs"
                                    title="Supprimer pour moi uniquement">
                                <i class="fa-solid fa-eye-slash"></i>
                            </button>
                        @endif
                    </div>

                    <p class="text-xs text-gray-400 mt-1 mx-1">{{ $msg->created_at->diffForHumans() }}</p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Zone saisie --}}
        <div class="px-4 py-3 border-t bg-white flex-shrink-0">
            <div class="flex items-end gap-3">
                <label class="w-9 h-9 bg-gray-100 hover:bg-gray-200 rounded-xl flex items-center justify-center cursor-pointer transition flex-shrink-0">
                    <i class="fa-solid fa-image text-gray-500 text-sm"></i>
                    <input type="file" id="img-input" accept="image/*" class="hidden" onchange="sendImage(this)"/>
                </label>
                <div class="flex-1 bg-gray-100 rounded-2xl px-4 py-2.5 flex items-end gap-2">
                    <textarea id="msg-input" rows="1"
                              placeholder="Écrire un message..."
                              class="flex-1 bg-transparent text-sm focus:outline-none resize-none max-h-32"
                              onkeydown="handleMsgKey(event)"
                              oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"></textarea>
                </div>
                <button onclick="sendMessage()"
                        class="w-10 h-10 bg-blue-600 hover:bg-blue-700 rounded-xl flex items-center justify-center transition flex-shrink-0">
                    <i class="fa-solid fa-paper-plane text-white text-sm"></i>
                </button>
            </div>
        </div>
    </div>
</div>
</div>

{{-- Modal paramètres --}}
@if($group->isAdmin(auth()->id()))
<div id="modal-settings" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-bold">Paramètres du groupe</h2>
            <button onclick="document.getElementById('modal-settings').classList.add('hidden')"
                    class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-gray-200 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('groups.update', $group) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf @method('PUT')
            <input type="text" name="name" value="{{ $group->name }}" required
                   class="w-full border rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"/>
            <textarea name="description" rows="2"
                      class="w-full border rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 resize-none">{{ $group->description }}</textarea>
            <div>
                <p class="text-xs text-gray-500 mb-1">Changer la photo du groupe</p>
                <input type="file" name="avatar" accept="image/*" class="w-full border rounded-xl px-4 py-3 text-sm"/>
            </div>
            <button type="submit"
                    class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold hover:bg-blue-700 transition">
                <i class="fa-solid fa-floppy-disk mr-2"></i>Enregistrer
            </button>
        </form>
        <form method="POST" action="{{ route('groups.destroy', $group) }}" class="mt-3">
            @csrf @method('DELETE')
            <button onclick="return confirm('Supprimer ce groupe définitivement ?')"
                    class="w-full bg-red-50 text-red-600 border border-red-200 py-3 rounded-xl font-semibold hover:bg-red-100 transition">
                <i class="fa-solid fa-trash mr-2"></i>Supprimer le groupe
            </button>
        </form>
    </div>
</div>
@endif

{{-- Modal confirmation suppression --}}
<div id="modal-confirm" class="hidden fixed inset-0 bg-black/40 z-[200] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
        <div id="modal-confirm-icon"
             class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4">
        </div>
        <h3 id="modal-confirm-title" class="text-lg font-bold text-gray-800 mb-2"></h3>
        <p id="modal-confirm-text" class="text-sm text-gray-500 mb-6"></p>
        <div class="flex gap-3">
            <button onclick="closeConfirm()"
                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2.5 rounded-xl font-semibold transition text-sm">
                Annuler
            </button>
            <button id="modal-confirm-btn"
                    class="flex-1 py-2.5 rounded-xl font-semibold transition text-sm text-white">
                Confirmer
            </button>
        </div>
    </div>
</div>

{{-- Modal appel --}}
<div id="call-modal" class="hidden fixed inset-0 z-[100] flex flex-col items-center justify-center"
     style="background:rgba(0,0,0,0.95);">
    <p class="text-white font-bold text-xl mb-6">
        <i class="fa-solid fa-phone-volume mr-2 text-green-400"></i>
        Appel en cours — {{ $group->name }}
    </p>
    <div class="flex flex-wrap gap-4 justify-center items-center flex-1 px-6 w-full max-w-5xl">
        <div class="relative rounded-2xl overflow-hidden bg-gray-800 shadow-2xl border border-white/10"
             style="width:280px;height:210px;">
            <video id="local-video" autoplay muted playsinline class="w-full h-full object-cover"></video>
            <div class="absolute bottom-2 left-2 bg-black/60 rounded-full px-2.5 py-0.5 text-white text-xs font-semibold">Vous</div>
        </div>
        <div id="remote-videos" class="flex flex-wrap gap-4 justify-center"></div>
    </div>
    <div class="flex items-center gap-5 py-8">
        <button onclick="toggleMic()" id="btn-mic"
                class="w-14 h-14 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center transition text-white">
            <i class="fa-solid fa-microphone text-xl"></i>
        </button>
        <button onclick="toggleCam()" id="btn-cam"
                class="w-14 h-14 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center transition text-white">
            <i class="fa-solid fa-video text-xl"></i>
        </button>
        <button onclick="endCall()"
                class="w-16 h-16 bg-red-600 hover:bg-red-700 rounded-full flex items-center justify-center transition shadow-xl">
            <i class="fa-solid fa-phone-slash text-white text-2xl"></i>
        </button>
        <button onclick="shareScreen()" id="btn-screen"
                class="w-14 h-14 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center transition text-white">
            <i class="fa-solid fa-display text-xl"></i>
        </button>
    </div>
</div>

{{-- Scripts --}}
<script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
<script>
const GROUP_ID   = {{ $group->id }};
const MY_ID      = {{ auth()->id() }};
const MY_NAME    = @json(auth()->user()->name);
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
const msgContainer = document.getElementById('messages-container');
msgContainer.scrollTop = msgContainer.scrollHeight;

// ── Pusher / Reverb ───────────────────────────────────────
const pusher = new Pusher('{{ env("REVERB_APP_KEY") }}', {
    wsHost:            'localhost',
    wsPort:            8080,
    wssPort:           8080,
    forceTLS:          false,
    enabledTransports: ['ws', 'wss'],
    cluster:           'mt1',
    authEndpoint:      '/broadcasting/auth',
    auth: { headers: { 'X-CSRF-TOKEN': CSRF_TOKEN } },
});

const channel = pusher.subscribe(`presence-group.${GROUP_ID}`);
channel.bind('App\\Events\\GroupMessageSent', data => appendMessage(data));
channel.bind('App\\Events\\GroupCallSignal',  data => handleCallSignal(data));

// ── Envoyer message ───────────────────────────────────────
async function sendMessage() {
    const input = document.getElementById('msg-input');
    const text  = input.value.trim();
    if (!text) return;
    input.value = '';
    input.style.height = 'auto';

    const res = await fetch(`/groups/${GROUP_ID}/messages`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept':       'application/json',
        },
        body: JSON.stringify({ content: text })
    });
    const msg = await res.json();
    appendMessage(msg, true);
}

function handleMsgKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

// ── Envoyer image ─────────────────────────────────────────
async function sendImage(input) {
    if (!input.files[0]) return;
    const form = new FormData();
    form.append('image', input.files[0]);
    const res = await fetch(`/groups/${GROUP_ID}/messages`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
        body: form
    });
    const msg = await res.json();
    appendMessage(msg, true);
    input.value = '';
}

// ── Afficher message ──────────────────────────────────────
function appendMessage(msg, isMine = false) {
    const mine = isMine || msg.user?.id === MY_ID;

    let bubble = '';
    if (msg.type === 'call_started') {
        bubble = `<div class="bg-green-50 border border-green-200 rounded-2xl px-4 py-2.5 text-sm text-green-700 flex items-center gap-2"><i class="fa-solid fa-phone text-green-500"></i>${msg.content}</div>`;
    } else if (msg.type === 'call_ended') {
        bubble = `<div class="bg-gray-100 border border-gray-200 rounded-2xl px-4 py-2.5 text-sm text-gray-500 flex items-center gap-2"><i class="fa-solid fa-phone-slash text-gray-400"></i>${msg.content}</div>`;
    } else if (msg.image_url) {
        bubble = `<img src="${msg.image_url}" class="max-w-xs rounded-2xl shadow-sm cursor-pointer hover:opacity-90 transition" onclick="this.requestFullscreen()"/>`;
    } else {
        bubble = `<div class="px-4 py-2.5 rounded-2xl text-sm ${mine
            ? 'bg-blue-600 text-white rounded-tr-sm'
            : 'bg-white text-gray-800 shadow-sm rounded-tl-sm'}">${msg.content}</div>`;
    }

    // Auteur du nouveau message → toujours 2 boutons
    const btns = `
        <div class="absolute -top-2 ${mine ? '-left-16' : '-right-16'}
                    opacity-0 group-hover:opacity-100 transition flex gap-1">
            <button onclick="deleteMessage(${msg.id})"
                    class="w-6 h-6 bg-gray-200 hover:bg-red-100 hover:text-red-500
                           rounded-full flex items-center justify-center text-gray-400 text-xs transition"
                    title="Supprimer pour tout le monde">
                <i class="fa-solid fa-trash"></i>
            </button>
            <button onclick="hideMessage(${msg.id})"
                    class="w-6 h-6 bg-gray-200 hover:bg-orange-100 hover:text-orange-500
                           rounded-full flex items-center justify-center text-gray-400 text-xs transition"
                    title="Supprimer pour moi uniquement">
                <i class="fa-solid fa-eye-slash"></i>
            </button>
        </div>`;

    const avatar = mine ? '' : (msg.user?.avatar_url
        ? `<img src="${msg.user.avatar_url}" class="w-8 h-8 rounded-full object-cover flex-shrink-0 mt-1"/>`
        : `<div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-bold flex-shrink-0 mt-1">${(msg.user?.name ?? '?')[0].toUpperCase()}</div>`);

    const name = !mine && msg.user?.name
        ? `<p class="text-xs text-gray-400 mb-1 ml-1">${msg.user.name}</p>` : '';

    msgContainer.innerHTML += `
        <div class="flex gap-3 group ${mine ? 'flex-row-reverse' : ''}" id="msg-${msg.id}">
            ${avatar}
            <div class="flex flex-col ${mine ? 'items-end' : 'items-start'} max-w-xs lg:max-w-md">
                ${name}
                <div class="relative">
                    ${bubble}
                    ${btns}
                </div>
                <p class="text-xs text-gray-400 mt-1 mx-1">${msg.created_at}</p>
            </div>
        </div>`;

    msgContainer.scrollTop = msgContainer.scrollHeight;
}

// ── Modal confirmation ────────────────────────────────────
function showConfirm({ title, text, icon, iconBg, btnColor, callback }) {
    document.getElementById('modal-confirm-title').textContent = title;
    document.getElementById('modal-confirm-text').textContent  = text;
    document.getElementById('modal-confirm-icon').className    =
        `w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4 ${iconBg}`;
    document.getElementById('modal-confirm-icon').innerHTML    =
        `<i class="${icon} text-xl"></i>`;
    document.getElementById('modal-confirm-btn').className     =
        `flex-1 py-2.5 rounded-xl font-semibold transition text-sm text-white ${btnColor}`;
    document.getElementById('modal-confirm-btn').onclick = () => {
        closeConfirm();
        callback();
    };
    document.getElementById('modal-confirm').classList.remove('hidden');
}

function closeConfirm() {
    document.getElementById('modal-confirm').classList.add('hidden');
}

document.getElementById('modal-confirm').addEventListener('click', function(e) {
    if (e.target === this) closeConfirm();
});

// ── 🗑️ Suppression définitive ────────────────────────────
function deleteMessage(msgId) {
    showConfirm({
        title:    'Supprimer pour tout le monde ?',
        text:     'Ce message sera définitivement supprimé pour tous les membres.',
        icon:     'fa-solid fa-trash text-red-500',
        iconBg:   'bg-red-100',
        btnColor: 'bg-red-600 hover:bg-red-700',
        callback: async () => {
            await fetch(`/groups/${GROUP_ID}/messages/${msgId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            });
            removeMessage(msgId);
        }
    });
}

// ── 👁️ Masquer pour soi uniquement ───────────────────────
function hideMessage(msgId) {
    showConfirm({
        title:    'Supprimer pour moi ?',
        text:     'Ce message disparaîtra uniquement de ta vue.',
        icon:     'fa-solid fa-eye-slash text-orange-500',
        iconBg:   'bg-orange-100',
        btnColor: 'bg-orange-500 hover:bg-orange-600',
        callback: async () => {
            await fetch(`/groups/${GROUP_ID}/messages/${msgId}/hide`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            });
            removeMessage(msgId);
        }
    });
}

// ── Retirer le message de l'écran ─────────────────────────
function removeMessage(msgId) {
    const el = document.getElementById(`msg-${msgId}`);
    if (el) {
        el.style.transition = 'opacity 0.3s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 300);
    }
}

// ════════════════════════════════════════════════════════════
// ── WebRTC ────────────────────────────────────────────────
// ════════════════════════════════════════════════════════════
let localStream = null;
let peers       = {};
let micOn       = true;
let camOn       = true;

const iceConfig = { iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] };

async function startCall(type = 'video') {
    document.getElementById('call-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    try {
        localStream = await navigator.mediaDevices.getUserMedia({
            audio: true,
            video: type === 'video',
        });
        document.getElementById('local-video').srcObject = localStream;
    } catch(e) {
        alert('Impossible d\'accéder au micro/caméra.');
        document.getElementById('call-modal').classList.add('hidden');
        document.body.style.overflow = '';
        return;
    }
    await sendSignal('start', { type });
    const res = await fetch(`/groups/${GROUP_ID}/messages`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
        body: JSON.stringify({
            content: `📞 ${MY_NAME} a démarré un appel ${type === 'video' ? 'vidéo' : 'audio'}`,
            type: 'call_started'
        })
    });
    const msg = await res.json();
    appendMessage(msg, true);
}

async function endCall() {
    if (localStream) { localStream.getTracks().forEach(t => t.stop()); localStream = null; }
    Object.values(peers).forEach(p => p.close());
    peers = {};
    document.getElementById('remote-videos').innerHTML = '';
    document.getElementById('local-video').srcObject   = null;
    document.getElementById('call-modal').classList.add('hidden');
    document.body.style.overflow = '';
    await sendSignal('end', {});
    const res = await fetch(`/groups/${GROUP_ID}/messages`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
        body: JSON.stringify({ content: `📵 ${MY_NAME} a terminé l'appel`, type: 'call_ended' })
    });
    const msg = await res.json();
    appendMessage(msg, true);
}

function toggleMic() {
    if (!localStream) return;
    micOn = !micOn;
    localStream.getAudioTracks().forEach(t => t.enabled = micOn);
    document.getElementById('btn-mic').innerHTML = micOn
        ? '<i class="fa-solid fa-microphone text-xl"></i>'
        : '<i class="fa-solid fa-microphone-slash text-xl text-red-400"></i>';
}

function toggleCam() {
    if (!localStream) return;
    camOn = !camOn;
    localStream.getVideoTracks().forEach(t => t.enabled = camOn);
    document.getElementById('btn-cam').innerHTML = camOn
        ? '<i class="fa-solid fa-video text-xl"></i>'
        : '<i class="fa-solid fa-video-slash text-xl text-red-400"></i>';
}

async function shareScreen() {
    try {
        const screen = await navigator.mediaDevices.getDisplayMedia({ video: true });
        const track  = screen.getVideoTracks()[0];
        Object.values(peers).forEach(peer => {
            const sender = peer.getSenders().find(s => s.track?.kind === 'video');
            if (sender) sender.replaceTrack(track);
        });
        document.getElementById('local-video').srcObject = screen;
        track.onended = () => {
            Object.values(peers).forEach(peer => {
                const sender = peer.getSenders().find(s => s.track?.kind === 'video');
                const orig   = localStream?.getVideoTracks()[0];
                if (sender && orig) sender.replaceTrack(orig);
            });
            document.getElementById('local-video').srcObject = localStream;
        };
    } catch(e) { console.log('Partage annulé'); }
}

function createPeer(userId, initiator = false) {
    if (peers[userId]) return peers[userId];
    const peer = new RTCPeerConnection(iceConfig);
    peers[userId] = peer;
    if (localStream) localStream.getTracks().forEach(t => peer.addTrack(t, localStream));
    peer.ontrack = e => {
        let rv = document.getElementById(`rv-${userId}`);
        if (!rv) {
            const div = document.createElement('div');
            div.className = 'relative rounded-2xl overflow-hidden bg-gray-800 shadow-2xl border border-white/10';
            div.style     = 'width:280px;height:210px;';
            div.innerHTML = `
                <video id="rv-${userId}" autoplay playsinline class="w-full h-full object-cover"></video>
                <div class="absolute bottom-2 left-2 bg-black/60 rounded-full px-2.5 py-0.5 text-white text-xs">Participant</div>`;
            document.getElementById('remote-videos').appendChild(div);
            rv = document.getElementById(`rv-${userId}`);
        }
        rv.srcObject = e.streams[0];
    };
    peer.onicecandidate = e => {
        if (e.candidate) sendSignal('candidate', e.candidate, userId);
    };
    if (initiator) {
        peer.createOffer()
            .then(o => peer.setLocalDescription(o))
            .then(() => sendSignal('offer', peer.localDescription, userId));
    }
    return peer;
}

async function handleCallSignal(e) {
    if (e.from_user_id === MY_ID) return;
    if (e.signal === 'start') {
        if (!localStream) {
            if (confirm(`📞 ${e.from_user_name} a démarré un appel. Rejoindre ?`)) {
                await startCall(e.data?.type ?? 'video');
            }
        } else {
            createPeer(e.from_user_id, true);
        }
        return;
    }
    if (e.signal === 'end') {
        const rv = document.getElementById(`rv-${e.from_user_id}`);
        if (rv) rv.closest('div').remove();
        if (peers[e.from_user_id]) { peers[e.from_user_id].close(); delete peers[e.from_user_id]; }
        return;
    }
    if (e.to_user_id && e.to_user_id !== MY_ID) return;
    if (e.signal === 'offer') {
        const peer = createPeer(e.from_user_id, false);
        await peer.setRemoteDescription(new RTCSessionDescription(e.data));
        const answer = await peer.createAnswer();
        await peer.setLocalDescription(answer);
        sendSignal('answer', peer.localDescription, e.from_user_id);
    }
    if (e.signal === 'answer') {
        const peer = peers[e.from_user_id];
        if (peer) await peer.setRemoteDescription(new RTCSessionDescription(e.data));
    }
    if (e.signal === 'candidate') {
        const peer = peers[e.from_user_id];
        if (peer) await peer.addIceCandidate(new RTCIceCandidate(e.data));
    }
}

async function sendSignal(signal, data, toUserId = null) {
    await fetch(`/groups/${GROUP_ID}/call-signal`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ signal, data, to_user_id: toUserId })
    });
}
</script>
@endsection