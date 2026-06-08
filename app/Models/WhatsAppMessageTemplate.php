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
        'safe_name',
        'category',
        'language',
        'status',
        'body',
        'body_meta',
        'header',
        'footer',
        'buttons',
        'variable_mapping',
        'source',
        'raw',
        'is_default',
        'last_synced_at',
        'submitted_at',
        'approved_at',
        'rejected_reason',
    ];

    protected $casts = [
        'buttons' => 'array',
        'variable_mapping' => 'array',
        'raw' => 'array',
        'is_default' => 'boolean',
        'last_synced_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
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
