<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Real charges switch
    |--------------------------------------------------------------------------
    |
    | Guard rail for App\Services\PaymentGuard. LIVE credentials (Culqi
    | sk_live_* or PayPal mode=live) are only allowed to actually charge
    | when this is true AND the app is running in the "production"
    | environment. Any other combination blocks the charge — see
    | RealChargeBlockedException.
    |
    */

    'live' => env('PAYMENTS_LIVE', false),

];
