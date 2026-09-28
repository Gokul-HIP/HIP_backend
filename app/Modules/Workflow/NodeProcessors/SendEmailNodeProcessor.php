<?php

namespace App\Modules\Workflow\NodeProcessors;

use App\Modules\Workflow\Services\Runtime\ActionDispatcher;
use App\Modules\Workflow\Services\Runtime\ChannelManager;
use App\Modules\Workflow\Services\Runtime\InvoicePdfAttachmentService;
use App\Modules\Workflow\Services\Runtime\TemplateManager;
use App\Modules\Workflow\Services\Runtime\VariableResolver;

class SendEmailNodeProcessor extends AbstractMessagingNodeProcessor
{
    public function __construct(
        ActionDispatcher $actionDispatcher,
        ChannelManager $channelManager,
        TemplateManager $templateManager,
        VariableResolver $variableResolver,
        protected InvoicePdfAttachmentService $invoicePdfAttachment,
    ) {
        parent::__construct($actionDispatcher, $channelManager, $templateManager, $variableResolver);
    }

    public function type(): string
    {
        return 'sendEmail';
    }

    protected function channel(): string
    {
        return 'email';
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $payload
     * @return list<array{filename: string, content: string, mime: string}>
     */
    protected function resolveAttachments(array $data, array $payload): array
    {
        if (! $this->invoicePdfAttachment->isEnabled($data)) {
            return [];
        }

        return $this->invoicePdfAttachment->attachments($payload);
    }
}
