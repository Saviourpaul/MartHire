<?php

return [
    'inactivity_days' => (int) env('ACCOUNT_INACTIVITY_DAYS', 30),
    'warning_days' => (int) env('ACCOUNT_DELETION_WARNING_DAYS', 7),
    'batch_size' => (int) env('ACCOUNT_DELETION_BATCH_SIZE', 5000),
    'warning_queue_retry_minutes' => (int) env('ACCOUNT_DELETION_WARNING_RETRY_MINUTES', 60),
];
