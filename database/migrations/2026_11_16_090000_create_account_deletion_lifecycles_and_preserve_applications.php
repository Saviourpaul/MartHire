<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexName = 'users_applicant_inactivity_scan_idx';
        $indexColumns = ['role', 'status', 'deleted_at', 'last_login_at'];
        $existingIndex = collect(Schema::getIndexes('users'))->firstWhere('name', $indexName);

        if (! $existingIndex) {
            Schema::table('users', function (Blueprint $table): void {
                $table->index(
                    ['role', 'status', 'deleted_at', 'last_login_at'],
                    'users_applicant_inactivity_scan_idx'
                );
            });
        } elseif (($existingIndex['columns'] ?? []) !== $indexColumns) {
            throw new RuntimeException(
                "The existing {$indexName} index does not match the required inactivity-scan columns. Reconcile the index before migrating."
            );
        }

        $userIdForeignKeys = collect(Schema::getForeignKeys('application_forms'))
            ->filter(fn (array $foreignKey): bool => ($foreignKey['columns'] ?? []) === ['user_id']);
        $hasSetNullForeignKey = $userIdForeignKeys->contains(fn (array $foreignKey): bool =>
            ($foreignKey['foreign_table'] ?? null) === 'users'
            && ($foreignKey['foreign_columns'] ?? []) === ['id']
            && ($foreignKey['on_delete'] ?? null) === 'set null'
        );

        if (! $hasSetNullForeignKey) {
            foreach ($userIdForeignKeys as $foreignKey) {
                Schema::table('application_forms', function (Blueprint $table) use ($foreignKey): void {
                    // SQLite rebuilds the table for foreign-key changes and needs
                    // the constrained columns; MySQL requires the actual key name.
                    $table->dropForeign(
                        DB::getDriverName() === 'sqlite'
                            ? ($foreignKey['columns'] ?? ['user_id'])
                            : $foreignKey['name']
                    );
                });
            }
        }

        $userIdColumn = collect(Schema::getColumns('application_forms'))->firstWhere('name', 'user_id');
        if (! ($userIdColumn['nullable'] ?? false)) {
            Schema::table('application_forms', function (Blueprint $table): void {
                $table->foreignId('user_id')->nullable()->change();
            });
        }

        if (! $hasSetNullForeignKey) {
            Schema::table('application_forms', function (Blueprint $table): void {
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('account_deletion_lifecycles')) {
            Schema::create('account_deletion_lifecycles', function (Blueprint $table): void {
                $table->id();
                $table->uuid('reference')->unique();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('active_user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
                $table->foreignId('initiated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('role_snapshot', 20)->nullable();
                $table->string('reason');
                $table->string('status', 30)->default('scheduled');
                $table->timestamp('last_login_at_snapshot')->nullable();
                // This is a required deadline, not an insertion timestamp. DATETIME
                // avoids MySQL TIMESTAMP implicit-default restrictions in strict mode.
                $table->dateTime('scheduled_deletion_at');
                $table->string('warning_status', 30)->default('pending');
                $table->timestamp('warning_queued_at')->nullable();
                $table->timestamp('warning_sent_at')->nullable();
                $table->unsignedSmallInteger('warning_attempts')->default(0);
                $table->string('warning_error', 200)->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->string('purge_status', 30)->default('pending');
                $table->unsignedSmallInteger('purge_attempts')->default(0);
                $table->string('purge_error', 200)->nullable();
                $table->json('purge_paths')->nullable();
                $table->timestamps();

                $table->index(['status', 'scheduled_deletion_at']);
                $table->index(['status', 'warning_status', 'scheduled_deletion_at'], 'account_deletions_warning_due_idx');
                $table->index(['status', 'purge_status', 'updated_at'], 'account_deletions_purge_retry_idx');
                $table->index(['user_id', 'created_at']);
            });
        } elseif (! Schema::hasColumns('account_deletion_lifecycles', [
            'id', 'reference', 'user_id', 'active_user_id', 'initiated_by_user_id', 'role_snapshot',
            'reason', 'status', 'last_login_at_snapshot', 'scheduled_deletion_at', 'warning_status',
            'warning_queued_at', 'warning_sent_at', 'warning_attempts', 'warning_error', 'cancelled_at',
            'completed_at', 'purge_status', 'purge_attempts', 'purge_error', 'purge_paths', 'created_at', 'updated_at',
        ])) {
            throw new RuntimeException('The existing account_deletion_lifecycles table does not match the expected schema. Reconcile it before migrating.');
        }
    }

    public function down(): void
    {
        if (DB::table('application_forms')->whereNull('user_id')->exists()) {
            throw new RuntimeException(
                'Cannot roll back account deletion lifecycle migration while application forms have anonymized user_id values.'
            );
        }
        if (DB::table('account_deletion_lifecycles')->where('status', 'completed')->exists()) {
            throw new RuntimeException(
                'Cannot roll back account deletion lifecycle migration after a permanent account deletion; lifecycle history would be lost.'
            );
        }

        Schema::dropIfExists('account_deletion_lifecycles');

        Schema::table('application_forms', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });

        Schema::table('application_forms', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::table('application_forms', function (Blueprint $table): void {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        if (Schema::hasIndex('users', 'users_applicant_inactivity_scan_idx')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropIndex('users_applicant_inactivity_scan_idx');
            });
        }
    }
};
