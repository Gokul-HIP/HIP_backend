# Hospital Automation Rebuild Analysis

**Status:** analysis and planning only. No application files were deleted, moved, or modified. No events, listeners, observers, engine, or node processors were implemented as part of this document.

**Date:** 2026-09-22  
**Repositories inspected:** `c:\xampp\htdocs\HIP_backend` (Laravel) and sibling frontend `d:\hip-automation` (React Flow / saved workflow JSON).

---

## 1. Executive summary

HIP already has a working **single-engine** path:

```
Domain event / HTTP / scheduler
  → HospitalAutomation listener OR ChatbotWorkflowService OR MedicineReminder job
  → AutomationEngine / WorkflowTriggerDispatcher
  → WorkflowRepository.findPublishedByTrigger (hospital + org isolation)
  → WorkflowExecutor + WorkflowCompiler
  → NodeExecutorRegistry
  → ChannelManager / DelayScheduler / ConditionEngine
```

That engine should be **extended, not replaced**. Manager Steps 1–2 (event inventory + event/listener/observer mapping) are the next implementation slice. Trigger handlers and a new WorkflowAutomationEngine must **not** be built in this phase.

### Critical findings

1. **The 15 product workflow JSON files are not in this Laravel repo.** The closest complete set is **13 campaign graphs** in `d:\hip-automation\automation\node-workflows\` plus **9 seeder graphs** in Laravel. This document does **not** claim 15 Laravel JSON files were reviewed.
2. **Most HospitalAutomation event classes exist and are listener-registered, but almost none are dispatched** from observers or domain services. Only `AppointmentBooked` (observer + status service), `AppointmentCancelled` / `AppointmentCompleted` (status service), `PrescriptionCreated` (prescription service), chatbot HTTP, and medicine-reminder dispatch are wired.
3. **Two parallel event namespaces** exist (`HospitalAutomation\Events` vs `Workflow\Events`). The Workflow copies are unused on the production bus.
4. **Two dispatchers** exist (`AutomationEngine` vs `WorkflowTriggerDispatcher`). Both eventually call `WorkflowExecutor`. Prescription uses the dispatcher; appointment/chat use the engine.
5. **`HospitalDomainTriggerExecutor` never registers** for catalog types: `WorkflowServiceProvider` already fills every `TriggerCatalog` type with `PassthroughTriggerExecutor`.
6. **Product JSON uses frontend contracts** (`wait` + `waitType: relative_date`, JEXL `expression` conditions, `campaignKey` / `campaignStep`, `recipient: caregiver`) that the current Laravel `DelayExecutor` / `ConditionEngine` **do not implement**.
7. Incoming WhatsApp/SMS **webhooks do not exist**. Chatbot uses `POST /api/chat/completions`, not `MessageReceived` event dispatch.

### Recommended direction

Keep `app/Modules/Workflow` as persistence + runtime. Keep `app/Modules/HospitalAutomation` as the **automation domain** (events, listeners, observers, trigger catalog, context). Keep `app/Modules/MedicineReminder` as the **medicine scheduling product**, feeding `medicineReminderDue` into the same engine. Later, optionally rename HospitalAutomation → Automation **without** creating a second engine.

---

## 2. Existing automation architecture and folder audit

### 2.1 Runtime flow (as implemented)

| Layer | Location | Role |
|---|---|---|
| Catalog | `HospitalAutomation/Support/TriggerCatalog.php` | Canonical trigger IDs (31 types) |
| Alias layer | `Workflow/Support/NodeTypeNormalizer.php` | Frontend IDs → Laravel IDs |
| Events | `HospitalAutomation/Events/*` | Domain events implementing `HospitalAutomationEvent` |
| Listeners | `AppointmentBookedListener`, `DispatchHospitalAutomationWorkflow` | Queued; call engine or trigger service |
| Observer | `DoctorBookingObserver` | `created`/`updated` → `AppointmentBooked` when confirmed |
| Chat HTTP | `ChatbotController` → `ChatbotWorkflowService` | Sync `executeWorkflow` for `messageReceived` |
| Medicine | `DispatchMedicineRemindersCommand` → `SendMedicineReminderJob` → `WorkflowExecutionBridge` | Starts `medicineReminderDue` graph |
| Engine | `AutomationEngine` | Lookup + duplicate skip + `WorkflowExecutor::start` |
| Alternate dispatcher | `WorkflowTriggerDispatcher` | Same lookup/start; used by prescription listener |
| Compiler | `Workflow/Services/Compiler/WorkflowCompiler.php` | React Flow JSON → execution graph |
| Executor | `WorkflowExecutor` | Walks graph, wait/resume, logs |
| Registry | `NodeExecutorRegistry` (singleton in `WorkflowServiceProvider`) | Maps canonical `nodeType` → executor |
| Messaging | `ChannelManager` + MedicineReminder notification services | whatsapp/sms/email/push |
| Delay | `DelayExecutor` + `DelayScheduler` + `ContinueWorkflowExecutionJob` | Relative duration only (`minutes`/`hours`/`days`/`weeks`/`seconds`) |
| Conditions | `ConditionExecutor` + `ConditionEngine` | Structured `rules` / `field`+`compare`, **not** JEXL `expression` |
| Isolation | `WorkflowRepository::findPublishedByTrigger` | Exact `hospital_id` when provided; org = exact OR null |

### 2.2 Module folders (actual)

**HospitalAutomation** (`app/Modules/HospitalAutomation/`): Console, Contracts, Controllers, Events (22), Executors (2), Jobs, Listeners (2), Observers (1), Routes, Services (engine, context, chatbot, trigger), Support, Testing, `HospitalAutomationServiceProvider.php`.

**Workflow** (`app/Modules/Workflow/`): builder CRUD, compiler, runtime, node executors, models (`Workflow`, `WorkflowVersion`, `WorkflowExecution`, `WorkflowMessageTemplate`, `WorkflowTemplate`, `CommunicationLog`), duplicate unused `Events/` (6 classes), unregistered `StartWorkflowOnPrescriptionAdded`.

**MedicineReminder** (`app/Modules/MedicineReminder/`): prescription listener, schedules, dispatch command/job, notification adapters, execution service. This is both a **product** (dose schedules) and a **workflow trigger** (`prescriptionAdded` / `medicineReminderDue`).

**Outside modules (wired into automation):**

| Path | Role |
|---|---|
| `app/Services/DoctorBookingStatusService.php` | Dispatches cancelled/completed automation events |
| `app/Services/PrescriptionService.php` | Dispatches `PrescriptionCreated` |
| `app/Http/Controllers/Api/ChatbotController.php` | Chatbot completions |
| `app/Models/DoctorBooking.php` | Observed |
| `app/Models/ChatbotSession.php`, `ChatbotMessage.php` | Chat memory |
| `app/Console/Kernel.php` + `routes/console.php` | Scheduler |
| `app/Console/Commands/SendPendingPaymentReminders.php` | Pending invoice **push**, not `PaymentPending` event |
| `bootstrap/providers.php` | Registers the three module providers |
| `database/seeders/HospitalAutomationWorkflowSeeder.php` | Publishes sample graphs |
| `database/seeders/data/hospital_automation_workflows.php` | 9 sample definitions |
| `tests/Feature/Automation/*`, `tests/Unit/SendAiChatExecutorTest.php`, chatbot tests | Coverage |

### 2.3 Autoload / registration conventions

- PSR-4: `"App\\": "app/"` in `composer.json`. Module namespaces are `App\Modules\{HospitalAutomation,Workflow,MedicineReminder}\...`.
- Providers: `WorkflowServiceProvider`, `HospitalAutomationServiceProvider`, `MedicineReminderServiceProvider` in `bootstrap/providers.php`.
- There is **no** Laravel `EventServiceProvider` map for these events; they are registered in module `boot()`.

---

## 3. Dependency and registration findings

### 3.1 Service providers

| Provider | Registers |
|---|---|
| `WorkflowServiceProvider` | `WorkflowCompilerInterface`, singleton `NodeExecutorRegistry` (specialized executors + passthrough for every TriggerCatalog type), `WorkflowTemplatePolicy`, `routes/workflow.php` |
| `HospitalAutomationServiceProvider` | `RunAutomationTestCommand`; attempts extra trigger executors (skipped if already registered); `Event::listen` for HA events; `DoctorBooking::observe`; `routes/hospitalAutomation.php` |
| `MedicineReminderServiceProvider` | `MedicineReminderInterface` binding; `PrescriptionCreated` → `CreateMedicineReminderSchedules`; `medicine-reminders:dispatch`; medicine routes |

### 3.2 Event / listener map (actual)

| Event | Listener | Registered? | Dispatched from |
|---|---|---|---|
| `HA\AppointmentBooked` | `AppointmentBookedListener` (queued) | Yes | `DoctorBookingObserver` created/updated when confirmed |
| `HA\AppointmentCancelled` | `DispatchHospitalAutomationWorkflow` | Yes | `DoctorBookingStatusService::dispatchAutomationEvents` |
| `HA\AppointmentCompleted` | same | Yes | same |
| `HA\AppointmentMissed` | same | Yes | **No production dispatch found** |
| `HA\AppointmentRescheduled` | same | Yes | **No production dispatch found** |
| `HA\LabTestOrdered`, `LabReportReady`, `LabCompleted` | same | Yes | **No production dispatch found** |
| `HA\MedicineRefillDue` | same | Yes | **No production dispatch found** |
| `HA\InvoiceGenerated`, `PaymentReceived`, `PaymentPending` | same | Yes | **No production dispatch found** (`reminders:pending-payments` bypasses event) |
| `HA\MembershipExpiry`, `MembershipRenewed`, `RewardPointsUpdated`, `RewardTierUpgraded` | same | Yes | **No production dispatch found** |
| `HA\BirthdayReached`, `AnniversaryReached` | same | Yes | **No scheduler/observer found** |
| `HA\PatientRegistered` | same | Yes | **No observer on Persons/HIPUser found** |
| `HA\ProcedureCompleted` | same | Yes | **No production dispatch found** |
| `HA\MessageReceived` | same | Yes | **Not used by ChatbotController** (sync engine path instead) |
| `HA\CampaignTriggered` | same | Yes | Manual `POST /hospital-automation/trigger` only (if caller sends this type) |
| `MR\PrescriptionCreated` | `CreateMedicineReminderSchedules` | Yes | `PrescriptionService` |
| `WF\PrescriptionAdded` / `StartWorkflowOnPrescriptionAdded` | — | **Not registered** | Would double-fire if registered alongside medicine listener |
| `MR\MedicineReminderTriggered` | **none** | Fired in `MedicineReminderExecutionService` with **no listener** | Side-effect-free today |
| `WF\Events\*` (6 classes) | none | Unused duplicates | — |

### 3.3 Observers

| Observer | Model | Lifecycle | Evidence |
|---|---|---|---|
| `DoctorBookingObserver` | `DoctorBooking` | `created`, `updated` (status → confirmed only) | Registered in HA provider. **No** `deleted`, `softDeleted`, `restored`. |
| — | `Persons`, `Invoice`, `Prescription`, `Report`, etc. | — | **Not observed** for automation |

Do **not** assume every lifecycle should fire automation. JSON evidence only requires: booking confirmed (created/updated), cancelled/completed (status service), missed (event exists, no dispatcher), prescription created, patient registered (JSON, no observer), scheduled scans, birthday/anniversary (JSON, no scheduler).

### 3.4 Scheduler / console

| Mechanism | Command | Automation event? |
|---|---|---|
| `routes/console.php` every minute | `medicine-reminders:dispatch` | Indirectly starts `medicineReminderDue` via job + `WorkflowExecutionBridge` |
| `routes/console.php` every minute **and** Kernel every 30 min | `reminders:pending-payments` | **No** — direct push, not `PaymentPending` |
| Daily 00:05 | expire family subscriptions | **No** `userPlanExpiry` / `membershipExpiry` event |
| — | birthday / anniversary / scheduledEvent / inactive 30/90 | **Missing** |

### 3.5 Duplicate / dead / uncertain code

| Item | Verdict | Evidence |
|---|---|---|
| `HospitalDomainTriggerExecutor` | **Dead for catalog types** | Registry already has passthrough before HA `boot()` |
| `Workflow\Events\*` | Unused duplicate | No `Event::listen` |
| `StartWorkflowOnPrescriptionAdded` | Unregistered; would duplicate prescription dispatch | Comment in class itself |
| `WorkflowTriggerDispatcher` vs `AutomationEngine` | Duplicated lookup/start | Prescription vs appointment/chat |
| `PassthroughTriggerExecutor` vs specialized `AppointmentBookedTriggerExecutor` / `BirthdayTriggerExecutor` / `ScheduledEventTriggerExecutor` | Specialized win but they only log + continue | Same as passthrough |
| `MedicineReminderTriggered` | Fired, no listener | Grep found no `Event::listen` |
| `ChatbotWorkflowService` vs `MessageReceived` | Two chat entry points | HTTP sync vs unused event |
| Frontend `d:\hip-automation\src\runtime\*` | **Do not port as a second PHP engine** | JS executor clone of Laravel concepts |
| `sendIvr`, `httpRequest`, `dbQuery`, `fhir`, `abdm` | Frontend catalog stubs | `backend-node-contracts.json` status `not_implemented` |

Nothing in this list is labelled safe to delete: tests, seeders, or string references may still load classes. Manual review required before any removal.

### 3.6 Reusable core (keep)

- `AutomationEngine`, `AutomationContextBuilder`, `WorkflowRepository`, `WorkflowExecutor`, `WorkflowCompiler`, `NodeExecutorRegistry`, `ChannelManager`, `TemplateManager`, `VariableResolver`, `DelayScheduler`, `ActionDispatcher`, `ChatbotWorkflowService` / conversation persistence, MedicineReminder schedule/job/notification adapters, `DoctorBookingObserver` + `AppointmentBookedListener` as the **reference wiring**.

---

## 4. Inventory of supplied workflows

### 4.1 Limitation (required disclosure)

| Source | Count | Path |
|---|---|---|
| Product campaign JSON (frontend sibling) | **13 reviewed** | `d:\hip-automation\automation\node-workflows\{firstAppointmentNurturing,postVisitFollowup,missedAppointmentRestart,digitalPrescriptionCampaign,medicineReminderCampaign,birthdayWish,womensDayWish,inactivePatient30,inactivePatient90,dentistSegment,seniorPatientSegment,parentChildSegment,segmentDentistSeniorParentChild}.workflow.json` |
| Laravel seeder graphs | **9 reviewed** | `database/seeders/data/hospital_automation_workflows.php` |
| Laravel sample file | **1 reviewed** | `docs/workflow/samples/appointment-booked-confirmation.json` |
| Catalog **node demo** JSON (one graph per catalog node) | **46 present, not treated as the 15 product workflows** | same frontend folder (`appointmentBooked.workflow.json`, `onChatMessage.workflow.json`, …) |
| Fifteen JSON files inside `HIP_backend` | **0** | Not provided in this repo |

The manager asked for 15 saved workflow JSON files. **Those 15 files were not attached to this Laravel workspace.** Thirteen campaign-style graphs were reviewed from the frontend automation folder (the set that contains `campaignKey`). Do not treat catalog stubs as the missing two.

Shared JSON facts for all 13 campaign graphs: `organization_id: 2`, `hospital_id: 12`, `status: "inactive"`, `builderVersion: "1"`, empty `templateId`, `recipient` on messaging nodes, `campaignStep` on messages. **None** use inbound WhatsApp/SMS webhook triggers.

### 4.2 Product campaign workflows (13)

#### 1. First Appointment Nurturing  
- **File:** `firstAppointmentNurturing.workflow.json`  
- **ID / key:** `campaignKey: first_appointment_nurturing`  
- **Trigger:** `patientRegistered` (`registrationSource: any`, `patientType: any`)  
- **Nodes:** wait 7d → condition `appointment.exists == false` → sendWhatsApp (`nurture_1`) → wait 2d → condition → sendSms (`nurture_2`) → wait 24h → condition → sendPush (`nurture_3`) → wait 2h → condition → sendWhatsApp (`nurture_4`) → end; false branches → end booked  
- **Wait:** `waitType: duration` (maps to Laravel `delay` alias)  
- **Extra JSON:** `suppressOnAppointment: true` — **no Laravel reader**  
- **Context needed:** patient, hospital, `appointment.exists`, `booking_link`, `hospital_phone`  
- **Unresolved:** JEXL `expression`; suppress-on-book; empty templateId + inline `message`

#### 2. Post-Visit Follow-up  
- **Key:** `post_visit_followup`  
- **Trigger:** `appointmentCompleted` (`requireFollowUp: yes`)  
- **Nodes:** condition `followup.exists == true` → sendWhatsApp to **doctor** → wait `relative_date` before followup by 4 days → WhatsApp patient → wait `relative_date` **on** followup date → SMS patient → end; false → end no follow-up  
- **Wait:** `waitType: relative_date` + `relativeDateField` — **not implemented** in `DelayScheduler`  
- **Context:** `followup.exists`, `followup.date`, doctor recipient  

#### 3. Missed Appointment Restart  
- **Key:** `missed_appointment_restart`  
- **Trigger:** `appointmentMissed`  
- **Nodes:** WhatsApp → wait 2 days → condition `appointment.exists == false` → SMS → end  
- **Notes in JSON:** cancel conflicting first-appointment / follow-up steps by `campaignKey` — **not in Laravel**

#### 4. Digital Prescription Share  
- **Key:** `digital_prescription`  
- **Trigger:** `prescriptionAdded`  
- **Nodes:** sendWhatsApp (`pharmacy_link`) → end  

#### 5. Medicine Reminder  
- **Key:** `medicine_reminder`  
- **Trigger:** `medicineReminder` → canonical `medicineReminderDue`  
- **Config:** `reminderTiming: at_due`, `minutesBefore: 30`  
- **Nodes:** sendWhatsApp (`medicine_name`, `dosage`, `frequency`) → end  
- **Existing:** schedule table + `medicine-reminders:dispatch`; trigger config minutesBefore **not proven mapped**

#### 6. Birthday Wish  
- **Key:** `birthday_wish`  
- **Trigger:** `birthday` (`triggerTiming: on_birthday`, `daysBefore: 1`, `executionTime: 09:00`)  
- **Nodes:** sendWhatsApp → end  
- **Missing:** daily birthday scheduler  

#### 7. Women's Day Wish  
- **Key:** `womens_day_wish`  
- **Trigger:** `anniversary` → canonical `anniversaryReached` (`anniversaryType: womens_day`, `on_date`, `09:00`)  
- **Nodes:** condition `patient.gender == "female"` → WhatsApp → end  
- **Missing:** calendar/occasion scheduler; `AnniversaryReached` payload is `Persons` only (no type)  

#### 8–9. Inactive Patient 30 / 90  
- **Keys:** `inactive_30`, `inactive_90`  
- **Trigger:** `scheduledEvent` recurring daily (`10:00` / `10:30`, `endCondition: never`)  
- **Nodes:** condition `last_visit >= 30` / `>= 90` → WhatsApp or SMS → end  
- **Missing:** cohort scheduler; `last_visit` / `last_visit_days` context  

#### 10–12. Dentist / Senior / Parent–Child segments  
- **Keys:** `dentist_segment`, `senior_patient_segment`, `parent_child_segment`  
- **Trigger:** `appointmentBooked`  
- **Conditions:** `appointment.department == "Dentistry"`; `patient.age >= 60`; `patient.age < 18 \|\| patient.relationship == "child"`  
- **Messaging:** patient WhatsApp/SMS or **caregiver** WhatsApp  

#### 13. Segment variants (combined)  
- **Key:** `segment_variants`  
- **Trigger:** `appointmentBooked`  
- **Edges:** dentist true→msg; false→senior; senior false→child; each msg→end  
- **Note:** sequential if/else, not parallel fan-out  

### 4.3 Laravel seeder workflows (9) — backend samples, not the frontend campaign set

| Name | Trigger | Graph (nodeTypes) | Wait | Condition | Channel |
|---|---|---|---|---|---|
| Appointment Booked Confirmation | `appointmentBooked` | delay 5 min → sendWhatsApp | `type/value` minutes | — | patient implied (inline template) |
| Appointment Tomorrow Reminder | `appointmentBooked` | delay 24h → sendPush | hours | — | push |
| Lab Report Ready Notification | `labReportReady` | condition `patient.segment exists` → push **or** email | — | structured `rules` | push/email |
| Medicine Refill Due Reminder | `medicineRefillDue` | condition segment → WhatsApp | — | structured; **false branch missing** (dead-end) | WhatsApp |
| Invoice Generated + Payment Reminder | `invoiceGenerated` | email → delay 3d → SMS | days | — | email/sms |
| Birthday Engagement | `birthday` | WhatsApp | — | — | WhatsApp |
| Appointment Feedback Flow | `appointmentCompleted` | delay 2h → WhatsApp → condition `rating < 3` → createRecord `support_tickets` | hours | structured | WhatsApp + DB |
| Lab Report AI Summary | `labReportReady` | `aiPrompt` | — | — | optional email via `outputChannel` |
| Diabetic Patient Campaign | `campaignTriggered` | **parallel** WhatsApp + email from trigger | — | `segment` on trigger data | both |

Seeder graphs use **Laravel-shaped** delay (`type`+`value`) and **structured** conditions. Campaign JSON uses **frontend-shaped** wait/expression. Both must be supported or normalized at compile time.

### 4.4 Distinct types found in reviewed product + seeder graphs

**Triggers (union):** `patientRegistered`, `appointmentBooked`, `appointmentCompleted`, `appointmentMissed`, `prescriptionAdded`, `medicineReminder`/`medicineReminderDue`, `birthday`, `anniversary`/`anniversaryReached`, `scheduledEvent`, `labReportReady`, `medicineRefillDue`, `invoiceGenerated`, `campaignTriggered`.

**Action/flow nodes (union):** `wait`/`delay`, `condition`, `sendWhatsApp`, `sendSms`/`sendSMS`, `sendPush`, `sendEmail`, `aiPrompt`, `createRecord`, `end`.

**Not present in these reviewed graphs:** inbound `onChatMessage`, `webhookEvent`, `sendAiChat`, `sendAiVoice`, `dbUpdate`/`dbDelete`, `httpRequest`. Chatbot is implemented in Laravel but is **not** one of the 13 campaign JSON files.

---

## 5. Manager Step 1 — Event inventory

| Event name | Category | Triggered by (intended) | Relevant workflow(s) | Existing implementation | Evidence | Missing work |
|---|---|---|---|---|---|---|
| Patient registered (created) | Database/domain | `Persons` / HIP user create | First Appointment Nurturing | Event `PatientRegistered` + listener registered | Class + HA provider; **no observer** | Observer or Auth/registration service dispatch; context `appointment.exists` |
| Appointment booked / confirmed | Database **created + updated** | `DoctorBooking` confirmed | Dentist/senior/parent campaigns; seeder confirmation + tomorrow reminder | Observer + `AppointmentBooked` + queued listener + engine | `DoctorBookingObserver`, `AppointmentBookedListener` | Department/age/relationship/caregiver on context; campaignKey |
| Appointment completed | Database **updated** | Status → completed | Post-visit follow-up; seeder feedback | Event + `DoctorBookingStatusService` | Status service match | Follow-up fields; `requireFollowUp`; relative waits |
| Appointment cancelled | Database **updated** | Status → cancelled | None in 13 campaign JSON | Event + status service | Status service | Confirm product needs a workflow before adding graphs |
| Appointment missed | Database **updated** (no-show) | Status → missed/no-show | Missed Appointment Restart | Event + listener **only** | No `event(new AppointmentMissed` in app | Dispatch from status service/observer; campaign cancel policy |
| Appointment rescheduled | Database **updated** | Date/slot change | None in reviewed JSON | Event + listener only | No dispatcher | Do **not** invent unless product requires |
| Appointment deleted / soft-deleted | Database delete | — | **Not required by JSON** | Observer has no deleted/softDeleted | Observer source | **Do not implement** unless product confirms |
| Prescription created | Database **created** | `PrescriptionService` | Digital prescription | `PrescriptionCreated` → `CreateMedicineReminderSchedules` → `prescriptionAdded` workflows | PrescriptionService + MR provider | Ensure digital_rx graph vs reminder graph isolation |
| Medicine reminder due | Scheduled | `medicine-reminders:dispatch` | Medicine Reminder campaign | Job → `WorkflowExecutionBridge` (`medicineReminderDue`) | Console schedule + execution service | Align `minutesBefore`; listen or drop unused `MedicineReminderTriggered` |
| Lab report ready | Database/domain | Lab report published | Seeder lab notify + AI summary | Event class + listener | **No model dispatch** | Wire report/result model or service |
| Lab test ordered / lab completed | Database | Order/complete | Catalog only | Events exist | No dispatch | Only if JSON/product requires |
| Invoice generated | Database **created** | Invoice insert | Seeder invoice + payment reminder | Event class | No observer on `Invoice` | Observer or invoice service |
| Payment received / pending | Database **updated** | Invoice status | Seeder delay SMS; Kernel pending command | Events exist; command sends push **without** event | `SendPendingPaymentReminders` | Decide: fold command into `PaymentPending` or leave as non-workflow |
| Birthday | Scheduled | Daily 09:00, optional daysBefore | Birthday Wish + seeder | Event + passthrough executor | No schedule | Artisan command + Persons DOB query |
| Anniversary / Women's Day | Scheduled | Calendar occasion | Women's Day Wish | `AnniversaryReached` (Persons only) | No schedule, no `anniversaryType` | Occasion calendar + gender already in JSON condition |
| Inactive 30/90 | Scheduled | Daily recurring `scheduledEvent` | Inactive Patient 30/90 | `ScheduledEventTriggerExecutor` logs only | No dispatcher, no `last_visit` | Cohort job per hospital |
| Campaign triggered | Manual / segment | Admin or cron | Diabetic seeder | Event + listener | Manual API trigger only | Segment loader |
| Chat message received | External HTTP | `POST /api/chat/completions` | Chatbot (not in 13 JSON) | `ChatbotWorkflowService` **sync**; `MessageReceived` unused | ChatbotController | Optional: also fire event for outbound graphs; do **not** force webhook observer |
| Incoming WhatsApp / SMS | External webhook | Provider webhook | **None in reviewed JSON** | Outbound notification services only | No inbound route | **Do not invent** WhatsAppIncomingEvent until a webhook and a workflow exist |
| Membership / rewards / procedure / webhook / api / userPlanExpiry / familyPackageTier / appointmentReminder | Mixed | Catalog | Not in 13 campaign JSON | Some events exist | Unwired | Defer until a saved graph needs them |

---

## 6. Manager Step 2 — Event / listener / observer mapping

Legend: **(1)** observer-dispatched, **(2)** webhook/HTTP, **(3)** scheduled job.

### 6.1 Implement / complete (supported by JSON or existing product)

| Required event | Class (reuse) | Listener | Observer / entry | Payload | Hospital / patient | Idempotency | Missing |
|---|---|---|---|---|---|---|---|
| Appointment confirmed **(1)** | Keep `HA\AppointmentBooked` | Keep `AppointmentBookedListener` | Keep `DoctorBookingObserver` created+updated | `appointment` DoctorBooking | `booking.hospital_id`, `patient_id` / `member_id` | Engine `alreadyStarted` by appointment_id | Enrich context: department name, age, relationship, caregiver contact; honour `campaignKey` |
| Appointment completed **(1)** | Keep `HA\AppointmentCompleted` | Keep `DispatchHospitalAutomationWorkflow` | Keep status service; optionally observer `updated` | `appointment` | same | same | Follow-up date on booking if column exists (**uncertain**) |
| Appointment cancelled **(1)** | Keep `HA\AppointmentCancelled` | Keep generic listener | Status service | `appointment` | same | same | Only if a cancelled graph is published |
| Appointment missed **(1)** | Keep `HA\AppointmentMissed` | Keep generic listener | **Add dispatch** from status service when no-show — **not** a new webhook | `appointment` | same | same + campaign cancel of `first_appointment_nurturing` | Status enum mapping TBD |
| Patient registered **(1)** | Keep `HA\PatientRegistered` | Keep generic listener | **New** observer or registration service on `Persons`/`HIPUser` **created only** | `patient`, `organization_id` | org on user/person; hospital from payload or default — **uncertain** | Prevent duplicate on profile updates | Confirm which table is “patient registered” |
| Prescription added **(1)** | Keep `MR\PrescriptionCreated` | Keep `CreateMedicineReminderSchedules` | `PrescriptionService` | `prescription` | hospital.organization_id | Do **not** also register `StartWorkflowOnPrescriptionAdded` | Digital vs reminder workflow selection |
| Medicine due **(3)** | Keep schedule job path | Optional listener on `MedicineReminderTriggered` **or** keep bridge | `medicine-reminders:dispatch` | schedule + prescription | hospital on prescription | Schedule status pending→processing | Unify event vs direct `WorkflowExecutionBridge` |
| Birthday **(3)** | Keep `HA\BirthdayReached` | Generic listener | **New** daily command, not observer | `patient` Persons | hospital isolation **uncertain** (Persons vs bookings) | One fire per patient per year | Query DOB; `executionTime` |
| Anniversary **(3)** | Keep `HA\AnniversaryReached`; extend payload with `anniversaryType` | Generic listener | **New** calendar command | patient + type | same | One fire per type per year | Women's Day vs wedding anniversary |
| Inactive scan **(3)** | Need `ScheduledEvent` event **or** command → `AutomationEngine::handle('scheduledEvent', cohortPayload)` | Generic or dedicated | **New** daily job; **not** model observer | patient + `last_visit` | workflow hospital_id | One message per campaignKey per patient per window | Cohort query definition |
| Chat completions **(2)** | Keep HTTP path; optionally also `HA\MessageReceived` | Chat uses `ChatbotWorkflowService` today | `ChatbotController` (Sanctum) | messages, session, org, hospital | request + session | session message dedupe already | Do not replace with observer |
| Manual campaign **(2)** | `HA\CampaignTriggered` | Generic | `POST /hospital-automation/trigger` | context array | payload | none | Segment expansion |

### 6.2 Do not force into observers

| Topic | Correct source | Why |
|---|---|---|
| WhatsApp/SMS inbound | Webhook controller **if** a provider is added | No inbound routes today; JSON has no inbound trigger |
| Chat | Existing REST chatbot | Already sync workflow execution |
| Birthday / Women's Day / inactive | Scheduler | Time-based in JSON (`executionTime`, `repeatFrequency`) |
| Medicine due times | Existing schedule rows + cron | Already a scheduler, not a model create |

### 6.3 Expected payload forwarded into AutomationEngine

Reuse `AutomationContextBuilder::merge` / `fromAppointment` / `fromPrescription` / `fromPatient` / `fromInvoice` / `fromLab`. Gaps vs JSON variables: `appointment.exists`, `followup.*`, `booking_link`, `hospital_phone`, `pharmacy_link`, `medicine_name`/`dosage`/`frequency`, `last_visit`, `patient.relationship`, caregiver mobile, `campaignKey` on execution meta.

Duplicate-dispatch: `AutomationEngine::alreadyStarted` keys on `appointment_id` only. Chat and scheduled cohort runs **must not** use that appointment-id gate incorrectly (already skipped when appointment_id empty). Campaign graphs need **campaignKey + patient + hospital** idempotency — **missing**.

---

## 7. Proposed folder structure

Treat **Automation** as the domain name, implemented by **evolving `HospitalAutomation`**, not a second engine. Medicine Reminder stays a product module.

```
app/Modules/HospitalAutomation/          # later optional rename: Automation
├── HospitalAutomationServiceProvider.php
├── Contracts/HospitalAutomationEvent.php
├── Support/TriggerCatalog.php
├── Events/                              # one class per trigger that is actually dispatched
├── Listeners/
│   ├── AppointmentBookedListener.php    # keep specialized
│   └── DispatchHospitalAutomationWorkflow.php
├── Observers/
│   ├── DoctorBookingObserver.php        # extend status → missed if product confirms
│   └── (PatientRegisteredObserver.php when Step 2 implements it)
├── Services/
│   ├── AutomationEngine.php             # THE engine — do not duplicate
│   ├── AutomationContextBuilder.php
│   ├── HospitalAutomationTriggerService.php
│   ├── ChatbotWorkflowService.php
│   └── ChatbotConversationService.php
├── Console/Commands/                    # birthday, anniversary, scheduledEvent, inactive cohort
├── Jobs/
├── Testing/
├── Controllers/ + Routes/               # catalog + manual trigger
└── Executors/                           # only if still needed after registry cleanup

app/Modules/Workflow/                    # persistence + graph runtime (keep)
├── Models, Repositories, Compiler, Runtime, Executors, Builder APIs
└── (retire unused Workflow/Events and unregistered listener after confirmation)

app/Modules/MedicineReminder/            # dose schedules + notification adapters (keep)
├── remain: models, jobs, notifications, prescription listener
└── continue to call WorkflowExecutor via bridge/engine — do not grow a second graph walker
```

**Not proposed:** copying `d:\hip-automation\src\runtime` into PHP; a `WorkflowAutomationEngine` class beside `AutomationEngine`; WhatsAppIncomingEvent without a webhook.

---

## 8. Existing-file-to-proposed-location mapping

| Existing path | Disposition | Namespace change |
|---|---|---|
| `HospitalAutomation/Services/AutomationEngine.php` | **Stay** — central engine | None in Step 2 |
| `HospitalAutomation/Services/AutomationContextBuilder.php` | Stay; extend context fields | None |
| `HospitalAutomation/Services/HospitalAutomationTriggerService.php` | Stay (thin adapter) | None |
| `HospitalAutomation/Support/TriggerCatalog.php` | Stay | None |
| `HospitalAutomation/Events/*` | Stay; dispatch the ones JSON needs | None |
| `HospitalAutomation/Listeners/*` | Stay | None |
| `HospitalAutomation/Observers/DoctorBookingObserver.php` | Stay; optional missed/reschedule | None |
| `HospitalAutomation/Executors/HospitalDomainTriggerExecutor.php` | **Uncertain** — currently never registered for catalog types; either register **before** passthrough or delete after tests | Manual review |
| `HospitalAutomation/Executors/AiPromptExecutor.php` | Stay in registry | None |
| Chatbot services | Stay under HA until a Chat submodule is justified | None |
| `Workflow/Services/Runtime/*` | Stay | None |
| `Workflow/Executors/**` | Stay as node processors | None |
| `Workflow/Events/*` | **Do not move**; mark obsolete after confirming no string references in tests | None until Step 2 complete |
| `Workflow/Listeners/StartWorkflowOnPrescriptionAdded.php` | Leave unregistered | Do not dual-register |
| `Workflow/Services/Runtime/WorkflowTriggerDispatcher.php` | Later fold call sites into `AutomationEngine::handle` | None now |
| `Workflow/Services/Bridge/*` | Stay until medicine path uses engine.handle(`medicineReminderDue`) | None now |
| `MedicineReminder/**` except workflow-shaped leftovers | **Remain** | None |
| `app/Services/DoctorBookingStatusService.php` | Remain; add missed dispatch here if status enum exists | None |
| `app/Services/PrescriptionService.php` | Remain | None |
| `app/Console/Commands/SendPendingPaymentReminders.php` | Remain until product chooses workflow vs legacy push | Uncertain |
| Frontend `src/runtime/**` | Remain in Node app; not a Laravel target | n/a |

**Provider changes (later, not this phase):** none required to start Step 2. When new observers/commands exist, register them only in `HospitalAutomationServiceProvider` / `routes/console.php`.

**Files that should remain where they are:** all Workflow builder HTTP, all MedicineReminder notifications, Filament/auth, chatbot tables/models, ChannelManager.

---

## 9. Node processor inventory

Canonical IDs after `NodeTypeNormalizer`.

| JSON nodeType / key | Workflows | Existing executor | Proposed responsibility | Input | Output / control | Gap |
|---|---|---|---|---|---|---|
| `patientRegistered` | First appointment | Passthrough (registry) | Start graph; variables from patient | Persons + hospital | continue | Event not dispatched |
| `appointmentBooked` | Segments + seeders | `AppointmentBookedTriggerExecutor` (log only) | continue after context merge | DoctorBooking | continue | Context fields for conditions |
| `appointmentCompleted` | Post-visit, feedback seeder | Passthrough | continue | booking + followup | continue | followup context |
| `appointmentMissed` | Missed restart | Passthrough | continue | booking | continue | no dispatch |
| `prescriptionAdded` | Digital Rx | `PrescriptionAddedTriggerExecutor` | may create reminder schedules | Prescription | continue | OK if listener stays |
| `medicineReminder` → `medicineReminderDue` | Medicine campaign | `MedicineReminderDueTriggerExecutor` + `MedicineReminderNodeExecutor` (legacy alias) | continue with medicine vars | schedule | continue | minutesBefore |
| `birthday` | Birthday wish, seeder | `BirthdayTriggerExecutor` | continue | patient | continue | no cron |
| `anniversary` → `anniversaryReached` | Women's Day | Passthrough | continue | patient + type | continue | no cron / type |
| `scheduledEvent` | Inactive 30/90 | `ScheduledEventTriggerExecutor` | continue per cohort member | last_visit | continue | no dispatcher |
| `labReportReady` | Seeders | Passthrough | continue | lab context | continue | no dispatch |
| `medicineRefillDue` | Seeder | Passthrough | continue | pharmacy context | continue | no dispatch |
| `invoiceGenerated` | Seeder | Passthrough | continue | Invoice | continue | no dispatch |
| `campaignTriggered` | Diabetic seeder | Passthrough | continue; may fan-out edges | segment | continue | no segment job |
| `messageReceived` / `onChatMessage` | Chatbot HTTP (not in 13 JSON) | Passthrough + `ChatbotWorkflowService` | response-only chat | session messages | `ai_chat_message` | inbound WA/SMS n/a |
| `wait` → `delay` | Most campaigns + seeders | `DelayExecutor` | pause execution | duration **or** relative_date | `wait` + job resume | **relative_date unsupported**; FE uses `amount`/`unit` vs seeder `value`/`type` — DurationScheduler already maps both for duration |
| `condition` | Most graphs | `ConditionExecutor` | branch true/false | `rules` **or** `expression` | `branch` handle | **`expression` ignored** (empty rules ⇒ always true) |
| `sendWhatsApp` | Many | `SendWhatsAppExecutor` | ChannelManager whatsapp | templateId or body, recipient | continue | caregiver recipient; campaignStep logging |
| `sendSms` → `sendSMS` | Many | `SendSMSExecutor` | sms | same | continue | same |
| `sendPush` | Nurture + seeder | `SendPushExecutor` | push | title/body | continue | same |
| `sendEmail` | Seeders | `SendEmailExecutor` | email | same | continue | — |
| `aiPrompt` / `ai` | Lab AI seeder | `AiPromptExecutor` | HTTP `WORKFLOW_AI_ENDPOINT` | prompt/template | `ai_summary` | fail-closed if unset |
| `createRecord` / `dbCreate` | Feedback seeder | `CreateRecordExecutor` | insert | table/values | continue | `support_tickets` table **uncertain** |
| `end` | All | `EndExecutor` | complete | — | end | — |
| `sendAiChat`, `sendAiVoice`, `sendTemplate`, `webhook`, `databaseUpdate`, `dbDelete` | Catalog / chatbot / other | Executors exist | unchanged | per class | — | Not in 13 campaign JSON |
| `sendIvr`, `httpRequest`, `dbQuery`, `updateAppointment`, `waitUntil`, `cron`, `fhir`, `abdm` | Catalog stubs | **none** | do not invent processors this phase | — | — | Frontend-only |

There is **no** `IfConditionNodeProcessor` class; the equivalent is `ConditionExecutor`. There is **no** `SendSMSNodeProcessor`; the equivalent is `SendSMSExecutor`.

---

## 10. Missing work and unresolved questions

### Missing implementation (evidence-based)

1. Dispatchers for: patient registered, appointment missed, lab report, invoice, birthday, anniversary, scheduled inactive cohort.  
2. `ConditionEngine` support for frontend `data.expression` (or compile-time rewrite to `rules`).  
3. `DelayScheduler` support for `waitType: relative_date`.  
4. Runtime `campaignKey` / `campaignStep` / `suppressOnAppointment` / cancel-on-book.  
5. Context: `appointment.exists`, follow-up, caregiver, `last_visit`, medicine dosage fields, booking/pharmacy links.  
6. HospitalDomainTriggerExecutor vs passthrough registration order.  
7. Dual dispatcher (`AutomationEngine` vs `WorkflowTriggerDispatcher`).  
8. Unused `Workflow\Events` and `MedicineReminderTriggered` listener gap.  
9. Incoming WhatsApp/SMS — **no webhook**, not required by reviewed JSON.  
10. Two extra product JSON files if the manager’s “15” are not the 13 campaign files.

### Questions that must be answered before implementation

1. What are the exact **15** workflow JSON files (paths)? The frontend campaign folder has **13**; Laravel has **9** seeders.  
2. Is `Persons` or `HIPUser` the patient-registered source, and how is `hospital_id` assigned at registration?  
3. Which `DoctorBooking` status value means **missed / no-show**?  
4. Where is **follow-up date** stored (column/table)?  
5. Should `reminders:pending-payments` remain a standalone command or become `PaymentPending` workflows?  
6. Should chatbot also dispatch `MessageReceived` for non-HTTP graphs, or stay exclusive to `/chat/completions`?  
7. Is there an inbound WhatsApp/SMS provider (Gupshup/Meta/MSG91) to attach a webhook to?  
8. Confirm **do not** fire automation on DoctorBooking delete/soft-delete.  
9. May `CreateRecordExecutor` write `support_tickets`, or is that table missing?  
10. Should inactive 30/90 run **one execution per patient** (N executions/day) or one execution with a patient list? JSON is per-patient graph.  
11. Who owns campaign suppression: Laravel execution cancel vs frontend-only `CampaignSuppression.js`?

---

## 11. Recommended implementation sequence

Aligns with the manager; later phases stay out of scope until Step 2 is accepted.

1. **Step 1 (this document):** freeze event inventory from JSON + code.  
2. **Step 2:** wire **only** events the JSON needs:  
   - Keep appointment booked/cancelled/completed.  
   - Add missed dispatch.  
   - Add patient-registered observer/service.  
   - Add birthday + anniversary + scheduledEvent commands.  
   - Do **not** add WhatsAppIncomingEvent without a webhook.  
   - Register new observers/commands in existing HA provider / `routes/console.php`.  
3. **Then (later):** trigger handlers as thin wrappers around `AutomationEngine::handle` — not a new engine.  
4. **Then:** extend `ConditionEngine` + `DelayScheduler` + context builder to match frontend JSON.  
5. **Then:** campaignKey idempotency and suppress-on-appointment.  
6. **Then:** optional namespace rename HospitalAutomation → Automation; retire unused Workflow events.  
7. **Never:** second `WorkflowAutomationEngine`; deleting files because they “look unused.”

Verification after Step 2 (no code in this task): feature tests that events dispatch once; inactive workflows still skipped; outbound ChannelManager unchanged; chatbot still OpenRouter + `data.reply`.

---

## 12. Risks and verification checklist

### Risks

| Risk | Why it matters |
|---|---|
| Building a second engine | Manager and existing docs forbid it; JS runtime in frontend is a trap |
| Observer on every lifecycle | JSON does not ask for delete/restore; noisy duplicate executions |
| Treating catalog stub JSON as product workflows | Inflates node/event scope (`fhir`, `ivr`, …) |
| `expression` conditions silently true | `ConditionEngine` empty rules return true — would message **all** patients |
| Appointment-id idempotency | Blocks legitimate second campaigns or skips chat/scheduled |
| Dual prescription listeners | Duplicate WhatsApp if both registered |
| Hospital isolation | Campaign JSON requires exact `hospital_id` 12 — already in repository |
| Queue | Listeners are `ShouldQueue` with default connection; local `artisan serve` needs a worker for appointment flows |

### Verification checklist (when implementation starts)

- [ ] `DoctorBooking` confirm still fires exactly one `AppointmentBooked` after commit  
- [ ] Cancel/complete still go through status service  
- [ ] Inactive published chatbot workflow still returns “No active chatbot workflow” and does not call OpenRouter  
- [ ] Medicine reminder cron still sends via ChannelManager / notification services  
- [ ] No inbound webhook invented  
- [ ] New observers have tests for created vs updated vs ignored statuses  
- [ ] Frontend `wait` duration still maps to `DelayExecutor`  
- [ ] Document remaining JSON gaps (`relative_date`, `expression`) as **known** until the node phase  
- [ ] `php artisan` schedule:list shows medicine dispatch; birthday/inactive only after those commands exist  

---

## Appendix A — TriggerCatalog vs dispatch (quick)

Registered in catalog (31): appointment\* (6 including reminder), lab\* (3), prescriptionAdded, medicineReminderDue, medicineRefillDue, invoiceGenerated, paymentReceived, paymentPending, membershipExpiry, membershipRenewed, userPlanExpiry, rewardPointsUpdated, rewardTierUpgraded, familyPackageTierUpdated, birthday, anniversaryReached, patientRegistered, procedureCompleted, messageReceived, webhookEvent, apiEvent, scheduledEvent, campaignTriggered.

**Production dispatch today:** appointmentBooked, appointmentCancelled, appointmentCompleted, prescriptionAdded (via PrescriptionCreated), medicineReminderDue (via job/bridge), messageReceived (HTTP, not event).

---

## Appendix B — Confirmation of non-modification

This document is the only file created for this task (`docs/automation/automation-rebuild-analysis.md`). **No existing application PHP, routes, migrations, workflow records, or JSON were deleted, moved, renamed, or edited. No automation implementation was performed.**
