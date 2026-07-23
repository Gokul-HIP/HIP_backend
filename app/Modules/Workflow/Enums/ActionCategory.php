<?php

namespace App\Modules\Workflow\Enums;

enum ActionCategory: string
{
    case Messaging = 'messaging';
    case Database = 'database';
    case Integration = 'integration';
    case Ai = 'ai';
    case Flow = 'flow';
}
