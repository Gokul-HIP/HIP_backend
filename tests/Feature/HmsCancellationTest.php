<?php

namespace Tests\Feature;

use App\Models\Coins;
use App\Models\DoctorBooking;
use App\Models\HmsBooking;
use App\Models\HmsIntegration;
use App\Models\HmsPayment;
use App\Models\Invoice;
use App\Models\RewardTierDiscountUsage;
use App\Models\UserFamilySubscription;
use App\Modules\Automation\Events\AppointmentCancelled;
use App\Services\Hms\HmsSignature;
use App\Services\NotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;

class HmsCancellationTest extends HmsPaymentVerificationTest
{
    private int $cancelClock = 20;

    private MockInterface $notifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notifier = $this->mock(NotificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('notifyUser')->andReturn(false);
        });
        Schema::table('hospitals', function (Blueprint $table): void {
            $table->unsignedBigInteger('organization_id')->nullable();
        });
        Schema::dropIfExists('reward_points_transitions');
        Schema::dropIfExists('hms_memberships');
        Schema::create('reward_points_transitions', function (Blueprint $table): void {
            $table->id();
            $table->string('hip_user_id');
            $table->integer('previous_balance');
            $table->integer('current_balance');
            $table->integer('points_changed');
            $table->integer('coins_earned')->nullable();
            $table->string('change_type');
            $table->string('reward_source');
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('booking_source')->nullable();
            $table->string('booking_id')->nullable();
            $table->string('occurrence_id')->nullable();
            $table->timestamps();
        });
        Schema::create('hms_memberships', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('integration_id');
            $table->string('hms_membership_id');
            $table->unsignedBigInteger('subscription_id');
            $table->unsignedBigInteger('plan_id');
            $table->uuid('hip_user_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('type', 16);
            $table->decimal('amount_paid', 12, 2);
            $table->decimal('expected_amount', 12, 2);
            $table->boolean('price_mismatch')->default(false);
            $table->string('payment_reference');
            $table->char('request_hash', 64);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_pending_cancel_releases_reservations(): void
    {
        config(['settings.fees.service_charges' => 10, 'services.service_charges_percent' => 10]);
        $this->createMembership();
        $booked = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-HOLD',
            'payable_amount' => 60,
        ]));
        $booked->assertCreated();
        $this->assertSame('membership_reserved', DB::table('subscription_usage_logs')->value('notes'));

        $held = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-HELD',
            'payable_amount' => 1,
        ]));
        $held->assertCreated();
        $this->assertSame('held_mismatch', HmsBooking::query()->where('hms_booking_id', 'HMS-HELD')->value('status'));

        $cancelled = $this->cancelBooking('HMS-HOLD');
        $cancelled->assertOk();
        $cancelled->assertJsonPath('state', 'cancelled');
        $cancelled->assertJsonPath('booking_id', $booked->json('booking_id'));
        $cancelled->assertJsonPath('refund', null);
        $cancelled->assertJsonPath('coins_reversed', 0);
        $this->assertNull(HmsBooking::query()->where('hms_booking_id', 'HMS-HOLD')->value('refund_status'));
        $this->assertSame('cancelled', DoctorBooking::query()->find($booked->json('booking_id'))->status);
        $this->assertSame('cancelled', Invoice::query()->find(HmsBooking::query()->where('hms_booking_id', 'HMS-HOLD')->value('invoice_id'))->status);
        $this->assertSame(0, DB::table('subscription_usage_logs')->where('booking_id', $booked->json('booking_id'))->count());
        $this->assertSame('patient request', HmsBooking::query()->where('hms_booking_id', 'HMS-HOLD')->value('cancel_reason'));

        $this->cancelBooking('HMS-HELD')->assertOk()->assertJsonPath('state', 'cancelled');
        $this->assertSame('cancelled', HmsBooking::query()->where('hms_booking_id', 'HMS-HELD')->value('status'));
        $this->notifier->shouldNotHaveReceived('notifyUser');
    }

    public function test_confirmed_cancels_return_usage_reward_and_coins(): void
    {
        Event::fake([AppointmentCancelled::class]);
        $doctor = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload());
        $doctor->assertCreated();
        $this->pay($this->paymentBody('HMS-10045', 'RCPT-DOC', 'success', 540))->assertOk();
        $this->assertSame('consumed', RewardTierDiscountUsage::query()->where('booking_type', 'doctor_booking')->value('status'));

        DB::table('reward_tiers')->insert([
            'id' => 2,
            'name' => 'Silver',
            'min_coins' => 1000,
            'sort_order' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('reward_tier_configs')->insert([
            'reward_tier_id' => 2,
            'total_discount_percentage' => 10,
            'free_checkup_count' => 20,
            'earned_coins_per_booking' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('user_reward_progress')->where('hip_user_id', $this->userId)->update([
            'reward_tier_id' => 2,
            'earned_coins_in_tier' => 3,
            'total_lifetime_coins' => 100,
        ]);
        Coins::query()->where('person_id', $this->primaryPersonId)->update(['coins' => 2]);

        $cancelled = $this->cancelBooking('HMS-10045');
        $cancelled->assertOk();
        $cancelled->assertJsonPath('state', 'cancelled');
        $cancelled->assertJsonPath('booking_type', 'doctor');
        $cancelled->assertJsonPath('refund.status', 'refund_due');
        $cancelled->assertJsonPath('refund.amount', 540);
        $cancelled->assertJsonPath('coins_reversed', 2);
        $this->assertSame(0, (int) Coins::query()->where('person_id', $this->primaryPersonId)->value('coins'));
        $progress = DB::table('user_reward_progress')->where('hip_user_id', $this->userId)->first();
        $this->assertSame(2, (int) $progress->reward_tier_id);
        $this->assertSame(0, (int) $progress->earned_coins_in_tier);
        $this->assertSame(95, (int) $progress->total_lifetime_coins);
        $this->assertSame('released', RewardTierDiscountUsage::query()->where('booking_type', 'doctor_booking')->value('status'));
        $reversal = DB::table('reward_points_transitions')->where('change_type', 'adjusted')->first();
        $this->assertSame(2, (int) $reversal->previous_balance);
        $this->assertSame(0, (int) $reversal->current_balance);
        $this->assertSame(-2, (int) $reversal->points_changed);
        $this->assertSame('completed', Invoice::query()->value('status'));
        $this->assertSame('completed', DB::table('transactions')->value('status'));
        Event::assertDispatched(AppointmentCancelled::class, 1);

        $second = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-SO',
            'appointment_type' => 'second_opinion',
        ]));
        $second->assertCreated();
        $this->pay($this->paymentBody('HMS-SO', 'RCPT-SO', 'success', 540))->assertOk();
        $coinsAfterSecond = (int) Coins::query()->where('person_id', $this->primaryPersonId)->value('coins');
        $this->assertGreaterThan(0, $coinsAfterSecond);
        $this->cancelBooking('HMS-SO')->assertOk()->assertJsonPath('state', 'cancelled');
        $this->assertSame('cancelled', DB::table('second_opinions')->value('status'));
        $this->assertSame(0, (int) Coins::query()->where('person_id', $this->primaryPersonId)->value('coins'));
        $this->assertSame('released', RewardTierDiscountUsage::query()->where('booking_type', 'second_opinion')->value('status'));

        $this->createMembership([
            'benefits' => json_encode([[
                'diagnostic_package_id' => 5,
                'benefit_type' => 'free',
                'max_lab_tests' => 1,
            ]]),
            'covered_member_ids' => json_encode([$this->primaryPersonId]),
        ]);
        $coveredDoctor = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-FREE',
            'payable_amount' => 0,
        ]));
        $coveredDoctor->assertCreated();
        $coveredDoctor->assertJsonPath('state', 'confirmed');
        $coveredDoctor->assertJsonPath('pricing.membership.applied', true);
        $package = $this->book('/apiv1/client/diagnostic/booked', $this->packagePayload([
            'hms_booking_id' => 'HMS-PKG-FREE',
            'payable_amount' => 0,
        ]));
        $package->assertCreated();
        $package->assertJsonPath('pricing.membership.applied', true);
        $this->assertSame(2, DB::table('subscription_usage_logs')->whereIn('notes', ['membership_consumed', 'membership_reserved'])->count());

        $freeCancel = $this->cancelBooking('HMS-FREE');
        $freeCancel->assertOk();
        $freeCancel->assertJsonPath('refund', null);
        $freeCancel->assertJsonPath('coins_reversed', 0);
        $this->assertNull(HmsBooking::query()->where('hms_booking_id', 'HMS-FREE')->value('refund_status'));
        $this->cancelBooking('HMS-PKG-FREE')->assertOk()->assertJsonPath('state', 'cancelled');
        $this->assertSame('cancelled', DB::table('diagnostic_test_bookings')->value('status'));
        $this->assertSame(0, DB::table('subscription_usage_logs')->count());
        $this->assertSame('completed', Invoice::query()->where('doctor_booking_id', $coveredDoctor->json('booking_id'))->value('status'));
        $this->assertSame('completed', Invoice::query()->where('diagnostic_test_booking_id', $package->json('booking_id'))->value('status'));
        Event::assertDispatched(AppointmentCancelled::class, 2);
        $this->notifier->shouldHaveReceived('notifyUser')->times(4);
    }

    public function test_a_paid_cancel_records_the_refund_and_accepts_the_hip_id(): void
    {
        Event::fake([AppointmentCancelled::class]);
        $paid = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload(['hms_booking_id' => 'HMS-PAID']));
        $paid->assertCreated();
        $this->pay($this->paymentBody('HMS-PAID', 'RCPT-PAID', 'success', 540))->assertOk();
        $bookingId = (int) $paid->json('booking_id');

        $cancelled = $this->signedCancel('/apiv1/client/booking/cancelled', [
            'booking_type' => 'doctor',
            'booking_id' => $bookingId,
            'reason' => 'patient request',
        ], 'evt-paid');
        $cancelled->assertOk();
        $cancelled->assertJsonPath('state', 'cancelled');
        $cancelled->assertJsonPath('booking_id', $bookingId);
        $cancelled->assertJsonPath('booking_type', 'doctor');
        $cancelled->assertJsonPath('refund.status', 'refund_due');
        $cancelled->assertJsonPath('refund.amount', 540);
        $cancelled->assertJsonPath('coins_reversed', 5);
        $this->assertSame(25, (int) Coins::query()->where('person_id', $this->primaryPersonId)->value('coins'));
        $this->assertSame('completed', Invoice::query()->value('status'));
        $this->assertSame('completed', DB::table('transactions')->value('status'));

        $repeat = $this->signedCancel('/apiv1/client/booking/cancelled', [
            'hms_booking_id' => 'HMS-PAID',
            'booking_type' => 'doctor',
            'booking_id' => $bookingId,
            'reason' => 'again',
        ], 'evt-paid-2');
        $repeat->assertOk();
        $repeat->assertJsonPath('refund.status', 'refund_due');
        $repeat->assertJsonPath('refund.amount', 540);
        $repeat->assertJsonPath('coins_reversed', 5);
        $this->assertSame('patient request', HmsBooking::query()->where('hms_booking_id', 'HMS-PAID')->value('cancel_reason'));
        $this->assertSame(25, (int) Coins::query()->where('person_id', $this->primaryPersonId)->value('coins'));
        $this->assertSame(1, DB::table('reward_points_transitions')->where('change_type', 'adjusted')->count());

        $status = $this->bookingStatus('hms_booking_id=HMS-PAID');
        $status->assertOk();
        $status->assertJsonPath('refund.status', 'refund_due');
        $status->assertJsonPath('refund.amount', 540);
        $status->assertJsonPath('coins_reversed', 5);
        $byHip = $this->bookingStatus('booking_type=doctor&booking_id='.$bookingId);
        $byHip->assertOk();
        $byHip->assertJsonPath('hms_booking_id', 'HMS-PAID');
        $byHip->assertJsonPath('refund.amount', 540);

        $left = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload(['hms_booking_id' => 'HMS-LEFT']));
        $right = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload(['hms_booking_id' => 'HMS-RIGHT']));
        $mismatch = $this->signedCancel('/apiv1/client/booking/cancelled', [
            'hms_booking_id' => 'HMS-LEFT',
            'booking_type' => 'doctor',
            'booking_id' => (int) $right->json('booking_id'),
        ], 'evt-mix');
        $this->assertHmsError($mismatch, 422, 'validation_error');
        $this->assertSame('pending', DoctorBooking::query()->find($left->json('booking_id'))->status);

        DB::table('doctor_bookings')->insert([
            'id' => 900001,
            'doctor_id' => $this->doctorId,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertHmsError($this->signedCancel('/apiv1/client/booking/cancelled', [
            'booking_type' => 'doctor',
            'booking_id' => 900001,
        ], 'evt-app'), 404, 'booking_not_found');
        $this->assertSame('confirmed', DB::table('doctor_bookings')->where('id', 900001)->value('status'));

        $other = HmsIntegration::query()->create([
            'name' => 'Other HMS',
            'hospital_ids' => [1],
            'organization_id' => 1,
            'api_key_hash' => HmsIntegration::hashKey('other-key'),
            'signing_secret' => 'other-secret',
            'is_enabled' => true,
            'rate_limit_per_minute' => 120,
        ]);
        DB::table('doctor_bookings')->insert([
            'id' => 900002,
            'doctor_id' => $this->doctorId,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        HmsBooking::query()->create([
            'integration_id' => $other->id,
            'hms_booking_id' => 'HMS-OTHER-INT',
            'booking_type' => 'doctor',
            'booking_id' => 900002,
            'hip_user_id' => $this->userId,
            'person_id' => $this->primaryPersonId,
            'hospital_id' => 1,
            'status' => HmsBooking::STATUS_CONFIRMED,
            'expected_amount' => 540,
            'hms_payable_amount' => 540,
            'price_mismatch' => false,
            'pricing_snapshot' => [],
            'request_hash' => str_repeat('b', 64),
        ]);
        $this->assertHmsError($this->signedCancel('/apiv1/client/booking/cancelled', [
            'booking_type' => 'doctor',
            'booking_id' => 900002,
        ], 'evt-foreign'), 404, 'booking_not_found');
        $this->assertSame(HmsBooking::STATUS_CONFIRMED, HmsBooking::query()->where('hms_booking_id', 'HMS-OTHER-INT')->value('status'));
        $this->assertNull(HmsBooking::query()->where('hms_booking_id', 'HMS-OTHER-INT')->value('refund_status'));
    }

    public function test_repeat_expired_completed_unknown_and_payment_after_cancel(): void
    {
        $booked = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-ONCE',
        ]));
        $booked->assertCreated();
        $first = $this->cancelBooking('HMS-ONCE', 'evt-once');
        $first->assertOk();
        $again = $this->cancelBooking('HMS-ONCE', 'evt-once-2');
        $again->assertOk();
        $again->assertJsonPath('state', 'cancelled');
        $again->assertJsonPath('booking_id', $first->json('booking_id'));
        $stored = $this->cancelBooking('HMS-ONCE', 'evt-once');
        $stored->assertOk();
        $stored->assertJsonPath('booking_id', $first->json('booking_id'));
        $this->assertSame(1, DB::table('doctor_booking_statuses')->where('to_status', 'cancelled')->count());

        $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-EXP',
        ]))->assertCreated();
        HmsBooking::query()->where('hms_booking_id', 'HMS-EXP')->update(['status' => 'expired']);
        $expired = $this->cancelBooking('HMS-EXP');
        $expired->assertOk();
        $expired->assertJsonPath('state', 'expired');
        $this->assertSame('pending', DoctorBooking::query()->find($expired->json('booking_id'))->status);

        $completed = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-DONE',
        ]));
        $completed->assertCreated();
        $this->pay($this->paymentBody('HMS-DONE', 'RCPT-DONE', 'success', 540))->assertOk();
        DoctorBooking::query()->whereKey($completed->json('booking_id'))->update([
            'status' => 'completed',
            'appointment_status' => 'completed',
        ]);
        $this->assertHmsError($this->cancelBooking('HMS-DONE'), 409, 'booking_not_cancellable');
        $this->assertSame('confirmed', HmsBooking::query()->where('hms_booking_id', 'HMS-DONE')->value('status'));
        $this->assertSame(30, (int) Coins::query()->where('person_id', $this->primaryPersonId)->value('coins'));

        $this->assertHmsError($this->cancelBooking('HMS-MISSING'), 404, 'booking_not_found');

        $pending = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-PAY',
        ]));
        $pending->assertCreated();
        $this->cancelBooking('HMS-PAY')->assertOk();
        $paid = $this->pay($this->paymentBody('HMS-PAY', 'RCPT-LATE', 'success', 540));
        $this->assertHmsError($paid, 409, 'booking_cancelled');
        $this->assertSame(HmsPayment::RESULT_CANCELLED, HmsPayment::query()->where('payment_reference', 'RCPT-LATE')->value('result'));
    }

    public function test_membership_cancel_stops_the_next_free_booking(): void
    {
        config(['settings.fees.service_charges' => 10, 'services.service_charges_percent' => 10]);
        $this->createMembership(['covered_member_ids' => json_encode([$this->primaryPersonId])]);
        $subscriptionId = (int) UserFamilySubscription::query()->value('id');
        DB::table('hms_memberships')->insert([
            'integration_id' => $this->integration->id,
            'hms_membership_id' => 'HMS-M-501',
            'subscription_id' => $subscriptionId,
            'plan_id' => 1,
            'hip_user_id' => $this->userId,
            'hospital_id' => 1,
            'type' => 'new',
            'amount_paid' => 1000,
            'expected_amount' => 1000,
            'price_mismatch' => false,
            'payment_reference' => 'RCPT-M',
            'request_hash' => str_repeat('a', 64),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reserved = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-RES',
            'payable_amount' => 60,
        ]));
        $reserved->assertCreated();
        $reserved->assertJsonPath('pricing.membership.applied', true);
        $this->assertSame('membership_reserved', DB::table('subscription_usage_logs')->value('notes'));

        $cancelled = $this->cancelMembership('HMS-M-501');
        $cancelled->assertOk();
        $cancelled->assertJsonPath('state', 'cancelled');
        $cancelled->assertJsonPath('subscription_id', $subscriptionId);
        $this->assertSame('cancelled', UserFamilySubscription::query()->value('status'));
        $this->assertSame(1, DB::table('subscription_usage_logs')->count());

        $repeat = $this->cancelMembership('HMS-M-501', 'evt-mem-2');
        $repeat->assertOk();
        $repeat->assertJsonPath('subscription_id', $subscriptionId);

        $next = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-NEXT',
            'payable_amount' => 540,
        ]));
        $next->assertCreated();
        $next->assertJsonPath('pricing.membership.applied', false);

        $this->pay($this->paymentBody('HMS-RES', 'RCPT-RES', 'success', 60))->assertOk()->assertJsonPath('state', 'confirmed');
        $this->assertSame('membership_consumed', DB::table('subscription_usage_logs')->where('booking_id', $reserved->json('booking_id'))->value('notes'));
        $this->assertHmsError($this->cancelMembership('HMS-NO'), 404, 'membership_not_found');
    }

    private function cancelBooking(string $hmsBookingId, string $eventId = 'evt-auto'): TestResponse
    {
        return $this->signedCancel('/apiv1/client/booking/cancelled', [
            'hms_booking_id' => $hmsBookingId,
            'reason' => 'patient request',
            'cancelled_at' => '2026-10-28T18:00:00+05:30',
        ], $eventId);
    }

    private function cancelMembership(string $hmsMembershipId, string $eventId = 'evt-auto'): TestResponse
    {
        return $this->signedCancel('/apiv1/client/membership/cancelled', [
            'hms_membership_id' => $hmsMembershipId,
            'reason' => 'patient request',
            'cancelled_at' => '2026-10-28T18:00:00+05:30',
        ], $eventId);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function bookingStatus(string $query): TestResponse
    {
        $uri = '/apiv1/client/booking/status?'.$query;
        $timestamp = (string) (now()->getTimestamp() + $this->cancelClock++);
        $signature = HmsSignature::sign('hms-test-secret', $timestamp, 'GET', $uri, '');

        return $this->call('GET', $uri, [], [], [], [
            'HTTP_X_HMS_KEY' => 'hms-test-key',
            'HTTP_X_HMS_TIMESTAMP' => $timestamp,
            'HTTP_X_HMS_SIGNATURE' => $signature,
            'HTTP_ACCEPT' => 'application/json',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function signedCancel(string $uri, array $payload, string $eventId): TestResponse
    {
        $raw = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $timestamp = (string) (now()->getTimestamp() + $this->cancelClock++);
        $signature = HmsSignature::sign('hms-test-secret', $timestamp, 'POST', $uri, $raw === false ? '' : $raw);

        return $this->call('POST', $uri, [], [], [], [
            'HTTP_X_HMS_KEY' => 'hms-test-key',
            'HTTP_X_HMS_TIMESTAMP' => $timestamp,
            'HTTP_X_HMS_SIGNATURE' => $signature,
            'HTTP_X_HMS_EVENT_ID' => $eventId === 'evt-auto' ? 'evt-c-'.$this->cancelClock : $eventId,
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], $raw === false ? '' : $raw);
    }
}
