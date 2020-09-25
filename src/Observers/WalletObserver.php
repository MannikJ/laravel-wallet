<?php

namespace MannikJ\Laravel\Wallet\Observers;

use MannikJ\Laravel\Wallet\Models\Wallet;
use MannikJ\Laravel\Wallet\Models\Transaction;
use MannikJ\Laravel\Wallet\Jobs\RecalculateWalletBalance;

class WalletObserver
{
    public function saved(Wallet $wallet)
    {
        if (
            $wallet->isDirty('balance')
            && \Wallet::autoRecalculationActive()
        ) {
            RecalculateWalletBalance::dispatch($wallet);
        }
    }
}
