# Step 3.5 Audit — Automation folder migration (no files moved)

**Status:** audit and proposal only. **No PHP was moved or renamed.**

**Date:** 2026-09-22

This proposal **supersedes** the earlier `automation-folder-structure-plan.md` full merge for *this* step. That plan would relocate Workflow runtime, node processors, Medicine Reminder, and builder APIs in one cut. Doing that now would violate Step 3.5 rules (preserve runtime, do not touch Medicine Reminder execution, do not use the move to “improve” architecture, do not start Step 4).

---

## 1. Complete current automation file inventory

There is **no** `app/Listeners`, **no** automation classes under `app/Observers`, **no** `EventServiceProvider`. Autoload is `"App\\": "app/"`.

### 1.1 `app/Modules/HospitalAutomation` (trigger / domain module) — **in scope to relocate**

| Path | Class | Responsibility |
|---|---|---|
| `HospitalAutomationServiceProvider.php` | `HospitalAutomationServiceProvider` | Commands, Event::listen, observers, HA routes, leftover trigger executors |
| `Contracts/HospitalAutomationEvent.php` | `HospitalAutomationEvent` | Event contract |
| `Support/TriggerCatalog.php` | `TriggerCatalog` | Canonical trigger IDs |
| `Support/EventDispatchGuard.php` | `EventDispatchGuard` | Step 2 cache idempotency |
| `Support/AfterCommit.php` | `AfterCommit` | Dispatch after DB commit |
| `Support/PatientAutomationIdentity.php` | `PatientAutomationIdentity` | Org/hospital from person/hipUser |
| `Events/*.php` (22) | See §7 | Domain/scheduled events |
| `Listeners/AppointmentBookedListener.php` | queued | Reload booking → TriggerService |
| `Listeners/DispatchHospitalAutomationWorkflow.php` | queued | Generic → TriggerService |
| `Observers/DoctorBookingObserver.php` | | Confirmed → `AppointmentBooked` |
| `Observers/PersonsObserver.php` | | Primary register → `PatientRegistered` |
| `Services/HospitalAutomationTriggerService.php` | **trigger-handler** | Normalize + scope + `engine.handle` once |
| `Services/AutomationEngine.php` | **fan-out owner** | Lookup + foreach start |
| `Services/AutomationContextBuilder.php` | | Merge context |
| `Services/ChatbotWorkflowService.php` | | HTTP chat → `executeWorkflow` |
| `Services/ChatbotConversationService.php` | | Chat session memory |
| `Executors/HospitalDomainTriggerExecutor.php` | | Catalog trigger passthrough (often skipped if Workflow already registered type) |
| `Executors/AiPromptExecutor.php` | | `aiPrompt` node |
| `Controllers/HospitalAutomationController.php` | | `/api/hospital-automation/*` |
| `Routes/hospitalAutomation.php` | | Those routes |
| `Jobs/RunAutomationTestJob.php` | queued | `automation:test` |
| `Console/Commands/RunAutomationTestCommand.php` | `automation:test` | |
| `Console/Commands/DispatchBirthdayReachedCommand.php` | `hospital-automation:dispatch-birthdays` | |
| `Console/Commands/DispatchAnniversaryReachedCommand.php` | `hospital-automation:dispatch-anniversaries` | |
| `Console/Commands/DispatchScheduledEventsCommand.php` | `hospital-automation:dispatch-scheduled-events` | |
| `Testing/*` (7 files) | | Isolated test runner |

**Events (22, all HA):** AppointmentBooked, AppointmentCompleted, AppointmentCancelled, AppointmentMissed, AppointmentRescheduled, PatientRegistered, BirthdayReached, AnniversaryReached, ScheduledEvent, LabTestOrdered, LabReportReady, LabCompleted, MedicineRefillDue, InvoiceGenerated, PaymentReceived, PaymentPending, MembershipExpiry, MembershipRenewed, RewardPointsUpdated, RewardTierUpgraded, ProcedureCompleted, MessageReceived, CampaignTriggered.

**Instantiated from outside the module:** `DoctorBookingStatusService` (Cancelled/Completed), `ChatbotController`, tests, seeders, `CreateMedicineReminderSchedules` (TriggerService), `WorkflowServiceProvider` (`TriggerCatalog`).

### 1.2 `app/Modules/Workflow` — **generic runtime + builder (do not move in Step 3.5)**

Classification:

