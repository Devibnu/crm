<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company_name',
        'email',
        'phone',
        'whatsapp',
        'source',
        'status',
        'owner_name',
        'notes',
    ];

    public function interactions(): HasMany
    {
        return $this->hasMany(CustomerInteraction::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CustomerTransaction::class);
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(CustomerPreference::class);
    }

    public function behaviors(): HasMany
    {
        return $this->hasMany(CustomerBehavior::class);
    }
}
