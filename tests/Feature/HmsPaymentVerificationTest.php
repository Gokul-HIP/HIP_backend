<?php

namespace Tests\Feature;

use App\Models\Coins;
use App\Models\DoctorBooking;
use App\Models\HmsBooking;
use App\Models\HmsIntegration;
use App\Models\HmsPayment;
use App\Models\Invoice;
use App\Models\RewardTierDiscountUsage;
use App\Modules\Automation\Events\InvoiceGenerated;
use App\Modules\Automation\Events\PaymentReceived;
use App\Services\Hms\HmsSignature;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;

class HmsPaymentVerificationTest extends HmsBookingWebhookTest
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropIfExists('hms_payments');
        Schema::create('hms_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('integration_id');
            $table->unsignedBigInteger('hms_booking_row_id');
            $table->string('payment_reference');
            $table->string('status', 32);
            $table->decimal('amount', 12, 2);
            $table->string('method')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('result', 64);
            $table->json('payload');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['integration_id', 'payment_reference']);
        });
        DB::table('reward_tier_configs')->update(['free_checkup_count' => 20]);
    }

    public function test_doctor_second_opinion_and_package_settle_once(): void
    {
        Event::fake([InvoiceGenerated::class, PaymentReceived::class]);
        $doctor = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload());
        $doctor->assertCreated();
        $paid = $this->pay($this->paymentBody('HMS-10045', 'RCPT-1', 'success', 540));
        $paid->assertOk();
        $paid->assertJsonPath('result', 'settled');
        $paid->assertJsonPath('state', 'confirmed');
        $paid->assertJsonPath('paid_amount', 540);
        $this->assertSame('confirmed', DoctorBooking::query()->find($doctor->json('booking_id'))->status);
        $this->assertSame('paid', DoctorBooking::query()->find($doctor->json('booking_id'))->payment_status);
        $this->assertSame('completed', Invoice::query()->value('status'));
        $this->assertSame('consumed', RewardTierDiscountUsage::query()->value('status'));
        $this->assertSame(30, (int) Coins::query()->where('person_id', $this->primaryPersonId)->value('coins'));
        $this->assertSame(5, (int) DB::table('user_reward_progress')->where('hip_user_id', $this->userId)->value('earned_coins_in_tier'));
        Event::assertDispatched(InvoiceGenerated::class, 1);
        Event::assertDispatched(PaymentReceived::class, 1);

        $again = $this->pay($this->paymentBody('HMS-10045', 'RCPT-1', 'success', 540));
        $again->assertOk();
        $again->assertJsonPath('result', 'duplicate');
        $this->assertSame(30, (int) Coins::query()->where('person_id', $this->primaryPersonId)->value('coins'));
        Event::assertDispatched(InvoiceGenerated::class, 1);

        $secondReference = $this->pay($this->paymentBody('HMS-10045', 'RCPT-2', 'success', 540));
        $this->assertHmsError($secondReference, 409, 'already_paid');
        $this->assertSame(1, HmsPayment::query()->count());

        $second = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-SO',
            'appointment_type' => 'second_opinion',
        ]));
        $this->pay($this->paymentBody('HMS-SO', 'RCPT-SO', 'success', 540))->assertOk()->assertJsonPath('state', 'confirmed');
        $this->assertSame('confirmed', DB::table('second_opinions')->value('status'));

        $package = $this->book('/apiv1/client/diagnostic/booked', $this->packagePayload());
        $this->pay($this->paymentBody('HMS-PKG', 'RCPT-PKG', 'success', 900))->assertOk();
        $this->assertSame('confirmed', DB::table('diagnostic_test_bookings')->find($package->json('booking_id'))->status);
    }

    public function test_amount_tolerance_held_failed_then_success_and_status(): void
    {
        $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload(['hms_booking_id' => 'HMS-TOL']));
        $this->pay($this->paymentBody('HMS-TOL', 'RCPT-TOL', 'success', 540.01))->assertOk()->assertJsonPath('state', 'confirmed');

        $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload(['hms_booking_id' => 'HMS-BAD']));
        $bad = $this->pay($this->paymentBody('HMS-BAD', 'RCPT-BAD', 'success', 100));
        $this->assertHmsError($bad, 409, 'amount_mismatch');
        $bad->assertJsonPath('expected_amount', 540);
        $bad->assertJsonPath('received_amount', 100);
        $this->assertSame(HmsBooking::STATUS_PENDING, HmsBooking::query()->where('hms_booking_id', 'HMS-BAD')->value('status'));
        $this->assertSame('reserved', RewardTierDiscountUsage::query()->where('booking_id', HmsBooking::query()->where('hms_booking_id', 'HMS-BAD')->value('booking_id'))->value('status'));

        $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload(['hms_booking_id' => 'HMS-FAIL']));
        $failed = $this->pay($this->paymentBody('HMS-FAIL', 'RCPT-FAIL', 'failed', 540));
        $failed->assertOk();
        $failed->assertJsonPath('state', 'pending_payment');
        $this->assertSame('pending', DoctorBooking::query()->find(HmsBooking::query()->where('hms_booking_id', 'HMS-FAIL')->value('booking_id'))->status);
        $this->pay($this->paymentBody('HMS-FAIL', 'RCPT-FAIL-OK', 'success', 540))->assertOk()->assertJsonPath('state', 'confirmed');

        $status = $this->signedGet('/apiv1/client/booking/status?hms_booking_id=HMS-FAIL');
        $status->assertOk();
        $status->assertJsonPath('state', 'confirmed');
        $status->assertJsonPath('paid_amount', 540);
        $this->assertCount(2, $status->json('payments'));

        $this->createMembership();
        config(['settings.fees.service_charges' => 0, 'services.service_charges_percent' => 0]);
        $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-HELD',
            'payable_amount' => 1,
        ]));
        $held = $this->pay($this->paymentBody('HMS-HELD', 'RCPT-HELD', 'success', 0));
        $this->assertHmsError($held, 409, 'amount_mismatch');
        $this->assertSame(HmsBooking::STATUS_HELD, HmsBooking::query()->where('hms_booking_id', 'HMS-HELD')->value('status'));

        $byLocal = $this->pay([
            'booking_id' => (int) HmsBooking::query()->where('hms_booking_id', 'HMS-TOL')->value('booking_id'),
            'booking_type' => 'doctor',
            'payment' => ['reference' => 'RCPT-LOCAL', 'status' => 'success', 'amount' => 540, 'method' => 'upi'],
        ]);
        $this->assertHmsError($byLocal, 409, 'already_paid');
    }

    public function test_late_window_cancelled_zero_total_and_unknown_bookings(): void
    {
        config(['hms.late_payment_window_hours' => 24]);
        $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload(['hms_booking_id' => 'HMS-LATE']));
        HmsBooking::query()->where('hms_booking_id', 'HMS-LATE')->update(['hold_expires_at' => now()->subHour()]);
        Artisan::call('hms:bookings:expire-unpaid');
        $late = $this->pay($this->paymentBody('HMS-LATE', 'RCPT-LATE', 'success', 540));
        $late->assertOk();
        $late->assertJsonPath('result', 'late_settled');
        $late->assertJsonPath('state', 'confirmed');
        $this->assertSame(HmsPayment::RESULT_LATE, HmsPayment::query()->where('payment_reference', 'RCPT-LATE')->value('result'));
        $this->assertSame('confirmed', DoctorBooking::query()->find(HmsBooking::query()->where('hms_booking_id', 'HMS-LATE')->value('booking_id'))->status);

        $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload(['hms_booking_id' => 'HMS-OLD']));
        HmsBooking::query()->where('hms_booking_id', 'HMS-OLD')->update(['hold_expires_at' => now()->subHours(48)]);
        Artisan::call('hms:bookings:expire-unpaid');
        $old = $this->pay($this->paymentBody('HMS-OLD', 'RCPT-OLD', 'success', 540));
        $this->assertHmsError($old, 409, 'booking_expired');
        $this->assertSame('cancelled', DoctorBooking::query()->find(HmsBooking::query()->where('hms_booking_id', 'HMS-OLD')->value('booking_id'))->status);

        $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload(['hms_booking_id' => 'HMS-LIMIT']));
        $limitBookingId = (int) HmsBooking::query()->where('hms_booking_id', 'HMS-LIMIT')->value('booking_id');
        HmsBooking::query()->where('hms_booking_id', 'HMS-LIMIT')->update(['hold_expires_at' => now()->subHour()]);
        Artisan::call('hms:bookings:expire-unpaid');
        DB::table('reward_tier_configs')->update(['free_checkup_count' => 1]);
        RewardTierDiscountUsage::query()->where('booking_id', '!=', $limitBookingId)->update(['status' => 'released']);
        RewardTierDiscountUsage::query()->create([
            'hip_user_id' => $this->userId,
            'reward_tier_id' => 1,
            'tier_generation' => 0,
            'booking_type' => 'doctor_booking',
            'booking_id' => 9000,
            'status' => 'consumed',
        ]);
        $forced = $this->pay($this->paymentBody('HMS-LIMIT', 'RCPT-LIMIT', 'success', 540));
        $forced->assertOk();
        $this->assertStringContainsString('exhausted', (string) HmsPayment::query()->where('payment_reference', 'RCPT-LIMIT')->value('notes'));
        $this->assertSame('consumed', RewardTierDiscountUsage::query()->where('booking_id', $limitBookingId)->value('status'));

        DB::table('reward_tier_configs')->update(['free_checkup_count' => 20]);
        $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload(['hms_booking_id' => 'HMS-CAN']));
        HmsBooking::query()->where('hms_booking_id', 'HMS-CAN')->update(['status' => HmsBooking::STATUS_CANCELLED]);
        $cancelled = $this->pay($this->paymentBody('HMS-CAN', 'RCPT-CAN', 'success', 540));
        $this->assertHmsError($cancelled, 409, 'booking_cancelled');

        $this->createMembership();
        $free = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-FREE',
            'payable_amount' => 0,
        ]));
        $free->assertJsonPath('state', 'confirmed');
        $coins = (int) Coins::query()->where('person_id', $this->primaryPersonId)->value('coins');
        $over = $this->pay($this->paymentBody('HMS-FREE', 'RCPT-FREE-OVER', 'success', 50));
        $this->assertHmsError($over, 409, 'amount_mismatch');
        $this->assertSame(HmsPayment::RESULT_MISMATCH, HmsPayment::query()->where('payment_reference', 'RCPT-FREE-OVER')->value('result'));
        $this->assertSame('confirmed', DoctorBooking::query()->find($free->json('booking_id'))->status);
        $this->assertSame($coins, (int) Coins::query()->where('person_id', $this->primaryPersonId)->value('coins'));
        $this->pay($this->paymentBody('HMS-FREE', 'RCPT-FREE', 'success', 0))->assertOk()->assertJsonPath('result', 'settled')->assertJsonPath('state', 'confirmed');
        $this->assertSame($coins, (int) Coins::query()->where('person_id', $this->primaryPersonId)->value('coins'));
        $this->assertSame('confirmed', DoctorBooking::query()->find($free->json('booking_id'))->status);

        $this->assertHmsError($this->pay($this->paymentBody('HMS-MISSING', 'RCPT-X', 'success', 1)), 404, 'booking_not_found');

        $other = HmsIntegration::query()->create([
            'name' => 'Other',
            'hospital_ids' => [1],
            'organization_id' => 1,
            'api_key_hash' => HmsIntegration::hashKey('other-key'),
            'signing_secret' => 'other-secret',
            'is_enabled' => true,
            'rate_limit_per_minute' => 120,
        ]);
        $foreign = $this->pay($this->paymentBody('HMS-LATE', 'RCPT-FOREIGN', 'success', 540), 'other-key', 'other-secret');
        $this->assertHmsError($foreign, 404, 'booking_not_found');
        $this->assertSame($other->id, HmsIntegration::query()->where('name', 'Other')->value('id'));
    }

    public function test_membership_usage_is_confirmed_when_a_covered_booking_is_settled(): void
    {
        config(['settings.fees.service_charges' => 10, 'services.service_charges_percent' => 10]);
        $this->createMembership();
        $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-MEM',
            'payable_amount' => 60,
        ]))->assertCreated();
        $this->assertSame('membership_reserved', DB::table('subscription_usage_logs')->value('notes'));

        $this->pay($this->paymentBody('HMS-MEM', 'RCPT-MEM', 'success', 60))->assertOk()->assertJsonPath('state', 'confirmed');
        $this->assertSame('membership_consumed', DB::table('subscription_usage_logs')->value('notes'));
    }

    public function test_a_payment_reference_is_consumed_only_when_accepted(): void
    {
        $first = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload(['hms_booking_id' => 'HMS-REF-A']));
        $this->pay($this->paymentBody('HMS-REF-A', 'RCPT-SHARED', 'success', 540))->assertOk()->assertJsonPath('result', 'settled');
        $again = $this->pay($this->paymentBody('HMS-REF-A', 'RCPT-SHARED', 'success', 540));
        $again->assertOk()->assertJsonPath('result', 'duplicate');
        $this->assertSame(1, HmsPayment::query()->where('payment_reference', 'RCPT-SHARED')->count());

        $second = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload(['hms_booking_id' => 'HMS-REF-B']));
        $used = $this->pay($this->paymentBody('HMS-REF-B', 'RCPT-SHARED', 'success', 540));
        $this->assertHmsError($used, 409, 'payment_reference_used');
        $used->assertJsonPath('message', 'This payment reference was already used for another booking.');
        $this->assertArrayNotHasKey('booking_id', $used->json());
        $this->assertSame('pending', DoctorBooking::query()->find($second->json('booking_id'))->status);
        $this->assertSame('confirmed', DoctorBooking::query()->find($first->json('booking_id'))->status);

        $rejected = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload(['hms_booking_id' => 'HMS-REJ-A']));
        $this->assertHmsError($this->pay($this->paymentBody('HMS-REJ-A', 'RCPT-REUSE', 'success', 100)), 409, 'amount_mismatch');
        $moved = HmsPayment::query()->where('payment_reference', 'RCPT-REUSE')->first();
        $matched = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload(['hms_booking_id' => 'HMS-REJ-B']));
        $settled = $this->pay($this->paymentBody('HMS-REJ-B', 'RCPT-REUSE', 'success', 540));
        $settled->assertOk()->assertJsonPath('result', 'settled')->assertJsonPath('state', 'confirmed');
        $moved->refresh();
        $this->assertSame(1, HmsPayment::query()->where('payment_reference', 'RCPT-REUSE')->count());
        $this->assertSame((int) HmsBooking::query()->where('hms_booking_id', 'HMS-REJ-B')->value('id'), (int) $moved->hms_booking_row_id);
        $this->assertSame(HmsPayment::RESULT_SETTLED, $moved->result);
        $this->assertStringContainsString('HMS-REJ-A', (string) $moved->notes);
        $this->assertStringContainsString(HmsPayment::RESULT_MISMATCH, (string) $moved->notes);
        $this->assertSame('pending', DoctorBooking::query()->find($rejected->json('booking_id'))->status);
        $this->assertSame('confirmed', DoctorBooking::query()->find($matched->json('booking_id'))->status);

        $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload(['hms_booking_id' => 'HMS-FAIL-REP']));
        $failed = $this->pay($this->paymentBody('HMS-FAIL-REP', 'RCPT-FAIL-REP', 'failed', 540), 'hms-test-key', 'hms-test-secret', 'evt-fail-rep');
        $failed->assertOk()->assertJsonPath('result', HmsPayment::RESULT_FAILED);
        $stored = $this->pay($this->paymentBody('HMS-FAIL-REP', 'RCPT-FAIL-REP', 'success', 540), 'hms-test-key', 'hms-test-secret', 'evt-fail-rep');
        $stored->assertOk()->assertJsonPath('result', HmsPayment::RESULT_FAILED);
        $this->assertSame('pending', DoctorBooking::query()->find(HmsBooking::query()->where('hms_booking_id', 'HMS-FAIL-REP')->value('booking_id'))->status);
        $retried = $this->pay($this->paymentBody('HMS-FAIL-REP', 'RCPT-FAIL-REP', 'success', 540));
        $retried->assertOk()->assertJsonPath('result', 'settled');
        $failedRow = HmsPayment::query()->where('payment_reference', 'RCPT-FAIL-REP')->first();
        $this->assertSame(1, HmsPayment::query()->where('payment_reference', 'RCPT-FAIL-REP')->count());
        $this->assertSame(HmsPayment::RESULT_SETTLED, $failedRow->result);
        $this->assertSame('success', $failedRow->status);
        $this->assertStringContainsString('HMS-FAIL-REP', (string) $failedRow->notes);
        $this->assertStringContainsString(HmsPayment::RESULT_FAILED, (string) $failedRow->notes);

        $kept = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload(['hms_booking_id' => 'HMS-KEPT']));
        $this->pay($this->paymentBody('HMS-KEPT', 'RCPT-KEPT', 'success', 540))->assertOk()->assertJsonPath('result', 'settled');
        HmsBooking::query()->where('hms_booking_id', 'HMS-KEPT')->update(['status' => HmsBooking::STATUS_CANCELLED]);
        $other = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload(['hms_booking_id' => 'HMS-OTHER']));
        $blocked = $this->pay($this->paymentBody('HMS-OTHER', 'RCPT-KEPT', 'success', 540));
        $this->assertHmsError($blocked, 409, 'payment_reference_used');
        $this->assertArrayNotHasKey('booking_id', $blocked->json());
        $keptPayment = HmsPayment::query()->where('payment_reference', 'RCPT-KEPT')->first();
        $this->assertSame(HmsPayment::RESULT_SETTLED, $keptPayment->result);
        $this->assertSame((int) HmsBooking::query()->where('hms_booking_id', 'HMS-KEPT')->value('id'), (int) $keptPayment->hms_booking_row_id);
        $this->assertSame('confirmed', DoctorBooking::query()->find($kept->json('booking_id'))->status);
        $this->assertSame('pending', DoctorBooking::query()->find($other->json('booking_id'))->status);
    }

    protected function paymentBody(string $hmsBookingId, string $reference, string $status, float $amount): array
    {
        return [
            'hms_booking_id' => $hmsBookingId,
            'payment' => [
                'reference' => $reference,
                'status' => $status,
                'amount' => $amount,
                'method' => 'cash',
                'paid_at' => now()->toIso8601String(),
            ],
        ];
    }

    private int $payClock = 100;

    protected function pay(array $payload, string $key = 'hms-test-key', string $secret = 'hms-test-secret', ?string $eventId = null): TestResponse
    {
        $raw = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $timestamp = (string) (now()->getTimestamp() + $this->payClock++);
        $uri = '/apiv1/client/payment-verification';
        $signature = HmsSignature::sign($secret, $timestamp, 'POST', $uri, $raw === false ? '' : $raw);

        return $this->call('POST', $uri, [], [], [], [
            'HTTP_X_HMS_KEY' => $key,
            'HTTP_X_HMS_TIMESTAMP' => $timestamp,
            'HTTP_X_HMS_SIGNATURE' => $signature,
            'HTTP_X_HMS_EVENT_ID' => $eventId ?? ('pay-'.$this->payClock),
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], $raw === false ? '' : $raw);
    }

    private function signedGet(string $uri): TestResponse
    {
        $timestamp = (string) (now()->getTimestamp() + $this->payClock++);
        $signature = HmsSignature::sign('hms-test-secret', $timestamp, 'GET', $uri, '');

        return $this->call('GET', $uri, [], [], [], [
            'HTTP_X_HMS_KEY' => 'hms-test-key',
            'HTTP_X_HMS_TIMESTAMP' => $timestamp,
            'HTTP_X_HMS_SIGNATURE' => $signature,
            'HTTP_ACCEPT' => 'application/json',
        ]);
    }
}
