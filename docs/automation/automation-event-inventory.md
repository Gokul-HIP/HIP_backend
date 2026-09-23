# Step 1 — Automation Event Inventory

**Status:** analysis only. No PHP, JavaScript, migrations, routes, services, observers, listeners, or providers were modified.

**Date:** 2026-09-22  
**Manager requirement:** Manually list all events that can trigger automation workflows.

**Legend**

| Marker | Meaning |
|---|---|
| **Fact** | Observed in inspected JSON or PHP |
| **Missing** | Required for a verified workflow, not wired in Laravel |
| **Unknown** | Manager decision needed; not invented here |

This inventory does **not** claim all 15 manager workflows were reviewed.

---

## 1. Scope of what was found

| Source | Count | Role |
|---|---|---|
| Named campaign graphs in `docs/automation/frontend-json.md` (not titled `Fixture —`) | **13** | Closest match to the manager’s product set. JSON inspected. |
| Manager workflows 14 and 15 | **0 found** | Names and files unidentified. |
| `Fixture — *` graphs in the same document | **46** | Catalog/test fixtures. Many set `workflow.trigger` to `appointmentBooked` while the first node is another catalog type. |
| Laravel seeder graphs | **9** | `database/seeders/data/hospital_automation_workflows.php` |
| Docs sample | **1** | `docs/workflow/samples/appointment-booked-confirmation.json` (same shape as seeder “Appointment Booked Confirmation”) |
| `*.workflow.json` inside `HIP_backend` | **0** | Physical JSON files are not in this repo. Canonical text is in `frontend-json.md`. Original filenames: `automation/node-workflows/<file>`. |

**Fact.** No repository document lists 15 named product workflows.

---

## 2. Manager’s 15 vs verified 13

| # | Workflow | Verified? |
|---|---|---|
| 1 | First Appointment Nurturing | Yes |
| 2 | Post-Visit Follow-up | Yes |
| 3 | Missed Appointment Restart | Yes |
| 4 | Digital Prescription Share | Yes |
| 5 | Medicine Reminder | Yes |
| 6 | Birthday Wish | Yes |
| 7 | Women's Day Wish | Yes |
| 8 | Inactive Patient 30 Days | Yes |
| 9 | Inactive Patient 90 Days | Yes |
| 10 | Dentist Segment Campaign | Yes |
| 11 | Senior Patient Segment Campaign | Yes |
| 12 | Parent–Child Segment Campaign | Yes |
| 13 | Segment Variants Example | Yes |
| 14 | *unidentified* | **No — missing name and file** |
| 15 | *unidentified* | **No — missing name and file** |

Do **not** fill 14–15 with fixtures, seeders, chatbot, or inbound WhatsApp/SMS. Those exist as other artifacts; they are not named as the missing two.

---

## 3. Workflow table (one row per workflow)

### 3.A Verified product campaigns (13)

Trigger JSON is in `docs/automation/frontend-json.md` under the workflow heading. Laravel wiring is cited where it exists.

