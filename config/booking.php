<?php

return [

    /*
    |--------------------------------------------------------------------------
    | سياسات مالية وتشغيلية قابلة للإعداد (غير مفعّلة كقواعد ثابتة غير قابلة للتغيير)
    |--------------------------------------------------------------------------
    | القرارات غير المعتمدة تبقى قابلة للضبط من هنا دون افتراض سياسة نهائية.
    */

    'allow_invoice_before_full_payment' => env('BOOKING_ALLOW_INVOICE_BEFORE_FULL_PAYMENT', true),
    'allow_confirm_with_remaining' => env('BOOKING_ALLOW_CONFIRM_WITH_REMAINING', true),
    'stack_discount_code_with_trip_discounts' => env('BOOKING_STACK_DISCOUNT_CODE', true),
    'tax_rate' => env('BOOKING_TAX_RATE', 0),
    'apply_tax_after_discounts' => env('BOOKING_TAX_AFTER_DISCOUNTS', true),

    'wallet_credit_targets' => ['agency'],
    'points_value' => env('BOOKING_POINTS_VALUE', 0.01),
    'points_max_redemption' => env('BOOKING_POINTS_MAX_REDEMPTION', null),
    'refund_points_on_cancel' => env('BOOKING_REFUND_POINTS_ON_CANCEL', true),
    'refund_discount_code_on_cancel' => env('BOOKING_REFUND_DISCOUNT_ON_CANCEL', true),

    'client_link_ttl_days' => env('BOOKING_CLIENT_LINK_TTL_DAYS', 30),
    'identity_required' => env('BOOKING_IDENTITY_REQUIRED', false),
    'block_on_expired_identity' => env('BOOKING_BLOCK_ON_EXPIRED_IDENTITY', false),
    'overpayment_policy' => env('BOOKING_OVERPAYMENT_POLICY', 'hold'),

    'sms_templates' => [
        'booking_confirmation' => 'عزيزي :name، تم تأكيد حجزك رقم :booking في رحلة :trip. المقعد: :seat',
        'trip_reminder' => 'تذكير: رحلتك :trip تنطلق بتاريخ :date. رقم الحجز :booking',
        'bus_location' => 'موقع انطلاق الحافلة لرحلة :trip: :location. رقم الحجز :booking',
        'seat_number' => 'رقم مقعدك في رحلة :trip هو :seat. رقم الحجز :booking',
        'room_number' => 'رقم غرفتك في رحلة :trip هو :room. رقم الحجز :booking',
        'remaining_amount' => 'عزيزي :name، المتبقي على حجزك :booking هو :remaining ر.س',
        'custom' => ':message',
    ],

    'whatsapp_templates' => [
        'booking_share' => "مرحباً :name\nتفاصيل حجزك رقم :booking في رحلة :trip\nالإجمالي: :total ر.س\nالمدفوع: :paid ر.س\nالمتبقي: :remaining ر.س\nرابط الحجز: :link",
    ],
];
