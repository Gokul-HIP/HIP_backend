# Pharmacy prescription orders API

Patient routes for submitting a prescription, following the order, commenting, cancelling, downloading the prescription, and reading the payment block on Prescription Details.

Pharmacist desk actions are not these routes. Paying the bill reuses the pay-bill and invoice payment APIs listed under Pay flow.

<!-- Pharmacist actions and payment are not in this phase. -->

Base path: `/api/pharmacy`

Authenticated routes use `Authorization: Bearer {sanctum token}`, middleware `auth:sanctum`, `account.active`, and `throttle:30,1`.

Success JSON:

```json
{ "status": 201, "message": "Prescription order submitted.", "data": {} }
```

Validation errors are HTTP 422 with `message` and `errors`. A deactivated account is HTTP 403:

```json
{ "message": "Your account has been deactivated. Please contact support." }
```

The client cannot send `status`, `payment_status`, `subtotal`, `discount`, `delivery_fee`, `tax`, `total`, or `invoice_id`.

## Statuses

| Value | Label |
|---|---|
| `requested` | Requested |
| `in_review` | In review |
| `pending_payment` | Pending payment |
| `processing` | Processing |
| `ready_for_delivery` | Ready for delivery |
| `dispatched` | Dispatched |
| `out_for_delivery` | Out for delivery |
| `delivered` | Delivered |
| `cancelled` | Cancelled |
| `rejected` | Rejected |

Payment statuses: `unpaid`, `paid`, `failed`, `refunded`.

`delivered`, `cancelled`, and `rejected` are terminal. Comments are closed on those. Cancel is allowed from `requested`, `in_review`, or `pending_payment`, and never after payment.

## GET /api/pharmacy/pharmacies

Auth: bearer token.

Query: `hospital_id` required integer, must exist.

Returns active pharmacies whose ids are in `hospitals.pharmacy_ids`. Empty `data` when none are active.

```json
{
  "status": 200,
  "message": "Pharmacies fetched.",
  "data": [
    {
      "id": 1,
      "name": "Nano Pharmacy",
      "address": "1 Market",
      "opening_time": "09:00",
      "closing_time": "21:00",
      "logo": null
    }
  ]
}
```

Errors: 422 if `hospital_id` is missing or unknown. 401 if unauthenticated. 403 if the account is inactive.

## POST /api/pharmacy/orders

Auth: bearer token. `multipart/form-data`.

| Field | Rules |
|---|---|
| `hospital_id` | required integer, existing hospital |
| `pharmacy_id` | optional integer. Normally omitted. If sent, it must be an active pharmacy listed in `hospitals.pharmacy_ids`. If omitted and the hospital has one active pharmacy, that pharmacy is used. If it has more than one, the active pharmacy with the lowest id is used |
| `patient_id` | optional uuid. See the family rule below. A person outside that family returns 403 `Patient not found in your family` |
| `note` | optional string, max 1000. Stored as the first patient comment |
| `delivery_name` | optional override, max 255. If omitted, copied from the selected person |
| `delivery_phone` | optional override, max 30. If omitted, copied from the selected person, then the family root, then `healthinpocket_users.mobile_num` |
| `delivery_address` | optional override, max 2000. If omitted, copied from `persons.address`, then the linked account's `house_number`, `street`, `city`, `state`, and `zip_code`. An empty result does not block the order |
| `prescription` | required file, max 10 MB. Real file MIME must be `image/jpeg`, `image/png`, `image/webp`, or `application/pdf` |

`patient_id` resolution (family first):

