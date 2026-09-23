# Step 2 Implementation Report — Events, Listeners, and Observers

**Date:** 2026-09-22 (corrected after Step 2 review)  
**Scope:** Manager Step 2 only, current `HospitalAutomation` namespaces.  
**Folder restructure / TriggerHandlers / engine / NodeProcessors:** not done.  
**Workflows 14 and 15:** still unidentified; not implemented.

---

## Review findings (before the correction pass)

1. **Patient registration.** HIP signup (`AuthService`, `BookingApiService`, `DesktopController`) creates `Persons` with `is_primary = true`. Dependents are created with `is_primary = false` (e.g. `CreatePayment`). Registration can also **update** an existing row by attaching `hip_user_id`. The first observer fired on every `Persons` insert, including dependents, and ignored hip-user attach. **Not** every `Persons` row is an account-holder registration.

2. **Missed / no-show.** Real `DoctorBooking` values are `pending|confirmed|cancelled|completed` and appointment_status `new_scheduled|checked_in|completed|cancelled` (`appointmentStatusOptions()`). No missed/no-show value exists in the model or status service. The first Step 2 pass invented `missed` / `no_show` matching. **That dispatch was removed.**

3. **Birthday `daysBefore`.** Saved JSON (`birthdayWish.workflow.json`) has `triggerTiming: on_birthday` and `daysBefore: 1` on the **trigger node**. Step 2 dispatches on calendar DOB month/day (`on_birthday`). Applying `daysBefore` would be trigger-node config (Step 3) and would double-send if both ran.

4. **Women’s Day gender.** JSON has a **condition** node `patient.gender == "female"`. Step 2 must not pre-filter gender (values may be `female` / `Female` / unset). Calendar gate remains 8 March. Gender stays Step 4 condition execution.

5. **Inactive 30 vs 90 times.** JSON: 30-day workflow `executionTime: 10:00`; 90-day `10:30`. Both use trigger `scheduledEvent`. Existing `AutomationEngine::handle` starts **every** published workflow for that trigger+hospital. A second 10:30 command would start both graphs again. Step 2 therefore schedules **one** daily run at **10:00**. Per-workflow clock is Step 3.

6. **Duplicates.** Engine `alreadyStarted` only keys on `appointment_id` (and ignores empty ids). Birthday/anniversary have no appointment id, so a second command run would start new executions. Putting the last booking on `ScheduledEvent` would make `alreadyStarted` skip **future days** for the same last visit. **Fix:** command-level `EventDispatchGuard` (cache) and **do not** attach `appointment` on `ScheduledEvent`.

7. **Isolation / shared triggers.** Repository still returns all published workflows for trigger + exact `hospital_id`. Commands must pass `hospital_id` / `organization_id` on the event. Engine was not changed and still loops every matching workflow (four `appointmentBooked` campaigns stay independent).

---

## 1. Requirements after correction

| Trigger | Step 2 behavior |
|---|---|
| `appointmentBooked` | Observer: created confirmed, or `status` → `confirmed`. Unrelated field changes do not fire. |
| `appointmentCompleted` / cancelled | Unchanged: `DoctorBookingStatusService`. |
| `appointmentMissed` | Event + listener **remain registered**. **No observer dispatch** until HIP stores a real missed status. |
| `patientRegistered` | `PersonsObserver`: `created` if `is_primary`; `updated` only when `hip_user_id` goes from empty → set on a primary person. Dependents skipped. Guard prevents duplicate dispatch. |
| `prescriptionAdded` / medicine reminder | Unchanged. Medicine Reminder not modified. No PHPUnit files exist for that module. |
| `birthday` | Daily 09:00. DOB month/day = run date (`on_birthday`). `daysBefore` not applied. Hospital/org on payload. Cache guard per person per year. |
| `anniversaryReached` | Daily 09:00; `womens_day` only on 8 March. No gender filter. Cache guard per person per year. |
| `scheduledEvent` | Daily **10:00** only, `min-inactive-days=30`. Payload: `last_visit` days + `hospital_id`. No `appointment` key. Cache guard per person/hospital/day. |
| Inbound WhatsApp/SMS / chat event | Not implemented (not in the 13). |