| Area | Files | Class (A/B/C/D) |
|---|---|---|
| Compiler | `Services/Compiler/WorkflowCompiler.php` | **B** generic runtime |
| Executor | `Services/Runtime/WorkflowExecutor.php` | **B** |
| Registry | `NodeExecutorRegistry.php` | **B** |
| Node executors | `Executors/**` (23) | **B** (Step 4); medicine trigger executors also **C**-adjacent |
| Condition / delay / channels | `ConditionEngine`, `DelayScheduler`, `ChannelManager`, `TemplateManager`, `VariableResolver`, `ActionDispatcher`, `AiVoiceCallService` | **B** |
| Persistence | Models, Enums, DTO, `WorkflowRepository` | **B** + used by automation lookup |
| Builder HTTP | Controllers, Requests, Resources, Policies, `Routes/workflow.php`, Builder services | **B** / frontend contract |
| Delay job | `Jobs/ContinueWorkflowExecutionJob.php` | **B** — **queued FQCN** |
| Duplicate events | `Events/{AppointmentBooked,BirthdayReached,LabReportReady,MedicineReminderDue,PaymentReceived,PrescriptionAdded}.php` | Unused on bus (**D** leftover) |
| Unregistered listener | `Listeners/StartWorkflowOnPrescriptionAdded.php` | **D** leftover |
| Second dispatcher | `WorkflowTriggerDispatcher.php` | leftover; prescription path no longer uses it |
| Bridges | `WorkflowExecutionBridge`, `MedicineWorkflowBridge` | **C** medicine due / pharmacy resolve — **do not move** |

### 1.3 `app/Modules/MedicineReminder` — **do not move in Step 3.5**

Entire product: models, cron `medicine-reminders:dispatch`, `SendMedicineReminderJob`, `MedicineReminderExecutionService`, notifications, CRUD API, `PrescriptionCreated`, `CreateMedicineReminderSchedules` (Step 3 adapter → TriggerService).

### 1.4 Application-level — **do not move**

`app/Services/DoctorBookingStatusService.php`, `PrescriptionService.php`, `app/Models/DoctorBooking.php`, `Persons.php`, `app/Http/Controllers/Api/ChatbotController.php`, `app/Livewire/Admin/Automation/**`, `routes/api.php`, `routes/web.php`, `routes/console.php` (schedule **strings** stay), `bootstrap/providers.php` (will need one line after move), seeders.

### 1.5 Tests that import HA namespaces

- `tests/Feature/HospitalAutomationTriggerHandlerTest.php`
- `tests/Feature/HospitalAutomationStep2DispatchTest.php`
- `tests/Feature/AppointmentBookedAutomationTest.php`
- `tests/Feature/ChatbotWorkflowCompletionsTest.php`
- `tests/Feature/Automation/RealNotificationAutomationTest.php`
- `tests/Unit/NodeTypeNormalizerTest.php` (TriggerCatalog)
- `tests/Support/FrontendNodeCatalog.php`, `WorkflowAutomationTestCase.php`

---

## 2. Proposed target folder tree (this step only)

Populate only folders that receive **existing** HA files. Do **not** create empty Processors/Messaging/Persistence just to match the concept diagram. Do **not** invent inbound events.

```
app/Modules/Automation/
├── AutomationServiceProvider.php          # today HospitalAutomationServiceProvider
├── Contracts/
│   └── HospitalAutomationEvent.php        # keep class name
├── Support/                               # TriggerCatalog, EventDispatchGuard, AfterCommit, PatientAutomationIdentity
├── Events/                                # all 22 existing HA events (no new classes)
├── Listeners/                             # AppointmentBookedListener, DispatchHospitalAutomationWorkflow
├── Observers/                             # DoctorBookingObserver, PersonsObserver
├── TriggerHandlers/
│   └── HospitalAutomationTriggerService.php   # SAME class; only folder/namespace
├── Engine/
│   ├── AutomationEngine.php               # SAME class
│   └── AutomationContextBuilder.php
├── Services/                              # ChatbotWorkflowService, ChatbotConversationService
├── Executors/                             # HospitalDomainTriggerExecutor, AiPromptExecutor (unchanged behavior)
├── Http/
│   ├── Controllers/HospitalAutomationController.php
│   └── Routes/hospitalAutomation.php      # URL prefix unchanged
├── Jobs/RunAutomationTestJob.php
├── Console/Commands/                      # 4 existing commands; signatures unchanged
└── Testing/

# Temporary compatibility (queue / stray imports):
app/Modules/HospitalAutomation/
└── aliases.php or stub class_alias files for queued listener/event FQCNs
```

