<?php

/**
 * Workflow Configuration & Unavailable Triggers
 *
 * CANONICAL BACKEND SOURCE OF TRUTH:
 * This list defines trigger types that are recognized in schemas/catalogs but
 * are currently unwired (no system event or scheduler dispatches them).
 *
 * BEHAVIOR:
 * - TriggerSchemaRegistry sets 'available' => false in GET /api/workflow/triggers.
 * - WorkflowPublishService blocks publishing with HTTP 422 ("This trigger is not available yet").
 * - Draft saving remains allowed.
 * - Removing a trigger type from this list immediately re-enables it.
 *
 * CRITICAL EMBED-MODE SYNCHRONIZATION:
 * In embed mode (inside the Laravel Admin iframe), the React Flow builder
 * (HIP_Automation) runs without making an extra network roundtrip to /api/workflow/triggers.
 * It reads a synchronized static set in:
 *   HIP_Automation: src/components/flow/config/triggers/shared.js (UNAVAILABLE_TRIGGERS)
 * Whenever you add or remove an unavailable trigger here, you MUST update
 * UNAVAILABLE_TRIGGERS in shared.js at the same time to prevent drift.
 */
return [
    'unavailable_triggers' => [
        'labReportReady',
        'medicineRefillDue',
        'membershipExpiry',
        'procedureCompleted',
        'campaignTriggered',
        'labCompleted',
        'userPlanExpiry',
        'familyPackageTierUpdated',
        'webhookEvent',
    ],
];

