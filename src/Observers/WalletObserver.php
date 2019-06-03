<?php

namespace MannikJ\Laravel\Wallet\Observers;

use MannikJ\Laravel\Wallet\Models\Wallet;
use MannikJ\Laravel\Wallet\Models\Transaction;
use MannikJ\Laravel\Wallet\Jobs\RecalculateWalletBalance;

class WalletObserver
{
    public function saved(Wallet $wallet)
    {
        if ($wallet->getOriginal('balance') != $wallet->balance
            && config('auto_recalculate_balance', false)) {
            $job = new RecalculateWalletBalance($wallet);
            dispatch($job);
        }
    }
}