<?php

namespace App\Modules\Workflow\NodeProcessors;

use App\Modules\Workflow\Services\Runtime\ActionDispatcher;
use App\Modules\Workflow\Services\Runtime\ChannelManager;
use App\Modules\Workflow\Services\Runtime\InvoicePdfAttachmentService;
use App\Modules\Workflow\Services\Runtime\TemplateManager;
use App\Modules\Workflow\Services\Runtime\VariableResolver;

class SendWhatsAppNodeProcessor extends AbstractMessagingNodeProcessor
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
        return 'sendWhatsApp';
    }

    protected function channel(): string
    {
        return 'whatsapp';
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

        return $this->invoicePdfAttachment->attachments($payload, 'Send WhatsApp');
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function channelOptions(array $data, array $payload): array
    {
        $templateName = trim((string) (
            $data['whatsappTemplateName']
            ?? $data['whatsapp_template_name']
            ?? $data['providerTemplateName']
            ?? $data['approvedTemplateName']
            ?? ''
        ));

        $language = trim((string) (
            $data['templateLanguage']
            ?? $data['template_language']
            ?? $data['whatsapp_template_language']
            ?? 'en'
        ));

        $components = $data['components']
            ?? $data['templateComponents']
            ?? $data['whatsapp_components']
            ?? [];

        if (! is_array($components)) {
            $components = [];
        }

        $options = [];

        if ($templateName !== '') {
            $options['mode'] = 'template';
            $options['template_name'] = $this->variableResolver->resolve($templateName, $payload);
            $options['template_language'] = $language !== '' ? $language : 'en';
            $options['components'] = $this->variableResolver->resolveDeep($components, $payload);
        }

        return $options;
    }
}