| Workflow | Trigger type/key | Category | Trigger source (verified) | Model / provider and condition | Evidence |
|---|---|---|---|---|---|
| First Appointment Nurturing | `patientRegistered` | Database / domain | Intended: patient record **created**. **Not dispatched today.** | Event class constructor uses `App\Models\Persons`. No observer on `Persons`. Listener registered. | JSON: `frontend-json.md` → `firstAppointmentNurturing.workflow.json`. Event: `app/Modules/HospitalAutomation/Events/PatientRegistered.php`. Provider: `HospitalAutomationServiceProvider::registerEvents()`. |
| Post-Visit Follow-up | `appointmentCompleted` | Database / domain | `DoctorBooking` status → completed | `DoctorBookingStatusService` dispatches `AppointmentCompleted`. Not observer `updated` for this event. JSON also has `requireFollowUp: yes`. | JSON: `postVisitFollowup.workflow.json`. Dispatch: `app/Services/DoctorBookingStatusService.php`. Event: `Events/AppointmentCompleted.php`. |
| Missed Appointment Restart | `appointmentMissed` | Database / domain | Intended: booking status → missed/no-show. **Dispatch missing.** | Event holds `DoctorBooking`. Listener registered. Exact status value **Unknown**. | JSON: `missedAppointmentRestart.workflow.json`. Event: `Events/AppointmentMissed.php`. No `AppointmentMissed::dispatch` in `app/`. |
| Digital Prescription Share | `prescriptionAdded` | Database / domain | Prescription **created** | `PrescriptionService` fires `MedicineReminder\Events\PrescriptionCreated`. | JSON: `digitalPrescriptionCampaign.workflow.json`. `app/Services/PrescriptionService.php`. Listener: `CreateMedicineReminderSchedules`. |
| Medicine Reminder | `medicineReminder` (canonical `medicineReminderDue`) | Scheduled | Cron due-time dispatch, not a model create | `medicine-reminders:dispatch` → `SendMedicineReminderJob` → `WorkflowExecutionBridge`. JSON: `reminderTiming: at_due`, `minutesBefore: 30`. | JSON: `medicineReminderCampaign.workflow.json`. `DispatchMedicineRemindersCommand`, `MedicineReminderExecutionService`. |
| Birthday Wish | `birthday` | Scheduled | Intended: daily clock + DOB. **No scheduler found.** | Event holds `Persons`. Listener registered. JSON: `on_birthday`, `daysBefore: 1`, `executionTime: 09:00`. | JSON: `birthdayWish.workflow.json`. Event: `Events/BirthdayReached.php`. |
| Women's Day Wish | `anniversary` (canonical `anniversaryReached`) | Scheduled | Intended: calendar occasion. **No scheduler found.** | Event holds `Persons` only (no `anniversaryType` on payload). JSON: `anniversaryType: womens_day`, `on_date`, `09:00`. | JSON: `womensDayWish.workflow.json`. Event: `Events/AnniversaryReached.php`. |
| Inactive Patient 30 Days | `scheduledEvent` | Scheduled | Intended: recurring daily 10:00. **No dispatcher found.** | No `ScheduledEvent` event class. JSON: `scheduleType: recurring`, `repeatFrequency: daily`. Condition uses `last_visit >= 30`. | JSON: `inactivePatient30.workflow.json`. Executor only: `ScheduledEventTriggerNodeProcessor`. |
| Inactive Patient 90 Days | `scheduledEvent` | Scheduled | Recurring daily 10:30. **No dispatcher found.** | Same as 30-day; condition `last_visit >= 90`. | JSON: `inactivePatient90.workflow.json`. |
| Dentist Segment Campaign | `appointmentBooked` | Database / domain | `DoctorBooking` **created** if already confirmed, or **updated** when status becomes confirmed | `DoctorBookingObserver`. JSON condition: `appointment.department == "Dentistry"`. | JSON: `dentistSegment.workflow.json`. Observer: `Observers/DoctorBookingObserver.php`. Event: `Events/AppointmentBooked.php`. Listener: `AppointmentBookedListener`. |
| Senior Patient Segment Campaign | `appointmentBooked` | Database / domain | Same observer as dentist | Condition: `patient.age >= 60`. | JSON: `seniorPatientSegment.workflow.json`. Same observer/event. |
| Parent–Child Segment Campaign | `appointmentBooked` | Database / domain | Same observer | Condition: `patient.age < 18 \|\| patient.relationship == "child"`. Recipient `caregiver`. | JSON: `parentChildSegment.workflow.json`. Same observer/event. |
| Segment Variants Example | `appointmentBooked` | Database / domain | Same observer | Sequential dentist → senior → child conditions. | JSON: `segmentDentistSeniorParentChild.workflow.json`. Same observer/event. |

`suppressOnAppointment` on First Appointment Nurturing is **not** a start trigger. It is campaign-cancel metadata. Laravel does not read it today.

Appointment **deleted / soft-deleted / restored** do not start any of these 13 graphs. Observer has no `deleted` / `trashed` methods.

### 3.B Laravel seeder workflows (9) — samples, not the manager 15

Inspected: `database/seeders/data/hospital_automation_workflows.php`.

