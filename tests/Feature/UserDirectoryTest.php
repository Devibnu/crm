<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_directory_requires_sanctum_authentication(): void
    {
        $this->getJson('/api/users?role=sales')->assertUnauthorized();
    }

    public function test_user_directory_returns_active_users_filtered_by_role(): void
    {
        $actor = User::factory()->create([
            'full_name' => 'Zaki Sales',
            'email' => 'sales.actor@example.com',
            'role' => 'sales',
            'module_permissions' => User::defaultModulePermissionsForRole('sales'),
        ]);

        Sanctum::actingAs($actor);

        $includedSalesUser = User::factory()->create([
            'full_name' => 'Ayu Sales',
            'email' => 'ayu.sales@example.com',
            'role' => 'sales',
            'status' => 'active',
        ]);

        User::factory()->create([
            'full_name' => 'Bima Sales',
            'email' => 'bima.sales@example.com',
            'role' => 'sales',
            'status' => 'inactive',
        ]);

        User::factory()->create([
            'full_name' => 'Citra Marketing',
            'email' => 'citra.marketing@example.com',
            'role' => 'marketing',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/users?role=sales');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $includedSalesUser->id,
                'fullName' => 'Ayu Sales',
                'email' => 'ayu.sales@example.com',
                'role' => 'sales',
            ]);

        $returnedEmails = collect($response->json('data'))->pluck('email')->all();

        $this->assertSame([
            $includedSalesUser->email,
            $actor->email,
        ], $returnedEmails);
    }
}