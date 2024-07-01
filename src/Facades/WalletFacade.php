<?php

namespace MannikJ\Laravel\Wallet\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * 
 * @method static mixed config(string $relativePath, mixed $default)
 * @method static array addingTransactionTypes()
 * @method static array subtractingTransactionTypes()
 * @method static array unbiasedTransactionTypes()
 * @method static array biasedTransactionTypes()
 * @method static array transactionTypes()
 * @method static bool autoRecalculationActive()
 * 
 * @see \MannikJ\Laravel\Wallet\Models\Wallet
 * @mixin \MannikJ\Laravel\Wallet\Models\Wallet
 */
class WalletFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'wallet';
    }
}
