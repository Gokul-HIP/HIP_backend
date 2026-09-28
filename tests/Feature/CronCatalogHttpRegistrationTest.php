<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CronCatalogHttpRegistrationTest extends TestCase
{
    public function createApplication()
    {
        $_ENV['APP_RUNNING_IN_CONSOLE'] = false;
        $_SERVER['APP_RUNNING_IN_CONSOLE'] = false;
        putenv('APP_RUNNING_IN_CONSOLE=false');

        return parent::createApplication();
    }

    protected function tearDown(): void
    {
        putenv('APP_RUNNING_IN_CONSOLE');
        unset($_ENV['APP_RUNNING_IN_CONSOLE'], $_SERVER['APP_RUNNING_IN_CONSOLE']);

        parent::tearDown();
    }

    public function test_catalog_commands_are_registered_during_http_runtime(): void
    {
        $this->assertFalse($this->app->runningInConsole());

        $registered = array_keys(Artisan::all());

        foreach (array_keys(config('cron.commands')) as $command) {
            $this->assertContains(
                $command,
                $registered,
                "Cron catalog command [{$command}] is missing from Artisan during HTTP (Filament) runtime."
            );
        }
    }
}
