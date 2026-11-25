<?php

namespace Tests\Unit;

use App\Support\PassengerSeat;
use PHPUnit\Framework\TestCase;

class PassengerSeatTest extends TestCase
{
    public function test_extra_seat_is_not_an_actual_guest(): void
    {
        $extra = (object) ['is_extra' => 1, 'parent_seat_id' => 9, 'room_type' => 'additional', 'passenger_name' => 'أحمد (مقعد إضافي)'];
        $this->assertTrue(PassengerSeat::isExtra($extra));
        $this->assertFalse(PassengerSeat::isActualGuest($extra));
    }

    public function test_companion_is_an_actual_guest(): void
    {
        $guest = (object) ['is_extra' => 0, 'parent_seat_id' => null, 'room_type' => 'double', 'passenger_name' => 'سارة'];
        $this->assertFalse(PassengerSeat::isExtra($guest));
        $this->assertTrue(PassengerSeat::isActualGuest($guest));
    }
}
