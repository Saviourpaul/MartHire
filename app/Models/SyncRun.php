<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncRun extends Model
{
    protected $fillable = [
        'company_id',
        'started_at',
        'finished_at',
        'status',
        'fetched',
        'created',
        'updated',
        'closed',
        'error',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function start(Company $company): self
    {
        return self::create([
            'company_id' => $company->id,
            'started_at' => now(),
            'status' => 'ok',
        ]);
    }

    /** @param array{fetched?:int,created?:int,updated?:int,closed?:int,status?:string,error?:?string} $counts */
    public function finish(array $counts): void
    {
        $this->finished_at = now();
        foreach (['fetched', 'created', 'updated', 'closed'] as $key) {
            $this->{$key} = (int) ($counts[$key] ?? 0);
        }
        if (isset($counts['status'])) {
            $this->status = $counts['status'];
        }
        if (array_key_exists('error', $counts)) {
            $this->error = $counts['error'];
        }
        $this->save();
    }
}
