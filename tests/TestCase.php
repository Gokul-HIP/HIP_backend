<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private static ?string $refreshDatabaseMigrationSignature = null;

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
}
