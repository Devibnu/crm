<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessageTemplate extends Model
{
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_NOT_FOUND_ON_META = 'NOT_FOUND_ON_META';

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

    public function scopeAvailableForMetaUse(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_APPROVED)
            ->where('source', 'meta_sync');
    }

    public function isMissingOnMeta(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_NOT_FOUND_ON_META;
    }

    public function isAvailableForMetaUse(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_APPROVED
            && $this->source === 'meta_sync';
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
