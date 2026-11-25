<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(private BookingChangeLogger $logger)
    {
    }

    public function issue(Booking $booking, string $issuedBy): BookingInvoice
    {
        $booking->loadMissing(['trip', 'agency', 'bookingSeats', 'payments', 'customServices', 'invoices']);

        if ($existing = $booking->invoices()->latest('id')->first()) {
            return $existing;
        }

        if (!config('bookings.allow_invoice_before_full_payment', true)) {
            $remaining = Money::sub($booking->total_amount, $booking->cash_paid);
            if (Money::gt($remaining, 0)) {
                throw new \RuntimeException('لا يمكن إصدار الفاتورة قبل اكتمال الدفع.');
            }
        }

        return DB::transaction(function () use ($booking, $issuedBy) {
            $locked = Booking::where('id', $booking->id)->lockForUpdate()->firstOrFail();
            $existing = $locked->invoices()->lockForUpdate()->first();
            if ($existing) {
                return $existing;
            }

            $snapshot = $this->buildSnapshot($locked);
            $invoice = $locked->invoices()->create([
                'agency_id' => $locked->agency_id ?? $locked->trip?->agency_id,
                'invoice_number' => $this->nextNumber($locked->id),
                'issued_at' => now(),
                'snapshot' => $snapshot,
                'subtotal' => $snapshot['subtotal'],
                'discount_total' => $snapshot['discount_total'],
                'tax_total' => $snapshot['tax_total'],
                'grand_total' => $snapshot['grand_total'],
                'paid_amount' => $snapshot['paid_amount'],
                'remaining_amount' => $snapshot['remaining_amount'],
                'issued_by' => $issuedBy,
            ]);

            foreach ($snapshot['items'] as $item) {
                $invoice->items()->create($item);
            }

            $this->logger->log($locked, 'invoice_issued', [], [
                'invoice_number' => $invoice->invoice_number,
            ], ['entity_type' => 'invoice', 'entity_id' => $invoice->id, 'force' => true]);

            return $invoice->fresh('items');
        });
    }

    public function buildSnapshot(Booking $booking): array
    {
        $booking->loadMissing(['trip', 'agency', 'user', 'bookingSeats', 'payments', 'customServices']);
        $items = [];

        foreach ($booking->bookingSeats as $seat) {
            if ($seat->is_extra) {
                $items[] = [
                    'item_type' => 'extra_seat',
                    'label' => 'مقعد إضافي - ' . ($seat->passenger_name ?? ''),
                    'quantity' => 1,
                    'unit_price' => $booking->trip->price ?? 0,
                    'total' => $booking->trip->price ?? 0,
                    'meta' => ['seat_id' => $seat->id],
                ];
                continue;
            }

            $items[] = [
                'item_type' => 'passenger',
                'label' => 'راكب: ' . ($seat->passenger_name ?? 'بدون اسم'),
                'quantity' => 1,
                'unit_price' => 0,
                'total' => 0,
                'meta' => [
                    'seat_id' => $seat->id,
                    'seat_number' => $seat->seat_number,
                    'room_type' => $seat->room_type,
                    'bed_type' => $seat->bed_type,
                    'extra_bed_quantity' => $seat->extra_bed_quantity,
                    'meals' => [
                        'package' => $seat->meal_options['package_name'] ?? null,
                        'package_price' => $seat->meal_options['package_price'] ?? 0,
                        'breakfast' => (bool) $seat->with_breakfast,
                        'lunch' => (bool) $seat->with_lunch,
                        'dinner' => (bool) $seat->with_dinner,
                    ],
                ],
            ];

            if ((int) $seat->extra_bed_quantity > 0) {
                $extraTotal = Money::toFloat(Money::mul($seat->extra_bed_quantity, $seat->extra_bed_unit_price));
                $items[] = [
                    'item_type' => 'extra_bed',
                    'label' => 'سرير إضافي',
                    'quantity' => (int) $seat->extra_bed_quantity,
                    'unit_price' => $seat->extra_bed_unit_price,
                    'total' => $extraTotal,
                    'meta' => ['seat_id' => $seat->id],
                ];
            }
        }

        foreach ($booking->customServices as $service) {
            $items[] = [
                'item_type' => 'custom_service',
                'label' => $service->name,
                'quantity' => $service->quantity,
                'unit_price' => $service->unit_price,
                'total' => $service->total_price,
                'meta' => ['description' => $service->description],
            ];
        }

        $paid = Money::of($booking->cash_paid);
        $total = Money::of($booking->total_amount);
        $remaining = Money::max(0, Money::sub($total, $paid));
        $overpaid = Money::max(0, Money::sub($paid, $total));

        return [
            'booking_id' => $booking->id,
            'agency' => [
                'name' => $booking->agency->name ?? $booking->agency->commercial_name ?? '',
            ],
            'customer' => [
                'name' => $booking->customer_name,
                'phone' => $booking->customer_phone,
            ],
            'trip' => [
                'id' => $booking->trip_id,
                'title' => $booking->trip->title ?? '',
            ],
            'items' => $items,
            'subtotal' => $booking->subtotal_amount ?? $booking->total_amount,
            'discount_total' => $booking->discount_amount ?? 0,
            'tax_total' => $booking->tax_amount ?? 0,
            'grand_total' => $booking->total_amount,
            'paid_amount' => Money::toFloat($paid),
            'remaining_amount' => Money::toFloat($remaining),
            'overpaid_amount' => Money::toFloat($overpaid),
            'payments' => $booking->payments->map(fn ($p) => [
                'method' => $p->payment_method,
                'amount' => $p->amount,
                'reference' => $p->reference_id,
            ])->values()->all(),
        ];
    }

    private function nextNumber(int $bookingId): string
    {
        return 'INV-' . now()->format('Ymd') . '-' . str_pad((string) $bookingId, 6, '0', STR_PAD_LEFT);
    }
}
