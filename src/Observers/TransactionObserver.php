<?php

namespace MannikJ\Laravel\Wallet\Observers;

use MannikJ\Laravel\Wallet\Models\Transaction;

class TransactionObserver
{
    public function creating($transaction)
    {
        $transaction->hash = uniqid();
    }

    public function saved(Transaction $transaction)
    {
        $transaction->wallet->actualBalance(true);
    }

    public function deleted(Transaction $transaction)
    {
        $transaction->wallet->actualBalance(true);
    }
}