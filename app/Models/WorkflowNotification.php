<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Laravel's own notifications table, extended with the scope/category/subject
 * metadata added by the add_scope_metadata_to_notifications_table migration.
 */
class WorkflowNotification extends DatabaseNotification
{
    protected function casts(): array
    {
        return [
            'organization_id' => 'integer',
            'subject_id' => 'integer',
            'schema_version' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
