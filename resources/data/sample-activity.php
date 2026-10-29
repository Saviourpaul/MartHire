<?php

/*
 * Placeholder activity for the signed-in dashboard while applications are not connected to the backend.
 * Everything here is made up. To go live, pass real data to the `dashboard` view as `$activity` with the
 * same keys and this file is no longer read:
 *
 *   applications: [job (a job id from the jobs feed), status, applied_at]   status: Applied|In review|Interview|Offer|Declined
 *   activity:     [icon (an icon name), text, at]                           newest first
 *   interviews:   [job, at, format]                                          soonest first
 *   series:       [labels, applications, responses]                          one number per label, oldest first
 *   spark:        [name => 8 weekly numbers, oldest first]                   tiny trend lines for the stat cards (the cards
 *                                                                            compare the latest 4 weeks with the 4 before)
 *   saved:        number of saved jobs
 *   profile:      [label => done?]                                           steps that make up "profile strength"
 *
 * Only `applications` and `series` are required; the dashboard hides any section whose data is missing.
 */

return [
    'applications' => [
        ['job' => 'sample-1', 'status' => 'Interview', 'applied_at' => now()->subDays(2)->toIso8601String()],
        ['job' => 'sample-2', 'status' => 'In review', 'applied_at' => now()->subDays(3)->toIso8601String()],
        ['job' => 'sample-19', 'status' => 'Applied', 'applied_at' => now()->subDays(4)->toIso8601String()],
        ['job' => 'sample-4', 'status' => 'Offer', 'applied_at' => now()->subDays(9)->toIso8601String()],
        ['job' => 'sample-8', 'status' => 'In review', 'applied_at' => now()->subDays(12)->toIso8601String()],
        ['job' => 'sample-10', 'status' => 'Interview', 'applied_at' => now()->subDays(15)->toIso8601String()],
        ['job' => 'sample-7', 'status' => 'Declined', 'applied_at' => now()->subDays(21)->toIso8601String()],
        ['job' => 'sample-3', 'status' => 'In review', 'applied_at' => now()->subDays(27)->toIso8601String()],
        ['job' => 'sample-12', 'status' => 'Declined', 'applied_at' => now()->subDays(34)->toIso8601String()],
        ['job' => 'sample-6', 'status' => 'Applied', 'applied_at' => now()->subDays(41)->toIso8601String()],
        ['job' => 'sample-15', 'status' => 'In review', 'applied_at' => now()->subDays(55)->toIso8601String()],
        ['job' => 'sample-18', 'status' => 'Applied', 'applied_at' => now()->subDays(70)->toIso8601String()],
    ],

    'interviews' => [
        ['job' => 'sample-1', 'at' => now()->addDays(2)->setTime(14, 30)->toIso8601String(), 'format' => 'Video call'],
        ['job' => 'sample-10', 'at' => now()->addDays(6)->setTime(10, 0)->toIso8601String(), 'format' => 'Video call'],
    ],

    'activity' => [
        ['icon' => 'calendar', 'text' => 'Interview scheduled with Brightwell Studio', 'at' => now()->subHours(2)->toIso8601String()],
        ['icon' => 'eye', 'text' => 'Northline Labs viewed your application', 'at' => now()->subHours(9)->toIso8601String()],
        ['icon' => 'check', 'text' => 'Harbor & Pine sent you an offer', 'at' => now()->subDays(1)->toIso8601String()],
        ['icon' => 'send', 'text' => 'You applied to Savannah Labs', 'at' => now()->subDays(4)->toIso8601String()],
        ['icon' => 'message', 'text' => 'Tidewater Bank asked for one more detail', 'at' => now()->subDays(6)->toIso8601String()],
        ['icon' => 'briefcase', 'text' => 'Orchard Learning closed a role you applied to', 'at' => now()->subDays(10)->toIso8601String()],
    ],

    'series' => [
        'labels' => collect(range(5, 0))->map(fn (int $monthsAgo) => now()->subMonths($monthsAgo)->format('M'))->all(),
        'applications' => [1, 1, 2, 2, 3, 3],
        'responses' => [0, 1, 1, 2, 2, 3],
    ],

    'spark' => [
        'applications' => [1, 2, 1, 3, 2, 4, 3, 5],
        'review' => [1, 1, 2, 2, 2, 3, 3, 4],
        'interviews' => [0, 0, 1, 0, 1, 1, 2, 2],
        'saved' => [2, 2, 3, 3, 3, 4, 4, 5],
    ],

    'saved' => 5,

    'profile' => [
        'Add your name and email' => true,
        'Verify your email address' => null, // null = worked out from the real account
        'Write a short introduction' => false,
        'Upload your CV' => false,
        'List your key skills' => true,
        'Add where you want to work' => true,
    ],
];
