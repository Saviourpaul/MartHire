<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\ApplicationForm;
use App\Models\Organization;
use App\Models\User;

class ActivityLogger
{
    /** @param array<string, mixed> $payload */
    public function record(Organization|int $organization, ?ApplicationForm $application, ?User $actor, string $type, array $payload = []): ActivityLog
    {
        return ActivityLog::create([
            'organization_id' => $organization instanceof Organization ? $organization->id : $organization,
            'application_form_id' => $application?->id, 'actor_id' => $actor?->id, 'type' => $type,
            'payload' => [...$payload, 'actor_name' => $actor ? trim($actor->first_name.' '.$actor->last_name) : 'System',
                'application_id' => $application?->id, 'application_reference' => $application?->reference],
            'created_at' => now(),
        ]);
    }
}
