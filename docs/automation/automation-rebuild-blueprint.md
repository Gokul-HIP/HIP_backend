# Automation Rebuild Blueprint

**Status:** analysis and planning only. No application PHP, routes, migrations, or workflow records were changed. No Events, Listeners, Observers, Trigger Handlers, NodeProcessors, or engines were implemented.

**Date:** 2026-09-22  
**Sources used:** `docs/automation/frontend-json.md`, `docs/automation/automation-rebuild-analysis.md`, `app/Modules/{HospitalAutomation,Workflow,MedicineReminder}`, and related Laravel dispatch sites.

**Legend**

| Marker | Meaning |
|---|---|
| **Fact** | Observed in JSON or PHP |
| **Proposal** | Recommended for later implementation; not created |
| **Unknown** | Needs manager confirmation before coding |

This document does **not** claim the manager’s set of 15 workflows is complete.

---

## 1. Verified workflow inventory

### 1.1 What was searched

| Location | Result |
|---|---|
| `docs/automation/frontend-json.md` | **Fact.** Inventory of 59 graphs copied from `automation/node-workflows/` |
| `HIP_backend/**/*.workflow.json` | **Fact.** Zero files |
| `database/seeders/data/hospital_automation_workflows.php` | **Fact.** Nine Laravel sample graphs (not the frontend campaign set) |
| `docs/workflow/samples/` | **Fact.** One appointment-booked sample |
| Named campaigns (not titled `Fixture —`) in `frontend-json.md` | **Fact.** **13** product graphs |

### 1.2 The manager’s “15” vs what exists

**Fact.** No document in this repository lists 15 named product workflows.

**Fact.** The only named, campaign-keyed product graphs in `frontend-json.md` are the **13** rows below.

**Unknown.** Names or files of the remaining **2** of 15. They were not found in Laravel, in `frontend-json.md` non-fixture entries, or as a numbered list of 15.

**Do not treat** the 46 `Fixture — *` graphs as those missing two. Their `meta.notes` state they are catalog/test fixtures; many set `workflow.trigger` to `appointmentBooked` while the first node is a different catalog type.

### 1.3 Verified product workflows (13) — JSON inspected

Canonical JSON text is in `docs/automation/frontend-json.md`. Original filename (frontend): `automation/node-workflows/<file>`.

