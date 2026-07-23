<?php

namespace App\Modules\Workflow\Executors\Actions;

class SendSMSExecutor extends AbstractMessagingExecutor
{
    public function type(): string
    {
        return 'sendSMS';
    }

    protected function channel(): string
    {
        return 'sms';
    }
}
