<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class OtpMailService
{
    public function send(string $email, string $otp, ?string $purpose = null): void
    {
        if ($purpose === 'reset') {
            $subject = 'إعادة تعيين كلمة المرور - توصيلة برو';
            $body = "رمز إعادة تعيين كلمة المرور: {$otp}\n\nصالح لمدة 10 دقائق.";
        } else {
            $subject = 'رمز التحقق - توصيلة برو';
            $body = "رمز التحقق الخاص بك في توصيلة برو: {$otp}\n\nصالح لمدة 10 دقائق.";
        }

        $apiKey = config('services.brevo.key');
        if (!empty($apiKey)) {
            $this->sendViaBrevo($email, $subject, $body, $apiKey);
            return;
        }

        $this->sendViaSmtp($email, $subject, $body);
    }

    private function sendViaBrevo(
        string $email,
        string $subject,
        string $body,
        string $apiKey
    ): void {
        $fromEmail = config('mail.from.address');
        $fromName = config('mail.from.name', 'Motorent');

        if (empty($fromEmail)) {
            throw new RuntimeException('MAIL_FROM_ADDRESS is not configured');
        }

        $response = Http::timeout(20)
            ->withHeaders([
                'api-key' => $apiKey,
                'Content-Type' => 'application/json',
                'accept' => 'application/json',
            ])
            ->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => [
                    'name' => $fromName,
                    'email' => $fromEmail,
                ],
                'to' => [
                    ['email' => $email],
                ],
                'subject' => $subject,
                'textContent' => $body,
            ]);

        if (!$response->successful()) {
            Log::error('Brevo OTP email failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Brevo mail request failed');
        }
    }

    private function sendViaSmtp(string $email, string $subject, string $body): void
    {
        $send = function () use ($email, $subject, $body) {
            Mail::raw($body, function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });
        };

        try {
            $send();
        } catch (\Throwable $firstError) {
            $port = (int) config('mail.mailers.smtp.port', 587);
            if ($port !== 587) {
                throw $firstError;
            }

            config([
                'mail.mailers.smtp.port' => 465,
                'mail.mailers.smtp.encryption' => 'ssl',
            ]);
            Mail::purge('smtp');

            $send();
        }
    }
}
