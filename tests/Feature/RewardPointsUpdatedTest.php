<?php

namespace Tests\Feature;

use App\Models\Coins;
use App\Models\HIPUser;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\RewardPointsTransition;
use App\Models\RewardTier;
use App\Models\RewardTierConfig;
use App\Modules\Automation\Engine\AutomationEngine;
use App\Modules\Automation\Events\RewardPointsUpdated;
use App\Modules\Automation\Support\RewardPointsContext;
use App\Modules\Automation\Support\RewardPointsScope;
use App\Modules\Workflow\Enums\CommunicationStatus;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Models\CommunicationLog;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Repositories\WorkflowRepository;
use App\Modules\Workflow\Services\Builder\VariableCatalogService;
use App\Services\Api\PaymentApiService;
use App\Services\CoinsWalletService;
use App\Services\DoctorNotificationService;
use App\Services\NotificationService;
use App\Services\RewardTierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RewardPointsUpdatedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => [
                'database/migrations/2025_12_10_111601_create_organizations_table.php',
                'database/migrations/2026_07_21_100000_create_workflows_table.php',
                'database/migrations/2026_07_21_100001_create_workflow_versions_table.php',
                'database/migrations/2026_07_21_100002_create_workflow_executions_table.php',
                'database/migrations/2026_07_21_100003_create_communication_logs_table.php',
                'database/migrations/2026_07_21_130000_add_draft_version_id_to_workflows_table.php',
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->fail('Reward points workflow tests must use the sqlite testing database.');
        }

        if (! Schema::hasColumn('workflows', 'hospital_id')) {
            Schema::table('workflows', function ($table) {
                $table->unsignedBigInteger('hospital_id')->nullable();
            });
        }

        $this->bootDomainTables();
    }

    public function test_appointment_payment_earns_coins_and_scopes_to_that_booking_hospital(): void
    {
        $this->organization(1);
        $this->organization(2);
        $this->hospital(5, 1);
        $this->hospital(9, 2);

        $titles = [];
        $this->mock(NotificationService::class, function ($mock) use (&$titles): void {
            $mock->shouldReceive('notifyUser')->andReturnUsing(function ($userId, $title) use (&$titles) {
                $titles[] = (string) $title;

                return true;
            });
            $mock->shouldReceive('sendToDevice')->never();
        });
        $this->mock(DoctorNotificationService::class, function ($mock): void {
            $mock->shouldReceive('notifyDoctorOfNewBooking')->once()->andReturn(null);
        });

        $user = HIPUser::withoutEvents(fn () => HIPUser::query()->create([
            'email' => 'points@example.com',
            'first_name' => 'Ada',
            'last_name' => 'Patient',
            'hip_id' => 'HIP-POINTS',
            'is_active' => true,
            'password' => null,
            'coins_balance' => 0,
        ]));
        $person = Persons::withoutEvents(fn () => Persons::query()->create([
            'first_name' => 'Ada',
            'last_name' => 'Patient',
            'hip_user_id' => $user->id,
            'is_primary' => true,
            'mobile' => '9876543210',
            'email' => 'ada@example.com',
        ]));

        $bronze = RewardTier::query()->create([
            'name' => 'Bronze',
            'min_coins' => 0,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        RewardTierConfig::query()->create([
            'reward_tier_id' => $bronze->id,
            'total_discount_percentage' => 0,
            'free_checkup_count' => 0,
            'earned_coins_per_booking' => 0,
        ]);
        $silver = RewardTier::query()->create([
            'name' => 'Silver',
            'min_coins' => 5,
            'sort_order' => 2,
            'is_active' => true,
        ]);
        RewardTierConfig::query()->create([
            'reward_tier_id' => $silver->id,
            'total_discount_percentage' => 0,
            'free_checkup_count' => 0,
            'earned_coins_per_booking' => 0,
        ]);
        app(RewardTierService::class)->enrollUserOnRegister((string) $user->id);

        DB::table('doctor_bookings')->insert([
            [
                'id' => 77,
                'member_id' => $user->id,
                'patient_id' => $person->id,
                'hospital_id' => 5,
                'branch_id' => 5,
                'status' => 'pending',
                'created_at' => '2026-10-01 09:00:00',
                'updated_at' => '2026-10-01 09:00:00',
            ],
            [
                'id' => 88,
                'member_id' => $user->id,
                'patient_id' => $person->id,
                'hospital_id' => 9,
                'branch_id' => 9,
                'status' => 'confirmed',
                'created_at' => '2026-10-20 09:00:00',
                'updated_at' => '2026-10-20 09:00:00',
            ],
        ]);

        $pointsHere = $this->publishWorkflow(1, 5, 'rewardPointsUpdated', 'Points here');
        $pointsElsewhere = $this->publishWorkflow(2, 9, 'rewardPointsUpdated', 'Points elsewhere');
        $tierHere = $this->publishWorkflow(1, 5, 'rewardTierUpgraded', 'Tier here');
        $tierLatest = $this->publishWorkflow(2, 9, 'rewardTierUpgraded', 'Tier latest booking');

        $invoice = new Invoice();
        $invoice->stopUserstamping();
        $invoice->forceFill([
            'primary_person_id' => $person->id,
            'person_id' => $person->id,
            'doctor_booking_id' => 77,
            'service_types' => ['doctor_consultation'],
            'status' => 'pending',
            'amount' => 500,
            'total_amount' => 500,
            'coins_applied' => 0,
            'coins_earned' => 0,
            'service_charges' => 0,
            'payment_gateway_charges' => 0,
            'total_gst' => 0,
            'discount_price' => 0,
        ])->save();

        $result = app(PaymentApiService::class)->completeInvoicePayment([
            'invoice_id' => $invoice->id,
            'payment_method' => 'cash',
            'total_amount' => 500,
            'original_amount' => 500,
            'status' => 'completed',
            'primary_person_id' => $person->id,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(5, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
        $this->assertSame(5, (int) $user->fresh()->coins_balance);
        $this->assertSame('completed', $invoice->fresh()->status);

        $pointsExecution = WorkflowExecution::query()->where('workflow_id', $pointsHere->id)->first();
        $this->assertNotNull($pointsExecution);
        $this->assertSame(0, WorkflowExecution::query()->where('workflow_id', $pointsElsewhere->id)->count());
        $this->assertSame(5, (int) data_get($pointsExecution->context, 'hospital_id'));
        $this->assertSame(1, (int) data_get($pointsExecution->context, 'organization_id'));
        $this->assertSame((string) $user->id, data_get($pointsExecution->context, 'member_id'));
        $this->assertSame(5, (int) data_get($pointsExecution->context, 'rewards.points_changed'));
        $this->assertSame(5, (int) data_get($pointsExecution->context, 'rewards.coins_earned'));
        $this->assertSame(5, (int) $invoice->fresh()->coins_earned);
        $this->assertSame('earned', data_get($pointsExecution->context, 'rewards.change_type'));
        $this->assertSame('appointment_payment', data_get($pointsExecution->context, 'rewards.source'));
        $this->assertStringStartsWith('reward-points-updated:transition:', (string) data_get($pointsExecution->context, 'event_occurrence_id'));
        $this->assertNotSame(
            data_get($pointsExecution->context, 'event_occurrence_id'),
            data_get($pointsExecution->context, 'member_id')
        );

        $tierExecution = WorkflowExecution::query()->where('workflow_id', $tierLatest->id)->first();
        $this->assertNotNull($tierExecution);
        $this->assertSame(0, WorkflowExecution::query()->where('workflow_id', $tierHere->id)->count());
        $this->assertSame(9, (int) data_get($tierExecution->context, 'hospital_id'));
        $this->assertStringStartsWith('reward-tier-upgraded:', (string) data_get($tierExecution->context, 'event_occurrence_id'));
        $this->assertNotSame(
            data_get($pointsExecution->context, 'event_occurrence_id'),
            data_get($tierExecution->context, 'event_occurrence_id')
        );
        $this->assertNotContains('Coins earned', $titles);
    }

    public function test_package_pharmacy_and_second_opinion_use_their_own_hospital(): void
    {
        $this->organization(1);
        $this->organization(2);
        $this->hospital(8, 1);
        $this->hospital(4, 1);
        $this->hospital(6, 1);
        $this->hospital(99, 2);
        $personId = $this->person('user-src');
        DB::table('doctor_bookings')->insert([
            'id' => 50,
            'member_id' => 'user-src',
            'patient_id' => $personId,
            'hospital_id' => 99,
            'status' => 'confirmed',
            'created_at' => '2026-10-22 10:00:00',
            'updated_at' => '2026-10-22 10:00:00',
        ]);

        $packageWorkflow = $this->publishWorkflow(1, 8, 'rewardPointsUpdated', 'Package');
        $pharmacyWorkflow = $this->publishWorkflow(1, 4, 'rewardPointsUpdated', 'Pharmacy');
        $opinionWorkflow = $this->publishWorkflow(1, 6, 'rewardPointsUpdated', 'Opinion');
        $unrelated = $this->publishWorkflow(2, 99, 'rewardPointsUpdated', 'Latest booking');

        DB::table('diagnostic_test_bookings')->insert([
            'id' => 3,
            'member_id' => 'user-src',
            'patient_id' => $personId,
            'branch_id' => 8,
            'status' => 'confirmed',
            'created_at' => '2026-10-02 10:00:00',
            'updated_at' => '2026-10-02 10:00:00',
        ]);
        DB::table('pharmacy_orders')->insert([
            'id' => 4,
            'hospital_id' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('second_opinions')->insert([
            'id' => 6,
            'member_id' => 'user-src',
            'patient_id' => $personId,
            'branch_id' => 6,
            'status' => 'confirmed',
            'created_at' => '2026-10-03 10:00:00',
            'updated_at' => '2026-10-03 10:00:00',
        ]);

        $wallet = $this->wallet($personId, 0);
        $this->creditInvoice($wallet, $this->invoice([
            'diagnostic_test_booking_id' => 3,
        ]), 12);
        $this->creditInvoice($wallet, $this->invoice([
            'pharmacy_order_id' => 4,
        ]), 3);
        $this->creditInvoice($wallet, $this->invoice([
            'second_opinion_id' => 6,
        ]), 2);

        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $packageWorkflow->id)->count());
        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $pharmacyWorkflow->id)->count());
        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $opinionWorkflow->id)->count());
        $this->assertSame(0, WorkflowExecution::query()->where('workflow_id', $unrelated->id)->count());

        $package = WorkflowExecution::query()->where('workflow_id', $packageWorkflow->id)->first();
        $this->assertSame(8, (int) data_get($package?->context, 'hospital_id'));
        $this->assertSame(1, (int) data_get($package?->context, 'organization_id'));
        $this->assertSame('package_booking', data_get($package?->context, 'reward_source'));
        $this->assertSame(12, (int) data_get($package?->context, 'rewards.coins_earned'));
        $this->assertSame(3, (int) data_get(
            WorkflowExecution::query()->where('workflow_id', $pharmacyWorkflow->id)->first()?->context,
            'rewards.coins_earned'
        ));
        $this->assertSame(2, (int) data_get(
            WorkflowExecution::query()->where('workflow_id', $opinionWorkflow->id)->first()?->context,
            'rewards.coins_earned'
        ));
        $this->assertSame('pharmacy_payment', data_get(
            WorkflowExecution::query()->where('workflow_id', $pharmacyWorkflow->id)->first()?->context,
            'reward_source'
        ));
        $this->assertSame('second_opinion_payment', data_get(
            WorkflowExecution::query()->where('workflow_id', $opinionWorkflow->id)->first()?->context,
            'reward_source'
        ));
    }

    public function test_redemption_referral_adjustment_and_bill_analysis_follow_balance_changes(): void
    {
        $this->organization(1);
        $this->hospital(5, 1);
        $personId = $this->person('user-redeem');
        $wallet = $this->wallet($personId, 20);
        $redeemWorkflow = $this->publishWorkflow(1, 5, 'rewardPointsUpdated', 'Redeem');
        $unscoped = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Unscoped');

        app(CoinsWalletService::class)->debit(
            $wallet,
            6,
            RewardPointsScope::forBookingHospital('redeemed', 'appointment_redemption', 5, 'doctor_booking', 77)
        );
        app(CoinsWalletService::class)->credit($wallet->fresh(), 4, new RewardPointsContext('adjusted', 'hip_points'));
        app(CoinsWalletService::class)->credit($wallet->fresh(), 1, new RewardPointsContext('earned', 'bill_analysis'));

        $redeemed = WorkflowExecution::query()->where('workflow_id', $redeemWorkflow->id)->first();
        $this->assertNotNull($redeemed);
        $this->assertSame(-6, (int) data_get($redeemed->context, 'points_changed'));
        $this->assertNull(data_get($redeemed->context, 'rewards.coins_earned'));
        $this->assertSame('redeemed', data_get($redeemed->context, 'change_type'));
        $this->assertSame('appointment_redemption', data_get($redeemed->context, 'reward_source'));
        $this->assertSame(5, (int) data_get($redeemed->context, 'hospital_id'));

        $unscopedExecutions = WorkflowExecution::query()->where('workflow_id', $unscoped->id)->orderBy('id')->get();
        $this->assertCount(2, $unscopedExecutions);
        $this->assertSame(['hip_points', 'bill_analysis'], $unscopedExecutions->map(
            fn ($execution) => data_get($execution->context, 'reward_source')
        )->all());
        foreach ($unscopedExecutions as $execution) {
            $this->assertNull(data_get($execution->context, 'rewards.coins_earned'));
        }
        $this->assertSame(0, WorkflowExecution::query()
            ->where('workflow_id', $redeemWorkflow->id)
            ->where('context->reward_source', 'hip_points')
            ->count());
    }

    public function test_unchanged_balance_does_not_dispatch(): void
    {
        $personId = $this->person('user-zero');
        $wallet = $this->wallet($personId, 0);
        $workflow = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Should not run');

        app(CoinsWalletService::class)->credit($wallet, 0);
        app(CoinsWalletService::class)->creditAfterPayment($wallet, 0);
        app(CoinsWalletService::class)->debit($wallet, 4);

        $this->assertSame(0, (int) $wallet->fresh()->coins);
        $this->assertSame(0, RewardPointsTransition::query()->count());
        $this->assertSame(0, WorkflowExecution::query()->where('workflow_id', $workflow->id)->count());
    }

    public function test_occurrence_id_is_stable_for_retries_and_distinct_for_each_update(): void
    {
        $personId = $this->person('user-id');
        $wallet = $this->wallet($personId, 0);
        $workflow = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Identity');

        app(CoinsWalletService::class)->creditAfterPayment($wallet, 5);
        app(CoinsWalletService::class)->creditAfterPayment($wallet->fresh(), 2);

        $transitions = RewardPointsTransition::query()->orderBy('id')->get();
        $this->assertCount(2, $transitions);
        $this->assertNotSame($transitions[0]->occurrence_id, $transitions[1]->occurrence_id);
        $this->assertSame(
            RewardPointsUpdated::occurrenceId($transitions[0]->id),
            $transitions[0]->occurrence_id
        );

        $payload = [
            'hip_user_id' => 'user-id',
            'member_id' => 'user-id',
            'hospital_id' => null,
            'organization_id' => null,
            'event_occurrence_id' => $transitions[0]->occurrence_id,
            'rewards' => ['points_changed' => 5],
        ];
        app(AutomationEngine::class)->handle('rewardPointsUpdated', $payload);
        app(AutomationEngine::class)->handle('rewardPointsUpdated', $payload);

        $this->assertSame(1, WorkflowExecution::query()
            ->where('workflow_id', $workflow->id)
            ->where('context->event_occurrence_id', $transitions[0]->occurrence_id)
            ->count());
        $this->assertSame(2, WorkflowExecution::query()->where('workflow_id', $workflow->id)->count());
    }

    public function test_failed_reward_points_execution_retries_without_a_second_row_or_a_second_accepted_send(): void
    {
        $calls = 0;
        $push = \Mockery::mock(\App\Modules\MedicineReminder\Notifications\PushNotificationService::class);
        $push->shouldReceive('send')->andReturnUsing(function () use (&$calls) {
            $calls++;

            return $calls > 1
                ? ['success' => true, 'response' => 'Push notification dispatched']
                : ['success' => false, 'response' => 'No FCM devices registered'];
        });
        $this->app->instance(\App\Modules\MedicineReminder\Notifications\PushNotificationService::class, $push);
        $this->app->forgetInstance(\App\Modules\Workflow\Services\Runtime\ChannelManager::class);
        $this->app->forgetInstance(\App\Modules\Workflow\NodeProcessorRegistry::class);
        $this->app->forgetInstance(\App\Modules\Workflow\Services\Runtime\WorkflowExecutor::class);

        $personId = $this->person('user-retry');
        $wallet = $this->wallet($personId, 0);
        $workflow = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Retry', [
            'title' => 'Points',
            'body' => 'Changed {{rewards.points_changed}}',
            'recipient' => 'patient',
        ]);

        app(CoinsWalletService::class)->creditAfterPayment($wallet, 5);

        $execution = WorkflowExecution::query()->where('workflow_id', $workflow->id)->firstOrFail();
        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->status);
        $occurrenceId = data_get($execution->context, 'event_occurrence_id');

        app(AutomationEngine::class)->handle('rewardPointsUpdated', $execution->context);
        $execution->refresh();
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->status);
        $this->assertSame($occurrenceId, data_get($execution->context, 'event_occurrence_id'));
        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $workflow->id)->count());
        $this->assertSame(2, $calls);

        app(AutomationEngine::class)->handle('rewardPointsUpdated', $execution->fresh()->context);
        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $workflow->id)->count());
        $this->assertSame(2, $calls);
        $this->assertSame(1, CommunicationLog::query()
            ->where('workflow_execution_id', $execution->id)
            ->where('status', CommunicationStatus::Sent->value)
            ->count());
    }

    public function test_automation_failure_does_not_roll_back_the_wallet_change(): void
    {
        Event::listen(RewardPointsUpdated::class, function (): void {
            throw new \RuntimeException('automation down');
        });

        $personId = $this->person('user-safe');
        $wallet = $this->wallet($personId, 0);

        DB::transaction(function () use ($wallet): void {
            app(CoinsWalletService::class)->creditAfterPayment(
                $wallet,
                4,
                new RewardPointsContext('earned', 'payment')
            );
        });

        $this->assertSame(4, (int) $wallet->fresh()->coins);
        $this->assertSame(1, RewardPointsTransition::query()->where('hip_user_id', 'user-safe')->count());
    }

    public function test_push_whatsapp_email_and_sms_receive_the_reward_context(): void
    {
        Mail::fake();
        $captured = [];
        $push = \Mockery::mock(\App\Modules\MedicineReminder\Notifications\PushNotificationService::class);
        $push->shouldReceive('send')->once()->andReturnUsing(function ($memberId, $title, $body) use (&$captured) {
            $captured['push'] = $body;

            return ['success' => true, 'response' => 'Push notification dispatched'];
        });
        $whatsApp = \Mockery::mock(\App\Modules\MedicineReminder\Notifications\WhatsAppNotificationService::class);
        $whatsApp->shouldReceive('clientClass')->andReturn('test');
        $whatsApp->shouldReceive('send')->once()->andReturnUsing(function ($mobile, $message) use (&$captured) {
            $captured['whatsapp'] = $message;

            return ['success' => true, 'response' => 'accepted'];
        });
        $this->app->instance(\App\Modules\MedicineReminder\Notifications\PushNotificationService::class, $push);
        $this->app->instance(\App\Modules\MedicineReminder\Notifications\WhatsAppNotificationService::class, $whatsApp);
        $this->app->forgetInstance(\App\Modules\Workflow\Services\Runtime\ChannelManager::class);
        $this->app->forgetInstance(\App\Modules\Workflow\NodeProcessorRegistry::class);
        $this->app->forgetInstance(\App\Modules\Workflow\Services\Runtime\WorkflowExecutor::class);

        $this->organization(1);
        $this->hospital(5, 1);
        $personId = $this->person('user-channel', [
            'first_name' => 'Ada',
            'mobile' => '9876543210',
            'email' => 'ada@example.com',
        ]);
        $wallet = $this->wallet($personId, 10);
        $template = 'Points {{rewards.points_changed}} now {{rewards.current_balance}} {{rewards.change_type}} {{patient.first_name}}';

        $this->publishWorkflow(1, 5, 'rewardPointsUpdated', 'Push', [
            'title' => 'Points',
            'body' => $template,
            'recipient' => 'patient',
        ], 'sendPush');
        $this->publishWorkflow(1, 5, 'rewardPointsUpdated', 'WhatsApp', [
            'messageTemplate' => $template,
            'recipient' => 'patient',
        ], 'sendWhatsApp');
        $this->publishWorkflow(1, 5, 'rewardPointsUpdated', 'Email', [
            'subject' => 'Points',
            'body' => $template,
            'recipient' => 'patient',
        ], 'sendEmail');
        $this->publishWorkflow(1, 5, 'rewardPointsUpdated', 'SMS', [
            'messageTemplate' => $template,
            'recipient' => 'patient',
        ], 'sendSMS');

        app(CoinsWalletService::class)->creditAfterPayment(
            $wallet,
            5,
            RewardPointsScope::forBookingHospital('earned', 'appointment_payment', 5, 'doctor_booking', 77)
        );

        $this->assertSame(4, WorkflowExecution::query()->where('trigger_type', 'rewardPointsUpdated')->count());
        $this->assertSame(4, WorkflowExecution::query()->where('status', WorkflowExecutionStatus::Completed->value)->count());
        $this->assertSame('Points 5 now 15 earned Ada', $captured['push']);
        $this->assertSame('Points 5 now 15 earned Ada', $captured['whatsapp']);

        $messages = CommunicationLog::query()->orderBy('id')->pluck('message')->all();
        $this->assertCount(4, $messages);
        foreach ($messages as $message) {
            $this->assertSame('Points 5 now 15 earned Ada', $message);
        }
        $this->assertSame(4, CommunicationLog::query()->where('status', CommunicationStatus::Sent->value)->count());
    }

    public function test_message_picker_exposes_reward_variables_for_reward_points_updated_only(): void
    {
        $catalog = app(VariableCatalogService::class)->forTrigger('rewardPointsUpdated');
        $keys = collect($catalog['groups']['rewards'] ?? [])->pluck('key')->all();

        $this->assertEqualsCanonicalizing([
            'rewards.previous_balance',
            'rewards.current_balance',
            'rewards.points_changed',
            'rewards.coins_earned',
            'rewards.change_type',
            'rewards.source',
        ], $keys);
        $this->assertSame(
            'Coins Earned From This Payment',
            collect($catalog['groups']['rewards'])->firstWhere('key', 'rewards.coins_earned')['label'] ?? null
        );

        $payment = app(VariableCatalogService::class)->forTrigger('paymentReceived');
        $this->assertArrayNotHasKey('rewards', $payment['groups']);
    }

    public function test_update_type_limits_which_balance_changes_start_a_workflow(): void
    {
        $open = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Open');
        $any = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Any', triggerData: ['updateType' => 'Any']);
        $empty = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Empty', triggerData: ['updateType' => []]);
        $added = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Added', triggerData: ['updateType' => ['Points Added']]);
        $redeemed = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Redeemed', triggerData: ['updateType' => 'Points Redeemed']);
        $adjusted = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Adjusted', triggerData: ['updateType' => '["Points Adjusted"]']);
        $addedOrRedeemed = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Added or redeemed', triggerData: [
            'updateType' => 'Points Added, Points Redeemed',
        ]);
        $tier = $this->publishWorkflow(null, null, 'rewardTierUpgraded', 'Tier still runs');

        $engine = app(AutomationEngine::class);
        foreach (['earned', 'redeemed', 'adjusted'] as $changeType) {
            $engine->handle('rewardPointsUpdated', $this->pointsPayload($changeType));
        }
        $engine->handle('rewardTierUpgraded', [
            'hip_user_id' => 'user-filter',
            'member_id' => 'user-filter',
            'event_occurrence_id' => 'reward-tier-upgraded:user-filter:from:1:to:2:transition:9',
            'hospital_id' => null,
            'organization_id' => null,
        ]);

        $this->assertSame(3, $this->executionCount($open));
        $this->assertSame(3, $this->executionCount($any));
        $this->assertSame(3, $this->executionCount($empty));
        $this->assertSame(1, $this->executionCount($added));
        $this->assertSame('earned', data_get($this->onlyExecution($added)->context, 'change_type'));
        $this->assertSame(1, $this->executionCount($redeemed));
        $this->assertSame('redeemed', data_get($this->onlyExecution($redeemed)->context, 'change_type'));
        $this->assertSame(1, $this->executionCount($adjusted));
        $this->assertSame('adjusted', data_get($this->onlyExecution($adjusted)->context, 'change_type'));
        $this->assertSame(2, $this->executionCount($addedOrRedeemed));
        $this->assertEqualsCanonicalizing(
            ['earned', 'redeemed'],
            WorkflowExecution::query()
                ->where('workflow_id', $addedOrRedeemed->id)
                ->get()
                ->map(fn ($execution) => data_get($execution->context, 'change_type'))
                ->all()
        );
        $this->assertSame(1, $this->executionCount($tier));
    }

    public function test_reward_source_filter_limits_payment_earnings_before_an_execution_is_created(): void
    {
        $sentBodies = [];
        $push = \Mockery::mock(\App\Modules\MedicineReminder\Notifications\PushNotificationService::class);
        $push->shouldReceive('send')->andReturnUsing(function ($memberId, $title, $body) use (&$sentBodies) {
            $sentBodies[] = $body;

            return ['success' => true, 'response' => 'Push notification dispatched'];
        });
        $this->app->instance(\App\Modules\MedicineReminder\Notifications\PushNotificationService::class, $push);
        $this->app->forgetInstance(\App\Modules\Workflow\Services\Runtime\ChannelManager::class);
        $this->app->forgetInstance(\App\Modules\Workflow\NodeProcessorRegistry::class);
        $this->app->forgetInstance(\App\Modules\Workflow\Services\Runtime\WorkflowExecutor::class);

        $paymentEarnings = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Payment earnings', triggerData: [
            'rewardSource' => ['payment_earnings'],
        ]);
        $any = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Any source', triggerData: [
            'rewardSource' => 'Any Source',
        ]);
        $empty = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Empty source', triggerData: [
            'rewardSource' => [],
        ]);
        $missing = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Missing source');
        $pharmacyOnly = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Pharmacy only', triggerData: [
            'rewardSource' => ['pharmacy_payment'],
        ]);
        $genericPayment = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Generic payment', triggerData: [
            'rewardSource' => ['payment'],
        ]);
        $addedPayments = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Added payments', triggerData: [
            'updateType' => ['Points Added'],
            'rewardSource' => ['Payment Earnings'],
        ]);
        $coinsMessage = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Coins message', [
            'title' => 'Points',
            'body' => 'Earned {{rewards.coins_earned}}',
            'recipient' => 'patient',
        ], triggerData: ['rewardSource' => ['any']]);
        $tier = $this->publishWorkflow(null, null, 'rewardTierUpgraded', 'Tier still runs');
        $paymentReceived = $this->publishWorkflow(null, null, 'paymentReceived', 'Unrelated', triggerData: [
            'rewardSource' => ['payment_earnings'],
        ]);

        $schema = app(\App\Modules\Workflow\Services\Builder\TriggerSchemaRegistry::class);
        $rewardFields = collect($schema->forType('rewardPointsUpdated')['fields'])->pluck('name')->all();
        $this->assertContains('rewardSource', $rewardFields);
        $this->assertNotContains('rewardSource', collect($schema->forType('rewardTierUpgraded')['fields'])->pluck('name')->all());

        $engine = app(AutomationEngine::class);
        foreach ([
            'appointment_payment',
            'package_booking',
            'second_opinion_payment',
            'pharmacy_payment',
        ] as $source) {
            $engine->handle('rewardPointsUpdated', $this->rewardPayload('earned', $source, 5));
        }

        $engine->handle('rewardPointsUpdated', $this->rewardPayload('redeemed', 'appointment_payment'));
        foreach ([
            'bill_analysis' => 'earned',
            'hip_points' => 'adjusted',
            'appointment_redemption' => 'redeemed',
            'package_redemption' => 'redeemed',
            'second_opinion_redemption' => 'redeemed',
            'redemption' => 'redeemed',
            'wallet' => 'earned',
            'payment' => 'earned',
        ] as $source => $changeType) {
            $engine->handle('rewardPointsUpdated', $this->rewardPayload($changeType, $source));
        }

        $this->assertSame(5, $this->executionCount($paymentEarnings));
        $this->assertEqualsCanonicalizing(
            ['appointment_payment', 'appointment_payment', 'package_booking', 'second_opinion_payment', 'pharmacy_payment'],
            WorkflowExecution::query()
                ->where('workflow_id', $paymentEarnings->id)
                ->get()
                ->map(fn ($execution) => data_get($execution->context, 'reward_source'))
                ->all()
        );
        $this->assertSame(13, $this->executionCount($any));
        $this->assertSame(13, $this->executionCount($empty));
        $this->assertSame(13, $this->executionCount($missing));
        $this->assertSame(1, $this->executionCount($pharmacyOnly));
        $this->assertSame('pharmacy_payment', data_get($this->onlyExecution($pharmacyOnly)->context, 'reward_source'));
        $this->assertSame(1, $this->executionCount($genericPayment));
        $this->assertSame('payment', data_get($this->onlyExecution($genericPayment)->context, 'reward_source'));
        $this->assertSame(4, $this->executionCount($addedPayments));
        $this->assertSame(
            ['earned'],
            WorkflowExecution::query()
                ->where('workflow_id', $addedPayments->id)
                ->get()
                ->map(fn ($execution) => data_get($execution->context, 'change_type'))
                ->unique()
                ->values()
                ->all()
        );

        $coinsExecutions = WorkflowExecution::query()->where('workflow_id', $coinsMessage->id)->get();
        $this->assertSame(13, $coinsExecutions->count());
        $failed = $coinsExecutions->where('status', WorkflowExecutionStatus::Failed->value);
        $this->assertSame(9, $failed->count());
        foreach ($failed as $execution) {
            $this->assertNull(data_get($execution->context, 'rewards.coins_earned'));
            $this->assertStringContainsString('unresolved', strtolower((string) $execution->failure_reason));
        }
        foreach ($sentBodies as $body) {
            $this->assertStringNotContainsString('{{', $body);
        }

        $engine->handle('rewardTierUpgraded', [
            'hip_user_id' => 'user-source-filter',
            'member_id' => 'user-source-filter',
            'event_occurrence_id' => 'reward-tier-upgraded:user-source-filter:from:1:to:2:transition:9',
            'hospital_id' => null,
            'organization_id' => null,
        ]);
        $this->assertSame(1, $this->executionCount($tier));

        $engine->executeWorkflow($paymentReceived, 'paymentReceived', [
            'member_id' => 'user-source-filter',
            'reward_source' => 'bill_analysis',
            'hospital_id' => null,
            'organization_id' => null,
            'event_occurrence_id' => 'payment-received:source-filter',
        ]);
        $this->assertSame(1, $this->executionCount($paymentReceived));
    }

    public function test_dispatched_payload_contains_the_canonical_reward_fields(): void
    {
        $captured = null;
        Event::listen(RewardPointsUpdated::class, function (RewardPointsUpdated $event) use (&$captured): void {
            $captured = $event->payload();
        });

        $personId = $this->person('user-payload', [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'mobile' => '9876543210',
            'email' => 'ada@example.com',
        ]);
        $wallet = $this->wallet($personId, 0);

        app(CoinsWalletService::class)->creditAfterPayment(
            $wallet,
            5,
            new RewardPointsContext('earned', 'appointment_payment', null, 12, 34, 'doctor_booking', 77)
        );

        $this->assertIsArray($captured);
        $this->assertSame('user-payload', $captured['hip_user_id']);
        $this->assertSame('user-payload', $captured['member_id']);
        $this->assertSame(0, $captured['previous_balance']);
        $this->assertSame(5, $captured['current_balance']);
        $this->assertSame(5, $captured['points_changed']);
        $this->assertSame('earned', $captured['change_type']);
        $this->assertSame('appointment_payment', $captured['reward_source']);
        $this->assertNull($captured['hospital_id']);
        $this->assertNull($captured['organization_id']);
        $this->assertSame(12, $captured['invoice_id']);
        $this->assertSame(34, $captured['transaction_id']);
        $this->assertSame('doctor_booking', $captured['booking_source']);
        $this->assertSame(77, $captured['booking_id']);
        $this->assertSame('Ada', $captured['patient']['first_name']);
        $this->assertSame('Lovelace', $captured['patient']['last_name']);
        $this->assertSame('9876543210', $captured['patient_mobile']);
        $this->assertSame('ada@example.com', $captured['patient_email']);
        $this->assertSame([
            'previous_balance' => 0,
            'current_balance' => 5,
            'points_changed' => 5,
            'coins_earned' => null,
            'change_type' => 'earned',
            'source' => 'appointment_payment',
            'update_type' => 'Points Added',
        ], $captured['rewards']);
        $this->assertIsString($captured['event_occurrence_id']);

        app(CoinsWalletService::class)->debit($wallet->fresh(), 4, new RewardPointsContext('redeemed', 'appointment_redemption'));
        $this->assertSame(5, $captured['previous_balance']);
        $this->assertSame(1, $captured['current_balance']);
        $this->assertSame(-4, $captured['points_changed']);
        $this->assertSame('redeemed', $captured['change_type']);
        $this->assertSame('appointment_redemption', $captured['reward_source']);
        $this->assertNull($captured['invoice_id']);
        $this->assertNull($captured['hospital_id']);
        $this->assertNull($captured['rewards']['coins_earned']);
        $this->assertNull($captured['booking_id']);
    }

    public function test_coins_earned_uses_the_persisted_award_and_not_the_wallet_delta(): void
    {
        $personId = $this->person('user-award');
        $wallet = $this->wallet($personId, 10);
        $workflow = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Award', [
            'title' => 'Points',
            'body' => 'Earned {{rewards.coins_earned}} changed {{rewards.points_changed}}',
        ]);
        $invoice = $this->invoice([
            'doctor_booking_id' => 77,
            'coins_earned' => 9,
        ]);

        app(CoinsWalletService::class)->creditAfterPayment(
            $wallet,
            5,
            RewardPointsScope::forInvoice($invoice, 'earned', 34, (int) $invoice->fresh()->coins_earned)
        );

        $execution = WorkflowExecution::query()->where('workflow_id', $workflow->id)->firstOrFail();
        $this->assertSame(5, (int) data_get($execution->context, 'rewards.points_changed'));
        $this->assertSame(10, (int) data_get($execution->context, 'rewards.previous_balance'));
        $this->assertSame(15, (int) data_get($execution->context, 'rewards.current_balance'));
        $this->assertSame(9, (int) data_get($execution->context, 'rewards.coins_earned'));
        $this->assertSame(9, (int) RewardPointsTransition::query()->value('coins_earned'));

        app(AutomationEngine::class)->handle('rewardPointsUpdated', $execution->context);
        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $workflow->id)->count());
        $this->assertSame(9, (int) data_get($execution->fresh()->context, 'rewards.coins_earned'));

        app(CoinsWalletService::class)->debit(
            $wallet->fresh(),
            4,
            new RewardPointsContext('redeemed', 'appointment_redemption', coinsEarned: 9)
        );
        $redeemed = RewardPointsTransition::query()->where('change_type', 'redeemed')->firstOrFail();
        $this->assertNull($redeemed->coins_earned);
        $this->assertSame(-4, (int) $redeemed->points_changed);
    }

    public function test_coins_earned_placeholder_is_saved_reopened_and_previewed(): void
    {
        $placeholder = 'You earned {{rewards.coins_earned}} coins.';
        $workflow = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Picker', [
            'title' => 'Points',
            'body' => $placeholder,
        ]);

        $reopened = $workflow->fresh(['currentVersion'])->currentVersion?->definition;
        $savedBody = data_get($reopened, 'nodes.1.data.body');
        $this->assertSame($placeholder, $savedBody);

        $catalog = collect(app(VariableCatalogService::class)->forTrigger('rewardPointsUpdated')['groups']['rewards'])
            ->firstWhere('key', 'rewards.coins_earned');
        $this->assertSame('Coins Earned From This Payment', $catalog['label'] ?? null);
        $this->assertArrayNotHasKey(
            'rewards',
            app(VariableCatalogService::class)->forTrigger('rewardTierUpgraded')['groups']
        );

        $resolver = app(\App\Modules\Workflow\Services\Runtime\VariableResolver::class);
        $this->assertSame(
            'You earned 9 coins.',
            $resolver->resolve($savedBody, ['rewards' => ['coins_earned' => 9]])
        );
        $this->assertSame(
            $placeholder,
            $resolver->resolve($savedBody, ['rewards' => ['coins_earned' => null, 'points_changed' => -4]])
        );
    }

    public function test_redemption_message_does_not_report_redeemed_coins_as_earned(): void
    {
        $bodies = [];
        $push = \Mockery::mock(\App\Modules\MedicineReminder\Notifications\PushNotificationService::class);
        $push->shouldReceive('send')->andReturnUsing(function ($memberId, $title, $body) use (&$bodies) {
            $bodies[] = $body;

            return ['success' => true, 'response' => 'Push notification dispatched'];
        });
        $this->app->instance(\App\Modules\MedicineReminder\Notifications\PushNotificationService::class, $push);
        $this->app->forgetInstance(\App\Modules\Workflow\Services\Runtime\ChannelManager::class);
        $this->app->forgetInstance(\App\Modules\Workflow\NodeProcessorRegistry::class);
        $this->app->forgetInstance(\App\Modules\Workflow\Services\Runtime\WorkflowExecutor::class);

        $personId = $this->person('user-empty-award');
        $wallet = $this->wallet($personId, 20);
        $earnedTemplate = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Earned placeholder', [
            'title' => 'Points',
            'body' => 'Earned {{rewards.coins_earned}}',
            'recipient' => 'patient',
        ]);
        $deltaTemplate = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Delta only', [
            'title' => 'Points',
            'body' => 'Changed {{rewards.points_changed}}',
            'recipient' => 'patient',
        ]);

        app(CoinsWalletService::class)->debit(
            $wallet,
            6,
            new RewardPointsContext('redeemed', 'appointment_redemption', coinsEarned: 6)
        );

        $blocked = WorkflowExecution::query()->where('workflow_id', $earnedTemplate->id)->firstOrFail();
        $sent = WorkflowExecution::query()->where('workflow_id', $deltaTemplate->id)->firstOrFail();
        $this->assertNull(data_get($blocked->context, 'rewards.coins_earned'));
        $this->assertSame(WorkflowExecutionStatus::Failed->value, $blocked->status);
        $this->assertStringContainsString('unresolved', strtolower((string) $blocked->failure_reason));
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $sent->status);
        $this->assertSame(['Changed -6'], $bodies);
        $this->assertSame(0, CommunicationLog::query()
            ->where('workflow_execution_id', $blocked->id)
            ->where('status', CommunicationStatus::Sent->value)
            ->count());
    }

    public function test_reward_points_push_uses_the_legacy_coin_details_deep_link(): void
    {
        $payloads = [];
        $push = \Mockery::mock(\App\Modules\MedicineReminder\Notifications\PushNotificationService::class);
        $push->shouldReceive('send')->andReturnUsing(function ($memberId, $title, $body, $data) use (&$payloads) {
            $payloads[] = $data;

            return ['success' => true, 'response' => 'Push notification dispatched'];
        });
        $this->app->instance(\App\Modules\MedicineReminder\Notifications\PushNotificationService::class, $push);
        $this->app->forgetInstance(\App\Modules\Workflow\Services\Runtime\ChannelManager::class);
        $this->app->forgetInstance(\App\Modules\Workflow\NodeProcessorRegistry::class);
        $this->app->forgetInstance(\App\Modules\Workflow\Services\Runtime\WorkflowExecutor::class);

        $this->organization(1);
        $this->hospital(5, 1);
        $personId = $this->person('user-deeplink');
        $wallet = $this->wallet($personId, 0);
        $workflow = $this->publishWorkflow(1, 5, 'rewardPointsUpdated', 'Coin details', [
            'title' => 'Points',
            'body' => 'Balance {{rewards.current_balance}}',
            'recipient' => 'patient',
        ]);

        DB::table('doctor_bookings')->insert([
            'id' => 41,
            'member_id' => 'user-deeplink',
            'patient_id' => $personId,
            'hospital_id' => 5,
            'branch_id' => 5,
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('diagnostic_test_bookings')->insert([
            'id' => 3,
            'member_id' => 'user-deeplink',
            'patient_id' => $personId,
            'branch_id' => 5,
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('pharmacy_orders')->insert([
            'id' => 4,
            'hospital_id' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('second_opinions')->insert([
            'id' => 6,
            'member_id' => 'user-deeplink',
            'patient_id' => $personId,
            'branch_id' => 5,
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $appointment = $this->invoice(['doctor_booking_id' => 41]);
        $this->creditInvoice($wallet, $appointment, 5);

        $execution = WorkflowExecution::query()->where('workflow_id', $workflow->id)->firstOrFail();
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->status);
        app(AutomationEngine::class)->handle('rewardPointsUpdated', $execution->context);
        $this->assertCount(1, $payloads);
        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $workflow->id)->count());

        $this->creditInvoice($wallet->fresh(), $this->invoice(['diagnostic_test_booking_id' => 3]), 12);
        $this->creditInvoice($wallet->fresh(), $this->invoice(['pharmacy_order_id' => 4]), 3);
        $this->creditInvoice($wallet->fresh(), $this->invoice(['second_opinion_id' => 6]), 2);

        $this->assertSame([
            'appointment_booking',
            'diagnostic_package_booking',
            'invoice',
            'second_opinion_booking',
        ], array_column($payloads, 'context'));

        $appointmentPayload = $payloads[0];
        $this->assertSame('/coin-details', $appointmentPayload['url']);
        $this->assertSame('/coin-details', $appointmentPayload['route']);
        $this->assertSame('coin_details', $appointmentPayload['screen']);
        $this->assertSame('coins_earned', $appointmentPayload['type']);
        $this->assertSame('coins_earned', $appointmentPayload['notification_type']);
        $this->assertSame('payment', $appointmentPayload['source']);
        $this->assertSame((string) $appointment->id, $appointmentPayload['invoice_id']);
        $this->assertSame('5', $appointmentPayload['coins_earned']);
        $this->assertSame('41', $appointmentPayload['context_id']);
        $this->assertSame('41', $appointmentPayload['appointment_id']);
        $this->assertSame($workflow->id, $appointmentPayload['workflow_id']);
        $this->assertSame($execution->id, $appointmentPayload['workflow_execution_id']);
        $this->assertArrayNotHasKey('click_action', $appointmentPayload);
        $this->assertArrayNotHasKey('paid_amount', $appointmentPayload);

        $this->assertSame('3', $payloads[1]['context_id']);
        $this->assertSame('12', $payloads[1]['coins_earned']);
        $this->assertArrayNotHasKey('appointment_id', $payloads[1]);
        $this->assertSame((string) Invoice::query()->where('pharmacy_order_id', 4)->value('id'), $payloads[2]['context_id']);
        $this->assertSame('3', $payloads[2]['coins_earned']);
        $this->assertSame('6', $payloads[3]['context_id']);
        $this->assertSame('2', $payloads[3]['coins_earned']);
        foreach (array_slice($payloads, 0, 4) as $payload) {
            $this->assertSame('/coin-details', $payload['url']);
            $this->assertSame('/coin-details', $payload['route']);
            $this->assertSame('coin_details', $payload['screen']);
            $this->assertSame('coins_earned', $payload['type']);
            $this->assertSame('coins_earned', $payload['notification_type']);
            $this->assertSame('payment', $payload['source']);
        }

        $unscoped = $this->publishWorkflow(null, null, 'rewardPointsUpdated', 'Unscoped points', [
            'title' => 'Points',
            'body' => 'Balance {{rewards.current_balance}}',
            'recipient' => 'patient',
        ]);

        app(CoinsWalletService::class)->debit(
            $wallet->fresh(),
            4,
            RewardPointsScope::forBookingHospital('redeemed', 'appointment_redemption', 5, 'doctor_booking', 41)
        );
        $redeemed = $payloads[4];
        $this->assertSame('/coin-details', $redeemed['url']);
        $this->assertSame('/coin-details', $redeemed['route']);
        $this->assertSame('coin_details', $redeemed['screen']);
        $this->assertSame('redeemed', $redeemed['type']);
        $this->assertSame('redeemed', $redeemed['notification_type']);
        $this->assertSame('appointment_redemption', $redeemed['source']);
        $this->assertArrayNotHasKey('coins_earned', $redeemed);
        $this->assertArrayNotHasKey('context', $redeemed);
        $this->assertSame(5, WorkflowExecution::query()->where('workflow_id', $workflow->id)->count());
        $this->assertSame(0, WorkflowExecution::query()->where('workflow_id', $unscoped->id)->count());

        app(CoinsWalletService::class)->credit($wallet->fresh(), 4, new RewardPointsContext('adjusted', 'hip_points'));
        app(CoinsWalletService::class)->credit($wallet->fresh(), 1, new RewardPointsContext('earned', 'bill_analysis'));

        $adjusted = $payloads[5];
        $this->assertSame('/coin-details', $adjusted['url']);
        $this->assertSame('/coin-details', $adjusted['route']);
        $this->assertSame('coin_details', $adjusted['screen']);
        $this->assertSame('adjusted', $adjusted['type']);
        $this->assertSame('adjusted', $adjusted['notification_type']);
        $this->assertSame('hip_points', $adjusted['source']);
        $this->assertArrayNotHasKey('coins_earned', $adjusted);

        $billAnalysis = $payloads[6];
        $this->assertSame('/coin-details', $billAnalysis['url']);
        $this->assertSame('coin_details', $billAnalysis['screen']);
        $this->assertSame('earned', $billAnalysis['type']);
        $this->assertSame('earned', $billAnalysis['notification_type']);
        $this->assertSame('bill_analysis', $billAnalysis['source']);
        $this->assertNotSame('coins_earned', $billAnalysis['type']);
        $this->assertNotSame('payment', $billAnalysis['source']);
        $this->assertArrayNotHasKey('coins_earned', $billAnalysis);
        $this->assertSame(2, WorkflowExecution::query()->where('workflow_id', $unscoped->id)->count());

        app(\App\Modules\Workflow\Services\Runtime\ChannelManager::class)->send(
            channel: 'push',
            execution: $execution,
            nodeId: 'custom-link',
            message: 'Opened from the workflow link',
            context: array_merge($execution->context, [
                'meta' => ['url' => '/from-workflow', 'route' => '/from-workflow'],
            ]),
            subject: 'Points',
        );
        $this->assertSame('/from-workflow', $payloads[7]['url']);
        $this->assertSame('/from-workflow', $payloads[7]['route']);
        $this->assertSame('coin_details', $payloads[7]['screen']);
        $this->assertSame('coins_earned', $payloads[7]['type']);
        $this->assertSame('payment', $payloads[7]['source']);
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    private function rewardPayload(string $changeType, string $rewardSource, ?int $coinsEarned = null): array
    {
        return [
            'hip_user_id' => 'user-source-filter',
            'member_id' => 'user-source-filter',
            'change_type' => $changeType,
            'reward_source' => $rewardSource,
            'coins_earned' => $coinsEarned,
            'rewards' => [
                'change_type' => $changeType,
                'source' => $rewardSource,
                'coins_earned' => $coinsEarned,
            ],
            'hospital_id' => null,
            'organization_id' => null,
            'event_occurrence_id' => 'reward-points-updated:transition:'.$changeType.':'.$rewardSource.':'.($coinsEarned ?? 'none'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function pointsPayload(string $changeType): array
    {
        return [
            'hip_user_id' => 'user-filter',
            'member_id' => 'user-filter',
            'change_type' => $changeType,
            'rewards' => ['change_type' => $changeType],
            'hospital_id' => null,
            'organization_id' => null,
            'event_occurrence_id' => 'reward-points-updated:transition:'.$changeType,
        ];
    }

    private function executionCount(Workflow $workflow): int
    {
        return WorkflowExecution::query()->where('workflow_id', $workflow->id)->count();
    }

    private function onlyExecution(Workflow $workflow): WorkflowExecution
    {
        return WorkflowExecution::query()->where('workflow_id', $workflow->id)->firstOrFail();
    }

    private function creditInvoice(Coins $wallet, Invoice $invoice, int $amount): void
    {
        $invoice->stopUserstamping();
        $invoice->forceFill(['coins_earned' => $amount])->save();
        $invoice->refresh();

        app(CoinsWalletService::class)->creditAfterPayment(
            $wallet->fresh(),
            $amount,
            RewardPointsScope::forInvoice($invoice, 'earned', null, (int) $invoice->coins_earned)
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function invoice(array $attributes): Invoice
    {
        $invoice = new Invoice();
        $invoice->stopUserstamping();
        $invoice->forceFill(array_merge([
            'status' => 'pending',
            'amount' => 0,
            'total_amount' => 0,
        ], $attributes))->save();

        return $invoice->fresh();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function person(string $hipUserId, array $extra = []): string
    {
        $id = 'person-'.$hipUserId;
        DB::table('persons')->insert(array_merge([
            'id' => $id,
            'hip_user_id' => $hipUserId,
            'first_name' => 'Ada',
            'last_name' => 'Patient',
            'mobile' => '9876543210',
            'email' => 'ada@example.com',
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $extra));

        return $id;
    }

    private function wallet(string $personId, int $coins): Coins
    {
        return Coins::query()->create([
            'person_id' => $personId,
            'coins' => $coins,
        ]);
    }

    private function organization(int $id): void
    {
        DB::table('organizations')->insert([
            'id' => $id,
            'org_name' => 'Org '.$id,
            'org_city' => 'City',
            'org_address' => 'Address',
            'org_logo' => 'logo',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function hospital(int $id, int $organizationId): void
    {
        DB::table('hospitals')->insert([
            'id' => $id,
            'organization_id' => $organizationId,
            'name' => 'Hospital '.$id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $nodeData
     */
    private function publishWorkflow(
        ?int $organizationId,
        ?int $hospitalId,
        string $trigger,
        string $name,
        array $nodeData = ['title' => 'Points', 'body' => 'Updated'],
        string $nodeType = 'sendPush',
        array $triggerData = [],
    ): Workflow {
        $workflow = Workflow::query()->create([
            'name' => $name,
            'status' => WorkflowStatus::Draft->value,
            'trigger_type' => $trigger,
            'organization_id' => $organizationId,
            'hospital_id' => $hospitalId,
        ]);

        app(WorkflowRepository::class)->publishVersion($workflow, [
            'builderVersion' => '1',
            'reactFlowVersion' => '12.x',
            'nodes' => [
                [
                    'id' => 't1',
                    'type' => 'workflow',
                    'position' => ['x' => 0, 'y' => 0],
                    'data' => array_merge([
                        'category' => 'triggers',
                        'nodeType' => $trigger,
                        'label' => $name,
                    ], $triggerData),
                ],
                [
                    'id' => 'm1',
                    'type' => 'workflow',
                    'position' => ['x' => 200, 'y' => 0],
                    'data' => array_merge(['category' => 'messaging', 'nodeType' => $nodeType], $nodeData),
                ],
                [
                    'id' => 'e1',
                    'type' => 'workflow',
                    'position' => ['x' => 400, 'y' => 0],
                    'data' => ['nodeType' => 'end'],
                ],
            ],
            'edges' => [
                ['id' => 'e-t-m', 'source' => 't1', 'target' => 'm1'],
                ['id' => 'e-m-e', 'source' => 'm1', 'target' => 'e1'],
            ],
        ]);

        return $workflow->fresh(['currentVersion']);
    }

    private function bootDomainTables(): void
    {
        Schema::create('hospitals', function ($table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });
        Schema::create('healthinpocket_users', function ($table) {
            $table->uuid('id')->primary();
            $table->string('email')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('password')->nullable();
            $table->string('hip_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->integer('coins_balance')->default(0);
            $table->timestamps();
        });
        Schema::create('persons', function ($table) {
            $table->string('id')->primary();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('hip_user_id')->nullable();
            $table->string('parent_id')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
        Schema::create('coins', function ($table) {
            $table->id();
            $table->string('person_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->integer('coins')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('invoices', function ($table) {
            $table->id();
            $table->string('primary_person_id')->nullable();
            $table->string('person_id')->nullable();
            $table->unsignedBigInteger('doctor_booking_id')->nullable();
            $table->unsignedBigInteger('diagnostic_test_booking_id')->nullable();
            $table->unsignedBigInteger('second_opinion_id')->nullable();
            $table->unsignedBigInteger('pharmacy_order_id')->nullable();
            $table->json('service_types')->nullable();
            $table->json('invoice_details')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('service_charges', 10, 2)->default(0);
            $table->decimal('payment_gateway_charges', 10, 2)->default(0);
            $table->decimal('total_gst', 10, 2)->default(0);
            $table->decimal('discount_price', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->integer('coins_applied')->default(0);
            $table->integer('coins_earned')->default(0);
            $table->string('payment_method')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('transactions', function ($table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->json('service_types')->nullable();
            $table->json('invoice_details')->nullable();
            $table->decimal('transaction_amount', 10, 2)->default(0);
            $table->decimal('service_charges', 10, 2)->default(0);
            $table->decimal('payment_gateway_charges', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_gst', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('status')->nullable();
            $table->string('payment_method')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('doctor_bookings', function ($table) {
            $table->id();
            $table->string('member_id')->nullable();
            $table->string('patient_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('diagnostic_test_bookings', function ($table) {
            $table->id();
            $table->string('member_id')->nullable();
            $table->string('patient_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('second_opinions', function ($table) {
            $table->id();
            $table->string('member_id')->nullable();
            $table->string('patient_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('pharmacy_orders', function ($table) {
            $table->id();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('reward_tiers', function ($table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('min_coins')->default(0);
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('reward_tier_configs', function ($table) {
            $table->id();
            $table->unsignedBigInteger('reward_tier_id');
            $table->decimal('total_discount_percentage', 5, 2)->nullable();
            $table->unsignedInteger('free_checkup_count')->default(0);
            $table->unsignedInteger('earned_coins_per_booking')->default(0);
            $table->timestamps();
        });
        Schema::create('user_reward_progress', function ($table) {
            $table->id();
            $table->string('hip_user_id')->unique();
            $table->unsignedBigInteger('reward_tier_id')->nullable();
            $table->unsignedInteger('earned_coins_in_tier')->default(0);
            $table->unsignedInteger('total_lifetime_coins')->default(0);
            $table->timestamps();
        });
        Schema::create('reward_tier_transitions', function ($table) {
            $table->id();
            $table->string('hip_user_id')->index();
            $table->unsignedBigInteger('previous_tier_id')->nullable();
            $table->unsignedBigInteger('new_tier_id');
            $table->string('occurrence_id')->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('reward_points_transitions', function ($table) {
            $table->id();
            $table->string('hip_user_id')->index();
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
            $table->string('occurrence_id')->nullable()->unique();
            $table->timestamps();
        });
    }
}
