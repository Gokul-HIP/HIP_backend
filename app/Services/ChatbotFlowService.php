<?php

namespace App\Services;

use App\Exceptions\ChatbotFlowException;
use App\Models\DiseaseChatbotBooking;
use App\Models\DiseaseChatbotMessage;
use App\Models\DiseaseChatbotSession;
use App\Models\DoctorBooking;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Persons;
use App\Models\Speciality;
use App\Models\SpecialitiesMaster;
use App\Services\Pharmacy\PharmacyOrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ChatbotFlowService
{
    public function __construct(
        protected PharmacyOrderService $pharmacyOrderService,
        protected DiseaseSymptomMatcher $symptomMatcher,
        protected DoctorAvailabilityService $availability,
        protected ChatbotBookingService $bookingService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function startSession(HIPUser $user, int $hospitalId, ?string $personId): array
    {
        $hospital = Hospital::query()->find($hospitalId);
        if (! $hospital) {
            throw ValidationException::withMessages([
                'hospital_id' => ['Hospital not found.'],
            ]);
        }

        $resolvedPersonId = $this->resolveAndAuthorizePerson($user, $personId);

        $enabled = Speciality::query()->enabledForChatbot($hospitalId)->exists()
            && $this->hospitalHasChatbotDiseases($hospitalId);

        $clientPassedPersonId = $personId !== null && $personId !== '';

        return DB::transaction(function () use ($user, $hospitalId, $resolvedPersonId, $enabled, $clientPassedPersonId) {
            $session = DiseaseChatbotSession::query()->create([
                'user_id' => $user->id,
                'person_id' => $resolvedPersonId,
                'hospital_id' => $hospitalId,
                'state' => $enabled
                    ? DiseaseChatbotSession::STATE_MENU
                    : DiseaseChatbotSession::STATE_DISABLED,
                'context' => [
                    'history' => [],
                    'symptoms' => [],
                    'free_text' => null,
                    'results' => [],
                    'idempotency' => [],
                    'hospital_enabled' => $enabled,
                    'client_passed_person_id' => $clientPassedPersonId,
                ],
                'status' => DiseaseChatbotSession::STATUS_ACTIVE,
                'last_activity_at' => now(),
                'expires_at' => now()->addMinutes((int) config('chatbot.idle_minutes', 30)),
            ]);

            if (! $enabled) {
                $bot = $this->storeBotMessage($session, 'text', [
                    'text' => (string) config('chatbot.hospital_disabled_message'),
                ]);

                return $this->envelope($session->fresh(['messages']), [$bot], [
                    'mode' => 'none',
                    'allow_text' => false,
                    'placeholder' => null,
                    'multi_select' => false,
                    'min' => null,
                    'max' => null,
                ], [], false, true);
            }

            return $this->handleMenu($session, null, true);
        });
    }

    /**
     * @param  array{type?: string, value?: mixed}|null  $input
     * @return array<string, mixed>
     */
    public function reply(DiseaseChatbotSession $session, ?array $input, ?string $clientMessageId = null): array
    {
        $this->assertOwnedActive($session);

        if ($clientMessageId) {
            $cached = data_get($session->context, 'idempotency.'.$clientMessageId);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $expired = $this->maybeEnterExpired($session->fresh(), $input);
        if ($expired !== null) {
            if ($clientMessageId) {
                $context = $session->fresh()->context ?? [];
                $context['idempotency'][$clientMessageId] = $expired;
                $session->update(['context' => $context]);
            }

            return $expired;
        }

        $envelope = match ($session->state) {
            DiseaseChatbotSession::STATE_MENU => $this->handleMenu($session, $input, false),
            DiseaseChatbotSession::STATE_ASK_SYMPTOMS => $this->handleAskSymptoms($session, $input),
            DiseaseChatbotSession::STATE_EMERGENCY_NOTICE => $this->handleEmergencyNotice($session, $input),
            DiseaseChatbotSession::STATE_SHOW_RESULTS => $this->handleShowResults($session, $input),
            DiseaseChatbotSession::STATE_ASK_CONSULT => $this->handleAskConsult($session, $input),
            DiseaseChatbotSession::STATE_CHOOSE_PERSON => $this->handleChoosePerson($session, $input),
            DiseaseChatbotSession::STATE_CHOOSE_SPECIALITY => $this->handleChooseSpeciality($session, $input),
            DiseaseChatbotSession::STATE_CHOOSE_DOCTOR => $this->handleChooseDoctor($session, $input),
            DiseaseChatbotSession::STATE_CHOOSE_DATE => $this->handleChooseDate($session, $input),
            DiseaseChatbotSession::STATE_CHOOSE_SLOT => $this->handleChooseSlot($session, $input),
            DiseaseChatbotSession::STATE_ASK_COINS => $this->handleAskCoins($session, $input),
            DiseaseChatbotSession::STATE_ASK_COINS_AMOUNT => $this->handleAskCoinsAmount($session, $input),
            DiseaseChatbotSession::STATE_CONFIRM_SUMMARY => $this->handleConfirmSummary($session, $input),
            DiseaseChatbotSession::STATE_PAYMENT_PENDING => $this->handlePaymentPending($session, $input),
            DiseaseChatbotSession::STATE_EXPIRED => $this->handleExpired($session, $input),
            DiseaseChatbotSession::STATE_CONFIRMED => $this->rejectInput($session, 'This booking is already confirmed. Restart to begin again.'),
            DiseaseChatbotSession::STATE_COMPLETED => $this->rejectInput($session, 'This session is already completed. Restart to begin again.'),
            DiseaseChatbotSession::STATE_DISABLED => $this->rejectInput($session, (string) config('chatbot.hospital_disabled_message')),
            default => $this->rejectInput($session, 'Unknown session state.'),
        };

        if ($clientMessageId) {
            $context = $session->fresh()->context ?? [];
            $context['idempotency'][$clientMessageId] = $envelope;
            $session->update(['context' => $context]);
        }

        return $envelope;
    }

    /**
     * @return array<string, mixed>
     */
    public function show(DiseaseChatbotSession $session): array
    {
        $this->assertOwnedNotForeign($session);
        $this->refreshExpiryOrThrow($session);

        $expired = $this->maybeEnterExpired($session->fresh());
        if ($expired !== null) {
            return $expired;
        }

        if ($session->state === DiseaseChatbotSession::STATE_PAYMENT_PENDING) {
            $status = $this->bookingService->readPaymentStatus($session);
            if ($status['paid'] && $status['booking']) {
                return $this->enterConfirmed($session->fresh(), $status['booking'], $status['invoice']);
            }
        }

        $history = $session->messages()
            ->orderBy('id')
            ->get()
            ->map(fn (DiseaseChatbotMessage $m) => $this->formatMessage($m))
            ->values()
            ->all();

        $step = $this->stepUiForState($session->fresh(), $session->fresh()->state);

        return $this->envelope($session->fresh(), $history, $step['input'], $step['options'], $step['allow_back'], $step['allow_restart']);
    }

    /**
     * @return array<string, mixed>
     */
    public function restart(DiseaseChatbotSession $session): array
    {
        $this->assertOwnedNotForeign($session);

        if ($session->isExpiredByIdle()) {
            $session->update(['status' => DiseaseChatbotSession::STATUS_EXPIRED]);
        }

        $enabled = (bool) data_get($session->context, 'hospital_enabled', true);
        if ($enabled) {
            $enabled = Speciality::query()->enabledForChatbot((int) $session->hospital_id)->exists()
                && $this->hospitalHasChatbotDiseases((int) $session->hospital_id);
        }

        $session->update([
            'state' => $enabled
                ? DiseaseChatbotSession::STATE_MENU
                : DiseaseChatbotSession::STATE_DISABLED,
            'status' => DiseaseChatbotSession::STATUS_ACTIVE,
            'context' => [
                'history' => [],
                'symptoms' => [],
                'free_text' => null,
                'results' => [],
                'idempotency' => [],
                'hospital_enabled' => $enabled,
            ],
            'last_activity_at' => now(),
            'expires_at' => now()->addMinutes((int) config('chatbot.idle_minutes', 30)),
        ]);

        $session = $session->fresh();

        if (! $enabled) {
            $bot = $this->storeBotMessage($session, 'text', [
                'text' => (string) config('chatbot.hospital_disabled_message'),
            ]);

            return $this->envelope($session->fresh(['messages']), [$bot], [
                'mode' => 'none',
                'allow_text' => false,
                'placeholder' => null,
                'multi_select' => false,
                'min' => null,
                'max' => null,
            ], [], false, true);
        }

        return $this->handleMenu($session, null, true);
    }

    public function findOwnedSession(HIPUser $user, string $sessionId): DiseaseChatbotSession
    {
        $session = DiseaseChatbotSession::query()
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if (! $session) {
            throw new HttpException(404, 'Session not found.');
        }

        return $session;
    }

    /**
     * person_id must be the authenticated user's primary person or a dependent
     * under that primary (persons.parent_id / hip_user_id), same as
     * PharmacyOrderService::familyPersonIds and HomePageController familyMembers.
     */
    public function resolveAndAuthorizePerson(HIPUser $user, ?string $personId): ?string
    {
        $familyIds = $this->pharmacyOrderService->familyPersonIds($user);

        if ($personId === null || $personId === '') {
            $primary = Persons::query()
                ->where('hip_user_id', $user->id)
                ->where('is_primary', true)
                ->first()
                ?? Persons::query()->where('hip_user_id', $user->id)->first();

            return $primary?->id;
        }

        if (! in_array((string) $personId, $familyIds, true)) {
            throw new HttpException(403, 'You can only start a session for yourself or a family member.');
        }

        return (string) $personId;
    }

    /**
     * @param  array{type?: string, value?: mixed}|null  $input
     * @return array<string, mixed>
     */
    protected function handleMenu(DiseaseChatbotSession $session, ?array $input, bool $isEntry): array
    {
        if ($isEntry || $input === null) {
            $this->touch($session, DiseaseChatbotSession::STATE_MENU);
            $bot = $this->storeBotMessage($session, 'text', [
                'text' => 'Hi! I can help you check possible conditions from your symptoms. This is not a medical diagnosis.',
            ]);

            return $this->envelope($session->fresh(), [$bot], [
                'mode' => 'options',
                'allow_text' => false,
                'placeholder' => null,
                'multi_select' => false,
                'min' => null,
                'max' => null,
            ], [
                $this->option('check_disease', 'Check my disease from my symptoms', 'primary'),
            ], false, true);
        }

        if ($this->isRestart($input)) {
            return $this->restart($session);
        }

        $value = $this->optionValue($input);
        if ($value !== 'check_disease') {
            return $this->rejectInput($session, 'Please choose an option to continue.');
        }

        $this->storeUserMessage($session, 'option', ['value' => $value]);
        $this->pushHistory($session, DiseaseChatbotSession::STATE_MENU);

        return $this->enterAskSymptoms($session);
    }

    /**
     * @param  array{type?: string, value?: mixed}|null  $input
     * @return array<string, mixed>
     */
    protected function handleAskSymptoms(DiseaseChatbotSession $session, ?array $input): array
    {
        if ($input === null) {
            return $this->enterAskSymptoms($session);
        }

        if ($this->isBack($input)) {
            return $this->goBack($session);
        }
        if ($this->isRestart($input)) {
            return $this->restart($session);
        }

        $type = (string) ($input['type'] ?? '');
        $value = $input['value'] ?? null;

        if (! in_array($type, ['symptoms', 'text', 'mixed'], true)) {
            return $this->rejectInput($session, 'Send your symptoms to continue.');
        }

        $catalog = $this->symptomCatalog((int) $session->hospital_id);
        $byId = [];
        $byLabel = [];
        foreach ($catalog as $item) {
            $byId[$item['id']] = $item['label'];
            $byLabel[mb_strtolower($item['label'])] = $item['label'];
        }

        $chosen = [];
        $rawTokens = [];
        $unresolved = [];
        $freeText = null;

        $resolveToken = function (string $token) use ($byId, $byLabel, &$chosen, &$rawTokens, &$unresolved): void {
            $token = trim($token);
            if ($token === '') {
                return;
            }
            $rawTokens[] = $token;
            if (isset($byId[$token])) {
                $chosen[] = $byId[$token];

                return;
            }
            $key = mb_strtolower($token);
            if (isset($byLabel[$key])) {
                $chosen[] = $byLabel[$key];

                return;
            }
            $unresolved[] = $token;
        };

        if ($type === 'symptoms' || $type === 'mixed') {
            $raw = is_array($value) && ! isset($value['symptoms']) && ! isset($value['text'])
                ? $value
                : (array) data_get($input, 'symptoms', is_array($value) ? ($value['symptoms'] ?? []) : []);
            foreach ($raw as $token) {
                if (is_string($token)) {
                    $resolveToken($token);
                }
            }
        }

        if ($type === 'text' || $type === 'mixed') {
            $text = is_string($value) ? $value : (string) data_get($input, 'text', '');
            if (is_array($value) && isset($value['text'])) {
                $text = (string) $value['text'];
            }
            $freeText = trim($text);
            if ($freeText !== '') {
                $rawTokens[] = $freeText;
                foreach ($byLabel as $key => $label) {
                    if (str_contains(mb_strtolower($freeText), $key)) {
                        $chosen[] = $label;
                    }
                }
            }
        }

        if (is_array($value) && (isset($value['symptoms']) || isset($value['text']))) {
            foreach ((array) ($value['symptoms'] ?? []) as $token) {
                if (is_string($token)) {
                    $resolveToken($token);
                }
            }
            $text = trim((string) ($value['text'] ?? ''));
            if ($text !== '') {
                $freeText = $text;
                $rawTokens[] = $text;
                foreach ($byLabel as $key => $label) {
                    if (str_contains(mb_strtolower($text), $key)) {
                        $chosen[] = $label;
                    }
                }
            }
        }

        $chosen = array_values(array_unique($chosen));
        $unresolved = array_values(array_unique(array_filter(array_map('trim', $unresolved))));
        if (($freeText === null || trim((string) $freeText) === '') && $unresolved !== []) {
            $freeText = implode(', ', $unresolved);
        }
        $redFlagMatch = $this->matchingRedFlags($rawTokens, $chosen, $freeText);

        $this->storeUserMessage($session, $type === 'text' ? 'text' : 'symptoms', [
            'value' => $value,
            'matched' => $chosen,
            'free_text' => $freeText,
            'red_flags' => $redFlagMatch['hits'],
            'red_flag_type' => $redFlagMatch['type'],
        ]);

        if ($redFlagMatch['type'] !== null) {
            $this->pushHistory($session, DiseaseChatbotSession::STATE_ASK_SYMPTOMS);
            $context = $session->fresh()->context ?? [];
            $context['symptoms'] = $chosen;
            $context['free_text'] = $freeText;
            $context['red_flags'] = $redFlagMatch['hits'];
            $context['red_flag_type'] = $redFlagMatch['type'];
            $context['emergency_banner'] = true;
            $session->update(['context' => $context]);

            return $this->enterEmergencyNotice($session->fresh());
        }

        if ($chosen === [] && trim((string) $freeText) === '') {
            $bot = $this->storeBotMessage($session, 'text', [
                'text' => 'Please select or describe at least one symptom so I can continue.',
            ]);
            $picker = $this->symptomPickerMessage($session);

            return $this->envelope($session->fresh(), [$bot, $picker], [
                'mode' => 'multi_select',
                'allow_text' => true,
                'placeholder' => 'Type a symptom or pick from the list',
                'multi_select' => true,
                'min' => 1,
                'max' => (int) config('chatbot.max_symptoms', 60),
            ], [], true, true);
        }

        $this->pushHistory($session, DiseaseChatbotSession::STATE_ASK_SYMPTOMS);
        $context = $session->fresh()->context ?? [];
        $context['symptoms'] = $chosen;
        $context['free_text'] = $freeText;
        $session->update(['context' => $context]);

        return $this->enterShowResults($session->fresh());
    }

    /**
     * @param  array{type?: string, value?: mixed}|null  $input
     * @return array<string, mixed>
     */
    protected function handleEmergencyNotice(DiseaseChatbotSession $session, ?array $input): array
    {
        if ($input === null) {
            return $this->enterEmergencyNotice($session);
        }

        if ($this->isBack($input)) {
            return $this->goBack($session);
        }
        if ($this->isRestart($input)) {
            return $this->restart($session);
        }

        $value = $this->optionValue($input);
        if ($value === 'continue_anyway') {
            $this->storeUserMessage($session, 'option', ['value' => $value]);
            $this->pushHistory($session, DiseaseChatbotSession::STATE_EMERGENCY_NOTICE);

            return $this->enterShowResults($session->fresh(), true);
        }

        return $this->rejectInput($session, 'Please choose Continue anyway or Restart.');
    }

    /**
     * @param  array{type?: string, value?: mixed}|null  $input
     * @return array<string, mixed>
     */
    protected function handleShowResults(DiseaseChatbotSession $session, ?array $input): array
    {
        if ($input === null) {
            return $this->enterShowResults($session);
        }

        if ($this->isBack($input)) {
            return $this->goBack($session);
        }
        if ($this->isRestart($input)) {
            return $this->restart($session);
        }

        $value = $this->optionValue($input);

        if ($value === 'choose_again') {
            $this->storeUserMessage($session, 'option', ['value' => $value]);
            $this->pushHistory($session, DiseaseChatbotSession::STATE_SHOW_RESULTS);

            return $this->enterAskSymptoms($session->fresh());
        }

        if ($value === 'continue_consult') {
            $this->storeUserMessage($session, 'option', ['value' => $value]);
            $this->pushHistory($session, DiseaseChatbotSession::STATE_SHOW_RESULTS);

            return $this->enterAskConsult($session->fresh());
        }

        return $this->rejectInput($session, 'Please choose an option to continue.');
    }

    /**
     * @param  array{type?: string, value?: mixed}|null  $input
     * @return array<string, mixed>
     */
    protected function handleAskConsult(DiseaseChatbotSession $session, ?array $input): array
    {
        if ($input === null) {
            return $this->enterAskConsult($session);
        }

        if ($this->isBack($input)) {
            return $this->goBack($session);
        }
        if ($this->isRestart($input)) {
            return $this->restart($session);
        }

        $value = $this->optionValue($input);
        if (! in_array($value, ['yes', 'no'], true)) {
            return $this->rejectInput($session, 'Please choose Yes or No.');
        }

        $this->storeUserMessage($session, 'option', ['value' => $value]);
        $this->pushHistory($session, DiseaseChatbotSession::STATE_ASK_CONSULT);

        if ($value === 'yes') {
            return $this->enterAfterConsultYes($session->fresh());
        }

        return $this->enterCompleted($session->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    protected function enterAfterConsultYes(DiseaseChatbotSession $session): array
    {
        if ($this->needsChoosePerson($session)) {
            return $this->enterChoosePerson($session);
        }

        return $this->enterAfterPersonResolved($session);
    }

    protected function needsChoosePerson(DiseaseChatbotSession $session): bool
    {
        if ((bool) data_get($session->context, 'client_passed_person_id', false)) {
            return false;
        }
        $user = HIPUser::query()->find($session->user_id);
        if (! $user) {
            return false;
        }

        return count($this->pharmacyOrderService->familyPersonIds($user)) > 1;
    }

    /**
     * @return array<string, mixed>
     */
    protected function enterAfterPersonResolved(DiseaseChatbotSession $session): array
    {
        $specialities = $this->availableSpecialitiesFromResults($session);
        if (count($specialities) > 1) {
            return $this->enterChooseSpeciality($session, $specialities);
        }

        $only = $specialities[0] ?? null;
        if ($only === null) {
            $bot = $this->storeBotMessage($session, 'text', [
                'text' => 'No doctors are available for these results at this hospital right now.',
            ]);
            $this->touch($session, DiseaseChatbotSession::STATE_ASK_CONSULT);

            return $this->envelope($session->fresh(), [$bot], [
                'mode' => 'none',
                'allow_text' => false,
                'placeholder' => null,
                'multi_select' => false,
                'min' => null,
                'max' => null,
            ], [], true, true);
        }

        $context = $session->context ?? [];
        $context['selected_speciality'] = $only;
        $session->update(['context' => $context]);

        return $this->enterChooseDoctor($session->fresh());
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    protected function distinctSpecialitiesFromResults(DiseaseChatbotSession $session): array
    {
        $byId = [];
        foreach ((array) data_get($session->context, 'results', []) as $card) {
            $id = (int) data_get($card, 'speciality.id', 0);
            $name = trim((string) data_get($card, 'speciality.name', ''));
            if ($id > 0 && $name !== '') {
                $byId[$id] = ['id' => $id, 'name' => $name];
            }
        }

        return array_values($byId);
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    protected function availableSpecialitiesFromResults(DiseaseChatbotSession $session): array
    {
        return $this->availability->specialitiesWithAvailability(
            (int) $session->hospital_id,
            $this->distinctSpecialitiesFromResults($session)
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function enterChoosePerson(DiseaseChatbotSession $session): array
    {
        $user = HIPUser::query()->findOrFail($session->user_id);
        $familyIds = $this->pharmacyOrderService->familyPersonIds($user);
        $people = Persons::query()->whereIn('id', $familyIds)->get();

        $this->touch($session, DiseaseChatbotSession::STATE_CHOOSE_PERSON);
        $bot = $this->storeBotMessage($session, 'text', [
            'text' => 'Who is this for?',
        ]);
        $options = [];
        foreach ($people as $person) {
            $name = trim(((string) $person->first_name).' '.((string) $person->last_name));
            $relation = trim((string) ($person->relationship ?? ($person->is_primary ? 'Self' : 'Family')));
            $label = $name !== '' ? $name.' ('.$relation.')' : $relation;
            $options[] = $this->option('person:'.$person->id, $label, 'primary');
        }

        return $this->envelope($session->fresh(), [$bot], [
            'mode' => 'options',
            'allow_text' => false,
            'placeholder' => null,
            'multi_select' => false,
            'min' => null,
            'max' => null,
        ], $options, true, true);
    }

    /**
     * @param  array{type?: string, value?: mixed}|null  $input
     * @return array<string, mixed>
     */
    protected function handleChoosePerson(DiseaseChatbotSession $session, ?array $input): array
    {
        if ($input === null) {
            return $this->enterChoosePerson($session);
        }
        if ($this->isBack($input)) {
            return $this->goBack($session);
        }
        if ($this->isRestart($input)) {
            return $this->restart($session);
        }

        $value = $this->optionValue($input);
        $personId = null;
        if ((string) ($input['type'] ?? '') === 'person') {
            $personId = trim((string) ($input['value'] ?? ''));
        } elseif (is_string($value) && str_starts_with($value, 'person:')) {
            $personId = substr($value, 7);
        }

        $user = HIPUser::query()->findOrFail($session->user_id);
        try {
            $authorized = $this->resolveAndAuthorizePerson($user, $personId);
        } catch (HttpException $e) {
            throw $e;
        }
        if ($authorized === null || $authorized === '') {
            return $this->rejectInput($session, 'Please choose who this appointment is for.');
        }

        $this->storeUserMessage($session, 'person', ['value' => $authorized]);
        $this->pushHistory($session, DiseaseChatbotSession::STATE_CHOOSE_PERSON);
        $session->update(['person_id' => $authorized]);

        return $this->enterAfterPersonResolved($session->fresh());
    }

    /**
     * @param  list<array{id: int, name: string}>  $specialities
     * @return array<string, mixed>
     */
    protected function enterChooseSpeciality(DiseaseChatbotSession $session, array $specialities): array
    {
        $specialities = $this->availability->specialitiesWithAvailability(
            (int) $session->hospital_id,
            $specialities
        );
        if ($specialities === []) {
            $bot = $this->storeBotMessage($session, 'text', [
                'text' => 'No doctors are available for these specialities at this hospital right now.',
            ]);
            $this->touch($session, DiseaseChatbotSession::STATE_CHOOSE_SPECIALITY);

            return $this->envelope($session->fresh(), [$bot], [
                'mode' => 'none',
                'allow_text' => false,
                'placeholder' => null,
                'multi_select' => false,
                'min' => null,
                'max' => null,
            ], [], true, true);
        }

        $this->touch($session, DiseaseChatbotSession::STATE_CHOOSE_SPECIALITY);
        $bot = $this->storeBotMessage($session, 'text', [
            'text' => 'Which speciality would you like to consult?',
        ]);
        $options = [];
        foreach ($specialities as $row) {
            $options[] = $this->option('spec:'.$row['id'], $row['name'], 'primary');
        }

        return $this->envelope($session->fresh(), [$bot], [
            'mode' => 'options',
            'allow_text' => false,
            'placeholder' => null,
            'multi_select' => false,
            'min' => null,
            'max' => null,
        ], $options, true, true);
    }

    /**
     * @param  array{type?: string, value?: mixed}|null  $input
     * @return array<string, mixed>
     */
    protected function handleChooseSpeciality(DiseaseChatbotSession $session, ?array $input): array
    {
        if ($input === null) {
            return $this->enterChooseSpeciality($session, $this->availableSpecialitiesFromResults($session));
        }
        if ($this->isBack($input)) {
            return $this->goBack($session);
        }
        if ($this->isRestart($input)) {
            return $this->restart($session);
        }

        $value = $this->optionValue($input);
        if (! is_string($value) || ! str_starts_with($value, 'spec:')) {
            return $this->rejectInput($session, 'Please choose a speciality to continue.');
        }
        $masterId = (int) substr($value, 5);
        $allowed = collect($this->availableSpecialitiesFromResults($session))->keyBy('id');
        if (! $allowed->has($masterId)) {
            return $this->rejectInput($session, 'Please choose a speciality to continue.');
        }

        $this->storeUserMessage($session, 'option', ['value' => $value]);
        $this->pushHistory($session, DiseaseChatbotSession::STATE_CHOOSE_SPECIALITY);
        $context = $session->fresh()->context ?? [];
        $context['selected_speciality'] = $allowed->get($masterId);
        $session->update(['context' => $context]);

        return $this->enterChooseDoctor($session->fresh());
    }

    /**
     * @param  array{type?: string, value?: mixed}|null  $input
     * @return array<string, mixed>
     */
    protected function handleChooseDoctor(DiseaseChatbotSession $session, ?array $input): array
    {
        if ($input === null) {
            return $this->enterChooseDoctor($session);
        }
        if ($this->isBack($input)) {
            return $this->goBack($session);
        }
        if ($this->isRestart($input)) {
            return $this->restart($session);
        }

        $value = $this->optionValue($input);
        if ($value === 'choose_speciality') {
            $this->storeUserMessage($session, 'option', ['value' => $value]);
            $this->pushHistory($session, DiseaseChatbotSession::STATE_CHOOSE_DOCTOR);

            return $this->enterChooseSpeciality($session->fresh(), $this->availableSpecialitiesFromResults($session->fresh()));
        }

        $doctorId = null;
        if ((string) ($input['type'] ?? '') === 'doctor') {
            $doctorId = trim((string) ($input['value'] ?? ''));
        } elseif (is_string($value) && $value !== '') {
            $doctorId = $value;
        }

        if ($doctorId === null || $doctorId === '') {
            return $this->rejectInput($session, 'Please choose a doctor to continue.');
        }

        $masterId = (int) data_get($session->context, 'selected_speciality.id', 0);
        $freshList = $this->availability->doctorsWithAvailability(
            (int) $session->hospital_id,
            $masterId,
            10
        );
        $allowedIds = collect($freshList)->pluck('id')->all();
        if (! in_array($doctorId, $allowedIds, true)) {
            return $this->rejectInput($session, 'Please choose a doctor from the list.');
        }

        $this->storeUserMessage($session, 'doctor', ['value' => $doctorId]);
        $this->pushHistory($session, DiseaseChatbotSession::STATE_CHOOSE_DOCTOR);
        $context = $session->fresh()->context ?? [];
        $context['selected_doctor_id'] = $doctorId;
        $context['doctor_list'] = $freshList;
        $session->update(['context' => $context]);

        return $this->enterChooseDate($session->fresh());
    }

    /**
     * @param  array{type?: string, value?: mixed}|null  $input
     * @return array<string, mixed>
     */
    protected function handleChooseDate(DiseaseChatbotSession $session, ?array $input): array
    {
        if ($input === null) {
            return $this->enterChooseDate($session);
        }
        if ($this->isBack($input)) {
            return $this->goBack($session);
        }
        if ($this->isRestart($input)) {
            return $this->restart($session);
        }

        $value = $this->optionValue($input);
        if ($value === 'choose_doctor') {
            $this->storeUserMessage($session, 'option', ['value' => $value]);
            $this->pushHistory($session, DiseaseChatbotSession::STATE_CHOOSE_DATE);

            return $this->enterChooseDoctor($session->fresh());
        }

        $date = null;
        if ((string) ($input['type'] ?? '') === 'date') {
            $date = trim((string) ($input['value'] ?? ''));
        } elseif (is_string($value)) {
            $date = $value;
        }

        if ($date === null || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->rejectInput($session, 'Please choose a date to continue.');
        }

        $doctorId = (string) data_get($session->context, 'selected_doctor_id', '');
        $masterId = (int) data_get($session->context, 'selected_speciality.id', 0);
        if (! $this->doctorStillEligible($session, $doctorId, $masterId)) {
            return $this->rejectInput($session, 'Please choose a doctor from the list.');
        }

        $slotsByDate = $this->availability->slotsForDoctor($doctorId, (int) $session->hospital_id);
        $times = $slotsByDate[$date] ?? [];
        if ($times === []) {
            return $this->rejectInput($session, 'Please choose an available date.');
        }

        $this->storeUserMessage($session, 'date', ['value' => $date]);
        $this->pushHistory($session, DiseaseChatbotSession::STATE_CHOOSE_DATE);
        $context = $session->fresh()->context ?? [];
        $context['selected_date'] = $date;
        $session->update(['context' => $context]);

        return $this->enterChooseSlot($session->fresh());
    }

    /**
     * @param  array{type?: string, value?: mixed}|null  $input
     * @return array<string, mixed>
     */
    protected function handleChooseSlot(DiseaseChatbotSession $session, ?array $input): array
    {
        if ($input === null) {
            return $this->enterChooseSlot($session);
        }
        if ($this->isBack($input)) {
            return $this->goBack($session);
        }
        if ($this->isRestart($input)) {
            return $this->restart($session);
        }

        $value = $this->optionValue($input);
        if ($value === 'choose_doctor') {
            $this->storeUserMessage($session, 'option', ['value' => $value]);
            $this->pushHistory($session, DiseaseChatbotSession::STATE_CHOOSE_SLOT);

            return $this->enterChooseDoctor($session->fresh());
        }
        if ($value === 'choose_date') {
            $this->storeUserMessage($session, 'option', ['value' => $value]);
            $this->pushHistory($session, DiseaseChatbotSession::STATE_CHOOSE_SLOT);

            return $this->enterChooseDate($session->fresh());
        }

        $slot = null;
        if ((string) ($input['type'] ?? '') === 'slot') {
            $slot = trim((string) ($input['value'] ?? ''));
        } elseif (is_string($value)) {
            $slot = $value;
        }

        if ($slot === null || $slot === '') {
            return $this->rejectInput($session, 'Please choose a time slot to continue.');
        }

        $doctorId = (string) data_get($session->context, 'selected_doctor_id', '');
        $date = (string) data_get($session->context, 'selected_date', '');
        $masterId = (int) data_get($session->context, 'selected_speciality.id', 0);
        if (! $this->doctorStillEligible($session, $doctorId, $masterId)) {
            return $this->rejectInput($session, 'Please choose a doctor from the list.');
        }

        $slotsByDate = $this->availability->slotsForDoctor($doctorId, (int) $session->hospital_id);
        $available = $slotsByDate[$date] ?? [];
        $slotMinutes = $this->availability->parseToMinutes($slot);
        $stillOpen = false;
        foreach ($available as $open) {
            if ($this->availability->parseToMinutes($open) === $slotMinutes) {
                $stillOpen = true;
                $slot = $open;
                break;
            }
        }

        if (! $stillOpen) {
            $this->touch($session, DiseaseChatbotSession::STATE_CHOOSE_SLOT);
            $notice = $this->storeBotMessage($session, 'text', [
                'text' => 'That time was just taken. Please pick another.',
            ]);
            $list = $this->storeBotMessage($session, 'slot_list', [
                'groups' => $this->availability->hourGroups($available),
            ]);
            $envelope = $this->envelope($session->fresh(), [$notice, $list], [
                'mode' => 'slot',
                'allow_text' => false,
                'placeholder' => null,
                'multi_select' => false,
                'min' => null,
                'max' => null,
            ], [
                $this->option('choose_doctor', 'Choose another doctor', 'neutral'),
                $this->option('choose_date', 'Choose another date', 'neutral'),
            ], true, true);
            $envelope['status'] = 422;

            throw new ChatbotFlowException('That time was just taken. Please pick another.', 422, $envelope);
        }

        $this->storeUserMessage($session, 'slot', ['value' => $slot]);
        $this->pushHistory($session, DiseaseChatbotSession::STATE_CHOOSE_SLOT);
        $context = $session->fresh()->context ?? [];
        $context['selected_slot'] = $slot;
        $session->update(['context' => $context]);

        return $this->enterAskCoins($session->fresh());
    }

    /**
     * @param  array{type?: string, value?: mixed}|null  $input
     * @return array<string, mixed>
     */
    protected function handleAskCoins(DiseaseChatbotSession $session, ?array $input): array
    {
        if ($input === null) {
            return $this->enterAskCoins($session);
        }
        if ($this->isBack($input)) {
            return $this->goBack($session);
        }
        if ($this->isRestart($input)) {
            return $this->restart($session);
        }

        $user = HIPUser::query()->findOrFail($session->user_id);
        $doctor = \App\Models\Doctor::query()->find((string) data_get($session->context, 'selected_doctor_id', ''));
        $fee = (float) ($doctor?->consultation_fee ?? 0);
        $preview = $this->bookingService->coinPreview($user, $fee);
        $max = (int) $preview['max_usable'];

        $value = $this->optionValue($input);
        if ($value === 'use_fewer') {
            $this->storeUserMessage($session, 'option', ['value' => $value]);
            $this->pushHistory($session, DiseaseChatbotSession::STATE_ASK_COINS);

            return $this->enterAskCoinsAmount($session->fresh());
        }

        $coins = null;
        if ($value === 'use_max') {
            $coins = $max;
        } elseif ($value === 'use_none') {
            $coins = 0;
        }

        if ($coins === null || $coins < 0 || $coins > $max) {
            return $this->rejectInput($session, 'Please choose a valid coin option.');
        }

        return $this->commitCoinsAndConfirm($session, $coins, DiseaseChatbotSession::STATE_ASK_COINS);
    }

    /**
     * @param  array{type?: string, value?: mixed}|null  $input
     * @return array<string, mixed>
     */
    protected function handleAskCoinsAmount(DiseaseChatbotSession $session, ?array $input): array
    {
        if ($input === null) {
            return $this->enterAskCoinsAmount($session);
        }
        if ($this->isBack($input)) {
            return $this->goBack($session);
        }
        if ($this->isRestart($input)) {
            return $this->restart($session);
        }

        $user = HIPUser::query()->findOrFail($session->user_id);
        $doctor = \App\Models\Doctor::query()->find((string) data_get($session->context, 'selected_doctor_id', ''));
        $fee = (float) ($doctor?->consultation_fee ?? 0);
        $preview = $this->bookingService->coinPreview($user, $fee);
        $max = (int) $preview['max_usable'];

        $value = $this->optionValue($input);
        $coins = null;
        if ($value === 'use_max') {
            $coins = $max;
        } elseif ($value === 'use_none') {
            $coins = 0;
        } elseif ((string) ($input['type'] ?? '') === 'number') {
            $coins = (int) ($input['value'] ?? 0);
        }

        if ($coins === null || $coins < 1 || $coins > $max) {
            return $this->rejectInput($session, 'Please enter between 1 and '.$max.' coins.');
        }

        return $this->commitCoinsAndConfirm($session, $coins, DiseaseChatbotSession::STATE_ASK_COINS_AMOUNT);
    }

    protected function commitCoinsAndConfirm(
        DiseaseChatbotSession $session,
        int $coins,
        string $historyState
    ): array {
        $this->storeUserMessage($session, 'coins', ['value' => $coins]);
        $this->pushHistory($session, $historyState);
        $context = $session->fresh()->context ?? [];
        $context['coins_used'] = $coins;
        $session->update(['context' => $context]);

        return $this->enterConfirmSummary($session->fresh());
    }

    /**
     * @param  array{type?: string, value?: mixed}|null  $input
     * @return array<string, mixed>
     */
    protected function handleExpired(DiseaseChatbotSession $session, ?array $input): array
    {
        if ($input === null) {
            return $this->enterExpired($session);
        }
        if ($this->isRestart($input)) {
            return $this->restart($session);
        }

        $value = $this->optionValue($input);
        if ($value === 'choose_another_time') {
            $this->storeUserMessage($session, 'option', ['value' => $value]);

            return $this->enterChooseDate($session->fresh());
        }

        return $this->rejectInput($session, 'Please choose another time or restart.');
    }

    /**
     * @param  array{type?: string, value?: mixed}|null  $input
     * @return array<string, mixed>
     */
    protected function handleConfirmSummary(DiseaseChatbotSession $session, ?array $input): array
    {
        if ($input === null) {
            return $this->enterConfirmSummary($session);
        }
        if ($this->isBack($input)) {
            return $this->goBack($session);
        }
        if ($this->isRestart($input)) {
            return $this->restart($session);
        }

        $value = $this->optionValue($input);
        if ($value === 'change_time') {
            $this->storeUserMessage($session, 'option', ['value' => $value]);
            $this->pushHistory($session, DiseaseChatbotSession::STATE_CONFIRM_SUMMARY);

            return $this->enterChooseSlot($session->fresh());
        }
        if ($value === 'confirm_booking') {
            $this->storeUserMessage($session, 'option', ['value' => $value]);

            return $this->confirmAndBook($session->fresh());
        }

        return $this->rejectInput($session, 'Please choose an option to continue.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function confirmAndBook(DiseaseChatbotSession $session): array
    {
        $user = HIPUser::query()->findOrFail($session->user_id);

        try {
            $result = $this->bookingService->book($session, $user);
        } catch (SlotTakenException $e) {
            $doctorId = (string) data_get($session->context, 'selected_doctor_id', '');
            $date = (string) data_get($session->context, 'selected_date', '');
            $slotsByDate = $this->availability->slotsForDoctor($doctorId, (int) $session->hospital_id);
            $available = $slotsByDate[$date] ?? [];
            $this->touch($session, DiseaseChatbotSession::STATE_CHOOSE_SLOT);
            $notice = $this->storeBotMessage($session, 'text', [
                'text' => 'That time was just taken. Please pick another.',
            ]);
            $list = $this->storeBotMessage($session, 'slot_list', [
                'groups' => $this->availability->hourGroups($available),
            ]);
            $envelope = $this->envelope($session->fresh(), [$notice, $list], [
                'mode' => 'slot',
                'allow_text' => false,
                'placeholder' => null,
                'multi_select' => false,
                'min' => null,
                'max' => null,
            ], [
                $this->option('choose_doctor', 'Choose another doctor', 'neutral'),
                $this->option('choose_date', 'Choose another date', 'neutral'),
            ], true, true);
            $envelope['status'] = 422;

            throw new ChatbotFlowException($e->getMessage(), 422, $envelope);
        } catch (InvalidBookingStateException $e) {
            return $this->rejectInput($session, $e->getMessage());
        }

        if ($result['free']) {
            return $this->enterConfirmed($session->fresh(), $result['booking'], null);
        }

        return $this->enterPaymentPending($session->fresh(), $result['booking'], $result['payment'] ?? []);
    }

    /**
     * @param  array{type?: string, value?: mixed}|null  $input
     * @return array<string, mixed>
     */
    protected function handlePaymentPending(DiseaseChatbotSession $session, ?array $input): array
    {
        $status = $this->bookingService->readPaymentStatus($session);
        if ($status['paid'] && $status['booking']) {
            return $this->enterConfirmed($session->fresh(), $status['booking'], $status['invoice']);
        }

        $link = $status['link'] ?? $this->bookingService->existingLink($session);
        if ($link && $link->status === DiseaseChatbotBooking::STATUS_EXPIRED) {
            return $this->enterExpired($session->fresh());
        }

        if ($input === null) {
            return $this->enterPaymentPending(
                $session,
                $status['booking'] ?? DoctorBooking::query()->find((int) data_get($session->context, 'booking_id')),
                (array) data_get($session->context, 'payment', [])
            );
        }
        if ($this->isRestart($input)) {
            return $this->restart($session);
        }

        $type = (string) ($input['type'] ?? '');
        $value = $this->optionValue($input);

        if ($type === 'payment_check' || $value === 'payment_check') {
            $status = $this->bookingService->readPaymentStatus($session->fresh());
            if ($status['paid'] && $status['booking']) {
                return $this->enterConfirmed($session->fresh(), $status['booking'], $status['invoice']);
            }
            $link = $status['link'] ?? $this->bookingService->existingLink($session->fresh());
            if ($link && $link->status === DiseaseChatbotBooking::STATUS_EXPIRED) {
                return $this->enterExpired($session->fresh());
            }

            $bot = $this->storeBotMessage($session, 'text', [
                'text' => "We haven't received your payment yet.",
            ]);
            $this->touch($session, DiseaseChatbotSession::STATE_PAYMENT_PENDING);

            return $this->envelope($session->fresh(), [$bot], [
                'mode' => 'options',
                'allow_text' => false,
                'placeholder' => null,
                'multi_select' => false,
                'min' => null,
                'max' => null,
            ], [
                $this->option('pay_now', 'Pay now', 'success'),
                $this->option('restart', 'Start over', 'neutral'),
            ], false, true);
        }

        if ($value === 'pay_now') {
            $booking = $status['booking'] ?? DoctorBooking::query()->find((int) data_get($session->context, 'booking_id'));
            if (! $booking) {
                return $this->rejectInput($session, 'Booking not found. Please start over.');
            }
            // Reuses PaymentApiService::createDoctorBookingRazorpayOrder → findExistingPendingRazorpayOrder.
            $payment = $this->bookingService->refreshPaymentPayload($booking);
            $context = $session->context ?? [];
            $context['payment'] = $payment;
            $session->update(['context' => $context]);

            return $this->enterPaymentPending($session->fresh(), $booking, $payment);
        }

        return $this->rejectInput($session, 'Please complete payment or start over.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function enterAskSymptoms(DiseaseChatbotSession $session): array
    {
        $this->touch($session, DiseaseChatbotSession::STATE_ASK_SYMPTOMS);
        $intro = $this->storeBotMessage($session, 'text', [
            'text' => 'Select the symptoms you are experiencing. You can also type them.',
        ]);
        $picker = $this->symptomPickerMessage($session);

        return $this->envelope($session->fresh(), [$intro, $picker], [
            'mode' => 'multi_select',
            'allow_text' => true,
            'placeholder' => 'Type a symptom or pick from the list',
            'multi_select' => true,
            'min' => 1,
            'max' => (int) config('chatbot.max_symptoms', 60),
        ], [], true, true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function enterEmergencyNotice(DiseaseChatbotSession $session): array
    {
        $this->touch($session, DiseaseChatbotSession::STATE_EMERGENCY_NOTICE);
        $emergency = $this->storeEmergencyMessage($session);

        return $this->envelope($session->fresh(), [$emergency], [
            'mode' => 'options',
            'allow_text' => false,
            'placeholder' => null,
            'multi_select' => false,
            'min' => null,
            'max' => null,
        ], [
            $this->option('continue_anyway', 'Continue anyway', 'neutral'),
            $this->option('restart', 'Restart', 'neutral'),
        ], true, true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function enterShowResults(DiseaseChatbotSession $session, bool $withEmergencyBanner = false): array
    {
        $symptoms = array_values((array) data_get($session->context, 'symptoms', []));
        $freeText = data_get($session->context, 'free_text');
        $freeText = is_string($freeText) ? $freeText : null;
        $hospitalId = (int) $session->hospital_id;

        $keywordResults = $this->scoreDiseases($hospitalId, $symptoms);
        $candidates = $this->buildAiCandidates($hospitalId, $symptoms);

        $match = $this->symptomMatcher->resolve(
            $hospitalId,
            (string) $session->user_id,
            $symptoms,
            $freeText,
            $candidates,
            $keywordResults,
        );

        // AI may only escalate; hard-coded red flags are handled before this method.
        if ($match['red_flag'] === true) {
            $context = $session->context ?? [];
            $context['emergency_banner'] = true;
            $context['match_source'] = $match['source'];
            $context['red_flag_type'] = $match['red_flag_type'] ?? 'medical';
            $session->update(['context' => $context]);

            return $this->enterEmergencyNotice($session->fresh());
        }

        $results = $match['results'];
        $sanitizedFree = $this->symptomMatcher->sanitizeTypedText($freeText);

        $context = $session->context ?? [];
        $context['results'] = $results;
        $context['match_source'] = $match['source'];
        if ($withEmergencyBanner) {
            $context['emergency_banner'] = true;
        }
        $session->update(['context' => $context]);

        $messages = [];
        if ($withEmergencyBanner || data_get($context, 'emergency_banner')) {
            $messages[] = $this->storeEmergencyMessage($session);
        }

        if ($results === [] && $sanitizedFree !== '') {
            $display = $sanitizedFree;
            $this->touch($session, DiseaseChatbotSession::STATE_ASK_SYMPTOMS);
            $messages[] = $this->storeBotMessage($session, 'text', [
                'text' => 'I couldn\'t match '.$display.'. Please pick from the list',
            ]);
            $picker = $this->symptomPickerMessage($session);

            return $this->envelope($session->fresh(), array_merge($messages, [$picker]), [
                'mode' => 'multi_select',
                'allow_text' => true,
                'placeholder' => 'Type a symptom or pick from the list',
                'multi_select' => true,
                'min' => 1,
                'max' => (int) config('chatbot.max_symptoms', 60),
            ], [], true, true);
        }

        $this->touch($session, DiseaseChatbotSession::STATE_SHOW_RESULTS);

        if ($results === []) {
            $messages[] = $this->storeBotMessage($session, 'text', [
                'text' => 'I could not match those symptoms to a known condition for this hospital. Try choosing symptoms again.',
            ]);

            return $this->envelope($session->fresh(), $messages, [
                'mode' => 'options',
                'allow_text' => false,
                'placeholder' => null,
                'multi_select' => false,
                'min' => null,
                'max' => null,
            ], [
                $this->option('choose_again', 'Choose symptoms again', 'primary'),
                $this->option('restart', 'Restart', 'neutral'),
            ], true, true);
        }

        $messages[] = $this->storeBotMessage($session, 'disease_cards', [
            'items' => $results,
            'disclaimer' => (string) config('chatbot.disclaimer'),
        ]);
        $messages[] = $this->storeBotMessage($session, 'text', [
            'text' => 'Would you like to consult a doctor about this?',
        ]);
        // Results were shown; Back returns here. Consult question is the current step.
        $this->pushHistory($session, DiseaseChatbotSession::STATE_SHOW_RESULTS);
        $this->touch($session, DiseaseChatbotSession::STATE_ASK_CONSULT);

        return $this->envelope($session->fresh(), $messages, [
            'mode' => 'options',
            'allow_text' => false,
            'placeholder' => null,
            'multi_select' => false,
            'min' => null,
            'max' => null,
        ], [
            $this->option('yes', 'Yes', 'success'),
            $this->option('no', 'No', 'neutral'),
        ], true, true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function enterAskConsult(DiseaseChatbotSession $session): array
    {
        $this->touch($session, DiseaseChatbotSession::STATE_ASK_CONSULT);
        $bot = $this->storeBotMessage($session, 'text', [
            'text' => 'Would you like to consult a doctor about this?',
        ]);

        return $this->envelope($session->fresh(), [$bot], [
            'mode' => 'options',
            'allow_text' => false,
            'placeholder' => null,
            'multi_select' => false,
            'min' => null,
            'max' => null,
        ], [
            $this->option('yes', 'Yes', 'success'),
            $this->option('no', 'No', 'neutral'),
        ], true, true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function enterChooseDoctor(DiseaseChatbotSession $session): array
    {
        $masterId = (int) data_get($session->context, 'selected_speciality.id', 0);
        $specialityName = (string) data_get($session->context, 'selected_speciality.name', 'this speciality');
        $items = $this->availability->doctorsWithAvailability(
            (int) $session->hospital_id,
            $masterId,
            10
        );

        $context = $session->context ?? [];
        $context['doctor_list'] = $items;
        $session->update(['context' => $context]);
        $this->touch($session, DiseaseChatbotSession::STATE_CHOOSE_DOCTOR);

        $multiSpeciality = count($this->availableSpecialitiesFromResults($session)) > 1;
        $options = [];
        if ($multiSpeciality) {
            $options[] = $this->option('choose_speciality', 'Choose another speciality', 'neutral');
        }

        if ($items === []) {
            $bot = $this->storeBotMessage($session, 'text', [
                'text' => 'No doctors are available for '.$specialityName.' at this hospital right now.',
            ]);

            return $this->envelope($session->fresh(), [$bot], [
                'mode' => 'none',
                'allow_text' => false,
                'placeholder' => null,
                'multi_select' => false,
                'min' => null,
                'max' => null,
            ], $options, true, true);
        }

        $list = $this->storeBotMessage($session, 'doctor_list', [
            'items' => $items,
        ]);

        return $this->envelope($session->fresh(), [$list], [
            'mode' => 'doctor',
            'allow_text' => false,
            'placeholder' => null,
            'multi_select' => false,
            'min' => null,
            'max' => null,
        ], $options, true, true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function enterChooseDate(DiseaseChatbotSession $session): array
    {
        $doctorId = (string) data_get($session->context, 'selected_doctor_id', '');
        $slotsByDate = $this->availability->slotsForDoctor($doctorId, (int) $session->hospital_id);
        $items = $this->availability->datePickerItems($slotsByDate);

        $this->touch($session, DiseaseChatbotSession::STATE_CHOOSE_DATE);
        $options = [
            $this->option('choose_doctor', 'Choose another doctor', 'neutral'),
        ];

        if ($items === []) {
            $bot = $this->storeBotMessage($session, 'text', [
                'text' => 'No dates are available for this doctor right now.',
            ]);

            return $this->envelope($session->fresh(), [$bot], [
                'mode' => 'options',
                'allow_text' => false,
                'placeholder' => null,
                'multi_select' => false,
                'min' => null,
                'max' => null,
            ], $options, true, true);
        }

        $picker = $this->storeBotMessage($session, 'date_picker', [
            'items' => $items,
        ]);

        return $this->envelope($session->fresh(), [$picker], [
            'mode' => 'date',
            'allow_text' => false,
            'placeholder' => null,
            'multi_select' => false,
            'min' => null,
            'max' => null,
        ], $options, true, true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function enterChooseSlot(DiseaseChatbotSession $session): array
    {
        $doctorId = (string) data_get($session->context, 'selected_doctor_id', '');
        $date = (string) data_get($session->context, 'selected_date', '');
        $slotsByDate = $this->availability->slotsForDoctor($doctorId, (int) $session->hospital_id);
        $times = $slotsByDate[$date] ?? [];

        $this->touch($session, DiseaseChatbotSession::STATE_CHOOSE_SLOT);
        $list = $this->storeBotMessage($session, 'slot_list', [
            'groups' => $this->availability->hourGroups($times),
        ]);

        return $this->envelope($session->fresh(), [$list], [
            'mode' => 'slot',
            'allow_text' => false,
            'placeholder' => null,
            'multi_select' => false,
            'min' => null,
            'max' => null,
        ], [
            $this->option('choose_doctor', 'Choose another doctor', 'neutral'),
            $this->option('choose_date', 'Choose another date', 'neutral'),
        ], true, true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function enterAskCoins(DiseaseChatbotSession $session): array
    {
        $user = HIPUser::query()->findOrFail($session->user_id);
        $doctor = \App\Models\Doctor::query()->find((string) data_get($session->context, 'selected_doctor_id', ''));
        $fee = (float) ($doctor?->consultation_fee ?? 0);
        $preview = $this->bookingService->coinPreview($user, $fee);
        $n = (int) $preview['balance'];
        $m = (int) $preview['max_usable'];

        if ($n <= 0 || $m <= 0) {
            $context = $session->context ?? [];
            $context['coins_used'] = 0;
            $session->update(['context' => $context]);

            return $this->enterConfirmSummary($session->fresh());
        }

        $rate = $this->bookingService->amountForOneCoin();
        $rupeeOff = (int) round($m * $rate);

        $this->touch($session, DiseaseChatbotSession::STATE_ASK_COINS);
        $bot = $this->storeBotMessage($session, 'text', [
            'text' => 'You have '.$n.' coins. You can use up to '.$m.' on this consultation ('
                .$m.' coins = ₹'.$rupeeOff.' off).',
        ]);

        return $this->envelope($session->fresh(), [$bot], [
            'mode' => 'options',
            'allow_text' => false,
            'placeholder' => null,
            'multi_select' => false,
            'min' => null,
            'max' => null,
        ], [
            $this->option('use_max', 'Use '.$m.' coins', 'success'),
            $this->option('use_none', "Don't use coins", 'neutral'),
            $this->option('use_fewer', 'Use fewer', 'neutral'),
        ], true, true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function enterAskCoinsAmount(DiseaseChatbotSession $session): array
    {
        $user = HIPUser::query()->findOrFail($session->user_id);
        $doctor = \App\Models\Doctor::query()->find((string) data_get($session->context, 'selected_doctor_id', ''));
        $fee = (float) ($doctor?->consultation_fee ?? 0);
        $preview = $this->bookingService->coinPreview($user, $fee);
        $m = (int) $preview['max_usable'];
        $rate = $this->bookingService->amountForOneCoin();
        $rupeeOff = (int) round($m * $rate);

        $this->touch($session, DiseaseChatbotSession::STATE_ASK_COINS_AMOUNT);
        $bot = $this->storeBotMessage($session, 'text', [
            'text' => $m.' coins = ₹'.$rupeeOff.' off. Enter how many coins to use (1–'.$m.').',
        ]);

        return $this->envelope($session->fresh(), [$bot], [
            'mode' => 'number',
            'allow_text' => false,
            'placeholder' => 'Coins (1–'.$m.')',
            'multi_select' => false,
            'min' => 1,
            'max' => $m,
        ], [
            $this->option('use_max', 'Use '.$m.' coins', 'success'),
            $this->option('use_none', "Don't use coins", 'neutral'),
        ], true, true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function enterExpired(DiseaseChatbotSession $session): array
    {
        $this->touch($session, DiseaseChatbotSession::STATE_EXPIRED);
        $bot = $this->storeBotMessage($session, 'text', [
            'text' => "Your slot was released because payment wasn't completed.",
        ]);

        return $this->envelope($session->fresh(), [$bot], [
            'mode' => 'options',
            'allow_text' => false,
            'placeholder' => null,
            'multi_select' => false,
            'min' => null,
            'max' => null,
        ], [
            $this->option('choose_another_time', 'Choose another time', 'success'),
            $this->option('restart', 'Restart', 'neutral'),
        ], false, true);
    }

    /**
     * When the chatbot booking link is expired (and unpaid), surface EXPIRED.
     *
     * @param  array{type?: string, value?: mixed}|null  $input
     * @return array<string, mixed>|null
     */
    protected function maybeEnterExpired(DiseaseChatbotSession $session, ?array $input = null): ?array
    {
        $link = $this->bookingService->existingLink($session);
        if (! $link || $link->status !== DiseaseChatbotBooking::STATUS_EXPIRED) {
            return null;
        }

        $status = $this->bookingService->readPaymentStatus($session);
        if ($status['paid'] && $status['booking']) {
            return $this->enterConfirmed($session->fresh(), $status['booking'], $status['invoice']);
        }

        // Already in EXPIRED: let handleExpired / show history handle the request.
        if ($session->state === DiseaseChatbotSession::STATE_EXPIRED) {
            return null;
        }

        return $this->enterExpired($session->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    protected function enterConfirmSummary(DiseaseChatbotSession $session): array
    {
        $doctorId = (string) data_get($session->context, 'selected_doctor_id', '');
        $doctor = \App\Models\Doctor::query()->find($doctorId);
        $hospital = Hospital::query()->find((int) $session->hospital_id);
        $speciality = (array) data_get($session->context, 'selected_speciality', []);
        $results = (array) data_get($session->context, 'results', []);
        $diseaseName = (string) data_get($results, '0.name', 'Consultation');
        $symptoms = array_values((array) data_get($session->context, 'symptoms', []));
        $types = (array) config('chatbot.appointment_types', ['In-Clinic']);
        $appointmentType = (string) ($types[0] ?? 'In-Clinic');
        $coinsUsed = max(0, (int) data_get($session->context, 'coins_used', 0));
        $fee = (float) ($doctor?->consultation_fee ?? 0);
        $breakdown = $this->bookingService->feeBreakdown($fee, $coinsUsed);

        $summary = [
            'doctor' => [
                'id' => $doctorId,
                'name' => (string) ($doctor?->name ?? ''),
                'photo_url' => $doctor?->doctor_image
                    ? url('storage/doctor/'.$doctor->doctor_image)
                    : null,
            ],
            'speciality' => [
                'id' => (int) ($speciality['id'] ?? 0),
                'name' => (string) ($speciality['name'] ?? ''),
            ],
            'hospital' => [
                'id' => (int) $session->hospital_id,
                'name' => (string) ($hospital?->name ?? ''),
            ],
            'date' => (string) data_get($session->context, 'selected_date', ''),
            'time' => (string) data_get($session->context, 'selected_slot', ''),
            'consultation_fee' => $fee,
            'appointment_type' => $appointmentType,
            'reason_of_visit' => $diseaseName,
            'message' => implode(', ', $symptoms),
            'coins_applied' => $breakdown['coins_applied'],
            'breakdown' => [
                'consultation_fee' => $breakdown['consultation_fee'],
                'coin_discount' => $breakdown['coin_discount'],
                'service_charge' => $breakdown['service_charge'],
                'taxes' => $breakdown['taxes'],
                'total_payable' => $breakdown['total_payable'],
            ],
        ];

        $context = $session->context ?? [];
        $context['booking_summary'] = $summary;
        $session->update(['context' => $context]);
        $this->touch($session, DiseaseChatbotSession::STATE_CONFIRM_SUMMARY);

        $card = $this->storeBotMessage($session, 'summary', $summary);

        return $this->envelope($session->fresh(), [$card], [
            'mode' => 'options',
            'allow_text' => false,
            'placeholder' => null,
            'multi_select' => false,
            'min' => null,
            'max' => null,
        ], [
            $this->option('confirm_booking', 'Confirm booking', 'success'),
            $this->option('change_time', 'Change time', 'neutral'),
        ], true, true);
    }

    /**
     * @param  array<string, mixed>  $payment
     * @return array<string, mixed>
     */
    protected function enterPaymentPending(DiseaseChatbotSession $session, ?DoctorBooking $booking, array $payment): array
    {
        $link = $this->bookingService->existingLink($session);
        if ($link && $link->status === DiseaseChatbotBooking::STATUS_EXPIRED) {
            return $this->enterExpired($session);
        }

        $expiresIn = $this->bookingService->expiresInSeconds($link?->expires_at);

        $summary = (array) data_get($session->context, 'booking_summary', []);
        $this->touch($session, DiseaseChatbotSession::STATE_PAYMENT_PENDING);
        $msg = $this->storeBotMessage($session, 'payment', [
            'payment' => $payment,
            'summary' => $summary,
            'expires_in_seconds' => $expiresIn,
        ]);

        return $this->envelope($session->fresh(), [$msg], [
            'mode' => 'payment',
            'allow_text' => false,
            'placeholder' => null,
            'multi_select' => false,
            'min' => null,
            'max' => null,
        ], [
            $this->option('pay_now', 'Pay now', 'success'),
            $this->option('restart', 'Start over', 'neutral'),
        ], false, true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function enterConfirmed(
        DiseaseChatbotSession $session,
        DoctorBooking $booking,
        $invoice = null
    ): array {
        $link = $this->bookingService->existingLink($session);
        if ($link && $link->status !== DiseaseChatbotBooking::STATUS_CONFIRMED) {
            $link->update(['status' => DiseaseChatbotBooking::STATUS_CONFIRMED]);
        }

        $hospital = Hospital::query()->find((int) $session->hospital_id);
        $doctor = $booking->relationLoaded('doctor') ? $booking->doctor : \App\Models\Doctor::query()->find($booking->doctor_id);
        $payload = [
            'booking_id' => (int) $booking->id,
            'doctor' => [
                'id' => (string) $booking->doctor_id,
                'name' => (string) ($doctor?->name ?? ''),
            ],
            'hospital' => [
                'id' => (int) $session->hospital_id,
                'name' => (string) ($hospital?->name ?? ''),
            ],
            'date' => $booking->booking_date?->format('Y-m-d') ?? (string) data_get($session->context, 'selected_date', ''),
            'time' => is_array($booking->required_time_slots)
                ? (string) ($booking->required_time_slots[0] ?? '')
                : (string) data_get($session->context, 'selected_slot', ''),
            'amount_paid' => (float) ($booking->total_amount ?? 0),
            'coins_applied' => (int) ($booking->coins_used ?? 0),
            'invoice_id' => $booking->invoice_id ? (int) $booking->invoice_id : ($invoice?->id ? (int) $invoice->id : null),
        ];

        $this->touch($session, DiseaseChatbotSession::STATE_CONFIRMED, DiseaseChatbotSession::STATUS_COMPLETED);
        $msg = $this->storeBotMessage($session, 'confirmation', $payload);

        return $this->envelope($session->fresh(), [$msg], [
            'mode' => 'none',
            'allow_text' => false,
            'placeholder' => null,
            'multi_select' => false,
            'min' => null,
            'max' => null,
        ], [], false, true);
    }

    protected function doctorStillEligible(DiseaseChatbotSession $session, string $doctorId, int $masterId): bool
    {
        if ($doctorId === '' || $masterId <= 0) {
            return false;
        }
        $list = $this->availability->doctorsWithAvailability(
            (int) $session->hospital_id,
            $masterId,
            50
        );

        return in_array($doctorId, collect($list)->pluck('id')->all(), true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function enterCompleted(DiseaseChatbotSession $session): array
    {
        $results = (array) data_get($session->context, 'results', []);
        $tests = collect($results)
            ->flatMap(fn ($r) => (array) ($r['recommended_tests'] ?? []))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $testsLabel = $tests !== [] ? implode(', ', $tests) : 'none listed';
        $text = 'Okay. If your symptoms get worse or you feel unwell, please see a doctor.'
            ."\nTests a clinician may suggest: ".$testsLabel.'. '
            .(string) config('chatbot.disclaimer');

        $this->touch($session, DiseaseChatbotSession::STATE_COMPLETED, DiseaseChatbotSession::STATUS_COMPLETED);
        $bot = $this->storeBotMessage($session, 'text', [
            'text' => $text,
        ]);

        return $this->envelope($session->fresh(), [$bot], [
            'mode' => 'none',
            'allow_text' => false,
            'placeholder' => null,
            'multi_select' => false,
            'min' => null,
            'max' => null,
        ], [], false, true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function goBack(DiseaseChatbotSession $session): array
    {
        $context = $session->context ?? [];
        $history = array_values((array) ($context['history'] ?? []));
        $previous = array_pop($history);
        $context['history'] = $history;
        $session->update(['context' => $context]);

        if (! is_string($previous) || $previous === '') {
            return $this->handleMenu($session->fresh(), null, true);
        }

        $this->storeUserMessage($session, 'option', ['value' => 'back']);

        return match ($previous) {
            DiseaseChatbotSession::STATE_MENU => $this->handleMenu($session->fresh(), null, true),
            DiseaseChatbotSession::STATE_ASK_SYMPTOMS => $this->enterAskSymptoms($session->fresh()),
            DiseaseChatbotSession::STATE_EMERGENCY_NOTICE => $this->enterEmergencyNotice($session->fresh()),
            DiseaseChatbotSession::STATE_SHOW_RESULTS => $this->enterShowResults(
                $session->fresh(),
                (bool) data_get($session->fresh()->context, 'emergency_banner')
            ),
            DiseaseChatbotSession::STATE_ASK_CONSULT => $this->enterAskConsult($session->fresh()),
            DiseaseChatbotSession::STATE_CHOOSE_PERSON => $this->enterChoosePerson($session->fresh()),
            DiseaseChatbotSession::STATE_CHOOSE_SPECIALITY => $this->enterChooseSpeciality(
                $session->fresh(),
                $this->availableSpecialitiesFromResults($session->fresh())
            ),
            DiseaseChatbotSession::STATE_CHOOSE_DOCTOR => $this->enterChooseDoctor($session->fresh()),
            DiseaseChatbotSession::STATE_CHOOSE_DATE => $this->enterChooseDate($session->fresh()),
            DiseaseChatbotSession::STATE_CHOOSE_SLOT => $this->enterChooseSlot($session->fresh()),
            DiseaseChatbotSession::STATE_ASK_COINS => $this->enterAskCoins($session->fresh()),
            DiseaseChatbotSession::STATE_ASK_COINS_AMOUNT => $this->enterAskCoinsAmount($session->fresh()),
            DiseaseChatbotSession::STATE_CONFIRM_SUMMARY => $this->enterConfirmSummary($session->fresh()),
            default => $this->handleMenu($session->fresh(), null, true),
        };
    }

    /**
     * @param  array{type?: string, value?: mixed}|null  $input
     */
    protected function isBack(?array $input): bool
    {
        return $this->optionValue($input) === 'back'
            || (string) ($input['type'] ?? '') === 'back';
    }

    /**
     * @param  array{type?: string, value?: mixed}|null  $input
     */
    protected function isRestart(?array $input): bool
    {
        return $this->optionValue($input) === 'restart'
            || (string) ($input['type'] ?? '') === 'restart';
    }

    /**
     * @param  array{type?: string, value?: mixed}|null  $input
     */
    protected function optionValue(?array $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $type = (string) ($input['type'] ?? '');
        $value = $input['value'] ?? null;

        if ($type === 'option' && is_string($value)) {
            return $value;
        }

        if (is_string($value) && in_array($value, [
            'back', 'restart', 'check_disease', 'choose_again', 'continue_consult',
            'continue_anyway', 'yes', 'no',
        ], true)) {
            return $value;
        }

        return is_string($value) ? $value : null;
    }

    protected function pushHistory(DiseaseChatbotSession $session, string $state): void
    {
        $context = $session->fresh()->context ?? [];
        $history = array_values((array) ($context['history'] ?? []));
        $history[] = $state;
        $context['history'] = $history;
        $session->update(['context' => $context]);
    }

    protected function touch(
        DiseaseChatbotSession $session,
        string $state,
        string $status = DiseaseChatbotSession::STATUS_ACTIVE
    ): void {
        $session->update([
            'state' => $state,
            'status' => $status,
            'last_activity_at' => now(),
            'expires_at' => now()->addMinutes((int) config('chatbot.idle_minutes', 30)),
        ]);
        $session->refresh();
    }

    protected function assertOwnedNotForeign(DiseaseChatbotSession $session): void
    {
        // Ownership is enforced by findOwnedSession; this is a no-op guard for clarity.
    }

    protected function assertOwnedActive(DiseaseChatbotSession $session): void
    {
        $this->refreshExpiryOrThrow($session);

        if ($session->status === DiseaseChatbotSession::STATUS_EXPIRED) {
            throw new ChatbotFlowException(
                'This session expired after 30 minutes of inactivity. Restart to continue.',
                410,
                [
                    'status' => 410,
                    'code' => 'session_expired',
                    'message' => 'This session expired after 30 minutes of inactivity. Restart to continue.',
                    'session_id' => (string) $session->id,
                    'hint' => 'POST /api/chatbot/sessions/'.$session->id.'/restart',
                ],
                'session_expired'
            );
        }
    }

    protected function refreshExpiryOrThrow(DiseaseChatbotSession $session): void
    {
        if ($session->isExpiredByIdle()) {
            $session->update(['status' => DiseaseChatbotSession::STATUS_EXPIRED]);
            throw new ChatbotFlowException(
                'This session expired after 30 minutes of inactivity. Restart to continue.',
                410,
                [
                    'status' => 410,
                    'code' => 'session_expired',
                    'message' => 'This session expired after 30 minutes of inactivity. Restart to continue.',
                    'session_id' => (string) $session->id,
                    'hint' => 'POST /api/chatbot/sessions/'.$session->id.'/restart',
                ],
                'session_expired'
            );
        }
    }

    /**
     * @return never
     */
    protected function rejectInput(DiseaseChatbotSession $session, string $message): array
    {
        $bot = $this->storeBotMessage($session, 'text', ['text' => $message]);
        $step = $this->stepUiForState($session->fresh(), $session->state);
        $envelope = $this->envelope(
            $session->fresh(),
            [$bot],
            $step['input'],
            $step['options'],
            $step['allow_back'],
            $step['allow_restart']
        );
        $envelope['status'] = 422;

        throw new ChatbotFlowException($message, 422, $envelope);
    }

    /**
     * @return array{input: array<string, mixed>, options: list<array<string, mixed>>, allow_back: bool, allow_restart: bool}
     */
    protected function stepUiForState(DiseaseChatbotSession $session, string $state): array
    {
        return match ($state) {
            DiseaseChatbotSession::STATE_MENU => [
                'input' => [
                    'mode' => 'options',
                    'allow_text' => false,
                    'placeholder' => null,
                    'multi_select' => false,
                    'min' => null,
                    'max' => null,
                ],
                'options' => [
                    $this->option('check_disease', 'Check my disease from my symptoms', 'primary'),
                ],
                'allow_back' => false,
                'allow_restart' => true,
            ],
            DiseaseChatbotSession::STATE_ASK_SYMPTOMS => [
                'input' => [
                    'mode' => 'multi_select',
                    'allow_text' => true,
                    'placeholder' => 'Type a symptom or pick from the list',
                    'multi_select' => true,
                    'min' => 1,
                    'max' => (int) config('chatbot.max_symptoms', 60),
                ],
                'options' => [],
                'allow_back' => true,
                'allow_restart' => true,
            ],
            DiseaseChatbotSession::STATE_EMERGENCY_NOTICE => [
                'input' => [
                    'mode' => 'options',
                    'allow_text' => false,
                    'placeholder' => null,
                    'multi_select' => false,
                    'min' => null,
                    'max' => null,
                ],
                'options' => [
                    $this->option('continue_anyway', 'Continue anyway', 'neutral'),
                    $this->option('restart', 'Restart', 'neutral'),
                ],
                'allow_back' => true,
                'allow_restart' => true,
            ],
            DiseaseChatbotSession::STATE_SHOW_RESULTS => [
                'input' => [
                    'mode' => 'options',
                    'allow_text' => false,
                    'placeholder' => null,
                    'multi_select' => false,
                    'min' => null,
                    'max' => null,
                ],
                'options' => [
                    $this->option('choose_again', 'Choose symptoms again', 'primary'),
                    $this->option('restart', 'Restart', 'neutral'),
                ],
                'allow_back' => true,
                'allow_restart' => true,
            ],
            DiseaseChatbotSession::STATE_ASK_CONSULT => [
                'input' => [
                    'mode' => 'options',
                    'allow_text' => false,
                    'placeholder' => null,
                    'multi_select' => false,
                    'min' => null,
                    'max' => null,
                ],
                'options' => [
                    $this->option('yes', 'Yes', 'success'),
                    $this->option('no', 'No', 'neutral'),
                ],
                'allow_back' => true,
                'allow_restart' => true,
            ],
            DiseaseChatbotSession::STATE_CHOOSE_PERSON => [
                'input' => [
                    'mode' => 'options',
                    'allow_text' => false,
                    'placeholder' => null,
                    'multi_select' => false,
                    'min' => null,
                    'max' => null,
                ],
                'options' => [],
                'allow_back' => true,
                'allow_restart' => true,
            ],
            DiseaseChatbotSession::STATE_CHOOSE_SPECIALITY => [
                'input' => [
                    'mode' => 'options',
                    'allow_text' => false,
                    'placeholder' => null,
                    'multi_select' => false,
                    'min' => null,
                    'max' => null,
                ],
                'options' => collect($this->availableSpecialitiesFromResults($session))
                    ->map(fn (array $row) => $this->option('spec:'.$row['id'], $row['name'], 'primary'))
                    ->values()
                    ->all(),
                'allow_back' => true,
                'allow_restart' => true,
            ],
            DiseaseChatbotSession::STATE_CHOOSE_DOCTOR => [
                'input' => [
                    'mode' => 'doctor',
                    'allow_text' => false,
                    'placeholder' => null,
                    'multi_select' => false,
                    'min' => null,
                    'max' => null,
                ],
                'options' => count($this->availableSpecialitiesFromResults($session)) > 1
                    ? [$this->option('choose_speciality', 'Choose another speciality', 'neutral')]
                    : [],
                'allow_back' => true,
                'allow_restart' => true,
            ],
            DiseaseChatbotSession::STATE_CHOOSE_DATE => [
                'input' => [
                    'mode' => 'date',
                    'allow_text' => false,
                    'placeholder' => null,
                    'multi_select' => false,
                    'min' => null,
                    'max' => null,
                ],
                'options' => [
                    $this->option('choose_doctor', 'Choose another doctor', 'neutral'),
                ],
                'allow_back' => true,
                'allow_restart' => true,
            ],
            DiseaseChatbotSession::STATE_CHOOSE_SLOT => [
                'input' => [
                    'mode' => 'slot',
                    'allow_text' => false,
                    'placeholder' => null,
                    'multi_select' => false,
                    'min' => null,
                    'max' => null,
                ],
                'options' => [
                    $this->option('choose_doctor', 'Choose another doctor', 'neutral'),
                    $this->option('choose_date', 'Choose another date', 'neutral'),
                ],
                'allow_back' => true,
                'allow_restart' => true,
            ],
            DiseaseChatbotSession::STATE_ASK_COINS => [
                'input' => [
                    'mode' => 'options',
                    'allow_text' => false,
                    'placeholder' => null,
                    'multi_select' => false,
                    'min' => null,
                    'max' => null,
                ],
                'options' => [
                    $this->option('use_max', 'Use coins', 'success'),
                    $this->option('use_none', "Don't use coins", 'neutral'),
                    $this->option('use_fewer', 'Use fewer', 'neutral'),
                ],
                'allow_back' => true,
                'allow_restart' => true,
            ],
            DiseaseChatbotSession::STATE_ASK_COINS_AMOUNT => [
                'input' => [
                    'mode' => 'number',
                    'allow_text' => false,
                    'placeholder' => 'Coins',
                    'multi_select' => false,
                    'min' => 1,
                    'max' => null,
                ],
                'options' => [
                    $this->option('use_max', 'Use coins', 'success'),
                    $this->option('use_none', "Don't use coins", 'neutral'),
                ],
                'allow_back' => true,
                'allow_restart' => true,
            ],
            DiseaseChatbotSession::STATE_EXPIRED => [
                'input' => [
                    'mode' => 'options',
                    'allow_text' => false,
                    'placeholder' => null,
                    'multi_select' => false,
                    'min' => null,
                    'max' => null,
                ],
                'options' => [
                    $this->option('choose_another_time', 'Choose another time', 'success'),
                    $this->option('restart', 'Restart', 'neutral'),
                ],
                'allow_back' => false,
                'allow_restart' => true,
            ],
            DiseaseChatbotSession::STATE_CONFIRM_SUMMARY => [
                'input' => [
                    'mode' => 'options',
                    'allow_text' => false,
                    'placeholder' => null,
                    'multi_select' => false,
                    'min' => null,
                    'max' => null,
                ],
                'options' => [
                    $this->option('confirm_booking', 'Confirm booking', 'success'),
                    $this->option('change_time', 'Change time', 'neutral'),
                ],
                'allow_back' => true,
                'allow_restart' => true,
            ],
            DiseaseChatbotSession::STATE_PAYMENT_PENDING => [
                'input' => [
                    'mode' => 'payment',
                    'allow_text' => false,
                    'placeholder' => null,
                    'multi_select' => false,
                    'min' => null,
                    'max' => null,
                ],
                'options' => [
                    $this->option('pay_now', 'Pay now', 'success'),
                    $this->option('restart', 'Start over', 'neutral'),
                ],
                'allow_back' => false,
                'allow_restart' => true,
            ],
            DiseaseChatbotSession::STATE_CONFIRMED,
            DiseaseChatbotSession::STATE_COMPLETED,
            DiseaseChatbotSession::STATE_DISABLED => [
                'input' => [
                    'mode' => 'none',
                    'allow_text' => false,
                    'placeholder' => null,
                    'multi_select' => false,
                    'min' => null,
                    'max' => null,
                ],
                'options' => [],
                'allow_back' => false,
                'allow_restart' => true,
            ],
            default => [
                'input' => [
                    'mode' => 'none',
                    'allow_text' => false,
                    'placeholder' => null,
                    'multi_select' => false,
                    'min' => null,
                    'max' => null,
                ],
                'options' => [],
                'allow_back' => false,
                'allow_restart' => true,
            ],
        };
    }

    protected function symptomPickerMessage(DiseaseChatbotSession $session): DiseaseChatbotMessage
    {
        $items = $this->symptomCatalog((int) $session->hospital_id);

        return $this->storeBotMessage($session, 'symptom_picker', [
            'items' => $items,
        ]);
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    protected function symptomCatalog(int $hospitalId): array
    {
        $masterIds = Speciality::query()
            ->enabledForChatbot($hospitalId)
            ->pluck('speciality_master_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $labels = [];
        SpecialitiesMaster::query()
            ->whereIn('id', $masterIds)
            ->where('status', 'active')
            ->get()
            ->each(function (SpecialitiesMaster $master) use (&$labels) {
                foreach ($master->normalizedDiseaseEntries() as $disease) {
                    foreach ((array) ($disease['symptoms'] ?? []) as $symptom) {
                        $label = trim((string) $symptom);
                        if ($label === '') {
                            continue;
                        }
                        $labels[mb_strtolower($label)] = $label;
                    }
                }
            });

        ksort($labels, SORT_NATURAL | SORT_FLAG_CASE);

        $max = (int) config('chatbot.max_symptoms', 60);
        $slice = array_slice(array_values($labels), 0, $max);

        return collect($slice)
            ->map(fn (string $label) => [
                'id' => $this->slugLabel($label),
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    protected function hospitalHasChatbotDiseases(int $hospitalId): bool
    {
        return $this->symptomCatalog($hospitalId) !== []
            || Speciality::query()
                ->enabledForChatbot($hospitalId)
                ->with('specialityMaster')
                ->get()
                ->contains(fn (Speciality $row) => $row->specialityMaster
                    && $row->specialityMaster->normalizedDiseaseEntries() !== []);
    }

    /**
     * Keyword-overlap disease cards (top 3). Fallback when AI is skipped or fails.
     *
     * @param  list<string>  $chosenSymptoms
     * @return list<array<string, mixed>>
     */
    protected function scoreDiseases(int $hospitalId, array $chosenSymptoms): array
    {
        return collect($this->collectDiseaseRows($hospitalId, $chosenSymptoms))
            ->filter(fn (array $row) => ($row['_overlap'] ?? 0) > 0)
            ->sort(fn (array $a, array $b) => [$b['_overlap'], $b['_ratio'], $a['name']] <=> [$a['_overlap'], $a['_ratio'], $b['name']])
            ->take(3)
            ->map(fn (array $item) => collect($item)->except(['_overlap', '_ratio', 'symptoms'])->all())
            ->values()
            ->all();
    }

    /**
     * Candidate list for the model: keyword-overlap first, then by name. Cap 60.
     * Each entry includes metadata for card mapping; only id/name/symptoms are sent to the API.
     *
     * @param  list<string>  $chosenSymptoms
     * @return list<array<string, mixed>>
     */
    protected function buildAiCandidates(int $hospitalId, array $chosenSymptoms): array
    {
        $rows = $this->collectDiseaseRows($hospitalId, $chosenSymptoms);

        usort($rows, function (array $a, array $b) {
            $ao = (int) ($a['_overlap'] ?? 0);
            $bo = (int) ($b['_overlap'] ?? 0);
            if ($ao !== $bo) {
                return $bo <=> $ao;
            }

            return strcasecmp((string) $a['name'], (string) $b['name']);
        });

        return collect($rows)
            ->take(60)
            ->map(fn (array $row) => [
                'id' => (string) $row['id'],
                'name' => (string) $row['name'],
                'symptoms' => array_values(array_slice((array) ($row['symptoms'] ?? []), 0, 12)),
                'about' => $row['about'] ?? null,
                'recommended_tests' => array_values((array) ($row['recommended_tests'] ?? [])),
                'speciality' => $row['speciality'] ?? ['id' => 0, 'name' => ''],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $chosenSymptoms
     * @return list<array<string, mixed>>
     */
    protected function collectDiseaseRows(int $hospitalId, array $chosenSymptoms): array
    {
        $chosenKeys = collect($chosenSymptoms)
            ->map(fn ($s) => mb_strtolower(trim((string) $s)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $masterRows = Speciality::query()
            ->enabledForChatbot($hospitalId)
            ->with('specialityMaster')
            ->get();

        $rows = [];

        foreach ($masterRows as $row) {
            $master = $row->specialityMaster;
            if (! $master) {
                continue;
            }

            foreach ($master->normalizedDiseaseEntries() as $disease) {
                $diseaseSymptoms = collect((array) ($disease['symptoms'] ?? []))
                    ->map(fn ($s) => trim((string) $s))
                    ->filter()
                    ->values();

                $matched = $diseaseSymptoms
                    ->filter(fn ($s) => in_array(mb_strtolower($s), $chosenKeys, true))
                    ->values()
                    ->all();

                $overlap = count($matched);
                $total = max(1, $diseaseSymptoms->count());
                $ratio = $overlap / $total;
                $confidence = $ratio >= 0.66 ? 'high' : ($ratio >= 0.33 ? 'medium' : 'low');
                $diseaseName = (string) $disease['name'];

                $rows[] = [
                    'id' => (int) $master->id.':'.$this->slugLabel($diseaseName),
                    'name' => $diseaseName,
                    'about' => $disease['about'] ?? null,
                    'matched_symptoms' => $matched,
                    'recommended_tests' => array_values((array) ($disease['recommended_tests'] ?? [])),
                    'speciality' => [
                        'id' => (int) $master->id,
                        'name' => (string) $master->name,
                    ],
                    'confidence' => $confidence,
                    'symptoms' => $diseaseSymptoms->all(),
                    '_overlap' => $overlap,
                    '_ratio' => $ratio,
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function storeBotMessage(DiseaseChatbotSession $session, string $type, array $payload): DiseaseChatbotMessage
    {
        return DiseaseChatbotMessage::query()->create([
            'session_id' => $session->id,
            'sender' => DiseaseChatbotMessage::SENDER_BOT,
            'type' => $type,
            'payload' => $payload,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function storeUserMessage(DiseaseChatbotSession $session, string $type, array $payload): DiseaseChatbotMessage
    {
        return DiseaseChatbotMessage::query()->create([
            'session_id' => $session->id,
            'sender' => DiseaseChatbotMessage::SENDER_USER,
            'type' => $type,
            'payload' => $payload,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatMessage(DiseaseChatbotMessage $message): array
    {
        $payload = is_array($message->payload) ? $message->payload : [];
        $base = [
            'id' => (int) $message->id,
            'sender' => (string) $message->sender,
            'type' => (string) $message->type,
        ];

        if ($message->type === 'text') {
            $base['text'] = (string) ($payload['text'] ?? '');
        } elseif ($message->type === 'symptom_picker') {
            $base['items'] = array_values((array) ($payload['items'] ?? []));
        } elseif ($message->type === 'disease_cards') {
            $base['items'] = array_values((array) ($payload['items'] ?? []));
            $base['disclaimer'] = (string) ($payload['disclaimer'] ?? config('chatbot.disclaimer'));
        } elseif ($message->type === 'doctor_list') {
            $base['items'] = array_values((array) ($payload['items'] ?? []));
        } elseif ($message->type === 'date_picker') {
            $base['items'] = array_values((array) ($payload['items'] ?? []));
        } elseif ($message->type === 'slot_list') {
            $base['groups'] = array_values((array) ($payload['groups'] ?? []));
        } elseif ($message->type === 'summary') {
            $base = array_merge($base, $payload);
            $base['type'] = 'summary';
        } elseif ($message->type === 'payment') {
            $base['payment'] = $payload['payment'] ?? null;
            $base['summary'] = $payload['summary'] ?? null;
            $base['expires_in_seconds'] = (int) ($payload['expires_in_seconds'] ?? 0);
        } elseif ($message->type === 'confirmation') {
            $base = array_merge($base, $payload);
            $base['type'] = 'confirmation';
        } elseif ($message->type === 'emergency') {
            $base['text'] = (string) ($payload['text'] ?? '');
            $base['variant'] = (string) ($payload['variant'] ?? 'danger');
            $base['red_flag_type'] = (string) ($payload['red_flag_type'] ?? 'medical');
            if (isset($payload['emergency_number'])) {
                $base['emergency_number'] = (string) $payload['emergency_number'];
            }
            if (isset($payload['crisis_number'])) {
                $base['crisis_number'] = (string) $payload['crisis_number'];
            }
            $base['button'] = $payload['button'] ?? null;
            if (isset($payload['secondary_button'])) {
                $base['secondary_button'] = $payload['secondary_button'];
            }
        } else {
            $base = array_merge($base, $payload);
        }

        return $base;
    }

    protected function storeEmergencyMessage(DiseaseChatbotSession $session): DiseaseChatbotMessage
    {
        $type = (string) data_get($session->context, 'red_flag_type', 'medical');
        if ($type === 'mental_health') {
            return $this->storeCrisisEmergencyMessage($session);
        }

        return $this->storeMedicalEmergencyMessage($session);
    }

    protected function storeMedicalEmergencyMessage(DiseaseChatbotSession $session): DiseaseChatbotMessage
    {
        $number = (string) config('chatbot.emergency_number', '112');
        $danger = (array) config('chatbot.styles.danger', []);

        return $this->storeBotMessage($session, 'emergency', [
            'text' => (string) config('chatbot.emergency_text'),
            'variant' => 'danger',
            'red_flag_type' => 'medical',
            'emergency_number' => $number,
            'button' => [
                'id' => 'call_emergency',
                'label' => 'Call '.$number,
                'variant' => 'danger',
                'action' => 'tel:'.$number,
                'style' => [
                    'variant' => (string) ($danger['variant'] ?? 'danger'),
                    'bg' => (string) ($danger['bg'] ?? '#dc2626'),
                    'text' => (string) ($danger['text'] ?? '#ffffff'),
                    'border' => (string) ($danger['border'] ?? '#dc2626'),
                    'icon' => $danger['icon'] ?? null,
                ],
            ],
        ]);
    }

    protected function storeCrisisEmergencyMessage(DiseaseChatbotSession $session): DiseaseChatbotMessage
    {
        $crisis = (string) config('chatbot.crisis_number', '14416');
        $emergency = (string) config('chatbot.emergency_number', '112');
        $crisisStyle = (array) config('chatbot.styles.crisis', config('chatbot.styles.primary'));
        $danger = (array) config('chatbot.styles.danger', []);

        return $this->storeBotMessage($session, 'emergency', [
            'text' => (string) config('chatbot.crisis_text'),
            'variant' => 'crisis',
            'red_flag_type' => 'mental_health',
            'crisis_number' => $crisis,
            'emergency_number' => $emergency,
            'button' => [
                'id' => 'call_crisis',
                'label' => 'Call Tele-MANAS '.$crisis,
                'variant' => 'crisis',
                'action' => 'tel:'.$crisis,
                'style' => [
                    'variant' => (string) ($crisisStyle['variant'] ?? 'crisis'),
                    'bg' => (string) ($crisisStyle['bg'] ?? '#7c3aed'),
                    'text' => (string) ($crisisStyle['text'] ?? '#ffffff'),
                    'border' => (string) ($crisisStyle['border'] ?? '#7c3aed'),
                    'icon' => $crisisStyle['icon'] ?? null,
                ],
            ],
            'secondary_button' => [
                'id' => 'call_emergency',
                'label' => 'Call '.$emergency.' if you are in immediate danger',
                'variant' => 'danger',
                'action' => 'tel:'.$emergency,
                'style' => [
                    'variant' => (string) ($danger['variant'] ?? 'danger'),
                    'bg' => (string) ($danger['bg'] ?? '#dc2626'),
                    'text' => (string) ($danger['text'] ?? '#ffffff'),
                    'border' => (string) ($danger['border'] ?? '#dc2626'),
                    'icon' => $danger['icon'] ?? null,
                ],
            ],
        ]);
    }

    /**
     * @param  list<string>  $rawTokens
     * @param  list<string>  $chosen
     * @return array{type: 'medical'|'mental_health'|null, hits: list<string>}
     */
    protected function matchingRedFlags(array $rawTokens, array $chosen, ?string $freeText): array
    {
        $haystacks = collect($rawTokens)
            ->merge($chosen)
            ->when($freeText, fn ($c) => $c->push($freeText))
            ->map(fn ($s) => mb_strtolower(trim((string) $s)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $combined = implode(' ', $haystacks);
        $groups = (array) config('chatbot.red_flags', []);

        // Back-compat: flat list treated as medical.
        if ($groups !== [] && array_is_list($groups)) {
            $groups = ['medical' => $groups, 'mental_health' => []];
        }

        $medicalHits = $this->matchFlagNeedles($combined, (array) ($groups['medical'] ?? []));
        $mentalHits = $this->matchFlagNeedles($combined, (array) ($groups['mental_health'] ?? []));

        if ($medicalHits !== []) {
            return ['type' => 'medical', 'hits' => $medicalHits];
        }
        if ($mentalHits !== []) {
            return ['type' => 'mental_health', 'hits' => $mentalHits];
        }

        return ['type' => null, 'hits' => []];
    }

    /**
     * @param  list<string>|array<int, string>  $flags
     * @return list<string>
     */
    protected function matchFlagNeedles(string $combined, array $flags): array
    {
        $hits = [];
        foreach ($flags as $flag) {
            $needle = mb_strtolower(trim((string) $flag));
            if ($needle === '') {
                continue;
            }
            if (str_contains($combined, $needle)) {
                $hits[] = (string) $flag;
            }
        }

        return array_values(array_unique($hits));
    }

    protected function slugLabel(string $label): string
    {
        $slug = Str::slug($label);

        return $slug !== '' ? $slug : 'item';
    }

    /**
     * @return array{id: string, label: string, style: array<string, mixed>}
     */
    protected function option(string $id, string $label, string $variant): array
    {
        $style = (array) config('chatbot.styles.'.$variant, config('chatbot.styles.neutral'));

        return [
            'id' => $id,
            'label' => $label,
            'style' => [
                'variant' => (string) ($style['variant'] ?? $variant),
                'bg' => (string) ($style['bg'] ?? '#f1f5f9'),
                'text' => (string) ($style['text'] ?? '#334155'),
                'border' => (string) ($style['border'] ?? '#cbd5e1'),
                'icon' => $style['icon'] ?? null,
            ],
        ];
    }

    /**
     * @param  list<DiseaseChatbotMessage|array<string, mixed>>  $messages
     * @param  array<string, mixed>  $input
     * @param  list<array<string, mixed>>  $options
     * @return array<string, mixed>
     */
    protected function envelope(
        DiseaseChatbotSession $session,
        array $messages,
        array $input,
        array $options,
        bool $allowBack,
        bool $allowRestart
    ): array {
        $formatted = collect($messages)
            ->map(function ($message) {
                if ($message instanceof DiseaseChatbotMessage) {
                    return $this->formatMessage($message);
                }

                return $message;
            })
            ->values()
            ->all();

        $state = (string) $session->state;
        $input['helper_text'] = $state === DiseaseChatbotSession::STATE_ASK_SYMPTOMS
            ? (string) config('chatbot.symptoms_helper_text')
            : null;

        return [
            'status' => 200,
            'session_id' => (string) $session->id,
            'state' => $state,
            'messages' => $formatted,
            'input' => $input,
            'options' => $options,
            'allow_back' => $allowBack,
            'allow_restart' => $allowRestart,
            'typing_delay_ms' => (int) config('chatbot.typing_delay_ms', 400),
        ];
    }
}
