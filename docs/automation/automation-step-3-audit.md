# Step 3 Audit and Proposed Architecture

**Status:** audit only. **No application code was changed for Step 3.** Implementation waits for approval (instruction §10).

**Date:** 2026-09-22

---

## A. Current-state audit

### A.1 Trigger entrypoints (what actually fires)

| Trigger | Dispatch/entrypoint that exists | Notes |
|---|---|---|
| `appointmentBooked` | `DoctorBookingObserver` → `AppointmentBooked` → `AppointmentBookedListener` → **`AutomationEngine::handle` (skips TriggerService)** | Confirmed create or `status` → confirmed. Reloads booking; aborts if unconfirmed or missing `hospital_id`. |
| `appointmentCompleted` | `DoctorBookingStatusService` → `AppointmentCompleted` → `DispatchHospitalAutomationWorkflow` → `HospitalAutomationTriggerService` → `AutomationEngine::handle` | Real status `completed`. |
| `appointmentCancelled` | Same status service → `AppointmentCancelled` → generic listener → TriggerService → engine | Real status `cancelled`. Not one of the 13 campaigns; still dispatched. |
| `patientRegistered` | `PersonsObserver` (primary `is_primary` create, or first `hip_user_id` attach) → `PatientRegistered` → generic listener → TriggerService → engine | `EventDispatchGuard` before dispatch. |
| `prescriptionAdded` | `PrescriptionService` → `PrescriptionCreated` → `CreateMedicineReminderSchedules` → **`WorkflowTriggerDispatcher` (not AutomationEngine)** | Second lookup path. **Does not pass `hospital_id`.** Schedule rows are created later by `PrescriptionAddedTriggerExecutor` (node layer, Step 4). |
| `medicineReminderDue` | Cron `medicine-reminders:dispatch` → job → `MedicineReminderExecutionService` → **`WorkflowExecutionBridge` → `WorkflowExecutor::start` on the schedule’s linked workflow** | Bypasses AutomationEngine fan-out. Bound to **one** workflow on the schedule. |
| `birthday` | `hospital-automation:dispatch-birthdays` 09:00 → `BirthdayReached` → generic listener → TriggerService → engine | Guard per person/year. Payload has org/hospital. |
| `anniversaryReached` | `hospital-automation:dispatch-anniversaries` 09:00 → `AnniversaryReached` → generic listener → TriggerService → engine | Women’s Day = 8 March only. |
| `scheduledEvent` | `hospital-automation:dispatch-scheduled-events` **10:00 only** → `ScheduledEvent` → generic listener → TriggerService → engine | No `appointment` on payload (avoids engine `alreadyStarted` killing later days). |
| `appointmentMissed` | Event class + listener **registered**. **No production dispatch.** | Booking statuses have no missed/no-show. Do not invent. |
| Chat | `POST /api/chat/completions` → `ChatbotWorkflowService` → `AutomationEngine::executeWorkflow` (single workflow) | Not `MessageReceived`. |
| Inbound WhatsApp / SMS | **Absent** | No webhook. |
| Manual | `POST /api/hospital-automation/trigger` → TriggerService | Existing. |

### A.2 Listeners

| Listener | Registered? | Calls |
|---|---|---|
| `AppointmentBookedListener` | Yes, `AppointmentBooked` only | Engine `handle` directly |
| `DispatchHospitalAutomationWorkflow` | Yes, all other HA events including `ScheduledEvent` | TriggerService |
| `CreateMedicineReminderSchedules` | Yes, `PrescriptionCreated` | `WorkflowTriggerDispatcher` |
| `StartWorkflowOnPrescriptionAdded` | **No** | Would double-fire if registered |

### A.3 Services that already act as trigger handlers

| Class | Role today |
|---|---|
| **`HospitalAutomationTriggerService`** | Thin: `dispatch($triggerType, $payload)` → `AutomationEngine::handle`. **This is the intended trigger-handler.** It does **not** look up workflows. |
| `AutomationEngine::handle` | Normalize trigger, merge context, resolve org/hospital, **`findPublishedByTrigger`**, **foreach workflow `start`**. **Fan-out owner.** |
| `AutomationEngine::executeWorkflow` | Start **one** already-chosen workflow (chatbot, `automation:test`). No fan-out. |
| `WorkflowTriggerDispatcher` | Duplicate of engine lookup+foreach, **without** context builder, hospital_id on prescription, or `alreadyStarted`. |
| `WorkflowExecutionBridge` | Medicine due: start the **linked** graph only. |

**Conclusion:** Do **not** add a new class that finds workflows and then calls `handle()`. That would **double fan-out**. The engine already owns discovery.

### A.4 Workflow lookup

