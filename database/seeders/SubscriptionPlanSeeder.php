<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'الأساسية (مجانية)', 'description' => 'للبداية وتجربة المنصة', 'is_default' => true,
                'monthly_price' => 0, 'yearly_price' => 0, 'sort_order' => 1,
                'max_active_trips' => 5, 'max_vehicles' => 3, 'max_drivers' => 3, 'max_employees' => 2,
                'allows_overflow_bus' => false,
                'commission_per_passenger' => 5, 'min_booking_commission' => 10, 'max_booking_commission' => 30,
                'whatsapp_monthly_limit' => 200, 'whatsapp_extra_rate' => 0.10,
            ],
            [
                'name' => 'الاحترافية', 'description' => 'للوكالات النشطة', 'is_default' => false,
                'monthly_price' => 299, 'yearly_price' => 2990, 'sort_order' => 2,
                'max_active_trips' => 30, 'max_vehicles' => 15, 'max_drivers' => 15, 'max_employees' => 10,
                'allows_overflow_bus' => true,
                'commission_per_passenger' => 3, 'min_booking_commission' => 6, 'max_booking_commission' => 20,
                'whatsapp_monthly_limit' => 1000, 'whatsapp_extra_rate' => 0.08,
            ],
            [
                'name' => 'المؤسسات', 'description' => 'بدون حدود للوكالات الكبرى', 'is_default' => false,
                'monthly_price' => 799, 'yearly_price' => 7990, 'sort_order' => 3,
                'max_active_trips' => null, 'max_vehicles' => null, 'max_drivers' => null, 'max_employees' => null,
                'allows_overflow_bus' => true,
                'commission_per_passenger' => 2, 'min_booking_commission' => 4, 'max_booking_commission' => 12,
                'whatsapp_monthly_limit' => 5000, 'whatsapp_extra_rate' => 0.05,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['name' => $plan['name']], $plan);
        }
    }
}