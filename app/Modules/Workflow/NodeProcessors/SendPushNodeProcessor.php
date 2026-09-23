<?php

namespace App\Modules\Workflow\NodeProcessors;

class SendPushNodeProcessor extends AbstractMessagingNodeProcessor
{
    public function type(): string
    {
        return 'sendPush';
    }

    protected function channel(): string
    {
        return 'push';
    }
}
