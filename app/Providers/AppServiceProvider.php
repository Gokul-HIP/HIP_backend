<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Mail\MailManager;
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
    }
}
