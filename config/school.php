<?php

return [
    // Check-ins after this time are recorded as "late".
    'late_after' => env('SCHOOL_LATE_AFTER', '08:00'),
    'name' => env('SCHOOL_NAME', 'مدرستنا'),

    // Staff who check in after this time are recorded as "late".
    'staff_late_after' => env('STAFF_LATE_AFTER', '07:45'),

    /*
     * Public trial mode for showing the product to a school (see docs/DEMO.md).
     * Turns on: a landing page, one-click role logins, a demo banner, safeguards (demo accounts cannot be locked,
     * messages are only logged, never sent) and a scheduled `demo:reset` that wipes and re-seeds ALL data.
     * NEVER enable this on a database that holds real school data.
     */
    'demo_mode' => (bool) env('SCHOOL_DEMO_MODE', false),
    'demo_reset_hours' => max(1, (int) env('DEMO_RESET_HOURS', 6)),
    // Where interested visitors reach you: international number without "+" (e.g. 962791234567) and/or an email.
    'contact_whatsapp' => preg_replace('/\D+/', '', (string) env('DEMO_WHATSAPP', '')),
    'contact_email' => env('DEMO_EMAIL'),

    // Shows one-click demo sign-ins on the login page. Demo/local use only; never enable in production.
    'demo_logins' => (bool) env('SCHOOL_DEMO_LOGINS', false),
];
