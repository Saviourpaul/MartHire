<?php

use App\Models\User;

// At <=767px, .nw-table__go action cells are display:none; only .nw-table__go--persist
// survives into the mobile card. A list whose sole per-row action sits in a plain
// nw-table__go cell is unusable on a phone, so the action cell must opt into --persist.

test('the admin users table keeps its row action reachable on mobile', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.applicants'))
        ->assertOk()
        ->assertSee('nw-table__go--persist', false);
});
