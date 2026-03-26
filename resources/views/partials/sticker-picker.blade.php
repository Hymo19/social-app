@php
$emojis = [
    '😀','😂','😍','🥰','😎','😭','😡','🤔','😴','🥳',
    '😊','😅','🤩','😢','😤','🙄','😏','🤗','😬','🫡',
    '💀','👻','🤖','🎭','🦁','🐶','🐱','🦊','🐸','🐧',
    '👍','👎','❤️','🔥','💯','🎉','🙏','👏','💪','🤣',
    '🍕','🍔','🎮','⚽','🏆','🎵','🌈','⭐','💎','🚀',
    '😇','🤑','🤠','🥸','🤡','👾','🫶','🫂','🤝','✌️',
];
@endphp

<div class="bg-white border border-gray-200 rounded-2xl shadow-xl p-3 w-72 z-50">
    <p class="text-xs font-semibold text-gray-400 mb-2 uppercase tracking-wide">
        <i class="fa-regular fa-face-smile mr-1"></i>Emojis & Stickers
    </p>
    <div class="grid grid-cols-10 gap-0.5 max-h-40 overflow-y-auto">
        @foreach($emojis as $emoji)
            <button type="button"
                    onclick="insertSticker('{{ $inputId }}', '{{ $emoji }}', '{{ $pickerId }}')"
                    class="text-xl hover:bg-gray-100 rounded-lg p-1 transition cursor-pointer leading-none">
                {{ $emoji }}
            </button>
        @endforeach
    </div>
</div>