| # | Workflow name | JSON file | Trigger key | Node types used | External events | Database / scheduled events | JSON inspected |
|---|---|---|---|---|---|---|---|
| 1 | First Appointment Nurturing | `firstAppointmentNurturing.workflow.json` | `patientRegistered` | `patientRegistered`, `wait`, `condition`, `sendWhatsApp`, `sendSms`, `sendPush`, `end` | None | Patient **created** (registration). Not appointment CRUD. `suppressOnAppointment` implies a later appointment may **cancel** the campaign, not start it. | Yes |
| 2 | Post-Visit Follow-up | `postVisitFollowup.workflow.json` | `appointmentCompleted` | `appointmentCompleted`, `condition`, `sendWhatsApp`, `wait`, `sendSms`, `end` | None | Appointment **updated** to completed (not delete/soft-delete). | Yes |
| 3 | Missed Appointment Restart | `missedAppointmentRestart.workflow.json` | `appointmentMissed` | `appointmentMissed`, `sendWhatsApp`, `wait`, `condition`, `sendSms`, `end` | None | Appointment **updated** to missed/no-show (status mapping **Unknown**). | Yes |
| 4 | Digital Prescription Share | `digitalPrescriptionCampaign.workflow.json` | `prescriptionAdded` | `prescriptionAdded`, `sendWhatsApp`, `end` | None | Prescription **created**. | Yes |
| 5 | Medicine Reminder | `medicineReminderCampaign.workflow.json` | `medicineReminder` | `medicineReminder`, `sendWhatsApp`, `end` | None | **Scheduled** due times from medicine schedules (`reminderTiming: at_due`, `minutesBefore: 30`). Not a model create by itself. | Yes |
| 6 | Birthday Wish | `birthdayWish.workflow.json` | `birthday` | `birthday`, `sendWhatsApp`, `end` | None | **Scheduled** (`triggerTiming: on_birthday`, `daysBefore: 1`, `executionTime: 09:00`). | Yes |
| 7 | Women's Day Wish | `womensDayWish.workflow.json` | `anniversary` | `anniversary`, `condition`, `sendWhatsApp`, `end` | None | **Scheduled** (`anniversaryType: womens_day`, `on_date`, `09:00`). | Yes |
| 8 | Inactive Patient 30 Days | `inactivePatient30.workflow.json` | `scheduledEvent` | `scheduledEvent`, `condition`, `sendWhatsApp`, `end` | None | **Scheduled** recurring daily 10:00. | Yes |
| 9 | Inactive Patient 90 Days | `inactivePatient90.workflow.json` | `scheduledEvent` | `scheduledEvent`, `condition`, `sendSms`, `end` | None | **Scheduled** recurring daily 10:30. | Yes |
| 10 | Dentist Segment Campaign | `dentistSegment.workflow.json` | `appointmentBooked` | `appointmentBooked`, `condition`, `sendWhatsApp`, `end` | None | Appointment **created** or **updated** to confirmed (existing observer pattern). | Yes |
| 11 | Senior Patient Segment Campaign | `seniorPatientSegment.workflow.json` | `appointmentBooked` | `appointmentBooked`, `condition`, `sendSms`, `end` | None | Same as #10. | Yes |
| 12 | Parent–Child Segment Campaign | `parentChildSegment.workflow.json` | `appointmentBooked` | `appointmentBooked`, `condition`, `sendWhatsApp`, `end` | None | Same as #10. Recipient `caregiver`. | Yes |
| 13 | Segment Variants Example | `segmentDentistSeniorParentChild.workflow.json` | `appointmentBooked` | `appointmentBooked`, `condition`, `sendWhatsApp`, `sendSms`, `end` | None | Same as #10. Sequential if/else, not parallel. | Yes |

### 1.4 Missing from the “15”

| Item | Status |
|---|---|
| Workflow 14 name / file | **Unknown** — not in `frontend-json.md` non-fixture list |
| Workflow 15 name / file | **Unknown** — not in `frontend-json.md` non-fixture list |

**Not counted as the missing two (Fact):**

- Chatbot `messageReceived` / `onChatMessage` — Laravel HTTP path exists; only a **fixture** graph is in `frontend-json.md`.
- Incoming WhatsApp or SMS — **no** product graph and **no** inbound webhook route under `app/` or `routes/`.
- Appointment cancelled / rescheduled / deleted / soft-deleted — **no** product graph requires them.
- Laravel seeder graphs (lab, invoice, campaign, AI, refill) — different JSON shape; not the 13 campaign files.

### 1.5 Distinct trigger types in the verified 13

`patientRegistered`, `appointmentBooked`, `appointmentCompleted`, `appointmentMissed`, `prescriptionAdded`, `medicineReminder` (canonical `medicineReminderDue`), `birthday`, `anniversary` (canonical `anniversaryReached`), `scheduledEvent`.

---

## 2. Step 1 event matrix

Scope: **verified 13 workflows** plus existing Laravel entry points that already run automation (chatbot HTTP, medicine reminder cron). Do not add events because a catalog fixture exists.

### 2.A External events

**Fact.** None of the 13 product graphs start on an inbound WhatsApp, SMS, or chat message.

| Event (JSON / catalog) | Proposed class | Source / provider | Triggering action | Workflows | Payload | Evidence | Unknowns |
|---|---|---|---|---|---|---|---|
| WhatsApp incoming | *Do not create `WhatsAppIncomingEvent` yet* | No WhatsApp webhook/controller found | — | None of the 13 | — | Grep of `app/` + `routes/` for whatsapp webhook: no inbound route. Product JSON uses `sendWhatsApp` outbound only. | If manager still requires this, name the provider and URL first. |
| SMS incoming | *Do not create `SMSIncomingEvent` yet* | No SMS inbound webhook found | — | None of the 13 | — | Same as above; JSON uses `sendSms` outbound. | Provider unknown. |
| Chat incoming | **Proposal:** keep using HTTP; optionally dispatch existing `MessageReceived` | `ChatbotController` (`POST` chat completions) | Authenticated chat request | **Not** in the 13. Laravel chatbot product only. Fixture `onChatMessage.workflow.json` is catalog-only. | Session, messages, org, hospital | `app/Http/Controllers/Api/ChatbotController.php` → `ChatbotWorkflowService`. `MessageReceived` is registered in `HospitalAutomationServiceProvider` but **not** dispatched from the controller. | Should chatbot ever go Event → Listener, or stay sync `executeWorkflow`? |

