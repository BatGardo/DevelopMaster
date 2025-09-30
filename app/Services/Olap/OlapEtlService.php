<?php

namespace App\Services\Olap;

use App\Models\OlapActivity;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OlapEtlService
{
    public function syncBatch(int $limit): int
    {
        $events = OlapActivity::query()
            ->pending()
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($events->isEmpty()) {
            return 0;
        }

        $processedIds = [];
        $processedAt = now();

        foreach ($events as $event) {
            $this->processEvent($event);
            $processedIds[] = $event->getKey();
        }

        OlapActivity::query()
            ->whereIn('id', $processedIds)
            ->update([
                'processed_at' => $processedAt,
                'updated_at' => $processedAt,
            ]);

        return count($processedIds);
    }

    protected function processEvent(OlapActivity $event): void
    {
        $metadata = $event->metadata ?? [];
        $occurredAt = $event->occurred_at ?? now();

        if (! $occurredAt instanceof CarbonInterface) {
            $occurredAt = Carbon::parse($occurredAt);
        }

        $dateKey = $this->ensureDateDimension($occurredAt);
        $roleKey = $this->ensureRoleDimension($metadata['role'] ?? 'user');
        $userKey = $this->ensureUserDimension($event->user_id, $metadata, $occurredAt);

        match ($event->event_type) {
            'user_login' => $this->storeLoginFact($event, $dateKey, $userKey, $roleKey),
            'user_registered' => $this->storeRegistrationFact($event, $dateKey, $userKey, $roleKey),
            'user_profile_updated' => $this->storeProfileUpdateFact($event, $dateKey, $userKey, $roleKey),
            default => null,
        };
    }

    protected function ensureDateDimension(CarbonInterface $occurredAt): string
    {
        $dateKey = $occurredAt->format('Ymd');

        DB::connection(config('olap.connection'))
            ->table('dim_dates')
            ->updateOrInsert(
                ['date_key' => $dateKey],
                [
                    'date' => $occurredAt->toDateString(),
                    'year' => (int) $occurredAt->format('Y'),
                    'quarter' => (int) $occurredAt->quarter,
                    'month' => (int) $occurredAt->format('m'),
                    'day' => (int) $occurredAt->format('d'),
                    'week_of_year' => (int) $occurredAt->format('W'),
                    'day_name' => $occurredAt->format('l'),
                    'month_name' => $occurredAt->format('F'),
                    'updated_at' => now(),
                ]
            );

        return $dateKey;
    }

    protected function ensureUserDimension(?int $userId, array $metadata, CarbonInterface $occurredAt): ?int
    {
        if ($userId === null) {
            return null;
        }

        $connection = DB::connection(config('olap.connection'));

        $existing = $connection->table('dim_users')
            ->where('user_key', $userId)
            ->exists();

        $payload = [
            'email' => Arr::get($metadata, 'email'),
            'name' => Arr::get($metadata, 'name'),
            'last_activity_at' => $occurredAt,
            'updated_at' => now(),
        ];

        if (isset($metadata['registered_at'])) {
            $payload['registered_at'] = $metadata['registered_at'];
        }

        if (! $existing) {
            $connection->table('dim_users')->insert(array_merge([
                'user_key' => $userId,
                'created_at' => now(),
            ], $payload));
        } else {
            $connection->table('dim_users')
                ->where('user_key', $userId)
                ->update($payload);
        }

        return $userId;
    }

    protected function ensureRoleDimension(?string $role): ?string
    {
        if (! $role) {
            return null;
        }

        $normalized = Str::lower($role);

        DB::connection(config('olap.connection'))
            ->table('dim_roles')
            ->updateOrInsert(
                ['role_key' => $normalized],
                [
                    'role_name' => $role,
                    'updated_at' => now(),
                ]
            );

        return $normalized;
    }

    protected function storeLoginFact(OlapActivity $event, string $dateKey, ?int $userKey, ?string $roleKey): void
    {
        $metadata = $event->metadata ?? [];

        DB::connection(config('olap.connection'))
            ->table('fact_user_logins')
            ->insert([
                'date_key' => $dateKey,
                'user_key' => $userKey,
                'role_key' => $roleKey,
                'login_count' => 1,
                'source' => Arr::get($metadata, 'source', 'web'),
                'ip_address' => Arr::get($metadata, 'ip'),
                'user_agent' => Str::limit(Arr::get($metadata, 'user_agent'), 255),
                'occurred_at' => $event->occurred_at,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    protected function storeRegistrationFact(OlapActivity $event, string $dateKey, ?int $userKey, ?string $roleKey): void
    {
        DB::connection(config('olap.connection'))
            ->table('fact_user_registrations')
            ->insert([
                'date_key' => $dateKey,
                'user_key' => $userKey,
                'role_key' => $roleKey,
                'occurred_at' => $event->occurred_at,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    protected function storeProfileUpdateFact(OlapActivity $event, string $dateKey, ?int $userKey, ?string $roleKey): void
    {
        $metadata = $event->metadata ?? [];

        DB::connection(config('olap.connection'))
            ->table('fact_user_profile_updates')
            ->insert([
                'date_key' => $dateKey,
                'user_key' => $userKey,
                'role_key' => $roleKey,
                'changed_columns' => json_encode(Arr::get($metadata, 'changed_columns', [])),
                'occurred_at' => $event->occurred_at,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
