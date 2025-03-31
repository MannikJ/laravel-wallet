<?php

namespace MannikJ\Laravel\Wallet\Observers;

use MannikJ\Laravel\Wallet\Models\Transaction;

class TransactionObserver
{
    public function creating(Transaction $transaction)
    {
        $transaction->hash = uniqid();
    }

    public function saved(Transaction $transaction)
    {
        $transaction->wallet->calculateBalance(true);
    }

    public function deleted(Transaction $transaction)
    {
        $transaction->wallet->calculateBalance(true);
    }
}
