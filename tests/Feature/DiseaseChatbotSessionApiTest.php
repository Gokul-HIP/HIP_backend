<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\DiseaseChatbotSession;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Persons;
use App\Models\SpecialitiesMaster;
use App\Models\Speciality;
use App\Services\DiseaseSymptomMatcher;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DiseaseChatbotSessionApiTest extends TestCase
{
    private static bool $schemaReady = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->fail('Disease chatbot tests must use the sqlite testing database.');
        }

        $this->bootSchema();
        $this->withoutMiddleware(EnsureAccountIsActive::class);
        config([
            'chatbot.disease_enabled' => true,
            'chatbot.ai_matching_enabled' => false,
        ]);
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-10-16 12:00:00', 'Asia/Kolkata'));

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

    public function test_start_session_returns_menu(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/chatbot/sessions', [
            'hospital_id' => $hospital->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 200)
            ->assertJsonPath('state', 'MENU')
            ->assertJsonPath('options.0.id', 'check_disease')
            ->assertJsonPath('options.0.style.bg', '#0da2e7')
            ->assertJsonPath('allow_back', false)
            ->assertJsonPath('allow_restart', true);

        $this->assertNotEmpty($response->json('session_id'));
        $this->assertSame('bot', $response->json('messages.0.sender'));
    }

    public function test_wrong_person_returns_403_and_missing_hospital_422(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        $stranger = $this->member('stranger@example.com', 'HIPSTR01');
        $strangerPerson = $this->person($stranger, true);

        Sanctum::actingAs($user);

        $this->postJson('/api/chatbot/sessions', [
            'hospital_id' => $hospital->id,
            'person_id' => $strangerPerson->id,
        ])->assertStatus(403);

        $this->postJson('/api/chatbot/sessions', [
            'hospital_id' => 99999,
        ])->assertStatus(422);
    }

    public function test_happy_path_menu_to_results_yes_and_no(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        Sanctum::actingAs($user);

        $start = $this->postJson('/api/chatbot/sessions', ['hospital_id' => $hospital->id])->assertOk();
        $sessionId = $start->json('session_id');

        $ask = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ])->assertOk();

        $this->assertSame('ASK_SYMPTOMS', $ask->json('state'));
        $this->assertSame('symptom_picker', $ask->json('messages.1.type'));

        $results = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => [
                'type' => 'symptoms',
                'value' => ['Wheeze', 'COUGH'],
            ],
        ])->assertOk();

        $this->assertSame('ASK_CONSULT', $results->json('state'));
        $this->assertSame('disease_cards', $results->json('messages.0.type'));
        $this->assertSame('This is not a medical diagnosis.', $results->json('messages.0.disclaimer'));
        $this->assertLessThanOrEqual(3, count($results->json('messages.0.items')));
        $this->assertStringContainsString(':', (string) $results->json('messages.0.items.0.id'));
        $this->assertContains($results->json('messages.0.items.0.confidence'), ['high', 'medium', 'low']);

        $yes = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'yes'],
        ])->assertOk();

        $this->assertSame('ASK_CONSULT', $yes->json('state'));
        $this->assertStringContainsString('No doctors are available', (string) $yes->json('messages.0.text'));

        // Second session path ending in No
        $start2 = $this->postJson('/api/chatbot/sessions', ['hospital_id' => $hospital->id])->assertOk();
        $id2 = $start2->json('session_id');
        $this->postJson('/api/chatbot/sessions/'.$id2.'/reply', [
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ])->assertOk();
        $this->postJson('/api/chatbot/sessions/'.$id2.'/reply', [
            'input' => ['type' => 'text', 'value' => 'I have a cough and wheeze today'],
        ])->assertOk();
        $no = $this->postJson('/api/chatbot/sessions/'.$id2.'/reply', [
            'input' => ['type' => 'option', 'value' => 'no'],
        ])->assertOk();

        $this->assertSame('COMPLETED', $no->json('state'));
        $completedText = (string) $no->json('messages.0.text');
        $this->assertStringContainsString('please see a doctor', mb_strtolower($completedText));
        $this->assertStringContainsString('Tests a clinician may suggest:', $completedText);
        $this->assertStringContainsString('not a medical diagnosis', mb_strtolower($completedText));
        $this->assertStringNotContainsString('rest', mb_strtolower($completedText));
        $this->assertStringNotContainsString('hydrated', mb_strtolower($completedText));
    }

    public function test_back_and_restart(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        Sanctum::actingAs($user);

        $sessionId = $this->postJson('/api/chatbot/sessions', ['hospital_id' => $hospital->id])
            ->json('session_id');

        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ])->assertJsonPath('state', 'ASK_SYMPTOMS');

        $back = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'back'],
        ])->assertOk();

        $this->assertSame('MENU', $back->json('state'));

        $restart = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/restart')->assertOk();
        $this->assertSame('MENU', $restart->json('state'));
        $this->assertSame('check_disease', $restart->json('options.0.id'));
    }

    public function test_expiry_returns_410(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        Sanctum::actingAs($user);

        $sessionId = $this->postJson('/api/chatbot/sessions', ['hospital_id' => $hospital->id])
            ->json('session_id');

        Carbon::setTestNow(Carbon::parse('2026-10-16 12:31:00', 'Asia/Kolkata'));

        $expired = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ]);

        $expired->assertStatus(410)
            ->assertJsonPath('code', 'session_expired')
            ->assertJsonPath('status', 410);

        $this->assertStringContainsString('restart', (string) $expired->json('hint'));
    }

    public function test_another_users_session_is_404(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        $other = $this->member('other@example.com', 'HIPOTH01');
        Sanctum::actingAs($user);

        $sessionId = $this->postJson('/api/chatbot/sessions', ['hospital_id' => $hospital->id])
            ->json('session_id');

        Sanctum::actingAs($other);
        $this->getJson('/api/chatbot/sessions/'.$sessionId)->assertNotFound();
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ])->assertNotFound();
    }

    public function test_hospital_without_enabled_specialities_gets_friendly_message(): void
    {
        $user = $this->member();
        $this->person($user, true);
        $hospital = $this->hospital('No Specs');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/chatbot/sessions', [
            'hospital_id' => $hospital->id,
        ])->assertOk();

        $this->assertSame('DISABLED', $response->json('state'));
        $this->assertNotEmpty($response->json('session_id'));
        $this->assertDatabaseHas('disease_chatbot_sessions', [
            'id' => $response->json('session_id'),
            'state' => 'DISABLED',
        ]);
        $this->assertSame(
            'This hospital has not enabled the disease checker yet',
            $response->json('messages.0.text')
        );
        $this->assertSame([], $response->json('options'));
        $this->assertSame('none', $response->json('input.mode'));
    }

    public function test_duplicate_client_message_id_is_idempotent(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        Sanctum::actingAs($user);

        $sessionId = $this->postJson('/api/chatbot/sessions', ['hospital_id' => $hospital->id])
            ->json('session_id');

        $first = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'client_message_id' => 'client-1',
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ])->assertOk();

        $second = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'client_message_id' => 'client-1',
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ])->assertOk();

        $this->assertSame($first->json('state'), $second->json('state'));
        $this->assertSame($first->json('messages'), $second->json('messages'));
        $this->assertSame('ASK_SYMPTOMS', DiseaseChatbotSession::query()->find($sessionId)?->state);
    }

    public function test_mixed_case_and_unknown_symptoms(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        Sanctum::actingAs($user);

        $sessionId = $this->postJson('/api/chatbot/sessions', ['hospital_id' => $hospital->id])
            ->json('session_id');
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ]);

        $unknown = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'symptoms', 'value' => ['totally-unknown-xyz']],
        ])->assertOk();

        $this->assertSame('ASK_SYMPTOMS', $unknown->json('state'));
        $this->assertStringContainsString("couldn't match", (string) $unknown->json('messages.0.text'));
        $this->assertStringContainsString('totally-unknown-xyz', (string) $unknown->json('messages.0.text'));

        $matched = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'text', 'value' => 'Patient has COUGH and mild WHEEZE'],
        ])->assertOk();

        $this->assertSame('ASK_CONSULT', $matched->json('state'));
        $this->assertNotEmpty($matched->json('messages.0.items'));
    }

    public function test_symptom_ids_and_labels_are_both_accepted(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        Sanctum::actingAs($user);

        $sessionId = $this->postJson('/api/chatbot/sessions', ['hospital_id' => $hospital->id])
            ->json('session_id');
        $ask = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ])->assertOk();

        $picker = collect($ask->json('messages'))->firstWhere('type', 'symptom_picker');
        $wheeze = collect($picker['items'])->firstWhere('label', 'Wheeze');
        $this->assertSame('wheeze', $wheeze['id']);

        $results = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => [
                'type' => 'symptoms',
                'value' => ['wheeze', 'Cough'],
            ],
        ])->assertOk();

        $this->assertSame('ASK_CONSULT', $results->json('state'));
        $this->assertSame('disease_cards', $results->json('messages.0.type'));
        $this->assertMatchesRegularExpression('/^\d+:[a-z0-9-]+$/', (string) $results->json('messages.0.items.0.id'));
    }

    public function test_red_flag_enters_emergency_notice_then_continue_anyway(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        Sanctum::actingAs($user);

        $sessionId = $this->postJson('/api/chatbot/sessions', ['hospital_id' => $hospital->id])
            ->json('session_id');
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ]);

        $emergency = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'symptoms', 'value' => ['Chest Pain']],
        ])->assertOk();

        $this->assertSame('EMERGENCY_NOTICE', $emergency->json('state'));
        $this->assertSame('emergency', $emergency->json('messages.0.type'));
        $this->assertSame('danger', $emergency->json('messages.0.variant'));
        $this->assertSame('medical', $emergency->json('messages.0.red_flag_type'));
        $this->assertSame('112', $emergency->json('messages.0.emergency_number'));
        $this->assertSame('call_emergency', $emergency->json('messages.0.button.id'));
        $this->assertSame('tel:112', $emergency->json('messages.0.button.action'));
        $this->assertSame('danger', $emergency->json('messages.0.button.variant'));
        $this->assertSame('continue_anyway', $emergency->json('options.0.id'));
        $this->assertNull($emergency->json('input.helper_text'));

        $continued = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'continue_anyway'],
        ])->assertOk();

        $this->assertSame('ASK_CONSULT', $continued->json('state'));
        $this->assertSame('emergency', $continued->json('messages.0.type'));
        $this->assertSame('disease_cards', $continued->json('messages.1.type'));
    }

    public function test_feature_flag_off_returns_503_on_all_endpoints(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        Sanctum::actingAs($user);
        config(['chatbot.disease_enabled' => true]);

        $sessionId = $this->postJson('/api/chatbot/sessions', ['hospital_id' => $hospital->id])
            ->json('session_id');

        config(['chatbot.disease_enabled' => false]);

        foreach ([
            fn () => $this->postJson('/api/chatbot/sessions', ['hospital_id' => $hospital->id]),
            fn () => $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
                'input' => ['type' => 'option', 'value' => 'check_disease'],
            ]),
            fn () => $this->getJson('/api/chatbot/sessions/'.$sessionId),
            fn () => $this->postJson('/api/chatbot/sessions/'.$sessionId.'/restart'),
        ] as $call) {
            $call()
                ->assertStatus(503)
                ->assertJsonPath('status', 503)
                ->assertJsonPath('code', 'feature_disabled');
        }

        config(['chatbot.disease_enabled' => true]);
        $this->getJson('/api/chatbot/sessions/'.$sessionId)->assertOk();
    }

    public function test_get_session_returns_history(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        Sanctum::actingAs($user);

        $sessionId = $this->postJson('/api/chatbot/sessions', ['hospital_id' => $hospital->id])
            ->json('session_id');
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ]);

        $show = $this->getJson('/api/chatbot/sessions/'.$sessionId)->assertOk();
        $this->assertSame('ASK_SYMPTOMS', $show->json('state'));
        $this->assertGreaterThanOrEqual(2, count($show->json('messages')));
    }

    public function test_ai_valid_json_returns_disease_cards(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        $asthmaId = $this->diseaseCardId('Asthma');
        Sanctum::actingAs($user);
        $this->enableAiMatching();
        $this->fakeOpenRouterJson([
            'matches' => [
                ['id' => $asthmaId, 'score' => 88, 'matched_symptoms' => ['whistling breath']],
            ],
            'red_flag' => false,
        ]);

        $sessionId = $this->startAskSymptoms($hospital->id);
        $response = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'text', 'value' => 'whistling breath at night'],
        ])->assertOk();

        $this->assertSame('ASK_CONSULT', $response->json('state'));
        $this->assertSame('disease_cards', $response->json('messages.0.type'));
        $this->assertSame($asthmaId, $response->json('messages.0.items.0.id'));
        $this->assertSame('high', $response->json('messages.0.items.0.confidence'));
        $this->assertArrayNotHasKey('match_source', $response->json());
        $this->assertSame('ai', DiseaseChatbotSession::query()->find($sessionId)?->context['match_source'] ?? null);
        Http::assertSentCount(1);
    }

    public function test_ai_hallucinated_id_is_dropped(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        $asthmaId = $this->diseaseCardId('Asthma');
        Sanctum::actingAs($user);
        $this->enableAiMatching();
        $this->fakeOpenRouterJson([
            'matches' => [
                ['id' => '999:not-real', 'score' => 99, 'matched_symptoms' => ['x']],
                ['id' => $asthmaId, 'score' => 70, 'matched_symptoms' => ['wheeze-like']],
            ],
            'red_flag' => false,
        ]);

        $sessionId = $this->startAskSymptoms($hospital->id);
        $response = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'text', 'value' => 'wheeze-like breathing'],
        ])->assertOk();

        $ids = collect($response->json('messages.0.items'))->pluck('id')->all();
        $this->assertSame([$asthmaId], $ids);
    }

    public function test_ai_invalid_json_falls_back_to_keyword(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        Sanctum::actingAs($user);
        $this->enableAiMatching();
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => 'NOT_JSON{{{']]],
                'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1, 'total_tokens' => 2],
            ], 200),
        ]);

        $sessionId = $this->startAskSymptoms($hospital->id);
        // Free text triggers AI; keyword still matches Cough from the text.
        $response = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'text', 'value' => 'I have a bad Cough today'],
        ])->assertOk();

        $this->assertSame('ASK_CONSULT', $response->json('state'));
        $this->assertSame('disease_cards', $response->json('messages.0.type'));
        $this->assertSame('keyword', DiseaseChatbotSession::query()->find($sessionId)?->context['match_source'] ?? null);
    }

    public function test_ai_timeout_falls_back_to_keyword(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        Sanctum::actingAs($user);
        $this->enableAiMatching();
        Http::fake(function () {
            throw new ConnectionException('cURL error 28: Operation timed out after 10001 milliseconds');
        });

        $sessionId = $this->startAskSymptoms($hospital->id);
        $response = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'text', 'value' => 'persistent Cough'],
        ])->assertOk();

        $this->assertSame('ASK_CONSULT', $response->json('state'));
        $this->assertSame('keyword', DiseaseChatbotSession::query()->find($sessionId)?->context['match_source'] ?? null);
    }

    public function test_ai_flag_off_sends_no_http_request(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        Sanctum::actingAs($user);
        config([
            'chatbot.ai_matching_enabled' => false,
            'services.openrouter.api_key' => 'test-key',
        ]);
        Http::fake();

        $sessionId = $this->startAskSymptoms($hospital->id);
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'text', 'value' => 'mystery symptom xyz'],
        ])->assertOk();

        Http::assertNothingSent();
    }

    public function test_ai_red_flag_enters_emergency_notice(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        Sanctum::actingAs($user);
        $this->enableAiMatching();
        $this->fakeOpenRouterJson([
            'matches' => [],
            'red_flag' => true,
            'red_flag_type' => 'medical',
        ]);

        $sessionId = $this->startAskSymptoms($hospital->id);
        $response = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'text', 'value' => 'feeling oddly unwell'],
        ])->assertOk();

        $this->assertSame('EMERGENCY_NOTICE', $response->json('state'));
        $this->assertSame('emergency', $response->json('messages.0.type'));
        $this->assertSame('medical', $response->json('messages.0.red_flag_type'));
    }

    public function test_mental_health_red_flag_enters_crisis_emergency(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        Sanctum::actingAs($user);

        $sessionId = $this->startAskSymptoms($hospital->id);
        $response = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'text', 'value' => 'I want to die'],
        ])->assertOk();

        $this->assertSame('EMERGENCY_NOTICE', $response->json('state'));
        $this->assertSame('emergency', $response->json('messages.0.type'));
        $this->assertSame('crisis', $response->json('messages.0.variant'));
        $this->assertSame('mental_health', $response->json('messages.0.red_flag_type'));
        $this->assertSame("You're not alone. Please talk to someone now.", $response->json('messages.0.text'));
        $this->assertSame('call_crisis', $response->json('messages.0.button.id'));
        $this->assertSame('tel:14416', $response->json('messages.0.button.action'));
        $this->assertSame('crisis', $response->json('messages.0.button.variant'));
        $this->assertStringContainsString('Tele-MANAS', (string) $response->json('messages.0.button.label'));
        $this->assertSame('#7c3aed', $response->json('messages.0.button.style.bg'));
        $this->assertSame('#ffffff', $response->json('messages.0.button.style.text'));
        $this->assertSame('#7c3aed', $response->json('messages.0.button.style.border'));
        $this->assertSame('crisis', $response->json('messages.0.button.style.variant'));
        $this->assertSame('call_emergency', $response->json('messages.0.secondary_button.id'));
        $this->assertSame('tel:112', $response->json('messages.0.secondary_button.action'));
        $this->assertSame('danger', $response->json('messages.0.secondary_button.style.variant'));
        $this->assertSame('#dc2626', $response->json('messages.0.secondary_button.style.bg'));
        $this->assertSame('continue_anyway', $response->json('options.0.id'));
        $this->assertNull($response->json('input.helper_text'));
    }

    public function test_ask_symptoms_includes_helper_text(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        Sanctum::actingAs($user);

        $menu = $this->postJson('/api/chatbot/sessions', ['hospital_id' => $hospital->id])->assertOk();
        $this->assertNull($menu->json('input.helper_text'));

        $ask = $this->postJson('/api/chatbot/sessions/'.$menu->json('session_id').'/reply', [
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ])->assertOk();

        $this->assertSame('ASK_SYMPTOMS', $ask->json('state'));
        $this->assertSame(
            "Don't include your name or phone number. Typed text may be analysed by an AI service.",
            $ask->json('input.helper_text')
        );
    }

    public function test_ai_request_body_contains_no_personal_identifiers(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        $asthmaId = $this->diseaseCardId('Asthma');
        $user->forceFill([
            'first_name' => 'SecretFirst',
            'last_name' => 'SecretLast',
            'email' => 'secret.patient@example.com',
            'mobile_num' => '9876543210',
        ])->save();
        Sanctum::actingAs($user);
        $this->enableAiMatching();
        $this->fakeOpenRouterJson([
            'matches' => [
                ['id' => $asthmaId, 'score' => 80, 'matched_symptoms' => ['noisy breathing']],
            ],
            'red_flag' => false,
        ]);

        $sessionId = $this->startAskSymptoms($hospital->id);
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'text', 'value' => 'noisy breathing'],
        ])->assertOk();

        Http::assertSent(function ($request) use ($user, $hospital, $sessionId) {
            $body = $request->body();
            $forbidden = [
                (string) $user->id,
                (string) $sessionId,
                'SecretFirst',
                'SecretLast',
                'secret.patient@example.com',
                '9876543210',
                (string) $hospital->name,
                'Chat Hospital',
            ];
            foreach ($forbidden as $needle) {
                if ($needle !== '' && str_contains($body, $needle)) {
                    return false;
                }
            }

            $json = $request->data();
            $this->assertSame('json_object', data_get($json, 'response_format.type'));
            $this->assertStringContainsString('<symptoms>', (string) data_get($json, 'messages.1.content'));
            $this->assertStringContainsString('<candidates>', (string) data_get($json, 'messages.1.content'));

            return true;
        });
    }

    public function test_ai_cache_hit_makes_one_http_call_across_two_sessions(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        $asthmaId = $this->diseaseCardId('Asthma');
        Sanctum::actingAs($user);
        $this->enableAiMatching();
        $this->fakeOpenRouterJson([
            'matches' => [
                ['id' => $asthmaId, 'score' => 75, 'matched_symptoms' => ['night whistle']],
            ],
            'red_flag' => false,
        ]);

        $s1 = $this->startAskSymptoms($hospital->id);
        $this->postJson('/api/chatbot/sessions/'.$s1.'/reply', [
            'input' => ['type' => 'text', 'value' => 'night whistle'],
        ])->assertOk();

        $s2 = $this->startAskSymptoms($hospital->id);
        $this->postJson('/api/chatbot/sessions/'.$s2.'/reply', [
            'input' => ['type' => 'text', 'value' => 'night whistle'],
        ])->assertOk();

        Http::assertSentCount(1);
        $this->assertSame('ai', DiseaseChatbotSession::query()->find($s2)?->context['match_source'] ?? null);
    }

    public function test_ai_rate_limit_falls_back_to_keyword(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        $asthmaId = $this->diseaseCardId('Asthma');
        Sanctum::actingAs($user);
        $this->enableAiMatching();
        config(['chatbot.ai_max_per_hour' => 1]);
        $this->fakeOpenRouterJson([
            'matches' => [
                ['id' => $asthmaId, 'score' => 80, 'matched_symptoms' => ['odd rasp']],
            ],
            'red_flag' => false,
        ]);

        $s1 = $this->startAskSymptoms($hospital->id);
        $this->postJson('/api/chatbot/sessions/'.$s1.'/reply', [
            'input' => ['type' => 'text', 'value' => 'odd rasp one'],
        ])->assertOk()->assertJsonPath('state', 'ASK_CONSULT');

        $s2 = $this->startAskSymptoms($hospital->id);
        // Different text → cache miss; rate limited → keyword empty → stay ASK_SYMPTOMS
        $second = $this->postJson('/api/chatbot/sessions/'.$s2.'/reply', [
            'input' => ['type' => 'text', 'value' => 'odd rasp two'],
        ])->assertOk();

        $this->assertSame('ASK_SYMPTOMS', $second->json('state'));
        $this->assertStringContainsString("couldn't match", (string) $second->json('messages.0.text'));
        $this->assertSame('keyword', DiseaseChatbotSession::query()->find($s2)?->context['match_source'] ?? null);
        Http::assertSentCount(1);
    }

    public function test_picked_only_symptoms_skip_ai(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        Sanctum::actingAs($user);
        $this->enableAiMatching();
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response(['error' => 'should not call'], 500),
        ]);

        $sessionId = $this->startAskSymptoms($hospital->id);
        $response = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'symptoms', 'value' => ['wheeze', 'cough']],
        ])->assertOk();

        $this->assertSame('ASK_CONSULT', $response->json('state'));
        $this->assertSame('keyword', DiseaseChatbotSession::query()->find($sessionId)?->context['match_source'] ?? null);
        Http::assertNothingSent();
    }

    public function test_score_to_confidence_and_sanitiser(): void
    {
        $matcher = app(DiseaseSymptomMatcher::class);
        $this->assertSame('high', $matcher->scoreToConfidence(70));
        $this->assertSame('medium', $matcher->scoreToConfidence(40));
        $this->assertSame('low', $matcher->scoreToConfidence(15));

        $clean = $matcher->sanitizeTypedText("Hello\x01 world secret@mail.com call 1234567890 more text");
        $this->assertStringNotContainsString('secret@mail.com', $clean);
        $this->assertStringNotContainsString('1234567890', $clean);
        $this->assertStringNotContainsString("\x01", $clean);
        $this->assertLessThanOrEqual(200, mb_strlen($clean));
    }

    public function test_dumps_ai_keyword_emergency_real_json(): void
    {
        [$user, $hospital] = $this->seedEnabledHospital();
        $asthmaId = $this->diseaseCardId('Asthma');
        Sanctum::actingAs($user);
        $dump = [];
        Cache::flush();

        $ask = $this->postJson('/api/chatbot/sessions', ['hospital_id' => $hospital->id])->assertOk();
        $askSession = $ask->json('session_id');
        $askSymptoms = $this->postJson('/api/chatbot/sessions/'.$askSession.'/reply', [
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ])->assertOk();
        $dump['ask_symptoms'] = ['http' => $askSymptoms->getStatusCode(), 'body' => $askSymptoms->json()];

        $this->enableAiMatching();
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::sequence()
                ->push([
                    'id' => 'gen-ai',
                    'model' => 'openai/gpt-4o-mini',
                    'choices' => [[
                        'message' => [
                            'role' => 'assistant',
                            'content' => json_encode([
                                'matches' => [
                                    ['id' => $asthmaId, 'score' => 91, 'matched_symptoms' => ['night whistle']],
                                ],
                                'red_flag' => false,
                                'red_flag_type' => null,
                            ]),
                        ],
                    ]],
                    'usage' => ['prompt_tokens' => 12, 'completion_tokens' => 8, 'total_tokens' => 20],
                ])
                ->push([
                    'id' => 'gen-emergency',
                    'model' => 'openai/gpt-4o-mini',
                    'choices' => [[
                        'message' => [
                            'role' => 'assistant',
                            'content' => json_encode([
                                'matches' => [],
                                'red_flag' => true,
                                'red_flag_type' => 'medical',
                            ]),
                        ],
                    ]],
                    'usage' => ['prompt_tokens' => 3, 'completion_tokens' => 2, 'total_tokens' => 5],
                ]),
        ]);

        $sAi = $this->startAskSymptoms($hospital->id);
        $ai = $this->postJson('/api/chatbot/sessions/'.$sAi.'/reply', [
            'input' => ['type' => 'text', 'value' => 'night whistle'],
        ]);
        $dump['ai'] = ['http' => $ai->getStatusCode(), 'body' => $ai->json()];

        // No API key → keyword fallback, no HTTP consumption from the sequence.
        config(['services.openrouter.api_key' => '']);
        $sKw = $this->startAskSymptoms($hospital->id);
        $kw = $this->postJson('/api/chatbot/sessions/'.$sKw.'/reply', [
            'input' => ['type' => 'text', 'value' => 'I have a Cough'],
        ]);
        $dump['keyword_fallback'] = ['http' => $kw->getStatusCode(), 'body' => $kw->json()];

        config(['services.openrouter.api_key' => 'test-openrouter-key']);
        $sEm = $this->startAskSymptoms($hospital->id);
        $em = $this->postJson('/api/chatbot/sessions/'.$sEm.'/reply', [
            'input' => ['type' => 'text', 'value' => 'unique-emergency-probe-text-xyz'],
        ]);
        $dump['emergency'] = ['http' => $em->getStatusCode(), 'body' => $em->json()];

        $sCrisis = $this->startAskSymptoms($hospital->id);
        $crisis = $this->postJson('/api/chatbot/sessions/'.$sCrisis.'/reply', [
            'input' => ['type' => 'text', 'value' => 'I want to die'],
        ]);
        $dump['mental_health_emergency'] = ['http' => $crisis->getStatusCode(), 'body' => $crisis->json()];

        $path = storage_path('framework/disease-chatbot-ai-dump.json');
        file_put_contents($path, json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fwrite(STDOUT, "\n===== DISEASE CHATBOT AI/KEYWORD/EMERGENCY DUMP =====\n");
        fwrite(STDOUT, file_get_contents($path)."\n");
        fwrite(STDOUT, "===== END AI DUMP =====\n");

        $this->assertSame('ASK_SYMPTOMS', $dump['ask_symptoms']['body']['state']);
        $this->assertNotNull($dump['ask_symptoms']['body']['input']['helper_text'] ?? null);
        $this->assertSame('ASK_CONSULT', $dump['ai']['body']['state']);
        $this->assertSame('ASK_CONSULT', $dump['keyword_fallback']['body']['state']);
        $this->assertSame('EMERGENCY_NOTICE', $dump['emergency']['body']['state']);
        $this->assertSame('mental_health', $dump['mental_health_emergency']['body']['messages'][0]['red_flag_type'] ?? null);
        Http::assertSentCount(2);
    }

    public function test_dumps_real_api_json_for_all_flows(): void
    {
        config(['chatbot.disease_enabled' => true]);
        [$user, $hospital] = $this->seedEnabledHospital();
        $stranger = $this->member('dump-stranger@example.com', 'HIPDUMP2');
        $strangerPerson = $this->person($stranger, true);
        $disabledHospital = $this->hospital('Dump Disabled');
        Sanctum::actingAs($user);

        $dump = [];

        $start = $this->postJson('/api/chatbot/sessions', ['hospital_id' => $hospital->id]);
        $dump['start'] = ['http' => $start->getStatusCode(), 'body' => $start->json()];
        $sessionId = $start->json('session_id');

        $pick = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ]);
        $dump['pick_option'] = ['http' => $pick->getStatusCode(), 'body' => $pick->json()];

        $symptoms = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'symptoms', 'value' => ['wheeze', 'Cough']],
        ]);
        $dump['symptoms'] = ['http' => $symptoms->getStatusCode(), 'body' => $symptoms->json()];
        $dump['results'] = $dump['symptoms'];

        $yes = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'yes'],
        ]);
        $dump['yes'] = ['http' => $yes->getStatusCode(), 'body' => $yes->json()];

        $sessionNo = $this->postJson('/api/chatbot/sessions', ['hospital_id' => $hospital->id])->json('session_id');
        $this->postJson('/api/chatbot/sessions/'.$sessionNo.'/reply', [
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ]);
        $this->postJson('/api/chatbot/sessions/'.$sessionNo.'/reply', [
            'input' => ['type' => 'symptoms', 'value' => ['Cough']],
        ]);
        $no = $this->postJson('/api/chatbot/sessions/'.$sessionNo.'/reply', [
            'input' => ['type' => 'option', 'value' => 'no'],
        ]);
        $dump['no'] = ['http' => $no->getStatusCode(), 'body' => $no->json()];

        $sessionEm = $this->postJson('/api/chatbot/sessions', ['hospital_id' => $hospital->id])->json('session_id');
        $this->postJson('/api/chatbot/sessions/'.$sessionEm.'/reply', [
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ]);
        $emergency = $this->postJson('/api/chatbot/sessions/'.$sessionEm.'/reply', [
            'input' => ['type' => 'symptoms', 'value' => ['Chest Pain']],
        ]);
        $dump['emergency'] = ['http' => $emergency->getStatusCode(), 'body' => $emergency->json()];

        $sessionBack = $this->postJson('/api/chatbot/sessions', ['hospital_id' => $hospital->id])->json('session_id');
        $this->postJson('/api/chatbot/sessions/'.$sessionBack.'/reply', [
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ]);
        $back = $this->postJson('/api/chatbot/sessions/'.$sessionBack.'/reply', [
            'input' => ['type' => 'option', 'value' => 'back'],
        ]);
        $dump['back'] = ['http' => $back->getStatusCode(), 'body' => $back->json()];

        $restart = $this->postJson('/api/chatbot/sessions/'.$sessionBack.'/restart');
        $dump['restart'] = ['http' => $restart->getStatusCode(), 'body' => $restart->json()];

        $wrong = $this->postJson('/api/chatbot/sessions/'.$sessionBack.'/reply', [
            'input' => ['type' => 'option', 'value' => 'not-a-real-option'],
        ]);
        $dump['wrong_input_422'] = ['http' => $wrong->getStatusCode(), 'body' => $wrong->json()];

        $wrongPerson = $this->postJson('/api/chatbot/sessions', [
            'hospital_id' => $hospital->id,
            'person_id' => $strangerPerson->id,
        ]);
        $dump['wrong_person_403'] = ['http' => $wrongPerson->getStatusCode(), 'body' => $wrongPerson->json()];

        Sanctum::actingAs($stranger);
        $other = $this->getJson('/api/chatbot/sessions/'.$sessionId);
        $dump['other_user_404'] = ['http' => $other->getStatusCode(), 'body' => $other->json()];

        Sanctum::actingAs($user);
        Carbon::setTestNow(Carbon::parse('2026-10-16 12:31:00', 'Asia/Kolkata'));
        $expired = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ]);
        $dump['expired_410'] = ['http' => $expired->getStatusCode(), 'body' => $expired->json()];
        Carbon::setTestNow(Carbon::parse('2026-10-16 12:00:00', 'Asia/Kolkata'));

        $disabled = $this->postJson('/api/chatbot/sessions', ['hospital_id' => $disabledHospital->id]);
        $dump['disabled_hospital'] = ['http' => $disabled->getStatusCode(), 'body' => $disabled->json()];

        config(['chatbot.disease_enabled' => false]);
        $flagOff = $this->postJson('/api/chatbot/sessions', ['hospital_id' => $hospital->id]);
        $dump['flag_off_503'] = ['http' => $flagOff->getStatusCode(), 'body' => $flagOff->json()];
        config(['chatbot.disease_enabled' => true]);

        $path = storage_path('framework/disease-chatbot-real-dump.json');
        file_put_contents($path, json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fwrite(STDOUT, "\n===== DISEASE CHATBOT REAL JSON DUMP =====\n");
        fwrite(STDOUT, file_get_contents($path)."\n");
        fwrite(STDOUT, "===== END DUMP (also at {$path}) =====\n");

        $this->assertFileExists($path);
        $this->assertSame(200, $dump['start']['http']);
        $this->assertSame(503, $dump['flag_off_503']['http']);
    }

    private function enableAiMatching(): void
    {
        config([
            'chatbot.ai_matching_enabled' => true,
            'chatbot.ai_timeout_seconds' => 10,
            'chatbot.ai_cache_minutes' => 1440,
            'chatbot.ai_max_per_hour' => 10,
            'chatbot.ai_model' => null,
            'services.openrouter.api_key' => 'test-openrouter-key',
            'services.openrouter.base_url' => 'https://openrouter.ai/api/v1',
            'services.openrouter.default_model' => 'openai/gpt-4o-mini',
            'services.openrouter.fallback_model' => 'openrouter/auto',
        ]);
    }

    private function diseaseCardId(string $diseaseName): string
    {
        $master = SpecialitiesMaster::query()->where('status', 'active')->firstOrFail();

        return (int) $master->id.':'.Str::slug($diseaseName);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function fakeOpenRouterJson(array $payload): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'id' => 'gen-test',
                'model' => 'openai/gpt-4o-mini',
                'choices' => [
                    ['message' => ['role' => 'assistant', 'content' => json_encode($payload)]],
                ],
                'usage' => [
                    'prompt_tokens' => 12,
                    'completion_tokens' => 8,
                    'total_tokens' => 20,
                ],
            ], 200),
        ]);
    }

    private function startAskSymptoms(int $hospitalId): string
    {
        $sessionId = $this->postJson('/api/chatbot/sessions', ['hospital_id' => $hospitalId])
            ->assertOk()
            ->json('session_id');
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ])->assertOk();

        return $sessionId;
    }

    /**
     * @return array{0: HIPUser, 1: Hospital}
     */
    private function seedEnabledHospital(): array
    {
        $user = $this->member();
        $this->person($user, true);
        $hospital = $this->hospital();

        $master = $this->master('Cardiology', 'CARD', [
            [
                'name' => 'Angina',
                'symptoms' => ['Chest Pain', 'Shortness of Breath'],
                'about' => 'Chest discomfort from heart strain',
                'recommended_tests' => ['ECG', 'Troponin'],
            ],
            [
                'name' => 'Asthma',
                'symptoms' => ['Wheeze', 'Cough'],
                'about' => 'Airway inflammation',
                'recommended_tests' => ['Spirometry'],
            ],
        ]);
        $this->department($hospital->id, $master->id, 'active');

        return [$user, $hospital];
    }

    private function member(string $email = 'chat@example.com', string $hipId = 'HIPCHAT01'): HIPUser
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

    private function person(HIPUser $user, bool $primary, ?string $parentId = null): Persons
    {
        return Persons::withoutEvents(fn () => Persons::query()->create([
            'first_name' => $primary ? 'Self' : 'Child',
            'last_name' => 'Member',
            'hip_user_id' => $primary ? $user->id : null,
            'parent_id' => $parentId,
            'is_primary' => $primary,
            'is_active' => true,
        ]));
    }

    private function hospital(string $name = 'Chat Hospital'): Hospital
    {
        return Hospital::withoutEvents(fn () => Hospital::query()->create([
            'name' => $name,
        ]));
    }

    private function master(string $name, string $code, array $diseases): SpecialitiesMaster
    {
        $master = new SpecialitiesMaster();
        $master->forceFill([
            'name' => $name,
            'code' => $code,
            'status' => 'active',
            'diseases' => $diseases,
        ]);
        $master->save();

        return $master->fresh();
    }

    private function department(int $hospitalId, int $masterId, string $status): Speciality
    {
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
    }

    private function bootSchema(): void
    {
        if (self::$schemaReady && Schema::hasTable('disease_chatbot_sessions') && Schema::hasTable('coins')) {
            return;
        }

        Schema::dropIfExists('disease_chatbot_messages');
        Schema::dropIfExists('disease_chatbot_sessions');
        Schema::dropIfExists('disease_chatbot_bookings');
        Schema::dropIfExists('doctor_bookings');
        Schema::dropIfExists('doctor_reviews');
        Schema::dropIfExists('doctor_assignments');
        Schema::dropIfExists('procedures');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('specialities');
        Schema::dropIfExists('specialities_masters');
        Schema::dropIfExists('coins');
        Schema::dropIfExists('persons');
        Schema::dropIfExists('hospitals');
        Schema::dropIfExists('healthinpocket_users');

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
            $table->index(['session_id', 'id']);
        });

        self::$schemaReady = true;
    }
}
