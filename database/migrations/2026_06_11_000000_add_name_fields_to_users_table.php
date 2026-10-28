<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 511)->change();
            $table->string('first_name', 255)->nullable();
            $table->string('last_name', 255)->nullable();
        });

        DB::table('users')
            ->select('id', 'name')
            ->chunkById(500, function ($users) {
                foreach ($users as $user) {
                    $parts = preg_split('/\s+/', trim($user->name), 2);

                    DB::table('users')->where('id', $user->id)->update([
                        'first_name' => $parts[0],
                        'last_name' => $parts[1] ?? null,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 255)->change();
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