Conceptual folders **not created in this step:** `Processors/`, `Scheduling/` (commands stay under Console), `Messaging/`, `Persistence/`.

---

## 3. Exact file-by-file move map (files we plan to move)

Class names stay the same unless noted. New namespace root: `App\Modules\Automation`.

| CURRENT FILE | TARGET FILE | NEW NAMESPACE | REFERENCES | RISK | ACTION |
|---|---|---|---|---|---|
| `HospitalAutomation/HospitalAutomationServiceProvider.php` | `Automation/AutomationServiceProvider.php` | `App\Modules\Automation` | `bootstrap/providers.php` | Boot if missed | MOVE + update providers.php |
| `HospitalAutomation/Contracts/HospitalAutomationEvent.php` | `Automation/Contracts/` | `...Automation\Contracts` | All HA events | Low | MOVE |
| `HospitalAutomation/Support/*.php` (4) | `Automation/Support/` | `...Automation\Support` | Engine, observers, commands, Workflow provider (`TriggerCatalog`) | Medium | MOVE + update Workflow provider import |
| `HospitalAutomation/Events/*.php` (22) | `Automation/Events/` | `...Automation\Events` | Provider listen list, status service, observers, commands, tests | **HIGH queue** (SerializesModels) | MOVE + **class_alias** old FQCN |
| `HospitalAutomation/Listeners/*.php` (2) | `Automation/Listeners/` | `...Automation\Listeners` | Provider `Event::listen`; **ShouldQueue** | **HIGH queue** | MOVE + **class_alias** |
| `HospitalAutomation/Observers/*.php` (2) | `Automation/Observers/` | `...Automation\Observers` | Provider `observe()` | Medium | MOVE |
| `HospitalAutomation/Services/HospitalAutomationTriggerService.php` | `Automation/TriggerHandlers/` | `...Automation\TriggerHandlers` | Listeners, controller, medicine listener, tests | Medium DI | MOVE class, **do not rename** |
| `HospitalAutomation/Services/AutomationEngine.php` | `Automation/Engine/` | `...Automation\Engine` | TriggerService, chatbot, tests | Medium DI | MOVE, **do not rename** |
| `HospitalAutomation/Services/AutomationContextBuilder.php` | `Automation/Engine/` | `...Automation\Engine` | Engine, HA executors | Medium | MOVE |
| `HospitalAutomation/Services/Chatbot*.php` (2) | `Automation/Services/` | `...Automation\Services` | `ChatbotController` | Medium | MOVE |
| `HospitalAutomation/Executors/*.php` (2) | `Automation/Executors/` | `...Automation\Executors` | HA provider registry | Low | MOVE (not Step 4 rewrite) |
| `HospitalAutomation/Controllers/HospitalAutomationController.php` | `Automation/Http/Controllers/` | `...Automation\Http\Controllers` | Routes file | Low | MOVE; **URLs unchanged** |
| `HospitalAutomation/Routes/hospitalAutomation.php` | `Automation/Http/Routes/` | n/a | Provider `loadRoutes` | Low | MOVE file; prefixes stay |
| `HospitalAutomation/Jobs/RunAutomationTestJob.php` | `Automation/Jobs/` | `...Automation\Jobs` | Test runner | **HIGH queue** | MOVE + alias |
| `HospitalAutomation/Console/Commands/*.php` (4) | `Automation/Console/Commands/` | `...Automation\Console\Commands` | HA provider `$this->commands`; `routes/console.php` uses **signature strings** | Low if signatures unchanged | MOVE |
| `HospitalAutomation/Testing/*.php` (7) | `Automation/Testing/` | `...Automation\Testing` | Test runner / RealNotification tests | Low | MOVE |

**After move:** empty `HospitalAutomation/` except compatibility aliases. Do not delete aliases until queues are drained.

---

## 4. Namespace changes (summary)

`App\Modules\HospitalAutomation\{X}` → `App\Modules\Automation\{X}` except:

- `Services\HospitalAutomationTriggerService` → `TriggerHandlers\HospitalAutomationTriggerService`
- `Services\AutomationEngine` → `Engine\AutomationEngine`
- `Services\AutomationContextBuilder` → `Engine\AutomationContextBuilder`
- `Controllers\...` → `Http\Controllers\...`
- Provider class: `HospitalAutomationServiceProvider` → `AutomationServiceProvider`