**Other external (not in the 13):** fixture `webhookEvent` / `apiEvent` only. No production webhook controller found. **Proposal:** defer.

### 2.B Database / model / scheduled events

Do **not** fire automation on appointment delete, soft-delete, or restore: **no verified JSON requires it.**

| Event | Proposed class (reuse existing if named) | Model / source | Exact action | Workflows | Required payload / context | Evidence | Unknowns |
|---|---|---|---|---|---|---|---|
| Patient registered | `PatientRegistered` (exists) | **Unknown** model: `Persons` vs `HIPUser` | **Created** only (not profile update unless manager says so) | First Appointment Nurturing | Patient id, name, hospital_id, org_id; later `appointment.exists`, `booking_link`, `hospital_phone` | Event class `HospitalAutomation/Events/PatientRegistered.php`; listener registered; **no observer**. JSON: `registrationSource: any`, `patientType: any`. | Which table is “registered”? How is `hospital_id` set at signup? |
| Appointment booked / confirmed | `AppointmentBooked` (exists) | `DoctorBooking` | `created` **if already confirmed**; `updated` **when status changes to confirmed** | Dentist, Senior, Parent–Child, Segment variants | Booking, hospital_id, patient, department name, age, relationship, caregiver contact | `DoctorBookingObserver::created/updated`; `AppointmentBookedListener` | Department field name; caregiver relation |
| Appointment completed | `AppointmentCompleted` (exists) | `DoctorBooking` | Status change to completed via **service**, not observer | Post-Visit Follow-up | Booking + `followup.exists`, `followup.date`, doctor contact | `DoctorBookingStatusService` (~line 287) | Follow-up column/table; `requireFollowUp: yes` |
| Appointment missed | `AppointmentMissed` (exists) | `DoctorBooking` | Status → missed/no-show | Missed Appointment Restart | Booking; later `appointment.exists` | Event + `DispatchHospitalAutomationWorkflow` registered; **no `AppointmentMissed::dispatch` in app** | Exact status value |
| Appointment cancelled | `AppointmentCancelled` (exists) | `DoctorBooking` | Status → cancelled | **None of the 13** | Booking | `DoctorBookingStatusService` (~line 284) | Keep for other graphs; do not invent a campaign |
| Appointment deleted / soft-deleted | *Do not add* | `DoctorBooking` | — | None | — | Observer has no `deleted` / `trashed` | Confirm with manager (recommended: skip) |
| Prescription created | `PrescriptionCreated` (MedicineReminder) | Prescription via `PrescriptionService` | Create | Digital Prescription Share; also starts medicine **schedules** | Prescription, hospital, doctor, `pharmacy_link` | `PrescriptionService` `event(new PrescriptionCreated)` | `pharmacy_link` source |
| Medicine reminder due | Existing job path; optional `MedicineReminderTriggered` (exists, **no listener**) | `MedicineReminderSchedule` | Cron `medicine-reminders:dispatch` → job | Medicine Reminder campaign | Patient, `medicine_name`, `dosage`, `frequency` | `DispatchMedicineRemindersCommand`, `MedicineReminderExecutionService`, `WorkflowExecutionBridge` | Whether `minutesBefore: 30` is applied |
| Birthday | `BirthdayReached` (exists) | Persons / patient DOB | Daily schedule 09:00 | Birthday Wish | Patient, hospital | Event + listener registered; **no command** | DOB column; hospital isolation for patients without a booking |
| Women's Day / anniversary | `AnniversaryReached` (exists) | Calendar, not a row insert | Annual/daily occasion job | Women's Day Wish | Patient + `anniversaryType`; gender used in JSON condition | Event payload is `Persons` only today (`AnniversaryReached.php`) | Confirm `womens_day` vs wedding anniversary |
| Inactive 30/90 | **Proposal:** `ScheduledEvent` class **does not exist** | Cohort query + scheduler | Recurring daily 10:00 / 10:30 | Inactive 30, Inactive 90 | Per-patient `last_visit` / `last_visit_days` | JSON `scheduleType: recurring`. `ScheduledEventTriggerExecutor` exists; no dispatcher | How `last_visit` is computed |

