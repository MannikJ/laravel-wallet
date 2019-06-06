<?php

namespace MannikJ\Laravel\Wallet\Services;

class Wallet
{
    public function addingTransactionTypes()
    {
        return config('wallet.adding_transaction_types', []);
    }

    public function subtractingTransactionTypes()
    {
        return config('wallet.subtracting_transaction_types', []);
    }

    public function unbiasedTransactionTypes()
    {
        return config('wallet.unbiased_transaction_types', []);
    }

    public function biasedTransactionTypes()
    {
        return array_merge($this->addingTransactionTypes(), $this->subtractingTransactionTypes());
    }

    public function transactionTypes()
    {
        return array_merge($this->biasedTransactionTypes(), $this->unbiasedTransactionTypes());
    }

    public function autoRecalculationActive()
    {
        return config('auto_recalculate_balance', false);
    }
}