1. The caller's family is every `persons` row with `hip_user_id` equal to the authenticated user, plus dependents whose `parent_id` is in that set. The walk keeps a visited set and stops after 5 levels. A row with `parent_id` equal to its own id is visited once and is not walked again.
2. If `patient_id` is in that set, that person is used.
3. If `patient_id` equals the caller's own `healthinpocket_users.id`, the caller's primary person is used (`hip_user_id` = caller and `is_primary`). This does not select a different person who happens to share that uuid.
4. Anything else is HTTP 403 `Patient not found in your family`. A person id outside the family is never used.
5. Omitted `patient_id` uses the caller's primary person. Several primaries return 422 `Several profiles match this account. Send the person id.` No linked person returns 422 `Patient was not found. Send the person id.`
6. The stored `person_id` is always the resolved `persons.id`. Detail also returns `patient_user_id`, that person's `hip_user_id`, or null, and `patient` (`id`, `name`, `hip_id`).

Delivery values are stored as a copy on the order. Later profile edits do not change that copy. Name is `first_name` + `last_name`. Phone is `persons.mobile`, then the family root's mobile, then `healthinpocket_users.mobile_num` on the selected person and then on the family root. Address is `persons.address`, then the linked account address (`house_number`, `street`, `city`, `state`, `zip_code`, skipping blanks). There is no saved-addresses table. A sent delivery field replaces the profile value for that field only.

An address that is still empty is stored as null. The response sets `delivery.address` to null and `delivery.address_missing` to true so the app can ask the patient. It does not return 422. A name that is still empty is HTTP 422 `Please add a delivery name to this patient's profile or send delivery_name.` No phone on the person, the family root, or the account is HTTP 422 `No phone number on file for this patient`.

Smallest request (multipart): `hospital_id`, `prescription`, and optionally `note` and `patient_id`.

```text
hospital_id=1
prescription=@rx.jpg
note=Morning delivery
patient_id=019e8721-981e-706a-b6ec-5faf81acc374
```

The file is stored on the private disk at `pharmacy-prescriptions/{Y/m}/{uuid}.{ext}`. The `documents` row uses `document_type` `pharmacy_prescription`, the original file name, and null `member_id` and `patient_id`. `created_by` is the authenticated user. The order starts as `requested` / `unpaid`. A status log is written from null to `requested`.

HTTP 201. `data` is the order detail resource below.

Errors: 422 validation. A hospital with no active pharmacy returns `No pharmacy is linked to this hospital.` A pharmacy that is not an active assignment of that hospital is rejected. A file over 10 MB or with the wrong MIME is rejected. `patient_id` outside the caller's family, including another user's id, is HTTP 403 `Patient not found in your family`.

## GET /api/pharmacy/orders

Auth: bearer token.

Query: `status` optional (one of the status values), `person_id` optional uuid. A `person_id` outside the family returns an empty page.

Returns the caller's orders and orders for the family root of the person linked to the caller (that person, and persons whose `parent_id` is that root), newest first, 15 per page. A logged-in dependent uses that same rule. The list is not widened beyond it.

```json
{
  "status": 200,
  "message": "Prescription orders fetched.",
  "data": [
    {
      "id": 1,
      "order_number": "PH-261010-00001",
      "status": "requested",
      "status_label": "Requested",
      "payment_status": "unpaid",
      "person_name": "Ada One",
      "patient": { "id": "person-uuid", "name": "Ada One", "hip_id": "HIP00023" },
      "pharmacy_name": "Nano Pharmacy",
      "pharmacy": { "id": 1, "name": "Nano Pharmacy" },
      "hospital_name": "Nano Test",
      "delivery": {
        "name": "Ada One",
        "phone": "9000000001",
        "address": "12 Lake Road",
        "address_missing": false,
        "partner": null,
        "rider_name": null,
        "tracking_ref": null,
        "expected_at": null
      },
      "total": 1100,
      "can_pay": true,
      "prescription_download_url": "https://example.test/api/pharmacy/orders/1/prescription?expires=...&signature=...",
      "created_at": "2026-10-10T11:40:00+05:30",
      "unread_comments_count": 0
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 1 }
}
```

`unread_comments_count` counts pharmacist comments with no `read_at`.

`can_pay` is true only when the order status is `pending_payment` and the linked invoice status is `pending`.

