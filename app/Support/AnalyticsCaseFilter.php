<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class AnalyticsCaseFilter
{
    public static function apply(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, fn (Builder $builder, $status) => $builder->where('status', $status))
            ->when($filters['executor'] ?? null, fn (Builder $builder, $executor) => $builder->where('executor_id', $executor))
            ->when($filters['owner'] ?? null, fn (Builder $builder, $owner) => $builder->where('user_id', $owner))
            ->when($filters['date_from'] ?? null, fn (Builder $builder, $from) => $builder->where('created_at', '>=', $from))
            ->when($filters['date_to'] ?? null, fn (Builder $builder, $to) => $builder->where('created_at', '<=', $to));
    }
}