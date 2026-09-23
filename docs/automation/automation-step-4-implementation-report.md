# Step 4 Implementation Report — Generic Workflow Automation Engine

**Date:** 2026-09-23  
**Scope:** Generic runtime support for the existing saved-workflow JSON contract. The 13 named campaigns were used as a capability matrix only. Workflows 14–15 remain unknown and were not fabricated. Step 5 was not implemented.

---

## 1. Generic architecture implemented

```
Saved definition (nodes / edges / node data / configuration flags)
        ↓
WorkflowCompiler (unchanged graph compile)
        ↓
AutomationEngine::handle  (lookup + fan-out; appointmentBooked also runs suppression)
        ↓
WorkflowRepository::findPublishedByTrigger
        ↓
WorkflowExecutor (graph walk, wait/resume, branch handles)
        ↓
NodeExecutorRegistry (nodeType → executor)
        ↓
Generic node executors (condition / delay / messaging / dbUpdate / …)
        ↓
Saved edges → next node → end
```

No second engine. No per-workflow or per-campaign PHP classes.

---

## 2. Existing engine components reused

- `HospitalAutomationTriggerService`
- `AutomationEngine::handle` / `executeWorkflow`
- `WorkflowRepository`
- `WorkflowCompiler` / `WorkflowExecutor`
- `NodeExecutorRegistry` and existing executors (`DelayExecutor`, `ConditionExecutor`, messaging, `UpdateRecordExecutor`, triggers, medicine due bridge)
- `ChannelManager`, `VariableResolver`, `TemplateManager`
- `ContinueWorkflowExecutionJob`
- `WorkflowExecutionBridge` (medicine due)

---

## 3. Existing components modified

| Component | Change |
|---|---|
| `ConditionEngine` | Expression API; `>=` / `<=` on rules-array leaves |
| `ConditionExecutor` | Prefer `data.expression`; live facts enrich; malformed → failed node |
| `DelayScheduler` | `waitType` duration + relative_date; legacy type/value kept |
| `DelayExecutor` | Passes context; fails on invalid relative_date (no silent default) |
| `AutomationEngine` | Facts enrich; `suppressOnAppointment` hook on `appointmentBooked` |
| `AutomationContextBuilder` | Unchanged (facts live in `AutomationFactsBuilder`) |
| `WorkflowExecutor::resume` | No-op unless waiting/running/started |
| `ContinueWorkflowExecutionJob` | Same resumable-status guard |
| `AbstractMessagingExecutor` | Body fallback; title variables |
| `ChannelManager` | Logical `caregiver` |
| `VariableResolver` | `booking_link`, `hospital_phone`, `followup_date`, `caregiver_contact` |
| `NodeTypeNormalizer` | `updateRecord` → `databaseUpdate` |

---

## 4. New generic processors/executors created

**None.** New support classes only:

- `ExpressionEvaluator` (no `eval()`)
- `AutomationFactsBuilder`
- `WaitingExecutionSuppressor`
- `InvalidExpressionException` / `InvalidDelayConfiguration`

---

## 5. Registry changes

No new executor registrations. `updateRecord` is a normalizer alias onto existing `UpdateRecordExecutor` (`databaseUpdate`).

---

## 6. Compiler changes

**None.** Arbitrary valid graphs already compile. New graphs A–E compile and execute in tests.

---

## 7. Condition engine changes

Saved contract `data.expression` is evaluated with:

`==` `!=` `<` `>` `<=` `>=` `&&` `||` parentheses, dotted paths, quoted strings, booleans, numbers.

Rules/conditions arrays remain supported. Empty rules still evaluate true. Malformed expressions fail the node with a message (not PHP `eval`, not hardcoded Dentistry/female/age 60).

---

## 8. Wait / delay changes

- **Duration:** `waitType=duration` + `amount` + `unit` (hours/days/…). Legacy `type`/`value` still works.
- **Relative date:** `relativeDateField` from context/facts, `relativeOffsetDirection` before/after/on, amount/unit.
- **Past target:** delay **0 seconds** (resume immediately). Documented behavior.
- **Invalid/missing date field:** throws; executor fails the node. Not converted to a default minute wait.

---

## 9. Context / facts changes

`AutomationFactsBuilder::enrich()` (also re-run at condition/wait time):

