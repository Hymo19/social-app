@extends('layouts.app')
@section('title', 'Mes Groupes')
@section('content')
<div class="max-w-4xl mx-auto mt-6 space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fa-solid fa-people-group text-blue-600"></i> Mes Groupes
        </h1>
        <button onclick="document.getElementById('modal-create').classList.remove('hidden')"
                class="bg-blue-600 text-white px-5 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Nouveau groupe
        </button>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">
            {{ session('success') }}
        </div>
    @endif

    @forelse($groups as $group)
    <a href="{{ route('groups.show', $group) }}"
       class="flex items-center gap-4 bg-white rounded-2xl shadow p-4 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5 block">
        <div class="w-14 h-14 rounded-2xl overflow-hidden flex-shrink-0 bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center">
            @if($group->avatar)
                <img src="{{ $group->avatar_url }}" class="w-full h-full object-cover"/>
            @else
                <i class="fa-solid fa-people-group text-white text-xl"></i>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-gray-800 text-lg truncate">{{ $group->name }}</p>
            @if($group->description)
                <p class="text-sm text-gray-500 truncate">{{ $group->description }}</p>
            @endif
            <p class="text-xs text-gray-400 mt-1">
                <i class="fa-solid fa-users mr-1"></i>{{ $group->members->count() }} membres
                @if($group->isAdmin(auth()->id()))
                    · <span class="text-blue-500 font-semibold">Admin</span>
                @endif
            </p>
        </div>
        <i class="fa-solid fa-chevron-right text-gray-300"></i>
    </a>
    @empty
    <div class="bg-white rounded-2xl shadow p-12 text-center text-gray-400">
        <i class="fa-solid fa-people-group text-5xl mb-4 block"></i>
        <p class="text-lg font-semibold">Aucun groupe pour l'instant</p>
        <p class="text-sm mt-1">Crée ton premier groupe de discussion !</p>
    </div>
    @endforelse
</div>

{{-- Modal Créer --}}
<div id="modal-create" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-bold text-gray-800">Créer un groupe</h2>
            <button onclick="document.getElementById('modal-create').classList.add('hidden')"
                    class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-gray-200 transition">
                <i class="fa-solid fa-xmark text-gray-600"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('groups.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div class="flex items-center gap-4">
                <div id="group-avatar-preview"
                     class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center overflow-hidden flex-shrink-0 cursor-pointer"
                     onclick="document.getElementById('group-avatar-input').click()">
                    <i class="fa-solid fa-camera text-white text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-700">Photo du groupe</p>
                    <p class="text-xs text-gray-400">Clique pour choisir</p>
                </div>
                <input type="file" id="group-avatar-input" name="avatar" accept="image/*" class="hidden"
                       onchange="previewGroupAvatar(this)"/>
            </div>

            <input type="text" name="name" placeholder="Nom du groupe *" required
                   class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"/>

            <textarea name="description" placeholder="Description (optionnel)" rows="2"
                      class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 resize-none"></textarea>

            <div>
                <p class="text-sm font-semibold text-gray-700 mb-2">
                    <i class="fa-solid fa-user-plus text-blue-500 mr-1"></i>Ajouter des membres
                </p>
                <div class="space-y-1 max-h-48 overflow-y-auto border border-gray-100 rounded-xl p-2">
                    @foreach(\App\Models\User::where('id', '!=', auth()->id())->get() as $user)
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="members[]" value="{{ $user->id }}" class="rounded accent-blue-600"/>
                        @if($user->avatar)
                            <img src="{{ Storage::url($user->avatar) }}" class="w-8 h-8 rounded-full object-cover"/>
                        @else
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-bold">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                        <span class="text-sm text-gray-700">{{ $user->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold hover:bg-blue-700 transition">
                <i class="fa-solid fa-people-group mr-2"></i>Créer le groupe
            </button>
        </form>
    </div>
</div>

<script>
function previewGroupAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('group-avatar-preview').innerHTML =
                `<img src="${e.target.result}" class="w-full h-full object-cover"/>`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection