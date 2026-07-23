<?php

namespace App\Modules\Workflow\Executors\Actions;

class SendEmailExecutor extends AbstractMessagingExecutor
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