| Fact | Derivation |
|---|---|
| `appointment.exists` | Live query: same `patient_id` + `hospital_id`, non-cancelled `doctor_bookings`; else event appointment / `appointment.exists` in payload |
| `appointment.department` | Event booking department only (not another booking) |
| `followup.exists` / `followup.date` | Payload, then event prescription `follow_up_date` / follow-up booking, then scoped prescription or future `is_follow_up` booking |
| `last_visit` | Whole days since last **completed** booking for same patient+hospital |
| `patient.age` | Payload age or `dob` |
| `patient.relationship` / `gender` | Patient or event booking |
| `caregiver_contact` | Payload, caregiver object, parent person mobile, member emergency phone |
| `booking_link` | Payload or booking `online_consultation_link` (no invented URL) |
| `hospital_phone` | Payload or hospital admin/ambulance contacts |

Does not pick an unrelated latest booking when the event already has that entity.

---

## 10. Variable resolver changes

Preserved `{{patient_name}}`, `{{hospital_name}}`, `{{doctor_name}}`, etc. Added flat keys used by saved templates: `booking_link`, `hospital_phone`, `followup_date`, `caregiver_contact`. Nested `{{patient.name}}` not added (saved templates use underscores).

---

## 11. Recipient changes

Logical `caregiver` resolves from context contact fields. Does **not** return the string `"caregiver"`. Unresolved caregiver does **not** fall back to the patient phone.

---

## 12. Messaging changes

Manual body order:

- WhatsApp/SMS: `messageTemplate` → `message` → `body` (`??`, empty string is kept)
- Push: `body` → `messageTemplate` → `message`
- Title/subject variables are resolved

---

## 13. suppressOnAppointment

On `appointmentBooked` in `AutomationEngine::handle()`:

Waiting executions with the **same patient_id and hospital_id** whose **published definition** has `suppressOnAppointment === true` are marked `cancelled`.

Does not inspect `campaignKey`, workflow id, or name.

`ContinueWorkflowExecutionJob` / `resume()` will not continue cancelled/completed/failed executions.

---

## 14. Execution-state changes

No schema change. Cancelled status used for suppression. Resume guards added. `resume_at` column still unused (job delay remains the clock).

---

## 15. Trigger naming verification (Medicine Reminder)

| Source | Value |
|---|---|
| Saved campaign JSON | `medicineReminder` |
| `NodeTypeNormalizer` | `medicineReminder` → `medicineReminderDue` (intentional alias, preserved) |
| `TriggerCatalog` | `medicineReminderDue` |
| Due runtime | Cron → job → `WorkflowExecutionBridge` → `WorkflowExecutor::start` with `medicineReminderDue` |
| Fan-out | **Not** used for due |

No rename. Bridge path unchanged.

---

## 16. Capability matrix (13 reference workflows)

Used only to discover the union: duration + relative_date waits, JEXL-like expressions, `message` + recipient patient/doctor/caregiver, `campaignKey`/`campaignStep` as data, `suppressOnAppointment`, facts listed above. **No PHP branches on those campaign names.**

Workflows **14–15: unknown**.

---

## 17. NEW workflow graphs used for generic tests

Not copies of the 13 files. Constructed in `GenericWorkflowEngineTest`:

| Graph | Shape |
|---|---|
| A | Trigger → SMS (`message` + `{{patient_name}}`) → End |
| B | Trigger → Wait 2 hours → Condition `appointment.exists == false` → SMS → End |
| C | Trigger → Condition `patient.age >= 60` → true SMS / false WhatsApp → End |
| D | Trigger → Wait → `updateRecord` → Condition → SMS → End |
| E | Trigger → Condition → Wait → Condition → SMS → End |

Plus caregiver message, push title/body, suppress tests, expression/delay unit tests.

---

## 18. Evidence of no workflow-specific hardcoding

Repository search in `app/Modules/Automation` and `app/Modules/Workflow`:

- no `campaignKey ===`
- no `first_appointment_nurturing` / `birthdayWish` / `parentChild` / `dentistSegment`
- no `$workflow->id ===` / `$workflow->name ===`

---

## 19. Exact files changed

**New**

- `app/Modules/Workflow/Services/Runtime/ExpressionEvaluator.php`
- `app/Modules/Workflow/Exceptions/InvalidExpressionException.php`
- `app/Modules/Workflow/Exceptions/InvalidDelayConfiguration.php`
- `app/Modules/Automation/Engine/AutomationFactsBuilder.php`
- `app/Modules/Automation/Engine/WaitingExecutionSuppressor.php`
- `tests/Unit/WorkflowExpressionEvaluatorTest.php`
- `tests/Unit/DelaySchedulerWaitContractTest.php`
- `tests/Feature/Automation/GenericWorkflowEngineTest.php`
- `tests/Feature/Automation/SuppressOnAppointmentTest.php`
- `docs/automation/automation-step-4-implementation-report.md`

