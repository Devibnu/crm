<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerIdentity extends Model
{
    protected $fillable = [
        'customer_id',
        'type',
        'value',
        'label',
        'is_primary',
        'is_verified',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'customer_id');
    }

    public static function normalizeValue(string $type, string $value): string
    {
        $normalized = trim($value);

        if ($type === 'email') {
            return strtolower($normalized);
        }

        if (in_array($type, ['phone', 'whatsapp'], true)) {
            return preg_replace('/\D+/', '', $normalized) ?: '';
        }

        return $normalized;
    }
}