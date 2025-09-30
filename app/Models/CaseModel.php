<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseModel extends Model
{
    use HasFactory;

    protected $table = 'cases';

    protected $fillable = [
        'title',
        'description',
        'user_id',
        'status',
        'executor_id',
        'claimant_name',
        'debtor_name',
        'deadline_at',
    ];

    protected $casts = [
        'deadline_at' => 'datetime',
    ];

    protected $appends = ['status_label'];

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
}