**Modified**

- `ConditionEngine.php`, `ConditionExecutor.php`
- `DelayScheduler.php`, `DelayExecutor.php`
- `AutomationEngine.php`
- `WorkflowExecutor.php`
- `ContinueWorkflowExecutionJob.php`
- `AbstractMessagingExecutor.php`
- `ChannelManager.php`
- `VariableResolver.php`
- `NodeTypeNormalizer.php`
- `tests/Unit/WorkflowDelayResumeTest.php`
- `tests/Unit/ChannelManagerRecipientResolverTest.php`
- `tests/Support/WorkflowAutomationTestCase.php` (create `communication_logs` if a prior SQLite suite omitted it)

**Not modified:** frontend, saved JSON, workflow storage schema, Step 2 observers/commands, TriggerService fan-out ownership, Medicine Reminder cron/job/bridge, API contracts.

---

## 20–21. Exact tests and results

All commands from `c:\xampp\htdocs\HIP_backend`. Suites that share SQLite were run **in separate processes** where a combined run is known to clash.

| Command | Result |
|---|---|
| `php artisan test tests/Unit/WorkflowExpressionEvaluatorTest.php tests/Unit/DelaySchedulerWaitContractTest.php tests/Unit/ChannelManagerRecipientResolverTest.php tests/Unit/WorkflowDelayResumeTest.php tests/Feature/Automation/GenericWorkflowEngineTest.php tests/Feature/Automation/SuppressOnAppointmentTest.php` | **37 passed** (108 assertions) |
| `php artisan test tests/Feature/HospitalAutomationTriggerHandlerTest.php` | **25 passed** |
| `php artisan test tests/Feature/AppointmentBookedAutomationTest.php` | **34 passed** |
| `php artisan test tests/Feature/HospitalAutomationStep2DispatchTest.php` | **17 passed** |
| `php artisan test tests/Feature/Automation/WorkflowConditionAndAiGraphTest.php tests/Feature/Automation/WorkflowMessagingGraphTest.php tests/Feature/Automation/WorkflowDatabaseGraphTest.php` | **18 passed, 12 skipped** (documented unimplemented node types) |
| `php artisan test tests/Feature/WorkflowBuilderTest.php` | **7 passed** |
| `php artisan test tests/Feature/WorkflowTemplateTest.php` | **4 passed** |
| `php artisan test tests/Feature/WorkflowMessageTemplateApiTest.php` | **20 passed** |
| `php artisan test tests/Feature/ChatbotWorkflowCompletionsTest.php` | **23 passed** |
| `php artisan test tests/Feature/Automation/WorkflowAutomationCoverageTest.php` | **25 passed** |

Combined `WorkflowTemplateTest` + `WorkflowMessageTemplateApiTest` in one process failed (SQLite isolation). Isolated runs passed.

Medicine due: covered by `HospitalAutomationTriggerHandlerTest` — linked workflow via bridge only.

---

## 22. Remaining unsupported capabilities

- `dbQuery`, `sendIvr`, inbound WhatsApp/SMS webhooks
- `appointmentMissed` has no production status dispatch (engine can still run the graph if the event is fired)
- Nested template variables `{{patient.name}}` (not in saved templates)
- `campaignStep` is metadata only
- `campaignKey` is data only (not a lookup key)

---

## 23. Known limitations

- Relative-date wait in the past → **0s delay** (immediate resume).
- Unresolved caregiver → no send (does not use patient as fallback).
- Condition facts for `appointment.exists` after a wait require `doctor_bookings` for a live query; tests without that table use payload flags.
- Eloquent `Model->exists` is not used as the workflow `appointment.exists` fact.

---

## Explicit confirmations

- The 13 reference workflows were **not** hardcoded.
- Workflow IDs and names were **not** hardcoded.
- `campaignKey` is **not** a workflow-specific switch.
- New valid graphs using supported node types execute without new PHP per graph (tests A–E).
- Node types are interpreted generically via registry + node config.
- `AutomationEngine` remains lookup/fan-out owner.
- `WorkflowExecutor` remains graph execution owner.
- Saved workflow JSON remains the source of truth.
- Frontend / workflow JSON / DB schema were not modified.
- Step 2 and Step 3 behavior remains intact (tests passed).
- Medicine Reminder due remains on `WorkflowExecutionBridge`.
- Step 5 was **not** implemented.
