<?php

namespace App\Providers;

use App\Listeners\RecordQueueWorkerHeartbeat;
use App\Models\PersonalAccessToken;
use App\Services\Queue\QueueDispatchGate;
use App\Support\AdminAccess;
use Illuminate\Mail\MailManager;
use Illuminate\Queue\Events\JobQueueing;
use Illuminate\Queue\Events\Looping;
use Illuminate\Queue\Events\WorkerStopping;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->extend('mail.manager', function (MailManager $manager, $app) {
            return new class($app) extends MailManager {
                protected function configureSmtpTransport(EsmtpTransport $transport, array $config): EsmtpTransport
                {
                    $transport = parent::configureSmtpTransport($transport, $config);

                    $stream = $transport->getStream();

                    if ($stream instanceof SocketStream && ! empty($config['stream'])) {
                        $stream->setStreamOptions($config['stream']);
                    }

                    return $transport;
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        config(['permission.defaults.guard' => 'filament']);

        // Doctor admin web UI (session-based Filament guard).
        Broadcast::routes([
            'middleware' => ['web', 'auth:filament'],
        ]);

        // Mobile app private channels (Sanctum bearer token → POST /api/broadcasting/auth).
        Broadcast::routes([
            'middleware' => ['api', 'auth:sanctum'],
            'prefix' => 'api',
        ]);

        Gate::define('manageCronJobs', fn ($user) => AdminAccess::canManageCron($user));
        Gate::define('manageQueues', fn ($user) => AdminAccess::canManageQueues($user));

        Event::listen(Looping::class, [RecordQueueWorkerHeartbeat::class, 'handleLooping']);
        Event::listen(WorkerStopping::class, [RecordQueueWorkerHeartbeat::class, 'handleStopping']);

        Event::listen(JobQueueing::class, function (): void {
            app(QueueDispatchGate::class)->assertEnabled();
        });
    }
}