| Workflow | Trigger type/key | Category | Trigger source (verified) | Model / condition | Evidence |
|---|---|---|---|---|---|
| Appointment Booked Confirmation | `appointmentBooked` | Database / domain | Same as §3.A booked | `DoctorBooking` confirmed | Seeder + `docs/workflow/samples/appointment-booked-confirmation.json` |
| Appointment Tomorrow Reminder | `appointmentBooked` | Database / domain | Same observer; delay 24h is a **node**, not a separate event | Confirmed booking | Seeder |
| Lab Report Ready Notification | `labReportReady` | Database / domain (intended) | **Event class + listener exist; no production dispatch found** | — | Seeder; `Events/LabReportReady.php` |
| Medicine Refill Due Reminder | `medicineRefillDue` | Scheduled / domain (intended) | **No production dispatch found** | — | Seeder; `Events/MedicineRefillDue.php` |
| Invoice Generated + Payment Reminder | `invoiceGenerated` | Database / domain (intended) | **No observer on Invoice found** | — | Seeder; `Events/InvoiceGenerated.php` |
| Birthday Engagement | `birthday` | Scheduled | Same gap as Birthday Wish | `Persons` | Seeder |
| Appointment Feedback Flow | `appointmentCompleted` | Database / domain | Same as Post-Visit (`DoctorBookingStatusService`) | Completed booking | Seeder |
| Lab Report AI Summary | `labReportReady` | Database / domain (intended) | Same unwired lab event | — | Seeder |
| Diabetic Patient Campaign | `campaignTriggered` | Other (manual / API) | Manual `POST` hospital-automation trigger if used; **no cohort job found** | Generic context array | Seeder; `Events/CampaignTriggered.php` |

### 3.C Catalog fixtures (46) — not product start events

Inspected via `docs/automation/frontend-json.md` index (rows 1–59). Each `Fixture — *` file is a node demo. **Do not treat `workflow.trigger: appointmentBooked` on a fixture as proof that product automation starts on that type**, when the focus node is a different catalog trigger.

Fixture focus-node types that appear as the first graph node include: `ai`, `anniversary`, `apiEvent`, `appointmentBooked`, `appointmentCancelled`, `appointmentCompleted`, `appointmentMissed`, `appointmentReminder`, `appointmentRescheduled`, `birthday`, `condition`, `dbCreate`, `dbDelete`, `dbQuery`, `dbUpdate`, `end`, `familyPackageTierUpdated`, `httpRequest`, `invoiceGenerated`, `labReportNotification`, `labTestOrdered`, `medicineReminder`, `membershipExpiry`, `onChatMessage`, `patientRegistered`, `paymentReceived`, `pharmacyRefillDue`, `prescriptionAdded`, `rewardUpdated`, `rewardsTierUpgraded`, `scheduledEvent`, `sendAiChat`, `sendAiVoice`, `sendEmail`, `sendIvr`, `sendPush`, `sendSms`, `sendTemplate`, `sendWhatsApp`, `start`, `updateAppointment`, `updateMembership`, `updatePrescription`, `userPlanExpiry`, `wait`, `webhookEvent`.

Those are catalog coverage, not the manager’s 15.

### 3.D Laravel chatbot (not one of the 13)

| Workflow | Trigger | Category | Source | Evidence |
|---|---|---|---|---|
| Published `messageReceived` / `onChatMessage` workflow in DB (if any) | `messageReceived` | External HTTP (not WhatsApp/SMS webhook) | `POST /api/chat/completions` → `ChatbotController` → `ChatbotWorkflowService` (sync). Does **not** dispatch `MessageReceived`. | `routes/api.php`; `ChatbotController.php`; `Events/MessageReceived.php` registered but unused by this path. Fixture: `onChatMessage.workflow.json`. |

---

## 4. Unique event types (verified 13 + how they enter Laravel)

Shared triggers are listed once.

