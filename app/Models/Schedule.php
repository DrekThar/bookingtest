<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    /**
     * Получить услугу, к которой относится это расписание.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
