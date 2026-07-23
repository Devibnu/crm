<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessCalendarHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_calendar_id',
        'holiday_date',
        'name',
        'description',
        'is_recurring',
    ];

    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
            'is_recurring' => 'boolean',
        ];
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(BusinessCalendar::class, 'business_calendar_id');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('holiday_date', '>=', now()->toDateString())->orderBy('holiday_date');
    }
}