`prescription_download_url` is a 10-minute signed link to `GET /api/pharmacy/orders/{id}/prescription`. It is null when the order has no prescription document. The URL does not contain the storage path.

## GET /api/pharmacy/orders/{id}

Auth: bearer token.

Returns the order, line items, billing, comments oldest first, timeline, and a 10-minute signed prescription URL. An order the caller cannot see is HTTP 404 `Order not found.` The same 404 is used when the id does not exist.

```json
{
  "status": 200,
  "message": "Prescription order fetched.",
  "data": {
    "id": 1,
    "order_number": "PH-261010-00001",
    "status": "requested",
    "status_label": "Requested",
    "payment_status": "unpaid",
    "person_id": "uuid",
    "patient_user_id": "uuid-or-null",
    "person_name": "Ada One",
    "patient": { "id": "person-uuid", "name": "Ada One", "hip_id": "HIP00023" },
    "hospital_id": 1,
    "hospital_name": "Nano Test",
    "pharmacy_id": 1,
    "pharmacy_name": "Nano Pharmacy",
    "pharmacy": { "id": 1, "name": "Nano Pharmacy" },
    "delivery_name": "Ada One",
    "delivery_phone": "9000000001",
    "delivery_address": "12 Lake Road",
    "items": [
      {
        "name": "Paracetamol",
        "strength": "500 mg",
        "dosage_form": "Tablet",
        "dosage": "1 Tablet",
        "frequency": "Once Daily",
        "duration": "5 Days",
        "when_to_take": "After Food",
        "special_instruction": null,
        "qty": 2,
        "unit_price": 500,
        "line_total": 1000
      }
    ],
    "subtotal": 1000,
    "discount": 0,
    "delivery_fee": 0,
    "tax": 50,
    "total": 1100,
    "invoice_id": 200,
    "payment": {
      "invoice_id": 200,
      "invoice_status": "pending",
      "amount_due": 1100,
      "amount_paid": 0,
      "coins_applied": 0,
      "coins_discount": 0,
      "method": null,
      "paid_on": null,
      "transaction_ref": null,
      "status_label": "pending",
      "attempted_at": null,
      "breakdown": {
        "subtotal": 1000,
        "discount": 0,
        "delivery_fee": 0,
        "gst": 50,
        "service_charges": 30,
        "gateway_charges": 20,
        "total": 1100
      },
      "can_pay": true,
      "retry": false,
      "invoice_pdf_url": null
    },
    "delivery": {
      "name": "Ada One",
      "phone": "9000000001",
      "address": "12 Lake Road",
      "address_missing": false,
      "partner": null,
      "rider_name": null,
      "tracking_ref": null,
      "expected_at": null
    },
    "comments": [
      {
        "id": 1,
        "author_type": "patient",
        "author_name": "Ada Patient",
        "is_mine": true,
        "body": "Morning delivery",
        "created_at": "2026-10-10T11:40:00+05:30",
        "read": false
      }
    ],
    "steps": [
      { "key": "verified", "label": "Verified", "state": "pending", "at": null },
      { "key": "packed", "label": "Packed", "state": "pending", "at": null },
      { "key": "dispatched", "label": "Dispatched", "state": "pending", "at": null },
      { "key": "out_for_delivery", "label": "Out for delivery", "state": "pending", "at": null },
      { "key": "delivered", "label": "Delivered", "state": "pending", "at": null }
    ],
    "timeline": [
      {
        "from_status": null,
        "to_status": "requested",
        "label": "Requested",
        "note": null,
        "at": "2026-10-10T11:40:00+05:30"
      }
    ],
    "prescription": {
      "file_name": "rx.jpg",
      "mime": "image/jpeg",
      "download_url": "https://example.test/api/pharmacy/orders/1/prescription?expires=...&signature=..."
    },
    "created_at": "2026-10-10T11:40:00+05:30"
  }
}
```

The download URL does not contain the storage path.

