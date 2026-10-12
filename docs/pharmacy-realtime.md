# Pharmacy orders — push and Reverb

The patient app receives pharmacy pushes through the existing notification table and FCM. The pharmacist panel listens on private Reverb channels. `wire:poll` on the order page stays as the fallback when Reverb is down.

## Start Reverb and the queue worker

`.env.example` already contains `BROADCAST_CONNECTION=reverb`, the `REVERB_*` keys, and the `VITE_REVERB_*` keys. Copy those into `.env` (do not commit secrets). `ShouldBroadcastNow` sends during the request, so a queue worker is not required for these pharmacy events. Start one anyway for the rest of the app.

```bash
composer reverb
composer dev:realtime
```

`composer reverb` runs `php artisan reverb:start`. `composer dev:realtime` runs `php artisan serve`, `php artisan queue:listen --tries=1`, and `php artisan reverb:start` together. The existing `composer dev` script is unchanged.

The pharmacist layout initialises Echo only for a logged-in pharmacist, using the same Reverb host, port, scheme, app key, and `/broadcasting/auth` CSRF headers as the doctor layout. `/broadcasting/auth` already uses the `filament` session guard, which is the pharmacist panel guard.

## Channels and events

Events implement `ShouldBroadcastNow` (they are not queued).

| Event | Channel | When |
| --- | --- | --- |
| `PharmacyOrderCreated` | `private-pharmacy.{pharmacyId}` | A patient submits an order |
| `PharmacyOrderUpdated` | `private-pharmacy.{pharmacyId}` and `private-pharmacy-order.{id}` | Status or payment changes |
| `PharmacyOrderCommentAdded` | `private-pharmacy-order.{id}` | A patient or pharmacist comment is saved |

Payloads are limited to order id, order number, status, payment status, and for comments the comment id, body, author type, and time. Phone, address, and prescription URLs are not included.

`pharmacy.{pharmacyId}` is allowed when `PharmacistScopeService::pharmacyIds` contains that pharmacy for the pharmacist. `pharmacy-order.{id}` is allowed when `PharmacyOrderPolicy::pharmacistCanManage` is true (the policy has no `view` method). Everyone else is denied.

The orders list refreshes on `pharmacy.{pharmacyId}`. The order page refreshes on `pharmacy-order.{id}` and reloads comments. The existing 5-second `wire:poll` on that page remains.

A broadcast or push failure is logged and does not roll back the order.

## Push payload

Every pharmacy push is stored for the patient's family primary `hip_user_id` (the same person `createInvoiceForPayment` bills). With no registered devices the row is still stored. These keys are always present:

| Key | Value |
| --- | --- |
| `type` | `pharmacy_order` |
| `notification_type` | Event key below |
| `source` | `pharmacy` |
| `screen` | `config('hip.pharmacy_deeplink.screen')` |
| `route` | `{config route}/{order id}` |
| `url` | same path as `route` |
| `pharmacy_order_id` | Order id |
| `order_number` | Order number |
| `status` | Current order status |
| `payment_status` | Current payment status |

`config/hip.php` → `pharmacy_deeplink` currently sets `screen` to `prescription_details` and `route` to `/prescription-details`. The push appends the order id, so both `route` and `url` are `/prescription-details/{order id}`.

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

Sending a payment request passes `suppress_payment_push=true`, so the generic "New Invoice Created" push is not sent. No push is sent for `in_review`, for patient-authored comments, or when a transition does not change the status. Pushes and broadcasts run in `DB::afterCommit`.

Pending-payment invoice reminders (`PendingPaymentDetector` → `PaymentPending`) are unchanged. That event payload has no `pharmacy_order_id`, screen, or route.
