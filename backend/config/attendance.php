<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Attendance Location Tracking
    |--------------------------------------------------------------------------
    |
    | Disable this to skip geofence/location validation during clock in/out.
    | Set ATTENDANCE_REQUIRE_LOCATION=true to enable it again.
    |
    */
    'require_location' => env('ATTENDANCE_REQUIRE_LOCATION', false),
];
