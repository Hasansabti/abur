<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\AgencyTransaction;
use App\Models\Booking;
use App\Models\BookingAccessToken;
use App\Models\Companion;
use App\Models\User;
use App\Models\WhatsappLog;
use App\Services\BookingChangeLogger;
use App\Services\DiscountCodeService;
use App\Services\InvoiceService;
use App\Services\LoyaltyService;
use App\Services\SmsService;
use App\Support\Nationalities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingOperationsController extends Controller
{
    public function __construct(
        private BookingChangeLogger $logger,
        private DiscountCodeService $discounts,
        private InvoiceService $invoices,
        private LoyaltyService $loyalty,
        private SmsService $sms,
    ) {
    }

    public function lookupPassenger(Request $request)
    {
        $nationalId = preg_replace('/\D+/', '', (string) $request->query('national_id', ''));
        if (strlen($nationalId) < 8) {
            return response()->json(['found' => false]);
        }

        $user = User::with('companions')->where('national_id', $nationalId)->first();
        $companion = Companion::where('national_id', $nationalId)->first();

        return response()->json([
            'found' => (bool) ($user || $companion),
            'user' => $user ? $this->passengerPayload($user) : null,
            'companion' => $companion ? $this->companionPayload($companion) : null,
            'companions' => $user ? $user->companions->map(fn ($c) => $this->companionPayload($c))->values() : [],
            'nationalities' => Nationalities::all(),
        ]);
    }

    public function companions(Request $request)
    {
        $userId = $request->query('user_id');
        $nationalId = preg_replace('/\D+/', '', (string) $request->query('national_id', ''));
        $user = $userId ? User::find($userId) : User::where('national_id', $nationalId)->first();
        if (!$user) {
            return response()->json(['companions' => []]);
        }

        return response()->json([
            'companions' => $user->companions()->latest()->get()->map(fn ($c) => $this->companionPayload($c))->values(),
        ]);
    }

    public function storeCompanion(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'national_id_owner' => 'nullable|string|max:20',
            'name' => 'required|string|max:255',
            'national_id' => 'required|string|max:20',
            'gender' => 'required|in:male,female',
            'birth_date' => 'nullable|date|before:today',
            'relationship' => 'nullable|string|max:100',
            'nationality_code' => 'nullable|string|max:10',
            'identity_version' => 'nullable|string|max:30',
            'identity_issue_place' => 'nullable|string|max:191',
            'identity_expiry_date' => 'nullable|date',
        ]);

        $user = null;
        if (!empty($data['user_id'])) {
            $user = User::find($data['user_id']);
        } elseif (!empty($data['national_id_owner'])) {
            $user = User::where('national_id', preg_replace('/\D+/', '', $data['national_id_owner']))->first();
        }

        if (!$user) {
            return response()->json(['message' => 'يجب اختيار عميل مسجل لربط التابع بملفه.'], 422);
        }

        $exists = $user->companions()->where('national_id', $data['national_id'])->exists();
        if ($exists) {
            return response()->json(['message' => 'هذا التابع مضاف مسبقاً لهذا العميل.'], 422);
        }

        $companion = $user->companions()->create(collect($data)->except(['user_id', 'national_id_owner'])->all());

        return response()->json(['companion' => $this->companionPayload($companion)]);
    }

    public function previewDiscount(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
            'trip_id' => 'nullable|integer',
            'customer_phone' => 'nullable|string',
        ]);

        $result = $this->discounts->preview(
            $data['code'],
            (float) $data['amount'],
            $this->agencyId(),
            $data['trip_id'] ?? null,
            $data['customer_phone'] ?? null
        );

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function issueInvoice(Request $request, Booking $booking)
    {
        $this->assertAgencyBooking($booking);

        try {
            $invoice = $this->invoices->issue($booking, $this->actorName());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'print_url' => route('agency.bookings.invoice.print', $invoice),
        ]);
    }

    public function printInvoice(Request $request, $invoice)
    {
        $invoice = \App\Models\BookingInvoice::with(['booking.trip', 'items'])->findOrFail($invoice);
        $this->assertAgencyBooking($invoice->booking);

        if ($request->boolean('pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('agency.bookings.invoice_print', ['invoice' => $invoice])->setPaper('a4');
            return $pdf->download($invoice->invoice_number . '.pdf');
        }

        return view('agency.bookings.invoice_print', compact('invoice'));
    }

    public function changeLogs(Booking $booking)
    {
        $this->assertAgencyBooking($booking);

        $logs = $booking->changeLogs()->latest()->paginate(20);

        return response()->json($logs);
    }

    public function sendSms(Request $request, Booking $booking)
    {
        $this->assertAgencyBooking($booking);
        $data = $request->validate([
            'phone' => 'required|string|max:20',
            'body' => 'required|string|max:500',
            'template_key' => 'nullable|string|max:50',
        ]);

        $recent = $booking->messageLogs()
            ->where('channel', 'sms')
            ->where('created_at', '>=', now()->subSeconds(30))
            ->exists();
        if ($recent) {
            return response()->json(['message' => 'تم إرسال رسالة مشابهة للتو. انتظر قليلاً.'], 429);
        }

        try {
            $log = $this->sms->send($booking, $data['phone'], $data['body'], $data['template_key'] ?? 'custom', $this->actorName());
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $this->logger->log($booking, 'sms_sent', [], ['phone' => $data['phone']], ['force' => true]);

        return response()->json(['status' => $log->status, 'id' => $log->id]);
    }

    public function addCredit(Request $request, Booking $booking)
    {
        $this->assertAgencyBooking($booking);
        $data = $request->validate([
            'wallet_type' => 'required|in:agency',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string|max:50',
            'reference' => 'nullable|string|max:80',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
            'idempotency_key' => 'nullable|string|max:80',
        ]);

        $reference = $data['idempotency_key'] ?: ('CR-' . $booking->id . '-' . ($data['reference'] ?: Str::uuid()));

        $tx = DB::transaction(function () use ($booking, $data, $reference) {
            $existing = AgencyTransaction::where('agency_id', $this->agencyId())
                ->where('description', 'like', '%' . $reference . '%')
                ->first();
            if ($existing) {
                return $existing;
            }

            $tx = AgencyTransaction::create([
                'agency_id' => $this->agencyId(),
                'booking_id' => $booking->id,
                'type' => 'credit',
                'amount' => $data['amount'],
                'status' => 'available',
                'description' => 'إضافة رصيد #' . $reference . ' - ' . $data['reason'] . ($data['notes'] ? ' | ' . $data['notes'] : ''),
            ]);

            $this->logger->log($booking, 'wallet_credit', [], [
                'amount' => $data['amount'],
                'method' => $data['method'],
            ], ['force' => true]);

            return $tx;
        });

        $agency = \App\Models\Agency::find($this->agencyId());

        return response()->json([
            'transaction_id' => $tx->id,
            'amount' => $tx->amount,
            'new_balance' => $agency?->available_balance,
        ]);
    }

    public function customerLink(Request $request, Booking $booking)
    {
        $this->assertAgencyBooking($booking);

        $token = $booking->accessTokens()
            ->whereNull('revoked_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();

        if (!$token) {
            $token = $booking->accessTokens()->create([
                'token' => Str::random(48),
                'expires_at' => now()->addDays((int) config('bookings.customer_link_ttl_days', 30)),
                'created_by' => $this->actorName(),
            ]);
            $this->logger->log($booking, 'customer_link_created', [], ['expires_at' => (string) $token->expires_at], ['force' => true]);
        }

        $url = url('/b/' . $token->token);

        return response()->json([
            'url' => $url,
            'expires_at' => optional($token->expires_at)?->toDateTimeString(),
        ]);
    }

    public function shareWhatsapp(Request $request, Booking $booking)
    {
        $this->assertAgencyBooking($booking);
        $data = $request->validate([
            'phone' => 'required|string|max:20',
            'mode' => 'required|in:manual,api',
            'body' => 'required|string|max:1000',
        ]);

        $phone = $this->internationalPhone($data['phone']);
        $log = $booking->messageLogs()->create([
            'agency_id' => $booking->agency_id,
            'customer_id' => $booking->user_id,
            'channel' => 'whatsapp',
            'phone' => $phone,
            'body' => $data['body'],
            'template_key' => $data['mode'],
            'status' => $data['mode'] === 'manual' ? 'manual_share' : 'queued',
            'sent_by' => $this->actorName(),
        ]);

        if ($data['mode'] === 'manual') {
            return response()->json([
                'mode' => 'manual',
                'wa_url' => 'https://wa.me/' . $phone . '?text=' . rawurlencode($data['body']),
                'charged' => false,
            ]);
        }

        $apiUrl = config('bookings.whatsapp.api_url');
        $token = config('bookings.whatsapp.api_token');
        if (!$apiUrl || !$token) {
            $log->update(['status' => 'failed', 'failure_reason' => 'مزود واتساب غير معدّ.']);
            return response()->json(['message' => 'لم يتم إعداد WhatsApp Business API.'], 422);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(20)
                ->withToken($token)
                ->post($apiUrl, ['to' => $phone, 'text' => $data['body']]);
            if (!$response->successful()) {
                $log->update(['status' => 'failed', 'failure_reason' => mb_substr($response->body(), 0, 400)]);
                return response()->json(['message' => 'فشل إرسال واتساب.'], 422);
            }
            $payload = $response->json() ?? [];
            $log->update([
                'status' => 'accepted',
                'provider_message_id' => $payload['id'] ?? null,
                'sent_at' => now(),
            ]);
            if (class_exists(WhatsappLog::class)) {
                WhatsappLog::create([
                    'agency_id' => $booking->agency_id,
                    'booking_id' => $booking->id,
                    'message_type' => 'booking_share',
                    'sent_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['mode' => 'api', 'status' => 'accepted', 'charged' => true]);
    }

    public function redeemPoints(Request $request, Booking $booking)
    {
        $this->assertAgencyBooking($booking);
        $data = $request->validate([
            'points' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:80',
        ]);

        if (!$booking->user_id) {
            return response()->json(['message' => 'لا يوجد عميل مسجل مرتبط بهذا الحجز لصرف النقاط.'], 422);
        }

        try {
            $tx = $this->loyalty->redeem(
                $booking->user,
                $booking,
                (int) $data['points'],
                $data['notes'] ?? 'صرف نقاط على الحجز',
                $this->actorName(),
                $data['reference'] ?? null
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $this->logger->log($booking, 'points_redeemed', [], [
            'points' => $data['points'],
            'value' => $tx->monetary_value,
        ], ['force' => true]);

        return response()->json([
            'balance_after' => $tx->balance_after,
            'value' => $tx->monetary_value,
            'total_amount' => $booking->fresh()->total_amount,
        ]);
    }

    public function mealsReport(Request $request)
    {
        $agencyId = $this->agencyId();
        $query = \App\Models\BookingSeat::query()
            ->with(['booking.trip'])
            ->whereHas('booking.trip', fn ($q) => $q->where('agency_id', $agencyId))
            ->whereHas('booking', fn ($q) => $q->whereNotIn('status', ['cancelled', 'requestC']))
            ->where(function ($q) {
                $q->where('is_extra', false)->orWhereNull('is_extra');
            })
            ->where(function ($q) {
                $q->whereNull('room_type')->orWhere('room_type', '!=', 'additional');
            })
            ->where(function ($q) {
                $q->where('with_breakfast', true)
                    ->orWhere('with_lunch', true)
                    ->orWhere('with_dinner', true);
            });

        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }
        if ($request->filled('booking_id')) {
            $query->where('booking_id', $request->booking_id);
        }
        if ($request->filled('passenger')) {
            $query->where('passenger_name', 'like', '%' . $request->passenger . '%');
        }
        if ($request->filled('hotel')) {
            $query->whereHas('booking.trip', fn ($q) => $q->where('hotel_name', 'like', '%' . $request->hotel . '%'));
        }
        if ($request->filled('meal')) {
            $meal = $request->meal;
            if (in_array($meal, ['breakfast', 'lunch', 'dinner'], true)) {
                $query->where('with_' . $meal, true);
            }
        }

        $rows = $query->orderByDesc('id')->get();
        $summary = [
            'people' => $rows->count(),
            'breakfast' => $rows->where('with_breakfast', true)->count(),
            'lunch' => $rows->where('with_lunch', true)->count(),
            'dinner' => $rows->where('with_dinner', true)->count(),
            'packages' => $rows->groupBy(fn ($s) => $s->meal_options['package_name'] ?? 'بدون باقة')->map->count(),
            'value' => $rows->sum(fn ($s) => (float) ($s->meal_options['package_price'] ?? 0) + (float) $s->breakfast_price + (float) $s->lunch_price + (float) $s->dinner_price),
        ];

        $trips = \App\Models\Trip::where('agency_id', $agencyId)->latest()->get(['id', 'title']);

        if ($request->get('export') === 'csv') {
            $filename = 'meals-report-' . now()->format('YmdHis') . '.csv';
            $headers = ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => "attachment; filename=\"$filename\""];
            $callback = function () use ($rows) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($out, ['الحجز', 'الراكب', 'الرحلة', 'الباقة', 'إفطار', 'غداء', 'عشاء', 'السعر']);
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->booking_id,
                        $row->passenger_name,
                        $row->booking?->trip?->title,
                        $row->meal_options['package_name'] ?? '',
                        $row->with_breakfast ? 'نعم' : 'لا',
                        $row->with_lunch ? 'نعم' : 'لا',
                        $row->with_dinner ? 'نعم' : 'لا',
                        $row->meal_options['package_price'] ?? 0,
                    ]);
                }
                fclose($out);
            };
            return response()->stream($callback, 200, $headers);
        }

        return view('agency.reports.meals', compact('rows', 'summary', 'trips'));
    }

    public function roomsExport(Request $request)
    {
        return redirect()->back()->with('info', 'استخدم زر الطباعة في صفحة التسكين أو تقرير الوجبات للتصدير.');
    }

    private function passengerPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'national_id' => $user->national_id,
            'gender' => $user->gender,
            'birth_date' => optional($user->birth_date)?->format('Y-m-d'),
            'nationality_code' => $user->nationality_code,
            'phone' => $user->phone,
            'loyalty_points' => (int) $user->loyalty_points,
        ];
    }

    private function companionPayload(Companion $companion): array
    {
        return [
            'id' => $companion->id,
            'user_id' => $companion->user_id,
            'name' => $companion->name,
            'national_id' => $companion->national_id,
            'gender' => $companion->gender,
            'birth_date' => optional($companion->birth_date)?->format('Y-m-d') ?: optional($companion->dob_gregorian)?->format('Y-m-d'),
            'relationship' => $companion->relationship,
            'nationality_code' => $companion->nationality_code,
            'identity_version' => $companion->identity_version,
            'identity_issue_place' => $companion->identity_issue_place,
            'identity_expiry_date' => optional($companion->identity_expiry_date)?->format('Y-m-d'),
        ];
    }

    private function assertAgencyBooking(Booking $booking): void
    {
        $agencyId = $this->agencyId();
        $tripAgency = $booking->agency_id ?? $booking->trip?->agency_id;
        abort_unless($agencyId && (int) $tripAgency === (int) $agencyId, 403, 'غير مصرح بالوصول لهذا الحجز.');
    }

    private function agencyId(): ?int
    {
        if (auth('agency')->check()) {
            return auth('agency')->id();
        }
        if (auth('employee')->check()) {
            return auth('employee')->user()->agency_id;
        }
        return session('agency_id');
    }

    private function actorName(): string
    {
        if (auth('employee')->check()) {
            return auth('employee')->user()->name;
        }
        if (auth('agency')->check()) {
            return auth('agency')->user()->name;
        }
        return 'وكالة';
    }

    private function internationalPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '966' . substr($digits, 1);
        }
        return $digits;
    }
}
