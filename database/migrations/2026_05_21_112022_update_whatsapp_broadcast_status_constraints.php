<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        match (DB::getDriverName()) {
            'pgsql' => $this->updatePostgresConstraints(),
            'mysql', 'mariadb' => $this->updateMysqlEnums(),
            default => null,
        };
    }

    public function down(): void
    {
        match (DB::getDriverName()) {
            'pgsql' => $this->rollbackPostgresConstraints(),
            'mysql', 'mariadb' => $this->rollbackMysqlEnums(),
            default => null,
        };
    }

    protected function updatePostgresConstraints(): void
    {
        DB::statement('ALTER TABLE whatsapp_broadcasts DROP CONSTRAINT IF EXISTS whatsapp_broadcasts_status_check');
        DB::statement("ALTER TABLE whatsapp_broadcasts ADD CONSTRAINT whatsapp_broadcasts_status_check CHECK (status::text = ANY (ARRAY['draft'::character varying, 'scheduled'::character varying, 'sending'::character varying, 'paused'::character varying, 'completed'::character varying, 'failed'::character varying, 'cancelled'::character varying]::text[]))");

        DB::statement('ALTER TABLE whatsapp_broadcast_recipients DROP CONSTRAINT IF EXISTS whatsapp_broadcast_recipients_status_check');
        DB::statement("ALTER TABLE whatsapp_broadcast_recipients ADD CONSTRAINT whatsapp_broadcast_recipients_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'queued'::character varying, 'sending'::character varying, 'sent'::character varying, 'delivered'::character varying, 'read'::character varying, 'replied'::character varying, 'failed'::character varying]::text[]))");
    }

    protected function rollbackPostgresConstraints(): void
    {
        DB::table('whatsapp_broadcast_recipients')->where('status', 'pending')->update(['status' => 'queued']);
        DB::table('whatsapp_broadcasts')->where('status', 'paused')->update(['status' => 'draft']);

        DB::statement('ALTER TABLE whatsapp_broadcast_recipients DROP CONSTRAINT IF EXISTS whatsapp_broadcast_recipients_status_check');
        DB::statement("ALTER TABLE whatsapp_broadcast_recipients ADD CONSTRAINT whatsapp_broadcast_recipients_status_check CHECK (status::text = ANY (ARRAY['queued'::character varying, 'sending'::character varying, 'sent'::character varying, 'delivered'::character varying, 'read'::character varying, 'replied'::character varying, 'failed'::character varying]::text[]))");

        DB::statement('ALTER TABLE whatsapp_broadcasts DROP CONSTRAINT IF EXISTS whatsapp_broadcasts_status_check');
        DB::statement("ALTER TABLE whatsapp_broadcasts ADD CONSTRAINT whatsapp_broadcasts_status_check CHECK (status::text = ANY (ARRAY['draft'::character varying, 'scheduled'::character varying, 'sending'::character varying, 'completed'::character varying, 'failed'::character varying, 'cancelled'::character varying]::text[]))");
    }

    protected function updateMysqlEnums(): void
    {
        DB::statement("ALTER TABLE whatsapp_broadcasts MODIFY status ENUM('draft','scheduled','sending','paused','completed','failed','cancelled') DEFAULT 'draft'");
        DB::statement("ALTER TABLE whatsapp_broadcast_recipients MODIFY status ENUM('pending','queued','sending','sent','delivered','read','replied','failed') DEFAULT 'queued'");
    }

    protected function rollbackMysqlEnums(): void
    {
        DB::table('whatsapp_broadcast_recipients')->where('status', 'pending')->update(['status' => 'queued']);
        DB::table('whatsapp_broadcasts')->where('status', 'paused')->update(['status' => 'draft']);

        DB::statement("ALTER TABLE whatsapp_broadcast_recipients MODIFY status ENUM('queued','sending','sent','delivered','read','replied','failed') DEFAULT 'queued'");
        DB::statement("ALTER TABLE whatsapp_broadcasts MODIFY status ENUM('draft','scheduled','sending','completed','failed','cancelled') DEFAULT 'draft'");
    }
};
