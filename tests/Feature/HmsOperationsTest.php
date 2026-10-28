<?php

namespace Tests\Feature;

use App\Models\HmsIntegration;
use App\Models\HmsWebhookInbox;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class HmsOperationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->fail('HMS operation tests must use the sqlite testing database.');
        }

        $this->bootHmsTables();
        $this->ensureEntityTables();
        DB::table('hms_webhook_inbox')->delete();
        DB::table('hms_external_id_maps')->delete();
        DB::table('hms_integrations')->delete();
    }

    public function test_integration_commands_store_a_hash_and_hide_the_secret(): void
    {
        $this->assertSame(0, Artisan::call('hms:integration:create', [
            'name' => 'City HMS',
            '--hospitals' => '1,2',
            '--organization' => '9',
            '--ips' => '10.1.1.1, 10.1.1.2',
            '--rate' => '40',
        ]));
        $created = Artisan::output();
        $this->assertMatchesRegularExpression('/API key: (hms_\S+)/', $created, $created);
        $this->assertMatchesRegularExpression('/Signing secret: (\S+)/', $created);
        preg_match('/API key: (hms_\S+)/', $created, $keyMatch);
        preg_match('/Signing secret: (\S+)/', $created, $secretMatch);
        $apiKey = $keyMatch[1];
        $secret = $secretMatch[1];

        $integration = HmsIntegration::query()->where('name', 'City HMS')->first();
        $this->assertNotNull($integration);
        $this->assertSame(HmsIntegration::hashKey($apiKey), $integration->api_key_hash);
        $this->assertNotSame($apiKey, $integration->api_key_hash);
        $this->assertSame($secret, $integration->signing_secret);
        $this->assertSame([1, 2], $integration->hospital_ids);
        $this->assertSame(9, (int) $integration->organization_id);
        $this->assertSame(['10.1.1.1', '10.1.1.2'], $integration->allowed_ips);
        $this->assertSame(40, (int) $integration->rate_limit_per_minute);
        $this->assertTrue($integration->is_enabled);

        Artisan::call('hms:integration:list');
        $listed = Artisan::output();
        $this->assertStringContainsString('City HMS', $listed);
        $this->assertStringContainsString('yes', $listed);
        $this->assertStringNotContainsString($apiKey, $listed);
        $this->assertStringNotContainsString($secret, $listed);
        $this->assertStringNotContainsString($integration->api_key_hash, $listed);

        Artisan::call('hms:integration:rotate-secret', ['id' => $integration->getKey()]);
        $rotated = Artisan::output();
        preg_match('/Signing secret: (\S+)/', $rotated, $rotatedMatch);
        $integration->refresh();
        $this->assertNotSame($secret, $integration->signing_secret);
        $this->assertSame($rotatedMatch[1], $integration->signing_secret);
        $this->assertStringNotContainsString($apiKey, $rotated);

        Artisan::call('hms:integration:disable', ['id' => $integration->getKey()]);
        $this->assertFalse($integration->fresh()->is_enabled);
        Artisan::call('hms:integration:enable', ['id' => $integration->getKey()]);
        $this->assertTrue($integration->fresh()->is_enabled);

        $this->assertSame(1, Artisan::call('hms:integration:rotate-secret', ['id' => 999999]));
        $this->assertSame(1, Artisan::call('hms:integration:create', [
            'name' => 'Bad rate',
            '--rate' => '0',
        ]));
    }

    public function test_map_import_skips_missing_local_ids_and_upserts(): void
    {
        $integration = HmsIntegration::query()->create([
            'name' => 'Map HMS',
            'hospital_ids' => [1],
            'api_key_hash' => HmsIntegration::hashKey('hms_map_key'),
            'signing_secret' => 'map-secret',
            'is_enabled' => true,
            'rate_limit_per_minute' => 120,
        ]);

        $doctorId = (string) Str::uuid();
        DB::table('hospitals')->updateOrInsert(['id' => 81], $this->stamp('hospitals', ['name' => 'Map Hospital']));
        DB::table('doctors')->updateOrInsert(['id' => $doctorId], $this->stamp('doctors', ['name' => 'Map Doctor']));
        DB::table('diagnostic_packages')->updateOrInsert(['id' => 81], $this->stamp('diagnostic_packages', ['name' => 'Map Package']));
        DB::table('family_packages')->updateOrInsert(['id' => 81], $this->stamp('family_packages', [
            'name' => 'Map Plan',
            'price' => 100,
            'duration_days' => 30,
        ]));

        $path = storage_path('app/hms-map-import-test.csv');
        file_put_contents($path, implode("\n", [
            'entity_type,external_id,local_id',
            'hospital,H-EXT,81',
            'doctor,D-EXT,'.$doctorId,
            'package,P-EXT,81',
            'plan,PLAN-EXT,81',
            'patient,PT-EXT,person-1',
            'hospital,MISSING,99999',
            'widget,X,81',
            'hospital,BLANK,,',
            '',
            'hospital,H-EXT,81',
        ]));

        $this->assertSame(0, Artisan::call('hms:map:import', [
            'integration_id' => $integration->getKey(),
            'file' => $path,
        ]));
        $output = Artisan::output();
        $this->assertStringContainsString('Created: 5', $output);
        $this->assertStringContainsString('Updated: 1', $output);
        $this->assertStringContainsString('Skipped: 3', $output);
        $this->assertStringContainsString('hospital local_id 99999 was not found', $output);
        $this->assertStringContainsString('entity_type "widget" is not allowed', $output);
        $this->assertSame(0, DB::table('hms_external_id_maps')->where('external_id', 'MISSING')->count());
        $this->assertSame('81', DB::table('hms_external_id_maps')->where('external_id', 'H-EXT')->value('local_id'));
        $this->assertSame('person-1', DB::table('hms_external_id_maps')->where('entity_type', 'patient')->value('local_id'));

        Artisan::call('hms:map:list', ['integration_id' => $integration->getKey()]);
        $listed = Artisan::output();
        $this->assertStringContainsString('H-EXT', $listed);
        $this->assertStringContainsString('PLAN-EXT', $listed);
        $this->assertStringNotContainsString('99999', $listed);
        $this->assertStringNotContainsString('map-secret', $listed);

        @unlink($path);
    }

    public function test_inbox_prune_deletes_only_old_rows_and_is_scheduled_daily(): void
    {
        $integration = HmsIntegration::query()->create([
            'name' => 'Prune HMS',
            'hospital_ids' => [1],
            'api_key_hash' => HmsIntegration::hashKey('hms_prune_key'),
            'signing_secret' => 'prune-secret',
            'is_enabled' => true,
            'rate_limit_per_minute' => 120,
        ]);

        $this->insertInbox($integration->getKey(), now()->subDays(91)->toDateTimeString());
        $recentId = $this->insertInbox($integration->getKey(), now()->subDays(10)->toDateTimeString());

        $this->assertSame(0, Artisan::call('hms:inbox:prune', ['--days' => 90]));
        $this->assertStringContainsString('Pruned 1 inbox row(s) older than 90 days.', Artisan::output());
        $this->assertSame([$recentId], HmsWebhookInbox::query()->pluck('id')->map(fn ($id) => (int) $id)->all());

        $this->assertSame(1, Artisan::call('hms:inbox:prune', ['--days' => 0]));

        $event = collect(app(Schedule::class)->events())
            ->first(fn ($scheduled) => str_contains((string) $scheduled->command, 'hms:inbox:prune'));
        $this->assertNotNull($event);
        $this->assertSame('10 1 * * *', $event->expression);
    }

    private function insertInbox(int $integrationId, string $receivedAt): int
    {
        return (int) DB::table('hms_webhook_inbox')->insertGetId([
            'integration_id' => $integrationId,
            'request_id' => (string) Str::uuid(),
            'event_type' => 'client.request',
            'method' => 'GET',
            'path' => '/apiv1/client/get/patient/membership',
            'headers' => json_encode([]),
            'payload' => json_encode([]),
            'state' => 'processed',
            'received_at' => $receivedAt,
        ]);
    }

    private function bootHmsTables(): void
    {
        Schema::dropIfExists('hms_webhook_inbox');
        Schema::dropIfExists('hms_external_id_maps');
        Schema::dropIfExists('hms_integrations');

        Schema::create('hms_integrations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->json('hospital_ids');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->char('api_key_hash', 64)->unique();
            $table->text('signing_secret');
            $table->json('allowed_ips')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('rate_limit_per_minute')->default(120);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hms_external_id_maps', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('integration_id');
            $table->string('entity_type', 32);
            $table->string('external_id');
            $table->string('local_id', 64);
            $table->timestamps();
            $table->unique(['integration_id', 'entity_type', 'external_id'], 'hms_external_id_maps_lookup_unique');
        });

        Schema::create('hms_webhook_inbox', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('integration_id')->nullable();
            $table->uuid('request_id');
            $table->string('event_id')->nullable();
            $table->string('event_type', 100);
            $table->string('method', 10);
            $table->string('path');
            $table->json('headers');
            $table->json('payload');
            $table->string('state', 32)->default('received');
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_body')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
        });
    }

    private function ensureEntityTables(): void
    {
        if (! Schema::hasTable('hospitals')) {
            Schema::create('hospitals', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('doctors')) {
            Schema::create('doctors', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('diagnostic_packages')) {
            Schema::create('diagnostic_packages', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('family_packages')) {
            Schema::create('family_packages', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->decimal('price', 10, 2)->default(0);
                $table->unsignedInteger('duration_days')->default(30);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (Schema::hasTable('family_packages') && ! Schema::hasColumn('family_packages', 'deleted_at')) {
            Schema::table('family_packages', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function stamp(string $table, array $values): array
    {
        if (Schema::hasColumn($table, 'created_at')) {
            $values['created_at'] = now();
            $values['updated_at'] = now();
        }

        return $values;
    }
}
