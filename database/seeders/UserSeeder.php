<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->createDemoUser([
            'full_name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'admin@demo.com',
            'password' => 'admin',
            'avatar' => '/images/avatars/avatar-1.png',
            'role' => 'admin',
            'module_permissions' => User::defaultModulePermissionsForRole('admin'),
        ]);

        $this->createDemoUser([
            'full_name' => 'Rina Observer',
            'username' => 'rinaobserver',
            'email' => 'observer@demo.com',
            'password' => 'observer',
            'avatar' => '/images/avatars/avatar-2.png',
            'role' => 'subscriber',
            'module_permissions' => [
                'customers' => 'view',
                'tickets' => 'view',
                'inbox' => 'view',
                'whatsapp' => 'view',
                'invoice' => 'view',
            ],
        ]);

        $this->createDemoUser([
            'full_name' => 'Fikri Inbox',
            'username' => 'fikriinbox',
            'email' => 'inbox-operator@demo.com',
            'password' => 'inbox',
            'avatar' => '/images/avatars/avatar-3.png',
            'role' => 'editor',
            'module_permissions' => [
                'customers' => 'view',
                'tickets' => 'handle',
                'inbox' => 'handle',
                'whatsapp' => 'handle',
                'invoice' => 'view',
            ],
        ]);

        $this->createDemoUser([
            'full_name' => 'Salsa Customer Admin',
            'username' => 'salsacustomeradmin',
            'email' => 'customer-admin@demo.com',
            'password' => 'customer',
            'avatar' => '/images/avatars/avatar-4.png',
            'role' => 'maintainer',
            'module_permissions' => [
                'customers' => 'manage',
                'tickets' => 'view',
                'inbox' => 'view',
                'whatsapp' => 'view',
                'invoice' => 'view',
            ],
        ]);

        $this->createDemoUser([
            'full_name' => 'Bima Finance Admin',
            'username' => 'bimafinanceadmin',
            'email' => 'finance-admin@demo.com',
            'password' => 'finance',
            'avatar' => '/images/avatars/avatar-5.png',
            'role' => 'maintainer',
            'module_permissions' => [
                'customers' => 'view',
                'tickets' => 'view',
                'inbox' => 'view',
                'whatsapp' => 'view',
                'invoice' => 'manage',
            ],
        ]);

        $this->createDemoUser([
            'full_name' => 'Mira Marketing',
            'username' => 'miramarketing',
            'email' => 'marketing@demo.com',
            'password' => 'marketing',
            'avatar' => '/images/avatars/avatar-6.png',
            'role' => 'marketing',
            'module_permissions' => User::defaultModulePermissionsForRole('marketing'),
        ]);

        $this->createDemoUser([
            'full_name' => 'Seno Sales',
            'username' => 'senosales',
            'email' => 'sales@demo.com',
            'password' => 'sales',
            'avatar' => '/images/avatars/avatar-7.png',
            'role' => 'sales',
            'module_permissions' => User::defaultModulePermissionsForRole('sales'),
        ]);

        $this->createDemoUser([
            'full_name' => 'Sari Service',
            'username' => 'sariservice',
            'email' => 'service@demo.com',
            'password' => 'service',
            'avatar' => '/images/avatars/avatar-8.png',
            'role' => 'service',
            'module_permissions' => User::defaultModulePermissionsForRole('service'),
        ]);
    }

    private function createDemoUser(array $attributes): void
    {
        $user = User::query()->firstOrNew([
            'email' => $attributes['email'],
        ]);

        $user->fill(array_merge([
            'company' => 'Pixinvent',
            'country' => 'USA',
            'contact' => '(123) 456-7890',
            'current_plan' => 'enterprise',
            'status' => 'active',
            'billing' => 'Auto Debit',
            'task_done' => 1230,
            'project_done' => 568,
            'tax_id' => 'Tax-8894',
            'language' => 'English',
        ], $attributes));

        $user->save();
    }
}