---

## 2. Files created or modified (including review pass)

**Created:** `ScheduledEvent.php`, `PersonsObserver.php`, `AfterCommit.php`, `EventDispatchGuard.php`, `PatientAutomationIdentity.php`, three dispatch commands, `tests/Feature/HospitalAutomationStep2DispatchTest.php`, this report.

**Modified:** `DoctorBookingObserver.php` (booked only; invented missed matching **removed**), `HospitalAutomationServiceProvider.php`, `PatientRegistered` / `BirthdayReached` / `AnniversaryReached` payloads, `routes/console.php` (`withoutOverlapping`; single 10:00 scheduledEvent). `DoctorBooking.php` invented `isMissed()` **removed**.

**Not modified:** `AutomationEngine`, `WorkflowExecutor`, node executors, `/api/workflows`, Medicine Reminder, chatbot, saved JSON.

---

## 3. Registration

| Mechanism | Location |
|---|---|
| Listeners | `HospitalAutomationServiceProvider::registerEvents()` including `ScheduledEvent` → `DispatchHospitalAutomationWorkflow` |
| Observers | `DoctorBooking`, `Persons` |
| Commands | Same provider `register()` |
| Schedule | `routes/console.php` 09:00 birthdays/anniversaries; 10:00 scheduled events; all `withoutOverlapping()` |

---

## 4. Payload schemas

**PatientRegistered / BirthdayReached:** `patient`, `patient_id`, `organization_id`, `hospital_id` (from loaded `hipUser`, else last booking hospital when the person exists).

**AnniversaryReached:** plus `anniversaryType`.

**AppointmentBooked / AppointmentMissed / AppointmentCompleted:** `{ appointment: DoctorBooking }`. Missed is only used if something else dispatches the existing event.

**ScheduledEvent:** `patient`, `patient_id`, `hospital_id`, `organization_id`, `last_visit`, `last_visit_days`. **No `appointment`.**

---

## 5. Tests executed

PHPUnit 11.5.55 / PHP 8.3.27 from `c:\xampp\htdocs\HIP_backend`.

| Suite | Result |
|---|---|
| `HospitalAutomationStep2DispatchTest.php` + `AppointmentBookedAutomationTest.php` | **OK (51 tests, 104 assertions)** |
| `WorkflowBuilderTest.php` | **OK (7 tests, 58 assertions)** |

Step 2 tests now cover: primary vs dependent create; hip_user attach; duplicate person create; unrelated person update; real booking statuses do **not** fire `AppointmentMissed`; pending→confirmed still fires booked once; scheduled payload has no appointment; hospital fields on birthday/anniversary; cache guard claims once; Women’s Day skip off 8 March.

Medicine Reminder: no tests under `tests/`; module not changed.

---

## 6. Remaining limitations

1. Workflows 14 and 15 unknown.
2. `AppointmentMissed` cannot fire until product defines a stored status.
3. Linking an existing person without `is_primary` true will not start first-appointment nurturing (`FamilyPackageService` notes some dependents may be mis-flagged `is_primary`).
4. `daysBefore` and Women’s Day gender and `last_visit >= 30|90` expressions are Step 3/4.
5. Inactive 90 JSON clock 10:30 is not a second cron (would duplicate all `scheduledEvent` workflows).
6. Cache guard is per app cache store; a flush can allow a same-day re-dispatch.
7. Engine still fans out all hospital-scoped workflows for a trigger; Step 2 does not collapse four `appointmentBooked` campaigns into one.

---

## 7. Steps 3 and 4

Not implemented. Engine, executor, and node processors were not modified.

**Ready for review** as corrected Step 2 dispatch wiring. Not ready as complete campaign execution.
