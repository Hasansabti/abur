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
        // 1. تحديث الـ Validation لاستقبال بيانات الفاتورة والإيصال بشكل صحيح
        $data = $request->validate([
            'trip_id'        => ['required', 'exists:trips,id'],
            'user_id'        => ['nullable', 'exists:users,id'],
            'title'          => ['required', 'string'],
            'customer_name'  => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_national_id' => ['required', 'string'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'seat_numbers'   => ['required', 'array', 'min:1'],
            'seat_numbers.*' => ['integer', 'min:1'],
            'room_type'      => ['required', 'in:single,double,triple,quad'],
            'payment_method' => ['required', 'in:card,tabby,bank'],
            'card_name'      => ['nullable', 'required_if:payment_method,card', 'string', 'max:255'],
            'bank_receipt'   => ['nullable', 'required_if:payment_method,bank', 'image', 'mimes:jpeg,png,jpg', 'max:4096'], // 4 ميجا بكسل حد أقصى
            'payment_status' => ['nullable', 'in:unpaid,paid,failed,pending,refunded'],
            'status'         => ['nullable', 'in:pending,confirmed,cancelled'],
        ]);

        $booking = DB::transaction(function () use ($data, $request) {
            $trip = Trip::query()->lockForUpdate()->findOrFail($data['trip_id']);
            $seatNumbers = collect($data['seat_numbers'])->unique()->values();

            if ($seatNumbers->count() > $trip->available_seats) {
                throw ValidationException::withMessages([
                    'seat_numbers' => ['عذراً، لا توجد مقاعد كافية متاحة في هذه الرحلة حالياً.'],
                ]);
            }

            $conflictExists = BookingSeat::query()
                ->where('trip_id', $trip->id)
                ->whereIn('seat_number', $seatNumbers)
                ->exists();

            if ($conflictExists) {
                throw ValidationException::withMessages([
                    'seat_numbers' => ['واحدة أو أكثر من المقاعد المحددة محجوزة مسبقاً من عميل آخر.'],
                ]);
            }

            // 2. 💡 تحديث منطق حساب السعر الإجمالي المعتمد برمجياً للفاتورة بناءً على نوع السكن والمقاعد
            $roomPrices = [
                'single' => $trip->room_prices['single'] ?? ($trip->price_single ?? 0),
                'double' => $trip->room_prices['double'] ?? ($trip->price_double ?? 0),
                'triple' => $trip->room_prices['triple'] ?? ($trip->price_triple ?? 0),
                'quad'   => $trip->room_prices['quad']   ?? ($trip->price_quad   ?? 0),
            ];

            $unitPrice = floatval($roomPrices[$data['room_type']] ?? 0);
            $seatBasePrice = floatval($trip->price ?? 0);

            // المعادلة: سعر الغرفة + (عدد المقاعد المحجوزة × سعر تذكرة المقعد للرحلة)
            $totalAmount = $unitPrice + ($seatNumbers->count() * $seatBasePrice);

            // تحديد حالة الدفع الافتراضية المناسبة حسب نوع معالجة العملية
            $defaultPaymentStatus = ($data['payment_method'] === 'bank') ? 'unpaid' : 'paid';

            // 3. إنشاء سجل الحجز المدمج بالفاتورة
            $booking = Booking::create([
                'trip_id'        => $trip->id,
                'user_id'        => $data['user_id'] ?? auth()->id(),
                'title'          => $trip->title,
                'customer_name'  => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? null,
                'customer_national_id' =>$data['customer_national_id'],
                'customer_phone' => $data['customer_phone'],
                'room_type'      => $data['room_type'],
                'payment_method' => $data['payment_method'],
                'card_name'      => $data['card_name'] ?? null,
                'status'         => $data['status'] ?? 'pending',
                'payment_status' => $data['payment_status'] ?? $defaultPaymentStatus,
                'total_amount'   => $totalAmount,
            ]);

            // 4. 🚀 استدعاء دالة الرفع السحرية من الموديل لربط الفاتورة وحفظها في السيرفر فوراً
            if ($request->hasFile('bank_receipt') && $data['payment_method'] === 'bank') {
                $booking->uploadReceipt($request->file('bank_receipt'));
            }

            //  الحل الاحترافي باستخدام العلاقة المعرفة بالموديل مباشرة
            $seatsData = $seatNumbers->map(fn(int $seatNumber) => [
                'trip_id'     => $trip->id,
                'seat_number' => $seatNumber,
                // لارافيل سيتكفل بوضع الـ booking_id والـ timestamps تلقائياً هنا!
            ])->all();

            // الحفظ من خلال علاقة الموديل لربط البيانات في الذاكرة فوراً
            $booking->bookingSeats()->createMany($seatsData);

            // تحديث المقاعد المتبقية بالرحلة
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

        DB::transaction(function () use ($data, $booking) {
            if ($data['status'] === 'cancelled' && $booking->status !== 'cancelled') {
                $seatsCount = $booking->seats()->count();

                // إعادة المقاعد للرحلة مجدداً
                $booking->trip()->increment('available_seats', $seatsCount);

                // حذف مقاعد الحجز لإتاحتها فوراً في باص الرحلة للآخرين
                $booking->seats()->delete();
            }

            $booking->update([
                'status' => $data['status'],
                'payment_status' => $data['payment_status'] ?? $booking->payment_status,
            ]);
        });

        return response()->json($booking->fresh()->load(['trip.agency', 'seats']));
    }
}
