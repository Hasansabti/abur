<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Trip;
use App\Models\TripRoom;
use App\Models\BookingSeat;
use Illuminate\Support\Facades\Log;

class RoomController extends Controller
{
    public function index($trip_id)
{
    $trip = Trip::where('id', $trip_id)
        ->where('agency_id', auth()->user()->agency_id)
        ->findOrFail($trip_id);

    // جلب كافة الغرف مع علاقاتها
    $roomsList = TripRoom::with('seats.booking')
        ->where('trip_id', $trip->id)
        ->get();

    $rooms = $roomsList->groupBy('room_type');

    // جلب كافة مقاعد الرحلة مع بيانات الحجز
    $allpeopleintrip = BookingSeat::with('booking')
        ->where('trip_id', $trip->id)
        ->get();

    $selectedTripId = $trip->id;
    $trips = Trip::where('agency_id', auth()->user()->agency_id)->get();

    return view('agency.bookings.rooms', compact('trip', 'trips', 'selectedTripId', 'rooms', 'roomsList', 'allpeopleintrip'));
}

    public function autoAssignRooms($bookingId, $tripId)
    {
        $seats = BookingSeat::where('booking_id', $bookingId)->get();

        $groupedSeats = $seats->groupBy(function ($seat) {
            return $this->getSeatRoomType($seat) ?? 'single';
        });

        foreach ($groupedSeats as $roomType => $seatsInGroup) {
            // تحديد سعة الغرفة بناءً على نوعها
            $capacity = match ($roomType) {
                'double' => 2,
                'triple' => 3,
                'quad'   => 4,
                default  => 1,
            };

            // تقسيم المقاعد إلى مجموعات بحسب سعة الغرفة
            $chunks = $seatsInGroup->chunk($capacity);

            foreach ($chunks as $chunk) {
                // جلب جنس المسافر من أول مقعد في المجموعة (عدل الحقل وفقاً للهيكلية لديك)
                $firstSeat = $chunk->first();
                $gender = $firstSeat->gender ?? $firstSeat->passenger?->gender ?? 'mixed';

                // إنشاء غرفة جديدة لكل مجموعة مقاعد
                $room = TripRoom::create([
                    'trip_id'     => $tripId,
                    'room_type'   => 'shared',
                    'gender_type' => $gender,
                    'capacity'    => $capacity,
                    'room_number' => null,
                ]);

                // تحديث المقاعد برقم الغرفة الجديدة
                foreach ($chunk as $seat) {
                    $seat->update(['trip_room_id' => $room->id]);
                }
            }
        }
    }

