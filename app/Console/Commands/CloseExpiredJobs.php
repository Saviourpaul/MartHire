<?php

namespace App\Console\Commands;

use App\Enums\JobStatus;
use App\Models\Job;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Console\Command;

class CloseExpiredJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:close-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close open jobs whose application deadline has passed';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = Job::withoutGlobalScope(OrganizationScope::class)->open()->where('due_date', '<', today()->toDateString())
            ->update(['status' => JobStatus::Closed]);

        $this->info("Closed {$count} expired jobs.");

        return self::SUCCESS;
    }
}
