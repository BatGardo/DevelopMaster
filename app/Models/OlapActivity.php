<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OlapActivity extends Model
{
    protected $table = 'olap_activity_log';

    protected $fillable = [
        'user_id',
        'event_type',
        'metadata',
        'occurred_at',
        'processed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('processed_at');
    }

    public function markProcessed(CarbonInterface $timestamp): void
    {
        $this->forceFill([
            'processed_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->save();
    }
}
