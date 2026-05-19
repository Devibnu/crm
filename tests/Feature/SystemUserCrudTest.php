<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SystemUserCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created_with_role_and_verified_status(): void
    {
        $response = $this->post(route('admin.system.users.store'), [
            'name' => 'System User Create',
            'email' => 'system-user-create@example.com',
            'role' => 'marketing',
            'is_verified' => '1',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('admin.system.users.index'));

        $user = User::query()->where('email', 'system-user-create@example.com')->firstOrFail();

        $this->assertSame('System User Create', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('marketing'));
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_user_show_and_edit_pages_are_accessible(): void
    {
        $user = User::factory()->create([
            'name' => 'Accessible User',
            'email' => 'accessible-user@example.com',
        ]);
        $user->assignRole('support');

        $this->get(route('admin.system.users.show', $user))
            ->assertOk()
            ->assertSee('Accessible User')
            ->assertSee('support');

        $this->get(route('admin.system.users.edit', $user))
            ->assertOk()
            ->assertSee('Edit User');
    }

    public function test_user_can_be_updated_with_new_role_and_password(): void
    {
        $user = User::factory()->create([
            'name' => 'Before Update User',
            'email' => 'before-update-user@example.com',
        ]);
        $user->assignRole('sales');

        $response = $this->put(route('admin.system.users.update', $user), [
            'name' => 'After Update User',
            'email' => 'after-update-user@example.com',
            'role' => 'support',
            'is_verified' => '0',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('admin.system.users.show', $user));

        $user->refresh();

        $this->assertSame('After Update User', $user->name);
        $this->assertSame('after-update-user@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('support'));
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_user_role_can_be_updated_from_listing(): void
    {
        $user = User::factory()->create([
            'name' => 'Role Update User',
            'email' => 'role-update-user@example.com',
        ]);
        $user->assignRole('sales');

        $this->put(route('admin.system.users.role.update', $user), [
            'role' => 'marketing',
        ])->assertRedirect(route('admin.system.users.index'));

        $user->refresh();

        $this->assertTrue($user->hasRole('marketing'));
        $this->assertFalse($user->hasRole('sales'));
    }

    public function test_user_search_can_find_role_name(): void
    {
        $matchingUser = User::factory()->create([
            'name' => 'Support Search User',
            'email' => 'support-search@example.com',
        ]);
        $matchingUser->assignRole('support');

        $otherUser = User::factory()->create([
            'name' => 'Sales Search User',
            'email' => 'sales-search@example.com',
        ]);
        $otherUser->assignRole('sales');

        $this->get(route('admin.system.users.index', ['q' => 'support']))
            ->assertOk()
            ->assertSee('Support Search User')
            ->assertDontSee('Sales Search User');
    }

    public function test_current_user_cannot_be_deleted(): void
    {
        $currentUser = User::query()->findOrFail(auth()->id());

        $this->delete(route('admin.system.users.destroy', $currentUser))
            ->assertRedirect(route('admin.system.users.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'id' => $currentUser->id,
        ]);
    }

    public function test_last_super_admin_cannot_be_downgraded(): void
    {
        $currentUser = User::query()->findOrFail(auth()->id());

        $this->put(route('admin.system.users.role.update', $currentUser), [
            'role' => 'admin',
        ])->assertRedirect(route('admin.system.users.index'))
            ->assertSessionHas('error');

        $currentUser->refresh();

        $this->assertTrue($currentUser->hasRole('super_admin'));
    }
}
