<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SmsGateway
{
    public function send(string $phone, string $message): void
    {
        $driver = config('services.sms.driver', 'log');

        if ($driver === 'log') {
            Log::info('SMS BBH Farm', ['to' => $phone, 'message' => $message]);

            return;
        }

        if ($driver !== 'twilio') {
            throw new RuntimeException('Driver SMS belum dikonfigurasi.');
        }

        $sid = config('services.sms.twilio.sid');
        $token = config('services.sms.twilio.token');
        $from = config('services.sms.twilio.from');

        if (! is_string($sid) || ! is_string($token) || ! is_string($from) || $sid === '' || $token === '' || $from === '') {
            throw new RuntimeException('Kredensial SMS Twilio belum lengkap.');
        }

        $response = Http::asForm()
            ->withBasicAuth($sid, $token)
            ->timeout(15)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => $phone,
                'Body' => $message,
            ]);

        if (! $response->successful()) {
            Log::warning('SMS Twilio gagal dikirim.', ['status' => $response->status(), 'body' => $response->json()]);

            throw new RuntimeException('SMS verifikasi tidak dapat dikirim.');
        }
    }

    public function normalize(string $phone): string
    {
        $value = preg_replace('/[\s().-]+/', '', $phone) ?? '';

        if (str_starts_with($value, '00')) {
            $value = '+'.substr($value, 2);
        } elseif (str_starts_with($value, '0')) {
            $value = '+62'.substr($value, 1);
        } elseif (str_starts_with($value, '62')) {
            $value = '+'.$value;
        }

        if (! preg_match('/^\+[1-9][0-9]{7,14}$/', $value)) {
            throw new RuntimeException('Format nomor telepon tidak valid. Gunakan nomor seluler yang aktif.');
        }

        return $value;
    }
}
