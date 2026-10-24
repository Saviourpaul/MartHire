<?php

namespace App\Jobs\Workable;

use App\Actions\Workable\SyncCompanyJobs;
use App\Models\Company;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncCompanyJobsJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Company $company) {}

    /**
     * Exceptions are deliberately not caught: the action records WorkableException
     * failures itself; anything else must propagate so the queue lands it in
     * failed_jobs instead of being silently swallowed.
     */
    public function handle(SyncCompanyJobs $action): void
    {
        $action($this->company);
    }
}
