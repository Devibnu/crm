<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pelanggan extends Model
{
    protected $table = 'pelanggan';

    protected $fillable = [
        'nama',
        'email',
        'no_hp',
        'status',
        'source',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function tiket(): HasMany
    {
        return $this->hasMany(Tiket::class, 'pelanggan_id');
    }

    public function identities(): HasMany
    {
        return $this->hasMany(CustomerIdentity::class, 'customer_id');
    }

    public function timelineEvents(): HasMany
    {
        return $this->hasMany(CustomerTimelineEvent::class, 'customer_id');
    }
}