<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyAndDiscountTest extends TestCase
{
    public function test_add_avoids_float_drift(): void
    {
        $this->assertSame('0.30', Money::add('0.10', '0.20'));
    }

    public function test_discount_cannot_exceed_eligible_amount(): void
    {
        $this->assertSame('50.00', Money::min('80.00', '50.00'));
        $this->assertSame('0.00', Money::max(0, Money::sub('10', '15')));
    }
}