`WorkflowRepository::findPublishedByTrigger($trigger, $organizationId, $hospitalId)`:

- `status = active`, `current_version_id` not null, `trigger_type` canonical.
- Excludes `source_type = automation_test`.
- Org: exact org **or** null org, if `$organizationId` given; else **only** null org.
- Hospital: **exact** `hospital_id` if provided; else **only** null-hospital rows. **No global fallback.**

`AutomationEngine::handle` additionally re-filters `appointmentBooked` to exact hospital (redundant with repository when hospital is set) and **returns without lookup** if booked and `hospital_id` is null.

### A.5 Engine entrypoints

- Production fan-out: `AutomationEngine::handle`.
- Single workflow: `executeWorkflow`.
- Node walk: `WorkflowExecutor::start` (Step 4; do not change).

### A.6 Fan-out owner (architecture B)

**The engine finds all matching published workflows and starts each once.**

Handlers/listeners must call `handle()` **once** per event.

`executeWorkflow` / medicine bridge / chatbot must **not** also call `handle()` for the same event.

### A.7 Context builder

`AutomationContextBuilder::merge`:

- `DoctorBooking` → `fromAppointment` (hospital, org, patient, doctor, …) then payload overlay.
- `Prescription` → `fromPrescription`.
- `Persons` → `fromPatient` (org from payload; **hospital_id only if already on payload**).
- Then `hospitalIdFrom`: appointment.hospital_id, else context/payload `hospital_id`. **Does not query an unrelated latest booking.** (`PatientAutomationIdentity` may use last booking only when building Step 2 event payload if hipUser has no hospital — that is **before** the handler.)

### A.8 Duplicate protection (do not add a third mechanism)

| Layer | What |
|---|---|
| Step 2 `EventDispatchGuard` (`Cache::add`) | Before emitting birthday / anniversary / scheduledEvent / patientRegistered |
| Scheduler `withoutOverlapping()` | Command overlap |
| Observer rules | Booked only on confirm transition; not on unrelated updates |
| Engine `alreadyStarted` | Same `workflow_id` + trigger + **non-empty `appointment_id`** |

Handler must **not** claim cache keys again. Handler must **not** pass `appointment` on `ScheduledEvent`.

### A.9 Hospital / organization resolution

| Trigger | Org | Hospital | Fallback |
|---|---|---|---|
| Appointment * | `booking.hospital.organization_id` via `fromAppointment` | **`booking.hospital_id` only** | Booked listener **aborts** if missing. Completed/cancelled have no abort in listener; engine lookup with null hospital → only null-hospital workflows. |
| patientRegistered / birthday / anniversary | Event payload / hipUser | Event payload / hipUser / (Step 2 identity: last booking **only if hipUser has no hospital**) | If both null: repository matches **null hospital + null-or-org** only. Hospital 12 campaigns will not run. |
| scheduledEvent | hipUser org on payload | **Latest booking’s `hospital_id` for that patient** (the inactivity scan is defined by that visit) | Same null-hospital rule. |
| prescriptionAdded today | `prescription.hospital.organization_id` | **Not passed** into dispatcher | Isolation gap. |
| medicineReminderDue | N/A to fan-out | Linked workflow, not catalog lookup | Unchanged. |

### A.10 Missing integrations

- `appointmentMissed`: no dispatch (documented).
- Inbound WhatsApp / SMS: no webhook.
- Chat: HTTP + `executeWorkflow`, not `MessageReceived`.
- `campaignKey` not used in lookup.

### A.11 Incorrect / duplicate paths

1. **Double discovery risk if Step 3 adds workflow-finding handlers** while engine still `handle()`-looks-up.
2. **`AppointmentBookedListener` bypasses TriggerService** — same engine method, but two entry styles.
3. **`WorkflowTriggerDispatcher` for prescription** — second fan-out, no `hospital_id`, no `alreadyStarted`.
4. **Medicine due** correctly uses single-workflow start; do not route it through `handle()` (would start every `medicineReminderDue` graph in the hospital, not the schedule’s workflow).

---

## B. Proposed Step 3 architecture

**Choice B (existing):** Trigger handler does **not** fetch workflows. Engine does.

```
DOMAIN / SCHEDULED EVENT
  → Listener / command (Step 2; reload/guard only)
  → HospitalAutomationTriggerService  ← Step 3 “trigger handler”
       • NodeTypeNormalizer::normalize
       • ensure payload org/hospital from the event’s own model (no extra latest-appointment guess)
       • AutomationEngine::handle once
  → AutomationEngine::handle
       • merge context
       • findPublishedByTrigger
       • foreach matching published workflow → WorkflowExecutor::start
```

