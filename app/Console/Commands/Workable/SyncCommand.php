<?php

namespace App\Console\Commands\Workable;

use App\Jobs\Workable\SyncCompanyJobsJob;
use App\Models\Company;
use Illuminate\Console\Command;

class SyncCommand extends Command
{
    protected $signature = 'workable:sync {--company= : subdomain or id to sync just one}';

    protected $description = 'Queue a job sync for every Workable company (or just one).';

    public function handle(): int
    {
        $option = $this->option('company');

        $companies = Company::query()
            ->when($option !== null && $option !== '', function ($query) use ($option) {
                // Numeric values match the id; anything else matches the subdomain.
                ctype_digit((string) $option)
                    ? $query->whereKey((int) $option)
                    : $query->where('subdomain', $option);
            })
            ->get();

        if ($companies->isEmpty() && $option) {
            $this->components->error("No company matches '{$option}'.");

            return self::FAILURE;
        }

        foreach ($companies as $company) {
            SyncCompanyJobsJob::dispatch($company);
        }

        $this->components->info("Queued {$companies->count()} sync job(s).");

        return self::SUCCESS;
    }
}
