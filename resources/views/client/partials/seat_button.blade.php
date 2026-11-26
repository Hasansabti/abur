{{-- @php
    $isBooked = in_array($i, $booked_seats);
    $isStaffSeat = $isStaff ?? false;

    // منطق الألوان والجنس (من الكود الأصلي)
    $rawGender = $booked_seats_details[$i] ?? null;
    $bookedGender = $rawGender ? strtolower(trim($rawGender)) : null;

    $isFemaleZone = $isAutoSplit && $i > $halfSeats;
    $seatShape = $isFemaleZone ? 'female' : 'male';

    $btnClass = '';
    $iconClass = '';

    if ($isStaffSeat) {
        $btnClass = 'bg-gradient-to-b from-zinc-200/90 to-zinc-300/90 border-slate-300 text-slate-500 cursor-not-allowed shadow-sm';
        $iconClass = 'text-slate-400';
    } elseif ($isBooked) {
        if ($bookedGender === 'female' || ($isFemaleZone && !$bookedGender)) {
            $btnClass = 'bg-gradient-to-b from-pink-100/90 to-pink-200/90 border-pink-400 text-pink-800 cursor-not-allowed opacity-90';
            $iconClass = 'text-pink-500';
            $seatShape = 'female';
        } elseif ($bookedGender === 'male' || (!$isFemaleZone && !$bookedGender)) {
            $btnClass = 'bg-gradient-to-b from-blue-100/90 to-blue-200/90 border-blue-400 text-blue-800 cursor-not-allowed opacity-90';
            $iconClass = 'text-blue-600';
            $seatShape = 'male';
        } else {
            $btnClass = 'bg-gradient-to-b from-zinc-200/90 to-zinc-300/90 border-zinc-400 text-zinc-800 cursor-not-allowed opacity-90';
            $iconClass = 'text-zinc-500';
        }
    } else {
        $btnClass = $isFemaleZone
            ? 'bg-white/90 border-pink-200 text-pink-700 hover:bg-pink-50 hover:border-pink-400 hover:shadow-md'
            : 'bg-white/90 border-blue-200 text-blue-700 hover:bg-blue-50 hover:border-blue-400 hover:shadow-md';
        $iconClass = $isFemaleZone ? 'text-pink-300' : 'text-blue-300';
    }
@endphp

<button type="button"
    @if ($isBooked || $isStaffSeat) @click="showBookedError = true" @else @click="toggleSeat({{ $i }})" @endif
    class="relative w-full h-12 rounded-t-xl rounded-b-lg transition-all duration-200 flex flex-col items-center justify-center border-[2px] shadow-sm seat-button backdrop-blur-[1px] {{ $btnClass }}"
    @if (!$isBooked && !$isStaffSeat) :class="{
        '{{ $btnClass }}': !isSelected({{ $i }}),
        'bg-gradient-to-b from-amber-300 to-amber-500 border-amber-600 text-amber-950 shadow-lg scale-105 z-20': isSelected({{ $i }})
    }" @endif>

    <div class="absolute left-[2px] top-2 bottom-2 w-1 bg-black/10 rounded-full"></div>
    <div class="absolute right-[2px] top-2 bottom-2 w-1 bg-black/10 rounded-full"></div>
    <div class="absolute top-[2px] w-5 h-1.5 bg-black/10 rounded-full"></div>

    <span class="text-[11px] font-black z-10 mb-0.5">
        @if($isStaffSeat) كادر @else {{ $i }} @endif
    </span>

    <div class="flex flex-col items-center transform scale-90"
        :class="isSelected({{ $i }}) ? 'text-amber-100' : '{{ $iconClass }}'">
        @if($isStaffSeat)
             <div class="w-2.5 h-2.5 bg-current rounded-full z-10 shadow-sm border border-black/5 mt-0.5"></div>
        @elseif ($seatShape === 'female')
            <div class="w-3 h-3 bg-current rounded-full z-10 shadow-sm border border-black/5"></div>
            <div class="w-[18px] h-2 bg-current rounded-t-full -mt-1 shadow-sm border border-black/5"></div>
        @else
            <div class="w-2.5 h-2.5 bg-current rounded-sm z-10 shadow-sm border border-black/5"></div>
            <div class="w-4 h-2 bg-current rounded-t-md -mt-0.5 shadow-sm border border-black/5"></div>
        @endif
    </div>
</button> --}}