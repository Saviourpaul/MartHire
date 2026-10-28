<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex('job_posts', ['status', 'created_at'], 'job_posts_status_created_at_idx');
        $this->addIndex('job_posts', ['employer_id', 'created_at'], 'job_posts_employer_created_at_idx');
        $this->addIndex('application_forms', ['user_id', 'submitted_at'], 'app_forms_user_submitted_idx');
        $this->addIndex('application_forms', ['job_id', 'submitted_at'], 'app_forms_job_submitted_idx');
        $this->addIndex('application_forms', ['status', 'submitted_at'], 'app_forms_status_submitted_idx');
        $this->addIndex('notifications', ['notifiable_type', 'notifiable_id', 'created_at'], 'notifications_notifiable_created_idx');
    }

    public function down(): void
    {
        $this->dropIndex('notifications', 'notifications_notifiable_created_idx');
        $this->dropIndex('application_forms', 'app_forms_status_submitted_idx');
        $this->dropIndex('application_forms', 'app_forms_job_submitted_idx');
        $this->dropIndex('application_forms', 'app_forms_user_submitted_idx');
        $this->dropIndex('job_posts', 'job_posts_employer_created_at_idx');
        $this->dropIndex('job_posts', 'job_posts_status_created_at_idx');
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function addIndex(string $table, array $columns, string $name): void
    {
        if (! Schema::hasTable($table) || Schema::hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($columns, $name) {
            $table->index($columns, $name);
        });
    }

    private function dropIndex(string $table, string $name): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($name) {
            $table->dropIndex($name);
        });
    }
};
