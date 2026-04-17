<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'company', 'company_email', 'address', 'country', 'contact'])]
class Client extends Model
{
    use HasFactory;

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function toApiResponse(): array
    {
        return [
            'address' => $this->address ?? '',
            'company' => $this->company ?? '',
            'companyEmail' => $this->company_email ?? '',
            'country' => $this->country ?? '',
            'contact' => $this->contact ?? '',
            'name' => $this->name,
        ];
    }
}
