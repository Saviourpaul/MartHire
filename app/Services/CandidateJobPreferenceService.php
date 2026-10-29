<?php

namespace App\Services;

use App\Models\CandidateJobPreference;
use App\Models\SettingsAuditEvent;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CandidateJobPreferenceService
{
    /** @var array<int,CandidateJobPreference> */
    private array $resolved = [];

    public function forUser(User $user): CandidateJobPreference
    {
        if (isset($this->resolved[$user->id])) {
            return $this->resolved[$user->id];
        }

        try {
            return $this->resolved[$user->id] = CandidateJobPreference::query()->where('user_id', $user->id)->first()
                ?? new CandidateJobPreference(['user_id' => $user->id]);
        } catch (QueryException) {
            return $this->resolved[$user->id] = new CandidateJobPreference(['user_id' => $user->id]);
        }
    }

    /**
     * @param  array<string, mixed>  $data  validated preference fields plus the expected `version`
     */
    public function update(User $user, array $data): CandidateJobPreference
    {
        return DB::transaction(function () use ($user, $data): CandidateJobPreference {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $preference = CandidateJobPreference::query()->firstOrNew(['user_id' => $user->id]);

            abort_if($preference->exists && (int) ($data['version'] ?? 0) !== (int) $preference->version, 409,
                'These preferences were updated elsewhere. Reload and try again.');

            $fields = [
                'job_alert_frequency',
                'profile_visible_to_employers',
                'preferred_roles',
                'preferred_locations',
                'preferred_job_type',
                'salary_min',
                'salary_max',
            ];
            $before = $preference->only($fields);
            $preference->fill(array_intersect_key($data, array_flip($fields)));
            $preference->version = (int) $preference->version + 1;
            $preference->save();

            SettingsAuditEvent::create([
                'actor_id' => $user->id,
                'user_id' => $user->id,
                'scope' => 'personal',
                'action' => 'job_preferences_updated',
                'changes' => ['before' => $before, 'after' => $preference->only($fields)],
                'created_at' => now(),
            ]);

            return $this->resolved[$user->id] = $preference;
        });
    }
}
