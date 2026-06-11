<?php

namespace App\Services\WhatsApp;

interface WhatsAppServiceInterface
{
    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function sendMessage(string $phone, string $message, array $options = []): array;

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function sendMediaMessage(string $phone, string $filePath, string $mediaType, array $options = []): array;

    /**
     * @param array<int, array<string, mixed>> $recipients
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function sendBroadcast(array $recipients, array $payload): array;

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function validateWebhook(array $payload): array;
}