---

## 3. Step 2 Events / Listeners / Observers matrix

**Proposal:** keep existing class names where they already match JSON triggers. Do not invent parallel `WhatsAppIncomingEvent` classes without a webhook.

Registration today: `HospitalAutomationServiceProvider::registerEvents()` and `registerObservers()`; `MedicineReminderServiceProvider` for `PrescriptionCreated`.

### 3.1 Database path (Eloquent → observer or domain service → event → listener)

```
Model lifecycle or status service
  → Observer or service dispatch
  → Event
  → Queued Listener
  → (later) TriggerHandler → WorkflowAutomationEngine
```

**Today** listeners call `AutomationEngine` / `HospitalAutomationTriggerService` / `WorkflowTriggerDispatcher`. **Proposal:** keep that until the engine phase; Step 2 only adds missing **dispatch** sites.

| JSON trigger | Event | Listener | Observer / service | Register in | Reuse | Missing |
|---|---|---|---|---|---|---|
| `appointmentBooked` | `AppointmentBooked` | `AppointmentBookedListener` | `DoctorBookingObserver` (`created`, `updated`) | Already `HospitalAutomationServiceProvider` | Full path exists | Context fields for conditions (`appointment.department`, `patient.age`, `patient.relationship`, caregiver) |
| `appointmentCompleted` | `AppointmentCompleted` | `DispatchHospitalAutomationWorkflow` | **Not observer** — `DoctorBookingStatusService` | Already | Dispatch exists | Follow-up on context |
| `appointmentMissed` | `AppointmentMissed` | `DispatchHospitalAutomationWorkflow` | **Proposal:** dispatch from `DoctorBookingStatusService` (same pattern as completed), **or** extend observer `updated` if status lives only on the model | Provider already listens | Event + listener exist | **Dispatch site** |
| `patientRegistered` | `PatientRegistered` | `DispatchHospitalAutomationWorkflow` | **Proposal:** observer on the confirmed patient model, `created` only | Provider already listens | Event + listener exist | **Observer + model choice** |
| `prescriptionAdded` | `PrescriptionCreated` | `CreateMedicineReminderSchedules` (then workflows via dispatcher) | `PrescriptionService` (not observer) | `MedicineReminderServiceProvider` | Exists | Do **not** also register `StartWorkflowOnPrescriptionAdded` |
| cancelled (not in 13) | `AppointmentCancelled` | Generic listener | Status service | Already | Exists | No product JSON |

Appointment **delete / soft-delete:** no observer methods; **proposal: do not add.**

### 3.2 External messaging path (only if later required)

Verified 13 do **not** need this. Existing chat (Laravel product, not in 13):

```
HTTP POST /api/chat/completions
  → ChatbotController
  → ChatbotWorkflowService (sync AutomationEngine)
```

**Proposal for inbound WhatsApp/SMS if a provider is added later (not implemented, no evidence today):**

```
Provider webhook controller
  → Event (new)
  → Listener
  → engine handle(messageReceived or dedicated trigger)
```

Do **not** put inbound WhatsApp on `DoctorBookingObserver`.

### 3.3 Scheduled path

```
Kernel / routes/console.php
  → Artisan command
  → Event or engine.handle(trigger, patientPayload)
  → Listener (if event)
```

