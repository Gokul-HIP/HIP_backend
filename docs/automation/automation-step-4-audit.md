# Step 4 Audit — Generic Workflow Automation Engine

**Status:** audit only. **No application PHP was changed.** Implementation waits for approval (§22).

**Date:** 2026-09-23

**Interpretation of “15 workflows”:** This repository still has **13 named product graphs** in `docs/automation/frontend-json.md`. Workflows 14–15 remain unidentified. The **13 + catalog fixtures** are treated as a **capability test matrix**, not a hardcoded catalog. Step 4 must make any **new** graph that uses the **existing saved JSON contract** run without a new PHP class per graph.

---

## 1. Existing workflow runtime architecture

```
Saved definition (nodes/edges in workflow_versions.definition)
        ↓
WorkflowCompiler (NodeTypeNormalizer on nodeType; edges + sourceHandle)
        ↓
AutomationEngine::handle   [fan-out / lookup only]
        ↓
WorkflowRepository::findPublishedByTrigger (org + exact hospital)
        ↓
WorkflowExecutor::start / resume
        ↓
NodeExecutorRegistry::get(normalized nodeType)
        ↓
NodeExecutor::execute → NodeExecutionResult
        ↓
follow edges (handle true/false) / wait → ContinueWorkflowExecutionJob
```

**Separation (must keep):**

| Owner | Job |
|---|---|
| Step 2 observers/commands | Emit events |
| `HospitalAutomationTriggerService` | Normalize + scope; `handle()` once |
| `AutomationEngine` | Find published workflows; start each |
| `WorkflowExecutor` | Walk the graph |
| `WorkflowExecutionBridge` | Medicine **due** → one linked graph |

There is already **one** engine and **one** executor. Do not add a second.

Medicine due stays on the bridge. Inbound WhatsApp/SMS: **still absent** — document as gap; do not invent webhooks.

---

## 2. Reference workflow capability matrix (13 named + union)

| # | Name | Trigger (JSON) | Nodes | Waits | Conditions (expressions) | Recipients | Campaign metadata |
|---|---|---|---|---|---|---|---|
| 1 | First Appointment Nurturing | `patientRegistered` | wait×4, condition×4, sendWhatsApp×2, sendSms, sendPush, end×2 | duration 7d, 2d, 24h, 2h | `appointment.exists == false` | patient | `campaignKey`, `suppressOnAppointment: true`, `campaignStep` |
| 2 | Post-Visit Follow-up | `appointmentCompleted` | condition, sendWhatsApp×2, wait×2, sendSms, end×2 | **relative_date** before `followup.date` (4d, 1d) | `followup.exists == true` | doctor, patient | campaignStep |
| 3 | Missed Appointment Restart | `appointmentMissed` | sendWhatsApp, wait, condition, sendSms, end×2 | duration | `appointment.exists == false` | patient | — |
| 4 | Digital Prescription Share | `prescriptionAdded` | sendWhatsApp, end | — | — | patient | — |
| 5 | Medicine Reminder | `medicineReminder` | sendWhatsApp, end | — | — | patient | reminderTiming on trigger |
| 6 | Birthday Wish | `birthday` | sendWhatsApp, end | — | — | patient | triggerTiming on trigger |
| 7 | Women's Day Wish | `anniversary` | condition, sendWhatsApp, end×2 | — | `patient.gender == "female"` | patient | anniversaryType on trigger |
| 8 | Inactive 30 | `scheduledEvent` | condition, sendWhatsApp, end×2 | — | `last_visit >= 30` | patient | schedule 10:00 |
| 9 | Inactive 90 | `scheduledEvent` | condition, sendSms, end×2 | — | `last_visit >= 90` | patient | schedule 10:30 |
| 10 | Dentist Segment | `appointmentBooked` | condition, sendWhatsApp, end×2 | — | `appointment.department == "Dentistry"` | patient | — |
| 11 | Senior Segment | `appointmentBooked` | condition, sendSms, end×2 | — | `patient.age >= 60` | patient | — |
| 12 | Parent–Child | `appointmentBooked` | condition, sendWhatsApp, end×2 | — | `patient.age < 18 \|\| patient.relationship == "child"` | **caregiver** | — |
| 13 | Segment Variants | `appointmentBooked` | condition×3 sequential, WhatsApp/SMS | — | dentist / age≥60 / child | patient, caregiver | sequential if/else |

