<?php

namespace App\Console\Commands\Workable;

use App\Integrations\Workable\Exceptions\CredentialsRejected;
use App\Integrations\Workable\Exceptions\WorkableException;
use App\Integrations\Workable\WorkableClient;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AddCompanyCommand extends Command
{
    protected $signature = 'workable:add-company {subdomain} {--name=} {--apply-mode=redirect}';

    protected $description = 'Add a Workable account. The API token is read via a hidden prompt, never an argument.';

    public function handle(WorkableClient $client): int
    {
        $subdomain = (string) $this->argument('subdomain');
        $name = $this->option('name') ?: Str::title(str_replace(['-', '_'], ' ', $subdomain));

        if (Company::where('subdomain', $subdomain)->exists()) {
            $this->components->error("A company with subdomain '{$subdomain}' already exists.");

            return self::FAILURE;
        }

        $token = $this->secret('Workable API access token (input hidden; leave blank for redirect-only)');
        $token = is_string($token) && trim($token) !== '' ? trim($token) : null;

        $company = Company::create([
            'subdomain' => $subdomain,
            'name' => $name,
            'slug' => $this->uniqueSlug($name ?: $subdomain),
            'apply_mode' => $token === null ? 'redirect' : (string) $this->option('apply-mode'),
            'credentials_status' => 'unverified',
            'api_token' => $token,
        ]);

        if ($token !== null) {
            try {
                $client->listPublishedJobs($company);
                $company->update(['credentials_status' => 'valid']);
            } catch (CredentialsRejected) {
                $company->update(['credentials_status' => 'invalid']);
                $this->components->warn('Workable rejected the token; marked invalid.');
            } catch (WorkableException $e) {
                $this->components->warn('Could not verify the token (left unverified): '.$e->getMessage());
            }
        }

        $this->components->info('Company added.');
        $this->table(['Field', 'Value'], [
            ['subdomain', $company->subdomain],
            ['name', $company->name],
            ['credentials_status', $company->credentials_status],
            ['apply_mode', $company->apply_mode],
        ]);

        return self::SUCCESS;
    }

    private function uniqueSlug(string $base): string
    {
        $slug = Str::slug($base) ?: 'company';
        $candidate = $slug;
        $i = 2;
        while (Company::where('slug', $candidate)->exists()) {
            $candidate = $slug.'-'.$i++;
        }

        return $candidate;
    }
}
