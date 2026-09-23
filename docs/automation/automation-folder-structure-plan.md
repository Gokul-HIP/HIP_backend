# Automation Module Folder Structure Plan

**Status:** analysis and documentation only. No application PHP was created, moved, renamed, or deleted. Workflow storage, builder API routes, and frontend contracts are unchanged.

**Date:** 2026-09-22  
**Autoload (Fact):** `"App\\": "app/"` in `composer.json`. Module classes are `App\Modules\{HospitalAutomation,Workflow,MedicineReminder}\...`. There is no Composer path for `Modules/Automation` today.

**Providers (Fact):** `bootstrap/providers.php` registers `WorkflowServiceProvider`, `HospitalAutomationServiceProvider`, `MedicineReminderServiceProvider`.

---

## 1. Proposed final folder tree

One module: `app/Modules/Automation`. **Do not** keep a competing `app/Modules/Workflow` after migration, and **do not** nest a second workflow engine under `Automation/Workflow/Engine` while `Workflow/` still runs.

```
app/Modules/Automation/
├── AutomationServiceProvider.php          # replaces the three providers after cutover
├── Contracts/
│   ├── AutomationEventInterface.php       # today: HospitalAutomationEvent
│   ├── NodeProcessorInterface.php         # today: NodeExecutorInterface
│   └── WorkflowCompilerInterface.php
├── Support/
│   ├── TriggerCatalog.php
│   └── NodeTypeNormalizer.php
├── Events/                                # Step 2 — one class per dispatched trigger
├── Listeners/
├── Observers/
├── Triggers/                              # trigger metadata / catalog helpers
├── TriggerHandlers/                       # Step 3 — event payload → engine.handle
├── Engine/
│   ├── WorkflowAutomationEngine.php       # wrap/replace AutomationEngine (one engine only)
│   ├── AutomationContextBuilder.php
│   ├── WorkflowExecutor.php               # graph walker (today WorkflowExecutor)
│   ├── WorkflowCompiler.php
│   ├── NodeProcessorRegistry.php          # today NodeExecutorRegistry
│   ├── ConditionEngine.php
│   ├── DelayScheduler.php
│   ├── ActionDispatcher.php
│   └── VariableResolver.php
├── NodeProcessors/
│   ├── Triggers/
│   ├── Flow/                              # wait, condition, end
│   └── Actions/                           # sendWhatsApp, sendSms, sendPush, …
├── Persistence/                           # builder storage — keep API stable
│   ├── Models/
│   ├── Repositories/
│   ├── Enums/
│   └── DTO/
├── Builder/                               # React Flow HTTP API — preserve routes
│   ├── Controllers/
│   ├── Requests/
│   ├── Resources/
│   ├── Policies/
│   ├── Services/
│   └── Routes/builder.php                 # today’s Routes/workflow.php
├── Messaging/
│   ├── ChannelManager.php
│   ├── TemplateManager.php
│   └── (channel adapters reused by medicine reminder)
├── Jobs/
│   └── ContinueWorkflowExecutionJob.php
├── Console/
│   ├── Commands/                          # birthday, anniversary, scheduledEvent, medicine dispatch
├── MedicineReminder/                      # existing product, nested not rewritten
│   ├── Models/
│   ├── Events/                            # PrescriptionCreated, MedicineReminderTriggered
│   ├── Listeners/
│   ├── Jobs/
│   ├── Notifications/
│   ├── Services/
│   ├── Controllers/
│   ├── Routes/medicineReminder.php        # keep URL prefixes
│   └── ...
├── Chatbot/                               # HTTP chat, not inbound WhatsApp
│   ├── ChatbotWorkflowService.php
│   └── ChatbotConversationService.php
├── Http/                                  # catalog + manual trigger (today hospital-automation)
│   ├── Controllers/
│   └── Routes/automation.php
└── Testing/
```

