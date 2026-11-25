<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingMessageLog;
use Illuminate\Support\Facades\Http;

class SmsService
{
    public function send(Booking $booking, string $phone, string $body, string $templateKey, string $actor): BookingMessageLog
    {
        $log = BookingMessageLog::create([
            'booking_id' => $booking->id,
            'agency_id' => $booking->agency_id,
            'customer_id' => $booking->user_id,
            'channel' => 'sms',
            'phone' => $phone,
            'body' => $body,
            'template_key' => $templateKey,
            'status' => 'queued',
            'sent_by' => $actor,
        ]);

        $provider = config('bookings.sms.provider');
        $apiUrl = config('bookings.sms.api_url');
        $apiKey = config('bookings.sms.api_key');

        if (!$provider || !$apiUrl || !$apiKey) {
            $log->update([
                'status' => 'failed',
                'failure_reason' => 'لم يتم إعداد مزود الرسائل النصية.',
            ]);
            throw new \RuntimeException('لم يتم إعداد مزود الرسائل النصية. أضف SMS_PROVIDER وSMS_API_URL وSMS_API_KEY في ملف البيئة.');
        }

        try {
            $response = Http::timeout(20)
                ->withToken($apiKey)
                ->acceptJson()
                ->post($apiUrl, [
                    'sender' => config('bookings.sms.sender'),
                    'phone' => $phone,
                    'message' => $body,
                ]);

            if (!$response->successful()) {
                $log->update([
                    'status' => 'failed',
                    'failure_reason' => mb_substr($response->body(), 0, 500),
                ]);
                throw new \RuntimeException('رفض مزود الرسائل إرسال الرسالة.');
            }

            $payload = $response->json() ?? [];
            $log->update([
                'status' => 'accepted',
                'provider_message_id' => $payload['id'] ?? $payload['message_id'] ?? null,
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            if ($log->status !== 'failed') {
                $log->update([
                    'status' => 'failed',
                    'failure_reason' => $e->getMessage(),
                ]);
            }
            throw $e;
        }

        return $log->fresh();
    }
}
