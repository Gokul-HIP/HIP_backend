<?php

/*
| HIP_ENFORCE_ACTIVE=false in the environment turns off account deactivation
| checks at login, in middleware, and in Filament panel access. No code change
| is required. Leave the variable unset, or set it to true, to enforce is_active.
*/

$enforceActive = env('HIP_ENFORCE_ACTIVE', true);

return [
    'enforce_active' => filter_var($enforceActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,

    // Patient-app pharmacy order deep link. Change these in one place if the frontend route moves.
    'pharmacy_deeplink' => [
        'screen' => 'prescription_details',
        'route' => '/prescription-details',
        'order_param' => 'order_id',
    ],
];