### Payment block

`payment` is always present.

| Field | Meaning |
|---|---|
| `invoice_id` | Linked invoice, or null before a payment request |
| `invoice_status` | `invoices.status` (`pending`, `completed`, `cancelled`, …) |
| `amount_due` | `invoices.total_amount` (GST, service charge, and gateway charge included) |
| `amount_paid` | Completed `transactions.total_amount`, otherwise the captured Razorpay `amount_paid`, otherwise `0` |
| `coins_applied` | `invoices.coins_applied` |
| `coins_discount` | `transactions.discount_amount` for the latest transaction |
| `method` | Latest non-empty `razorpay_payments.payment_method` for the invoice. Otherwise `invoices.payment_method`. The literal `razorpay` on the invoice is a gateway name and is returned as null |
| `paid_on` | Order `paid_at` |
| `transaction_ref` | `TXN-` plus the `transactions.id` padded to 8 digits. This is the Transaction ID shown in the app |
| `status_label` | Display value: `unpaid`, `pending`, `paid`, `failed`, or `refunded`. `pending` when the order is `pending_payment` or the invoice is `pending`, unless payment is already `paid`, `failed`, or `refunded` |
| `attempted_at` | ISO time of the latest `razorpay_payments` row for the invoice (`created_at`, or `updated_at` when `created_at` is empty). If there is no Razorpay row, the latest transaction's `updated_at`. Otherwise null |
| `breakdown` | `{ subtotal, discount, delivery_fee, gst, service_charges, gateway_charges, total }`. `subtotal - discount + delivery_fee + gst + service_charges + gateway_charges` equals `total` within 0.01. Before an invoice, every figure comes from `PharmacyBillCalculator`. After an invoice, `gst` is `invoices.total_gst`, `service_charges` is `invoices.service_charges`, `gateway_charges` is `invoices.payment_gateway_charges`, and `total` is `invoices.total_amount`. `subtotal`, `discount`, and `delivery_fee` stay the order figures the invoice was built from |
| `can_pay` | Order status is `pending_payment` and the invoice status is `pending` |
| `retry` | Order `payment_status` is `failed` |
| `invoice_pdf_url` | 1-day signed `hospital.pay-bill-invoice-download` URL, only when the invoice status is `completed` |

`delivery.name`, `delivery.phone`, and `delivery.address` are the snapshot stored on the order. `delivery.address` is null and `delivery.address_missing` is true when no address could be copied. `delivery.partner`, `delivery.rider_name`, `delivery.tracking_ref`, and `delivery.expected_at` stay null until the pharmacist records a dispatch.

### Steps

`steps` is the fixed Prescription Details timeline. It is separate from `timeline`, which remains the raw status log.

| key | label | reached when the order has entered |
|---|---|---|
| `verified` | Verified | `in_review` |
| `packed` | Packed | `ready_for_delivery` |
| `dispatched` | Dispatched | `dispatched` |
| `out_for_delivery` | Out for delivery | `out_for_delivery` |
| `delivered` | Delivered | `delivered` |

Each step is `{ key, label, state, at }`. `state` is `done`, `active`, or `pending`. `at` is the ISO time from `pharmacy_order_status_logs` (or the matching timestamp column), or null. `requested` leaves every step `pending`. `pending_payment` and `processing` mark Verified `done`. `delivered` marks all five `done`. `cancelled` and `rejected` replace the five steps with one terminal step, `state` `done`.

### Comments

Each comment keeps `id`, `author_type` (`patient` or `pharmacist`), `author_name`, `body`, `created_at`, and `read`. `is_mine` is true when `author_id` is the authenticated user.

After payment, `invoice_status` is `completed`, `can_pay` is false, `retry` is false, `paid_on` is set, and `invoice_pdf_url` is present. A failed Razorpay attempt sets `retry` true and leaves `can_pay` true while the invoice is still `pending`.

### Payment states

