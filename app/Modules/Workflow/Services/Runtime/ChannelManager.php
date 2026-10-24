<?php

namespace App\Modules\Workflow\Services\Runtime;

use App\Modules\MedicineReminder\Notifications\EmailNotificationService;
use App\Modules\MedicineReminder\Notifications\PushNotificationService;
use App\Modules\MedicineReminder\Notifications\SMSNotificationService;
use App\Modules\MedicineReminder\Notifications\WhatsAppNotificationService;
use App\Modules\Workflow\Enums\CommunicationStatus;
use App\Modules\Workflow\Models\CommunicationLog;
use App\Modules\Workflow\Models\WorkflowExecution;
use Illuminate\Support\Facades\Log;

class ChannelManager
{
    private const LOGICAL_RECIPIENTS = [
        'patient',
        'doctor',
        'hospital',
        'member',
        'organization',
        'caregiver',
    ];

    public function __construct(
        protected PushNotificationService $push,
        protected WhatsAppNotificationService $whatsApp,
        protected SMSNotificationService $sms,
        protected EmailNotificationService $email,
        protected AiVoiceCallService $aiVoice,
        protected ?WhatsAppMediaPublisher $whatsAppMedia = null,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @param  list<array{filename?: string, content?: string, mime?: string}>  $attachments
     * @param  array<string, mixed>  $options
     * @return array{success: bool, response: string, call_id?: string|null, log_uid?: string|null, wamid?: string|null}
     */
    public function send(
        string $channel,
        ?WorkflowExecution $execution,
        string $nodeId,
        string $message,
        array $context,
        ?string $subject = null,
        ?string $recipient = null,
        array $attachments = [],
        array $options = [],
    ): array {
        if ($this->alreadyDelivered($execution, $nodeId, $channel)) {
            return [
                'success' => true,
                'response' => 'Skipped duplicate WhatsApp send for this workflow node',
            ];
        }

        $log = CommunicationLog::query()->create([
            'workflow_id' => $execution->workflow_id,
            'workflow_execution_id' => $execution->id,
            'node_id' => $nodeId,
            'channel' => $channel,
            'status' => CommunicationStatus::Pending->value,
            'recipient' => $recipient,
            'message' => $message,
            'payload' => $this->safeLogPayload($context),
        ]);

        if ($this->messageHasUnresolvedVariables($message) || $this->messageHasUnresolvedVariables((string) $subject)) {
            $result = [
                'success' => false,
                'response' => 'Message still contains unresolved {{variables}}',
            ];
        } else {
            $result = $this->dispatchToProvider(
                $channel,
                $execution,
                $message,
                $context,
                $subject,
                $recipient,
                $attachments,
                $nodeId,
                $options
            );
        }

        if (
            $execution
            && $execution->trigger_type === 'appointmentBooked'
            && in_array($channel, ['push', 'sendPush'], true)
            && ! ($result['success'] ?? false)
        ) {
            Log::warning('[appointment-booked] Push notification failed', [
                'execution_id' => $execution->id,
                'workflow_id' => $execution->workflow_id,
                'appointment_id' => $context['appointment_id'] ?? null,
                'response' => $result['response'] ?? null,
            ]);
        }

        $log->update([
            'status' => ($result['success'] ?? false)
                ? CommunicationStatus::Sent->value
                : CommunicationStatus::Failed->value,
            'provider_response' => $result['response'] ?? null,
            'sent_at' => ($result['success'] ?? false) ? now() : null,
            'failed_at' => ($result['success'] ?? false) ? null : now(),
        ]);

        if (! ($result['success'] ?? false) && ($context['retry'] ?? false)) {
            $this->retry($log, $channel, $message, $context, $subject, $recipient, $attachments);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $options
     * @return array{success: bool, response: string, call_id?: string|null, log_uid?: string|null, wamid?: string|null}
     */
    protected function dispatchToProvider(
        string $channel,
        ?WorkflowExecution $execution,
        string $message,
        array $context,
        ?string $subject,
        ?string $recipient,
        array $attachments = [],
        string $nodeId = '',
        array $options = [],
    ): array {
        $channelType = $this->normalizeChannelType($channel);
        $resolvedRecipient = $this->resolveRecipient($recipient, $channelType, $context);

        $providerPayload = $this->providerPayload($execution, $context, $nodeId);

        if (in_array($channel, ['push', 'sendPush'], true)) {
            $providerPayload = $this->withRewardPointsDeepLink($execution, $context, $providerPayload);
        }

        return match ($channel) {
            'push', 'sendPush' => $this->push->send(
                $context['member_id'] ?? null,
                $subject ?? 'Notification',
                $message,
                $providerPayload
            ),
            'whatsapp', 'sendWhatsApp' => $this->dispatchWhatsApp(
                $resolvedRecipient,
                $message,
                $providerPayload,
                $attachments,
                $options
            ),
            'sms', 'sendSMS' => $this->sms->send(
                $resolvedRecipient,
                $message,
                $providerPayload
            ),
            'email', 'sendEmail' => $this->email->send(
                $resolvedRecipient,
                $subject ?? 'Notification',
                $message,
                $providerPayload,
                $attachments
            ),
            'ai_voice', 'sendAiVoice', 'voice' => $this->aiVoice->send(
                $resolvedRecipient,
                $message,
                array_merge($providerPayload, is_array($context['meta'] ?? null) ? $context['meta'] : [])
            ),
            default => [
                'success' => false,
                'response' => "Unsupported channel: {$channel}",
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function resolveRecipient(?string $recipient, string $channelType, array $context): ?string
    {
        if ($recipient === null || trim($recipient) === '') {
            return $this->defaultRecipientForChannel($channelType, $context);
        }

        $trimmed = trim($recipient);

        if ($this->isConcreteContact($trimmed, $channelType)) {
            return $trimmed;
        }

        $logical = strtolower($trimmed);

        if (! in_array($logical, self::LOGICAL_RECIPIENTS, true)) {
            return $trimmed;
        }

        $resolved = match ($logical) {
            'patient' => $this->resolvePatientContact($channelType, $context),
            'doctor' => $this->resolveDoctorContact($channelType, $context),
            'hospital' => $this->resolveHospitalContact($channelType, $context),
            'member' => $this->resolveMemberContact($channelType, $context),
            'organization' => $this->resolveOrganizationContact($channelType, $context),
            'caregiver' => $this->resolveCaregiverContact($channelType, $context),
            default => null,
        };

        if (filled($resolved)) {
            return $resolved;
        }

        if ($logical === 'caregiver') {
            return null;
        }

        return $this->defaultRecipientForChannel($channelType, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function providerPayload(?WorkflowExecution $execution, array $context, string $nodeId = ''): array
    {
        $payload = is_array($context['meta'] ?? null) ? $context['meta'] : [];

        if ($nodeId !== '') {
            $payload['node_id'] = $nodeId;
        }

        if ($execution) {
            $payload['workflow_id'] = $execution->workflow_id;
            $payload['workflow_execution_id'] = $execution->id;
            $payload['execution_id'] = $execution->id;
        }
        $payload['source'] = $payload['source'] ?? 'hospital_automation';

        if (isset($context['appointment_id'])) {
            $payload['appointment_id'] = (string) $context['appointment_id'];
        }

        if (isset($context['patient_id'])) {
            $payload['patient_id'] = (string) $context['patient_id'];
        }

        if (isset($context['doctor_id'])) {
            $payload['doctor_id'] = (string) $context['doctor_id'];
        }

        if (isset($context['hospital_id'])) {
            $payload['hospital_id'] = (string) $context['hospital_id'];
        }

        if (isset($context['invoice_id'])) {
            $payload['invoice_id'] = (string) $context['invoice_id'];
        }

        if (isset($context['prescription_id'])) {
            $payload['prescription_id'] = (string) $context['prescription_id'];
        }

        if (($execution?->trigger_type) === 'appointmentBooked') {
            $payload['notification_type'] = 'appointment_booked';
            $payload['type'] = $payload['type'] ?? 'appointment_booked';
        }

        return $payload;
    }

    /**
     * The retired coins-earned push opened the app at /coin-details.
     * Reward Points Updated reuses this push path and sends the same data keys.
     * A non-empty url, route, or screen already present in context or meta is kept.
     *
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function withRewardPointsDeepLink(?WorkflowExecution $execution, array $context, array $payload): array
    {
        if (($execution?->trigger_type) !== 'rewardPointsUpdated') {
            return $payload;
        }

        $meta = is_array($context['meta'] ?? null) ? $context['meta'] : [];

        // screen/url/route are the navigation fields. type, notification_type,
        // and source describe the event. The inbox uses type for presentation
        // and returns url/route separately.
        $payload['screen'] = $this->authoredPushValue($meta, $context, 'screen') ?? 'coin_details';
        $payload['url'] = $this->authoredPushValue($meta, $context, 'url') ?? '/coin-details';
        $payload['route'] = $this->authoredPushValue($meta, $context, 'route') ?? '/coin-details';

        if ($this->isLegacyCoinsEarnedPayment($context)) {
            $payload['type'] = $this->authoredPushValue($meta, $context, 'type') ?? 'coins_earned';
            $payload['notification_type'] = $this->authoredPushValue($meta, $context, 'notification_type') ?? 'coins_earned';
            $payload['source'] = $this->authoredPushValue($meta, $context, 'source') ?? 'payment';
            $payload['coins_earned'] = (string) $context['coins_earned'];

            $this->applyLegacyCoinNavigationContext($meta, $context, $payload);

            if (isset($context['paid_amount']) && $context['paid_amount'] !== null && $context['paid_amount'] !== '') {
                $payload['paid_amount'] = number_format((float) $context['paid_amount'], 2, '.', '');
            }

            if (! empty($context['service_types'])) {
                $payload['service_types'] = $context['service_types'];
            }

            return $payload;
        }

        // A missing type is later replaced with medicine_reminder. Use the
        // event's own change_type and reward_source instead of the payment labels.
        $changeType = trim((string) ($context['change_type'] ?? ''));
        $rewardSource = trim((string) ($context['reward_source'] ?? ''));
        $payload['type'] = $this->authoredPushValue($meta, $context, 'type') ?? ($changeType !== '' ? $changeType : null);
        $payload['notification_type'] = $this->authoredPushValue($meta, $context, 'notification_type') ?? ($changeType !== '' ? $changeType : null);
        $payload['source'] = $this->authoredPushValue($meta, $context, 'source') ?? ($rewardSource !== '' ? $rewardSource : ($payload['source'] ?? 'hospital_automation'));
        foreach (['type', 'notification_type'] as $key) {
            if ($payload[$key] === null) {
                unset($payload[$key]);
            }
        }
        unset($payload['coins_earned']);

        return $payload;
    }

    /**
     * The retired push existed only when a payment had awarded coins.
     *
     * @param  array<string, mixed>  $context
     */
    private function isLegacyCoinsEarnedPayment(array $context): bool
    {
        $award = $context['coins_earned'] ?? null;
        $source = (string) ($context['reward_source'] ?? '');

        return ($context['change_type'] ?? null) === 'earned'
            && is_numeric($award)
            && (int) $award > 0
            && in_array($source, [
                'payment',
                'appointment_payment',
                'package_booking',
                'pharmacy_payment',
                'second_opinion_payment',
            ], true);
    }

    /**
     * Same invoice-to-screen context the old coins push merged in before the route keys.
     *
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $payload
     */
    private function applyLegacyCoinNavigationContext(array $meta, array $context, array &$payload): void
    {
        $bookingSource = (string) ($context['booking_source'] ?? '');
        $bookingId = $context['booking_id'] ?? null;
        $hasBooking = $bookingId !== null && $bookingId !== '';

        if ($bookingSource === 'doctor_booking' && $hasBooking) {
            $payload['context'] = $this->authoredPushValue($meta, $context, 'context') ?? 'appointment_booking';
            $payload['context_id'] = $this->authoredPushValue($meta, $context, 'context_id') ?? (string) $bookingId;
            $payload['appointment_id'] = $payload['appointment_id'] ?? (string) $bookingId;

            return;
        }

        if ($bookingSource === 'diagnostic_test_booking' && $hasBooking) {
            $payload['context'] = $this->authoredPushValue($meta, $context, 'context') ?? 'diagnostic_package_booking';
            $payload['context_id'] = $this->authoredPushValue($meta, $context, 'context_id') ?? (string) $bookingId;

            return;
        }

        if ($bookingSource === 'second_opinion' && $hasBooking) {
            $payload['context'] = $this->authoredPushValue($meta, $context, 'context') ?? 'second_opinion_booking';
            $payload['context_id'] = $this->authoredPushValue($meta, $context, 'context_id') ?? (string) $bookingId;

            return;
        }

        $invoiceId = $context['invoice_id'] ?? null;
        if ($invoiceId !== null && $invoiceId !== '') {
            $payload['context'] = $this->authoredPushValue($meta, $context, 'context') ?? 'invoice';
            $payload['context_id'] = $this->authoredPushValue($meta, $context, 'context_id') ?? (string) $invoiceId;
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $context
     */
    private function authoredPushValue(array $meta, array $context, string $key): ?string
    {
        foreach ([$meta[$key] ?? null, $context[$key] ?? null] as $value) {
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function resolveCaregiverContact(string $channelType, array $context): ?string
    {
        if ($channelType === 'email') {
            $email = $context['caregiver_email']
                ?? data_get($context, 'caregiver.email')
                ?? data_get($context, 'patient.guardian_email');

            return filled($email) ? (string) $email : null;
        }

        $mobile = $context['caregiver_contact']
            ?? $context['guardian_mobile']
            ?? $context['parent_mobile']
            ?? data_get($context, 'caregiver.mobile')
            ?? data_get($context, 'caregiver.mobile_number')
            ?? data_get($context, 'patient.caregiver_contact')
            ?? data_get($context, 'patient.guardian_mobile')
            ?? data_get($context, 'patient.parent_mobile');

        return filled($mobile) ? (string) $mobile : null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function defaultRecipientForChannel(string $channelType, array $context): ?string
    {
        return match ($channelType) {
            'email' => $context['patient_email'] ?? null,
            'sms', 'whatsapp', 'ai_voice' => $context['patient_mobile'] ?? null,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function resolvePatientContact(string $channelType, array $context): ?string
    {
        if ($channelType === 'email') {
            if (filled($context['patient_email'] ?? null)) {
                return (string) $context['patient_email'];
            }

            $patient = $context['patient'] ?? null;

            return is_object($patient) ? ($patient->email ?? null) : null;
        }

        if (filled($context['patient_mobile'] ?? null)) {
            return (string) $context['patient_mobile'];
        }

        $patient = $context['patient'] ?? null;

        if (is_array($patient)) {
            $mobile = $patient['mobile'] ?? $patient['mobile_number'] ?? $patient['mobile_num'] ?? null;

            return filled($mobile) ? (string) $mobile : null;
        }

        return is_object($patient) ? ($patient->mobile ?? $patient->mobile_number ?? null) : null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function resolveDoctorContact(string $channelType, array $context): ?string
    {
        $doctor = $context['doctor'] ?? null;

        if (! is_object($doctor)) {
            return null;
        }

        if ($channelType === 'email') {
            return $doctor->email ?? null;
        }

        return $doctor->mobile ?? $doctor->mobile_number ?? null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function resolveHospitalContact(string $channelType, array $context): ?string
    {
        $hospital = $context['hospital'] ?? null;

        if (! is_object($hospital)) {
            return null;
        }

        if ($channelType === 'email') {
            return $hospital->admin_email ?? null;
        }

        return $hospital->admin_contact
            ?? $hospital->admin_emergency_contact
            ?? $hospital->ambulance_number
            ?? null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function resolveMemberContact(string $channelType, array $context): ?string
    {
        $member = $context['member'] ?? null;

        if (is_object($member)) {
            if ($channelType === 'email') {
                return $member->email ?? null;
            }

            return $member->mobile_num ?? $member->mobile ?? null;
        }

        if ($channelType === 'email') {
            return $context['member_email'] ?? null;
        }

        return $context['member_mobile'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function resolveOrganizationContact(string $channelType, array $context): ?string
    {
        if ($channelType === 'email') {
            if (filled($context['organization_email'] ?? null)) {
                return (string) $context['organization_email'];
            }

            $hospital = $context['hospital'] ?? null;

            return is_object($hospital) ? ($hospital->admin_email ?? null) : null;
        }

        if (filled($context['organization_mobile'] ?? null)) {
            return (string) $context['organization_mobile'];
        }

        $hospital = $context['hospital'] ?? null;

        return is_object($hospital)
            ? ($hospital->admin_contact ?? $hospital->admin_emergency_contact ?? null)
            : null;
    }

    protected function normalizeChannelType(string $channel): string
    {
        return match ($channel) {
            'email', 'sendEmail' => 'email',
            'sms', 'sendSMS' => 'sms',
            'whatsapp', 'sendWhatsApp' => 'whatsapp',
            'push', 'sendPush' => 'push',
            'ai_voice', 'sendAiVoice', 'voice' => 'ai_voice',
            default => $channel,
        };
    }

    protected function isConcreteContact(string $value, string $channelType): bool
    {
        if ($channelType === 'email') {
            return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        }

        if (in_array($channelType, ['sms', 'whatsapp', 'ai_voice'], true)) {
            $digits = preg_replace('/\D+/', '', $value);

            return is_string($digits) && strlen($digits) >= 7;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function retry(
        CommunicationLog $log,
        string $channel,
        string $message,
        array $context,
        ?string $subject,
        ?string $recipient,
        array $attachments = [],
    ): void {
        $maxRetries = (int) ($context['max_retries'] ?? 3);

        if ($log->retry_count >= $maxRetries) {
            return;
        }

        $log->update([
            'status' => CommunicationStatus::Retry->value,
            'retry_count' => $log->retry_count + 1,
        ]);

        Log::info('Channel delivery scheduled for retry', [
            'communication_log_id' => $log->id,
            'channel' => $channel,
            'retry_count' => $log->retry_count,
        ]);

        $fallback = $context['fallback_channel'] ?? null;

        if (is_string($fallback) && $fallback !== '' && $fallback !== $channel) {
            $this->dispatchToProvider($fallback, null, $message, $context, $subject, $recipient, $attachments, $log->node_id ?? '', []);
        }
    }

    /**
     * @param  array<string, mixed>  $providerPayload
     * @param  list<array{filename?: string, content?: string, mime?: string}>  $attachments
     * @param  array<string, mixed>  $options
     * @return array{success: bool, response: string, log_uid?: string|null, wamid?: string|null}
     */
    protected function dispatchWhatsApp(
        ?string $resolvedRecipient,
        string $message,
        array $providerPayload,
        array $attachments,
        array $options,
    ): array {
        $delivery = $options;

        Log::info('Send WhatsApp dispatching to provider', [
            'workflow_execution_id' => $providerPayload['workflow_execution_id'] ?? null,
            'workflow_id' => $providerPayload['workflow_id'] ?? null,
            'node_id' => $providerPayload['node_id'] ?? null,
            'node_type' => 'sendWhatsApp',
            'recipient_masked' => $this->maskPhone($resolvedRecipient),
            'provider_class' => $this->whatsApp::class,
            'client_class' => method_exists($this->whatsApp, 'clientClass')
                ? $this->whatsApp->clientClass()
                : null,
        ]);

        if ($attachments !== []) {
            $last = null;
            foreach ($attachments as $attachment) {
                try {
                    $publisher = $this->whatsAppMedia ?? app(WhatsAppMediaPublisher::class);
                    $url = $publisher->publicPdfUrl($attachment, [
                        'workflow_execution_id' => $providerPayload['workflow_execution_id'] ?? null,
                        'invoice_id' => $attachment['invoice_id'] ?? $providerPayload['invoice_id'] ?? null,
                        'prescription_id' => $attachment['prescription_id']
                            ?? $providerPayload['prescription_id']
                            ?? null,
                    ]);
                } catch (\Throwable $e) {
                    return [
                        'success' => false,
                        'response' => $e->getMessage(),
                    ];
                }

                $mediaDelivery = $delivery;
                $mediaDelivery['mode'] = 'media';
                $mediaDelivery['media_type'] = 'document';
                $mediaDelivery['media_url'] = $url;
                $mediaDelivery['caption'] = $message;

                $last = $this->whatsApp->send($resolvedRecipient, $message, $providerPayload, $mediaDelivery);
                if (! ($last['success'] ?? false)) {
                    return $last;
                }
            }

            return $last ?? [
                'success' => false,
                'response' => 'WhatsApp media send returned no result',
            ];
        }

        return $this->whatsApp->send($resolvedRecipient, $message, $providerPayload, $delivery);
    }

    protected function messageHasUnresolvedVariables(string $text): bool
    {
        return (bool) preg_match('/\{\{\s*[a-zA-Z0-9_.]+\s*\}\}/', $text);
    }

    protected function alreadyDelivered(?WorkflowExecution $execution, string $nodeId, string $channel): bool
    {
        if (! $execution || $nodeId === '') {
            return false;
        }

        if (! in_array($channel, ['whatsapp', 'sendWhatsApp'], true)) {
            return false;
        }

        return CommunicationLog::query()
            ->where('workflow_execution_id', $execution->id)
            ->where('node_id', $nodeId)
            ->whereIn('channel', ['whatsapp', 'sendWhatsApp'])
            ->where('status', CommunicationStatus::Sent->value)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function safeLogPayload(array $context): array
    {
        $copy = $context;
        unset($copy['token'], $copy['bearer'], $copy['password'], $copy['authorization']);

        foreach (array_keys($copy) as $key) {
            if (is_string($key) && preg_match('/token|secret|password|authorization|bearer/i', $key)) {
                unset($copy[$key]);
            }
        }

        return $copy;
    }

    protected function maskPhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) < 4) {
            return '****';
        }

        return str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -4);
    }
}
