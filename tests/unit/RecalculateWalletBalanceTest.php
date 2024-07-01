<?php

namespace MannikJ\Laravel\Wallet\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use MannikJ\Laravel\Wallet\Jobs\RecalculateWalletBalance;
use MannikJ\Laravel\Wallet\Models\Transaction;
use MannikJ\Laravel\Wallet\Tests\Factories\WalletFactory;
use MannikJ\Laravel\Wallet\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RecalculateWalletBalanceTest extends TestCase
{
    use RefreshDatabase;

   #[Test]
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
