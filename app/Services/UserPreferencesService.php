<?php

namespace App\Services;

use App\Models\SettingsAuditEvent;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class UserPreferencesService
{
    /** @var array<int,UserPreference> */
    private array $resolved = [];

    public function forUser(User $user): UserPreference
    {
        if (isset($this->resolved[$user->id])) {
            return $this->resolved[$user->id];
        }
        try {
            return $this->resolved[$user->id] = UserPreference::query()->where('user_id', $user->id)->first()
                ?? new UserPreference(['user_id' => $user->id]);
        } catch (QueryException) {
            return $this->resolved[$user->id] = new UserPreference(['user_id' => $user->id]);
        }
    }

    public function displayTimezone(User $user): string
    {
        return $this->forUser($user)->timezone ?? config('app.timezone', 'UTC');
    }

    /** @param array{timezone:?string} $data */
    public function update(User $user, array $data): UserPreference
    {
        return DB::transaction(function () use ($user, $data): UserPreference {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $preference = UserPreference::query()->firstOrNew(['user_id' => $user->id]);
            $before = $preference->only(['timezone']);
            $preference->fill(['timezone' => $data['timezone']])->save();
            SettingsAuditEvent::create([
                'actor_id' => $user->id, 'user_id' => $user->id, 'scope' => 'personal',
                'action' => 'timezone_updated', 'changes' => ['before' => $before, 'after' => $preference->only(['timezone'])],
                'created_at' => now(),
            ]);

            return $this->resolved[$user->id] = $preference;
        });
    }
}
