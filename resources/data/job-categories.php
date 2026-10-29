<?php

/*
 * Categories shown on the front page and used as filters on the jobs page.
 * A job belongs to a category when its `field` matches a `name` here.
 * `icon` must be a name that exists in views/components/icon.blade.php.
 */

return [
    ['name' => 'Design', 'icon' => 'pencil', 'blurb' => 'Product, brand and research'],
    ['name' => 'Engineering', 'icon' => 'code', 'blurb' => 'Software, mobile and infrastructure'],
    ['name' => 'Marketing', 'icon' => 'megaphone', 'blurb' => 'Growth, content and communications'],
    ['name' => 'Sales', 'icon' => 'trending-up', 'blurb' => 'Accounts, partnerships and business development'],
    ['name' => 'Customer support', 'icon' => 'headset', 'blurb' => 'Support, success and onboarding'],
    ['name' => 'Healthcare', 'icon' => 'heart', 'blurb' => 'Nursing, care and clinical roles'],
    ['name' => 'Finance', 'icon' => 'chart', 'blurb' => 'Accounting, analysis and banking'],
    ['name' => 'Operations', 'icon' => 'layers', 'blurb' => 'Logistics, planning and delivery'],
    ['name' => 'Education', 'icon' => 'book', 'blurb' => 'Teaching, training and learning'],
];
