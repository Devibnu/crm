<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AktivitasTiket extends Model
{
    protected $table = 'aktivitas_tiket';

    public const UPDATED_AT = null;

    protected $fillable = [
        'tiket_id',
        'user_id',
        'tipe',
        'judul',
        'deskripsi',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function tiket(): BelongsTo
    {
        return $this->belongsTo(Tiket::class, 'tiket_id');
    }

    public function aktor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function record(
        Tiket $tiket,
        string $type,
        ?User $user,
        string $title,
        ?string $description = null,
        array $metadata = [],
    ): self {
        return self::query()->create([
            'tiket_id' => $tiket->id,
            'user_id' => $user?->id,
            'tipe' => $type,
            'judul' => $title,
            'deskripsi' => $description,
            'metadata' => $metadata ?: null,
        ]);
    }
}