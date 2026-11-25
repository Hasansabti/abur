<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\Booking;
use App\Models\BookingMessageLog;
use App\Models\WhatsappLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class WhatsAppShareService
{
    public function buildManualShare(Booking $booking, string $phone, string $message): array
    {
        $phone = app(SmsService::class)->normalizePhone($phone);
        $url = 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);

        return [
            'mode' => 'manual',
            'url' => $url,
            'phone' => $phone,
            'message' => $message,
            'counts_against_quota' => false,
        ];
    }

    public function sendApi(Booking $booking, string $phone, string $message, ?string $templateKey = null, ?string $idempotencyKey = null): BookingMessageLog
    {
        $phone = app(SmsService::class)->normalizePhone($phone);
        $agencyId = $booking->agency_id ?? $booking->trip?->agency_id;
        $provider = config('services.whatsapp.provider');
        $token = config('services.whatsapp.token');

        if ($idempotencyKey) {
            $existing = BookingMessageLog::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        if (!$provider || !$token) {
            throw ValidationException::withMessages([
                'whatsapp' => 'مزود واتساب غير معدّ. يمكنك استخدام المشاركة اليدوية أو ضبط WHATSAPP_PROVIDER و WHATSAPP_TOKEN.',
            ]);
        }

        return DB::transaction(function () use ($booking, $phone, $message, $templateKey, $idempotencyKey, $agencyId, $provider, $token) {
            $log = BookingMessageLog::create([
                'booking_id' => $booking->id,
                'agency_id' => $agencyId,
                'channel' => 'whatsapp',
                'template_key' => $templateKey,
                'customer_name' => $booking->customer_name,
                'phone' => $phone,
                'message' => $message,
                'status' => 'queued',
                'counts_against_quota' => true,
                'sent_by' => BookingChangeLogger::actor()['name'],
                'idempotency_key' => $idempotencyKey,
            ]);

            try {
                $endpoint = config('services.whatsapp.endpoint') ?: 'https://graph.facebook.com/v20.0/' . config('services.whatsapp.phone_number_id') . '/messages';
                $response = Http::timeout(20)->withToken($token)->post($endpoint, [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'text',
                    'text' => ['body' => $message],
                ]);

                if (!$response->successful()) {
                    $log->update([
                        'status' => 'failed',
                        'failure_reason' => 'رفض المزود: ' . $response->status(),
                    ]);
                    throw ValidationException::withMessages(['whatsapp' => 'فشل إرسال رسالة واتساب من المزود.']);
                }

                $json = $response->json() ?? [];
                $log->update([
                    'status' => 'accepted',
                    'provider_message_id' => $json['messages'][0]['id'] ?? null,
                    'delivery_status' => 'accepted',
                    'sent_at' => now(),
                ]);

                WhatsappLog::create([
                    'agency_id' => $agencyId,
                    'booking_id' => $booking->id,
                    'message_type' => $templateKey ?: 'share',
                    'sent_at' => now(),
                ]);
            } catch (ValidationException $e) {
                throw $e;
            } catch (\Throwable $e) {
                Log::error('WhatsApp API send failed', ['error' => $e->getMessage(), 'provider' => $provider]);
                $log->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
                throw ValidationException::withMessages(['whatsapp' => 'تعذر الاتصال بمزود واتساب.']);
            }

            BookingChangeLogger::log($booking, 'send_whatsapp', [], [
                'phone' => $phone,
                'status' => $log->status,
            ], 'message', $log->id);

            return $log->fresh();
        });
    }

    public function renderShareMessage(Booking $booking, string $link): string
    {
        $tpl = config('booking.whatsapp_templates.booking_share');
        $paid = (float) ($booking->cash_paid ?? 0);
        $total = (float) $booking->total_amount;
        $vars = [
            'name' => $booking->customer_name,
            'booking' => 'BK-' . $booking->id,
            'trip' => $booking->trip?->title ?? '',
            'total' => number_format($total, 2),
            'paid' => number_format($paid, 2),
            'remaining' => number_format(max(0, $total - $paid), 2),
            'link' => $link,
        ];
        foreach ($vars as $k => $v) {
            $tpl = str_replace(':' . $k, (string) $v, $tpl);
        }

        return $tpl;
    }
}
