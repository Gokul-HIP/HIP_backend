<?php

namespace App\Modules\Workflow\NodeProcessors;

class SendWhatsAppNodeProcessor extends AbstractMessagingNodeProcessor
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
