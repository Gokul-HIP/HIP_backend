<?php

namespace App\Modules\Workflow\Executors\Actions;

class SendWhatsAppExecutor extends AbstractMessagingExecutor
{
    public function type(): string
    {
        return 'sendWhatsApp';
    }

    protected function channel(): string
    {
        return 'whatsapp';
    }
}
