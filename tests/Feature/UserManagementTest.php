<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_crud_pages_and_actions_are_available(): void
    {
        $user = User::factory()->create();
        $user->assignRole('sales');

        $this->get(route('admin.system.users.index'))
            ->assertOk()
            ->assertSee('Create User')
            ->assertSee(route('admin.system.users.show', $user), false)
            ->assertSee(route('admin.system.users.edit', $user), false)
            ->assertSee(route('admin.system.users.destroy', $user), false);

        $this->get(route('admin.system.users.create'))
            ->assertOk()
            ->assertSee('Create User');

        $this->get(route('admin.system.users.edit', $user))
            ->assertOk()
            ->assertSee('Edit User');
    }

    public function test_user_can_be_created_with_hashed_password_and_role(): void
    {
        $response = $this->post(route('admin.system.users.store'), [
            'name' => 'New CRM User',
            'email' => 'new-user@krakatau.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'role' => 'sales',
        ]);

        $user = User::query()->where('email', 'new-user@krakatau.test')->firstOrFail();

        $response->assertRedirect(route('admin.system.users.show', $user));
        $this->assertTrue(Hash::check('secure-password', $user->password));
        $this->assertTrue($user->hasRole('sales'));
    }

    public function test_user_can_be_created_with_default_password_when_password_is_empty(): void
    {
        $this->post(route('admin.system.users.store'), [
            'name' => 'Default Password User',
            'email' => 'default-password@krakatau.test',
            'password' => '',
            'password_confirmation' => '',
            'role' => 'support',
        ])->assertSessionHas('success', 'User berhasil dibuat.')
            ->assertSessionHas('default_password', 'KrakatauCRM@123');

        $user = User::query()->where('email', 'default-password@krakatau.test')->firstOrFail();

        $this->assertTrue(Hash::check('KrakatauCRM@123', $user->password));
        $this->assertTrue($user->hasRole('support'));

        $this->get(route('admin.system.users.show', $user))
            ->assertOk()
            ->assertSee('User berhasil dibuat.')
            ->assertSee('Password default: KrakatauCRM@123');
    }

    public function test_user_can_be_updated_and_assigned_a_new_role_without_changing_password(): void
    {
        $user = User::factory()->create([
            'name' => 'Before Update',
            'email' => 'before@krakatau.test',
            'password' => Hash::make('original-password'),
        ]);
        $user->assignRole('sales');
        $originalPassword = $user->password;

        $this->put(route('admin.system.users.update', $user), [
            'name' => 'After Update',
            'email' => 'after@krakatau.test',
            'password' => '',
            'password_confirmation' => '',
            'role' => 'manager',
        ])->assertRedirect(route('admin.system.users.show', $user));

        $user->refresh();
        $this->assertSame('After Update', $user->name);
        $this->assertSame('after@krakatau.test', $user->email);
        $this->assertSame($originalPassword, $user->password);
        $this->assertTrue($user->hasRole('manager'));
        $this->assertFalse($user->hasRole('sales'));
    }

    public function test_user_password_can_be_updated_with_a_confirmed_new_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('original-password')]);
        $user->assignRole('sales');

        $this->put(route('admin.system.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
            'role' => 'sales',
        ])->assertRedirect(route('admin.system.users.show', $user));

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
        $this->assertFalse(Hash::check('original-password', $user->fresh()->password));
    }

    public function test_user_email_must_be_unique(): void
    {
        $existing = User::factory()->create(['email' => 'existing@krakatau.test']);
        $user = User::factory()->create(['email' => 'editable@krakatau.test']);
        $user->assignRole('sales');

        $this->from(route('admin.system.users.edit', $user))
            ->put(route('admin.system.users.update', $user), [
                'name' => $user->name,
                'email' => $existing->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => 'sales',
            ])->assertRedirect(route('admin.system.users.edit', $user))
            ->assertSessionHasErrors('email');
    }

    public function test_regular_user_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $user->assignRole('sales');

        $this->delete(route('admin.system.users.destroy', $user))
            ->assertRedirect(route('admin.system.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_super_admin_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->delete(route('admin.system.users.destroy', $user))
            ->assertRedirect(route('admin.system.users.index'))
            ->assertSessionHas('error', 'Super admin tidak boleh dihapus.');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