**14–15:** unknown. **Not** to be faked.

**Fixture union (catalog language, not the 13):** `ai`/`aiPrompt`, `dbCreate`/`createRecord`, `dbUpdate`/`databaseUpdate`, `dbDelete`, `dbQuery` (no executor), `httpRequest`/`webhook`, `sendEmail`, `sendTemplate`, `sendAiChat`, `sendAiVoice`, `sendIvr` (no executor), `updateAppointment`/`updateMembership`/`updatePrescription` (likely `dbUpdate` variants), `start` (frontend-only).

---

## 3. Complete node-type matrix (13 + aliases)

| JSON nodeType | Canonical | Executor | Class | Input | Next-node | Status |
|---|---|---|---|---|---|---|
| Catalog triggers | TriggerCatalog ids | `PassthroughTriggerExecutor` or specialized | A | node data unused for side effects | all outgoing | **A** generic |
| `wait` | `delay` | `DelayExecutor` | B | FE: `waitType`,`amount`,`unit` or relative_*; executor reads `type`/`value` | pause, resume next | **B/C** duration keys mismatch; **relative_date missing** |
| `condition` | `condition` | `ConditionExecutor` | C | FE: **`expression` string**; executor: **`rules`/`conditions` array** | `sourceHandle` true/false | **C** expressions ignored → empty rules → **true** |
| `sendWhatsApp` / `sendSms` / `sendPush` | sendWhatsApp / sendSMS / sendPush | AbstractMessagingExecutor | B | FE: `message`, `recipient`; executor: `messageTemplate`/`body` | continue | **B** `message` not read; caregiver not resolved |
| `end` | `end` | `EndExecutor` | A | — | complete | **A** |
| `createRecord` / `dbCreate` | createRecord | `CreateRecordExecutor` | A/B | table + values | continue | **A** for that contract; not used in the 13 |
| `dbUpdate` | databaseUpdate | `UpdateRecordExecutor` | A/B | table, where, values | continue | **A** for that contract |
| `dbQuery` | — | **none** | D | — | — | **D** if used in a new graph |
| `sendIvr` | — | **none** | D | — | — | **D** |
| inbound WA/SMS | — | — | D | — | — | **D** integration gap |

---

## 4. Compiler audit

**Fact.** Compiles arbitrary `nodes` + `edges`; normalizes `nodeType`; keeps full `data` array; preserves `sourceHandle`. Start = trigger with no incoming edge.

**Does not strip** expression, waitType, campaignKey (campaignKey lives on **workflow.configuration**, not always copied into compiled graph metadata). Runtime that needs `suppressOnAppointment` must read **published definition / workflow row**, not only the node list.

**Gap:** compiler does not validate expression/wait contracts. Fine for genericity.

**Hydrate:** `WorkflowExecutor` recompiles from `definition` even when `compiled_graph` is cached (metadata-only cache). Behavior is definition-driven. **A.**

---

## 5. Executor audit

Graph walk in `WorkflowExecutor` is **generic**: registry lookup, continue / wait / fail / complete / branch handle, parallel extra edges via job.

**No** `workflow_id` / `campaignKey` branches in Workflow module (grep).

Specialized trigger executors (prescription, medicine due, appointment booked, birthday, scheduled) add side effects; remaining triggers are passthrough. **Do not** add per-campaign executors.

---

## 6. Registry audit

`WorkflowServiceProvider` registers specialized executors then **passthrough for every TriggerCatalog type**. `wait`→`delay` via normalizer. `sendSms`→`sendSMS`.

**Duplicate:** `HospitalDomainTriggerExecutor` only binds types not already registered — usually a no-op.

**Missing registrations:** `dbQuery`, `sendIvr`, `httpRequest` unless aliased to `webhook`.

---

## 7. Condition audit

**Saved contract (13):** JEXL-like **`data.expression`**, e.g. `appointment.exists == false`, `patient.age >= 60`, `A || B`.

**Implemented:** nested `{operator, conditions[], field, compare, value}`. Empty rules → **true**.

**Tests** (`WorkflowConditionAndAiGraphTest`) use **rules**, not expressions.

**Required generic work:** evaluate `expression` when present (boolean, `==`, `!=`, `<`, `>`, `>=`, `<=`, `&&`, `||`, dotted paths, quoted strings). Keep rules-array as second format. **No** hardcoding of Dentistry / female / age 60.

