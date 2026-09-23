<?php

namespace App\Modules\Workflow\NodeProcessors;

class SendSMSNodeProcessor extends AbstractMessagingNodeProcessor
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
