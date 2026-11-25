<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    /**
     * عرض شاشة المحفظة الرئيسية للوكالة
     */
    public function indexWallet(Request $request)
    {
        /** @var \App\Models\Agency $agency */
        $agency = auth('agency')->user() ?? auth()->user()->agency;

        if (!$agency) {
            return redirect()->route('login')->with('error', 'جلسة العمل غير صالحة.');
        }

        // 0. تحويل المعاملات المالية للرحلات المكتملة من "معلق" إلى "متاح للسحب" تلقائياً
        $agency->transactions()
            ->where('status', 'pending')
            ->whereHas('booking', function ($query) {
                $query->whereIn('status', ['completed', 'تمت الرحلة'])
                    ->orWhereHas('trip', function ($tripQuery) {
                        $tripQuery->whereIn('status', ['completed', 'تمت الرحلة']);
                    });
            })
            ->update(['status' => 'available']);

        // 1. جلب الأرصدة المحسوبة تلقائياً وإعدادات العمولة الخاصة بالوكالة
        $pendingBalance   = $agency->pending_balance;
        $availableBalance = $agency->available_balance;

        // تفاصيل العمولة الجديدة لكل مسافر والحدود
        $commissionPerPassenger = $agency->commission_per_passenger ?? 0.0;
        $minBookingCommission   = $agency->min_booking_commission;
        $maxBookingCommission   = $agency->max_booking_commission;

        // 2. حساب إجمالي التحويلات البنكية المكتملة وإجمالي الأرباح الصافية
        $totalApprovedPayouts = $agency->payoutRequests()
            ->whereIn('status', ['approved', 'completed'])
            ->sum('requested_amount');

        // إجمالي الأرباح = الرصيد المتاح + الرصيد المعلق + المبالغ التي تم تحويلها بنكياً بالفعل
        $totalEarnings = $availableBalance + $pendingBalance + $totalApprovedPayouts;

        // 3. حساب استهلاك الواتساب للشهر الحالي
        $whatsappUsage = $agency->calculateCurrentMonthWhatsappFee();

        // 4. بناء استعلام الحركات المالية مع دعم البحث والفلترة
        $transactionsQuery = $agency->transactions()
            ->with(['booking.bookingSeats'])
            ->latest();

        if ($request->filled('search')) {
            $transactionsQuery->where('description', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $transactionsQuery->where('type', $request->type);
        }

        if ($request->filled('date_range')) {
            if ($request->date_range === 'this_month') {
                $transactionsQuery->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
            } elseif ($request->date_range === 'last_month') {
                $transactionsQuery->whereMonth('created_at', now()->subMonth()->month)
                    ->whereYear('created_at', now()->subMonth()->year);
            }
        }

        // 5. تصدير كشف الحساب CSV
        if ($request->get('export') === 'csv') {
            $exportTransactions = $transactionsQuery->get();
            $fileName = 'statement_' . date('Y-m-d_H-i') . '.csv';

            $handle = fopen('php://temp', 'r+');
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ['رقم المرجع', 'التاريخ', 'النوع', 'الوصف', 'الإجمالي (ر.س)', 'تفاصيل العمولة', 'عمولة المنصة (ر.س)', 'الصافي للمحفظة (ر.س)', 'الحالة']);

            foreach ($exportTransactions as $trx) {
                $isBooking = $trx->type === 'booking_credit';

                // الأولوية للقيم المخزنة على الحركة، ثم القيم المخزنة على الحجز
                $commissionAmount = $isBooking
                    ? (float) ($trx->commission_amount ?? $trx->booking?->platform_commission ?? 0)
                    : 0.00;

                $gross = $isBooking
                    ? (float) ($trx->gross_amount ?? ($trx->amount + $commissionAmount))
                    : (float) $trx->amount;

                $passengersCount = $isBooking
                    ? ($trx->passengers_count ?? $trx->booking?->bookingSeats?->where('is_extra', 0)->count() ?? 0)
                    : 0;

                fputcsv($handle, [
                    '#TRX-' . $trx->id,
                    $trx->created_at->format('Y-m-d H:i'),
                    $trx->type,
                    $trx->description,
                    number_format($gross, 2),
                    $isBooking ? ($passengersCount . ' مسافر') : '—',
                    number_format($commissionAmount, 2),
                    number_format($trx->amount, 2),
                    $trx->status,
                ]);
            }

            rewind($handle);
            $csvContent = stream_get_contents($handle);
            fclose($handle);

            return response($csvContent, 200, [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Pragma'              => 'no-cache',
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Expires'             => '0',
            ]);
        }

        // 6. جلب سجل الحركات المالية مع الترقيم
        $transactions = $transactionsQuery->paginate(15, ['*'], 'transactions_page');

        // 7. جلب سجل طلبات السحب
        $payoutRequests = $agency->payoutRequests()
            ->latest()
            ->paginate(10, ['*'], 'payouts_page');

        // 8. التحقق مما إذا كان هناك طلب سحب قيد المعالجة
        $hasPendingPayout = $agency->payoutRequests()
            ->where('status', 'pending')
            ->exists();

        return view('agency.wallet.index', compact(
            'agency',
            'pendingBalance',
            'availableBalance',
            'totalEarnings',
            'totalApprovedPayouts',
            'whatsappUsage',
            'transactions',
            'payoutRequests',
            'hasPendingPayout',
            'commissionPerPassenger',
            'minBookingCommission',
            'maxBookingCommission'
        ));
    }

    /**
     * إرسال طلب سحب أرباح للإدارة
     */
    public function requestPayout(Request $request)
    {
        /** @var \App\Models\Agency $agency */
        $agency = auth('agency')->user() ?? auth()->user()->agency;

        $availableBalance = $agency->available_balance;

        $request->validate([
            'requested_amount' => [
                'required',
                'numeric',
                'min:100',
                'max:' . $availableBalance,
            ],
        ], [
            'requested_amount.required' => 'يرجى إدخال المبلغ المطلوب سحبه.',
            'requested_amount.min'      => 'الحد الأدنى لطلب السحب هو 100 ريال.',
            'requested_amount.max'      => 'المبلغ المطلوب أكبر من الرصيد المتاح للسحب.',
        ]);

        $pendingCount = $agency->payoutRequests()->where('status', 'pending')->count();
        if ($pendingCount > 0) {
            return redirect()->back()->with('error', 'لديك طلب سحب معلق بالفعل، يرجى الانتظار لحين معالجته من قبل الإدارة.');
        }

        try {
            DB::transaction(function () use ($agency, $request) {
                $requestedAmount = floatval($request->input('requested_amount'));

                $whatsappData = $agency->calculateCurrentMonthWhatsappFee();
                $whatsappFee = $whatsappData['extra_fee'];

                // تصحيح العملية الحسابية للخصم (المبلغ المطلوب ناقص رسوم الواتساب)
                $netPayout = max(0, $requestedAmount - $whatsappFee);

                PayoutRequest::create([
                    'agency_id'             => $agency->id,
                    'requested_amount'      => $requestedAmount,
                    'whatsapp_fee_deducted' => $whatsappFee,
                    'net_payout_amount'     => $netPayout,
                    'status'                => 'pending',
                ]);
            });

            return redirect()->back()->with('success', 'تم تقديم طلب سحب الأرباح بنجاح، وسيطبق الخصم المستحق ويتم التحويل للبنك بعد مراجعة الإدارة.');
        } catch (\Exception $e) {
            Log::error("فشل تقديم طلب سحب للوكالة #{$agency->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'حدث خطأ أثناء تقديم الطلب، يرجى المحاولة لاحقاً.');
        }
    }
}
