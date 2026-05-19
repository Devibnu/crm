<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerBehavior extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'lifecycle_stage',
        'engagement_score',
        'last_activity_at',
        'product_interest',
        'behavior_notes',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
