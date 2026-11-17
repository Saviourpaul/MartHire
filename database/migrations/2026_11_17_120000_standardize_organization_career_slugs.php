<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_slug_aliases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            // Keep a row for organizations that had no slug so rollback can
            // restore their original NULL value as well as existing slugs.
            $table->string('slug')->nullable()->unique();
        });

        DB::table('organizations')
            ->orderBy('id')
            ->chunkById(500, function ($organizations): void {
                $aliases = $organizations
                    ->map(fn (object $organization): array => [
                        'organization_id' => $organization->id,
                        'slug' => $organization->slug,
                    ])->all();

                if ($aliases !== []) {
                    DB::table('organization_slug_aliases')->insert($aliases);
                }
            });

        DB::table('organizations')->orderBy('id')->chunkById(500, function ($organizations): void {
            foreach ($organizations as $organization) {
                DB::table('organizations')->where('id', $organization->id)->update([
                    'slug' => 'pending-organization-slug-'.$organization->id.'-'.Str::uuid(),
                ]);
            }
        });

        $aliasOwners = DB::table('organization_slug_aliases')
            ->whereNotNull('slug')
            ->pluck('organization_id', 'slug')
            ->all();
        $claimed = [];

        DB::table('organizations')->orderBy('id')->chunkById(500, function ($organizations) use (&$aliasOwners, &$claimed): void {
            foreach ($organizations as $organization) {
                $base = mb_substr(Str::slug($organization->name) ?: 'organization', 0, 220);
                $slug = $base;
                $suffix = 2;

                while (isset($claimed[$slug])
                    || (isset($aliasOwners[$slug]) && (int) $aliasOwners[$slug] !== (int) $organization->id)) {
                    $ending = '-'.$suffix++;
                    $slug = mb_substr($base, 0, 255 - mb_strlen($ending)).$ending;
                }

                $claimed[$slug] = (int) $organization->id;
                DB::table('organizations')->where('id', $organization->id)->update(['slug' => $slug]);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('organization_slug_aliases')) {
            return;
        }

        $aliases = DB::table('organization_slug_aliases')->orderBy('organization_id')->get();

        foreach ($aliases as $alias) {
            DB::table('organizations')->where('id', $alias->organization_id)->update([
                'slug' => 'rollback-organization-slug-'.$alias->organization_id.'-'.Str::uuid(),
            ]);
        }

        foreach ($aliases as $alias) {
            DB::table('organizations')->where('id', $alias->organization_id)->update(['slug' => $alias->slug]);
        }

        Schema::drop('organization_slug_aliases');
    }
};
