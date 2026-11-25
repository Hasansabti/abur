<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\AgencyTransaction;
use App\Models\Agency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use App\Models\Booking;
use App\Models\agency\AgencySubscription;

class PayoutController extends Controller
{
    /**
     * عرض جميع طلبات سحب الأرباح
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $search = trim((string) $request->query('search', ''));

        $payoutRequests = PayoutRequest::with('agency')
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('agency', function ($a) use ($search) {
                    $a->where(function ($w) use ($search) {
                        $w->where('name', 'like', "%{$search}%");
                        foreach (['ibanA', 'iban', 'bank_name'] as $col) {
                            if (Schema::hasColumn('agencies', $col)) {
                                $w->orWhere($col, 'like', "%{$search}%");
                            }
                        }
                    });
                });
            })
            ->latest()
            ->paginate(15);

        // إجماليات حقيقية (مو بس الصفحة الحالية)
        $pendingTotal  = (float) PayoutRequest::where('status', 'pending')->sum('requested_amount');
        $approvedTotal = (float) PayoutRequest::where('status', 'approved')->sum('requested_amount');

        $agencies = Agency::latest()->get();

        // الاشتراكات النشطة حالياً (وكالة => اشتراك + باقة)
        $agencySubs = AgencySubscription::with('plan:id,name')
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->get()
            ->keyBy('agency_id');
            // تفاصيل عمولات المنصة من الحجوزات
$commissionSearch = trim((string) $request->query('csearch', ''));

$commissionBookings = Booking::with([
        'trip:id,title,agency_id',
        'trip.agency:id,name,commission_per_passenger,min_booking_commission,max_booking_commission',
    ])
    ->withCount('bookingSeats')
    ->whereIn('payment_status', ['paid', 'paidof'])
    ->where('platform_commission', '>', 0)
    ->when($commissionSearch !== '', function ($q) use ($commissionSearch) {
        $id = (int) preg_replace('/\D/', '', $commissionSearch);
        $q->where(function ($w) use ($commissionSearch, $id) {
            $w->where('customer_name', 'like', "%{$commissionSearch}%")
              ->orWhereHas('trip', fn($t) => $t->where('title', 'like', "%{$commissionSearch}%"))
              ->orWhereHas('trip.agency', fn($a) => $a->where('name', 'like', "%{$commissionSearch}%"));
            if ($id > 0) {
                $w->orWhere('id', $id);
            }
        });
    })
    ->latest()
    ->paginate(15, ['*'], 'commissions_page')
    ->withQueryString()
    ->fragment('commissions-table');

        return view('admin.payouts.index', compact(
            'payoutRequests',
            'status',
            'agencies',
            'agencySubs',
            'pendingTotal',
            'approvedTotal',
            'commissionSearch',
            'commissionBookings',
        ));
    }

    /**
     * كشف حساب وكالة: من أي حجز، من أي رحلة، من أي مسافر، وكيف حُسبت العمولة
     */
    public function ledger(Agency $agency)
    {
        $rate = (float) ($agency->commission_per_passenger ?? 0);
        $min  = $agency->min_booking_commission !== null ? (float) $agency->min_booking_commission : null;
        $max  = $agency->max_booking_commission !== null ? (float) $agency->max_booking_commission : null;

        $allBookings = Booking::whereHas('trip', fn($q) => $q->where('agency_id', $agency->id))
            ->whereIn('payment_status', ['paid', 'paidof'])
            ->with(['trip:id,title,agency_id', 'bookingSeats'])
            ->latest()
            ->get();

        $rows = $allBookings->map(function ($b) use ($rate, $min, $max) {
            $n    = $b->bookingSeats->count();
            $calc = $n * $rate;
            $note = "{$n} × " . number_format($rate, 2);
            if ($min !== null && $calc < $min) { $calc = $min; $note .= ' (طُبّق الحد الأدنى)'; }
            if ($max !== null && $calc > $max) { $calc = $max; $note .= ' (طُبّق الحد الأعلى)'; }

            $gross  = (float) ($b->agency_amount ?? 0);
            $stored = (float) ($b->platform_commission ?? 0);
            $cash   = (float) ($b->cash_paid ?? 0);
            $net    = (float) ($b->net_amount ?? 0);

            return [
                'id'              => $b->id,
                'trip_id'         => $b->trip_id,
                'date'            => $b->created_at?->format('Y-m-d H:i'),
                'trip'            => $b->trip->title ?? 'رحلة محذوفة',
                'customer'        => $b->customer_name,
                'payment_status'  => $b->payment_status,
                'seats_count'     => $n,
                'travelers'       => $b->bookingSeats->pluck('passenger_name')->filter()->values()->all(),
                'gross'           => $gross,
                'vat'             => (float) ($b->vat_amount ?? 0),
                'commission'      => $stored,
                'commission_note' => $note . ' = ' . number_format($calc, 2),
                'rates_changed'   => abs($calc - $stored) > 0.01,
                'cash'            => $cash,
                'net'             => $net,
                // تحقق: الإجمالي − العمولة − النقد
                'net_check'       => round($gross - $stored - $cash, 2),
                'net_mismatch'    => abs(($gross - $stored - $cash) - $net) > 0.01,
            ];
        });

        $totals = [
            'bookings_count' => $rows->count(),
            'travelers'      => $rows->sum('seats_count'),
            'gross'          => round($rows->sum('gross'), 2),
            'vat'            => round($rows->sum('vat'), 2),
            'commission'     => round($rows->sum('commission'), 2),
            'cash'           => round($rows->sum('cash'), 2),
            'net'            => round($rows->sum('net'), 2),
        ];

        // ملخص لكل رحلة: كم حجز، كم مسافر، وكم ربحت الوكالة والمنصة منها
        $tripsSummary = $rows->groupBy('trip_id')->map(function ($g) {
            return [
                'trip_id'    => $g->first()['trip_id'],
                'trip'       => $g->first()['trip'],
                'bookings'   => $g->count(),
                'travelers'  => $g->sum('seats_count'),
                'gross'      => round($g->sum('gross'), 2),
                'commission' => round($g->sum('commission'), 2),
                'cash'       => round($g->sum('cash'), 2),
                'net'        => round($g->sum('net'), 2),
            ];
        })->sortByDesc('net')->values();

        // رصيد الوكالة: الصافي − المسحوب − المعلق
        $paidOut = (float) \App\Models\PayoutRequest::where('agency_id', $agency->id)
            ->where('status', 'approved')->sum('requested_amount');
        $pending = (float) \App\Models\PayoutRequest::where('agency_id', $agency->id)
            ->where('status', 'pending')->sum('requested_amount');

        $balance = [
            'earned'    => $totals['net'],
            'paid_out'  => round($paidOut, 2),
            'pending'   => round($pending, 2),
            'available' => round($totals['net'] - $paidOut - $pending, 2),
        ];

        $transactions = AgencyTransaction::where('agency_id', $agency->id)
            ->latest()->limit(100)
            ->get(['id', 'type', 'amount', 'status', 'description', 'created_at'])
            ->map(fn($t) => [
                'id'          => $t->id,
                'type'        => $t->type,
                'amount'      => (float) $t->amount,
                'status'      => $t->status,
                'description' => $t->description,
                'date'        => $t->created_at?->format('Y-m-d H:i'),
            ]);

        $sub = AgencySubscription::with('plan')
            ->where('agency_id', $agency->id)
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->first();

        return response()->json([
            'agency'         => ['id' => $agency->id, 'name' => $agency->name],
            'plan'           => $sub ? [
                'name'     => $sub->plan->name ?? '-',
                'cycle'    => $sub->billing_cycle === 'yearly' ? 'سنوي' : 'شهري',
                'ends_at'  => $sub->ends_at?->format('Y-m-d'),
                'per_pass' => (float) ($sub->plan->commission_per_passenger ?? 0),
                'min'      => $sub->plan->min_booking_commission,
                'max'      => $sub->plan->max_booking_commission,
            ] : null,
            'rates'          => ['per_passenger' => $rate, 'min' => $min, 'max' => $max],
            'totals'         => $totals,
            'balance'        => $balance,
            'trips'          => $tripsSummary,
            'bookings'       => $rows->take(300)->values(),
            'bookings_total' => $rows->count(),
            'transactions'   => $transactions,
        ]);
    }

