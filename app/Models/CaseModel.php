<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CaseModel extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (CaseModel $case) {
            if ($case->region || empty($case->title)) {
                return;
            }

            if (preg_match('/\(([^,]+),/u', $case->title, $matches)) {
                $case->region = $matches[1];
            }
        });
    }

    protected $table = 'cases';

    protected $fillable = [
        'title',
        'description',
        'user_id',
        'status',
        'executor_id',
        'region',
        'claimant_name',
        'debtor_name',
        'deadline_at',
    ];

    protected $casts = [
        'deadline_at' => 'datetime',
    ];

    protected $appends = ['status_label', 'region_label'];

    public const STATUSES = ['new', 'in_progress', 'done', 'closed'];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function executor()
    {
        return $this->belongsTo(User::class, 'executor_id');
    }

    public function actions()
    {
        return $this->hasMany(CaseAction::class, 'case_id')->latest();
    }

    public function documents()
    {
        return $this->hasMany(CaseDocument::class, 'case_id')->latest();
    }

    public function getStatusLabelAttribute(): string
    {
        return __('statuses.' . $this->status);
    }

    public function getRegionLabelAttribute(): string
    {
        return $this->region ?? __('Not specified');
    }

    public static function statusOptions(): array
    {
        return collect(self::STATUSES)
            ->mapWithKeys(fn ($status) => [$status => __('statuses.' . $status)])
            ->toArray();
    }

    public static function statusLabels(): array
    {
        return self::statusOptions();
    }

    public static function regionOptions(): array
    {
        return self::query()
            ->whereNotNull('region')
            ->select('region')
            ->distinct()
            ->orderBy('region')
            ->pluck('region')
            ->map(fn ($value) => Str::of($value)->squish()->title()->value())
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    public function setRegionAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['region'] = null;

            return;
        }

        $normalized = Str::of($value)->squish();

        $this->attributes['region'] = $normalized->isEmpty()
            ? null
            : $normalized->title()->value();
    }
}