| Unique trigger key | Canonical ID | Category | Workflows (verified 13) | Existing Laravel event class | Actually dispatched today? |
|---|---|---|---|---|---|
| `patientRegistered` | `patientRegistered` | Database / domain | First Appointment Nurturing | `PatientRegistered` | **No** |
| `appointmentBooked` | `appointmentBooked` | Database / domain | Dentist, Senior, Parent–Child, Segment variants (**4 workflows share this trigger**) | `AppointmentBooked` | **Yes** — `DoctorBookingObserver` on create/update-to-confirmed |
| `appointmentCompleted` | `appointmentCompleted` | Database / domain | Post-Visit Follow-up | `AppointmentCompleted` | **Yes** — `DoctorBookingStatusService` |
| `appointmentMissed` | `appointmentMissed` | Database / domain | Missed Appointment Restart | `AppointmentMissed` | **No** |
| `prescriptionAdded` | `prescriptionAdded` | Database / domain | Digital Prescription Share | `PrescriptionCreated` (MedicineReminder module) | **Yes** — `PrescriptionService` |
| `medicineReminder` | `medicineReminderDue` | Scheduled | Medicine Reminder | `MedicineReminderTriggered` (no listener); runtime uses `WorkflowExecutionBridge` | **Yes** via cron/job, not via HA event bus |
| `birthday` | `birthday` | Scheduled | Birthday Wish | `BirthdayReached` | **No** |
| `anniversary` | `anniversaryReached` | Scheduled | Women's Day Wish | `AnniversaryReached` | **No** |
| `scheduledEvent` | `scheduledEvent` | Scheduled | Inactive 30, Inactive 90 (**2 workflows share this trigger**) | **No event class** | **No** |

**Count of unique trigger types for the verified 13: 9.**

Seeder-only extra types (not in the 13): `labReportReady`, `medicineRefillDue`, `invoiceGenerated`, `campaignTriggered`. Catalog also lists many types in `TriggerCatalog` that have **no verified product workflow**.

### Shared `appointmentBooked`

**Fact.** Four verified campaigns and two seeder graphs all start on `appointmentBooked`. Laravel already looks up **all** published workflows for that trigger and hospital (`AutomationEngine` / repository). Conditions inside each graph (department, age, relationship) distinguish segments. Duplicate-start logic today keys on `appointment_id`, not `campaignKey` — **Unknown** whether four graphs should all run on one booking.

---

## 5. Potential inbound events (manager examples)

These names are **not** present as PHP classes. They are evaluated only against actual HTTP/provider code.

| Proposed name | Required by verified 13? | Webhook / provider / HTTP exists? | Implemented as Laravel event? | Verdict |
|---|---|---|---|---|
| `WhatsAppIncomingEvent` | **No.** Product graphs only **send** WhatsApp. | **No** inbound WhatsApp webhook or provider callback found under `app/` or `routes/`. | **No** class with this name. | Not required by inspected product JSON. Not implemented. |
| `SMSIncomingEvent` | **No.** Product graphs only **send** SMS. | **No** inbound SMS webhook found. | **No**. | Not required. Not implemented. |
| `ChatMessageIncomingEvent` | **No** (not in the 13). | **Yes, different path:** `POST /api/chat/completions` (`auth:sanctum`) → `ChatbotController`. This is app chat, not a WhatsApp/SMS provider webhook. | Class is `MessageReceived`, not `ChatMessageIncomingEvent`. Controller does **not** dispatch it. | Chat HTTP exists. Named event `ChatMessageIncomingEvent` does **not**. Do not treat documentation of `messageReceived` as inbound WhatsApp. |

Outbound WhatsApp/SMS (`SendWhatsAppNodeProcessor`, MedicineReminder notification services) is **not** inbound.

Fixture `webhookEvent.workflow.json` shows a sample URL `https://api.healthinpocket.in/api/webhooks/{workflow_id}`. **Fact:** no matching inbound webhook controller was found in this repository. That fixture does not implement a webhook.

---

## 6. Events that exist in Laravel but are not start events for the verified 13

Listener-registered in `HospitalAutomationServiceProvider`, generally **undispatched** unless noted:

