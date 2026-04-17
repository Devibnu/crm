<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'full_name',
        'email',
        'phone',
        'company',
        'source',
        'status',
        'assigned_user_id',
        'captured_by',
        'qualification_notes',
        'last_contacted_at',
        'qualified_at',
        'disqualified_at',
        'metadata',
    ];

    protected $casts = [
        'last_contacted_at' => 'datetime',
        'qualified_at' => 'datetime',
        'disqualified_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function capturedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }
}