| JSON trigger | Event | Listener | Command / job | Reuse | Missing |
|---|---|---|---|---|---|
| `birthday` | `BirthdayReached` | Generic HA listener | **Proposal:** daily command | Event class | Command + DOB query |
| `anniversary` | `AnniversaryReached` | Generic HA listener | **Proposal:** occasion command | Event class | Command + `anniversaryType` on payload |
| `scheduledEvent` | **Proposal:** new `ScheduledEvent` implementing `HospitalAutomationEvent` **or** command calling engine with `triggerType scheduledEvent` | Generic listener if event exists | **Proposal:** daily cohort job per workflow hospital | `ScheduledEventTriggerExecutor` | Event class, cohort query, `last_visit` |
| `medicineReminder` | Optional `MedicineReminderTriggered` | None today | `medicine-reminders:dispatch` → `SendMedicineReminderJob` → `WorkflowExecutionBridge` | Full schedule product | Unify with engine `handle('medicineReminderDue')`; map `minutesBefore` |

---

## 4. Workflow node → NodeProcessor mapping

From the **13 inspected product graphs** only. Fixture-only node types (`ai`, `httpRequest`, `dbQuery`, `sendIvr`, `onChatMessage`, …) are **not** required to run those 13.

Aliases: `wait` → `delay`; `sendSms` → `sendSMS`; `medicineReminder` → `medicineReminderDue`; `anniversary` → `anniversaryReached` (`NodeTypeNormalizer`).

| JSON `nodeType` | Proposed NodeProcessor | Existing Laravel class | Required context / config (from JSON) | Status |
|---|---|---|---|---|
| `patientRegistered` | `PatientRegisteredTriggerHandler` / passthrough trigger processor | `PassthroughTriggerExecutor` | `registrationSource`, `patientType` | Trigger executor exists; **event not dispatched** |
| `appointmentBooked` | `AppointmentBookedTriggerHandler` | `AppointmentBookedTriggerExecutor` (logs + continue) | `source: any` | Reuse; enrich context |
| `appointmentCompleted` | `AppointmentCompletedTriggerHandler` | Passthrough | `requireFollowUp: yes` | Uncertain if trigger config is read |
| `appointmentMissed` | `AppointmentMissedTriggerHandler` | Passthrough | none beyond trigger | Dispatch missing |
| `prescriptionAdded` | `PrescriptionAddedTriggerHandler` | `PrescriptionAddedTriggerExecutor` | `source: any` | Reuse |
| `medicineReminder` | `MedicineReminderDueTriggerHandler` | `MedicineReminderDueTriggerExecutor` | `reminderTiming`, `minutesBefore` | Config mapping uncertain |
| `birthday` | `BirthdayTriggerHandler` | `BirthdayTriggerExecutor` | `on_birthday`, `daysBefore`, `executionTime` | Scheduler missing |
| `anniversary` | `AnniversaryReachedTriggerHandler` | Passthrough | `anniversaryType: womens_day`, `on_date`, `09:00` | Scheduler + type missing |
| `scheduledEvent` | `ScheduledEventTriggerHandler` | `ScheduledEventTriggerExecutor` | `recurring`, `executionTime`, `repeatFrequency: daily`, `endCondition: never` | Dispatcher/cohort missing |
| `wait` | `WaitNodeProcessor` / Delay | `DelayExecutor` + `DelayScheduler` | Duration: `waitType: duration`, `amount`, `unit`. Relative: `waitType: relative_date`, `relativeDateField`, `relativeOffsetDirection`, `relativeOffsetAmount`, `relativeOffsetUnit` | **Duration: reusable. `relative_date`: missing** (Post-Visit) |
| `condition` | `IfConditionNodeProcessor` | `ConditionExecutor` + `ConditionEngine` | `expression` JEXL strings, e.g. `appointment.exists == false`, `appointment.department == "Dentistry"`, `patient.age >= 60`, `patient.age < 18 \|\| patient.relationship == "child"`, `followup.exists == true`, `last_visit >= 30`, `patient.gender == "female"` | **Existing engine uses `rules`/`field`, not `expression`. Empty rules currently evaluate true. Missing / incorrect for these graphs.** |
| `sendWhatsApp` | `SendWhatsAppNodeProcessor` | `SendWhatsAppExecutor` | `templateId` empty; inline `message`; `recipient`: `patient` \| `doctor` \| `caregiver`; `campaignStep` | Channel exists; **caregiver/doctor recipient + campaignStep unused** |
| `sendSms` | `SendSMSNodeProcessor` | `SendSMSExecutor` | same pattern | Same gaps |
| `sendPush` | `SendPushNodeProcessor` | `SendPushExecutor` | `title`, `body`, `priority` (nurture step 3) | Reuse; campaignStep unused |
| `end` | `EndNodeProcessor` | `EndExecutor` | optional labels | Reuse |

