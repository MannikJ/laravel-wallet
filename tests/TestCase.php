<?php

namespace MannikJ\Laravel\Wallet\Tests;

use MannikJ\Laravel\Wallet\Facades\WalletFacade;
use MannikJ\Laravel\Wallet\WalletServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom('database/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            WalletServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Wallet' => WalletFacade::class,
        ];
    }
}
