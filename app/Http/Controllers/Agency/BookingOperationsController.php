<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Concerns\ResolvesAgencyContext;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Booking;
use App\Models\BookingChangeLog;
use App\Models\BookingCustomService;
use App\Models\Companion;
use App\Models\DiscountCode;
use App\Models\Nationality;
use App\Models\User;
use App\Services\AccessTokenService;
use App\Services\BookingChangeLogger;
use App\Services\DiscountCodeService;
use App\Services\InvoiceService;
use App\Services\PointsService;
use App\Services\SmsService;
use App\Services\WalletCreditService;
use App\Services\WhatsAppShareService;
use App\Support\Money;
use App\Support\PassengerSeat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingOperationsController extends Controller
{
    use ResolvesAgencyContext;

    public function lookupPassenger(Request $request)
    {
        $this->denyUnless('booking_passengers');
        $nationalId = preg_replace('/\D+/', '', (string) $request->query('national_id', ''));
        $phone = preg_replace('/\D+/', '', (string) $request->query('phone', ''));

        $user = null;
        if ($nationalId) {
            $user = User::with(['companions.nationality', 'nationality'])->where('national_id', $nationalId)->first();
        }
        if (!$user && $phone) {
            $user = User::with(['companions.nationality', 'nationality'])
                ->where('phone', 'like', '%' . substr($phone, -9) . '%')
                ->first();
        }

        return response()->json([
            'found' => (bool) $user,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'national_id' => $user->national_id,
                'phone' => $user->phone,
                'gender' => $user->gender,
                'birth_date' => optional($user->birth_date)?->format('Y-m-d') ?? $user->birth_date,
                'nationality_id' => $user->nationality_id,
                'points_balance' => (int) ($user->points_balance ?? 0),
            ] : null,
            'companions' => $user ? $user->companions->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'national_id' => $c->national_id,
                'gender' => $c->gender,
                'birth_date' => optional($c->birth_date)?->format('Y-m-d'),
                'relationship' => $c->relationship,
                'nationality_id' => $c->nationality_id,
                'identity_version' => $c->identity_version,
                'identity_issue_place' => $c->identity_issue_place,
                'identity_expiry_date' => optional($c->identity_expiry_date)?->format('Y-m-d'),
            ]) : [],
        ]);
    }

    public function storeCompanion(Request $request)
    {
        $this->denyUnless('booking_companions');
        $data = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'parent_national_id' => 'nullable|string',
            'name' => 'required|string|max:255',
            'national_id' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female',
            'birth_date' => 'nullable|date|before:today',
            'relationship' => 'nullable|string|max:100',
            'nationality_id' => 'nullable|exists:nationalities,id',
            'identity_version' => 'nullable|string|max:50',
            'identity_issue_place' => 'nullable|string|max:150',
            'identity_expiry_date' => 'nullable|date',
        ]);

        $userId = $data['user_id'] ?? null;
        if (!$userId && !empty($data['parent_national_id'])) {
            $userId = User::where('national_id', $data['parent_national_id'])->value('id');
        }
        if (!$userId) {
            return $this->jsonFail('لا يمكن إنشاء تابع إلا بعد تحديد عميل مسجّل في النظام.', 422);
        }

        $companion = Companion::create(array_merge($data, ['user_id' => $userId]));

        return $this->jsonOk('تم حفظ التابع.', ['companion' => $companion]);
    }

    public function nationalities()
    {
        return response()->json(
            Nationality::where('is_active', true)->orderBy('name_ar')->get(['id', 'iso2', 'name_ar', 'name_en'])
        );
    }

    public function previewDiscount(Request $request, DiscountCodeService $service)
    {
        $this->denyUnless('booking_discounts');
        $data = $request->validate([
            'code' => 'required|string|max:50',
            'trip_id' => 'nullable|integer',
            'amount' => 'required|numeric|min:0',
            'customer_key' => 'nullable|string',
        ]);

        try {
            $result = $service->preview(
                $data['code'],
                (int) $this->getAgencyId(),
                (float) $data['amount'],
                $data['trip_id'] ?? null,
                $data['customer_key'] ?? null
            );

            return response()->json(['success' => true] + $result);
        } catch (ValidationException $e) {
            return $this->jsonFail(collect($e->errors())->flatten()->first() ?: 'كود غير صالح', 422);
        }
    }

    public function recalculate(Request $request)
    {
        $this->denyUnless('bookings');
        $data = $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'room_quantities' => 'nullable',
            'selected_seats' => 'nullable',
            'room_assignments' => 'nullable',
            'passenger_meals' => 'nullable',
            'passenger_services' => 'nullable',
            'custom_services' => 'nullable|array',
            'bed_assignments' => 'nullable|array',
            'discount_code' => 'nullable|string',
            'paid_amount' => 'nullable|numeric|min:0',
        ]);

        $trip = \App\Models\Trip::where('id', $data['trip_id'])->where('agency_id', $this->getAgencyId())->firstOrFail();
        $eligible = 0;
        $options = [
            'custom_services' => $data['custom_services'] ?? [],
            'bed_assignments' => $data['bed_assignments'] ?? [],
            'extra_bed_price' => $trip->extra_bed_price,
            'paid_amount' => $data['paid_amount'] ?? 0,
        ];

        $previewTotals = \App\Services\BookingCalculator::calculate(
            $trip,
            $data['room_quantities'] ?? [],
            $data['selected_seats'] ?? [],
            $data['room_assignments'] ?? [],
            $data['passenger_meals'] ?? [],
            $data['passenger_services'] ?? [],
            $options
        );

        if (!empty($data['discount_code'])) {
            try {
                $preview = app(DiscountCodeService::class)->preview(
                    $data['discount_code'],
                    (int) $this->getAgencyId(),
                    (float) $previewTotals['subtotal_before_discount'],
                    $trip->id,
                    $request->input('customer_key')
                );
                $options['discount_code_amount'] = $preview['amount'];
                $previewTotals = \App\Services\BookingCalculator::calculate(
                    $trip,
                    $data['room_quantities'] ?? [],
                    $data['selected_seats'] ?? [],
                    $data['room_assignments'] ?? [],
                    $data['passenger_meals'] ?? [],
                    $data['passenger_services'] ?? [],
                    $options
                );
                $previewTotals['discount_code'] = $preview;
            } catch (ValidationException $e) {
                $previewTotals['discount_code_error'] = collect($e->errors())->flatten()->first();
            }
        }

        return response()->json(['success' => true, 'totals' => $previewTotals]);
    }

    public function issueInvoice(Request $request, Booking $booking, InvoiceService $invoices)
    {
        $this->denyUnless('issue_invoices');
        $booking = $this->findAgencyBooking($booking->id);

        if (!config('booking.allow_invoice_before_full_payment', true)) {
            $summary = $booking->paymentSummary();
            if ($summary['remaining'] > 0.004) {
                return $this->jsonFail('إصدار الفاتورة غير مسموح قبل اكتمال الدفع حسب سياسة النظام.');
            }
        }

        $invoice = $invoices->issue($booking, 'invoice-booking-' . $booking->id);

        return $this->jsonOk('تم إصدار الفاتورة.', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'url' => route('agency.bookings.invoice.show', $booking),
            'already_existed' => $invoice->wasRecentlyCreated === false,
        ]);
    }

    public function showInvoice(Booking $booking, InvoiceService $invoices)
    {
        $this->denyUnless('print_invoices');
        $booking = $this->findAgencyBooking($booking->id);
        $invoice = $invoices->findForBooking($booking);
        if (!$invoice) {
            $invoice = $invoices->issue($booking, 'invoice-booking-' . $booking->id);
        }

        if ($booking->wantsJson ?? request()->wantsJson()) {
            return response()->json(['success' => true, 'invoice' => $invoice]);
        }

        return view('agency.bookings.invoice', compact('booking', 'invoice'));
    }

    public function downloadInvoice(Booking $booking, InvoiceService $invoices)
    {
        $this->denyUnless('print_invoices');
        $booking = $this->findAgencyBooking($booking->id);
        $invoice = $invoices->findForBooking($booking) ?: $invoices->issue($booking, 'invoice-booking-' . $booking->id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('agency.bookings.invoice_pdf', compact('booking', 'invoice'))
            ->setPaper('a4');

        return $pdf->download($invoice->invoice_number . '.pdf');
    }

    public function sendSms(Request $request, Booking $booking, SmsService $sms)
    {
        $this->denyUnless('send_sms');
        $booking = $this->findAgencyBooking($booking->id);
        $data = $request->validate([
            'phone' => 'required|string|max:20',
            'template' => 'required|string|max:50',
            'message' => 'nullable|string|max:1000',
            'idempotency_key' => 'nullable|string|max:64',
        ]);

        $vars = $this->messageVars($booking);
        $text = $sms->renderTemplate($data['template'], $vars, $data['message'] ?? null);
        $log = $sms->send($booking, $data['phone'], $text, $data['template'], $data['idempotency_key'] ?? ('sms-' . $booking->id . '-' . md5($text)));

        return $this->jsonOk('تم قبول الرسالة من المزود.', [
            'status' => $log->status,
            'provider_message_id' => $log->provider_message_id,
            'delivery_status' => $log->delivery_status,
            'preview' => $text,
        ]);
    }

    public function previewSms(Request $request, Booking $booking, SmsService $sms)
    {
        $this->denyUnless('send_sms');
        $booking = $this->findAgencyBooking($booking->id);
        $template = $request->input('template', 'booking_confirmation');
        $text = $sms->renderTemplate($template, $this->messageVars($booking), $request->input('message'));

        return response()->json(['success' => true, 'preview' => $text, 'phone' => $booking->customer_phone]);
    }

    public function creditWallet(Request $request, Booking $booking, WalletCreditService $wallet)
    {
        $this->denyUnless('add_balance');
        $booking = $this->findAgencyBooking($booking->id);
        $data = $request->validate([
            'wallet_type' => 'required|in:agency',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string|max:50',
            'reference' => 'nullable|string|max:100',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'idempotency_key' => 'nullable|string|max:64',
        ]);

        $agency = Agency::findOrFail($this->getAgencyId());
        $trx = $wallet->creditAgency(
            $agency,
            (float) $data['amount'],
            $data['method'],
            $data['reference'] ?? null,
            trim(($data['reason'] ?? '') . ' ' . ($data['notes'] ?? '')),
            $booking,
            $data['idempotency_key'] ?? null
        );

        $newBalance = $agency->fresh()->available_balance ?? null;

        return $this->jsonOk('تم إضافة الرصيد وتسجيل الحركة المالية.', [
            'transaction_id' => $trx->id,
            'new_balance' => $newBalance,
        ]);
    }

    public function clientLink(Booking $booking, AccessTokenService $tokens)
    {
        $this->denyUnless('client_links');
        $booking = $this->findAgencyBooking($booking->id);
        $token = $tokens->issueOrReuse($booking);

        return $this->jsonOk('تم تجهيز رابط الزبون.', [
            'url' => $tokens->url($token),
            'expires_at' => optional($token->expires_at)?->toDateTimeString(),
        ]);
    }

    public function whatsappShare(Request $request, Booking $booking, WhatsAppShareService $wa, AccessTokenService $tokens)
    {
        $this->denyUnless('send_whatsapp');
        $booking = $this->findAgencyBooking($booking->id);
        $data = $request->validate([
            'phone' => 'required|string|max:20',
            'mode' => 'required|in:manual,api',
            'message' => 'nullable|string|max:2000',
            'idempotency_key' => 'nullable|string|max:64',
        ]);

        $link = $tokens->url($tokens->issueOrReuse($booking));
        $message = $data['message'] ?: $wa->renderShareMessage($booking, $link);

        if ($data['mode'] === 'manual') {
            $share = $wa->buildManualShare($booking, $data['phone'], $message);

            return $this->jsonOk('تم تجهيز رابط المشاركة اليدوية. لن تُحتسب ضمن الحصة إلا بعد الإرسال الآلي.', $share);
        }

        $log = $wa->sendApi($booking, $data['phone'], $message, 'booking_share', $data['idempotency_key'] ?? null);

        return $this->jsonOk('تم إرسال رسالة واتساب.', [
            'status' => $log->status,
            'provider_message_id' => $log->provider_message_id,
        ]);
    }

    public function redeemPoints(Request $request, Booking $booking, PointsService $points)
    {
        $this->denyUnless('redeem_points');
        $booking = $this->findAgencyBooking($booking->id);
        $data = $request->validate([
            'points' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
            'idempotency_key' => 'nullable|string|max:64',
        ]);

        $user = $booking->user;
        if (!$user) {
            $user = User::where('national_id', $booking->customer_national_id)->first();
        }
        if (!$user) {
            return $this->jsonFail('لا يوجد ملف عميل معتمد لصرف النقاط. الرصيد التقديري غير قابل للصرف.');
        }

        $trx = $points->redeem($user, $booking, (int) $data['points'], $data['reason'] ?? null, $data['idempotency_key'] ?? ('points-' . $booking->id . '-' . $data['points']));

        return $this->jsonOk('تم صرف النقاط.', [
            'balance_after' => $trx->balance_after,
            'monetary_value' => $trx->monetary_value,
            'booking_total' => $booking->fresh()->total_amount,
        ]);
    }

    public function changeLogs(Booking $booking)
    {
        $this->denyUnless('view_change_logs');
        $booking = $this->findAgencyBooking($booking->id);
        $logs = BookingChangeLog::where('booking_id', $booking->id)
            ->where('agency_id', $this->getAgencyId())
            ->latest()
            ->paginate(20);

        $canSeeId = BookingChangeLogger::canSeeIdentity();
        $logs->getCollection()->transform(function ($log) use ($canSeeId) {
            if (!$canSeeId) {
                foreach (['old_values', 'new_values'] as $field) {
                    $vals = $log->{$field} ?? [];
                    if (isset($vals['passenger_national_id'])) {
                        $vals['passenger_national_id'] = BookingChangeLogger::mask((string) $vals['passenger_national_id']);
                        $log->{$field} = $vals;
                    }
                }
            }
            return $log;
        });

        return response()->json($logs);
    }

    public function discountCodes()
    {
        $this->denyUnless('manage_discount_codes');
        $codes = DiscountCode::where('agency_id', $this->getAgencyId())->latest()->paginate(20);

        return view('agency.bookings.discount_codes', compact('codes'));
    }

    public function storeDiscountCode(Request $request)
    {
        $this->denyUnless('manage_discount_codes');
        $data = $request->validate([
            'code' => 'required|string|max:50',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0.01',
            'max_discount' => 'nullable|numeric|min:0',
            'min_booking_amount' => 'nullable|numeric|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'nullable|boolean',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_customer' => 'nullable|integer|min:1',
        ]);
        $data['agency_id'] = $this->getAgencyId();
        $data['code'] = mb_strtoupper(trim($data['code']));
        $data['is_active'] = $request->boolean('is_active', true);
        DiscountCode::create($data);

        return back()->with('success', 'تم حفظ كود الخصم.');
    }

    public function walletContext(Booking $booking)
    {
        $this->denyUnless('add_balance');
        $booking = $this->findAgencyBooking($booking->id);
        $agency = Agency::findOrFail($this->getAgencyId());

        return response()->json([
            'success' => true,
            'holder' => $agency->name,
            'wallet_type' => 'agency',
            'current_balance' => $agency->available_balance,
            'booking_id' => $booking->id,
        ]);
    }

    public function pointsContext(Booking $booking)
    {
        $this->denyUnless('redeem_points');
        $booking = $this->findAgencyBooking($booking->id);
        $user = $booking->user ?: User::where('national_id', $booking->customer_national_id)->first();

        return response()->json([
            'success' => true,
            'customer_name' => $user?->name ?? $booking->customer_name,
            'has_wallet' => (bool) $user,
            'points_balance' => (int) ($user?->points_balance ?? 0),
            'point_value' => (float) config('booking.points_value', 0.01),
            'estimated_only' => !$user,
        ]);
    }

    protected function messageVars(Booking $booking): array
    {
        $seats = $booking->bookingSeats->filter(fn ($s) => !PassengerSeat::isExtra($s));
        $room = $seats->map(fn ($s) => $s->room?->room_number)->filter()->first();

        return [
            'name' => $booking->customer_name,
            'booking' => 'BK-' . $booking->id,
            'trip' => $booking->trip?->title ?? '',
            'date' => optional($booking->trip?->departure_at)?->format('Y-m-d H:i'),
            'seat' => $seats->map(fn ($s) => is_array($s->seat_number) ? implode(',', $s->seat_number) : $s->seat_number)->implode(', '),
            'room' => $room ?: '—',
            'location' => $booking->trip?->from_city ?? '',
            'remaining' => number_format($booking->paymentSummary()['remaining'], 2),
            'message' => request('message', ''),
        ];
    }
}