**Do not create** `AppointmentBookedTriggerHandler`, `BirthdayTriggerHandler`, etc. that each query the repository.

**Do not restructure folders.** Keep `HospitalAutomationTriggerService` as the handler (optionally add a short class docblock / alias comment `TriggerHandler`).

### Intended ownership

| Concern | Owner |
|---|---|
| Domain dispatch, schedule, EventDispatchGuard | Step 2 (unchanged) |
| Normalize trigger, pass context, single `handle()` | `HospitalAutomationTriggerService` |
| Org/hospital from payload/context, published lookup, fan-out, `alreadyStarted` | `AutomationEngine` + `WorkflowRepository` |
| Node execution | `WorkflowExecutor` (Step 4, untouched) |
| Medicine due one-graph start | `WorkflowExecutionBridge` (untouched) |
| Chat one-graph start | `ChatbotWorkflowService` + `executeWorkflow` (untouched) |

### Minimal code after approval

1. **Extend** `HospitalAutomationTriggerService::dispatch`: normalize type; if payload has `DoctorBooking`/`Prescription`, copy `hospital_id` / `organization_id` from **that** model when missing; then `handle()`.
2. **`AppointmentBookedListener`:** keep reload + still-confirmed + hospital abort; call **TriggerService** instead of engine.
3. **`CreateMedicineReminderSchedules`:** call TriggerService with `prescription` plus `hospital_id` from `$prescription->hospital_id`. Stop using `WorkflowTriggerDispatcher` on this path only. Leave `PrescriptionAddedTriggerExecutor` / schedule CRUD as-is.
4. **Do not** change medicine cron, bridge, chatbot, JSON, node processors, condition/delay/channel.
5. **Do not** invent missed status or inbound message events.
6. Optional: `fromPatient` copies `hospital_id` from the payload argument so patient-only events don’t rely solely on merge order (behavior-preserving).

`WorkflowTriggerDispatcher` remains for any leftover callers (`StartWorkflowOnPrescriptionAdded` is unregistered). Do not delete it in Step 3.

---

## C. Files intended to modify (after approval only)

| File | Change |
|---|---|
| `app/Modules/HospitalAutomation/Services/HospitalAutomationTriggerService.php` | Normalize + scope copy + `handle` once |
| `app/Modules/HospitalAutomation/Listeners/AppointmentBookedListener.php` | Delegate to TriggerService |
| `app/Modules/MedicineReminder/Listeners/CreateMedicineReminderSchedules.php` | Minimal adapter: TriggerService + hospital_id |
| `app/Modules/HospitalAutomation/Services/AutomationContextBuilder.php` | **Only if needed:** pass `hospital_id` through `fromPatient` |
| `tests/Feature/HospitalAutomationTriggerHandlerTest.php` | **New** |

**Not intended:** engine fan-out loop, repository isolation rules, observers/commands (except if a payload bug is found), Medicine Reminder jobs/cron/notifications, executors, frontend JSON.

---

## D. Tests intended to add/update

New `tests/Feature/HospitalAutomationTriggerHandlerTest.php` (same sqlite workflow harness pattern as booked tests):

1. `appointmentBooked` — one published graph executes via TriggerService.
2. `appointmentCompleted` / `appointmentCancelled` — same.
3. `patientRegistered` — payload hospital/org used for lookup.
4. `prescriptionAdded` — after adapter: hospital-scoped published graph starts; other hospital does not.
5. `medicineReminderDue` — **assert existing bridge path still starts the linked workflow / or document no PHPUnit file and do not change code** (no handler fan-out).
6. `birthday` / `anniversaryReached` / `scheduledEvent` — TriggerService → engine with hospital on payload.
7. Two published `appointmentBooked` workflows same hospital → **two** executions, not one, not four.
8. Unpublished / inactive excluded.
9. Missing hospital on booked: no execution (existing abort + engine).
10. Duplicate `handle` same appointment_id + workflow: second skipped (`alreadyStarted`).
11. Hospital A event does not start Hospital B workflow.
12. Existing `AppointmentBookedAutomationTest` still pass.
13. Medicine Reminder: no module edits beyond the one listener adapter; cron/job/bridge files unchanged.

---

## Gaps left as future work (not Step 3)

- Node processors, JEXL conditions, `relative_date` wait, channel send, `campaignKey`.
- `appointmentMissed` dispatch.
- Inbound WhatsApp/SMS.
- Chat `MessageReceived`.
- Folder move to `Modules/Automation`.
- Step 4.
- 10:30 second scheduledEvent cron.

---

## Stop

No Step 3 implementation has been applied. Approve the architecture (engine-owned fan-out; one TriggerService; prescription listener adapter; medicine due left on the bridge) before any PHP edits.
