<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tiket extends Model
{
    protected $table = 'tiket';

    protected $fillable = [
        'pelanggan_id',
        'assigned_user_id',
        'kategori',
        'subjek',
        'status',
        'prioritas',
        'batas_sla',
    ];

    protected $casts = [
        'batas_sla' => 'datetime',
    ];

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function pesan(): HasMany
    {
        return $this->hasMany(Pesan::class, 'tiket_id');
    }

    public function aktivitas(): HasMany
    {
        return $this->hasMany(AktivitasTiket::class, 'tiket_id');
    }

    public function pesanTerbaru(): HasOne
    {
        return $this->hasOne(Pesan::class, 'tiket_id')->latestOfMany();
    }
}