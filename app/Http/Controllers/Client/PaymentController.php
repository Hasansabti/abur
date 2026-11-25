<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\AuditLog;
use App\Models\AgencyTransaction;
use App\Models\BookingExtraCharge;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingTicketMail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PaymentController extends Controller
{
    public function showPaymentpage(Booking $booking)
    {
        if ($booking->payment_status === 'paid') {
            // ✅ الحجز مدفوع → نمسح المسودة المؤقتة
            return redirect()->route('client.profile')->with([
                'success' => 'هذا الحجز مدفوع بالفعل',
                'clear_booking_draft' => true,
            ]);
        }

        // التحقق مما إذا كان الحجز ملغياً بالفعل من النظام
        if ($booking->status === 'cancelled') {
            return redirect()->route('client.index')->with('error', 'عذراً، هذا الحجز ملغى. يمكنك إجراء حجز جديد.');
        }

        $expiresAt = $booking->expires_at ?? $booking->created_at->copy()->addMinutes(15);
        $remainingSeconds = max(0, (int) now()->diffInSeconds($expiresAt, false));

        return view('client.payment.pay', ['pay' => [
            'kind'         => 'booking',
            'booking_id'   => $booking->id,
            'trip_title'   => $booking->trip->title ?? null,
            'amount'       => (float) $booking->total_amount,
            'callback_url' => route('client.payment.callback', ['booking_id' => $booking->id]),
            'metadata'     => ['booking_id' => (string) $booking->id],
            'expires_in'   => $remainingSeconds,
            'abort_url'    => route('client.payment.abort', $booking->id),
        ]]);
    }
    /**
     * نقطة الدخول من الإيميل/البروفايل: تحوّل العميل لصفحة ميسر الحقيقية.
     * عامة (بدون auth)، والحماية بالتوكن العشوائي.
     */
    public function extraChargePay(string $token)
{
    $charge = BookingExtraCharge::with('booking.trip')->where('token', $token)->firstOrFail();

    if ($charge->status === 'paid') {
        return redirect()->route('client.profile')->with('success', 'تم دفع هذا المبلغ الإضافي مسبقاً.');
    }
    if ($charge->status !== 'pending') {
        return redirect()->route('client.index')->with('error', 'طلب الدفع هذا لم يعد صالحاً.');
    }

    return view('client.payment.pay', ['pay' => [
        'kind'         => 'extra',
        'booking_id'   => $charge->booking_id,
        'trip_title'   => $charge->booking->trip->title ?? null,
        'amount'       => (float) $charge->amount,
        'description'  => $charge->description,
        'callback_url' => route('client.extra_charge.return', $charge->token),
        'metadata'     => ['booking_id' => (string) $charge->booking_id, 'token' => $charge->token],
        'back_url'     => route('client.profile'),
    ]]);
}

public function extraChargeReturn(Request $request, string $token)
{
    $charge = BookingExtraCharge::with('booking')->where('token', $token)->firstOrFail();

    if ($charge->status === 'paid') {
        return redirect()->route('client.profile')->with('success', 'تم دفع هذا المبلغ الإضافي مسبقاً.');
    }
    if ($charge->status !== 'pending') {
        return redirect()->route('client.profile')->with('error', 'طلب الدفع هذا لم يعد صالحاً.');
    }

    $paymentId = $request->query('id');
    if (!$paymentId) {
        return redirect()->route('client.extra_charge.pay', $token)
            ->with('error', 'تم إلغاء عملية الدفع قبل اكتمالها، يمكنك المحاولة مجدداً.');
    }

    // نتحقق من ميسر مباشرة، ولا نثق بما يرجع في الرابط
    $response = Http::withBasicAuth(config('services.moyasar.secret_key'), '')
        ->timeout(15)
        ->get("https://api.moyasar.com/v1/payments/{$paymentId}");

    if (!$response->successful()) {
        Log::error('Moyasar payment fetch error: ' . $response->body());
        return redirect()->route('client.extra_charge.pay', $token)
            ->with('error', 'تعذر التحقق من الدفع الآن، حاول مرة أخرى.');
    }

    $payment  = $response->json();
    $expected = (int) round($charge->amount * 100);

    if (($payment['status'] ?? '') !== 'paid') {
        $msg = $payment['source']['message'] ?? 'لم تكتمل عملية الدفع.';
        return redirect()->route('client.extra_charge.pay', $token)->with('error', "فشلت عملية الدفع: {$msg}");
    }

    $valid = (int) ($payment['amount'] ?? 0) >= $expected
        && ($payment['currency'] ?? '') === 'SAR'
        && (string) ($payment['metadata']['token'] ?? '') === $token;

    if (!$valid) {
        Log::warning("Extra charge payment mismatch #{$charge->id} / {$paymentId}");
        return redirect()->route('client.extra_charge.pay', $token)
            ->with('error', 'بيانات الدفعة لا تطابق هذا الطلب.');
    }

    DB::transaction(function () use ($charge, $paymentId) {
        $locked = BookingExtraCharge::lockForUpdate()->find($charge->id);
        if ($locked->status === 'paid') return;

        $locked->update([
            'status'             => 'paid',
            'paid_at'            => now(),
            'moyasar_payment_id' => $paymentId,
        ]);

        // نفس منطق الدالة القديمة بدون تغيير
        AgencyTransaction::create([
            'agency_id'   => $locked->agency_id,
            'booking_id'  => $locked->booking_id,
            'type'        => 'extra_charge',
            'amount'      => $locked->amount,
            'status'      => 'pending',
            'description' => "دفعة إضافية للحجز #{$locked->booking_id}: {$locked->description}",
        ]);

        AuditLog::create([
            'agency_id'      => $locked->agency_id,
            'trip_id'        => $locked->booking->trip_id,
            'action_type'    => 'increase',
            'category'       => 'money',
            'amount_changed' => $locked->amount,
            'seats_changed'  => 0,
            'description'    => "دفع المسافر مبلغاً إضافياً ({$locked->amount}) ر.س للحجز #{$locked->booking_id}: {$locked->description}",
            'operator_name'  => 'النظام (دفع إلكتروني)',
        ]);
    });

    return redirect()->route('client.profile')->with('success', 'تم دفع المبلغ الإضافي بنجاح ✅');
}

    public function handleCallback(Request $request)
    {
        $paymentId = $request->query('id');
        $bookingId = $request->query('booking_id');

        //إذا ألغى المستخدم العملية أو أغلق نافذة البنك قبل الإكمال
        if (!$paymentId) {
            return redirect()->route('client.payment.pay', $bookingId ?? $request->route('booking'))
                ->with('error', 'تم إلغاء عملية الدفع قبل اكتمالها، يمكنك المحاولة مجدداً.');
        }

        // 🟢 جلب الحجز مع الوكالة والرحلة والمقاعد للحصول على عدد المسافرين واحتساب العمولة
        $booking = Booking::with(['bookingSeats', 'trip.agency', 'agency'])->find($bookingId);

        if (!$booking) {
            return redirect()->route('client.index')->with('error', 'لم يتم العثور على الحجز المطلوب.');
        }

        // 🟢 حماية من المعالجة المكررة في حال تحديث الشاشة
        if ($booking->payment_status === 'paid') {
            // ✅ تم الدفع مسبقاً → نمسح المسودة المؤقتة
            return redirect()->route('client.profile')->with([
                'success' => 'تم تأكيد ومعالجة هذا الحجز مسبقاً.',
                'clear_booking_draft' => true,
            ]);
        }

        try {
            $secretKey = config('services.moyasar.secret_key');

            // التحقق من وجود المفتاح الخاص بميسر
            if (empty($secretKey)) {
                Log::error("MOYASAR_SECRET_KEY مفقود في ملف config/services.php أو .env");
                return redirect()->route('client.payment.pay', $booking->id)
                    ->with('error', 'خطأ في إعدادات بوابة الدفع (Secret Key مفقود).');
            }

            // الاستعلام من API ميسر
            $response = Http::withBasicAuth($secretKey, '')
                ->get("https://api.moyasar.com/v1/payments/{$paymentId}");

            if (!$response->successful()) {
                $errBody = $response->json();
                $msg = $errBody['message'] ?? 'فشل الاتصال بخادم ميسر';
                Log::error("Moyasar API error response: " . $response->body());

                return redirect()->route('client.payment.pay', $booking->id)
                    ->with('error', "استجابة ميسر: {$msg}");
            }

            $paymentData = $response->json();
            $status = $paymentData['status'] ?? 'unknown';
            
            if ($status === 'paid' && (string) ($paymentData['metadata']['booking_id'] ?? '') !== (string) $booking->id) {
    Log::warning("Payment {$paymentId} does not belong to booking #{$booking->id}");
    return redirect()->route('client.payment.pay', $booking->id)
        ->with('error', 'هذه الدفعة لا تخص هذا الحجز.');
}

            // 2. التحقق من حالة الدفع
            if ($status === 'paid') {
                $paidAmountHalala = (int) $paymentData['amount'];
                $expectedAmountHalala = (int) round($booking->total_amount * 100);

                // مطابقة المبلغ المدفوع بالمبلغ المطلوب
                if ($paidAmountHalala >= $expectedAmountHalala) {

                    DB::transaction(function () use ($booking, $paymentData, $paymentId) {

                        $booking->update([
                            'payment_status'     => 'paid',
                            'status'             => 'approved',
                            'payment_method'     => 'moyasar',
                            'moyasar_payment_id' => $paymentId,
                            'card_name'          => $paymentData['source']['company'] ?? 'Mada/Card',
                        ]);

                        // حساب العمولة + إنشاء حركة المحفظة (مكان واحد لكل المسارات)
                        app(\App\Services\BookingSettlement::class)->settle($booking);

                        $this->generateTicketsQrCodes($booking);
                    });

                    try {
                        $booking->refresh()->load(['trip', 'bookingSeats']);
                        if (!empty($booking->customer_email)) {
                            Mail::to($booking->customer_email)->send(new BookingTicketMail($booking));
                        }
                    } catch (\Exception $mailEx) {
                        Log::error('فشل إرسال إيميل تأكيد الدفع #' . $booking->id . ': ' . $mailEx->getMessage());
                    }

                    // ✅ نجاح الدفع → نمسح المسودة المؤقتة
                    return redirect()->route('client.profile')->with([
                        'success' => 'تم الدفع بنجاح وتأكيد حجزك رقم #BK-' . $booking->id,
                        'clear_booking_draft' => true,
                    ]);
                } else {
                    return redirect()->route('client.payment.pay', $booking->id)
                        ->with('error', "المبلغ المدفوع ({$paidAmountHalala} هللة) لا يطابق قيمة الحجز المطلوبة ({$expectedAmountHalala} هللة).");
                }
            } else {
                // ❌ حالة فشل من البنك (إظهار سبب الرفض المباشر من البنك)
                $bankMessage = $paymentData['source']['message'] ?? 'تم رفض البطاقة من قبل البنك المصدِر.';

                return redirect()->route('client.payment.pay', $booking->id)
                    ->with('error', "فشلت عملية الدفع: {$bankMessage}");
            }
        } catch (\Exception $e) {
            Log::error('Exception in Moyasar Callback: ' . $e->getMessage());

            return redirect()->route('client.payment.pay', $booking->id)
                ->with('error', 'حدث خطأ أثناء التحقق: ' . $e->getMessage());
        }
    }

    // الإلغاء من صفحة الدفع: تراجع كامل بدون حفظ بيانات
    public function cancelPayment(Booking $booking)
    {
        // حجز مدفوع: لا يُلغى من هنا، لأن الإلغاء يشغّل استرداداً تلقائياً عبر ميسر
        if ($booking->payment_status === 'paid' || in_array($booking->status, ['paid', 'confirmed', 'approved'])) {
            return redirect()->route('client.profile')
                ->with('error', 'هذا الحجز مدفوع، يمكنك تقديم طلب إلغاء من صفحة حجوزاتك.');
        }

        // سبق إلغاؤه أو عليه طلب إلغاء
        if (in_array($booking->status, ['cancelled', 'requestC'])) {
            return redirect()->route('client.trips')->with([
                'success' => 'هذا الحجز ملغى بالفعل.',
                'clear_booking_draft' => true,
            ]);
        }

        // غير مدفوع: إلغاء كامل وتحرير المقاعد والغرف فوراً
        $this->cancelAndReleaseSeats($booking, 'تراجع من العميل في صفحة الدفع');

        return redirect()->route('client.trips')->with([
            'success' => 'تم إلغاء الحجز بالكامل وتحرير المقاعد.',
            'clear_booking_draft' => true,
        ]);
    }

    /**
     * دالة مساعدة لتوليد أكواد الـ QR بعد نجاح الدفع
     */
    private function generateTicketsQrCodes(Booking $booking)
    {
        try {
            foreach ($booking->bookingSeats as $bookedSeat) {
                // ✅ 1. معالجة رقم المقعد ليصبح نصاً صالحاً لتسمية الملفات والبيانات
                $seatString = is_array($bookedSeat->seat_number)
                    ? implode('-', $bookedSeat->seat_number)
                    : ($bookedSeat->seat_number ?? 'NA');

                $qrPayload = [
                    'ticket_no'     => 'TKT-' . $bookedSeat->id,
                    'booking_no'    => 'BK-' . $booking->id,
                    'trip'          => $booking->trip->title_ar ?? $booking->trip->title ?? $booking->trip->id,
                    'seat'          => $seatString, // ✅ تم التعديل
                    'passenger'     => $bookedSeat->passenger_name,
                    'passenger_id'  => $bookedSeat->passenger_national_id,
                    'room_assigned' => $bookedSeat->room_type,
                    'date'          => $booking->trip->departure_at ?? $booking->trip->start_date,
                ];

                $qrString = json_encode($qrPayload, JSON_UNESCAPED_UNICODE);
                $image = QrCode::format('svg')->encoding('UTF-8')->size(300)->errorCorrection('H')->generate($qrString);

                $folderPath = 'qrcodes/bookings/' . $booking->id;
                // ✅ 2. استخدام المتغير الآمن في اسم الملف
                $fileName = 'ticket_' . $bookedSeat->id . '_' . $seatString . '.svg';
                $fullPath = $folderPath . '/' . $fileName;

                Storage::disk('public')->put($fullPath, $image);
                $bookedSeat->update(['qr_code_path' => $fullPath]);
            }
        } catch (\Exception $qrException) {
            Log::error('فشل إنشاء ملف الـ QR للتذكرة: ' . $qrException->getMessage());
        }
    }

    /**
     * دالة مساعدة لإلغاء الحجز، استرداد المال عبر ميسر (إن وجد)، وإعادة المقاعد للرحلة
     */
    public function cancelAndReleaseSeats(Booking $booking, $reason = 'فشل عملية الدفع / انتهاء المهلة')
    {
        // 1. التحقق مما إذا كان الحجز ملغى مسبقاً
        if ($booking->status === 'cancelled') {
            return false;
        }

        // تحميل العلاقات المطلوبة إذا لم تكن محملة
        $booking->loadMissing(['seats', 'trip']);

        return DB::transaction(function () use ($booking, $reason) {
            $trip = $booking->trip;

            // حساب المبلغ الأصلي ورقم المعاملة قبل تصفير الحقول
            $originalAmount = floatval($booking->total_amount ?? $booking->total_price ?? $booking->amount ?? 0);
            $deductedAmount = -abs($originalAmount);
            $moyasarPaymentId = $booking->moyasar_payment_id ?? $booking->payment_id ?? null;

            // 🟢 محاولة الاسترداد المالي التلقائي من ميسر إذا كان الحجز مدفوعاً بالفعل
            $paymentStatus = 'failed';
            $refundLogNote = '';

            if ($booking->payment_status === 'paid' && !empty($moyasarPaymentId) && $originalAmount > 0) {
                $refundResult = $this->refundMoyasarPayment($moyasarPaymentId, $originalAmount);

                if ($refundResult['success']) {
                    $paymentStatus = 'refunded';
                    $refundLogNote = " وتم إرجاع مبلغ ({$originalAmount}) ريال تلقائياً لبطاقة العميل عبر ميسر.";
                } else {
                    $paymentStatus = 'refund_failed';
                    $refundLogNote = " وتأجل الإرجاع التلقائي لتعثره: " . ($refundResult['message'] ?? '');
                }
            }
            $extraResult = app(\App\Services\ExtraChargeRefunder::class)->refundAll($booking);
            $refundLogNote .= $extraResult['note'];
            // دعم مسمى العلاقة سواء كانت (seats) أو (bookingSeats)
            $seatsCollection = $booking->seats ?? $booking->bookingSeats;
            $seatsCount = $seatsCollection ? $seatsCollection->count() : 0;

            if ($trip) {
                // أ. جلب مصفوفة الغرف الحالية وتأمينها كمصفوفة
                $roomOptions = is_array($trip->room_options)
                    ? $trip->room_options
                    : (json_decode($trip->room_options, true) ?? []);

                // ب. تحديد طريقة إرجاع مخزون الغرف (من الحجز مباشرة أو عبر حساب المقاعد)
                if (!empty($booking->room_type) && isset($roomOptions[$booking->room_type])) {

                    // [الخيار الأول]: إذا كانت الغرفة مسجلة مباشرة في جدول الحجوزات
                    $roomsToRestore = $booking->rooms_count ?? 1;
                    $roomOptions[$booking->room_type]['count'] += $roomsToRestore;
                } else if ($seatsCollection && $seatsCollection->count() > 0) {

                    // [الخيار الثاني]: حساب الغرف تلقائياً بناءً على مقاعد الركاب ونوع غرفهم
                    $seatsByRoom = $seatsCollection->groupBy(function ($seat) {
                        return explode('_', $seat->room_type ?? 'single')[0];
                    });

                    foreach ($seatsByRoom as $cleanType => $seatsGroup) {
                        if (in_array($cleanType, ['single', 'double', 'triple', 'quad'])) {

                            $capacity = match ($cleanType) {
                                'single' => 1,
                                'double' => 2,
                                'triple' => 3,
                                'quad'   => 4,
                                default  => 1
                            };

                            $roomsToRestore = ceil($seatsGroup->count() / $capacity);

                            if (isset($roomOptions[$cleanType])) {
                                $roomOptions[$cleanType]['count'] += $roomsToRestore;
                            }
                        }
                    }
                }

                // ج. حفظ مصفوفة الغرف المحدثة للرحلة
                $trip->room_options = $roomOptions;

                // د. تحديث مخزون المقاعد المتاحة الكلي للرحلة
                if ($seatsCount > 0) {
                    $trip->available_seats += $seatsCount;
                }

                // حفظ التعديلات على جدول الرحلة
                $trip->save();
            }

            // 2. تحديث حالة الحجز وتصفير العمولة والمبالغ وتحديد حالة الدفع الجديدة (refunded / failed)
            $booking->update([
                'status'              => 'cancelled',
                'payment_status'      => $paymentStatus,
                'platform_commission' => 0,
                'total_amount'        => 0,
                'agency_amount'       => 0,
            ]);

            // 3. تحديث المعاملة المالية للوكالة وإلغائها لتصفير الرصيد المعلق (pending_balance)
            app(\App\Services\BookingSettlement::class)->reverse($booking);

            // 4. تفريغ المقاعد وحذفها من الحجز
            if (method_exists($booking, 'seats') && $booking->seats()) {
                $booking->seats()->delete();
            } elseif (method_exists($booking, 'bookingSeats') && $booking->bookingSeats()) {
                $booking->bookingSeats()->delete();
            }

            // 5. تسجيل عملية الخصم والإلغاء والاسترداد في جدول السجلات (AuditLog)
            $agencyId     = $trip->agency_id ?? auth()->user()->agency_id ?? null;
            $operatorName = method_exists($this, 'getOperatorName') ? $this->getOperatorName() : 'النظام (تلقائي)';
            $logReason    = $reason ? " - السبب: {$reason}" : '';

            AuditLog::create([
                'agency_id'      => $agencyId,
                'trip_id'        => $booking->trip_id,
                'action_type'    => 'cancel_booking',
                'category'       => 'booking',
                'amount_changed' => $deductedAmount,
                'seats_changed'  => -$seatsCount,
                'description'    => "تم إلغاء الحجز رقم (#{$booking->id}) وتصفير العمولة والسجل المالي{$refundLogNote}{$logReason}",
                'operator_name'  => $operatorName,
            ]);

            return true;
        });
    }
    // التراجع الكامل من صفحة الدفع (حجز غير مدفوع فقط)
    public function abortPayment(Booking $booking)
    {
        // حماية: الحجز يخص المستخدم الحالي (عدّل اسم العمود إن كان مختلفاً)
        abort_unless($booking->user_id === auth()->id(), 403);

        // مدفوع: لا يُلغى من هنا
        if ($booking->payment_status === 'paid') {
            return redirect()->route('client.profile')
                ->with('error', 'هذا الحجز مدفوع، يمكنك تقديم طلب إلغاء من صفحة حجوزاتك.');
        }

        if ($booking->status !== 'cancelled') {
            $this->cancelAndReleaseSeats($booking, 'تراجع من العميل في صفحة الدفع');
        }

        return redirect()->route('client.trips')->with([
            'success' => 'تم إلغاء الحجز بالكامل وتحرير المقاعد.',
            'clear_booking_draft' => true,
        ]);
    }

    /**
     * دالة مساعدة للاسترداد التلقائي عبر API ميسر
     */
    private function refundMoyasarPayment($paymentId, $amountInRiyal)
    {
        try {
            // تحويل المبلغ إلى هللات (مثال: 100 ريال = 10000 هللة)
            $amountInHalalas = (int) round($amountInRiyal * 100);

            $response = Http::withBasicAuth(config('services.moyasar.secret_key'), '')
                ->post("https://api.moyasar.com/v1/payments/{$paymentId}/refund", [
                    'amount' => $amountInHalalas,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data'    => $response->json(),
                ];
            }

            Log::error('Moyasar Refund Error: ' . $response->body());
            return [
                'success' => false,
                'message' => $response->json('message') ?? 'فشلت عملية الاسترداد من ميسر',
            ];
        } catch (\Exception $e) {
            Log::error('Moyasar Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
