<?php

return [
    // Check-ins after this time are recorded as "late".
    'late_after' => env('SCHOOL_LATE_AFTER', '08:00'),
    'name' => env('SCHOOL_NAME', 'مدرستنا'),

    // Staff who check in after this time are recorded as "late".
    'staff_late_after' => env('STAFF_LATE_AFTER', '07:45'),

    // Shows one-click demo sign-ins on the login page. Demo/local use only; never enable in production.
    'demo_logins' => (bool) env('SCHOOL_DEMO_LOGINS', false),
];
