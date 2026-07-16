<?php

namespace App\Modules\MedicineReminder\Services;

use App\Modules\MedicineReminder\Enums\NotificationChannel;
use App\Modules\MedicineReminder\Enums\NotificationLogStatus;
use App\Modules\MedicineReminder\Interfaces\MedicineReminderInterface;
use App\Modules\MedicineReminder\Models\MedicineReminderSchedule;
use App\Modules\MedicineReminder\Notifications\EmailNotificationService;
use App\Modules\MedicineReminder\Notifications\PushNotificationService;
use App\Modules\MedicineReminder\Notifications\SMSNotificationService;
use App\Modules\MedicineReminder\Notifications\WhatsAppNotificationService;

class NotificationDispatcher
{
    public function __construct(
        protected MedicineReminderInterface $repository,
        protected PushNotificationService $push,
        protected WhatsAppNotificationService $whatsApp,
        protected SMSNotificationService $sms,
        protected EmailNotificationService $email,
    ) {}

    /**
     * @param  array<int, string>  $channels
     * @param  array<string, mixed>  $context
     * @return array<int, array{channel: string, success: bool, response: string}>
     */
    public function dispatch(
        MedicineReminderSchedule $schedule,
        array $channels,
        string $resolvedMessage,
        array $context = []
    ): array {
        $results = [];
        $channels = $channels !== [] ? $channels : [NotificationChannel::Push->value];

        foreach ($channels as $channel) {
            $result = match ($channel) {
                NotificationChannel::Push->value => $this->push->send(
                    $context['member_id'] ?? null,
                    'Medicine Reminder',
                    $resolvedMessage,
                    [
                        'schedule_id' => (string) $schedule->id,
                        'prescription_id' => (string) $schedule->prescription_id,
                    ]
                ),
                NotificationChannel::WhatsApp->value => $this->whatsApp->send(
                    $context['patient_mobile'] ?? null,
                    $resolvedMessage,
                    ['schedule_id' => $schedule->id]
                ),
                NotificationChannel::Sms->value => $this->sms->send(
                    $context['patient_mobile'] ?? null,
                    $resolvedMessage,
                    ['schedule_id' => $schedule->id]
                ),
                NotificationChannel::Email->value => $this->email->send(
                    $context['patient_email'] ?? null,
                    'Medicine Reminder',
                    $resolvedMessage,
                    ['schedule_id' => $schedule->id]
                ),
                default => [
                    'success' => false,
                    'response' => "Unsupported channel: {$channel}",
                ],
            };

            $status = $result['success']
                ? NotificationLogStatus::Sent->value
                : NotificationLogStatus::Failed->value;

            $this->repository->createNotificationLog(
                $schedule->id,
                $channel,
                $status,
                $result['response'] ?? null
            );

            $results[] = [
                'channel' => $channel,
                'success' => (bool) ($result['success'] ?? false),
                'response' => (string) ($result['response'] ?? ''),
            ];
        }

        return $results;
    }
}