| Event class | Trigger ID | Dispatched? | In verified 13? |
|---|---|---|---|
| `AppointmentCancelled` | `appointmentCancelled` | Yes — status service | No |
| `AppointmentRescheduled` | `appointmentRescheduled` | No | No |
| `LabTestOrdered`, `LabReportReady`, `LabCompleted` | lab\* | No | No |
| `MedicineRefillDue` | `medicineRefillDue` | No | No |
| `InvoiceGenerated`, `PaymentReceived`, `PaymentPending` | billing | No (`reminders:pending-payments` is a separate command, not `PaymentPending`) | No |
| `MembershipExpiry`, `MembershipRenewed`, `RewardPointsUpdated`, `RewardTierUpgraded` | membership | No | No |
| `ProcedureCompleted` | `procedureCompleted` | No | No |
| `MessageReceived` | `messageReceived` | No (chat uses HTTP) | No |
| `CampaignTriggered` | `campaignTriggered` | Manual API only if called | No |

`TriggerCatalog` also includes `appointmentReminder`, `userPlanExpiry`, `familyPackageTierUpdated`, `webhookEvent`, `apiEvent` **without** matching event classes in `HospitalAutomation/Events/`.

---

## 7. Missing information

- Names/files of manager workflows 14 and 15.
- `DoctorBooking` status value that means missed/no-show.
- Patient-registration `hospital_id` source (`PatientRegistered` takes `Persons` + optional `organizationId` only).
- Follow-up date field for `appointmentCompleted` / relative waits (node concern; event still starts on completed).
- How `last_visit` is computed for `scheduledEvent`.
- Whether all four `appointmentBooked` campaigns should run on every confirmed booking.
- Whether inbound WhatsApp/SMS is in scope despite no JSON and no webhook.

---

## 8. Questions for the manager

1. Confirm the product set is these **13** campaigns, or supply files for **14 and 15**.
2. Confirm inbound `WhatsAppIncomingEvent` / `SMSIncomingEvent` are **out of scope** until a provider webhook exists.
3. Confirm chatbot stays on `POST /api/chat/completions` and is **not** one of the 15.
4. Confirm appointment **delete / soft-delete** must **not** start workflows.
5. Which `DoctorBooking` status is **missed**?
6. Is `Persons` the correct patient-registered model?
7. Should one confirmed booking start **all four** `appointmentBooked` segment workflows?

---

## 9. Final counts

| Item | Count |
|---|---|
| Manager workflows claimed | 15 |
| Verified and JSON-inspected product workflows | **13** |
| Unidentified manager workflows | **2** |
| Unique trigger types among the 13 | **9** |
| Of those 9, dispatched in production Laravel today | **4** (`appointmentBooked`, `appointmentCompleted`, `prescriptionAdded` via `PrescriptionCreated`, `medicineReminderDue` via cron) |
| Shared `appointmentBooked` product workflows | **4** |
| Shared `scheduledEvent` product workflows | **2** |
| Inbound WhatsApp/SMS events implemented | **0** |
| Catalog fixtures documented | **46** |
| Laravel seeder graphs | **9** |

---

## 10. Files inspected (this task)

**Documentation**

- `docs/automation/automation-rebuild-analysis.md`
- `docs/automation/automation-rebuild-blueprint.md`
- `docs/automation/frontend-json.md` (59-graph index + product JSON bodies)
- `docs/workflow/HOSPITAL_AUTOMATION.md` (note: `BookingApiService` → `AppointmentBooked` is **not** confirmed in current PHP; observer is)
- `docs/workflow/samples/appointment-booked-confirmation.json`

**Workflow definitions**

- `database/seeders/data/hospital_automation_workflows.php`
- `database/seeders/HospitalAutomationWorkflowSeeder.php`

**Laravel wiring (read/grep only)**

- `app/Modules/HospitalAutomation/Support/TriggerCatalog.php`
- `app/Modules/HospitalAutomation/HospitalAutomationServiceProvider.php`
- `app/Modules/HospitalAutomation/Observers/DoctorBookingObserver.php`
- `app/Modules/HospitalAutomation/Events/{PatientRegistered,AppointmentBooked,AppointmentMissed,AppointmentCompleted,BirthdayReached,AnniversaryReached,MessageReceived}.php`
- `app/Services/DoctorBookingStatusService.php`
- `app/Services/PrescriptionService.php`
- `app/Http/Controllers/Api/ChatbotController.php`
- `routes/api.php`

**Created**

- `docs/automation/automation-event-inventory.md`