**Leave outside the module (KEEP):** `app/Models/DoctorBooking.php`, `app/Models/Persons.php`, `app/Services/DoctorBookingStatusService.php`, `app/Services/PrescriptionService.php`, `app/Http/Controllers/Api/ChatbotController.php`, `app/Livewire/Admin/Automation/**`, `routes/api.php` chat routes, `routes/web.php` Livewire admin routes, `routes/console.php` schedule lines.

**Temporary dual-namespace (only during migration):** keep `App\Modules\HospitalAutomation` and `App\Modules\Workflow` class aliases pointing at new classes so tests and Livewire do not break. Remove aliases after verification. **Do not** run two engines.

---

## 2. Responsibilities of each major folder

| Folder | Responsibility | Must not |
|---|---|---|
| `Events` / `Listeners` / `Observers` | Manager Step 2. Domain and scheduled start signals. | Invent WhatsApp/SMS inbound events without a webhook |
| `TriggerHandlers` | Manager Step 3. Map event → `triggerType` + context | Walk JSON nodes |
| `Engine` | Single `WorkflowAutomationEngine` + compiler/executor | A second dispatcher beside `WorkflowTriggerDispatcher` after cutover |
| `NodeProcessors` | One processor per JSON `nodeType` used by verified workflows | Duplicate `Executors` forever |
| `Persistence` + `Builder` | Workflow rows, versions, executions, React Flow CRUD/publish | Change URL contracts in the same PR as a namespace move |
| `Messaging` | Outbound WhatsApp/SMS/email/push | Inbound provider webhooks (none exist) |
| `MedicineReminder` | Dose schedules, medicine-workflow CRUD, cron | Its own graph walker |
| `Chatbot` | `POST /api/chat/completions` bridge | Observer on chat messages |
| `Jobs` / `Console` | Delay resume, birthday/inactive/medicine schedule | Kernel duplicates of the same command |
| `Http` | Catalog + manual `campaignTriggered` | Replacing builder CRUD |

---

## 3. Existing-file mapping table

**Action values:** KEEP | MOVE | REFACTOR | REPLACE LATER | DELETE ONLY AFTER VERIFICATION

### 3.1 HospitalAutomation (engine domain)

