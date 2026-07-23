<?php

namespace App\Modules\Workflow\Executors\Actions;

class SendPushExecutor extends AbstractMessagingExecutor
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
