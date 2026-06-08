<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessageTemplate extends Model
{
    protected $table = 'whatsapp_message_templates';

    protected $fillable = [
        'provider_id',
        'template_id',
        'name',
        'category',
        'language',
        'status',
        'body',
        'header',
        'footer',
        'buttons',
        'raw',
        'is_default',
        'last_synced_at',
    ];

    protected $casts = [
        'buttons' => 'array',
        'raw' => 'array',
        'is_default' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(WhatsAppProvider::class, 'provider_id');
    }

    public function hasVariables(): bool
    {
        return preg_match('/\{\{\s*\d+\s*\}\}/', implode(' ', [
            (string) $this->header,
            (string) $this->body,
            (string) $this->footer,
        ])) === 1;
    }
}
