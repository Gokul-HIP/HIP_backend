<?php

return [
    /*
    | How long an unpaid HMS booking keeps its membership usage and reward
    | reservation. App bookings keep the 15-minute reward hold.
    */
    'pending_hold_minutes' => (int) env('HMS_PENDING_HOLD_MINUTES', 30),

    /*
    | A success payment that arrives after the hold expired can still settle
    | inside this window when the amount matches. After that, HMS must refund
    | or rebook.
    */
    'late_payment_window_hours' => (int) env('HMS_LATE_PAYMENT_WINDOW_HOURS', 24),
];
