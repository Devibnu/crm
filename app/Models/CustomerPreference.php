<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'preferred_channel',
        'product_interest',
        'communication_consent',
        'segment',
        'notes',
    ];

    protected $casts = [
        'communication_consent' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
