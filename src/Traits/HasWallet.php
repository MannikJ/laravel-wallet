<?php

namespace MannikJ\Laravel\Wallet\Traits;

// use MannikJ\Laravel\Wallet\Models\Transaction;
// use MannikJ\Laravel\Wallet\Models\Wallet;

trait HasWallet
{
    /**
     * Retrieve the balance of this user's wallet
     */
    public function getBalanceAttribute()
    {
        return $this->wallet->refresh()->balance;
    }

    /**
     * Retrieve the wallet of this user
     */
    public function wallet()
    {
        return $this->morphOne(config('wallet.wallet_model'), 'owner')->withDefault();
    }

    /**
     * Retrieve all transactions of this user
     */
    public function walletTransactions()
    {
        return $this->hasManyThrough(
            config('wallet.transaction_model'),
            config('wallet.wallet_model'),
            'owner_id',
            'wallet_id'
        )->whereHas('wallet', function ($query) {
            $query->whereNull('deleted_at');
        })->latest();
    }
}