Template variables used in the 13 (must appear in runtime context): `patient_name`, `hospital_name`, `hospital_phone`, `booking_link`, `doctor_name`, `pharmacy_link`, `followup_date`, `medicine_name`, `dosage`, `frequency`.

JSON fields with **no PHP reader found:** `campaignKey`, `campaignStep`, `suppressOnAppointment`.

---

## 5. Proposed target folder structure

**Proposal.** Evolve `app/Modules/HospitalAutomation` as the Automation domain (optional later rename). Keep `Workflow` for stored graphs + builder API. Keep `MedicineReminder` as the dose-scheduling product.

Do not move files in this phase.

```
app/Modules/HospitalAutomation/          # Automation domain
├── HospitalAutomationServiceProvider.php
├── Contracts/
│   └── HospitalAutomationEvent.php
├── Events/                              # Step 2: one event per dispatched trigger
├── Listeners/                           # Step 2
├── Observers/                           # Step 2: DoctorBooking + (later) patient registered
├── TriggerHandlers/                     # Step 3 (later) — thin: event payload → engine.handle
├── Engine/                              # Step 3 (later) WorkflowAutomationEngine
│   ├── WorkflowAutomationEngine.php     # replace/wrap AutomationEngine; do not run two engines
│   └── AutomationContextBuilder.php     # move from Services/ when implementing
├── NodeProcessors/                      # later; today Workflow/Executors/*
├── Jobs/
├── Console/Commands/                    # birthday, anniversary, scheduledEvent
├── Support/TriggerCatalog.php
├── Controllers/ + Routes/               # catalog + manual trigger; keep UI/API
└── Testing/

app/Modules/Workflow/                    # persistence + compiler + current executors
├── Models, Repositories, Compiler, Runtime
├── Executors/                           # stay until NodeProcessors land
└── builder HTTP (do not break React Flow save API)

app/Modules/MedicineReminder/            # keep in place
├── schedules, jobs, notification adapters, MedicineWorkflow CRUD
└── feeds medicineReminderDue into the Automation engine; do not grow a second graph walker
```

### Medicine Reminder

**Fact.** It already: listens to `PrescriptionCreated`, writes `MedicineReminderSchedule`, cron-dispatches jobs, notifies via WhatsApp/SMS/email/push, and starts `medicineReminderDue` via `WorkflowExecutionBridge`.

**Proposal.** Leave models, notifications, and `MedicineWorkflow` where they are. Step 2/3 should only make the due-job call the same engine as other triggers. Do not delete `MedicineReminderTriggered` until a listener or removal is confirmed.

---

## 6. Existing code to reuse

| Area | Path | Role |
|---|---|---|
| Engine | `HospitalAutomation/Services/AutomationEngine.php` | Lookup + start (wrap later, do not fork) |
| Context | `HospitalAutomation/Services/AutomationContextBuilder.php` | Merge appointment/prescription/patient |
| Catalog | `HospitalAutomation/Support/TriggerCatalog.php` | Canonical trigger IDs |
| Alias | `Workflow/Support/NodeTypeNormalizer.php` | Frontend JSON keys |
| Observer pattern | `DoctorBookingObserver` + `AppointmentBookedListener` | Template for Step 2 |
| Generic listener | `DispatchHospitalAutomationWorkflow` | Most HA events |
| Status dispatch | `app/Services/DoctorBookingStatusService.php` | Completed / cancelled |
| Prescription | `PrescriptionService` + `CreateMedicineReminderSchedules` | Digital Rx + reminder rows |
| Runtime | `WorkflowExecutor`, `WorkflowCompiler`, `NodeExecutorRegistry` | Graph walk |
| Messaging | `ChannelManager`, `SendWhatsAppExecutor`, `SendSMSExecutor`, `SendPushExecutor` | Outbound |
| Delay | `DelayExecutor`, `DelayScheduler`, `ContinueWorkflowExecutionJob` | Duration waits |
| Medicine cron | `DispatchMedicineRemindersCommand`, `SendMedicineReminderJob` | Due times |
| Chat | `ChatbotController`, `ChatbotWorkflowService` | Inbound chat (not in 13) |
| Persistence / UI | Workflow module models, controllers, routes | Keep intact |

