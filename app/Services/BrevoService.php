<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.brevo.com/v3';

    public function __construct()
    {
        $this->apiKey = config('services.brevo.api_key', '');
    }

    /**
     * Kirim transactional email menggunakan template Brevo.
     *
     * @param  int    $templateId  ID template di Brevo
     * @param  string $toEmail     Email penerima
     * @param  string $toName      Nama penerima
     * @param  array  $params      Parameter untuk template (e.g. ['name' => 'John', 'OTP' => '123456'])
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function sendTemplateEmail(int $templateId, string $toEmail, string $toName, array $params = []): bool
    {
        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post("{$this->baseUrl}/smtp/email", [
            'templateId' => $templateId,
            'to' => [
                [
                    'email' => $toEmail,
                    'name'  => $toName,
                ],
            ],
            'params' => $params,
        ]);

        if ($response->successful()) {
            Log::info('Brevo template email sent', [
                'template_id' => $templateId,
                'to'          => $toEmail,
                'message_id'  => $response->json('messageId'),
            ]);
            return true;
        }

        Log::error('Brevo template email failed', [
            'template_id' => $templateId,
            'to'          => $toEmail,
            'status'      => $response->status(),
            'error'       => $response->json(),
        ]);

        throw new \RuntimeException(
            'Failed to send Brevo template email: ' . ($response->json('message') ?? 'Unknown error')
        );
    }

    /**
     * Kirim email dengan konten HTML custom (tanpa template ID).
     */
    public function sendHtmlEmail(string $subject, string $htmlContent, string $toEmail, string $toName): bool
    {
        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post("{$this->baseUrl}/smtp/email", [
            'sender' => [
                'name' => config('mail.from.name', 'ConnectX Official'),
                'email' => config('mail.from.address', 'info@getconnectx.app')
            ],
            'to' => [
                [
                    'email' => $toEmail,
                    'name'  => $toName,
                ],
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
        ]);

        if ($response->successful()) {
            Log::info('Brevo HTML email sent', [
                'subject'    => $subject,
                'to'         => $toEmail,
                'message_id' => $response->json('messageId'),
            ]);
            return true;
        }

        Log::error('Brevo HTML email failed', [
            'subject' => $subject,
            'to'      => $toEmail,
            'status'  => $response->status(),
            'error'   => $response->json(),
        ]);

        throw new \RuntimeException(
            'Failed to send Brevo HTML email: ' . ($response->json('message') ?? 'Unknown error')
        );
    }
}
