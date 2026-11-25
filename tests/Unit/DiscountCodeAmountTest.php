<?php

namespace Tests\Unit;

use App\Models\DiscountCode;
use App\Support\Money;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DiscountCodeAmountTest extends TestCase
{
    public function test_percent_discount_respects_max_and_eligible_amount(): void
    {
        $service = new \App\Services\DiscountCodeService();
        $ref = new ReflectionClass($service);
        $method = $ref->getMethod('computeAmount');
        $method->setAccessible(true);

        $code = new DiscountCode([
            'type' => 'percent',
            'value' => 50,
            'max_discount' => 30,
        ]);

        $this->assertSame('30.00', $method->invoke($service, $code, 100));
        $this->assertSame('10.00', $method->invoke($service, new DiscountCode(['type' => 'fixed', 'value' => 10]), 100));
        $this->assertSame('5.00', $method->invoke($service, new DiscountCode(['type' => 'fixed', 'value' => 40]), 5));
        $this->assertSame('0.00', Money::maxZero(Money::sub(5, 5)));
    }
}