| Existing file/path | Current responsibility | Proposed path | Action | Dependencies/risk |
|---|---|---|---|---|
| `HospitalAutomation/HospitalAutomationServiceProvider.php` | Registers executors, events, `DoctorBooking` observer, HA routes | `Automation/AutomationServiceProvider.php` (merged) | MOVE | Listed in `bootstrap/providers.php`. Moving without updating that file **breaks boot**. |
| `HospitalAutomation/Contracts/HospitalAutomationEvent.php` | Event contract (`triggerType`, `payload`) | `Automation/Contracts/AutomationEventInterface.php` | MOVE | All HA Events implement it. |
| `HospitalAutomation/Support/TriggerCatalog.php` | 31 canonical trigger IDs | `Automation/Support/TriggerCatalog.php` | MOVE | Used by `WorkflowServiceProvider`, compiler, publish, tests (`FrontendNodeCatalog`). High risk. |
| `HospitalAutomation/Events/*.php` (22 classes) | Domain events | `Automation/Events/` | MOVE | Listeners + tests import FQCNs. `AppointmentBooked` used by observer and `AppointmentBookedAutomationTest`. |
| `HospitalAutomation/Listeners/AppointmentBookedListener.php` | Queued; calls `AutomationEngine` | `Automation/Listeners/` | KEEP then MOVE | Production booked path. |
| `HospitalAutomation/Listeners/DispatchHospitalAutomationWorkflow.php` | Generic HA listener | `Automation/Listeners/` | KEEP then MOVE | Registered for ~20 events. |
| `HospitalAutomation/Observers/DoctorBookingObserver.php` | Confirmed booking → `AppointmentBooked` | `Automation/Observers/` | KEEP then MOVE | Only registered observer. Breaking this stops 4 campaign workflows. |
| `HospitalAutomation/Services/AutomationEngine.php` | Lookup + start executions | `Automation/Engine/WorkflowAutomationEngine.php` | REPLACE LATER | Called by listeners, chatbot, tests. Wrap first; do not add a parallel class that both run. |
| `HospitalAutomation/Services/AutomationContextBuilder.php` | Runtime context | `Automation/Engine/` | MOVE | Engine + HA trigger executor. |
| `HospitalAutomation/Services/HospitalAutomationTriggerService.php` | Thin adapter to dispatcher | `Automation/TriggerHandlers/` or fold into engine | REFACTOR | `DispatchHospitalAutomationWorkflow` depends on it. |
| `HospitalAutomation/Services/ChatbotWorkflowService.php` | HTTP chat → engine | `Automation/Chatbot/` | MOVE | `ChatbotController` (outside module). |
| `HospitalAutomation/Services/ChatbotConversationService.php` | Session memory | `Automation/Chatbot/` | MOVE | ChatbotController + tests. |
| `HospitalAutomation/Executors/HospitalDomainTriggerExecutor.php` | Intended catalog trigger executor | — | DELETE ONLY AFTER VERIFICATION | **Fact:** `WorkflowServiceProvider` already registers passthrough for every catalog type first; this class never binds those types. Still constructed in HA provider. Tests may reference it. |
| `HospitalAutomation/Executors/AiPromptExecutor.php` | `aiPrompt` node | `Automation/NodeProcessors/Actions/` | MOVE | Registry in HA provider; seeder lab-AI graph. Not in the 13 campaigns. |
| `HospitalAutomation/Controllers/HospitalAutomationController.php` | Catalog, list, manual trigger | `Automation/Http/Controllers/` | KEEP URL `/api/hospital-automation/*` then MOVE class | Sanctum routes. |
| `HospitalAutomation/Routes/hospitalAutomation.php` | Those routes | `Automation/Http/Routes/automation.php` | KEEP paths; MOVE file | Prefix `hospital-automation`. |
| `HospitalAutomation/Jobs/RunAutomationTestJob.php` | Test runner job | `Automation/Jobs/` | MOVE | Console test command. |
| `HospitalAutomation/Console/Commands/RunAutomationTestCommand.php` | Artisan test | `Automation/Console/` | MOVE | Registered in HA provider. |
| `HospitalAutomation/Testing/*` | Test factories/runner | `Automation/Testing/` | MOVE | Used by feature tests. |

### 3.2 Workflow (builder + runtime)

