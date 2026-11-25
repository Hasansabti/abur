<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingExtraCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoyasarReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $page   = max(1, (int) $request->input('page', 1));
        $status = $request->input('status');
        $from   = $request->input('from');
        $to     = $request->input('to');
        $filter = $request->input('match'); // matched | mismatch | orphan

        $query = ['page' => $page];
        if ($status) $query['status'] = $status;
        if ($from)   $query['created[gt]'] = $from . 'T00:00:00';
        if ($to)     $query['created[lt]'] = $to . 'T23:59:59';

        $error = null;
        $payments = [];
        $meta = [];

        try {
            $res = Http::withBasicAuth(config('services.moyasar.secret_key'), '')
                ->timeout(15)
                ->get('https://api.moyasar.com/v1/payments', $query);

            if ($res->successful()) {
                $payments = $res->json('payments', []);
                $meta     = $res->json('meta', []);
            } else {
                Log::error('Moyasar list payments: ' . $res->body());
                $error = 'تعذر جلب المدفوعات من ميسر: ' . ($res->json('message') ?? $res->status());
            }
        } catch (\Throwable $e) {
            Log::error('Moyasar list exception: ' . $e->getMessage());
            $error = 'تعذر الاتصال بميسر.';
        }

        // نجلب السجلات المحلية دفعة واحدة
        $ids = collect($payments)->pluck('id')->filter()->all();

        $bookings = Booking::with('trip:id,title')
            ->whereIn('moyasar_payment_id', $ids)
            ->get()
            ->keyBy('moyasar_payment_id');

        $charges = BookingExtraCharge::with('booking:id,customer_name')
            ->whereIn('moyasar_payment_id', $ids)
            ->get()
            ->keyBy('moyasar_payment_id');

        $rows = collect($payments)->map(function ($p) use ($bookings, $charges) {
            $booking = $bookings->get($p['id']);
            $charge  = $charges->get($p['id']);

            // fallback: الربط عبر metadata إذا لم يُخزَّن الـ id بعد
            if (!$booking && !$charge && !empty($p['metadata']['booking_id'])) {
                $booking = Booking::with('trip:id,title')->find($p['metadata']['booking_id']);
            }

            $paidAtMoyasar = ($p['status'] ?? '') === 'paid';
            $amount        = ((int) ($p['amount'] ?? 0)) / 100;

            // حالة المطابقة
            if ($booking) {
                $localPaid = $booking->payment_status === 'paid';
                $expected  = (float) $booking->total_amount;

                if ($paidAtMoyasar && !$localPaid && $booking->status !== 'cancelled') {
                    $match = 'mismatch'; $note = 'مدفوع في ميسر وغير مؤكد في الموقع';
                } elseif ($paidAtMoyasar && $localPaid && abs($expected - $amount) > 0.01 && $expected > 0) {
                    $match = 'amount'; $note = 'فرق في المبلغ (الحجز: ' . number_format($expected, 2) . ')';
                } elseif (!$paidAtMoyasar && $localPaid) {
                    $match = 'mismatch'; $note = 'مؤكد في الموقع وغير مدفوع في ميسر';
                } else {
                    $match = 'matched'; $note = '';
                }
                $ref = ['type' => 'حجز', 'label' => '#BK-' . $booking->id, 'sub' => $booking->customer_name];
            } elseif ($charge) {
                if ($paidAtMoyasar && $charge->status !== 'paid') {
                    $match = 'mismatch'; $note = 'فاتورة إضافية مدفوعة في ميسر وغير مسجلة';
                } else {
                    $match = 'matched'; $note = '';
                }
                $ref = ['type' => 'فاتورة إضافية', 'label' => 'حجز #BK-' . $charge->booking_id, 'sub' => $charge->description];
            } else {
                $match = $paidAtMoyasar ? 'orphan' : 'other';
                $note  = $paidAtMoyasar ? 'دفعة مدفوعة بدون حجز مرتبط' : '';
                $ref   = null;
            }

            return [
                'id'       => $p['id'],
                'status'   => $p['status'] ?? '-',
                'amount'   => $amount,
                'fee'      => ((int) ($p['fee'] ?? 0)) / 100,
                'refunded' => ((int) ($p['refunded'] ?? 0)) / 100,
                'method'   => $p['source']['company'] ?? ($p['source']['type'] ?? '-'),
                'created'  => $p['created_at'] ?? null,
                'ref'      => $ref,
                'match'    => $match,
                'note'     => $note,
            ];
        });

        $stats = [
            'count'    => $rows->count(),
            'paid'     => $rows->where('status', 'paid')->sum('amount'),
            'fees'     => $rows->where('status', 'paid')->sum('fee'),
            'refunded' => $rows->sum('refunded'),
            'issues'   => $rows->whereIn('match', ['mismatch', 'orphan', 'amount'])->count(),
        ];

        if ($filter === 'issues') {
            $rows = $rows->whereIn('match', ['mismatch', 'orphan', 'amount'])->values();
        } elseif ($filter) {
            $rows = $rows->where('match', $filter)->values();
        }

        return view('admin.moyasar.payments', compact('rows', 'stats', 'meta', 'error', 'page', 'status', 'from', 'to', 'filter'));
    }
}