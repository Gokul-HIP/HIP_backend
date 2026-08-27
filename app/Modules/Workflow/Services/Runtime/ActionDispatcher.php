<?php

namespace App\Modules\Workflow\Services\Runtime;

use App\Modules\Workflow\Enums\ActionCategory;
use App\Modules\Workflow\Support\NodeTypeNormalizer;

class ActionDispatcher
{
    /** @var array<string, ActionCategory> */
    protected array $categoryMap = [
        'sendWhatsApp' => ActionCategory::Messaging,
        'sendEmail' => ActionCategory::Messaging,
        'sendPush' => ActionCategory::Messaging,
        'sendSMS' => ActionCategory::Messaging,
        'sendTemplate' => ActionCategory::Messaging,
        'webhook' => ActionCategory::Integration,
        'databaseUpdate' => ActionCategory::Database,
        'createRecord' => ActionCategory::Database,
        'dbDelete' => ActionCategory::Database,
        'condition' => ActionCategory::Flow,
        'delay' => ActionCategory::Flow,
        'end' => ActionCategory::Flow,
    ];

    public function categoryFor(string $nodeType): ActionCategory
    {
        $normalized = NodeTypeNormalizer::normalize($nodeType);

        return $this->categoryMap[$normalized] ?? ActionCategory::Flow;
    }

    public function dispatch(string $nodeType, callable $handler): mixed
    {
        return $handler();
    }
}
