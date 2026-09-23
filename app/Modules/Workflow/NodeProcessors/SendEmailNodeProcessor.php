<?php

namespace App\Modules\Workflow\NodeProcessors;

class SendEmailNodeProcessor extends AbstractMessagingNodeProcessor
{
    public function type(): string
    {
        return 'sendEmail';
    }

    protected function channel(): string
    {
        return 'email';
    }
}
