<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->shouldRunForPostgres()) {
            return;
        }

        $allowedTypes = $this->allowedMessageTypes();

        DB::statement('ALTER TABLE whatsapp_messages DROP CONSTRAINT IF EXISTS whatsapp_messages_message_type_check');
        DB::statement(sprintf(
            "ALTER TABLE whatsapp_messages ADD CONSTRAINT whatsapp_messages_message_type_check CHECK (message_type IN (%s)) NOT VALID",
            $this->quotedList($allowedTypes)
        ));
    }

    public function down(): void
    {
        if (! $this->shouldRunForPostgres()) {
            return;
        }

        DB::statement('ALTER TABLE whatsapp_messages DROP CONSTRAINT IF EXISTS whatsapp_messages_message_type_check');
        DB::statement("ALTER TABLE whatsapp_messages ADD CONSTRAINT whatsapp_messages_message_type_check CHECK (message_type IN ('inbound', 'outbound')) NOT VALID");
    }

    private function shouldRunForPostgres(): bool
    {
        return DB::getDriverName() === 'pgsql'
            && Schema::hasTable('whatsapp_messages')
            && Schema::hasColumn('whatsapp_messages', 'message_type');
    }

    /**
     * @return array<int, string>
     */
    private function allowedMessageTypes(): array
    {
        return [
            'inbound',
            'outbound',
            'text',
            'template',
            'interactive',
            'image',
            'video',
            'audio',
            'document',
            'sticker',
            'location',
            'contacts',
            'unknown',
        ];
    }

    /**
     * @param array<int, string> $values
     */
    private function quotedList(array $values): string
    {
        return collect($values)
            ->map(fn (string $value): string => DB::getPdo()->quote($value))
            ->implode(', ');
    }
};