| Existing file/path | Current responsibility | Proposed path | Action | Dependencies/risk |
|---|---|---|---|---|
| `Workflow/WorkflowServiceProvider.php` | Compiler + `NodeExecutorRegistry` singleton + builder routes | Merge into `AutomationServiceProvider` | MOVE | **Highest risk.** Registry order is why HA domain executor is dead. Tests boot this provider. |
| `Workflow/Controllers/WorkflowBuilderController.php` | React Flow CRUD/publish | `Automation/Builder/Controllers/` | KEEP routes then MOVE | `tests/Feature/WorkflowBuilderTest.php`, Livewire builders. **Do not change URI.** |
| `Workflow/Controllers/WorkflowTemplateController.php` | Template catalog/admin | `Automation/Builder/Controllers/` | KEEP then MOVE | Template tests + Livewire. |
| `Workflow/Routes/workflow.php` | `/api/workflows`, templates, message templates | `Automation/Builder/Routes/builder.php` | KEEP paths | Frontend builder. |
| `Workflow/Requests/*` | Validation | `Automation/Builder/Requests/` | MOVE | Controllers. |
| `Workflow/Resources/*` | API resources | `Automation/Builder/Resources/` | MOVE | Controllers. |
| `Workflow/Policies/WorkflowTemplatePolicy.php` | Gate | `Automation/Builder/Policies/` | MOVE | Registered in Workflow provider. |
| `Workflow/Services/Builder/*` | Builder/publish/catalog | `Automation/Builder/Services/` | MOVE | Controllers. |
| `Workflow/Models/{Workflow,WorkflowVersion,WorkflowExecution,WorkflowTemplate,WorkflowMessageTemplate,CommunicationLog}.php` | Persistence | `Automation/Persistence/Models/` | MOVE | Eloquent table names stay. Seeders, Livewire, repository, medicine bridge. **High risk** if table/class map breaks. |
| `Workflow/Repositories/WorkflowRepository.php` | Hospital-isolated lookup | `Automation/Persistence/Repositories/` | MOVE | Engine + HA controller. Isolation rule must stay. |
| `Workflow/Enums/*` | Status, node types | `Automation/Persistence/Enums/` | MOVE | Compiler, models, tests. |
| `Workflow/DTO/*` | Compiled graph | `Automation/Persistence/DTO/` | MOVE | Executor/compiler. |
| `Workflow/Contracts/*` | Compiler + node executor | `Automation/Contracts/` | MOVE | Provider bindings. |
| `Workflow/Support/NodeTypeNormalizer.php` | Frontend aliases | `Automation/Support/` | MOVE | Compiler, tests (`NodeTypeNormalizerTest`). |
| `Workflow/Services/Compiler/WorkflowCompiler.php` | JSON → graph | `Automation/Engine/` | MOVE | Bound as interface. |
| `Workflow/Services/Runtime/WorkflowExecutor.php` | Walk nodes | `Automation/Engine/` | MOVE | Engine, delay job, tests. |
| `Workflow/Services/Runtime/NodeExecutorRegistry.php` | Processor map | `Automation/Engine/NodeProcessorRegistry.php` | REFACTOR | Singleton. |
| `Workflow/Services/Runtime/{ConditionEngine,DelayScheduler,ChannelManager,TemplateManager,VariableResolver,ActionDispatcher,AiVoiceCallService}.php` | Runtime services | Engine or Messaging as listed in tree | MOVE | Messaging executors + tests. |
| `Workflow/Services/Runtime/WorkflowTriggerDispatcher.php` | Second start path (prescription) | Fold into `WorkflowAutomationEngine` | REPLACE LATER | `CreateMedicineReminderSchedules` / prescription path. Dual start is a known duplicate. |
| `Workflow/Services/Bridge/WorkflowExecutionBridge.php` | Medicine due → executor | `Automation/MedicineReminder/` or TriggerHandlers | REFACTOR | `MedicineReminderExecutionService`. |
| `Workflow/Services/Bridge/MedicineWorkflowBridge.php` | Links medicine workflows | `Automation/MedicineReminder/` | MOVE | Medicine module. |
| `Workflow/Executors/**` | Current node executors | `Automation/NodeProcessors/**` | REPLACE LATER | Rename interface; keep behavior. Needed by 13 campaigns: delay, condition, sendWhatsApp/SMS/push, end, trigger passthrough. |
| `Workflow/Jobs/ContinueWorkflowExecutionJob.php` | Resume after wait | `Automation/Jobs/` | MOVE | Serialized on queue — **job class FQCN change breaks in-flight jobs**. Migrate only with empty queue or job alias. |
| `Workflow/Events/*.php` (6) | Duplicate HA events | — | DELETE ONLY AFTER VERIFICATION | **Fact:** no `Event::listen` on these. `StartWorkflowOnPrescriptionAdded` imports some. Grep tests before delete. |
| `Workflow/Listeners/StartWorkflowOnPrescriptionAdded.php` | Unregistered duplicate prescription start | — | DELETE ONLY AFTER VERIFICATION | Class comment says do not dual-register. Still a loadable class. |

### 3.3 MedicineReminder (product)

