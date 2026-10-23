# Reward Tiers & Membership Packages

How loyalty tiers and paid memberships work in HealthInPocket (HIP), what each admin field means, and how benefits are applied when a patient books.

> **Naming:** The admin UI says **Membership Packages**. In code and APIs the same product is **Family Packages** (`FamilyPackage`, `UserFamilySubscription`, `/api/family-packages`). There is no `MembershipPackage` model.

---

## Table of contents

1. [Overview — two different benefit systems](#1-overview--two-different-benefit-systems)
2. [How pricing stacks (important)](#2-how-pricing-stacks-important)
3. [Reward Tiers](#3-reward-tiers)
4. [Membership Packages](#4-membership-packages)
5. [HIP Coins vs tier progress coins](#5-hip-coins-vs-tier-progress-coins)
6. [Patient APIs](#6-patient-apis)
7. [Worked examples](#7-worked-examples)
8. [What is not implemented yet](#8-what-is-not-implemented-yet)
9. [Key code files](#9-key-code-files)

---

## 1. Overview — two different benefit systems

| | **Reward Tiers** | **Membership Packages** |
|---|---|---|
| What it is | Free loyalty ladder (Bronze → Silver → Gold, etc.) | Paid subscription plan for a family |
| How you get it | Automatically by earning **progress coins** | Buy / staff-activate a package |
| Time limit | No expiry; no demotion when spending wallet coins | Yes — `duration_days`, then expires |
| Main benefit style | % discount, package promos, extra progress coins | Free consultations + free selected diagnostic packages (limits) |
| Admin page | Settings → Reward Tiers | Membership Packages |
| Core service | `RewardTierService` | `MembershipEntitlementService` + `FamilyPackageService` |

Patients can have **both** at once. They do not replace each other.

---

## 2. How pricing stacks (important)

For **doctor consultations** and **diagnostic packages**, the backend always prices in this order:

```text
1. Start with original base fee (consultation fee or package fee)
2. Apply membership (if eligible) → may waive the whole base fee
3. Apply reward-tier benefit on whatever base remains
      - Diagnostic: package-specific free/discount first, else tier %
      - Doctor: tier % only
4. Apply HIP Coins (spendable wallet) on what is still left
5. Add service charge on the discounted base
      (if base becomes ₹0, service charge is calculated on the original fee)
6. Final payable = discounted base + service charge
```

**Rules that always hold:**

- Membership and reward benefits apply to the **base fee only**, never to service charge by themselves.
- Membership free benefit and reward free package do **not** double-waive the same fee (membership runs first; if base is already ₹0, reward has nothing left to discount).
- Discount math is **server-side**. The client cannot invent a cheaper total; Razorpay orders use the server-calculated payable amount.

---

## 3. Reward Tiers

### 3.1 What a tier is

A reward tier is one step on the loyalty ladder. Admins create tiers such as:

| Sort | Name | Min coins (to unlock this tier) |
|---|---|---|
| 1 | Bronze | 0 |
| 2 | Silver | 100 |
| 3 | Gold | 500 |

Patients start on the first active tier (`sort_order = 1`, usually Bronze) when they register.

### 3.2 Admin fields — tier itself

Configured on **Admin → Settings → Reward Tiers** (also Healthcare Admin).

| Field (UI) | Database | What it means | What it does at runtime |
|---|---|---|---|
| **Name** | `reward_tiers.name` | Display label | Shown to patients in reward-progress API |
| **Description** | `reward_tiers.description` | Optional notes | Stored for admin/display; not used in pricing math |
| **Minimum Coins Required** | `reward_tiers.min_coins` | Coins needed **while on the previous tier** to unlock **this** tier | Compared to `user_reward_progress.earned_coins_in_tier`. Example: Silver `min_coins = 100` means the patient must earn 100 progress coins on Bronze before upgrading |
| **Sort Order** | `reward_tiers.sort_order` | Ladder position | Controls display order and “next tier” selection. Default entry tier is active with `sort_order = 1` |
| **Active / Inactive** | `reward_tiers.is_active` | Whether the tier participates | Inactive tiers are skipped for upgrades and next-tier progress. Inactive current tier grants **no** discount / booking bonus |

### 3.3 Admin fields — tier benefits (config)

Stored in `reward_tier_configs` (one row per tier).

| Field (UI) | Database | What it means | What it does at runtime |
|---|---|---|---|
| **Total Discount %** | `total_discount_percentage` | Percent off remaining **base** price | Applied after membership on doctor bookings and on diagnostic packages that have **no** package-specific promotion. Example: 5% of ₹1,000 = ₹50 off → base ₹950. Service charge is then calculated on ₹950 |
| **Free Checkups** | `free_checkup_count` | Intended free checkup allowance | **Saved and returned in the patient API for display only.** Not enforced in booking yet (see [§8](#8-what-is-not-implemented-yet)) |
| **Coins Earned per Booking** | `earned_coins_per_booking` | Extra progress coins per successful paid booking | Added to **tier progress** (not the spendable wallet) after payment succeeds. Example: standard 1% earn = 10, bonus = 2 → **12 progress coins** |

### 3.4 Admin fields — package promotions

Rows in `reward_tier_package_discounts`. Each row targets **one** diagnostic package for patients currently on that tier.

| Field (UI) | Database | What it means | What it does at runtime |
|---|---|---|---|
| **Package** | `diagnostic_package_id` | Which diagnostic package | Only that package gets the promo. Other packages fall back to Total Discount % |
| **Promotion type** | `promotion_type` | `free` or `discount` | `free` → waive remaining base fee for that package. `discount` → reduce base by percentage or flat amount |
| **Discount type** | `discount_type` | `percentage` or `flat` (only when promotion is `discount`) | How `discount_value` is read |
| **Discount value** | `discount_value` | e.g. `20` for 20%, or `100` for ₹100 off | Applied to remaining base after membership |

**Priority for a diagnostic booking on a reward tier:**

1. Matching package row with `free`
2. Matching package row with `discount`
3. Else tier `total_discount_percentage`

Doctor consultations never use package rows — only Total Discount %.

### 3.5 How tier progression works

Progress lives in `user_reward_progress`:

| Column | Meaning |
|---|---|
| `reward_tier_id` | Current tier |
| `earned_coins_in_tier` | Progress toward the **next** tier (resets to `0` on upgrade) |
| `total_lifetime_coins` | Lifetime progress coins earned (never resets; informational) |

**Upgrade rule (Silver example):**

```text
Patient is on Bronze.
Silver.min_coins = 100.
Patient earns progress coins until earned_coins_in_tier >= 100.
→ Patient becomes Silver.
→ earned_coins_in_tier resets to 0.
→ RewardTierUpgraded event is fired (automation can listen).
```

**Important:**

- Threshold uses **progress coins**, not wallet balance.
- Spending HIP Coins from the wallet does **not** demote the tier.
- There is no tier expiry.
- Multiple upgrades can happen in one award if enough coins are added at once.
- Inactive next tiers are skipped (e.g. inactive Silver → jump toward Gold when threshold met).

### 3.6 When progress coins are awarded

Progress is awarded **only after a booking is paid** (`payment_status = paid`), including free/zero-payable confirmed bookings.

```text
progress_coins =
    round(payable_amount × 0.01)     // same 1% basis as wallet earn
  + earned_coins_per_booking         // tier bonus → progress only
```

| Path | What happens |
|---|---|
| Online booking, payment pending | No progress yet |
| Razorpay / payment finalize succeeds | Wallet gets 1%; progress gets 1% + tier bonus |
| Free booking (payable ₹0, confirmed paid) | Wallet gets 0; progress still gets tier bonus if configured |
| Failed / abandoned payment | No coins, no progress |

The spendable wallet and tier progress are updated together on the payment path, but the **tier bonus is progress-only**.

### 3.7 Where reward benefits apply today

| Booking type | Total Discount % | Package promotions | Coins-per-booking bonus |
|---|---|---|---|
| Doctor appointment | Yes | N/A | Yes (on paid) |
| Diagnostic package | Yes (fallback) | Yes | Yes (on paid) |
| Second opinion | No (legacy pricing) | No | Yes (on paid) |

---

## 4. Membership Packages

### 4.1 What a membership is

A **Family Package** is a purchasable plan. When a patient (or staff) activates it, a row is created in `user_family_subscriptions` with a start/end date. While `status = active` and `end_date` is not past, the patient can use plan entitlements.

### 4.2 Admin fields — package

Configured on **Admin → Membership Packages** (also Healthcare Admin).

| Field (UI) | Database | What it means | What it does at runtime |
|---|---|---|---|
| **Name** | `family_packages.name` | Plan name | Shown in lists and APIs |
| **Description** | `description` | Marketing / detail text | Display |
| **Price** | `price` | Plan price | Used for purchase / invoices |
| **Duration (days)** | `duration_days` | How long the subscription lasts | `end_date = start_date + duration_days` |
| **Max members** | `max_members` | How many family members the plan can cover | Enforced when staff set covered members |
| **Max consultations** | `max_consultations` | Free doctor consultations allowed while subscribed | `0` = unlimited. Each eligible booked consultation consumes allowance via usage logs. When exhausted, consultation is charged normally |
| **Max lab tests** (legacy column) | `max_lab_tests` | Older package-level lab limit | Prefer **per-package benefits** below. Still stored for compatibility / labels |
| **Max HIP coins** | `max_hip_coins` | Configured coin entitlement figure | Exposed in usage summaries; **not** auto-credited into the patient wallet as a live entitlement engine today |
| **Branches** | `branch_ids` | Hospitals/branches where the plan is valid | Empty/null = all branches. If set, booking at a non-listed branch is not eligible for free benefits |
| **Plan benefits** | `benefits` (JSON) | List of diagnostic packages included as free (with per-row lab limits) | See next section |
| **Terms & conditions** | `terms_conditions` | Legal / marketing terms | Display |
| **Active** | `is_active` | Whether patients can subscribe | Inactive packages are hidden from active listings |
| **Sort order** | `sort_order` | Display order | Listing order |

### 4.3 Plan benefits (diagnostic package rows)

Each benefit row in the admin form becomes one object in `family_packages.benefits`:

```json
{
  "diagnostic_package_id": 42,
  "benefit_type": "free",
  "max_lab_tests": 2
}
```

| Field | Meaning | Runtime effect |
|---|---|---|
| **Diagnostic package** | Which package is covered | Only this package can be booked free under membership. Other packages are charged normally (reward tier may still discount them) |
| **Benefit type** | Currently `free` | Waives the **package base fee** when eligible |
| **Max lab tests** | How many times this package can be used free | `0` = unlimited for that package. Counted from `subscription_usage_logs` for that package. When exhausted → charged normally |

**Branch filter in the form:** Only diagnostic packages available at the selected branches can be chosen. Changing branches clears invalid rows.

**Disease packages** are not covered by membership free benefits.

### 4.4 What membership does on booking

#### Doctor consultation

Eligible when:

- Active subscription exists
- Branch allowed (if package restricts branches)
- Consultation allowance remaining (`max_consultations`; `0` = unlimited)

**Effect:** Base consultation fee waived. **Service charge remains payable.**

Usage is reserved / logged so the allowance cannot be silently exceeded.

#### Diagnostic package

Eligible when:

- Active subscription
- Branch allowed
- Package exists at that branch
- Package is listed in plan `benefits` with `benefit_type = free`
- Per-package `max_lab_tests` not exhausted

**Effect:** Package base fee waived. **Service charge remains payable.**

### 4.5 Subscription lifecycle

| Status | Meaning |
|---|---|
| `active` | Entitlements apply (if dates valid) |
| `pending` | Awaiting payment activation (staff/payment flows) |
| `expired` | Past `end_date` (scheduler / expire job marks these) |
| `cancelled` | Cancelled by user/staff |

Daily jobs expire stale subscriptions (`expire-subscriptions` / `subscriptions:expire`).

### 4.6 Usage tracking

`subscription_usage_logs` records consumption (`consultation`, `lab_test`, etc.) with notes such as `membership_reserved` / `membership_consumed`.

- Reserve on booking when membership benefit applied
- Confirm / release depending on payment success, cancel, or abandon paths in booking/payment services

---

## 5. HIP Coins vs tier progress coins

| | **Spendable HIP Coins (wallet)** | **Reward tier progress coins** |
|---|---|---|
| Table | `coins` (and related user balance fields) | `user_reward_progress` |
| Used for | Paying part/all of a booking | Unlocking higher reward tiers |
| Earned how | Typically ~1% of amount actually paid | Same 1% **plus** tier `earned_coins_per_booking` bonus |
| Spent how | Applied as `coins_discount` on remaining base | Not spendable |
| Affects tier? | No | Yes |

**Do not treat these as the same number in the app UI.**

---

## 6. Patient APIs

### Reward progress

`GET /api/user/reward-progress` (authenticated)

Typical payload fields:

| Field | Meaning |
|---|---|
| `current_tier_name` | Current loyalty tier |
| `next_tier_name` | Next active tier, if any |
| `earned_coins_in_tier` | Progress toward next tier |
| `coins_to_next_tier` | How many more progress coins needed |
| `progress_percentage` | UI progress bar helper |
| `total_lifetime_coins` | Lifetime progress total |
| `total_discount_percentage` | Current tier’s % benefit |
| `free_checkup_count` | Configured value (**display only** until enforced) |
| `earned_coins_per_booking` | Progress bonus per paid booking |
| `package_discounts` | List of package promotions on the current tier |

### Membership / family packages

| Endpoint | Purpose |
|---|---|
| `GET /api/family-packages` | List active plans |
| `POST /api/family-packages/{id}/subscribe` | Subscribe |
| `GET /api/user/subscription` | Active subscription + usage summary |
| `GET /api/user/subscription/history` | Past subscriptions |
| `POST /api/user/subscription/cancel` | Cancel active plan |

### Booking responses

Doctor and diagnostic booking responses include `membership_pricing` with:

- `membership_benefit_applied`, `membership_discount`
- `reward_discount`, `reward_discount_source` (`tier_percentage` or `package_promotion`)
- `service_charges`, `final_payable_amount`
- Remaining consultation / lab allowances when relevant

The Flutter app is outside this repository; these endpoints are the integration contract.

---

## 7. Worked examples

### Example A — Silver 5% on a doctor visit (no membership)

- Consultation fee: ₹1,000  
- Tier Total Discount %: 5  
- Service charge config: 3%

```text
Membership discount     = ₹0
Reward discount         = ₹50
Discounted base         = ₹950
Service charge (3%)     = ₹28.50
Payable                 = ₹978.50
```

### Example B — Membership free consultation

- Consultation fee: ₹1,000  
- Membership eligible  
- Silver 5% also configured

```text
Membership discount     = ₹1,000  (base waived)
Reward discount         = ₹0      (nothing left)
Discounted base         = ₹0
Service charge          = 3% of original ₹1,000 = ₹30
Payable                 = ₹30
```

### Example C — Reward “Respiratory Health Package = Free”

- Package fee: ₹1,000  
- No membership  
- Tier has package promotion `free` for that package only  
- Service charge 3%

```text
Reward package free     = ₹1,000 off base
Discounted base         = ₹0
Service charge          = ₹30 (on original)
Payable                 = ₹30
```

Another diagnostic package **without** a promo uses Total Discount % instead (e.g. 5% → ₹950 + service on ₹950).

### Example D — Coins earned per booking = 2

- Patient on Silver with `earned_coins_per_booking = 2`
- Pays ₹1,000 after discounts

```text
Wallet credit           = round(1000 × 0.01) = 10 HIP Coins
Progress credit         = 10 + 2 = 12 progress coins
```

If Silver requires 100 coins from Bronze, those 12 count toward `earned_coins_in_tier`.

---

## 8. What is not implemented yet

| Feature | Status |
|---|---|
| **Free Checkups** (`free_checkup_count`) | Saved in admin + shown in API. **No booking enforcement / usage counter.** Needs a business definition of “checkup” before implementation |
| **Reward discount on second-opinion bookings** | Still uses legacy pricing path |
| **Tier demotion** | Not implemented (spending wallet coins never lowers tier) |
| **Membership `max_hip_coins` wallet credit** | Config/display oriented; not a full wallet entitlement engine |
| **Flutter UI** | Not in this repo — verify screens against the APIs above |

---

## 9. Key code files

| Area | Files |
|---|---|
| Reward admin | `app/Livewire/Admin/Settings/RewardTiers.php`, Hospital Admin counterpart, Blade under `resources/views/livewire/.../reward-tiers.blade.php` |
| Reward logic | `app/Services/RewardTierService.php` |
| Reward models | `RewardTier`, `RewardTierConfig`, `RewardTierPackageDiscount`, `UserRewardProgress` |
| Membership admin | `ManageFamilyPackages`, `ManagesFamilyPackagePlanBenefits`, membership package Blades |
| Membership logic | `app/Services/MembershipEntitlementService.php`, `app/Services/FamilyPackageService.php` |
| Membership models | `FamilyPackage`, `UserFamilySubscription`, `SubscriptionUsageLog` |
| Booking / payment | `app/Services/Api/BookingApiService.php`, `app/Services/Api/PaymentApiService.php` |
| Fee math | `app/Services/InvoiceFeeCalculator.php` |
| Patient APIs | `HomePageController::rewardProgress`, `FamilyPackageController` |
| Tests | `tests/Unit/RewardTierServiceTest.php`, `tests/Unit/MembershipEntitlementServiceTest.php` |

---

*This document reflects the backend behavior after reward-tier benefits were wired into booking pricing. Prefer this file over older notes that said total discount / package promotions were config-only.*
