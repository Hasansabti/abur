<?php

namespace Tests\Feature;

use Tests\TestCase;

class BookingOperationsRoutesTest extends TestCase
{
    public function test_customer_share_link_rejects_unknown_token(): void
    {
        $this->get('/b/not-a-real-token')->assertNotFound();
    }

    public function test_agency_discount_preview_requires_authentication(): void
    {
        $this->postJson('/agency/discount-codes/preview', [
            'code' => 'TEST',
            'amount' => 100,
        ])->assertUnauthorized();
    }
}