These four `payment` objects are the resource output for `GET /api/pharmacy/orders/{id}` on throwaway rows (subtotal ₹1,000, GST 5%, service 3%, gateway 2%). Show the amounts with ₹.

Unpaid, no invoice. `attempted_at` is null. `breakdown` comes from `PharmacyBillCalculator`.

```json
{
  "invoice_id": null,
  "invoice_status": null,
  "amount_due": null,
  "amount_paid": 0,
  "coins_applied": 0,
  "coins_discount": 0,
  "method": null,
  "paid_on": null,
  "transaction_ref": null,
  "status_label": "unpaid",
  "attempted_at": null,
  "breakdown": {
    "subtotal": 1000,
    "discount": 0,
    "delivery_fee": 0,
    "gst": 50,
    "service_charges": 30,
    "gateway_charges": 20,
    "total": 1100
  },
  "can_pay": false,
  "retry": false,
  "invoice_pdf_url": null
}
```

Pending. A bill is waiting and `can_pay` is true.

```json
{
  "invoice_id": 322,
  "invoice_status": "pending",
  "amount_due": 1100,
  "amount_paid": 0,
  "coins_applied": 0,
  "coins_discount": 0,
  "method": null,
  "paid_on": null,
  "transaction_ref": null,
  "status_label": "pending",
  "attempted_at": null,
  "breakdown": {
    "subtotal": 1000,
    "discount": 0,
    "delivery_fee": 0,
    "gst": 50,
    "service_charges": 30,
    "gateway_charges": 20,
    "total": 1100
  },
  "can_pay": true,
  "retry": false,
  "invoice_pdf_url": null
}
```

Paid. `paid_on` and `invoice_pdf_url` are set. Synthetic sample; real values to be confirmed after a Razorpay test-mode payment. In this sample `amount_paid` is 0 because no completed transaction was stored.

```json
{
  "invoice_id": 322,
  "invoice_status": "completed",
  "amount_due": 1100,
  "amount_paid": 0,
  "coins_applied": 0,
  "coins_discount": 0,
  "method": "upi",
  "paid_on": "2026-10-12T11:58:12+05:30",
  "transaction_ref": null,
  "status_label": "paid",
  "attempted_at": "2026-10-12T11:48:06+05:30",
  "breakdown": {
    "subtotal": 1000,
    "discount": 0,
    "delivery_fee": 0,
    "gst": 50,
    "service_charges": 30,
    "gateway_charges": 20,
    "total": 1100
  },
  "can_pay": false,
  "retry": false,
  "invoice_pdf_url": "https://example.test/api/hospital/pay-bill-invoice-download/322?expires=...&signature=..."
}
```

Failed. `retry` is true and `attempted_at` is the Razorpay row's `created_at`. `can_pay` stays true while the invoice is `pending`.

```json
{
  "invoice_id": 322,
  "invoice_status": "pending",
  "amount_due": 1100,
  "amount_paid": 0,
  "coins_applied": 0,
  "coins_discount": 0,
  "method": "upi",
  "paid_on": null,
  "transaction_ref": null,
  "status_label": "failed",
  "attempted_at": "2026-10-12T11:48:06+05:30",
  "breakdown": {
    "subtotal": 1000,
    "discount": 0,
    "delivery_fee": 0,
    "gst": 50,
    "service_charges": 30,
    "gateway_charges": 20,
    "total": 1100
  },
  "can_pay": true,
  "retry": true,
  "invoice_pdf_url": null
}
```

`payment.method` uses the latest non-empty `razorpay_payments.payment_method`. If that is empty, it uses `invoices.payment_method`, except the literal `razorpay`, which is returned as null. Values present in this database: `upi`, `netbanking`, `cash`, `online`, `free`. `razorpay_payments.payment_method` currently has `upi` and `netbanking`. `invoices.payment_method` has `razorpay`, `cash`, `online`, and `free`. `transactions.payment_method` has `razorpay`, `netbanking`, `cash`, `online`, `free`, and `upi`. Card and wallet are separate Razorpay columns (`card_network`, `wallet`), not values of `payment.method` in the current rows. Label `payment.method` without appending the bank name.