**Keep class names:** `HospitalAutomationTriggerService`, `AutomationEngine`, `AppointmentBookedListener`, all event class names, command **signatures**.

No per-trigger `*TriggerHandler` classes.

---

## 5. Provider / event / scheduler registrations affected

| Registration | Change |
|---|---|
| `bootstrap/providers.php` | Replace HA provider with `App\Modules\Automation\AutomationServiceProvider` |
| `Event::listen` inside provider | Same events/listeners, new namespaces |
| `DoctorBooking::observe` / `Persons::observe` | New observer FQCNs |
| Console commands | Re-register four command classes; **signatures unchanged** so `routes/console.php` needs **no** edit |
| `WorkflowServiceProvider` | Update `use ...TriggerCatalog` |
| MedicineReminder provider | **No change** (still listens `PrescriptionCreated` → `CreateMedicineReminderSchedules`) |
| `CreateMedicineReminderSchedules` | Update `use` of TriggerService only |

---

## 6. Queued-job / serialization risks

| Payload | Old FQCN stored on queue | If moved without alias |
|---|---|---|
| `AppointmentBookedListener` | listener class + `AppointmentBooked` event | In-flight booked jobs fail to unserialize |
| `DispatchHospitalAutomationWorkflow` | listener + any HA event | Same |
| `CreateMedicineReminderSchedules` | **not moved** | Safe |
| `SendMedicineReminderJob` | **not moved** | Safe |
| `ContinueWorkflowExecutionJob` | **not moved** | Safe |
| `RunAutomationTestJob` | test job only | Low prod; still alias |

**Mitigation (required):** `class_alias` from every old queued event/listener/job FQCN to the new class, kept until production queues are empty. Drain workers during deploy if possible.

Eloquent observer FQCNs are not typically queued. Failed-jobs table may contain old listener names.

No evidence of workflow **DB rows** storing PHP class names (trigger_type is a string like `appointmentBooked`).

---

## 7. Tests that need namespace/path updates

All files in §1.5 plus any `string` FQCNs in those tests. Test **file paths** can stay (`HospitalAutomationTriggerHandlerTest.php` etc.).

---

## 8. Classes that stay in MedicineReminder or Workflow (intentional)

**MedicineReminder — all of it**, including `CreateMedicineReminderSchedules` and `PrescriptionCreated`. Moving would recouple queues, cron, notifications, and CRUD. Step 3 adapter remains: listener → TriggerService.

**Workflow — all runtime, builder, repository, executors, delay job, bridges.** Forcing them into `Automation/Engine` or `Processors` is Step 4 / a later cutover, not this refactor.

**Unused `Workflow\Events\*` and `StartWorkflowOnPrescriptionAdded`:** leave in place (no delete in this step).

---

## 9. Expected runtime flow after migration

```
EVENT
  → Listener / Command / Observer          (new Automation namespaces; same classes)
  → HospitalAutomationTriggerService       (TriggerHandlers\; same class)
  → AutomationEngine::handle()             (Engine\; same class)
  → WorkflowRepository::findPublishedByTrigger()   (still Workflow)
  → WorkflowExecutor::start()                      (still Workflow)

medicineReminderDue:
  medicine-reminders:dispatch → jobs → MedicineReminderExecutionService
  → WorkflowExecutionBridge → WorkflowExecutor::start
```

No second engine. No extra workflow discovery. No per-trigger handlers.

---

## 10. Risks and rollback

| Risk | Rollback |
|---|---|
| Missed `use` import | Grep `HospitalAutomation\` after move; restore from git |
| Queue poison | Keep `class_alias`; revert provider + files |
| Chatbot 500 | `ChatbotController` import not updated |
| Livewire admin | Does not import HA module today (uses Workflow models) — **low** if we do not move Workflow |
| Command not scheduled | Signatures unchanged — **low** |

Rollback: revert the migration commit; `bootstrap/providers.php` back to HA provider.

---

## Files not moving (why)

- Entire `Modules/Workflow` — generic runtime (B); delay job FQCN; builder `/api/workflows`
- Entire `Modules/MedicineReminder` — product + queued job + cron
- `app/Services/*`, models, ChatbotController, Livewire, `routes/console.php` schedule lines
- Node processors / ConditionEngine / DelayScheduler / ChannelManager — Step 4
- Frontend, JSON, schema

---

## Stop

No files have been moved. Approve this **HospitalAutomation → Automation** slice (with queue `class_alias`, Workflow + MedicineReminder left in place) before any implementation.
