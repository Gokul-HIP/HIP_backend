<?php

return [
    /*
    | Inbox rows older than this are removed by hms:inbox:prune.
    | The daily schedule passes --days=90, which matches this default.
    */
    'inbox_retention_days' => (int) env('HMS_INBOX_RETENTION_DAYS', 90),

    /*
    | When enabled, the Super Admin Transaction Report (/admin/transactions)
    | queries hmis_transactions instead of transactions.
    */
    'transaction_report_use_hms' => (bool) env('TRANSACTION_REPORT_USE_HMS', false),
];
