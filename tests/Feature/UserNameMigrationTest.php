<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('structured name migration backfills legacy users and rolls back without losing their data', function () {
    $migration = require database_path('migrations/2026_06_11_000000_add_name_fields_to_users_table.php');

    $this->assertDatabaseCount('users', 0);
    $migration->down();

    $legacyColumns = Schema::getColumnListing('users');
    $legacyNames = [
        ['Ada Lovelace', 'Ada', 'Lovelace'],
        ['Prince', 'Prince', null],
        ['  Mary   Jane  Watson  ', 'Mary', 'Jane  Watson'],
        [" \t Grace \t Brewster Murray Hopper \n", 'Grace', 'Brewster Murray Hopper'],
    ];
    $legacyUsers = [];

    foreach ($legacyNames as $index => [$name, $firstName, $lastName]) {
        $user = [
            'name' => $name,
            'email' => 'legacy-'.$index.'@example.com',
            'email_verified_at' => '2026-10-01 09:30:00',
            'password' => 'existing-password-hash-'.$index,
            'remember_token' => 'existing-token-'.$index,
            'created_at' => '2026-09-01 09:30:00',
            'updated_at' => '2026-09-15 09:30:00',
        ];
        $user['id'] = DB::table('users')->insertGetId($user);
        $legacyUsers[] = [$user, $firstName, $lastName];
    }

    $migration->up();

    $this->assertTrue(Schema::hasColumns('users', ['first_name', 'last_name']));

    foreach ($legacyUsers as [$user, $firstName, $lastName]) {
        $this->assertDatabaseHas('users', array_merge($user, [
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]));
    }

    $migration->down();

    $this->assertEqualsCanonicalizing($legacyColumns, Schema::getColumnListing('users'));
    $this->assertDatabaseCount('users', count($legacyUsers));

    foreach ($legacyUsers as [$user]) {
        $this->assertDatabaseHas('users', $user);
    }
});
