<?php

return [
    /*
    | Inbox rows older than this are removed by hms:inbox:prune.
    | The daily schedule passes --days=90, which matches this default.
    */
    'inbox_retention_days' => (int) env('HMS_INBOX_RETENTION_DAYS', 90),
];
