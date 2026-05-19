<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\MenuSeeder;
use Database\Seeders\RoleMenuSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('roles') || ! Schema::hasTable('users')) {
            return;
        }

        $this->seed(RolePermissionSeeder::class);

        if (Schema::hasTable('menus') && Schema::hasTable('role_menu')) {
            $this->seed(MenuSeeder::class);
            $this->seed(RoleMenuSeeder::class);
        }

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user);
    }
}
