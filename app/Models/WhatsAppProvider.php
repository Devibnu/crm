<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppProvider extends Model
{
    /** @use HasFactory<\Database\Factories\WhatsAppProviderFactory> */
    use HasFactory;

    protected $table = 'whatsapp_providers';

    protected $fillable = [
        'name',
        'provider',
        'api_url',
        'api_token',
        'device_id',
        'display_phone_number',
        'verified_name',
        'webhook_secret',
        'business_account_id',
        'graph_api_version',
        'meta_template_name',
        'meta_template_language',
        'meta_connection_status',
        'meta_connection_error',
        'status',
        'is_default',
        'notes',
        'last_connected_at',
    ];

    protected $casts = [
        'api_token' => 'encrypted',
        'is_default' => 'boolean',
        'last_connected_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function messageTemplates(): HasMany
    {
        return $this->hasMany(WhatsAppMessageTemplate::class, 'provider_id');
    }
}
