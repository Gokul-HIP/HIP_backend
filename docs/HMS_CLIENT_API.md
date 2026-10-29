# HMS client API

Read endpoints, booking webhooks, and payment verification for doctor, second opinion, and diagnostic package appointments. Cancel and membership purchase are not part of this step. Booking webhooks do not call Razorpay and do not debit coins. Coins are earned only when a payment is settled.

Base path (no `/api` prefix):

```text
/apiv1/client
```

Local example: `http://localhost/HIP_backend/public`

## Authentication

Every request needs three headers:

| Header | Value |
| --- | --- |
| `X-HMS-Key` | Raw API key. The server stores only `sha256` of this value. |
| `X-HMS-Timestamp` | Unix time in seconds. |
| `X-HMS-Signature` | Hex HMAC-SHA256 of the canonical string, using the integration signing secret. |

Canonical string:

```text
{timestamp}.{METHOD}.{path+query}.{raw body}
```

- `METHOD` is uppercase (`GET`, `POST`).
- `path+query` is the path the app receives, plus `?` and the **raw** query string when one is present. Do not sort or re-encode the query.
- `raw body` is the exact request body. GET uses an empty string.
- Compare hex case-insensitively. The server uses a constant-time compare.

A timestamp more than 5 minutes away from the server clock is rejected. A signature cannot be sent again inside that window.

`X-HMS-Event-Id` is optional on the read and quote endpoints, including `GET /booking/status`. It is required on every booking POST and on `POST /payment-verification`. A missing id is `422` `missing_event_id`. The first request is stored in `hms_webhook_inbox`. A repeat of the same id for that integration returns the stored response and is logged as `duplicate`. A retry must be signed again (new timestamp and signature) and must keep the same `X-HMS-Event-Id`. A retry of that same payment event must also send the same `payment.reference`. The same event id returns the stored response and is not processed again. A new event id may reuse a reference that was rejected or recorded as failed. Use a different event id for the payment than the one used to create the booking.

Every response includes `X-Request-Id`. Failures also include `request_id` in the JSON body.

The integration only sees hospitals listed in its `hospital_ids`. A known hospital outside that list is `403`. An optional IP allow list rejects other addresses.

## Mobile numbers

`mobile` is matched to `healthinpocket_users.mobile_num` after normalization:

1. Trim the value and remove spaces, dashes, parentheses, dots, and a leading `+`.
2. Drop a leading `0`.
3. Drop a leading `91` only when digits still remain after it. A 10-digit number that itself starts with `91` is kept.

These all match an account stored as `8296951192`:

```text
8296951192
08296951192
918296951192
+91 82969 51192
82969-51192
0918296951192
```

A value that has no digits left (`000`, `+`) is `422` `validation_error`.

### PHP

```php
<?php

$secret = 'hms_sandbox_secret';
$timestamp = (string) time();
$method = 'GET';
$pathAndQuery = '/apiv1/client/get/patient/membership?mobile=9990001111&hospital_code=H1';
$rawBody = '';

$canonical = $timestamp.'.'.$method.'.'.$pathAndQuery.'.'.$rawBody;
$signature = hash_hmac('sha256', $canonical, $secret);

$headers = [
    'X-HMS-Key: hms_sandbox_key',
    'X-HMS-Timestamp: '.$timestamp,
    'X-HMS-Signature: '.$signature,
    'Accept: application/json',
];
```

### Node

```js
const crypto = require('crypto');

const secret = 'hms_sandbox_secret';
const timestamp = Math.floor(Date.now() / 1000).toString();
const method = 'GET';
const pathAndQuery = '/apiv1/client/get/patient/membership?mobile=9990001111&hospital_code=H1';
const rawBody = '';

const canonical = `${timestamp}.${method}.${pathAndQuery}.${rawBody}`;
const signature = crypto.createHmac('sha256', secret).update(canonical).digest('hex');
```

## Sandbox integration

Local only. The seeder refuses to run when `APP_ENV=production`.

```bash
php artisan db:seed --class=HmsSandboxIntegrationSeeder
```

| | |
| --- | --- |
| API key | `hms_sandbox_key` |
| Signing secret | `hms_sandbox_secret` |

