<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Safety check on every test setup to ensure tests never touch development data.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureSafeTestingDatabase();
    }

    /**
     * Inspect active database connection and abort immediately if unsafe.
     *
     * @throws RuntimeException
     */
    protected function ensureSafeTestingDatabase(): void
    {
        $defaultConn = (string) config('database.default');
        $configuredDb = (string) config("database.connections.{$defaultConn}.database");
        $activeDb = (string) DB::connection()->getDatabaseName();

        self::validateDatabaseSafety($defaultConn, $configuredDb, $activeDb);
    }

    /**
     * Validate database name against strict safety rules.
     *
     * @throws RuntimeException
     */
    public static function validateDatabaseSafety(?string $connection, ?string $configDb, ?string $activeDb): void
    {
        $approvedDb = 'huniapp_testing';
        $forbiddenDb = 'huniapp';

        if (empty($configDb) || empty($activeDb)) {
            throw new RuntimeException(
                "UNSAFE TEST DATABASE CONFIGURATION: Database name is empty or not resolved for connection '{$connection}'. ".
                "Tests must run against '{$approvedDb}' to protect development data."
            );
        }

        if ($configDb === $forbiddenDb || $activeDb === $forbiddenDb) {
            throw new RuntimeException(
                "CRITICAL DATABASE SAFETY VIOLATION: Test suite attempted to connect to the development database '{$forbiddenDb}'. ".
                'Execution aborted immediately to prevent data corruption.'
            );
        }

        if ($configDb !== $approvedDb || $activeDb !== $approvedDb) {
            throw new RuntimeException(
                "UNAPPROVED TEST DATABASE: Test database is configured as '{$configDb}' / active '{$activeDb}'. ".
                "Tests are strictly restricted to '{$approvedDb}'."
            );
        }
    }
}