Also refresh **live** facts used in expressions (`appointment.exists`) at evaluation time from **scoped** patient/hospital context — generic helper, not campaign names.

---

## 8. Wait / delay audit

**Saved:** `waitType: duration` + `amount` + `unit`; `waitType: relative_date` + `relativeDateField` + `relativeOffsetDirection` + `relativeOffsetAmount` + `relativeOffsetUnit`.

**Implemented:** `type`/`unit` + `value`/`amount` → seconds. Unknown type treated as **minutes**. Relative date **not implemented** (would wait 0 minutes or mis-parse).

**Resume:** `status=waiting`, `current_node_id` = **next** node, `ContinueWorkflowExecutionJob` delayed. No `resume_at` column; job delay is the clock. **Adequate** if relative_date computes seconds (or datetime) generically.

Multiple waits in one graph: already supported if each wait returns `waiting`.

---

## 9. Variable / context audit

Templates use `{{patient_name}}`, `{{hospital_name}}`, `{{booking_link}}`, `{{hospital_phone}}`, `{{doctor_name}}`, `{{followup_date}}`, `{{medicine_name}}` (underscore keys).

`VariableResolver` builds a **flat** map (`patient_name`, …). Regex is `[a-zA-Z0-9_]+` — **no** `{{patient.name}}`.

Condition paths are **dotted** (`patient.age`) via `data_get` **if** expression eval uses payload.

**Gaps:** `appointment.exists`, `followup.exists` / `followup.date`, `appointment.department`, `last_visit`, `patient.age`, `patient.relationship`, `caregiver` contact, `booking_link`, `hospital_phone` — not systematically set on `AutomationContextBuilder` for all triggers.

**Generic approach:** enrich context with **named facts** from the event’s own models (booking, person, prescription) and **query existence** only for the **same patient + hospital** (e.g. any future booking). Do **not** pick an unrelated latest appointment when the event already has a booking.

---

## 10. Recipient audit

JSON: `patient` | `doctor` | `caregiver`.

`ChannelManager::LOGICAL_RECIPIENTS` = patient, doctor, hospital, member, organization. **`caregiver` is not listed**; unknown logical names are returned **as the raw string** `"caregiver"` (invalid phone).

`custom` is used by sendAiChat/Voice, not ChannelManager.

**Required:** resolve `caregiver` from context (`caregiver_contact`, parent/guardian phone) generically.

---

## 11. Action / channel audit

Messaging executors call `ChannelManager` with resolved body. **Body field in the 13 is `message`**, not `messageTemplate`. Result: **empty message** unless a templateId is set (campaigns use `templateId: ""`).

Push uses `title`/`body` — those keys **are** read for push. WhatsApp/SMS **B/C**.

---

## 12. Campaign metadata audit

| Field | Where | Runtime today | Required generic meaning |
|---|---|---|---|
| `campaignKey` | workflow.configuration | unused | Identity for suppression/cancel; **not** a uniqueness key for lookup |
| `campaignStep` | node data | unused | Logging/metadata only unless product later uses it |
| `suppressOnAppointment` | workflow.configuration | **unused** | When **true**, an `appointmentBooked` for that patient/hospital should **cancel waiting** executions of workflows that set this flag |

**Forbidden:** `if ($campaignKey === 'first_appointment_nurturing')`.

**Allowed:** `if ($publishedDefinition['suppressOnAppointment'] === true)` then cancel matching waiting runs.

---

## 13. Execution-state audit

Statuses: running, waiting, completed, failed, cancelled. Fields: current_node_id, context JSON, variables JSON, failure_reason.

**Missing:** dedicated `resume_at`, completed-node list, branch choice log. Resume uses job + `current_node_id`. **Cancelled** unused for suppressOnAppointment.

Not a schema blocker if cancel = set status cancelled and don’t resume jobs (need to **forget** queued jobs or check status in `ContinueWorkflowExecutionJob`).

---

## 14. Actual runtime errors / incorrect behavior (from code vs JSON)