Hospital scope is the first 50 hospital ids in the local database. Map external ids in `hms_external_id_maps` (`hospital`, `doctor`, `package`, `plan`, `patient`).

These values are for the local sandbox. Do not reuse them in production.

## Production commands

These commands are safe to run in production. The sandbox seeder is not.

```bash
php artisan hms:integration:create "City HMS" --hospitals=1,2 --organization=9 --ips=10.1.1.1 --rate=120
php artisan hms:integration:rotate-secret 1
php artisan hms:integration:disable 1
php artisan hms:integration:enable 1
php artisan hms:integration:list
php artisan hms:map:import 1 storage/hms-map.csv
php artisan hms:map:list 1
```

`hms:integration:create` prints a random API key and signing secret once. The database stores `sha256` of the key and the encrypted secret. `hms:integration:list` does not print either value. `hms:integration:rotate-secret` prints the new secret once.

`hms:map:import` reads a CSV with the header `entity_type,external_id,local_id`. It upserts on integration, entity type, and external id. `hospital`, `doctor`, `package`, and `plan` rows are imported only when that local id exists (`hospitals`, `doctors`, `diagnostic_packages`, `family_packages`). A missing local id is reported and skipped. `patient` rows are stored without a person lookup. The command prints created, updated, and skipped counts.

## Endpoints

### GET `/apiv1/client/get/patient/membership`

Query: `mobile` (HIP account `mobile_num`), `hospital_code` (external id, or a numeric hospital id), optional `patient_ref` (external patient id).

The lookup is read-only. A missing patient is `404` with code `patient_not_found` and no account is created. A missing hospital, doctor, or package stays `404` `not_found`. No active membership is `200` with `has_membership: false`.

`patient_covered` is true when the person is in `covered_member_ids`. An empty list means the account's family is covered. Consultation and lab usage are counted the same way as `MembershipEntitlementService` (consultation rows in `subscription_usage_logs`, lab rows via the diagnostic booking the entitlement service uses). Nothing is reserved or expired by this call.

```json
{
  "has_membership": true,
  "valid_at_hospital": true,
  "patient_covered": true,
  "plan": {
    "id": 1,
    "code": "FAM-GOLD",
    "name": "Family Gold",
    "payment_status": "paid",
    "start_date": "2026-10-28",
    "end_date": "2026-11-07",
    "days_left": 10,
    "branches": [1]
  },
  "free_consultations": {"unlimited": false, "total": 4, "used": 1, "remaining": 3},
  "free_packages": [
    {"package_id": 5, "package_code": "LAB-1", "name": "CBC", "unlimited": false, "total": 2, "used": 0, "remaining": 2}
  ],
  "covered_members": [
    {"person_id": "…", "name": "Asha Rao", "relationship": "Self"}
  ]
}
```

`plan.code` is `family_packages.code` (nullable, unique). Set it as Plan code on the admin and hospital-admin family package forms. It is not added to the existing app family-package API. `remaining` is `null` when the allowance is unlimited (`total` 0 and `unlimited` true).

### GET `/apiv1/client/get/patient/reward/plan`

Same query parameters. An unknown patient is `404` `patient_not_found`. Read-only: it uses `RewardTierService::getReadOnlyRewardProgressPayload`, `getReadOnlyDiscountUsageSummary`, and `getReadOnlyTierForUser`. It does not create `user_reward_progress`.

`discounted_bookings.used` counts consumed rows in `reward_tier_discount_usages`. `remaining` also treats a fresh reservation as used, matching the entitlement service. All three fields are `null` when the tier allowance is unlimited.

`wallet_coin_balance` is the existing coins row for the person (or their `parent_id`). `lifetime_coins` is the stored lifetime total.

```json
{
  "tier_name": "Silver",
  "percentage": 15,
  "status": true,
  "discounted_bookings": {"limit": 2, "used": 2, "remaining": 0},
  "package_promotions": [
    {"package_id": 5, "package_code": "LAB-1", "type": "percentage", "value": 10}
  ],
  "progress": {
    "next_tier_name": "Gold",
    "earned_coins_in_tier": 40,
    "coins_to_next_tier": 60,
    "progress_percentage": 40
  },
  "lifetime_coins": 80,
  "wallet_coin_balance": 25
}
```

