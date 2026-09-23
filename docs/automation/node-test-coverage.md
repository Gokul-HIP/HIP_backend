# Hospital Automation — Node Test Coverage Matrix

Generated from `docs/automation/backend-node-contracts.json` and live
`NodeProcessorRegistry` / `NodeTypeNormalizer` inspection.

**Legend**
- **Status**: contract status (`implemented` | `partial` | `not_implemented`)
- **Graph tested**: FE ID → Normalizer → Compiler → Registry → Executor → End (or documented skip)
- **Notes**: runtime reality (passthrough ≠ domain-complete)

| FE Node | Canonical | Executor | Status | Graph tested | Notes |
|---|---|---|---|---|---|
| start | — | none | frontend_only | SKIP | FE-only; compiler strips; publish skips |
| onChatMessage | messageReceived | TriggerNodeProcessor | partial* | YES (chat HTTP) | *Domain observer incomplete; POST /api/chat/completions → ChatbotWorkflowService is implemented |
| patientRegistered | patientRegistered | TriggerNodeProcessor | partial | YES (passthrough) | |
| appointmentBooked | appointmentBooked | AppointmentBookedTriggerNodeProcessor | implemented | YES | Full observer/engine suite in AppointmentBookedAutomationTest |
| appointmentRescheduled | appointmentRescheduled | TriggerNodeProcessor | partial | YES (passthrough) | |
| appointmentCompleted | appointmentCompleted | TriggerNodeProcessor | partial | YES (passthrough) | Domain via DoctorBookingStatusService |
| appointmentCancelled | appointmentCancelled | TriggerNodeProcessor | partial | YES (passthrough) | Domain via DoctorBookingStatusService |
| appointmentMissed | appointmentMissed | TriggerNodeProcessor | partial | YES (passthrough) | |
| prescriptionAdded | prescriptionAdded | PrescriptionAddedTriggerNodeProcessor | implemented | YES | |
| medicineReminder | medicineReminderDue | MedicineReminderDueTriggerNodeProcessor | implemented | YES* | *Requires schedule_id; started via WorkflowExecutionBridge linked workflow, not AutomationEngine fan-out |
| labTestOrdered | labTestOrdered | TriggerNodeProcessor | partial | YES (passthrough) | |
| labReportNotification | labReportReady | TriggerNodeProcessor | partial | YES (passthrough) | |
| pharmacyRefillDue | medicineRefillDue | TriggerNodeProcessor | partial | YES (passthrough) | |
| appointmentReminder | appointmentReminder | TriggerNodeProcessor | not_implemented | SKIP | Domain incomplete |
| birthday | birthday | BirthdayTriggerNodeProcessor | partial | YES | |
| anniversary | anniversaryReached | TriggerNodeProcessor | partial | YES (passthrough) | |
| membershipExpiry | membershipExpiry | TriggerNodeProcessor | partial | YES (passthrough) | |
| userPlanExpiry | userPlanExpiry | TriggerNodeProcessor | not_implemented | SKIP | |
| rewardUpdated | rewardPointsUpdated | TriggerNodeProcessor | partial | YES (passthrough) | |
| rewardsTierUpgraded | rewardTierUpgraded | TriggerNodeProcessor | partial | YES (passthrough) | |
| familyPackageTierUpdated | familyPackageTierUpdated | TriggerNodeProcessor | not_implemented | SKIP | |
| invoiceGenerated | invoiceGenerated | TriggerNodeProcessor | partial | YES (passthrough) | |
| paymentReceived | paymentReceived | TriggerNodeProcessor | partial | YES (passthrough) | |
| webhookEvent | webhookEvent | TriggerNodeProcessor | not_implemented | SKIP | |
| apiEvent | apiEvent | TriggerNodeProcessor | not_implemented | SKIP | |
| scheduledEvent | scheduledEvent | ScheduledEventTriggerNodeProcessor | partial | YES | |
| condition | condition | ConditionNodeProcessor | implemented | YES | True/false branches |
| wait | delay | DelayNodeProcessor | implemented | YES | Queue::fake + manual resume |
| sendWhatsApp | sendWhatsApp | SendWhatsAppNodeProcessor | implemented | YES | Provider mocked |
| sendSms | sendSMS | SendSMSNodeProcessor | implemented | YES | Provider mocked |
| sendEmail | sendEmail | SendEmailNodeProcessor | implemented | YES | Provider mocked |
| sendPush | sendPush | SendPushNodeProcessor | implemented | YES | Provider mocked |
| sendAiChat | sendAiChat | SendAiChatNodeProcessor | implemented | YES | Http::fake + ChannelManager; distinct from ai/aiPrompt |
| sendAiVoice | sendAiVoice | SendAiVoiceNodeProcessor | implemented | YES | Http::fake + AiVoiceCallService / ChannelManager; distinct from sendAiChat/sendIvr |
| sendIvr | — | none | future_integration | REJECT publish / FAIL runtime | No IVR provider. Not faked. Frontend will hide. See send-ivr-future-integration.md |
| sendTemplate | sendTemplate | SendTemplateNodeProcessor | implemented | YES | Provider mocked |
| dbCreate | createRecord | CreateRecordNodeProcessor | implemented | YES | Test DB table |
| dbUpdate | databaseUpdate | UpdateRecordNodeProcessor | implemented | YES | Test DB table |
| dbDelete | dbDelete | DbDeleteNodeProcessor | implemented | YES | Allowlist + hospital isolation |
| updateAppointment | databaseUpdate | UpdateRecordNodeProcessor | implemented | YES | Alias + DomainRecordMap doctor_bookings + hospital scope |
| updatePrescription | databaseUpdate | UpdateRecordNodeProcessor | implemented | YES | Alias + DomainRecordMap prescriptions + hospital scope |
| updateMembership | databaseUpdate | UpdateRecordNodeProcessor | implemented | YES | Alias + DomainRecordMap user_family_subscriptions + member scope |
| dbQuery | dbQuery | DbQueryNodeProcessor | implemented | YES | Constrained Query Builder; SQL strings fail |
| httpRequest | webhook | WebhookNodeProcessor | implemented | YES | Alias to webhook; URL/method/headers/body/timeout from node config |
| ai | aiPrompt | AiPromptNodeProcessor | implemented | YES | Http::fake |
| end | end | EndNodeProcessor | implemented | YES | |

