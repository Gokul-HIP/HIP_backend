<?php

return [

    /*
    | Master switch for the disease-checker chatbot APIs.
    | When false, all /api/chatbot/* endpoints return 503 feature_disabled.
    */
    'disease_enabled' => filter_var(
        env('CHATBOT_DISEASE_ENABLED', false),
        FILTER_VALIDATE_BOOLEAN
    ),

    'idle_minutes' => 30,

    'max_symptoms' => 60,

    'typing_delay_ms' => 400,

    'disclaimer' => 'This is not a medical diagnosis.',

    'hospital_disabled_message' => 'This hospital has not enabled the disease checker yet',

    'emergency_number' => (string) env('CHATBOT_EMERGENCY_NUMBER', '112'),

    'emergency_text' => 'Some of your symptoms may need urgent care. If you are in danger, call emergency services now.',

    'crisis_number' => (string) env('CHATBOT_CRISIS_NUMBER', '14416'),

    'crisis_text' => "You're not alone. Please talk to someone now.",

    'symptoms_helper_text' => "Don't include your name or phone number. Typed text may be analysed by an AI service.",

    'appointment_types' => (($types = array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CHATBOT_APPOINTMENT_TYPES', 'In-Clinic'))
    )))) !== [] ? $types : ['In-Clinic']),

    'slot_minutes' => (int) env('CHATBOT_SLOT_MINUTES', 30),
    'slot_lead_minutes' => (int) env('CHATBOT_SLOT_LEAD_MINUTES', 30),
    'slot_horizon_days' => (int) env('CHATBOT_SLOT_HORIZON_DAYS', 14),

    /*
    | Single hold window for unpaid chatbot bookings: pending slot occupancy
    | and disease_chatbot_bookings.expires_at. CHATBOT_SLOT_HOLD_MINUTES is a
    | legacy fallback only.
    */
    'payment_hold_minutes' => (int) env(
        'CHATBOT_PAYMENT_HOLD_MINUTES',
        env('CHATBOT_SLOT_HOLD_MINUTES', 15)
    ),
    // Deprecated alias — prefer payment_hold_minutes.
    'slot_hold_minutes' => (int) env(
        'CHATBOT_PAYMENT_HOLD_MINUTES',
        env('CHATBOT_SLOT_HOLD_MINUTES', 15)
    ),

    /*
    | AI symptom→disease matching (OpenRouter). Keyword matching remains the fallback.
    */
    'ai_matching_enabled' => filter_var(
        env('CHATBOT_AI_MATCHING_ENABLED', false),
        FILTER_VALIDATE_BOOLEAN
    ),
    'ai_model' => env('CHATBOT_AI_MODEL'),
    'ai_timeout_seconds' => (int) env('CHATBOT_AI_TIMEOUT', 10),
    'ai_cache_minutes' => (int) env('CHATBOT_AI_CACHE_MINUTES', 1440),
    'ai_max_per_hour' => (int) env('CHATBOT_AI_MAX_PER_HOUR', 10),

    /*
    | Case-insensitive substring match against picked symptom labels and free text.
    | medical → 112 emergency; mental_health → Tele-MANAS crisis flow.
    | If both match, medical wins (immediate physical danger).
    */
    'red_flags' => [
        'medical' => [
            'chest pain',
            'chest tightness',
            'shortness of breath',
            'difficulty breathing',
            'severe bleeding',
            'unconscious',
            'fainting',
            'seizure',
            'slurred speech',
            'face drooping',
            'sudden weakness',
            'sudden severe headache',
        ],
        'mental_health' => [
            'suicidal',
            'self harm',
            'kill myself',
            'want to die',
            'end my life',
            'hurt myself',
        ],
    ],

    /*
    | Button styles sent to the app (variant + hex). One source of truth.
    */
    'styles' => [
        'primary' => [
            'variant' => 'primary',
            'bg' => '#0da2e7',
            'text' => '#ffffff',
            'border' => '#0da2e7',
            'icon' => null,
        ],
        'success' => [
            'variant' => 'success',
            'bg' => '#16a34a',
            'text' => '#ffffff',
            'border' => '#16a34a',
            'icon' => null,
        ],
        'neutral' => [
            'variant' => 'neutral',
            'bg' => '#f1f5f9',
            'text' => '#334155',
            'border' => '#cbd5e1',
            'icon' => null,
        ],
        'danger' => [
            'variant' => 'danger',
            'bg' => '#dc2626',
            'text' => '#ffffff',
            'border' => '#dc2626',
            'icon' => null,
        ],
        'crisis' => [
            'variant' => 'crisis',
            'bg' => '#7c3aed',
            'text' => '#ffffff',
            'border' => '#7c3aed',
            'icon' => null,
        ],
        'warning' => [
            'variant' => 'warning',
            'bg' => '#f59e0b',
            'text' => '#1c1917',
            'border' => '#f59e0b',
            'icon' => null,
        ],
    ],

];
