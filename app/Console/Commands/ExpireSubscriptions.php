<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';
    protected $description = 'إنهاء الاشتراكات المنتهية وإرجاع الوكالات للباقة الافتراضية';

    public function handle(SubscriptionService $service): int
    {
        $count = $service->expireDue();
        $this->info("تم إنهاء {$count} اشتراك.");

        return self::SUCCESS;
    }
}