<?php

namespace Tests\Feature;

use App\Models\DoctorBooking;
use App\Models\HIPUser;
use App\Models\Persons;
use App\Modules\Automation\Events\AnniversaryReached;
use App\Modules\Automation\Events\AppointmentBooked;
use App\Modules\Automation\Events\AppointmentMissed;
use App\Modules\Automation\Events\BirthdayReached;
use App\Modules\Automation\Events\PatientRegistered;
use App\Modules\Automation\Events\ScheduledEvent;
use App\Modules\Automation\Listeners\DispatchHospitalAutomationWorkflow;
use App\Modules\Automation\Observers\DoctorBookingObserver;
use App\Modules\Automation\Observers\PersonsObserver;
use App\Modules\Automation\TriggerHandlers\HospitalAutomationTriggerService;
use App\Modules\Automation\Support\EventDispatchGuard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class HospitalAutomationStep2DispatchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_listeners_are_registered_for_step_2_events(): void
    {
        $this->assertTrue(Event::hasListeners(PatientRegistered::class));
        $this->assertTrue(Event::hasListeners(AppointmentMissed::class));
        $this->assertTrue(Event::hasListeners(BirthdayReached::class));
        $this->assertTrue(Event::hasListeners(AnniversaryReached::class));
        $this->assertTrue(Event::hasListeners(ScheduledEvent::class));
        $this->assertTrue(Event::hasListeners(AppointmentBooked::class));
    }

    public function test_scheduled_automation_commands_are_registered(): void
    {
        $this->assertContains('hospital-automation:dispatch-birthdays', array_keys(\Illuminate\Support\Facades\Artisan::all()));
        $this->assertContains('hospital-automation:dispatch-anniversaries', array_keys(\Illuminate\Support\Facades\Artisan::all()));
        $this->assertContains('hospital-automation:dispatch-scheduled-events', array_keys(\Illuminate\Support\Facades\Artisan::all()));
        $this->assertContains('hospital-automation:dispatch-missed-appointments', array_keys(\Illuminate\Support\Facades\Artisan::all()));
    }

    public function test_primary_person_created_dispatches_patient_registered_once(): void
    {
        Event::fake([PatientRegistered::class]);

        $person = $this->makePerson(['id' => 'person-1', 'is_primary' => true]);

        (new PersonsObserver)->created($person);

        Event::assertDispatchedTimes(PatientRegistered::class, 1);
        Event::assertDispatched(PatientRegistered::class, function (PatientRegistered $event) {
            return $event->patient->id === 'person-1'
                && $event->triggerType() === 'patientRegistered';
        });
    }

    public function test_dependent_person_created_does_not_dispatch_patient_registered(): void
    {
        Event::fake([PatientRegistered::class]);

        (new PersonsObserver)->created($this->makePerson([
            'id' => 'person-dep',
            'is_primary' => false,
            'relationship' => 'child',
        ]));

        Event::assertNotDispatched(PatientRegistered::class);
    }

    public function test_primary_person_created_twice_does_not_duplicate_patient_registered(): void
    {
        Event::fake([PatientRegistered::class]);

        $person = $this->makePerson(['id' => 'person-dup', 'is_primary' => true]);
        $observer = new PersonsObserver;
        $observer->created($person);
        $observer->created($person);

        Event::assertDispatchedTimes(PatientRegistered::class, 1);
    }

    public function test_attaching_hip_user_to_primary_person_dispatches_patient_registered(): void
    {
        Event::fake([PatientRegistered::class]);

        $person = $this->makePerson([
            'id' => 'person-link',
            'is_primary' => true,
            'hip_user_id' => null,
        ]);
        $person->hip_user_id = 'user-1';
        $person->syncChanges();

        (new PersonsObserver)->updated($person);

        Event::assertDispatchedTimes(PatientRegistered::class, 1);
    }

    public function test_unrelated_person_update_does_not_dispatch_patient_registered(): void
    {
        Event::fake([PatientRegistered::class]);

        $person = $this->makePerson([
            'id' => 'person-upd',
            'is_primary' => true,
            'first_name' => 'Ada',
            'hip_user_id' => 'user-1',
        ]);
        $person->first_name = 'Ada Lovelace';
        $person->syncChanges();

        (new PersonsObserver)->updated($person);

        Event::assertNotDispatched(PatientRegistered::class);
    }

    public function test_persons_created_includes_hospital_and_organization_from_loaded_hip_user(): void
    {
        Event::fake([PatientRegistered::class]);

        $user = new HIPUser;
        $user->forceFill([
            'organization_id' => 2,
            'hospital_id' => 12,
        ]);

        $person = $this->makePerson(['id' => 'person-2', 'is_primary' => true]);
        $person->setRelation('hipUser', $user);

        (new PersonsObserver)->created($person);

        Event::assertDispatched(PatientRegistered::class, function (PatientRegistered $event) {
            return $event->organizationId === 2
                && (int) $event->hospitalId === 12
                && $event->payload()['hospital_id'] == 12;
        });
    }

    public function test_generic_listener_receives_patient_registered_payload(): void
    {
        $person = $this->makePerson(['id' => 'person-listen', 'is_primary' => true]);
        $event = new PatientRegistered($person, 2, 12);

        $trigger = Mockery::mock(HospitalAutomationTriggerService::class);
        $trigger->shouldReceive('dispatch')
            ->once()
            ->with('patientRegistered', Mockery::on(function (array $payload) use ($person) {
                return $payload['patient'] === $person
                    && $payload['organization_id'] === 2
                    && $payload['hospital_id'] === 12;
            }));

        (new DispatchHospitalAutomationWorkflow($trigger))->handle($event);
    }

    public function test_confirmed_booking_status_values_do_not_dispatch_appointment_missed(): void
    {
        Event::fake([AppointmentMissed::class, AppointmentBooked::class]);

        $observer = new DoctorBookingObserver;

        $observer->updated($this->bookingAfterStatusChange(
            DoctorBooking::STATUS_CONFIRMED,
            DoctorBooking::STATUS_COMPLETED
        ));
        $observer->updated($this->bookingAfterStatusChange(
            DoctorBooking::STATUS_CONFIRMED,
            DoctorBooking::STATUS_CANCELLED
        ));
        $observer->updated($this->bookingAfterStatusChange(
            DoctorBooking::STATUS_PENDING,
            DoctorBooking::STATUS_CONFIRMED,
            ['id' => 77]
        ));

        $noShow = $this->makeBooking([
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'appointment_status' => DoctorBooking::APPOINTMENT_STATUS_NEW,
        ]);
        $noShow->appointment_status = 'no_show';
        $noShow->syncChanges();
        $observer->updated($noShow);

        Event::assertNotDispatched(AppointmentMissed::class);
        Event::assertDispatchedTimes(AppointmentBooked::class, 1);
    }

    public function test_unrelated_confirmed_update_does_not_dispatch_booked_or_missed(): void
    {
        Event::fake([AppointmentMissed::class, AppointmentBooked::class]);

        $booking = $this->makeBooking([
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'doctor_id' => 1,
        ]);
        $booking->doctor_id = 9;
        $booking->syncChanges();

        (new DoctorBookingObserver)->updated($booking);

        Event::assertNotDispatched(AppointmentMissed::class);
        Event::assertNotDispatched(AppointmentBooked::class);
    }

    public function test_generic_listener_still_handles_appointment_missed_when_dispatched(): void
    {
        $booking = $this->makeBooking(['id' => 92, 'status' => DoctorBooking::STATUS_CONFIRMED, 'hospital_id' => 12]);
        $event = new AppointmentMissed($booking);

        $trigger = Mockery::mock(HospitalAutomationTriggerService::class);
        $trigger->shouldReceive('dispatch')
            ->once()
            ->with('appointmentMissed', Mockery::on(function (array $payload) use ($booking) {
                return $payload['appointment'] === $booking;
            }));

        (new DispatchHospitalAutomationWorkflow($trigger))->handle($event);
    }

    public function test_scheduled_event_payload_has_last_visit_without_appointment_model(): void
    {
        $person = $this->makePerson(['id' => 'person-inactive']);
        $event = new ScheduledEvent($person, [
            'hospital_id' => 12,
            'last_visit' => 30,
            'last_visit_days' => 30,
        ]);

        $this->assertSame('scheduledEvent', $event->triggerType());
        $this->assertSame(12, $event->payload()['hospital_id']);
        $this->assertSame(30, $event->payload()['last_visit']);
        $this->assertArrayNotHasKey('appointment', $event->payload());
    }

    public function test_birthday_and_anniversary_payloads_include_hospital_isolation_fields(): void
    {
        $person = $this->makePerson(['id' => 'person-iso']);

        $birthday = new BirthdayReached($person, 2, 12);
        $this->assertSame(2, $birthday->payload()['organization_id']);
        $this->assertSame(12, $birthday->payload()['hospital_id']);

        $anniversary = new AnniversaryReached($person, 'womens_day', 2, 12);
        $this->assertSame('womens_day', $anniversary->payload()['anniversaryType']);
        $this->assertSame(12, $anniversary->payload()['hospital_id']);
    }

    public function test_generic_listener_receives_scheduled_and_birthday_payloads(): void
    {
        $person = $this->makePerson(['id' => 'person-sched']);

        $trigger = Mockery::mock(HospitalAutomationTriggerService::class);
        $trigger->shouldReceive('dispatch')->once()->with('birthday', Mockery::type('array'));
        $trigger->shouldReceive('dispatch')->once()->with('anniversaryReached', Mockery::on(function (array $payload) {
            return $payload['anniversaryType'] === 'womens_day';
        }));
        $trigger->shouldReceive('dispatch')->once()->with('scheduledEvent', Mockery::type('array'));

        $listener = new DispatchHospitalAutomationWorkflow($trigger);
        $listener->handle(new BirthdayReached($person, 2, 12));
        $listener->handle(new AnniversaryReached($person, 'womens_day', 2, 12));
        $listener->handle(new ScheduledEvent($person, ['last_visit' => 90, 'hospital_id' => 12]));
    }

    public function test_event_dispatch_guard_claims_only_once(): void
    {
        $expires = now()->addHour();

        $this->assertTrue(EventDispatchGuard::claim('birthday:p1:2026', $expires));
        $this->assertFalse(EventDispatchGuard::claim('birthday:p1:2026', $expires));
        $this->assertTrue(EventDispatchGuard::claim('birthday:p2:2026', $expires));
    }

    public function test_womens_day_command_skips_non_march_eighth(): void
    {
        $this->artisan('hospital-automation:dispatch-anniversaries', [
            '--date' => '2026-09-22',
            '--type' => 'womens_day',
        ])->expectsOutputToContain('Skipping')->assertSuccessful();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makePerson(array $attributes = []): Persons
    {
        $person = new Persons;
        $person->forceFill(array_merge([
            'id' => 'person-default',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'is_primary' => true,
        ], $attributes));
        $person->syncOriginal();

        return $person;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makeBooking(array $attributes = []): DoctorBooking
    {
        $booking = new DoctorBooking;
        $booking->forceFill(array_merge([
            'id' => 41,
            'hospital_id' => 5,
            'doctor_id' => 1,
            'status' => DoctorBooking::STATUS_PENDING,
        ], $attributes));
        $booking->syncOriginal();

        return $booking;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function bookingAfterStatusChange(string $from, string $to, array $attributes = []): DoctorBooking
    {
        $booking = $this->makeBooking(array_merge($attributes, ['status' => $from]));
        $booking->status = $to;
        $booking->syncChanges();

        return $booking;
    }
}
