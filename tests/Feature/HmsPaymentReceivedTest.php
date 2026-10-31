<?php

namespace Tests\Feature;

use App\Enums\MembershipPaymentType;
use App\Models\Coins;
use App\Models\FamilyPackage;
use App\Models\HIPUser;
use App\Models\HmisPatientSaving;
use App\Models\HmisTransaction;
use App\Models\HmsIntegration;
use App\Models\MembershipPackageDiscount;
use App\Models\Persons;
use App\Models\RewardPointsTransition;
use App\Models\UserFamilySubscription;
use App\Services\Hms\HmsSignature;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class HmsPaymentReceivedTest extends TestCase
{
    private const API_KEY = 'hms_test_key_payment';

    private const SECRET = 'payment-signing-secret';

    private int $clockStep = 0;

    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->fail('HMS payment tests must use the sqlite testing database.');
        }

        $this->bootSchema();
        Cache::flush();
        $this->clockStep = 0;

        $user = HIPUser::query()->create([
            'hip_id' => 'HIP00021',
            'first_name' => 'Asha',
            'last_name' => 'Rao',
            'mobile_num' => '9876543210',
            'password' => null,
        ]);
        $this->userId = (string) $user->id;

        DB::table('hospitals')->insert([
            'id' => 1,
            'name' => 'City Hospital',
            'code' => 'CITY',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        HmsIntegration::query()->create([
            'name' => 'City HMS',
            'hospital_ids' => [1],
            'api_key_hash' => HmsIntegration::hashKey(self::API_KEY),
            'signing_secret' => self::SECRET,
            'is_enabled' => true,
            'rate_limit_per_minute' => 120,
        ]);
    }

    public function test_hospital_code_must_match_the_hospital_code(): void
    {
        $this->person('MRN-CODE', 0);

        $mismatch = $this->postPayment($this->payment([
            'transaction_id' => 'TX-CODE-BAD',
            'mrn_id' => 'MRN-CODE',
            'hospital_code' => 'OTHER',
        ]));
        $mismatch->assertStatus(422);
        $mismatch->assertJsonPath('error', 'hospital_code_mismatch');
        $mismatch->assertJsonPath('is_synched', false);
        $this->assertSame(0, HmisTransaction::query()->count());

        $omitted = $this->payment([
            'transaction_id' => 'TX-CODE-OMIT',
            'mrn_id' => 'MRN-CODE',
        ]);
        unset($omitted['hospital_code']);
        $this->postPayment($omitted)
            ->assertOk()
            ->assertJsonPath('result', 'recorded')
            ->assertJsonPath('is_synched', true);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $this->postPayment($this->payment(), ['signature' => str_repeat('ab', 32)])
            ->assertStatus(401)
            ->assertJsonPath('code', 'invalid_signature');
    }

    public function test_plain_payment_earns_coins(): void
    {
        $this->person('MRN-PLAIN', 0);

        $response = $this->postPayment($this->payment([
            'transaction_id' => 'TX-PLAIN',
            'mrn_id' => 'MRN-PLAIN',
            'original_amount' => 500,
            'paid_amount' => 500,
        ]));

        $response->assertOk();
        $response->assertJsonPath('result', 'recorded');
        $response->assertJsonPath('is_synched', true);
        $response->assertJsonPath('coins_earned', 5);
        $response->assertJsonPath('coins_deducted', 0);
        $response->assertJsonPath('coin_balance', 5);
        $this->assertSame(1, RewardPointsTransition::query()->where('change_type', 'earned')->count());
        $this->assertSame(1, (int) HmisPatientSaving::query()->value('transactions_count'));

        $half = $this->postPayment($this->payment([
            'transaction_id' => 'TX-HALF',
            'mrn_id' => 'MRN-PLAIN',
            'original_amount' => 150,
            'paid_amount' => 150,
        ]));
        $half->assertOk();
        $half->assertJsonPath('coins_earned', 2);
        $half->assertJsonPath('coin_balance', 7);
    }

    public function test_membership_payment_earns_none_and_updates_totals(): void
    {
        $person = $this->person('MRN-MEM', 20);
        $subscriptionId = $this->membership($person, 20);

        $response = $this->postPayment($this->payment([
            'transaction_id' => 'TX-MEM',
            'mrn_id' => 'MRN-MEM',
            'original_amount' => 1000,
            'is_membership_applied' => true,
            'membership_id' => $subscriptionId,
            'percentage_discount_applied' => 20,
            'discount_amount' => 200,
            'paid_amount' => 800,
        ]));

        $response->assertOk();
        $response->assertJsonPath('result', 'recorded');
        $response->assertJsonPath('coins_earned', 0);
        $response->assertJsonPath('coin_balance', 20);
        $this->assertEquals(200, $response->json('lifetime_discount_total'));
        $saving = HmisPatientSaving::query()->first();
        $this->assertEquals(200, (float) $saving->membership_discount_total);
        $this->assertEquals(800, (float) $saving->lifetime_paid_total);
        $this->assertSame(0, RewardPointsTransition::query()->count());
    }

    public function test_coins_used_debits_and_earns_none(): void
    {
        $this->person('MRN-COIN', 100);

        $response = $this->postPayment($this->payment([
            'transaction_id' => 'TX-COIN',
            'mrn_id' => 'MRN-COIN',
            'original_amount' => 500,
            'is_coins_used' => true,
            'coins_used' => 10,
            'coin_discount_amount' => 100,
            'paid_amount' => 400,
        ]));

        $response->assertOk();
        $response->assertJsonPath('coins_earned', 0);
        $response->assertJsonPath('coins_deducted', 10);
        $response->assertJsonPath('coin_balance', 90);
        $this->assertSame(1, RewardPointsTransition::query()->where('change_type', 'redeemed')->count());
        $this->assertEquals(100, (float) HmisPatientSaving::query()->value('coins_value_total'));
    }

    public function test_membership_percentage_mismatch_is_flagged_and_recorded(): void
    {
        $person = $this->person('MRN-FLAG', 0);
        $subscriptionId = $this->membership($person, 20);

        $response = $this->postPayment($this->payment([
            'transaction_id' => 'TX-FLAG',
            'mrn_id' => 'MRN-FLAG',
            'original_amount' => 1000,
            'is_membership_applied' => true,
            'membership_id' => $subscriptionId,
            'percentage_discount_applied' => 10,
            'discount_amount' => 100,
            'paid_amount' => 900,
        ]));

        $response->assertOk();
        $response->assertJsonPath('result', 'recorded');
        $response->assertJsonPath('flags', ['membership_percentage_mismatch']);
        $response->assertJsonPath('coins_earned', 0);
    }

    public function test_excluded_and_membership_types_are_ignored(): void
    {
        $this->person('MRN-EX', 15);

        $excluded = $this->postPayment($this->payment([
            'transaction_id' => 'TX-EX',
            'mrn_id' => 'MRN-EX',
            'payment_type' => 'ip_advance_deposit',
            'original_amount' => 100,
            'paid_amount' => 100,
        ]));
        $excluded->assertOk();
        $excluded->assertJsonPath('result', 'ignored_ineligible');
        $excluded->assertJsonPath('coin_balance', 15);
        $this->assertNull(HmisPatientSaving::query()->first());

        $provisional = $this->postPayment($this->payment([
            'transaction_id' => 'TX-TYPE-PROV',
            'mrn_id' => 'MRN-EX',
            'payment_type' => 'ip_provisional_bill',
            'original_amount' => 100,
            'paid_amount' => 100,
        ]));
        $provisional->assertOk();
        $provisional->assertJsonPath('result', 'ignored_ineligible');
        $this->assertSame(15, (int) Coins::query()->value('coins'));
    }

    public function test_unknown_payment_type_is_rejected(): void
    {
        $this->person('MRN-BAD', 0);

        $response = $this->postPayment($this->payment([
            'transaction_id' => 'TX-BAD',
            'mrn_id' => 'MRN-BAD',
            'payment_type' => 'not_a_type',
        ]));

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'validation_error');
        $response->assertJsonPath('is_synched', false);
        $this->assertSame(0, HmisTransaction::query()->count());
    }

    public function test_unknown_mobile_is_stored_unmatched(): void
    {
        $response = $this->postPayment($this->payment([
            'transaction_id' => 'TX-UNMATCHED',
            'mrn_id' => 'MRN-MISSING',
            'mobile' => '9000000000',
            'original_amount' => 100,
            'paid_amount' => 100,
        ]));

        $response->assertOk();
        $response->assertJsonPath('result', 'recorded_unmatched');
        $response->assertJsonPath('is_synched', true);
        $response->assertJsonPath('coins_earned', 0);
        $response->assertJsonPath('coin_balance', 0);
        $this->assertSame('unmatched', HmisTransaction::query()->value('status'));
        $this->assertNull(HmisPatientSaving::query()->first());
    }

    public function test_mobile_match_saves_a_blank_mrn_and_flags_a_conflict(): void
    {
        $blank = $this->person(null, 0);
        $this->postPayment($this->payment([
            'transaction_id' => 'TX-LINK',
            'mrn_id' => 'MRN-LINKED',
            'mobile' => '919876543210',
            'original_amount' => 100,
            'paid_amount' => 100,
        ]))->assertJsonPath('result', 'recorded');

        $this->assertSame('MRN-LINKED', $blank->fresh()->mrn);

        $blank->update(['mrn' => 'MRN-OLD']);
        $conflict = $this->postPayment($this->payment([
            'transaction_id' => 'TX-CONFLICT',
            'mrn_id' => 'MRN-OTHER',
            'mobile' => '9876543210',
            'original_amount' => 100,
            'paid_amount' => 100,
        ]));
        $conflict->assertOk();
        $conflict->assertJsonPath('flags', ['mrn_conflict']);
        $this->assertSame('MRN-OLD', $blank->fresh()->mrn);
    }

    public function test_pending_payment_completes_once_and_a_repeat_is_a_duplicate(): void
    {
        $this->person('MRN-PEND', 0);
        $body = $this->payment([
            'transaction_id' => 'TX-PEND',
            'mrn_id' => 'MRN-PEND',
            'original_amount' => 200,
            'paid_amount' => 200,
            'is_paid' => false,
        ]);

        $this->postPayment($body)
            ->assertJsonPath('result', 'recorded_pending')
            ->assertJsonPath('coins_earned', 0);
        $this->assertSame(0, (int) Coins::query()->value('coins'));

        $body['is_paid'] = true;
        $this->postPayment($body)
            ->assertJsonPath('result', 'recorded')
            ->assertJsonPath('coins_earned', 2)
            ->assertJsonPath('coin_balance', 2);

        $this->postPayment($body)
            ->assertJsonPath('result', 'duplicate')
            ->assertJsonPath('coin_balance', 2);
        $this->assertSame(1, HmisTransaction::query()->count());
    }

    public function test_refund_reverses_coins_and_totals_once(): void
    {
        $this->person('MRN-REF', 0);
        $this->postPayment($this->payment([
            'transaction_id' => 'TX-ORIG',
            'mrn_id' => 'MRN-REF',
            'original_amount' => 500,
            'paid_amount' => 500,
        ]))->assertJsonPath('coins_earned', 5);

        $refund = $this->payment([
            'transaction_id' => 'TX-REFUND',
            'mrn_id' => 'MRN-REF',
            'original_amount' => 500,
            'paid_amount' => 500,
            'is_refund' => true,
            'refund_of_transaction_id' => 'TX-ORIG',
        ]);

        $first = $this->postPayment($refund);
        $first->assertOk();
        $first->assertJsonPath('result', 'refund_applied');
        $first->assertJsonPath('coins_deducted', 5);
        $first->assertJsonPath('coin_balance', 0);
        $this->assertEquals(0, $first->json('lifetime_discount_total'));
        $this->assertSame('refunded', HmisTransaction::query()->where('transaction_id', 'TX-ORIG')->value('status'));
        $this->assertSame(5, (int) HmisTransaction::query()->where('transaction_id', 'TX-ORIG')->value('coins_reversed'));
        $this->assertSame(0, (int) HmisPatientSaving::query()->value('transactions_count'));

        $this->postPayment($refund)->assertJsonPath('result', 'duplicate');
        $this->assertSame(0, (int) Coins::query()->value('coins'));
        $this->assertSame(2, HmisTransaction::query()->count());
    }

    public function test_insufficient_coins_stores_nothing(): void
    {
        $this->person('MRN-LOW', 1);

        $response = $this->postPayment($this->payment([
            'transaction_id' => 'TX-LOW',
            'mrn_id' => 'MRN-LOW',
            'original_amount' => 100,
            'is_coins_used' => true,
            'coins_used' => 5,
            'coin_discount_amount' => 50,
            'paid_amount' => 50,
        ]));

        $response->assertStatus(409);
        $response->assertJsonPath('error', 'coins_insufficient');
        $response->assertJsonPath('is_synched', false);
        $this->assertSame(0, HmisTransaction::query()->count());
        $this->assertSame(1, (int) Coins::query()->value('coins'));
    }

    public function test_arithmetic_failure_stores_nothing(): void
    {
        $this->person('MRN-MATH', 0);

        $response = $this->postPayment($this->payment([
            'transaction_id' => 'TX-MATH',
            'mrn_id' => 'MRN-MATH',
            'original_amount' => 100,
            'paid_amount' => 90,
        ]));

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'amount_check_failed');
        $response->assertJsonPath('is_synched', false);
        $this->assertSame(0, HmisTransaction::query()->count());
    }

    public function test_membership_activation_creates_subscription_invoice_and_transaction(): void
    {
        $person = $this->person('MRN-MEM-ACT', 0);

        $package = FamilyPackage::query()->create([
            'name' => 'Family Premium',
            'price' => 1200,
            'duration_days' => 365,
            'max_members' => 3,
            'is_active' => true,
        ]);

        $response = $this->postPayment($this->payment([
            'transaction_id' => 'TX-MEM-ACT-1',
            'mrn_id' => 'MRN-MEM-ACT',
            'payment_type' => 'membership',
            'original_amount' => 1200,
            'paid_amount' => 1200,
            'package_id' => $package->id,
            'members' => [
                [
                    'name' => 'Kiran Rao',
                    'dob' => '2016-04-12',
                    'gender' => 'Male',
                    'relation' => 'Child',
                ],
            ],
        ]));

        $response->assertOk();
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('is_synched', true);
        $response->assertJsonPath('result', 'membership_created');
        $response->assertJsonPath('coins_earned', 0);
        $response->assertJsonPath('coins_deducted', 0);
        $response->assertJsonPath('membership.plan_name', 'Family Premium');
        $response->assertJsonPath('membership.valid_from', now()->toDateString());
        $response->assertJsonPath('membership.valid_until', now()->addDays(365)->toDateString());

        $membershipId = $response->json('membership.membership_id');
        $this->assertNotNull($membershipId);

        $subscription = UserFamilySubscription::query()->find($membershipId);
        $this->assertNotNull($subscription);
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals('paid', $subscription->payment_status);
        $this->assertEquals(1200, (float) $subscription->amount_paid);
        $this->assertCount(2, $subscription->covered_member_ids);

        $this->assertEquals(1, DB::table('invoices')->count());
        $this->assertEquals(1, DB::table('transactions')->count());
        $this->assertEquals(1, HmisTransaction::query()->where('transaction_id', 'TX-MEM-ACT-1')->count());
        $this->assertNull(HmisPatientSaving::query()->first());
    }

    public function test_membership_same_plan_renewal_extends_end_date(): void
    {
        $person = $this->person('MRN-MEM-RENEW', 0);

        $package = FamilyPackage::query()->create([
            'name' => 'Family Care',
            'price' => 500,
            'duration_days' => 100,
            'max_members' => 2,
            'is_active' => true,
        ]);

        $initialEndDate = now()->addDays(20)->toDateString();
        $sub = UserFamilySubscription::query()->create([
            'hip_user_id' => $this->userId,
            'family_package_id' => $package->id,
            'covered_member_ids' => [(string) $person->id],
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => $initialEndDate,
            'status' => 'active',
            'payment_status' => 'paid',
            'amount_paid' => 500,
        ]);

        $response = $this->postPayment($this->payment([
            'transaction_id' => 'TX-MEM-RENEW-1',
            'mrn_id' => 'MRN-MEM-RENEW',
            'payment_type' => 'membership',
            'original_amount' => 500,
            'paid_amount' => 500,
            'package_id' => $package->id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('result', 'membership_created');
        $response->assertJsonPath('membership.membership_id', $sub->id);
        $response->assertJsonPath('membership.valid_until', Carbon::parse($initialEndDate)->addDays(100)->toDateString());

        $this->assertEquals(1, UserFamilySubscription::query()->where('hip_user_id', $this->userId)->count());
        $sub->refresh();
        $this->assertEquals(Carbon::parse($initialEndDate)->addDays(100)->toDateString(), $sub->end_date->toDateString());
    }

    public function test_membership_different_active_plan_returns_409(): void
    {
        $person = $this->person('MRN-MEM-DIFF', 0);

        $planA = FamilyPackage::query()->create([
            'name' => 'Plan A',
            'price' => 500,
            'duration_days' => 100,
            'is_active' => true,
        ]);

        $planB = FamilyPackage::query()->create([
            'name' => 'Plan B',
            'price' => 1500,
            'duration_days' => 200,
            'is_active' => true,
        ]);

        UserFamilySubscription::query()->create([
            'hip_user_id' => $this->userId,
            'family_package_id' => $planA->id,
            'covered_member_ids' => [(string) $person->id],
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'status' => 'active',
            'payment_status' => 'paid',
        ]);

        $response = $this->postPayment($this->payment([
            'transaction_id' => 'TX-MEM-DIFF-1',
            'mrn_id' => 'MRN-MEM-DIFF',
            'payment_type' => 'membership',
            'original_amount' => 1500,
            'paid_amount' => 1500,
            'package_id' => $planB->id,
        ]));

        $response->assertStatus(409);
        $response->assertJsonPath('error', 'membership_exists');
        $this->assertSame(0, HmisTransaction::query()->where('transaction_id', 'TX-MEM-DIFF-1')->count());
        $this->assertEquals(1, UserFamilySubscription::query()->where('hip_user_id', $this->userId)->count());
    }

    public function test_membership_unmatched_patient_stores_unmatched_with_flag(): void
    {
        $package = FamilyPackage::query()->create([
            'name' => 'Solo Care',
            'price' => 300,
            'duration_days' => 60,
            'is_active' => true,
        ]);

        $response = $this->postPayment($this->payment([
            'transaction_id' => 'TX-MEM-UNMATCHED',
            'mrn_id' => 'MRN-NON-EXISTENT',
            'mobile' => '',
            'payment_type' => 'membership',
            'original_amount' => 300,
            'paid_amount' => 300,
            'package_id' => $package->id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('result', 'recorded_unmatched');
        $response->assertJsonPath('flags', ['membership_not_activated']);
        $this->assertEquals(0, UserFamilySubscription::query()->count());
        $this->assertEquals(1, HmisTransaction::query()->where('transaction_id', 'TX-MEM-UNMATCHED')->count());
    }

    public function test_membership_price_mismatch_still_activates_with_flag(): void
    {
        $this->person('MRN-MEM-PRICE', 0);

        $package = FamilyPackage::query()->create([
            'name' => 'Standard Care',
            'price' => 1000,
            'duration_days' => 365,
            'is_active' => true,
        ]);

        $response = $this->postPayment($this->payment([
            'transaction_id' => 'TX-MEM-PRICE-1',
            'mrn_id' => 'MRN-MEM-PRICE',
            'payment_type' => 'membership',
            'original_amount' => 850,
            'paid_amount' => 850,
            'package_id' => $package->id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('result', 'membership_created');
        $response->assertJsonPath('flags', ['plan_price_mismatch']);

        $sub = UserFamilySubscription::query()->first();
        $this->assertNotNull($sub);
        $this->assertEquals(850, (float) $sub->amount_paid);
    }

    public function test_membership_repeat_transaction_id_returns_duplicate_with_membership_block(): void
    {
        $this->person('MRN-MEM-DUP', 0);

        $package = FamilyPackage::query()->create([
            'name' => 'Gold Care',
            'price' => 1000,
            'duration_days' => 365,
            'is_active' => true,
        ]);

        $first = $this->postPayment($this->payment([
            'transaction_id' => 'TX-MEM-DUP-1',
            'mrn_id' => 'MRN-MEM-DUP',
            'payment_type' => 'membership',
            'original_amount' => 1000,
            'paid_amount' => 1000,
            'package_id' => $package->id,
        ]));

        $first->assertOk();
        $first->assertJsonPath('result', 'membership_created');
        $expectedMembership = $first->json('membership');

        $second = $this->postPayment($this->payment([
            'transaction_id' => 'TX-MEM-DUP-1',
            'mrn_id' => 'MRN-MEM-DUP',
            'payment_type' => 'membership',
            'original_amount' => 1000,
            'paid_amount' => 1000,
            'package_id' => $package->id,
        ]));

        $second->assertOk();
        $second->assertJsonPath('result', 'duplicate');
        $second->assertJsonPath('membership', $expectedMembership);
        $this->assertEquals(1, UserFamilySubscription::query()->count());
    }

    public function test_membership_pending_then_paid_activates_once(): void
    {
        $this->person('MRN-MEM-PEND', 0);

        $package = FamilyPackage::query()->create([
            'name' => 'Silver Care',
            'price' => 600,
            'duration_days' => 180,
            'is_active' => true,
        ]);

        $pending = $this->postPayment($this->payment([
            'transaction_id' => 'TX-MEM-PEND-1',
            'mrn_id' => 'MRN-MEM-PEND',
            'payment_type' => 'membership',
            'original_amount' => 600,
            'paid_amount' => 600,
            'package_id' => $package->id,
            'is_paid' => false,
        ]));

        $pending->assertOk();
        $pending->assertJsonPath('result', 'recorded_pending');
        $this->assertEquals(0, UserFamilySubscription::query()->count());

        $paid = $this->postPayment($this->payment([
            'transaction_id' => 'TX-MEM-PEND-1',
            'mrn_id' => 'MRN-MEM-PEND',
            'payment_type' => 'membership',
            'original_amount' => 600,
            'paid_amount' => 600,
            'package_id' => $package->id,
            'is_paid' => true,
        ]));

        $paid->assertOk();
        $paid->assertJsonPath('result', 'membership_created');
        $this->assertEquals(1, UserFamilySubscription::query()->count());
    }

    public function test_membership_refund_does_not_cancel_membership_and_adds_flag(): void
    {
        $this->person('MRN-MEM-REF', 0);

        $package = FamilyPackage::query()->create([
            'name' => 'Bronze Care',
            'price' => 400,
            'duration_days' => 90,
            'is_active' => true,
        ]);

        $activation = $this->postPayment($this->payment([
            'transaction_id' => 'TX-MEM-REF-ORIG',
            'mrn_id' => 'MRN-MEM-REF',
            'payment_type' => 'membership',
            'original_amount' => 400,
            'paid_amount' => 400,
            'package_id' => $package->id,
        ]));
        $activation->assertOk();

        $sub = UserFamilySubscription::query()->first();
        $this->assertEquals('active', $sub->status);

        $refund = $this->postPayment($this->payment([
            'transaction_id' => 'TX-MEM-REF-1',
            'mrn_id' => 'MRN-MEM-REF',
            'payment_type' => 'membership',
            'original_amount' => 400,
            'paid_amount' => 400,
            'is_refund' => true,
            'refund_of_transaction_id' => 'TX-MEM-REF-ORIG',
        ]));

        $refund->assertOk();
        $refund->assertJsonPath('result', 'refund_applied');
        $refund->assertJsonPath('flags', ['membership_refund_needs_review']);

        $sub->refresh();
        $this->assertEquals('active', $sub->status);
    }

    public function test_membership_plan_validation(): void
    {
        $this->person('MRN-MEM-VAL', 0);

        $package = FamilyPackage::query()->create([
            'name' => 'Active Plan',
            'price' => 500,
            'duration_days' => 100,
            'is_active' => true,
        ]);

        $missingPackage = $this->postPayment($this->payment([
            'transaction_id' => 'TX-VAL-1',
            'mrn_id' => 'MRN-MEM-VAL',
            'payment_type' => 'membership',
            'original_amount' => 500,
            'paid_amount' => 500,
        ]));
        $missingPackage->assertStatus(422);
        $missingPackage->assertJsonPath('error', 'validation_error');

        $inactivePlan = FamilyPackage::query()->create([
            'name' => 'Inactive Plan',
            'price' => 500,
            'duration_days' => 100,
            'is_active' => false,
        ]);

        $inactiveResponse = $this->postPayment($this->payment([
            'transaction_id' => 'TX-VAL-2',
            'mrn_id' => 'MRN-MEM-VAL',
            'payment_type' => 'membership',
            'original_amount' => 500,
            'paid_amount' => 500,
            'package_id' => $inactivePlan->id,
        ]));
        $inactiveResponse->assertStatus(422);
        $inactiveResponse->assertJsonPath('error', 'validation_error');

        $futureDateResponse = $this->postPayment($this->payment([
            'transaction_id' => 'TX-VAL-3',
            'mrn_id' => 'MRN-MEM-VAL',
            'payment_type' => 'membership',
            'original_amount' => 500,
            'paid_amount' => 500,
            'package_id' => $package->id,
            'start_date' => now()->addDays(10)->toDateString(),
        ]));
        $futureDateResponse->assertStatus(422);
        $futureDateResponse->assertJsonPath('error', 'validation_error');

        $otherDataOnlyResponse = $this->postPayment($this->payment([
            'transaction_id' => 'TX-VAL-4',
            'mrn_id' => 'MRN-MEM-VAL',
            'payment_type' => 'membership',
            'original_amount' => 500,
            'paid_amount' => 500,
            'other_data' => ['package_id' => $package->id],
        ]));
        $otherDataOnlyResponse->assertStatus(422);
        $otherDataOnlyResponse->assertJsonPath('error', 'validation_error');
    }

    public function test_membership_get_returns_the_new_membership_after_activation(): void
    {
        $this->person('MRN-MEM-GET', 0);

        $package = FamilyPackage::query()->create([
            'name' => 'Complete Care',
            'price' => 2000,
            'duration_days' => 365,
            'is_active' => true,
        ]);
        MembershipPackageDiscount::query()->create([
            'family_package_id' => $package->id,
            'payment_type' => MembershipPaymentType::OpConsultation->value,
            'percentage' => 25,
        ]);

        $activation = $this->postPayment($this->payment([
            'transaction_id' => 'TX-MEM-GET-1',
            'mrn_id' => 'MRN-MEM-GET',
            'payment_type' => 'membership',
            'original_amount' => 2000,
            'paid_amount' => 2000,
            'package_id' => $package->id,
        ]));
        $activation->assertOk();

        $activatedSubId = $activation->json('membership.membership_id');

        $get = $this->getSigned('/apiv1/client/get/patient/membership?mrn_id=MRN-MEM-GET');
        $get->assertOk();
        $get->assertJsonPath('has_membership', true);
        $get->assertJsonPath('membership_id', $activatedSubId);
        $get->assertJsonPath('plan_name', 'Complete Care');
        $get->assertJsonPath('discounts.op_consultation.percentage', 25);
    }

    public function test_membership_plans_list(): void
    {
        $planA = FamilyPackage::query()->create([
            'name' => 'Active Alpha',
            'price' => 1000,
            'duration_days' => 180,
            'max_members' => 3,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        MembershipPackageDiscount::query()->create([
            'family_package_id' => $planA->id,
            'payment_type' => MembershipPaymentType::OpConsultation->value,
            'percentage' => 20,
        ]);

        $planB = FamilyPackage::query()->create([
            'name' => 'Active Beta',
            'price' => 2000,
            'duration_days' => 365,
            'max_members' => 5,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        FamilyPackage::query()->create([
            'name' => 'Inactive Gamma',
            'price' => 500,
            'duration_days' => 90,
            'is_active' => false,
        ]);

        $response = $this->getSigned('/apiv1/client/get/membership/plans');
        $response->assertOk();
        $response->assertJsonPath('status', true);

        $plans = $response->json('plans');
        $this->assertCount(2, $plans);

        $this->assertEquals($planA->id, $plans[0]['id']);
        $this->assertEquals('Active Alpha', $plans[0]['name']);
        $this->assertEquals(1000, $plans[0]['price']);
        $this->assertEquals(180, $plans[0]['duration_days']);
        $this->assertEquals(20, $plans[0]['discounts']['op_consultation']['percentage']);

        $this->assertEquals($planB->id, $plans[1]['id']);
        $this->assertEquals('Active Beta', $plans[1]['name']);
        $this->assertEquals(2000, $plans[1]['price']);
        $this->assertEquals(365, $plans[1]['duration_days']);
    }

    public function test_membership_payment_method_sets_transactions_row_payment_method(): void
    {
        $person = $this->person('MRN-MEM-PM', 0);
        $package = FamilyPackage::query()->create([
            'name' => 'PM Plan',
            'price' => 1200,
            'duration_days' => 365,
            'max_members' => 2,
            'is_active' => true,
        ]);

        $response = $this->postPayment($this->payment([
            'transaction_id' => 'TX-MEM-PM-1',
            'payment_type' => 'membership',
            'mrn_id' => 'MRN-MEM-PM',
            'package_id' => $package->id,
            'original_amount' => 1200,
            'paid_amount' => 1200,
            'payment_method' => 'card',
        ]));

        $response->assertOk();
        $response->assertJsonPath('result', 'membership_created');

        $transaction = \App\Models\Transactions::query()->latest('id')->first();
        $this->assertNotNull($transaction);
        $this->assertSame('card', $transaction->payment_method);

        // Test invalid payment_method (> 30 chars) returns 422
        $responseBad = $this->postPayment($this->payment([
            'transaction_id' => 'TX-MEM-PM-BAD',
            'payment_type' => 'membership',
            'mrn_id' => 'MRN-MEM-PM',
            'package_id' => $package->id,
            'original_amount' => 1200,
            'paid_amount' => 1200,
            'payment_method' => str_repeat('a', 31),
        ]));
        $responseBad->assertStatus(422);
        $responseBad->assertJsonPath('error', 'validation_error');
    }

    public function test_membership_package_hospital_scope_validation_and_listing(): void
    {
        $this->person('MRN-SCOPE', 0);

        // Package scoped only to hospital 999 (integration has hospital 1)
        $scopedOther = FamilyPackage::query()->create([
            'name' => 'Hospital 999 Only',
            'price' => 1000,
            'duration_days' => 365,
            'max_members' => 2,
            'branch_ids' => [999],
            'is_active' => true,
        ]);

        // Attempting membership purchase for this package fails 422
        $response = $this->postPayment($this->payment([
            'transaction_id' => 'TX-SCOPE-FAIL',
            'payment_type' => 'membership',
            'mrn_id' => 'MRN-SCOPE',
            'package_id' => $scopedOther->id,
            'original_amount' => 1000,
            'paid_amount' => 1000,
        ]));

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'validation_error');

        // GET /get/membership/plans must exclude this package
        $plansResponse = $this->getSigned('/apiv1/client/get/membership/plans');
        $plansResponse->assertOk();
        $planIds = collect($plansResponse->json('plans'))->pluck('id')->all();
        $this->assertNotContains($scopedOther->id, $planIds);
    }

    public function test_reward_tier_progress_increases_on_eligible_paid_bill(): void
    {
        $this->person('MRN-TIER-1', 0);

        $response = $this->postPayment($this->payment([
            'transaction_id' => 'TX-TIER-1',
            'mrn_id' => 'MRN-TIER-1',
            'payment_type' => 'op_consultation',
            'original_amount' => 2000,
            'paid_amount' => 2000,
        ]));

        $response->assertOk();
        $response->assertJsonPath('result', 'recorded');
        $response->assertJsonPath('coins_earned', 20);
        $response->assertJsonPath('reward_tier.name', 'Bronze');
        $response->assertJsonPath('reward_tier.upgraded', false);

        $progress = \App\Models\UserRewardProgress::query()->where('hip_user_id', $this->userId)->first();
        $this->assertNotNull($progress);
        $this->assertSame(20, (int) $progress->earned_coins_in_tier);
        $this->assertSame(20, (int) $progress->total_lifetime_coins);
    }

    public function test_reward_tier_no_change_on_discounted_or_coins_used(): void
    {
        $person = $this->person('MRN-TIER-DISC', 50);
        $subId = $this->membership($person, 20.0);

        // Discounted bill earns 0 coins
        $respDisc = $this->postPayment($this->payment([
            'transaction_id' => 'TX-TIER-DISC',
            'mrn_id' => 'MRN-TIER-DISC',
            'payment_type' => 'op_consultation',
            'is_membership_applied' => true,
            'membership_id' => $subId,
            'percentage_discount_applied' => 20.0,
            'original_amount' => 100,
            'discount_amount' => 20,
            'paid_amount' => 80,
        ]));
        $respDisc->assertOk();
        $respDisc->assertJsonPath('coins_earned', 0);

        $progress = \App\Models\UserRewardProgress::query()->where('hip_user_id', $this->userId)->first();
        $this->assertTrue($progress === null || (int) $progress->earned_coins_in_tier === 0);

        // Coin-used bill earns 0 coins
        $respCoins = $this->postPayment($this->payment([
            'transaction_id' => 'TX-TIER-COINS',
            'mrn_id' => 'MRN-TIER-DISC',
            'payment_type' => 'op_consultation',
            'is_coins_used' => true,
            'coins_used' => 10,
            'coin_discount_amount' => 100,
            'original_amount' => 500,
            'paid_amount' => 400,
        ]));
        $respCoins->assertOk();
        $respCoins->assertJsonPath('coins_earned', 0);

        $progress = \App\Models\UserRewardProgress::query()->where('hip_user_id', $this->userId)->first();
        $this->assertTrue($progress === null || (int) $progress->earned_coins_in_tier === 0);
    }

    public function test_reward_tier_promotes_when_tier_coins_completed(): void
    {
        $this->person('MRN-TIER-UPGRADE', 0);

        $bronze = \App\Models\RewardTier::query()->where('name', 'Bronze')->first();
        $silver = \App\Models\RewardTier::query()->where('name', 'Silver')->first();

        // Seed user at 90 earned coins in Bronze (Silver needs 100)
        \App\Models\UserRewardProgress::query()->create([
            'hip_user_id' => $this->userId,
            'reward_tier_id' => $bronze->id,
            'earned_coins_in_tier' => 90,
            'total_lifetime_coins' => 90,
        ]);

        // Bill of 1000 earns 10 coins => reaches 100 coins => promotes to Silver!
        $response = $this->postPayment($this->payment([
            'transaction_id' => 'TX-TIER-PROMO',
            'mrn_id' => 'MRN-TIER-UPGRADE',
            'payment_type' => 'op_consultation',
            'original_amount' => 1000,
            'paid_amount' => 1000,
        ]));

        $response->assertOk();
        $response->assertJsonPath('coins_earned', 10);
        $response->assertJsonPath('reward_tier.name', 'Silver');
        $response->assertJsonPath('reward_tier.upgraded', true);

        $progress = \App\Models\UserRewardProgress::query()->where('hip_user_id', $this->userId)->first();
        $this->assertSame((int) $silver->id, (int) $progress->reward_tier_id);
        $this->assertSame(0, (int) $progress->earned_coins_in_tier);
        $this->assertSame(100, (int) $progress->total_lifetime_coins);
    }

    public function test_reward_tier_repeat_transaction_id_counts_once(): void
    {
        $this->person('MRN-TIER-DUP', 0);

        $response1 = $this->postPayment($this->payment([
            'transaction_id' => 'TX-TIER-DUP',
            'mrn_id' => 'MRN-TIER-DUP',
            'payment_type' => 'op_consultation',
            'original_amount' => 1000,
            'paid_amount' => 1000,
        ]));
        $response1->assertOk();
        $response1->assertJsonPath('result', 'recorded');
        $response1->assertJsonPath('coins_earned', 10);

        $progress1 = \App\Models\UserRewardProgress::query()->where('hip_user_id', $this->userId)->first();
        $this->assertSame(10, (int) $progress1->earned_coins_in_tier);

        // Repeat transaction
        $response2 = $this->postPayment($this->payment([
            'transaction_id' => 'TX-TIER-DUP',
            'mrn_id' => 'MRN-TIER-DUP',
            'payment_type' => 'op_consultation',
            'original_amount' => 1000,
            'paid_amount' => 1000,
        ]));
        $response2->assertOk();
        $response2->assertJsonPath('result', 'duplicate');
        $response2->assertJsonPath('reward_tier.upgraded', false);

        $progress2 = \App\Models\UserRewardProgress::query()->where('hip_user_id', $this->userId)->first();
        $this->assertSame(10, (int) $progress2->earned_coins_in_tier);
    }

    public function test_reward_tier_refund_reverses_progress_without_lowering_tier(): void
    {
        $this->person('MRN-TIER-REFUND', 0);
        $silver = \App\Models\RewardTier::query()->where('name', 'Silver')->first();

        // User is at Silver with 20 coins in tier and 120 lifetime coins
        \App\Models\UserRewardProgress::query()->create([
            'hip_user_id' => $this->userId,
            'reward_tier_id' => $silver->id,
            'earned_coins_in_tier' => 20,
            'total_lifetime_coins' => 120,
        ]);

        // First payment: earns 10 coins
        $respPay = $this->postPayment($this->payment([
            'transaction_id' => 'TX-ORIGINAL-EARN',
            'mrn_id' => 'MRN-TIER-REFUND',
            'payment_type' => 'op_consultation',
            'original_amount' => 1000,
            'paid_amount' => 1000,
        ]));
        $respPay->assertOk();

        $progressAfterPay = \App\Models\UserRewardProgress::query()->where('hip_user_id', $this->userId)->first();
        $this->assertSame(30, (int) $progressAfterPay->earned_coins_in_tier);
        $this->assertSame(130, (int) $progressAfterPay->total_lifetime_coins);

        // Refund of this transaction
        $respRefund = $this->postPayment($this->payment([
            'transaction_id' => 'TX-REFUND-TIER',
            'is_refund' => true,
            'refund_of_transaction_id' => 'TX-ORIGINAL-EARN',
            'payment_type' => 'op_consultation',
            'original_amount' => 1000,
            'paid_amount' => 1000,
        ]));
        $respRefund->assertOk();
        $respRefund->assertJsonPath('result', 'refund_applied');

        $progressAfterRefund = \App\Models\UserRewardProgress::query()->where('hip_user_id', $this->userId)->first();
        // Progress goes down
        $this->assertSame(20, (int) $progressAfterRefund->earned_coins_in_tier);
        $this->assertSame(120, (int) $progressAfterRefund->total_lifetime_coins);
        // Tier already reached is NEVER lowered (still Silver)
        $this->assertSame((int) $silver->id, (int) $progressAfterRefund->reward_tier_id);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payment(array $overrides = []): array
    {
        return array_merge([
            'transaction_id' => 'TX-1',
            'hospital_code' => 'city',
            'mrn_id' => 'MRN-1',
            'mobile' => '9876543210',
            'payment_type' => 'op_consultation',
            'paid_at' => '2026-10-30 10:00:00',
            'is_paid' => true,
            'original_amount' => 100,
            'is_membership_applied' => false,
            'discount_amount' => 0,
            'is_coins_used' => false,
            'coins_used' => 0,
            'coin_discount_amount' => 0,
            'paid_amount' => 100,
            'is_refund' => false,
            'is_synced' => true,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>  $overrides
     */
    private function postPayment(array $body, array $overrides = []): TestResponse
    {
        $raw = json_encode($body, JSON_UNESCAPED_SLASHES);
        $uri = '/apiv1/client/payment/received';
        $timestamp = (string) ($overrides['timestamp'] ?? (now()->getTimestamp() + $this->clockStep++));
        $signature = $overrides['signature'] ?? HmsSignature::sign(self::SECRET, $timestamp, 'POST', $uri, $raw === false ? '' : $raw);

        return $this->call('POST', $uri, [], [], [], [
            'HTTP_X_HMS_KEY' => self::API_KEY,
            'HTTP_X_HMS_TIMESTAMP' => $timestamp,
            'HTTP_X_HMS_SIGNATURE' => $signature,
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], $raw === false ? '' : $raw);
    }

    private function getSigned(string $uri, array $overrides = []): TestResponse
    {
        $timestamp = (string) ($overrides['timestamp'] ?? (now()->getTimestamp() + $this->clockStep++));
        $signature = $overrides['signature'] ?? HmsSignature::sign(self::SECRET, $timestamp, 'GET', $uri, '');

        return $this->call('GET', $uri, [], [], [], [
            'HTTP_X_HMS_KEY' => self::API_KEY,
            'HTTP_X_HMS_TIMESTAMP' => $timestamp,
            'HTTP_X_HMS_SIGNATURE' => $signature,
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ]);
    }

    private function person(?string $mrn, int $coins): Persons
    {
        $person = Persons::query()->create([
            'mrn' => $mrn,
            'first_name' => 'Asha',
            'last_name' => 'Rao',
            'mobile' => '9876543210',
            'hip_user_id' => $this->userId,
            'is_primary' => true,
            'gender' => 'Female',
            'dob' => '1990-01-15',
        ]);
        $person->update(['parent_id' => $person->id]);

        if ($coins > 0) {
            Coins::query()->create([
                'person_id' => $person->id,
                'coins' => $coins,
            ]);
        }

        return $person->fresh();
    }

    private function membership(Persons $person, float $percentage): int
    {
        $package = FamilyPackage::query()->create([
            'name' => 'Family Gold',
            'price' => 1000,
            'duration_days' => 365,
            'max_members' => 4,
            'max_consultations' => 0,
            'is_active' => true,
        ]);
        MembershipPackageDiscount::query()->create([
            'family_package_id' => $package->id,
            'payment_type' => MembershipPaymentType::OpConsultation->value,
            'percentage' => $percentage,
        ]);

        return (int) UserFamilySubscription::query()->create([
            'hip_user_id' => $this->userId,
            'family_package_id' => $package->id,
            'covered_member_ids' => [(string) $person->id],
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ])->id;
    }

    private function bootSchema(): void
    {
        foreach ([
            'reward_tier_transitions',
            'user_reward_progress',
            'reward_tier_configs',
            'reward_tiers',
            'reward_points_transitions',
            'hmis_patient_savings',
            'hmis_transactions',
            'membership_package_discounts',
            'subscription_usage_logs',
            'user_family_subscriptions',
            'user_rewards',
            'transactions',
            'invoices',
            'coins',
            'doctor_bookings',
            'hms_webhook_inbox',
            'hms_integrations',
            'hospitals',
            'persons',
            'family_packages',
            'healthinpocket_users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('healthinpocket_users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('hip_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('mobile_num')->nullable();
            $table->string('password')->nullable();
            $table->string('organization_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('profile_update')->nullable();
            $table->timestamps();
        });

        Schema::create('hospitals', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code', 50)->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('persons', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('mrn', 64)->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('mobile')->nullable();
            $table->string('gender')->nullable();
            $table->string('relationship')->nullable();
            $table->date('dob')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->uuid('hip_user_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('family_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('duration_days')->default(365);
            $table->unsignedInteger('max_members')->default(1);
            $table->unsignedInteger('max_consultations')->default(0);
            $table->unsignedInteger('max_lab_tests')->default(0);
            $table->unsignedInteger('max_hip_coins')->default(0);
            $table->json('branch_ids')->nullable();
            $table->json('benefits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('membership_package_discounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('family_package_id');
            $table->string('payment_type', 64);
            $table->decimal('percentage', 5, 2);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->uuid('primary_person_id')->nullable();
            $table->uuid('person_id')->nullable();
            $table->json('service_types')->nullable();
            $table->json('invoice_details')->nullable();
            $table->string('prescription_img')->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->decimal('total_gst', 10, 2)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->decimal('service_charges', 10, 2)->nullable();
            $table->decimal('payment_gateway_charges', 10, 2)->nullable();
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->string('status')->default('pending');
            $table->integer('coins_applied')->nullable();
            $table->integer('coins_earned')->nullable();
            $table->string('payment_method')->nullable();
            $table->boolean('is_notified')->default(false);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->json('service_types')->nullable();
            $table->json('invoice_details')->nullable();
            $table->decimal('transaction_amount', 10, 2)->nullable();
            $table->decimal('service_charges', 10, 2)->nullable();
            $table->decimal('payment_gateway_charges', 10, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->decimal('total_gst', 10, 2)->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->string('status')->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('user_rewards', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_type')->nullable();
            $table->string('hip_user_id')->nullable();
            $table->boolean('is_applied')->default(false);
            $table->timestamps();
        });

        Schema::create('user_family_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->string('hip_user_id');
            $table->unsignedBigInteger('family_package_id');
            $table->json('covered_member_ids')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->string('payment_status')->nullable();
            $table->string('payment_mode')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->string('created_by')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->boolean('auto_renew')->default(false);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_usage_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_family_subscription_id');
            $table->string('usage_type');
            $table->timestamps();
        });

        Schema::create('reward_tiers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('min_coins')->default(0);
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('reward_tier_configs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('reward_tier_id');
            $table->decimal('total_discount_percentage', 5, 2)->nullable();
            $table->unsignedInteger('free_checkup_count')->default(0);
            $table->unsignedInteger('earned_coins_per_booking')->default(0);
            $table->timestamps();
        });

        Schema::create('user_reward_progress', function (Blueprint $table): void {
            $table->id();
            $table->uuid('hip_user_id')->unique();
            $table->unsignedBigInteger('reward_tier_id');
            $table->unsignedInteger('earned_coins_in_tier')->default(0);
            $table->unsignedInteger('total_lifetime_coins')->default(0);
            $table->unsignedInteger('tier_generation')->default(1);
            $table->timestamps();
        });

        Schema::create('reward_tier_transitions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('hip_user_id');
            $table->unsignedBigInteger('previous_tier_id')->nullable();
            $table->unsignedBigInteger('new_tier_id');
            $table->string('occurrence_id')->nullable();
            $table->timestamps();
        });

        \App\Models\RewardTier::query()->create([
            'name' => 'Bronze',
            'min_coins' => 0,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        \App\Models\RewardTier::query()->create([
            'name' => 'Silver',
            'min_coins' => 100,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        Schema::create('coins', function (Blueprint $table): void {
            $table->id();
            $table->string('person_id');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->integer('coins')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('doctor_bookings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('patient_id')->nullable();
            $table->uuid('member_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->string('status')->nullable();
            $table->date('booking_date')->nullable();
            $table->timestamps();
        });

        Schema::create('hms_integrations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->json('hospital_ids');
            $table->char('api_key_hash', 64);
            $table->text('signing_secret');
            $table->json('allowed_ips')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('rate_limit_per_minute')->default(120);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hms_webhook_inbox', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('integration_id')->nullable();
            $table->uuid('request_id');
            $table->string('event_id')->nullable();
            $table->string('event_type');
            $table->string('method', 10);
            $table->string('path');
            $table->json('headers');
            $table->json('payload');
            $table->string('state', 32);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_body')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
        });

        Schema::create('hmis_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->string('hospital_code')->nullable();
            $table->string('mrn_id');
            $table->string('person_id')->nullable();
            $table->string('user_id')->nullable();
            $table->string('payment_type');
            $table->timestamp('paid_at')->nullable();
            $table->string('time_slot', 50)->nullable();
            $table->boolean('is_paid')->nullable();
            $table->string('status');
            $table->string('doctor_id')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('lab_test_name')->nullable();
            $table->decimal('original_amount', 12, 2)->default(0);
            $table->boolean('is_membership_applied')->default(false);
            $table->unsignedBigInteger('membership_id')->nullable();
            $table->decimal('percentage_discount_applied', 5, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->boolean('is_coins_used')->default(false);
            $table->unsignedInteger('coins_used')->default(0);
            $table->decimal('coin_discount_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->unsignedInteger('coins_earned')->default(0);
            $table->unsignedInteger('coins_reversed')->default(0);
            $table->boolean('is_refund')->default(false);
            $table->string('refund_of_transaction_id')->nullable();
            $table->json('flags')->nullable();
            $table->json('other_data')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('hmis_patient_savings', function (Blueprint $table): void {
            $table->id();
            $table->string('person_id')->unique();
            $table->decimal('lifetime_discount_total', 14, 2)->default(0);
            $table->decimal('membership_discount_total', 14, 2)->default(0);
            $table->decimal('coins_value_total', 14, 2)->default(0);
            $table->decimal('lifetime_paid_total', 14, 2)->default(0);
            $table->unsignedInteger('transactions_count')->default(0);
            $table->timestamps();
        });

        Schema::create('reward_points_transitions', function (Blueprint $table): void {
            $table->id();
            $table->string('hip_user_id')->nullable();
            $table->integer('previous_balance')->default(0);
            $table->integer('current_balance')->default(0);
            $table->integer('points_changed')->default(0);
            $table->integer('coins_earned')->nullable();
            $table->string('change_type')->nullable();
            $table->string('reward_source')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('booking_source')->nullable();
            $table->string('booking_id')->nullable();
            $table->string('occurrence_id')->nullable();
            $table->timestamps();
        });
    }
}
