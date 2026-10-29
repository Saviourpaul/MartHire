<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            $table->string('company_logo_path')->nullable()->after('organization_id');
            $table->text('about')->nullable()->after('company_logo_path');
            $table->string('careers_url')->nullable()->after('about');
            $table->string('public_contact_email')->nullable()->after('careers_url');
            $table->string('public_contact_phone', 50)->nullable()->after('public_contact_email');
            $table->string('default_employment_type', 50)->nullable()->after('default_interview_contact_phone');
            $table->text('default_application_instructions')->nullable()->after('default_employment_type');
        });
    }

    public function down(): void
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            $table->dropColumn([
                'company_logo_path',
                'about',
                'careers_url',
                'public_contact_email',
                'public_contact_phone',
                'default_employment_type',
                'default_application_instructions',
            ]);
        });
    }
};