1. **Conditions always true** when only `expression` is saved.
2. **Duration wait** may be wrong (`amount` works if `unit` is used; `type` defaults to minutes — OK if unit is days).
3. **relative_date wait** not implemented.
4. **WhatsApp/SMS body** empty (`message` vs `messageTemplate`).
5. **caregiver** recipient not resolved.
6. **suppressOnAppointment** never cancels waiting nurture runs.
7. **appointment.exists / followup / last_visit / department / age** often absent from payload → even a correct expression engine would fail closed/open incorrectly.
8. **appointmentMissed** still has **no production dispatch** (Step 2 gap) — engine can execute the graph if the event is fired; do not invent a status.
9. Sending **failed** node **fails the whole execution** (no skip). Matches current executor; document as policy.

---

## 15. Generic capability gaps (union)

| Capability | Support | Step 4 work |
|---|---|---|
| Arbitrary linear graphs of existing node types | A | Tests A–E |
| True/false condition handles | A (edges) | Expression eval |
| Sequential conditions | A | — |
| Duration wait | B | Map waitType/amount/unit |
| Relative-date wait | D | Generic datetime-from-context-field |
| JEXL-like expressions | D/C | Generic expression interpreter |
| Flat `{{patient_name}}` | A | — |
| Nested `{{patient.name}}` | D | Optional if contract needs it |
| Recipients patient/doctor | A | — |
| Recipient caregiver | C | Add logical resolver |
| `message` field | C | Read `message` in messaging executor |
| suppressOnAppointment | D | Generic cancel of waiting executions |
| Fan-out same trigger | A (Step 3) | Do not touch |
| Medicine due single graph | A | Do not route through handle() |
| New node types (dbQuery, sendIvr, inbound WA) | D | Document; do not fake |

---

## 16. Files that must change (after approval)

Prefer extend-in-place:

- `ConditionEngine.php`, `ConditionExecutor.php`
- `DelayScheduler.php` (and DelayExecutor only if needed)
- `AbstractMessagingExecutor.php`
- `ChannelManager.php`
- `VariableResolver.php` / `AutomationContextBuilder.php` (generic facts)
- `ContinueWorkflowExecutionJob.php` (skip if execution cancelled)
- Thin **generic** suppression helper used from `AppointmentBooked` path / engine **without** campaign name checks
- **New tests** under `tests/Feature/Automation/` for graphs A–E and expression/wait/message/caregiver

---

## 17. Files that should NOT change

- Frontend / saved JSON / builder API
- Step 2 observers/commands (except if suppress hook is a **minimal** listen on existing `AppointmentBooked`)
- Step 3 TriggerService fan-out ownership
- `WorkflowRepository` isolation rules
- Medicine Reminder cron/job/bridge
- No per-workflow PHP classes

---

## 18. Proposed generic architecture

Keep current engine/executor/registry.

1. **Condition:** if `data.expression` set → parse/eval against context (`data_get` + comparators + `&&` `||`); else existing rules array.
2. **Wait:** if `waitType===duration` use amount/unit; if `relative_date` compute seconds until (field ± offset); clamp past dates to 0 or skip per documented rule.
3. **Context facts module:** given payload, set `appointment.exists`, `followup.exists`, `patient.age`, etc. from **this** patient/hospital only.
4. **Messaging:** body = messageTemplate ?? message ?? body.
5. **Recipients:** add caregiver (and custom if already in node data).
6. **Suppression:** on booked event, load waiting executions for that patient/hospital whose **definition.suppressOnAppointment** is true → mark cancelled; job no-ops if not waiting/running.

No second engine. No workflow-id switches.

---

## 19. New tests needed

- Graphs **A–E** as specified (constructed JSON, not the 13 files).
- Expression: `appointment.exists == false`, `patient.age >= 60`, `a || b`, true/false handles.
- Wait: duration 2 hours; relative_date before a context datetime.
- Message field WhatsApp; recipient caregiver.
- Two published graphs same trigger still start twice (Step 3 regression).
- suppressOnAppointment: waiting execution cancelled on booked (generic flag, **not** campaignKey string).
- Compiler: new graph compile + start + complete.
- Medicine due still one linked workflow.

---

## 20. Proof strategy for workflows **not** in the original 13/15

Tests **construct** definitions in PHP (`nodes`/`edges` only). Assertions: compile succeeds, execution status completed/waiting as expected, **no** fixture name/id in production code. Grep CI: `campaignKey ===` / `first_appointment` / workflow name in `app/Modules/{Automation,Workflow}` executors must stay empty.

---

## Stop

No Step 4 PHP has been written. Approve this generic-gap list (expression, wait mapping, relative_date, `message`, caregiver, context facts, suppressOnAppointment) before implementation.
