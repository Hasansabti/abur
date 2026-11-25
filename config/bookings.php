<?php

return [
    'allow_invoice_before_full_payment' => (bool) env('BOOKINGS_INVOICE_BEFORE_FULL_PAYMENT', true),
    'allow_confirm_with_remaining' => (bool) env('BOOKINGS_CONFIRM_WITH_REMAINING', true),
    'allow_combine_discount_code_with_trip_discounts' => (bool) env('BOOKINGS_COMBINE_DISCOUNTS', true),
    'identity_expiry_blocks_booking' => (bool) env('BOOKINGS_IDENTITY_EXPIRY_BLOCKS', false),
    'customer_link_ttl_days' => (int) env('BOOKINGS_CUSTOMER_LINK_TTL_DAYS', 30),
    'extra_bed_default_price' => env('BOOKINGS_EXTRA_BED_PRICE', 0),
    'loyalty' => [
        'sar_per_point' => env('LOYALTY_SAR_PER_POINT', 1),
        'max_redeem_percent' => env('LOYALTY_MAX_REDEEM_PERCENT', 100),
        'refund_on_cancel' => (bool) env('LOYALTY_REFUND_ON_CANCEL', true),
    ],
    'sms' => [
        'provider' => env('SMS_PROVIDER'),
        'api_url' => env('SMS_API_URL'),
        'api_key' => env('SMS_API_KEY'),
        'sender' => env('SMS_SENDER'),
    ],
    'whatsapp' => [
        'api_url' => env('WHATSAPP_API_URL'),
        'api_token' => env('WHATSAPP_API_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    ],
];
