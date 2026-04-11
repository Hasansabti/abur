@php
    $booked_seats_details = $booked_seats_details ?? [];
    $isBooked = in_array($i, $booked_seats);
    $gender = $booked_seats_details[$i] ?? null;

    // تحديد كلاس اللون بناءً على الحالة والجنس
    $colorClass = match (true) {
        $isBooked && $gender === 'female' => 'bg-pink-100 border-pink-300 text-pink-600 cursor-not-allowed',
        $isBooked && $gender === 'male' => 'bg-blue-100 border-blue-300 text-blue-600 cursor-not-allowed',
        $isBooked => 'bg-slate-100 border-slate-200 text-slate-400 cursor-not-allowed',
        default => '',
    };
@endphp

<button @if (!$isBooked) @click="toggleSeat({{ $i }})" @endif
    :disabled="@json($isBooked)"
    class="w-10 h-10 md:w-12 md:h-12 rounded-xl text-[9px] font-black transition-all flex flex-col items-center justify-center border-2 relative {{ $colorClass }}"
    :class="{
        @if (!$isBooked) 'bg-white border-slate-100 text-slate-400 hover:border-blue-200': !isSelected({{ $i }}),
            'bg-blue-600 border-blue-700 text-white shadow-lg shadow-blue-200 scale-110 z-10': isSelected({{ $i }}) @endif
    }">
    @if ($isBooked)
        @if ($gender === 'female')
            👩
        @elseif($gender === 'male')
            👨
        @else
            ❓
        @endif
    @else
        <span class="text-[7px] opacity-60">{{ $label }}</span>
    @endif

    <span class="{{ $isBooked ? 'mt-3' : '' }}">{{ $i }}</span>
</button>