---

## 7. Missing integrations

| Gap | Needed by | Evidence |
|---|---|---|
| Patient registered dispatch | Workflow 1 | Event unused |
| Appointment missed dispatch | Workflow 3 | Event unused |
| Birthday / Women's Day scheduler | Workflows 6–7 | No console command |
| `scheduledEvent` cohort runner | Workflows 8–9 | No dispatcher; no `last_visit` |
| JEXL `expression` conditions | Almost all 13 | `ConditionEngine` ignores `data.expression` |
| `waitType: relative_date` | Workflow 2 | `DelayScheduler` duration-only |
| `campaignKey` / `suppressOnAppointment` | Workflows 1–3 especially | JSON only |
| Caregiver / doctor recipient | Workflows 2, 12, 13 | Executors assume patient channel |
| Context variables listed in §4 | Messaging nodes | Templates in JSON |
| Inbound WhatsApp/SMS | Manager examples; **not** the 13 | No webhook |
| `ScheduledEvent` event class | Workflows 8–9 | Not in `HospitalAutomation/Events/` |
| Two of fifteen workflow files | Manager count | Not found |

---

## 8. Unresolved questions for the manager

1. What are the **names and files of workflows 14 and 15**?
2. Confirm the supported set is the **13 campaign graphs** in `frontend-json.md`, not the 46 fixtures.
3. Patient registration: which Eloquent model and which `hospital_id` source?
4. Which `DoctorBooking.status` value means **missed**?
5. Where is **follow-up date** stored?
6. Should inbound WhatsApp/SMS be in scope without a provider webhook?
7. Should chatbot stay on `ChatbotController` (current) instead of `MessageReceived`?
8. Confirm **no** automation on appointment delete / soft-delete.
9. Inactive scans: one execution **per patient per day**, or one run with a list? JSON is a per-patient graph.
10. Who cancels `first_appointment_nurturing` on book: Laravel `suppressOnAppointment` or frontend-only?
11. May `CreateRecordExecutor` / catalog fixtures be ignored until a product graph uses them?

---

## 9. Safe implementation order

Do not implement in this task. Order after manager answers §8:

1. **Freeze workflow set** (13 verified + named 14–15, or written confirmation that 13 is the set).
2. **Step 2 — Events / Listeners / Observers only**
   - Reuse existing event classes.
   - Add patient-registered observer (after model confirmation).
   - Add missed dispatch on the status service (after status confirmation).
   - Add birthday / anniversary / scheduledEvent **commands** (scheduled events, not fake webhooks).
   - Do not add WhatsApp/SMS inbound events.
   - Do not add delete/soft-delete observer methods.
   - Register only in existing service providers / `routes/console.php`.
3. **Step 3 — TriggerHandlers** as adapters into one engine (`AutomationEngine` renamed/wrapped as `WorkflowAutomationEngine`). **One engine only.**
4. **NodeProcessors** for the 13: expression conditions, relative_date wait, recipient roles, campaignKey.
5. **Medicine Reminder** job calls the same engine; leave reminder CRUD/notifications in `MedicineReminder`.
6. Keep Workflow builder API and stored JSON unchanged.

**Verification when coding starts:** one confirmed booking still fires `AppointmentBooked` once; chatbot inactive workflow still skips OpenRouter; medicine cron still sends; no inbound webhook invented.

---

## Appendix — Fixture graphs (out of product scope)

`docs/automation/frontend-json.md` lists 46 `Fixture — *` files. They demonstrate catalog nodes (`ai`, `dbCreate`, `httpRequest`, `onChatMessage`, `webhookEvent`, …). Several list **Triggers** as both `appointmentBooked` and the focus node because `workflow.trigger` is `appointmentBooked` while the first node is another type. **They are not used as the 15 product workflows in this blueprint.**
