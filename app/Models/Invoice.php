<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_id', 'issued_date', 'due_date', 'service',
    'total', 'avatar', 'invoice_status', 'balance',
])]
class Invoice extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'due_date' => 'date',
            'total' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'issuedDate' => $this->issued_date->format('Y-m-d'),
            'dueDate' => $this->due_date->format('Y-m-d'),
            'client' => $this->client->toApiResponse(),
            'service' => $this->service,
            'total' => (float) $this->total,
            'avatar' => $this->avatar ?? '',
            'invoiceStatus' => $this->invoice_status,
            'balance' => (float) $this->balance,
        ];
    }
}
