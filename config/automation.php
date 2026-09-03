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

    /*
    |--------------------------------------------------------------------------
    | Real-notification automation testing (manual only)
    |--------------------------------------------------------------------------
    |
    | Used exclusively by `php artisan automation:test`. When disabled, the
    | command refuses to run. Recipients come from env — never from patients.
    | Keep AUTOMATION_TEST_ENABLED=false except while manually testing.
    |
    */
    'test' => [
        'enabled' => filter_var(env('AUTOMATION_TEST_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'hospital_id' => env('AUTOMATION_TEST_HOSPITAL_ID') !== null && env('AUTOMATION_TEST_HOSPITAL_ID') !== ''
            ? (int) env('AUTOMATION_TEST_HOSPITAL_ID')
            : null,
        'organization_id' => env('AUTOMATION_TEST_ORGANIZATION_ID') !== null && env('AUTOMATION_TEST_ORGANIZATION_ID') !== ''
            ? (int) env('AUTOMATION_TEST_ORGANIZATION_ID')
            : null,
        'accounts' => [
            1 => [
                'label' => env('AUTOMATION_TEST_ACCOUNT_1_LABEL', 'Test Account 1'),
                'user_id' => env('AUTOMATION_TEST_USER_ID'),
                'phone' => env('AUTOMATION_TEST_PHONE'),
                'email' => env('AUTOMATION_TEST_EMAIL'),
                'whatsapp' => env('AUTOMATION_TEST_WHATSAPP'),
                'device_token' => env('AUTOMATION_TEST_DEVICE_TOKEN'),
            ],
            2 => [
                'label' => env('AUTOMATION_TEST_ACCOUNT_2_LABEL', 'Test Account 2'),
                'user_id' => env('AUTOMATION_TEST_2_USER_ID'),
                'phone' => env('AUTOMATION_TEST_2_PHONE'),
                'email' => env('AUTOMATION_TEST_2_EMAIL'),
                'whatsapp' => env('AUTOMATION_TEST_2_WHATSAPP'),
                'device_token' => env('AUTOMATION_TEST_2_DEVICE_TOKEN'),
            ],
        ],
    ],
];