`type` is `free`, or the promotion's `percentage` / `flat`. `value` is null for a free promotion.

### POST `/apiv1/client/price/quote`

```json
{
  "booking_type": "doctor",
  "mobile": "9990001111",
  "hospital_code": "H1",
  "patient_ref": "DEP",
  "doctor_id": "DOC-1",
  "package_code": "LAB-1",
  "original_fee": 640,
  "coins_used": 0
}
```

`booking_type` is `doctor`, `second_opinion`, or `package`. `doctor_id` is required for the doctor types and may be an external id or the doctor UUID. `package_code` is required for `package` and may be an external id or `diagnostic_packages.code`. `original_fee` is rupees; when it is omitted the stored doctor fee or package price is used. `coins_used` is ignored and priced as 0.

An unknown mobile still returns `200`. Pricing uses the base (lowest) tier, membership is not applied, and `patient_exists` is `false`. No account, reward progress, or reservation is created. A known mobile whose `patient_ref` does not resolve is `404` `patient_not_found`. A known patient quote includes `patient_exists: true`.

The numbers come from `BookingPricingPreviewService`, the same service as the app preview. The quote is not stored. `expires_at` is 15 minutes after the response. Currency is `INR`. `payable_amount` equals `pricing.total_payable`.

Second opinions follow the app preview and do not apply membership. Doctor and package quotes do.

## Booking webhooks

All three are `POST`. Amounts are rupees and may include decimals. Slots are not checked against HIP availability.

| Path | Creates |
| --- | --- |
| `/apiv1/client/appointment/booked` | Doctor when `appointment_type` is `doctor`. Second opinion when it is `second_opinion`. |
| `/apiv1/client/diagnostic/booked` | Diagnostic package booking. `package_code` replaces `doctor_id` and `appointment_type`. `appointment_date` and `slot_time` are optional. |
| `/apiv1/client/diagonistic/booked` | Same handler as `diagnostic/booked`. |
| `/apiv1/client/labtest/booked` | `501` `not_supported`. The request is still written to the inbox. |

`hospital_code`, `doctor_id`, and `package_code` resolve through `hms_external_id_maps`, then a numeric hospital id, a doctor UUID, or `diagnostic_packages.code`. A hospital outside the integration is `403` `forbidden_hospital`. Anything else missing is `404` `not_found`.

`patient.mobile` is normalized. `patient_ref` is required and stored as a `patient` map. An unknown mobile creates a password-null HIP user, a primary person, an MRN, a zero-coin wallet, and base-tier reward progress. No welcome notification is sent. A `relationship` other than `Self` needs `primary_mobile` and creates a person with `hip_user_id` null under that account (the account is created first when it does not exist). The same `patient_ref` is reused. A ref that already belongs to a different mobile is `422` `validation_error`.

Pricing uses `BookingPricingPreviewService` with `coins_used` 0. `original_fee` replaces the stored doctor or package fee. Membership applies only when this person's id is covered (`covered_member_ids`; empty means the whole family). Second opinions never use membership. A package uses a membership free-package benefit first, otherwise the reward promotion or percentage. The app quote does not pass a covered person, so app pricing is unchanged.

The webhook inserts a pending booking, a pending invoice, and a pending transaction. It does not create a Razorpay order or a `razorpay_payments` row. Membership usage and the reward discount are reserved when they apply. `hold_expires_at` is `HMS_PENDING_HOLD_MINUTES` from now (default 30). The reward reservation uses that same expiry, so the 15-minute app release does not drop it early.

If HIP `total_payable` is 0 and the amounts match, the booking is confirmed immediately (`state` `confirmed`): membership usage is consumed, the reward reservation is consumed, and the invoice and transaction are completed with `payment_method` `free`.

If `abs(payable_amount - total_payable) > 0.01`, the rows are still created and reservations are still held, but `hms_bookings.status` is `held_mismatch`. The response `state` is still `pending_payment`, with `price_mismatch` true. The amount is not rewritten. Nothing is confirmed until a later payment step matches the HIP amount or an admin resolves it. After a price mismatch, cancel that booking and send it again with a new `hms_booking_id`. The same id with a different payload returns `409` `booking_conflict`.