| Existing file/path | Current responsibility | Proposed path | Action | Dependencies/risk |
|---|---|---|---|---|
| `MedicineReminder/MedicineReminderServiceProvider.php` | Binding, `PrescriptionCreated` listen, routes, command | Merge into Automation provider **or** nested provider | KEEP behavior; MOVE later | `bootstrap/providers.php`. |
| `MedicineReminder/Events/PrescriptionCreated.php` | Prescription create | `Automation/MedicineReminder/Events/` or `Automation/Events/` | KEEP then MOVE | `PrescriptionService` (app-level). |
| `MedicineReminder/Events/MedicineReminderTriggered.php` | Fired with **no listener** | Keep until listener or confirmed unused | KEEP | `MedicineReminderExecutionService`. |
| `MedicineReminder/Listeners/CreateMedicineReminderSchedules.php` | Builds schedule rows + may start `prescriptionAdded` workflows | `Automation/MedicineReminder/Listeners/` | KEEP then MOVE | Provider registration. |
| `MedicineReminder/Models/*` | `MedicineWorkflow`, schedules, logs | `Automation/MedicineReminder/Models/` | MOVE | Separate tables from `workflows`. Do not merge into Workflow model. |
| `MedicineReminder/Jobs/SendMedicineReminderJob.php` | Due send | `Automation/Jobs/` or nested | MOVE | Queue FQCN risk (same as delay job). |
| `MedicineReminder/Console/DispatchMedicineRemindersCommand.php` | Cron | `Automation/Console/` | MOVE | `routes/console.php` command name `medicine-reminders:dispatch` must stay. |
| `MedicineReminder/Notifications/*` | WhatsApp/SMS/email/push adapters | `Automation/MedicineReminder/Notifications/` or Messaging | KEEP then MOVE | `NotificationDispatcher`. Distinct from `ChannelManager` — **do not delete** as “duplicate” without proving same providers/payloads. |
| `MedicineReminder/Services/*` | Scheduler, execution, variables | Nested Services | KEEP then MOVE | Execution → Workflow bridge. |
| `MedicineReminder/Controllers` + `Routes/medicineReminder.php` | `/api/medicine-workflows`, `/api/medicine-reminders` | Nested Http | KEEP URLs | Frontend medicine UI. |
| Remaining Requests, Resources, Enums, Interfaces, Repositories | CRUD/API | Nested same layout | MOVE | Controller/repository binding. |

### 3.4 Application-level (not inside the three modules)

| Existing file/path | Current responsibility | Proposed path | Action | Dependencies/risk |
|---|---|---|---|---|
| `app/Services/DoctorBookingStatusService.php` | Dispatches cancelled/completed | Stay in `app/Services` | KEEP | Add missed dispatch later (Step 2); do not move into Automation. |
| `app/Services/PrescriptionService.php` | Dispatches `PrescriptionCreated` | Stay | KEEP | |
| `app/Http/Controllers/Api/ChatbotController.php` | Chat HTTP | Stay | KEEP | Imports HA chatbot services. |
| `app/Models/DoctorBooking.php` | Observed model | Stay | KEEP | Observer registration by class name. |
| `app/Models/Persons.php` | `PatientRegistered` / birthday payload | Stay | KEEP | |
| `app/Livewire/Admin/Automation/**` | Admin UI | Stay | KEEP | Imports Workflow models/controllers. Namespace move of models **breaks Livewire** until updated. |
| `routes/api.php` chat routes | `/api/chat/completions` | Stay | KEEP | |
| `routes/web.php` automation Livewire | Admin pages | Stay | KEEP | |
| `routes/console.php` | `medicine-reminders:dispatch` | Stay | KEEP | |
| `bootstrap/providers.php` | Three module providers | Add Automation; remove old after cutover | KEEP until cutover | Boot failure if wrong. |
| `database/seeders/HospitalAutomation*.php` | Sample graphs/templates | Stay | KEEP | Import Workflow models. |
| `tests/Feature/**`, `tests/Unit/**`, `tests/Support/**` | Coverage | Update imports after MOVE | KEEP tests; REFACTOR imports | Many FQCNs. |

---

