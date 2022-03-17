<?php

namespace MannikJ\Laravel\Wallet\Services;

class Wallet
{
    public function addingTransactionTypes(): array
    {
        return config('wallet.adding_transaction_types', []);
    }

    public function subtractingTransactionTypes(): array
    {
        return config('wallet.subtracting_transaction_types', []);
    }

    public function unbiasedTransactionTypes(): array
    {
        return config('wallet.unbiased_transaction_types', []);
    }

    public function biasedTransactionTypes(): array
    {
        return array_merge($this->addingTransactionTypes(), $this->subtractingTransactionTypes());
    }

    public function transactionTypes(): array
    {
        return array_merge($this->biasedTransactionTypes(), $this->unbiasedTransactionTypes());
    }

    public function autoRecalculationActive(): bool
    {
        return config('wallet.auto_recalculate_balance', false);
    }
}
