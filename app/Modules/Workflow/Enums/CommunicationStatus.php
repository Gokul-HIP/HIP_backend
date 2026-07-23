<?php

namespace App\Modules\Workflow\Enums;

enum CommunicationStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Failed = 'failed';
    case Retry = 'retry';
    case Skipped = 'skipped';
}
