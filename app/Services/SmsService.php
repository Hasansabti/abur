<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingMessageLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SmsService
{
    public function send(Booking $booking, string $phone, string $message, ?string $templateKey = null, ?string $idempotencyKey = null): BookingMessageLog
    {
        $phone = $this->normalizePhone($phone);
        $agencyId = $booking->agency_id ?? $booking->trip?->agency_id;

        if ($idempotencyKey) {
            $existing = BookingMessageLog::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        $provider = config('services.sms.provider');
        $apiKey = config('services.sms.key');
        $sender = config('services.sms.sender');

        $log = BookingMessageLog::create([
            'booking_id' => $booking->id,
            'agency_id' => $agencyId,
            'channel' => 'sms',
            'template_key' => $templateKey,
            'customer_name' => $booking->customer_name,
            'phone' => $phone,
            'message' => $message,
            'status' => 'queued',
            'sent_by' => BookingChangeLogger::actor()['name'],
            'idempotency_key' => $idempotencyKey,
        ]);

        if (!$provider || !$apiKey) {
            $log->update([
                'status' => 'failed',
                'failure_reason' => 'مزود خدمة الرسائل النصية غير معدّ. أضف SMS_PROVIDER و SMS_API_KEY في ملف البيئة.',
            ]);
            throw ValidationException::withMessages([
                'sms' => 'مزود خدمة الرسائل النصية غير معدّ. يرجى ضبط بيانات المزود أولاً.',
            ]);
        }

        try {
            $result = $this->dispatch($provider, $apiKey, $sender, $phone, $message);
            $log->update([
                'status' => $result['accepted'] ? 'accepted' : 'failed',
                'provider_message_id' => $result['id'] ?? null,
                'delivery_status' => $result['delivery'] ?? 'unknown',
                'failure_reason' => $result['error'] ?? null,
                'sent_at' => $result['accepted'] ? now() : null,
            ]);

            if (!$result['accepted']) {
                throw ValidationException::withMessages(['sms' => $result['error'] ?: 'فشل إرسال الرسالة من المزود.']);
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('SMS send failed', ['booking' => $booking->id, 'error' => $e->getMessage()]);
            $log->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);
            throw ValidationException::withMessages(['sms' => 'تعذر الاتصال بمزود الرسائل النصية.']);
        }

        BookingChangeLogger::log($booking, 'send_sms', [], [
            'phone' => $phone,
            'template' => $templateKey,
            'status' => $log->status,
        ], 'message', $log->id);

        return $log->fresh();
    }

    public function renderTemplate(string $key, array $vars, ?string $custom = null): string
    {
        $templates = config('booking.sms_templates', []);
        $tpl = $key === 'custom' ? ($custom ?? ':message') : ($templates[$key] ?? $custom ?? '');

        foreach ($vars as $name => $value) {
            $tpl = str_replace(':' . $name, (string) $value, $tpl);
        }

        return $tpl;
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '05') && strlen($digits) === 10) {
            $digits = '966' . substr($digits, 1);
        } elseif (str_starts_with($digits, '5') && strlen($digits) === 9) {
            $digits = '966' . $digits;
        }

        return $digits;
    }

    protected function dispatch(string $provider, string $apiKey, ?string $sender, string $phone, string $message): array
    {
        $url = config('services.sms.endpoint');

        if ($provider === 'taqnyat') {
            $url = $url ?: 'https://api.taqnyat.sa/v1/messages';
            $response = Http::timeout(20)->withToken($apiKey)->post($url, [
                'recipients' => [$phone],
                'body' => $message,
                'sender' => $sender,
            ]);
        } elseif ($provider === 'unifonic') {
            $url = $url ?: 'https://el.cloud.unifonic.com/rest/SMS/messages';
            $response = Http::timeout(20)->asForm()->post($url, [
                'AppSid' => $apiKey,
                'SenderID' => $sender,
                'Recipient' => $phone,
                'Body' => $message,
            ]);
        } else {
            if (!$url) {
                return ['accepted' => false, 'error' => 'عنوان واجهة مزود SMS غير محدد.'];
            }
            $response = Http::timeout(20)->withToken($apiKey)->post($url, [
                'to' => $phone,
                'sender' => $sender,
                'message' => $message,
            ]);
        }

        if (!$response->successful()) {
            return ['accepted' => false, 'error' => 'رفض المزود الرسالة: ' . $response->status()];
        }

        $json = $response->json() ?? [];

        return [
            'accepted' => true,
            'id' => $json['messageId'] ?? $json['id'] ?? $json['data'][0]['id'] ?? null,
            'delivery' => 'accepted',
        ];
    }
}