A second call with the same `hms_booking_id` and the same payload (a new event id) returns `200` and the existing booking. `original_fee` and `payable_amount` match across `600` and `600.00`. A different payload is `409` `booking_conflict` and includes the stored `request_hash`. Nothing is changed.

`php artisan hms:bookings:expire-unpaid` runs every minute. Pending and held rows past `hold_expires_at` become `expired`. The HIP booking becomes `cancelled` (the same status an unpaid app booking uses), the pending invoice and transaction become `cancelled`, and the reward and membership reservations are released. Confirmed rows are left alone.

Doctor, second opinion, and package rows are created as `pending` with payment `pending`, so the existing observers dispatch `AppointmentBooked`, `SecondOpinionBooked`, and `LabTestOrdered` the same way they do for an app pending online booking. Workflows that look at `status` / payment fields are what decide a confirmed or paid message. The confirmed event is dispatched only when status later becomes `confirmed` (a zero payable now, or payment settlement later).

### Appointment body

```json
{
  "hms_booking_id": "HMS-10045",
  "appointment_type": "doctor",
  "hospital_code": "H1",
  "doctor_id": "DOC-1",
  "appointment_date": "2026-11-02",
  "slot_time": "10:30",
  "original_fee": 600,
  "payable_amount": 605.94,
  "patient": {
    "mobile": "9990001111",
    "name": "Asha Rao",
    "dob": "1990-05-01",
    "gender": "female",
    "email": null,
    "patient_ref": "P-77",
    "relationship": "Self",
    "primary_mobile": null
  },
  "consultation_type": "in_person",
  "notes": null
}
```

`consultation_type` is optional: `in_person` (default) or `online`. In-person is stored as the app's `In-Person` / `In-Clinic` values. Online is stored as `Online` / `Online Consultation` and is the only doctor booking that appears on the Online Consultation page. `patient.patient_ref` is optional when `relationship` is `Self` and required for any other relationship (`422` `patient_ref_required`). When no mapping exists, a dependent is matched to an existing dependent of the same primary by name and date of birth before a new person is created. `primary_patient.name` is optional and is used only when the primary account is created for a dependent. If it is omitted, that primary's name falls back to the mobile.

### Response

`201` the first time, `200` when the same `hms_booking_id` and payload are sent again.

```json
{
  "status": true,
  "booking_id": 101,
  "booking_type": "doctor",
  "state": "pending_payment",
  "payable_amount": 605.94,
  "hms_payable_amount": 605.94,
  "price_mismatch": false,
  "hold_expires_at": "2026-11-02T10:00:00+05:30",
  "patient": {"person_id": "…", "created": true},
  "pricing": {}
}
```

`payable_amount` is the HIP total. `hms_payable_amount` is the amount HMS sent. `pricing` is the same block as `price/quote`, including on a price mismatch. `state` is `pending_payment`, `confirmed`, `expired`, or `cancelled`. A price hold stays `pending_payment` and sets `price_mismatch` true. `held_mismatch` is only the stored row status.

## Payment verification

`POST /apiv1/client/payment-verification`

Requires `X-HMS-Event-Id`. Send either `hms_booking_id` or both `booking_id` and `booking_type` (`doctor`, `second_opinion`, `package`). A booking that belongs to another integration is `404` `booking_not_found`.

```json
{
  "hms_booking_id": "HMS-10045",
  "payment": {
    "reference": "RCPT-8891",
    "status": "success",
    "amount": 605.94,
    "method": "cash",
    "paid_at": "2026-10-28T16:45:00+05:30"
  }
}
```

`payment.reference` is required. It belongs to one accepted payment for this integration. After a rejection or a recorded failure, the same reference may be used again, including on another booking. A payment that was accepted is never moved to another booking. If that booking is later cancelled, HMS refunds it. `payment.status` is `success` or `failed`. `payment.amount` is rupees. `payment.method` is free text (`cash`, `card`, `upi`). There is no Razorpay signature.

The check runs in one database transaction with a row lock on the HMS booking and on the payment reference.

