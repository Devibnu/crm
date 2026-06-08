<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class WhatsAppTemplateBuilder
{
    public const EXAMPLES = [
        'nama' => 'Ibnu',
        'no_hp' => '6281234567890',
        'email' => 'user@example.com',
        'kode' => 'ABC123',
        'otp' => '123456',
        'no_order' => 'INV-001',
        'tanggal' => '10 Juni 2026',
    ];

    private const PROMO_WORDS = [
        'promo', 'diskon', 'gratis', 'sale', 'murah', 'voucher', 'cashback',
        'beli sekarang', 'penawaran', 'limited', 'flash sale', 'bonus', 'hadiah',
    ];

    public function safeName(string $name): string
    {
        $safe = Str::snake(Str::ascii(mb_strtolower(trim($name))));
        $safe = preg_replace('/[^a-z0-9_]+/', '_', $safe) ?: '';
        $safe = preg_replace('/_+/', '_', $safe) ?: '';

        return trim($safe, '_');
    }

    /**
     * @return array{meta_text:string, mapping:array<string, string>, examples:array<int, string>}
     */
    public function convertVariables(string $body): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/', $body, $matches);
        $variables = [];

        foreach ($matches[1] ?? [] as $variable) {
            if (! in_array($variable, $variables, true)) {
                $variables[] = $variable;
            }
        }

        $metaText = $body;
        $mapping = [];
        $examples = [];

        foreach ($variables as $index => $variable) {
            $number = (string) ($index + 1);
            $mapping[$number] = $variable;
            $examples[] = self::EXAMPLES[$variable] ?? ucfirst(str_replace('_', ' ', $variable));
            $metaText = preg_replace('/\{\{\s*'.preg_quote($variable, '/').'\s*\}\}/', '{{'.$number.'}}', $metaText) ?: $metaText;
        }

        return [
            'meta_text' => $metaText,
            'mapping' => $mapping,
            'examples' => $examples,
        ];
    }

    /**
     * @return array{level:string, score:int, reasons:array<int, string>, blocking:bool}
     */
    public function readiness(string $category, string $body): array
    {
        $reasons = [];
        $blocking = false;
        $lowerBody = mb_strtolower($body);

        if ($category === 'UTILITY') {
            foreach (self::PROMO_WORDS as $word) {
                if (str_contains($lowerBody, $word)) {
                    $reasons[] = 'Template Utility tidak boleh berisi promosi. Gunakan kategori Marketing atau ubah kalimat agar informatif.';
                    $blocking = true;
                    break;
                }
            }
        }

        if ($category === 'AUTHENTICATION' && ! preg_match('/\b(kode|otp|verifikasi|autentikasi)\b/i', $body)) {
            $reasons[] = 'Template Authentication hanya untuk OTP/kode/verifikasi.';
            $blocking = true;
        }

        if (preg_match('/https?:\/\/|www\./i', $body)) {
            $reasons[] = 'Template baru tidak boleh berisi link.';
            $blocking = true;
        }

        if ($this->emojiCount($body) > 2) {
            $reasons[] = 'Emoji maksimal 2 agar template tetap aman.';
            $blocking = true;
        }

        $converted = $this->convertVariables($body);

        foreach ($converted['mapping'] as $variable) {
            if (! array_key_exists($variable, self::EXAMPLES)) {
                $reasons[] = "Variable {$variable} belum punya contoh otomatis.";
                $blocking = true;
            }
        }

        if (mb_strlen($body) > 800) {
            $reasons[] = 'Body terlalu panjang. Idealnya 1-3 paragraf pendek.';
        }

        $score = max(0, 100 - count($reasons) * 25 - ($blocking ? 25 : 0));
        $level = $blocking ? 'Risky' : ($score >= 80 ? 'High Approval Chance' : 'Medium');

        return compact('level', 'score', 'reasons', 'blocking');
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(array $data): array
    {
        $converted = $this->convertVariables((string) $data['body']);
        $components = [];

        if (trim((string) ($data['header'] ?? '')) !== '') {
            $components[] = [
                'type' => 'HEADER',
                'format' => 'TEXT',
                'text' => $data['header'],
            ];
        }

        $body = [
            'type' => 'BODY',
            'text' => $converted['meta_text'],
        ];

        if ($converted['examples'] !== []) {
            $body['example'] = [
                'body_text' => [
                    $converted['examples'],
                ],
            ];
        }

        $components[] = $body;

        if (trim((string) ($data['footer'] ?? '')) !== '') {
            $components[] = [
                'type' => 'FOOTER',
                'text' => $data['footer'],
            ];
        }

        return [
            'name' => $data['safe_name'],
            'language' => $data['language'],
            'category' => $data['category'],
            'components' => $components,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function submitToMeta(WhatsAppProvider $provider, array $data): array
    {
        $endpoint = rtrim((string) ($provider->api_url ?: 'https://graph.facebook.com'), '/')
            .'/'.trim((string) ($provider->graph_api_version ?: 'v23.0'), '/')
            .'/'.trim((string) $provider->business_account_id)
            .'/message_templates';
        $payload = $this->payload($data);
        $response = Http::withToken((string) $provider->api_token)
            ->timeout(20)
            ->post($endpoint, $payload);

        if (! $response->successful()) {
            $raw = $response->json() ?? [];
            $message = data_get($raw, 'error.message')
                ?? data_get($raw, 'error.error_data.details')
                ?? "Meta Graph API gagal dengan HTTP {$response->status()}.";
            $code = data_get($raw, 'error.code');
            $type = data_get($raw, 'error.type');

            if ((string) $code === '190') {
                $message = 'Token Meta telah kedaluwarsa. Silakan perbarui Access Token pada System -> WhatsApp Providers.';
            }

            throw new RuntimeException("{$message} Provider: {$provider->name}. WABA ID: {$provider->business_account_id}. Endpoint: {$endpoint}. Error code: {$code}. Error type: {$type}.");
        }

        return [
            'payload' => $payload,
            'raw' => $response->json() ?? [],
            'template_id' => $response->json('id'),
        ];
    }

    private function emojiCount(string $value): int
    {
        preg_match_all('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $value, $matches);

        return count($matches[0] ?? []);
    }
}
