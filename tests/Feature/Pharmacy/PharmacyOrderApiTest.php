<?php

namespace Tests\Feature\Pharmacy;

use App\Enums\PharmacyOrderStatus;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\Coins;
use App\Models\Document;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\Pharmacy;
use App\Models\PharmacyOrder;
use App\Models\RazorpayPayment;
use App\Services\Pharmacy\PharmacyBillCalculator;
use App\Services\Pharmacy\PharmacyOrderService;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
            $table->timestamps();
        });

        Schema::create('razorpay_payments', function ($table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('payment_method')->nullable();
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
