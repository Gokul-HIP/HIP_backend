# Hospital Automation — Node Test Coverage Matrix

Generated from `docs/automation/backend-node-contracts.json` and live
`NodeExecutorRegistry` / `NodeTypeNormalizer` inspection.

**Legend**
- **Status**: contract status (`implemented` | `partial` | `not_implemented`)
- **Graph tested**: FE ID → Normalizer → Compiler → Registry → Executor → End (or documented skip)
- **Notes**: runtime reality (passthrough ≠ domain-complete)

| FE Node | Canonical | Executor | Status | Graph tested | Notes |
|---|---|---|---|---|---|
| start | — | none | partial | SKIP | FE-only entry; stripped before Laravel persistence |
| onChatMessage | messageReceived | PassthroughTriggerExecutor | partial | YES (passthrough) | No domain observer/dispatch |
| patientRegistered | patientRegistered | PassthroughTriggerExecutor | partial | YES (passthrough) | |
| appointmentBooked | appointmentBooked | AppointmentBookedTriggerExecutor | implemented | YES | Full observer/engine suite in AppointmentBookedAutomationTest |
| appointmentRescheduled | appointmentRescheduled | PassthroughTriggerExecutor | partial | YES (passthrough) | |
| appointmentCompleted | appointmentCompleted | PassthroughTriggerExecutor | partial | YES (passthrough) | Domain via DoctorBookingStatusService |
| appointmentCancelled | appointmentCancelled | PassthroughTriggerExecutor | partial | YES (passthrough) | Domain via DoctorBookingStatusService |
| appointmentMissed | appointmentMissed | PassthroughTriggerExecutor | partial | YES (passthrough) | |
| prescriptionAdded | prescriptionAdded | PrescriptionAddedTriggerExecutor | implemented | YES | |
| medicineReminder | medicineReminderDue | MedicineReminderDueTriggerExecutor | implemented | YES* | *Requires schedule_id for full path; registry+normalize covered |
| labTestOrdered | labTestOrdered | PassthroughTriggerExecutor | partial | YES (passthrough) | |
| labReportNotification | labReportReady | PassthroughTriggerExecutor | partial | YES (passthrough) | |
| pharmacyRefillDue | medicineRefillDue | PassthroughTriggerExecutor | partial | YES (passthrough) | |
| appointmentReminder | appointmentReminder | PassthroughTriggerExecutor | not_implemented | SKIP | Domain incomplete |
| birthday | birthday | BirthdayTriggerExecutor | partial | YES | |
| anniversary | anniversaryReached | PassthroughTriggerExecutor | partial | YES (passthrough) | |
| membershipExpiry | membershipExpiry | PassthroughTriggerExecutor | partial | YES (passthrough) | |
| userPlanExpiry | userPlanExpiry | PassthroughTriggerExecutor | not_implemented | SKIP | |
| rewardUpdated | rewardPointsUpdated | PassthroughTriggerExecutor | partial | YES (passthrough) | |
| rewardsTierUpgraded | rewardTierUpgraded | PassthroughTriggerExecutor | partial | YES (passthrough) | |
| familyPackageTierUpdated | familyPackageTierUpdated | PassthroughTriggerExecutor | not_implemented | SKIP | |
| invoiceGenerated | invoiceGenerated | PassthroughTriggerExecutor | partial | YES (passthrough) | |
| paymentReceived | paymentReceived | PassthroughTriggerExecutor | partial | YES (passthrough) | |
| webhookEvent | webhookEvent | PassthroughTriggerExecutor | not_implemented | SKIP | |
| apiEvent | apiEvent | PassthroughTriggerExecutor | not_implemented | SKIP | |
| scheduledEvent | scheduledEvent | ScheduledEventTriggerExecutor | partial | YES | |
| condition | condition | ConditionExecutor | implemented | YES | True/false branches |
| wait | delay | DelayExecutor | implemented | YES | Queue::fake + manual resume |
| sendWhatsApp | sendWhatsApp | SendWhatsAppExecutor | implemented | YES | Provider mocked |
| sendSms | sendSMS | SendSMSExecutor | implemented | YES | Provider mocked |
| sendEmail | sendEmail | SendEmailExecutor | implemented | YES | Provider mocked |
| sendPush | sendPush | SendPushExecutor | implemented | YES | Provider mocked |
| sendAiChat | — | none | not_implemented | SKIP | No executor |
| sendAiVoice | — | none | not_implemented | SKIP | No executor |
| sendIvr | — | none | not_implemented | SKIP | No executor |
| sendTemplate | sendTemplate | SendTemplateExecutor | implemented | YES | Provider mocked |
| dbCreate | createRecord | CreateRecordExecutor | implemented | YES | Test DB table |
| dbUpdate | databaseUpdate | UpdateRecordExecutor | implemented | YES | Test DB table |
| dbDelete | dbDelete | DbDeleteExecutor | implemented | YES | Allowlist + hospital isolation |
| updateAppointment | — | none | not_implemented | SKIP | |
| updatePrescription | — | none | not_implemented | SKIP | |
| updateMembership | — | none | not_implemented | SKIP | |
| dbQuery | dbQuery | none | not_implemented | SKIP | |
| httpRequest | httpRequest | none | not_implemented | SKIP | `webhook` executor is backend-only, different FE ID |
| ai | aiPrompt | AiPromptExecutor | implemented | YES | Http::fake |
| end | end | EndExecutor | implemented | YES | |

## Critical suites (already present + extended)

| Suite | Purpose |
|---|---|
| `AppointmentBookedAutomationTest` | Observer rules, hospital isolation, duplicates, payment vs automation notification metadata |
| `WorkflowDelayResumeTest` | wait→delay, resume, no re-entry loop |
| `SendTemplateExecutorTest` | Template channel/variables/failures |
| `DbDeleteExecutorTest` | Allowlist + isolation |
| `NodeTypeNormalizerTest` | Aliases + compiler |
| `WorkflowAutomationCoverageTest` | Full FE matrix: normalize, registry, graph, skips |
| `WorkflowMessagingGraphTest` | All messaging channels via real executor path |
| `WorkflowDatabaseGraphTest` | dbCreate/dbUpdate/dbDelete graphs |
| `WorkflowConditionGraphTest` | True/false branching |
| `WorkflowAiGraphTest` | ai→aiPrompt with Http::fake |

## Test constraints

- No cron jobs
- No `queue:work` required (`Queue::fake()` + synchronous `WorkflowExecutor` / `AutomationEngine::handle`; `phpunit.xml` sets `QUEUE_CONNECTION=sync`)
- No real SMS / WhatsApp / Email / Push / FCM / HTTP
- Production code not changed to make tests pass

## How to run

```bash
# New matrix suite only
php artisan test tests/Feature/Automation

# Focused automation suite (includes AppointmentBooked + key unit suites)
php artisan test --testsuite=Automation

# Prefer process isolation when mixing RefreshDatabase migrateFreshUsing paths:
php artisan test tests/Feature/Automation
php artisan test --filter=AppointmentBookedAutomationTest
php artisan test --filter=SendTemplateExecutorTest
```

**Note:** Mixing test classes that override `migrateFreshUsing()` with different migration paths in one PHPUnit process can cause SQLite schema conflicts. Run suites separately if you see missing-table errors.