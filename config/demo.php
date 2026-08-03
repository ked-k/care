<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Live demo mode
    |--------------------------------------------------------------------------
    |
    | When enabled, all data-mutating requests (POST/PUT/PATCH/DELETE) are
    | blocked so visitors can explore the live demo without changing the seeded
    | data. The blocked request is bounced back with a "restricted" toast.
    | Authentication (login/logout) is always allowed.
    |
    */

    'enabled' => env('DEMO_MODE', false),

    'message' => env('DEMO_MESSAGE', 'This action is restricted in the live demo.'),

];
