<?php

return [
    // Check-ins after this time are recorded as "late".
    'late_after' => env('SCHOOL_LATE_AFTER', '08:00'),
    'name' => env('SCHOOL_NAME', 'مدرستنا'),
];
