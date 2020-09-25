<?php

namespace MannikJ\Laravel\Wallet;

use Illuminate\Support\ServiceProvider;
use MannikJ\Laravel\Wallet\Observers\WalletObserver;
use MannikJ\Laravel\Wallet\Observers\TransactionObserver;
use MannikJ\Laravel\Wallet\Services\Wallet;

class WalletServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('wallet.php'),
            ], 'config');
            $timestamp = date('Y_m_d_His', time());
            $this->publishes([
                __DIR__ . '/../database/migrations/2018_09_13_123456_create_wallet_tables.php' => database_path('migrations/' . $timestamp . '_create_wallet_tables.php'),
            ], 'migrations');
        }
        if (config('wallet.load_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
        config('wallet.wallet_model')::observe(WalletObserver::class);
        config('wallet.transaction_model')::observe(TransactionObserver::class);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'wallet');

        $this->app->singleton('wallet', function () {
            return new Wallet;
        });
    }
}