    /**
     * تحديث عمولة وكالة محددة (لكل مسافر + حد أدنى + حد أعلى)
     */
    public function updateCommission(Request $request, Agency $agency)
    {
        $data = $this->validateCommissionData($request);

        $agency->update($data);

        return redirect()->back()->with(
            'success',
            "تم تحديث عمولة وكالة ({$agency->name}): " . $this->commissionSummary($data)
        );
    }

    /**
     * تحديث عمولة جميع الوكالات
     */
    public function updateAllCommissions(Request $request)
    {
        $data = $this->validateCommissionData($request);

        Agency::query()->update($data);

        return redirect()->back()->with(
            'success',
            'تم تحديث عمولة جميع الوكالات: ' . $this->commissionSummary($data)
        );
    }

    /**
     * التحقق من حقول العمولة (مشترك بين التعديل الفردي والجماعي)
     */
    private function validateCommissionData(Request $request): array
    {
        $data = $request->validate([
            'commission_per_passenger' => 'required|numeric|min:0|max:100000',
            'min_booking_commission'   => 'nullable|numeric|min:0|max:100000',
            'max_booking_commission'   => 'nullable|numeric|min:0|max:100000',
        ], [
            'commission_per_passenger.required' => 'يرجى إدخال عمولة المسافر الواحد.',
            'commission_per_passenger.numeric'  => 'عمولة المسافر يجب أن تكون رقماً.',
            'commission_per_passenger.min'      => 'عمولة المسافر لا يمكن أن تكون أقل من 0.',
            'min_booking_commission.numeric'    => 'الحد الأدنى يجب أن يكون رقماً.',
            'min_booking_commission.min'        => 'الحد الأدنى لا يمكن أن يكون أقل من 0.',
            'max_booking_commission.numeric'    => 'الحد الأعلى يجب أن يكون رقماً.',
            'max_booking_commission.min'        => 'الحد الأعلى لا يمكن أن يكون أقل من 0.',
        ]);

        $min = $data['min_booking_commission'] ?? null;
        $max = $data['max_booking_commission'] ?? null;

        if ($min !== null && $max !== null && (float) $max < (float) $min) {
            throw ValidationException::withMessages([
                'max_booking_commission' => 'الحد الأعلى يجب أن يكون أكبر من أو يساوي الحد الأدنى.',
            ]);
        }

        return [
            'commission_per_passenger' => $data['commission_per_passenger'],
            'min_booking_commission'   => $min, // null = بدون حد أدنى
            'max_booking_commission'   => $max, // null = بدون حد أعلى
        ];
    }

