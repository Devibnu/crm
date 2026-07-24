<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferenceValueCapability extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_value_id',
        'capability',
    ];

    public function value(): BelongsTo
    {
        return $this->belongsTo(ReferenceValue::class, 'reference_value_id');
    }
}
