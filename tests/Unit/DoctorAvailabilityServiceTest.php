<?php

namespace Tests\Unit;

use App\Models\DiseaseChatbotBooking;
use App\Models\Doctor;
use App\Models\DoctorAssignment;
use App\Models\DoctorBooking;
use App\Models\Hospital;
use App\Models\Procedure;
use App\Models\SpecialitiesMaster;
use App\Models\Speciality;
use App\Services\DoctorAvailabilityService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class DoctorAvailabilityServiceTest extends TestCase
{
    private static bool $schemaReady = false;

    private DoctorAvailabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'sqlite') {
            $this->fail('Doctor availability tests must use sqlite.');
        }
        $this->bootSchema();
        $this->service = app(DoctorAvailabilityService::class);
        config([
            'chatbot.slot_minutes' => 30,
            'chatbot.slot_lead_minutes' => 30,
            'chatbot.slot_hold_minutes' => 15,
            'chatbot.slot_horizon_days' => 14,
            'app.timezone' => 'Asia/Kolkata',
        ]);
        Carbon::setTestNow(Carbon::parse('2026-10-17 10:00:00', 'Asia/Kolkata'));

        foreach ([
            'disease_chatbot_bookings', 'doctor_bookings', 'doctor_reviews', 'doctor_assignments', 'procedures',
            'doctors', 'specialities', 'specialities_masters', 'hospitals',
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

    public function test_window_split_and_last_slot_fits(): void
    {
        [$hospital, $master, $doctor] = $this->seedDoctorWithWindow('Saturday', '09:00 AM', '11:00 AM');
        // 2026-10-17 is Saturday
        $slots = $this->service->slotsForDoctor($doctor->id, $hospital->id, Carbon::parse('2026-10-17', 'Asia/Kolkata'), 1);
        $times = $slots['2026-10-17'] ?? [];
        // lead: now 10:00 + 30 = 10:30, so first valid is 10:30; 10:30+30<=11:00
        $this->assertSame(['10:30 AM'], $times);
    }

    public function test_window_not_multiple_of_thirty(): void
    {
        [$hospital, , $doctor] = $this->seedDoctorWithWindow('Saturday', '09:00 AM', '10:15 AM');
        Carbon::setTestNow(Carbon::parse('2026-10-17 08:00:00', 'Asia/Kolkata'));
        $times = $this->service->slotsForDoctor($doctor->id, $hospital->id, Carbon::parse('2026-10-17'), 1)['2026-10-17'];
        // 09:00, 09:30; 10:00+30 > 10:15 so drop 10:00
        $this->assertSame(['09:00 AM', '09:30 AM'], $times);
    }

    public function test_lead_time_drops_past_and_near_slots(): void
    {
        [$hospital, , $doctor] = $this->seedDoctorWithWindow('Saturday', '09:00 AM', '12:00 PM');
        Carbon::setTestNow(Carbon::parse('2026-10-17 10:00:00', 'Asia/Kolkata'));
        $times = $this->service->slotsForDoctor($doctor->id, $hospital->id, Carbon::parse('2026-10-17'), 1)['2026-10-17'];
        $this->assertSame(['10:30 AM', '11:00 AM', '11:30 AM'], $times);
    }

    public function test_booked_times_normalize_2pm_vs_02pm_and_cancelled_frees(): void
    {
        [$hospital, , $doctor] = $this->seedDoctorWithWindow('Saturday', '01:00 PM', '04:00 PM');
        Carbon::setTestNow(Carbon::parse('2026-10-17 08:00:00', 'Asia/Kolkata'));

        DoctorBooking::withoutEvents(fn () => DoctorBooking::query()->create([
            'doctor_id' => $doctor->id,
            'hospital_id' => $hospital->id,
            'booking_date' => '2026-10-17',
            'required_time_slots' => ['2:00 PM'],
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'name' => 'A',
            'mobile_number' => '1',
        ]));

        $times = $this->service->slotsForDoctor($doctor->id, $hospital->id, Carbon::parse('2026-10-17'), 1)['2026-10-17'];
        $this->assertNotContains('02:00 PM', $times);

        DoctorBooking::query()->where('doctor_id', $doctor->id)->update([
            'status' => DoctorBooking::STATUS_CANCELLED,
        ]);
        $times2 = $this->service->slotsForDoctor($doctor->id, $hospital->id, Carbon::parse('2026-10-17'), 1)['2026-10-17'];
        $this->assertContains('02:00 PM', $times2);
    }

    public function test_pending_hold_under_and_over_15_minutes(): void
    {
        [$hospital, , $doctor] = $this->seedDoctorWithWindow('Saturday', '01:00 PM', '03:00 PM');
        Carbon::setTestNow(Carbon::parse('2026-10-17 08:00:00', 'Asia/Kolkata'));

        $fresh = DoctorBooking::withoutEvents(fn () => DoctorBooking::query()->create([
            'doctor_id' => $doctor->id,
            'hospital_id' => $hospital->id,
            'booking_date' => '2026-10-17',
            'required_time_slots' => ['01:30 PM'],
            'status' => DoctorBooking::STATUS_PENDING,
            'name' => 'A',
            'mobile_number' => '1',
            'created_at' => Carbon::parse('2026-10-17 07:50:00', 'Asia/Kolkata'),
            'updated_at' => Carbon::parse('2026-10-17 07:50:00', 'Asia/Kolkata'),
        ]));
        $times = $this->service->slotsForDoctor($doctor->id, $hospital->id, Carbon::parse('2026-10-17'), 1)['2026-10-17'];
        $this->assertNotContains('01:30 PM', $times);

        $fresh->forceFill([
            'created_at' => Carbon::parse('2026-10-17 07:40:00', 'Asia/Kolkata'),
        ])->save();
        $times2 = $this->service->slotsForDoctor($doctor->id, $hospital->id, Carbon::parse('2026-10-17'), 1)['2026-10-17'];
        $this->assertContains('01:30 PM', $times2);
    }

    public function test_chatbot_pending_payment_blocks_slot_past_hold_window(): void
    {
        [$hospital, , $doctor] = $this->seedDoctorWithWindow('Saturday', '01:00 PM', '03:00 PM');
        Carbon::setTestNow(Carbon::parse('2026-10-17 08:00:00', 'Asia/Kolkata'));

        $booking = DoctorBooking::withoutEvents(fn () => DoctorBooking::query()->create([
            'doctor_id' => $doctor->id,
            'hospital_id' => $hospital->id,
            'booking_date' => '2026-10-17',
            'required_time_slots' => ['01:30 PM'],
            'status' => DoctorBooking::STATUS_PENDING,
            'name' => 'ChatHold',
            'mobile_number' => '1',
        ]));
        $booking->forceFill([
            'created_at' => Carbon::parse('2026-10-17 07:00:00', 'Asia/Kolkata'),
            'updated_at' => Carbon::parse('2026-10-17 07:00:00', 'Asia/Kolkata'),
        ])->save();

        // Without chatbot link, stale pending would free after 15 minutes.
        $timesFree = $this->service->slotsForDoctor($doctor->id, $hospital->id, Carbon::parse('2026-10-17'), 1)['2026-10-17'];
        $this->assertContains('01:30 PM', $timesFree);

        DiseaseChatbotBooking::query()->create([
            'session_id' => (string) Str::uuid(),
            'doctor_booking_id' => $booking->id,
            'invoice_id' => null,
            'status' => DiseaseChatbotBooking::STATUS_PENDING_PAYMENT,
            'expires_at' => Carbon::parse('2026-10-17 08:15:00', 'Asia/Kolkata'),
        ]);

        $timesBlocked = $this->service->slotsForDoctor($doctor->id, $hospital->id, Carbon::parse('2026-10-17'), 1)['2026-10-17'];
        $this->assertNotContains('01:30 PM', $timesBlocked);
    }

    public function test_chatbot_expired_link_stops_blocking_stale_pending(): void
    {
        [$hospital, , $doctor] = $this->seedDoctorWithWindow('Saturday', '01:00 PM', '03:00 PM');
        Carbon::setTestNow(Carbon::parse('2026-10-17 08:00:00', 'Asia/Kolkata'));

        $booking = DoctorBooking::withoutEvents(fn () => DoctorBooking::query()->create([
            'doctor_id' => $doctor->id,
            'hospital_id' => $hospital->id,
            'booking_date' => '2026-10-17',
            'required_time_slots' => ['02:00 PM'],
            'status' => DoctorBooking::STATUS_PENDING,
            'name' => 'ChatExp',
            'mobile_number' => '1',
        ]));
        $booking->forceFill([
            'created_at' => Carbon::parse('2026-10-17 07:00:00', 'Asia/Kolkata'),
            'updated_at' => Carbon::parse('2026-10-17 07:00:00', 'Asia/Kolkata'),
        ])->save();

        $link = DiseaseChatbotBooking::query()->create([
            'session_id' => (string) Str::uuid(),
            'doctor_booking_id' => $booking->id,
            'invoice_id' => null,
            'status' => DiseaseChatbotBooking::STATUS_PENDING_PAYMENT,
            'expires_at' => now()->subMinute(),
        ]);

        $this->assertNotContains(
            '02:00 PM',
            $this->service->slotsForDoctor($doctor->id, $hospital->id, Carbon::parse('2026-10-17'), 1)['2026-10-17']
        );

        foreach ([
            DiseaseChatbotBooking::STATUS_EXPIRED,
            DiseaseChatbotBooking::STATUS_CANCELLED,
            DiseaseChatbotBooking::STATUS_REFUND_REQUIRED,
        ] as $terminal) {
            $link->update(['status' => $terminal]);
            $times = $this->service->slotsForDoctor($doctor->id, $hospital->id, Carbon::parse('2026-10-17'), 1)['2026-10-17'];
            $this->assertContains('02:00 PM', $times, "slot should free when link is {$terminal}");
        }
    }

    public function test_booking_at_another_hospital_blocks(): void
    {
        [$hospital, , $doctor] = $this->seedDoctorWithWindow('Saturday', '01:00 PM', '03:00 PM');
        $other = Hospital::withoutEvents(fn () => Hospital::query()->create(['name' => 'Other']));
        Carbon::setTestNow(Carbon::parse('2026-10-17 08:00:00', 'Asia/Kolkata'));

        DoctorBooking::withoutEvents(fn () => DoctorBooking::query()->create([
            'doctor_id' => $doctor->id,
            'hospital_id' => $other->id,
            'booking_date' => '2026-10-17',
            'required_time_slots' => ['01:00 PM'],
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'name' => 'A',
            'mobile_number' => '1',
        ]));

        $times = $this->service->slotsForDoctor($doctor->id, $hospital->id, Carbon::parse('2026-10-17'), 1)['2026-10-17'];
        $this->assertNotContains('01:00 PM', $times);
    }

    public function test_two_windows_in_one_day_and_hour_groups(): void
    {
        [$hospital, $master, $doctor] = $this->seedBase();
        DoctorAssignment::withoutEvents(fn () => DoctorAssignment::query()->create([
            'doctor_id' => $doctor->id,
            'hospital_id' => $hospital->id,
            'status' => 'active',
            'procedure_ids' => [Procedure::query()->value('id')],
            'time_slots' => [
                ['day' => 'Saturday', 'start' => '09:00 AM', 'end' => '10:00 AM'],
                ['day' => 'Saturday', 'start' => '05:00 PM', 'end' => '06:00 PM'],
            ],
        ]));
        Carbon::setTestNow(Carbon::parse('2026-10-17 08:00:00', 'Asia/Kolkata'));
        $times = $this->service->slotsForDoctor($doctor->id, $hospital->id, Carbon::parse('2026-10-17'), 1)['2026-10-17'];
        $this->assertSame(['09:00 AM', '09:30 AM', '05:00 PM', '05:30 PM'], $times);

        $groups = $this->service->hourGroups($times);
        $labels = collect($groups)->pluck('label')->all();
        $this->assertSame(['Morning', 'Evening'], $labels);
        $this->assertSame('09:00 AM', $groups[0]['slots'][0]['value']);
        $this->assertSame('09:00 AM', $groups[0]['slots'][0]['label']);
    }

    public function test_next_available_label_and_null_qualification(): void
    {
        [$hospital, $master, $doctor] = $this->seedDoctorWithWindow('Saturday', '09:00 AM', '12:00 PM');
        Carbon::setTestNow(Carbon::parse('2026-10-17 08:00:00', 'Asia/Kolkata'));
        $cards = $this->service->doctorsWithAvailability($hospital->id, $master->id, 10);
        $this->assertSame('Today, 09:00 AM', $cards[0]['next_available']['label']);
        $this->assertNull($cards[0]['qualification']);
    }

    public function test_inactive_doctor_and_wrong_hospital_speciality_excluded(): void
    {
        [$hospital, $master, $doctor] = $this->seedDoctorWithWindow('Saturday', '09:00 AM', '12:00 PM');
        $doctor->update(['status' => 'inactive']);
        $this->assertTrue($this->service->eligibleDoctors($hospital->id, $master->id)->isEmpty());

        $doctor->update(['status' => 'active']);
        $otherHospital = Hospital::withoutEvents(fn () => Hospital::query()->create(['name' => 'H2']));
        $this->assertTrue($this->service->eligibleDoctors($otherHospital->id, $master->id)->isEmpty());
    }

    public function test_profile_speciality_lists_doctor_assignment_without_profile_does_not(): void
    {
        [$hospital, $master, $withProfile] = $this->seedDoctorWithWindow('Saturday', '09:00 AM', '12:00 PM');
        Carbon::setTestNow(Carbon::parse('2026-10-17 08:00:00', 'Asia/Kolkata'));

        $listed = $this->service->doctorsWithAvailability($hospital->id, $master->id, 10);
        $this->assertSame([(string) $withProfile->id], collect($listed)->pluck('id')->all());

        $assignmentOnly = Doctor::withoutEvents(function () {
            $doctor = new Doctor();
            $doctor->forceFill([
                'name' => 'Dr Assign Only',
                'status' => 'active',
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
        DoctorAssignment::withoutEvents(fn () => DoctorAssignment::query()->create([
            'doctor_id' => $assignmentOnly->id,
            'hospital_id' => $hospital->id,
            'status' => 'active',
            'procedure_ids' => [Procedure::query()->value('id')],
            'time_slots' => [
                ['day' => 'Saturday', 'start' => '09:00 AM', 'end' => '12:00 PM'],
            ],
        ]));

        $this->assertTrue($this->service->eligibleDoctors($hospital->id, $master->id)->pluck('id')->doesntContain($assignmentOnly->id));
        $this->assertSame([(string) $withProfile->id], collect(
            $this->service->doctorsWithAvailability($hospital->id, $master->id, 10)
        )->pluck('id')->all());
    }

    public function test_profile_speciality_without_slots_is_hidden_when_configured(): void
    {
        [$hospital, $master] = $this->seedBase();
        $doctor = Doctor::withoutEvents(function () use ($hospital, $master) {
            $doctor = new Doctor();
            $doctor->forceFill([
                'name' => 'Dr No Assign',
                'status' => 'active',
                'consultation_fee' => 500,
                'working_since' => '2018',
                'qualifications' => [],
                'speciality' => [(string) $master->id],
                'hospital_ids' => [(string) $hospital->id],
            ]);
            $doctor->id = (string) Str::uuid();
            $doctor->save();

            return $doctor;
        });

        Carbon::setTestNow(Carbon::parse('2026-10-17 08:00:00', 'Asia/Kolkata'));
        config(['chatbot.hide_doctors_without_slots' => true]);
        $this->assertTrue($this->service->eligibleDoctors($hospital->id, $master->id)->pluck('id')->contains($doctor->id));
        $this->assertSame([], $this->service->doctorsWithAvailability($hospital->id, $master->id, 10));
        $this->assertSame([], $this->service->specialitiesWithAvailability($hospital->id, [
            ['id' => (int) $master->id, 'name' => 'Cardiology'],
        ]));
    }

    public function test_json_id_list_contains_mixed_ints_and_strings(): void
    {
        $this->assertTrue($this->service->jsonIdListContains([1, '2', 3], 2));
        $this->assertTrue($this->service->jsonIdListContains(['10'], 10));
        $this->assertTrue($this->service->jsonIdListContains([10], 10));
        $this->assertFalse($this->service->jsonIdListContains(['10a'], 10));
        $this->assertFalse($this->service->jsonIdListContains([], 1));
    }

    public function test_date_picker_items_only_available_dates(): void
    {
        $items = $this->service->datePickerItems([
            '2026-10-17' => ['09:00 AM'],
            '2026-10-18' => [],
            '2026-10-19' => ['10:00 AM', '10:30 AM'],
        ]);
        $this->assertSame([
            [
                'date' => '2026-10-17',
                'label' => Carbon::parse('2026-10-17', 'Asia/Kolkata')->format('D, j M'),
                'slots_count' => 1,
                'available' => true,
            ],
            [
                'date' => '2026-10-19',
                'label' => Carbon::parse('2026-10-19', 'Asia/Kolkata')->format('D, j M'),
                'slots_count' => 2,
                'available' => true,
            ],
        ], $items);
    }

    public function test_query_count_bound_for_slots_range(): void
    {
        [$hospital, , $doctor] = $this->seedDoctorWithWindow('Saturday', '09:00 AM', '12:00 PM');
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->service->slotsForDoctor($doctor->id, $hospital->id, Carbon::parse('2026-10-17'), 14);
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertLessThanOrEqual(4, $count, 'Expected assignments + bookings (+ maybe trivial) only, got '.$count);
    }

    /**
     * @return array{0: Hospital, 1: SpecialitiesMaster, 2: Doctor}
     */
    private function seedDoctorWithWindow(string $day, string $start, string $end): array
    {
        [$hospital, $master, $doctor] = $this->seedBase();
        $procedureId = (int) Procedure::query()->value('id');
        DoctorAssignment::withoutEvents(fn () => DoctorAssignment::query()->create([
            'doctor_id' => $doctor->id,
            'hospital_id' => $hospital->id,
            'status' => 'active',
            'procedure_ids' => [$procedureId],
            'time_slots' => [
                ['day' => $day, 'start' => $start, 'end' => $end],
            ],
        ]));

        return [$hospital, $master, $doctor];
    }

    /**
     * @return array{0: Hospital, 1: SpecialitiesMaster, 2: Doctor}
     */
    private function seedBase(): array
    {
        $hospital = Hospital::withoutEvents(fn () => Hospital::query()->create(['name' => 'Avail Hospital']));
        $master = SpecialitiesMaster::withoutEvents(function () {
            $master = new SpecialitiesMaster();
            $master->forceFill([
                'name' => 'Cardiology',
                'code' => 'CARD',
                'status' => 'active',
                'diseases' => [['name' => 'Asthma', 'symptoms' => ['Cough'], 'about' => 'a', 'recommended_tests' => []]],
            ]);
            $master->save();

            return $master;
        });

        $dept = Speciality::withoutEvents(function () use ($hospital, $master) {
            $dept = new Speciality();
            $dept->forceFill([
                'speciality_name' => 'Cardio',
                'speciality_code' => 'C1',
                'department_category' => 'Cardio',
                'status' => 'active',
                'hospital_id' => $hospital->id,
                'organization_id' => 1,
                'speciality_master_id' => $master->id,
            ]);
            $dept->save();

            return $dept;
        });

        Procedure::query()->create([
            'procedure_name' => 'Consult',
            'speciality_id' => $dept->id,
            'hospital_id' => $hospital->id,
            'status' => 'active',
        ]);

        $doctor = Doctor::withoutEvents(function () use ($hospital, $master) {
            $doctor = new Doctor();
            $doctor->forceFill([
                'name' => 'Dr Available',
                'status' => 'active',
                'consultation_fee' => 500,
                'working_since' => '2018',
                'qualifications' => [],
                'speciality' => [(int) $master->id],
                'hospital_ids' => [(int) $hospital->id],
            ]);
            $doctor->id = (string) Str::uuid();
            $doctor->save();

            return $doctor;
        });

        return [$hospital, $master->fresh(), $doctor];
    }

    private function bootSchema(): void
    {
        if (self::$schemaReady && Schema::hasTable('doctor_assignments')) {
            return;
        }

        Schema::dropIfExists('doctor_bookings');
        Schema::dropIfExists('doctor_reviews');
        Schema::dropIfExists('doctor_assignments');
        Schema::dropIfExists('disease_chatbot_bookings');
        Schema::dropIfExists('procedures');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('specialities');
        Schema::dropIfExists('specialities_masters');
        Schema::dropIfExists('hospitals');

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
            $table->decimal('rating', 3, 1)->nullable();
            $table->string('status')->default('active');
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