    /**
     * نص مختصر يوصف العمولة للرسائل
     */
    private function commissionSummary(array $d): string
    {
        $text = number_format((float) $d['commission_per_passenger'], 2) . ' ر.س لكل مسافر';
        $text .= $d['min_booking_commission'] !== null
            ? ' | حد أدنى ' . number_format((float) $d['min_booking_commission'], 2)
            : ' | بدون حد أدنى';
        $text .= $d['max_booking_commission'] !== null
            ? ' | حد أعلى ' . number_format((float) $d['max_booking_commission'], 2)
            : ' | بدون حد أعلى';

        return $text;
    }

    /**
     * الموافقة على طلب السحب وتوثيق التحويل البنكي
     */
    public function approve(Request $request, PayoutRequest $payoutRequest)
    {
        if ($payoutRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'تمت معالجة هذا الطلب مسبقاً.');
        }

        $request->validate([
            'bank_receipt' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120',
            'admin_notes'  => 'nullable|string|max:500',
        ], [
            'bank_receipt.required' => 'يرجى إرفاق إيصال التحويل البنكي.',
            'bank_receipt.mimes'    => 'صيغ الملف المقبولة هي: PNG, JPEG, PDF.',
        ]);

        try {
            DB::transaction(function () use ($request, $payoutRequest) {
                $agency = $payoutRequest->agency;

                $whatsappData = $agency->calculateCurrentMonthWhatsappFee();
                $whatsappFee  = $whatsappData['extra_fee'];

                $requestedAmount = floatval($payoutRequest->requested_amount);
                $netPayoutAmount = max(0, $requestedAmount - $whatsappFee);

                $receiptPath = null;
                if ($request->hasFile('bank_receipt')) {
                    $receiptPath = $request->file('bank_receipt')->store('payout_receipts', 'public');
                }

                $payoutRequest->update([
                    'status'                => 'approved',
                    'whatsapp_fee_deducted' => $whatsappFee,
                    'net_payout_amount'     => $netPayoutAmount,
                    'bank_receipt'          => $receiptPath,
                    'admin_notes'           => $request->input('admin_notes'),
                    'processed_at'          => now(),
                    'processed_by'          => auth()->id(),
                ]);

                AgencyTransaction::create([
                    'agency_id'   => $agency->id,
                    'type'        => 'payout',
                    'amount'      => -$requestedAmount,
                    'status'      => 'completed',
                    'description' => "سحب أرباح معتمد (طلب رقم #{$payoutRequest->id})",
                ]);

                if ($whatsappFee > 0) {
                    AgencyTransaction::create([
                        'agency_id'   => $agency->id,
                        'type'        => 'whatsapp_fee',
                        'amount'      => 0,
                        'status'      => 'completed',
                        'description' => "خصم رسوم إشعارات الواتساب الزائدة ({$whatsappData['extra_messages']} رسالة) عند معالجة طلب السحب #{$payoutRequest->id}",
                    ]);
                }
            });

            return redirect()->back()->with('success', 'تمت الموافقة على طلب السحب واعتماد إيصال التحويل بنجاح.');
        } catch (\Exception $e) {
            Log::error("فشل اعتماد طلب السحب #{$payoutRequest->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'حدث خطأ أثناء معالجة الطلب: ' . $e->getMessage());
        }
    }

    /**
     * رفض طلب السحب مع إبداء السبب
     */
    public function reject(Request $request, PayoutRequest $payoutRequest)
    {
        if ($payoutRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'تمت معالجة هذا الطلب مسبقاً.');
        }

        $request->validate([
            'admin_notes' => 'required|string|max:500',
        ], [
            'admin_notes.required' => 'يرجى كتابة سبب رفض الطلب للوكالة.',
        ]);

        $payoutRequest->update([
            'status'       => 'rejected',
            'admin_notes'  => $request->input('admin_notes'),
            'processed_at' => now(),
            'processed_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'تم رفض طلب السحب وإبلاغ الوكالة بالسبب.');
    }
}