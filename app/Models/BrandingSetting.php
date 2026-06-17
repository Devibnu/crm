<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BrandingSetting extends Model
{
    public const FALLBACK_APP_NAME = 'CRM Krakatau';
    public const FALLBACK_LOGO_PATH = 'assets/vuexy/logo.svg';

    protected $fillable = [
        'app_name',
        'sidebar_logo_path',
        'login_logo_path',
        'favicon_path',
        'primary_color',
    ];

    public static function current(): self
    {
        if (! Schema::hasTable('branding_settings')) {
            return new self();
        }

        return self::query()->first() ?? new self();
    }

    public function getDisplayAppNameAttribute(): string
    {
        return filled($this->app_name) ? $this->app_name : self::FALLBACK_APP_NAME;
    }

    public function getSidebarLogoUrlAttribute(): string
    {
        return $this->publicUrlOrFallback($this->sidebar_logo_path);
    }

    public function getLoginLogoUrlAttribute(): string
    {
        return $this->publicUrlOrFallback($this->login_logo_path);
    }

    public function getFaviconUrlAttribute(): ?string
    {
        if (! filled($this->favicon_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->favicon_path);
    }

    public function getDisplayPrimaryColorAttribute(): ?string
    {
        return filled($this->primary_color) ? $this->primary_color : null;
    }

    protected function publicUrlOrFallback(?string $path): string
    {
        if (! filled($path)) {
            return asset(self::FALLBACK_LOGO_PATH);
        }

        return Storage::disk('public')->url($path);
    }
}