## 4. Dependency and deletion-risk analysis

**Do not delete anything in the first implementation PR.**

| Risk | Evidence | If moved/deleted carelessly |
|---|---|---|
| Builder API | `Workflow/Routes/workflow.php` loaded with `api` prefix | React Flow save/publish fails |
| Hospital isolation | `WorkflowRepository::findPublishedByTrigger` | Wrong hospital runs a graph |
| Booked campaigns | Observer + `AppointmentBookedListener` + engine | Four `appointmentBooked` workflows never start |
| Queue class names | `ContinueWorkflowExecutionJob`, `SendMedicineReminderJob` | In-flight jobs unserialize to missing class |
| Dual engine | `AutomationEngine` and `WorkflowTriggerDispatcher` | Prescription vs appointment diverge further if only one is moved |
| Medicine product | Separate `MedicineWorkflow` model + routes | Dose CRUD breaks even if campaign WhatsApp works |
| Chat | Controller outside module | Namespace move of chatbot services without controller update |
| Livewire | `app/Livewire/Admin/Automation/*` | Admin pages 500 |
| Duplicate Workflow events | Unused on bus | Safe only after grep of string FQCNs, tests, and serialized payloads |
| `HospitalDomainTriggerExecutor` | Skipped by registry | Removing is low functional risk, still verify tests |
| Notification dual stack | `ChannelManager` vs MedicineReminder `*NotificationService` | Deleting one stack can silence medicine or workflow messages |
| Docs vs code | `docs/workflow/HOSPITAL_AUTOMATION.md` mentions `BookingApiService` for `AppointmentBooked` | **Fact:** current dispatch is `DoctorBookingObserver`, not that service |

**Cross-module imports (Fact):** Workflow provider imports `TriggerCatalog`. Medicine execution imports `WorkflowExecutionBridge`. ChatbotController imports HA chatbot services. PrescriptionService imports MedicineReminder event. Status service imports HA events. Livewire imports Workflow models.

**String / config references:** `NodeTypeNormalizer` aliases, `TriggerCatalog::types()`, artisan command names, queue job names, route names (`workflow-templates.catalog`, etc.).

---

## 5. Workflow / trigger / node requirements

Source: `docs/automation/automation-event-inventory.md`, `docs/automation/frontend-json.md`. **13 of 15** manager workflows verified. **2 unidentified.**

### Confirmed product workflows (13)

First Appointment Nurturing, Post-Visit Follow-up, Missed Appointment Restart, Digital Prescription Share, Medicine Reminder, Birthday Wish, Women's Day Wish, Inactive 30, Inactive 90, Dentist Segment, Senior Segment, Parent–Child Segment, Segment Variants.

### Distinct trigger types (9)

`patientRegistered`, `appointmentBooked`, `appointmentCompleted`, `appointmentMissed`, `prescriptionAdded`, `medicineReminder` → `medicineReminderDue`, `birthday`, `anniversary` → `anniversaryReached`, `scheduledEvent`.

| Category | Triggers in the 13 |
|---|---|
| Database / domain | `patientRegistered`, `appointmentBooked`, `appointmentCompleted`, `appointmentMissed`, `prescriptionAdded` |
| Scheduled | `medicineReminderDue`, `birthday`, `anniversaryReached`, `scheduledEvent` |
| External inbound WhatsApp/SMS | **None** |
| External chat | **Not in the 13** (Laravel `POST /api/chat/completions` exists separately) |

### Distinct node types in the 13 (processors needed)

| JSON nodeType | Processor (proposed) | Existing class to reuse |
|---|---|---|
| Trigger nodes (9 keys above) | TriggerHandlers + passthrough processors | `PassthroughTriggerExecutor` + specialized trigger executors |
| `wait` | `WaitNodeProcessor` | `DelayExecutor` (duration only; `relative_date` missing) |
| `condition` | `IfConditionNodeProcessor` | `ConditionExecutor` (`expression` missing) |
| `sendWhatsApp` | `SendWhatsAppNodeProcessor` | `SendWhatsAppExecutor` |
| `sendSms` | `SendSMSNodeProcessor` | `SendSMSExecutor` |
| `sendPush` | `SendPushNodeProcessor` | `SendPushExecutor` |
| `end` | `EndNodeProcessor` | `EndExecutor` |

