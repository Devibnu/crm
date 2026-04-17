<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('module_permissions')->nullable()->after('role');
        });

        $defaultPermissions = [
            'admin' => [
                'customers' => 'full',
                'tickets' => 'full',
                'inbox' => 'full',
                'whatsapp' => 'full',
            ],
            'maintainer' => [
                'customers' => 'manage',
                'tickets' => 'manage',
                'inbox' => 'manage',
                'whatsapp' => 'manage',
            ],
            'author' => [
                'customers' => 'view',
                'tickets' => 'manage',
                'inbox' => 'handle',
                'whatsapp' => 'handle',
            ],
            'editor' => [
                'customers' => 'view',
                'tickets' => 'handle',
                'inbox' => 'handle',
                'whatsapp' => 'view',
            ],
            'subscriber' => [
                'customers' => 'view',
                'tickets' => 'view',
                'inbox' => 'view',
                'whatsapp' => 'view',
            ],
        ];

        DB::table('users')->select(['id', 'role'])->orderBy('id')->each(function (object $user) use ($defaultPermissions) {
            $permissions = $defaultPermissions[$user->role] ?? $defaultPermissions['subscriber'];

            DB::table('users')
                ->where('id', $user->id)
                ->update(['module_permissions' => json_encode($permissions, JSON_THROW_ON_ERROR)]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('module_permissions');
        });
    }
};