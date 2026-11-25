<?php

namespace Tests\Unit;

use App\Services\BookingCalculator;
use PHPUnit\Framework\TestCase;

class BookingCalculatorExtrasTest extends TestCase
{
    public function test_custom_services_and_extra_beds_are_included_once(): void
    {
        $trip = (object) [
            'room_options' => ['single' => ['price' => 100]],
            'meal_options' => [],
            'additional_services' => [],
            'extra_seat_price' => 50,
            'price' => 50,
            'accommodation_only_discount' => 0,
            'extra_bed_price' => 40,
        ];

        $totals = BookingCalculator::calculate(
            $trip,
            ['single' => 1],
            [1],
            [1 => 'single'],
            [1 => ['wants_meal' => false]],
            [],
            [
                'custom_services' => [
                    ['name' => 'نقل إضافي', 'quantity' => 2, 'unit_price' => 50],
                ],
                'bed_assignments' => [
                    1 => ['extra_bed_quantity' => 1, 'extra_bed_unit_price' => 40],
                ],
                'discount_code_amount' => 20,
                'paid_amount' => 50,
                'stack_discounts' => true,
                'tax_rate' => 0,
            ]
        );

        $this->assertSame(100.0, $totals['total_custom_services_cost']);
        $this->assertSame(40.0, $totals['total_extra_beds_cost']);
        $this->assertSame(20.0, $totals['discount_code_amount']);
        $this->assertGreaterThan(0, $totals['total_price']);
        $this->assertSame('partial', $totals['payment_state']);
        $this->assertGreaterThan(0, $totals['remaining_amount']);
    }

    public function test_total_never_goes_negative(): void
    {
        $trip = (object) [
            'room_options' => ['single' => ['price' => 10]],
            'meal_options' => [],
            'additional_services' => [],
            'price' => 0,
            'accommodation_only_discount' => 0,
        ];

        $totals = BookingCalculator::calculate(
            $trip,
            ['single' => 1],
            [1],
            [1 => 'single'],
            [],
            [],
            ['discount_code_amount' => 999, 'stack_discounts' => true, 'tax_rate' => 0]
        );

        $this->assertGreaterThanOrEqual(0, $totals['total_price']);
    }
}
