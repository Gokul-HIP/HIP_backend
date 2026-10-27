<?php

namespace Tests\Feature\Pharmacy;

use App\Enums\PharmacyOrderStatus;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\Coins;
use App\Models\DoctorBooking;
use App\Models\Document;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\Pharmacy;
use App\Models\PharmacyOrder;
use App\Models\RazorpayPayment;
use App\Models\Setting;
use App\Models\Transactions;
use App\Modules\Automation\Events\AppointmentBooked;
use App\Modules\Automation\Services\InvoiceGeneratedDispatcher;
use App\Modules\Automation\Services\PaymentReceivedDispatcher;
use App\Services\Api\PaymentApiService;
use App\Services\NotificationService;
use App\Services\Pharmacy\PharmacyBillCalculator;
use App\Services\Pharmacy\PharmacyOrderService;
use App\Services\RewardTierService;
use Razorpay\Api\Api;
use ReflectionProperty;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PharmacyOrderApiTest extends TestCase
{
    private static bool $schemaReady = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->fail('Pharmacy API tests must use the sqlite testing database.');
        }

        $this->bootSchema();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        if (! $this->isSafeTestDatabase()) {
            return;
        }

        if (DB::getDriverName() !== 'sqlite' || DB::getDatabaseName() !== ':memory:') {
            parent::tearDown();

            return;
        }

        if (Schema::hasTable('documents')) {
            foreach (Document::query()->pluck('document_path') as $path) {
                if (is_string($path) && $path !== '') {
                    Storage::disk('local')->delete($path);
                }
            }

            foreach ([
                'pharmacy_order_status_logs',
                'pharmacy_order_comments',
                'pharmacy_order_items',
                'pharmacy_orders',
                'documents',
                'transactions',
                'razorpay_payments',
                'invoices',
                'coins',
                'persons',
                'model_has_roles',
                'healthinpocket_users',
                'pharmacies',
                'hospitals',
            ] as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }
        }

        parent::tearDown();
    }

    public function test_submit_stores_a_private_prescription_and_the_first_comment(): void
    {
        [$hospital, $pharmacy] = $this->place();
        $user = $this->member('Ada', 'HIP91001');
        $person = $this->person($user, 'Ada', 'One', true);

        Sanctum::actingAs($user);

        $response = $this->post('/api/pharmacy/orders', [
            'hospital_id' => $hospital->id,
            'note' => 'Morning delivery',
            'delivery_name' => 'Ada One',
            'delivery_phone' => '9000000001',
            'delivery_address' => '12 Lake Road',
            'prescription' => UploadedFile::fake()->image('rx.jpg'),
        ], ['Accept' => 'application/json']);

        $response->assertCreated();
        $orderId = (int) $response->json('data.id');
        $order = PharmacyOrder::query()->with(['comments', 'statusLogs', 'prescriptionDocument'])->findOrFail($orderId);
        $document = $order->prescriptionDocument;

        $this->assertNotNull($document);
        $this->assertNull($document->member_id);
        $this->assertNull($document->patient_id);
        $this->assertSame($user->id, $document->created_by);
        $this->assertSame(PharmacyOrder::PRESCRIPTION_DOC_TYPE, $document->document_type);
        $this->assertSame($person->id, $order->person_id);
        $this->assertSame($user->id, $response->json('data.patient_user_id'));
        $this->assertSame((int) $pharmacy->id, (int) $order->pharmacy_id);
        $this->assertSame('requested', $order->status->value);
        $this->assertSame('unpaid', $order->payment_status->value);
        $this->assertTrue(Storage::disk('local')->exists($document->document_path));
        $this->assertFalse(Storage::disk('public')->exists($document->document_path));
        $this->assertSame('Morning delivery', $order->comments->first()->body);
        $this->assertSame('patient', $order->comments->first()->author_type);
        $this->assertNull($order->statusLogs->first()->from_status);
        $this->assertSame('requested', $order->statusLogs->first()->to_status);
    }

    public function test_submit_allows_a_dependent_and_rejects_another_family(): void
    {
        [$hospital] = $this->place();
        $user = $this->member('Ada', 'HIP91002');
        $primary = $this->person($user, 'Ada', 'One', true);
        $dependent = $this->person($user, 'Kid', 'One', false, $primary->id);
        $other = $this->member('Bea', 'HIP91003');
        $otherPerson = $this->person($other, 'Bea', 'Two', true);

        Sanctum::actingAs($user);

        $this->post('/api/pharmacy/orders', $this->payload($hospital->id, [
            'patient_id' => $dependent->id,
        ]), ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonPath('data.person_id', $dependent->id);

        $this->post('/api/pharmacy/orders', $this->payload($hospital->id, [
            'patient_id' => $otherPerson->id,
        ]), ['Accept' => 'application/json'])->assertForbidden()
            // ->assertJsonPath('message', 'You can only order for yourself or your dependents.');
            ->assertJsonPath('message', 'Patient not found in your family');
    }

    public function test_submit_rejects_a_file_that_is_not_a_real_prescription_mime(): void
    {
        [$hospital] = $this->place();
        $user = $this->member('Ada', 'HIP91004');
        $this->person($user, 'Ada', 'One', true);
        Sanctum::actingAs($user);

        $this->post('/api/pharmacy/orders', $this->payload($hospital->id, [
            'prescription' => UploadedFile::fake()->createWithContent('rx.pdf', 'not a real pdf'),
        ]), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['prescription']);
    }

    public function test_submit_rejects_a_file_over_10_mb(): void
    {
        [$hospital] = $this->place();
        $user = $this->member('Ada', 'HIP91005');
        $this->person($user, 'Ada', 'One', true);
        Sanctum::actingAs($user);

        $this->post('/api/pharmacy/orders', $this->payload($hospital->id, [
            'prescription' => UploadedFile::fake()->create('big.jpg', 10241, 'image/jpeg'),
        ]), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['prescription']);
    }

    public function test_submit_rejects_a_pharmacy_that_is_not_assigned_to_the_hospital(): void
    {
        [$hospital, $pharmacy] = $this->place();
        $other = Pharmacy::withoutEvents(function () {
            $other = new Pharmacy();
            $other->stopUserstamping();
            $other->forceFill([
                'name' => 'Other',
                'pharmacy_id' => 'PH-OTHER',
                'address' => 'Elsewhere',
                'status' => 'active',
            ])->save();

            return $other;
        });

        $user = $this->member('Ada', 'HIP91006');
        $this->person($user, 'Ada', 'One', true);
        Sanctum::actingAs($user);

        $this->post('/api/pharmacy/orders', $this->payload($hospital->id, [
            'pharmacy_id' => $other->id,
        ]), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['pharmacy_id']);

        $this->assertSame((int) $pharmacy->id, (int) $pharmacy->id);
    }

    public function test_list_shows_own_and_family_orders_only_and_hides_other_families(): void
    {
        [$hospital] = $this->place();
        $user = $this->member('Ada', 'HIP91007');
        $primary = $this->person($user, 'Ada', 'One', true);
        $dependent = $this->person($user, 'Kid', 'One', false, $primary->id);
        $other = $this->member('Bea', 'HIP91008');
        $this->person($other, 'Bea', 'Two', true);

        Sanctum::actingAs($user);
        $own = $this->post('/api/pharmacy/orders', $this->payload($hospital->id), ['Accept' => 'application/json']);
        $own->assertCreated();
        $kid = $this->post('/api/pharmacy/orders', $this->payload($hospital->id, [
            'patient_id' => $dependent->id,
        ]), ['Accept' => 'application/json']);
        $kid->assertCreated();

        Sanctum::actingAs($other);
        $foreign = $this->post('/api/pharmacy/orders', $this->payload($hospital->id), ['Accept' => 'application/json']);
        $foreign->assertCreated();

        Sanctum::actingAs($user);
        $list = $this->getJson('/api/pharmacy/orders');
        $list->assertOk();
        $numbers = collect($list->json('data'))->pluck('order_number')->all();
        $this->assertContains($own->json('data.order_number'), $numbers);
        $this->assertContains($kid->json('data.order_number'), $numbers);
        $this->assertNotContains($foreign->json('data.order_number'), $numbers);

        $this->getJson('/api/pharmacy/orders/'.$foreign->json('data.id'))
            ->assertNotFound()
            ->assertJsonPath('message', 'Order not found.');
    }

    public function test_omitted_pharmacy_id_uses_the_first_active_pharmacy(): void
    {
        [$hospital, $first] = $this->place();
        $second = $this->pharmacy('Second Pharmacy', 'active');
        $closed = $this->pharmacy('Closed Pharmacy', 'inactive');
        $user = $this->member('Ada', 'HIP91020');
        $this->person($user, 'Ada', 'One', true, null, '9000000020', '20 First Road');
        Sanctum::actingAs($user);

        $this->assignPharmacies($hospital, [(int) $closed->id, (int) $second->id, (int) $first->id]);

        $lowestActiveId = min((int) $first->id, (int) $second->id);

        $this->post('/api/pharmacy/orders', $this->payload($hospital->id, [
            'delivery_name' => null,
            'delivery_phone' => null,
            'delivery_address' => null,
        ]), ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.pharmacy_id', $lowestActiveId)
            ->assertJsonPath('data.pharmacy.id', $lowestActiveId);

        $this->assignPharmacies($hospital, [(int) $second->id, (int) $first->id]);

        $this->post('/api/pharmacy/orders', $this->payload($hospital->id), ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.pharmacy_id', $lowestActiveId);

        $this->post('/api/pharmacy/orders', $this->payload($hospital->id, [
            'pharmacy_id' => (int) $second->id,
        ]), ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.pharmacy_id', (int) $second->id)
            ->assertJsonPath('data.pharmacy.name', 'Second Pharmacy');
    }

    public function test_submit_rejects_a_hospital_with_no_active_pharmacy(): void
    {
        [$hospital, $pharmacy] = $this->place();
        Pharmacy::withoutEvents(fn () => $pharmacy->forceFill(['status' => 'inactive'])->save());
        $user = $this->member('Ada', 'HIP91021');
        $this->person($user, 'Ada', 'One', true, null, '9000000021', '21 Quiet Road');
        Sanctum::actingAs($user);

        $this->post('/api/pharmacy/orders', $this->payload($hospital->id), ['Accept' => 'application/json'])
            ->assertStatus(422)
            // ->assertJsonPath('errors.pharmacy_id.0', 'This hospital has no active pharmacy.');
            ->assertJsonPath('errors.pharmacy_id.0', 'No pharmacy is linked to this hospital.');

        $this->assignPharmacies($hospital, []);

        $this->post('/api/pharmacy/orders', $this->payload($hospital->id), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('errors.pharmacy_id.0', 'No pharmacy is linked to this hospital.');
    }

    public function test_patient_id_accepts_a_person_id_or_a_linked_user_id(): void
    {
        [$hospital] = $this->place();
        $user = $this->member('Ada', 'HIP91022');
        $primary = $this->person($user, 'Ada', 'One', true, null, '9000000022', '22 Primary Road');
        $dependentUser = $this->member('Kid', 'HIP91023');
        $dependent = $this->person($user, 'Kid', 'One', false, $primary->id, '9000000023', '23 Kid Road', $dependentUser);
        $other = $this->member('Bea', 'HIP91024');
        $this->person($other, 'Bea', 'Two', true, null, '9000000024', '24 Other Road');

        Sanctum::actingAs($user);

        $this->post('/api/pharmacy/orders', $this->payload($hospital->id, [
            'patient_id' => $dependent->id,
        ]), ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.person_id', $dependent->id)
            ->assertJsonPath('data.patient_user_id', $dependentUser->id);

        $this->post('/api/pharmacy/orders', $this->payload($hospital->id, [
            'patient_id' => $user->id,
        ]), ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.person_id', $primary->id)
            ->assertJsonPath('data.patient_user_id', $user->id);

        // A dependent's own user id is not the caller's id and is not a person id in the family.
        // $this->post('/api/pharmacy/orders', $this->payload($hospital->id, [
        //     'patient_id' => $dependentUser->id,
        // ]), ['Accept' => 'application/json'])
        //     ->assertCreated()
        //     ->assertJsonPath('data.person_id', $dependent->id)
        //     ->assertJsonPath('data.patient_user_id', $dependentUser->id);
        $this->post('/api/pharmacy/orders', $this->payload($hospital->id, [
            'patient_id' => $dependentUser->id,
        ]), ['Accept' => 'application/json'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Patient not found in your family');

        $this->post('/api/pharmacy/orders', $this->payload($hospital->id), ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.person_id', $primary->id)
            ->assertJsonPath('data.patient.id', $primary->id)
            ->assertJsonPath('data.patient.hip_id', 'HIP91022');

        $this->post('/api/pharmacy/orders', $this->payload($hospital->id, [
            'patient_id' => $other->id,
        ]), ['Accept' => 'application/json'])
            ->assertForbidden()
            // ->assertJsonPath('message', 'You can only order for yourself or your dependents.');
            ->assertJsonPath('message', 'Patient not found in your family');
    }

    public function test_delivery_details_are_copied_from_the_person_unless_overridden(): void
    {
        [$hospital] = $this->place();
        $user = $this->member('Ada', 'HIP91025');
        $this->person($user, 'Ada', 'One', true, null, '9000000025', '25 Profile Road');
        Sanctum::actingAs($user);

        $this->post('/api/pharmacy/orders', [
            'hospital_id' => $hospital->id,
            'prescription' => UploadedFile::fake()->image('rx.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.delivery_name', 'Ada One')
            ->assertJsonPath('data.delivery_phone', '9000000025')
            ->assertJsonPath('data.delivery_address', '25 Profile Road');

        $this->post('/api/pharmacy/orders', $this->payload($hospital->id, [
            'delivery_name' => null,
            'delivery_phone' => null,
            'delivery_address' => 'Override Lane',
        ]), ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.delivery_name', 'Ada One')
            ->assertJsonPath('data.delivery_phone', '9000000025')
            ->assertJsonPath('data.delivery_address', 'Override Lane');
    }

    public function test_dependent_without_contact_details_uses_the_primary_phone_and_requires_an_address(): void
    {
        [$hospital] = $this->place();
        $user = $this->member('Ada', 'HIP91026');
        $primary = $this->person($user, 'Ada', 'One', true, null, '9000000026', '26 Primary Road');
        $dependent = $this->person($user, 'Kid', 'One', false, $primary->id);
        Sanctum::actingAs($user);

        $this->post('/api/pharmacy/orders', $this->payload($hospital->id, [
            'patient_id' => $dependent->id,
            'delivery_name' => null,
            'delivery_phone' => null,
            'delivery_address' => null,
        ]), ['Accept' => 'application/json'])
            // ->assertStatus(422)
            // ->assertJsonPath('errors.delivery_address.0', 'Please add a delivery address to this patient\'s profile or send delivery_address.');
            ->assertCreated()
            ->assertJsonPath('data.person_id', $dependent->id)
            ->assertJsonPath('data.delivery_name', 'Kid One')
            ->assertJsonPath('data.delivery_phone', '9000000026')
            ->assertJsonPath('data.delivery.address', null)
            ->assertJsonPath('data.delivery.address_missing', true);

        $this->post('/api/pharmacy/orders', $this->payload($hospital->id, [
            'patient_id' => $dependent->id,
            'delivery_name' => null,
            'delivery_phone' => null,
            'delivery_address' => 'Kid drop point',
        ]), ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.person_id', $dependent->id)
            ->assertJsonPath('data.delivery_name', 'Kid One')
            ->assertJsonPath('data.delivery_phone', '9000000026')
            ->assertJsonPath('data.delivery_address', 'Kid drop point');
    }

    public function test_account_address_fills_delivery_when_the_person_has_none(): void
    {
        [$hospital] = $this->place();
        $user = $this->member('Ada', 'HIP91028');
        HIPUser::withoutEvents(fn () => $user->forceFill([
            'house_number' => '12',
            'street' => 'Lake Road',
            'city' => 'Chennai',
            'state' => 'Tamil Nadu',
            'zip_code' => '600001',
        ])->save());
        $this->person($user, 'Ada', 'One', true, null, '9000000028');
        Sanctum::actingAs($user);

        $this->post('/api/pharmacy/orders', [
            'hospital_id' => $hospital->id,
            'prescription' => UploadedFile::fake()->image('rx.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.delivery_address', '12, Lake Road, Chennai, Tamil Nadu, 600001');
    }

    public function test_missing_delivery_details_name_the_empty_field(): void
    {
        [$hospital] = $this->place();
        $user = $this->member('Ada', 'HIP91027');
        $this->person($user, 'Ada', 'One', true);
        Sanctum::actingAs($user);

        $this->post('/api/pharmacy/orders', [
            'hospital_id' => $hospital->id,
            'prescription' => UploadedFile::fake()->image('rx.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            // ->assertJsonPath('errors.delivery_phone.0', 'Please add a delivery phone to this patient\'s profile or send delivery_phone.')
            // ->assertJsonPath('errors.delivery_address.0', 'Please add a delivery address to this patient\'s profile or send delivery_address.');
            ->assertJsonPath('errors.delivery_phone.0', 'No phone number on file for this patient')
            ->assertJsonMissingValidationErrors(['delivery_address']);
    }

    public function test_phone_falls_back_to_the_account_mobile_when_the_person_has_none(): void
    {
        [$hospital] = $this->place();
        $user = $this->member('Ada', 'HIP91031');
        HIPUser::withoutEvents(fn () => $user->forceFill(['mobile_num' => '9811111111'])->save());
        $this->person($user, 'Ada', 'One', true);
        Sanctum::actingAs($user);

        $this->post('/api/pharmacy/orders', [
            'hospital_id' => $hospital->id,
            'prescription' => UploadedFile::fake()->image('rx.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.delivery_phone', '9811111111')
            ->assertJsonPath('data.delivery.phone', '9811111111')
            ->assertJsonPath('data.delivery.address', null)
            ->assertJsonPath('data.delivery.address_missing', true);
    }

    public function test_colliding_user_id_resolves_to_the_callers_primary_person_not_the_orphan(): void
    {
        [$hospital] = $this->place();
        $userId = '019ebfd5-bbfe-728a-a50e-8235b6f72d32';
        $primaryId = '019ebfd5-bc0d-7020-bd31-9407f49ccdbe';
        $user = HIPUser::withoutEvents(function () use ($userId) {
            $user = new HIPUser();
            $user->forceFill([
                'id' => $userId,
                'email' => 'hip00017@example.com',
                'hip_id' => 'HIP00017',
                'mobile_num' => '9747816550',
                'is_active' => true,
                'password' => null,
            ])->save();

            return $user->fresh();
        });
        Persons::withoutEvents(function () use ($userId) {
            $person = new Persons();
            $person->forceFill([
                'id' => $userId,
                'first_name' => 'Gokul',
                'last_name' => 's',
                'mobile' => '6381987776',
                'hip_user_id' => null,
                'parent_id' => $userId,
                'is_primary' => true,
                'is_active' => true,
            ])->save();
        });
        Persons::withoutEvents(function () use ($primaryId, $userId) {
            $person = new Persons();
            $person->forceFill([
                'id' => $primaryId,
                'first_name' => 'Primary',
                'last_name' => 'Account',
                'mobile' => '9747816550',
                'hip_user_id' => $userId,
                'parent_id' => $primaryId,
                'is_primary' => true,
                'is_active' => true,
            ])->save();
        });

        Sanctum::actingAs($user);

        $this->post('/api/pharmacy/orders', $this->payload($hospital->id, [
            'patient_id' => $userId,
        ]), ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.person_id', $primaryId)
            ->assertJsonPath('data.patient.id', $primaryId)
            ->assertJsonPath('data.patient.name', 'Primary Account')
            ->assertJsonPath('data.patient.hip_id', 'HIP00017');

        $other = $this->member('Bea', 'HIP91033');
        $this->person($other, 'Bea', 'Two', true, null, '9000000033', '33 Other Road');
        Sanctum::actingAs($other);

        $this->post('/api/pharmacy/orders', $this->payload($hospital->id, [
            'patient_id' => $userId,
        ]), ['Accept' => 'application/json'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Patient not found in your family');
    }

    public function test_detail_exposes_items_payment_delivery_steps_and_a_signed_prescription_url(): void
    {
        [$hospital, $pharmacy] = $this->place();
        $user = $this->member('Ada', 'HIP91032');
        $person = $this->person($user, 'Ada', 'One', true, null, '9000000032', '32 Shape Road');
        Sanctum::actingAs($user);

        $created = $this->post('/api/pharmacy/orders', [
            'hospital_id' => $hospital->id,
            'note' => 'Morning delivery',
            'prescription' => UploadedFile::fake()->image('rx.jpg'),
        ], ['Accept' => 'application/json']);
        $created->assertCreated()
            ->assertJsonPath('data.patient.id', $person->id)
            ->assertJsonPath('data.patient.hip_id', 'HIP91032')
            ->assertJsonPath('data.pharmacy.id', (int) $pharmacy->id)
            ->assertJsonPath('data.pharmacy.name', 'Nano Pharmacy')
            ->assertJsonPath('data.delivery.address_missing', false)
            ->assertJsonPath('data.payment.status_label', 'unpaid')
            ->assertJsonPath('data.steps.0.key', 'verified')
            ->assertJsonPath('data.steps.0.state', 'pending')
            ->assertJsonPath('data.steps.4.key', 'delivered')
            ->assertJsonPath('data.comments.0.is_mine', true)
            ->assertJsonPath('data.comments.0.author_type', 'patient');
        $this->assertStringContainsString('signature=', (string) $created->json('data.prescription.download_url'));

        $orderId = (int) $created->json('data.id');
        $order = PharmacyOrder::query()->findOrFail($orderId);
        $order->items()->create([
            'name' => 'Paracetamol',
            'strength' => '500 mg',
            'dosage_form' => 'Tablet',
            'dosage' => '1 Tablet',
            'frequency' => 'Once Daily',
            'duration' => '5 Days',
            'when_to_take' => 'After Food',
            'special_instruction' => null,
            'qty' => 2,
            'unit_price' => 500,
            'line_total' => 1000,
            'is_custom' => false,
        ]);
        $order->statusLogs()->create([
            'from_status' => 'requested',
            'to_status' => 'in_review',
            'actor_type' => 'pharmacist',
            'actor_id' => $user->id,
            'created_at' => now(),
        ]);
        $order->forceFill([
            'status' => PharmacyOrderStatus::OutForDelivery->value,
            'payment_status' => 'failed',
        ])->save();

        $detail = $this->getJson('/api/pharmacy/orders/'.$orderId);
        $detail->assertOk()
            ->assertJsonPath('data.items.0.dosage', '1 Tablet')
            ->assertJsonPath('data.items.0.frequency', 'Once Daily')
            ->assertJsonPath('data.items.0.duration', '5 Days')
            ->assertJsonPath('data.items.0.when_to_take', 'After Food')
            ->assertJsonPath('data.items.0.qty', 2)
            ->assertJsonPath('data.items.0.unit_price', 500)
            ->assertJsonPath('data.items.0.line_total', 1000)
            ->assertJsonPath('data.payment.status_label', 'failed')
            ->assertJsonPath('data.payment.retry', true)
            ->assertJsonPath('data.steps.0.state', 'done')
            ->assertJsonPath('data.steps.3.key', 'out_for_delivery')
            ->assertJsonPath('data.steps.3.state', 'active')
            ->assertJsonPath('data.steps.4.state', 'pending');
        $this->assertNotNull($detail->json('data.steps.0.at'));
        $this->assertStringContainsString('signature=', (string) $detail->json('data.prescription.download_url'));

        $order->forceFill(['status' => PharmacyOrderStatus::Cancelled->value])->save();
        $order->statusLogs()->create([
            'from_status' => 'out_for_delivery',
            'to_status' => 'cancelled',
            'actor_type' => 'patient',
            'actor_id' => $user->id,
            'created_at' => now(),
        ]);

        $this->getJson('/api/pharmacy/orders/'.$orderId)
            ->assertOk()
            ->assertJsonCount(1, 'data.steps')
            ->assertJsonPath('data.steps.0.key', 'cancelled')
            ->assertJsonPath('data.steps.0.state', 'done');

        $list = $this->getJson('/api/pharmacy/orders');
        $list->assertOk()
            ->assertJsonPath('data.0.patient.id', $person->id)
            ->assertJsonPath('data.0.patient.hip_id', 'HIP91032')
            ->assertJsonPath('data.0.pharmacy.id', (int) $pharmacy->id)
            ->assertJsonPath('data.0.delivery.address', '32 Shape Road')
            ->assertJsonPath('data.0.delivery.address_missing', false);
    }

    public function test_payment_attempted_at_and_breakdown_identity(): void
    {
        [$hospital] = $this->place();
        $user = $this->member('Ada', 'HIP91040');
        $person = $this->person($user, 'Ada', 'One', true, null, '9000000040', '40 Bill Road');
        Sanctum::actingAs($user);

        $created = $this->post('/api/pharmacy/orders', $this->payload($hospital->id), ['Accept' => 'application/json']);
        $created->assertCreated();
        $orderId = (int) $created->json('data.id');
        $bill = app(PharmacyBillCalculator::class)->calculate(1000, 100, 50);

        PharmacyOrder::query()->whereKey($orderId)->update([
            'subtotal' => $bill['subtotal'],
            'discount' => $bill['discount'],
            'delivery_fee' => $bill['delivery_fee'],
            'tax' => $bill['tax'],
            'service_charge' => $bill['service_charge'],
            'gateway_charge' => $bill['gateway_charge'],
            'total' => $bill['total'],
        ]);

        $unpaid = $this->getJson('/api/pharmacy/orders/'.$orderId);
        $unpaid->assertOk()
            ->assertJsonPath('data.payment.attempted_at', null)
            ->assertJsonPath('data.payment.status_label', 'unpaid');
        $this->assertEquals($bill['subtotal'], $unpaid->json('data.payment.breakdown.subtotal'));
        $this->assertEquals($bill['tax'], $unpaid->json('data.payment.breakdown.gst'));
        $this->assertEquals($bill['total'], $unpaid->json('data.payment.breakdown.total'));
        $this->assertBreakdownIdentity($unpaid->json('data.payment.breakdown'));

        $invoice = Invoice::withoutEvents(function () use ($person, $bill) {
            $invoice = new Invoice();
            $invoice->stopUserstamping();
            $invoice->forceFill([
                'primary_person_id' => $person->id,
                'person_id' => $person->id,
                'status' => 'pending',
                'amount' => round($bill['subtotal'] - $bill['discount'] + $bill['delivery_fee'], 2),
                'discount_price' => 0,
                'total_gst' => $bill['tax'],
                'service_charges' => $bill['service_charge'],
                'payment_gateway_charges' => $bill['gateway_charge'],
                'total_amount' => $bill['total'],
                'payment_method' => 'razorpay',
            ])->save();

            return $invoice;
        });
        PharmacyOrder::query()->whereKey($orderId)->update([
            'invoice_id' => $invoice->id,
            'status' => PharmacyOrderStatus::PendingPayment->value,
            'payment_status' => 'unpaid',
        ]);

        $pending = $this->getJson('/api/pharmacy/orders/'.$orderId);
        $pending->assertOk()
            ->assertJsonPath('data.payment.can_pay', true)
            ->assertJsonPath('data.payment.status_label', 'pending')
            ->assertJsonPath('data.payment.attempted_at', null)
            ->assertJsonPath('data.payment.method', null);
        $this->assertEquals($bill['tax'], $pending->json('data.payment.breakdown.gst'));
        $this->assertEquals($bill['service_charge'], $pending->json('data.payment.breakdown.service_charges'));
        $this->assertEquals($bill['gateway_charge'], $pending->json('data.payment.breakdown.gateway_charges'));
        $this->assertEquals($bill['total'], $pending->json('data.payment.breakdown.total'));
        $this->assertBreakdownIdentity($pending->json('data.payment.breakdown'));

        $attemptedAt = now()->subMinutes(5)->startOfSecond();
        $attempt = new RazorpayPayment();
        $attempt->forceFill([
            'invoice_id' => $invoice->id,
            'payment_method' => 'upi',
            'payment_status' => 'failed',
            'amount_paid' => 0,
            'created_at' => $attemptedAt,
            'updated_at' => $attemptedAt,
        ])->save();
        PharmacyOrder::query()->whereKey($orderId)->update([
            'payment_status' => 'failed',
        ]);

        $failed = $this->getJson('/api/pharmacy/orders/'.$orderId);
        $failed->assertOk()
            ->assertJsonPath('data.payment.retry', true)
            ->assertJsonPath('data.payment.status_label', 'failed')
            ->assertJsonPath('data.payment.attempted_at', $attemptedAt->toIso8601String())
            ->assertJsonPath('data.payment.method', 'upi');

        $paidOn = now()->startOfSecond();
        Invoice::withoutEvents(fn () => $invoice->forceFill(['status' => 'completed', 'payment_method' => 'upi'])->save());
        PharmacyOrder::query()->whereKey($orderId)->update([
            'status' => PharmacyOrderStatus::Processing->value,
            'payment_status' => 'paid',
            'paid_at' => $paidOn,
        ]);

        $paid = $this->getJson('/api/pharmacy/orders/'.$orderId);
        $paid->assertOk()
            ->assertJsonPath('data.payment.status_label', 'paid')
            ->assertJsonPath('data.payment.can_pay', false)
            ->assertJsonPath('data.payment.paid_on', $paidOn->toIso8601String());
        $this->assertNotNull($paid->json('data.payment.invoice_pdf_url'));
        $this->assertStringContainsString('signature=', (string) $paid->json('data.payment.invoice_pdf_url'));
        $this->assertBreakdownIdentity($paid->json('data.payment.breakdown'));

        $attempt->forceFill(['payment_method' => null])->save();
        Invoice::withoutEvents(fn () => $invoice->forceFill(['payment_method' => 'cash'])->save());

        $this->getJson('/api/pharmacy/orders/'.$orderId)
            ->assertOk()
            ->assertJsonPath('data.payment.method', 'cash');
    }

    /**
     * @param  array<string, mixed>  $breakdown
     */
    private function assertBreakdownIdentity(array $breakdown): void
    {
        $sum = round(
            (float) $breakdown['subtotal']
            - (float) $breakdown['discount']
            + (float) $breakdown['delivery_fee']
            + (float) $breakdown['gst']
            + (float) $breakdown['service_charges']
            + (float) $breakdown['gateway_charges'],
            2
        );

        $this->assertEqualsWithDelta($sum, (float) $breakdown['total'], 0.01);
    }

    public function test_patient_can_comment_until_the_order_is_delivered(): void
    {
        [$hospital] = $this->place();
        $user = $this->member('Ada', 'HIP91009');
        $this->person($user, 'Ada', 'One', true);
        Sanctum::actingAs($user);

        $created = $this->post('/api/pharmacy/orders', $this->payload($hospital->id), ['Accept' => 'application/json']);
        $created->assertCreated();
        $orderId = $created->json('data.id');

        $this->postJson('/api/pharmacy/orders/'.$orderId.'/comments', ['body' => 'Any update?'])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Any update?')
            ->assertJsonPath('data.author_type', 'patient');

        PharmacyOrder::query()->whereKey($orderId)->update(['status' => PharmacyOrderStatus::Delivered->value]);

        $this->postJson('/api/pharmacy/orders/'.$orderId.'/comments', ['body' => 'Still there?'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    }

    public function test_cancel_from_requested_succeeds_and_processing_is_blocked(): void
    {
        [$hospital] = $this->place();
        $user = $this->member('Ada', 'HIP91010');
        $person = $this->person($user, 'Ada', 'One', true);
        Sanctum::actingAs($user);

        $created = $this->post('/api/pharmacy/orders', $this->payload($hospital->id), ['Accept' => 'application/json']);
        $created->assertCreated();
        $order = PharmacyOrder::query()->findOrFail($created->json('data.id'));
        $invoice = Invoice::withoutEvents(function () use ($person) {
            $invoice = new Invoice();
            $invoice->stopUserstamping();
            $invoice->forceFill([
                'primary_person_id' => $person->id,
                'person_id' => $person->id,
                'status' => 'pending',
                'total_amount' => 10,
            ])->save();

            return $invoice;
        });
        $order->invoice_id = $invoice->id;
        $order->save();

        $this->postJson('/api/pharmacy/orders/'.$order->id.'/cancel', ['reason' => 'Changed my mind'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame('cancelled', $invoice->fresh()->status);
        $this->assertSame('Changed my mind', $order->fresh()->cancel_reason);

        $second = $this->post('/api/pharmacy/orders', $this->payload($hospital->id), ['Accept' => 'application/json']);
        $second->assertCreated();
        PharmacyOrder::query()->whereKey($second->json('data.id'))->update([
            'status' => PharmacyOrderStatus::Processing->value,
        ]);

        $this->postJson('/api/pharmacy/orders/'.$second->json('data.id').'/cancel', ['reason' => 'Too late'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This order can no longer be cancelled.');
    }

    public function test_prescription_download_requires_a_valid_unexpired_signature(): void
    {
        [$hospital] = $this->place();
        $user = $this->member('Ada', 'HIP91011');
        $this->person($user, 'Ada', 'One', true);
        Sanctum::actingAs($user);

        $created = $this->post('/api/pharmacy/orders', $this->payload($hospital->id), ['Accept' => 'application/json']);
        $created->assertCreated();
        $url = $created->json('data.prescription.download_url');
        $this->assertIsString($url);
        $this->assertStringNotContainsString('pharmacy-prescriptions', $url);

        $download = $this->get($url);
        $download->assertOk();
        $this->assertStringContainsString('inline', (string) $download->headers->get('content-disposition'));
        $this->assertStringContainsString('no-store', (string) $download->headers->get('cache-control'));

        $tampered = preg_replace('/signature=[^&]+/', 'signature=tampered', $url);
        $this->get($tampered)->assertForbidden();

        $expired = URL::temporarySignedRoute(
            'pharmacy.orders.prescription',
            now()->subMinute(),
            ['order' => $created->json('data.id')]
        );
        $this->get($expired)->assertForbidden();
    }

    public function test_inactive_account_is_rejected_with_the_deactivation_message(): void
    {
        $user = $this->member('Ada', 'HIP91012', false);
        $this->person($user, 'Ada', 'One', true);
        Sanctum::actingAs($user);

        $this->getJson('/api/pharmacy/orders')
            ->assertForbidden()
            ->assertJsonPath('message', EnsureAccountIsActive::MESSAGE);
    }

    public function test_transition_to_processing_is_rejected_outside_mark_paid(): void
    {
        [$hospital, $pharmacy] = $this->place();
        $user = $this->member('Ada', 'HIP91013');
        $person = $this->person($user, 'Ada', 'One', true);
        $order = PharmacyOrder::query()->create([
            'order_number' => 'PH-261010-90001',
            'hip_user_id' => $user->id,
            'person_id' => $person->id,
            'hospital_id' => $hospital->id,
            'pharmacy_id' => $pharmacy->id,
            'status' => PharmacyOrderStatus::PendingPayment,
            'payment_status' => 'unpaid',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('outside markPaid');

        app(PharmacyOrderService::class)->transition($order, PharmacyOrderStatus::Processing, $user);
    }

    /**
     * @return array{0: Hospital, 1: Pharmacy}
     */
    private function place(): array
    {
        $pharmacy = Pharmacy::withoutEvents(function () {
            $pharmacy = new Pharmacy();
            $pharmacy->stopUserstamping();
            $pharmacy->forceFill([
                'name' => 'Nano Pharmacy',
                'pharmacy_id' => 'PH-'.uniqid(),
                'address' => '1 Market',
                'opening_time' => '09:00',
                'closing_time' => '21:00',
                'logo' => null,
                'status' => 'active',
            ])->save();

            return $pharmacy;
        });

        $hospital = Hospital::withoutEvents(function () use ($pharmacy) {
            $hospital = new Hospital();
            $hospital->stopUserstamping();
            $hospital->forceFill([
                'name' => 'Nano Test',
                'pharmacy_ids' => [(int) $pharmacy->id],
            ])->save();

            return $hospital;
        });

        return [$hospital->fresh(), $pharmacy->fresh()];
    }

    private function pharmacy(string $name, string $status): Pharmacy
    {
        return Pharmacy::withoutEvents(function () use ($name, $status) {
            $pharmacy = new Pharmacy();
            $pharmacy->stopUserstamping();
            $pharmacy->forceFill([
                'name' => $name,
                'pharmacy_id' => 'PH-'.uniqid(),
                'address' => '1 Market',
                'status' => $status,
            ])->save();

            return $pharmacy;
        });
    }

    /**
     * @param  list<int>  $pharmacyIds
     */
    private function assignPharmacies(Hospital $hospital, array $pharmacyIds): void
    {
        Hospital::withoutEvents(function () use ($hospital, $pharmacyIds) {
            $hospital->forceFill(['pharmacy_ids' => $pharmacyIds])->save();
        });
    }

    private function member(string $name, string $hipId, bool $active = true): HIPUser
    {
        return HIPUser::withoutEvents(fn () => HIPUser::query()->create([
            'email' => strtolower($hipId).'@example.com',
            'first_name' => $name,
            'last_name' => 'Patient',
            'hip_id' => $hipId,
            'is_active' => $active,
            'password' => null,
        ]));
    }

    private function person(
        HIPUser $user,
        string $first,
        string $last,
        bool $primary,
        ?string $parentId = null,
        ?string $mobile = null,
        ?string $address = null,
        ?HIPUser $linkedUser = null
    ): Persons {
        return Persons::withoutEvents(fn () => Persons::query()->create([
            'first_name' => $first,
            'last_name' => $last,
            'mobile' => $mobile,
            'address' => $address,
            'hip_user_id' => $linkedUser?->id ?? ($primary ? $user->id : null),
            'parent_id' => $parentId,
            'is_primary' => $primary,
            'relationship' => $primary ? 'Self' : 'Child',
            'is_active' => true,
        ]));
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function payload(int $hospitalId, array $extra = []): array
    {
        return array_merge([
            'hospital_id' => $hospitalId,
            'delivery_name' => 'Ada One',
            'delivery_phone' => '9000000001',
            'delivery_address' => '12 Lake Road',
            'prescription' => UploadedFile::fake()->image('rx.jpg'),
        ], $extra);
    }

    public function test_pharmacy_user_coins_discount_the_invoice_total_and_leave_consultations_alone(): void
    {
        config([
            'settings.payment.amount_for_one_coin' => 10,
            'settings.fees.service_charges' => 1,
        ]);

        [$hospital, $pharmacy] = $this->place();
        $user = $this->member('Ada', 'HIP91088');
        $person = $this->person($user, 'Ada', 'One', true);
        $other = $this->member('Bea', 'HIP91089');
        $otherPerson = $this->person($other, 'Bea', 'Two', true);
        Coins::query()->create(['person_id' => $person->id, 'coins' => 488]);
        Coins::query()->create(['person_id' => $otherPerson->id, 'coins' => 488]);

        $order = PharmacyOrder::withoutEvents(function () use ($user, $person, $hospital, $pharmacy) {
            $order = new PharmacyOrder();
            $order->stopUserstamping();
            $order->forceFill([
                'order_number' => 'PO-COIN-91088',
                'hip_user_id' => $user->id,
                'person_id' => $person->id,
                'hospital_id' => $hospital->id,
                'pharmacy_id' => $pharmacy->id,
                'status' => 'pending_payment',
                'payment_status' => 'unpaid',
                'subtotal' => 1000,
                'tax' => 50,
                'service_charge' => 30,
                'gateway_charge' => 20,
                'total' => 1100,
            ])->save();

            return $order;
        });

        $invoice = new Invoice();
        $invoice->stopUserstamping();
        $invoice->forceFill([
            'primary_person_id' => $person->id,
            'person_id' => $person->id,
            'pharmacy_order_id' => $order->id,
            'status' => 'pending',
            'amount' => 1000,
            'total_gst' => 50,
            'service_charges' => 30,
            'payment_gateway_charges' => 20,
            'discount_price' => 0,
            'total_amount' => 1100,
        ])->save();

        Sanctum::actingAs($user);

        $this->getJson('/api/home/user-coins?invoice_id='.$invoice->id.'&coins_used=10&consultation_fee=1')
            ->assertOk()
            ->assertJsonPath('data.coins_used', 10)
            ->assertJsonPath('data.coin_discount', 100)
            ->assertJsonPath('data.service_charge', 0)
            ->assertJsonPath('data.total_amount', 1000)
            ->assertJsonPath('data.consultation_fee', 1100)
            ->assertJsonPath('data.remaining_coins', 478);

        $this->getJson('/api/home/user-coins?consultation_fee=600&coins_used=10')
            ->assertOk()
            ->assertJsonPath('data.consultation_fee', 600)
            ->assertJsonPath('data.coin_discount', 100)
            ->assertJsonPath('data.service_charge', 5)
            ->assertJsonPath('data.total_amount', 505)
            ->assertJsonPath('data.coins_used', 10);

        Sanctum::actingAs($other);

        $this->getJson('/api/home/user-coins?invoice_id='.$invoice->id.'&consultation_fee=600&coins_used=10')
            ->assertOk()
            ->assertJsonPath('data.consultation_fee', 600)
            ->assertJsonPath('data.coin_discount', 100)
            ->assertJsonPath('data.service_charge', 5)
            ->assertJsonPath('data.total_amount', 505);
    }

    public function test_signed_invoice_download_returns_a_pdf_attachment_without_a_bearer_token(): void
    {
        $invoice = $this->paidPharmacyInvoice();
        $url = $this->signedInvoiceDownloadUrl($invoice->id);

        $response = $this->get($url);

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringStartsWith('attachment', $disposition);
        $this->assertStringContainsString('filename="Invoice-PO-PDF-91090.pdf"', $disposition);

        $text = $this->pdfText((string) $response->getContent());
        $this->assertStringContainsString('Original Amount: â‚¹1100.00', $text);
        $this->assertStringContainsString('Discount: â‚¹100.00', $text);
        $this->assertStringContainsString('Final Total: â‚¹1000.00', $text);
    }

    public function test_signed_invoice_download_rejects_a_tampered_signature(): void
    {
        $invoice = $this->paidPharmacyInvoice();
        $url = $this->signedInvoiceDownloadUrl($invoice->id);
        $tampered = preg_replace('/signature=[^&]+/', 'signature=tampered', $url);

        $this->get($tampered)->assertForbidden();
    }

    public function test_signed_invoice_download_rejects_an_expired_url(): void
    {
        $invoice = $this->paidPharmacyInvoice();
        $url = $this->signedInvoiceDownloadUrl($invoice->id, now()->subMinute());

        $this->get($url)->assertForbidden();
    }

    public function test_signed_invoice_download_rejects_a_rewritten_invoice_id(): void
    {
        $invoice = $this->paidPharmacyInvoice();
        $url = $this->signedInvoiceDownloadUrl($invoice->id);
        $rewritten = str_replace(
            '/pay-bill-invoice-download/'.$invoice->id.'?',
            '/pay-bill-invoice-download/'.($invoice->id + 50).'?',
            $url
        );

        $this->assertNotSame($url, $rewritten);
        $this->get($rewritten)->assertForbidden();
    }

    private function signedInvoiceDownloadUrl(int $invoiceId, ?\DateTimeInterface $expires = null): string
    {
        return URL::temporarySignedRoute(
            'hospital.pay-bill-invoice-download',
            $expires ?? now()->addDay(),
            ['invoice_id' => $invoiceId]
        );
    }

    private function paidPharmacyInvoice(): Invoice
    {
        [$hospital, $pharmacy] = $this->place();
        $user = $this->member('Ada', 'HIP91090');
        $person = $this->person($user, 'Ada', 'One', true);

        $order = PharmacyOrder::withoutEvents(function () use ($user, $person, $hospital, $pharmacy) {
            $order = new PharmacyOrder();
            $order->stopUserstamping();
            $order->forceFill([
                'order_number' => 'PO-PDF-91090',
                'hip_user_id' => $user->id,
                'person_id' => $person->id,
                'hospital_id' => $hospital->id,
                'pharmacy_id' => $pharmacy->id,
                'status' => 'processing',
                'payment_status' => 'paid',
                'subtotal' => 1000,
                'tax' => 50,
                'service_charge' => 30,
                'gateway_charge' => 20,
                'total' => 1100,
            ])->save();

            return $order;
        });

        $invoice = new Invoice();
        $invoice->stopUserstamping();
        $invoice->forceFill([
            'primary_person_id' => $person->id,
            'person_id' => $person->id,
            'pharmacy_order_id' => $order->id,
            'status' => 'completed',
            'amount' => 1000,
            'total_gst' => 50,
            'service_charges' => 30,
            'payment_gateway_charges' => 20,
            'discount_price' => 100,
            'coins_applied' => 10,
            'total_amount' => 1100,
        ])->save();

        return $invoice->fresh(['pharmacyOrder']);
    }

    public function test_pharmacy_coin_payment_supersedes_the_first_order_and_keeps_one_rupee_payable(): void
    {
        config(['settings.payment.amount_for_one_coin' => 10]);
        putenv('AMOUNT_FOR_ONE_COIN=10');
        putenv('AMOUNT_For_ONE_COIN=10');
        $_ENV['AMOUNT_FOR_ONE_COIN'] = '10';
        $_ENV['AMOUNT_For_ONE_COIN'] = '10';

        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyUser')->andReturn(true);
        });
        $this->mock(RewardTierService::class, function ($mock) {
            $mock->shouldReceive('addEarnedCoins')->andReturnNull();
            $mock->shouldReceive('reserveDiscountUsage')->andReturnNull();
            $mock->shouldReceive('confirmDiscountUsage')->andReturnNull();
            $mock->shouldReceive('releaseDiscountUsage')->andReturnNull();
        });
        $this->mock(InvoiceGeneratedDispatcher::class, function ($mock) {
            $mock->shouldReceive('dispatch')->andReturnNull();
        });
        $this->mock(PaymentReceivedDispatcher::class, function ($mock) {
            $mock->shouldReceive('dispatch')->andReturnNull();
        });
        $this->bindRazorpayStub();

        [$hospital, $pharmacy] = $this->place();
        $user = $this->member('Cara', 'HIP91091');
        $person = $this->person($user, 'Cara', 'Pay', true);
        Coins::query()->create(['person_id' => $person->id, 'coins' => 488]);
        Sanctum::actingAs($user);

        $zero = $this->pendingPharmacyBill($user, $person, $hospital, $pharmacy, 'PO-PAY-0');
        $openedZero = $this->getJson('/api/hospital/pay-bill-details/'.$zero['invoice']->id)->assertOk();
        $this->assertSame(110000, $openedZero->json('data.payment.amount'));
        $this->postJson('/api/invoices/verify-payment', $this->verifyBody($zero['invoice']->id, $openedZero->json('data.payment.order_id'), 99999))
            ->assertOk()
            ->assertJsonPath('data.amount_paid', 1100)
            ->assertJsonPath('data.coins_applied', 0)
            ->assertJsonPath('data.coins_earned', 11);
        $zero['order']->refresh();
        $zero['invoice']->refresh();
        $this->assertSame('captured', RazorpayPayment::query()->where('razorpay_order_id', $openedZero->json('data.payment.order_id'))->value('payment_status'));
        $this->assertSame('completed', Transactions::query()->where('invoice_id', $zero['invoice']->id)->latest('id')->value('status'));
        $this->assertSame('paid', $zero['order']->payment_status->value);
        $this->assertSame('processing', $zero['order']->status->value);
        $this->assertSame('completed', $zero['invoice']->status);
        $this->assertSame(0, (int) $zero['order']->coins_applied);
        $this->assertSame(0.0, (float) $zero['order']->coins_discount);
        $this->assertSame(1100.0, (float) $zero['order']->amount_paid);
        $this->assertSame(0, (int) $zero['invoice']->coins_applied);
        $this->assertSame(11, (int) $zero['invoice']->coins_earned);
        $this->assertSame(499, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
        $this->assertSame(0.0, (float) $zero['order']->discount);

        $again = $this->postJson('/api/invoices/verify-payment', $this->verifyBody($zero['invoice']->id, $openedZero->json('data.payment.order_id'), 99999));
        $again->assertOk()
            ->assertJsonPath('data.amount_paid', 1100)
            ->assertJsonPath('data.coins_applied', 0)
            ->assertJsonPath('data.coins_earned', 11);
        $this->assertSame(1, Transactions::query()->where('invoice_id', $zero['invoice']->id)->where('status', 'completed')->count());
        $this->assertSame(499, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
        $this->assertSame(1100.0, (float) $zero['order']->fresh()->amount_paid);

        $ten = $this->pendingPharmacyBill($user, $person, $hospital, $pharmacy, 'PO-PAY-10');
        $opened = $this->getJson('/api/hospital/pay-bill-details/'.$ten['invoice']->id)->assertOk();
        $orderA = $opened->json('data.payment.order_id');
        $this->assertSame(110000, $opened->json('data.payment.amount'));

        $this->getJson('/api/home/user-coins?invoice_id='.$ten['invoice']->id.'&coins_used=10')
            ->assertOk()
            ->assertJsonPath('data.coins_used', 10)
            ->assertJsonPath('data.total_amount', 1000)
            ->assertJsonPath('data.service_charge', 0);

        $withCoins = $this->getJson('/api/hospital/pay-bill-details/'.$ten['invoice']->id.'?coins_applied=10')->assertOk();
        $orderB = $withCoins->json('data.payment.order_id');
        $this->assertSame(100000, $withCoins->json('data.payment.amount'));
        $this->assertNotSame($orderA, $orderB);
        $this->assertSame('superseded', RazorpayPayment::query()->where('razorpay_order_id', $orderA)->value('payment_status'));
        $this->assertSame('cancelled', Transactions::query()->where('invoice_id', $ten['invoice']->id)->where('status', 'cancelled')->value('status'));
        $this->assertSame('created', RazorpayPayment::query()->where('razorpay_order_id', $orderB)->value('payment_status'));
        $this->assertSame(499, (int) Coins::query()->where('person_id', $person->id)->value('coins'));

        $this->postJson('/api/invoices/verify-payment', $this->verifyBody($ten['invoice']->id, $orderB, 99999))
            ->assertOk()
            ->assertJsonPath('data.amount_paid', 1000)
            ->assertJsonPath('data.coins_applied', 10)
            ->assertJsonPath('data.coins_earned', 10);
        $ten['order']->refresh();
        $ten['invoice']->refresh();
        $this->assertSame('captured', RazorpayPayment::query()->where('razorpay_order_id', $orderB)->value('payment_status'));
        $this->assertSame('completed', Transactions::query()->where('invoice_id', $ten['invoice']->id)->where('status', 'completed')->value('status'));
        $this->assertSame('paid', $ten['order']->payment_status->value);
        $this->assertSame('completed', $ten['invoice']->status);
        $this->assertSame(100.0, (float) $ten['invoice']->discount_price);
        $this->assertSame(10, (int) $ten['order']->coins_applied);
        $this->assertSame(100.0, (float) $ten['order']->coins_discount);
        $this->assertSame(1000.0, (float) $ten['order']->amount_paid);
        $this->assertSame(0.0, (float) $ten['order']->discount);
        $this->getJson('/api/pharmacy/orders/'.$ten['order']->id)
            ->assertOk()
            ->assertJsonPath('data.payment.coins_applied', 10)
            ->assertJsonPath('data.payment.coins_discount', 100)
            ->assertJsonPath('data.payment.amount_paid', 1000);
        $this->assertSame(1100.0, (float) $ten['order']->total);
        $this->assertSame(499, (int) Coins::query()->where('person_id', $person->id)->value('coins'));

        $cover = $this->pendingPharmacyBill($user, $person, $hospital, $pharmacy, 'PO-PAY-COVER');
        $this->getJson('/api/home/user-coins?invoice_id='.$cover['invoice']->id.'&coins_used=99999')
            ->assertOk()
            ->assertJsonPath('data.coins_used', 109)
            ->assertJsonPath('data.coin_discount', 1090)
            ->assertJsonPath('data.service_charge', 0)
            ->assertJsonPath('data.total_amount', 10);
        $covered = $this->getJson('/api/hospital/pay-bill-details/'.$cover['invoice']->id.'?coins_applied=99999')->assertOk();
        $this->assertSame(1000, $covered->json('data.payment.amount'));
        $this->postJson('/api/invoices/verify-payment', $this->verifyBody($cover['invoice']->id, $covered->json('data.payment.order_id'), 99999))
            ->assertOk()
            ->assertJsonPath('data.amount_paid', 10)
            ->assertJsonPath('data.coins_applied', 109)
            ->assertJsonPath('data.coins_earned', 0);
        $cover['order']->refresh();
        $cover['invoice']->refresh();
        $this->assertSame('paid', $cover['order']->payment_status->value);
        $this->assertSame('completed', $cover['invoice']->status);
        $this->assertSame(390, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
    }

    public function test_a_captured_payment_on_a_superseded_order_is_honoured_without_a_coin_debit(): void
    {
        config(['settings.payment.amount_for_one_coin' => 10]);
        putenv('AMOUNT_FOR_ONE_COIN=10');
        putenv('AMOUNT_For_ONE_COIN=10');
        $_ENV['AMOUNT_FOR_ONE_COIN'] = '10';
        $_ENV['AMOUNT_For_ONE_COIN'] = '10';

        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyUser')->andReturn(true);
        });
        $this->mock(RewardTierService::class, function ($mock) {
            $mock->shouldReceive('addEarnedCoins')->andReturnNull();
            $mock->shouldReceive('reserveDiscountUsage')->andReturnNull();
            $mock->shouldReceive('confirmDiscountUsage')->andReturnNull();
            $mock->shouldReceive('releaseDiscountUsage')->andReturnNull();
        });
        $this->mock(InvoiceGeneratedDispatcher::class, function ($mock) {
            $mock->shouldReceive('dispatch')->andReturnNull();
        });
        $this->mock(PaymentReceivedDispatcher::class, function ($mock) {
            $mock->shouldReceive('dispatch')->andReturnNull();
        });
        $this->bindRazorpayStub();

        [$hospital, $pharmacy] = $this->place();
        $user = $this->member('Owen', 'HIP91093');
        $person = $this->person($user, 'Owen', 'Honour', true);
        Coins::query()->create(['person_id' => $person->id, 'coins' => 488]);
        Sanctum::actingAs($user);

        $logs = [];
        Log::listen(function ($event) use (&$logs) {
            $logs[] = ['level' => $event->level, 'message' => $event->message, 'context' => $event->context];
        });

        $stale = $this->pendingPharmacyBill($user, $person, $hospital, $pharmacy, 'PO-PAY-STALE');
        $opened = $this->getJson('/api/hospital/pay-bill-details/'.$stale['invoice']->id)->assertOk();
        $orderA = (string) $opened->json('data.payment.order_id');
        $openedB = $this->getJson('/api/hospital/pay-bill-details/'.$stale['invoice']->id.'?coins_applied=10')->assertOk();
        $orderB = (string) $openedB->json('data.payment.order_id');
        $this->assertNotSame($orderA, $orderB);
        $this->assertSame('superseded', RazorpayPayment::query()->where('razorpay_order_id', $orderA)->value('payment_status'));

        $GLOBALS['pharmacy_rzp_payment_status'] = 'captured';
        $GLOBALS['pharmacy_rzp_payment_amount'] = 110000;
        $honoured = $this->postJson('/api/invoices/verify-payment', $this->verifyBody($stale['invoice']->id, $orderA, 99999));
        unset($GLOBALS['pharmacy_rzp_payment_status'], $GLOBALS['pharmacy_rzp_payment_amount']);

        $honoured->assertOk()
            ->assertJsonPath('data.amount_paid', 1100)
            ->assertJsonPath('data.coins_applied', 0)
            ->assertJsonPath('data.coins_earned', 11);
        $stale['order']->refresh();
        $stale['invoice']->refresh();
        $this->assertSame('captured', RazorpayPayment::query()->where('razorpay_order_id', $orderA)->value('payment_status'));
        $this->assertSame(0, (int) RazorpayPayment::query()->where('razorpay_order_id', $orderA)->value('coins_applied'));
        $this->assertSame(1100.0, (float) RazorpayPayment::query()->where('razorpay_order_id', $orderA)->value('amount_paid'));
        $this->assertSame('paid', $stale['order']->payment_status->value);
        $this->assertSame('completed', $stale['invoice']->status);
        $this->assertSame(0, (int) $stale['order']->coins_applied);
        $this->assertSame(0.0, (float) $stale['order']->coins_discount);
        $this->assertSame(1100.0, (float) $stale['order']->amount_paid);
        $this->assertSame(0, (int) $stale['invoice']->coins_applied);
        $this->assertSame(499, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
        $honourLog = collect($logs)->first(fn (array $row) => $row['message'] === 'Pharmacy payment on superseded order honoured');
        $this->assertNotNull($honourLog);
        $this->assertSame('warning', $honourLog['level']);
        $this->assertSame((int) $stale['invoice']->id, $honourLog['context']['invoice_id']);
        $this->assertSame($orderA, $honourLog['context']['razorpay_order_id']);
        $this->assertSame('pay_'.$orderA, $honourLog['context']['razorpay_payment_id']);
        $this->assertSame(1100.0, (float) $honourLog['context']['captured_amount']);
        $requestLog = collect($logs)->first(fn (array $row) => $row['message'] === 'Pharmacy verify payment request');
        $this->assertNotNull($requestLog);
        $this->assertSame($user->id, $requestLog['context']['user_id']);
        $this->assertContains('invoice_id', $requestLog['context']['keys']);
        $this->assertContains('razorpay_order_id', $requestLog['context']['keys']);
        $this->assertContains('razorpay_payment_id', $requestLog['context']['keys']);
        $this->assertContains('razorpay_signature', $requestLog['context']['keys']);
        $this->assertStringNotContainsString('sig_'.$orderA, json_encode($requestLog['context']));
        $this->assertStringContainsString('json', strtolower((string) $requestLog['context']['content_type']));
        $this->assertTrue(collect($logs)->contains(fn (array $row) => $row['message'] === 'Pharmacy verify payment response'
            && ($row['context']['status'] ?? null) === 200));

        $again = $this->postJson('/api/invoices/verify-payment', $this->verifyBody($stale['invoice']->id, $orderA, 99999));
        $again->assertOk()
            ->assertJsonPath('data.amount_paid', 1100)
            ->assertJsonPath('data.coins_applied', 0)
            ->assertJsonPath('data.coins_earned', 11);
        $this->assertSame(1, Transactions::query()->where('invoice_id', $stale['invoice']->id)->where('status', 'completed')->count());
        $this->assertSame(499, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
        $this->assertSame(1, collect($logs)->where('message', 'Pharmacy payment on superseded order honoured')->count());
        $this->assertSame('superseded', RazorpayPayment::query()->where('razorpay_order_id', $orderB)->value('payment_status'));
        $this->assertSame(0, Transactions::query()->where('invoice_id', $stale['invoice']->id)->where('status', 'pending')->count());
        $this->assertSame('cancelled', Transactions::query()->where('invoice_id', $stale['invoice']->id)->where('status', '!=', 'completed')->latest('id')->value('status'));

        $this->getJson('/api/hospital/pay-bill-details/'.$stale['invoice']->id)
            ->assertOk()
            ->assertJsonPath('data.payment.requires_payment', false)
            ->assertJsonMissingPath('data.payment.order_id');
        $this->getJson('/api/pharmacy/orders/'.$stale['order']->id)
            ->assertOk()
            ->assertJsonPath('data.payment.can_pay', false);

        $GLOBALS['pharmacy_rzp_payment_status'] = 'captured';
        $GLOBALS['pharmacy_rzp_payment_amount'] = 100000;
        $duplicate = $this->postJson('/api/invoices/verify-payment', $this->verifyBody($stale['invoice']->id, $orderB, 10));
        unset($GLOBALS['pharmacy_rzp_payment_status'], $GLOBALS['pharmacy_rzp_payment_amount']);
        $duplicate->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'duplicate_payment')
            ->assertJsonPath('message', 'Duplicate payment on completed pharmacy invoice')
            ->assertJsonPath('data.invoice_id', $stale['invoice']->id)
            ->assertJsonPath('data.razorpay_order_id', $orderB)
            ->assertJsonPath('data.razorpay_payment_id', 'pay_'.$orderB)
            ->assertJsonPath('data.amount', 1000);
        $this->assertSame('completed', $stale['invoice']->fresh()->status);
        $this->assertSame(1100.0, (float) $stale['order']->fresh()->amount_paid);
        $this->assertSame(0, (int) $stale['invoice']->fresh()->coins_applied);
        $this->assertSame(11, (int) $stale['invoice']->fresh()->coins_earned);
        $this->assertSame(499, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
        $this->assertSame(1, Transactions::query()->where('invoice_id', $stale['invoice']->id)->where('status', 'completed')->count());
        $this->assertSame('superseded', RazorpayPayment::query()->where('razorpay_order_id', $orderB)->value('payment_status'));
        $duplicateLog = collect($logs)->first(fn (array $row) => $row['message'] === 'Duplicate payment on completed pharmacy invoice');
        $this->assertNotNull($duplicateLog);
        $this->assertSame('warning', $duplicateLog['level']);
        $this->assertSame((int) $stale['invoice']->id, $duplicateLog['context']['invoice_id']);
        $this->assertSame($orderB, $duplicateLog['context']['razorpay_order_id']);
        $this->assertSame('pay_'.$orderB, $duplicateLog['context']['razorpay_payment_id']);
        $this->assertSame(1000.0, (float) $duplicateLog['context']['amount']);
        $this->assertTrue(collect($logs)->contains(fn (array $row) => $row['message'] === 'Pharmacy verify payment response'
            && ($row['context']['status'] ?? null) === 409));

        $bad = $this->pendingPharmacyBill($user, $person, $hospital, $pharmacy, 'PO-PAY-STALE-SIG');
        $openedBad = $this->getJson('/api/hospital/pay-bill-details/'.$bad['invoice']->id)->assertOk();
        $badOrder = (string) $openedBad->json('data.payment.order_id');
        $this->getJson('/api/hospital/pay-bill-details/'.$bad['invoice']->id.'?coins_applied=10')->assertOk();
        $GLOBALS['pharmacy_rzp_reject_signature'] = true;
        $rejected = $this->postJson('/api/invoices/verify-payment', $this->verifyBody($bad['invoice']->id, $badOrder, 0));
        $GLOBALS['pharmacy_rzp_reject_signature'] = false;
        $rejected->assertStatus(400);
        $this->assertStringContainsString('Invalid signature', (string) $rejected->json('message'));
        $this->assertSame('superseded', RazorpayPayment::query()->where('razorpay_order_id', $badOrder)->value('payment_status'));
        $this->assertSame('unpaid', $bad['order']->fresh()->payment_status->value);
        $this->assertSame('pending', $bad['invoice']->fresh()->status);
        $this->assertSame(499, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
        $this->assertTrue(collect($logs)->contains(fn (array $row) => $row['message'] === 'Razorpay signature verification failed'
            && ($row['context']['reason'] ?? null) === 'bad signature'
            && ($row['context']['razorpay_order_id'] ?? null) === $badOrder));

        $open = $this->pendingPharmacyBill($user, $person, $hospital, $pharmacy, 'PO-PAY-STALE-OPEN');
        $openedOpen = $this->getJson('/api/hospital/pay-bill-details/'.$open['invoice']->id)->assertOk();
        $openOrder = (string) $openedOpen->json('data.payment.order_id');
        $this->getJson('/api/hospital/pay-bill-details/'.$open['invoice']->id.'?coins_applied=10')->assertOk();
        $GLOBALS['pharmacy_rzp_payment_status'] = 'authorized';
        $GLOBALS['pharmacy_rzp_payment_amount'] = 110000;
        $uncaptured = $this->postJson('/api/invoices/verify-payment', $this->verifyBody($open['invoice']->id, $openOrder, 0));
        unset($GLOBALS['pharmacy_rzp_payment_status'], $GLOBALS['pharmacy_rzp_payment_amount']);
        $uncaptured->assertStatus(400);
        $this->assertStringContainsString('Payment was not captured.', (string) $uncaptured->json('message'));
        $this->assertSame('superseded', RazorpayPayment::query()->where('razorpay_order_id', $openOrder)->value('payment_status'));
        $this->assertSame('unpaid', $open['order']->fresh()->payment_status->value);
        $this->assertSame('pending', $open['invoice']->fresh()->status);
        $this->assertSame(499, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
        $this->assertTrue(collect($logs)->contains(fn (array $row) => $row['message'] === 'Pharmacy verify payment rejected'
            && ($row['context']['reason'] ?? null) === 'not captured'
            && ($row['context']['razorpay_order_id'] ?? null) === $openOrder));

        $foreign = $this->postJson('/api/invoices/verify-payment', $this->verifyBody($open['invoice']->id, $orderA, 0));
        $foreign->assertStatus(400);
        $this->assertTrue(collect($logs)->contains(fn (array $row) => $row['message'] === 'Pharmacy verify payment rejected'
            && ($row['context']['reason'] ?? null) === 'not found'
            && ($row['context']['invoice_id'] ?? null) === (int) $open['invoice']->id
            && ($row['context']['razorpay_order_id'] ?? null) === $orderA));
        $this->assertSame('pending', $open['invoice']->fresh()->status);
        $this->assertSame(499, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
    }

    public function test_verify_payment_logs_the_request_and_a_dropped_coin_count_without_writing_laravel_log(): void
    {
        config(['settings.payment.amount_for_one_coin' => 10]);
        putenv('AMOUNT_FOR_ONE_COIN=10');
        putenv('AMOUNT_For_ONE_COIN=10');
        $_ENV['AMOUNT_FOR_ONE_COIN'] = '10';
        $_ENV['AMOUNT_For_ONE_COIN'] = '10';

        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyUser')->andReturn(true);
        });
        $this->mock(RewardTierService::class, function ($mock) {
            $mock->shouldReceive('addEarnedCoins')->andReturnNull();
            $mock->shouldReceive('reserveDiscountUsage')->andReturnNull();
            $mock->shouldReceive('confirmDiscountUsage')->andReturnNull();
            $mock->shouldReceive('releaseDiscountUsage')->andReturnNull();
        });
        $this->mock(InvoiceGeneratedDispatcher::class, function ($mock) {
            $mock->shouldReceive('dispatch')->andReturnNull();
        });
        $this->mock(PaymentReceivedDispatcher::class, function ($mock) {
            $mock->shouldReceive('dispatch')->andReturnNull();
        });
        $this->bindRazorpayStub();

        $logPath = storage_path('logs/laravel.log');
        $logBytes = is_file($logPath) ? filesize($logPath) : 0;
        $this->assertSame('testing', config('logging.default'));

        [$hospital, $pharmacy] = $this->place();
        $user = $this->member('Ada', 'HIP91094');
        $person = $this->person($user, 'Ada', 'Log', true);
        Coins::query()->create(['person_id' => $person->id, 'coins' => 488]);
        Sanctum::actingAs($user);

        $logs = [];
        Log::listen(function ($event) use (&$logs) {
            $logs[] = ['level' => $event->level, 'message' => $event->message, 'context' => $event->context];
        });

        $invalid = $this->postJson('/api/invoices/verify-payment', [
            'razorpay_signature' => 'super-secret-signature',
        ]);
        $invalid->assertStatus(422);
        $requestLog = collect($logs)->first(fn (array $row) => $row['message'] === 'Pharmacy verify payment request');
        $this->assertNotNull($requestLog);
        $this->assertSame($user->id, $requestLog['context']['user_id']);
        $this->assertSame(['razorpay_signature'], $requestLog['context']['keys']);
        $this->assertStringNotContainsString('super-secret-signature', json_encode($requestLog['context']));
        $this->assertStringContainsString('json', strtolower((string) $requestLog['context']['content_type']));
        $validationLog = collect($logs)->first(fn (array $row) => $row['message'] === 'Pharmacy verify payment validation failed');
        $this->assertNotNull($validationLog);
        $this->assertSame('warning', $validationLog['level']);
        $this->assertArrayHasKey('invoice_id', $validationLog['context']['errors']);
        $this->assertArrayHasKey('razorpay_payment_id', $validationLog['context']['errors']);
        $this->assertArrayHasKey('razorpay_order_id', $validationLog['context']['errors']);
        $this->assertStringNotContainsString('super-secret-signature', json_encode($validationLog['context']));
        $this->assertTrue(collect($logs)->contains(fn (array $row) => $row['message'] === 'Pharmacy verify payment response'
            && ($row['context']['status'] ?? null) === 422));

        $bill = $this->pendingPharmacyBill($user, $person, $hospital, $pharmacy, 'PO-PAY-DROP');
        $this->getJson('/api/hospital/pay-bill-details/'.$bill['invoice']->id)->assertOk();
        $this->assertFalse(collect($logs)->contains(fn (array $row) => $row['message'] === 'Pharmacy coin count dropped'));

        $this->getJson('/api/home/user-coins?invoice_id='.$bill['invoice']->id.'&coins_used=10')->assertOk();
        $this->getJson('/api/home/user-coins?invoice_id='.$bill['invoice']->id)->assertOk();
        $previewDrop = collect($logs)->first(fn (array $row) => $row['message'] === 'Pharmacy coin count dropped'
            && ($row['context']['source'] ?? null) === 'user-coins');
        $this->assertNotNull($previewDrop);
        $this->assertSame('warning', $previewDrop['level']);
        $this->assertSame((int) $bill['invoice']->id, $previewDrop['context']['invoice_id']);
        $this->assertSame(10, $previewDrop['context']['previous_coins']);
        $this->assertNull($previewDrop['context']['coins']);

        $this->getJson('/api/hospital/pay-bill-details/'.$bill['invoice']->id.'?coins_applied=10')->assertOk();
        $this->getJson('/api/hospital/pay-bill-details/'.$bill['invoice']->id)->assertOk();
        $billDrop = collect($logs)->first(fn (array $row) => $row['message'] === 'Pharmacy coin count dropped'
            && ($row['context']['source'] ?? null) === 'pay-bill-details');
        $this->assertNotNull($billDrop);
        $this->assertSame(10, $billDrop['context']['previous_coins']);
        $this->assertNull($billDrop['context']['coins']);

        clearstatcache(true, $logPath);
        $this->assertSame($logBytes, is_file($logPath) ? filesize($logPath) : 0);
    }

    public function test_a_bad_signature_marks_the_payment_failed_and_a_post_capture_error_does_not(): void
    {
        config(['settings.payment.amount_for_one_coin' => 10]);
        putenv('AMOUNT_FOR_ONE_COIN=10');
        putenv('AMOUNT_For_ONE_COIN=10');
        $_ENV['AMOUNT_FOR_ONE_COIN'] = '10';
        $_ENV['AMOUNT_For_ONE_COIN'] = '10';

        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyUser')->andReturn(true);
        });
        $this->mock(InvoiceGeneratedDispatcher::class, function ($mock) {
            $mock->shouldReceive('dispatch')->andReturnNull();
        });
        $this->mock(PaymentReceivedDispatcher::class, function ($mock) {
            $mock->shouldReceive('dispatch')->andReturnNull();
        });
        $this->bindRazorpayStub();

        [$hospital, $pharmacy] = $this->place();
        $user = $this->member('Nia', 'HIP91092');
        $person = $this->person($user, 'Nia', 'Sign', true);
        Coins::query()->create(['person_id' => $person->id, 'coins' => 488]);
        Sanctum::actingAs($user);

        $bad = $this->pendingPharmacyBill($user, $person, $hospital, $pharmacy, 'PO-PAY-SIG');
        $opened = $this->getJson('/api/hospital/pay-bill-details/'.$bad['invoice']->id)->assertOk();
        $orderId = (string) $opened->json('data.payment.order_id');
        $responseStatuses = [];
        Log::listen(function ($event) use (&$responseStatuses) {
            if ($event->message === 'Pharmacy verify payment response') {
                $responseStatuses[] = $event->context['status'] ?? null;
            }
        });
        $GLOBALS['pharmacy_rzp_reject_signature'] = true;
        $rejected = $this->postJson('/api/invoices/verify-payment', $this->verifyBody($bad['invoice']->id, $orderId, 0));
        $GLOBALS['pharmacy_rzp_reject_signature'] = false;

        $rejected->assertStatus(400);
        $this->assertContains(400, $responseStatuses);
        $this->assertStringContainsString('Invalid signature', (string) $rejected->json('message'));
        $bad['order']->refresh();
        $bad['invoice']->refresh();
        $this->assertSame('failed', RazorpayPayment::query()->where('razorpay_order_id', $orderId)->value('payment_status'));
        $this->assertSame('failed', Transactions::query()->where('invoice_id', $bad['invoice']->id)->latest('id')->value('status'));
        $this->assertSame('pending', $bad['invoice']->status);
        $this->assertSame('failed', $bad['order']->payment_status->value);
        $this->assertSame('pending_payment', $bad['order']->status->value);
        $this->assertSame(488, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
        $this->assertNull($bad['order']->amount_paid);

        $this->mock(RewardTierService::class, function ($mock) {
            $mock->shouldReceive('addEarnedCoins')->andThrow(new \RuntimeException('forced completion failure'));
        });

        $caught = $this->pendingPharmacyBill($user, $person, $hospital, $pharmacy, 'PO-PAY-AFTER');
        $openedCaught = $this->getJson('/api/hospital/pay-bill-details/'.$caught['invoice']->id)->assertOk();
        $caughtOrderId = (string) $openedCaught->json('data.payment.order_id');
        $loggedPaymentId = null;
        $responseStatuses = [];
        Log::listen(function ($event) use (&$loggedPaymentId, &$responseStatuses) {
            if ($event->level === 'error' && isset($event->context['razorpay_payment_id'])) {
                $loggedPaymentId = $event->context['razorpay_payment_id'];
            }
            if ($event->message === 'Pharmacy verify payment response') {
                $responseStatuses[] = $event->context['status'] ?? null;
            }
        });
        $after = $this->postJson('/api/invoices/verify-payment', $this->verifyBody($caught['invoice']->id, $caughtOrderId, 0));

        $after->assertStatus(409);
        $after->assertJsonPath('code', 'payment_pending_confirmation');
        $this->assertContains(409, $responseStatuses);
        $this->assertStringContainsString('not marked failed', (string) $after->json('message'));
        $caught['order']->refresh();
        $caught['invoice']->refresh();
        $this->assertSame('created', RazorpayPayment::query()->where('razorpay_order_id', $caughtOrderId)->value('payment_status'));
        $this->assertSame('pending', Transactions::query()->where('invoice_id', $caught['invoice']->id)->latest('id')->value('status'));
        $this->assertSame('pending', $caught['invoice']->status);
        $this->assertSame('unpaid', $caught['order']->payment_status->value);
        $this->assertSame('pending_payment', $caught['order']->status->value);
        $this->assertSame(488, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
        $this->assertNull($caught['order']->amount_paid);
        $this->assertSame('pay_'.$caughtOrderId, $loggedPaymentId);

        $retry = $this->postJson('/api/invoices/verify-payment', $this->verifyBody($caught['invoice']->id, $caughtOrderId, 0));
        $retry->assertStatus(409);
        $retry->assertJsonPath('code', 'payment_pending_confirmation');
        $this->assertSame('created', RazorpayPayment::query()->where('razorpay_order_id', $caughtOrderId)->value('payment_status'));
        $this->assertSame('pending', Transactions::query()->where('invoice_id', $caught['invoice']->id)->latest('id')->value('status'));
        $this->assertSame('pending', $caught['invoice']->fresh()->status);
        $this->assertSame('unpaid', $caught['order']->fresh()->payment_status->value);
        $this->assertSame(488, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
        $this->assertNull($caught['order']->fresh()->amount_paid);
    }

    public function test_pharmacy_bill_of_1156_68_charges_the_preview_amount_when_the_env_coin_rate_is_ten_times_higher(): void
    {
        config(['settings.payment.amount_for_one_coin' => 10]);
        putenv('AMOUNT_FOR_ONE_COIN=100');
        putenv('AMOUNT_For_ONE_COIN=100');
        $_ENV['AMOUNT_FOR_ONE_COIN'] = '100';
        $_ENV['AMOUNT_For_ONE_COIN'] = '100';
        $_SERVER['AMOUNT_FOR_ONE_COIN'] = '100';
        $_SERVER['AMOUNT_For_ONE_COIN'] = '100';

        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyUser')->andReturn(true);
        });
        $this->mock(RewardTierService::class, function ($mock) {
            $mock->shouldReceive('addEarnedCoins')->andReturnNull();
            $mock->shouldReceive('reserveDiscountUsage')->andReturnNull();
            $mock->shouldReceive('confirmDiscountUsage')->andReturnNull();
            $mock->shouldReceive('releaseDiscountUsage')->andReturnNull();
        });
        $this->mock(InvoiceGeneratedDispatcher::class, function ($mock) {
            $mock->shouldReceive('dispatch')->andReturnNull();
        });
        $this->mock(PaymentReceivedDispatcher::class, function ($mock) {
            $mock->shouldReceive('dispatch')->andReturnNull();
        });
        $this->bindRazorpayStub();
        Carbon::setTestNow(Carbon::parse('2026-11-20 13:22:00', 'Asia/Kolkata'));

        [$hospital, $pharmacy] = $this->place();

        $cases = [
            0 => ['coins' => 0, 'preview' => 1156.68, 'paise' => 115668, 'paid' => 1156.68, 'discount' => 0.0, 'earned' => 12],
            10 => ['coins' => 10, 'preview' => 1056.68, 'paise' => 105668, 'paid' => 1056.68, 'discount' => 100.0, 'earned' => 11],
            115 => ['coins' => 115, 'preview' => 6.68, 'paise' => 668, 'paid' => 6.68, 'discount' => 1150.0, 'earned' => 0],
            1000 => ['coins' => 115, 'preview' => 6.68, 'paise' => 668, 'paid' => 6.68, 'discount' => 1150.0, 'earned' => 0],
        ];

        foreach ($cases as $requested => $expected) {
            $user = $this->member('Bill'.$requested, 'HIP91'.str_pad((string) $requested, 3, '0', STR_PAD_LEFT));
            $person = $this->person($user, 'Bill', (string) $requested, true);
            Coins::query()->create(['person_id' => $person->id, 'coins' => 603]);
            Sanctum::actingAs($user);

            $bill = $this->pendingPharmacyBill($user, $person, $hospital, $pharmacy, 'PO-RATE-'.$requested);
            $bill['invoice']->forceFill([
                'amount' => 1051.52,
                'total_gst' => 52.58,
                'service_charges' => 31.55,
                'payment_gateway_charges' => 21.03,
                'total_amount' => 1156.68,
            ])->save();
            $bill['order']->forceFill([
                'subtotal' => 1051.52,
                'tax' => 52.58,
                'service_charge' => 31.55,
                'gateway_charge' => 21.03,
                'total' => 1156.68,
            ])->save();

            $preview = $this->getJson('/api/home/user-coins?invoice_id='.$bill['invoice']->id.'&coins_used='.$requested);
            $preview->assertOk();
            $this->assertEqualsWithDelta($expected['preview'], (float) $preview->json('data.total_amount'), 0.001);
            $this->assertEqualsWithDelta($expected['discount'], (float) $preview->json('data.coin_discount'), 0.001);
            $this->assertSame($expected['coins'], (int) $preview->json('data.coins_used'));

            $opened = $this->getJson('/api/hospital/pay-bill-details/'.$bill['invoice']->id.'?coins_applied='.$requested);
            $opened->assertOk();
            $this->assertSame($expected['paise'], (int) $opened->json('data.payment.amount'));

            $paid = $this->postJson('/api/invoices/verify-payment', $this->verifyBody(
                $bill['invoice']->id,
                (string) $opened->json('data.payment.order_id'),
                $requested
            ));
            $paid->assertOk();
            $this->assertEqualsWithDelta($expected['paid'], (float) $paid->json('data.amount_paid'), 0.001);
            $this->assertSame($expected['coins'], (int) $paid->json('data.coins_applied'));
            $paid->assertJsonPath('data.date', '20 Nov 26');
            $paid->assertJsonPath('data.time', '01.22 Pm');

            if ($requested === 0) {
                Carbon::setTestNow(Carbon::parse('2026-12-01 08:15:00', 'Asia/Kolkata'));
                $repeat = $this->postJson('/api/invoices/verify-payment', $this->verifyBody(
                    $bill['invoice']->id,
                    (string) $opened->json('data.payment.order_id'),
                    $requested
                ));
                $repeat->assertOk();
                $repeat->assertJsonPath('data.date', '20 Nov 26');
                $repeat->assertJsonPath('data.time', '01.22 Pm');
                $this->assertEqualsWithDelta($expected['paid'], (float) $repeat->json('data.amount_paid'), 0.001);
                Carbon::setTestNow(Carbon::parse('2026-11-20 13:22:00', 'Asia/Kolkata'));
            }

            $bill['order']->refresh();
            $this->assertSame($expected['coins'], (int) $bill['order']->coins_applied);
            $this->assertEqualsWithDelta($expected['discount'], (float) $bill['order']->coins_discount, 0.001);
            $this->assertEqualsWithDelta($expected['paid'], (float) $bill['order']->amount_paid, 0.001);
            $this->assertSame(
                603 - $expected['coins'] + $expected['earned'],
                (int) Coins::query()->where('person_id', $person->id)->value('coins')
            );
        }

        foreach ([
            '2026-11-20 00:05:00' => ['time' => '12.05 Am', 'hip' => 'HIP91005'],
            '2026-11-20 12:05:00' => ['time' => '12.05 Pm', 'hip' => 'HIP91012'],
        ] as $when => $clock) {
            Carbon::setTestNow(Carbon::parse($when, 'Asia/Kolkata'));
            $user = $this->member('Clock'.$clock['hip'], $clock['hip']);
            $person = $this->person($user, 'Clock', $clock['hip'], true);
            Coins::query()->create(['person_id' => $person->id, 'coins' => 10]);
            Sanctum::actingAs($user);
            $bill = $this->pendingPharmacyBill($user, $person, $hospital, $pharmacy, 'PO-CLOCK-'.$clock['hip']);
            $opened = $this->getJson('/api/hospital/pay-bill-details/'.$bill['invoice']->id.'?coins_applied=0')->assertOk();
            $paid = $this->postJson('/api/invoices/verify-payment', $this->verifyBody(
                $bill['invoice']->id,
                (string) $opened->json('data.payment.order_id'),
                0
            ));
            $paid->assertOk();
            $paid->assertJsonPath('data.date', '20 Nov 26');
            $paid->assertJsonPath('data.time', $clock['time']);
        }
    }

    public function test_appointment_coins_use_the_app_setting_when_the_env_rate_is_100(): void
    {
        config(['settings.payment.amount_for_one_coin' => 100]);
        putenv('AMOUNT_FOR_ONE_COIN=100');
        putenv('AMOUNT_For_ONE_COIN=100');
        $_ENV['AMOUNT_FOR_ONE_COIN'] = '100';
        $_ENV['AMOUNT_For_ONE_COIN'] = '100';
        $_SERVER['AMOUNT_FOR_ONE_COIN'] = '100';
        $_SERVER['AMOUNT_For_ONE_COIN'] = '100';

        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function ($table) {
                $table->id();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->string('type')->nullable();
                $table->string('group')->nullable();
                $table->timestamps();
            });
        }
        Setting::set('amount_for_one_coin', 10, 'float', 'payment');

        if (! Schema::hasTable('doctor_bookings')) {
            Schema::create('doctor_bookings', function ($table) {
                $table->id();
                $table->uuid('patient_id')->nullable();
                $table->unsignedInteger('coins_used')->default(0);
                $table->string('payment_status')->nullable();
                $table->string('status')->nullable();
                $table->string('appointment_status')->nullable();
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->string('doctor_id')->nullable();
                $table->uuid('created_by')->nullable();
                $table->uuid('updated_by')->nullable();
                $table->timestamps();
            });
        }

        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyUser')->andReturn(true);
        });
        $this->mock(RewardTierService::class, function ($mock) {
            $mock->shouldReceive('addEarnedCoins')->andReturnNull();
            $mock->shouldReceive('reserveDiscountUsage')->andReturnNull();
            $mock->shouldReceive('confirmDiscountUsage')->andReturnNull();
            $mock->shouldReceive('releaseDiscountUsage')->andReturnNull();
        });
        $this->mock(InvoiceGeneratedDispatcher::class, function ($mock) {
            $mock->shouldReceive('dispatch')->andReturnNull();
        });
        $this->mock(PaymentReceivedDispatcher::class, function ($mock) {
            $mock->shouldReceive('dispatch')->andReturnNull();
        });
        Event::fake([AppointmentBooked::class]);
        $this->bindRazorpayStub();
        Carbon::setTestNow(Carbon::parse('2026-11-20 13:22:00', 'Asia/Kolkata'));

        $user = $this->member('Asha', 'HIP91077');
        $person = $this->person($user, 'Asha', 'Appoint', true);
        Coins::query()->create(['person_id' => $person->id, 'coins' => 40]);
        Sanctum::actingAs($user);

        $booking = new DoctorBooking();
        $booking->stopUserstamping();
        $booking->forceFill([
            'patient_id' => $person->id,
            'coins_used' => 10,
            'payment_status' => 'pending',
            'status' => 'pending',
            'doctor_id' => null,
        ])->save();

        $invoice = new Invoice();
        $invoice->stopUserstamping();
        $invoice->forceFill([
            'primary_person_id' => $person->id,
            'person_id' => $person->id,
            'doctor_booking_id' => $booking->id,
            'status' => 'pending',
            'amount' => 500,
            'total_gst' => 0,
            'service_charges' => 0,
            'payment_gateway_charges' => 0,
            'discount_price' => 0,
            'coins_applied' => 10,
            'total_amount' => 500,
        ])->save();

        $order = app(PaymentApiService::class)->createRazorpayOrder((int) $invoice->id, 10);
        $this->assertSame(40000, (int) $order['amount']);

        $paid = $this->postJson('/api/invoices/verify-payment', $this->verifyBody((int) $invoice->id, (string) $order['order_id'], 10));
        $paid->assertOk();
        $paid->assertJsonPath('data.date', '20 Nov 26');
        $paid->assertJsonPath('data.time', '01.22 Pm');
        $this->assertEqualsWithDelta(400.0, (float) $paid->json('data.amount_paid'), 0.001);
        $this->assertSame(10, (int) $paid->json('data.coins_applied'));
        $this->assertEqualsWithDelta(100.0, round(500 - (float) $paid->json('data.amount_paid'), 2), 0.001);
        $this->assertEqualsWithDelta(100.0, round(500 - (float) RazorpayPayment::query()->where('razorpay_order_id', $order['order_id'])->value('amount_paid'), 2), 0.001);
        $this->assertSame(34, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
    }

    public function test_payment_page_bill_applies_coins_and_a_booking_bill_does_not(): void
    {
        config(['settings.payment.amount_for_one_coin' => 10]);
        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function ($table) {
                $table->id();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->string('type')->nullable();
                $table->string('group')->nullable();
                $table->timestamps();
            });
        }
        Setting::set('amount_for_one_coin', 10, 'float', 'payment');

        if (! Schema::hasTable('doctor_bookings')) {
            Schema::create('doctor_bookings', function ($table) {
                $table->id();
                $table->uuid('patient_id')->nullable();
                $table->unsignedInteger('coins_used')->default(0);
                $table->string('payment_status')->nullable();
                $table->string('status')->nullable();
                $table->string('appointment_status')->nullable();
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->string('doctor_id')->nullable();
                $table->uuid('created_by')->nullable();
                $table->uuid('updated_by')->nullable();
                $table->timestamps();
            });
        }

        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyUser')->andReturn(true);
        });
        $this->mock(RewardTierService::class, function ($mock) {
            $mock->shouldReceive('addEarnedCoins')->andReturnNull();
            $mock->shouldReceive('reserveDiscountUsage')->andReturnNull();
            $mock->shouldReceive('confirmDiscountUsage')->andReturnNull();
            $mock->shouldReceive('releaseDiscountUsage')->andReturnNull();
        });
        $this->mock(InvoiceGeneratedDispatcher::class, function ($mock) {
            $mock->shouldReceive('dispatch')->andReturnNull();
        });
        $this->mock(PaymentReceivedDispatcher::class, function ($mock) {
            $mock->shouldReceive('dispatch')->andReturnNull();
        });
        Event::fake([AppointmentBooked::class]);
        $this->bindRazorpayStub();

        $cases = [
            0 => ['coins' => 0, 'paise' => 1115400, 'paid' => 11154.0, 'discount' => 0.0, 'earned' => 112],
            10 => ['coins' => 10, 'paise' => 1105400, 'paid' => 11054.0, 'discount' => 100.0, 'earned' => 111],
            1115 => ['coins' => 1115, 'paise' => 400, 'paid' => 4.0, 'discount' => 11150.0, 'earned' => 0],
        ];

        foreach ($cases as $requested => $expected) {
            $user = $this->member('Page'.$requested, 'HIP92'.str_pad((string) $requested, 3, '0', STR_PAD_LEFT));
            $person = $this->person($user, 'Page', (string) $requested, true);
            Coins::query()->create(['person_id' => $person->id, 'coins' => 5000]);
            Sanctum::actingAs($user);
            $invoice = $this->paymentPageInvoice($person, 11154);

            if ($requested === 10) {
                $openedZero = $this->getJson('/api/hospital/pay-bill-details/'.$invoice->id.'?coins_applied=0')->assertOk();
                $this->assertSame(1115400, (int) $openedZero->json('data.payment.amount'));
            }

            $opened = $this->getJson('/api/hospital/pay-bill-details/'.$invoice->id.'?coins_applied='.$requested)->assertOk();
            $this->assertSame($expected['paise'], (int) $opened->json('data.payment.amount'));
            $payment = $opened->json('data.payment');
            $this->assertIsArray($payment);
            $this->assertArrayHasKey('order_id', $payment);
            $this->assertArrayNotHasKey('orderId', $payment);
            $orderId = (string) $payment['order_id'];
            $this->assertNotSame('', $orderId);
            if ($requested === 10) {
                $this->assertArrayHasKey('order_id', $openedZero->json('data.payment'));
                $this->assertArrayNotHasKey('orderId', $openedZero->json('data.payment'));
                $this->assertNotSame((string) $openedZero->json('data.payment.order_id'), $orderId);
                $this->assertSame('superseded', RazorpayPayment::query()->where('razorpay_order_id', $openedZero->json('data.payment.order_id'))->value('payment_status'));
            }
            $this->assertSame($expected['coins'], (int) RazorpayPayment::query()->where('razorpay_order_id', $orderId)->value('coins_applied'));

            $paid = $this->postJson('/api/invoices/verify-payment', $this->verifyBody((int) $invoice->id, $orderId, 99999));
            $paid->assertOk();
            $this->assertEqualsWithDelta($expected['paid'], (float) $paid->json('data.amount_paid'), 0.001);
            $this->assertSame($expected['coins'], (int) $paid->json('data.coins_applied'));
            $this->assertEqualsWithDelta($expected['discount'], round(11154 - (float) $paid->json('data.amount_paid'), 2), 0.001);
            $this->assertSame(5000 - $expected['coins'] + $expected['earned'], (int) Coins::query()->where('person_id', $person->id)->value('coins'));
        }

        $user = $this->member('Keep', 'HIP92030');
        $person = $this->person($user, 'Keep', 'Coins', true);
        Coins::query()->create(['person_id' => $person->id, 'coins' => 5000]);
        Sanctum::actingAs($user);
        $kept = $this->paymentPageInvoice($person, 11154);
        $withThirty = $this->getJson('/api/hospital/pay-bill-details/'.$kept->id.'?coins_applied=30')->assertOk();
        $plain = $this->getJson('/api/hospital/pay-bill-details/'.$kept->id)->assertOk();
        $this->assertSame($withThirty->json('data.payment.order_id'), $plain->json('data.payment.order_id'));
        $this->assertSame(1085400, (int) $withThirty->json('data.payment.amount'));
        $this->assertSame(1085400, (int) $plain->json('data.payment.amount'));
        $this->assertArrayNotHasKey('orderId', $plain->json('data.payment'));
        $reset = $this->getJson('/api/hospital/pay-bill-details/'.$kept->id.'?coins_applied=0')->assertOk();
        $this->assertNotSame($withThirty->json('data.payment.order_id'), $reset->json('data.payment.order_id'));
        $this->assertSame(1115400, (int) $reset->json('data.payment.amount'));
        $this->assertSame(0, (int) RazorpayPayment::query()->where('razorpay_order_id', $reset->json('data.payment.order_id'))->value('coins_applied'));
        $this->assertSame('superseded', RazorpayPayment::query()->where('razorpay_order_id', $withThirty->json('data.payment.order_id'))->value('payment_status'));

        $user = $this->member('Booked', 'HIP92010');
        $person = $this->person($user, 'Booked', 'Visit', true);
        Coins::query()->create(['person_id' => $person->id, 'coins' => 5000]);
        Sanctum::actingAs($user);
        $booking = new DoctorBooking();
        $booking->stopUserstamping();
        $booking->forceFill([
            'patient_id' => $person->id,
            'coins_used' => 0,
            'payment_status' => 'pending',
            'status' => 'pending',
        ])->save();
        $invoice = $this->paymentPageInvoice($person, 11154);
        $invoice->forceFill(['doctor_booking_id' => $booking->id])->save();

        $opened = $this->getJson('/api/hospital/pay-bill-details/'.$invoice->id.'?coins_applied=10')->assertOk();
        $this->assertSame(1115400, (int) $opened->json('data.payment.amount'));
        $this->assertArrayHasKey('order_id', $opened->json('data.payment'));
        $this->assertArrayNotHasKey('orderId', $opened->json('data.payment'));
        $orderId = (string) $opened->json('data.payment.order_id');
        $this->assertSame(0, (int) RazorpayPayment::query()->where('razorpay_order_id', $orderId)->value('coins_applied'));

        $paid = $this->postJson('/api/invoices/verify-payment', $this->verifyBody((int) $invoice->id, $orderId, 10));
        $paid->assertOk();
        $this->assertEqualsWithDelta(11154.0, (float) $paid->json('data.amount_paid'), 0.001);
        $this->assertSame(0, (int) $paid->json('data.coins_applied'));
        $this->assertSame(5112, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
    }

    public function test_payment_page_and_pharmacy_previews_match_pay_now(): void
    {
        config(['settings.payment.amount_for_one_coin' => 10]);
        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function ($table) {
                $table->id();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->string('type')->nullable();
                $table->string('group')->nullable();
                $table->timestamps();
            });
        }
        Setting::set('amount_for_one_coin', 10, 'float', 'payment');

        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyUser')->andReturn(true);
        });
        $this->bindRazorpayStub();

        $user = $this->member('Page', 'HIP94010');
        $person = $this->person($user, 'Page', 'Bill', true);
        Coins::query()->create(['person_id' => $person->id, 'coins' => 5000]);
        Sanctum::actingAs($user);
        $page = $this->paymentPageInvoice($person, 11154);
        $this->assertNull($page->pharmacy_order_id);

        $pagePreview = $this->getJson('/api/home/user-coins?invoice_id='.$page->id.'&coins_used=10');
        $pagePay = $this->getJson('/api/hospital/pay-bill-details/'.$page->id.'?coins_applied=10');
        $pagePreview->assertOk();
        $pagePay->assertOk();
        $this->assertEqualsWithDelta(11054.0, (float) $pagePreview->json('data.total_amount'), 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $pagePreview->json('data.service_charge'), 0.001);
        $this->assertSame(1105400, (int) $pagePay->json('data.payment.amount'));

        [$hospital, $pharmacy] = $this->place();
        $pharmacyUser = $this->member('Order', 'HIP94044');
        $pharmacyPerson = $this->person($pharmacyUser, 'Order', 'Bill', true);
        Coins::query()->create(['person_id' => $pharmacyPerson->id, 'coins' => 5000]);
        Sanctum::actingAs($pharmacyUser);
        $bill = $this->pendingPharmacyBill($pharmacyUser, $pharmacyPerson, $hospital, $pharmacy, 'PO-347');
        $bill['invoice']->forceFill([
            'amount' => 1375.80,
            'total_gst' => 0,
            'service_charges' => 1,
            'payment_gateway_charges' => 0,
            'total_amount' => 1375.80,
        ])->save();
        $bill['order']->forceFill(['total' => 1375.80])->save();

        $pharmacyPreview = $this->getJson('/api/home/user-coins?invoice_id='.$bill['invoice']->id.'&coins_used=44');
        $pharmacyPay = $this->getJson('/api/hospital/pay-bill-details/'.$bill['invoice']->id.'?coins_applied=44');
        $pharmacyPreview->assertOk();
        $pharmacyPay->assertOk();
        $this->assertEqualsWithDelta(935.80, (float) $pharmacyPreview->json('data.total_amount'), 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $pharmacyPreview->json('data.service_charge'), 0.001);
        $this->assertSame(93580, (int) $pharmacyPay->json('data.payment.amount'));
    }

    public function test_signed_invoices_pay_with_and_without_coins_stops_before_a_razorpay_order(): void
    {
        $this->assertSignedPayDoesNotOpenAnOrder('/api/invoices/%d/pay');
    }

    public function test_signed_payment_requests_pay_with_and_without_coins_stops_before_a_razorpay_order(): void
    {
        $this->assertSignedPayDoesNotOpenAnOrder('/api/payment-requests/%d/pay');
    }

    public function test_pay_invoice_page_with_and_without_coins_returns_the_static_file(): void
    {
        foreach (['/pay/invoice', '/pay/invoice?coins_applied=10'] as $url) {
            $response = $this->get($url);
            $response->assertOk();
            $this->assertStringContainsString('text/html', (string) $response->headers->get('content-type'));
            $html = $response->streamedContent();
            $this->assertStringContainsString('Invoice Payment', $html);
            $this->assertStringContainsString("/api/invoices/' + encodeURIComponent(String(body.invoice_id)) + '/pay'", $html);
            $this->assertStringNotContainsString('verify-payment', $html);
        }

        $this->assertSame(0, RazorpayPayment::query()->count());
    }

    private function assertSignedPayDoesNotOpenAnOrder(string $path): void
    {
        config(['settings.payment.amount_for_one_coin' => 10]);
        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function ($table) {
                $table->id();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->string('type')->nullable();
                $table->string('group')->nullable();
                $table->timestamps();
            });
        }
        Setting::set('amount_for_one_coin', 10, 'float', 'payment');

        $user = $this->member('Signed', 'HIP93001');
        $person = $this->person($user, 'Signed', 'Pay', true);
        Coins::query()->create(['person_id' => $person->id, 'coins' => 5000]);
        Sanctum::actingAs($user);
        $invoice = $this->paymentPageInvoice($person, 11154);
        $token = URL::temporarySignedRoute('payments.invoice.page', now()->addMinutes(30), [
            'invoice_id' => $invoice->id,
        ]);

        foreach ([null, 10] as $coins) {
            $payload = [
                'member_id' => $user->hip_id,
                'primary_person_id' => $person->id,
                'token' => $token,
            ];
            if ($coins !== null) {
                $payload['coins_applied'] = $coins;
            }

            $response = $this->postJson(sprintf($path, $invoice->id), $payload);
            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['primary_person_id']);
        }

        $this->assertSame(0, RazorpayPayment::query()->where('invoice_id', $invoice->id)->count());
        $this->assertSame('pending', $invoice->fresh()->status);
    }

    private function paymentPageInvoice(Persons $person, float $total): Invoice
    {
        $invoice = new Invoice();
        $invoice->stopUserstamping();
        $invoice->forceFill([
            'primary_person_id' => $person->id,
            'person_id' => $person->id,
            'status' => 'pending',
            'amount' => $total,
            'total_gst' => 0,
            'service_charges' => 1,
            'payment_gateway_charges' => 0,
            'discount_price' => 0,
            'coins_applied' => 0,
            'total_amount' => $total,
        ])->save();

        return $invoice;
    }

    /**
     * @return array{order: PharmacyOrder, invoice: Invoice}
     */
    private function pendingPharmacyBill(HIPUser $user, Persons $person, Hospital $hospital, Pharmacy $pharmacy, string $orderNumber): array
    {
        $order = PharmacyOrder::withoutEvents(function () use ($user, $person, $hospital, $pharmacy, $orderNumber) {
            $order = new PharmacyOrder();
            $order->stopUserstamping();
            $order->forceFill([
                'order_number' => $orderNumber,
                'hip_user_id' => $user->id,
                'person_id' => $person->id,
                'hospital_id' => $hospital->id,
                'pharmacy_id' => $pharmacy->id,
                'status' => 'pending_payment',
                'payment_status' => 'unpaid',
                'subtotal' => 1000,
                'discount' => 0,
                'delivery_fee' => 0,
                'tax' => 50,
                'service_charge' => 30,
                'gateway_charge' => 20,
                'total' => 1100,
            ])->save();

            return $order;
        });

        $invoice = new Invoice();
        $invoice->stopUserstamping();
        $invoice->forceFill([
            'primary_person_id' => $person->id,
            'person_id' => $person->id,
            'pharmacy_order_id' => $order->id,
            'status' => 'pending',
            'amount' => 1000,
            'total_gst' => 50,
            'service_charges' => 30,
            'payment_gateway_charges' => 20,
            'discount_price' => 0,
            'total_amount' => 1100,
        ])->save();

        return ['order' => $order, 'invoice' => $invoice];
    }

    private function verifyBody(int $invoiceId, string $orderId, int $coinsApplied): array
    {
        return [
            'invoice_id' => $invoiceId,
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => 'pay_'.$orderId,
            'razorpay_signature' => 'sig_'.$orderId,
            'coins_applied' => $coinsApplied,
        ];
    }

    private function bindRazorpayStub(): void
    {
        $GLOBALS['pharmacy_rzp_seq'] = 0;
        unset($GLOBALS['pharmacy_rzp_reject_signature'], $GLOBALS['pharmacy_rzp_payment_status'], $GLOBALS['pharmacy_rzp_payment_amount']);
        $order = new class {
            public function create(array $attributes): object
            {
                $GLOBALS['pharmacy_rzp_seq']++;

                return (object) ['id' => 'order_test_'.$GLOBALS['pharmacy_rzp_seq']];
            }
        };
        $utility = new class {
            public function verifyPaymentSignature(array $attributes): void
            {
                if (! empty($GLOBALS['pharmacy_rzp_reject_signature'])) {
                    throw new \Razorpay\Api\Errors\SignatureVerificationError('Invalid signature passed');
                }
            }
        };
        $payment = new class {
            public ?string $method = 'upi';
            public $card_id = null;
            public $bank = null;
            public $wallet = null;
            public $vpa = null;
            public int $fee = 0;
            public int $tax = 0;
            public ?string $status = 'captured';
            public ?int $amount = null;

            public function fetch(string $paymentId): self
            {
                if (array_key_exists('pharmacy_rzp_payment_status', $GLOBALS)) {
                    $this->status = $GLOBALS['pharmacy_rzp_payment_status'];
                }
                if (array_key_exists('pharmacy_rzp_payment_amount', $GLOBALS)) {
                    $this->amount = $GLOBALS['pharmacy_rzp_payment_amount'] === null
                        ? null
                        : (int) $GLOBALS['pharmacy_rzp_payment_amount'];
                }

                return $this;
            }
        };
        $api = new class($order, $utility, $payment) extends Api {
            public function __construct(private object $orderEntity, private object $utilityEntity, private object $paymentEntity)
            {
            }

            public function __get($name)
            {
                return match ($name) {
                    'order' => $this->orderEntity,
                    'utility' => $this->utilityEntity,
                    'payment' => $this->paymentEntity,
                    default => null,
                };
            }
        };

        $service = app(PaymentApiService::class);
        $client = new ReflectionProperty(PaymentApiService::class, 'razorpayApi');
        $client->setValue($service, $api);
        $key = new ReflectionProperty(PaymentApiService::class, 'razorpayKeyId');
        $key->setValue($service, 'rzp_test');
        app()->instance(PaymentApiService::class, $service);
    }

    private function pdfText(string $binary): string
    {
        $text = '';
        if (! preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $binary, $matches)) {
            return $binary;
        }

        foreach ($matches[1] as $stream) {
            $decoded = @gzuncompress($stream);
            if (! is_string($decoded)) {
                $decoded = @gzinflate($stream);
            }
            if (! is_string($decoded)) {
                continue;
            }
            if (preg_match_all('/\((?:\\\\.|[^\\\\)])*\)/s', $decoded, $parts)) {
                foreach ($parts[0] as $part) {
                    $literal = stripcslashes(substr($part, 1, -1));
                    if (str_contains($literal, "\0")) {
                        $converted = mb_convert_encoding($literal, 'UTF-8', 'UTF-16BE');
                        $literal = is_string($converted) ? $converted : $literal;
                    }
                    $text .= ' '.$literal;
                }
            }
        }

        return $text;
    }

    private function bootSchema(): void
    {
        if (self::$schemaReady && Schema::hasTable('pharmacy_orders')) {
            return;
        }

        Schema::create('healthinpocket_users', function ($table) {
            $table->uuid('id')->primary();
            $table->string('email')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('password')->nullable();
            $table->string('hip_id')->nullable();
            $table->string('mobile_num')->nullable();
            $table->string('house_number')->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('hospital_id')->nullable();
            $table->timestamps();
        });

        Schema::create('coins', function ($table) {
            $table->id();
            $table->uuid('person_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->integer('coins')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('persons', function ($table) {
            $table->uuid('id')->primary();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('mobile')->nullable();
            $table->text('address')->nullable();
            $table->uuid('hip_user_id')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('relationship')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hospitals', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('pharmacy_ids')->nullable();
            $table->timestamps();
        });

        Schema::create('pharmacies', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('pharmacy_id')->nullable();
            $table->string('address')->nullable();
            $table->string('opening_time')->nullable();
            $table->string('closing_time')->nullable();
            $table->string('logo')->nullable();
            $table->string('status')->default('inactive');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('documents', function ($table) {
            $table->id();
            $table->uuid('member_id')->nullable();
            $table->uuid('patient_id')->nullable();
            $table->string('document_name')->nullable();
            $table->string('document_path')->nullable();
            $table->string('document_type')->nullable();
            $table->string('document_size')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function ($table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('status')->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->nullable();
            $table->text('service_types')->nullable();
            $table->text('invoice_details')->nullable();
            $table->decimal('transaction_amount', 12, 2)->default(0);
            $table->decimal('service_charges', 12, 2)->default(0);
            $table->decimal('payment_gateway_charges', 12, 2)->default(0);
            $table->decimal('total_gst', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('razorpay_payments', function ($table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('razorpay_order_id')->nullable();
            $table->string('razorpay_payment_id')->nullable();
            $table->string('razorpay_signature')->nullable();
            $table->string('payment_method')->nullable();
            $table->unsignedInteger('coins_applied')->default(0);
            $table->string('currency')->nullable();
            $table->string('bank')->nullable();
            $table->string('wallet')->nullable();
            $table->string('vpa')->nullable();
            $table->string('card_last4')->nullable();
            $table->string('card_network')->nullable();
            $table->decimal('razorpay_fee', 12, 2)->nullable();
            $table->decimal('razorpay_tax', 12, 2)->nullable();
            $table->string('payment_status')->nullable();
            $table->decimal('amount_paid', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function ($table) {
            $table->id();
            $table->uuid('primary_person_id')->nullable();
            $table->uuid('person_id')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('amount', 12, 2)->nullable();
            $table->decimal('total_gst', 12, 2)->nullable();
            $table->decimal('service_charges', 12, 2)->nullable();
            $table->decimal('payment_gateway_charges', 12, 2)->nullable();
            $table->decimal('discount_price', 12, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->unsignedInteger('coins_applied')->default(0);
            $table->unsignedInteger('coins_earned')->default(0);
            $table->unsignedBigInteger('doctor_booking_id')->nullable();
            $table->unsignedBigInteger('second_opinion_id')->nullable();
            $table->unsignedBigInteger('diagnostic_test_booking_id')->nullable();
            $table->text('service_types')->nullable();
            $table->text('invoice_details')->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->unsignedBigInteger('pharmacy_order_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('pharmacy_orders', function ($table) {
            $table->id();
            $table->string('order_number', 30)->unique();
            $table->uuid('hip_user_id');
            $table->uuid('person_id')->nullable();
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('pharmacy_id');
            $table->unsignedBigInteger('prescription_document_id')->nullable();
            $table->string('status', 30)->default('requested');
            $table->string('payment_status', 20)->default('unpaid');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('service_charge', 12, 2)->default(0);
            $table->decimal('gateway_charge', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->unsignedInteger('coins_applied')->nullable();
            $table->decimal('coins_discount', 12, 2)->nullable();
            $table->decimal('amount_paid', 12, 2)->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->text('delivery_name')->nullable();
            $table->text('delivery_phone')->nullable();
            $table->text('delivery_address')->nullable();
            $table->string('delivery_partner')->nullable();
            $table->string('rider_name')->nullable();
            $table->string('tracking_ref')->nullable();
            $table->timestamp('expected_delivery_at')->nullable();
            $table->uuid('assigned_pharmacist_id')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->uuid('cancelled_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('in_review_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('out_for_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('pharmacy_order_items', function ($table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('pharmacy_product_id')->nullable();
            $table->string('name')->nullable();
            $table->string('dosage_form')->nullable();
            $table->string('strength')->nullable();
            $table->unsignedInteger('qty');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->boolean('is_custom')->default(false);
            $table->unsignedBigInteger('medicine_master_id')->nullable();
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable();
            $table->string('duration')->nullable();
            $table->string('when_to_take')->nullable();
            $table->text('special_instruction')->nullable();
            $table->timestamps();
        });

        Schema::create('pharmacy_order_comments', function ($table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('author_type', 20);
            $table->uuid('author_id')->nullable();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pharmacy_order_status_logs', function ($table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('actor_type', 20);
            $table->uuid('actor_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('roles', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('model_has_roles', function ($table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->uuid('model_id');
        });

        self::$schemaReady = true;
    }
}
