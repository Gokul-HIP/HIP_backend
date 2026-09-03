<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'razorpay' => [
        'key' => env('RAZORPAY_KEY'),
        'secret' => env('RAZORPAY_SECRET'),
    ],

    'google_vision' => [
        'api_key' => env('GOOGLE_VISION_API_KEY'),
        'endpoint' => env('GOOGLE_VISION_ENDPOINT'),
        'imagemagick_binary' => env('IMAGEMAGICK_BINARY', 'magick'),
        'ca_bundle' => env('GOOGLE_VISION_CA_BUNDLE', 'C:\xampp\php\extras\ssl\cacert.pem'),
        'render_density' => env('GOOGLE_VISION_RENDER_DENSITY', 300),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'default_model' => env('OPENROUTER_DEFAULT_MODEL', 'openai/gpt-4o-mini'),
        'fallback_model' => env('OPENROUTER_FALLBACK_MODEL', 'openrouter/auto'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Workflow / Hospital Automation AI
    |--------------------------------------------------------------------------
    |
    | Shared HTTP endpoint used by aiPrompt (AiPromptExecutor) and sendAiChat.
    | sendAiChat fails closed when this is unset; aiPrompt keeps its legacy stub.
    |
    */
    'workflow_ai' => [
        'endpoint' => env('WORKFLOW_AI_ENDPOINT'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment / Invoice
    |--------------------------------------------------------------------------
    */
    'gst_percent' => (float) env('GST_PERCENT', 5),
    'service_charges_percent' => (float) env('SERVICE_CHARGES', 3),
    'payment_gateway_charges_percent' => (float) env('PAYMENT_GATEWAY_CHARGES', 2),

    'doctor_notifications' => [
        'enabled' => env('DOCTOR_NOTIFICATIONS_ENABLED', true),
    ],

];