| Result | HTTP | What happens |
| --- | --- | --- |
| `success`, amount equals `expected_amount` within 0.01, state `pending_payment` | 200 `state: confirmed` | Transaction and invoice are completed, the booking is paid and confirmed, membership usage and the reward usage are consumed, the patient earns coins equal to 1% of the amount paid, and `InvoiceGenerated` and `PaymentReceived` are dispatched |
| Same `payment.reference` again, and that attempt was `settled` or `late_settled` on this booking | 200 `result: duplicate` | No second settlement and no second coin credit |
| Same accepted `payment.reference` on a different `hms_booking_id` | 409 `payment_reference_used` | Message is "This payment reference was already used for another booking." The other booking is not returned. This still applies after that booking is cancelled. HMS refunds the accepted payment |
| Same `payment.reference` after `rejected_amount_mismatch`, `rejected_expired`, `rejected_cancelled`, or `failed_recorded` | The new attempt follows the normal rules | The existing `hms_payments` row is updated in place. Its notes gain the previous booking and result. The reference is not inserted again |
| Already confirmed, different reference | 409 `already_paid` | Nothing changes |
| Amount does not match, or the booking is held for a price mismatch | 409 `amount_mismatch` | Body includes `expected_amount` and `received_amount`. The booking stays pending and the reservations stay until the hold expires |
| `failed` | 200 `state: pending_payment` | Recorded as `failed_recorded`. The hold stays until it expires |
| `success` after the hold expired, inside `HMS_LATE_PAYMENT_WINDOW_HOURS` (default 24), amount matches | 200 `state: confirmed` | Booking, invoice, and transaction are restored and settled. Result is `late_settled`. If the reward or membership allowance is now exhausted, the payment still settles and `hms_payments.notes` says so |
| `success` after the window, or the amount does not match an expired booking | 409 `booking_expired` | Recorded as `rejected_expired`. HMS refunds or rebooks |
| `success` for a cancelled booking | 409 `booking_cancelled` | Recorded as `rejected_cancelled` |
| Booking was already confirmed because the HIP total was zero, and the amount is within 0.01 of zero | 200 `result: settled` | No change and no second coin credit |
| Booking was already confirmed because the HIP total was zero, and the amount is above 0.01 | 409 `amount_mismatch` | Recorded as `rejected_amount_mismatch`. The booking stays confirmed |

A retry of the same webhook delivery must be signed again, keep the same `X-HMS-Event-Id`, and keep the same `payment.reference`. The same event id returns the stored response and is not processed again. A new event id is a new attempt. It may reuse a reference that was rejected or recorded as failed. Sending a reference that already settled or late-settled this booking returns `200` and `result` `duplicate`. Sending a reference that was accepted for a different booking returns `409` `payment_reference_used`.

Every settled payment body includes `result`: `settled`, `late_settled`, or `duplicate`.

```json
{"status": true, "result": "settled", "state": "confirmed", "booking_id": 101, "paid_amount": 605.94}
```

## Booking status

`GET /apiv1/client/booking/status?hms_booking_id=HMS-10045`

Read-only. Expiry does not send a webhook, so HMS polls this. Send `hms_booking_id`, or `booking_type` (`doctor`, `second_opinion`, or `package`) with `booking_id` (the HIP id). A HIP id is found only when this integration created that booking. `state` is `pending_payment`, `confirmed`, `expired`, or `cancelled`. A held price is `pending_payment` with `price_mismatch` true. `paid_amount` is null until a payment settles. `refund` is null until a paid booking is cancelled. `coins_reversed` is `0` until then.

```json
{
  "hms_booking_id": "HMS-10045",
  "booking_id": 101,
  "booking_type": "doctor",
  "state": "pending_payment",
  "expected_amount": 605.94,
  "hms_payable_amount": 605.94,
  "paid_amount": null,
  "hold_expires_at": "2026-11-02T10:00:00+05:30",
  "price_mismatch": false,
  "refund": null,
  "coins_reversed": 0,
  "payments": [
    {"reference": "RCPT-8891", "status": "success", "amount": 605.94, "result": "settled", "paid_at": "2026-10-28T16:45:00+05:30"}
  ]
}
```

## Errors

```json
{"status": false, "code": "invalid_signature", "message": "Signature is invalid.", "request_id": "…"}
```

