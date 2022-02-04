<?php

namespace MannikJ\Laravel\Wallet\Tests\Unit;

use MannikJ\Laravel\Wallet\Models\Wallet;
use MannikJ\Laravel\Wallet\Exceptions\UnacceptedTransactionException;
use MannikJ\Laravel\Wallet\Tests\TestCase;
use MannikJ\Laravel\Wallet\Tests\Models\User;
use MannikJ\Laravel\Wallet\Models\Transaction;
use Illuminate\Support\Collection;
use MannikJ\Laravel\Wallet\Jobs\RecalculateWalletBalance;
use MannikJ\Laravel\Wallet\DebouncedJob;
use MannikJ\Laravel\Wallet\Tests\Factories\WalletFactory;

class RecalculateWalletBalanceTest extends TestCase
{
    /** @test */
    public function dispatch()
    {
        config(['wallet.auto_recalculate_balance' => true]);
        $wallet = WalletFactory::new()->create();
        Transaction::flushEventListeners();
        $transaction = $wallet->transactions()->make(['type' => 'deposit', 'amount' => 10]);
        $transaction->hash = uniqid();
        $transaction->save();
        $this->assertNotEquals(10, $wallet->balance);
        RecalculateWalletBalance::dispatch($wallet);
        $this->assertEquals(10, $wallet->refresh()->balance);
    }

}