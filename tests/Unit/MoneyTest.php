<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_avoids_float_drift(): void
    {
        $this->assertSame('0.30', Money::add('0.10', '0.20'));
        $this->assertSame('0.00', Money::maxZero(-5));
        $this->assertSame('25.00', Money::percent(100, 25));
        $this->assertSame('50.00', Money::min(80, 50));
    }
}
