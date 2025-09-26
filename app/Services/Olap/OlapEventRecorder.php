<?php

namespace App\Services\Olap;

use App\Models\OlapActivity;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;

class OlapEventRecorder
{
    public function recordLogin(User $user, array $context = []): void
    {
        $this->record('user_login', $user, $context);
    }

    public function recordRegistration(User $user, array $context = []): void
    {
        $this->record('user_registered', $user, $context);
    }

    public function recordProfileUpdate(User $user, array $changedColumns, array $context = []): void
    {
        if (empty($changedColumns)) {
            return;
        }

        $this->record('user_profile_updated', $user, array_merge($context, [
            'changed_columns' => array_values($changedColumns),
        ]));
    }

    protected function record(string $eventType, ?User $user, array $context = []): void
    {
        $occurredAt = $context['occurred_at'] ?? now();

        OlapActivity::create([
            'user_id' => $user?->getKey(),
            'event_type' => $eventType,
            'metadata' => $this->buildMetadata($user, $context),
            'occurred_at' => $occurredAt instanceof CarbonInterface ? $occurredAt : now(),
        ]);
    }

    protected function buildMetadata(?User $user, array $context): array
    {
        $defaults = [
            'email' => $user?->email,
            'name' => $user?->name,
            'role' => $user?->role ?? null,
        ];

        if ($user && isset($context['registered_at'])) {
            $defaults['registered_at'] = $context['registered_at'];
        }

        return array_filter(array_merge($defaults, Arr::except($context, ['occurred_at'])));
    }
}