## Critical suites (already present + extended)

| Suite | Purpose |
|---|---|
| `AppointmentBookedAutomationTest` | Observer rules, hospital isolation, duplicates, payment vs automation notification metadata |
| `WorkflowDelayResumeTest` | wait→delay, resume, no re-entry loop |
| `SendTemplateNodeProcessorTest` | Template channel/variables/failures |
| `SendAiChatNodeProcessorTest` | sendAiChat AI+ChannelManager path (Http::fake) |
| `SendAiVoiceNodeProcessorTest` | sendAiVoice AiVoiceCallService+ChannelManager path (Http::fake) |
| `ChatbotWorkflowCompletionsTest` | /chat/completions → onChatMessage discovery → SendAiChat reply |
| `DbDeleteNodeProcessorTest` | Allowlist + isolation |
| `NodeTypeNormalizerTest` | Aliases + compiler |
| `WorkflowAutomationCoverageTest` | Full FE matrix: normalize, registry, graph, skips |
| `WorkflowMessagingGraphTest` | All messaging channels via real executor path |
| `WorkflowDatabaseGraphTest` | dbCreate/dbUpdate/dbDelete graphs |
| `WorkflowConditionGraphTest` | True/false branching |
| `FrontendExposedNodeCapabilitiesTest` | dbQuery, httpRequest, update*, sendIvr reject |

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
php artisan test --filter=SendTemplateNodeProcessorTest
```

**Note:** Mixing test classes that override `migrateFreshUsing()` with different migration paths in one PHPUnit process can cause SQLite schema conflicts. Run suites separately if you see missing-table errors.