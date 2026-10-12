<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Facade;

abstract class TestCase extends BaseTestCase
{
    private static ?string $refreshDatabaseMigrationSignature = null;

    public function createApplication()
    {
        $app = parent::createApplication();

        $connection = (string) config('database.default');
        $name = (string) config('database.connections.'.$connection.'.database');
        $unsafeConnection = in_array($connection, ['mysql', 'mariadb'], true);
        $unsafeName = $name !== ':memory:' && ! str_ends_with($name, '_test');

        if ($unsafeConnection || $unsafeName) {
            if ($app->bound('db')) {
                $app->make('db')->purge();
            }

            Facade::clearResolvedInstances();
            Facade::setFacadeApplication(null);

            throw new \RuntimeException(
                "Refusing to run tests against a non-test database: {$connection}/{$name}"
            );
        }

        return $app;
    }

    protected function setUp(): void
    {
        $this->invalidateSharedInMemorySchemaIfNeeded();

        parent::setUp();
    }

    /**
     * SQLite :memory: reuses one PDO for the whole PHPUnit process. RefreshDatabase only
     * runs migrate:fresh once, so the first class's --path list would starve later classes.
     */
    private function invalidateSharedInMemorySchemaIfNeeded(): void
    {
        if (! in_array(RefreshDatabase::class, class_uses_recursive(static::class), true)) {
            return;
        }

        $signature = md5((string) json_encode($this->migrateFreshUsing()));

        if (self::$refreshDatabaseMigrationSignature !== null
            && self::$refreshDatabaseMigrationSignature !== $signature) {
            RefreshDatabaseState::$migrated = false;
            RefreshDatabaseState::$inMemoryConnections = [];
        }

        self::$refreshDatabaseMigrationSignature = $signature;
    }

    protected function isSafeTestDatabase(): bool
    {
        if (! $this->app) {
            return false;
        }

        return config('database.default') === 'sqlite'
            && config('database.connections.sqlite.database') === ':memory:';
    }
}
