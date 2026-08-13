<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Automation UI (React Flow builder)
    |--------------------------------------------------------------------------
    |
    | Absolute URL of the hip-automation Next.js app. Livewire admin embeds
    | /embed/workflow-builder in an iframe (template + workflow modes). That
    | route bypasses frontend login and renders the builder only. Saves are
    | handled by Livewire via WorkflowTemplateService / WorkflowBuilderService.
    |
    */
    'ui_url' => rtrim(env('AUTOMATION_UI_URL', env('WORKFLOW_BUILDER_URL', 'https://hip-automation-v35j.vercel.app')), '/'),
];
