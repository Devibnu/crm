<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'name',
        'company_name',
        'email',
        'phone',
        'whatsapp',
        'source',
        'lead_source',
        'status',
        'priority',
        'assigned_to',
        'last_whatsapp_message',
        'last_whatsapp_at',
        'notes',
    ];

    protected $casts = [
        'last_whatsapp_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