| HTTP | `code` | When |
| --- | --- | --- |
| 401 | `invalid_signature` | Missing or bad key, bad signature, or a replayed signature |
| 401 | `stale_timestamp` | Timestamp is more than 5 minutes from now |
| 403 | `invalid_signature` | Integration is disabled, or the caller IP is not on the allow list |
| 403 | `forbidden_hospital` | Hospital is outside this integration's `hospital_ids` |
| 404 | `not_found` | Hospital, doctor, or package was not found |
| 404 | `patient_not_found` | Membership or reward lookup found no patient, or a quote's mobile exists but `patient_ref` does not |
| 404 | `booking_not_found` | Unknown `hms_booking_id`, a HIP id that is not an HMS booking for this integration, or the booking belongs to another integration |
| 409 | `booking_conflict` | Same `hms_booking_id` with a different payload. Body includes `request_hash` |
| 409 | `already_paid` | Booking is already confirmed and this `payment.reference` is new |
| 409 | `payment_reference_used` | `payment.reference` was already accepted for a different booking, even if that booking was later cancelled |
| 409 | `amount_mismatch` | Amount is outside 0.01 of the HIP total, or the booking is held for a price mismatch. Body includes `expected_amount` and `received_amount` |
| 409 | `booking_expired` | Success arrived after the late-payment window, or the amount did not match an expired booking |
| 409 | `booking_cancelled` | Success arrived for a cancelled booking |
| 422 | `missing_event_id` | Booking POST or payment verification without `X-HMS-Event-Id` |
| 422 | `patient_ref_required` | Dependent booking without `patient.patient_ref` |
| 422 | `validation_error` | Required field missing, bad date or time, or `patient_ref` does not match the mobile |
| 429 | `rate_limited` | Over `rate_limit_per_minute` (default 120) |
| 501 | `not_supported` | `labtest/booked` |

Disabled and IP failures use `invalid_signature` so the code list stays the one above. The `message` says which check failed. A disabled integration is HTTP 403, not 401, because the signature itself was valid.

## Inbox

`hms_webhook_inbox` stores every request, including GET. `X-HMS-Key` and `X-HMS-Signature` are stored as `[redacted]`. Successful reads are `processed`. Failures are `failed`.

```bash
php artisan hms:inbox:prune --days=90
```

That command deletes inbox rows whose `received_at` is older than 90 days. It is scheduled every day at 01:10. `--days` must be at least 1.

```bash
php artisan hms:bookings:expire-unpaid
```

That command expires unpaid HMS holds. It is scheduled every minute. `HMS_PENDING_HOLD_MINUTES` defaults to 30. `HMS_LATE_PAYMENT_WINDOW_HOURS` defaults to 24 and is the window after `hold_expires_at` in which a matching success payment can still settle.

## Membership purchase

`POST /apiv1/client/membership/purchased`

Requires `X-HMS-Event-Id`. `plan_code` is an external plan map or `family_packages.code`. The plan must be active, and its `branch_ids` must be empty or include the hospital. The purchaser is provisioned like a booking patient. `covered_members` is optional. An empty list covers the purchaser's current family. A non-empty list covers the purchaser plus those dependents, and `max_members` counts the purchaser (`422` `too_many_members`). A same-plan renewal adds listed members and does not remove anyone. `start_date` in the future is `422` `start_date_in_future` when it would start a new period. A same-plan extension ignores `start_date`.

Activation calls `FamilyPackageService::createCashierSubscription` (completed family-package invoice, completed transaction, active `user_family_subscriptions` row, `payment_status` paid). `start_date` defaults to the `paid_at` date. `end_date` is `start_date` plus `duration_days`. `amount_paid` is stored as HMS sent it. A difference from the plan price still activates and returns `price_mismatch` true with `expected_amount` and `received_amount`.

`type` `new` while any membership is active is `409` `membership_already_active`. `type` `renewal` for the same active plan extends that row's `end_date` by `duration_days` from the current end date. Usage on that period stays. A renewal with no active plan creates a new period. A renewal while a different plan is active is `409` `membership_already_active`. The same `hms_membership_id` and payload returns `200`. A different payload is `409` `membership_conflict`. The same `payment.reference` on another membership is `409` `payment_reference_used`.

