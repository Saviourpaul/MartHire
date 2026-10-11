<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nigeria_states', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('type')->default('state');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('nigeria_local_government_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nigeria_state_id')->constrained('nigeria_states')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['nigeria_state_id', 'slug'], 'nigeria_lgas_state_slug_unique');
            $table->index(['nigeria_state_id', 'name'], 'nigeria_lgas_state_name_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nigeria_local_government_areas');
        Schema::dropIfExists('nigeria_states');
    }
};
