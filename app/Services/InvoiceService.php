<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Support\Money;
use App\Support\PassengerSeat;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function issue(Booking $booking, ?string $idempotencyKey = null): BookingInvoice
    {
        $booking->loadMissing(['trip.agency', 'bookingSeats.nationality', 'bookingSeats.room', 'payments', 'customServices', 'user']);

        return DB::transaction(function () use ($booking, $idempotencyKey) {
            $existing = BookingInvoice::where('booking_id', $booking->id)->lockForUpdate()->first();
            if ($existing) {
                return $existing;
            }

            $snapshot = $this->buildSnapshot($booking);
            $invoice = BookingInvoice::create([
                'invoice_number' => $this->nextNumber($booking->agency_id ?? $booking->trip?->agency_id),
                'booking_id' => $booking->id,
                'agency_id' => $booking->agency_id ?? $booking->trip?->agency_id,
                'issued_at' => now(),
                'snapshot' => $snapshot,
                'subtotal' => $snapshot['subtotal'],
                'discount_total' => $snapshot['discount_total'],
                'tax_total' => $snapshot['tax_total'],
                'grand_total' => $snapshot['grand_total'],
                'paid_amount' => $snapshot['paid_amount'],
                'remaining_amount' => $snapshot['remaining_amount'],
                'status' => 'issued',
                'issued_by_name' => BookingChangeLogger::actor()['name'],
                'idempotency_key' => $idempotencyKey,
            ]);

            foreach ($snapshot['items'] as $item) {
                $invoice->items()->create($item);
            }

            BookingChangeLogger::log($booking, 'issue_invoice', [], [
                'invoice_number' => $invoice->invoice_number,
                'grand_total' => $invoice->grand_total,
            ], 'invoice', $invoice->id);

            return $invoice->fresh('items');
        });
    }

    public function findForBooking(Booking $booking): ?BookingInvoice
    {
        return BookingInvoice::with('items')->where('booking_id', $booking->id)->first();
    }

    protected function nextNumber(?int $agencyId): string
    {
        $year = now()->format('Y');
        $prefix = 'INV-' . ($agencyId ?: 0) . '-' . $year . '-';

        $last = BookingInvoice::where('invoice_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('invoice_number');

        $seq = 1;
        if ($last) {
            $seq = ((int) substr($last, strlen($prefix))) + 1;
        }

        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    public function buildSnapshot(Booking $booking): array
    {
        $trip = $booking->trip;
        $agency = $trip?->agency;
        $items = [];

        foreach ($booking->bookingSeats as $seat) {
            $isExtra = PassengerSeat::isExtra($seat);
            $items[] = [
                'item_type' => $isExtra ? 'extra_seat' : 'seat',
                'label' => ($isExtra ? 'مقعد إضافي' : 'مقعد') . ' #' . (is_array($seat->seat_number) ? implode(',', $seat->seat_number) : $seat->seat_number) . ' - ' . $seat->passenger_name,
                'quantity' => 1,
                'unit_price' => 0,
                'total' => 0,
                'meta' => [
                    'passenger_name' => $seat->passenger_name,
                    'seat_number' => $seat->seat_number,
                    'room_type' => $seat->room_type,
                    'bed_type' => $seat->bed_type,
                    'extra_bed_quantity' => $seat->extra_bed_quantity,
                    'meals' => $seat->meal_snapshot ?? $seat->meal_options,
                    'is_extra' => $isExtra,
                ],
            ];

            $extraBedQty = (int) ($seat->extra_bed_quantity ?? 0);
            if ($extraBedQty > 0) {
                $unit = (float) ($seat->extra_bed_unit_price ?? 0);
                $items[] = [
                    'item_type' => 'extra_bed',
                    'label' => 'سرير إضافي - ' . $seat->passenger_name,
                    'quantity' => $extraBedQty,
                    'unit_price' => $unit,
                    'total' => Money::toFloat(Money::mul($unit, $extraBedQty)),
                    'meta' => ['bed_type' => $seat->bed_type],
                ];
            }
        }

        foreach ($booking->customServices ?? [] as $service) {
            $items[] = [
                'item_type' => 'custom_service',
                'label' => $service->name,
                'quantity' => $service->quantity,
                'unit_price' => (float) $service->unit_price,
                'total' => (float) $service->total_price,
                'meta' => ['description' => $service->description],
            ];
        }

        $paid = (float) ($booking->cash_paid ?? 0);
        $total = (float) $booking->total_amount;
        $discount = (float) ($booking->discount_amount ?? 0);
        $tax = (float) ($booking->tax_amount ?? 0);
        $subtotal = (float) ($booking->subtotal_amount ?? Money::add($total, $discount));
        $remaining = max(0, $total - $paid);
        $overpaid = max(0, $paid - $total);

        return [
            'invoice_date' => now()->toDateTimeString(),
            'agency' => [
                'id' => $agency?->id,
                'name' => $agency?->name,
                'phone' => $agency?->phone,
            ],
            'customer' => [
                'name' => $booking->customer_name,
                'phone' => $booking->customer_phone,
                'email' => $booking->customer_email,
            ],
            'booking_id' => $booking->id,
            'trip' => [
                'id' => $trip?->id,
                'title' => $trip?->title,
                'from' => $trip?->from_city,
                'to' => $trip?->to_city,
                'departure_at' => optional($trip?->departure_at)?->toDateTimeString(),
            ],
            'items' => $items,
            'subtotal' => $subtotal,
            'discount_total' => $discount,
            'discount_code' => $booking->discount_code,
            'accommodation_only_discount' => $trip?->accommodation_only_discount,
            'tax_total' => $tax,
            'grand_total' => $total,
            'paid_amount' => $paid,
            'remaining_amount' => $remaining,
            'overpaid_amount' => $overpaid,
            'payment_status' => $booking->payment_status,
            'payments' => $booking->payments?->map(fn ($p) => [
                'method' => $p->payment_method,
                'amount' => (float) $p->amount,
                'reference' => $p->reference_id,
            ])->values()->all() ?? [],
        ];
    }
}