The cashier method dispatches `InvoiceGenerated`, `PaymentReceived`, and `MembershipRenewed`. It does not call `NotificationService` and it does not credit the coin wallet. The invoice `coins_earned` column is still set to 1% of the amount, which is what that method already writes. A renewal that only extends the current period does not create a second invoice or send those events again.

`201` the first time:

```json
{"status": true, "membership": {"subscription_id": 12, "plan": {"id": 3, "code": "FAM-GOLD", "name": "Family Gold"}, "start_date": "2026-10-28", "end_date": "2026-11-27", "covered_members": [{"person_id": "…", "name": "Ravi", "relationship": "Child"}]}, "price_mismatch": false, "expected_amount": 5000, "received_amount": 5000}
```

`GET /apiv1/client/get/patient/membership` reads this subscription, including covered members and remaining free consultations and packages.

## Cancellation

`POST /apiv1/client/booking/cancelled` and `POST /apiv1/client/membership/cancelled` require `X-HMS-Event-Id`.

Send `hms_booking_id`, or `booking_type` (`doctor`, `second_opinion`, or `package`) with `booking_id` (the HIP id). The HIP id is resolved only through an `hms_bookings` row for this integration. An app booking, or a booking created by another integration, is `404` `booking_not_found`. If both identifiers are sent they must be the same booking, or the response is `422`.

A `pending_payment` booking, including a price hold, is cancelled the same way an unpaid hold expires: the local booking, pending invoice, and pending transaction become `cancelled`, and the reward and membership reservations are released. `hms_bookings.status` becomes `cancelled`, with `cancelled_at` and `cancel_reason`. `refund` stays null. A later success payment is stored as `rejected_cancelled`.

A confirmed booking is cancelled with the same patient push the admin status change sends. A doctor goes through `DoctorBookingStatusService::updateBookingStatus`, which also dispatches `AppointmentCancelled`. A second opinion or package is set to `cancelled` and sends the admin cancellation push. Those admin screens do not dispatch a cancel automation event, and neither does this webhook. The paid invoice and transaction stay `completed`. The cancellation is recorded on `hms_bookings` only. HIP does not send the money back. When the booking was paid, `refund_status` is `refund_due` and `refund_amount` is the amount paid, and HMS refunds the patient. There is no refunded webhook. A zero-total booking leaves `refund_status` null. Consumed membership usage is deleted so the allowance returns. Consumed reward-tier discount usage is released. Coins earned on that invoice are debited through `CoinsWalletService::debit` (the balance stops at zero and a `reward_points_transitions` row records the change). `coins_reversed` is the number of coins actually taken from the wallet. Reward progress loses those coins plus the patient's current tier `earned_coins_per_booking` bonus. That bonus is read at cancellation time, not from a value stored when the booking was paid. The tier id is not lowered. Pending and expired cancels stay silent.

An already cancelled booking returns `200` with `state` `cancelled` and the stored refund values. Nothing is changed again. An expired booking returns `200` with `state` `expired` and nothing changes. A completed appointment is `409` `booking_not_cancellable`. An unknown booking is `404` `booking_not_found`. The same event id returns the stored response. A new event id for a booking that is already cancelled is a no-op `200`.

```json
{"status": true, "state": "cancelled", "booking_id": 101, "booking_type": "doctor", "refund": {"status": "refund_due", "amount": 545.4}, "coins_reversed": 5}
```

A pending cancel uses `"refund": null` and `"coins_reversed": 0`.

`POST /apiv1/client/membership/cancelled` calls `FamilyPackageService::cancelSubscription`, so the subscription status becomes `cancelled` and benefits stop. Usage logs and bookings that already used it stay. A pending HMS booking keeps its reservation and can still settle or expire. A repeat is `200`. An unknown id is `404` `membership_not_found`. HIP does not refund the fee.

```json
{"status": true, "subscription_id": 12, "state": "cancelled"}
```

## What this step does not do

No Razorpay order. Payment verification does not call `completeRazorpayPayment` and does not redeem coins. Existing app booking, payment, and pricing endpoints are unchanged. The quote is still not a hold. A booking webhook is the call that reserves membership usage and the reward discount.