### Push notification types

Patient pushes are documented in [pharmacy-realtime.md](pharmacy-realtime.md). `notification_type` values:

| Event | `notification_type` | Title |
| --- | --- | --- |
| Payment request sent | `payment_requested` | Payment request for your prescription order |
| Payment received | `payment_received` | Payment received |
| Payment failed | `payment_failed` | Payment failed. Tap to retry |
| Ready for delivery | `ready_for_delivery` | Your prescription order is ready for delivery |
| Dispatched | `dispatched` | Your prescription order has been dispatched |
| Out for delivery | `out_for_delivery` | Your prescription order is out for delivery |
| Delivered | `delivered` | Your prescription order was delivered |
| Cancelled | `cancelled` | Your prescription order was cancelled |
| Rejected | `rejected` | Your prescription order was rejected |
| Pharmacist message | `pharmacist_comment` | New message from the pharmacy |

The payload `screen` is `prescription_details`. `route` and `url` are `/prescription-details/{order id}`.

### Screen mapping

| Screen element | API field |
| --- | --- |
| Prescription Details title / order number | `order_number` |
| Status chip | `status_label` |
| Patient name and HIP id | `patient.name`, `patient.hip_id` |
| Pharmacy name | `pharmacy.name` |
| Five-step tracker | `steps` (`key`, `label`, `state`, `at`) |
| Medicines and dosing | `items` |
| Delivery address prompt | `delivery.address`, `delivery.address_missing` |
| Courier block | `delivery.partner`, `delivery.rider_name`, `delivery.tracking_ref`, `delivery.expected_at` |
| Comments, mine vs pharmacy | `comments`, `comments[].is_mine`, `comments[].author_type` |
| Prescription file | `prescription.download_url` |
| Unpaid / pending / failed state | `payment.status_label` |
| Pay button | `payment.can_pay` |
| Retry | `payment.retry`, `payment.attempted_at` |
| Paid time | `payment.paid_on` |
| Bill lines on Prescription Details | `payment.breakdown` (Subtotal, discount, delivery, GST, service, gateway, total) |
| Bill Details title "Pharmacy" | pay-bill `pharmacy_order_id` |
| Bill Details Subtotal | `subtotal` (`invoices.amount` on pay-bill, or `payment.breakdown.subtotal`) |
| Bill Details GST | `total_gst` or `payment.breakdown.gst` |
| Bill Details Service | `service_charges` |
| Bill Details Gateway | `payment_gateway_charges` or `payment.breakdown.gateway_charges` |
| Bill Details Total | `total_amount` or `payment.breakdown.total` |
| Bill Details method | `payment.method` |
| Invoice PDF | `payment.invoice_pdf_url` or pay-bill `invoice_download_url` |

### Hospital bill details

`GET /api/hospital/pay-bill-details/{invoice_id}` keeps every existing key. `amount` is still the grand total (`invoices.total_amount`). Added keys:

| Field | Source |
|---|---|
| `subtotal` | `invoices.amount` (medicine base after the order discount and delivery fee, before GST, service, and gateway) |
| `total_gst` | `invoices.total_gst` |
| `service_charges` | already returned |
| `payment_gateway_charges` | `invoices.payment_gateway_charges` |
| `total_amount` | `invoices.total_amount` |
| `pharmacy_order_id` | only on a pharmacy-order invoice |
| `order_number` | only on a pharmacy-order invoice |

The app should display subtotal, GST, service charge, gateway charge, and total. Pharmacy invoices are created with `is_in_patient` false, so they appear on the outpatient bill list (`type=out_patient`), which filters `primary_person_id` of the caller's primary person and `is_in_patient` false. Bill details use that same `primary_person_id`.

