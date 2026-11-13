<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('organization_demo_requests')) {
            Schema::create('organization_demo_requests', function (Blueprint $table): void {
                $table->id();
                $table->string('contact_first_name', 120);
                $table->string('contact_last_name', 120);
                $table->string('company_name', 255);
                $table->string('business_email', 255);
                $table->char('active_email_hash', 64)->nullable()->unique();
                $table->string('phone', 24);
                $table->char('phone_country', 2);
                $table->string('phone_dial_code', 8);
                $table->string('phone_national_number', 20);
                $table->string('industry', 100);
                $table->string('organization_size', 24);
                $table->string('status', 32)->default('submitted')->index();
                $table->string('onboarding_status', 32)->default('not_started')->index();
                $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('demo_scheduled_at')->nullable();
                $table->string('demo_timezone', 100)->nullable();
                $table->text('internal_notes')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->char('setup_token_hash', 64)->nullable()->index();
                $table->timestamp('setup_expires_at')->nullable();
                $table->timestamp('setup_sent_at')->nullable();
                $table->timestamp('setup_accepted_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('converted_user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
                $table->foreignId('converted_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
                $table->timestamps();
                $table->index(['status', 'created_at']);
            });
        } elseif (! Schema::hasColumns('organization_demo_requests', [
            'id', 'contact_first_name', 'contact_last_name', 'company_name', 'business_email',
            'active_email_hash', 'phone', 'phone_country', 'phone_dial_code', 'phone_national_number',
            'industry', 'organization_size', 'status', 'onboarding_status', 'assigned_admin_id',
            'reviewed_by_id', 'demo_scheduled_at', 'demo_timezone', 'internal_notes', 'rejection_reason',
            'setup_token_hash', 'setup_expires_at', 'setup_sent_at', 'setup_accepted_at', 'reviewed_at',
            'approved_at', 'rejected_at', 'completed_at', 'converted_user_id', 'converted_organization_id',
            'created_at', 'updated_at',
        ])) {
            throw new \RuntimeException('The existing organization_demo_requests table does not match the expected schema. Reconcile it before migrating.');
        }

        $eventTable = 'organization_demo_request_events';

        if (! Schema::hasTable($eventTable)) {
            Schema::create($eventTable, function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('organization_demo_request_id');
                $table->foreignId('actor_id')->nullable();
                $table->string('event', 48);
                $table->json('details')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['organization_demo_request_id', 'created_at'], 'demo_req_events_request_created_idx');
                $table->foreign('organization_demo_request_id', 'demo_req_events_request_fk')
                    ->references('id')->on('organization_demo_requests')->cascadeOnDelete();
                $table->foreign('actor_id', 'demo_req_events_actor_fk')
                    ->references('id')->on('users')->nullOnDelete();
            });

            return;
        }

        if (! Schema::hasColumns($eventTable, ['id', 'organization_demo_request_id', 'actor_id', 'event', 'details', 'created_at'])) {
            throw new \RuntimeException('The existing organization_demo_request_events table does not match the expected schema. Reconcile it before migrating.');
        }

        $foreignKeys = Schema::getForeignKeys($eventTable);
        $hasRequestForeignKey = count(array_filter($foreignKeys, static fn (array $foreignKey): bool =>
            $foreignKey['columns'] === ['organization_demo_request_id']
            && $foreignKey['foreign_table'] === 'organization_demo_requests'
            && $foreignKey['foreign_columns'] === ['id']
        )) > 0;
        $hasActorForeignKey = count(array_filter($foreignKeys, static fn (array $foreignKey): bool =>
            $foreignKey['columns'] === ['actor_id']
            && $foreignKey['foreign_table'] === 'users'
            && $foreignKey['foreign_columns'] === ['id']
        )) > 0;
        $hasRequestCreatedIndex = Schema::hasIndex($eventTable, ['organization_demo_request_id', 'created_at']);

        Schema::table($eventTable, function (Blueprint $table) use ($hasRequestForeignKey, $hasActorForeignKey, $hasRequestCreatedIndex): void {
            if (! $hasRequestCreatedIndex) {
                $table->index(['organization_demo_request_id', 'created_at'], 'demo_req_events_request_created_idx');
            }

            if (! $hasRequestForeignKey) {
                $table->foreign('organization_demo_request_id', 'demo_req_events_request_fk')
                    ->references('id')->on('organization_demo_requests')->cascadeOnDelete();
            }

            if (! $hasActorForeignKey) {
                $table->foreign('actor_id', 'demo_req_events_actor_fk')
                    ->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_demo_request_events');
        Schema::dropIfExists('organization_demo_requests');
    }
};
