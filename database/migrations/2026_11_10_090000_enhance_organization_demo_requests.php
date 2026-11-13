<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_demo_requests', function (Blueprint $table): void {
            $table->string('industry', 100)->nullable()->change();
            $table->char('email_domain_hash', 64)->nullable();
            $table->json('risk_flags')->nullable();
            $table->unsignedBigInteger('possible_duplicate_of_id')->nullable();
            $table->timestamp('setup_signed_at')->nullable();
            $table->char('requester_update_token_hash', 64)->nullable();
            $table->timestamp('requester_update_expires_at')->nullable();
            $table->text('requester_information_response')->nullable();
            $table->timestamp('requester_information_received_at')->nullable();
            $table->index('email_domain_hash', 'demo_requests_email_domain_hash_idx');
            $table->index('requester_update_token_hash', 'demo_request_update_token_idx');
            $table->foreign('possible_duplicate_of_id', 'demo_request_possible_duplicate_fk')
                ->references('id')->on('organization_demo_requests')->nullOnDelete();
        });

        $publicDomains = config('organization_demo.public_email_domains', []);
        DB::table('organization_demo_requests')->select(['id', 'business_email'])->orderBy('id')->chunkById(500, function ($rows) use ($publicDomains): void {
            foreach ($rows as $row) {
                $domain = mb_strtolower((string) substr(strrchr((string) $row->business_email, '@') ?: '', 1));
                $flags = in_array($domain, $publicDomains, true) ? ['public_email_provider'] : [];

                DB::table('organization_demo_requests')->where('id', $row->id)->update([
                    'email_domain_hash' => $domain !== '' ? hash('sha256', $domain) : null,
                    'risk_flags' => $flags === [] ? null : json_encode($flags, JSON_THROW_ON_ERROR),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('organization_demo_requests')->whereNull('industry')->update(['industry' => 'Not specified']);

        Schema::table('organization_demo_requests', function (Blueprint $table): void {
            $table->dropForeign('demo_request_possible_duplicate_fk');
            $table->dropIndex('demo_requests_email_domain_hash_idx');
            $table->dropIndex('demo_request_update_token_idx');
            $table->dropColumn([
                'email_domain_hash',
                'risk_flags',
                'possible_duplicate_of_id',
                'setup_signed_at',
                'requester_update_token_hash',
                'requester_update_expires_at',
                'requester_information_response',
                'requester_information_received_at',
            ]);
            $table->string('industry', 100)->nullable(false)->change();
        });
    }
};
