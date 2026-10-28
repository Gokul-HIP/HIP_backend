# HMS client API (step 1)

Read-only endpoints for a hospital management system. This step does not create bookings, take payment, or call Razorpay. Booking and payment webhooks will be added later on the same route group.

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

Optional `X-HMS-Event-Id`: the first request is stored in `hms_webhook_inbox`. A repeat of the same id for that integration returns the stored response and is logged as `duplicate`.

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
| 422 | `validation_error` | Required field missing or `booking_type` / `original_fee` is invalid |
| 429 | `rate_limited` | Over `rate_limit_per_minute` (default 120) |

Disabled and IP failures use `invalid_signature` so the code list stays the one above. The `message` says which check failed. A disabled integration is HTTP 403, not 401, because the signature itself was valid.

## Inbox

`hms_webhook_inbox` stores every request, including GET. `X-HMS-Key` and `X-HMS-Signature` are stored as `[redacted]`. Successful reads are `processed`. Failures are `failed`.

```bash
php artisan hms:inbox:prune --days=90
```

That command deletes inbox rows whose `received_at` is older than 90 days. It is scheduled every day at 01:10. `--days` must be at least 1.

## What this step does not do

No booking creation, no payment, no Razorpay calls, and no changes to the existing app membership, reward, or pricing endpoints. The quote is not a hold on consultations, coins, or tier discounts.