```json
{
  "amount": 1100,
  "service_charges": 30,
  "subtotal": 1000,
  "total_gst": 50,
  "payment_gateway_charges": 20,
  "total_amount": 1100,
  "pharmacy_order_id": 1,
  "order_number": "PH-261010-00001"
}
```

## POST /api/pharmacy/orders/{id}/comments

Auth: bearer token.

Body JSON: `body` required string, max 1000.

Allowed until the order is `delivered`, `cancelled`, or `rejected`. `author_type` is `patient`.

HTTP 201. `data` is one comment object (`id`, `author_type`, `author_name`, `is_mine`, `body`, `created_at`, `read`). The detail resource uses the same comment fields. `is_mine` is true when the author is the logged-in user.

Errors: 404 if the order is not visible. 422 if comments are closed or `body` is missing.

## POST /api/pharmacy/orders/{id}/comments/read

Auth: bearer token. No body.

Marks unread pharmacist comments on that order as read.

```json
{ "status": 200, "message": "Comments marked as read.", "data": { "updated": 1 } }
```

404 if the order is not visible.

## POST /api/pharmacy/orders/{id}/cancel

Auth: bearer token.

Body JSON: `reason` optional string, max 1000.

Allowed from `requested`, `in_review`, or `pending_payment` only. If a linked invoice is `pending`, it is set to `cancelled` in the same transaction.

HTTP 200. `data` is the order detail resource with `status` `cancelled`.

Errors: 404 if the order is not visible. 422 `This order can no longer be cancelled.` after payment or once the order is `processing` or later.

## GET /api/pharmacy/orders/{id}/prescription

No bearer token. The URL must be the signed `download_url` from the order detail. It expires after 10 minutes.

The response streams the private file inline with the detected `Content-Type`, `Content-Disposition: inline`, and `Cache-Control` including `no-store`. A missing, tampered, or expired signature is HTTP 403. A missing file is HTTP 404. The storage path is never returned.

## Pay flow

There is no separate pharmacy checkout. When `payment.can_pay` is true, the app reuses the existing bill APIs:

- `GET /api/hospital/pay-bill-list`
- `GET /api/hospital/pay-bill-details/{invoice_id}`
- `GET /api/hospital/pay-bill-invoice-download/{invoice_id}` is a 1-day signed URL with no Bearer token. A missing, tampered, expired, or rewritten invoice id returns 403. The PDF is `Content-Type: application/pdf` and `Content-Disposition: attachment` with filename `Invoice-<order number, or invoice id>.pdf`. `payment.invoice_pdf_url` and pay-bill `invoice_download_url` are that same route. `invoice_pdf_url` is null until the invoice is `completed`. `invoice_download_url` is present on bill details before payment as well. After payment, use `payment.invoice_pdf_url` on the order screen and `invoice_download_url` on the bill screen.
- `POST /api/invoices/{invoice_id}/pay`
- `POST /api/invoices/verify-payment`
- `GET /api/payment-requests/{invoice_id}`
- `POST /api/payment-requests/{invoice_id}/apply-coins`
- `POST /api/payment-requests/{invoice_id}/pay`

Show the bill with ₹:

| Label | Field | Example |
|---|---|---|
| Subtotal | `subtotal` | ₹1,000.00 |
| GST | `total_gst` | ₹50.00 |
| Service | `service_charges` | ₹30.00 |
| Gateway | `payment_gateway_charges` | ₹20.00 |
| Total | `total_amount` | ₹1,100.00 |

`amount` on the bill details response is the same grand total as `total_amount`.

Pharmacy coin preview is `GET /api/home/user-coins`. Optional query `invoice_id` (integer). Optional query `coins_used` (integer, min 0, default 0). When `invoice_id` is a pharmacy invoice owned by the caller's primary person, `consultation_fee` is ignored. The base is `invoices.total_amount`. Coins used are `min(requested, wallet, floor(total / amount_for_one_coin))`. `coin_discount` is coins × ₹10 (`app_setting` `amount_for_one_coin`). `service_charge` is 0. `total_amount` in that response is the bill total minus the coin discount. `consultation_fee` echoes the bill total. GST, the stored service charge, and the gateway charge are not recalculated. Any other `invoice_id`, or a call with only `consultation_fee`, keeps the existing consultation preview.

