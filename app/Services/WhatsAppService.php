<?php

namespace App\Services;

use App\Exceptions\WhatsAppDeliveryException;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $provider;
    private ?string $apiUrl;
    private ?string $apiToken;

    public function __construct()
    {
        $this->provider  = config('otp.whatsapp.provider', 'fonnte');
        $this->apiUrl    = config('otp.whatsapp.api_url');
        $this->apiToken  = config('otp.whatsapp.api_token');
    }

    /**
     * Generate OTP and send it via WhatsApp.
     *
     * @param  User   $user
     * @param  string $phoneNumber  International format e.g. +6281234567890
     * @return OtpCode
     *
     * @throws WhatsAppDeliveryException
     */
    public function sendOtp(User $user, string $phoneNumber): OtpCode
    {
        /** @var OtpService $otpService */
        $otpService = app(OtpService::class);
        $otp = $otpService->generate($user, 'whatsapp');

        $message = $this->buildMessage($otp->code);

        $this->send($phoneNumber, $message);

        // Persist phone number on user if not set
        if (! $user->whatsapp_number) {
            $user->update(['whatsapp_number' => $phoneNumber]);
        }

        return $otp;
    }

    /**
     * Dispatch the HTTP request to the WhatsApp provider.
     *
     * @throws WhatsAppDeliveryException
     */
    private function send(string $to, string $message): void
    {
        try {
            $response = match ($this->provider) {
                'fonnte'   => $this->sendViaFonnte($to, $message),
                'twilio'   => $this->sendViaTwilio($to, $message),
                'webhook'  => $this->sendViaWebhook($to, $message),
                'wasender' => $this->sendViaWasender($to, $message),
                'saungwa'  => $this->sendViaSaungwa($to, $message),
                default    => throw new WhatsAppDeliveryException("Provider '{$this->provider}' tidak didukung."),
            };

            if (! $response->successful()) {
                Log::error('WhatsApp send failed', [
                    'provider' => $this->provider,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);
                throw new WhatsAppDeliveryException('Gagal mengirim OTP WhatsApp. Coba kembali.');
            }
        } catch (WhatsAppDeliveryException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('WhatsApp HTTP exception', ['error' => $e->getMessage()]);
            throw new WhatsAppDeliveryException('Terjadi kesalahan saat menghubungi WhatsApp API.');
        }
    }

    // ─── Provider Adapters ────────────────────────────────────────────────────

    private function sendViaFonnte(string $to, string $message)
    {
        return Http::withHeaders([
            'Authorization' => $this->apiToken,
        ])->post($this->apiUrl, [
            'target'  => $to,
            'message' => $message,
        ]);
    }

    private function sendViaWebhook(string $to, string $message)
    {
        return Http::post($this->apiUrl, [
            'target'  => $to,
            'message' => $message,
            'provider' => 'webhook_test'
        ]);
    }

    private function sendViaTwilio(string $to, string $message)
    {
        // Twilio uses Basic Auth with Account SID + Auth Token
        // TWILIO_ACCOUNT_SID and TWILIO_AUTH_TOKEN from .env
        $accountSid = config('otp.whatsapp.twilio_sid');
        $authToken  = config('otp.whatsapp.twilio_token');
        $from       = config('otp.whatsapp.twilio_from');

        return Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => "whatsapp:{$from}",
                'To'   => "whatsapp:{$to}",
                'Body' => $message,
            ]);
    }

    private function sendViaWasender(string $to, string $message)
    {
        // WASenderApi Implementation based on User's API specification
        $url = $this->apiUrl ?: 'https://wasenderapi.com/api/send-message';
        
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiToken,
        ])->post($url, [
            'to'   => $to,
            'text' => $message,
        ]);
    }

    /**
     * Saung WA API implementation.
     * Docs: https://saungwa.com (dashboard → API Doc)
     *
     * Auth: appkey + authkey (form-data style, not Bearer token)
     * Endpoint: POST https://app.saungwa.com/api/create-message
     */
    private function sendViaSaungwa(string $to, string $message)
    {
        $url     = $this->apiUrl ?: 'https://app.saungwa.com/api/create-message';
        $appKey  = config('otp.whatsapp.saungwa_appkey');
        $authKey = config('otp.whatsapp.saungwa_authkey');

        if (empty($appKey) || empty($authKey)) {
            throw new WhatsAppDeliveryException(
                'Saung WA credentials belum diisi. Set SAUNGWA_APP_KEY & SAUNGWA_AUTH_KEY di .env'
            );
        }

        return Http::asMultipart()->post($url, [
            ['name' => 'appkey',  'contents' => $appKey],
            ['name' => 'authkey', 'contents' => $authKey],
            ['name' => 'to',      'contents' => $to],
            ['name' => 'message', 'contents' => $message],
            ['name' => 'sandbox', 'contents' => 'false'],
        ]);
    }

    private function buildMessage(string $code): string
    {
        $expiry = config('otp.expiry_minutes', 10);
        return __('messages.wa_otp_message', ['code' => $code, 'expiry' => $expiry]);
    }
}
