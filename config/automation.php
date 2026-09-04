<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Chatbot memory / OpenRouter debug logging
    |--------------------------------------------------------------------------
    |
    | debug_chat_memory: structured safe logs (ids, roles, lengths, truncated previews).
    | debug_chat_payload: may include full OpenRouter message contents (local only).
    |
    */

    'debug_chat_memory' => (bool) env('AUTOMATION_DEBUG_CHAT_MEMORY', false),
    'debug_chat_payload' => (bool) env('AUTOMATION_DEBUG_CHAT_PAYLOAD', false),

];
