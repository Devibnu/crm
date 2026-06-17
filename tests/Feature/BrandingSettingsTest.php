<?php

namespace Tests\Feature;

use App\Models\BrandingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_branding_edit_page(): void
    {
        $this->get(route('admin.system.branding.edit'))
            ->assertOk()
            ->assertSee('Branding Settings')
            ->assertSee('Nama Aplikasi')
            ->assertSee('Preview Sidebar Brand')
            ->assertSee('Preview Login Brand')
            ->assertSee('Preview Favicon')
            ->assertSee('name="sidebar_logo"', false)
            ->assertSee('name="login_logo"', false)
            ->assertSee('name="favicon"', false);
    }

    public function test_branding_menu_is_visible_in_system_sidebar(): void
    {
        $this->get(route('admin.system.branding.edit'))
            ->assertOk()
            ->assertSee('System')
            ->assertSee('Branding')
            ->assertSee(route('admin.system.branding.edit'), false)
            ->assertSee('active', false);
    }

    public function test_admin_can_update_application_name(): void
    {
        $this->put(route('admin.system.branding.update'), [
            'app_name' => 'Krakatau Command Center',
            'primary_color' => '#123abc',
        ])->assertRedirect(route('admin.system.branding.edit'));

        $this->assertDatabaseHas('branding_settings', [
            'app_name' => 'Krakatau Command Center',
            'primary_color' => '#123abc',
        ]);
    }

    public function test_valid_logo_upload_is_stored(): void
    {
        Storage::fake('public');

        $this->put(route('admin.system.branding.update'), [
            'app_name' => 'Krakatau CRM',
            'sidebar_logo' => UploadedFile::fake()->image('sidebar-logo.png', 120, 80)->size(256),
            'login_logo' => UploadedFile::fake()->image('login-logo.webp', 120, 80)->size(256),
        ])->assertRedirect(route('admin.system.branding.edit'));

        $branding = BrandingSetting::query()->firstOrFail();

        $this->assertNotNull($branding->sidebar_logo_path);
        $this->assertNotNull($branding->login_logo_path);
        Storage::disk('public')->assertExists($branding->sidebar_logo_path);
        Storage::disk('public')->assertExists($branding->login_logo_path);
        $this->assertStringStartsWith('branding/', $branding->sidebar_logo_path);
        $this->assertStringStartsWith('branding/', $branding->login_logo_path);
    }

    public function test_invalid_upload_is_rejected(): void
    {
        Storage::fake('public');

        $this->from(route('admin.system.branding.edit'))
            ->put(route('admin.system.branding.update'), [
                'sidebar_logo' => UploadedFile::fake()->create('unsafe.svg', 10, 'image/svg+xml'),
            ])
            ->assertRedirect(route('admin.system.branding.edit'))
            ->assertSessionHasErrors('sidebar_logo');

        $this->assertDatabaseMissing('branding_settings', [
            'sidebar_logo_path' => 'unsafe.svg',
        ]);
    }

    public function test_fallback_is_rendered_when_setting_is_empty(): void
    {
        auth()->logout();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('CRM Krakatau')
            ->assertSee('assets/vuexy/logo.svg', false);

        $this->actingAs($this->userForAdminRoutes());

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('CRM Krakatau')
            ->assertSee('assets/vuexy/logo.svg', false);

        $this->get(route('admin.system.branding.edit'))
            ->assertOk()
            ->assertSee('Default Vuexy logo')
            ->assertSee('Belum ada favicon custom');
    }

    public function test_login_page_uses_branding_name_and_logo(): void
    {
        BrandingSetting::query()->create([
            'app_name' => 'Krakatau Portal',
            'login_logo_path' => 'branding/login.png',
        ]);

        auth()->logout();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Login - Krakatau Portal')
            ->assertSee('Krakatau Portal')
            ->assertSee('storage/branding/login.png', false);
    }

    public function test_sidebar_uses_branding_name_and_logo(): void
    {
        BrandingSetting::query()->create([
            'app_name' => 'Krakatau Backoffice',
            'sidebar_logo_path' => 'branding/sidebar.png',
        ]);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Krakatau Backoffice')
            ->assertSee('storage/branding/sidebar.png', false);
    }

    protected function userForAdminRoutes()
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }
}
