<?php

namespace MannikJ\Laravel\Wallet\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use MannikJ\Laravel\Wallet\WalletServiceProvider;
use MannikJ\Laravel\Wallet\Facades\WalletFacade;

class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->withFactories(__DIR__ . '/factories');
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom('database/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            WalletServiceProvider::class
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Wallet' => WalletFacade::class
        ];
    }
}