`GET /api/hospital/pay-bill-details/{invoice_id}` accepts optional query `coins_applied` (integer, min 0). For a pharmacy invoice the Razorpay amount is the bill total minus those coins × ₹10, and `razorpay_payments.coins_applied` stores the capped count. The same count reuses the pending order. A different count cancels the pending `transactions` row (`cancelled` already exists on that model) and creates a new Razorpay order. `razorpay_payments` has no cancelled status in this code (`created`, `failed`, `captured` only), so the superseded Razorpay row stays `created`. Non-pharmacy bills ignore `coins_applied` and still open Razorpay for the full invoice total.

| Coins requested | `user-coins` with `invoice_id` | Razorpay rupees |
|---|---|---|
| 0 | `coins_used` 0, `coin_discount` 0, `service_charge` 0, `total_amount` 1100, `consultation_fee` 1100 | 1100 |
| 10 | `coins_used` 10, `coin_discount` 100, `service_charge` 0, `total_amount` 1000, `consultation_fee` 1100, `remaining_coins` wallet − 10 | 1000 |

`verify-payment` is unchanged. It applies the coin count stored on the Razorpay row, so a pharmacy order opened with 10 coins records `amount_paid` 1000, `coins_applied` 10, and a ₹100 discount. The order detail then shows `amount_due` 1100, `coins_discount` 100, `amount_paid` 1000. The tax invoice placeholders for a pharmacy invoice use the bill total as `original_amount`, the coin discount as `discount_amount`, and the paid amount as `invoice_total`.

## Deep link

Pharmacy pushes use `config/hip.php` `pharmacy_deeplink`: screen `prescription_details`. `route` and `url` append the order id, the same way a doctor review uses `/review/{doctor id}`.

```json
{
  "type": "pharmacy_order",
  "notification_type": "payment_requested",
  "source": "pharmacy",
  "screen": "prescription_details",
  "route": "/prescription-details/1",
  "url": "/prescription-details/1",
  "pharmacy_order_id": "1",
  "order_number": "PH-261010-00001",
  "status": "pending_payment",
  "payment_status": "unpaid"
}
```

Open `screen` `prescription_details` at route `/prescription-details/{order id}`.

## Frontend notes

- Show amounts with ₹. Do not show $.
- Show `payment.method` as the method only. Do not append the bank name.
- When pay-bill details include `pharmacy_order_id`, title that bill page "Pharmacy".
- On a pharmacy bill, draw the service line from pay-bill details `service_charges`. Do not draw it from `user-coins` `service_charge`, which is 0 for a pharmacy invoice. Send `invoice_id` and `coins_used` to `GET /api/home/user-coins` for the coin preview, and the same coin count as `coins_applied` on `GET /api/hospital/pay-bill-details/{invoice_id}` before opening Razorpay.
- Prescription Details has three payment states from `payment.status_label`: `pending` (a bill is waiting; `can_pay` is true while the invoice is `pending`), `unpaid` (no bill yet), and `failed` (`retry` is true). `paid` and `refunded` are the other `status_label` values.
- Send `hospital_id` and `prescription`. Omit `pharmacy_id` unless the patient must pick among several pharmacies. Omit `patient_id` to order for the logged-in user's primary person. Send `delivery_name`, `delivery_phone`, or `delivery_address` only to override the profile copy.
- If `delivery.address_missing` is true, ask the patient for an address. The order is already created.
- Both hospitals in the current database have exactly one pharmacy, so auto-select is the normal path. Zero hospitals currently have more than one pharmacy. The lowest-active-id rule is what the API uses when a hospital does.
