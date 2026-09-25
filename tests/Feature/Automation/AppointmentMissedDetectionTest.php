<?php

namespace Tests\Feature\Automation;

use App\Models\DoctorBooking;
use App\Modules\Automation\Events\AppointmentBooked;
use App\Modules\Automation\Events\AppointmentMissed;
use App\Modules\Automation\Services\MissedAppointmentDetector;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AppointmentMissedDetectionTest extends TestCase
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
                'database/migrations/2026_02_14_173508_create_user_devices_table.php',
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureDoctorBookingsTable();
        Carbon::setTestNow(Carbon::parse('2026-09-23 12:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_past_confirmed_appointment_is_marked_missed_and_dispatches_once(): void
    {
        Event::fake([AppointmentMissed::class, AppointmentBooked::class]);

        $booking = $this->createBooking([
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'booking_date' => '2026-09-23',
            'required_time_slots' => ['10:00 AM'],
            'hospital_id' => 17,
        ]);

        $this->detector()->detectAndDispatch();

        $booking->refresh();
        $this->assertTrue($booking->isMissed());
        $this->assertSame(DoctorBooking::STATUS_MISSED, $booking->status);
        Event::assertDispatchedTimes(AppointmentMissed::class, 1);
        Event::assertDispatched(AppointmentMissed::class, function (AppointmentMissed $event) use ($booking) {
            return $event->appointment->id === $booking->id
                && (int) $event->appointment->hospital_id === 17
                && $event->triggerType() === 'appointmentMissed';
        });
        Event::assertNotDispatched(AppointmentBooked::class);
    }

    public function test_future_appointment_is_untouched(): void
    {
        Event::fake([AppointmentMissed::class]);

        $booking = $this->createBooking([
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'booking_date' => '2026-09-23',
            'required_time_slots' => ['03:00 PM'],
        ]);

        $this->detector()->detectAndDispatch();

        $this->assertSame(DoctorBooking::STATUS_CONFIRMED, $booking->fresh()->status);
        Event::assertNotDispatched(AppointmentMissed::class);
    }

    public function test_completed_appointment_is_not_missed(): void
    {
        Event::fake([AppointmentMissed::class]);

        $booking = $this->createBooking([
            'status' => DoctorBooking::STATUS_COMPLETED,
            'appointment_status' => DoctorBooking::APPOINTMENT_STATUS_COMPLETED,
            'booking_date' => '2026-09-22',
            'required_time_slots' => ['10:00 AM'],
        ]);

        $this->detector()->detectAndDispatch();

        $this->assertSame(DoctorBooking::STATUS_COMPLETED, $booking->fresh()->status);
        Event::assertNotDispatched(AppointmentMissed::class);
    }

    public function test_cancelled_appointment_is_not_missed(): void
    {
        Event::fake([AppointmentMissed::class]);

        $booking = $this->createBooking([
            'status' => DoctorBooking::STATUS_CANCELLED,
            'appointment_status' => DoctorBooking::APPOINTMENT_STATUS_CANCELLED,
            'booking_date' => '2026-09-22',
            'required_time_slots' => ['10:00 AM'],
        ]);

        $this->detector()->detectAndDispatch();

        $this->assertSame(DoctorBooking::STATUS_CANCELLED, $booking->fresh()->status);
        Event::assertNotDispatched(AppointmentMissed::class);
    }

    public function test_checked_in_appointment_is_not_missed(): void
    {
        Event::fake([AppointmentMissed::class]);

        $booking = $this->createBooking([
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'appointment_status' => DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN,
            'booking_date' => '2026-09-22',
            'required_time_slots' => ['10:00 AM'],
        ]);

        $this->detector()->detectAndDispatch();

        $this->assertSame(DoctorBooking::STATUS_CONFIRMED, $booking->fresh()->status);
        $this->assertSame(DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN, $booking->fresh()->appointment_status);
        Event::assertNotDispatched(AppointmentMissed::class);
    }

    public function test_already_missed_is_idempotent(): void
    {
        Event::fake([AppointmentMissed::class, AppointmentBooked::class]);

        $booking = $this->createBooking([
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'booking_date' => '2026-09-22',
            'required_time_slots' => ['09:00 AM'],
            'hospital_id' => 4,
        ]);

        $this->detector()->detectAndDispatch();
        $this->detector()->detectAndDispatch();

        $this->assertSame(DoctorBooking::STATUS_MISSED, $booking->fresh()->status);
        Event::assertDispatchedTimes(AppointmentMissed::class, 1);
        Event::assertNotDispatched(AppointmentBooked::class);
    }

    public function test_event_payload_uses_the_processed_booking_hospital(): void
    {
        Event::fake([AppointmentMissed::class]);

        $this->createBooking([
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'booking_date' => '2026-09-22',
            'required_time_slots' => ['08:00 AM'],
            'hospital_id' => 21,
        ]);
        $this->createBooking([
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'booking_date' => '2026-09-22',
            'required_time_slots' => ['08:00 AM'],
            'hospital_id' => 22,
        ]);

        $this->detector()->detectAndDispatch();

        Event::assertDispatchedTimes(AppointmentMissed::class, 2);
        $hospitalIds = [];
        Event::assertDispatched(AppointmentMissed::class, function (AppointmentMissed $event) use (&$hospitalIds) {
            $hospitalIds[] = (int) $event->appointment->hospital_id;

            return (int) $event->appointment->hospital_id === (int) $event->payload()['appointment']->hospital_id;
        });
        $this->assertEqualsCanonicalizing([21, 22], $hospitalIds);
    }

    public function test_malformed_schedule_is_skipped_without_stopping_the_batch(): void
    {
        Event::fake([AppointmentMissed::class]);

        $invalidDate = $this->createBooking([
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'booking_date' => null,
            'required_time_slots' => ['10:00 AM'],
        ]);
        $emptySlots = $this->createBooking([
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'booking_date' => '2026-09-22',
            'required_time_slots' => [],
        ]);
        $badTime = $this->createBooking([
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'booking_date' => '2026-09-22',
            'required_time_slots' => ['not-a-time'],
        ]);
        $valid = $this->createBooking([
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'booking_date' => '2026-09-22',
            'required_time_slots' => ['09:30 AM'],
        ]);

        $this->detector()->detectAndDispatch();

        $this->assertSame(DoctorBooking::STATUS_CONFIRMED, $invalidDate->fresh()->status);
        $this->assertSame(DoctorBooking::STATUS_CONFIRMED, $emptySlots->fresh()->status);
        $this->assertSame(DoctorBooking::STATUS_CONFIRMED, $badTime->fresh()->status);
        $this->assertSame(DoctorBooking::STATUS_MISSED, $valid->fresh()->status);
        Event::assertDispatchedTimes(AppointmentMissed::class, 1);
    }

    public function test_marking_missed_does_not_dispatch_appointment_booked(): void
    {
        Event::fake([AppointmentMissed::class, AppointmentBooked::class]);

        $booking = $this->createBooking([
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'booking_date' => '2026-09-21',
            'required_time_slots' => ['11:00 AM'],
        ]);

        $this->artisan('hospital-automation:dispatch-missed-appointments')
            ->assertSuccessful();

        $this->assertSame(DoctorBooking::STATUS_MISSED, $booking->fresh()->status);
        Event::assertDispatchedTimes(AppointmentMissed::class, 1);
        Event::assertNotDispatched(AppointmentBooked::class);
    }

    public function test_pending_past_appointment_is_not_missed(): void
    {
        Event::fake([AppointmentMissed::class]);

        $booking = $this->createBooking([
            'status' => DoctorBooking::STATUS_PENDING,
            'booking_date' => '2026-09-22',
            'required_time_slots' => ['10:00 AM'],
        ]);

        $this->detector()->detectAndDispatch();

        $this->assertSame(DoctorBooking::STATUS_PENDING, $booking->fresh()->status);
        Event::assertNotDispatched(AppointmentMissed::class);
    }

    protected function detector(): MissedAppointmentDetector
    {
        return app(MissedAppointmentDetector::class);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createBooking(array $attributes): DoctorBooking
    {
        $booking = new DoctorBooking;
        $booking->forceFill(array_merge([
            'name' => 'Test Patient',
            'hospital_id' => 5,
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'appointment_status' => DoctorBooking::APPOINTMENT_STATUS_NEW,
        ], $attributes));
        DoctorBooking::withoutEvents(fn () => $booking->save());

        return $booking;
    }

    protected function ensureDoctorBookingsTable(): void
    {
        if (Schema::hasTable('doctor_bookings')) {
            return;
        }

        Schema::create('doctor_bookings', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->string('doctor_id')->nullable();
            $table->string('patient_id')->nullable();
            $table->date('booking_date')->nullable();
            $table->json('required_time_slots')->nullable();
            $table->string('status')->nullable();
            $table->string('appointment_status')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
    }
}
