<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\Doctor;
use App\Models\DoctorAssignment;
use App\Models\DoctorBooking;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Persons;
use App\Models\Procedure;
use App\Models\SpecialitiesMaster;
use App\Models\Speciality;
use App\Services\InvoiceFeeCalculator;
use App\Services\MembershipEntitlementService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DiseaseChatbotDoctorFlowApiTest extends TestCase
{
    private static bool $schemaReady = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->fail('Disease chatbot doctor-flow tests must use sqlite.');
        }

        $this->bootSchema();
        $this->withoutMiddleware(EnsureAccountIsActive::class);
        config([
            'chatbot.disease_enabled' => true,
            'chatbot.ai_matching_enabled' => false,
            'chatbot.slot_minutes' => 30,
            'chatbot.slot_lead_minutes' => 30,
            'chatbot.slot_hold_minutes' => 15,
            'chatbot.slot_horizon_days' => 14,
            'chatbot.appointment_types' => ['In-Clinic'],
            'app.timezone' => 'Asia/Kolkata',
        ]);
        Carbon::setTestNow(Carbon::parse('2026-10-17 08:00:00', 'Asia/Kolkata'));

        $this->mock(MembershipEntitlementService::class, function ($mock) {
            $feeCalc = app(InvoiceFeeCalculator::class);
            $mock->shouldReceive('priceDoctorConsultation')->andReturnUsing(
                function (string $hipUserId, float $fee, float $coinsValue = 0.0, ?int $branchId = null) use ($feeCalc) {
                    $fee = round(max(0, $fee), 2);
                    $coinsValue = round(max(0, $coinsValue), 2);
                    $coinsDiscount = round(min($coinsValue, $fee), 2);
                    $bd = $feeCalc->bookingBreakdown($fee, $coinsDiscount);

                    return [
                        'original_base_price' => $bd['original_amount'],
                        'membership_discount' => 0.0,
                        'reward_discount' => 0.0,
                        'coins_discount' => $coinsDiscount,
                        'service_charges' => $bd['service_charges'],
                        'final_payable_amount' => $bd['invoice_total'],
                        'breakdown' => $bd,
                    ];
                }
            );
        });

        foreach ([
            'disease_chatbot_messages',
            'disease_chatbot_sessions',
            'doctor_bookings',
            'doctor_reviews',
            'doctor_assignments',
            'procedures',
            'doctors',
            'specialities',
            'specialities_masters',
            'coins',
            'persons',
            'hospitals',
            'healthinpocket_users',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_choose_speciality_skipped_with_one_speciality_full_path_to_summary(): void
    {
        [$user, $hospital, $master, $doctor] = $this->seedHospitalWithDoctor();
        Sanctum::actingAs($user);

        $sessionId = $this->reachAskConsult($hospital->id);
        $yes = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'yes'],
        ])->assertOk();

        $this->assertSame('CHOOSE_DOCTOR', $yes->json('state'));
        $this->assertSame('text', $yes->json('messages.0.type'));
        $this->assertSame('Pick a doctor.', $yes->json('messages.0.text'));
        $this->assertSame('doctor', $yes->json('input.type'));
        $doctorOption = collect($yes->json('options'))->firstWhere('id', (string) $doctor->id);
        $this->assertNotNull($doctorOption);
        $this->assertSame('value', $doctorOption['kind']);
        $this->assertSame('neutral', $doctorOption['style']['variant']);
        $this->assertNull(collect($yes->json('options') ?? [])->firstWhere('id', 'choose_speciality'));

        $date = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'doctor', 'value' => $doctor->id],
        ])->assertOk();
        $this->assertSame('CHOOSE_DATE', $date->json('state'));
        $this->assertSame('text', $date->json('messages.0.type'));
        $this->assertSame('Pick a date.', $date->json('messages.0.text'));
        $this->assertSame('date', $date->json('input.type'));
        $dateItems = collect($date->json('options'))->where('kind', 'value')->values()->all();
        $this->assertNotEmpty($dateItems);
        foreach ($dateItems as $item) {
            $this->assertTrue($item['available']);
            $this->assertGreaterThan(0, (int) $item['slots_count']);
            $this->assertSame('chip', $item['style']['variant']);
            $this->assertSame('primary', $item['selected_style']['variant']);
        }
        $sat = collect($dateItems)->firstWhere('id', '2026-10-17');
        $this->assertNotNull($sat);
        $this->assertGreaterThan(0, (int) ($sat['slots_count'] ?? 0));
        $this->assertSame('action', collect($date->json('options'))->firstWhere('id', 'choose_doctor')['kind']);

        $slot = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'date', 'value' => '2026-10-17'],
        ])->assertOk();
        $this->assertSame('CHOOSE_SLOT', $slot->json('state'));
        $this->assertSame('text', $slot->json('messages.0.type'));
        $this->assertSame('Pick a time.', $slot->json('messages.0.text'));
        $this->assertSame('slot', $slot->json('input.type'));
        $slotOptions = collect($slot->json('options'))->where('kind', 'value')->values();
        $this->assertNotEmpty($slotOptions);
        $firstSlot = $slotOptions->first()['id'];
        $this->assertNotEmpty($firstSlot);
        $this->assertSame($firstSlot, $slotOptions->first()['label']);
        $this->assertSame('chip', $slotOptions->first()['style']['variant']);
        $this->assertSame('primary', $slotOptions->first()['selected_style']['variant']);

        $summary = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'slot', 'value' => $firstSlot],
        ])->assertOk();
        $this->assertSame('CONFIRM_SUMMARY', $summary->json('state'));
        $this->assertSame('summary', $summary->json('messages.0.type'));
        $this->assertSame((string) $doctor->id, $summary->json('messages.0.doctor.id'));
        $this->assertSame((int) $master->id, $summary->json('messages.0.speciality.id'));
        $this->assertSame('Chat Hospital', $summary->json('messages.0.hospital.name'));
        $this->assertSame('2026-10-17', $summary->json('messages.0.date'));
        $this->assertSame($firstSlot, $summary->json('messages.0.time'));
        $this->assertSame(500.0, (float) $summary->json('messages.0.consultation_fee'));
        $this->assertSame('In-Clinic', $summary->json('messages.0.appointment_type'));
        $this->assertSame('Asthma', $summary->json('messages.0.reason_of_visit'));
        $this->assertStringContainsString('Cough', (string) $summary->json('messages.0.message'));
        $this->assertArrayHasKey('breakdown', $summary->json('messages.0'));
        $this->assertSame('Today, 09:00 AM', $doctorOption['next_available']['label']);
        $this->assertNull($doctorOption['qualification']);
        $this->assertSame($doctorOption['label'], 'Dr Available');
        $this->assertSame($firstSlot, $slotOptions->first()['label']);
    }

    public function test_choose_speciality_shown_when_multiple_with_doctors(): void
    {
        [$user, $hospital, $cardio, $pulmo] = $this->seedMultiSpecialityHospitalWithDoctors();
        Sanctum::actingAs($user);

        $sessionId = $this->reachAskConsult($hospital->id, ['Cough']);
        $yes = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'yes'],
        ])->assertOk();

        $this->assertSame('CHOOSE_SPECIALITY', $yes->json('state'));
        $optionIds = collect($yes->json('options'))->pluck('id')->all();
        $this->assertCount(2, array_filter($optionIds, fn ($id) => str_starts_with((string) $id, 'spec:')));
        $this->assertContains('spec:'.$cardio->id, $optionIds);
        $this->assertContains('spec:'.$pulmo->id, $optionIds);
    }

    public function test_zero_available_specialities_friendly_restart(): void
    {
        [$user, $hospital] = $this->seedMultiSpecialityHospital();
        Sanctum::actingAs($user);

        $sessionId = $this->reachAskConsult($hospital->id, ['Cough']);
        $yes = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'yes'],
        ])->assertOk();

        $this->assertStringContainsString('No doctors are available', (string) $yes->json('messages.0.text'));
        $this->assertTrue((bool) $yes->json('allow_restart'));
    }

    public function test_tampered_doctor_date_slot_return_422(): void
    {
        [$user, $hospital, , $doctor] = $this->seedHospitalWithDoctor();
        Sanctum::actingAs($user);
        $sessionId = $this->reachAskConsult($hospital->id);
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'yes'],
        ])->assertOk();

        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'doctor', 'value' => (string) Str::uuid()],
        ])->assertStatus(422)->assertJsonPath('messages.0.text', 'Unknown option');

        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'doctor', 'value' => $doctor->id],
        ])->assertOk();

        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'date', 'value' => '2099-01-01'],
        ])->assertStatus(422)->assertJsonPath('messages.0.text', 'Unknown option');

        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'date', 'value' => '2026-10-17'],
        ])->assertOk();

        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'slot', 'value' => '11:55 PM'],
        ])->assertStatus(422)->assertJsonPath('messages.0.text', 'Unknown option');
    }

    public function test_action_option_type_is_accepted_and_wrong_type_is_422(): void
    {
        [$user, $hospital, , $doctor] = $this->seedHospitalWithDoctor();
        Sanctum::actingAs($user);
        $sessionId = $this->reachAskConsult($hospital->id);
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'yes'],
        ])->assertOk();
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'doctor', 'value' => $doctor->id],
        ])->assertOk();

        $wrong = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'text', 'value' => '2026-10-17'],
        ]);
        $this->assertSame(422, $wrong->json('status'));
        $this->assertSame('Expected input type date.', $wrong->json('messages.0.text'));

        $backToDoctor = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'choose_doctor'],
        ])->assertOk();
        $this->assertSame('CHOOSE_DOCTOR', $backToDoctor->json('state'));

        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'doctor', 'value' => $doctor->id],
        ])->assertOk();
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'date', 'value' => '2026-10-17'],
        ])->assertOk();

        $wrongSlot = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'doctor', 'value' => '09:00 AM'],
        ]);
        $this->assertSame(422, $wrongSlot->json('status'));
        $this->assertSame('Expected input type slot.', $wrongSlot->json('messages.0.text'));

        $backToDate = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'choose_date'],
        ])->assertOk();
        $this->assertSame('CHOOSE_DATE', $backToDate->json('state'));
        $this->assertSame('date', $backToDate->json('input.type'));
    }

    public function test_slot_value_options_carry_morning_and_afternoon_groups(): void
    {
        [$user, $hospital, $master] = $this->seedBaseHospital('Group Hospital', 'HIPGROUP');
        Sanctum::actingAs($user);
        $doctor = $this->createDoctor('Dr Day');
        $this->assignDoctor($doctor, $hospital, $master, 'Saturday', '11:00 AM', '02:00 PM');

        $sessionId = $this->reachAskConsult($hospital->id);
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'yes'],
        ])->assertOk();
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'doctor', 'value' => $doctor->id],
        ])->assertOk();
        $slot = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'date', 'value' => '2026-10-17'],
        ])->assertOk();

        $values = collect($slot->json('options'))->where('kind', 'value');
        $this->assertSame('Morning', $values->firstWhere('id', '11:00 AM')['group']);
        $this->assertSame('Morning', $values->firstWhere('id', '11:30 AM')['group']);
        $this->assertSame('Afternoon', $values->firstWhere('id', '12:00 PM')['group']);
        $this->assertSame('Afternoon', $values->firstWhere('id', '01:30 PM')['group']);
        $this->assertGreaterThanOrEqual(2, $values->pluck('group')->unique()->count());
        $this->assertArrayNotHasKey('group', collect($slot->json('options'))->firstWhere('id', 'choose_date'));

        file_put_contents(
            storage_path('framework/disease-chatbot-slot-groups.json'),
            json_encode($slot->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    public function test_race_slot_taken_returns_422_with_fresh_slots(): void
    {
        [$user, $hospital, , $doctor] = $this->seedHospitalWithDoctor();
        Sanctum::actingAs($user);
        $sessionId = $this->reachAskConsult($hospital->id);
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'yes'],
        ])->assertOk();
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'doctor', 'value' => $doctor->id],
        ])->assertOk();
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'date', 'value' => '2026-10-17'],
        ])->assertOk();

        DoctorBooking::withoutEvents(fn () => DoctorBooking::query()->create([
            'doctor_id' => $doctor->id,
            'hospital_id' => $hospital->id,
            'booking_date' => '2026-10-17',
            'required_time_slots' => ['09:00 AM'],
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'name' => 'Race',
            'mobile_number' => '1',
        ]));

        $race = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'slot', 'value' => '09:00 AM'],
        ])->assertStatus(422);

        $this->assertSame(422, $race->json('status'));
        $this->assertSame('CHOOSE_SLOT', $race->json('state'));
        $this->assertSame('That time was just taken. Please pick another.', $race->json('messages.0.text'));
        $this->assertSame('text', $race->json('messages.0.type'));
        $allSlots = collect($race->json('options'))->where('kind', 'value')->pluck('id')->all();
        $this->assertNotContains('09:00 AM', $allSlots);
        $this->assertNotEmpty($allSlots);
    }

    public function test_back_through_doctor_date_slot_summary(): void
    {
        [$user, $hospital, , $doctor] = $this->seedHospitalWithDoctor();
        Sanctum::actingAs($user);
        $sessionId = $this->reachAskConsult($hospital->id);
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'yes'],
        ])->assertOk();
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'doctor', 'value' => $doctor->id],
        ])->assertOk();
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'date', 'value' => '2026-10-17'],
        ])->assertOk();
        $slotResp = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'slot', 'value' => '09:00 AM'],
        ])->assertOk();
        $this->assertSame('CONFIRM_SUMMARY', $slotResp->json('state'));

        $this->assertSame('CHOOSE_SLOT', $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'back'],
        ])->assertOk()->json('state'));
        $this->assertSame('CHOOSE_DATE', $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'back'],
        ])->assertOk()->json('state'));
        $this->assertSame('CHOOSE_DOCTOR', $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'back'],
        ])->assertOk()->json('state'));
        $this->assertSame('ASK_CONSULT', $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'back'],
        ])->assertOk()->json('state'));
    }

    public function test_doctor_with_no_slots_excluded_and_inactive_excluded(): void
    {
        [$user, $hospital, $master] = $this->seedBaseHospital();
        Sanctum::actingAs($user);

        $noSlot = $this->createDoctor('Dr NoSlot');
        // Window too short for a 30-minute slot on every Saturday in the horizon.
        $this->assignDoctor($noSlot, $hospital, $master, 'Saturday', '09:00 AM', '09:10 AM');

        $inactive = $this->createDoctor('Dr Inactive', 'inactive');
        $this->assignDoctor($inactive, $hospital, $master, 'Saturday', '09:00 AM', '12:00 PM');

        $active = $this->createDoctor('Dr Active');
        $this->assignDoctor($active, $hospital, $master, 'Saturday', '09:00 AM', '12:00 PM');

        $sessionId = $this->reachAskConsult($hospital->id);
        $yes = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'yes'],
        ])->assertOk();

        $ids = collect($yes->json('options'))->where('kind', 'value')->pluck('id')->all();
        $this->assertSame([(string) $active->id], $ids);
    }

    public function test_assignment_without_profile_speciality_is_not_listed(): void
    {
        [$user, $hospital, $master] = $this->seedBaseHospital();
        Sanctum::actingAs($user);

        $profiled = $this->createDoctor('Dr Profiled');
        $this->assignDoctor($profiled, $hospital, $master, 'Saturday', '09:00 AM', '12:00 PM');

        $assignmentOnly = $this->createDoctor('Dr AssignOnly');
        $procedure = $this->ensureProcedure($hospital, $master);
        DoctorAssignment::withoutEvents(fn () => DoctorAssignment::query()->create([
            'doctor_id' => $assignmentOnly->id,
            'hospital_id' => $hospital->id,
            'status' => 'active',
            'procedure_ids' => [$procedure->id],
            'time_slots' => [
                ['day' => 'Saturday', 'start' => '09:00 AM', 'end' => '12:00 PM'],
            ],
        ]));
        // Intentionally leave speciality / hospital_ids empty (assignment chain only).

        $sessionId = $this->reachAskConsult($hospital->id);
        $yes = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'yes'],
        ])->assertOk();

        $ids = collect($yes->json('options'))->where('kind', 'value')->pluck('id')->all();
        $this->assertSame([(string) $profiled->id], $ids);
        $this->assertNotContains((string) $assignmentOnly->id, $ids);
    }

    public function test_date_picker_only_available_dates_and_rejects_empty_date(): void
    {
        [$user, $hospital, , $doctor] = $this->seedHospitalWithDoctor();
        Sanctum::actingAs($user);

        $sessionId = $this->reachAskConsult($hospital->id);
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'yes'],
        ])->assertOk();
        $dateStep = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'doctor', 'value' => $doctor->id],
        ])->assertOk();

        $this->assertSame('CHOOSE_DATE', $dateStep->json('state'));
        $items = collect($dateStep->json('options'))->where('kind', 'value')->values()->all();
        $this->assertNotEmpty($items);
        foreach ($items as $item) {
            $this->assertTrue($item['available']);
            $this->assertGreaterThan(0, $item['slots_count']);
        }
        $this->assertNull(collect($items)->firstWhere('available', false));
        $this->assertNull(collect($items)->firstWhere('slots_count', 0));

        $bad = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'date', 'value' => '2099-01-01'],
        ]);
        $this->assertSame(422, $bad->json('status'));
        $this->assertSame('CHOOSE_DATE', $bad->json('state'));
        $this->assertSame('Unknown option', $bad->json('messages.0.text'));
    }

    public function test_choose_date_zero_available_dates_message(): void
    {
        config(['chatbot.hide_doctors_without_slots' => false]);
        [$user, $hospital, $master] = $this->seedBaseHospital();
        Sanctum::actingAs($user);

        $doctor = $this->createDoctor('Dr Profile NoSlots');
        Doctor::withoutEvents(fn () => $doctor->forceFill([
            'speciality' => [(string) $master->id],
            'hospital_ids' => [(string) $hospital->id],
        ])->save());

        $sessionId = $this->reachAskConsult($hospital->id);
        $yes = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'yes'],
        ])->assertOk();
        $this->assertSame([(string) $doctor->id], collect($yes->json('options'))->where('kind', 'value')->pluck('id')->all());

        $dateStep = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'doctor', 'value' => $doctor->id],
        ])->assertOk();

        $this->assertSame('CHOOSE_DATE', $dateStep->json('state'));
        $this->assertSame('option', $dateStep->json('input.type'));
        $this->assertSame('option', $dateStep->json('input.mode'));
        $this->assertSame(
            'No dates are available for this doctor right now.',
            $dateStep->json('messages.0.text')
        );
        $this->assertNotNull(collect($dateStep->json('options'))->firstWhere('id', 'choose_doctor'));
    }

    public function test_dumps_doctor_flow_real_json(): void
    {
        [$user, $hospital, $cardio, $pulmo] = $this->seedMultiSpecialityHospitalWithDoctors();
        Sanctum::actingAs($user);

        $dump = [];

        $sessionMulti = $this->reachAskConsult($hospital->id, ['Cough']);
        $chooseSpec = $this->postJson('/api/chatbot/sessions/'.$sessionMulti.'/reply', [
            'input' => ['type' => 'option', 'value' => 'yes'],
        ]);
        $dump['choose_speciality'] = ['http' => $chooseSpec->getStatusCode(), 'body' => $chooseSpec->json()];

        [$user2, $hospital2, $master2, $doctor2] = $this->seedHospitalWithDoctor('Solo Hospital', 'HIPDOC02');
        Sanctum::actingAs($user2);
        $sessionId = $this->reachAskConsult($hospital2->id);
        $chooseDoctor = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'yes'],
        ]);
        $dump['choose_doctor'] = ['http' => $chooseDoctor->getStatusCode(), 'body' => $chooseDoctor->json()];

        $chooseDate = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'doctor', 'value' => $doctor2->id],
        ]);
        $dump['choose_date'] = ['http' => $chooseDate->getStatusCode(), 'body' => $chooseDate->json()];

        $chooseSlot = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'date', 'value' => '2026-10-17'],
        ]);
        $dump['choose_slot'] = ['http' => $chooseSlot->getStatusCode(), 'body' => $chooseSlot->json()];

        $summary = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'slot', 'value' => '09:00 AM'],
        ]);
        $dump['confirm_summary'] = ['http' => $summary->getStatusCode(), 'body' => $summary->json()];

        DoctorBooking::withoutEvents(fn () => DoctorBooking::query()->create([
            'doctor_id' => $doctor2->id,
            'hospital_id' => $hospital2->id,
            'booking_date' => '2026-10-17',
            'required_time_slots' => ['09:30 AM'],
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'name' => 'Race',
            'mobile_number' => '1',
        ]));

        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'change_time'],
        ])->assertOk();
        $race = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'slot', 'value' => '09:30 AM'],
        ]);
        $dump['race_422'] = ['http' => $race->getStatusCode(), 'body' => $race->json()];

        $path = storage_path('framework/disease-chatbot-doctor-flow-dump.json');
        file_put_contents($path, json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fwrite(STDOUT, "\n===== DISEASE CHATBOT DOCTOR FLOW DUMP =====\n");
        fwrite(STDOUT, file_get_contents($path)."\n");
        fwrite(STDOUT, "===== END DOCTOR FLOW DUMP ({$path}) =====\n");

        $this->assertSame(200, $dump['choose_speciality']['http']);
        $this->assertSame('CHOOSE_SPECIALITY', $dump['choose_speciality']['body']['state']);
        $this->assertSame('CHOOSE_DOCTOR', $dump['choose_doctor']['body']['state']);
        $this->assertSame('CHOOSE_DATE', $dump['choose_date']['body']['state']);
        $this->assertSame('CHOOSE_SLOT', $dump['choose_slot']['body']['state']);
        $this->assertSame('CONFIRM_SUMMARY', $dump['confirm_summary']['body']['state']);
        $this->assertSame(422, $dump['race_422']['http']);
    }

    /**
     * @param  list<string>  $symptoms
     */
    private function reachAskConsult(int $hospitalId, array $symptoms = ['Wheeze', 'Cough']): string
    {
        $sessionId = $this->postJson('/api/chatbot/sessions', ['hospital_id' => $hospitalId])
            ->assertOk()
            ->json('session_id');
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ])->assertOk();
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'symptoms', 'value' => $symptoms],
        ])->assertOk();

        return $sessionId;
    }

    /**
     * @return array{0: HIPUser, 1: Hospital, 2: SpecialitiesMaster, 3: Doctor}
     */
    private function seedHospitalWithDoctor(string $hospitalName = 'Chat Hospital', string $hipId = 'HIPDOC01'): array
    {
        [$user, $hospital, $master] = $this->seedBaseHospital($hospitalName, $hipId);
        $doctor = $this->createDoctor('Dr Available');
        $this->assignDoctor($doctor, $hospital, $master, 'Saturday', '09:00 AM', '12:00 PM');

        return [$user, $hospital, $master, $doctor];
    }

    /**
     * @return array{0: HIPUser, 1: Hospital, 2: SpecialitiesMaster}
     */
    private function seedBaseHospital(string $hospitalName = 'Chat Hospital', string $hipId = 'HIPDOC01'): array
    {
        $user = $this->member('docflow@example.com', $hipId);
        $this->person($user, true);
        $hospital = Hospital::withoutEvents(fn () => Hospital::query()->create(['name' => $hospitalName]));
        $master = $this->master('Cardiology', 'CARD'.$hipId, [
            [
                'name' => 'Asthma',
                'symptoms' => ['Wheeze', 'Cough'],
                'about' => 'Airway inflammation',
                'recommended_tests' => ['Spirometry'],
            ],
        ]);
        $this->department($hospital->id, $master->id, 'active');
        $this->ensureProcedure($hospital, $master);

        return [$user, $hospital, $master];
    }

    /**
     * @return array{0: HIPUser, 1: Hospital}
     */
    private function seedMultiSpecialityHospital(): array
    {
        $user = $this->member('multi@example.com', 'HIPMULTI1');
        $this->person($user, true);
        $hospital = Hospital::withoutEvents(fn () => Hospital::query()->create(['name' => 'Multi Hospital']));

        $cardio = $this->master('Cardiology', 'MCARD', [
            ['name' => 'Asthma', 'symptoms' => ['Cough'], 'about' => 'a', 'recommended_tests' => []],
        ]);
        $pulmo = $this->master('Pulmonology', 'MPULM', [
            ['name' => 'Bronchitis', 'symptoms' => ['Cough'], 'about' => 'b', 'recommended_tests' => []],
        ]);
        $this->department($hospital->id, $cardio->id, 'active');
        $this->department($hospital->id, $pulmo->id, 'active');

        return [$user, $hospital];
    }

    /**
     * @return array{0: HIPUser, 1: Hospital, 2: SpecialitiesMaster, 3: SpecialitiesMaster}
     */
    private function seedMultiSpecialityHospitalWithDoctors(): array
    {
        [$user, $hospital] = $this->seedMultiSpecialityHospital();
        $cardio = SpecialitiesMaster::query()->where('code', 'MCARD')->firstOrFail();
        $pulmo = SpecialitiesMaster::query()->where('code', 'MPULM')->firstOrFail();
        $this->assignDoctor($this->createDoctor('Dr Cardio'), $hospital, $cardio, 'Saturday', '09:00 AM', '12:00 PM');
        $this->assignDoctor($this->createDoctor('Dr Pulmo'), $hospital, $pulmo, 'Saturday', '09:00 AM', '12:00 PM');

        return [$user, $hospital, $cardio, $pulmo];
    }

    private function seedSecondSpecialityWithDoctor(Hospital $hospital): SpecialitiesMaster
    {
        $pulmo = $this->master('Pulmonology', 'PULMDUMP', [
            ['name' => 'Bronchitis', 'symptoms' => ['Cough'], 'about' => 'b', 'recommended_tests' => []],
        ]);
        $this->department($hospital->id, $pulmo->id, 'active');
        $this->assignDoctor($this->createDoctor('Dr Pulmo Dump'), $hospital, $pulmo, 'Saturday', '09:00 AM', '12:00 PM');

        return $pulmo;
    }

    private function ensureProcedure(Hospital $hospital, SpecialitiesMaster $master): Procedure
    {
        $dept = Speciality::query()
            ->where('hospital_id', $hospital->id)
            ->where('speciality_master_id', $master->id)
            ->firstOrFail();

        $existing = Procedure::query()
            ->where('speciality_id', $dept->id)
            ->where('hospital_id', $hospital->id)
            ->first();
        if ($existing) {
            return $existing;
        }

        return Procedure::query()->create([
            'procedure_name' => 'Consult',
            'speciality_id' => $dept->id,
            'hospital_id' => $hospital->id,
            'status' => 'active',
        ]);
    }

    private function createDoctor(string $name, string $status = 'active'): Doctor
    {
        return Doctor::withoutEvents(function () use ($name, $status) {
            $doctor = new Doctor();
            $doctor->forceFill([
                'name' => $name,
                'status' => $status,
                'consultation_fee' => 500,
                'working_since' => '2018',
                'qualifications' => [],
                'speciality' => [],
                'hospital_ids' => [],
            ]);
            $doctor->id = (string) Str::uuid();
            $doctor->save();

            return $doctor;
        });
    }

    private function assignDoctor(
        Doctor $doctor,
        Hospital $hospital,
        SpecialitiesMaster $master,
        string $day,
        string $start,
        string $end
    ): void {
        $procedure = $this->ensureProcedure($hospital, $master);
        DoctorAssignment::withoutEvents(fn () => DoctorAssignment::query()->create([
            'doctor_id' => $doctor->id,
            'hospital_id' => $hospital->id,
            'status' => 'active',
            'procedure_ids' => [$procedure->id],
            'time_slots' => [
                ['day' => $day, 'start' => $start, 'end' => $end],
            ],
        ]));
        $speciality = array_values(array_unique(array_merge(
            array_map('intval', (array) ($doctor->speciality ?? [])),
            [(int) $master->id]
        )));
        $hospitalIds = array_values(array_unique(array_merge(
            array_map('intval', (array) ($doctor->hospital_ids ?? [])),
            [(int) $hospital->id]
        )));
        Doctor::withoutEvents(fn () => $doctor->forceFill([
            'speciality' => $speciality,
            'hospital_ids' => $hospitalIds,
        ])->save());
        $doctor->refresh();
    }

    private function member(string $email, string $hipId): HIPUser
    {
        return HIPUser::withoutEvents(fn () => HIPUser::query()->create([
            'email' => $email,
            'first_name' => 'Chat',
            'last_name' => 'User',
            'hip_id' => $hipId,
            'is_active' => true,
            'password' => null,
            'mobile_num' => '9800000001',
        ]));
    }

    private function person(HIPUser $user, bool $primary): Persons
    {
        return Persons::withoutEvents(fn () => Persons::query()->create([
            'first_name' => 'Self',
            'last_name' => 'Member',
            'hip_user_id' => $user->id,
            'parent_id' => null,
            'is_primary' => $primary,
            'is_active' => true,
        ]));
    }

    private function master(string $name, string $code, array $diseases): SpecialitiesMaster
    {
        return SpecialitiesMaster::withoutEvents(function () use ($name, $code, $diseases) {
            $master = new SpecialitiesMaster();
            $master->forceFill([
                'name' => $name,
                'code' => $code,
                'status' => 'active',
                'diseases' => $diseases,
            ]);
            $master->save();

            return $master->fresh();
        });
    }

    private function department(int $hospitalId, int $masterId, string $status): Speciality
    {
        return Speciality::withoutEvents(function () use ($hospitalId, $masterId, $status) {
            $row = new Speciality();
            $row->forceFill([
                'speciality_name' => 'Dept '.$masterId,
                'speciality_code' => 'D'.$masterId.$hospitalId,
                'department_category' => 'Dept '.$masterId,
                'status' => $status,
                'hospital_id' => $hospitalId,
                'organization_id' => 1,
                'speciality_master_id' => $masterId,
            ]);
            $row->save();

            return $row->fresh();
        });
    }

    private function bootSchema(): void
    {
        if (self::$schemaReady && Schema::hasTable('disease_chatbot_sessions') && Schema::hasTable('coins')) {
            return;
        }

        foreach ([
            'disease_chatbot_messages', 'disease_chatbot_sessions', 'disease_chatbot_bookings',
            'doctor_bookings', 'doctor_reviews', 'doctor_assignments', 'procedures', 'doctors',
            'specialities', 'specialities_masters', 'coins', 'persons', 'hospitals', 'healthinpocket_users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('healthinpocket_users', function ($table) {
            $table->uuid('id')->primary();
            $table->string('email')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('password')->nullable();
            $table->string('hip_id')->nullable();
            $table->string('mobile_num')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('persons', function ($table) {
            $table->uuid('id')->primary();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->uuid('hip_user_id')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('hospitals', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });
        Schema::create('specialities_masters', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->json('diseases')->nullable();
            $table->string('display_image')->nullable();
            $table->string('status')->default('inactive');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('specialities', function ($table) {
            $table->id();
            $table->string('speciality_name');
            $table->string('speciality_code');
            $table->string('department_category');
            $table->string('status')->default('inactive');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('speciality_master_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('procedures', function ($table) {
            $table->id();
            $table->string('procedure_name')->nullable();
            $table->unsignedBigInteger('speciality_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('doctors', function ($table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('status')->default('inactive');
            $table->string('doctor_image')->nullable();
            $table->json('qualifications')->nullable();
            $table->json('speciality')->nullable();
            $table->json('hospital_ids')->nullable();
            $table->string('working_since')->nullable();
            $table->decimal('consultation_fee', 10, 2)->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('doctor_assignments', function ($table) {
            $table->id();
            $table->uuid('doctor_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('day')->nullable();
            $table->date('date')->nullable();
            $table->json('time_slots')->nullable();
            $table->json('procedure_ids')->nullable();
            $table->string('status')->default('active');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('doctor_bookings', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('mobile_number')->nullable();
            $table->uuid('doctor_id');
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->date('booking_date')->nullable();
            $table->json('required_time_slots')->nullable();
            $table->string('status')->default('pending');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('doctor_reviews', function ($table) {
            $table->id();
            $table->uuid('doctor_id');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
        Schema::create('coins', function ($table) {
            $table->id();
            $table->uuid('person_id');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedInteger('coins')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('disease_chatbot_sessions', function ($table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('person_id')->nullable();
            $table->unsignedBigInteger('hospital_id');
            $table->string('state');
            $table->json('context')->nullable();
            $table->string('status');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('disease_chatbot_messages', function ($table) {
            $table->id();
            $table->uuid('session_id');
            $table->string('sender');
            $table->string('type');
            $table->json('payload')->nullable();
            $table->timestamps();
        });
        Schema::create('disease_chatbot_bookings', function ($table) {
            $table->id();
            $table->uuid('session_id');
            $table->unsignedBigInteger('doctor_booking_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('status');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('refund_notified_at')->nullable();
            $table->timestamps();
            $table->unique('session_id');
            $table->unique('doctor_booking_id');
        });

        self::$schemaReady = true;
    }
}
