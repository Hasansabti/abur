<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Booking::query()->with(['trip.agency', 'seats']);

        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->integer('trip_id'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        $perPage = max(1, min($request->integer('per_page', 15), 100));
        $bookings = $query->latest()->paginate($perPage);

        return response()->json($bookings);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'trip_id' => ['required', 'exists:trips,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'seat_numbers' => ['required', 'array', 'min:1'],
            'seat_numbers.*' => ['integer', 'min:1'],
            'payment_status' => ['nullable', 'in:unpaid,paid,failed'],
            'status' => ['nullable', 'in:pending,confirmed,cancelled'],
        ]);

        $booking = DB::transaction(function () use ($data) {
            $trip = Trip::query()->lockForUpdate()->findOrFail($data['trip_id']);
            $seatNumbers = collect($data['seat_numbers'])->unique()->values();

            if ($seatNumbers->count() > $trip->available_seats) {
                throw ValidationException::withMessages([
                    'seat_numbers' => ['Not enough available seats for this trip.'],
                ]);
            }

            $conflictExists = BookingSeat::query()
                ->where('trip_id', $trip->id)
                ->whereIn('seat_number', $seatNumbers)
                ->exists();

            if ($conflictExists) {
                throw ValidationException::withMessages([
                    'seat_numbers' => ['One or more selected seats are already booked.'],
                ]);
            }

            $booking = Booking::create([
                'trip_id' => $trip->id,
                'user_id' => $data['user_id'] ?? null,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'payment_status' => $data['payment_status'] ?? 'unpaid',
                'total_amount' => $trip->price * $seatNumbers->count(),
            ]);

            $rows = $seatNumbers->map(fn (int $seatNumber) => [
                'booking_id' => $booking->id,
                'trip_id' => $trip->id,
                'seat_number' => $seatNumber,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            BookingSeat::query()->insert($rows);

            $trip->decrement('available_seats', $seatNumbers->count());

            return $booking->load(['trip.agency', 'seats']);
        });

        return response()->json($booking, 201);
    }

    public function show(Booking $booking): JsonResponse
    {
        return response()->json($booking->load(['trip.agency', 'seats', 'user']));
    }

    public function updateStatus(Request $request, Booking $booking): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,confirmed,cancelled'],
            'payment_status' => ['nullable', 'in:unpaid,paid,failed'],
        ]);

        $booking->update([
            'status' => $data['status'],
            'payment_status' => $data['payment_status'] ?? $booking->payment_status,
        ]);

        return response()->json($booking->fresh()->load(['trip.agency', 'seats']));
    }
}
