@extends('layouts.app')

@section('title', 'Modifier le profil')

@section('content')
<div class="max-w-2xl mx-auto mt-6">
    <div class="bg-white rounded-2xl shadow p-6">

        <h2 class="text-xl font-bold text-gray-800 mb-6">
            <i class="fa-solid fa-user-pen mr-2 text-blue-600"></i>Modifier mon profil
        </h2>

        <form method="POST" action="{{ route('profile.update', $user) }}"
              enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            {{-- Avatar --}}
            <div class="flex items-center gap-4 mb-2">
                @if($user->avatar)
                    <img src="{{ Storage::url($user->avatar) }}"
                         class="w-20 h-20 rounded-full object-cover border-4 border-blue-200"/>
                @else
                    <div class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-3xl font-bold border-4 border-blue-200">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fa-solid fa-camera mr-1"></i>Photo de profil
                    </label>
                    <input type="file" name="avatar" accept="image/*"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
                    @error('avatar') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Cover --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fa-solid fa-image mr-1"></i>Photo de couverture
                </label>
                @if($user->cover_photo)
                    <img src="{{ Storage::url($user->cover_photo) }}"
                         class="w-full h-32 object-cover rounded-lg mb-2"/>
                    <p class="text-xs text-gray-400 mb-1">Uploade une nouvelle image pour la remplacer</p>
                @endif
                <input type="file" name="cover_photo" accept="image/*"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
                @error('cover_photo') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Nom --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fa-solid fa-user mr-1"></i>Nom complet
                </label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 @error('name') border-red-400 @enderror"/>
                @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Bio --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fa-solid fa-align-left mr-1"></i>Bio
                </label>
                <textarea name="bio" rows="3" placeholder="Parle un peu de toi..."
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">{{ old('bio', $user->bio) }}</textarea>
                @error('bio') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Localisation --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fa-solid fa-location-dot mr-1"></i>Ville / Pays
                </label>
                <input type="text" name="location" value="{{ old('location', $user->location) }}"
                       placeholder="ex: Paris, France"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"/>
                @error('location') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Date de naissance --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fa-solid fa-cake-candles mr-1"></i>Date de naissance
                </label>
                <input type="date" name="birth_date"
                       value="{{ old('birth_date', $user->birth_date?->format('Y-m-d')) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"/>
                @error('birth_date') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Statut de présence --}}
            <div class="border-t pt-4">
                <h3 class="text-sm font-bold text-gray-700 mb-3">
                    <i class="fa-solid fa-circle-dot mr-1 text-blue-500"></i>Statut de présence
                </h3>
                <select name="status"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 mb-2">
                    <option value="online"  {{ old('status', $user->status) === 'online'  ? 'selected' : '' }}>🟢 En ligne</option>
                    <option value="away"    {{ old('status', $user->status) === 'away'    ? 'selected' : '' }}>🟡 Absent</option>
                    <option value="busy"    {{ old('status', $user->status) === 'busy'    ? 'selected' : '' }}>🔴 Occupé</option>
                    <option value="offline" {{ old('status', $user->status) === 'offline' ? 'selected' : '' }}>⚫ Hors ligne</option>
                </select>
                <input type="text" name="status_text"
                       value="{{ old('status_text', $user->status_text) }}"
                       placeholder="ex: En train de coder 💻"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"/>
            </div>

            {{-- ✅ Confidentialité des stories --}}
            <div class="border-t pt-4">
                <h3 class="text-sm font-bold text-gray-700 mb-1">
                    <i class="fa-solid fa-lock mr-1 text-blue-500"></i>Confidentialité des stories
                </h3>
                <p class="text-xs text-gray-400 mb-3">Qui peut voir tes stories ?</p>

                <div class="flex flex-col gap-3">
                    {{-- Option Public --}}
                    <label class="flex items-center gap-4 p-3 border rounded-xl cursor-pointer transition
                                  {{ old('story_privacy', $user->story_privacy ?? 'public') === 'public'
                                     ? 'border-blue-400 bg-blue-50'
                                     : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50' }}"
                           id="label-public">
                        <input type="radio" name="story_privacy" value="public"
                               {{ old('story_privacy', $user->story_privacy ?? 'public') === 'public' ? 'checked' : '' }}
                               class="accent-blue-600 w-4 h-4"
                               onchange="updatePrivacyLabels()"/>
                        <div>
                            <p class="font-semibold text-gray-800 text-sm">
                                🌍 Tout le monde
                            </p>
                            <p class="text-xs text-gray-400">N'importe qui peut voir tes stories</p>
                        </div>
                    </label>

                    {{-- Option Amis --}}
                    <label class="flex items-center gap-4 p-3 border rounded-xl cursor-pointer transition
                                  {{ old('story_privacy', $user->story_privacy ?? 'public') === 'friends'
                                     ? 'border-blue-400 bg-blue-50'
                                     : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50' }}"
                           id="label-friends">
                        <input type="radio" name="story_privacy" value="friends"
                               {{ old('story_privacy', $user->story_privacy ?? 'public') === 'friends' ? 'checked' : '' }}
                               class="accent-blue-600 w-4 h-4"
                               onchange="updatePrivacyLabels()"/>
                        <div>
                            <p class="font-semibold text-gray-800 text-sm">
                                👥 Amis uniquement
                            </p>
                            <p class="text-xs text-gray-400">Seuls tes amis peuvent voir tes stories</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Boutons --}}
            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                    <i class="fa-solid fa-floppy-disk mr-2"></i>Enregistrer
                </button>
                <a href="{{ route('profile.show', $user) }}"
                   class="flex-1 text-center bg-gray-100 text-gray-700 py-2 rounded-lg font-semibold hover:bg-gray-200 transition">
                    <i class="fa-solid fa-xmark mr-2"></i>Annuler
                </a>
            </div>

        </form>
    </div>
</div>

<script>
function updatePrivacyLabels() {
    const val = document.querySelector('input[name="story_privacy"]:checked')?.value;
    document.getElementById('label-public').className  = `flex items-center gap-4 p-3 border rounded-xl cursor-pointer transition ${val === 'public'  ? 'border-blue-400 bg-blue-50' : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'}`;
    document.getElementById('label-friends').className = `flex items-center gap-4 p-3 border rounded-xl cursor-pointer transition ${val === 'friends' ? 'border-blue-400 bg-blue-50' : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'}`;
}
</script>
@endsection