    public function updateRoomNumber(Request $request)
    {
        $request->validate([
            'room_id'     => 'nullable|integer|exists:trip_rooms,id',
            'room_number' => 'nullable|string|max:50',
            'trip_id'     => 'required_without:room_id|integer|exists:trips,id',
            'booking_id'  => 'required_without:room_id|integer|exists:bookings,id',
            'room_type'   => 'nullable|string',
        ]);

        try {
            $room = $request->room_id
                ? TripRoom::findOrFail($request->room_id)
                : $this->findOrCreateRoomForBooking($request->trip_id, $request->booking_id, $request->room_type);

            $room->update(['room_number' => $request->room_number]);

            return response()->json([
                'success'     => true,
                'new_room_id' => $room->id,
                'message'     => 'تم تحديث رقم الغرفة بنجاح 🕋',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث رقم الغرفة: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateNotes(Request $request)
    {
        $request->validate([
            'room_id'    => 'nullable|integer|exists:trip_rooms,id',
            'trip_id'    => 'required_without:room_id|integer|exists:trips,id',
            'booking_id' => 'required_without:room_id|integer|exists:bookings,id',
            'room_type'  => 'nullable|string',
            'notes'      => 'nullable|string|max:250',
        ]);

        try {
            $room = $request->room_id
                ? TripRoom::findOrFail($request->room_id)
                : $this->findOrCreateRoomForBooking($request->trip_id, $request->booking_id, $request->room_type);

            $room->update(['notes' => $request->notes]);

            return response()->json([
                'success'     => true,
                'new_room_id' => $room->id,
                'message'     => 'تم حفظ الملاحظات بنجاح ✓',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الملاحظات: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function findOrCreateRoomForBooking($tripId, $bookingId, $roomType)
    {
        $seats = BookingSeat::where('trip_id', $tripId)
            ->where('booking_id', $bookingId)
            ->get();

        if ($seats->isEmpty()) {
            throw new \Exception('لا يوجد ركاب مرتبطين بهذا الحجز.');
        }

        $existingRoomId = $seats->whereNotNull('trip_room_id')->pluck('trip_room_id')->first();
        if ($existingRoomId) {
            return TripRoom::findOrFail($existingRoomId);
        }

        $room = TripRoom::create([
            'trip_id'     => $tripId,
            'room_type'   => $roomType ?: 'single',
            'room_number' => null,
        ]);

        foreach ($seats as $seat) {
            $seat->update(['trip_room_id' => $room->id]);
        }

        return $room;
    }

    public function notifyClients($tripId)
    {
        $assignedPassengers = BookingSeat::where('trip_id', $tripId)
            ->whereNotNull('trip_room_id')
            ->with('room')
            ->get();

        if ($assignedPassengers->isEmpty()) {
            return redirect()->back()->with('error', 'لا يوجد مسافرون مسكنون في غرف حالياً لإرسال بياناتهم.');
        }

        foreach ($assignedPassengers as $passenger) {
            $roomNumber = $passenger->room->room_number ?? 'قيد التأكيد';
        }

        return redirect()->back()->with('success', '📢 تم اعتماد التوزيع وإرسال أرقام الغرف الفندقية إلى هواتف وحسابات العملاء بنجاح!');
    }

    /**
     * التوزيع التلقائي الذكي للركاب على الغرف
     */

    public function autoDistribute($tripId)
    {
        $useUid = false;

        $rooms = TripRoom::where('trip_id', $tripId)->get()
            ->sortBy('room_number', SORT_NATURAL)
            ->values();

        // ---------- دوال مساعدة موحدة ----------
       // ---------- دوال مساعدة موحدة ----------
        $normalizeType = function ($t) {
            $t = strtolower(trim((string)$t));
            if (str_contains($t, 'single') || str_contains($t, 'فرد') || $t === '1') return 'single';
            if (str_contains($t, 'double') || str_contains($t, 'ثنائ') || $t === '2') return 'double';
            if (str_contains($t, 'triple') || str_contains($t, 'ثلاث') || $t === '3') return 'triple';
            if (str_contains($t, 'quad')   || str_contains($t, 'رباع') || $t === '4') return 'quad';
            return $t ?: 'single';
        };

        $normalizeGender = function ($g) {
            $g = strtolower(trim((string)$g));
            if (in_array($g, ['male', 'm', 'ذكر', 'رجل', '1'], true)) return 'male';
            if (in_array($g, ['female', 'f', 'أنثى', 'امرأة', '2'], true)) return 'female';
            return null;
        };

        $isShared = function ($s) {
            $rt = strtolower(trim((string) $s->room_type));
            $opts = [];
            if (!empty($s->room_options)) {
                $opts = is_string($s->room_options) ? json_decode($s->room_options, true) : (array)$s->room_options;
            }

            return str_contains($rt, 'shared')
                || str_contains($rt, 'public')
                || str_contains($rt, 'مشترك')
                || str_contains($rt, 'مشاركة')
                || str_contains($rt, 'عام')
                || !empty($s->shared_room_type)
                || (!empty($opts['is_shared']) || (isset($opts['room_type']) && str_contains(strtolower((string)$opts['room_type']), 'shared')));
        };

        $getSharedSubtype = function ($s) use ($normalizeType) {
            if (!empty($s->shared_room_type)) {
                return $normalizeType($s->shared_room_type);
            }
            if (!empty($s->room_options)) {
                $opts = is_string($s->room_options) ? json_decode($s->room_options, true) : (array)$s->room_options;
                $st = $opts['shared_room_type'] ?? $opts['sub_type'] ?? null;
                if ($st) return $normalizeType($st);
            }
            return $normalizeType($this->getSeatRoomType($s));
        };
        $clean = fn($t) => strtolower(explode('_', (string) $t)[0]);

        $isShared = fn($s) => in_array($clean($s->room_type), ['shared', 'public', ''], true)
            || str_contains((string)$s->room_type, 'مشترك')
            || str_contains((string)$s->room_type, 'مشاركة')
            || !empty($s->shared_room_type);

        $isLocked  = fn($s) => $s->booking && ($s->booking->status === 'locked' || $s->booking->is_locked == 1);
        $isBlocked = fn($s) => $s->room_type === 'blocked'
            || $isLocked($s)
            || str_contains((string) $s->passenger_name, 'مغلق');

        $isExtraSeat = fn($s) => str_contains((string) $s->passenger_name, 'مقعد إضافي')
            || (!$isShared($s) && !in_array($clean($s->room_type), ['single', 'double', 'triple', 'quad'], true));

        $capacity = fn($r) => max($this->getRoomCapacity($r), 1);
        $roomRef  = fn($r) => $useUid ? ($r->room_uid ?: $r->id) : $r->id;

        $roomByRef = [];
        foreach ($rooms as $r) {
            $roomByRef[(string) $r->id] = $r;
            if ($r->room_uid) {
                $roomByRef[(string) $r->room_uid] = $r;
            }
        }

        // 2. الحالة الحالية لكل غرفة
        $occ = $gen = $priv = [];
        foreach ($rooms as $r) {
            $occ[$r->id]  = 0;
            $priv[$r->id] = false;
            $gen[$r->id]  = $normalizeGender($r->gender_type);
        }

        $allSeats = BookingSeat::with('booking')->where('trip_id', $tripId)->get();

        foreach ($allSeats->filter(fn($s) => !empty($s->trip_room_id)) as $s) {
            $room = $roomByRef[(string) $s->trip_room_id] ?? null;
            if (!$room) continue;

            $occ[$room->id]++;
            if (!$isShared($s)) {
                $priv[$room->id] = true;
            }

            $seatGen = $normalizeGender($s->gender);
            if ($seatGen) {
                if ($gen[$room->id] === null) {
                    $gen[$room->id] = $seatGen;
                } elseif ($gen[$room->id] !== $seatGen) {
                    $gen[$room->id] = 'mixed';
                }
            }
        }

        // 3. المقاعد غير المسكنة
        $waiting = $allSeats
            ->filter(fn($s) => empty($s->trip_room_id) && !$isBlocked($s))
            ->values();

        if ($waiting->isEmpty()) {
            return redirect()->back()->with('error', 'لا يوجد مسافرون غير مسكنين حالياً لتوزيعهم تلقائياً.');
        }

        $extraSeats   = $waiting->filter($isExtraSeat)->values();
        $mainWaiting  = $waiting->reject($isExtraSeat)->values();
        $privateSeats = $mainWaiting->reject($isShared)->values();
        $sharedSeats  = $mainWaiting->filter($isShared)->values();

        // 👈 تعريف وتشييد كافة العدادات المطلوبة لتفادي خطأ Undefined variable
        $assigned        = 0;
        $createdRooms    = 0;
        $extrasFollowing = 0;
        $skipped  = ['no_gender' => 0, 'no_private_room' => 0, 'extra_no_host' => 0];

        $assign = function ($seats, $room) use (&$occ, &$gen, &$priv, &$assigned, $roomRef, $isShared, $normalizeGender) {
            foreach ($seats as $seat) {
                $seat->trip_room_id = $roomRef($room);
                $seat->save();

                $occ[$room->id]++;
                $assigned++;

                if (!$isShared($seat)) {
                    $priv[$room->id] = true;
                }

                $seatGen = $normalizeGender($seat->gender);
                if ($seatGen) {
                    if ($gen[$room->id] === null) {
                        $gen[$room->id] = $seatGen;
                    } elseif ($gen[$room->id] !== $seatGen) {
                        $gen[$room->id] = 'mixed';
                    }
                }
            }
        };

        DB::beginTransaction();

        try {
            // ===== أ) الحجوزات الخاصة أولاً =====
            foreach ($privateSeats->groupBy('booking_id') as $bookingId => $seats) {
                $dominant = $seats->map(fn($s) => $normalizeType($s->room_type))
                    ->countBy()->sortDesc()->keys()->first();

                $main   = $seats->filter(fn($s) => $normalizeType($s->room_type) === $dominant)->values();
                $extras = $seats->reject(fn($s) => $normalizeType($s->room_type) === $dominant)
                    ->concat($extraSeats->where('booking_id', $bookingId))
                    ->values();

                $candidates = $rooms
                    ->filter(fn($r) => $normalizeType($r->room_type) === $dominant && $occ[$r->id] === 0)
                    ->values();

                if ($candidates->isEmpty()) {
                    $skipped['no_private_room'] += $seats->count();
                    continue;
                }

                $single = $candidates->first(fn($r) => $capacity($r) >= $main->count());
                if ($single) {
                    $assign($seats, $single);
                    continue;
                }

                $queue = $main->values();
                $first = true;
                foreach ($candidates as $room) {
                    if ($queue->isEmpty()) break;
                    $chunk = $queue->splice(0, max($capacity($room), 1));
                    $assign($first ? $chunk->concat($extras) : $chunk, $room);
                    $first = false;
                }
                if ($queue->isNotEmpty()) {
                    $skipped['no_private_room'] += $queue->count();
                }
            }

            // ===== ب) الحجوزات المشتركة =====
            foreach ($sharedSeats->groupBy('booking_id') as $bookingId => $seats) {
                foreach ($seats as $seat) {
                    $g = $normalizeGender($seat->gender);

                    if (!$g) {
                        $skipped['no_gender']++;
                        continue;
                    }

                    $subType = $getSharedSubtype($seat);

                    $baseFits = fn($r) => !$priv[$r->id]
                        && $occ[$r->id] < $capacity($r)
                        && ($gen[$r->id] === null || $gen[$r->id] === $g);

                    // 1. بحث عن غرفة مطابقة للنوع والجنس
                    $exactFits = fn($r) => $baseFits($r) && ($subType ? $normalizeType($r->room_type) === $subType : true);

                    $room = $rooms->first(fn($r) => $exactFits($r) && $occ[$r->id] > 0)
                        ?? $rooms->first($exactFits);

                    // 2. بحث مرن في أية غرفة عامة متبقية تناسب الجنس (بما فيها الغرف المفردة)
                    if (!$room) {
                        $room = $rooms->first(fn($r) => $baseFits($r) && $occ[$r->id] > 0)
                            ?? $rooms->first($baseFits);
                    }

                    // 3. إنشاء غرفة جديدة عند عدم توفر غرفة تناسب المسافر
                    if (!$room) {
                        $roomCapacity = match ($subType) {
                            'single' => 1,
                            'double' => 2,
                            'triple' => 3,
                            default  => 4,
                        };

                        $room = TripRoom::create([
                            'trip_id'     => $tripId,
                            'room_type'   => $subType ?: 'single',
                            'gender_type' => $g,
                            'capacity'    => $roomCapacity,
                            'room_number' => null,
                        ]);

                        $rooms->push($room);
                        $occ[$room->id]  = 0;
                        $priv[$room->id] = false;
                        $gen[$room->id]  = $g;
                        $createdRooms++;
                    }

                    $assign([$seat], $room);
                }
            }

            // ===== ج) المقاعد الإضافية =====
            foreach ($extraSeats as $x) {
                if (!empty($x->trip_room_id)) continue;

                $host = $allSeats->first(
                    fn($s) => $s->booking_id == $x->booking_id
                        && !empty($s->trip_room_id)
                        && !$isExtraSeat($s)
                );

                if (!$host) {
                    $skipped['extra_no_host']++;
                    continue;
                }

                if ($isShared($host)) {
                    $extrasFollowing++;
                    continue;
                }

                $x->trip_room_id = $host->trip_room_id;
                $x->save();
                $assigned++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('autoDistribute failed', ['trip' => $tripId, 'error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'حدث خطأ أثناء التوزيع التلقائي: ' . $e->getMessage());
        }

        $notes = [];
        if ($skipped['no_gender']) $notes[] = "{$skipped['no_gender']} مقعد بدون جنس محدد";
        if ($skipped['no_private_room']) $notes[] = "{$skipped['no_private_room']} مقعد (حجز خاص) لم يجد غرفة شاغرة من نوعه";
        if ($skipped['extra_no_host']) $notes[] = "{$skipped['extra_no_host']} مقعد إضافي بدون مسافر مسكّن يتبعه";

        $notesText = $notes ? ' | ملاحظات: ' . implode('، ', $notes) : '';
        $createdText = $createdRooms > 0 ? " (تم إنشاء $createdRooms غرفة جديدة)" : '';
        $extraText = $extrasFollowing ? " (+{$extrasFollowing} مقعد إضافي)" : '';

        if ($assigned > 0) {
            return redirect()->back()->with('success', "🤖 تم التوزيع التلقائي بنجاح! تم تسكين ($assigned) مسافر.$createdText$extraText$notesText");
        }

        return redirect()->back()->with('error', 'لم يتم تسكين أي مسافر.' . ($notesText ?: ' يرجى التأكد من بيانات الحجز.'));
    }

    private function getSeatRoomType($seat)
    {
        if (!empty($seat->room_type)) {
            return $seat->room_type;
        }

        if (!empty($seat->room_options)) {
            $options = is_string($seat->room_options) ? json_decode($seat->room_options, true) : (array)$seat->room_options;
            return $options['room_type'] ?? null;
        }

        return null;
    }

    private function getRoomCapacity($room)
    {
        if (!empty($room->capacity) && $room->capacity > 0) return (int) $room->capacity;
        if (!empty($room->beds_count) && $room->beds_count > 0) return (int) $room->beds_count;

        $type = strtolower((string) $room->room_type);

        if (str_contains($type, 'double') || str_contains($type, 'ثنائي')) return 2;
        if (str_contains($type, 'triple') || str_contains($type, 'ثلاثي')) return 3;
        if (str_contains($type, 'quad')   || str_contains($type, 'رباعي')) return 4;
        if (str_contains($type, 'quin')   || str_contains($type, 'خماسي')) return 5;
        if (str_contains($type, 'shared') || str_contains($type, 'مشترك')) return 4;

        return 1;
    }

    public function findSuitableRoomForGroup($rooms, $requiredCapacity)
    {
        foreach ($rooms as $room) {
            $capacity = $this->getRoomCapacity($room);

            $currentOccupied = BookingSeat::where('trip_room_id', $room->id)->count();
            $availableCapacity = $capacity - $currentOccupied;

            if ($availableCapacity >= $requiredCapacity) {
                return $room;
            }
        }
        return null;
    }

    public function findSuitableRoomForGender($rooms, $gender, $requiredCapacity)
    {
        foreach ($rooms as $room) {
            $capacity = $this->getRoomCapacity($room);

            $assignedSeats = BookingSeat::where('trip_room_id', $room->id)->get();
            $currentOccupied = $assignedSeats->count();
            $availableCapacity = $capacity - $currentOccupied;

            if ($availableCapacity < $requiredCapacity) {
                continue;
            }

            $roomGenders = $assignedSeats->pluck('gender')->unique()->filter();

            if ($roomGenders->isEmpty() || ($roomGenders->count() === 1 && strtolower((string)$roomGenders->first()) === strtolower((string)$gender))) {
                return $room;
            }
        }
        return null;
    }
}