Seeder/catalog extras (`sendEmail`, `aiPrompt`, `createRecord`, `sendAiChat`, …) stay as MOVE of existing executors; they are **not** required to run the 13.

**Missing files:** manager workflows 14 and 15. **Unresolved:** patient model/hospital at registration; missed status enum; whether inbound WhatsApp is ever in scope.

---

## 6. Recommended migration order

Do **not** create `Modules/Automation` or move files until Step 2 works on **current** namespaces. Folder moves are the highest-breakage change.

1. **Freeze** the 13 workflows (or receive 14–15). Keep builder API frozen.
2. **Step 2 on current tree:** dispatch/observers/listeners under `HospitalAutomation` + `MedicineReminder` as they exist. Add commands for birthday/anniversary/scheduledEvent. No folder rename.
3. **Step 3 on current tree:** TriggerHandlers calling existing `AutomationEngine::handle`. Optionally rename class internally with alias `WorkflowAutomationEngine`.
4. **NodeProcessors:** extend condition/wait/recipients in place (`Workflow/Executors`).
5. **Create `Automation` module** as a thin service provider that **re-exports** existing classes (aliases), without moving files.
6. **MOVE** files in vertical slices: Persistence+Builder (with route tests), then Engine, then Events, then MedicineReminder, then delete empty old dirs.
7. **DELETE ONLY AFTER VERIFICATION:** `Workflow/Events/*`, unregistered prescription listener, unused domain trigger executor.
8. Point `bootstrap/providers.php` at `AutomationServiceProvider` only after aliases and tests pass.

Never: delete `Modules/Workflow` in the same commit as engine rewrite; never stand up a second executor registry.

---

## 7. Tests and checks after any future migration

- `php artisan route:list` — `/api/workflows`, `/api/hospital-automation/*`, `/api/medicine-workflows`, `/api/chat/completions` unchanged.
- `tests/Feature/WorkflowBuilderTest.php`, `WorkflowTemplateTest.php`, `WorkflowMessageTemplateApiTest.php`
- `tests/Feature/AppointmentBookedAutomationTest.php`
- `tests/Feature/ChatbotWorkflowCompletionsTest.php`
- `tests/Feature/Automation/*`, unit executor/delay/normalizer tests
- Confirm `DoctorBookingObserver` still registered
- Confirm `medicine-reminders:dispatch` still scheduled
- Empty or drain queues before renaming job classes
- Livewire admin: open workflows index/builder
- Hospital isolation: workflow for hospital 12 does not run for another hospital

---

## 8. Questions before implementation

1. Confirm **13 workflows** (not 15) as the move-scope, or supply 14–15.
2. Confirm **do not** move files until Step 2 events work in the current modules.
3. Confirm builder URLs stay `/api/workflows` even after class namespace becomes `Automation\Builder`.
4. Confirm Medicine Reminder stays a **nested folder**, not a separate Composer package.
5. Confirm Livewire `App\Livewire\Admin\Automation` stays outside `Modules/Automation`.
6. Queue: is a maintenance window available to rename job FQCNs?
7. Inbound WhatsApp/SMS: out of folder scope until a webhook exists?

---

## 9. What this task did not do

- No `app/Modules/Automation` directory was created.
- No existing Events, Listeners, Observers, Services, Providers, Jobs, or routes were modified.
- No workflow JSON, storage, or frontend builder API was changed.

**Next safe implementation step (not this task):** implement manager **Step 2** (missing event **dispatch** sites and observers) **inside the current `HospitalAutomation` / `MedicineReminder` namespaces**. Do not restructure folders first.
